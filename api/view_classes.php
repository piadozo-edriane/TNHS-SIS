<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'improved-tnhs-sis';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT class_id, class_name, grade_level_id, adviser_id, room_number, number_of_students FROM class");
    $classes = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'classes' => $classes
    ]);
} catch (PDOException $e) {
    error_log('Error fetching classes: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error.'
    ]);
}
