<?php 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    try {
        require_once 'api_handler.php';
    } catch (Exception $e) {
        error_log("Error including api_handler.php: " . $e->getMessage());
        header('Location: http://localhost/sample-final/frontend/login.php?message=' . urlencode('System error. Please try again.'));
        exit;
    }

    if (!isset($doctor) || !isset($doctor_id) || !isset($availability)) {
        error_log("Required variables not set in doctor_show.php");
        header('Location: http://localhost/sample-final/frontend/login.php?message=' . urlencode('Profile loading error.'));
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dr. <?php echo htmlspecialchars($doctor['doc_name'] ?? 'Unknown'); ?> - Profile</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-user-md"></i> Doctor Profile</h1>
                <p>Works at CureBooking</p>
                <div class="session-info">
                    <small>Logged in as: <?php echo htmlspecialchars($_SESSION['doctor_name'] ?? 'Doctor'); ?></small>
                </div>
            </div>

            <div class="main-content">
                <div class="sidebar">
                    <div class="doctor-card">
                        <div class="doctor-image" onclick="toggleImageUpload()">
                            <?php if (!empty($doctor['doc_img'])): ?>
                                <img src="http://localhost/adminhub/manage-doctors/uploads/<?php echo htmlspecialchars($doctor['doc_img']); ?>" alt="Dr. <?php echo htmlspecialchars($doctor['doc_name']); ?>" id="doctor-image">
                            <?php else: ?>
                                <i class="fas fa-user-md" id="doctor-icon"></i>
                            <?php endif; ?>
                            <div class="image-overlay">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        
                        <div class="image-upload-area" id="imageUploadArea">
                            <form id="imageUploadForm" enctype="multipart/form-data">
                                <div class="file-input-wrapper">
                                    <input type="file" id="doctorImageInput" class="file-input" accept="image/*" onchange="handleImageSelect(this)">
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
                        
                        <h2><?php echo htmlspecialchars($doctor['doc_name'] ?? 'Unknown Doctor'); ?></h2>
                        <p style="color: #2196F3; font-weight: bold; margin-top: 10px;">
                            <?php echo htmlspecialchars($doctor['doc_specia'] ?? 'General Practice'); ?>
                        </p>
                    </div>

                    <div class="section-card">
                        <div class="section-title">
                            <div>
                                <i class="fas fa-info-circle"> Quick Info</i> 
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-venus-mars"></i>
                            <div>
                                <strong>Gender</strong><br>
                                <?php echo htmlspecialchars($doctor['gender'] ?? 'Not specified'); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-graduation-cap"></i> 
                            <div>
                                <strong>Education & Qualifications</strong><br>
                                <?php echo nl2br(htmlspecialchars($doctor['education'] ?? 'Not specified')); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email</strong><br>
                                <?php echo htmlspecialchars($doctor['doc_email'] ?? 'Not provided'); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Experience</strong><br>
                                <?php echo htmlspecialchars($doctor['experience'] ?? 'Not specified'); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Location</strong><br>
                                <?php echo htmlspecialchars($doctor['location'] ?? 'Not specified'); ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <div>
                                <strong>About</strong><br>
                                <?php echo nl2br(htmlspecialchars($doctor['bio'] ?? 'No bio available')); ?>
                            </div>
                        </div>

                        <div class="info-item clickable" onclick="openPasswordModal()">
                            <i class="fa-solid fa-key"></i>
                            <div>
                                <strong>Change Password</strong>   
                            </div>
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
                </div>

                <div class="details-section">
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

                    <div class="section-card">
                        <div class="section-title">
                            <i class="fas fa-clock"> Availability Schedule</i> 
                        </div>
                        <div class="availability-grid">
                            <?php if (!empty($availability) && is_array($availability)): ?>
                                <?php foreach ($availability as $day => $slots): ?>
                                    <div class="day-card">
                                        <div class="day-name"><?php echo ucfirst(htmlspecialchars($day)); ?></div>
                                        <?php if (is_array($slots)): ?>
                                            <?php foreach ($slots as $time => $available): ?>
                                                <span class="time-slot <?php echo $available ? 'time-available' : 'time-unavailable'; ?>">
                                                    <?php echo htmlspecialchars(str_replace('-', ' - ', $time)); ?>
                                                    <i class="fas fa-<?php echo $available ? 'check' : 'times'; ?>"></i>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-availability">
                                    <p>No availability schedule configured.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
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

        <script>
            const DOCTOR_ID = <?php echo $_SESSION['doctor_id']; ?>;
            let selectedFile = null;

            document.addEventListener("DOMContentLoaded", function () {
                const currentDate = new Date().toLocaleDateString("en-IN", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                });
                document.getElementById("currentDate").textContent = currentDate;
                loadBookingStats();
                initializePasswordForm();
            });

            function openPasswordModal() {
                document.getElementById('passwordModal').classList.add('active');
                document.body.style.overflow = 'hidden';
                document.getElementById('currentPassword').focus();
            }

            function closePasswordModal() {
                document.getElementById('passwordModal').classList.remove('active');
                document.body.style.overflow = '';
                resetPasswordForm();
            }

            function resetPasswordForm() {
                document.getElementById('passwordForm').reset();
                document.getElementById('passwordAlert').style.display = 'none';
                document.getElementById('changePasswordBtn').disabled = false;
            }

            function initializePasswordForm() {
                const newPasswordInput = document.getElementById('newPassword');
                const confirmPasswordInput = document.getElementById('confirmPassword');

                confirmPasswordInput.addEventListener('input', function() {
                    const newPassword = newPasswordInput.value;
                    const confirmPassword = this.value;
                    
                    if (confirmPassword && newPassword !== confirmPassword) {
                        this.style.borderColor = '#f44336';
                    } else {
                        this.style.borderColor = '#e0e0e0';
                    }
                });

                document.getElementById('passwordForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    changePassword();
                });
            }

            function changePassword() {
                const form = document.getElementById('passwordForm');
                const formData = new FormData(form);
                formData.append('action', 'change_password');
                formData.append('doctor_id', DOCTOR_ID);

                const submitBtn = document.getElementById('changePasswordBtn');
                const originalContent = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="loading-spinner"></div> Changing...';

                fetch('api_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const alert = document.getElementById('passwordAlert');
                    alert.textContent = data.message;
                    alert.className = `alert ${data.success ? 'success' : 'error'}`;
                    alert.style.display = 'block';

                    if (data.success) {
                        setTimeout(() => {
                            closePasswordModal();
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const alert = document.getElementById('passwordAlert');
                    alert.textContent = 'An error occurred. Please try again.';
                    alert.className = 'alert error';
                    alert.style.display = 'block';
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalContent;
                });
            }

            document.getElementById('passwordModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closePasswordModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('passwordModal').classList.contains('active')) {
                    closePasswordModal();
                }
            });

            function toggleImageUpload() {
                document.getElementById("doctorImageInput").click();
            }

            function handleImageSelect(input) {
                if (input.files && input.files[0]) {
                    selectedFile = input.files[0];
                    const uploadBtn = document.getElementById("uploadBtn");
                    const uploadArea = document.getElementById("imageUploadArea");
                    const fileInputWrapper = document.querySelector(".file-input-wrapper");

                    uploadArea.classList.add("active");
                    fileInputWrapper.style.display = "none";
                    uploadBtn.disabled = false;
                }
            }

            function uploadImage() {
                if (!selectedFile) return;

                const formData = new FormData();
                formData.append("doctor_image", selectedFile);
                formData.append("doctor_id", DOCTOR_ID);
                formData.append("action", "upload_image");

                const uploadBtn = document.getElementById("uploadBtn");
                const progress = document.getElementById("uploadProgress");
                const progressFill = document.getElementById("progressFill");

                uploadBtn.disabled = true;
                progress.style.display = "block";

                let progressValue = 0;
                const progressInterval = setInterval(() => {
                    progressValue += 10;
                    progressFill.style.width = progressValue + "%";
                    if (progressValue >= 90) clearInterval(progressInterval);
                }, 100);

                fetch("api_handler.php", {
                    method: "POST",
                    body: formData,
                })
                .then((response) => response.json())
                .then((data) => {
                    clearInterval(progressInterval);
                    progressFill.style.width = "100%";

                    setTimeout(() => {
                        if (data.success) {
                            const doctorImage = document.getElementById("doctor-image");
                            const doctorIcon = document.getElementById("doctor-icon");
                            const imageContainer = document.querySelector(".doctor-image");

                            if (doctorImage) {
                                doctorImage.src = data.image_path;
                            } else {
                                if (doctorIcon) doctorIcon.remove();
                                const newImg = document.createElement("img");
                                newImg.id = "doctor-image";
                                newImg.src = data.image_path;
                                newImg.alt = "Doctor Image";
                                imageContainer.insertBefore(newImg, imageContainer.firstChild);
                            }

                            setTimeout(() => cancelImageUpload(), 2000);
                        } else {
                            uploadBtn.disabled = false;
                        }

                        progress.style.display = "none";
                        progressFill.style.width = "0%";
                    }, 500);
                })
                .catch((error) => {
                    clearInterval(progressInterval);
                    console.error("Error:", error);
                    uploadBtn.disabled = false;
                    progress.style.display = "none";
                    progressFill.style.width = "0%";
                });
            }

            function cancelImageUpload() {
                const uploadArea = document.getElementById("imageUploadArea");
                const uploadBtn = document.getElementById("uploadBtn");
                const fileInput = document.getElementById("doctorImageInput");
                const fileInputWrapper = document.querySelector(".file-input-wrapper");

                uploadArea.classList.remove("active");
                fileInputWrapper.style.display = "block";
                uploadBtn.disabled = true;
                fileInput.value = "";
                selectedFile = null;
            }

            function loadBookingStats() {
                fetch("api_handler.php?get_stats=1")
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            document.getElementById("todayBookings").textContent = data.today_bookings;
                            document.getElementById("tomorrowBookings").textContent = data.tomorrow_bookings;
                            document.getElementById("upcomingToday").textContent = data.upcoming_today;
                        } else {
                            console.error("Error loading booking stats:", data.message);
                        }
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                    });
            }

            document.addEventListener("click", function (event) {
                const uploadArea = document.getElementById("imageUploadArea");
                const doctorImage = document.querySelector(".doctor-image");

                if (uploadArea.classList.contains("active") && !uploadArea.contains(event.target) && !doctorImage.contains(event.target)) {
                    cancelImageUpload();
                }
            });

            const observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = "1";
                            entry.target.style.transform = "translateY(0)";
                        }
                    });
                },
                { threshold: 0.1, rootMargin: "0px 0px -50px 0px" }
            );

            document.querySelectorAll(".section-card, .doctor-card").forEach((card) => {
                card.style.opacity = "0";
                card.style.transform = "translateY(30px)";
                card.style.transition = "opacity 0.6s ease, transform 0.6s ease";
                observer.observe(card);
            });
        </script>
    </body>
</html>