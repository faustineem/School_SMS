<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['admin']);

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $admission_number = trim($_POST['admission_number']);
        $gender = $_POST['gender'];
        $date_of_birth = $_POST['date_of_birth'];
        $class = $_POST['class'];
        $stream = $_POST['stream'];
        $address = trim($_POST['address']);
        $advisor_id = $_POST['advisor_id'] ?: null;
        
        // Generate default password
        $password = password_hash('student123', PASSWORD_DEFAULT);
        
        try {
            $pdo->beginTransaction();
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'student')");
            $stmt->execute([$full_name, $email, $phone, $password]);
            $user_id = $pdo->lastInsertId();
            
            // Insert student
            $stmt = $pdo->prepare("INSERT INTO students (user_id, admission_number, gender, date_of_birth, class, stream, address, advisor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $admission_number, $gender, $date_of_birth, $class, $stream, $address, $advisor_id]);
            
            $pdo->commit();
            $success = 'Student added successfully! Default password: student123';
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Failed to add student. Email or admission number may already exist.';
        }
    }
    
    if ($action === 'delete') {
        $user_id = $_POST['user_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student'");
            $stmt->execute([$user_id]);
            $success = 'Student deleted successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to delete student.';
        }
    }
}

// Get all students
try {
    $stmt = $pdo->query("SELECT u.*, s.*, t.full_name as advisor_name 
                         FROM users u 
                         JOIN students s ON u.user_id = s.user_id 
                         LEFT JOIN users t ON s.advisor_id = t.user_id 
                         WHERE u.role = 'student' 
                         ORDER BY u.full_name");
    $students = $stmt->fetchAll();
    
    // Get advisors (teachers)
    $stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name");
    $advisors = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to load students.';
    $students = [];
    $advisors = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-users"></i> Manage Students</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus"></i> Add Student
                </button>
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
                
                <div class="mb-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search students...">
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th class="sortable">Full Name</th>
                                <th class="sortable">Admission Number</th>
                                <th class="sortable">Email</th>
                                <th class="sortable">Class</th>
                                <th class="sortable">Gender</th>
                                <th>Academic Advisor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class'] . ' ' . $student['stream']); ?></td>
                                    <td><?php echo ucfirst($student['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($student['advisor_name'] ?? 'Not assigned'); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewStudent(<?php echo $student['user_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete" 
                                                    onclick="deleteStudent(<?php echo $student['user_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                                <div class="invalid-feedback">Please enter the student's full name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admission_number" class="form-label">Admission Number</label>
                                <input type="text" class="form-control" id="admission_number" name="admission_number" required>
                                <div class="invalid-feedback">Please enter the admission number.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                <div class="invalid-feedback">Please select gender.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                <div class="invalid-feedback">Please enter date of birth.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="advisor_id" class="form-label">Academic Advisor</label>
                                <select class="form-select" id="advisor_id" name="advisor_id">
                                    <option value="">Select Advisor</option>
                                    <?php foreach ($advisors as $advisor): ?>
                                        <option value="<?php echo $advisor['user_id']; ?>">
                                            <?php echo htmlspecialchars($advisor['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="class" class="form-label">Class</label>
                                <input type="text" class="form-control" id="class" name="class" required>
                                <div class="invalid-feedback">Please enter the class.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stream" class="form-label">Stream</label>
                                <input type="text" class="form-control" id="stream" name="stream">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteStudent(userId) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewStudent(userId) {
    // This would open a modal with student details
    alert('View student functionality to be implemented');
}
</script>

<?php include '../includes/footer.php'; ?>
