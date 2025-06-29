<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $lrn = $input['lrn'] ?? '';
    $birthMonth = $input['birthMonth'] ?? '';
    $birthDay = $input['birthDay'] ?? '';
    $birthYear = $input['birthYear'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($lrn) || empty($birthMonth) || empty($birthDay) || empty($birthYear) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if ($password !== '123456') {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }
    
    $birthDate = $birthYear . '-' . str_pad($birthMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($birthDay, 2, '0', STR_PAD_LEFT);
    
    try {
        $stmt = $pdo->prepare("SELECT s.*, es.lrn as enrolled_lrn 
                              FROM student s 
                              LEFT JOIN enrolled_student es ON s.lrn = es.lrn 
                              WHERE s.lrn = ? AND s.birth_date = ?");
        $stmt->execute([$lrn, $birthDate]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            if ($student['enrolled_lrn']) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Login successful',
                    'student' => [
                        'student_id' => $student['student_id'],
                        'lrn' => $student['lrn'],
                        'first_name' => $student['first_name'],
                        'middle_name' => $student['middle_name'],
                        'last_name' => $student['last_name'],
                        'extension_name' => $student['extension_name']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student is not enrolled']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid LRN or birth date']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
