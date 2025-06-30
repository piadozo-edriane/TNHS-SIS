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


$stmt = $pdo->prepare("SELECT subject_name FROM subject WHERE grade_level = 10 AND academic_year = 2025");
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'subjects' => $subjects]);
?> 