<?php
// Start output buffering to catch any unwanted output
ob_start();

// Set error reporting to not display errors to output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include database connection
require_once '../include/database_connection.php';

// Helper function to send JSON response
function sendResponse($status, $message, $data = null) {
    // Clean any output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    header('Content-Type: application/json');
    $response = ['status' => $status, 'message' => $message];
    if ($data) $response = array_merge($response, $data);
    echo json_encode($response);
    exit;
}

// Determine operation from request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    $action = isset($data['action']) ? $data['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
} else {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
}

// Route to appropriate function
switch ($action) {
    case 'add': saveDoctor(); break;
    case 'list': getDoctorsList(); break;
    case 'get': getDoctorById(); break;
    case 'update': updateDoctor(); break;
    case 'delete': deleteDoctor(); break;
    case 'get_clinic_availability': getClinicAvailability(); break;
    default: sendResponse('error', 'Invalid action specified');
}

function saveDoctor() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Invalid request method.');
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Validate required fields
        $requiredFields = ['doc_name', 'doc_specia', 'fees', 'doc_pass'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                sendResponse('error', ucfirst(str_replace('_', ' ', $field)) . ' is required.');
            }
        }
        
        // Validate clinic assignments
        if (empty($_POST['clinic_assignments'])) {
            sendResponse('error', 'At least one clinic assignment is required.');
        }
        
        $clinicAssignments = json_decode($_POST['clinic_assignments'], true);
        if (!$clinicAssignments || !is_array($clinicAssignments)) {
            sendResponse('error', 'Invalid clinic assignments data.');
        }
        
        // Validate time slot conflicts
        $conflictCheck = validateTimeSlotConflicts($clinicAssignments);
        if (!$conflictCheck['valid']) {
            sendResponse('error', $conflictCheck['message']);
        }
        
        // Handle file upload
        $uploadDir = '../uploads/'; // Make sure this path is correct
        $doctorImg = '';
        
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                sendResponse('error', 'Failed to create upload directory.');
            }
        }
        
        if (!empty($_FILES['doc_img']['name'])) {
            // Check for upload errors
            if ($_FILES['doc_img']['error'] !== UPLOAD_ERR_OK) {
                sendResponse('error', 'File upload error: ' . $_FILES['doc_img']['error']);
            }
            
            $fileExtension = pathinfo($_FILES['doc_img']['name'], PATHINFO_EXTENSION);
            $uniqueFilename = uniqid() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $uniqueFilename;
            
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                sendResponse('error', 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.');
            }
            
            if ($_FILES['doc_img']['size'] > 2000000) {
                sendResponse('error', 'File size exceeds the limit of 2MB.');
            }
            
            if (move_uploaded_file($_FILES['doc_img']['tmp_name'], $uploadFile)) {
                $doctorImg = $uniqueFilename;
            } else {
                sendResponse('error', 'Failed to upload the image.');
            }
        }
        
        // Hash the password
        $hashedPassword = password_hash($_POST['doc_pass'], PASSWORD_DEFAULT);
        
        // Prepare SQL statement for doctor
        $sql = "INSERT INTO doctor (doc_name, doc_specia, doc_email, fees, doc_img, gender, experience, location, education, bio, doc_pass) 
                VALUES (:doc_name, :doc_specia, :doc_email, :fees, :doc_img, :gender, :experience, :location, :education, :bio, :doc_pass)";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters with null checks
        $doc_email = !empty($_POST['doc_email']) ? $_POST['doc_email'] : null;
        $gender = !empty($_POST['gender']) ? $_POST['gender'] : null;
        $experience = !empty($_POST['experience']) ? $_POST['experience'] : null;
        $location = !empty($_POST['location']) ? $_POST['location'] : null;
        $education = !empty($_POST['education']) ? $_POST['education'] : null;
        $bio = !empty($_POST['bio']) ? $_POST['bio'] : null;
        
        $stmt->bindParam(':doc_name', $_POST['doc_name']);
        $stmt->bindParam(':doc_specia', $_POST['doc_specia']);
        $stmt->bindParam(':doc_email', $doc_email);
        $stmt->bindParam(':fees', $_POST['fees']);
        $stmt->bindParam(':doc_img', $doctorImg);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':experience', $experience);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':education', $education);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':doc_pass', $hashedPassword);
        
        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert doctor record.');
        }
        
        // Get the doctor ID
        $doctorId = $pdo->lastInsertId();
        
        // Insert clinic assignments
        foreach ($clinicAssignments as $assignment) {
            $clinicId = $assignment['clinic_id'];
            $availability = $assignment['availability'];
            
            // Insert into doctor_clinic_assignments table
            $assignmentSql = "INSERT INTO doctor_clinic_assignments (doctor_id, clinic_id, availability_schedule, created_at) 
                             VALUES (:doctor_id, :clinic_id, :availability_schedule, NOW())";
            
            $assignmentStmt = $pdo->prepare($assignmentSql);
            $assignmentStmt->bindParam(':doctor_id', $doctorId);
            $assignmentStmt->bindParam(':clinic_id', $clinicId);
            $assignmentStmt->bindParam(':availability_schedule', json_encode($availability));
            
            if (!$assignmentStmt->execute()) {
                throw new Exception('Failed to insert clinic assignment.');
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        sendResponse('success', 'Doctor added successfully!', ['doctor_id' => $doctorId]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        // Delete uploaded file if it exists
        if (!empty($doctorImg) && file_exists($uploadDir . $doctorImg)) {
            unlink($uploadDir . $doctorImg);
        }
        
        sendResponse('error', 'Error: ' . $e->getMessage());
    }
}

function validateTimeSlotConflicts($clinicAssignments) {
    global $pdo;
    
    $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
    
    // Validate that clinic assignments have proper structure
    foreach ($clinicAssignments as $assignment) {
        if (!isset($assignment['clinic_id']) || empty($assignment['clinic_id'])) {
            return [
                'valid' => false,
                'message' => 'Each clinic assignment must have a valid clinic ID.'
            ];
        }
        
        if (!isset($assignment['availability']) || !is_array($assignment['availability'])) {
            return [
                'valid' => false,
                'message' => 'Each clinic assignment must have a valid availability schedule.'
            ];
        }
        
        // Validate that at least one time slot is selected for each clinic
        $hasTimeSlot = false;
        foreach ($weekDays as $day) {
            if (isset($assignment['availability'][$day])) {
                foreach ($timeSlots as $slot) {
                    if (isset($assignment['availability'][$day][$slot]) && 
                        $assignment['availability'][$day][$slot] === true) {
                        $hasTimeSlot = true;
                        break 2;
                    }
                }
            }
        }
        
        if (!$hasTimeSlot) {
            return [
                'valid' => false,
                'message' => 'Each clinic assignment must have at least one time slot selected.'
            ];
        }
    }
    
    return ['valid' => true];
}

function getDoctorsList() {
    global $pdo;
    
    try {
        $sql = "SELECT d.*, 
                       GROUP_CONCAT(DISTINCT c.clinic_name SEPARATOR ', ') as clinic_names,
                       COUNT(DISTINCT dca.clinic_id) as clinic_count
                FROM doctor d
                LEFT JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id
                LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id
                GROUP BY d.doc_id
                ORDER BY d.doc_name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse('success', 'Doctors retrieved successfully', ['doctors' => $doctors]);
        
    } catch (Exception $e) {
        sendResponse('error', 'Error retrieving doctors: ' . $e->getMessage());
    }
}

function getDoctorById() {
    global $pdo;
    
    $doctorId = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : '');
    
    if (empty($doctorId)) {
        sendResponse('error', 'Doctor ID is required.');
    }
    
    try {
        // Get doctor details
        $sql = "SELECT * FROM doctor WHERE doc_id = :doc_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':doc_id', $doctorId);
        $stmt->execute();
        
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctor) {
            sendResponse('error', 'Doctor not found.');
        }
        
        // Get clinic assignments
        $assignmentSql = "SELECT dca.*, c.clinic_name, c.location 
                         FROM doctor_clinic_assignments dca
                         JOIN clinics c ON dca.clinic_id = c.clinic_id
                         WHERE dca.doctor_id = :doctor_id";
        
        $assignmentStmt = $pdo->prepare($assignmentSql);
        $assignmentStmt->bindParam(':doctor_id', $doctorId);
        $assignmentStmt->execute();
        
        $assignments = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse availability schedules
        foreach ($assignments as &$assignment) {
            $assignment['availability_schedule'] = json_decode($assignment['availability_schedule'], true);
        }
        
        sendResponse('success', 'Doctor retrieved successfully', [
            'doctor' => $doctor,
            'clinic_assignments' => $assignments
        ]);
        
    } catch (Exception $e) {
        sendResponse('error', 'Error retrieving doctor: ' . $e->getMessage());
    }
}

