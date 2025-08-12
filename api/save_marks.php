<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['teacher']);

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Get teacher ID from session
$teacher_id = $_SESSION['user_id'] ?? null;
if (!$teacher_id) {
    $response['message'] = 'Teacher not authenticated.';
    echo json_encode($response);
    exit;
}

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $response['message'] = 'Invalid JSON input.';
    echo json_encode($response);
    exit;
}

$student_id = filter_var($input['student_id'] ?? null, FILTER_VALIDATE_INT);
$term = trim($input['term'] ?? '');
$subject = trim($input['subject'] ?? '');
$marks = filter_var($input['marks'] ?? null, FILTER_VALIDATE_FLOAT);

// Validate all inputs
if (!$student_id || $student_id <= 0) {
    $response['message'] = 'Invalid student ID.';
    echo json_encode($response);
    exit;
}

if (empty($term)) {
    $response['message'] = 'Term is required.';
    echo json_encode($response);
    exit;
}

if (empty($subject)) {
    $response['message'] = 'Subject is required.';
    echo json_encode($response);
    exit;
}

if ($marks === false || $marks < 0 || $marks > 100) {
    $response['message'] = 'Marks must be between 0 and 100.';
    echo json_encode($response);
    exit;
}

try {
    // Verify teacher is assigned to this subject
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id = ? AND subject = ?");
    $stmt->execute([$teacher_id, $subject]);
    
    if ($stmt->fetchColumn() == 0) {
        $response['message'] = 'You are not assigned to teach this subject.';
        echo json_encode($response);
        exit;
    }

    // Verify teacher can access this student for this subject
    $stmt = $pdo->prepare("
        SELECT s.student_id, s.full_name 
        FROM students s 
        JOIN teacher_subjects ts ON s.class_id = ts.class_id 
        WHERE s.student_id = ? AND ts.teacher_id = ? AND ts.subject = ?
    ");
    $stmt->execute([$student_id, $teacher_id, $subject]);
    
    if (!$stmt->fetch()) {
        $response['message'] = 'You are not authorized to enter marks for this student in this subject.';
        echo json_encode($response);
        exit;
    }

    // Get exam_id from term
    $stmt = $pdo->prepare("SELECT exam_id FROM exams WHERE term = ? LIMIT 1");
    $stmt->execute([$term]);
    $exam = $stmt->fetch();
    
    if (!$exam) {
        $response['message'] = "No exam found for term: " . htmlspecialchars($term);
        echo json_encode($response);
        exit;
    }
    
    $exam_id = $exam['exam_id'];

    // Start transaction
    $pdo->beginTransaction();

    // Check if result already exists
    $stmt = $pdo->prepare("
        SELECT result_id FROM results 
        WHERE student_id = ? AND exam_id = ? AND subject = ?
    ");
    $stmt->execute([$student_id, $exam_id, $subject]);
    
    if ($stmt->fetch()) {
        // Update existing result
        $stmt = $pdo->prepare("
            UPDATE results 
            SET marks_obtained = ?, max_marks = 100, teacher_id = ?, updated_at = NOW() 
            WHERE student_id = ? AND exam_id = ? AND subject = ?
        ");
        $stmt->execute([$marks, $teacher_id, $student_id, $exam_id, $subject]);
        $message = 'Marks updated successfully.';
    } else {
        // Insert new result
        $stmt = $pdo->prepare("
            INSERT INTO results (student_id, exam_id, subject, marks_obtained, max_marks, teacher_id, created_at) 
            VALUES (?, ?, ?, ?, 100, ?, NOW())
        ");
        $stmt->execute([$student_id, $exam_id, $subject, $marks, $teacher_id]);
        $message = 'Marks saved successfully.';
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = $message;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("save_marks.php database error: " . $e->getMessage());
    $response['message'] = 'Database error occurred while saving marks.';
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("save_marks.php general error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred while saving marks.';
}

echo json_encode($response);
?>