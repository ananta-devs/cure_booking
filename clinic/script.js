// Appointment Management System - JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the application
    initializeApp();
});

// Global variables
let appointments = [];
let doctors = [];
let specialties = [];
let filteredAppointments = [];

// Initialize the application
function initializeApp() {
    loadAppointments();
    loadDoctors();
    loadSpecialties();
    setupEventListeners();
    setMinDate();
}

// Setup event listeners
function setupEventListeners() {
    // Navigation buttons
<<<<<<< HEAD
    document
        .getElementById("viewAppointmentsBtn")
        ?.addEventListener("click", showAppointmentsSection);
    document
        .getElementById("allDoctorsBtn")
        ?.addEventListener("click", showDoctorsSection);

    // Filter buttons and inputs
    const filterElements = [
        "filterBtn",
        "filterDate",
        "filterStatus",
        "filterDoctor",
    ];
    filterElements.forEach((id) => {
        const element = document.getElementById(id);
        if (element) {
            const eventType = id === "filterBtn" ? "click" : "change";
            element.addEventListener(eventType, filterAppointments);
        }
    });

    document
        .getElementById("clearFilterBtn")
        ?.addEventListener("click", clearFilters);
    document
        .getElementById("statusUpdateForm")
        ?.addEventListener("submit", handleStatusUpdate);

    // Modal events
    document.addEventListener("click", handleModalClicks);
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeAllModals();
=======
    document.getElementById('viewAppointmentsBtn')?.addEventListener('click', () => showSection('appointments'));
    document.getElementById('allDoctorsBtn')?.addEventListener('click', () => showSection('doctors'));
    
    // Filter buttons
    document.getElementById('filterBtn')?.addEventListener('click', applyFilters);
    document.getElementById('clearFilterBtn')?.addEventListener('click', clearFilters);
    
    // Appointment date change for time slots
    document.getElementById('appointmentDate')?.addEventListener('change', loadTimeSlots);
    
    // Modal close events
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
>>>>>>> 860a47c5a1bbfafa34d82ce525f8de395d08e09d
    });
    
    // Form submissions
    document.getElementById('statusUpdateForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        saveStatusUpdate();
    });
    
    document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        saveAppointmentBooking();
    });
}

<<<<<<< HEAD
// Handle modal clicks for closing on backdrop
function handleModalClicks(event) {
    const modals = [
        "appointmentModal",
        "statusModal",
        "doctorModal",
        "bookingModal",
    ];
    modals.forEach((modalId) => {
        if (event.target === document.getElementById(modalId)) {
            closeModal(modalId);
        }
    });
}

// Navigation functions
function showSection(activeSection, inactiveSection, activeBtn, inactiveBtn) {
    document.getElementById(activeSection).style.display = "block";
    document.getElementById(inactiveSection).style.display = "none";
    document.getElementById(activeBtn).classList.add("active");
    document.getElementById(inactiveBtn).classList.remove("active");
    closeAllModals();
}

function showAppointmentsSection() {
    showSection(
        "appointmentsSection",
        "doctorsSection",
        "viewAppointmentsBtn",
        "allDoctorsBtn"
    );
    loadAppointments();
}

function showDoctorsSection() {
    showSection(
        "doctorsSection",
        "appointmentsSection",
        "allDoctorsBtn",
        "viewAppointmentsBtn"
    );
    loadDoctors();
}

// API call helper
async function apiCall(action, additionalData = {}) {
    const formData = new FormData();
    formData.append("action", action);

    Object.entries(additionalData).forEach(([key, value]) => {
        if (value) formData.append(key, value);
    });

    const response = await fetch("api.php", { method: "POST", body: formData });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    return response.json();
}

