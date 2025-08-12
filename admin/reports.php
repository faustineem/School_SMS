<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['admin']);

$report_type = $_GET['type'] ?? 'students';
$success = '';
$error = '';

// Handle report generation
$report_data = [];
$report_title = '';

try {
    switch ($report_type) {
        case 'students':
            $stmt = $pdo->query("
                SELECT u.full_name, u.email, u.phone, s.admission_number, s.gender, 
                       s.date_of_birth, s.class, s.stream, s.address, 
                       advisor.full_name as advisor_name, u.created_at
                FROM users u 
                JOIN students s ON u.user_id = s.user_id 
                LEFT JOIN users advisor ON s.advisor_id = advisor.user_id
                WHERE u.role = 'student' 
                ORDER BY s.class, s.stream, u.full_name
            ");
            $report_data = $stmt->fetchAll();
            $report_title = 'Students Report';
            break;
            
        case 'teachers':
            $stmt = $pdo->query("
                SELECT u.full_name, u.email, u.phone, u.created_at,
                       COUNT(DISTINCT s.student_id) as students_advised,
                       COUNT(DISTINCT r.result_id) as results_entered
                FROM users u 
                LEFT JOIN students s ON u.user_id = s.advisor_id
                LEFT JOIN results r ON u.user_id = r.teacher_id
                WHERE u.role = 'teacher' 
                GROUP BY u.user_id
                ORDER BY u.full_name
            ");
            $report_data = $stmt->fetchAll();
            $report_title = 'Teachers Report';
            break;
            
        case 'parents':
            $stmt = $pdo->query("
                SELECT u.full_name, u.email, u.phone, u.created_at,
                       COUNT(ps.student_id) as children_count,
                       GROUP_CONCAT(DISTINCT student_u.full_name SEPARATOR ', ') as children_names
                FROM users u 
                LEFT JOIN parent_student ps ON u.user_id = ps.parent_id
                LEFT JOIN students s ON ps.student_id = s.student_id
                LEFT JOIN users student_u ON s.user_id = student_u.user_id
                WHERE u.role = 'parent' 
                GROUP BY u.user_id
                ORDER BY u.full_name
            ");
            $report_data = $stmt->fetchAll();
            $report_title = 'Parents Report';
            break;
            
        case 'fees':
            $stmt = $pdo->query("
                SELECT u.full_name, s.admission_number, s.class, s.stream,
                       SUM(f.amount_paid) as total_paid,
                       COUNT(f.fee_id) as payment_count,
                       MAX(f.payment_date) as last_payment
                FROM users u 
                JOIN students s ON u.user_id = s.user_id 
                LEFT JOIN fees f ON s.student_id = f.student_id
                WHERE u.role = 'student'
                GROUP BY s.student_id
                ORDER BY s.class, s.stream, u.full_name
            ");
            $report_data = $stmt->fetchAll();
            $report_title = 'Fees Report';
            break;
            
        case 'results':
            $class_filter = $_GET['class'] ?? '';
            $exam_filter = $_GET['exam'] ?? '';
            
            $where_conditions = [];
            $params = [];
            
            if ($class_filter) {
                $where_conditions[] = "s.class = ?";
                $params[] = $class_filter;
            }
            
            if ($exam_filter) {
                $where_conditions[] = "e.exam_id = ?";
                $params[] = $exam_filter;
            }
            
            $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
            
            $stmt = $pdo->prepare("
                SELECT u.full_name, s.admission_number, s.class, s.stream,
                       e.exam_name, e.term, e.year, r.subject, r.marks_obtained, r.max_marks,
                       (r.marks_obtained / r.max_marks * 100) as percentage
                FROM users u 
                JOIN students s ON u.user_id = s.user_id 
                JOIN results r ON s.student_id = r.student_id
                JOIN exams e ON r.exam_id = e.exam_id
                $where_clause
                ORDER BY e.year DESC, e.term DESC, s.class, s.stream, u.full_name, r.subject
            ");
            $stmt->execute($params);
            $report_data = $stmt->fetchAll();
            $report_title = 'Results Report';
            break;
    }
    
    // Get additional data for filters
    $stmt = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class");
    $classes = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT * FROM exams ORDER BY year DESC, term DESC");
    $exams = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to generate report: ' . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-file-text"></i> Reports</h4>
                <button class="btn btn-outline-primary no-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4 no-print">
                    <div class="col-md-8">
                        <div class="btn-group" role="group">
                            <a href="?type=students" class="btn btn-<?php echo $report_type === 'students' ? 'primary' : 'outline-primary'; ?>">
                                <i class="fas fa-users"></i> Students
                            </a>
                            <a href="?type=teachers" class="btn btn-<?php echo $report_type === 'teachers' ? 'primary' : 'outline-primary'; ?>">
                                <i class="fas fa-chalkboard-teacher"></i> Teachers
                            </a>
                            <a href="?type=parents" class="btn btn-<?php echo $report_type === 'parents' ? 'primary' : 'outline-primary'; ?>">
                                <i class="fas fa-user-friends"></i> Parents
                            </a>
                            <a href="?type=fees" class="btn btn-<?php echo $report_type === 'fees' ? 'primary' : 'outline-primary'; ?>">
                                <i class="fas fa-credit-card"></i> Fees
                            </a>
                            <a href="?type=results" class="btn btn-<?php echo $report_type === 'results' ? 'primary' : 'outline-primary'; ?>">
                                <i class="fas fa-award"></i> Results
                            </a>
                        </div>
                    </div>
                    <?php if ($report_type === 'results'): ?>
                        <div class="col-md-4">
                            <form method="GET" class="d-flex gap-2">
                                <input type="hidden" name="type" value="results">
                                <select name="class" class="form-select form-select-sm">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo htmlspecialchars($class['class']); ?>" 
                                                <?php echo ($_GET['class'] ?? '') === $class['class'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="exam" class="form-select form-select-sm">
                                    <option value="">All Exams</option>
                                    <?php foreach ($exams as $exam): ?>
                                        <option value="<?php echo $exam['exam_id']; ?>" 
                                                <?php echo ($_GET['exam'] ?? '') == $exam['exam_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($exam['exam_name'] . ' - ' . $exam['term'] . ' ' . $exam['year']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mb-4">
                    <h3><?php echo $report_title; ?></h3>
                    <p class="text-muted">Generated on <?php echo date('F d, Y \a\t g:i A'); ?></p>
                </div>
                
                <?php if (empty($report_data)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No data available for this report.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <?php if ($report_type === 'students'): ?>
                                        <th>Name</th>
                                        <th>Admission No.</th>
                                        <th>Class</th>
                                        <th>Gender</th>
                                        <th>Date of Birth</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Advisor</th>
                                    <?php elseif ($report_type === 'teachers'): ?>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Students Advised</th>
                                        <th>Results Entered</th>
                                        <th>Joined Date</th>
                                    <?php elseif ($report_type === 'parents'): ?>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Children Count</th>
                                        <th>Children Names</th>
                                        <th>Joined Date</th>
                                    <?php elseif ($report_type === 'fees'): ?>
                                        <th>Student Name</th>
                                        <th>Admission No.</th>
                                        <th>Class</th>
                                        <th>Total Paid</th>
                                        <th>Payment Count</th>
                                        <th>Last Payment</th>
                                    <?php elseif ($report_type === 'results'): ?>
                                        <th>Student Name</th>
                                        <th>Admission No.</th>
                                        <th>Class</th>
                                        <th>Exam</th>
                                        <th>Subject</th>
                                        <th>Marks</th>
                                        <th>Max Marks</th>
                                        <th>Percentage</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <?php if ($report_type === 'students'): ?>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['admission_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['class'] . ' ' . $row['stream']); ?></td>
                                            <td><?php echo ucfirst($row['gender']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['date_of_birth'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['advisor_name'] ?? 'Not assigned'); ?></td>
                                        <?php elseif ($report_type === 'teachers'): ?>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo $row['students_advised']; ?></td>
                                            <td><?php echo $row['results_entered']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <?php elseif ($report_type === 'parents'): ?>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo $row['children_count']; ?></td>
                                            <td><?php echo htmlspecialchars($row['children_names'] ?? 'None'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <?php elseif ($report_type === 'fees'): ?>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['admission_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['class'] . ' ' . $row['stream']); ?></td>
                                            <td>TZS <?php echo number_format($row['total_paid'] ?? 0); ?></td>
                                            <td><?php echo $row['payment_count']; ?></td>
                                            <td><?php echo $row['last_payment'] ? date('M d, Y', strtotime($row['last_payment'])) : 'N/A'; ?></td>
                                        <?php elseif ($report_type === 'results'): ?>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['admission_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['class'] . ' ' . $row['stream']); ?></td>
                                            <td><?php echo htmlspecialchars($row['exam_name'] . ' - ' . $row['term'] . ' ' . $row['year']); ?></td>
                                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                            <td><?php echo number_format($row['marks_obtained'], 1); ?></td>
                                            <td><?php echo number_format($row['max_marks'], 1); ?></td>
                                            <td><?php echo number_format($row['percentage'], 1); ?>%</td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p class="text-muted">Total Records: <?php echo count($report_data); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
