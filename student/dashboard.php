<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['student']);

$success = '';
$error = '';

try {
    // Get student information
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name, u.email, u.phone, 
               advisor.full_name as advisor_name, advisor.email as advisor_email
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN users advisor ON s.advisor_id = advisor.user_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        $error = 'Student record not found.';
        $stats = [];
    } else {
        // Get student statistics
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM results WHERE student_id = ?");
        $stmt->execute([$student['student_id']]);
        $stats['total_results'] = $stmt->fetch()['count'];
        
        $stmt = $pdo->prepare("SELECT AVG(marks_obtained) as avg FROM results WHERE student_id = ?");
        $stmt->execute([$student['student_id']]);
        $stats['average_marks'] = round($stmt->fetch()['avg'] ?? 0, 1);
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) as total FROM fees WHERE student_id = ?");
        $stmt->execute([$student['student_id']]);
        $stats['total_fees_paid'] = $stmt->fetch()['total'];
        
        $expected_fees = 1500000; // TZS 1,500,000
        $stats['fee_balance'] = $expected_fees - $stats['total_fees_paid'];
        
        // Get recent results
        $stmt = $pdo->prepare("
            SELECT r.*, e.exam_name, e.term, e.year
            FROM results r
            JOIN exams e ON r.exam_id = e.exam_id
            WHERE r.student_id = ?
            ORDER BY e.year DESC, e.term DESC, r.subject
            LIMIT 5
        ");
        $stmt->execute([$student['student_id']]);
        $recent_results = $stmt->fetchAll();
        
        // Get messages for students
        $stmt = $pdo->prepare("
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE m.recipient_role IN ('student', 'all')
            ORDER BY m.sent_at DESC
            LIMIT 3
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $error = 'Failed to load student information.';
    $student = null;
    $stats = [];
    $recent_results = [];
    $messages = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="welcome-section mb-4">
            <h2><i class="fas fa-user-graduate"></i> Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
            <p class="text-muted">Here's your academic overview and important information.</p>
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

<?php if ($student): ?>
    <!-- Student Profile Card -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-id-card"></i> My Profile</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="profile-image-placeholder">
                            <i class="fas fa-user-circle fa-5x text-muted"></i>
                        </div>
                    </div>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Admission No:</strong></td>
                            <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Class:</strong></td>
                            <td><?php echo htmlspecialchars($student['class'] . ' ' . $student['stream']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Gender:</strong></td>
                            <td><?php echo ucfirst(htmlspecialchars($student['gender'])); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Date of Birth:</strong></td>
                            <td><?php echo date('M d, Y', strtotime($student['date_of_birth'])); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                        </tr>
                        <?php if ($student['advisor_name']): ?>
                        <tr>
                            <td><strong>Advisor:</strong></td>
                            <td><?php echo htmlspecialchars($student['advisor_name']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Academic Statistics -->
            <div class="row mb-4 text-center">
                <div class="col-md-3">
                    <div class="stat-card border rounded p-3">
                        <i class="fas fa-clipboard-list text-primary fa-2x"></i>
                        <h3 class="mt-2"><?php echo $stats['total_results']; ?></h3>
                        <p>Total Results</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border rounded p-3">
                        <i class="fas fa-chart-line text-success fa-2x"></i>
                        <h3 class="mt-2"><?php echo $stats['average_marks']; ?>%</h3>
                        <p>Average Score</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border rounded p-3">
                        <i class="fas fa-money-bill-wave text-info fa-2x"></i>
                        <h3 class="mt-2">TZS <?php echo number_format($stats['total_fees_paid']); ?></h3>
                        <p>Fees Paid</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php
                        $balance_icon = $stats['fee_balance'] > 0 ? 'exclamation-triangle text-warning' : 'check-circle text-success';
                    ?>
                    <div class="stat-card border rounded p-3">
                        <i class="fas fa-<?= $balance_icon ?> fa-2x"></i>
                        <h3 class="mt-2">TZS <?php echo number_format($stats['fee_balance']); ?></h3>
                        <p>Balance</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Results -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-secondary text-white">
                    <h5><i class="fas fa-chart-bar"></i> Recent Results</h5>
                    <a href="<?= BASE_URL ?>/student/results.php" class="btn btn-sm btn-outline-light">View All</a>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($recent_results)): ?>
                        <p class="text-muted">No results available yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Exam</th>
                                        <th>Subject</th>
                                        <th>Score</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_results as $result): ?>
                                        <?php
                                        $percentage = ($result['marks_obtained'] / $result['max_marks']) * 100;
                                        $grade = $percentage >= 90 ? 'A' : ($percentage >= 80 ? 'B' : ($percentage >= 70 ? 'C' : ($percentage >= 60 ? 'D' : 'F')));
                                        $grade_class = $percentage >= 70 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['subject']); ?></td>
                                            <td><?php echo $result['marks_obtained']; ?>/<?php echo $result['max_marks']; ?></td>
                                            <td><span class="badge bg-<?= $grade_class; ?>"><?= $grade ?></span></td>
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
    
    <!-- Messages -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-secondary text-white">
                    <h5><i class="fas fa-envelope"></i> Recent Messages</h5>
                    <a href="<?= BASE_URL ?>/student/messages.php" class="btn btn-sm btn-outline-light">View All</a>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($messages)): ?>
                        <p class="text-muted">No messages available.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($messages as $message): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($message['title']); ?></h6>
                                            <p class="card-text"><?php echo substr(htmlspecialchars($message['body']), 0, 100); ?>...</p>
                                            <small class="text-muted">
                                                From: <?php echo htmlspecialchars($message['sender_name']); ?><br>
                                                <?php echo date('M d, Y', strtotime($message['sent_at'])); ?>
                                            </small>
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
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <a href="<?= BASE_URL ?>/student/results.php" class="dashboard-card text-decoration-none">
                                <div class="card h-100 text-center">
                                    <div class="card-body">
                                        <i class="fas fa-chart-bar fa-2x"></i>
                                        <h6 class="mt-2">View Results</h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <a href="<?= BASE_URL ?>/student/fees.php" class="dashboard-card text-decoration-none">
                                <div class="card h-100 text-center">
                                    <div class="card-body">
                                        <i class="fas fa-credit-card fa-2x"></i>
                                        <h6 class="mt-2">Fee Status</h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <a href="<?= BASE_URL ?>/student/messages.php" class="dashboard-card text-decoration-none">
                                <div class="card h-100 text-center">
                                    <div class="card-body">
                                        <i class="fas fa-envelope fa-2x"></i>
                                        <h6 class="mt-2">Messages</h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <a href="<?= BASE_URL ?>/users/profile.php" class="dashboard-card text-decoration-none">
                                <div class="card h-100 text-center">
                                    <div class="card-body">
                                        <i class="fas fa-user-edit fa-2x"></i>
                                        <h6 class="mt-2">Edit Profile</h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
