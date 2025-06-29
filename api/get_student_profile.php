<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';


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

if ($result) {
    echo json_encode(['success' => true, 'address' => $result['address'], 'birth_date' => $result['birth_date']]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data found for this LRN']);
}
?> 