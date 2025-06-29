<?php

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    $conn = new mysqli("localhost", "root", "", "cure_booking");
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
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
        case 'check_daily_bookings':
            checkDailyBookings($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

function getDoctors($conn) {
    try {
        $specialization = $_GET['specialization'] ?? '';
        
        $sql = "SELECT d.*, 
                    GROUP_CONCAT(DISTINCT c.clinic_name SEPARATOR ', ') as clinic_names,
                    GROUP_CONCAT(DISTINCT c.location SEPARATOR ', ') as clinic_locations,
                    GROUP_CONCAT(DISTINCT CONCAT(c.clinic_id, '|', c.clinic_name, '|', c.location) SEPARATOR '||') as clinic_details
                FROM doctor d 
                LEFT JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
                LEFT JOIN clinics c ON dca.clinic_id = c.clinic_id";

        if (!empty($specialization)) {
            $sql .= " WHERE d.doc_specia = ?";
            $stmt = $conn->prepare($sql . " GROUP BY d.doc_id");
            $stmt->bind_param("s", $specialization);
        } else {
            $stmt = $conn->prepare($sql . " GROUP BY d.doc_id");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $doctors = [];
        
        while($row = $result->fetch_assoc()) {
            $doctors[] = [
                'id' => $row['doc_id'],
                'name' => $row['doc_name'],
                'doc_img' => $row['doc_img'],
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
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch doctors']);
    }
}

function getTimeSlots($conn) {
    try {
        $doctor_id = intval($_GET['doctor_id'] ?? 0);
        $clinic_name = $_GET['clinic_name'] ?? '';
        $selected_date = $_GET['date'] ?? '';
        
        if (!$doctor_id || !$clinic_name || !$selected_date) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
        
        $day_of_week = strtolower(date('l', strtotime($selected_date)));
        
        $stmt = $conn->prepare("SELECT dca.availability_schedule 
                            FROM clinics c 
                            JOIN doctor_clinic_assignments dca ON c.clinic_id = dca.clinic_id 
                            WHERE c.clinic_name = ? AND dca.doctor_id = ?");
        
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
        
        $available_slots = array_keys(array_filter($availability_schedule[$day_of_week]));
        
        if (empty($available_slots)) {
            echo json_encode(['success' => false, 'message' => 'No available time slots for this day']);
            return;
        }
        
        // Check availability for each slot
        $final_slots = [];
        foreach ($available_slots as $slot) {
            $slot_parts = explode('-', $slot);
            $slot_start = trim($slot_parts[0]);
            
            // Check if this slot has availability
            $has_availability = checkSlotAvailability($conn, $doctor_id, $selected_date, $slot_start, $clinic_name);
            
            $final_slots[] = [
                'value' => $slot_start,
                'label' => $slot,
                'available' => $has_availability
            ];
        }
        
        echo json_encode([
            'success' => true,
            'time_slots' => $final_slots,
            'day' => $day_of_week
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get time slots']);
    }
}


function bookAppointment($conn) {
    try {
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $required = ['doctor_id', 'name', 'phone', 'email', 'date', 'time', 'clinic'];
        $data = [];
        
        foreach ($required as $field) {
            $data[$field] = trim($_POST[$field] ?? '');
            if (empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
                return;
            }
        }
        
        $data['doctor_id'] = intval($data['doctor_id']);
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            return;
        }

        if (!preg_match('/^[0-9]{10,15}$/', $data['phone'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
            return;
        }

        $appointment_date = new DateTime($data['date']);
        $today = new DateTime();
        $max_date = (new DateTime())->add(new DateInterval('P3M'));
        
        if ($appointment_date < $today || $appointment_date > $max_date) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment date']);
            return;
        }

        $user_email = $_SESSION['email'] ?? $_SESSION['user_email'] ?? $data['email'];
        $user_name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? $data['name'];

        // Get doctor and clinic information
        $info_stmt = $conn->prepare("SELECT d.doc_name, d.doc_specia, c.clinic_id 
                                    FROM doctor d 
                                    LEFT JOIN clinics c ON c.clinic_name = ? 
                                    WHERE d.doc_id = ?");
        
        $info_stmt->bind_param("si", $data['clinic'], $data['doctor_id']);
        $info_stmt->execute();
        $info_result = $info_stmt->get_result();
        
        if ($info_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
            return;
        }
        
        $info = $info_result->fetch_assoc();

        // Determine the actual booking time based on slot availability
        $final_time = determineBookingTime($conn, $data['doctor_id'], $data['date'], $data['time'], $data['clinic']);
        
        if ($final_time === false) {
            echo json_encode(['success' => false, 'message' => 'All slots are full for this time period']);
            return;
        }

        $gender = 'other';

        // Insert the appointment with the determined time
        $insert_stmt = $conn->prepare("INSERT INTO appointments 
                                    (doctor_id, doctor_name, doctor_specialization, clinic_id, clinic_name,
                                    patient_name, patient_phone, patient_email, gender, appointment_date, appointment_time,
                                    booked_by_email, booked_by_name, booking_date, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')");
        
        $insert_stmt->bind_param("ississsssssss", 
            $data['doctor_id'], 
            $info['doc_name'], 
            $info['doc_specia'], 
            $info['clinic_id'], 
            $data['clinic'],
            $data['name'], 
            $data['phone'], 
            $data['email'], 
            $gender,
            $data['date'], 
            $final_time,
            $user_email,
            $user_name
        );
        
        $insert_stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked successfully!',
            'appointment_details' => [
                'appointment_id' => $conn->insert_id,
                'patient_name' => $data['name'],
                'doctor_name' => $info['doc_name'],
                'appointment_date' => $data['date'],
                'appointment_time' => date('h:i A', strtotime($final_time)),
                'clinic_name' => $data['clinic']
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Booking failed. Please try again.']);
    }
}

function determineBookingTime($conn, $doctor_id, $date, $selected_time_slot, $clinic_name) {
    // Define time slot ranges
    $time_slots = [
        '11:00' => ['start' => '11:00:00', 'end' => '13:00:00'],
        '14:00' => ['start' => '14:00:00', 'end' => '16:00:00'],
        '17:00' => ['start' => '17:00:00', 'end' => '19:00:00']
    ];
    
    // Determine which slot the user selected
    $slot_info = null;
    foreach ($time_slots as $slot_start => $slot_range) {
        if ($selected_time_slot === $slot_start) {
            $slot_info = $slot_range;
            break;
        }
    }
    
    if (!$slot_info) {
        return false; // Invalid time slot
    }
    
    // Get all existing appointments for this doctor, date, and clinic in the selected time range
    $existing_stmt = $conn->prepare("SELECT appointment_time 
                                   FROM appointments 
                                   WHERE doctor_id = ? 
                                   AND appointment_date = ? 
                                   AND clinic_name = ? 
                                   AND appointment_time >= ? 
                                   AND appointment_time <= ? 
                                   AND status != 'cancelled'
                                   ORDER BY appointment_time ASC");
    
    $existing_stmt->bind_param("issss", $doctor_id, $date, $clinic_name, $slot_info['start'], $slot_info['end']);
    $existing_stmt->execute();
    $existing_result = $existing_stmt->get_result();
    
    $existing_times = [];
    while ($row = $existing_result->fetch_assoc()) {
        $existing_times[] = $row['appointment_time'];
    }
    
    // Start from the beginning of the selected slot
    $current_time = new DateTime($date . ' ' . $slot_info['start']);
    $end_time = new DateTime($date . ' ' . $slot_info['end']);
    
    // Find the next available 20-minute slot
    while ($current_time < $end_time) {
        $current_time_str = $current_time->format('H:i:s');
        
        // Check if this time slot is already taken
        if (!in_array($current_time_str, $existing_times)) {
            return $current_time_str;
        }
        
        // Add 20 minutes for next slot
        $current_time->add(new DateInterval('PT20M'));
    }
    
    // If we reach here, all slots are full
    return false;
}

function checkSlotAvailability($conn, $doctor_id, $date, $slot_start, $clinic_name) {
    // Define time slot ranges
    $time_slots = [
        '11:00' => ['start' => '11:00:00', 'end' => '13:00:00'],
        '14:00' => ['start' => '14:00:00', 'end' => '16:00:00'],
        '17:00' => ['start' => '17:00:00', 'end' => '19:00:00']
    ];
    
    if (!isset($time_slots[$slot_start])) {
        return false;
    }
    
    $slot_info = $time_slots[$slot_start];
    
    // Count existing appointments in this slot
    $count_stmt = $conn->prepare("SELECT COUNT(*) as count 
                                FROM appointments 
                                WHERE doctor_id = ? 
                                AND appointment_date = ? 
                                AND clinic_name = ? 
                                AND appointment_time >= ? 
                                AND appointment_time <= ? 
                                AND status != 'cancelled'");
    
    $count_stmt->bind_param("issss", $doctor_id, $date, $clinic_name, $slot_info['start'], $slot_info['end']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    
    // Calculate maximum possible appointments in this slot (2 hours / 20 minutes = 6 slots)
    $slot_duration_minutes = 120; // 2 hours
    $appointment_duration_minutes = 20;
    $max_appointments = $slot_duration_minutes / $appointment_duration_minutes;
    
    return $count_data['count'] < $max_appointments;
}

function getDoctorAvailability($conn) {
    try {
        $doctor_id = intval($_GET['doctor_id'] ?? 0);
        
        if (!$doctor_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid doctor ID']);
            return;
        }
        
        $stmt = $conn->prepare("SELECT c.clinic_name, dca.availability_schedule 
                            FROM doctor_clinic_assignments dca 
                            JOIN clinics c ON dca.clinic_id = c.clinic_id 
                            WHERE dca.doctor_id = ?");
        
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $availability = [];
        while ($row = $result->fetch_assoc()) {
            $schedule = json_decode($row['availability_schedule'], true);
            if ($schedule) {
                $availability[$row['clinic_name']] = $schedule;
            }
        }
        
        if (empty($availability)) {
            $general_stmt = $conn->prepare("SELECT availability FROM doctor WHERE doc_id = ?");
            $general_stmt->bind_param("i", $doctor_id);
            $general_stmt->execute();
            $general_result = $general_stmt->get_result();
            
            if ($general_result->num_rows > 0) {
                $general_data = $general_result->fetch_assoc();
                $general_availability = json_decode($general_data['availability'], true);
                if ($general_availability) {
                    $availability['general'] = $general_availability;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'availability' => $availability,
            'doctor_id' => $doctor_id
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get doctor availability']);
    }
}

function checkDailyBookings($conn) {
    try {
        $email = $_GET['email'] ?? '';
        $date = $_GET['date'] ?? '';
        
        if (!$email || !$date) {
            echo json_encode(['success' => false, 'message' => 'Email and date are required']);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            return;
        }
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments 
                              WHERE patient_email = ? AND appointment_date = ? AND status != 'cancelled'");
        
        $stmt->bind_param("ss", $email, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'daily_count' => intval($data['count']),
            'remaining_slots' => 999,
            'limit_reached' => false,
            'date' => $date,
            'message' => 'No booking limits - book as many appointments as needed'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to check daily bookings']);
    }
}
?>