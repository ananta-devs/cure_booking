<?php
session_start();
include_once 'database_connection.php';

// Check if user is logged in
if (!isset($_SESSION['clinic_logged_in'])) {
    header("Location: http://localhost/cure_booking/login.php");
    exit();
}

/**
 * Database Helper Functions
 */
class AppointmentAPI {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Fetch all appointments with doctor and patient details
     */
    public function getAppointments($filters = []) {
        $query = "SELECT 
                    a.id, a.patient_name, a.patient_phone, a.patient_email,
                    a.appointment_date, a.appointment_time, a.status, a.gender,
                    d.doc_name, d.doc_specia as doctor_specialization, d.doc_id
                FROM appointments a 
                JOIN doctor d ON a.doctor_id = d.doc_id";
        
        $whereClause = [];
        $params = [];
        $types = "";
        
        // Apply filters if provided
        if (!empty($filters['date'])) {
            $whereClause[] = "a.appointment_date = ?";
            $params[] = $filters['date'];
            $types .= "s";
        }
        
        if (!empty($filters['status'])) {
            $whereClause[] = "a.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['doctor_id'])) {
            $whereClause[] = "a.doctor_id = ?";
            $params[] = $filters['doctor_id'];
            $types .= "i";
        }
        
        if (!empty($whereClause)) {
            $query .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $stmt = mysqli_prepare($this->conn, $query);
        
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $appointments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $appointments[] = $row;
        }
        
        return $appointments;
    }
    
    /**
     * Get single appointment by ID
     */
    public function getAppointmentById($id) {
        $query = "SELECT 
                    a.*, d.doc_name, d.doc_specia as doctor_specialization
                FROM appointments a 
                JOIN doctor d ON a.doctor_id = d.doc_id 
                WHERE a.id = ?";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_fetch_assoc($result);
    }
    
