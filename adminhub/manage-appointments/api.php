<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$dbname = 'cure_booking';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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
    case 'get_clinics':
        fetchClinics($pdo);
        break;
    case 'get_doctor_clinics':
        fetchDoctorClinics($pdo, $_GET['doctor_id'] ?? '');
        break;
    case 'get_available_slots':
        fetchAvailableSlots($pdo, $_GET['doctor_id'] ?? '', $_GET['clinic_id'] ?? '', $_GET['date'] ?? '');
        break;
    case 'save_appointment':
        saveAppointment($pdo);
        break;
    case 'get_doctor_clinic_schedule':
        fetchDoctorClinicSchedule($pdo, $_GET['doctor_id'] ?? '', $_GET['clinic_id'] ?? '');
        break;
    default:
        sendResponse('error', 'Invalid action.');
}

function fetchSpecialities($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT doc_specia FROM doctor WHERE doc_specia IS NOT NULL AND doc_specia != '' ORDER BY doc_specia");
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
            $stmt = $pdo->prepare("SELECT doc_id as id, doc_name, doc_specia, fees FROM doctor WHERE doc_specia = :speciality ORDER BY doc_name");
            $stmt->bindParam(':speciality', $speciality);
        } else {
            $stmt = $pdo->prepare("SELECT doc_id as id, doc_name, doc_specia, fees FROM doctor ORDER BY doc_name");
        }
        
        $stmt->execute();
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse('success', 'Doctors fetched successfully', $doctors);
    } catch (PDOException $e) {
        sendResponse('error', 'Failed to fetch doctors: ' . $e->getMessage());
    }
}

function fetchClinics($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT clinic_id, clinic_name, location FROM clinics WHERE status = 'active' ORDER BY clinic_name");
        $stmt->execute();
        $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse('success', 'Clinics fetched successfully', $clinics);
    } catch (PDOException $e) {
        sendResponse('error', 'Failed to fetch clinics: ' . $e->getMessage());
    }
}

