<?php
session_start();
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

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$teacher_id = $data['teacher_id'] ?? null;
$class_id = $data['class_id'] ?? null;
$subject_id = $data['subject_id'] ?? null;
$day = $data['day'] ?? null;
$time = $data['time'] ?? null;
$minutes = $data['minutes'] ?? null;
$room_number = $data['room_number'] ?? null;

if (!$teacher_id || !$class_id || !$subject_id || !$day || !$time || !$minutes) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO class_schedule (class_id, subject_id, day, time, minutes, teacher_id, room_number) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$class_id, $subject_id, $day, $time, $minutes, $teacher_id, $room_number]);
    echo json_encode(['success' => true, 'message' => 'Class schedule created successfully.']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to create class schedule.']);
} 