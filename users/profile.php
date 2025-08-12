<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

requireAuth();

$user = getUserData();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Update basic info
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
        $stmt->execute([$full_name, $phone, $_SESSION['user_id']]);

        // Update password if provided
        if (!empty($current_password) && !empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $error = 'New passwords do not match.';
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                $success = 'Profile updated successfully!';
            }
        } else {
            // Only update success if no error was set above
            if (empty($error)) {
                $success = 'Profile updated successfully!';
            }
        }

        // Refresh user data and update session full name
        $user = getUserData();
        $_SESSION['full_name'] = $user['full_name'];

    } catch (PDOException $e) {
        $error = 'Failed to update profile. Please try again.';
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-user-edit"></i> Edit Profile</h4>
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

                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                <div class="invalid-feedback">
                                    Please enter your full name.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                <small class="form-text text-muted">Email cannot be changed.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($user['phone']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role" name="role" 
                                       value="<?= getRoleDisplayName($user['role']) ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5>Change Password</h5>
                    <p class="text-muted">Leave blank if you don't want to change your password.</p>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                        <a href="<?= BASE_URL ?>/<?= $_SESSION['role'] ?>/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
