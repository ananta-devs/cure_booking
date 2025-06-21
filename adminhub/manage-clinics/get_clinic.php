<?php
session_start();
include '../include/database_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['adm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            getClinics();
            break;
        
        case 'get':
            getClinic($_GET['id']);
            break;
        
        case 'update':
            updateClinic();
            break;
        
        case 'delete':
            deleteClinic();
            break;
        
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}

function fixImagePath($imagePath) {
    if (empty($imagePath)) {
        return null;
    }
    
    // If it's already a complete URL, return as is
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }
    
    // Remove any leading slashes or dots
    $imagePath = ltrim($imagePath, './');
    
    // Define possible paths to check
    $possiblePaths = [
        $imagePath,                                    // Original path
        'uploads/' . basename($imagePath),             // Just filename in uploads
        '../uploads/' . basename($imagePath),          // Go up one level
        './uploads/' . basename($imagePath),           // Current directory uploads  
        'uploads/' . $imagePath,                       // Add uploads prefix
        '../uploads/' . $imagePath,                    // Add uploads prefix with ../
    ];
    
    // Check each possible path
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            // Return relative path for web display
            // Remove ../ for web URLs and ensure it starts correctly
            $webPath = str_replace('../', '', $path);
            return $webPath;
        }
    }
    
    // If no file found, check if uploads directory exists and try original filename
    $filename = basename($imagePath);
    if (!empty($filename)) {
        // Check if uploads directory exists
        $uploadDirs = ['uploads/', '../uploads/'];
        foreach ($uploadDirs as $dir) {
            if (is_dir($dir)) {
                $fullPath = $dir . $filename;
                if (file_exists($fullPath)) {
                    return str_replace('../', '', $fullPath);
                }
            }
        }
    }
    
    // Return null if no valid image found
    return null;
}

function getClinics() {
    global $conn;
    
    try {
        // Add search functionality
        $searchCondition = '';
        $searchParam = $_GET['search'] ?? '';
        
        if (!empty($searchParam)) {
            $searchCondition = "WHERE clinic_name LIKE ? OR location LIKE ? OR clinic_email LIKE ?";
        }
        
        $sql = "SELECT 
                    clinic_id, 
                    clinic_name, 
                    clinic_email, 
                    contact_number, 
                    location, 
                    available_timing, 
                    profile_image, 
                    about, 
                    status,
                    created_at,
                    updated_at
                FROM clinics 
                $searchCondition
                ORDER BY clinic_name ASC";
        
        if (!empty($searchParam)) {
            $stmt = $conn->prepare($sql);
            $searchTerm = "%$searchParam%";
            $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }
        
        if ($result === false) {
            throw new Exception("Database query failed: " . $conn->error);
        }
        
        $clinics = [];
        while ($row = $result->fetch_assoc()) {
            // Fix profile image path using the new function
            $row['profile_image'] = fixImagePath($row['profile_image']);
            $clinics[] = $row;
        }
        
        echo json_encode([
            'status' => 'success',
            'clinics' => $clinics
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch clinics: ' . $e->getMessage()
        ]);
    }
}

