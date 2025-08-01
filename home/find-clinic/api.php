<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

// Base URL for images (adjust this according to your setup)
$base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

try {
    // Create connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle different request methods
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch($method) {
        case 'GET':
            // Check if requesting doctors for a specific clinic
            if (isset($_GET['clinic_doctors']) && !empty($_GET['clinic_doctors'])) {
                $clinicId = $_GET['clinic_doctors'];
                
                $stmt = $pdo->prepare("
                    SELECT 
                        d.doc_id,
                        d.doc_name,
                        d.doc_specia,
                        d.experience,
                        d.fees,
                        d.doc_img,
                        d.gender,
                        d.education,
                        d.bio,
                        dca.availability_schedule
                    FROM doctor d
                    INNER JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id
                    WHERE dca.clinic_id = ?
                    ORDER BY d.doc_name ASC
                ");
                $stmt->execute([$clinicId]);
                $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Process availability schedule and image paths for each doctor
                foreach ($doctors as &$doctor) {
                    // Process doctor image path
                    if (!empty($doctor['doc_img'])) {
                        // Check if it's already a full URL
                        if (!filter_var($doctor['doc_img'], FILTER_VALIDATE_URL)) {
                            // If it's a relative path, make it absolute
                            if (!str_starts_with($doctor['doc_img'], '/')) {
                                $doctor['doc_img'] = 'http://localhost/cure_booking/adminhub/manage-doctors/uploads' . '/' . $doctor['doc_img'];
                            } else {
                                $doctor['doc_img'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $doctor['doc_img'];
                            }
                        }
                    }
                    
                    $schedule = json_decode($doctor['availability_schedule'], true);
                    $availableDays = [];
                    $availableSlots = [];
                    
                    if ($schedule) {
                        foreach ($schedule as $day => $slots) {
                            $daySlots = [];
                            foreach ($slots as $timeSlot => $isAvailable) {
                                if ($isAvailable) {
                                    $daySlots[] = $timeSlot;
                                }
                            }
                            if (!empty($daySlots)) {
                                $availableDays[] = ucfirst($day);
                                $availableSlots[$day] = $daySlots;
                            }
                        }
                    }
                    
                    $doctor['available_days'] = $availableDays;
                    $doctor['available_slots'] = $availableSlots;
                    // Remove raw schedule from response
                    unset($doctor['availability_schedule']);
                }
                
                $response = [
                    'success' => true,
                    'count' => count($doctors),
                    'clinic_id' => $clinicId,
                    'data' => $doctors
                ];
                
                echo json_encode($response);
                break;
            }
            
            // Check if searching for specific clinic
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = '%' . $_GET['search'] . '%';
                $stmt = $pdo->prepare("
                    SELECT clinic_id, clinic_name, clinic_email, contact_number, 
                           location, available_timing, profile_image, about, status
                    FROM clinics 
                    WHERE status = 'active' 
                    AND (clinic_name LIKE ? OR location LIKE ? OR about LIKE ?)
                    ORDER BY clinic_name ASC
                ");
                $stmt->execute([$search, $search, $search]);
            } else {
                // Get all active clinics
                $stmt = $pdo->prepare("
                    SELECT clinic_id, clinic_name, clinic_email, contact_number, 
                           location, available_timing, profile_image, about, status
                    FROM clinics 
                    WHERE status = 'active'
                    ORDER BY clinic_name ASC
                ");
                $stmt->execute();
            }
            
            $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process image paths for each clinic
            foreach ($clinics as &$clinic) {
                if (!empty($clinic['profile_image'])) {
                    // Check if it's already a full URL
                    if (!filter_var($clinic['profile_image'], FILTER_VALIDATE_URL)) {
                        // If it's a relative path, make it absolute
                        if (!str_starts_with($clinic['profile_image'], '/')) {
                            $clinic['profile_image'] = 'http://localhost/cure_booking/adminhub/manage-clinics/uploads' . '/' . $clinic['profile_image'];
                        } else {
                            $clinic['profile_image'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $clinic['profile_image'];
                        }
                    }
                }
                
                // Add image validation
                $clinic['has_image'] = !empty($clinic['profile_image']);
            }
            
            // Format the response
            $response = [
                'success' => true,
                'count' => count($clinics),
                'data' => $clinics,
                'base_url' => $base_url
            ];
            
            echo json_encode($response);
            break;
            
        case 'POST':
            // Handle POST requests (if needed for future functionality)
            $response = [
                'success' => false,
                'message' => 'POST method not implemented yet'
            ];
            echo json_encode($response);
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => 'Method not allowed'
            ];
            http_response_code(405);
            echo json_encode($response);
            break;
    }
    
} catch(PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
    http_response_code(500);
    echo json_encode($response);
}
?>