<?php
// get_grades.php
header('Content-Type: application/json');
$host = 'localhost';
$dbname = 'improved-tnhs-sis';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$sql = "SELECT * FROM grade";
$result = $conn->query($sql);
$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
}

$conn->close();
echo json_encode(['success' => true, 'grades' => $grades]);
