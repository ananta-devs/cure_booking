<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['adm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include '../include/database_connection.php';

// Check if action parameter exists
if (!isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit();
}

$action = $_POST['action'];

if ($action === 'update') {
    try {
        // Validate required fields
        $clinic_id = isset($_POST['clinic_id']) ? intval($_POST['clinic_id']) : 0;
        $clinic_name = isset($_POST['clinic_name']) ? trim($_POST['clinic_name']) : '';
        $clinic_email = isset($_POST['clinic_email']) ? trim($_POST['clinic_email']) : '';
        $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $available_timing = isset($_POST['available_timing']) ? trim($_POST['available_timing']) : '';
        $about = isset($_POST['about']) ? trim($_POST['about']) : '';
        
        // Basic validation
        if ($clinic_id <= 0) {
            throw new Exception('Invalid clinic ID');
        }
        
        if (empty($clinic_name) || empty($clinic_email) || empty($contact_number) || 
            empty($location) || empty($available_timing)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Validate email format
        if (!filter_var($clinic_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email already exists for other clinics
        $stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE clinic_email = ? AND clinic_id != ?");
        $stmt->execute([$clinic_email, $clinic_id]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists for another clinic');
        }
        
        // Check if clinic exists
        $stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE clinic_id = ?");
        $stmt->execute([$clinic_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Clinic not found');
        }
        
        // Update clinic data
        $sql = "UPDATE clinics SET 
                clinic_name = ?, 
                clinic_email = ?, 
                contact_number = ?, 
                location = ?, 
                available_timing = ?, 
                about = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE clinic_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $clinic_name,
            $clinic_email,
            $contact_number,
            $location,
            $available_timing,
            $about,
            $clinic_id
        ]);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Clinic updated successfully!'
            ]);
        } else {
            throw new Exception('Failed to update clinic data');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($action === 'delete') {
    try {
        $clinic_id = isset($_POST['clinic_id']) ? intval($_POST['clinic_id']) : 0;
        
        // Basic validation
        if ($clinic_id <= 0) {
            throw new Exception('Invalid clinic ID');
        }
        
        // Check if clinic exists
        $stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE clinic_id = ?");
        $stmt->execute([$clinic_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Clinic not found');
        }
        
        // Check if clinic has associated appointments
        $stmt = $pdo->prepare("SELECT COUNT(*) as appointment_count FROM appointments WHERE clinic_id = ?");
        $stmt->execute([$clinic_id]);
        $result = $stmt->fetch();
        
        if ($result['appointment_count'] > 0) {
            throw new Exception('Cannot delete clinic. There are existing appointments associated with this clinic.');
        }
        
        // Check if clinic has associated doctors
        $stmt = $pdo->prepare("SELECT COUNT(*) as doctor_count FROM doctors WHERE clinic_id = ?");
        $stmt->execute([$clinic_id]);
        $result = $stmt->fetch();
        
        if ($result['doctor_count'] > 0) {
            throw new Exception('Cannot delete clinic. There are doctors associated with this clinic.');
        }
        
        // Delete clinic
        $stmt = $pdo->prepare("DELETE FROM clinics WHERE clinic_id = ?");
        $result = $stmt->execute([$clinic_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Clinic deleted successfully!'
            ]);
        } else {
            throw new Exception('Failed to delete clinic');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($action === 'get') {
    try {
        $clinic_id = isset($_POST['clinic_id']) ? intval($_POST['clinic_id']) : 0;
        
        // Basic validation
        if ($clinic_id <= 0) {
            throw new Exception('Invalid clinic ID');
        }
        
        // Get clinic data
        $stmt = $pdo->prepare("SELECT * FROM clinics WHERE clinic_id = ?");
        $stmt->execute([$clinic_id]);
        $clinic = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($clinic) {
            echo json_encode([
                'status' => 'success',
                'data' => $clinic
            ]);
        } else {
            throw new Exception('Clinic not found');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($action === 'list') {
    try {
        // Get all clinics with basic info
        $stmt = $pdo->prepare("SELECT clinic_id, clinic_name, clinic_email, contact_number, location, available_timing, status, created_at FROM clinics ORDER BY clinic_name ASC");
        $stmt->execute();
        $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $clinics
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($action === 'toggle_status') {
    try {
        $clinic_id = isset($_POST['clinic_id']) ? intval($_POST['clinic_id']) : 0;
        
        // Basic validation
        if ($clinic_id <= 0) {
            throw new Exception('Invalid clinic ID');
        }
        
        // Get current status
        $stmt = $pdo->prepare("SELECT status FROM clinics WHERE clinic_id = ?");
        $stmt->execute([$clinic_id]);
        $clinic = $stmt->fetch();
        
        if (!$clinic) {
            throw new Exception('Clinic not found');
        }
        
        // Toggle status
        $new_status = ($clinic['status'] === 'active') ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE clinics SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE clinic_id = ?");
        $result = $stmt->execute([$new_status, $clinic_id]);
        
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Clinic status updated successfully!',
                'new_status' => $new_status
            ]);
        } else {
            throw new Exception('Failed to update clinic status');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid action specified'
    ]);
}

// Close database connection
$pdo = null;
?>