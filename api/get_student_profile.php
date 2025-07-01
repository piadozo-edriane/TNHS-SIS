<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$dbname = 'improved-tnhs-sis'; 
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get LRN from POST or GET
$lrn = $_POST['lrn'] ?? $_GET['lrn'] ?? null;

if (!$lrn) {
    echo json_encode(['success' => false, 'message' => 'LRN is required']);
    exit;
}

// Fetch student information including birthday and address from place_of_birth table
$studentStmt = $pdo->prepare('
    SELECT s.student_id, s.first_name, s.middle_name, s.last_name, s.extension_name, s.lrn,
           pob.birth_date, pob.address as place_of_birth_address,
           es.student_id as enrolled_student_id
    FROM student s
    LEFT JOIN place_of_birth pob ON s.lrn = pob.lrn
    LEFT JOIN enrolled_student es ON s.lrn = es.lrn
    WHERE s.lrn = ?
');
$studentStmt->execute([$lrn]);
$studentResult = $studentStmt->fetch(PDO::FETCH_ASSOC);

// Get class name "DIAMOND" from class table
$classStmt = $pdo->prepare('
    SELECT class_name
    FROM class
    WHERE class_name = "DIAMOND"
    LIMIT 1
');
$classStmt->execute();
$classResult = $classStmt->fetch(PDO::FETCH_ASSOC);

// Get grade level "10" from grade_level table
$gradeStmt = $pdo->prepare('
    SELECT grade_level
    FROM grade_level
    WHERE grade_level = 10
    LIMIT 1
');
$gradeStmt->execute();
$gradeResult = $gradeStmt->fetch(PDO::FETCH_ASSOC);

// Format birth date
$formattedBirthDate = 'Not available';
if ($studentResult['birth_date']) {
    // Try to parse and format the birth date
    $birthDate = $studentResult['birth_date'];
    // If it's already in a good format, use it as is
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $birthDate)) {
        $formattedBirthDate = $birthDate;
    } else {
        // Try to convert various formats
        $timestamp = strtotime($birthDate);
        if ($timestamp) {
            $formattedBirthDate = date('m/d/Y', $timestamp);
        } else {
            $formattedBirthDate = $birthDate; // Use as is if can't parse
        }
    }
}

if ($studentResult) {
    echo json_encode([
        'success' => true,
        'address' => $studentResult['place_of_birth_address'] ?: 'Not available',
        'birth_date' => $formattedBirthDate,
        'class_name' => $classResult['class_name'] ?? 'Not available',
        'grade_level' => $gradeResult['grade_level'] ?? 'Not available'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data found for this LRN']);
}
?> 