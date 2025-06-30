<?php
    session_start();

    $host = 'localhost';
    $dbname = 'tnhs-sis'; 
    $username = 'root';
    $password_db = ''; 

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $teacher_id = $_POST['teacher_id'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($teacher_id) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    $valid_password = 'teacher123';  

    if ($password !== $valid_password) {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password_db);
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT teacher_id, first_name, middle_name, extension_name FROM teacher WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch();

        if (!$teacher) {
            echo json_encode(['success' => false, 'message' => 'Teacher ID not found']);
            exit;
        }

        $_SESSION['logged_in'] = true;
        $_SESSION['teacher_id'] = $teacher['teacher_id'];
        $_SESSION['first_name'] = $teacher['first_name'];
        $_SESSION['middle_name'] = $teacher['middle_name'];
        $_SESSION['extension_name'] = $teacher['extension_name'];
        $_SESSION['login_time'] = time();

        echo json_encode([
            'success' => true, 
            'message' => 'Login successful',
            'teacher_id' => $teacher['teacher_id'],
            'name' => trim($teacher['first_name'] . ' ' . $teacher['middle_name'] . ' ' . $teacher['extension_name'])
        ]);

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
    }
?>