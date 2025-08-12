<?php
// Database configuration
$host = 'localhost';
$dbname = 'nyampulukano_sms';
$username = 'root';
$password = '';
$port = '3306';

try {
    // Create PDO instance with proper error handling
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    // Set global PDO connection for backward compatibility
    $GLOBALS['pdo'] = $pdo;
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display user-friendly error message
    die("Database connection failed. Please contact the system administrator.");
}

// Function to get database connection
function getDbConnection() {
    global $pdo;
    return $pdo;
}

// Function to test database connection
function testDbConnection() {
    try {
        global $pdo;
        $stmt = $pdo->query("SELECT 1");
        return $stmt !== false;
    } catch (PDOException $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        return false;
    }
}

// Function to execute prepared statements safely
function executeQuery($sql, $params = []) {
    try {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw $e;
    }
}

// Function to get single row
function fetchSingle($sql, $params = []) {
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fetch single failed: " . $e->getMessage());
        return false;
    }
}

// Function to get multiple rows
function fetchMultiple($sql, $params = []) {
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fetch multiple failed: " . $e->getMessage());
        return [];
    }
}
?>