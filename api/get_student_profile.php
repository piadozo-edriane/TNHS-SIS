<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$dbname = 'final-tnhs-sis'; 
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

$stmt = $pdo->prepare('SELECT address, birth_date FROM place_of_birth WHERE lrn = ?');
$stmt->execute([$lrn]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch class and grade info for the current school year
$classInfo = null;
$classStmt = $pdo->prepare('
    SELECT c.class_name, gl.grade_level
    FROM enrollment e
    JOIN class c ON e.class_id = c.class_id
    JOIN grade_level gl ON c.grade_level_id = gl.grade_level_id
    WHERE e.student_id = (SELECT student_id FROM student WHERE lrn = ? LIMIT 1)
    ORDER BY e.enrollment_id DESC
    LIMIT 1
');
$classStmt->execute([$lrn]);
$classInfo = $classStmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo json_encode([
        'success' => true,
        'address' => $result['address'],
        'birth_date' => $result['birth_date'],
        'class_name' => $classInfo['class_name'] ?? null,
        'grade_level' => $classInfo['grade_level'] ?? null
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data found for this LRN']);
}
?> 