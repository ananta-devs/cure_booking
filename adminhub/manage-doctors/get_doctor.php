<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../include/database_connection.php';

function sendResponse($status, $message, $data = null) {
    ob_clean();
    header('Content-Type: application/json');
    $response = ['status' => $status, 'message' => $message];
    if ($data) $response = array_merge($response, $data);
    echo json_encode($response);
    exit;
}

function getRequestData() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true) ?: $_POST;
        return $data['action'] ?? $_POST['action'] ?? '';
    }
    return $_GET['action'] ?? '';
}

function validateRequiredFields($fields, $data) {
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            sendResponse('error', ucfirst(str_replace('_', ' ', $field)) . ' is required.');
        }
    }
}

function getImageUrl($imageName) {
    if (empty($imageName)) {
        return "../assets/img/default-doctor.png"; // Default image path
    }
    
    // Check if image exists in uploads directory
    $imagePath = "./uploads/" . $imageName;
    if (file_exists($imagePath)) {
        return "./uploads/" . $imageName;
    }
    
    // Check alternative paths
    $alternativePaths = [
        "../uploads/" . $imageName,
        "uploads/" . $imageName,
        "./assets/uploads/" . $imageName
    ];
    
    foreach ($alternativePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // Return default if image not found
    return "../assets/img/default-doctor.png";
}

function handleFileUpload($oldImage = '') {
    // Create uploads directory if it doesn't exist
    $uploadDir = './uploads/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            sendResponse('error', 'Failed to create upload directory.');
        }
    }
    
    // If no new file uploaded, return old image
    if (empty($_FILES['doc_img']['name'])) {
        return $oldImage;
    }
    
    // Check for upload errors
    if ($_FILES['doc_img']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
        ];
        
        $errorMsg = $errorMessages[$_FILES['doc_img']['error']] ?? 'Unknown upload error.';
        sendResponse('error', $errorMsg);
    }
    
    // Validate file type
    $fileInfo = pathinfo($_FILES['doc_img']['name']);
    $fileExtension = strtolower($fileInfo['extension'] ?? '');
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (empty($fileExtension) || !in_array($fileExtension, $allowedTypes)) {
        sendResponse('error', 'Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.');
    }
    
    // Check file size (2MB limit)
    if ($_FILES['doc_img']['size'] > 2 * 1024 * 1024) {
        sendResponse('error', 'File size exceeds 2MB limit.');
    }
    
    // Validate image using getimagesize
    $imageInfo = getimagesize($_FILES['doc_img']['tmp_name']);
    if ($imageInfo === false) {
        sendResponse('error', 'Invalid image file.');
    }
    
    // Generate unique filename
    $uniqueFilename = uniqid() . time() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $uniqueFilename;
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES['doc_img']['tmp_name'], $uploadPath)) {
        sendResponse('error', 'Failed to upload image file.');
    }
    
    // Set proper permissions
    chmod($uploadPath, 0644);
    
    // Delete old image if it exists and is different from new one
    if ($oldImage && $oldImage !== $uniqueFilename) {
        $oldImagePaths = [
            $uploadDir . $oldImage,
            './uploads/' . $oldImage,
            '../uploads/' . $oldImage
        ];
        
        foreach ($oldImagePaths as $oldPath) {
            if (file_exists($oldPath)) {
                unlink($oldPath);
                break;
            }
        }
    }
    
    return $uniqueFilename;
}

function validateClinicAssignments($assignments) {
    if (!$assignments || !is_array($assignments)) {
        return ['valid' => false, 'message' => 'Invalid clinic assignments.'];
    }
    
    $timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
    $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    foreach ($assignments as $assignment) {
        if (empty($assignment['clinic_id']) || !is_array($assignment['availability'])) {
            return ['valid' => false, 'message' => 'Invalid assignment structure.'];
        }
        
        $hasSlot = false;
        foreach ($weekDays as $day) {
            foreach ($timeSlots as $slot) {
                if (!empty($assignment['availability'][$day][$slot])) {
                    $hasSlot = true;
                    break 2;
                }
            }
        }
        
        if (!$hasSlot) {
            return ['valid' => false, 'message' => 'Each clinic must have at least one time slot.'];
        }
    }
    
    return ['valid' => true];
}

function saveClinicAssignments($doctorId, $assignments) {
    global $pdo;
    
    foreach ($assignments as $assignment) {
        $sql = "INSERT INTO doctor_clinic_assignments (doctor_id, clinic_id, availability_schedule, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$doctorId, $assignment['clinic_id'], json_encode($assignment['availability'])]);
    }
}

