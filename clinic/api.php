<?php
include_once 'database_connection.php';

// Check authentication and get clinic_id
if (!isset($_SESSION['clinic_logged_in'], $_SESSION['clinic_id'])) {
    header("Location: http://localhost/cure_booking/login.php");
    exit();
}

$clinic_id = $_SESSION['clinic_id'];

// Fetch appointments with doctor details
function getAppointments($conn, $clinic_id) {
    $query = "SELECT a.id, a.patient_name, a.patient_phone, a.patient_email,
                     a.appointment_date, a.appointment_time, a.status, a.gender,
                     a.created_at,
                     d.doc_name, d.doc_specia as doctor_specialization, d.doc_id,
                     d.doc_email, d.experience, d.location, d.education
              FROM appointments a 
              JOIN doctor d ON a.doctor_id = d.doc_id 
              WHERE a.clinic_id = ?
              ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("MySQL prepare error: " . mysqli_error($conn));
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $clinic_id);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log("MySQL result error: " . mysqli_error($conn));
        return [];
    }
    
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Fetch doctors assigned to clinic
function getDoctors($conn, $clinic_id) {
    $query = "SELECT DISTINCT d.* 
              FROM doctor d 
              JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
              WHERE dca.clinic_id = ? 
              ORDER BY d.doc_name";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $clinic_id);
    mysqli_stmt_execute($stmt);
    
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

// Fetch specialties
function getSpecialties($conn, $clinic_id) {
    $query = "SELECT DISTINCT d.doc_specia 
              FROM doctor d 
              JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
              WHERE dca.clinic_id = ? 
              ORDER BY d.doc_specia";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $clinic_id);
    mysqli_stmt_execute($stmt);
    
    return array_column(mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC), 'doc_specia');
}

// Get available time slots
function getTimeSlots($conn, $doctor_id, $clinic_id, $appointment_date) {
    $query = "SELECT availability_schedule FROM doctor_clinic_assignments 
              WHERE doctor_id = ? AND clinic_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $clinic_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || !($row = mysqli_fetch_assoc($result)) || empty($row['availability_schedule'])) {
        return [];
    }
    
    $schedule = json_decode($row['availability_schedule'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($schedule)) {
        return [];
    }
    
    $dayOfWeek = strtolower((new DateTime($appointment_date))->format('l'));
    if (!isset($schedule[$dayOfWeek])) {
        return [];
    }
    
    // Get all available slots for the day
    $all_slots = [];
    foreach ($schedule[$dayOfWeek] as $timeRange => $isAvailable) {
        if ($isAvailable === true) {
            $all_slots = array_merge($all_slots, parseTimeRangePredefined($timeRange));
        }
    }
    
    if (empty($all_slots)) {
        return [];
    }
    
    // Get booked slots
    $booked_query = "SELECT appointment_time FROM appointments 
                     WHERE doctor_id = ? AND clinic_id = ? AND appointment_date = ? 
                     AND status NOT IN ('cancelled', 'no_show')";
    
    $booked_stmt = mysqli_prepare($conn, $booked_query);
    mysqli_stmt_bind_param($booked_stmt, "iis", $doctor_id, $clinic_id, $appointment_date);
    mysqli_stmt_execute($booked_stmt);
    $booked_result = mysqli_stmt_get_result($booked_stmt);
    
    $booked_slots = [];
    while ($booked_row = mysqli_fetch_assoc($booked_result)) {
        $time_obj = DateTime::createFromFormat('H:i:s', $booked_row['appointment_time']);
        if ($time_obj) {
            $booked_slots[] = $time_obj->format('H:i');
        }
    }
    
    $available_slots = array_values(array_diff($all_slots, $booked_slots));
    sort($available_slots);
    
    return $available_slots;
}

