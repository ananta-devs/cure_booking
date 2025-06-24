<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include API handler with error handling
try {
    require_once 'api_handler.php';
} catch (Exception $e) {
    error_log("Error including api_handler.php: " . $e->getMessage());
    header('Location: http://localhost/sample-final/frontend/login.php?message=' . urlencode('System error. Please try again.'));
    exit;
}

// Validate required variables
if (!isset($doctor) || !isset($doctor_id)) {
    error_log("Required variables not set in doctor_show.php");
    header('Location: http://localhost/sample-final/frontend/login.php?message=' . urlencode('Profile loading error.'));
    exit;
}

// Helper function to safely output HTML
function safeOutput($value, $default = 'Not specified') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

// Helper function to safely output multi-line text
function safeMultilineOutput($value, $default = 'Not specified') {
    return nl2br(htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8'));
}

// Prepare doctor data
$doctorData = [
    'name' => $doctor['doc_name'] ?? 'Unknown Doctor',
    'specialty' => $doctor['doc_specia'] ?? 'General Practice',
    'image' => $doctor['doc_img'] ?? '',
    'gender' => $doctor['gender'] ?? 'Not specified',
    'education' => $doctor['education'] ?? 'Not specified',
    'email' => $doctor['doc_email'] ?? 'Not provided',
    'experience' => $doctor['experience'] ?? 'Not specified',
    'location' => $doctor['location'] ?? 'Not specified',
    'bio' => $doctor['bio'] ?? 'No bio available'
];

$sessionData = [
    'doctor_name' => $_SESSION['doctor_name'] ?? 'Doctor',
    'doctor_id' => $_SESSION['doctor_id'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. <?= safeOutput($doctorData['name']); ?> - Profile</title>
    
    <!-- External Resources -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <header class="header">
            <h1><i class="fas fa-user-md"></i> Doctor Profile</h1>
            <p>Works at CureBooking</p>
            <div class="session-info">
                <small>Logged in as: <?= safeOutput($sessionData['doctor_name']); ?></small>
            </div>
        </header>

        <main class="main-content">
            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Doctor Card -->
                <div class="doctor-card">
                    <div class="doctor-image" onclick="toggleImageUpload()">
                        <?php if (!empty($doctorData['image'])): ?>
                            <img src="http://localhost/adminhub/manage-doctors/uploads/<?= safeOutput($doctorData['image']); ?>" 
                                 alt="Dr. <?= safeOutput($doctorData['name']); ?>" 
                                 id="doctor-image">
                        <?php else: ?>
                            <i class="fas fa-user-md" id="doctor-icon"></i>
                        <?php endif; ?>
                        <div class="image-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    
                    <!-- Image Upload Area -->
                    <div class="image-upload-area" id="imageUploadArea">
                        <form id="imageUploadForm" enctype="multipart/form-data">
                            <div class="file-input-wrapper">
                                <input type="file" 
                                       id="doctorImageInput" 
                                       class="file-input" 
                                       accept="image/*" 
                                       onchange="handleImageSelect(this)">
                            </div>
                        </form>
                        
                        <div class="upload-progress" id="uploadProgress">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                        </div>
                        
                        <div class="controlBtn">
                            <button class="save-btn" onclick="uploadImage()" id="uploadBtn" disabled>
                                <i class="fas fa-upload"></i> Upload
                            </button>
                            <button class="cancel-btn" onclick="cancelImageUpload()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                    
                    <h2><?= safeOutput($doctorData['name']); ?></h2>
                    <p style="color: #2196F3; font-weight: bold; margin-top: 10px;">
                        <?= safeOutput($doctorData['specialty']); ?>
                    </p>
                </div>

                <!-- Quick Info Section -->
                <div class="section-card">
                    <div class="section-title">
                        <div><i class="fas fa-info-circle"> Quick Info</i></div>
                    </div>
                    
                    <?php
                    $infoItems = [
                        ['icon' => 'fas fa-venus-mars', 'label' => 'Gender', 'value' => $doctorData['gender']],
                        ['icon' => 'fas fa-graduation-cap', 'label' => 'Education & Qualifications', 'value' => $doctorData['education'], 'multiline' => true],
                        ['icon' => 'fas fa-envelope', 'label' => 'Email', 'value' => $doctorData['email']],
                        ['icon' => 'fas fa-clock', 'label' => 'Experience', 'value' => $doctorData['experience']],
                        ['icon' => 'fas fa-map-marker-alt', 'label' => 'Location', 'value' => $doctorData['location']],
                        ['icon' => 'fas fa-user', 'label' => 'About', 'value' => $doctorData['bio'], 'multiline' => true]
                    ];
                    
                    foreach ($infoItems as $item): ?>
                        <div class="info-item">
                            <i class="<?= $item['icon']; ?>"></i>
                            <div>
                                <strong><?= $item['label']; ?></strong><br>
                                <?= isset($item['multiline']) && $item['multiline'] 
                                    ? safeMultilineOutput($item['value']) 
                                    : safeOutput($item['value']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Action Items -->
                    <div class="info-item clickable" onclick="openPasswordModal()">
                        <i class="fa-solid fa-key"></i>
                        <div><strong>Change Password</strong></div>
                    </div>

                    <div class="info-item clickable danger">
                        <a href="api_handler.php?action=logout" style="text-decoration: none; color: rgba(240, 6, 6, 0.95);">
                            <div>
                                <i class="fa-solid fa-right-from-bracket" style="color: rgba(240, 6, 6, 0.95);"></i>
                                <strong>Sign Out</strong>
                            </div>
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Main Details Section -->
            <section class="details-section">
                <!-- Today's Appointments -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="fa-regular fa-calendar-check"> Today's Appointments</i>
                        <div class="date-display" id="currentDate"></div>
                    </div>
                    
                    <div class="booking-stats">
                        <div class="stat-card">
                            <h3>Today's Bookings</h3>
                            <div class="stat-number" id="todayBookings">Loading...</div>
                        </div>
                        <div class="stat-card">
                            <h3>Tomorrow's Bookings</h3>
                            <div class="stat-number" id="tomorrowBookings">Loading...</div>
                        </div>
                        <div class="stat-card">
                            <h3>Upcoming Today</h3>
                            <div class="stat-number" id="upcomingToday">Loading...</div>
                        </div>
                    </div>
                </div>

                <!-- Availability Schedule -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-clock"> Availability Schedule</i>
                        <div class="refresh-btn" onclick="refreshAvailability()" title="Refresh Schedule">
                            <i class="fas fa-sync-alt" id="refreshIcon"></i>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div class="availability-loading" id="availabilityLoading">
                        <div class="loading-spinner"></div>
                        <p>Loading availability schedule...</p>
                    </div>
                    
                    <!-- Availability Grid -->
                    <div class="availability-grid" id="availabilityGrid">
                        <!-- Content populated by JavaScript -->
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Password Change Modal -->
    <div class="modal-overlay" id="passwordModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-key"></i>
                    Change Password
                </div>
                <button class="modal-close" onclick="closePasswordModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="alert" id="passwordAlert"></div>

            <form id="passwordForm">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="new_password" required minlength="6">
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar" id="strengthBar"></div>
                        <span id="strengthText"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirm_password" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closePasswordModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary" id="changePasswordBtn">
                        <i class="fas fa-key"></i>
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Set doctor ID for JavaScript
        const DOCTOR_ID = <?= (int)$sessionData['doctor_id']; ?>;
    </script>
    <script src="script.js"></script>
</body>
</html>