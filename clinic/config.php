<?php
/**
 * Database Configuration File
 * Hospital/Clinic Appointment Management System
 */

// Prevent direct access
if (!defined('DB_ACCESS')) {
    define('DB_ACCESS', true);
}

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'cure_booking');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Medical Appointment System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/appointment-system');

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('DOCTOR_IMG_DIR', UPLOAD_DIR . 'doctors/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMG_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Timezone Configuration
date_default_timezone_set('Asia/Kolkata');

// Database Connection
$conn = null;

try {
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Set charset
    $conn->set_charset(DB_CHARSET);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
} catch (Exception $e) {
    // Log error (in production, don't display database errors to users)
    error_log("Database connection error: " . $e->getMessage());
    
    // Display user-friendly error
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Connection Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 50px; }
            .error-container { 
                background: #f8d7da; 
                border: 1px solid #f5c6cb; 
                color: #721c24; 
                padding: 20px; 
                border-radius: 5px; 
                max-width: 600px;
            }
            .error-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
            .error-message { margin-bottom: 15px; }
            .error-steps { margin-left: 20px; }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='error-title'>Database Connection Error</div>
            <div class='error-message'>Unable to connect to the database. Please check the following:</div>
            <ol class='error-steps'>
                <li>Make sure MySQL/MariaDB is running</li>
                <li>Verify database credentials in config.php</li>
                <li>Ensure the database '" . DB_NAME . "' exists</li>
                <li>Check if the user has proper permissions</li>
            </ol>
        </div>
    </body>
    </html>
    ");
}

/**
 * Initialize database tables if they don't exist
 */
// function initializeDatabase($conn) {
//     $tables = [
//         'users' => "
//             CREATE TABLE IF NOT EXISTS `users` (
//                 `id` int(11) NOT NULL AUTO_INCREMENT,
//                 `username` varchar(50) NOT NULL UNIQUE,
//                 `email` varchar(100) NOT NULL UNIQUE,
//                 `password` varchar(255) NOT NULL,
//                 `full_name` varchar(100) NOT NULL,
//                 `role` enum('admin','staff','doctor') DEFAULT 'staff',
//                 `status` enum('active','inactive','suspended') DEFAULT 'active',
//                 `last_login` timestamp NULL DEFAULT NULL,
//                 `failed_attempts` int(11) DEFAULT 0,
//                 `locked_until` timestamp NULL DEFAULT NULL,
//                 `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
//                 `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//                 PRIMARY KEY (`id`)
//             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
//         ",
        
//         'doctors' => "
//             CREATE TABLE IF NOT EXISTS `doctors` (
//                 `doc_id` int(11) NOT NULL AUTO_INCREMENT,
//                 `doc_name` varchar(100) NOT NULL,
//                 `doc_email` varchar(100) NOT NULL UNIQUE,
//                 `doc_phone` varchar(20) DEFAULT NULL,
//                 `doc_specia` varchar(100) NOT NULL,
//                 `doc_img` varchar(255) DEFAULT NULL,
//                 `experience` int(11) DEFAULT 0,
//                 `location` varchar(200) DEFAULT NULL,
//                 `education` text DEFAULT NULL,
//                 `consultation_fee` decimal(10,2) DEFAULT 0.00,
//                 `available_days` varchar(50) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
//                 `available_time_start` time DEFAULT '09:00:00',
//                 `available_time_end` time DEFAULT '17:00:00',
//                 `status` enum('active','inactive','on_leave') DEFAULT 'active',
//                 `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
//                 `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//                 PRIMARY KEY (`doc_id`)
//             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
//         ",
        
//         'appointments' => "
//             CREATE TABLE IF NOT EXISTS `appointments` (
//                 `id` int(11) NOT NULL AUTO_INCREMENT,
//                 `patient_name` varchar(100) NOT NULL,
//                 `patient_phone` varchar(20) NOT NULL,
//                 `patient_email` varchar(100) NOT NULL,
//                 `patient_age` int(11) DEFAULT NULL,
//                 `gender` enum('male','female','other') NOT NULL,
//                 `doctor_id` int(11) NOT NULL,
//                 `appointment_date` date NOT NULL,
//                 `appointment_time` time NOT NULL,
//                 `status` enum('pending','confirmed','completed','cancelled','no_show') DEFAULT 'pending',
//                 `symptoms` text DEFAULT NULL,
//                 `notes` text DEFAULT NULL,
//                 `consultation_fee` decimal(10,2) DEFAULT 0.00,
//                 `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
//                 `created_by` int(11) DEFAULT NULL,
//                 `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
//                 `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//                 PRIMARY KEY (`id`),
//                 KEY `idx_doctor_date` (`doctor_id`, `appointment_date`),
//                 KEY `idx_patient_email` (`patient_email`),
//                 KEY `idx_appointment_date` (`appointment_date`),
//                 KEY `idx_status` (`status`),
//                 FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doc_id`) ON DELETE CASCADE ON UPDATE CASCADE,
//                 FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
//             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
//         ",
        