function fetchDoctorClinics($pdo, $doctorId) {
    try {
        if (empty($doctorId)) {
            sendResponse('error', 'Doctor ID is required');
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT c.clinic_id, c.clinic_name, c.location 
            FROM clinics c 
            INNER JOIN doctor_clinic_assignments dca ON c.clinic_id = dca.clinic_id 
            WHERE dca.doctor_id = :doctor_id AND c.status = 'active'
            ORDER BY c.clinic_name
        ");
        $stmt->bindParam(':doctor_id', $doctorId);
        $stmt->execute();
        $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse('success', 'Doctor clinics fetched successfully', $clinics);
    } catch (PDOException $e) {
        sendResponse('error', 'Failed to fetch doctor clinics: ' . $e->getMessage());
    }
}

function fetchAvailableSlots($pdo, $doctorId, $clinicId, $date) {
    try {
        if (empty($doctorId) || empty($date)) {
            sendResponse('error', 'Doctor ID and date are required');
            return;
        }
        
        // Get day of week (lowercase)
        $dayOfWeek = strtolower(date('l', strtotime($date)));
        
        // If clinic is selected, get availability for that specific clinic
        if (!empty($clinicId)) {
            $stmt = $pdo->prepare("
                SELECT availability_schedule 
                FROM doctor_clinic_assignments 
                WHERE doctor_id = :doctor_id AND clinic_id = :clinic_id
            ");
            $stmt->bindParam(':doctor_id', $doctorId);
            $stmt->bindParam(':clinic_id', $clinicId);
        } else {
            // If no clinic selected, get all available slots for this doctor across all clinics
            $stmt = $pdo->prepare("
                SELECT availability_schedule 
                FROM doctor_clinic_assignments 
                WHERE doctor_id = :doctor_id
            ");
            $stmt->bindParam(':doctor_id', $doctorId);
        }
        
        $stmt->execute();
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $availableSlots = [];
        $allTimeSlots = [
            '11:00-13:00' => '11:00-01:00 PM',
            '14:00-16:00' => '02:00-04:00 PM',
            '17:00-19:00' => '05:00-07:00 PM'
        ];
        
        if (!empty($schedules)) {
            foreach ($schedules as $schedule) {
                $availabilityData = json_decode($schedule['availability_schedule'], true);
                
                if (isset($availabilityData[$dayOfWeek])) {
                    $daySchedule = $availabilityData[$dayOfWeek];
                    
                    foreach ($daySchedule as $timeSlot => $isAvailable) {
                        if ($isAvailable && isset($allTimeSlots[$timeSlot])) {
                            $availableSlots[$timeSlot] = $allTimeSlots[$timeSlot];
                        }
                    }
                }
            }
        }
        
        // Check for existing appointments on the selected date
        if (!empty($availableSlots)) {
            $appointmentTimeSlots = [];
            
            if (!empty($clinicId)) {
                $stmt = $pdo->prepare("
                    SELECT appointment_time 
                    FROM appointments 
                    WHERE doctor_id = :doctor_id 
                    AND clinic_id = :clinic_id 
                    AND appointment_date = :date 
                    AND status NOT IN ('cancelled')
                ");
                $stmt->bindParam(':doctor_id', $doctorId);
                $stmt->bindParam(':clinic_id', $clinicId);
                $stmt->bindParam(':date', $date);
            } else {
                $stmt = $pdo->prepare("
                    SELECT appointment_time 
                    FROM appointments 
                    WHERE doctor_id = :doctor_id 
                    AND appointment_date = :date 
                    AND status NOT IN ('cancelled')
                ");
                $stmt->bindParam(':doctor_id', $doctorId);
                $stmt->bindParam(':date', $date);
            }
            
            $stmt->execute();
            $existingAppointments = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Remove booked time slots
            foreach ($existingAppointments as $bookedTime) {
                // Convert TIME format to slot format (e.g., "11:00:00" to "11:00-13:00")
                $timeStr = substr($bookedTime, 0, 5); // Get HH:MM part
                foreach ($allTimeSlots as $slotKey => $slotLabel) {
                    $slotStart = substr($slotKey, 0, 5);
                    if ($timeStr === $slotStart) {
                        if (isset($availableSlots[$slotKey])) {
                            unset($availableSlots[$slotKey]);
                        }
                        break;
                    }
                }
            }
        }
        
        // Convert to array format expected by frontend
        $slotsArray = [];
        foreach ($availableSlots as $value => $label) {
            $slotsArray[] = ['value' => $value, 'label' => $label];
        }
        
        sendResponse('success', 'Available slots fetched successfully', $slotsArray);
        
    } catch (PDOException $e) {
        sendResponse('error', 'Failed to fetch available slots: ' . $e->getMessage());
    } catch (Exception $e) {
        sendResponse('error', 'An error occurred: ' . $e->getMessage());
    }
}

function saveAppointment($pdo) {
    // Log the received data for debugging
    error_log("Received POST data: " . print_r($_POST, true));
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Invalid request method.');
        return;
    }
    
    try {
        // Validate required fields
        $requiredFields = ['firstName', 'gender', 'phone', 'email', 'specialityType', 'doctor', 'preferredDate', 'time'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            sendResponse('error', 'Missing required fields: ' . implode(', ', $missingFields));
            return;
        }
        
        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            sendResponse('error', 'Please enter a valid email address.');
            return;
        }
        
        // Validate date
        if ($_POST['preferredDate'] < date('Y-m-d')) {
            sendResponse('error', 'Please select a future date.');
            return;
        }
        
        // Extract and validate data
        $doctorId = intval($_POST['doctor']);
        $clinicId = !empty($_POST['clinic']) ? intval($_POST['clinic']) : null;
        $appointmentDate = $_POST['preferredDate'];
        $appointmentTime = $_POST['time'];
        
        // Convert time slot to actual time (use start time of the slot)
        $timeSlotMapping = [
            '11:00-13:00' => '11:00:00',
            '14:00-16:00' => '14:00:00',
            '17:00-19:00' => '17:00:00'
        ];
        
        if (!isset($timeSlotMapping[$appointmentTime])) {
            sendResponse('error', 'Invalid time slot selected: ' . $appointmentTime);
            return;
        }
        
        $actualTime = $timeSlotMapping[$appointmentTime];
        
        // Verify doctor exists
        $stmt = $pdo->prepare("SELECT doc_id, doc_name, doc_specia FROM doctor WHERE doc_id = :id");
        $stmt->execute([':id' => $doctorId]);
        $doctor = $stmt->fetch();
        
        if (!$doctor) {
            sendResponse('error', "Selected doctor does not exist. Doctor ID: $doctorId");
            return;
        }
        
        // Get clinic info if selected
        $clinic = null;
        if ($clinicId) {
            $stmt = $pdo->prepare("SELECT clinic_id, clinic_name FROM clinics WHERE clinic_id = :id AND status = 'active'");
            $stmt->execute([':id' => $clinicId]);
            $clinic = $stmt->fetch();
            
            if (!$clinic) {
                sendResponse('error', "Selected clinic does not exist or is not active. Clinic ID: $clinicId");
                return;
            }
            
            // Verify doctor-clinic assignment
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM doctor_clinic_assignments WHERE doctor_id = :doctor_id AND clinic_id = :clinic_id");
            $stmt->execute([':doctor_id' => $doctorId, ':clinic_id' => $clinicId]);
            $assignmentExists = $stmt->fetchColumn();
            
            if ($assignmentExists == 0) {
                sendResponse('error', 'Selected doctor is not assigned to the selected clinic.');
                return;
            }
        }
        
        // Modified conflict check - remove the unique constraint check that was causing issues
        $conflictQuery = "SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND appointment_date = :date AND appointment_time = :time AND status NOT IN ('cancelled')";
        $conflictParams = [':doctor_id' => $doctorId, ':date' => $appointmentDate, ':time' => $actualTime];
        
        if ($clinicId) {
            $conflictQuery .= " AND clinic_id = :clinic_id";
            $conflictParams[':clinic_id'] = $clinicId;
        }
        
        $stmt = $pdo->prepare($conflictQuery);
        $stmt->execute($conflictParams);
        $existingCount = $stmt->fetchColumn();
        
        if ($existingCount > 0) {
            sendResponse('error', 'This time slot is already booked. Please select another time.');
            return;
        }
        
        // Start session to get admin info
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $bookedByEmail = $_SESSION['adm_email'] ?? 'admin@system.com';
        $bookedByName = $_SESSION['adm_name'] ?? 'System Admin';
        
        // Insert appointment
        $sql = "INSERT INTO appointments (
            doctor_id, 
            doctor_name, 
            doctor_specialization, 
            clinic_id, 
            clinic_name, 
            patient_name, 
            patient_phone, 
            patient_email, 
            gender,
            appointment_date, 
            appointment_time, 
            booked_by_email, 
            booked_by_name, 
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $doctorId,
            $doctor['doc_name'],
            $doctor['doc_specia'],
            $clinicId,
            $clinic ? $clinic['clinic_name'] : null,
            trim($_POST['firstName']),
            trim($_POST['phone']),
            trim($_POST['email']),
            $_POST['gender'],
            $appointmentDate,
            $actualTime,
            $bookedByEmail,
            $bookedByName,
            'pending'
        ];
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $appointmentId = $pdo->lastInsertId();
            
            // Prepare success message
            $clinicText = $clinic ? " at " . $clinic['clinic_name'] : "";
            $timeText = date('g:i A', strtotime($actualTime));
            $dateText = date('F j, Y', strtotime($appointmentDate));
            
            $message = "Appointment booked successfully! " .
                      "ID: $appointmentId | " .
                      "Dr. {$doctor['doc_name']} ({$doctor['doc_specia']}) " .
                      "on $dateText at $timeText$clinicText";
            
            sendResponse('success', $message);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("SQL Error: " . print_r($errorInfo, true));
            sendResponse('error', 'Failed to book appointment. SQL Error: ' . implode(' ', $errorInfo));
        }
        
    } catch (PDOException $e) {
        error_log("Database error in saveAppointment: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        
        // Handle specific database errors
        if ($e->getCode() == '23000') {
            if (strpos($e->getMessage(), 'unique_appointment_slot') !== false) {
                sendResponse('error', 'This time slot is already booked. Please select another time.');
            } elseif (strpos($e->getMessage(), 'fk_appointments_doctor') !== false) {
                sendResponse('error', 'Selected doctor is not valid. Please refresh and try again.');
            } elseif (strpos($e->getMessage(), 'fk_appointments_clinic') !== false) {
                sendResponse('error', 'Selected clinic is not valid. Please refresh and try again.');
            } else {
                sendResponse('error', 'Database constraint error: ' . $e->getMessage());
            }
        } else {
            sendResponse('error', 'Database error: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("General error in saveAppointment: " . $e->getMessage());
        sendResponse('error', 'An unexpected error occurred: ' . $e->getMessage());
    }
}

function fetchDoctorClinicSchedule($pdo, $doctorId, $clinicId) {
    try {
        if (empty($doctorId) || empty($clinicId)) {
            sendResponse('error', 'Doctor ID and Clinic ID are required');
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT availability_schedule 
            FROM doctor_clinic_assignments 
            WHERE doctor_id = :doctor_id AND clinic_id = :clinic_id
        ");
        $stmt->execute([':doctor_id' => $doctorId, ':clinic_id' => $clinicId]);
        
        $schedule = $stmt->fetch();
        
        if ($schedule) {
            sendResponse('success', 'Doctor-clinic schedule fetched successfully', $schedule);
        } else {
            sendResponse('error', 'No schedule found for this doctor-clinic combination');
        }
        
    } catch (PDOException $e) {
        sendResponse('error', 'Failed to fetch doctor-clinic schedule: ' . $e->getMessage());
    }
}

function sendResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    $response = [
        'status' => $status, 
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}
?>