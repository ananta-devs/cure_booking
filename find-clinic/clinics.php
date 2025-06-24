<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Find Clinics - Healthcare Directory</title>
        <link rel="stylesheet" href="styles.css">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    </head>
    <body>
        <?php
            include '../include/header.php';
        ?>
        <section class="hero">
            <div class="container">
                <h1>Your Trusted Partner in Health & Wellness.</h1>
                <p>Find all your prescription medications, supplements, and healthcare essentials at competitive prices.</p>
                <form class="search-container" onsubmit="searchClinics(event)">
                    <input type="text" id="search-bar" placeholder="Search for clinics..."/>
                    <button type="submit" aria-label="Search"><i class="ri-search-line"></i></button>
                </form>
            </div>
        </section>
        
        <section class="clinics-section">
            <h2>Available Clinics</h2>
            <div id="loading" class="loading" style="display: none;">
                <i class="ri-loader-4-line"></i> Loading clinics...
            </div>
            <div id="error-message" class="error-message" style="display: none;"></div>
            <div id="clinics-container" class="clinics-container"></div>
        </section>

        <!-- Doctor Modal -->
        <div id="doctorModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="modalTitle">Available Doctors</h2>
                    <button class="close" onclick="closeDoctorModal()">&times;</button>
                </div>
                <div class="modal-body" id="modalBody">
                    <div class="loading-modal">
                        <i class="ri-loader-4-line"></i>
                        <p>Loading doctors...</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Global variables
            let allClinics = [];
            let isLoading = false;

            // Load clinics when page loads
            document.addEventListener('DOMContentLoaded', function() {
                loadClinics();
            });

            // Function to load all clinics
            async function loadClinics() {
                if (isLoading) return;
                
                isLoading = true;
                showLoading(true);
                hideError();

                try {
                    const response = await fetch('api.php');
                    const data = await response.json();

                    if (data.success) {
                        allClinics = data.data;
                        displayClinics(allClinics);
                    } else {
                        showError('Failed to load clinics: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error loading clinics:', error);
                    showError('Failed to load clinics. Please check your connection and try again.');
                } finally {
                    isLoading = false;
                    showLoading(false);
                }
            }

            // Function to search clinics
            async function searchClinics(event) {
                event.preventDefault();
                
                if (isLoading) return;

                const searchTerm = document.getElementById('search-bar').value.trim();
                
                if (searchTerm === '') {
                    displayClinics(allClinics);
                    return;
                }

                isLoading = true;
                showLoading(true);
                hideError();

                try {
                    const response = await fetch(`api.php?search=${encodeURIComponent(searchTerm)}`);
                    const data = await response.json();

                    if (data.success) {
                        displayClinics(data.data);
                    } else {
                        showError('Search failed: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error searching clinics:', error);
                    showError('Search failed. Please try again.');
                } finally {
                    isLoading = false;
                    showLoading(false);
                }
            }

            // Function to display clinics
            function displayClinics(clinics) {
                const container = document.getElementById('clinics-container');
                
                if (clinics.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <i class="ri-hospital-line"></i>
                            <h3>No clinics found</h3>
                            <p>Try adjusting your search criteria or browse all available clinics.</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = clinics.map(clinic => `
                    <div class="clinic-card">
                        <div class="clinic-image">
                            ${clinic.profile_image ? 
                                `<img src="${clinic.profile_image}" alt="${clinic.clinic_name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` : 
                                ''
                            }
                            <div class="clinic-image-placeholder" ${clinic.profile_image ? 'style="display:none;"' : ''}>
                                <i class="ri-hospital-fill"></i>
                            </div>
                        </div>
                        <div class="clinic-info">
                            <h3 class="clinic-name">${escapeHtml(clinic.clinic_name)}</h3>
                            <div class="clinic-details">
                                <div class="detail-item">
                                    <i class="ri-map-pin-line"></i>
                                    <span>${escapeHtml(clinic.location)}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="ri-time-line"></i>
                                    <span>${escapeHtml(clinic.available_timing)}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="ri-phone-line"></i>
                                    <span>${escapeHtml(clinic.contact_number)}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="ri-mail-line"></i>
                                    <span>${escapeHtml(clinic.clinic_email)}</span>
                                </div>
                            </div>
                            ${clinic.about ? `
                                <div class="clinic-about">
                                    <p>${escapeHtml(clinic.about)}</p>
                                </div>
                            ` : ''}
                            <div class="clinic-actions">
                                <button class="btn-secondary" onclick="viewAvailableDoctors(${clinic.clinic_id}, '${escapeHtml(clinic.clinic_name)}')">
                                    <i class="ri-eye-line"></i> View Available Doctors
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            // Function to view available doctors
            async function viewAvailableDoctors(clinicId, clinicName) {
                const modal = document.getElementById('doctorModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalBody = document.getElementById('modalBody');

                // Set modal title
                modalTitle.textContent = `Available Doctors - ${clinicName}`;

                // Show loading state
                modalBody.innerHTML = `
                    <div class="loading-modal">
                        <i class="ri-loader-4-line"></i>
                        <p>Loading doctors...</p>
                    </div>
                `;

                // Show modal
                modal.style.display = 'block';

                try {
                    const response = await fetch(`api.php?clinic_doctors=${clinicId}`);
                    const data = await response.json();

                    if (data.success) {
                        displayDoctors(data.data);
                    } else {
                        modalBody.innerHTML = `
                            <div class="no-doctors">
                                <i class="ri-user-x-line"></i>
                                <h3>No doctors found</h3>
                                <p>This clinic doesn't have any available doctors at the moment.</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Error loading doctors:', error);
                    modalBody.innerHTML = `
                        <div class="no-doctors">
                            <i class="ri-error-warning-line"></i>
                            <h3>Error loading doctors</h3>
                            <p>Failed to load doctor information. Please try again.</p>
                        </div>
                    `;
                }
            }

            // Function to display doctors in modal
            function displayDoctors(doctors) {
                const modalBody = document.getElementById('modalBody');

                if (doctors.length === 0) {
                    modalBody.innerHTML = `
                        <div class="no-doctors">
                            <i class="ri-user-x-line"></i>
                            <h3>No doctors available</h3>
                            <p>This clinic doesn't have any available doctors at the moment.</p>
                        </div>
                    `;
                    return;
                }

                modalBody.innerHTML = `
                    <div class="doctor-grid">
                        ${doctors.map(doctor => `
                            <div class="doctor-card">
                                <div class="doctor-header">
                                    ${doctor.doc_img ? 
                                        `<img src="${doctor.doc_img}" alt="${doctor.doc_name}" class="doctor-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` : 
                                        ''
                                    }
                                    <div class="doctor-avatar-placeholder" ${doctor.doc_img ? 'style="display:none;"' : ''}>
                                        <i class="ri-user-line"></i>
                                    </div>
                                    <div class="doctor-basic-info">
                                        <h3>${escapeHtml(doctor.doc_name)}</h3>
                                        <p class="doctor-specialty">${escapeHtml(doctor.doc_specia)}</p>
                                    </div>
                                </div>
                                
                                <div class="doctor-details">
                                    <div class="detail-row">
                                        <i class="ri-graduation-cap-line"></i>
                                        <span>${escapeHtml(doctor.education)}</span>
                                    </div>
                                    <div class="detail-row">
                                        <i class="ri-time-line"></i>
                                        <span>${doctor.experience} years experience</span>
                                    </div>
                                    ${doctor.fees > 0 ? `
                                        <div class="detail-row">
                                            <i class="ri-money-rupee-circle-line"></i>
                                            <span>₹${doctor.fees} consultation fee</span>
                                        </div>
                                    ` : ''}
                                    <div class="detail-row">
                                        <i class="ri-user-2-line"></i>
                                        <span>${escapeHtml(doctor.gender)}</span>
                                    </div>
                                </div>

                                <div class="availability-section">
                                    <div class="availability-title">Available Days</div>
                                    <div class="availability-days">
                                        ${doctor.available_days.map(day => `
                                            <span class="day-badge">${day}</span>
                                        `).join('')}
                                    </div>
                                    
                                    <div class="availability-title">Time Slots</div>
                                    <div class="time-slots">
                                        ${Object.values(doctor.available_slots).flat().map(slot => `
                                            <span class="time-slot">${slot}</span>
                                        `).join('')}
                                    </div>
                                </div>

                                ${doctor.bio && doctor.bio !== 'Nothing' && doctor.bio !== 'nothing' ? `
                                    <div class="doctor-bio">
                                        <p><strong>About:</strong> ${escapeHtml(doctor.bio)}</p>
                                    </div>
                                ` : ''}
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            // Function to close doctor modal
            function closeDoctorModal() {
                const modal = document.getElementById('doctorModal');
                modal.style.display = 'none';
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('doctorModal');
                if (event.target === modal) {
                    closeDoctorModal();
                }
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeDoctorModal();
                }
            });

            // Utility functions
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function showLoading(show) {
                const loading = document.getElementById('loading');
                loading.style.display = show ? 'block' : 'none';
            }

            function showError(message) {
                const errorElement = document.getElementById('error-message');
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }

            function hideError() {
                const errorElement = document.getElementById('error-message');
                errorElement.style.display = 'none';
            }

            // Clear search functionality
            document.getElementById('search-bar').addEventListener('input', function(e) {
                if (e.target.value === '') {
                    displayClinics(allClinics);
                }
            });
        </script>
    </body>
</html>