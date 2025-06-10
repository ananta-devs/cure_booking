<?php

// Include database connection
require_once '../include/database_connection.php';

// Helper function to send JSON response
function sendResponse($status, $message, $data = null) {
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
    default: sendResponse('error', 'Invalid action specified');
}

function saveDoctor() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Invalid request method.');
    }
    
    try {
        // Validate required fields
        $requiredFields = ['doc_name', 'doc_specia', 'fees'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                sendResponse('error', ucfirst(str_replace('_', ' ', $field)) . ' is required.');
            }
        }
        
        // Handle file upload
        $uploadDir = 'uploads/';
        $doctorImg = '';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        if (!empty($_FILES['doc_img']['name'])) {
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
        
        // Process availability data
        $availability = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
        
        foreach ($days as $day) {
            $availability[$day] = [];
            foreach ($timeSlots as $slot) {
                $availability[$day][$slot] = isset($_POST['availability'][$day][$slot]) ? true : false;
            }
        }
        
        // Prepare SQL statement
        $sql = "INSERT INTO doctor (doc_name, doc_specia, doc_email, fees, doc_img, gender, experience, location, education, bio, availability) 
                VALUES (:doc_name, :doc_specia, :doc_email, :fees, :doc_img, :gender, :experience, :location, :education, :bio, :availability)";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        $params = [
            ':doc_name' => trim($_POST['doc_name']),
            ':doc_specia' => trim($_POST['doc_specia']),
            ':doc_email' => trim($_POST['doc_email'] ?? ''),
            ':fees' => floatval($_POST['fees']),
            ':doc_img' => $doctorImg ?: null,
            ':gender' => $_POST['gender'] ?? 'male',
            ':experience' => intval($_POST['experience'] ?? 5),
            ':location' => trim($_POST['location'] ?? ''),
            ':education' => trim($_POST['education'] ?? ''),
            ':bio' => trim($_POST['bio'] ?? ''),
            ':availability' => json_encode($availability)
        ];
        
        $stmt->execute($params);
        sendResponse('success', 'Doctor information saved successfully!');
    } catch (PDOException $e) {
        sendResponse('error', 'Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendResponse('error', 'An error occurred: ' . $e->getMessage());
    }
}

function getDoctorsList() {
    global $pdo;
    
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $limit = 10;
    $offset = ($page - 1) * $limit;

    try {
        // Build the query
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE doc_name LIKE ? OR doc_specia LIKE ?";
            $searchPattern = "%{$search}%";
            $params = [$searchPattern, $searchPattern];
        }
        
        // Count total records
        $countQuery = "SELECT COUNT(*) AS total FROM doctor {$whereClause}";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalRecords / $limit);
        
        // Get doctors data
        $query = "SELECT id, doc_name, doc_specia, doc_email, fees, doc_img, gender, experience 
                FROM doctor {$whereClause} 
                ORDER BY doc_name 
                LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse('success', 'Doctors list retrieved successfully', [
            'doctors' => $doctors, 
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords,
                'limit' => $limit
            ]
        ]);
    } catch (PDOException $e) {
        sendResponse('error', 'Database error: ' . $e->getMessage());
    }
}

function getDoctorById() {
    global $pdo;
    
    $doctorId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($doctorId <= 0) {
        sendResponse('error', 'Invalid doctor ID');
    }

    try {
        $query = "SELECT * FROM doctor WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$doctorId]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctor) {
            sendResponse('error', 'Doctor not found');
        }
        
        sendResponse('success', 'Doctor information retrieved successfully', ['doctor' => $doctor]);
    } catch (PDOException $e) {
        sendResponse('error', 'Database error: ' . $e->getMessage());
    }
}

