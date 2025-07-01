<?php
session_start();

$host = 'localhost';
$dbname = 'improved-tnhs-sis';
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
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
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

    // Check if enrollment table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'enrollment'");
        if ($stmt->rowCount() == 0) {
            throw new Exception('Enrollment table does not exist in the database');
        }
    } catch (Exception $e) {
        error_log("Table check error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to verify database schema: ' . $e->getMessage()]);
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
    if (!empty($_POST['ip_community']) && $_POST['ip_community'] === 'yes' && empty($_POST['ip_specify'])) {
        $response['errors'][] = 'Please specify the Indigenous Community.';
    }

    if (!empty($_POST['four_ps']) && $_POST['four_ps'] === 'yes' && empty($_POST['four_ps_id'])) {
        $response['errors'][] = 'Please provide the 4Ps Household ID Number.';
    }

    if (!empty($_POST['disability']) && $_POST['disability'] === 'yes' && empty($_POST['disability_type'])) {
        $response['errors'][] = 'Please select at least one disability type.';
    }

    if (!empty($_POST['same_address']) && $_POST['same_address'] === 'no' && (empty($_POST['permanent_house_no']) || empty($_POST['permanent_barangay']))) {
        $response['errors'][] = 'Please complete required permanent address fields.';
    }

    if (!empty($_POST['returning_transfer_status']) && $_POST['returning_transfer_status'] === 'yes' && (empty($_POST['last_grade_level']) || empty($_POST['last_school_attended']) || empty($_POST['last_school_year']) || empty($_POST['school_id']))) {
        $response['errors'][] = 'Please complete all fields for Returning Learner/Transfer.';
    }

    if (!empty($_POST['senior_high_status']) && $_POST['senior_high_status'] === 'yes' && (empty($_POST['semester']) || empty($_POST['track']) || empty($_POST['strand']))) {
        $response['errors'][] = 'Please complete all Senior High School fields.';
    }

    if (empty($_POST['modular_print']) && empty($_POST['modular_digital']) && empty($_POST['online']) && empty($_POST['radio_based']) && empty($_POST['educational_tv']) && empty($_POST['blended']) && empty($_POST['homeschooling'])) {
        $response['errors'][] = 'Please select at least one distance learning modality.';
    }

    // Signature file handling
    $signature_path = null;
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'Uploads/';
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

    // Validate LRN if lrn_status is 'yes'
    if ($_POST['lrn_status'] === 'yes' && empty($_POST['lrn'])) {
        $response['errors'][] = 'Please provide a Learner Reference Number (LRN) when LRN status is Yes.';
    }

    // If there are errors, return them
    if (!empty($response['errors'])) {
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");

        // Get sex_id
        $stmt = $pdo->prepare("SELECT sex_id FROM sex WHERE sex_name = :sex_name");
        $stmt->execute(['sex_name' => $_POST['sex']]);
        $sex_id = $stmt->fetchColumn();
        if (!$sex_id) {
            throw new Exception('Invalid sex value: ' . $_POST['sex']);
        }

        // Get mother_tongue_id
        $mother_tongue_id = null;
        if (!empty($_POST['mother_tongue'])) {
            $stmt = $pdo->prepare("SELECT tongue_id FROM mother_tongue WHERE tongue_name = :tongue_name");
            $stmt->execute(['tongue_name' => $_POST['mother_tongue']]);
            $mother_tongue_id = $stmt->fetchColumn();
            if (!$mother_tongue_id) {
                throw new Exception('Invalid mother tongue value: ' . $_POST['mother_tongue']);
            }
        }

        // Get place_of_birth_id
        $place_of_birth_id = null;
        if (!empty($_POST['place_of_birth'])) {
            $stmt = $pdo->prepare("SELECT municipality_id FROM municipality WHERE municipality_name = :municipality_name");
            $stmt->execute(['municipality_name' => $_POST['place_of_birth']]);
            $place_of_birth_id = $stmt->fetchColumn();
            if (!$place_of_birth_id) {
                throw new Exception('Invalid place of birth value: ' . $_POST['place_of_birth']);
            }
        }

        // Get current_barangay_id
        $stmt = $pdo->prepare("SELECT barangay_id FROM barangay WHERE barangay_name = :barangay_name");
        $stmt->execute(['barangay_name' => $_POST['current_barangay']]);
        $current_barangay_id = $stmt->fetchColumn();
        if (!$current_barangay_id) {
            throw new Exception('Invalid current barangay value: ' . $_POST['current_barangay']);
        }

        // Get permanent_barangay_id if applicable
        $permanent_barangay_id = null;
        if (!empty($_POST['same_address']) && $_POST['same_address'] === 'no' && !empty($_POST['permanent_barangay'])) {
            $stmt = $pdo->prepare("SELECT barangay_id FROM barangay WHERE barangay_name = :barangay_name");
            $stmt->execute(['barangay_name' => $_POST['permanent_barangay']]);
            $permanent_barangay_id = $stmt->fetchColumn();
            if (!$permanent_barangay_id) {
                throw new Exception('Invalid permanent barangay value: ' . $_POST['permanent_barangay']);
            }
        }

        // Handle LRN: Check if lrn exists in student table, insert if not
        $lrn = !empty($_POST['lrn']) ? $_POST['lrn'] : null;
        if ($lrn !== null) {
            $stmt = $pdo->prepare("SELECT lrn FROM student WHERE lrn = :lrn");
            $stmt->execute(['lrn' => $lrn]);
            if (!$stmt->fetchColumn()) {
                error_log("Inserting into student table: lrn=$lrn");
                $stmt = $pdo->prepare("
                    INSERT INTO student (
                        lrn, first_name, middle_name, last_name, extension_name,
                        birth_date, sex_id, mother_tongue_id
                    ) VALUES (
                        :lrn, :first_name, :middle_name, :last_name, :extension_name,
                        :birth_date, :sex_id, :mother_tongue_id
                    )
                ");
                $stmt->execute([
                    'lrn' => $lrn,
                    'first_name' => $_POST['first_name'],
                    'middle_name' => $_POST['middle_name'] ?? null,
                    'last_name' => $_POST['last_name'],
                    'extension_name' => $_POST['extension_name'] ?? null,
                    'birth_date' => $_POST['birthdate'],
                    'sex_id' => $sex_id,
                    'mother_tongue_id' => $mother_tongue_id
                ]);
            }
        }

        // Insert into enrolled_student table
        $enrolled_student_data = [
            'psa_birth_cert' => $_POST['psa_birth_cert'] ?? null,
            'lrn' => $lrn,
            'last_name' => $_POST['last_name'],
            'first_name' => $_POST['first_name'],
            'middle_name' => $_POST['middle_name'] ?? null,
            'extension_name' => $_POST['extension_name'] ?? null,
            'birthdate' => $_POST['birthdate'],
            'sex_id' => $sex_id,
            'age' => (int)$_POST['age'],
            'place_of_birth_id' => $place_of_birth_id,
            'tongue_id' => $mother_tongue_id,
            'ip_community' => $_POST['ip_community'] ?? null,
            'ip_specify' => $_POST['ip_specify'] ?? null,
            'four_ps' => $_POST['four_ps'] ?? null,
            'four_ps_id' => $_POST['four_ps_id'] ?? null
        ];
        error_log("Inserting into enrolled_student with data: " . json_encode($enrolled_student_data));
        $stmt = $pdo->prepare("
            INSERT INTO enrolled_student (
                psa_birth_cert, lrn, last_name, first_name, middle_name, extension_name,
                birthdate, sex_id, age, place_of_birth_id, tongue_id, ip_community, ip_specify,
                four_ps, four_ps_id
            ) VALUES (
                :psa_birth_cert, :lrn, :last_name, :first_name, :middle_name, :extension_name,
                :birthdate, :sex_id, :age, :place_of_birth_id, :tongue_id, :ip_community, :ip_specify,
                :four_ps, :four_ps_id
            )
        ");
        $stmt->execute($enrolled_student_data);
        $student_id = $pdo->lastInsertId();
        error_log("Inserted enrolled_student with ID: $student_id");

        // Get track_id and strand_id
        $track_id = null;
        $strand_id = null;
        if (!empty($_POST['track']) && !empty($_POST['strand'])) {
            $stmt = $pdo->prepare("SELECT track_id FROM track WHERE track_name = :track_name");
            $stmt->execute(['track_name' => $_POST['track']]);
            $track_id = $stmt->fetchColumn();
            if (!$track_id) {
                throw new Exception('Invalid track value: ' . $_POST['track']);
            }

            $stmt = $pdo->prepare("SELECT strand_id FROM strand WHERE track_id = :track_id AND strand_name = :strand_name");
            $stmt->execute(['track_id' => $track_id, 'strand_name' => $_POST['strand']]);
            $strand_id = $stmt->fetchColumn();
            if (!$strand_id) {
                throw new Exception('Invalid strand value: ' . $_POST['strand']);
            }
        }

        // Log enrollment data for debugging
        $enrollment_data = [
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
        ];
        error_log("Inserting into enrollment with data: " . json_encode($enrollment_data));
        $stmt = $pdo->prepare("
            INSERT INTO enrollment (
                student_id, school_year, grade_level, lrn_status, returning_status,
                returning_transfer_status, last_grade_level, last_school_year, last_school_attended,
                school_id, semester, track_id, strand_id, signature_path, certification_date
            ) VALUES (
                :student_id, :school_year, :grade_level, :lrn_status, :returning_status,
                :returning_transfer_status, :last_grade_level, :last_school_year, :last_school_attended,
                :school_id, :semester, :track_id, :strand_id, :signature_path, :certification_date
            )
        ");
        $stmt->execute($enrollment_data);
        $enrollment_id = $pdo->lastInsertId();
        if ($enrollment_id == 0) {
            throw new Exception('Invalid enrollment_id generated: 0');
        }
        error_log("Inserted enrollment with ID: $enrollment_id");

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
            INSERT INTO learning_modality (enrollment_id, modality_type) VALUES (:enrollment_id, :modality_type)
        ");
        $modality_inserted = false;
        foreach ($modalities as $type => $value) {
            if ($value) {
                error_log("Inserting learning modality: enrollment_id=$enrollment_id, modality_type=$type");
                $stmt->execute([
                    'enrollment_id' => $enrollment_id,
                    'modality_type' => $type
                ]);
                $modality_inserted = true;
            }
        }

        // Ensure at least one modality was inserted
        if (!$modality_inserted) {
            throw new Exception('No learning modalities were inserted.');
        }

        // Insert current address
        $address_data = [
            'student_id' => $student_id,
            'house_no' => $_POST['current_house_no'],
            'sitio_street' => $_POST['current_sitio_street'] ?? null,
            'barangay_id' => $current_barangay_id,
            'same_address' => $_POST['same_address'] ?? null
        ];
        error_log("Inserting current address with data: " . json_encode($address_data));
        $stmt = $pdo->prepare("
            INSERT INTO address (
                student_id, address_type, house_no, sitio_street, barangay_id, same_address
            ) VALUES (
                :student_id, 'current', :house_no, :sitio_street, :barangay_id, :same_address
            )
        ");
        $stmt->execute($address_data);

        // Insert permanent address if not same as current
        if (!empty($_POST['same_address']) && $_POST['same_address'] === 'no') {
            $permanent_address_data = [
                'student_id' => $student_id,
                'house_no' => $_POST['permanent_house_no'] ?? null,
                'sitio_street' => $_POST['permanent_sitio_street'] ?? null,
                'barangay_id' => $permanent_barangay_id
            ];
            error_log("Inserting permanent address with data: " . json_encode($permanent_address_data));
            $stmt = $pdo->prepare("
                INSERT INTO address (
                    student_id, address_type, house_no, sitio_street, barangay_id
                ) VALUES (
                    :student_id, 'permanent', :house_no, :sitio_street, :barangay_id
                )
            ");
            $stmt->execute($permanent_address_data);
        }

        // Insert guardians
        $guardians = [
            ['type' => 'father', 'last_name' => $_POST['father_last_name'] ?? null, 'first_name' => $_POST['father_first_name'] ?? null, 'middle_name' => $_POST['father_middle_name'] ?? null, 'contact' => $_POST['father_contact'] ?? null],
            ['type' => 'mother', 'last_name' => $_POST['mother_last_name'] ?? null, 'first_name' => $_POST['mother_first_name'] ?? null, 'middle_name' => $_POST['mother_middle_name'] ?? null, 'contact' => $_POST['mother_contact'] ?? null],
            ['type' => 'legal_guardian', 'last_name' => $_POST['guardian_last_name'] ?? null, 'first_name' => $_POST['guardian_first_name'] ?? null, 'middle_name' => $_POST['guardian_middle_name'] ?? null, 'contact' => $_POST['guardian_contact'] ?? null]
        ];
        $stmt = $pdo->prepare("
            INSERT INTO guardian (
                student_id, guardian_type, last_name, first_name, middle_name, contact
            ) VALUES (
                :student_id, :guardian_type, :last_name, :first_name, :middle_name, :contact
            )
        ");
        foreach ($guardians as $guardian) {
            if ($guardian['last_name'] || $guardian['first_name'] || $guardian['middle_name'] || $guardian['contact']) {
                error_log("Inserting guardian: " . json_encode($guardian));
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
        if (!empty($_POST['disability']) && $_POST['disability'] === 'yes' && !empty($_POST['disability_type'])) {
            $stmt = $pdo->prepare("
                INSERT INTO disability (student_id, disability_type) VALUES (:student_id, :disability_type)
            ");
            foreach ($_POST['disability_type'] as $type) {
                error_log("Inserting disability: student_id=$student_id, disability_type=$type");
                $stmt->execute([
                    'student_id' => $student_id,
                    'disability_type' => $type
                ]);
            }
        }

        $pdo->commit();
        error_log("Enrollment form submitted successfully");
        echo json_encode(['success' => 'Enrollment form submitted successfully!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error during enrollment: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save enrollment data: ' . $e->getMessage()]);
    }
}
?>