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
        $fees = [];
        $total_paid = 0;
    } else {
        // Get all fee payments for this student
        $stmt = $pdo->prepare("
            SELECT * FROM fees 
            WHERE student_id = ? 
            ORDER BY payment_date DESC
        ");
        $stmt->execute([$student['student_id']]);
        $fees = $stmt->fetchAll();
        
        // Calculate total paid
        $total_paid = array_sum(array_column($fees, 'amount_paid'));
    }
    
} catch (PDOException $e) {
    $error = 'Failed to load fee information.';
    $fees = [];
    $total_paid = 0;
}

// Set expected fees (this would typically come from a settings table)
$expected_fees = 1500000; // TZS 1,500,000 per year
$balance = $expected_fees - $total_paid;

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-credit-card"></i> My Fee Status</h4>
                <button class="btn btn-outline-primary no-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Statement
                </button>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-money-bill-wave text-primary"></i>
                            <h3>TZS <?php echo number_format($expected_fees); ?></h3>
                            <p>Expected Fees</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-check-circle text-success"></i>
                            <h3>TZS <?php echo number_format($total_paid); ?></h3>
                            <p>Total Paid</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-<?php echo $balance > 0 ? 'exclamation-triangle text-warning' : 'thumbs-up text-success'; ?>"></i>
                            <h3>TZS <?php echo number_format($balance); ?></h3>
                            <p><?php echo $balance > 0 ? 'Balance Due' : 'Overpaid'; ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <i class="fas fa-percentage text-info"></i>
                            <h3><?php echo number_format(($total_paid / $expected_fees) * 100, 1); ?>%</h3>
                            <p>Fees Paid</p>
                        </div>
                    </div>
                </div>
                
                <?php if ($balance > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        You have an outstanding balance of <strong>TZS <?php echo number_format($balance); ?></strong>. 
                        Please contact the school administration for payment details.
                    </div>
                <?php elseif ($balance < 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        You have overpaid by <strong>TZS <?php echo number_format(abs($balance)); ?></strong>. 
                        This amount will be applied to next term's fees.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        Your fees are fully paid. Thank you!
                    </div>
                <?php endif; ?>
                
                <h5>Payment History</h5>
                <?php if (empty($fees)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No fee payments recorded yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="sortable">Payment Date</th>
                                    <th class="sortable">Amount Paid</th>
                                    <th class="sortable">Payment Method</th>
                                    <th class="sortable">Receipt Number</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fees as $fee): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($fee['payment_date'])); ?></td>
                                        <td>TZS <?php echo number_format($fee['amount_paid']); ?></td>
                                        <td><?php echo htmlspecialchars($fee['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($fee['receipt_number']); ?></td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Confirmed
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <h5>Payment Instructions</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-light">
                                <h6><i class="fas fa-university"></i> Bank Transfer</h6>
                                <p><strong>Bank:</strong> Stanbic Bank Uganda</p>
                                <p><strong>Account Name:</strong> Nyampulukano Secondary School</p>
                                <p><strong>Account Number:</strong> 9030012345678</p>
                                <p><strong>Branch:</strong> Kampala Main Branch</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-light">
                                <h6><i class="fas fa-mobile-alt"></i> Mobile Money</h6>
                                <p><strong>MTN:</strong> *165*3# (School Code: 12345)</p>
                                <p><strong>Airtel:</strong> *185*9# (School Code: 12345)</p>
                                <p><strong>Note:</strong> Use your admission number as reference</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