function parseTimeRangePredefined($timeRange) {
    $predefinedSlots = [
        '11:00-13:00' => ['11:00', '11:30', '12:00', '12:30'],
        '14:00-16:00' => ['14:00', '14:30', '15:00', '15:30'], 
        '17:00-19:00' => ['17:00', '17:30', '18:00', '18:30']
    ];
    
    return $predefinedSlots[$timeRange] ?? [];
}

// Validate date
function validateDate($date) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        return false;
    }
    return $dateObj >= new DateTime('today');
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_appointments':
            try {
                $appointments = getAppointments($conn, $clinic_id);
                echo json_encode([
                    'success' => true,
                    'appointments' => $appointments
                ]);
            } catch (Exception $e) {
                error_log("Error getting appointments: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to load appointments'
                ]);
            }
            exit();

        case 'get_appointment_details':
            try {
                $appointment_id = (int)$_POST['appointment_id'];
                
                if (!$appointment_id) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid appointment ID'
                    ]);
                    exit();
                }
                
                // Enhanced query with all necessary joins and fields
                $query = "SELECT a.*, 
                                 d.doc_name, d.doc_specia, d.doc_email, 
                                 d.experience, d.location, d.education,
                                 c.clinic_name
                          FROM appointments a 
                          JOIN doctor d ON a.doctor_id = d.doc_id 
                          LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
                          WHERE a.id = ? AND a.clinic_id = ?";
                
                $stmt = mysqli_prepare($conn, $query);
                if (!$stmt) {
                    throw new Exception("Database prepare error: " . mysqli_error($conn));
                }
                
                mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $clinic_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (!$result) {
                    throw new Exception("Database query error: " . mysqli_error($conn));
                }
                
                $appointment = mysqli_fetch_assoc($result);
                
                if ($appointment) {
                    // Ensure all expected fields are present
                    $appointment['doctor_specialization'] = $appointment['doc_specia'];
                    
                    echo json_encode([
                        'success' => true,
                        'appointment' => $appointment
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Appointment not found or access denied'
                    ]);
                }
            } catch (Exception $e) {
                error_log("Error getting appointment details: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error occurred'
                ]);
            }
            exit();

        case 'update_appointment_status':
            try {
                $appointment_id = (int)$_POST['appointment_id'];
                $new_status = mysqli_real_escape_string($conn, $_POST['status']);
                $note = isset($_POST['note']) ? mysqli_real_escape_string($conn, $_POST['note']) : '';
                
                // Validate status
                $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
                if (!in_array($new_status, $allowed_statuses)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid status'
                    ]);
                    exit();
                }
                
                // Verify appointment belongs to clinic
                $verify_query = "SELECT id FROM appointments WHERE id = ? AND clinic_id = ?";
                $verify_stmt = mysqli_prepare($conn, $verify_query);
                mysqli_stmt_bind_param($verify_stmt, "ii", $appointment_id, $clinic_id);
                mysqli_stmt_execute($verify_stmt);
                
                if (mysqli_num_rows(mysqli_stmt_get_result($verify_stmt)) === 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Appointment not found'
                    ]);
                    exit();
                }
                
                // Update appointment status
                $update_query = "UPDATE appointments SET status = ?, updated_at = NOW()";
                $params = [$new_status];
                $types = "s";
                
                // Add note if provided
                if (!empty($note)) {
                    $update_query .= ", notes = ?";
                    $params[] = $note;
                    $types .= "s";
                }
                
                $update_query .= " WHERE id = ? AND clinic_id = ?";
                $params[] = $appointment_id;
                $params[] = $clinic_id;
                $types .= "ii";
                
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                
                if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Appointment status updated successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update appointment status'
                    ]);
                }
            } catch (Exception $e) {
                error_log("Error updating appointment status: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error occurred'
                ]);
            }
            exit();

        case 'cancel_appointment':
            try {
                $appointment_id = (int)$_POST['appointment_id'];
                
                // Update appointment status to cancelled
                $query = "UPDATE appointments SET status = 'cancelled', updated_at = NOW() 
                         WHERE id = ? AND clinic_id = ?";
                
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $clinic_id);
                
                if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Appointment cancelled successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to cancel appointment'
                    ]);
                }
            } catch (Exception $e) {
                error_log("Error cancelling appointment: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error occurred'
                ]);
            }
            exit();
            
        case 'get_time_slots':
            $doctor_id = (int)$_POST['doctor_id'];
            $appointment_date = $_POST['appointment_date'];
            
            if (!$doctor_id || !$appointment_date || !validateDate($appointment_date)) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters or past date']);
                exit();
            }
            
            $time_slots = getTimeSlots($conn, $doctor_id, $clinic_id, $appointment_date);
            echo json_encode(['success' => true, 'time_slots' => $time_slots]);
            exit();
            
        case 'book_appointment':
            $required_fields = ['patient_name', 'patient_phone', 'patient_email', 'doctor_id', 'appointment_date', 'appointment_time', 'gender'];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit();
                }
            }
            
            $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
            $patient_phone = mysqli_real_escape_string($conn, $_POST['patient_phone']);
            $patient_email = mysqli_real_escape_string($conn, $_POST['patient_email']);
            $doctor_id = (int)$_POST['doctor_id'];
            $appointment_date = $_POST['appointment_date'];
            $appointment_time = $_POST['appointment_time'];
            $gender = mysqli_real_escape_string($conn, $_POST['gender']);
            
            if (!filter_var($patient_email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit();
            }
            
            if (!validateDate($appointment_date)) {
                echo json_encode(['success' => false, 'message' => 'Invalid date or past date']);
                exit();
            }
            
            // Validate time slot availability
            $available_slots = getTimeSlots($conn, $doctor_id, $clinic_id, $appointment_date);
            if (!in_array($appointment_time, $available_slots)) {
                echo json_encode(['success' => false, 'message' => 'Selected time slot is not available']);
                exit();
            }
            
            $time_obj = DateTime::createFromFormat('H:i', $appointment_time);
            if (!$time_obj) {
                echo json_encode(['success' => false, 'message' => 'Invalid time format']);
                exit();
            }
            $formatted_time = $time_obj->format('H:i:s');
            
            // Get doctor and clinic info
            $doctor_query = "SELECT d.doc_name, d.doc_specia, c.clinic_name 
                           FROM doctor d 
                           JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
                           JOIN clinics c ON dca.clinic_id = c.clinic_id 
                           WHERE d.doc_id = ? AND dca.clinic_id = ?";
            
            $doctor_stmt = mysqli_prepare($conn, $doctor_query);
            mysqli_stmt_bind_param($doctor_stmt, "ii", $doctor_id, $clinic_id);
            mysqli_stmt_execute($doctor_stmt);
            $doctor_info = mysqli_fetch_assoc(mysqli_stmt_get_result($doctor_stmt));
            
            if (!$doctor_info) {
                echo json_encode(['success' => false, 'message' => 'Doctor not found']);
                exit();
            }
            
            // Check for duplicates
            $duplicate_query = "SELECT id FROM appointments 
                               WHERE doctor_id = ? AND clinic_id = ? AND appointment_date = ? 
                               AND appointment_time = ? AND status NOT IN ('cancelled', 'no_show')";
            
            $duplicate_stmt = mysqli_prepare($conn, $duplicate_query);
            mysqli_stmt_bind_param($duplicate_stmt, "iiss", $doctor_id, $clinic_id, $appointment_date, $formatted_time);
            mysqli_stmt_execute($duplicate_stmt);
            
            if (mysqli_num_rows(mysqli_stmt_get_result($duplicate_stmt)) > 0) {
                echo json_encode(['success' => false, 'message' => 'Time slot already booked']);
                exit();
            }
            
            // Insert appointment
            $booked_by_email = $_SESSION['clinic_email'] ?? 'system@clinic.com';
            $booked_by_name = $_SESSION['clinic_name'] ?? 'Clinic Admin';
            
            $query = "INSERT INTO appointments (
                doctor_id, doctor_name, doctor_specialization, clinic_id, clinic_name,
                patient_name, patient_phone, patient_email, gender,
                appointment_date, appointment_time, booked_by_email, booked_by_name, 
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "issississssss", 
                $doctor_id, $doctor_info['doc_name'], $doctor_info['doc_specia'], 
                $clinic_id, $doctor_info['clinic_name'], $patient_name, 
                $patient_phone, $patient_email, $gender, $appointment_date, 
                $formatted_time, $booked_by_email, $booked_by_name
            );
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Appointment booked successfully!',
                    'appointment_id' => mysqli_insert_id($conn)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error booking appointment']);
            }
            exit();
            
        case 'update_appointment':
            $id = (int)$_POST['id'];
            
            // Verify appointment belongs to clinic
            $verify_query = "SELECT id FROM appointments WHERE id = ? AND clinic_id = ?";
            $verify_stmt = mysqli_prepare($conn, $verify_query);
            mysqli_stmt_bind_param($verify_stmt, "ii", $id, $clinic_id);
            mysqli_stmt_execute($verify_stmt);
            
            if (mysqli_num_rows(mysqli_stmt_get_result($verify_stmt)) === 0) {
                echo json_encode(['success' => false, 'message' => 'Appointment not found']);
                exit();
            }
            
            $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
            $patient_phone = mysqli_real_escape_string($conn, $_POST['patient_phone']);
            $patient_email = mysqli_real_escape_string($conn, $_POST['patient_email']);
            $doctor_id = (int)$_POST['doctor_id'];
            $appointment_date = $_POST['appointment_date'];
            $appointment_time = $_POST['appointment_time'];
            $gender = mysqli_real_escape_string($conn, $_POST['gender']);
            
            // Get doctor info and update
            $doctor_query = "SELECT doc_name, doc_specia FROM doctor WHERE doc_id = ?";
            $doctor_stmt = mysqli_prepare($conn, $doctor_query);
            mysqli_stmt_bind_param($doctor_stmt, "i", $doctor_id);
            mysqli_stmt_execute($doctor_stmt);
            $doctor_info = mysqli_fetch_assoc(mysqli_stmt_get_result($doctor_stmt));
            
            $query = "UPDATE appointments SET 
                     patient_name=?, patient_phone=?, patient_email=?, 
                     doctor_id=?, doctor_name=?, doctor_specialization=?,
                     appointment_date=?, appointment_time=?, gender=?
                     WHERE id=? AND clinic_id=?";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssissssssii", 
                $patient_name, $patient_phone, $patient_email, 
                $doctor_id, $doctor_info['doc_name'], $doctor_info['doc_specia'],
                $appointment_date, $appointment_time, $gender, $id, $clinic_id
            );
            
            echo json_encode([
                'success' => mysqli_stmt_execute($stmt),
                'message' => mysqli_stmt_execute($stmt) ? 'Updated successfully!' : 'Update failed'
            ]);
            exit();
            
        case 'delete_appointment':
            $id = (int)$_POST['id'];
            
            $query = "DELETE FROM appointments WHERE id = ? AND clinic_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $id, $clinic_id);
            
            $success = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Deleted successfully!' : 'Delete failed'
            ]);
            exit();
            
        case 'get_doctors_by_specialty':
            $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
            $query = "SELECT d.doc_id, d.doc_name, d.doc_specia, d.experience, d.location, d.education 
                     FROM doctor d 
                     JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
                     WHERE d.doc_specia = ? AND dca.clinic_id = ? 
                     ORDER BY d.doc_name";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $specialty, $clinic_id);
            mysqli_stmt_execute($stmt);
            
            echo json_encode(mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC));
            exit();

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            exit();
    }
}

// Fetch data for page load
$appointments = getAppointments($conn, $clinic_id);
$doctors = getDoctors($conn, $clinic_id);
$specialties = getSpecialties($conn, $clinic_id);
?>