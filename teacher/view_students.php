<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['teacher']);

$class_filter = $_GET['class'] ?? '';
$stream_filter = $_GET['stream'] ?? '';
$subject_filter = $_GET['subject'] ?? '';

$success = '';
$error = '';

// Get students taught by this teacher
try {
    $where_conditions = ["r.teacher_id = ?"];
    $params = [$_SESSION['user_id']];
    
    if ($class_filter) {
        $where_conditions[] = "s.class = ?";
        $params[] = $class_filter;
    }
    
    if ($stream_filter) {
        $where_conditions[] = "s.stream = ?";
        $params[] = $stream_filter;
    }
    
    if ($subject_filter) {
        $where_conditions[] = "r.subject = ?";
        $params[] = $subject_filter;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.full_name, s.admission_number, s.class, s.stream, s.gender,
               u.email, u.phone, advisor.full_name as advisor_name,
               COUNT(DISTINCT r.subject) as subjects_taught,
               COUNT(DISTINCT r.result_id) as results_count,
               AVG(r.marks_obtained / r.max_marks * 100) as avg_percentage
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        JOIN results r ON s.student_id = r.student_id
        LEFT JOIN users advisor ON s.advisor_id = advisor.user_id
        WHERE $where_clause
        GROUP BY s.student_id
        ORDER BY s.class, s.stream, u.full_name
    ");
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    // Get available classes and subjects for filters
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.class 
        FROM students s 
        JOIN results r ON s.student_id = r.student_id 
        WHERE r.teacher_id = ? 
        ORDER BY s.class
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.stream 
        FROM students s 
        JOIN results r ON s.student_id = r.student_id 
        WHERE r.teacher_id = ? 
        ORDER BY s.stream
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $streams = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT r.subject 
        FROM results r 
        WHERE r.teacher_id = ? 
        ORDER BY r.subject
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $subjects = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to load student information.';
    $students = [];
    $classes = [];
    $streams = [];
    $subjects = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-users"></i> My Students</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-12">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select name="class" class="form-select">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo htmlspecialchars($class['class']); ?>" 
                                                <?php echo $class_filter === $class['class'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="stream" class="form-select">
                                    <option value="">All Streams</option>
                                    <?php foreach ($streams as $stream): ?>
                                        <option value="<?php echo htmlspecialchars($stream['stream']); ?>" 
                                                <?php echo $stream_filter === $stream['stream'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($stream['stream']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="subject" class="form-select">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo htmlspecialchars($subject['subject']); ?>" 
                                                <?php echo $subject_filter === $subject['subject'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="/teacher/view_students.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-users text-primary"></i>
                            <h3><?php echo count($students); ?></h3>
                            <p>Students Found</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-percentage text-success"></i>
                            <h3><?php echo count($students) > 0 ? number_format(array_sum(array_column($students, 'avg_percentage')) / count($students), 1) : 0; ?>%</h3>
                            <p>Average Performance</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-award text-info"></i>
                            <h3><?php echo array_sum(array_column($students, 'results_count')); ?></h3>
                            <p>Total Results</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-book text-warning"></i>
                            <h3><?php echo count($students) > 0 ? number_format(array_sum(array_column($students, 'subjects_taught')) / count($students), 1) : 0; ?></h3>
                            <p>Avg Subjects per Student</p>
                        </div>
                    </div>
                </div>
                
                <!-- Search -->
                <div class="mb-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search students by name or admission number...">
                </div>
                
                <!-- Students Table -->
                <?php if (empty($students)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No students found for the selected criteria.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="sortable">Student Name</th>
                                    <th class="sortable">Admission Number</th>
                                    <th class="sortable">Class</th>
                                    <th class="sortable">Gender</th>
                                    <th class="sortable">Email</th>
                                    <th class="sortable">Phone</th>
                                    <th>Subjects Taught</th>
                                    <th>Results Count</th>
                                    <th>Average %</th>
                                    <th>Academic Advisor</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['class'] . ' ' . $student['stream']); ?></td>
                                        <td><?php echo ucfirst($student['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $student['subjects_taught']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $student['results_count']; ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $avg_percentage = $student['avg_percentage'];
                                            $badge_class = 'bg-secondary';
                                            if ($avg_percentage >= 80) $badge_class = 'bg-success';
                                            elseif ($avg_percentage >= 70) $badge_class = 'bg-primary';
                                            elseif ($avg_percentage >= 60) $badge_class = 'bg-info';
                                            elseif ($avg_percentage >= 50) $badge_class = 'bg-warning';
                                            else $badge_class = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo number_format($avg_percentage, 1); ?>%
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['advisor_name'] ?? 'Not assigned'); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewStudentResults('<?php echo htmlspecialchars($student['admission_number']); ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="addMarks('<?php echo htmlspecialchars($student['admission_number']); ?>')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </td>
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

<script>
function viewStudentResults(admissionNumber) {
    // This would open a modal or navigate to student results
    alert('View results for student: ' + admissionNumber);
}

function addMarks(admissionNumber) {
    // Navigate to enter marks page
    window.location.href = '/teacher/enter_marks.php?student=' + encodeURIComponent(admissionNumber);
}
</script>

<?php include '../includes/footer.php'; ?>
