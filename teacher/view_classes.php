<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['teacher']);

$success = '';
$error = '';

// Get classes taught by this teacher (based on results entered)
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.class, s.stream, COUNT(DISTINCT s.student_id) as student_count,
               COUNT(DISTINCT r.subject) as subjects_taught,
               COUNT(DISTINCT r.exam_id) as exams_conducted
        FROM students s
        JOIN results r ON s.student_id = r.student_id
        WHERE r.teacher_id = ?
        GROUP BY s.class, s.stream
        ORDER BY s.class, s.stream
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll();
    
    // Get subjects taught by this teacher
    $stmt = $pdo->prepare("
        SELECT DISTINCT r.subject, COUNT(DISTINCT s.student_id) as student_count,
               COUNT(DISTINCT s.class) as classes_taught
        FROM results r
        JOIN students s ON r.student_id = s.student_id
        WHERE r.teacher_id = ?
        GROUP BY r.subject
        ORDER BY r.subject
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $subjects = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to load class information.';
    $classes = [];
    $subjects = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-chalkboard"></i> My Classes</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <i class="fas fa-chalkboard text-primary"></i>
                            <h3><?php echo count($classes); ?></h3>
                            <p>Classes Taught</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <i class="fas fa-book text-success"></i>
                            <h3><?php echo count($subjects); ?></h3>
                            <p>Subjects Taught</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <i class="fas fa-users text-info"></i>
                            <h3><?php echo array_sum(array_column($classes, 'student_count')); ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <h5>Classes Overview</h5>
                        <?php if (empty($classes)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No classes found. You haven't entered any results yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Class</th>
                                            <th>Stream</th>
                                            <th>Students</th>
                                            <th>Subjects</th>
                                            <th>Exams</th>
                                            <!-- <th>Actions</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['class']); ?></td>
                                                <td><?php echo htmlspecialchars($class['stream']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $class['student_count']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $class['subjects_taught']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $class['exams_conducted']; ?></span>
                                                </td>
                                                <!-- <td> -->
                                                    <!-- <a href="/teacher/view_students.php?class=<?php echo urlencode($class['class']); ?>&stream=<?php echo urlencode($class['stream']); ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View Students
                                                    </a> -->
                                                <!-- </td> -->
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <h5>Subjects Overview</h5>
                        <?php if (empty($subjects)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No subjects found.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($subject['subject']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $subject['classes_taught']; ?> classes, 
                                                <?php echo $subject['student_count']; ?> students
                                            </small>
                                        </div>
                                        <div>
                                            <a href="/teacher/enter_marks.php?subject=<?php echo urlencode($subject['subject']); ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-plus"></i> Add Marks
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