function saveDoctor() {
    global $pdo;
    
    validateRequiredFields(['doc_name', 'doc_specia', 'fees', 'doc_pass'], $_POST);
    
    if (empty($_POST['clinic_assignments'])) {
        sendResponse('error', 'At least one clinic assignment required.');
    }
    
    $clinicAssignments = json_decode($_POST['clinic_assignments'], true);
    $validation = validateClinicAssignments($clinicAssignments);
    if (!$validation['valid']) {
        sendResponse('error', $validation['message']);
    }
    
    try {
        $pdo->beginTransaction();
        
        $doctorImg = handleFileUpload();
        $hashedPassword = password_hash($_POST['doc_pass'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO doctor (doc_name, doc_specia, doc_email, fees, doc_img, gender, experience, location, education, bio, doc_pass) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['doc_name'],
            $_POST['doc_specia'],
            $_POST['doc_email'] ?: null,
            $_POST['fees'],
            $doctorImg,
            $_POST['gender'] ?: null,
            $_POST['experience'] ?: null,
            $_POST['location'] ?: null,
            $_POST['education'] ?: null,
            $_POST['bio'] ?: null,
            $hashedPassword
        ]);
        
        $doctorId = $pdo->lastInsertId();
        saveClinicAssignments($doctorId, $clinicAssignments);
        
        $pdo->commit();
        sendResponse('success', 'Doctor added successfully!', ['doctor_id' => $doctorId]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        if (!empty($doctorImg) && file_exists('./uploads/' . $doctorImg)) {
            unlink('./uploads/' . $doctorImg);
        }
        sendResponse('error', 'Error: ' . $e->getMessage());
    }
}

function getDoctorsList() {
    global $pdo;
    
    try {
        $sql = "SELECT d.*, 
                       GROUP_CONCAT(DISTINCT c.clinic_name SEPARATOR ', ') as clinic_names,
                       COUNT(DISTINCT dca.clinic_id) as clinic_count
                FROM doctor d
                LEFT JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id
                LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id AND c.status = 'active'
                GROUP BY d.doc_id
                ORDER BY d.doc_name";
        
        $doctors = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        // Process image URLs for each doctor
        foreach ($doctors as &$doctor) {
            $doctor['image_url'] = getImageUrl($doctor['doc_img']);
            $doctor['has_image'] = !empty($doctor['doc_img']) && file_exists('./uploads/' . $doctor['doc_img']);
        }
        
        sendResponse('success', 'Doctors retrieved successfully', ['doctors' => $doctors]);
        
    } catch (Exception $e) {
        sendResponse('error', 'Error retrieving doctors: ' . $e->getMessage());
    }
}

function getDoctorById() {
    global $pdo;
    
    $doctorId = $_GET['id'] ?? $_POST['id'] ?? '';
    if (empty($doctorId)) sendResponse('error', 'Doctor ID required.');
    
    try {
        $doctor = $pdo->prepare("SELECT * FROM doctor WHERE doc_id = ?");
        $doctor->execute([$doctorId]);
        $doctorData = $doctor->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctorData) sendResponse('error', 'Doctor not found.');
        
        // Add image URL to doctor data
        $doctorData['image_url'] = getImageUrl($doctorData['doc_img']);
        $doctorData['has_image'] = !empty($doctorData['doc_img']) && file_exists('./uploads/' . $doctorData['doc_img']);
        
        $assignments = $pdo->prepare("
            SELECT dca.*, c.* FROM doctor_clinic_assignments dca
            JOIN clinics c ON dca.clinic_id = c.clinic_id
            WHERE dca.doctor_id = ? ORDER BY c.clinic_name
        ");
        $assignments->execute([$doctorId]);
        $assignmentData = $assignments->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($assignmentData as &$assignment) {
            $assignment['availability_schedule'] = json_decode($assignment['availability_schedule'], true);
        }
        
        sendResponse('success', 'Doctor retrieved successfully', [
            'doctor' => $doctorData,
            'clinic_assignments' => $assignmentData
        ]);
        
    } catch (Exception $e) {
        sendResponse('error', 'Error retrieving doctor: ' . $e->getMessage());
    }
}

function updateDoctor() {
    global $pdo;
    
    $doctorId = $_POST['doc_id'] ?? '';
    if (empty($doctorId)) sendResponse('error', 'Doctor ID required.');
    
    validateRequiredFields(['doc_name', 'doc_specia', 'fees'], $_POST);
    
    try {
        $pdo->beginTransaction();
        
        // Get existing image name
        $existingImg = $_POST['existing_img'] ?? '';
        $doctorImg = handleFileUpload($existingImg);
        
        $sql = "UPDATE doctor SET doc_name=?, doc_specia=?, doc_email=?, fees=?, doc_img=?, 
                gender=?, experience=?, location=?, education=?, bio=?";
        $params = [
            $_POST['doc_name'], $_POST['doc_specia'], $_POST['doc_email'] ?: null,
            $_POST['fees'], $doctorImg, $_POST['gender'] ?: null,
            $_POST['experience'] ?: null, $_POST['location'] ?: null,
            $_POST['education'] ?: null, $_POST['bio'] ?: null
        ];
        
        if (!empty($_POST['doc_pass'])) {
            $sql .= ", doc_pass=?";
            $params[] = password_hash($_POST['doc_pass'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE doc_id=?";
        $params[] = $doctorId;
        
        $pdo->prepare($sql)->execute($params);
        
        if (!empty($_POST['clinic_assignments'])) {
            $pdo->prepare("DELETE FROM doctor_clinic_assignments WHERE doctor_id = ?")->execute([$doctorId]);
            $clinicAssignments = json_decode($_POST['clinic_assignments'], true);
            saveClinicAssignments($doctorId, $clinicAssignments);
        }
        
        $pdo->commit();
        sendResponse('success', 'Doctor updated successfully!');
        
    } catch (Exception $e) {
        $pdo->rollback();
        sendResponse('error', 'Error updating doctor: ' . $e->getMessage());
    }
}

function deleteDoctor() {
    global $pdo;
    
    // Get doctor ID from multiple possible sources
    $doctorId = $_POST['doctor_id'] ?? $_POST['id'] ?? $_GET['id'] ?? '';
    
    if (empty($doctorId)) {
        sendResponse('error', 'Doctor ID required.');
    }
    
    try {
        $pdo->beginTransaction();
        
        // First, get the doctor's image to delete it later
        $doctor = $pdo->prepare("SELECT doc_img FROM doctor WHERE doc_id = ?");
        $doctor->execute([$doctorId]);
        $doctorData = $doctor->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctorData) {
            $pdo->rollback();
            sendResponse('error', 'Doctor not found.');
        }
        
        // Delete doctor clinic assignments first (foreign key constraint)
        $pdo->prepare("DELETE FROM doctor_clinic_assignments WHERE doctor_id = ?")->execute([$doctorId]);
        
        // Delete any appointments related to this doctor (if applicable)
        $pdo->prepare("DELETE FROM appointments WHERE doctor_id = ?")->execute([$doctorId]);
        
        // Delete the doctor record
        $deleteStmt = $pdo->prepare("DELETE FROM doctor WHERE doc_id = ?");
        $deleteStmt->execute([$doctorId]);
        
        // Check if any rows were affected
        if ($deleteStmt->rowCount() === 0) {
            $pdo->rollback();
            sendResponse('error', 'Doctor not found or already deleted.');
        }
        
        // Delete image file if exists
        if (!empty($doctorData['doc_img'])) {
            $imagePaths = [
                './uploads/' . $doctorData['doc_img'],
                '../uploads/' . $doctorData['doc_img'],
                'uploads/' . $doctorData['doc_img']
            ];
            
            foreach ($imagePaths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                    break;
                }
            }
        }
        
        $pdo->commit();
        sendResponse('success', 'Doctor deleted successfully!');
        
    } catch (Exception $e) {
        $pdo->rollback();
        sendResponse('error', 'Error deleting doctor: ' . $e->getMessage());
    }
}

function getClinicAvailability() {
    global $pdo;
    
    $clinicId = $_GET['clinic_id'] ?? '';
    if (empty($clinicId)) sendResponse('error', 'Clinic ID required.');
    
    try {
        $sql = "SELECT dca.availability_schedule, d.doc_name, d.doc_id, d.doc_specia
                FROM doctor_clinic_assignments dca
                JOIN doctor d ON dca.doctor_id = d.doc_id
                WHERE dca.clinic_id = ? ORDER BY d.doc_name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$clinicId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $occupiedSlots = [];
        $timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
        $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($assignments as $assignment) {
            $schedule = json_decode($assignment['availability_schedule'], true);
            $doctorInfo = [
                'doc_id' => $assignment['doc_id'],
                'doc_name' => $assignment['doc_name'],
                'doc_specia' => $assignment['doc_specia']
            ];
            
            foreach ($weekDays as $day) {
                foreach ($timeSlots as $slot) {
                    if (!empty($schedule[$day][$slot])) {
                        $occupiedSlots[$day][$slot][] = $doctorInfo;
                    }
                }
            }
        }
        
        sendResponse('success', 'Availability retrieved successfully', ['occupied_slots' => $occupiedSlots]);
        
    } catch (Exception $e) {
        sendResponse('error', 'Error retrieving availability: ' . $e->getMessage());
    }
}

// Route actions
$action = getRequestData();

switch ($action) {
    case 'add': saveDoctor(); break;
    case 'list': getDoctorsList(); break;
    case 'get': getDoctorById(); break;
    case 'update': updateDoctor(); break;
    case 'delete': deleteDoctor(); break;
    case 'get_clinic_availability': getClinicAvailability(); break;
    default: sendResponse('error', 'Invalid action specified');
}

ob_end_clean();
?>