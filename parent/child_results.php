<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['parent']);

// Define BASE_URL if not already defined (adjust accordingly)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/School_SMS'); // Adjust this to your base path
}

$student_id = isset($_GET['student_id']) && is_numeric($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

$success = '';
$error = '';
$results = [];
$student = null;

if ($student_id <= 0) {
    $error = "Invalid student ID.";
}

function getGrade($percentage) {
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 50) return 'D';
    return 'F';
}

function getGradeClass($grade) {
    switch ($grade) {
        case 'A': return 'bg-success';
        case 'B': return 'bg-primary';
        case 'C': return 'bg-info';
        case 'D': return 'bg-warning';
        case 'F': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

try {
    if (!$error) {
        // Verify this student belongs to the logged-in parent
        $stmt = $pdo->prepare("
            SELECT s.*, u.full_name 
            FROM parent_student ps
            JOIN students s ON ps.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            WHERE ps.parent_id = ? AND s.student_id = ?
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id'], $student_id]);
        $student = $stmt->fetch();

        if (!$student) {
            $error = 'Student not found or you do not have permission to view this information.';
        } else {
            // Get all results for this student
            $stmt = $pdo->prepare("
                SELECT r.*, e.exam_name, e.term, e.year, u.full_name AS teacher_name
                FROM results r
                JOIN exams e ON r.exam_id = e.exam_id
                JOIN users u ON r.teacher_id = u.user_id
                WHERE r.student_id = ?
                ORDER BY e.year DESC, e.term DESC, r.subject
            ");
            $stmt->execute([$student_id]);
            $results = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    $error = 'Failed to load results.';
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h4>
                    <i class="fas fa-award"></i>
                    <?= $student ? htmlspecialchars($student['full_name']) . "'s" : 'Child' ?> Results
                </h4>
                <div>
                    <a href="<?= BASE_URL ?>/parent/child_info.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Back to Children
                    </a>
                    <button class="btn btn-outline-light no-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Results
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php elseif ($student): ?>
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h5>Student Information</h5>
                            <p><strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
                            <p><strong>Admission Number:</strong> <?= htmlspecialchars($student['admission_number']) ?></p>
                            <p><strong>Class:</strong> <?= htmlspecialchars($student['class'] . ' ' . $student['stream']) ?></p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-user-graduate fa-4x text-primary"></i>
                        </div>
                    </div>

                    <?php if (empty($results)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No exam results found for this student.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Term</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Marks Obtained</th>
                                        <th>Maximum Marks</th>
                                        <th>Percentage</th>
                                        <th>Grade</th>
                                        <th>Teacher</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $result):
                                        $percentage = $result['max_marks'] > 0
                                            ? ($result['marks_obtained'] / $result['max_marks']) * 100
                                            : 0;
                                        $grade = getGrade($percentage);
                                        $gradeClass = getGradeClass($grade);
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($result['exam_name']) ?></td>
                                            <td><?= htmlspecialchars($result['term']) ?></td>
                                            <td><?= htmlspecialchars($result['year']) ?></td>
                                            <td><?= htmlspecialchars($result['subject']) ?></td>
                                            <td><?= number_format($result['marks_obtained'], 1) ?></td>
                                            <td><?= number_format($result['max_marks'], 1) ?></td>
                                            <td><?= number_format($percentage, 1) ?>%</td>
                                            <td><span class="badge <?= $gradeClass ?>"><?= $grade ?></span></td>
                                            <td><?= htmlspecialchars($result['teacher_name']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5>Performance Summary</h5>
                                <?php
                                    $total_marks = array_sum(array_column($results, 'marks_obtained'));
                                    $total_max = array_sum(array_column($results, 'max_marks'));
                                    $overall_percentage = $total_max > 0 ? ($total_marks / $total_max) * 100 : 0;
                                    $overall_grade = getGrade($overall_percentage);
                                    $overall_grade_class = getGradeClass($overall_grade);
                                ?>
                                <div class="alert alert-info">
                                    <p><strong>Total Marks:</strong> <?= number_format($total_marks, 1) ?> / <?= number_format($total_max, 1) ?></p>
                                    <p><strong>Overall Percentage:</strong> <?= number_format($overall_percentage, 1) ?>%</p>
                                    <p><strong>Overall Grade:</strong> <span class="badge <?= $overall_grade_class ?>"><?= $overall_grade ?></span></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Grading Scale</h5>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Grade</th>
                                            <th>Percentage</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td><span class="badge bg-success">A</span></td><td>80-100%</td><td>Excellent</td></tr>
                                        <tr><td><span class="badge bg-primary">B</span></td><td>70-79%</td><td>Very Good</td></tr>
                                        <tr><td><span class="badge bg-info">C</span></td><td>60-69%</td><td>Good</td></tr>
                                        <tr><td><span class="badge bg-warning">D</span></td><td>50-59%</td><td>Satisfactory</td></tr>
                                        <tr><td><span class="badge bg-danger">F</span></td><td>Below 50%</td><td>Needs Improvement</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
