<?php
$host = 'localhost';
$dbname = 'improved-tnhs-sis';
$username = 'root';
$password = '';

function getDBConnection() {
    global $host, $dbname, $username, $password;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}

$pdo = getDBConnection();
$type = $_GET['type'] ?? '';

if ($type === 'provinces') {
    $stmt = $pdo->query("SELECT province_name FROM province ORDER BY province_name");
    $provinces = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($provinces);
} elseif ($type === 'municipalities' && !empty($_GET['province'])) {
    $stmt = $pdo->prepare("SELECT m.municipality_name 
                           FROM municipality m 
                           JOIN province p ON m.province_id = p.province_id 
                           WHERE p.province_name = :province_name 
                           ORDER BY m.municipality_name");
    $stmt->execute(['province_name' => $_GET['province']]);
    $municipalities = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($municipalities);
} elseif ($type === 'barangays' && !empty($_GET['province']) && !empty($_GET['municipality'])) {
    $stmt = $pdo->prepare("SELECT b.barangay_name 
                           FROM barangay b 
                           JOIN municipality m ON b.municipality_id = m.municipality_id 
                           JOIN province p ON m.province_id = p.province_id 
                           WHERE p.province_name = :province_name 
                           AND m.municipality_name = :municipality_name 
                           ORDER BY b.barangay_name");
    $stmt->execute([
        'province_name' => $_GET['province'],
        'municipality_name' => $_GET['municipality']
    ]);
    $barangays = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($barangays);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>