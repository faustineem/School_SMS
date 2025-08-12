<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['teacher']);

$success = '';
$error = '';

try {
    // Get teacher statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.student_id) as student_count
        FROM students s 
        JOIN results r ON s.student_id = r.student_id 
        WHERE r.teacher_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['assigned_students'] = $stmt->fetch()['student_count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM results WHERE teacher_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total_results'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT subject) as count FROM results WHERE teacher_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['subjects_taught'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT AVG(marks_obtained) as avg FROM results WHERE teacher_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['average_performance'] = round($stmt->fetch()['avg'] ?? 0, 1);
    
    // Get classes and students taught by this teacher
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.class, s.stream, COUNT(DISTINCT s.student_id) as student_count
        FROM students s 
        JOIN results r ON s.student_id = r.student_id 
        WHERE r.teacher_id = ?
        GROUP BY s.class, s.stream
        ORDER BY s.class, s.stream
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll();
    
    // Get recent results entered by this teacher
    $stmt = $pdo->prepare("
        SELECT r.*, e.exam_name, e.term, e.year, u.full_name as student_name, s.admission_number
        FROM results r
        JOIN exams e ON r.exam_id = e.exam_id
        JOIN students s ON r.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE r.teacher_id = ?
        ORDER BY r.result_id DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_results = $stmt->fetchAll();
    
    // Get subjects taught by this teacher
    $stmt = $pdo->prepare("
        SELECT DISTINCT subject, COUNT(*) as result_count, AVG(marks_obtained) as avg_marks
        FROM results 
        WHERE teacher_id = ? 
        GROUP BY subject
        ORDER BY subject
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $subjects = $stmt->fetchAll();
    
    // Get assigned students (those who have results by this teacher)
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.full_name, s.admission_number, s.class, s.stream, s.student_id,
               COUNT(r.result_id) as result_count, AVG(r.marks_obtained) as avg_marks
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        JOIN results r ON s.student_id = r.student_id
        WHERE r.teacher_id = ?
        GROUP BY s.student_id, u.full_name, s.admission_number, s.class, s.stream
        ORDER BY s.class, s.stream, u.full_name
        LIMIT 20
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $assigned_students = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to load teacher information.';
    $stats = [];
    $classes = [];
    $recent_results = [];
    $subjects = [];
    $assigned_students = [];
}


include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="welcome-section mb-4">
            <h2><i class="fas fa-chalkboard-teacher"></i> Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
            <p class="text-muted">Here's an overview of your teaching activities and student progress.</p>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Teacher Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fas fa-users text-primary"></i>
            <h3><?php echo $stats['assigned_students'] ?? 0; ?></h3>
            <p>Assigned Students</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fas fa-clipboard-list text-success"></i>
            <h3><?php echo $stats['total_results'] ?? 0; ?></h3>
            <p>Results Entered</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fas fa-book text-info"></i>
            <h3><?php echo $stats['subjects_taught'] ?? 0; ?></h3>
            <p>Subjects Taught</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fas fa-chart-line text-warning"></i>
            <h3><?php echo $stats['average_performance'] ?? 0; ?>%</h3>
            <p>Average Performance</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <!-- <h5><i class="fas fa-bolt"></i> Quick Actions</h5> -->
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <a href="/School_SMS/teacher/enter_marks.php" class="dashboard-card">
                            <div class="card h-100">
                                <div class="card-body">
                                    <i class="fas fa-plus-circle"></i>
                                    <h6>Enter Marks</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <a href="/School_SMS/teacher/view_classes.php" class="dashboard-card">
                            <div class="card h-100">
                                <div class="card-body">
                                    <i class="fas fa-school"></i>
                                    <h6>View Classes</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <a href="/School_SMS/teacher/view_results.php" class="dashboard-card">
                            <div class="card h-100">
                                <div class="card-body">
                                    <i class="fas fa-chart-bar"></i>
                                    <h6>View Results</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <a href="/School_SMS/teacher/my_students.php" class="dashboard-card">
                            <div class="card h-100">
                                <div class="card-body">
                                    <i class="fas fa-user-graduate"></i>
                                    <h6>My Students</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Classes Overview -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-school"></i> Classes Overview</h5>
            </div>
            <div class="card-body">
                <?php if (empty($classes)): ?>
                    <p class="text-muted">No classes assigned yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Students</th>
                                    <!-- <th>Actions</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($class['class']); ?></td>
                                        <td><?php echo htmlspecialchars($class['stream']); ?></td>
                                        <td><?php echo $class['student_count']; ?></td>
                                        <!-- <td> -->
                                            <!-- <a href="/School_SMS/teacher/view_class.php?class=<?php echo urlencode($class['class']); ?>&stream=<?php echo urlencode($class['stream']); ?>"  -->
                                               <!-- class="btn btn-sm btn-outline-primary">View</a> -->
                                        <!-- </td> -->
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Subjects Taught -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-book"></i> Subjects Taught</h5>
            </div>
            <div class="card-body">
                <?php if (empty($subjects)): ?>
                    <p class="text-muted">No subjects assigned yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Results</th>
                                    <th>Avg Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subject['subject']); ?></td>
                                        <td><?php echo $subject['result_count']; ?></td>
                                        <td><?php echo round($subject['avg_marks'], 1); ?>%</td>
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

<!-- Assigned Students -->
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-user-graduate"></i> Assigned Students</h5>
                <a href="/School_SMS/teacher/my_students.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($assigned_students)): ?>
                    <p class="text-muted">No students assigned yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Admission No</th>
                                    <th>Class</th>
                                    <th>Results</th>
                                    <th>Avg Score</th>
                                    <!-- <th>Actions</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['class'] . ' ' . $student['stream']); ?></td>
                                        <td><?php echo $student['result_count']; ?></td>
                                        <td><?php echo round($student['avg_marks'], 1); ?>%</td>
                                        <!-- <td> -->
                                            <!-- Removed absolute URL, use project relative -->
                                            <!-- <a href="/School_SMS/teacher/student_details.php?student_id=<?php echo urlencode($student['student_id']); ?>"  -->
                                               <!-- class="btn btn-sm btn-outline-primary">View</a> -->
                                        <!-- </td> -->
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Results -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-chart-bar"></i> Recent Results</h5>
                <a href="/School_SMS/teacher/view_results.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_results)): ?>
                    <p class="text-muted">No results entered yet.</p>
                <?php else: ?>
                    <div class="recent-results">
                        <?php foreach (array_slice($recent_results, 0, 8) as $result): ?>
                            <div class="result-item mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($result['student_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($result['subject']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <strong><?php echo $result['marks_obtained']; ?>/<?php echo $result['max_marks']; ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
