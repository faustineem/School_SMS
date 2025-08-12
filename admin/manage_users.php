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
        $role = $_POST['role'];
        
        // Generate default password based on role
        $default_password = $role . '123';
        $password = password_hash($default_password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $password, $role]);
            $success = 'User added successfully! Default password: ' . $default_password;
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Email already exists. Please use a different email address.';
            } else {
                $error = 'Failed to add user. Please try again.';
            }
        }
    }
    
    if ($action === 'edit') {
        $user_id = $_POST['user_id'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $email, $phone, $role, $user_id]);
            $success = 'User updated successfully!';
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Email already exists. Please use a different email address.';
            } else {
                $error = 'Failed to update user. Please try again.';
            }
        }
    }
    
    if ($action === 'delete') {
        $user_id = $_POST['user_id'];
        
        // Prevent admin from deleting themselves
        if ($user_id == $_SESSION['user_id']) {
            $error = 'You cannot delete your own account.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $success = 'User deleted successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to delete user. This user may have related records.';
            }
        }
    }
    
    if ($action === 'reset_password') {
        $user_id = $_POST['user_id'];
        
        try {
            // Get user role for default password
            $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_role = $stmt->fetchColumn();
            
            $default_password = $user_role . '123';
            $password = password_hash($default_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$password, $user_id]);
            $success = 'Password reset successfully! New password: ' . $default_password;
            
        } catch (PDOException $e) {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}

// Handle search and filter
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // Get total count
    $count_query = "SELECT COUNT(*) FROM users $where_clause";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    
    // Get users with pagination
    $query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Calculate pagination
    $total_pages = ceil($total_users / $per_page);
    
    // Get role statistics
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $role_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    $error = 'Failed to load users.';
    $users = [];
    $total_users = 0;
    $total_pages = 0;
    $role_stats = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-users-cog"></i> Manage Users</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add User
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
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <i class="fas fa-users text-primary"></i>
                            <h3><?php echo number_format($total_users); ?></h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <i class="fas fa-user-shield text-success"></i>
                            <h3><?php echo $role_stats['admin'] ?? 0; ?></h3>
                            <p>Admins</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <i class="fas fa-chalkboard-teacher text-info"></i>
                            <h3><?php echo $role_stats['teacher'] ?? 0; ?></h3>
                            <p>Teachers</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <i class="fas fa-user-graduate text-warning"></i>
                            <h3><?php echo $role_stats['student'] ?? 0; ?></h3>
                            <p>Students</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <i class="fas fa-user-friends text-secondary"></i>
                            <h3><?php echo $role_stats['parent'] ?? 0; ?></h3>
                            <p>Parents</p>
                        </div>
                    </div>
                </div>
                
                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" 
                                   placeholder="Search by name or email..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <select name="role" class="form-select me-2">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="parent" <?php echo $role_filter === 'parent' ? 'selected' : ''; ?>>Parent</option>
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
                    </div>
                </div>
                
                <!-- Users Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getRoleBadgeColor($user['role']); ?>">
                                            <?php echo getRoleDisplayName($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    onclick="resetPassword(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger btn-delete" 
                                                        onclick="deleteUser(<?php echo $user['user_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                        <div class="invalid-feedback">Please enter the full name.</div>
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
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                            <option value="parent">Parent</option>
                        </select>
                        <div class="invalid-feedback">Please select a role.</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Default password will be: <strong>[role]123</strong> (e.g., admin123, teacher123)
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                        <div class="invalid-feedback">Please enter the full name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="edit_phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                            <option value="parent">Parent</option>
                        </select>
                        <div class="invalid-feedback">Please select a role.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_role').value = user.role;
    
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    editModal.show();
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone and may affect related records.')) {
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

function resetPassword(userId) {
    if (confirm('Are you sure you want to reset this user\'s password? They will need to use the default password to login.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
function getRoleBadgeColor($role) {
    switch ($role) {
        case 'admin': return 'success';
        case 'teacher': return 'info';
        case 'student': return 'warning';
        case 'parent': return 'secondary';
        default: return 'light';
    }
}

include '../includes/footer.php';
?>