// Appointment Modal Functions
async function viewAppointment(appointmentId) {
    currentAppointmentId = appointmentId;
    const modal = document.getElementById("appointmentModal");
    const modalBody = document.getElementById("appointmentModalBody");

    if (!modal || !modalBody) {
        console.error("Modal elements not found");
        return;
    }

    modalBody.innerHTML = createLoadingHTML("Loading appointment details...");
    modal.style.display = "block";

    try {
        const data = await apiCall("get_appointment_details", {
            appointment_id: appointmentId,
        });

        if (data.success && data.appointment) {
            displayAppointmentDetails(data.appointment);
        } else {
            modalBody.innerHTML = createErrorHTML(
                data.message || "No appointment data found",
                "closeAppointmentModal()"
            );
        }
    } catch (error) {
        console.error("Error:", error);
        modalBody.innerHTML = createErrorHTML(
            `An error occurred: ${error.message}`,
            "closeAppointmentModal()"
        );
    }
}

// Enhanced displayAppointmentDetails function
function displayAppointmentDetails(appointment) {
    const modalBody = document.getElementById("appointmentModalBody");

    if (!appointment) {
        modalBody.innerHTML = createErrorHTML(
            "No appointment data received",
            "closeAppointmentModal()"
        );
        return;
=======
// Set minimum date for appointment booking
function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('appointmentDate');
    if (dateInput) {
        dateInput.min = today;
    }
}

// Show/hide sections
function showSection(section) {
    // Update button states
    document.querySelectorAll('.action-btn').forEach(btn => btn.classList.remove('active'));
    
    if (section === 'appointments') {
        document.getElementById('viewAppointmentsBtn').classList.add('active');
        document.getElementById('appointmentsSection').style.display = 'block';
        document.getElementById('doctorsSection').style.display = 'none';
    } else if (section === 'doctors') {
        document.getElementById('allDoctorsBtn').classList.add('active');
        document.getElementById('appointmentsSection').style.display = 'none';
        document.getElementById('doctorsSection').style.display = 'block';
        displayDoctors();
>>>>>>> 860a47c5a1bbfafa34d82ce525f8de395d08e09d
    }
}

// API Functions
async function apiRequest(action, data = {}) {
    try {
<<<<<<< HEAD
        const formattedDate = formatDate(appointment.appointment_date);
        const formattedTime = formatTime(appointment.appointment_time);
        const patientName = appointment.patient_name || "Unknown Patient";
        const patientInitials = getInitials(patientName);
        const statusClass = getStatusClass(appointment.status);
        const statusIcon = getStatusIcon(appointment.status);
        const doctorName =
            appointment.doc_name || appointment.doctor_name || "Not assigned";
        const contactNumber =
            appointment.patient_phone ||
            appointment.contact_number ||
            "Not provided";
        const email =
            appointment.patient_email || appointment.email || "Not provided";

        modalBody.innerHTML = `
            <div class="appointment-detail-card">
                <div class="appointment-header">
                    <div class="patient-avatar">${patientInitials}</div>
                    <div class="appointment-info">
                        <h3>${patientName}</h3>
                        <div class="status-badge ${statusClass}">
                            <i class="${statusIcon}"></i>
                            ${appointment.status || "Unknown"}
                        </div>
                    </div>
                </div>
                
                <div class="appointment-details">
                    ${createDetailRow("fa-calendar", "Date", formattedDate)}
                    ${createDetailRow("fa-clock", "Time", formattedTime)}
                    ${createDetailRow("fa-user-md", "Doctor", doctorName)}
                    ${createDetailRow("fa-phone", "Contact", contactNumber)}
                    ${createDetailRow("fa-envelope", "Email", email)}
                    ${
                        appointment.gender
                            ? createDetailRow(
                                  "fa-user",
                                  "Gender",
                                  appointment.gender
                              )
                            : ""
                    }
                    ${
                        appointment.reason
                            ? createDetailRow(
                                  "fa-stethoscope",
                                  "Reason",
                                  appointment.reason
                              )
                            : ""
                    }
                    ${
                        appointment.notes
                            ? createDetailRow(
                                  "fa-sticky-note",
                                  "Notes",
                                  appointment.notes
                              )
                            : ""
                    }
                </div>
                
                <div class="appointment-actions">
                    <button class="btn btn-primary" onclick="openStatusModal(${
                        appointment.id
                    }, '${appointment.status}')">
                        <i class="fa fa-edit"></i> Update Status
                    </button>
                    <button class="btn btn-secondary" onclick="closeAppointmentModal()">
                        <i class="fa fa-times"></i> Close
                    </button>
                </div>
            </div>
        `;
    } catch (error) {
        console.error("Error displaying appointment details:", error);
        modalBody.innerHTML = createErrorHTML(
            "An error occurred while displaying appointment details",
            "closeAppointmentModal()"
        );
=======
        const formData = new FormData();
        formData.append('action', action);
        
        // Add all data to FormData
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API Request failed:', error);
        showMessage('Network error occurred. Please try again.', 'error');
        return { success: false, message: 'Network error occurred' };
>>>>>>> 860a47c5a1bbfafa34d82ce525f8de395d08e09d
    }
}

// Load appointments
async function loadAppointments() {
    showLoading('appointmentsContainer');
    
    const result = await apiRequest('get_appointments');
    
    if (result.success) {
        appointments = result.appointments || [];
        filteredAppointments = [...appointments];
        displayAppointments();
        populateFilterDropdowns();
    } else {
        showMessage(result.message || 'Failed to load appointments', 'error');
        document.getElementById('appointmentsContainer').innerHTML = '<p class="no-data">Failed to load appointments</p>';
    }
<<<<<<< HEAD

    if (statusSelect) statusSelect.value = currentStatus;
    modal.style.display = "block";
}

async function handleStatusUpdate(event) {
    event.preventDefault();

    const newStatus = document.getElementById("newStatus")?.value;
    const notes = document.getElementById("statusNotes")?.value || "";

    if (!currentAppointmentId || !newStatus) {
        showNotification("Please select a status", "error");
        return;
    }

    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';
    submitBtn.disabled = true;

    try {
        const data = await apiCall("update_appointment_status", {
            appointment_id: currentAppointmentId,
            status: newStatus,
            notes: notes,
        });

        if (data.success) {
            showNotification(
                "Appointment status updated successfully!",
                "success"
            );
            closeModal("statusModal");
            loadAppointments();

            if (
                document.getElementById("appointmentModal").style.display ===
                "block"
            ) {
                viewAppointment(currentAppointmentId);
            }
        } else {
            showNotification(
                data.message || "Failed to update appointment status",
                "error"
            );
        }
    } catch (error) {
        console.error("Error:", error);
        showNotification(
            "An error occurred while updating the appointment",
            "error"
        );
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// Load and display appointments
async function loadAppointments() {
    const container = document.getElementById("appointmentsContainer");
    if (!container) return;

    container.innerHTML = createLoadingHTML("Loading appointments...");

    try {
        const data = await apiCall("get_appointments");

        if (data.success && data.appointments) {
            displayAppointments(data.appointments);
        } else {
            container.innerHTML = createNoDataHTML(
                "fa-calendar-times",
                "No appointments found",
                data.message || "There are no appointments to display."
            );
        }
    } catch (error) {
        console.error("Error:", error);
        container.innerHTML = createErrorHTML(
            `Failed to load appointments: ${error.message}`,
            "loadAppointments()"
        );
    }
}

function displayAppointments(appointments) {
    const container = document.getElementById("appointmentsContainer");

    if (!appointments?.length) {
        container.innerHTML = createNoDataHTML(
            "fa-calendar-times",
            "No appointments found",
            "There are no appointments to display."
        );
        return;
    }

    const appointmentsHTML = appointments
        .map((appointment) => {
            const statusClass = getStatusClass(appointment.status);
            const statusIcon = getStatusIcon(appointment.status);
            const formattedDate = formatDate(appointment.appointment_date);
            const formattedTime = formatTime(appointment.appointment_time);
            const patientName = appointment.patient_name || "Unknown Patient";
            const doctorName =
                appointment.doc_name ||
                appointment.doctor_name ||
                "Not assigned";

            return `
            <div class="appointment-card" onclick="viewAppointment(${
                appointment.id
            })">
                <div class="appointment-card-header">
                    <div class="patient-info">
                        <h4>${patientName}</h4>
                        <p class="doctor-name">
                            <i class="fa fa-user-md"></i>
                            ${doctorName}
                        </p>
=======
}

// Load doctors
async function loadDoctors() {
    const result = await apiRequest('get_doctors');
    
    if (result.success) {
        doctors = result.doctors || [];
    } else {
        console.error('Failed to load doctors:', result.message);
    }
}

// Load specialties
async function loadSpecialties() {
    const result = await apiRequest('get_specialties');
    
    if (result.success) {
        specialties = result.specialties || [];
    } else {
        console.error('Failed to load specialties:', result.message);
    }
}

// Display appointments in table format
function displayAppointments() {
    const container = document.getElementById('appointmentsContainer');
    
    if (!filteredAppointments.length) {
        container.innerHTML = '<p class="no-data">No appointments found</p>';
        return;
    }
    
    const table = `
        <div class="table-responsive">
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${filteredAppointments.map(appointment => `
                        <tr class="appointment-row status-${appointment.status}">
                            <td>
                                <div class="patient-info">
                                    <strong>${escapeHtml(appointment.patient_name)}</strong>
                                    <br><small>${escapeHtml(appointment.gender)}</small>
                                </div>
                            </td>
                            <td>
                                <div class="doctor-info">
                                    <strong>${escapeHtml(appointment.doc_name)}</strong>
                                    <br><small>${escapeHtml(appointment.doctor_specialization)}</small>
                                </div>
                            </td>
                            <td>
                                <div class="datetime-info">
                                    <strong>${formatDate(appointment.appointment_date)}</strong>
                                    <br><small>${formatTime(appointment.appointment_time)}</small>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-${appointment.status}">
                                    ${capitalizeFirst(appointment.status)}
                                </span>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <small>${escapeHtml(appointment.patient_phone)}</small>
                                    <br><small>${escapeHtml(appointment.patient_email)}</small>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-view" onclick="viewAppointment(${appointment.id})" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button class="btn-action btn-edit" onclick="updateStatus(${appointment.id})" title="Update Status">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-cancel" onclick="cancelAppointment(${appointment.id})" title="Cancel">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = table;
}

// Display doctors
function displayDoctors() {
    const container = document.getElementById('doctorsContainer');
    
    if (!doctors.length) {
        container.innerHTML = '<p class="no-data">No doctors found</p>';
        return;
    }
    
    const doctorsGrid = `
        <div class="doctors-grid">
            ${doctors.map(doctor => `
                <div class="doctor-card">
                    <div class="doctor-header">
                        <h3>${escapeHtml(doctor.doc_name)}</h3>
                        <span class="doctor-specialty">${escapeHtml(doctor.doc_specia)}</span>
>>>>>>> 860a47c5a1bbfafa34d82ce525f8de395d08e09d
                    </div>
                    <div class="doctor-details">
                        <p><i class="fa fa-graduation-cap"></i> ${escapeHtml(doctor.education || 'Not specified')}</p>
                        <p><i class="fa fa-clock"></i> ${escapeHtml(doctor.experience || 'Not specified')} Experience</p>
                        <p><i class="fa fa-map-marker"></i> ${escapeHtml(doctor.location || 'Not specified')}</p>
                        <p><i class="fa fa-envelope"></i> ${escapeHtml(doctor.doc_email || 'Not specified')}</p>
                    </div>
                    <div class="doctor-actions">
                        <button class="btn-primary" onclick="bookAppointmentWithDoctor(${doctor.doc_id})">
                            <i class="fa fa-calendar-plus"></i> Book Appointment
                        </button>
                    </div>
                </div>
<<<<<<< HEAD
                <div class="appointment-card-body">
                    <div class="appointment-datetime">
                        <div class="date-info">
                            <i class="fa fa-calendar"></i>
                            <span>${formattedDate}</span>
                        </div>
                        <div class="time-info">
                            <i class="fa fa-clock"></i>
                            <span>${formattedTime}</span>
                        </div>
                    </div>
                    ${
                        appointment.reason
                            ? `
                        <div class="appointment-reason">
                            <i class="fa fa-stethoscope"></i>
                            <span>${appointment.reason}</span>
                        </div>
                    `
                            : ""
                    }
                </div>
            </div>
        `;
        })
        .join("");

    container.innerHTML = appointmentsHTML;
}

// Filter Functions
async function filterAppointments() {
    const filters = {
        date_filter: document.getElementById("filterDate")?.value,
        status_filter: document.getElementById("filterStatus")?.value,
        doctor_filter: document.getElementById("filterDoctor")?.value,
    };

    const container = document.getElementById("appointmentsContainer");
    container.innerHTML = createLoadingHTML("Filtering appointments...");

    try {
        const data = await apiCall("get_appointments", filters);

        if (data.success && data.appointments) {
            displayAppointments(data.appointments);
        } else {
            container.innerHTML = createNoDataHTML(
                "fa-filter",
                "No appointments match your filters",
                "Try adjusting your filter criteria."
            );
        }
    } catch (error) {
        console.error("Error:", error);
        container.innerHTML = createErrorHTML(
            `Failed to filter appointments: ${error.message}`
        );
    }
=======
            `).join('')}
        </div>
    `;
    
    container.innerHTML = doctorsGrid;
>>>>>>> 860a47c5a1bbfafa34d82ce525f8de395d08e09d
}

// Populate filter dropdowns
function populateFilterDropdowns() {
    const doctorFilter = document.getElementById('filterDoctor');
    if (doctorFilter) {
        doctorFilter.innerHTML = '<option value="">All Doctors</option>';
        const uniqueDoctors = [...new Set(appointments.map(apt => apt.doc_name))];
        uniqueDoctors.forEach(doctorName => {
            doctorFilter.innerHTML += `<option value="${escapeHtml(doctorName)}">${escapeHtml(doctorName)}</option>`;
        });
    }
}

// Apply filters
function applyFilters() {
    const dateFilter = document.getElementById('filterDate')?.value;
    const statusFilter = document.getElementById('filterStatus')?.value;
    const doctorFilter = document.getElementById('filterDoctor')?.value;
    
    filteredAppointments = appointments.filter(appointment => {
        if (dateFilter && appointment.appointment_date !== dateFilter) return false;
        if (statusFilter && appointment.status !== statusFilter) return false;
        if (doctorFilter && appointment.doc_name !== doctorFilter) return false;
        return true;
    });
    
    displayAppointments();
}

// Clear filters
function clearFilters() {
<<<<<<< HEAD
    ["filterDate", "filterStatus", "filterDoctor"].forEach((id) => {
        const element = document.getElementById(id);
        if (element) element.value = "";
    });
    loadAppointments();
}

// Modal Functions
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "none";
        if (modalId === "statusModal") {
            document.getElementById("statusUpdateForm")?.reset();
        }
    }
}

function closeAppointmentModal() {
    closeModal("appointmentModal");
}

function closeAllModals() {
    ["appointmentModal", "statusModal", "doctorModal", "bookingModal"].forEach(
        closeModal
    );
}

// Doctor section functions
async function loadDoctors() {
    const container = document.getElementById("doctorsContainer");
    if (!container) return;

    container.innerHTML = createLoadingHTML("Loading doctors...");

    try {
        const data = await apiCall("get_doctors");

        if (data.success && data.doctors) {
            displayDoctors(data.doctors);
        } else {
            container.innerHTML = createNoDataHTML(
                "fa-user-md",
                "No doctors found",
                data.message || "There are no doctors to display."
            );
        }
    } catch (error) {
        console.error("Error:", error);
        container.innerHTML = createErrorHTML(
            `Failed to load doctors: ${error.message}`,
            "loadDoctors()"
        );
    }
}

function displayDoctors(doctors) {
    const container = document.getElementById("doctorsContainer");

    if (!doctors?.length) {
        container.innerHTML = createNoDataHTML(
            "fa-user-md",
            "No doctors found",
            "There are no doctors to display."
        );
        return;
    }

    const doctorsHTML = doctors
        .map((doctor) => {
            const name = doctor.doc_name || doctor.name || "Unknown Doctor";
            const initials = getInitials(name);

            return `
            <div class="doctor-card">
                <div class="doctor-avatar">${initials}</div>
                <div class="doctor-info">
                    <h4>${name}</h4>
                    <p class="specialty">${
                        doctor.doc_specia ||
                        doctor.specialty ||
                        "General Practice"
                    }</p>
                    <p class="contact">
                        <i class="fa fa-phone"></i>
                        ${doctor.contact_number || "No contact provided"}
                    </p>
                    <p class="email">
                        <i class="fa fa-envelope"></i>
                        ${
                            doctor.doc_email ||
                            doctor.email ||
                            "No email provided"
                        }
                    </p>
                    ${
                        doctor.experience
                            ? `<p class="experience">
                        <i class="fa fa-star"></i>
                        ${doctor.experience} years experience
                    </p>`
                            : ""
                    }
                    ${
                        doctor.location
                            ? `<p class="location">
                        <i class="fa fa-map-marker"></i>
                        ${doctor.location}
                    </p>`
                            : ""
                    }
                </div>
            </div>
        `;
        })
        .join("");

    container.innerHTML = doctorsHTML;
}

// Utility Functions
function getStatusClass(status) {
    const statusClasses = {
        pending: "status-pending",
        confirmed: "status-confirmed",
        completed: "status-completed",
        cancelled: "status-cancelled",
        "no-show": "status-no-show",
    };
    return statusClasses[status?.toLowerCase()] || "status-unknown";
}

function getStatusIcon(status) {
    const statusIcons = {
        pending: "fa fa-clock",
        confirmed: "fa fa-check-circle",
        completed: "fa fa-check-double",
        cancelled: "fa fa-times-circle",
        "no-show": "fa fa-user-times",
    };
    return statusIcons[status?.toLowerCase()] || "fa fa-question-circle";
}

function formatDate(dateString) {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    return isNaN(date.getTime())
        ? "N/A"
        : date.toLocaleDateString("en-GB", {
              weekday: "long",
              year: "numeric",
              month: "long",
              day: "numeric",
          });
}

function formatTime(timeString) {
    if (!timeString) return "N/A";
    const time = new Date(`1970-01-01T${timeString}`);
    return isNaN(time.getTime())
        ? "N/A"
        : time.toLocaleTimeString("en-US", {
              hour: "numeric",
              minute: "2-digit",
              hour12: true,
          });
}

function getInitials(name) {
    return name
        ? name
              .split(" ")
              .map((n) => n.charAt(0))
              .join("")
              .toUpperCase()
        : "DR";
}

function createDetailRow(icon, label, value) {
    return `
        <div class="detail-row">
            <i class="fa ${icon}"></i>
            <span>${label}: ${value}</span>
        </div>
    `;
}

function createLoadingHTML(message) {
    return `
        <div class="loading-container">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>${message}</p>
        </div>
    `;
}

function createErrorHTML(message, retryAction = null) {
    return `
        <div class="error-container">
            <i class="fa fa-exclamation-triangle fa-2x"></i>
            <p>${message}</p>
            ${
                retryAction
                    ? `<button class="btn btn-primary" onclick="${retryAction}">
                <i class="fa fa-refresh"></i> Retry
            </button>`
                    : ""
            }
        </div>
    `;
}

function createNoDataHTML(icon, title, message) {
    return `
        <div class="no-data-container">
            <i class="fa ${icon} fa-3x"></i>
            <h3>${title}</h3>
            <p>${message}</p>
        </div>
    `;
}

function showNotification(message, type = "info") {
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;

    const iconMap = {
        success: "fa-check-circle",
        error: "fa-exclamation-circle",
        info: "fa-info-circle",
    };

    notification.innerHTML = `
        <div class="notification-content">
            <i class="fa ${iconMap[type] || iconMap.info}"></i>
            <span>${message}</span>
=======
    document.getElementById('filterDate').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterDoctor').value = '';
    
    filteredAppointments = [...appointments];
    displayAppointments();
}

// View appointment details
async function viewAppointment(appointmentId) {
    const result = await apiRequest('get_appointment_details', { appointment_id: appointmentId });
    
    if (result.success) {
        displayAppointmentDetails(result.appointment);
        openModal('appointmentModal');
    } else {
        showMessage(result.message || 'Failed to load appointment details', 'error');
    }
}

// Display appointment details in modal
function displayAppointmentDetails(appointment) {
    const modalBody = document.getElementById('appointmentModalBody');
    modalBody.innerHTML = `
        <div class="appointment-details">
            <div class="detail-section">
                <h3><i class="fa fa-user"></i> Patient Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Name:</label>
                        <span>${escapeHtml(appointment.patient_name)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Gender:</label>
                        <span>${escapeHtml(appointment.gender)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Phone:</label>
                        <span>${escapeHtml(appointment.patient_phone)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span>${escapeHtml(appointment.patient_email)}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fa fa-user-md"></i> Doctor Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Doctor:</label>
                        <span>${escapeHtml(appointment.doc_name)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Specialization:</label>
                        <span>${escapeHtml(appointment.doctor_specialization)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Experience:</label>
                        <span>${escapeHtml(appointment.experience || 'Not specified')}</span>
                    </div>
                    <div class="detail-item">
                        <label>Location:</label>
                        <span>${escapeHtml(appointment.location || 'Not specified')}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fa fa-calendar"></i> Appointment Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Date:</label>
                        <span>${formatDate(appointment.appointment_date)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Time:</label>
                        <span>${formatTime(appointment.appointment_time)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="status-badge status-${appointment.status}">
                            ${capitalizeFirst(appointment.status)}
                        </span>
                    </div>
                    <div class="detail-item">
                        <label>Clinic:</label>
                        <span>${escapeHtml(appointment.clinic_name || 'Not specified')}</span>
                    </div>
                </div>
            </div>
            
>>>>>>> 860a47c5a1bbfafa34d82ce525f8de395d08e09d
        </div>
    `;
}

<<<<<<< HEAD
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
=======
// Update appointment status
function updateStatus(appointmentId) {
    document.getElementById('appointmentId').value = appointmentId;
    document.getElementById('newStatus').value = '';
    // document.getElementById('statusNote').value = '';
    openModal('statusModal');
}

// Save status update
async function saveStatusUpdate() {
    const appointmentId = document.getElementById('appointmentId').value;
    const status = document.getElementById('newStatus').value;
    // const note = document.getElementById('statusNote').value;
    
    if (!status) {
        showMessage('Please select a status', 'error');
        return;
    }
    
    const result = await apiRequest('update_appointment_status', {
        appointment_id: appointmentId,
        status: status,
    });
    
    if (result.success) {
        showMessage('Appointment status updated successfully', 'success');
        closeModal('statusModal');
        loadAppointments(); // Reload appointments
    } else {
        showMessage(result.message || 'Failed to update status', 'error');
    }
}

// Cancel appointment
async function cancelAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) {
        return;
    }
    
    const result = await apiRequest('cancel_appointment', { appointment_id: appointmentId });
    
    if (result.success) {
        showMessage('Appointment cancelled successfully', 'success');
        loadAppointments(); // Reload appointments
    } else {
        showMessage(result.message || 'Failed to cancel appointment', 'error');
    }
}

// Book appointment with specific doctor
function bookAppointmentWithDoctor(doctorId) {
    const doctor = doctors.find(d => d.doc_id == doctorId);
    if (!doctor) {
        showMessage('Doctor not found', 'error');
        return;
    }
    
    // Pre-fill doctor information
    document.getElementById('bookingDoctorId').value = doctorId;
    document.getElementById('bookingDoctorName').value = doctor.doc_name;
    document.getElementById('bookingDoctorSpecialty').value = doctor.doc_specia;
    
    // Clear form
    document.getElementById('bookingForm').reset();
    document.getElementById('bookingDoctorId').value = doctorId;
    document.getElementById('bookingDoctorName').value = doctor.doc_name;
    document.getElementById('bookingDoctorSpecialty').value = doctor.doc_specia;
    
    openModal('bookingModal');
}

// Load available time slots
async function loadTimeSlots() {
    const doctorId = document.getElementById('bookingDoctorId').value;
    const appointmentDate = document.getElementById('appointmentDate').value;
    const timeSelect = document.getElementById('appointmentTime');
    
    if (!doctorId || !appointmentDate) {
        timeSelect.innerHTML = '<option value="">Select Time</option>';
        return;
    }
    
    const result = await apiRequest('get_time_slots', {
        doctor_id: doctorId,
        appointment_date: appointmentDate
    });
    
    timeSelect.innerHTML = '<option value="">Select Time</option>';
    
    if (result.success && result.time_slots) {
        if (result.time_slots.length === 0) {
            timeSelect.innerHTML = '<option value="">No slots available</option>';
        } else {
            result.time_slots.forEach(slot => {
                timeSelect.innerHTML += `<option value="${slot}">${formatTime(slot + ':00')}</option>`;
            });
        }
    } else {
        timeSelect.innerHTML = '<option value="">Error loading slots</option>';
    }
}

// Save appointment booking
async function saveAppointmentBooking() {
    const form = document.getElementById('bookingForm');
    const formData = new FormData(form);
    
    // Convert FormData to object
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    // Validate required fields
    const requiredFields = ['patient_name', 'patient_phone', 'patient_email', 'appointment_date', 'appointment_time', 'gender'];
    for (const field of requiredFields) {
        if (!data[field]) {
            showMessage(`Please fill in the ${field.replace('_', ' ')}`, 'error');
            return;
        }
    }
    
    // Validate email
    if (!isValidEmail(data.patient_email)) {
        showMessage('Please enter a valid email address', 'error');
        return;
    }
    
    // Validate phone
    if (!isValidPhone(data.patient_phone)) {
        showMessage('Please enter a valid phone number', 'error');
        return;
    }
    
    const result = await apiRequest('book_appointment', data);
    
    if (result.success) {
        showMessage('Appointment booked successfully!', 'success');
        closeModal('bookingModal');
        loadAppointments(); // Reload appointments
        form.reset();
    } else {
        showMessage(result.message || 'Failed to book appointment', 'error');
    }
}

// Utility functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

function showMessage(message, type = 'info') {
    const messageDiv = document.getElementById('message');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.display = 'block';
    
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

function showLoading(containerId) {
    document.getElementById(containerId).innerHTML = '<div class="loading">Loading...</div>';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
>>>>>>> 860a47c5a1bbfafa34d82ce525f8de395d08e09d
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(timeString) {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ');
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^\d{10}$/;
    return phoneRegex.test(phone.replace(/\D/g, ''));
}

// Export functions for global access
window.openModal = openModal;
window.closeModal = closeModal;
window.viewAppointment = viewAppointment;
window.updateStatus = updateStatus;
window.cancelAppointment = cancelAppointment;
window.bookAppointmentWithDoctor = bookAppointmentWithDoctor;
window.saveStatusUpdate = saveStatusUpdate;
window.saveAppointmentBooking = saveAppointmentBooking;