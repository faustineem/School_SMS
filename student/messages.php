<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['student']);

$success = '';
$error = '';

// Get messages for students
try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.recipient_role IN ('student', 'all')
        ORDER BY m.sent_at DESC
    ");
    $stmt->execute();
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
            <div class="card-header">
                <h4><i class="fas fa-envelope"></i> Messages from School</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
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

<?php include '../includes/footer.php'; ?>