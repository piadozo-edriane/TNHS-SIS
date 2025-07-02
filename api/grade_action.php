<?php
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';
    $grade_id = isset($input['grade_id']) ? intval($input['grade_id']) : null;
    $enrollment_id = isset($input['enrollment_id']) ? intval($input['enrollment_id']) : null;
    $teacher_id = isset($input['teacher_id']) ? intval($input['teacher_id']) : null;
    $quarter_number = isset($input['quarter_number']) ? intval($input['quarter_number']) : null;
    $general_weighted_average = isset($input['general_weighted_average']) ? floatval($input['general_weighted_average']) : null;
    $lrn = isset($input['lrn']) ? $input['lrn'] : null;
    $class_subject_id = isset($input['class_subject_id']) ? $input['class_subject_id'] : null;
    $class_id = isset($input['class_id']) ? $input['class_id'] : null;

    if ($action === 'create') {
        $stmt = $conn->prepare("INSERT INTO grade (lrn, class_subject_id, general_weighted_average) VALUES (?, ?, ?)");
        $stmt->bind_param('ssd', $lrn, $class_subject_id, $general_weighted_average);
        $success = $stmt->execute();
        echo json_encode(['success' => $success, 'id' => $conn->insert_id]);
    } elseif ($action === 'insert') {
        if ($enrollment_id === null && $teacher_id === null && $quarter_number === null && $class_id === null) {
            $stmt = $conn->prepare("INSERT INTO grade (lrn, class_subject_id, general_weighted_average) VALUES (?, ?, ?)");
            $stmt->bind_param('ssd', $lrn, $class_subject_id, $general_weighted_average);
            $success = $stmt->execute();
            echo json_encode(['success' => $success, 'id' => $conn->insert_id]);
        } else {
            $stmt = $conn->prepare("INSERT INTO grade (enrollment_id, teacher_id, quarter_number, general_weighted_average, lrn, class_subject_id, class_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iiidssi', $enrollment_id, $teacher_id, $quarter_number, $general_weighted_average, $lrn, $class_subject_id, $class_id);
            $success = $stmt->execute();
            echo json_encode(['success' => $success, 'id' => $conn->insert_id]);
        }
    } elseif ($action === 'update') {
        $stmt = $conn->prepare("UPDATE grade SET general_weighted_average=? WHERE grade_id=?");
        $stmt->bind_param('di', $general_weighted_average, $grade_id);
        $success = $stmt->execute();
        echo json_encode(['success' => $success]);
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM grade WHERE grade_id=?");
        $stmt->bind_param('i', $grade_id);
        $success = $stmt->execute();
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
