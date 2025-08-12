<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['student']);

$success = '';
$error = '';

try {
    // Get student information
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $error = 'Student record not found.';
        $results = [];
    } else {
        // Get all results for this student
        $stmt = $pdo->prepare("
            SELECT r.*, e.exam_name, e.term, e.year, u.full_name as teacher_name
            FROM results r
            JOIN exams e ON r.exam_id = e.exam_id
            JOIN users u ON r.teacher_id = u.user_id
            WHERE r.student_id = ?
            ORDER BY e.year DESC, e.term DESC, r.subject
        ");
        $stmt->execute([$student['student_id']]);
        $results = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $error = 'Failed to load results.';
    $results = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-chart-bar"></i> My Exam Results</h4>
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Results
                </button>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($results)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No exam results available yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Subject</th>
                                    <th>Marks Obtained</th>
                                    <th>Maximum Marks</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                    <th>Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <?php
                                    $percentage = ($result['marks_obtained'] / $result['max_marks']) * 100;
                                    $grade = $percentage >= 90 ? 'A' : ($percentage >= 80 ? 'B' : ($percentage >= 70 ? 'C' : ($percentage >= 60 ? 'D' : 'F')));
                                    $grade_class = $percentage >= 70 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['exam_name'] . ' - ' . $result['term'] . ' ' . $result['year']); ?></td>
                                        <td><?php echo htmlspecialchars($result['subject']); ?></td>
                                        <td><?php echo $result['marks_obtained']; ?></td>
                                        <td><?php echo $result['max_marks']; ?></td>
                                        <td><?php echo round($percentage, 1); ?>%</td>
                                        <td><span class="badge bg-<?php echo $grade_class; ?>"><?php echo $grade; ?></span></td>
                                        <td><?php echo htmlspecialchars($result['teacher_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>