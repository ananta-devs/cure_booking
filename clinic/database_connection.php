<?php
// Database connection configuration
$host = 'localhost';
$dbname = 'cure_booking';
$username = 'root';
$password = '';

// Create MySQLi connection for legacy code
$conn = new mysqli($host, $username, $password, $dbname);

// Check MySQLi connection
if ($conn->connect_error) {
    die("MySQLi Connection failed: " . $conn->connect_error);
}

// Create PDO connection for modern code
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}
?>