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
        
        // Generate default password
        $password = password_hash('teacher123', PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'teacher')");
            $stmt->execute([$full_name, $email, $phone, $password]);
            $success = 'Teacher added successfully! Default password: teacher123';
            
        } catch (PDOException $e) {
            $error = 'Failed to add teacher. Email may already exist.';
        }
    }
    
    if ($action === 'delete') {
        $user_id = $_POST['user_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'teacher'");
            $stmt->execute([$user_id]);
            $success = 'Teacher deleted successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to delete teacher.';
        }
    }
}

// Get all teachers
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY full_name");
    $teachers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to load teachers.';
    $teachers = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                    <i class="fas fa-plus"></i> Add Teacher
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
                    <input type="text" class="form-control" id="searchInput" placeholder="Search teachers...">
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th class="sortable">Full Name</th>
                                <th class="sortable">Email</th>
                                <th class="sortable">Phone</th>
                                <th class="sortable">Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['phone'] ?? 'Not provided'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($teacher['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewTeacher(<?php echo $teacher['user_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete" 
                                                    onclick="deleteTeacher(<?php echo $teacher['user_id']; ?>)">
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

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                        <div class="invalid-feedback">Please enter the teacher's full name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Default password will be: <strong>teacher123</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteTeacher(userId) {
    if (confirm('Are you sure you want to delete this teacher? This action cannot be undone.')) {
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

function viewTeacher(userId) {
    // This would open a modal with teacher details
    alert('View teacher functionality to be implemented');
}
</script>

<?php include '../includes/footer.php'; ?>
