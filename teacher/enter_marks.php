<?php
require_once '../includes/auth.php';
require_once '../includes/roles.php';
require_once '../config/db.php';

requireRole(['teacher']);

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = $_POST['exam_id'] ?? null;
    $subject = trim($_POST['subject'] ?? '');
    $max_marks = $_POST['max_marks'] ?? null;
    $student_marks = $_POST['student_marks'] ?? [];

    if (!$exam_id || !$subject || !$max_marks) {
        $error = 'All fields are required.';
    } else {
        try {
            $pdo->beginTransaction();

            foreach ($student_marks as $student_id => $marks) {
                if ($marks !== '') {
                    // Check if result already exists
                    $stmt = $pdo->prepare("SELECT result_id FROM results WHERE student_id = ? AND exam_id = ? AND subject = ?");
                    $stmt->execute([$student_id, $exam_id, $subject]);

                    if ($stmt->fetch()) {
                        // Update result
                        $stmt = $pdo->prepare("UPDATE results SET marks_obtained = ?, max_marks = ? WHERE student_id = ? AND exam_id = ? AND subject = ?");
                        $stmt->execute([$marks, $max_marks, $student_id, $exam_id, $subject]);
                    } else {
                        // Insert new result
                        $teacher_id = $_SESSION['user_id'] ?? null;
                        if ($teacher_id) {
                            $stmt = $pdo->prepare("INSERT INTO results (student_id, exam_id, subject, marks_obtained, max_marks, teacher_id) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$student_id, $exam_id, $subject, $marks, $max_marks, $teacher_id]);
                        }
                    }
                }
            }

            $pdo->commit();
            $success = 'Marks entered successfully!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error saving marks: " . $e->getMessage());
            $error = 'Failed to save marks. Please try again.';
        }
    }
}

// Fetch exams
try {
    $stmt = $pdo->query("SELECT * FROM exams ORDER BY year DESC, term DESC");
    $exams = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error loading exams: " . $e->getMessage());
    $error = 'Failed to load exams.';
    $exams = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-edit"></i> Enter Exam Marks</h4>
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
                        <div class="col-md-4">
                            <label for="exam_id" class="form-label">Select Exam</label>
                            <select class="form-select" id="exam_id" name="exam_id" required>
                                <option value="">Choose an exam...</option>
                                <?php foreach ($exams as $exam): ?>
                                    <option value="<?= $exam['exam_id'] ?>">
                                        <?= htmlspecialchars($exam['exam_name'] . ' - ' . $exam['term'] . ' ' . $exam['year']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select an exam.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                            <div class="invalid-feedback">Please enter the subject.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="max_marks" class="form-label">Maximum Marks</label>
                            <input type="number" class="form-control" id="max_marks" name="max_marks" min="1" max="100" required>
                            <div class="invalid-feedback">Please enter max marks.</div>
                        </div>
                    </div>

                    <div class="my-3">
                        <button type="button" id="loadStudents" class="btn btn-info">
                            <i class="fas fa-search"></i> Load Students
                        </button>
                    </div>

                    <div id="studentsContainer" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Admission Number</th>
                                        <th>Class</th>
                                        <th>Marks Obtained</th>
                                    </tr>
                                </thead>
                                <tbody id="studentsTableBody"></tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Marks
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('loadStudents').addEventListener('click', function () {
    const examId = document.getElementById('exam_id').value;
    const subject = document.getElementById('subject').value;

    if (!examId || !subject) {
        alert('Please select an exam and enter a subject.');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';

    fetch('/api/get_students.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ exam_id: examId, subject: subject })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.getElementById('studentsTableBody');
            tbody.innerHTML = '';
            data.students.forEach(student => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${student.full_name}</td>
                    <td>${student.admission_number}</td>
                    <td>${student.class} ${student.stream}</td>
                    <td>
                        <input type="number" class="form-control" name="student_marks[${student.student_id}]" 
                               min="0" max="100" step="0.01" value="${student.marks_obtained || ''}" placeholder="Enter marks">
                    </td>`;
                tbody.appendChild(row);
            });
            document.getElementById('studentsContainer').style.display = 'block';
        } else {
            alert('Failed to load students: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Error loading students.');
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-search"></i> Load Students';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
