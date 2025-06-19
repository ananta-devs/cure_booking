<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_doctors':
        getDoctors($conn);
        break;
    case 'get_time_slots':
        getTimeSlots($conn);
        break;
    case 'book_appointment':
        bookAppointment($conn);
        break;
    case 'get_doctor_availability':
        getDoctorAvailability($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();

function getDoctors($conn) {
    $specialization = $_GET['specialization'] ?? '';

    $sql = "SELECT d.*, 
                   GROUP_CONCAT(DISTINCT c.clinic_name SEPARATOR ', ') as clinic_names,
                   GROUP_CONCAT(DISTINCT c.location SEPARATOR ', ') as clinic_locations,
                   GROUP_CONCAT(DISTINCT CONCAT(c.clinic_id, '|', c.clinic_name, '|', c.location) SEPARATOR '||') as clinic_details
            FROM doctor d 
            LEFT JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
            LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id 
            WHERE 1=1";

    if (!empty($specialization)) {
        $sql .= " AND d.doc_specia = '" . $conn->real_escape_string($specialization) . "'";
    }

    $sql .= " GROUP BY d.doc_id";
    $result = $conn->query($sql);

    if ($result) {
        $doctors = [];
        
        while($row = $result->fetch_assoc()) {
            $doctors[] = [
                'id' => $row['doc_id'],
                'name' => $row['doc_name'],
                'specialty' => $row['doc_specia'],
                'specialization' => strtolower($row['doc_specia']),
                'email' => $row['doc_email'],
                'fees' => $row['fees'],
                'experience' => $row['experience'] ?? 5,
                'gender' => $row['gender'] ?? 'male',
                'location' => $row['location'] ?? 'Medical Center',
                'education' => $row['education'] ?? 'MD',
                'bio' => $row['bio'] ?? 'Experienced doctor specializing in ' . $row['doc_specia'],
                'availability' => json_decode($row['availability'] ?? '["Monday", "Wednesday", "Friday"]'),
                'clinic_names' => $row['clinic_names'] ?? '',
                'clinic_locations' => $row['clinic_locations'] ?? '',
                'clinic_details' => $row['clinic_details'] ?? ''
            ];
        }
        
        echo json_encode($doctors);
    } else {
        echo json_encode(["error" => "Error: " . $conn->error]);
    }
}

function getTimeSlots($conn) {
    $doctor_id = intval($_GET['doctor_id'] ?? 0);
    $clinic_name = $conn->real_escape_string($_GET['clinic_name'] ?? '');
    $selected_date = $conn->real_escape_string($_GET['date'] ?? '');
    
    if (empty($doctor_id) || empty($clinic_name) || empty($selected_date)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        return;
    }
    
    $day_of_week = strtolower(date('l', strtotime($selected_date)));
    
    $sql = "SELECT c.clinic_id, dca.availability_schedule 
            FROM clinics c 
            JOIN doctor_clinic_assignments dca ON c.clinic_id = dca.clinic_id 
            WHERE c.clinic_name = ? AND dca.doctor_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $clinic_name, $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Doctor not available at this clinic']);
        return;
    }
    
    $row = $result->fetch_assoc();
    $availability_schedule = json_decode($row['availability_schedule'], true);
    
    if (!isset($availability_schedule[$day_of_week])) {
        echo json_encode(['success' => false, 'message' => 'Doctor not available on this day']);
        return;
    }
    
    $available_slots = [];
    foreach ($availability_schedule[$day_of_week] as $time_slot => $is_available) {
        if ($is_available) {
            $available_slots[] = $time_slot;
        }
    }
    
    if (empty($available_slots)) {
        echo json_encode(['success' => false, 'message' => 'No available time slots for this day']);
        return;
    }
    
    $booked_slots_sql = "SELECT appointment_time 
                        FROM appointments 
                        WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
                        
    $booked_stmt = $conn->prepare($booked_slots_sql);
    $booked_stmt->bind_param("is", $doctor_id, $selected_date);
    $booked_stmt->execute();
    $booked_result = $booked_stmt->get_result();
    
    $booked_times = [];
    while ($booked_row = $booked_result->fetch_assoc()) {
        $booked_times[] = substr($booked_row['appointment_time'], 0, 5);
    }
    
    $final_available_slots = [];
    foreach ($available_slots as $slot) {
        $slot_start_time = explode('-', $slot)[0];
        if (!in_array($slot_start_time, $booked_times)) {
            $final_available_slots[] = [
                'value' => $slot_start_time,
                'label' => $slot,
                'available' => true
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'time_slots' => $final_available_slots,
        'day' => $day_of_week
    ]);
}

function bookAppointment($conn) {
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }

    $required_fields = ['doctor_id', 'name', 'phone', 'email', 'date', 'time', 'clinic'];
    $data = [];
    
    foreach ($required_fields as $field) {
        $data[$field] = trim($_POST[$field] ?? '');
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
            return;
        }
    }
    
    $data['doctor_id'] = intval($data['doctor_id']);
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        return;
    }

    if (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
        return;
    }

    // Validate appointment date
    $appointment_date = new DateTime($data['date']);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($appointment_date < $today) {
        echo json_encode(['success' => false, 'message' => 'Appointment date cannot be in the past']);
        return;
    }

    $max_date = new DateTime();
    $max_date->add(new DateInterval('P3M'));
    
    if ($appointment_date > $max_date) {
        echo json_encode(['success' => false, 'message' => 'Appointments can only be booked up to 3 months in advance']);
        return;
    }

    // Get user session data
    $session_keys = [
        'user_id' => ['user_id', 'id', 'userId'],
        'user_email' => ['user_email', 'email', 'userEmail'],
        'user_name' => ['user_name', 'name', 'username', 'userName']
    ];

    $user_data = [];
    foreach ($session_keys as $key => $possible_keys) {
        foreach ($possible_keys as $session_key) {
            if (isset($_SESSION[$session_key]) && !empty($_SESSION[$session_key])) {
                $user_data[$key] = $_SESSION[$session_key];
                break;
            }
        }
    }
    
    $booked_by_user_id = intval($user_data['user_id'] ?? 0);
    
    if ($booked_by_user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Unable to identify user. Please login again.', 'redirect_to_login' => true]);
        return;
    }

    // Get doctor info
    $doctor_query = "SELECT doc_name, doc_specia FROM doctor WHERE doc_id = ?";
    $stmt_doctor = $conn->prepare($doctor_query);
    $stmt_doctor->bind_param("i", $data['doctor_id']);
    $stmt_doctor->execute();
    $doctor_result = $stmt_doctor->get_result();
    
    if ($doctor_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        return;
    }
    
    $doctor_data = $doctor_result->fetch_assoc();

    // Get clinic info
    $clinic_id = null;
    $clinic_query = "SELECT clinic_id FROM clinics WHERE clinic_name = ?";
    $stmt_clinic = $conn->prepare($clinic_query);
    $stmt_clinic->bind_param("s", $data['clinic']);
    $stmt_clinic->execute();
    $clinic_result = $stmt_clinic->get_result();
    
    if ($clinic_result->num_rows > 0) {
        $clinic_data = $clinic_result->fetch_assoc();
        $clinic_id = $clinic_data['clinic_id'];
    }

    // Check for duplicate bookings
    $duplicate_check = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
    $stmt_duplicate = $conn->prepare($duplicate_check);
    $time_with_seconds = $data['time'] . ':00';
    $stmt_duplicate->bind_param("iss", $data['doctor_id'], $data['date'], $time_with_seconds);
    $stmt_duplicate->execute();
    
    if ($stmt_duplicate->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please choose a different time.']);
        return;
    }

    // Rate limiting check
    $rate_limit_check = "SELECT COUNT(*) as booking_count FROM appointments WHERE booked_by_user_id = ? AND booking_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt_rate = $conn->prepare($rate_limit_check);
    $stmt_rate->bind_param("i", $booked_by_user_id);
    $stmt_rate->execute();
    $rate_data = $stmt_rate->get_result()->fetch_assoc();
    
    if ($rate_data['booking_count'] >= 3) {
        echo json_encode(['success' => false, 'message' => 'Too many bookings in the last hour. Please wait before booking again.']);
        return;
    }

    // Check existing appointment
    $existing_appointment_check = "SELECT id FROM appointments 
                                 WHERE doctor_id = ? 
                                 AND appointment_date = ? 
                                 AND patient_email = ? 
                                 AND status != 'cancelled'";
    $stmt_existing = $conn->prepare($existing_appointment_check);
    $stmt_existing->bind_param("iss", $data['doctor_id'], $data['date'], $data['email']);
    $stmt_existing->execute();
    
    if ($stmt_existing->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have an appointment with this doctor on the selected date.']);
        return;
    }

    // Check table structure
    $table_check = "SHOW COLUMNS FROM appointments";
    $table_result = $conn->query($table_check);
    
    $columns = [];
    while ($row = $table_result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    // Build dynamic INSERT query
    $insert_columns = [
        'doctor_id' => $data['doctor_id'],
        'patient_name' => $data['name'],
        'patient_phone' => $data['phone'],
        'patient_email' => $data['email'],
        'appointment_date' => $data['date'],
        'appointment_time' => $time_with_seconds,
        'booking_date' => date('Y-m-d H:i:s'),
        'status' => 'confirmed'
    ];

    // Add optional columns if they exist
    if (in_array('doctor_name', $columns)) {
        $insert_columns['doctor_name'] = $doctor_data['doc_name'];
    }
    if (in_array('doctor_specialization', $columns)) {
        $insert_columns['doctor_specialization'] = $doctor_data['doc_specia'];
    }
    if (in_array('clinic_id', $columns) && $clinic_id) {
        $insert_columns['clinic_id'] = $clinic_id;
    }
    if (in_array('clinic_name', $columns)) {
        $insert_columns['clinic_name'] = $data['clinic'];
    }
    if (in_array('booked_by_user_id', $columns)) {
        $insert_columns['booked_by_user_id'] = $booked_by_user_id;
    }
    if (in_array('booked_by_email', $columns)) {
        $insert_columns['booked_by_email'] = $user_data['user_email'] ?? '';
    }
    if (in_array('booked_by_name', $columns)) {
        $insert_columns['booked_by_name'] = $user_data['user_name'] ?? '';
    }

    // Build SQL query
    $column_names = array_keys($insert_columns);
    $placeholders = str_repeat('?,', count($column_names) - 1) . '?';
    $sql = "INSERT INTO appointments (" . implode(', ', $column_names) . ") VALUES (" . $placeholders . ")";
    
    $stmt = $conn->prepare($sql);
    
    // Create type string for bind_param
    $types = '';
    $values = [];
    foreach ($insert_columns as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } else {
            $types .= 's';
        }
        $values[] = $value;
    }
    
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        $appointment_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Appointment booked successfully!',
            'appointment_details' => [
                'appointment_id' => $appointment_id,
                'patient_name' => $data['name'],
                'patient_email' => $data['email'],
                'patient_phone' => $data['phone'],
                'doctor_name' => $doctor_data['doc_name'],
                'doctor_specialization' => $doctor_data['doc_specia'],
                'clinic_name' => $data['clinic'],
                'appointment_date' => date('M d, Y', strtotime($data['date'])),
                'appointment_time' => date('h:i A', strtotime($data['time'])),
                'booked_by' => $user_data['user_name'] ?? '',
                'booking_date' => date('M d, Y h:i A'),
                'status' => 'pending'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred. Please try again.'
        ]);
    }
}

