<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../config/db.php';

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

        $password = password_hash('parent123', PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'parent')");
            $stmt->execute([$full_name, $email, $phone, $password]);
            $success = 'Parent added successfully! Default password: parent123';
        } catch (PDOException $e) {
            $error = 'Failed to add parent. Email may already exist.';
        }
    }

    if ($action === 'delete') {
        $user_id = $_POST['user_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'parent'");
            $stmt->execute([$user_id]);
            $success = 'Parent deleted successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to delete parent.';
        }
    }
}

// Fetch parents with child count
try {
    $stmt = $pdo->query("SELECT u.*, COUNT(ps.student_id) AS children_count 
                         FROM users u 
                         LEFT JOIN parent_student ps ON u.user_id = ps.parent_id 
                         WHERE u.role = 'parent' 
                         GROUP BY u.user_id 
                         ORDER BY u.full_name");
    $parents = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load parents.';
    $parents = [];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-user-friends"></i> Manage Parents</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addParentModal">
                    <i class="fas fa-plus"></i> Add Parent
                </button>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search parents...">
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Children Count</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parents as $parent): ?>
                                <tr>
                                    <td><?= htmlspecialchars($parent['full_name']) ?></td>
                                    <td><?= htmlspecialchars($parent['email']) ?></td>
                                    <td><?= htmlspecialchars($parent['phone'] ?? 'Not provided') ?></td>
                                    <td><span class="badge bg-info"><?= $parent['children_count'] ?></span></td>
                                    <td><?= date('M d, Y', strtotime($parent['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary"
                                                    onclick="viewParent(<?= $parent['user_id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>/admin/link_parent.php?parent_id=<?= $parent['user_id'] ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-link"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteParent(<?= $parent['user_id'] ?>)">
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

<!-- Add Parent Modal -->
<div class="modal fade" id="addParentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Parent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                        <div class="invalid-feedback">Please enter the parent's full name.</div>
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
                        Default password will be: <strong>parent123</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Parent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteParent(userId) {
    if (confirm('Are you sure you want to delete this parent?')) {
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

function viewParent(userId) {
    alert('View parent functionality to be implemented');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
