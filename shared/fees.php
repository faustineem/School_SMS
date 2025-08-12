<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireAuth();

$success = '';
$error = '';

// Handle form submission (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasPermission('manage_students')) {
    $action = $_POST['action'];
    
    if ($action === 'add_payment') {
        $student_id = $_POST['student_id'];
        $amount_paid = $_POST['amount_paid'];
        $payment_date = $_POST['payment_date'];
        $payment_method = $_POST['payment_method'];
        $receipt_number = $_POST['receipt_number'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO fees (student_id, amount_paid, payment_date, payment_method, receipt_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $amount_paid, $payment_date, $payment_method, $receipt_number]);
            $success = 'Fee payment recorded successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to record payment. Please try again.';
        }
    }
    
    if ($action === 'delete_payment') {
        $fee_id = $_POST['fee_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM fees WHERE fee_id = ?");
            $stmt->execute([$fee_id]);
            $success = 'Payment record deleted successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to delete payment record.';
        }
    }
}

// Handle search and filter
$search = $_GET['search'] ?? '';
$class_filter = $_GET['class'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';

// Build query based on user role
$where_conditions = [];
$params = [];

if ($_SESSION['role'] === 'student') {
    // Students can only see their own fees
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_record = $stmt->fetch();
    
    if ($student_record) {
        $where_conditions[] = "s.student_id = ?";
        $params[] = $student_record['student_id'];
    } else {
        $error = 'Student record not found.';
    }
} elseif ($_SESSION['role'] === 'parent') {
    // Parents can only see their children's fees
    $where_conditions[] = "s.student_id IN (SELECT student_id FROM parent_student WHERE parent_id = ?)";
    $params[] = $_SESSION['user_id'];
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR s.admission_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($class_filter) {
    $where_conditions[] = "s.class = ?";
    $params[] = $class_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // Get fee summary data
    $query = "
        SELECT s.student_id, u.full_name, s.admission_number, s.class, s.stream,
               COALESCE(SUM(f.amount_paid), 0) as total_paid,
               COUNT(f.fee_id) as payment_count,
               MAX(f.payment_date) as last_payment_date
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN fees f ON s.student_id = f.student_id
        $where_clause
        GROUP BY s.student_id, u.full_name, s.admission_number, s.class, s.stream
        ORDER BY s.class, s.stream, u.full_name
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students_fees = $stmt->fetchAll();
    
    // Get classes for filter
    $stmt = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class");
    $classes = $stmt->fetchAll();
    
    // Get all students for payment form (admin only)
    if (hasPermission('manage_students')) {
        $stmt = $pdo->query("SELECT s.student_id, u.full_name, s.admission_number, s.class, s.stream FROM students s JOIN users u ON s.user_id = u.user_id ORDER BY u.full_name");
        $all_students = $stmt->fetchAll();
    }
    
    // Get recent payments for display
    $recent_payments_query = "
        SELECT f.*, u.full_name, s.admission_number, s.class, s.stream
        FROM fees f
        JOIN students s ON f.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        $where_clause
        ORDER BY f.payment_date DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($recent_payments_query);
    $stmt->execute($params);
    $recent_payments = $stmt->fetchAll();
    
    // Calculate statistics
    $total_expected = count($students_fees) * 1500000; // Expected fees per student
    $total_collected = array_sum(array_column($students_fees, 'total_paid'));
    $total_outstanding = $total_expected - $total_collected;
    
} catch (PDOException $e) {
    $error = 'Failed to load fee information: ' . $e->getMessage();
    $students_fees = [];
    $classes = [];
    $recent_payments = [];
    $all_students = [];
}

$expected_fee_per_student = 1500000; // TZS 1,500,000

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-credit-card"></i> Fee Management</h4>
                <?php if (hasPermission('manage_students')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="fas fa-plus"></i> Record Payment
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics (for admin and teachers) -->
                <?php if (hasPermission('view_reports')): ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-money-bill-wave text-primary"></i>
                                <h3>TZS <?php echo number_format($total_expected ?? 0); ?></h3>
                                <p>Total Expected</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-check-circle text-success"></i>
                                <h3>TZS <?php echo number_format($total_collected ?? 0); ?></h3>
                                <p>Total Collected</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <h3>TZS <?php echo number_format($total_outstanding ?? 0); ?></h3>
                                <p>Total Outstanding</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-percentage text-info"></i>
                                <h3><?php echo $total_expected > 0 ? number_format(($total_collected / $total_expected) * 100, 1) : 0; ?>%</h3>
                                <p>Collection Rate</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" 
                                   placeholder="Search by student name or admission number..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <select name="class" class="form-select me-2">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo htmlspecialchars($class['class']); ?>" 
                                            <?php echo $class_filter === $class['class'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>
                
                <!-- Fee Summary Table -->
                <h5>Fee Summary</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Admission Number</th>
                                <th>Class</th>
                                <th>Expected Fees</th>
                                <th>Total Paid</th>
                                <th>Balance</th>
                                <th>Payment Count</th>
                                <th>Last Payment</th>
                                <th>Status</th>
                                <?php if (hasPermission('manage_students')): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_fees as $student): ?>
                                <?php
                                $balance = $expected_fee_per_student - $student['total_paid'];
                                $percentage_paid = ($student['total_paid'] / $expected_fee_per_student) * 100;
                                $status_class = $balance <= 0 ? 'success' : ($percentage_paid >= 50 ? 'warning' : 'danger');
                                $status_text = $balance <= 0 ? 'Paid' : ($percentage_paid >= 50 ? 'Partial' : 'Outstanding');
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class'] . ' ' . $student['stream']); ?></td>
                                    <td>TZS <?php echo number_format($expected_fee_per_student); ?></td>
                                    <td>TZS <?php echo number_format($student['total_paid']); ?></td>
                                    <td>TZS <?php echo number_format($balance); ?></td>
                                    <td><?php echo $student['payment_count']; ?></td>
                                    <td><?php echo $student['last_payment_date'] ? date('M d, Y', strtotime($student['last_payment_date'])) : 'Never'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <?php if (hasPermission('manage_students')): ?>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewPaymentHistory(<?php echo $student['student_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="addPayment(<?php echo $student['student_id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Recent Payments -->
                <h5 class="mt-4">Recent Payments</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Admission No.</th>
                                <th>Class</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Receipt</th>
                                <?php if (hasPermission('manage_students')): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_payments as $payment): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['class'] . ' ' . $payment['stream']); ?></td>
                                    <td>TZS <?php echo number_format($payment['amount_paid']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['receipt_number']); ?></td>
                                    <?php if (hasPermission('manage_students')): ?>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deletePayment(<?php echo $payment['fee_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (hasPermission('manage_students')): ?>
<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Record Fee Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_payment">
                    
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($all_students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>">
                                    <?php echo htmlspecialchars($student['full_name'] . ' (' . $student['admission_number'] . ') - ' . $student['class'] . ' ' . $student['stream']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a student.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount_paid" class="form-label">Amount Paid (TZS)</label>
                        <input type="number" class="form-control" id="amount_paid" name="amount_paid" min="1" step="0.01" required>
                        <div class="invalid-feedback">Please enter a valid amount.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                        <div class="invalid-feedback">Please select the payment date.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                        <div class="invalid-feedback">Please select a payment method.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="receipt_number" class="form-label">Receipt Number</label>
                        <input type="text" class="form-control" id="receipt_number" name="receipt_number" required>
                        <div class="invalid-feedback">Please enter the receipt number.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function viewPaymentHistory(studentId) {
    // This would open a modal with detailed payment history
    alert('Payment history for student ID: ' + studentId);
}

function addPayment(studentId, studentName) {
    document.getElementById('student_id').value = studentId;
    const modal = new bootstrap.Modal(document.getElementById('addPaymentModal'));
    modal.show();
}

function deletePayment(feeId) {
    if (confirm('Are you sure you want to delete this payment record? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_payment">
            <input type="hidden" name="fee_id" value="${feeId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