    /**
     * Create new appointment
     */
    public function createAppointment($data) {
        // Validate required fields
        $required = ['patient_name', 'patient_phone', 'patient_email', 'doctor_id', 'appointment_date', 'appointment_time', 'gender'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }
        
        // Check for existing appointment at the same time
        if ($this->isTimeSlotTaken($data['doctor_id'], $data['appointment_date'], $data['appointment_time'])) {
            return ['success' => false, 'message' => 'This time slot is already booked for the selected doctor'];
        }
        
        $query = "INSERT INTO appointments (patient_name, patient_phone, patient_email, doctor_id, appointment_date, appointment_time, gender, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "sssisss", 
            $data['patient_name'], 
            $data['patient_phone'], 
            $data['patient_email'], 
            $data['doctor_id'], 
            $data['appointment_date'], 
            $data['appointment_time'], 
            $data['gender']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $appointmentId = mysqli_insert_id($this->conn);
            return ['success' => true, 'message' => 'Appointment booked successfully!', 'appointment_id' => $appointmentId];
        } else {
            return ['success' => false, 'message' => 'Error booking appointment: ' . mysqli_error($this->conn)];
        }
    }
    
    /**
     * Update existing appointment
     */
    public function updateAppointment($id, $data) {
        // Validate required fields
        $required = ['patient_name', 'patient_phone', 'patient_email', 'doctor_id', 'appointment_date', 'appointment_time', 'gender'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }
        
        // Check if appointment exists
        if (!$this->getAppointmentById($id)) {
            return ['success' => false, 'message' => 'Appointment not found'];
        }
        
        // Check for time slot conflicts (excluding current appointment)
        if ($this->isTimeSlotTaken($data['doctor_id'], $data['appointment_date'], $data['appointment_time'], $id)) {
            return ['success' => false, 'message' => 'This time slot is already booked for the selected doctor'];
        }
        
        $query = "UPDATE appointments SET patient_name=?, patient_phone=?, patient_email=?, doctor_id=?, appointment_date=?, appointment_time=?, gender=?, updated_at=NOW() WHERE id=?";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "sssisssi", 
            $data['patient_name'], 
            $data['patient_phone'], 
            $data['patient_email'], 
            $data['doctor_id'], 
            $data['appointment_date'], 
            $data['appointment_time'], 
            $data['gender'], 
            $id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'Appointment updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Error updating appointment: ' . mysqli_error($this->conn)];
        }
    }
    
    /**
     * Update appointment status
     */
    public function updateAppointmentStatus($id, $status) {
        $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        if (!$this->getAppointmentById($id)) {
            return ['success' => false, 'message' => 'Appointment not found'];
        }
        
        $query = "UPDATE appointments SET status=?, updated_at=NOW() WHERE id=?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'Appointment status updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Error updating status: ' . mysqli_error($this->conn)];
        }
    }
    
    /**
     * Delete appointment
     */
    public function deleteAppointment($id) {
        if (!$this->getAppointmentById($id)) {
            return ['success' => false, 'message' => 'Appointment not found'];
        }
        
        $query = "DELETE FROM appointments WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'Appointment deleted successfully!'];
        } else {
            return ['success' => false, 'message' => 'Error deleting appointment: ' . mysqli_error($this->conn)];
        }
    }
    
    /**
     * Get all doctors
     */
    public function getDoctors($specialty = null) {
        $query = "SELECT * FROM doctor";
        $params = [];
        $types = "";
        
        if ($specialty) {
            $query .= " WHERE doc_specia = ?";
            $params[] = $specialty;
            $types = "s";
        }
        
        $query .= " ORDER BY doc_name";
        
        $stmt = mysqli_prepare($this->conn, $query);
        
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $doctors = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $doctors[] = $row;
        }
        
        return $doctors;
    }
    
    /**
     * Get all specialties
     */
    public function getSpecialties() {
        $query = "SELECT DISTINCT doc_specia FROM doctor WHERE doc_specia IS NOT NULL AND doc_specia != '' ORDER BY doc_specia";
        $result = mysqli_query($this->conn, $query);
        
        $specialties = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $specialties[] = $row['doc_specia'];
            }
        }
        
        return $specialties;
    }
    
    /**
     * Get available time slots for a doctor on a specific date
     */
    public function getAvailableTimeSlots($doctorId, $date) {
        // Define all possible time slots
        $allSlots = [
            '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '12:00', '12:30', '14:00', '14:30', '15:00', '15:30',
            '16:00', '16:30', '17:00', '17:30'
        ];
        
        // Get booked slots for this doctor on this date
        $query = "SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "is", $doctorId, $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $bookedSlots = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $bookedSlots[] = $row['appointment_time'];
        }
        
        // Return available slots
        return array_diff($allSlots, $bookedSlots);
    }
    
    /**
     * Check if a time slot is already taken
     */
    private function isTimeSlotTaken($doctorId, $date, $time, $excludeId = null) {
        $query = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
        $params = [$doctorId, $date, $time];
        $types = "iss";
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_num_rows($result) > 0;
    }
    
    /**
     * Get appointment statistics
     */
    public function getAppointmentStats($dateRange = null) {
        $baseQuery = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show
        FROM appointments";
        
        if ($dateRange) {
            $baseQuery .= " WHERE appointment_date >= ? AND appointment_date <= ?";
            $stmt = mysqli_prepare($this->conn, $baseQuery);
            mysqli_stmt_bind_param($stmt, "ss", $dateRange['start'], $dateRange['end']);
        } else {
            $stmt = mysqli_prepare($this->conn, $baseQuery);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_fetch_assoc($result);
    }
}

