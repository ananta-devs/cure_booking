<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['adm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include '../include/database_connection.php';

try {
    // Fetch all clinics from database
    $sql = "SELECT clinic_id, clinic_name, clinic_email, contact_number, location, 
            available_timing, profile_image, about, created_at 
            FROM clinics 
            ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'clinics' => $clinics,
        'total' => count($clinics)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch clinics: ' . $e->getMessage()
    ]);
}
?>