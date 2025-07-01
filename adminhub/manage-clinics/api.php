<?php
session_start();
include '../include/database_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['adm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// FIXED: Get action from multiple sources including JSON input
$action = '';

// First try GET and POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// If not found, try JSON input
if (empty($action)) {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['action'])) {
        $action = $input['action'];
    }
}

if (empty($action)) {
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit();
}

try {
    switch ($action) {
        case 'add': addClinic(); break;
        case 'list': getClinics(); break;
        case 'get': getClinic($_GET['id'] ?? ''); break;
        case 'update': updateClinic(); break;
        case 'delete': deleteClinic(); break;
        default: echo json_encode(['status' => 'error', 'message' => 'Invalid action']); break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}

// Rest of your functions remain the same...
function fixImagePath($imagePath) {
    if (empty($imagePath)) return null;
    
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }
    
    $imagePath = ltrim($imagePath, './');
    
    $possiblePaths = [
        $imagePath,
        'uploads/' . basename($imagePath),
        '../uploads/' . basename($imagePath),
        './uploads/' . $imagePath,
        'uploads/' . $imagePath,
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return str_replace('../', '', $path);
        }
    }
    
    $filename = basename($imagePath);
    if (!empty($filename)) {
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
    
    return null;
}

function addClinic() {
    global $conn, $pdo;
    
    try {
        $clinic_name = trim($_POST['clinic_name'] ?? '');
        $clinic_email = trim($_POST['clinic_email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $available_timing = trim($_POST['available_timing'] ?? '');
        $clinic_pass = trim($_POST['clinic_pass'] ?? '');
        $about = trim($_POST['about'] ?? '');
        
        if (empty($clinic_name) || empty($clinic_email) || empty($contact_number) || 
            empty($location) || empty($available_timing) || empty($clinic_pass)) {
            throw new Exception('All required fields must be filled');
        }
        
        if (!filter_var($clinic_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE clinic_email = ?");
            $stmt->execute([$clinic_email]);
            if ($stmt->fetch()) {
                throw new Exception('Email already exists');
            }
        } else {
            $stmt = $conn->prepare("SELECT clinic_id FROM clinics WHERE clinic_email = ?");
            $stmt->bind_param("s", $clinic_email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('Email already exists');
            }
        }
        
        if (strlen($clinic_pass) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }
        
        $hashed_password = password_hash($clinic_pass, PASSWORD_DEFAULT);
        
        $profile_image_path = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = './uploads/';
            
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception('Failed to create upload directory');
                }
            }
            
            $file_info = pathinfo($_FILES['profile_image']['name']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                throw new Exception('Invalid file type. Only JPG, JPEG, PNG, GIF and WEBP files are allowed');
            }
            
            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                throw new Exception('File size too large. Maximum size is 5MB');
            }
            
            $filename = 'clinic_' . uniqid() . '.' . $file_info['extension'];
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                $profile_image_path = $filename;
            } else {
                throw new Exception('Failed to upload profile image');
            }
        }
        
        $sql = "INSERT INTO clinics (clinic_name, clinic_email, contact_number, location, 
                available_timing, clinic_pass, profile_image, about) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if (isset($pdo)) {
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $clinic_name, $clinic_email, $contact_number, $location,
                $available_timing, $hashed_password, $profile_image_path, $about
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
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", 
                $clinic_name, $clinic_email, $contact_number, $location,
                $available_timing, $hashed_password, $profile_image_path, $about
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Clinic registered successfully!',
                    'clinic_id' => $conn->insert_id
                ]);
            } else {
                throw new Exception('Failed to save clinic data');
            }
        }
        
    } catch (Exception $e) {
        if (isset($target_path) && file_exists($target_path)) {
            unlink($target_path);
        }
        
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getClinics() {
    global $conn;
    
    try {
        $searchParam = $_GET['search'] ?? '';
        $sql = "SELECT clinic_id, clinic_name, clinic_email, contact_number, location, 
                    available_timing, profile_image, about, status, created_at, updated_at
                FROM clinics";
        
        if (!empty($searchParam)) {
            $sql .= " WHERE clinic_name LIKE ? OR location LIKE ? OR clinic_email LIKE ?";
            $stmt = $conn->prepare($sql . " ORDER BY clinic_name ASC");
            $searchTerm = "%$searchParam%";
            $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql . " ORDER BY clinic_name ASC");
        }
        
        if ($result === false) {
            throw new Exception("Database query failed: " . $conn->error);
        }
        
        $clinics = [];
        while ($row = $result->fetch_assoc()) {
            $row['profile_image'] = fixImagePath($row['profile_image']);
            $clinics[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'clinics' => $clinics]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch clinics: ' . $e->getMessage()]);
    }
}

function getClinic($clinicId) {
    global $conn;
    
    if (!$clinicId) {
        echo json_encode(['status' => 'error', 'message' => 'Clinic ID is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("SELECT clinic_id, clinic_name, clinic_email, contact_number, location,
                                available_timing, profile_image, about, status, created_at, updated_at
                                FROM clinics WHERE clinic_id = ?");
        
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
        $clinic['profile_image'] = fixImagePath($clinic['profile_image']);
        
        echo json_encode(['status' => 'success', 'clinic' => $clinic]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch clinic: ' . $e->getMessage()]);
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
        
        if (empty($clinic_id) || empty($clinic_name) || empty($clinic_email) || 
            empty($contact_number) || empty($location) || empty($available_timing)) {
            echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled']);
            return;
        }
        
        if (!filter_var($clinic_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
            return;
        }
        
        $checkStmt = $conn->prepare("SELECT clinic_id FROM clinics WHERE clinic_id = ?");
        $checkStmt->bind_param("i", $clinic_id);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Clinic not found']);
            return;
        }
        
        $profile_image = $existing_img;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
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
            
            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                echo json_encode(['status' => 'error', 'message' => 'File size too large. Maximum size is 5MB']);
                return;
            }
            
            $fileName = 'clinic_' . $clinic_id . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
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
        
        $updateFields = [
            'clinic_name = ?', 'clinic_email = ?', 'contact_number = ?', 'location = ?',
            'available_timing = ?', 'about = ?', 'status = ?', 'profile_image = ?', 'updated_at = NOW()'
        ];
        
        $params = [$clinic_name, $clinic_email, $contact_number, $location, $available_timing, $about, $status, $profile_image];
        $types = 'ssssssss';
        
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
            echo json_encode(['status' => 'success', 'message' => 'Clinic updated successfully']);
        } else {
            throw new Exception("Update failed: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update clinic: ' . $e->getMessage()]);
    }
}

function deleteClinic() {
    global $conn;
    
    try {
        // Get clinic_id from different sources
        $clinic_id = null;
        
        // Try to get from JSON input first
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input && isset($input['clinic_id'])) {
            $clinic_id = $input['clinic_id'];
        }
        // Fallback to POST data
        elseif (isset($_POST['clinic_id'])) {
            $clinic_id = $_POST['clinic_id'];
        }
        // Fallback to GET data
        elseif (isset($_GET['clinic_id'])) {
            $clinic_id = $_GET['clinic_id'];
        }
        
        if (empty($clinic_id) || !is_numeric($clinic_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Valid Clinic ID is required']);
            return;
        }
        
        // Convert to integer
        $clinic_id = intval($clinic_id);
        
        // Check if clinic exists and get profile image
        $checkStmt = $conn->prepare("SELECT profile_image FROM clinics WHERE clinic_id = ?");
        if (!$checkStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $checkStmt->bind_param("i", $clinic_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Clinic not found']);
            return;
        }
        
        $clinic = $result->fetch_assoc();
        
        // Check for dependencies (appointments) - Skip this check if appointments table doesn't exist
        try {
            $dependencyStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE clinic_id = ?");
            if ($dependencyStmt) {
                $dependencyStmt->bind_param("i", $clinic_id);
                $dependencyStmt->execute();
                $dependencyResult = $dependencyStmt->get_result();
                $dependencyCount = $dependencyResult->fetch_assoc()['count'];
                
                if ($dependencyCount > 0) {
                    echo json_encode([
                        'status' => 'error', 
                        'message' => 'Cannot delete clinic. There are ' . $dependencyCount . ' existing appointments associated with this clinic.'
                    ]);
                    return;
                }
            }
        } catch (Exception $e) {
            // If appointments table doesn't exist, just continue with deletion
        }
        
        // Delete the clinic
        $deleteStmt = $conn->prepare("DELETE FROM clinics WHERE clinic_id = ?");
        if (!$deleteStmt) {
            throw new Exception("Delete prepare failed: " . $conn->error);
        }
        
        $deleteStmt->bind_param("i", $clinic_id);
        
        if ($deleteStmt->execute()) {
            // Check if any rows were affected
            if ($deleteStmt->affected_rows > 0) {
                // Delete associated image file
                if (!empty($clinic['profile_image'])) {
                    $imagePaths = [
                        $clinic['profile_image'],
                        'uploads/' . basename($clinic['profile_image']),
                        '../uploads/' . basename($clinic['profile_image']),
                        './uploads/' . basename($clinic['profile_image'])
                    ];
                    
                    foreach ($imagePaths as $imagePath) {
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                            break;
                        }
                    }
                }
                
                echo json_encode(['status' => 'success', 'message' => 'Clinic deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No clinic was deleted. Clinic may not exist.']);
            }
        } else {
            throw new Exception("Delete execution failed: " . $deleteStmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete clinic: ' . $e->getMessage()]);
    }
}
?>