<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, full_name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                redirectToDashboard();
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Nyampulukano SMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap + Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(to right, #2980b9, #6dd5fa, #ffffff);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            background: #007bff;
            padding: 1.5rem;
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            text-align: center;
        }
        .login-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        .login-header p {
            font-size: 0.9rem;
            margin-top: 4px;
        }
        .card-body {
            padding: 2rem;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #007bff;
        }
        .btn-primary {
            background: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .password-toggle {
            cursor: pointer;
            border-left: none;
        }
        .forgot-password {
            display: block;
            text-align: right;
            margin-top: 6px;
            font-size: 0.85rem;
            color: #007bff;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
        .alert {
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h3><i class="fas fa-school me-2"></i>Nyampulukano SMS</h3>
            <p>School Management System</p>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control" required
                               value="<?= htmlspecialchars($email ?? '') ?>">
                        <div class="invalid-feedback">Please enter your email address.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" required>
                        <span class="input-group-text password-toggle" onclick="togglePassword(this)">
                            <i class="fas fa-eye"></i>
                        </span>
                        <div class="invalid-feedback">Please enter your password.</div>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" id="loginBtn" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form Validation
        (() => {
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // Toggle Password Visibility
        function togglePassword(el) {
            const input = el.parentElement.querySelector('input');
            const icon = el.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