function getDoctorAvailability($conn) {
    $doctor_id = intval($_GET['doctor_id'] ?? 0);
    
    if ($doctor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid doctor ID']);
        return;
    }
    
    // Get doctor's availability schedule from all clinics
    $sql = "SELECT c.clinic_name, c.location, dca.availability_schedule 
            FROM doctor_clinic_assignments dca 
            JOIN clinics c ON dca.clinic_id = c.clinic_id 
            WHERE dca.doctor_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $availability_data = [];
    
    while ($row = $result->fetch_assoc()) {
        $clinic_name = $row['clinic_name'];
        $availability_schedule = json_decode($row['availability_schedule'], true);
        
        if ($availability_schedule) {
            $availability_data[$clinic_name] = $availability_schedule;
        }
    }
    
    // If no clinic-specific availability found, try general doctor availability
    if (empty($availability_data)) {
        $general_sql = "SELECT availability FROM doctor WHERE doc_id = ?";
        $general_stmt = $conn->prepare($general_sql);
        $general_stmt->bind_param("i", $doctor_id);
        $general_stmt->execute();
        $general_result = $general_stmt->get_result();
        
        if ($general_result->num_rows > 0) {
            $general_data = $general_result->fetch_assoc();
            $general_availability = json_decode($general_data['availability'], true);
            
            if ($general_availability) {
                $availability_data['general'] = $general_availability;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'availability' => $availability_data,
        'doctor_id' => $doctor_id
    ]);
}
?>