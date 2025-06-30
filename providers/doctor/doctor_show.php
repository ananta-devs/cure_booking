<?php
    session_start();

    // Check if user is logged in (for both doctor and clinic)
    if (!isset($_SESSION['logged_in']) && !isset($_SESSION['doctor_id'])) {
        header('Location: ../login.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - CureBooking</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="profile-header">
            <div class="doctor-basic-info">
                <div class="doctor-image">
                    <img id="doctorImg" src="" alt="Doctor Image" />
                </div>
                <div class="doctor-details">
                    <h1 id="doctorName">Loading...</h1>
                    <p class="specialization" id="doctorSpecialization">Loading...</p>
                    
                    <!-- Appointment counts section -->
                    <div class="appointment-counts">
                        <div class="appointment-count-item">
                            <div class="count-number" id="todayCount">-</div>
                            <div class="count-label">Today's Appointments</div>
                        </div>
                        <div class="appointment-count-item">
                            <div class="count-number" id="tomorrowCount">-</div>
                            <div class="count-label">Tomorrow's Appointments</div>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <button id="changePasswordBtn" class="change-password-btn" title="Change Password">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <circle cx="12" cy="16" r="1"></circle>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Change Password
                    </button>
                </div>
            </div>
        </header>

        <main class="profile-content">
            <section class="doctor-info">
                <div class="info-card">
                    <h2>Experience</h2>
                    <p id="doctorExperience">Loading...</p>
                </div>
                
                <div class="info-card">
                    <h2>Location</h2>
                    <p id="doctorLocation">Loading...</p>
                </div>
                
                <div class="info-card">
                    <h2>Fees</h2>
                    <p id="doctorFees">Loading...</p>
                </div>
                
                <div class="info-card">
                    <h2>About</h2>
                    <p id="doctorBio">Loading...</p>
                </div>
                
                <div class="info-card">
                    <h2>Education</h2>
                    <p id="doctorEducation">Loading...</p>
                </div>
                
                <div class="info-card">
                    <h2>Contact Information</h2>
                    <p id="doctorEmail">Loading...</p>
                </div>

                <div class="info-card">
                    <a href="http://localhost/cure_booking/providers/logout.php" style="text-decoration:none;"><h2>Log out</h2></a>
                </div>
            </section>

            <section class="availability-section">
                <h2>Clinic Availability</h2>
                <div id="clinicList" class="clinic-list">
                    <div class="loading">Loading clinics...</div>
                </div>
                  
                <div id="availabilityDetails" class="availability-details" style="display: none;">
                    <div class="availability-header">
                        <h3 id="selectedClinicName">Clinic Name</h3>
                        <button id="closeAvailabilityBtn" class="close-button" title="Close availability schedule">×</button>
                    </div>
                    <div id="availabilitySchedule" class="weekly-schedule">
                        <!-- Schedule will be populated here -->
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script src="script.js"></script>
      
</body>
</html>