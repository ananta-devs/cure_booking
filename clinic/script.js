// Global variables
let currentAppointmentId = null;
let currentAppointmentStatus = null;

// DOM Content Loaded Event
document.addEventListener("DOMContentLoaded", function () {
    initializeEventListeners();
    showAppointmentsSection();
    loadAppointments();
});

// Initialize all event listeners
function initializeEventListeners() {
    // Navigation buttons
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
    });
}

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
    }

    try {
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
    }
}

// Status Modal Functions
function openStatusModal(appointmentId, currentStatus) {
    currentAppointmentId = appointmentId;
    currentAppointmentStatus = currentStatus;

    const modal = document.getElementById("statusModal");
    const statusSelect = document.getElementById("newStatus");

    if (!modal) {
        console.error("Status modal not found");
        return;
    }

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
                    </div>
                    <div class="status-badge ${statusClass}">
                        <i class="${statusIcon}"></i>
                        ${appointment.status || "Unknown"}
                    </div>
                </div>
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
}

function clearFilters() {
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
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fa fa-times"></i>
        </button>
    `;

    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}
