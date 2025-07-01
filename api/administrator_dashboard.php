<?php
// Database connection (update with your actual credentials)
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'tnhs_sis';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Fetch faculty count
$facultyCount = 0;
$result = $conn->query('SELECT COUNT(*) as count FROM faculty');
if ($result && $row = $result->fetch_assoc()) {
    $facultyCount = $row['count'];
}

// Fetch student count
$studentCount = 0;
$result = $conn->query('SELECT COUNT(*) as count FROM students');
if ($result && $row = $result->fetch_assoc()) {
    $studentCount = $row['count'];
}

// Fetch report count (update table name if needed)
$reportCount = 0;
$result = $conn->query('SELECT COUNT(*) as count FROM reports');
if ($result && $row = $result->fetch_assoc()) {
    $reportCount = $row['count'];
}

$conn->close();

echo json_encode([
    'success' => true,
    'facultyCount' => $facultyCount,
    'studentCount' => $studentCount,
    'reportCount' => $reportCount
]);
exit();
?> 