function getClinic($clinicId) {
    global $conn;
    
    if (!$clinicId) {
        echo json_encode(['status' => 'error', 'message' => 'Clinic ID is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("SELECT 
                                    clinic_id, 
                                    clinic_name, 
                                    clinic_email, 
                                    contact_number, 
                                    location, 
                                    available_timing, 
                                    profile_image, 
                                    about, 
                                    status,
                                    created_at,
                                    updated_at
                                FROM clinics 
                                WHERE clinic_id = ?");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $clinicId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Clinic not found']);
            return;
        }
        
        $clinic = $result->fetch_assoc();
        
        // Fix profile image path using the new function
        $clinic['profile_image'] = fixImagePath($clinic['profile_image']);
        
        echo json_encode([
            'status' => 'success',
            'clinic' => $clinic
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to fetch clinic: ' . $e->getMessage()
        ]);
    }
}

function updateClinic() {
    global $conn;
    
    try {
        $clinic_id = $_POST['clinic_id'] ?? '';
        $clinic_name = $_POST['clinic_name'] ?? '';
        $clinic_email = $_POST['clinic_email'] ?? '';
        $contact_number = $_POST['contact_number'] ?? '';
        $location = $_POST['location'] ?? '';
        $available_timing = $_POST['available_timing'] ?? '';
        $about = $_POST['about'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $clinic_pass = $_POST['clinic_pass'] ?? '';
        $existing_img = $_POST['existing_img'] ?? '';
        
        // Validate required fields
        if (empty($clinic_id) || empty($clinic_name) || empty($clinic_email) || 
            empty($contact_number) || empty($location) || empty($available_timing)) {
            echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled']);
            return;
        }
        
        // Validate email format
        if (!filter_var($clinic_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
            return;
        }
        
        // Check if clinic exists
        $checkStmt = $conn->prepare("SELECT clinic_id FROM clinics WHERE clinic_id = ?");
        $checkStmt->bind_param("i", $clinic_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Clinic not found']);
            return;
        }
        
        // Handle profile image upload
        $profile_image = $existing_img;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            // Ensure upload directory exists
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, JPEG, PNG, GIF and WEBP are allowed']);
                return;
            }
            
            // Check file size (max 5MB)
            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                echo json_encode(['status' => 'error', 'message' => 'File size too large. Maximum size is 5MB']);
                return;
            }
            
            // Generate unique filename
            $fileName = 'clinic_' . $clinic_id . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                // Delete old image if exists
                if (!empty($existing_img)) {
                    $oldImagePaths = [
                        $existing_img,
                        'uploads/' . basename($existing_img),
                        '../uploads/' . basename($existing_img)
                    ];
                    
                    foreach ($oldImagePaths as $oldPath) {
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                            break;
                        }
                    }
                }
                $profile_image = $uploadPath;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
                return;
            }
        }
        
        // Prepare update query
        $updateFields = [
            'clinic_name = ?',
            'clinic_email = ?',
            'contact_number = ?',
            'location = ?',
            'available_timing = ?',
            'about = ?',
            'status = ?',
            'profile_image = ?',
            'updated_at = NOW()'
        ];
        
        $params = [$clinic_name, $clinic_email, $contact_number, $location, $available_timing, $about, $status, $profile_image];
        $types = 'ssssssss';
        
        // Add password update if provided
        if (!empty($clinic_pass)) {
            $updateFields[] = 'clinic_pass = ?';
            $params[] = password_hash($clinic_pass, PASSWORD_DEFAULT);
            $types .= 's';
        }
        
        $params[] = $clinic_id;
        $types .= 'i';
        
        $sql = "UPDATE clinics SET " . implode(', ', $updateFields) . " WHERE clinic_id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Clinic updated successfully'
            ]);
        } else {
            throw new Exception("Update failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update clinic: ' . $e->getMessage()
        ]);
    }
}

function deleteClinic() {
    global $conn;
    
    try {
        // Handle both GET and POST requests, and JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        $clinic_id = $input['clinic_id'] ?? $_POST['clinic_id'] ?? $_GET['clinic_id'] ?? '';
        
        if (empty($clinic_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Clinic ID is required']);
            return;
        }
        
        // Check if clinic exists and get profile image
        $checkStmt = $conn->prepare("SELECT profile_image FROM clinics WHERE clinic_id = ?");
        $checkStmt->bind_param("i", $clinic_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Clinic not found']);
            return;
        }
        
        $clinic = $result->fetch_assoc();
        
        // Check for dependencies (appointments, etc.)
        $dependencyStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE clinic_id = ?");
        $dependencyStmt->bind_param("i", $clinic_id);
        $dependencyStmt->execute();
        $dependencyResult = $dependencyStmt->get_result();
        $dependencyCount = $dependencyResult->fetch_assoc()['count'];
        
        if ($dependencyCount > 0) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Cannot delete clinic. There are existing appointments associated with this clinic.'
            ]);
            return;
        }
        
        // Delete the clinic
        $deleteStmt = $conn->prepare("DELETE FROM clinics WHERE clinic_id = ?");
        $deleteStmt->bind_param("i", $clinic_id);
        
        if ($deleteStmt->execute()) {
            // Delete profile image if exists
            if (!empty($clinic['profile_image'])) {
                $imagePaths = [
                    $clinic['profile_image'],
                    'uploads/' . basename($clinic['profile_image']),
                    '../uploads/' . basename($clinic['profile_image'])
                ];
                
                foreach ($imagePaths as $imagePath) {
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                        break;
                    }
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Clinic deleted successfully'
            ]);
        } else {
            throw new Exception("Delete failed: " . $deleteStmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete clinic: ' . $e->getMessage()
        ]);
    }
}

// Additional utility functions

function validateClinicData($data) {
    $errors = [];
    
    if (empty($data['clinic_name'])) {
        $errors[] = 'Clinic name is required';
    }
    
    if (empty($data['clinic_email']) || !filter_var($data['clinic_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($data['contact_number']) || !preg_match('/^[0-9+\-\s()]{10,15}$/', $data['contact_number'])) {
        $errors[] = 'Valid contact number is required';
    }
    
    if (empty($data['location'])) {
        $errors[] = 'Location is required';
    }
    
    if (empty($data['available_timing'])) {
        $errors[] = 'Available timing is required';
    }
    
    return $errors;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Close database connection on script end
if (isset($conn)) {
    $conn->close();
}
?>