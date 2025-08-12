<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireAuth();

$success = '';
$error = '';

// Handle message sending (admin and teachers only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasPermission('send_messages')) {
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    $recipient_role = $_POST['recipient_role'];
    
    if (empty($title) || empty($body) || empty($recipient_role)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, recipient_role, title, body) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $recipient_role, $title, $body]);
            $success = 'Message sent successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to send message. Please try again.';
        }
    }
}

// Get messages based on user role
try {
    $role = $_SESSION['role'];
    
    if ($role === 'admin' || $role === 'teacher') {
        // Admin and teachers can see all messages
        $stmt = $pdo->prepare("
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            ORDER BY m.sent_at DESC
        ");
        $stmt->execute();
    } else {
        // Students and parents see messages for their role or all
        $stmt = $pdo->prepare("
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE m.recipient_role IN (?, 'all')
            ORDER BY m.sent_at DESC
        ");
        $stmt->execute([$role]);
    }
    
    $messages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to load messages.';
    $messages = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-envelope"></i> Messages</h4>
                <?php if (hasPermission('send_messages')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                        <i class="fas fa-plus"></i> Send Message
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
                
                <?php if (empty($messages)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No messages found.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($messages as $message): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($message['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y g:i A', strtotime($message['sent_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($message['body'])); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                From: <?php echo htmlspecialchars($message['sender_name']); ?>
                                            </small>
                                            <span class="badge bg-<?php echo $message['recipient_role'] === 'all' ? 'primary' : 'info'; ?>">
                                                <?php echo ucfirst($message['recipient_role']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (hasPermission('send_messages')): ?>
<!-- Send Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Send Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="recipient_role" class="form-label">Send To</label>
                        <select class="form-select" id="recipient_role" name="recipient_role" required>
                            <option value="">Select recipient...</option>
                            <option value="all">All Users</option>
                            <option value="student">Students</option>
                            <option value="parent">Parents</option>
                            <option value="teacher">Teachers</option>
                        </select>
                        <div class="invalid-feedback">Please select a recipient.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Message Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                        <div class="invalid-feedback">Please enter a message title.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="body" class="form-label">Message Body</label>
                        <textarea class="form-control" id="body" name="body" rows="5" required></textarea>
                        <div class="invalid-feedback">Please enter the message content.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
