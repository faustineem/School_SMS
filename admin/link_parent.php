<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['admin']);

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_id = $_POST['parent_id'];
    $student_id = $_POST['student_id'];
    
    try {
        // Check if link already exists
        $stmt = $pdo->prepare("SELECT * FROM parent_student WHERE parent_id = ? AND student_id = ?");
        $stmt->execute([$parent_id, $student_id]);
        
        if ($stmt->fetch()) {
            $error = 'This parent is already linked to this student.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)");
            $stmt->execute([$parent_id, $student_id]);
            $success = 'Parent linked to student successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Failed to link parent to student.';
    }
}

// Handle unlinking
if (isset($_GET['unlink'])) {
    $link_id = $_GET['unlink'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM parent_student WHERE id = ?");
        $stmt->execute([$link_id]);
        $success = 'Parent unlinked from student successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to unlink parent from student.';
    }
}

// Get all parents
try {
    $stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'parent' ORDER BY full_name");
    $parents = $stmt->fetchAll();
    
    // Get all students
    $stmt = $pdo->query("SELECT s.student_id, u.full_name, s.admission_number, s.class, s.stream 
                         FROM students s 
                         JOIN users u ON s.user_id = u.user_id 
                         ORDER BY u.full_name");
    $students = $stmt->fetchAll();
    
    // Get existing links
    $stmt = $pdo->query("SELECT ps.*, p.full_name as parent_name, s.full_name as student_name, st.admission_number, st.class, st.stream
                         FROM parent_student ps 
                         JOIN users p ON ps.parent_id = p.user_id 
                         JOIN students st ON ps.student_id = st.student_id 
                         JOIN users s ON st.user_id = s.user_id 
                         ORDER BY p.full_name, s.full_name");
    $links = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Failed to load data.';
    $parents = [];
    $students = [];
    $links = [];
}

$selected_parent = $_GET['parent_id'] ?? '';

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-link"></i> Link Parent to Student</h4>
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
                
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Select Parent</label>
                        <select class="form-select" id="parent_id" name="parent_id" required>
                            <option value="">Choose a parent...</option>
                            <?php foreach ($parents as $parent): ?>
                                <option value="<?php echo $parent['user_id']; ?>" 
                                        <?php echo $selected_parent == $parent['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($parent['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a parent.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Select Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Choose a student...</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>">
                                    <?php echo htmlspecialchars($student['full_name']); ?> 
                                    (<?php echo htmlspecialchars($student['admission_number']); ?> - 
                                    <?php echo htmlspecialchars($student['class'] . ' ' . $student['stream']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a student.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-link"></i> Link Parent to Student
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-list"></i> Existing Links</h4>
            </div>
            <div class="card-body">
                <?php if (empty($links)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No parent-student links found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Parent</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($links as $link): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($link['parent_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($link['student_name']); ?>
                                            <small class="text-muted d-block">
                                                <?php echo htmlspecialchars($link['admission_number']); ?>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($link['class'] . ' ' . $link['stream']); ?></td>
                                        <td>
                                            <a href="?unlink=<?php echo $link['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to unlink this parent from the student?')">
                                                <i class="fas fa-unlink"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
