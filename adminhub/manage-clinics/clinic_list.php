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
    <title>Clinics List - Cure Booking</title>
    <link rel="stylesheet" href="style.css">
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
                    <h1>Clinics Management</h1>
                </header>
                
                <div id="messageContainer"></div>
                    
                <div class="search-box">
                    <form class="search-form" id="searchForm">
                        <input type="text" class="search-input" id="searchInput" placeholder="Search clinics by name or location...">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
                <div class="clinicsTableContainer">
                    <div id="clinicsTableContainer">
                        <!-- Table will be loaded here via AJAX -->
                        Loading clinics list...
                    </div>
                </div>
            </div>
                
            <!-- View Clinic Modal -->
            <div id="viewModal" class="modal">
                <div class="modal-content" style="max-width: 800px;">
                    <div class="modal-header">
                        <h2 class="modal-title">Clinic Information</h2>
                        <span class="close">&times;</span>
                    </div>
                    <div class="modal-body" id="viewModalBody">
                        <!-- Clinic details will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Edit Clinic Modal -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Edit Clinic Information</h2>
                        <span class="close">&times;</span>
                    </div>
                    <form id="editClinicForm">
                        <input type="hidden" id="edit_clinic_id" name="clinic_id">
                        <input type="hidden" id="existing_img" name="existing_img">
                            
                        <div class="form-group">
                            <label for="edit_clinic_name" class="form-label">Clinic Name</label>
                            <input type="text" class="form-control" id="edit_clinic_name" name="clinic_name" required>
                        </div>
                            
                        <div class="form-group">
                            <label for="edit_clinic_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_clinic_email" name="clinic_email" required>
                        </div>
                            
                        <div class="form-group">
                            <label for="edit_contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="edit_contact_number" name="contact_number" required>
                        </div>
                            
                        <div class="form-group">
                            <label for="edit_available_timing" class="form-label">Available Timing</label>
                            <input type="text" class="form-control" id="edit_available_timing" name="available_timing" required>
                        </div>
                            
                        <div class="form-group">
                            <label for="edit_location" class="form-label">Location</label>
                            <textarea class="form-control" id="edit_location" name="location" rows="2" required></textarea>
                        </div>
                            
                        <div class="form-group">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-control" id="edit_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                            
                        <div class="form-group">
                            <label for="edit_about" class="form-label">About</label>
                            <textarea class="form-control" id="edit_about" name="about" rows="3"></textarea>
                        </div>
                            
                        <div class="form-group">
                            <label class="form-label">Current Image</label>
                            <div id="current_image_container"></div>
                        </div>
                            
                        <div class="form-group">
                            <label for="edit_profile_image" class="form-label">Update Image</label>
                            <input type="file" class="form-control" id="edit_profile_image" name="profile_image" accept="image/*">
                            <small>Leave empty to keep current image</small>
                        </div>

                        <div class="form-group">
                            <label for="edit_clinic_pass" class="form-label">Password (Leave empty to keep current)</label>
                            <input type="password" class="form-control" id="edit_clinic_pass" name="clinic_pass">
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
                    <p>Are you sure you want to delete this clinic? This action cannot be undone.</p>
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
    let clinicIdToDelete = null;

    // DOM Elements
    const clinicsTableContainer = document.getElementById("clinicsTableContainer");
    const messageContainer = document.getElementById("messageContainer");
    const searchForm = document.getElementById("searchForm");
    const searchInput = document.getElementById("searchInput");
    const viewModal = document.getElementById("viewModal");
    const editModal = document.getElementById("editModal");
    const deleteModal = document.getElementById("deleteModal");
    const editClinicForm = document.getElementById("editClinicForm");

    // Initialize page
    document.addEventListener("DOMContentLoaded", function () {
        loadClinics();
        setupEventListeners();
    });

    // Setup event listeners
    function setupEventListeners() {
        // Search form
        searchForm.addEventListener("submit", function (e) {
            e.preventDefault();
            loadClinics();
        });

        // Close modals
        document.querySelectorAll(".close").forEach(function (closeBtn) {
            closeBtn.addEventListener("click", function () {
                viewModal.style.display = "none";
                editModal.style.display = "none";
                deleteModal.style.display = "none";
            });
        });

        // Edit clinic form submission
        editClinicForm.addEventListener("submit", function (e) {
            e.preventDefault();
            updateClinic();
        });

        // Cancel delete
        document.getElementById("cancelDelete").addEventListener("click", function () {
            deleteModal.style.display = "none";
        });

        // Confirm delete
        document.getElementById("confirmDelete").addEventListener("click", function () {
            if (clinicIdToDelete) {
                deleteClinic(clinicIdToDelete);
            } else {
                showMessage("error", "Error: Clinic ID not found");
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
    
    // Load clinics list
    function loadClinics() {
        clinicsTableContainer.innerHTML = '<div class="loading">Loading clinics list...</div>';

        const searchQuery = searchInput.value.trim();
        const searchParam = searchQuery ? `&search=${encodeURIComponent(searchQuery)}` : '';

        fetch(`get_clinic.php?action=list${searchParam}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    renderClinicsTable(data.clinics);
                } else {
                    showMessage("error", data.message || "Failed to load clinics list");
                    clinicsTableContainer.innerHTML = "<p>No clinics found.</p>";
                }
            })
            .catch((error) => {
                console.error("Error loading clinics:", error);
                showMessage("error", "An error occurred while loading the clinics list");
                clinicsTableContainer.innerHTML = "<p>Error loading clinics.</p>";
            });
    }

    // Render clinics table
    function renderClinicsTable(clinics) {
        if (clinics.length === 0) {
            clinicsTableContainer.innerHTML = "<p>No clinics found.</p>";
            return;
        }

        let tableHtml = `
            <table class="data-table">
            <thead>
                <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Location</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Timing</th>
                <th>Actions</th>
                </tr>
            </thead>
            <tbody>
        `;

        clinics.forEach((clinic) => {
            const imageUrl = clinic.profile_image || "assets/img/default-clinic.png";
            const statusClass = clinic.status === 'active' ? 'status-active' : 
                              clinic.status === 'suspended' ? 'status-suspended' : 'status-inactive';
            
            tableHtml += `
            <tr>
                <td><img src="${imageUrl}" alt="${clinic.clinic_name}" class="doctor-img" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;"></td>
                <td>${clinic.clinic_name}</td>
                <td>${clinic.location || "N/A"}</td>
                <td>${clinic.clinic_email || "N/A"}</td>
                <td>${clinic.contact_number || "N/A"}</td>
                <td><span class="status-badge ${statusClass}">${clinic.status ? clinic.status.charAt(0).toUpperCase() + clinic.status.slice(1) : 'N/A'}</span></td>
                <td>${clinic.available_timing || "N/A"}</td>
                <td>
                    <div class="actions">
                        <button class="btn btn-info view-btn" data-id="${clinic.clinic_id}" title="View Details">
                            <i class='bx bx-show'></i>
                        </button>
                        <button class="btn btn-primary edit-btn" data-id="${clinic.clinic_id}" title="Edit">
                            <i class='bx bx-edit'></i>
                        </button>
                        <button class="btn btn-danger delete-btn" data-id="${clinic.clinic_id}" title="Delete">
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

        clinicsTableContainer.innerHTML = tableHtml;

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
    function openViewModal(clinicId) {
        fetch(`get_clinic.php?action=get&id=${clinicId}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    const clinic = data.clinic;
                    
                    let viewHtml = `
                        <div class="doctor-view-container">
                            <div class="doctor-basic-info">
                                <div class="doctor-image-section">
                                    ${clinic.profile_image ? 
                                        `<img src="${clinic.profile_image}" alt="${clinic.clinic_name}" class="doctor-profile-img" style="width: 150px; height: 150px; border-radius: 10px; object-fit: cover;">` : 
                                        '<div class="no-image">No Image Available</div>'
                                    }
                                </div>
                                <div class="doctor-details-section">
                                    <h3>${clinic.clinic_name}</h3>
                                    <p><strong>Email:</strong> ${clinic.clinic_email || 'N/A'}</p>
                                    <p><strong>Contact:</strong> ${clinic.contact_number || 'N/A'}</p>
                                    <p><strong>Location:</strong> ${clinic.location || 'N/A'}</p>
                                    <p><strong>Status:</strong> <span class="status-badge ${clinic.status === 'active' ? 'status-active' : clinic.status === 'suspended' ? 'status-suspended' : 'status-inactive'}">${clinic.status ? clinic.status.charAt(0).toUpperCase() + clinic.status.slice(1) : 'N/A'}</span></p>
                                    <p><strong>Available Timing:</strong> ${clinic.available_timing || 'N/A'}</p>
                                    <p><strong>Created:</strong> ${clinic.created_at ? new Date(clinic.created_at).toLocaleDateString() : 'N/A'}</p>
                                    <p><strong>Last Updated:</strong> ${clinic.updated_at ? new Date(clinic.updated_at).toLocaleDateString() : 'N/A'}</p>
                                </div>
                            </div>
                            
                            ${clinic.about ? `
                                <div class="doctor-bio-section">
                                    <h4>About</h4>
                                    <p>${clinic.about}</p>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    
                    document.getElementById("viewModalBody").innerHTML = viewHtml;
                    viewModal.style.display = "block";
                } else {
                    showMessage("error", data.message || "Failed to load clinic information");
                }
            })
            .catch((error) => {
                console.error("Error loading clinic details:", error);
                showMessage("error", "An error occurred while loading clinic details");
            });
    }

    // Open edit modal
    function openEditModal(clinicId) {
        fetch(`get_clinic.php?action=get&id=${clinicId}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    const clinic = data.clinic;

                    // Populate basic form fields
                    document.getElementById("edit_clinic_id").value = clinic.clinic_id;
                    document.getElementById("edit_clinic_name").value = clinic.clinic_name || "";
                    document.getElementById("edit_clinic_email").value = clinic.clinic_email || "";
                    document.getElementById("edit_contact_number").value = clinic.contact_number || "";
                    document.getElementById("edit_available_timing").value = clinic.available_timing || "";
                    document.getElementById("edit_location").value = clinic.location || "";
                    document.getElementById("edit_status").value = clinic.status || "";
                    document.getElementById("edit_about").value = clinic.about || "";
                    document.getElementById("existing_img").value = clinic.profile_image || "";

                    // Show current image
                    const imageContainer = document.getElementById("current_image_container");
                    if (clinic.profile_image) {
                        imageContainer.innerHTML = `<img src="${clinic.profile_image}" alt="${clinic.clinic_name}" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
                    } else {
                        imageContainer.innerHTML = "<p>No image available</p>";
                    }

                    // Display modal
                    editModal.style.display = "block";
                } else {
                    showMessage("error", data.message || "Failed to load clinic information");
                }
            })
            .catch((error) => {
                console.error("Error loading clinic details:", error);
                showMessage("error", "An error occurred while loading clinic details");
            });
    }

    // Open delete modal
    function openDeleteModal(clinicId) {
        clinicIdToDelete = clinicId;
        deleteModal.style.display = "block";
    }

    // Update clinic
    function updateClinic() {
        const formData = new FormData(editClinicForm);
        formData.append("action", "update");

        fetch("get_clinic.php", {
            method: "POST",
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    showMessage("success", data.message || "Clinic information updated successfully");
                    editModal.style.display = "none";
                    loadClinics();
                } else {
                    showMessage("error", data.message || "Failed to update clinic information");
                }
            })
            .catch((error) => {
                console.error("Error updating clinic:", error);
                showMessage("error", "An error occurred while updating clinic information");
            });
    }

    // Delete clinic
    function deleteClinic(clinicId) {
        fetch("get_clinic.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                action: "delete",
                clinic_id: clinicId,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "success") {
                    showMessage("success", data.message || "Clinic deleted successfully");
                    deleteModal.style.display = "none";
                    loadClinics();
                } else {
                    showMessage("error", data.message || "Failed to delete clinic");
                }
            })
            .catch((error) => {
                console.error("Error deleting clinic:", error);
                showMessage("error", "An error occurred while deleting clinic");
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