//         'appointment_history' => "
//             CREATE TABLE IF NOT EXISTS `appointment_history` (
//                 `id` int(11) NOT NULL AUTO_INCREMENT,
//                 `appointment_id` int(11) NOT NULL,
//                 `action` varchar(50) NOT NULL,
//                 `old_status` varchar(20) DEFAULT NULL,
//                 `new_status` varchar(20) DEFAULT NULL,
//                 `notes` text DEFAULT NULL,
//                 `changed_by` int(11) DEFAULT NULL,
//                 `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
//                 PRIMARY KEY (`id`),
//                 KEY `idx_appointment_id` (`appointment_id`),
//                 FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
//                 FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
//             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
//         ",
        
//         'settings' => "
//             CREATE TABLE IF NOT EXISTS `settings` (
//                 `id` int(11) NOT NULL AUTO_INCREMENT,
//                 `setting_key` varchar(100) NOT NULL UNIQUE,
//                 `setting_value` text DEFAULT NULL,
//                 `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
//                 `description` text DEFAULT NULL,
//                 `updated_by` int(11) DEFAULT NULL,
//                 `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//                 PRIMARY KEY (`id`),
//                 FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
//             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
//         "
//     ];
    
//     foreach ($tables as $tableName => $sql) {
//         if (!mysqli_query($conn, $sql)) {
//             error_log("Error creating table $tableName: " . mysqli_error($conn));
//         }
//     }
    
//     // Insert default admin user if users table is empty
//     $userCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
//     $row = mysqli_fetch_assoc($userCount);
    
//     if ($row['count'] == 0) {
//         $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
//         $insertAdmin = "INSERT INTO users (username, email, password, full_name, role, status) 
//                        VALUES ('admin', 'admin@hospital.com', '$defaultPassword', 'System Administrator', 'admin', 'active')";
//         mysqli_query($conn, $insertAdmin);
//     }
    
//     // Insert sample doctors if doctors table is empty
//     $doctorCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM doctors");
//     $row = mysqli_fetch_assoc($doctorCount);
    
//     if ($row['count'] == 0) {
//         $sampleDoctors = [
//             ['Dr. Rajesh Kumar', 'rajesh@hospital.com', '9876543210', 'Cardiology', 15, 'New Delhi', 'MBBS, MD Cardiology'],
//             ['Dr. Priya Sharma', 'priya@hospital.com', '9876543211', 'Pediatrics', 12, 'Mumbai', 'MBBS, MD Pediatrics'],
//             ['Dr. Amit Singh', 'amit@hospital.com', '9876543212', 'Orthopedics', 18, 'Bangalore', 'MBBS, MS Orthopedics'],
//             ['Dr. Sunita Patel', 'sunita@hospital.com', '9876543213', 'Gynecology', 10, 'Chennai', 'MBBS, MD Gynecology'],
//             ['Dr. Vikram Gupta', 'vikram@hospital.com', '9876543214', 'Neurology', 20, 'Kolkata', 'MBBS, DM Neurology']
//         ];
        
//         foreach ($sampleDoctors as $doctor) {
//             $stmt = mysqli_prepare($conn, "INSERT INTO doctors (doc_name, doc_email, doc_phone, doc_specia, experience, location, education, consultation_fee) VALUES (?, ?, ?, ?, ?, ?, ?, 500.00)");
//             mysqli_stmt_bind_param($stmt, "ssssiss", $doctor[0], $doctor[1], $doctor[2], $doctor[3], $doctor[4], $doctor[5], $doctor[6]);
//             mysqli_stmt_execute($stmt);
//         }
//     }
    
//     // Insert default settings
//     $settingsCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM settings");
//     $row = mysqli_fetch_assoc($settingsCount);
    
//     if ($row['count'] == 0) {
//         $defaultSettings = [
//             ['hospital_name', 'City General Hospital', 'string', 'Hospital/Clinic Name'],
//             ['hospital_address', '123 Medical Street, Healthcare City, State 12345', 'string', 'Hospital Address'],
//             ['hospital_phone', '+91-11-12345678', 'string', 'Hospital Phone Number'],
//             ['hospital_email', 'info@hospital.com', 'string', 'Hospital Email'],
//             ['appointment_duration', '30', 'number', 'Default appointment duration in minutes'],
//             ['working_hours_start', '09:00', 'string', 'Working hours start time'],
//             ['working_hours_end', '18:00', 'string', 'Working hours end time'],
//             ['booking_advance_days', '30', 'number', 'How many days in advance appointments can be booked'],
//             ['email_notifications', 'true', 'boolean', 'Enable email notifications'],
//             ['sms_notifications', 'false', 'boolean', 'Enable SMS notifications']
//         ];
        
//         foreach ($defaultSettings as $setting) {
//             $stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
//             mysqli_stmt_bind_param($stmt, "ssss", $setting[0], $setting[1], $setting[2], $setting[3]);
//             mysqli_stmt_execute($stmt);
//         }
//     }
// }

// Initialize database tables
// initializeDatabase($conn);

/**
 * Utility Functions
 */

// Sanitize input
function sanitizeInput($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Get setting value
function getSetting($key, $default = null) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    
    return $default;
}

// Update setting value
function updateSetting($key, $value, $userId = null) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?");
    mysqli_stmt_bind_param($stmt, "sis", $value, $userId, $key);
    return mysqli_stmt_execute($stmt);
}

// Create directories if they don't exist
$directories = [UPLOAD_DIR, DOCTOR_IMG_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function generateCSRFToken() {
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>