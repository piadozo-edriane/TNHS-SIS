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

$stmt = $pdo->prepare('
    SELECT c.class_id, c.class_name, gl.grade_level
    FROM class c
    JOIN grade_level gl ON c.grade_level_id = gl.grade_level_id
');
$stmt->execute();
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'sections' => $sections]); 