// Initialize API class
$api = new AppointmentAPI($conn);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'book_appointment':
        case 'create_appointment':
            $appointmentData = [
                'patient_name' => mysqli_real_escape_string($conn, $_POST['patient_name'] ?? ''),
                'patient_phone' => mysqli_real_escape_string($conn, $_POST['patient_phone'] ?? ''),
                'patient_email' => mysqli_real_escape_string($conn, $_POST['patient_email'] ?? ''),
                'doctor_id' => (int)($_POST['doctor_id'] ?? 0),
                'appointment_date' => $_POST['appointment_date'] ?? '',
                'appointment_time' => $_POST['appointment_time'] ?? '',
                'gender' => mysqli_real_escape_string($conn, $_POST['gender'] ?? '')
            ];
            
            $response = $api->createAppointment($appointmentData);
            break;
            
        case 'update_appointment':
            $id = (int)($_POST['id'] ?? 0);
            $appointmentData = [
                'patient_name' => mysqli_real_escape_string($conn, $_POST['patient_name'] ?? ''),
                'patient_phone' => mysqli_real_escape_string($conn, $_POST['patient_phone'] ?? ''),
                'patient_email' => mysqli_real_escape_string($conn, $_POST['patient_email'] ?? ''),
                'doctor_id' => (int)($_POST['doctor_id'] ?? 0),
                'appointment_date' => $_POST['appointment_date'] ?? '',
                'appointment_time' => $_POST['appointment_time'] ?? '',
                'gender' => mysqli_real_escape_string($conn, $_POST['gender'] ?? '')
            ];
            
            $response = $api->updateAppointment($id, $appointmentData);
            break;
            
        case 'update_status':
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            $response = $api->updateAppointmentStatus($id, $status);
            break;
            
        case 'delete_appointment':
            $id = (int)($_POST['id'] ?? 0);
            $response = $api->deleteAppointment($id);
            break;
            
        case 'get_appointment':
            $id = (int)($_POST['id'] ?? 0);
            $appointment = $api->getAppointmentById($id);
            
            if ($appointment) {
                $response = ['success' => true, 'data' => $appointment];
            } else {
                $response = ['success' => false, 'message' => 'Appointment not found'];
            }
            break;
            
        case 'get_appointments':
            $filters = [];
            if (!empty($_POST['date'])) $filters['date'] = $_POST['date'];
            if (!empty($_POST['status'])) $filters['status'] = $_POST['status'];
            if (!empty($_POST['doctor_id'])) $filters['doctor_id'] = (int)$_POST['doctor_id'];
            
            $appointments = $api->getAppointments($filters);
            $response = ['success' => true, 'data' => $appointments];
            break;
            
        case 'get_doctors':
        case 'get_doctors_by_specialty':
            $specialty = !empty($_POST['specialty']) ? mysqli_real_escape_string($conn, $_POST['specialty']) : null;
            $doctors = $api->getDoctors($specialty);
            $response = ['success' => true, 'data' => $doctors];
            break;
            
        case 'get_specialties':
            $specialties = $api->getSpecialties();
            $response = ['success' => true, 'data' => $specialties];
            break;
            
        case 'get_available_slots':
            $doctorId = (int)($_POST['doctor_id'] ?? 0);
            $date = $_POST['date'] ?? '';
            
            if ($doctorId && $date) {
                $availableSlots = $api->getAvailableTimeSlots($doctorId, $date);
                $response = ['success' => true, 'data' => $availableSlots];
            } else {
                $response = ['success' => false, 'message' => 'Doctor ID and date are required'];
            }
            break;
            
        case 'get_stats':
            $dateRange = null;
            if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
                $dateRange = [
                    'start' => $_POST['start_date'],
                    'end' => $_POST['end_date']
                ];
            }
            
            $stats = $api->getAppointmentStats($dateRange);
            $response = ['success' => true, 'data' => $stats];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
            break;
    }
    
    echo json_encode($response);
    exit();
}

// Handle GET requests for data fetching (for page loads)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'get_appointments':
            $filters = [];
            if (!empty($_GET['date'])) $filters['date'] = $_GET['date'];
            if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
            if (!empty($_GET['doctor_id'])) $filters['doctor_id'] = (int)$_GET['doctor_id'];
            
            $appointments = $api->getAppointments($filters);
            $response = ['success' => true, 'data' => $appointments];
            break;
            
        case 'get_doctors':
            $specialty = !empty($_GET['specialty']) ? mysqli_real_escape_string($conn, $_GET['specialty']) : null;
            $doctors = $api->getDoctors($specialty);
            $response = ['success' => true, 'data' => $doctors];
            break;
            
        case 'get_specialties':
            $specialties = $api->getSpecialties();
            $response = ['success' => true, 'data' => $specialties];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
            break;
    }
    
    echo json_encode($response);
    exit();
}

// Fetch data for page load (backward compatibility)
$appointments = $api->getAppointments();
$doctors = $api->getDoctors();
$specialties = $api->getSpecialties();

// Helper functions for backward compatibility
function getAppointments($conn) {
    global $api;
    return $api->getAppointments();
}

function getDoctors($conn) {
    global $api;
    return $api->getDoctors();
}

function getSpecialties($conn) {
    global $api;
    return $api->getSpecialties();
}
?>