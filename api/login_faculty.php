<?php
    session_start();

    $host = 'localhost';
    $dbname = 'final-tnhs-sis'; 
    $username = 'root';
    $password = ''; 

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    function debug_log($msg) {
        file_put_contents(__DIR__ . '/debug_login.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        debug_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $teacher_id = $data['teacher_id'] ?? null;
    $teacher_password = $data['password'] ?? null;

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT teacher_id, first_name, middle_name, extension_name FROM teacher WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch();

        if (!$teacher) {
            debug_log('Teacher ID not found: ' . $teacher_id);
            echo json_encode(['success' => false, 'message' => 'Teacher ID not found']);
            exit;
        }

        if ($teacher_password === 'password') {
            $_SESSION['logged_in'] = true;
            $_SESSION['teacher_id'] = $teacher['teacher_id'];
            $_SESSION['first_name'] = $teacher['first_name'];
            $_SESSION['middle_name'] = $teacher['middle_name'];
            $_SESSION['extension_name'] = $teacher['extension_name'];
            $_SESSION['login_time'] = time();


    } catch (PDOException $e) {
        debug_log('Database error: ' . $e->getMessage());
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database connection error']);
    }
?>