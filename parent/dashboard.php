<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../config/db.php';

requireRole(['parent']);

$success = '';
$error = '';

try {
    // Get children linked to this parent
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name, u.email, u.phone,
               advisor.full_name AS advisor_name, advisor.email AS advisor_email
        FROM parent_student ps
        JOIN students s ON ps.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN users advisor ON s.advisor_id = advisor.user_id
        WHERE ps.parent_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll();

    $stats = [];
    $recent_activities = [];

    if (!empty($children)) {
        foreach ($children as $child) {
            // Total results count
            $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM results WHERE student_id = ?");
            $stmt->execute([$child['student_id']]);
            $stats[$child['student_id']]['total_results'] = (int) $stmt->fetch()['count'];

            // Average marks
            $stmt = $pdo->prepare("SELECT AVG(marks_obtained) AS avg FROM results WHERE student_id = ?");
            $stmt->execute([$child['student_id']]);
            $avg = $stmt->fetch()['avg'];
            $stats[$child['student_id']]['average_marks'] = round($avg ?? 0, 1);

            // Total fees paid
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) AS total FROM fees WHERE student_id = ?");
            $stmt->execute([$child['student_id']]);
            $stats[$child['student_id']]['total_fees_paid'] = (float) $stmt->fetch()['total'];

            // Fee balance
            $expected_fees = 1500000; // Adjust this as needed
            $stats[$child['student_id']]['fee_balance'] = $expected_fees - $stats[$child['student_id']]['total_fees_paid'];

            // Recent results (limit 3)
            $stmt = $pdo->prepare("
                SELECT r.*, e.exam_name, e.term, e.year, s.full_name AS child_name
                FROM results r
                JOIN exams e ON r.exam_id = e.exam_id
                JOIN students st ON r.student_id = st.student_id
                JOIN users s ON st.user_id = s.user_id
                WHERE r.student_id = ?
                ORDER BY e.year DESC, e.term DESC, r.subject
                LIMIT 3
            ");
            $stmt->execute([$child['student_id']]);
            $child_results = $stmt->fetchAll();
            $recent_activities = array_merge($recent_activities, $child_results);
        }
    }

    // Fetch recent messages for parent
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.recipient_role IN ('parent', 'all')
        ORDER BY m.sent_at DESC
        LIMIT 3
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = 'Failed to load parent dashboard information.';
    $children = [];
    $stats = [];
    $recent_activities = [];
    $messages = [];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="welcome-section mb-4">
            <h2><i class="fas fa-user-friends"></i> Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h2>
            <p class="text-muted">Here's an overview of your child's academic progress and important information.</p>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($children)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No children found. Please contact the school administration to link your account to your child's profile.
            </div>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($children as $child): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-user-graduate"></i> <?= htmlspecialchars($child['full_name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Profile -->
                            <div class="col-md-4">
                                <div class="card border-info mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6><i class="fas fa-id-card"></i> Profile Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless mb-0">
                                            <tr><td><strong>Admission No:</strong></td><td><?= htmlspecialchars($child['admission_number']) ?></td></tr>
                                            <tr><td><strong>Class:</strong></td><td><?= htmlspecialchars($child['class'] . ' ' . $child['stream']) ?></td></tr>
                                            <tr><td><strong>Gender:</strong></td><td><?= ucfirst(htmlspecialchars($child['gender'])) ?></td></tr>
                                            <tr><td><strong>Date of Birth:</strong></td><td><?= date('M d, Y', strtotime($child['date_of_birth'])) ?></td></tr>
                                            <?php if (!empty($child['advisor_name'])): ?>
                                            <tr>
                                                <td><strong>Academic Advisor:</strong></td>
                                                <td>
                                                    <?= htmlspecialchars($child['advisor_name']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($child['advisor_email']) ?></small>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats and Actions -->
                            <div class="col-md-8">
                                <div class="row mb-3 text-center">
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                                            <h3><?= $stats[$child['student_id']]['total_results'] ?? 0 ?></h3>
                                            <p>Total Results</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-chart-line fa-2x text-success"></i>
                                            <h3><?= $stats[$child['student_id']]['average_marks'] ?? 0 ?>%</h3>
                                            <p>Average Score</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3">
                                            <i class="fas fa-money-bill-wave fa-2x text-info"></i>
                                            <h3>TZS <?= number_format($stats[$child['student_id']]['total_fees_paid'] ?? 0) ?></h3>
                                            <p>Fees Paid</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <?php
                                            $fee_balance = $stats[$child['student_id']]['fee_balance'] ?? 0;
                                            $icon = $fee_balance > 0 ? 'exclamation-triangle text-warning' : 'check-circle text-success';
                                        ?>
                                        <div class="border rounded p-3">
                                            <i class="fas fa-<?= $icon ?> fa-2x"></i>
                                            <h3>TZS <?= number_format($fee_balance) ?></h3>
                                            <p>Balance</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <a href="<?= BASE_URL ?>/parent/child_results.php?student_id=<?= $child['student_id'] ?>" class="btn btn-outline-primary w-100 btn-sm">
                                            <i class="fas fa-chart-bar"></i> Results
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <a href="<?= BASE_URL ?>/parent/child_fees.php?student_id=<?= $child['student_id'] ?>" class="btn btn-outline-success w-100 btn-sm">
                                            <i class="fas fa-credit-card"></i> Fee Status
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <a href="<?= BASE_URL ?>/parent/child_info.php?student_id=<?= $child['student_id'] ?>" class="btn btn-outline-info w-100 btn-sm">
                                            <i class="fas fa-info-circle"></i> Profile
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <a href="<?= BASE_URL ?>/parent/child_messages.php" class="btn btn-outline-secondary w-100 btn-sm">
                                            <i class="fas fa-envelope"></i> Messages
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-chart-bar"></i> Recent Academic Activities</h5>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($recent_activities)): ?>
                        <p class="text-muted">No recent activities available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Child</th>
                                        <th>Exam</th>
                                        <th>Subject</th>
                                        <th>Score</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $activity): 
                                        $percentage = $activity['max_marks'] > 0 
                                            ? ($activity['marks_obtained'] / $activity['max_marks']) * 100 
                                            : 0;
                                        if ($percentage >= 90) $grade = 'A';
                                        elseif ($percentage >= 80) $grade = 'B';
                                        elseif ($percentage >= 70) $grade = 'C';
                                        elseif ($percentage >= 60) $grade = 'D';
                                        else $grade = 'F';

                                        $grade_class = $percentage >= 70 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($activity['child_name']) ?></td>
                                            <td><?= htmlspecialchars($activity['exam_name']) ?></td>
                                            <td><?= htmlspecialchars($activity['subject']) ?></td>
                                            <td><?= $activity['marks_obtained'] ?>/<?= $activity['max_marks'] ?></td>
                                            <td><span class="badge bg-<?= $grade_class ?>"><?= $grade ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-secondary text-white">
                    <h5><i class="fas fa-envelope"></i> Messages</h5>
                    <a href="<?= BASE_URL ?>/parent/child_messages.php" class="btn btn-sm btn-outline-light">View All</a>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($messages)): ?>
                        <p class="text-muted">No messages available.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="mb-3">
                                <div class="card">
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-1"><?= htmlspecialchars($message['title']) ?></h6>
                                        <p class="card-text mb-1"><?= htmlspecialchars(substr($message['body'], 0, 80)) ?>...</p>
                                        <small class="text-muted">
                                            From: <?= htmlspecialchars($message['sender_name']) ?><br>
                                            <?= date('M d, Y', strtotime($message['sent_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