function updateDoctor() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Invalid request method.');
    }
    
    try {
        $pdo->beginTransaction();
        
        $doctorId = $_POST['doc_id'] ?? '';
        if (empty($doctorId)) {
            sendResponse('error', 'Doctor ID is required.');
        }
        
        // Validate required fields
        $requiredFields = ['doc_name', 'doc_specia', 'fees'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                sendResponse('error', ucfirst(str_replace('_', ' ', $field)) . ' is required.');
            }
        }
        
        // Handle file upload
        $uploadDir = '../uploads/';
        $doctorImg = $_POST['existing_img'] ?? '';
        
        if (!empty($_FILES['doc_img']['name'])) {
            if ($_FILES['doc_img']['error'] !== UPLOAD_ERR_OK) {
                sendResponse('error', 'File upload error: ' . $_FILES['doc_img']['error']);
            }
            
            $fileExtension = pathinfo($_FILES['doc_img']['name'], PATHINFO_EXTENSION);
            $uniqueFilename = uniqid() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $uniqueFilename;
            
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                sendResponse('error', 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.');
            }
            
            if ($_FILES['doc_img']['size'] > 2000000) {
                sendResponse('error', 'File size exceeds the limit of 2MB.');
            }
            
            if (move_uploaded_file($_FILES['doc_img']['tmp_name'], $uploadFile)) {
                // Delete old image if it exists
                if (!empty($doctorImg) && file_exists($uploadDir . $doctorImg)) {
                    unlink($uploadDir . $doctorImg);
                }
                $doctorImg = $uniqueFilename;
            } else {
                sendResponse('error', 'Failed to upload the image.');
            }
        }
        
        // Update doctor record
        $sql = "UPDATE doctor SET 
                doc_name = :doc_name,
                doc_specia = :doc_specia,
                doc_email = :doc_email,
                fees = :fees,
                doc_img = :doc_img,
                gender = :gender,
                experience = :experience,
                location = :location,
                education = :education,
                bio = :bio";
        
        // Only update password if provided
        if (!empty($_POST['doc_pass'])) {
            $sql .= ", doc_pass = :doc_pass";
            $hashedPassword = password_hash($_POST['doc_pass'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE doc_id = :doc_id";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters with null checks
        $doc_email = !empty($_POST['doc_email']) ? $_POST['doc_email'] : null;
        $gender = !empty($_POST['gender']) ? $_POST['gender'] : null;
        $experience = !empty($_POST['experience']) ? $_POST['experience'] : null;
        $location = !empty($_POST['location']) ? $_POST['location'] : null;
        $education = !empty($_POST['education']) ? $_POST['education'] : null;
        $bio = !empty($_POST['bio']) ? $_POST['bio'] : null;
        
        $stmt->bindParam(':doc_name', $_POST['doc_name']);
        $stmt->bindParam(':doc_specia', $_POST['doc_specia']);
        $stmt->bindParam(':doc_email', $doc_email);
        $stmt->bindParam(':fees', $_POST['fees']);
        $stmt->bindParam(':doc_img', $doctorImg);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':experience', $experience);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':education', $education);
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':doc_id', $doctorId);
        
        if (!empty($_POST['doc_pass'])) {
            $stmt->bindParam(':doc_pass', $hashedPassword);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update doctor record.');
        }
        
        // Update clinic assignments if provided
        if (!empty($_POST['clinic_assignments'])) {
            $clinicAssignments = json_decode($_POST['clinic_assignments'], true);
            
            // Delete existing assignments
            $deleteAssignmentsSql = "DELETE FROM doctor_clinic_assignments WHERE doctor_id = :doctor_id";
            $deleteStmt = $pdo->prepare($deleteAssignmentsSql);
            $deleteStmt->bindParam(':doctor_id', $doctorId);
            $deleteStmt->execute();
            
            // Insert new assignments
            foreach ($clinicAssignments as $assignment) {
                $clinicId = $assignment['clinic_id'];
                $availability = $assignment['availability'];
                
                $assignmentSql = "INSERT INTO doctor_clinic_assignments (doctor_id, clinic_id, availability_schedule, created_at) 
                                 VALUES (:doctor_id, :clinic_id, :availability_schedule, NOW())";
                
                $assignmentStmt = $pdo->prepare($assignmentSql);
                $assignmentStmt->bindParam(':doctor_id', $doctorId);
                $assignmentStmt->bindParam(':clinic_id', $clinicId);
                $assignmentStmt->bindParam(':availability_schedule', json_encode($availability));
                
                if (!$assignmentStmt->execute()) {
                    throw new Exception('Failed to update clinic assignment.');
                }
            }
        }
        
        $pdo->commit();
        sendResponse('success', 'Doctor updated successfully!');
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        sendResponse('error', 'Error updating doctor: ' . $e->getMessage());
    }
}

