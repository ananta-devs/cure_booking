<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_doctors':
        getDoctors($conn);
        break;
        
    case 'book_appointment':
        bookAppointment($conn);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();

function getDoctors($conn) {
    try {
        $specialization = isset($_GET['specialization']) ? $_GET['specialization'] : '';
        $experience = isset($_GET['experience']) ? $_GET['experience'] : '';

        $sql = "SELECT * FROM doctor WHERE 1=1";

        if (!empty($specialization)) {
            $sql .= " AND doc_specia = '" . $conn->real_escape_string($specialization) . "'";
        }

        $result = $conn->query($sql);

        if ($result) {
            $doctors = [];
            
            while($row = $result->fetch_assoc()) {
                $doctors[] = [
                    'id' => $row['id'],
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
                    'availability' => json_decode($row['availability'] ?? '["Monday", "Wednesday", "Friday"]')
                ];
            }
            
            echo json_encode($doctors);
        } else {
            echo json_encode(["error" => "Error: " . $conn->error]);
        }
        
    } catch (Exception $e) {
        echo json_encode(["error" => "Exception: " . $e->getMessage()]);
    }
}

function bookAppointment($conn) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $doctor_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
        $phone = isset($_POST['phone']) ? $conn->real_escape_string($_POST['phone']) : '';
        $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
        $date = isset($_POST['date']) ? $conn->real_escape_string($_POST['date']) : '';
        $time = isset($_POST['time']) ? $conn->real_escape_string($_POST['time']) : '';

        if (empty($doctor_id) || empty($name) || empty($phone) || empty($email) || empty($date) || empty($time)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        try {
            $doctor_query = "SELECT doc_name, doc_specia FROM doctor WHERE id = ?";
            $stmt_doctor = $conn->prepare($doctor_query);
            
            if (!$stmt_doctor) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt_doctor->bind_param("i", $doctor_id);
            $stmt_doctor->execute();
            $doctor_result = $stmt_doctor->get_result();
            
            if ($doctor_result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Doctor not found']);
                exit;
            }
            
            $doctor_data = $doctor_result->fetch_assoc();
            $doctor_name = $doctor_data['doc_name'];
            $doctor_specialization = $doctor_data['doc_specia'];
            $stmt_doctor->close();

            $sql = "INSERT INTO appointments (doctor_id, doctor_name, doctor_specialization, patient_name, patient_phone, patient_email, appointment_date, appointment_time, booking_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("isssssss", $doctor_id, $doctor_name, $doctor_specialization, $name, $phone, $email, $date, $time);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Appointment booked successfully!']);
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
}
?>