<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['student']);

// Get student information
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
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
                <h4><i class="fas fa-award"></i> My Exam Results</h4>
                <button class="btn btn-outline-primary no-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Results
                </button>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($results)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No exam results found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="sortable">Exam</th>
                                    <th class="sortable">Term</th>
                                    <th class="sortable">Year</th>
                                    <th class="sortable">Subject</th>
                                    <th class="sortable">Marks Obtained</th>
                                    <th class="sortable">Maximum Marks</th>
                                    <th class="sortable">Percentage</th>
                                    <th class="sortable">Grade</th>
                                    <th>Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                    <?php
                                    $percentage = ($result['marks_obtained'] / $result['max_marks']) * 100;
                                    $grade = getGrade($percentage);
                                    $gradeClass = getGradeClass($grade);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                        <td><?php echo htmlspecialchars($result['term']); ?></td>
                                        <td><?php echo htmlspecialchars($result['year']); ?></td>
                                        <td><?php echo htmlspecialchars($result['subject']); ?></td>
                                        <td><?php echo number_format($result['marks_obtained'], 1); ?></td>
                                        <td><?php echo number_format($result['max_marks'], 1); ?></td>
                                        <td><?php echo number_format($percentage, 1); ?>%</td>
                                        <td>
                                            <span class="badge <?php echo $gradeClass; ?>">
                                                <?php echo $grade; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($result['teacher_name']); ?></td>
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
                            ?>
                            <div class="alert alert-info">
                                <p><strong>Total Marks:</strong> <?php echo number_format($total_marks, 1); ?> / <?php echo number_format($total_max, 1); ?></p>
                                <p><strong>Overall Percentage:</strong> <?php echo number_format($overall_percentage, 1); ?>%</p>
                                <p><strong>Overall Grade:</strong> 
                                    <span class="badge <?php echo getGradeClass(getGrade($overall_percentage)); ?>">
                                        <?php echo getGrade($overall_percentage); ?>
                                    </span>
                                </p>
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
            </div>
        </div>
    </div>
</div>

<?php
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

include '../includes/footer.php';
?>
