<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/roles.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$user = getUserData();
$role = $_SESSION['role'] ?? '';

// Redirect users to their role-specific dashboards
$currentPath = $_SERVER['PHP_SELF'];

if ($role === 'student' && strpos($currentPath, '/student/') === false) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}
if ($role === 'parent' && strpos($currentPath, '/parent/') === false) {
    header('Location: ' . BASE_URL . '/parent/dashboard.php');
    exit;
}
if ($role === 'teacher' && strpos($currentPath, '/teacher/') === false) {
    header('Location: ' . BASE_URL . '/teacher/dashboard.php');
    exit;
}
// Admin stays here

$stats = [];

try {
    if ($role === 'admin') {
        $stats['students'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
        $stats['teachers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
        $stats['parents']  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'parent'")->fetchColumn();
        $stats['exams']    = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();

    } elseif ($role === 'teacher') {
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT s.student_id) FROM students s 
                               JOIN results r ON s.student_id = r.student_id 
                               WHERE r.teacher_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['students'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM results WHERE teacher_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['results'] = $stmt->fetchColumn();

    } elseif ($role === 'student') {
        $stmt = $pdo->prepare("SELECT s.*, u.full_name as advisor_name FROM students s 
                               LEFT JOIN users u ON s.advisor_id = u.user_id 
                               WHERE s.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $student_info = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM results WHERE student_id = ?");
        $stmt->execute([$student_info['student_id']]);
        $stats['results'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM fees WHERE student_id = ?");
        $stmt->execute([$student_info['student_id']]);
        $stats['fees_paid'] = $stmt->fetchColumn() ?? 0;

    } elseif ($role === 'parent') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM parent_student ps 
                               JOIN students s ON ps.student_id = s.student_id 
                               WHERE ps.parent_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['children'] = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    // You may log the error: error_log($e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<!-- HTML DASHBOARD START -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-tachometer-alt"></i> Dashboard</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h2>Welcome, <?= htmlspecialchars($user['full_name']) ?>!</h2>
                        <p class="text-muted">Role: <?= getRoleDisplayName($role) ?></p>

                        <?php if ($role === 'student' && isset($student_info)): ?>
                            <div class="alert alert-info">
                                <h6>Student Information</h6>
                                <p><strong>Admission Number:</strong> <?= htmlspecialchars($student_info['admission_number']) ?></p>
                                <p><strong>Class:</strong> <?= htmlspecialchars($student_info['class']) ?> <?= htmlspecialchars($student_info['stream']) ?></p>
                                <?php if ($student_info['advisor_name']): ?>
                                    <p><strong>Academic Advisor:</strong> <?= htmlspecialchars($student_info['advisor_name']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mt-4">
    <?php if ($role === 'admin'): ?>
        <?php
        $cards = [
            ['students', 'users', 'Students', 'primary'],
            ['teachers', 'chalkboard-teacher', 'Teachers', 'success'],
            ['parents', 'user-friends', 'Parents', 'info'],
            ['exams', 'clipboard-list', 'Exams', 'warning'],
        ];
        foreach ($cards as [$key, $icon, $label, $color]): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card">
                    <i class="fas fa-<?= $icon ?> text-<?= $color ?>"></i>
                    <h3><?= number_format($stats[$key] ?? 0) ?></h3>
                    <p><?= $label ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php elseif ($role === 'teacher'): ?>
        <div class="col-md-6 mb-4">
            <div class="stat-card">
                <i class="fas fa-users text-primary"></i>
                <h3><?= number_format($stats['students'] ?? 0) ?></h3>
                <p>Students Taught</p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="stat-card">
                <i class="fas fa-award text-success"></i>
                <h3><?= number_format($stats['results'] ?? 0) ?></h3>
                <p>Results Entered</p>
            </div>
        </div>
    <?php elseif ($role === 'student'): ?>
        <div class="col-md-6 mb-4">
            <div class="stat-card">
                <i class="fas fa-award text-primary"></i>
                <h3><?= number_format($stats['results'] ?? 0) ?></h3>
                <p>Exam Results</p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="stat-card">
                <i class="fas fa-money-bill text-success"></i>
                <h3>UGX <?= number_format($stats['fees_paid'] ?? 0) ?></h3>
                <p>Fees Paid</p>
            </div>
        </div>
    <?php elseif ($role === 'parent'): ?>
        <div class="col-md-12 mb-4">
            <div class="stat-card">
                <i class="fas fa-child text-primary"></i>
                <h3><?= number_format($stats['children'] ?? 0) ?></h3>
                <p>Children</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-th-large"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $menuItems = getMenuItems($role);
                    foreach ($menuItems as $item):
                        if ($item['title'] !== 'Dashboard'):
                    ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <a href="<?= BASE_URL . $item['url'] ?>" class="dashboard-card">
                                <div class="card h-100 text-center">
                                    <div class="card-body">
                                        <i class="fas fa-<?= $item['icon'] ?> fa-2x mb-2"></i>
                                        <h6><?= htmlspecialchars($item['title']) ?></h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