function deleteDoctor() {
    global $pdo;
    
    $doctorId = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : '');
    
    if (empty($doctorId)) {
        sendResponse('error', 'Doctor ID is required.');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get doctor image to delete
        $imgSql = "SELECT doc_img FROM doctor WHERE doc_id = :doc_id";
        $imgStmt = $pdo->prepare($imgSql);
        $imgStmt->bindParam(':doc_id', $doctorId);
        $imgStmt->execute();
        $doctor = $imgStmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete clinic assignments first
        $deleteAssignmentsSql = "DELETE FROM doctor_clinic_assignments WHERE doctor_id = :doctor_id";
        $deleteAssignmentsStmt = $pdo->prepare($deleteAssignmentsSql);
        $deleteAssignmentsStmt->bindParam(':doctor_id', $doctorId);
        $deleteAssignmentsStmt->execute();
        
        // Delete doctor record
        $deleteDoctorSql = "DELETE FROM doctor WHERE doc_id = :doc_id";
        $deleteDoctorStmt = $pdo->prepare($deleteDoctorSql);
        $deleteDoctorStmt->bindParam(':doc_id', $doctorId);
        
        if (!$deleteDoctorStmt->execute()) {
            throw new Exception('Failed to delete doctor.');
        }
        
        // Delete doctor image file if it exists
        if (!empty($doctor['doc_img']) && file_exists('../uploads/' . $doctor['doc_img'])) {
            unlink('../uploads/' . $doctor['doc_img']);
        }
        
        $pdo->commit();
        sendResponse('success', 'Doctor deleted successfully!');
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        sendResponse('error', 'Error deleting doctor: ' . $e->getMessage());
    }
}

