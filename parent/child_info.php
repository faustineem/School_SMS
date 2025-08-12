<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

// Define BASE_URL according to your environment:
define('BASE_URL', '/School_SMS'); // change '/School_SMS' to your project root folder or "" if at domain root

requireRole(['parent']);

$success = '';
$error = '';

try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name, u.email, u.phone, advisor.full_name as advisor_name, advisor.email as advisor_email, s.address
        FROM parent_student ps
        JOIN students s ON ps.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN users advisor ON s.advisor_id = advisor.user_id
        WHERE ps.parent_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $children = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to load children information.';
    $children = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4><i class="fas fa-child"></i> My Children</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($children)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No children found. Please contact the school administration to link your account.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($children as $child): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h5><i class="fas fa-user"></i> <?= htmlspecialchars($child['full_name']) ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Personal Information</h6>
                                                <p><strong>Admission Number:</strong> <?= htmlspecialchars($child['admission_number']) ?></p>
                                                <p><strong>Class:</strong> <?= htmlspecialchars($child['class'] . ' ' . $child['stream']) ?></p>
                                                <p><strong>Gender:</strong> <?= ucfirst(htmlspecialchars($child['gender'])) ?></p>
                                                <p><strong>Date of Birth:</strong> <?= date('M d, Y', strtotime($child['date_of_birth'])) ?></p>
                                                <p><strong>Email:</strong> <?= htmlspecialchars($child['email']) ?></p>
                                                <p><strong>Phone:</strong> <?= !empty($child['phone']) ? htmlspecialchars($child['phone']) : 'Not provided' ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Academic Information</h6>
                                                <?php if (!empty($child['advisor_name'])): ?>
                                                    <p><strong>Academic Advisor:</strong> <?= htmlspecialchars($child['advisor_name']) ?></p>
                                                    <p><strong>Advisor Email:</strong> <?= htmlspecialchars($child['advisor_email']) ?></p>
                                                <?php else: ?>
                                                    <p><strong>Academic Advisor:</strong> Not assigned</p>
                                                <?php endif; ?>
                                                
                                                <h6>Address</h6>
                                                <p><?= !empty($child['address']) ? htmlspecialchars($child['address']) : 'Not provided' ?></p>
                                                
                                                <div class="mt-3">
                                                    <a href="<?= BASE_URL ?>/parent/child_results.php?student_id=<?= $child['student_id'] ?>" class="btn btn-primary btn-sm me-2">
                                                        <i class="fas fa-award"></i> View Results
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/parent/child_fees.php?student_id=<?= $child['student_id'] ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-credit-card"></i> View Fees
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
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
