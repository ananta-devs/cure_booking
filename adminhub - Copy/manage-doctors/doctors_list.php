<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors List - Cure Booking</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php
        session_start();
        if (!isset($_SESSION['adm_id'])) {
            header("Location: http://localhost/cure_booking/adminhub/login.php");
            exit();
        }

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
            <header>
                <div class="container">
                    <h1>Doctors Management</h1>
                </div>
            </header>
                
            <div class="container">
                <div id="messageContainer"></div>
                    
                <div class="search-box">
                    <form class="search-form" id="searchForm">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search doctors by name or specialty...">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
                    
                <div id="doctorsTableContainer">
                    <!-- Table will be loaded here via AJAX -->
                    <div class="loading">Loading doctors list...</div>
                </div>
                    
                <div class="pagination" id="pagination">
                    <!-- Pagination will be generated dynamically -->
                </div>
            </div>
                
            <!-- Edit Doctor Modal -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Edit Doctor Information</h2>
                        <span class="close">&times;</span>
                    </div>
                    <form id="editDoctorForm">
                        <input type="hidden" id="edit_doctor_id" name="doctor_id">
                            
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
                            
                        <div class="form-group">
                            <label class="form-label">Current Image</label>
                            <div id="current_image_container"></div>
                        </div>
                            
                        <div class="form-group">
                            <label for="edit_doc_img" class="form-label">Update Image</label>
                            <input type="file" class="form-control" id="edit_doc_img" name="doc_img">
                            <small>Leave empty to keep current image</small>
                        </div>

                        <div class="availability-container">
                            <label class="form-label">Availability Schedule</label>
                            <div class="availability-grid">
                            <!-- Monday -->
                            <div class="day-row">
                                <div class="day-label">Monday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="monday_11:00-13:00" name="availability[monday][11:00-13:00]">
                                        <label for="monday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="monday_14:00-16:00" name="availability[monday][14:00-16:00]">
                                        <label for="monday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="monday_17:00-19:00" name="availability[monday][17:00-19:00]">
                                        <label for="monday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Tuesday -->
                            <div class="day-row">
                                <div class="day-label">Tuesday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="tuesday_11:00-13:00" name="availability[tuesday][11:00-13:00]">
                                        <label for="tuesday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="tuesday_14:00-16:00" name="availability[tuesday][14:00-16:00]">
                                        <label for="tuesday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="tuesday_17:00-19:00" name="availability[tuesday][17:00-19:00]">
                                        <label for="tuesday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Wednesday -->
                            <div class="day-row">
                                <div class="day-label">Wednesday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="wednesday_11:00-13:00" name="availability[wednesday][11:00-13:00]">
                                        <label for="wednesday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="wednesday_14:00-16:00" name="availability[wednesday][14:00-16:00]">
                                        <label for="wednesday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="wednesday_17:00-19:00" name="availability[wednesday][17:00-19:00]">
                                        <label for="wednesday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Thursday -->
                            <div class="day-row">
                                <div class="day-label">Thursday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="thursday_11:00-13:00" name="availability[thursday][11:00-13:00]">
                                        <label for="thursday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="thursday_14:00-16:00" name="availability[thursday][14:00-16:00]">
                                        <label for="thursday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="thursday_17:00-19:00" name="availability[thursday][17:00-19:00]">
                                        <label for="thursday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Friday -->
                            <div class="day-row">
                                <div class="day-label">Friday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="friday_11:00-13:00" name="availability[friday][11:00-13:00]">
                                        <label for="friday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="friday_14:00-16:00" name="availability[friday][14:00-16:00]">
                                        <label for="friday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="friday_17:00-19:00" name="availability[friday][17:00-19:00]">
                                        <label for="friday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Saturday -->
                            <div class="day-row">
                                <div class="day-label">Saturday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="saturday_11:00-13:00" name="availability[saturday][11:00-13:00]">
                                        <label for="saturday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="saturday_14:00-16:00" name="availability[saturday][14:00-16:00]">
                                        <label for="saturday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="saturday_17:00-19:00" name="availability[saturday][17:00-19:00]">
                                        <label for="saturday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Sunday -->
                            <div class="day-row">
                                <div class="day-label">Sunday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="sunday_11:00-13:00" name="availability[sunday][11:00-13:00]">
                                        <label for="sunday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="sunday_14:00-16:00" name="availability[sunday][14:00-16:00]">
                                        <label for="sunday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="sunday_17:00-19:00" name="availability[sunday][17:00-19:00]">
                                        <label for="sunday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="form-group">
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
    let currentPage = 1;
    let searchQuery = "";
    let doctorIdToDelete = null;

    // DOM Elements
    const doctorsTableContainer = document.getElementById("doctorsTableContainer");
    const messageContainer = document.getElementById("messageContainer");
    const searchForm = document.getElementById("searchForm");
    const searchInput = document.getElementById("searchInput");
    const editModal = document.getElementById("editModal");
    const deleteModal = document.getElementById("deleteModal");
    const editDoctorForm = document.getElementById("editDoctorForm");
    const paginationContainer = document.getElementById("pagination");

    // Initialize page
    document.addEventListener("DOMContentLoaded", function () {
    loadDoctors();
    setupEventListeners();
    });

    // Setup event listeners
    function setupEventListeners() {
    // Search form
    searchForm.addEventListener("submit", function (e) {
        e.preventDefault();
        searchQuery = searchInput.value.trim();
        currentPage = 1;
        loadDoctors();
    });

    // Close modals
    document.querySelectorAll(".close").forEach(function (closeBtn) {
        closeBtn.addEventListener("click", function () {
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

    const url = `get_doctor.php?action=list&page=${currentPage}&search=${encodeURIComponent(searchQuery)}`;

    fetch(url)
        .then((response) => response.json())
        .then((data) => {
        if (data.status === "success") {
            renderDoctorsTable(data.doctors);
            renderPagination(data.pagination);
        } else {
            showMessage("error", data.message || "Failed to load doctors list");
        }
        })
        .catch((error) => {
        console.error("Error loading doctors:", error);
        showMessage("error", "An error occurred while loading the doctors list");
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
            <th>Actions</th>
            </tr>
        </thead>
        <tbody>
    `;

    doctors.forEach((doctor) => {
        const imageUrl = doctor.doc_img ? `uploads/${doctor.doc_img}` : "assets/img/default-doctor.png";

        tableHtml += `
        <tr>
            <td><img src="${imageUrl}" alt="${doctor.doc_name}" class="doctor-img"></td>
            <td>${doctor.doc_name}</td>
            <td>${doctor.doc_specia}</td>
            <td>$${parseFloat(doctor.fees).toFixed(2)}</td>
            <td>${doctor.experience} years</td>
            <td>${doctor.doc_email || "N/A"}</td>
            <td class="actions">
            <button class="btn btn-primary edit-btn" data-id="${doctor.id}">Edit</button>
            <button class="btn btn-danger delete-btn" data-id="${doctor.id}">Delete</button>
            </td>
        </tr>
        `;
    });

    tableHtml += `
        </tbody>
        </table>
    `;

    doctorsTableContainer.innerHTML = tableHtml;

    // Add event listeners to edit and delete buttons
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

    // Render pagination
    function renderPagination(pagination) {
    if (!pagination || pagination.totalPages <= 1) {
        paginationContainer.innerHTML = "";
        return;
    }

    let paginationHtml = "";
    const isFirstPage = pagination.currentPage === 1;
    const isLastPage = pagination.currentPage === pagination.totalPages;

    // Previous button
    paginationHtml += `
        <a href="#" class="${isFirstPage ? "disabled" : ""}" 
        onclick="${isFirstPage ? "return false" : "changePage(" + (pagination.currentPage - 1) + ")"}">
        &laquo; Previous
        </a>
    `;

    // Page numbers
    const maxPages = 5;
    let startPage = Math.max(1, pagination.currentPage - Math.floor(maxPages / 2));
    let endPage = Math.min(pagination.totalPages, startPage + maxPages - 1);

    if (endPage - startPage + 1 < maxPages) {
        startPage = Math.max(1, endPage - maxPages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
        <a href="#" class="${i === pagination.currentPage ? "active" : ""}" 
            onclick="changePage(${i}); return false;">
            ${i}
        </a>
        `;
    }

    // Next button
    paginationHtml += `
        <a href="#" class="${isLastPage ? "disabled" : ""}" 
        onclick="${isLastPage ? "return false" : "changePage(" + (pagination.currentPage + 1) + ")"}">
        Next &raquo;
        </a>
    `;

    paginationContainer.innerHTML = paginationHtml;
    }

    // Change page
    function changePage(page) {
    currentPage = page;
    loadDoctors();
    }

    // Open edit modal
    function openEditModal(doctorId) {
    fetch(`get_doctor.php?action=get&id=${doctorId}`)
        .then((response) => response.json())
        .then((data) => {
        if (data.status === "success") {
            const doctor = data.doctor;

            // Populate basic form fields
            document.getElementById("edit_doctor_id").value = doctor.id;
            document.getElementById("edit_doc_name").value = doctor.doc_name;
            document.getElementById("edit_doc_specia").value = doctor.doc_specia;
            document.getElementById("edit_doc_email").value = doctor.doc_email || "";
            document.getElementById("edit_fees").value = doctor.fees;
            document.getElementById("edit_gender").value = doctor.gender;
            document.getElementById("edit_experience").value = doctor.experience;
            document.getElementById("edit_location").value = doctor.location || "";
            document.getElementById("edit_education").value = doctor.education || "";
            document.getElementById("edit_bio").value = doctor.bio || "";

            // Show current image
            const imageContainer = document.getElementById("current_image_container");
            if (doctor.doc_img) {
            imageContainer.innerHTML = `<img src="uploads/${doctor.doc_img}" alt="${doctor.doc_name}" style="max-width: 100px; max-height: 100px;">`;
            } else {
            imageContainer.innerHTML = "<p>No image available</p>";
            }

            // Handle availability data
            if (doctor.availability) {
            try {
                const availabilityData = JSON.parse(doctor.availability);
                
                // Reset all checkboxes first
                document.querySelectorAll('input[name^="availability"]').forEach((checkbox) => {
                checkbox.checked = false;
                });

                // Set checkboxes based on availability data
                const days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
                const timeSlots = ["11:00-13:00", "14:00-16:00", "17:00-19:00"];

                days.forEach((day) => {
                if (availabilityData[day]) {
                    timeSlots.forEach((slot) => {
                    if (availabilityData[day][slot]) {
                        const checkbox = document.getElementById(`${day}_${slot}`);
                        if (checkbox) checkbox.checked = true;
                    }
                    });
                }
                });
            } catch (e) {
                console.error("Error parsing availability data:", e);
            }
            }

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

    // Open delete modal
    function openDeleteModal(doctorId) {
    doctorIdToDelete = doctorId;
    deleteModal.style.display = "block";
    }

    // Update doctor
    function updateDoctor() {
    const formData = new FormData(editDoctorForm);
    formData.append("action", "update");

    // Get availability data from checkboxes
    const availabilityData = {};
    const days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
    const timeSlots = ["11:00-13:00", "14:00-16:00", "17:00-19:00"];

    days.forEach((day) => {
        availabilityData[day] = {};
        timeSlots.forEach((slot) => {
        const checkbox = document.getElementById(`${day}_${slot}`);
        availabilityData[day][slot] = checkbox ? checkbox.checked : false;
        });
    });

    formData.append("availability", JSON.stringify(availabilityData));

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