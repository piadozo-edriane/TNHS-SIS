<?php
session_start();

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
        return false;
    }
}

function getTeacherInfo($teacher_id) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT teacher_id, first_name, middle_name, last_name, extension_name FROM teacher WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch();
        
        if ($teacher) {
            $teacher['middle_name'] = $teacher['middle_name'] ?? '';
            $teacher['extension_name'] = $teacher['extension_name'] ?? '';
            return $teacher;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error fetching teacher info: " . $e->getMessage());
        return false;
    }
}

function authenticateTeacher($teacher_id, $password = null) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT teacher_id FROM teacher WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Error authenticating teacher: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_teacher_info':
            if (!isset($_SESSION['teacher_id'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'not_logged_in'
                ]);
                exit;
            }
            
            $teacher = getTeacherInfo($_SESSION['teacher_id']);
            if ($teacher) {
                echo json_encode([
                    'success' => true,
                    'teacher' => $teacher
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'teacher_not_found'
                ]);
            }
            break;
            
        case 'login':
            $teacher_id = $_POST['teacher_id'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($teacher_id)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'teacher_id_required'
                ]);
                exit;
            }
            
            if (authenticateTeacher($teacher_id, $password)) {
                $_SESSION['teacher_id'] = $teacher_id;
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'invalid_credentials'
                ]);
            }
            break;
            
        case 'logout':
            session_destroy();
            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'invalid_action'
            ]);
            break;
    }
    exit;
}
?>