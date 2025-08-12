<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['teacher']);

header('Content-Type: application/json');

$response = [
    'success' => false, 
    'message' => '', 
    'students' => []
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$teacher_id = $_SESSION['user_id'] ?? null;
if (!$teacher_id) {
    $response['message'] = 'Teacher not authenticated.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $response['message'] = 'Invalid JSON input.';
    echo json_encode($response);
    exit;
}

try {
    // Check if we're loading by class_id or exam_id+subject
    if (isset($input['class_id'])) {
        // Load students by class (for initial selection)
        
        // Verify teacher teaches this class
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM teacher_subjects 
            WHERE teacher_id = ? AND class_id = ?
        ");
        $stmt->execute([$teacher_id, $input['class_id']]);
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'You are not assigned to this class.';
            echo json_encode($response);
            exit;
        }

        // Fetch students in this class
        $stmt = $pdo->prepare("
            SELECT s.student_id, s.full_name, s.admission_number, 
                   CONCAT(c.class_name, COALESCE(CONCAT(' ', c.stream), '')) AS class_name
            FROM students s
            JOIN classes c ON s.class_id = c.class_id
            WHERE s.class_id = ?
            ORDER BY s.full_name ASC
        ");
        $stmt->execute([$input['class_id']]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            $response['message'] = 'No students found in this class.';
        } else {
            $response['success'] = true;
            $response['students'] = $students;
        }

    } elseif (isset($input['exam_id']) && isset($input['subject'])) {
        // Load students with existing marks (for marks entry)
        
        // Verify teacher teaches this subject
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM teacher_subjects 
            WHERE teacher_id = ? AND subject = ?
        ");
        $stmt->execute([$teacher_id, $input['subject']]);
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'You are not assigned to teach this subject.';
            echo json_encode($response);
            exit;
        }

        // Fetch students with existing marks
        $stmt = $pdo->prepare("
            SELECT s.student_id, s.full_name, s.admission_number,
                   CONCAT(c.class_name, COALESCE(CONCAT(' ', c.stream), '')) AS class_name,
                   r.marks_obtained
            FROM students s
            JOIN classes c ON s.class_id = c.class_id
            LEFT JOIN results r ON s.student_id = r.student_id 
                AND r.exam_id = ? 
                AND r.subject = ?
            WHERE s.class_id IN (
                SELECT class_id 
                FROM teacher_subjects 
                WHERE teacher_id = ? AND subject = ?
            )
            ORDER BY s.full_name ASC
        ");
        $stmt->execute([
            $input['exam_id'],
            $input['subject'],
            $teacher_id,
            $input['subject']
        ]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            $response['message'] = 'No students found for this exam and subject.';
        } else {
            $response['success'] = true;
            $response['students'] = $students;
        }
    } else {
        $response['message'] = 'Missing required parameters.';
    }

} catch (PDOException $e) {
    error_log("get_students.php database error: " . $e->getMessage());
    $response['message'] = 'Database error occurred while loading students.';
}

echo json_encode($response);
?>