<?php
session_start();
require_once '../include/database_connection.php';

function respond($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(false, 'Invalid request method');
}

$action = $_POST['action'] ?? '';

if ($action === 'add_admin') {
    $name = trim($_POST['adminName'] ?? '');
    $email = trim($_POST['adminEmail'] ?? '');
    $phone = trim($_POST['adminPhone'] ?? '');
    $password = $_POST['adminPassword'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        respond(false, 'All fields are required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Invalid email format');
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT adm_id FROM admin WHERE adm_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        respond(false, 'Email already exists');
    }
    
    // Handle image upload
    $imgName = "";
    if (isset($_FILES['adminImage']) && $_FILES['adminImage']['error'] == 0) {
        $target_dir = "admin_images/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file = $_FILES['adminImage'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file['size'] > 5000000) respond(false, 'File too large (max 5MB)');
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) respond(false, 'Only JPG, JPEG, PNG allowed');
        
        $imgName = date("YmdHis") . "." . $ext;
        if (!move_uploaded_file($file['tmp_name'], $target_dir . $imgName)) {
            respond(false, 'Error uploading file');
        }
    }
    
    // Insert admin
    $stmt = $conn->prepare("INSERT INTO admin (adm_name, adm_email, adm_ph, adm_img, adm_pass) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $name, $email, $phone, $imgName, $password);
    
    if ($stmt->execute()) {
        respond(true, 'Admin added successfully');
    } else {
        respond(false, 'Database error: ' . $stmt->error);
    }
}

if ($action === 'update_password') {
    if (!isset($_SESSION['adm_id'])) {
        respond(false, 'Authentication required');
    }
    
    $adminId = $_SESSION['adm_id'];
    $currentPass = $_POST['currentPassword'] ?? '';
    $newPass = $_POST['newPassword'] ?? '';
    $confirmPass = $_POST['confirmNewPassword'] ?? '';
    
    if ($newPass !== $confirmPass) {
        respond(false, 'Password confirmation does not match');
    }
    
    // Password strength validation
    if (strlen($newPass) < 8 || !preg_match('/[A-Z]/', $newPass) || 
        !preg_match('/[0-9]/', $newPass) || !preg_match('/[^A-Za-z0-9]/', $newPass)) {
        respond(false, 'Password must be 8+ chars with uppercase, number and special character');
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT adm_pass FROM admin WHERE adm_id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        respond(false, 'Admin not found');
    }
    
    if ($currentPass !== $result->fetch_assoc()['adm_pass']) {
        respond(false, 'Current password is incorrect');
    }
}

respond(false, 'Invalid action');
?>