function updateDoctor() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Invalid request method');
    }
    
    try {
        if (empty($_POST['doctor_id']) || !is_numeric($_POST['doctor_id'])) {
            sendResponse('error', 'Invalid doctor ID');
        }
        
        $doctorId = (int)$_POST['doctor_id'];
        
        // Validate required fields
        $requiredFields = ['doc_name', 'doc_specia', 'fees'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                sendResponse('error', ucfirst(str_replace('_', ' ', $field)) . ' is required.');
            }
        }
        
        // Get current doctor data
        $checkQuery = "SELECT doc_img FROM doctor WHERE id = ?";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$doctorId]);
        $existingDoctor = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingDoctor) {
            sendResponse('error', 'Doctor not found');
        }
        
        $currentImage = $existingDoctor['doc_img'];
        $doctorImg = $currentImage;
        
        // Handle file upload
        if (!empty($_FILES['doc_img']['name'])) {
            $uploadDir = 'uploads/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
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
                
                // Delete old image
                if ($currentImage && file_exists($uploadDir . $currentImage)) {
                    unlink($uploadDir . $currentImage);
                }
            } else {
                sendResponse('error', 'Failed to upload the image.');
            }
        }
        
        // Process availability data
        $availability = [];
        
        // Check if availability data was sent as JSON string
        if (isset($_POST['availability']) && is_string($_POST['availability'])) {
            $availability = json_decode($_POST['availability'], true);
        } else {
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
            
            foreach ($days as $day) {
                $availability[$day] = [];
                foreach ($timeSlots as $slot) {
                    $availability[$day][$slot] = isset($_POST['availability'][$day][$slot]) ? true : false;
                }
            }
        }
        
        // Prepare SQL statement
        $sql = "UPDATE doctor SET 
                doc_name = :doc_name, 
                doc_specia = :doc_specia, 
                doc_email = :doc_email, 
                fees = :fees, 
                gender = :gender, 
                experience = :experience, 
                location = :location, 
                education = :education, 
                bio = :bio,
                availability = :availability";
        
        // Only update image if a new one was uploaded
        if ($doctorImg !== $currentImage) {
            $sql .= ", doc_img = :doc_img";
        }
        
        $sql .= " WHERE id = :doctor_id";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        $params = [
            ':doctor_id' => $doctorId,
            ':doc_name' => trim($_POST['doc_name']),
            ':doc_specia' => trim($_POST['doc_specia']),
            ':doc_email' => trim($_POST['doc_email'] ?? ''),
            ':fees' => floatval($_POST['fees']),
            ':gender' => $_POST['gender'] ?? 'male',
            ':experience' => intval($_POST['experience'] ?? 0),
            ':location' => trim($_POST['location'] ?? ''),
            ':education' => trim($_POST['education'] ?? ''),
            ':bio' => trim($_POST['bio'] ?? ''),
            ':availability' => json_encode($availability)
        ];
        
        if ($doctorImg !== $currentImage) {
            $params[':doc_img'] = $doctorImg;
        }
        
        $stmt->execute($params);
        sendResponse('success', 'Doctor information updated successfully!');
    } catch (PDOException $e) {
        sendResponse('error', 'Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendResponse('error', 'An error occurred: ' . $e->getMessage());
    }
}

function deleteDoctor() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Invalid request method');
    }
    
    try {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!isset($data['doctor_id']) || !is_numeric($data['doctor_id'])) {
            sendResponse('error', 'Invalid doctor ID');
        }
        
        $doctorId = (int)$data['doctor_id'];
        
        // Get doctor image before deletion
        $imageQuery = "SELECT doc_img FROM doctor WHERE id = ?";
        $imageStmt = $pdo->prepare($imageQuery);
        $imageStmt->execute([$doctorId]);
        $doctor = $imageStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctor) {
            sendResponse('error', 'Doctor not found');
        }
        
        // Delete doctor from database
        $deleteQuery = "DELETE FROM doctor WHERE id = ?";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute([$doctorId]);
        
        // Delete doctor image if exists
        if ($doctor['doc_img'] && file_exists('uploads/' . $doctor['doc_img'])) {
            unlink('uploads/' . $doctor['doc_img']);
        }
        
        sendResponse('success', 'Doctor deleted successfully');
    } catch (PDOException $e) {
        sendResponse('error', 'Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendResponse('error', 'An error occurred: ' . $e->getMessage());
    }
}