function getClinicAvailability() {
    global $pdo;
    
    $clinicId = isset($_GET['clinic_id']) ? $_GET['clinic_id'] : '';
    
    if (empty($clinicId)) {
        sendResponse('error', 'Clinic ID is required.');
    }
    
    try {
        // Get all time slots occupied by doctors at this clinic
        $sql = "SELECT dca.availability_schedule, d.doc_name 
                FROM doctor_clinic_assignments dca
                JOIN doctor d ON dca.doctor_id = d.doc_id
                WHERE dca.clinic_id = :clinic_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':clinic_id', $clinicId);
        $stmt->execute();
        
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $occupiedSlots = [];
        $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
        
        foreach ($assignments as $assignment) {
            $schedule = json_decode($assignment['availability_schedule'], true);
            
            foreach ($weekDays as $day) {
                foreach ($timeSlots as $slot) {
                    if (isset($schedule[$day][$slot]) && $schedule[$day][$slot] === true) {
                        $occupiedSlots[$day][$slot] = $assignment['doc_name'];
                    }
                }
            }
        }
        
        sendResponse('success', 'Clinic availability retrieved successfully', [
            'occupied_slots' => $occupiedSlots
        ]);
        
    } catch (Exception $e) {
        sendResponse('error', 'Error retrieving clinic availability: ' . $e->getMessage());
    }
}

// Clean output buffer at the end
if (ob_get_length()) {
    ob_end_clean();
}
?>