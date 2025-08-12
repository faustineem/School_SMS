<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';
require_once '../config/constants.php'; // for BASE_URL

requireRole(['parent']);

$student_id = $_GET['student_id'] ?? '';
$student = null;
$fees = [];
$error = '';
$total_paid = 0;

// Redirect if ID is invalid
if (!is_numeric($student_id)) {
    header("Location: " . BASE_URL . "/parent/child_info.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name
        FROM parent_student ps
        JOIN students s ON ps.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE ps.parent_id = ? AND s.student_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $error = 'Student not found or you do not have permission to view this information.';
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM fees
            WHERE student_id = ?
            ORDER BY payment_date DESC
        ");
        $stmt->execute([$student_id]);
        $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_paid = array_sum(array_column($fees, 'amount_paid'));
    }

} catch (PDOException $e) {
    $error = 'Failed to load fee information.';
}

$expected_fees = 1500000; // TZS
$balance = $expected_fees - $total_paid;

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-credit-card"></i>
                    <?= $student ? htmlspecialchars($student['full_name']) . "'s" : 'Child'; ?> Fee Status
                </h4>
                <div>
                    <a href="<?= BASE_URL ?>/parent/child_info.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Children
                    </a>
                    <button class="btn btn-outline-primary no-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Statement
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php elseif ($student): ?>
                    <!-- Student Info -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h5>Student Information</h5>
                            <p><strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
                            <p><strong>Admission Number:</strong> <?= htmlspecialchars($student['admission_number']) ?></p>
                            <p><strong>Class:</strong> <?= htmlspecialchars($student['class'] . ' ' . $student['stream']) ?></p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-money-bill-wave fa-4x text-success"></i>
                        </div>
                    </div>

                    <!-- Fee Stats -->
                    <div class="row mb-4">
                        <?php
                        $percent_paid = $expected_fees > 0 ? ($total_paid / $expected_fees) * 100 : 0;
                        ?>
                        <div class="col-md-3">
                            <div class="stat-card bg-light p-3 text-center shadow-sm rounded">
                                <i class="fas fa-coins text-primary fa-2x"></i>
                                <h4 class="my-2">TZS <?= number_format($expected_fees) ?></h4>
                                <small>Expected Fees</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-light p-3 text-center shadow-sm rounded">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                                <h4 class="my-2">TZS <?= number_format($total_paid) ?></h4>
                                <small>Total Paid</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-light p-3 text-center shadow-sm rounded">
                                <i class="fas <?= $balance > 0 ? 'fa-exclamation-triangle text-warning' : 'fa-thumbs-up text-success' ?> fa-2x"></i>
                                <h4 class="my-2">TZS <?= number_format($balance) ?></h4>
                                <small><?= $balance > 0 ? 'Balance Due' : 'Overpaid' ?></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-light p-3 text-center shadow-sm rounded">
                                <i class="fas fa-percentage text-info fa-2x"></i>
                                <h4 class="my-2"><?= number_format($percent_paid, 1) ?>%</h4>
                                <small>Paid</small>
                            </div>
                        </div>
                    </div>

                    <!-- Status Message -->
                    <?php if ($balance > 0): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            Outstanding balance: <strong>TZS <?= number_format($balance) ?></strong>
                        </div>
                    <?php elseif ($balance < 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Overpaid by <strong>TZS <?= number_format(abs($balance)) ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Fees fully paid.
                        </div>
                    <?php endif; ?>

                    <!-- Payment History -->
                    <h5>Payment History</h5>
                    <?php if (empty($fees)): ?>
                        <div class="alert alert-info"><i class="fas fa-info-circle"></i> No payments recorded.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount (TZS)</th>
                                        <th>Method</th>
                                        <th>Receipt</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fees as $fee): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($fee['payment_date'])) ?></td>
                                            <td><?= number_format($fee['amount_paid']) ?></td>
                                            <td><?= htmlspecialchars($fee['payment_method']) ?></td>
                                            <td><?= htmlspecialchars($fee['receipt_number']) ?></td>
                                            <td><span class="badge bg-success"><i class="fas fa-check"></i> Confirmed</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Payment Instructions -->
                    <div class="mt-4">
                        <h5>Payment Instructions</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-light border shadow-sm">
                                    <h6><i class="fas fa-university"></i> Bank Transfer</h6>
                                    <p><strong>Bank:</strong> NMB Bank</p>
                                    <p><strong>Account Name:</strong> Nyampulukano Secondary School</p>
                                    <p><strong>Account Number:</strong> 123456789000</p>
                                    <p><strong>Branch:</strong> Bukoba</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-light border shadow-sm">
                                    <h6><i class="fas fa-mobile-alt"></i> Mobile Money</h6>
                                    <p><strong>Vodacom:</strong> *150*00# → Lipa Namba → 123456</p>
                                    <p><strong>Airtel:</strong> *150*60# → Pay School → Code: 78910</p>
                                    <p><strong>MixByYas App:</strong> Use School ID: NYAMPU123</p>
                                    <p><strong>Reference:</strong> Use Admission Number</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
