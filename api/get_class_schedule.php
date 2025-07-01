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

$class_id = 7;

$stmt = $pdo->prepare("
    SELECT s.subject_name, cs.day, cs.time, cs.minutes, cs.room_number
    FROM class_schedule cs
    JOIN subject s ON cs.subject_id = s.subject_id
    WHERE cs.class_id = :class_id AND s.grade_level = 10 AND s.academic_year = 2025
    ORDER BY cs.time
");
$stmt->execute(['class_id' => $class_id]);
$schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'schedule' => $schedule]);
?> 