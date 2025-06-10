<?php
// Database connection
$host = 'localhost';
$dbname = 'cure_booking';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    sendResponse('error', 'Database connection failed: ' . $e->getMessage());
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_specialities':
        fetchSpecialities($pdo);
        break;
    case 'get_doctors':
        fetchDoctors($pdo, $_GET['speciality'] ?? '');
        break;
    case 'save_appointment':
        saveAppointment($pdo);
        break;
    default:
        sendResponse('error', 'Invalid action.');
}

function fetchSpecialities($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT doc_specia FROM doctor ORDER BY doc_specia");
        $stmt->execute();
        $specialities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse('success', 'Specialities fetched successfully', $specialities);
    } catch (PDOException $e) {
        sendResponse('error', 'Failed to fetch specialities: ' . $e->getMessage());
    }
}

function fetchDoctors($pdo, $speciality = '') {
    try {
        if (!empty($speciality)) {
            $stmt = $pdo->prepare("SELECT id, doc_name, doc_specia, fees FROM doctor WHERE doc_specia = :speciality ORDER BY doc_name");
            $stmt->bindParam(':speciality', $speciality);
        } else {
            $stmt = $pdo->prepare("SELECT id, doc_name, doc_specia, fees FROM doctor ORDER BY doc_name");
        }
        
        $stmt->execute();
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse('success', 'Doctors fetched successfully', $doctors);
    } catch (PDOException $e) {
        sendResponse('error', 'Failed to fetch doctors: ' . $e->getMessage());
    }
}

function saveAppointment($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Invalid request method.');
        return;
    }
    
    try {
        $requiredFields = ['firstName', 'gender', 'phone', 'email', 'specialityType', 'doctor', 'preferredDate', 'time'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                sendResponse('error', ucfirst(str_replace(['_', 'firstName'], [' ', 'name'], $field)) . ' is required.');
                return;
            }
        }
        
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            sendResponse('error', 'Please enter a valid email address.');
            return;
        }
        
        if ($_POST['preferredDate'] < date('Y-m-d')) {
            sendResponse('error', 'Please select a future date.');
            return;
        }
        
        $doctorId = intval($_POST['doctor']);
        $stmt = $pdo->prepare("SELECT id FROM doctor WHERE id = :id");
        $stmt->bindParam(':id', $doctorId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            sendResponse('error', 'Selected doctor does not exist.');
            return;
        }
        
        $sql = "INSERT INTO appointments (patient_name, patient_gender, patient_phone, patient_email, doctor_id, appointment_date, appointment_time) 
                VALUES (:patient_name, :patient_gender, :patient_phone, :patient_email, :doctor_id, :appointment_date, :appointment_time)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':patient_name' => trim($_POST['firstName']),
            ':patient_gender' => $_POST['gender'],
            ':patient_phone' => trim($_POST['phone']),
            ':patient_email' => trim($_POST['email']),
            ':doctor_id' => $doctorId,
            ':appointment_date' => $_POST['preferredDate'],
            ':appointment_time' => $_POST['time']
        ]);
        
        sendResponse('success', 'Your appointment has been booked successfully! We will contact you soon to confirm.');
        
    } catch (PDOException $e) {
        sendResponse('error', 'Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        sendResponse('error', 'An error occurred: ' . $e->getMessage());
    }
}

function sendResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}
?>