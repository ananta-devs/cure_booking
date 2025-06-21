<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['adm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include '../include/database_connection.php';

// Check if action parameter exists
if (!isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit();
}

if ($_POST['action'] === 'add') {
    try {
        // Validate and sanitize input data
        $clinic_name = isset($_POST['clinic_name']) ? trim($_POST['clinic_name']) : '';
        $clinic_email = isset($_POST['clinic_email']) ? trim($_POST['clinic_email']) : '';
        $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $available_timing = isset($_POST['available_timing']) ? trim($_POST['available_timing']) : '';
        $clinic_pass = isset($_POST['clinic_pass']) ? trim($_POST['clinic_pass']) : '';
        $about = isset($_POST['about']) ? trim($_POST['about']) : '';
        
        // Basic validation
        if (empty($clinic_name) || empty($clinic_email) || empty($contact_number) || 
            empty($location) || empty($available_timing) || empty($clinic_pass)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Validate email format
        if (!filter_var($clinic_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE clinic_email = ?");
        $stmt->execute([$clinic_email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Validate password length
        if (strlen($clinic_pass) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }
        
        // Hash the password
        $hashed_password = password_hash($clinic_pass, PASSWORD_DEFAULT);
        
        // Handle file upload
        $profile_image_path = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = './uploads/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception('Failed to create upload directory');
                }
            }
            
            $file_info = pathinfo($_FILES['profile_image']['name']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed');
            }
            
            // Check file size (max 5MB)
            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                throw new Exception('File size too large. Maximum size is 5MB');
            }
            
            // Generate unique filename
            $filename = 'clinic_' . uniqid() . '.' . $file_info['extension'];
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                $profile_image_path = 'uploads/clinics/' . $filename;
            } else {
                throw new Exception('Failed to upload profile image');
            }
        }
        
        // Insert clinic data into database
        $sql = "INSERT INTO clinics (clinic_name, clinic_email, contact_number, location, 
                available_timing, clinic_pass, profile_image, about) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $clinic_name,
            $clinic_email,
            $contact_number,
            $location,
            $available_timing,
            $hashed_password,
            $profile_image_path,
            $about
        ]);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Clinic registered successfully!',
                'clinic_id' => $pdo->lastInsertId()
            ]);
        } else {
            throw new Exception('Failed to save clinic data');
        }
        
    } catch (Exception $e) {
        // If there was an error and a file was uploaded, delete it
        if (isset($target_path) && file_exists($target_path)) {
            unlink($target_path);
        }
        
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>