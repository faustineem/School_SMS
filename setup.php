<?php
// Database setup script - run this once to create all tables
require_once 'config/db.php';

try {
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id SERIAL PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) CHECK (role IN ('admin', 'teacher', 'student', 'parent')) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Create students table
    $sql = "CREATE TABLE IF NOT EXISTS students (
        student_id SERIAL PRIMARY KEY,
        user_id INT NOT NULL,
        admission_number VARCHAR(50) UNIQUE NOT NULL,
        gender VARCHAR(10) CHECK (gender IN ('male', 'female')) NOT NULL,
        date_of_birth DATE NOT NULL,
        class VARCHAR(20) NOT NULL,
        stream VARCHAR(20),
        address TEXT,
        advisor_id INT,
        profile_image VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (advisor_id) REFERENCES users(user_id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);

    // Create parent_student table
    $sql = "CREATE TABLE IF NOT EXISTS parent_student (
        id SERIAL PRIMARY KEY,
        parent_id INT NOT NULL,
        student_id INT NOT NULL,
        FOREIGN KEY (parent_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // Create fees table
    $sql = "CREATE TABLE IF NOT EXISTS fees (
        fee_id SERIAL PRIMARY KEY,
        student_id INT NOT NULL,
        amount_paid DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        receipt_number VARCHAR(50) NOT NULL,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // Create exams table
    $sql = "CREATE TABLE IF NOT EXISTS exams (
        exam_id SERIAL PRIMARY KEY,
        exam_name VARCHAR(100) NOT NULL,
        term VARCHAR(20) NOT NULL,
        year INT NOT NULL
    )";
    $pdo->exec($sql);

    // Create results table
    $sql = "CREATE TABLE IF NOT EXISTS results (
        result_id SERIAL PRIMARY KEY,
        student_id INT NOT NULL,
        exam_id INT NOT NULL,
        subject VARCHAR(100) NOT NULL,
        marks_obtained DECIMAL(5,2) NOT NULL,
        max_marks DECIMAL(5,2) NOT NULL,
        teacher_id INT NOT NULL,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
        FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // Create messages table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        message_id SERIAL PRIMARY KEY,
        sender_id INT NOT NULL,
        recipient_role VARCHAR(20) CHECK (recipient_role IN ('student', 'parent', 'teacher', 'all')) NOT NULL,
        title VARCHAR(100) NOT NULL,
        body TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);

    // Create default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (full_name, email, phone, password, role) VALUES 
            ('System Administrator', 'admin@nyampulukano.edu', '0700000000', ?, 'admin')
            ON CONFLICT (email) DO NOTHING";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_password]);

    echo "Database setup completed successfully!<br>";
    echo "Default admin login: admin@nyampulukano.edu / admin123<br>";
    echo "<a href='index.php'>Go to Login Page</a>";

} catch (PDOException $e) {
    echo "Setup failed: " . $e->getMessage();
}
?>
