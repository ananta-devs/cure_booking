<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get the action parameter
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'search':
        handleSearch($pdo);
        break;
    case 'doctor_details':
        getDoctorDetails($pdo);
        break;
    case 'clinic_details':
        getClinicDetails($pdo);
        break;
    case 'clinic_doctors':
        getClinicDoctors($pdo);
        break;
    case 'debug_doctor':
        debugDoctorData($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleSearch($pdo) {
    $query = $_GET['query'] ?? '';
    
    if (empty($query)) {
        echo json_encode(['doctors' => [], 'clinics' => []]);
        return;
    }
    
    // Search doctors
    $doctorStmt = $pdo->prepare("
        SELECT doc_id, doc_name, doc_specia, doc_img, fees, location, experience 
        FROM doctor 
        WHERE doc_name LIKE ? OR doc_specia LIKE ? 
        ORDER BY doc_name ASC 
        LIMIT 10
    ");
    $searchTerm = '%' . $query . '%';
    $doctorStmt->execute([$searchTerm, $searchTerm]);
    $doctors = $doctorStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search clinics
    $clinicStmt = $pdo->prepare("
        SELECT clinic_id, clinic_name, location, available_timing, contact_number 
        FROM clinics 
        WHERE clinic_name LIKE ? OR location LIKE ? 
        AND status = 'active'
        ORDER BY clinic_name ASC 
        LIMIT 10
    ");
    $clinicStmt->execute([$searchTerm, $searchTerm]);
    $clinics = $clinicStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'doctors' => $doctors,
        'clinics' => $clinics
    ]);
}

function getDoctorDetails($pdo) {
    $doctorId = $_GET['id'] ?? '';
    
    if (empty($doctorId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Doctor ID is required']);
        return;
    }
    
    // Get doctor basic details
    $stmt = $pdo->prepare("
        SELECT d.*, 
               GROUP_CONCAT(DISTINCT c.clinic_name SEPARATOR ', ') as clinics
        FROM doctor d
        LEFT JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id
        LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id AND c.status = 'active'
        WHERE d.doc_id = ?
        GROUP BY d.doc_id
    ");
    $stmt->execute([$doctorId]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctor) {
        http_response_code(404);
        echo json_encode(['error' => 'Doctor not found']);
        return;
    }
    
    // Get doctor's detailed schedule with proper JSON parsing
    $scheduleStmt = $pdo->prepare("
        SELECT c.clinic_name, c.location, c.clinic_id,
               dca.availability_schedule,
               dca.doctor_id,
               dca.clinic_id as assignment_clinic_id
        FROM doctor_clinic_assignments dca
        JOIN clinics c ON dca.clinic_id = c.clinic_id
        WHERE dca.doctor_id = ? AND c.status = 'active'
        ORDER BY c.clinic_name
    ");
    $scheduleStmt->execute([$doctorId]);
    $schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process and validate JSON schedules
    $processedSchedules = [];
    foreach ($schedules as $schedule) {
        $availabilityData = null;
        
        if (!empty($schedule['availability_schedule'])) {
            // Try to decode JSON
            $decoded = json_decode($schedule['availability_schedule'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $availabilityData = $decoded;
            } else {
                // Handle malformed JSON or non-JSON data
                $availabilityData = [
                    'error' => 'Invalid schedule format',
                    'raw_data' => $schedule['availability_schedule']
                ];
            }
        } else {
            $availabilityData = ['message' => 'No schedule available'];
        }
        
        $processedSchedules[] = [
            'clinic_name' => $schedule['clinic_name'],
            'location' => $schedule['location'],
            'clinic_id' => $schedule['clinic_id'],
            'availability_schedule' => $availabilityData,
            'raw_schedule' => $schedule['availability_schedule'] // For debugging
        ];
    }
    
    $doctor['schedules'] = $processedSchedules;
    $doctor['schedule_count'] = count($processedSchedules);
    
    echo json_encode($doctor);
}

function getClinicDetails($pdo) {
    $clinicId = $_GET['id'] ?? '';
    
    if (empty($clinicId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Clinic ID is required']);
        return;
    }
    
    // Get clinic details
    $stmt = $pdo->prepare("
        SELECT c.*,
               COUNT(DISTINCT dca.doctor_id) as doctor_count
        FROM clinics c
        LEFT JOIN doctor_clinic_assignments dca ON c.clinic_id = dca.clinic_id
        WHERE c.clinic_id = ? AND c.status = 'active'
        GROUP BY c.clinic_id
    ");
    $stmt->execute([$clinicId]);
    $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$clinic) {
        http_response_code(404);
        echo json_encode(['error' => 'Clinic not found']);
        return;
    }
    
    echo json_encode($clinic);
}

function getClinicDoctors($pdo) {
    $clinicId = $_GET['id'] ?? '';
    
    if (empty($clinicId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Clinic ID is required']);
        return;
    }
    
    // Get doctors assigned to this clinic with their schedules
    $stmt = $pdo->prepare("
        SELECT d.doc_id, d.doc_name, d.doc_specia, d.doc_img, d.fees, 
               d.location, d.experience, d.education, d.bio,
               dca.availability_schedule,
               c.clinic_name
        FROM doctor d
        JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id
        JOIN clinics c ON dca.clinic_id = c.clinic_id
        WHERE c.clinic_id = ? AND c.status = 'active'
        ORDER BY d.doc_name ASC
    ");
    $stmt->execute([$clinicId]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process JSON schedules for each doctor
    foreach ($doctors as &$doctor) {
        if (!empty($doctor['availability_schedule'])) {
            $decoded = json_decode($doctor['availability_schedule'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $doctor['availability_schedule'] = $decoded;
            } else {
                $doctor['availability_schedule'] = [
                    'error' => 'Invalid schedule format',
                    'raw_data' => $doctor['availability_schedule']
                ];
            }
        } else {
            $doctor['availability_schedule'] = ['message' => 'No schedule available'];
        }
        
        // Add raw schedule for debugging
        $doctor['raw_schedule'] = $doctor['availability_schedule'];
    }
    
    echo json_encode([
        'doctors' => $doctors,
        'clinic_id' => $clinicId
    ]);
}

// Debug function to check doctor data structure
function debugDoctorData($pdo) {
    $doctorId = $_GET['id'] ?? '';
    
    if (empty($doctorId)) {
        echo json_encode(['error' => 'Doctor ID required for debug']);
        return;
    }
    
    // Get all data for debugging
    $stmt = $pdo->prepare("
        SELECT d.*, 
               dca.availability_schedule,
               dca.doctor_id as assignment_doctor_id,
               dca.clinic_id as assignment_clinic_id,
               c.clinic_name,
               c.location as clinic_location,
               c.status as clinic_status
        FROM doctor d
        LEFT JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id
        LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id
        WHERE d.doc_id = ?
    ");
    $stmt->execute([$doctorId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also get table structure
    $structureStmt = $pdo->prepare("DESCRIBE doctor_clinic_assignments");
    $structureStmt->execute();
    $tableStructure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'doctor_data' => $results,
        'table_structure' => $tableStructure,
        'row_count' => count($results),
        'doctor_id_searched' => $doctorId
    ]);
}
?>