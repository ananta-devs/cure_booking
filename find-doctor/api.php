<?php

    session_start();
    header('Content-Type: application/json');

    // Database connection
    $conn = new mysqli("localhost", "root", "", "cure_booking");
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
        case 'check_daily_bookings':
            checkDailyBookings($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
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
    }

    function getTimeSlots($conn) {
        $doctor_id = intval($_GET['doctor_id'] ?? 0);
        $clinic_name = $_GET['clinic_name'] ?? '';
        $selected_date = $_GET['date'] ?? '';
        
        if (!$doctor_id || !$clinic_name || !$selected_date) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
        
        $day_of_week = strtolower(date('l', strtotime($selected_date)));
        
        // Get availability schedule
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
        
        // Get available slots
        $available_slots = array_keys(array_filter($availability_schedule[$day_of_week]));
        
        if (empty($available_slots)) {
            echo json_encode(['success' => false, 'message' => 'No available time slots for this day']);
            return;
        }
        
        // Get booked slots
        $booked_stmt = $conn->prepare("SELECT TIME_FORMAT(appointment_time, '%H:%i') as booked_time 
                                    FROM appointments 
                                    WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'");
        $booked_stmt->bind_param("is", $doctor_id, $selected_date);
        $booked_stmt->execute();
        $booked_result = $booked_stmt->get_result();
        
        $booked_times = [];
        while ($booked_row = $booked_result->fetch_assoc()) {
            $booked_times[] = $booked_row['booked_time'];
        }
        
        // Filter available slots
        $final_slots = [];
        foreach ($available_slots as $slot) {
            $slot_start = explode('-', $slot)[0];
            if (!in_array($slot_start, $booked_times)) {
                $final_slots[] = [
                    'value' => $slot_start,
                    'label' => $slot,
                    'available' => true
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'time_slots' => $final_slots,
            'day' => $day_of_week
        ]);
    }

    function bookAppointment($conn) {
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        // Validate input
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

        // Date validation
        $appointment_date = new DateTime($data['date']);
        $today = new DateTime();
        $max_date = (new DateTime())->add(new DateInterval('P3M'));
        
        if ($appointment_date < $today || $appointment_date > $max_date) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment date']);
            return;
        }

        // Get user info from session
        $user_email = $_SESSION['email'] ?? $_SESSION['user_email'] ?? $data['email'];
        $user_name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? $data['name'];

        // Check for conflicts
        $time_with_seconds = $data['time'] . ':00';
        $conflict_stmt = $conn->prepare("SELECT id FROM appointments 
                                        WHERE doctor_id = ? AND appointment_date = ? 
                                        AND appointment_time = ? AND status != 'cancelled'");
        $conflict_stmt->bind_param("iss", $data['doctor_id'], $data['date'], $time_with_seconds);
        $conflict_stmt->execute();
        
        if ($conflict_stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Time slot is no longer available']);
            return;
        }

        // Check for duplicate booking by same patient with same doctor on same date
        $duplicate_stmt = $conn->prepare("SELECT id FROM appointments 
                                        WHERE patient_email = ? AND doctor_id = ? 
                                        AND appointment_date = ? AND status != 'cancelled'");
        $duplicate_stmt->bind_param("sis", $data['email'], $data['doctor_id'], $data['date']);
        $duplicate_stmt->execute();
        
        if ($duplicate_stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'You already have an appointment with this doctor on this date']);
            return;
        }

        // UPDATED: Daily booking limit - check appointments for the same day (4 appointments per day limit)
        $daily_limit_stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments 
                                          WHERE patient_email = ? AND appointment_date = ? AND status != 'cancelled'");
        $daily_limit_stmt->bind_param("ss", $data['email'], $data['date']);
        $daily_limit_stmt->execute();
        $daily_data = $daily_limit_stmt->get_result()->fetch_assoc();
        
        if ($daily_data['count'] >= 4) {
            echo json_encode(['success' => false, 'message' => 'You have reached the maximum limit of 4 appointments per day. Please choose a different date.']);
            return;
        }

        // Get doctor and clinic info
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

        // Set default gender if not provided
        $gender = 'other'; // You can modify this or add a gender field to the form

        // Insert appointment - Updated to match the actual table schema
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
            $time_with_seconds, 
            $user_email,
            $user_name
        );
        
        if ($insert_stmt->execute()) {
            // Get current appointment count for the day to show in response
            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments 
                                        WHERE patient_email = ? AND appointment_date = ? AND status != 'cancelled'");
            $count_stmt->bind_param("ss", $data['email'], $data['date']);
            $count_stmt->execute();
            $current_count = $count_stmt->get_result()->fetch_assoc()['count'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment booked successfully!',
                'appointment_details' => [
                    'appointment_id' => $conn->insert_id,
                    'patient_name' => $data['name'],
                    'doctor_name' => $info['doc_name'],
                    'appointment_date' => date('M d, Y', strtotime($data['date'])),
                    'appointment_time' => date('h:i A', strtotime($data['time'])),
                    'clinic_name' => $data['clinic'],
                    'daily_appointments_count' => $current_count,
                    'remaining_slots_today' => 4 - $current_count
                ]
            ]);
        } else {
            error_log("Database error: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Booking failed. Please try again.']);
        }
    }

    function getDoctorAvailability($conn) {
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
        
        // Fallback to general availability if no clinic-specific data
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
    }

    function checkDailyBookings($conn) {
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
            'remaining_slots' => 4 - intval($data['count']),
            'limit_reached' => intval($data['count']) >= 4,
            'date' => $date
        ]);
    }
?>