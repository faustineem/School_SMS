<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    
    if (empty($email) || empty($full_name)) {
        $error = 'Please provide both your email and full name.';
    } else {
        try {
            // Check if email exists in the database
            $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                // In a real system, this could trigger an email to admin or log the request
                $success = 'Your request has been submitted. The school administration will contact you shortly.';
            } else {
                $error = 'No account found with this email address.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Nyampulukano Secondary School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            margin: 0;
            font-family: 'Arial', sans-serif;
        }
        .forgot-password-card {
            max-width: 500px;
            width: 100%;
            margin: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            background: #ffffff;
            transition: transform 0.3s ease;
        }
        .forgot-password-card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background: #007bff;
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }
        .card-body {
            padding: 2rem;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            background: #007bff;
            border: none;
            padding: 0.75rem;
            font-size: 1.1rem;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-primary:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .alert {
            position: relative;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-dismissible .btn-close {
            position: absolute;
            top: 0.75rem;
            right: 1rem;
        }
        .form-label {
            font-weight: 500;
            color: #333;
        }
        .contact-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .contact-info p {
            margin: 0.5rem 0;
        }
        .back-to-login {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #007bff;
            text-decoration: none;
        }
        .back-to-login:hover {
            text-decoration: underline;
        }
        @media (max-width: 576px) {
            .forgot-password-card {
                margin: 15px;
            }
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-password-card">
        <div class="card">
            <div class="card-header text-center">
                <h3><i class="fas fa-key me-2"></i>Forgot Password</h3>
                <p class="mb-0">Nyampulukano School Management System</p>
            </div>
            <div class="card-body">
                <div class="contact-info">
                    <h5>Contact School Administration</h5>
                    <p>If you've forgotten your password, please follow these steps:</p>
                    <ol>
                        <li>Submit the form below with your registered email and full name.</li>
                        <li>The school administration will verify your identity.</li>
                        <li>You will receive further instructions via email or phone within 24-48 hours.</li>
                    </ol>
                    <p><strong>Contact Details:</strong></p>
                    <p><i class="fas fa-envelope me-2"></i>Email: admin@nyampulukano.ac.tz</p>
                    <p><i class="fas fa-phone me-2"></i>Phone: +255 123 456 789</p>
                    <p><i class="fas fa-map-marker-alt me-2"></i>Office: Nyampulukano Secondary School, Admin Block, Room 12</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($full_name ?? ''); ?>" 
                               required aria-describedby="fullNameHelp">
                        <div class="invalid-feedback">
                            Please enter your full name.
                        </div>
                        <small id="fullNameHelp" class="form-text text-muted">Enter your name as registered.</small>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                               required aria-describedby="emailHelp">
                        <div class="invalid-feedback">
                            Please enter a valid email address.
                        </div>
                        <small id="emailHelp" class="form-text text-muted">Use the email associated with your account.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                    </button>
                </form>

                <a href="login.php" class="back-to-login">Back to Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap form validation
        (function () {
            'use strict';
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

        // Submit button loading state
        document.querySelector('form').addEventListener('submit', function (e) {
            const submitBtn = document.getElementById('submitBtn');
            if (this.checkValidity()) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            }
        });
    </script>
</body>
</html>