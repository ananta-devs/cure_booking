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

<script>
    // Global variables
    let doctorIdToDelete = null;
    let availableClinics = [];

    // DOM Elements
    const doctorsTableContainer = document.getElementById("doctorsTableContainer");
    const messageContainer = document.getElementById("messageContainer");
    const searchForm = document.getElementById("searchForm");
    const searchInput = document.getElementById("searchInput");
    const viewModal = document.getElementById("viewModal");
    const editModal = document.getElementById("editModal");
    const deleteModal = document.getElementById("deleteModal");
    const editDoctorForm = document.getElementById("editDoctorForm");

    // Initialize page
    document.addEventListener("DOMContentLoaded", function () {
        loadDoctors();
        setupEventListeners();
        loadAvailableClinics();
    });

    // Setup event listeners
    function setupEventListeners() {
        // Search form
        searchForm.addEventListener("submit", function (e) {
            e.preventDefault();
            loadDoctors();
        });

        // Close modals
        document.querySelectorAll(".close").forEach(function (closeBtn) {
            closeBtn.addEventListener("click", function () {
                viewModal.style.display = "none";
                editModal.style.display = "none";
                deleteModal.style.display = "none";
            });
        });

        // Edit doctor form submission
        editDoctorForm.addEventListener("submit", function (e) {
            e.preventDefault();
            updateDoctor();
        });

        // Cancel delete
        document.getElementById("cancelDelete").addEventListener("click", function () {
            deleteModal.style.display = "none";
        });

        // Confirm delete
        document.getElementById("confirmDelete").addEventListener("click", function () {
            if (doctorIdToDelete) {
                deleteDoctor(doctorIdToDelete);
            } else {
                showMessage("error", "Error: Doctor ID not found");
            }
        });

        // Close modals when clicking outside
        window.addEventListener("click", function (e) {
            if (e.target === viewModal) {
                viewModal.style.display = "none";
            }
            if (e.target === editModal) {
                editModal.style.display = "none";
            }
            if (e.target === deleteModal) {
                deleteModal.style.display = "none";
            }
        });
    }

    // Load doctors list
    function loadDoctors() {
        doctorsTableContainer.innerHTML = '<div class="loading">Loading doctors list...</div>';

        fetch('get_doctor.php?action=list')
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    renderDoctorsTable(data.doctors);
                } else {
                    showMessage("error", data.message || "Failed to load doctors list");
                    doctorsTableContainer.innerHTML = "<p>No doctors found.</p>";
                }
            })
            .catch((error) => {
                console.error("Error loading doctors:", error);
                showMessage("error", "An error occurred while loading the doctors list");
                doctorsTableContainer.innerHTML = "<p>Error loading doctors.</p>";
            });
    }

    // Render doctors table
    function renderDoctorsTable(doctors) {
        if (doctors.length === 0) {
            doctorsTableContainer.innerHTML = "<p>No doctors found.</p>";
            return;
        }

        let tableHtml = `
            <table class="data-table">
            <thead>
                <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Specialty</th>
                <th>Fee</th>
                <th>Experience</th>
                <th>Email</th>
                <th>Clinics</th>
                <th>Actions</th>
                </tr>
            </thead>
            <tbody>
        `;

        doctors.forEach((doctor) => {
            const imageUrl = doctor.image_url || "assets/img/default-doctor.png";
            const clinicNames = doctor.clinic_names || "No clinics assigned";
            
            tableHtml += `
            <tr>
                <td><img src="${imageUrl}" alt="${doctor.doc_name}" class="doctor-img" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;"></td>
                <td>${doctor.doc_name}</td>
                <td>${doctor.doc_specia}</td>
                <td>$${parseFloat(doctor.fees || 0).toFixed(2)}</td>
                <td>${doctor.experience || 0} years</td>
                <td>${doctor.doc_email || "N/A"}</td>
                <td>${clinicNames}</td>
                <td>
                    <div class="actions">
                        <button class="btn btn-info view-btn" data-id="${doctor.doc_id}" title="View Details">
                            <i class='bx bx-show'></i>
                        </button>
                        <button class="btn btn-primary edit-btn" data-id="${doctor.doc_id}" title="Edit">
                            <i class='bx bx-edit'></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="${doctor.doc_id}" title="Delete">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        });

        tableHtml += `
            </tbody>
            </table>
        `;

        doctorsTableContainer.innerHTML = tableHtml;

        // Add event listeners to action buttons
        document.querySelectorAll(".view-btn").forEach((btn) => {
            btn.addEventListener("click", function () {
                openViewModal(this.getAttribute("data-id"));
            });
        });

        document.querySelectorAll(".edit-btn").forEach((btn) => {
            btn.addEventListener("click", function () {
                openEditModal(this.getAttribute("data-id"));
            });
        });

        document.querySelectorAll(".delete-btn").forEach((btn) => {
            btn.addEventListener("click", function () {
                openDeleteModal(this.getAttribute("data-id"));
            });
        });
    }

    // Open view modal
    function openViewModal(doctorId) {
        fetch(`get_doctor.php?action=get&id=${doctorId}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    const doctor = data.doctor;
                    const clinicAssignments = data.clinic_assignments || [];
                    
                    let viewHtml = `
                        <div class="doctor-view-container">
                            <div class="doctor-basic-info">
                                <div class="doctor-image-section">
                                    ${doctor.has_image && doctor.doc_img ? 
                                        `<img src="${doctor.image_url}" alt="${doctor.doc_name}" class="doctor-profile-img" style="width: 150px; height: 150px; border-radius: 10px; object-fit: cover;">` : 
                                        '<div class="no-image">No Image Available</div>'
                                    }
                                </div>
                                <div class="doctor-details-section">
                                    <h3>${doctor.doc_name}</h3>
                                    <p><strong>Specialty:</strong> ${doctor.doc_specia}</p>
                                    <p><strong>Email:</strong> ${doctor.doc_email || 'N/A'}</p>
                                    <p><strong>Consultation Fee:</strong> $${parseFloat(doctor.fees || 0).toFixed(2)}</p>
                                    <p><strong>Gender:</strong> ${doctor.gender ? doctor.gender.charAt(0).toUpperCase() + doctor.gender.slice(1) : 'N/A'}</p>
                                    <p><strong>Experience:</strong> ${doctor.experience || 0} years</p>
                                    <p><strong>Location:</strong> ${doctor.location || 'N/A'}</p>
                                    <p><strong>Education:</strong> ${doctor.education || 'N/A'}</p>
                                </div>
                            </div>
                            
                            ${doctor.bio ? `
                                <div class="doctor-bio-section">
                                    <h4>Biography</h4>
                                    <p>${doctor.bio}</p>
                                </div>
                            ` : ''}
                            
                            <div class="clinic-assignments-section">
                                <h4>Clinic Assignments</h4>
                                ${clinicAssignments.length > 0 ? renderClinicAssignments(clinicAssignments) : '<p>No clinic assignments found.</p>'}
                            </div>
                        </div>
                    `;
                    
                    document.getElementById("viewModalBody").innerHTML = viewHtml;
                    viewModal.style.display = "block";
                } else {
                    showMessage("error", data.message || "Failed to load doctor information");
                }
            })
            .catch((error) => {
                console.error("Error loading doctor details:", error);
                showMessage("error", "An error occurred while loading doctor details");
            });
    }

    // Render clinic assignments
    function renderClinicAssignments(assignments) {
        let html = '';
        const timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
        const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        assignments.forEach((assignment) => {
            html += `
                <div class="clinic-assignment">
                    <h5>${assignment.clinic_name}</h5>
                    
                    <div class="availability-schedule">
                        <h6>Availability Schedule:</h6>
                        <table class="schedule-table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr style="background-color: #f5f5f5;">
                                    <th style="border: 1px solid #ddd; padding: 8px;">Day</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">11:00-13:00</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">14:00-16:00</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">17:00-19:00</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            weekDays.forEach((day) => {
                html += `
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">${day.charAt(0).toUpperCase() + day.slice(1)}</td>
                `;
                
                timeSlots.forEach((slot) => {
                    const isAvailable = assignment.availability_schedule && 
                                      assignment.availability_schedule[day] && 
                                      assignment.availability_schedule[day][slot];
                    
                    html += `
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                            ${isAvailable ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Not Available</span>'}
                        </td>
                    `;
                });
                
                html += '</tr>';
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
                <hr style="margin: 20px 0;">
            `;
        });
        
        return html;
    }
    function loadAvailableClinics() {
        fetch('../manage-clinics/get_clinics.php?action=list') // You may need to create this endpoint or adjust the URL
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    availableClinics = data.clinics || [];
                }
            })
            .catch(error => {
                console.error('Error loading clinics:', error);
                // Fallback - you can hardcode some clinics or handle this differently
                availableClinics = [];
            });
    }
    // Open edit modal
    function openEditModal(doctorId) {
        fetch(`get_doctor.php?action=get&id=${doctorId}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    const doctor = data.doctor;
                    const clinicAssignments = data.clinic_assignments || [];

                    // Populate basic form fields (existing code)
                    document.getElementById("edit_doctor_id").value = doctor.doc_id;
                    document.getElementById("edit_doc_name").value = doctor.doc_name || "";
                    document.getElementById("edit_doc_specia").value = doctor.doc_specia || "";
                    document.getElementById("edit_doc_email").value = doctor.doc_email || "";
                    document.getElementById("edit_fees").value = doctor.fees || "";
                    document.getElementById("edit_gender").value = doctor.gender || "";
                    document.getElementById("edit_experience").value = doctor.experience || "";
                    document.getElementById("edit_location").value = doctor.location || "";
                    document.getElementById("edit_education").value = doctor.education || "";
                    document.getElementById("edit_bio").value = doctor.bio || "";
                    document.getElementById("existing_img").value = doctor.doc_img || "";

                    // Show current image (existing code)
                    const imageContainer = document.getElementById("current_image_container");
                    if (doctor.has_image && doctor.doc_img) {
                        imageContainer.innerHTML = `<img src="${doctor.image_url}" alt="${doctor.doc_name}" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
                    } else {
                        imageContainer.innerHTML = "<p>No image available</p>";
                    }

                    // Load clinic assignments
                    loadClinicAssignments(clinicAssignments);

                    // Display modal
                    editModal.style.display = "block";
                } else {
                    showMessage("error", data.message || "Failed to load doctor information");
                }
            })
            .catch((error) => {
                console.error("Error loading doctor details:", error);
                showMessage("error", "An error occurred while loading doctor details");
            });
    }

    function loadClinicAssignments(assignments) {
        const container = document.getElementById('clinic_assignments_container');
        container.innerHTML = '';
        
        assignments.forEach((assignment, index) => {
            addClinicAssignmentRow(assignment, index);
        });
        
        // Add event listener for adding new clinic assignments
        document.getElementById('add_clinic_assignment').onclick = function() {
            addClinicAssignmentRow(null, assignments.length);
        };
    }

    function addClinicAssignmentRow(assignment = null, index = 0) {
        const container = document.getElementById('clinic_assignments_container');
        
        // Create clinic selection dropdown
        let clinicOptions = '<option value="">Select Clinic</option>';
        if (availableClinics.length > 0) {
            availableClinics.forEach(clinic => {
                const selected = assignment && assignment.clinic_id == clinic.clinic_id ? 'selected' : '';
                clinicOptions += `<option value="${clinic.clinic_id}" ${selected}>${clinic.clinic_name}</option>`;
            });
        } else {
            // Fallback if clinic data not available
            if (assignment) {
                clinicOptions += `<option value="${assignment.clinic_id}" selected>${assignment.clinic_name}</option>`;
            }
        }
        
        const timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
        const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Create availability schedule checkboxes
        let scheduleHtml = `
            <table class="schedule-table" style="width: 100%; border-collapse: collapse; margin: 10px 0;">
                <thead>
                    <tr style="background-color: #f5f5f5;">
                        <th style="border: 1px solid #ddd; padding: 8px;">Day</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">11:00-13:00</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">14:00-16:00</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">17:00-19:00</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        weekDays.forEach(day => {
            scheduleHtml += `
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;">${day.charAt(0).toUpperCase() + day.slice(1)}</td>
            `;
            
            timeSlots.forEach(slot => {
                const isChecked = assignment && 
                                assignment.availability_schedule && 
                                assignment.availability_schedule[day] && 
                                assignment.availability_schedule[day][slot] ? 'checked' : '';
                
                scheduleHtml += `
                    <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                        <input type="checkbox" 
                            name="clinic_assignments[${index}][availability][${day}][${slot}]" 
                            value="1" ${isChecked}>
                    </td>
                `;
            });
            
            scheduleHtml += '</tr>';
        });
        
        scheduleHtml += '</tbody></table>';
        
        const assignmentHtml = `
            <div class="clinic-assignment-row" style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4>Clinic Assignment ${index + 1}</h4>
                    <button type="button" class="btn btn-danger btn-sm remove-clinic-assignment">Remove</button>
                </div>
                
                <div class="form-group">
                    <label>Select Clinic:</label>
                    <select name="clinic_assignments[${index}][clinic_id]" class="form-control" required>
                        ${clinicOptions}
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Availability Schedule:</label>
                    ${scheduleHtml}
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', assignmentHtml);
        
        // Add event listener for remove button
        const removeButtons = container.querySelectorAll('.remove-clinic-assignment');
        removeButtons[removeButtons.length - 1].addEventListener('click', function() {
            this.closest('.clinic-assignment-row').remove();
            reindexClinicAssignments();
        });
    }

    function reindexClinicAssignments() {
        const container = document.getElementById('clinic_assignments_container');
        const rows = container.querySelectorAll('.clinic-assignment-row');
        
        rows.forEach((row, index) => {
            // Update header
            row.querySelector('h4').textContent = `Clinic Assignment ${index + 1}`;
            
            // Update form field names
            const clinicSelect = row.querySelector('select');
            clinicSelect.name = `clinic_assignments[${index}][clinic_id]`;
            
            const checkboxes = row.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                const namePattern = /clinic_assignments\[\d+\]/;
                checkbox.name = checkbox.name.replace(namePattern, `clinic_assignments[${index}]`);
            });
        });
    }
    // Open delete modal
    function openDeleteModal(doctorId) {
        doctorIdToDelete = doctorId;
        deleteModal.style.display = "block";
    }

    // Update doctor
    function updateDoctor() {
        const formData = new FormData(editDoctorForm);
        formData.append("action", "update");
        
        // Collect clinic assignments data
        const clinicAssignments = [];
        const container = document.getElementById('clinic_assignments_container');
        const assignmentRows = container.querySelectorAll('.clinic-assignment-row');
        
        assignmentRows.forEach((row, index) => {
            const clinicId = row.querySelector(`select[name="clinic_assignments[${index}][clinic_id]"]`).value;
            
            if (clinicId) {
                const availability = {};
                const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                const timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
                
                weekDays.forEach(day => {
                    availability[day] = {};
                    timeSlots.forEach(slot => {
                        const checkbox = row.querySelector(`input[name="clinic_assignments[${index}][availability][${day}][${slot}]"]`);
                        availability[day][slot] = checkbox && checkbox.checked ? true : false;
                    });
                });
                
                clinicAssignments.push({
                    clinic_id: clinicId,
                    availability: availability
                });
            }
        });
        
        // Add clinic assignments to form data
        formData.append('clinic_assignments', JSON.stringify(clinicAssignments));

        fetch("get_doctor.php", {
            method: "POST",
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    showMessage("success", data.message || "Doctor information updated successfully");
                    editModal.style.display = "none";
                    loadDoctors();
                } else {
                    showMessage("error", data.message || "Failed to update doctor information");
                }
            })
            .catch((error) => {
                console.error("Error updating doctor:", error);
                showMessage("error", "An error occurred while updating doctor information");
            });
    }

    // Delete doctor
    function deleteDoctor(doctorId) {
        fetch("get_doctor.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                action: "delete",
                doctor_id: doctorId,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    showMessage("success", data.message || "Doctor deleted successfully");
                    deleteModal.style.display = "none";
                    loadDoctors();
                } else {
                    showMessage("error", data.message || "Failed to delete doctor");
                }
            })
            .catch((error) => {
                console.error("Error deleting doctor:", error);
                showMessage("error", "An error occurred while deleting doctor");
            });
    }

    // Show message
    function showMessage(type, message) {
        messageContainer.innerHTML = `
            <div class="alert alert-${type === "success" ? "success" : "danger"}">
                ${message}
            </div>
        `;

        // Auto-hide message after 5 seconds
        setTimeout(() => {
            messageContainer.innerHTML = "";
        }, 5000);
    }
</script>
</body>
</html>