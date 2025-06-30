<?php
session_start();

$host = 'localhost';
$dbname = 'final-tnhs-sis';
$username = 'root';
$password = '';

function getDBConnection() {
    global $host, $dbname, $username, $password;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDBConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    // Initialize response array
    $response = ['errors' => []];

    // Required fields validation
    $required_fields = [
        'school_year', 'grade_level', 'lrn_status', 'returning_status',
        'last_name', 'first_name', 'birthdate', 'sex', 'age',
        'current_house_no', 'current_barangay'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $response['errors'][] = "Please fill in $field.";
        }
    }

    // Specific validations
    if ($_POST['ip_community'] === 'yes' && empty($_POST['ip_specify'])) {
        $response['errors'][] = 'Please specify the Indigenous Community.';
    }

    if ($_POST['four_ps'] === 'yes' && empty($_POST['four_ps_id'])) {
        $response['errors'][] = 'Please provide the 4Ps Household ID Number.';
    }

    if ($_POST['disability'] === 'yes' && empty($_POST['disability_type'])) {
        $response['errors'][] = 'Please select at least one disability type.';
    }

    if ($_POST['same_address'] === 'no' && (empty($_POST['permanent_house_no']) || empty($_POST['permanent_barangay']))) {
        $response['errors'][] = 'Please complete required permanent address fields.';
    }

    if ($_POST['returning_transfer_status'] === 'yes' && (empty($_POST['last_grade_level']) || empty($_POST['last_school_attended']) || empty($_POST['last_school_year']) || empty($_POST['school_id']))) {
        $response['errors'][] = 'Please complete all fields for Returning Learner/Transfer.';
    }

    if ($_POST['senior_high_status'] === 'yes' && (empty($_POST['semester']) || empty($_POST['track']) || empty($_POST['strand']))) {
        $response['errors'][] = 'Please complete all Senior High School fields.';
    }

    if (empty($_POST['modular_print']) && empty($_POST['modular_digital']) && empty($_POST['online']) && empty($_POST['radio_based']) && empty($_POST['educational_tv']) && empty($_POST['blended']) && empty($_POST['homeschooling'])) {
        $response['errors'][] = 'Please select at least one distance learning modality.';
    }

    // Signature file handling
    $signature_path = null;
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $signature_path = $upload_dir . time() . '_' . basename($_FILES['signature']['name']);
        if (!move_uploaded_file($_FILES['signature']['tmp_name'], $signature_path)) {
            $response['errors'][] = 'Failed to upload signature file.';
        }
    } else {
        $response['errors'][] = 'Please upload a signature file.';
    }

    if (!empty($_POST['birthdate']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['birthdate'])) {
        $response['errors'][] = 'Please select a valid birthdate.';
    }

    if (!empty($_POST['certification_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['certification_date'])) {
        $response['errors'][] = 'Please select a valid certification date.';
    }

    // If there are errors, return them
    if (!empty($response['errors'])) {
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get sex_id
        $stmt = $pdo->prepare("SELECT sex_id FROM sexes WHERE sex_name = :sex_name");
        $stmt->execute(['sex_name' => $_POST['sex']]);
        $sex_id = $stmt->fetchColumn();
        if (!$sex_id) {
            throw new Exception('Invalid sex value.');
        }

        // Get tongue_id
        $tongue_id = null;
        if (!empty($_POST['mother_tongue'])) {
            $stmt = $pdo->prepare("SELECT tongue_id FROM mother_tongues WHERE tongue_name = :tongue_name");
            $stmt->execute(['tongue_name' => $_POST['mother_tongue']]);
            $tongue_id = $stmt->fetchColumn();
            if (!$tongue_id) {
                throw new Exception('Invalid mother tongue value.');
            }
        }

        // Get place_of_birth_id
        $place_of_birth_id = null;
        if (!empty($_POST['place_of_birth'])) {
            $stmt = $pdo->prepare("SELECT municipality_id FROM municipalities WHERE municipality_name = :municipality_name");
            $stmt->execute(['municipality_name' => $_POST['place_of_birth']]);
            $place_of_birth_id = $stmt->fetchColumn();
            if (!$place_of_birth_id) {
                throw new Exception('Invalid place of birth value.');
            }
        }

        // Get current_barangay_id
        $stmt = $pdo->prepare("SELECT barangay_id FROM barangays WHERE barangay_name = :barangay_name");
        $stmt->execute(['barangay_name' => $_POST['current_barangay']]);
        $current_barangay_id = $stmt->fetchColumn();
        if (!$current_barangay_id) {
            throw new Exception('Invalid current barangay value.');
        }

        // Get permanent_barangay_id if applicable
        $permanent_barangay_id = null;
        if ($_POST['same_address'] === 'no' && !empty($_POST['permanent_barangay'])) {
            $stmt = $pdo->prepare("SELECT barangay_id FROM barangays WHERE barangay_name = :barangay_name");
            $stmt->execute(['barangay_name' => $_POST['permanent_barangay']]);
            $permanent_barangay_id = $stmt->fetchColumn();
            if (!$permanent_barangay_id) {
                throw new Exception('Invalid permanent barangay value.');
            }
        }

        // Insert into enrolled_students table
        $stmt = $pdo->prepare("
            INSERT INTO enrolled_students (
                psa_birth_cert, lrn, last_name, first_name, middle_name, extension_name,
                birthdate, sex_id, age, place_of_birth_id, tongue_id, ip_community, ip_specify,
                four_ps, four_ps_id
            ) VALUES (
                :psa_birth_cert, :lrn, :last_name, :first_name, :middle_name, :extension_name,
                :birthdate, :sex_id, :age, :place_of_birth_id, :tongue_id, :ip_community, :ip_specify,
                :four_ps, :four_ps_id
            )
        ");
        $stmt->execute([
            'psa_birth_cert' => $_POST['psa_birth_cert'] ?? null,
            'lrn' => $_POST['lrn'] ?? null,
            'last_name' => $_POST['last_name'],
            'first_name' => $_POST['first_name'],
            'middle_name' => $_POST['middle_name'] ?? null,
            'extension_name' => $_POST['extension_name'] ?? null,
            'birthdate' => $_POST['birthdate'],
            'sex_id' => $sex_id,
            'age' => (int)$_POST['age'],
            'place_of_birth_id' => $place_of_birth_id,
            'tongue_id' => $tongue_id,
            'ip_community' => $_POST['ip_community'] ?? null,
            'ip_specify' => $_POST['ip_specify'] ?? null,
            'four_ps' => $_POST['four_ps'] ?? null,
            'four_ps_id' => $_POST['four_ps_id'] ?? null
        ]);
        $student_id = $pdo->lastInsertId();

        // Get track_id and strand_id
        $track_id = null;
        $strand_id = null;
        if (!empty($_POST['track']) && !empty($_POST['strand'])) {
            $stmt = $pdo->prepare("SELECT track_id FROM tracks WHERE track_name = :track_name");
            $stmt->execute(['track_name' => $_POST['track']]);
            $track_id = $stmt->fetchColumn();
            if (!$track_id) {
                throw new Exception('Invalid track value.');
            }

            $stmt = $pdo->prepare("SELECT strand_id FROM strands WHERE track_id = :track_id AND strand_name = :strand_name");
            $stmt->execute(['track_id' => $track_id, 'strand_name' => $_POST['strand']]);
            $strand_id = $stmt->fetchColumn();
            if (!$strand_id) {
                throw new Exception('Invalid strand value.');
            }
        }

        // Insert into enrollments table
        $stmt = $pdo->prepare("
            INSERT INTO enrollments (
                student_id, school_year, grade_level, lrn_status, returning_status,
                returning_transfer_status, last_grade_level, last_school_year, last_school_attended,
                school_id, semester, track_id, strand_id, signature_path, certification_date
            ) VALUES (
                :student_id, :school_year, :grade_level, :lrn_status, :returning_status,
                :returning_transfer_status, :last_grade_level, :last_school_year, :last_school_attended,
                :school_id, :semester, :track_id, :strand_id, :signature_path, :certification_date
            )
        ");
        $stmt->execute([
            'student_id' => $student_id,
            'school_year' => $_POST['school_year'],
            'grade_level' => $_POST['grade_level'],
            'lrn_status' => $_POST['lrn_status'],
            'returning_status' => $_POST['returning_status'],
            'returning_transfer_status' => $_POST['returning_transfer_status'] ?? null,
            'last_grade_level' => $_POST['last_grade_level'] ?? null,
            'last_school_year' => $_POST['last_school_year'] ?? null,
            'last_school_attended' => $_POST['last_school_attended'] ?? null,
            'school_id' => $_POST['school_id'] ?? null,
            'semester' => $_POST['semester'] ?? null,
            'track_id' => $track_id,
            'strand_id' => $strand_id,
            'signature_path' => $signature_path,
            'certification_date' => $_POST['certification_date'] ?? null
        ]);
        $enrollment_id = $pdo->lastInsertId();

        // Insert current address
        $stmt = $pdo->prepare("
            INSERT INTO addresses (
                student_id, address_type, house_no, sitio_street, barangay_id, same_address
            ) VALUES (
                :student_id, 'current', :house_no, :sitio_street, :barangay_id, :same_address
            )
        ");
        $stmt->execute([
            'student_id' => $student_id,
            'house_no' => $_POST['current_house_no'],
            'sitio_street' => $_POST['current_sitio_street'] ?? null,
            'barangay_id' => $current_barangay_id,
            'same_address' => $_POST['same_address'] ?? null
        ]);

        // Insert permanent address if not same as current
        if ($_POST['same_address'] === 'no') {
            $stmt = $pdo->prepare("
                INSERT INTO addresses (
                    student_id, address_type, house_no, sitio_street, barangay_id
                ) VALUES (
                    :student_id, 'permanent', :house_no, :sitio_street, :barangay_id
                )
            ");
            $stmt->execute([
                'student_id' => $student_id,
                'house_no' => $_POST['permanent_house_no'] ?? null,
                'sitio_street' => $_POST['permanent_sitio_street'] ?? null,
                'barangay_id' => $permanent_barangay_id
            ]);
        }

        // Insert guardians
        $guardians = [
            ['type' => 'father', 'last_name' => $_POST['father_last_name'] ?? null, 'first_name' => $_POST['father_first_name'] ?? null, 'middle_name' => $_POST['father_middle_name'] ?? null, 'contact' => $_POST['father_contact'] ?? null],
            ['type' => 'mother', 'last_name' => $_POST['mother_last_name'] ?? null, 'first_name' => $_POST['mother_first_name'] ?? null, 'middle_name' => $_POST['mother_middle_name'] ?? null, 'contact' => $_POST['mother_contact'] ?? null],
            ['type' => 'legal_guardian', 'last_name' => $_POST['guardian_last_name'] ?? null, 'first_name' => $_POST['guardian_first_name'] ?? null, 'middle_name' => $_POST['guardian_middle_name'] ?? null, 'contact' => $_POST['guardian_contact'] ?? null]
        ];
        $stmt = $pdo->prepare("
            INSERT INTO guardians (
                student_id, guardian_type, last_name, first_name, middle_name, contact
            ) VALUES (
                :student_id, :guardian_type, :last_name, :first_name, :middle_name, :contact
            )
        ");
        foreach ($guardians as $guardian) {
            if ($guardian['last_name'] || $guardian['first_name'] || $guardian['middle_name'] || $guardian['contact']) {
                $stmt->execute([
                    'student_id' => $student_id,
                    'guardian_type' => $guardian['type'],
                    'last_name' => $guardian['last_name'],
                    'first_name' => $guardian['first_name'],
                    'middle_name' => $guardian['middle_name'],
                    'contact' => $guardian['contact']
                ]);
            }
        }

        // Insert disabilities
        if ($_POST['disability'] === 'yes' && !empty($_POST['disability_type'])) {
            $stmt = $pdo->prepare("
                INSERT INTO disabilities (student_id, disability_type) VALUES (:student_id, :disability_type)
            ");
            foreach ($_POST['disability_type'] as $type) {
                $stmt->execute([
                    'student_id' => $student_id,
                    'disability_type' => $type
                ]);
            }
        }

        // Insert learning modalities
        $modalities = [
            'modular_print' => $_POST['modular_print'] ?? null,
            'modular_digital' => $_POST['modular_digital'] ?? null,
            'online' => $_POST['online'] ?? null,
            'radio_based' => $_POST['radio_based'] ?? null,
            'educational_tv' => $_POST['educational_tv'] ?? null,
            'blended' => $_POST['blended'] ?? null,
            'homeschooling' => $_POST['homeschooling'] ?? null
        ];
        $stmt = $pdo->prepare("
            INSERT INTO learning_modalities (enrollment_id, modality_type) VALUES (:enrollment_id, :modality_type)
        ");
        foreach ($modalities as $type => $value) {
            if ($value) {
                $stmt->execute([
                    'enrollment_id' => $enrollment_id,
                    'modality_type' => $type
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => 'Enrollment form submitted successfully!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save enrollment data']);
    }
}
?>