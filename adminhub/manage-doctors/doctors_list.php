<?php
    session_start();
    if (!isset($_SESSION['adm_id'])) {
        header("Location: http://localhost/adminhub/login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors List - Cure Booking</title>
    <link rel="stylesheet" href="style_doctor.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php
        // Include database connection
        include '../include/database_connection.php';

        // SIDEBAR
        include '../include/sidebar.php';
    ?>

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
		<?php
			include '../include/top-header.php';
		?>
        <main>
            <div class="container">
                <header>
                    <h1>Doctors Management</h1>
                </header>
                
                <div id="messageContainer"></div>
                    
                <div class="search-box">
                    <form class="search-form" id="searchForm">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search doctors by name or specialty...">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
                <div class="doctorsTableContainer">
                    <div id="doctorsTableContainer">
                        <!-- Table will be loaded here via AJAX -->
                        Loading doctors list...
                    </div>
                </div>
            </div>
                
            <!-- View Doctor Modal -->
            <div id="viewModal" class="modal">
                <div class="modal-content" style="max-width: 800px;">
                    <div class="modal-header">
                        <h2 class="modal-title">Doctor Information</h2>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body" id="viewModalBody">
                        <!-- Doctor details will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Edit Doctor Modal -->
            <div id="editModal" class="modal">
                <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
                    <div class="modal-header">
                        <h2 class="modal-title">Edit Doctor Information</h2>
                        <span class="close">&times;</span>
                    </div>
                    <form id="editDoctorForm">
                        <input type="hidden" id="edit_doctor_id" name="doc_id">
                        <input type="hidden" id="existing_img" name="existing_img">
                            
                        <!-- Basic Information Section -->
                        <div style="background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                            <h3 style="margin-top: 0;">Basic Information</h3>
                            
                            <div class="form-group">
                                <label for="edit_doc_name" class="form-label">Doctor Name</label>
                                <input type="text" class="form-control" id="edit_doc_name" name="doc_name" required>
                            </div>
                                
                            <div class="form-group">
                                <label for="edit_doc_specia" class="form-label">Specialty</label>
                                <input type="text" class="form-control" id="edit_doc_specia" name="doc_specia" required>
                            </div>
                                
                            <div class="form-group">
                                <label for="edit_doc_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_doc_email" name="doc_email">
                            </div>
                                
                            <div class="form-group">
                                <label for="edit_fees" class="form-label">Consultation Fee</label>
                                <input type="number" min="0" step="0.01" class="form-control" id="edit_fees" name="fees" required>
                            </div>
                                
                            <div class="form-group">
                                <label for="edit_gender" class="form-label">Gender</label>
                                <select class="form-control" id="edit_gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                                
                            <div class="form-group">
                                <label for="edit_experience" class="form-label">Experience (Years)</label>
                                <input type="number" min="0" class="form-control" id="edit_experience" name="experience">
                            </div>
                                
                            <div class="form-group">
                                <label for="edit_location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="edit_location" name="location">
                            </div>
                                
                            <div class="form-group">
                                <label for="edit_education" class="form-label">Education</label>
                                <input type="text" class="form-control" id="edit_education" name="education">
                            </div>
                                
                            <div class="form-group">
                                <label for="edit_bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="edit_bio" name="bio"></textarea>
                            </div>
                        </div>

                        <!-- Clinic Assignments Section -->
                        <div style="background: #f0f8ff; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                            <h3 style="margin-top: 0;">Clinic Assignments & Availability</h3>
                            <div id="clinic_assignments_container">
                                <!-- Clinic assignments will be loaded here -->
                            </div>
                            <button type="button" id="add_clinic_assignment" class="btn btn-secondary" style="margin-top: 10px;">
                                <i class='bx bx-plus'></i> Add Clinic Assignment
                            </button>
                        </div>

                        <!-- Image Section -->
                        <div style="background: #fff5f5; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                            <h3 style="margin-top: 0;">Profile Image</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Current Image</label>
                                <div id="current_image_container"></div>
                            </div>
                                
                            <div class="form-group">
                                <label for="edit_doc_img" class="form-label">Update Image</label>
                                <input type="file" class="form-control" id="edit_doc_img" name="doc_img" accept="image/*">
                                <small>Leave empty to keep current image</small>
                            </div>
                        </div>

                        <div class="form-group" style="text-align: right; padding-top: 15px; border-top: 1px solid #ddd;">
                            <button type="button" class="btn" onclick="editModal.style.display='none'" style="margin-right: 10px;">Cancel</button>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
                            
            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Confirm Deletion</h2>
                        <span class="close">&times;</span>
                    </div>
                    <p>Are you sure you want to delete this doctor's information? This action cannot be undone.</p>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                        <button id="cancelDelete" class="btn">Cancel</button>
                        <button id="confirmDelete" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </div>
        </main>
    </section>
    <!-- CONTENT -->
    <script src="doctor_script.js"></script>

</body>
</html>