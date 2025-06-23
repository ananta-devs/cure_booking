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

    // Filter buttons
    document
        .getElementById("filterBtn")
        ?.addEventListener("click", filterAppointments);
    document
        .getElementById("clearFilterBtn")
        ?.addEventListener("click", clearFilters);

    // Filter inputs
    document
        .getElementById("filterDate")
        ?.addEventListener("change", filterAppointments);
    document
        .getElementById("filterStatus")
        ?.addEventListener("change", filterAppointments);
    document
        .getElementById("filterDoctor")
        ?.addEventListener("change", filterAppointments);

    // Status update form
    document
        .getElementById("statusUpdateForm")
        ?.addEventListener("submit", handleStatusUpdate);

    // Modal events
    document.addEventListener("click", handleModalClicks);
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeAllModals();
        }
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
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
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
    loadDoctors(); // Load doctors when switching to doctors section
}

// Appointment Modal Functions
function viewAppointment(appointmentId) {
    currentAppointmentId = appointmentId;

    const modal = document.getElementById("appointmentModal");
    const modalBody = document.getElementById("appointmentModalBody");

    if (!modal || !modalBody) {
        console.error("Modal elements not found");
        return;
    }

    // Show loading state
    modalBody.innerHTML = `
        <div class="loading-container">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>Loading appointment details...</p>
        </div>
    `;

    modal.style.display = "block";

    // Fetch appointment details
    const formData = new FormData();
    formData.append("action", "get_appointment_details");
    formData.append("appointment_id", appointmentId);

    fetch("api.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => {
            console.log("Response status:", response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text(); // Get text first to see raw response
        })
        .then((text) => {
            console.log("Raw response:", text); // Debug log
            try {
                const data = JSON.parse(text);
                console.log("Parsed data:", data); // Debug log
                if (data.success && data.appointment) {
                    displayAppointmentDetails(data.appointment);
                } else {
                    modalBody.innerHTML = `
                    <div class="error-container">
                        <i class="fa fa-exclamation-triangle fa-2x"></i>
                        <p>Error: ${
                            data.message || "No appointment data found"
                        }</p>
                        <button class="btn btn-secondary" onclick="closeAppointmentModal()">Close</button>
                    </div>
                `;
                }
            } catch (parseError) {
                console.error("JSON parse error:", parseError);
                modalBody.innerHTML = `
                <div class="error-container">
                    <i class="fa fa-exclamation-triangle fa-2x"></i>
                    <p>Invalid response from server</p>
                    <button class="btn btn-secondary" onclick="closeAppointmentModal()">Close</button>
                </div>
            `;
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            modalBody.innerHTML = `
            <div class="error-container">
                <i class="fa fa-exclamation-triangle fa-2x"></i>
                <p>An error occurred while loading appointment details: ${error.message}</p>
                <button class="btn btn-secondary" onclick="closeAppointmentModal()">Close</button>
            </div>
        `;
        });
}

// Enhanced displayAppointmentDetails function with better error handling
function displayAppointmentDetails(appointment) {
    const modalBody = document.getElementById("appointmentModalBody");

    if (!appointment) {
        modalBody.innerHTML = `
            <div class="error-container">
                <i class="fa fa-exclamation-triangle fa-2x"></i>
                <p>No appointment data received</p>
                <button class="btn btn-secondary" onclick="closeAppointmentModal()">Close</button>
            </div>
        `;
        return;
    }

    try {
        // Format date and time with error handling
        let formattedDate = "N/A";
        let formattedTime = "N/A";

        if (appointment.appointment_date) {
            const appointmentDate = new Date(appointment.appointment_date);
            if (!isNaN(appointmentDate.getTime())) {
                formattedDate = appointmentDate.toLocaleDateString("en-GB", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                });
            }
        }

        if (appointment.appointment_time) {
            const appointmentTime = new Date(
                `1970-01-01T${appointment.appointment_time}`
            );
            if (!isNaN(appointmentTime.getTime())) {
                formattedTime = appointmentTime.toLocaleTimeString("en-US", {
                    hour: "numeric",
                    minute: "2-digit",
                    hour12: true,
                });
            }
        }

        // Get patient initials for avatar - fix field name mapping
        const patientName =
            appointment.patient_name ||
            appointment.patient_name ||
            "Unknown Patient";
        const patientInitials = patientName
            .split(" ")
            .map((name) => name.charAt(0))
            .join("")
            .toUpperCase();

        // Get status styling
        const statusClass = getStatusClass(appointment.status);
        const statusIcon = getStatusIcon(appointment.status);

        // Fix field name mapping for doctor and contact info
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
                    <div class="patient-avatar">
                        ${patientInitials}
                    </div>
                    <div class="appointment-info">
                        <h3>${patientName}</h3>
                        <div class="status-badge ${statusClass}">
                            <i class="${statusIcon}"></i>
                            ${appointment.status || "Unknown"}
                        </div>
                    </div>
                </div>
                
                <div class="appointment-details">
                    <div class="detail-row">
                        <i class="fa fa-calendar"></i>
                        <span>Date: ${formattedDate}</span>
                    </div>
                    <div class="detail-row">
                        <i class="fa fa-clock"></i>
                        <span>Time: ${formattedTime}</span>
                    </div>
                    <div class="detail-row">
                        <i class="fa fa-user-md"></i>
                        <span>Doctor: ${doctorName}</span>
                    </div>
                    <div class="detail-row">
                        <i class="fa fa-phone"></i>
                        <span>Contact: ${contactNumber}</span>
                    </div>
                    <div class="detail-row">
                        <i class="fa fa-envelope"></i>
                        <span>Email: ${email}</span>
                    </div>
                    ${
                        appointment.gender
                            ? `
                    <div class="detail-row">
                        <i class="fa fa-user"></i>
                        <span>Gender: ${appointment.gender}</span>
                    </div>
                    `
                            : ""
                    }
                    ${
                        appointment.reason
                            ? `
                    <div class="detail-row">
                        <i class="fa fa-stethoscope"></i>
                        <span>Reason: ${appointment.reason}</span>
                    </div>
                    `
                            : ""
                    }
                    ${
                        appointment.notes
                            ? `
                    <div class="detail-row">
                        <i class="fa fa-sticky-note"></i>
                        <span>Notes: ${appointment.notes}</span>
                    </div>
                    `
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
        modalBody.innerHTML = `
            <div class="error-container">
                <i class="fa fa-exclamation-triangle fa-2x"></i>
                <p>An error occurred while displaying appointment details</p>
                <button class="btn btn-secondary" onclick="closeAppointmentModal()">Close</button>
            </div>
        `;
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

    // Set current status as selected
    if (statusSelect) {
        statusSelect.value = currentStatus;
    }

    modal.style.display = "block";
}

function handleStatusUpdate(event) {
    event.preventDefault();

    const newStatus = document.getElementById("newStatus")?.value;
    const notes = document.getElementById("statusNotes")?.value || "";

    if (!currentAppointmentId || !newStatus) {
        showNotification("Please select a status", "error");
        return;
    }

    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';
    submitBtn.disabled = true;

    const formData = new FormData();
    formData.append("action", "update_appointment_status");
    formData.append("appointment_id", currentAppointmentId);
    formData.append("status", newStatus);
    formData.append("notes", notes);

    fetch("api.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                showNotification(
                    "Appointment status updated successfully!",
                    "success"
                );
                closeModal("statusModal");
                loadAppointments(); // Refresh the appointments list

                // If appointment modal is open, refresh its content
                if (
                    document.getElementById("appointmentModal").style
                        .display === "block"
                ) {
                    viewAppointment(currentAppointmentId);
                }
            } else {
                showNotification(
                    data.message || "Failed to update appointment status",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showNotification(
                "An error occurred while updating the appointment",
                "error"
            );
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}

// Load and display appointments
function loadAppointments() {
    const appointmentsContainer = document.getElementById(
        "appointmentsContainer"
    );

    if (!appointmentsContainer) {
        console.error("Appointments container not found");
        return;
    }

    // Show loading state
    appointmentsContainer.innerHTML = `
        <div class="loading-container">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>Loading appointments...</p>
        </div>
    `;

    const formData = new FormData();
    formData.append("action", "get_appointments");

    fetch("api.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (data.success && data.appointments) {
                displayAppointments(data.appointments);
            } else {
                appointmentsContainer.innerHTML = `
                    <div class="no-data-container">
                        <i class="fa fa-calendar-times fa-3x"></i>
                        <h3>No appointments found</h3>
                        <p>${
                            data.message ||
                            "There are no appointments to display."
                        }</p>
                    </div>
                `;
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            appointmentsContainer.innerHTML = `
                <div class="error-container">
                    <i class="fa fa-exclamation-triangle fa-2x"></i>
                    <p>Failed to load appointments: ${error.message}</p>
                    <button class="btn btn-primary" onclick="loadAppointments()">
                        <i class="fa fa-refresh"></i> Retry
                    </button>
                </div>
            `;
        });
}

function displayAppointments(appointments) {
    const appointmentsContainer = document.getElementById(
        "appointmentsContainer"
    );

    if (!appointments || appointments.length === 0) {
        appointmentsContainer.innerHTML = `
            <div class="no-data-container">
                <i class="fa fa-calendar-times fa-3x"></i>
                <h3>No appointments found</h3>
                <p>There are no appointments to display.</p>
            </div>
        `;
        return;
    }

    const appointmentsHTML = appointments
        .map((appointment) => {
            const statusClass = getStatusClass(appointment.status);
            const statusIcon = getStatusIcon(appointment.status);

            // Format date and time - fix field name mapping
            let formattedDate = "N/A";
            let formattedTime = "N/A";

            if (appointment.appointment_date) {
                const date = new Date(appointment.appointment_date);
                if (!isNaN(date.getTime())) {
                    formattedDate = date.toLocaleDateString("en-GB");
                }
            }

            if (appointment.appointment_time) {
                const time = new Date(
                    `1970-01-01T${appointment.appointment_time}`
                );
                if (!isNaN(time.getTime())) {
                    formattedTime = time.toLocaleTimeString("en-US", {
                        hour: "numeric",
                        minute: "2-digit",
                        hour12: true,
                    });
                }
            }

            // Fix field name mapping
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

    appointmentsContainer.innerHTML = appointmentsHTML;
}

// Filter Functions
function filterAppointments() {
    const dateFilter = document.getElementById("filterDate")?.value;
    const statusFilter = document.getElementById("filterStatus")?.value;
    const doctorFilter = document.getElementById("filterDoctor")?.value;

    const formData = new FormData();
    formData.append("action", "get_appointments");

    if (dateFilter) formData.append("date_filter", dateFilter);
    if (statusFilter) formData.append("status_filter", statusFilter);
    if (doctorFilter) formData.append("doctor_filter", doctorFilter);

    const appointmentsContainer = document.getElementById(
        "appointmentsContainer"
    );
    appointmentsContainer.innerHTML = `
        <div class="loading-container">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>Filtering appointments...</p>
        </div>
    `;

    fetch("api.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (data.success && data.appointments) {
                displayAppointments(data.appointments);
            } else {
                appointmentsContainer.innerHTML = `
                    <div class="no-data-container">
                        <i class="fa fa-filter"></i>
                        <h3>No appointments match your filters</h3>
                        <p>Try adjusting your filter criteria.</p>
                    </div>
                `;
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            appointmentsContainer.innerHTML = `
                <div class="error-container">
                    <i class="fa fa-exclamation-triangle fa-2x"></i>
                    <p>Failed to filter appointments: ${error.message}</p>
                </div>
            `;
        });
}

function clearFilters() {
    // Clear filter inputs
    const filterDate = document.getElementById("filterDate");
    const filterStatus = document.getElementById("filterStatus");
    const filterDoctor = document.getElementById("filterDoctor");

    if (filterDate) filterDate.value = "";
    if (filterStatus) filterStatus.value = "";
    if (filterDoctor) filterDoctor.value = ""; // Fixed typo here

    // Reload all appointments
    loadAppointments();
}

// Modal Functions
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "none";

        // Clear form data if it's a form modal
        if (modalId === "statusModal") {
            const form = document.getElementById("statusUpdateForm");
            if (form) form.reset();
        }
    }
}

function closeAppointmentModal() {
    closeModal("appointmentModal");
}

function closeAllModals() {
    const modals = [
        "appointmentModal",
        "statusModal",
        "doctorModal",
        "bookingModal",
    ];
    modals.forEach((modalId) => closeModal(modalId));
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

function showNotification(message, type = "info") {
    // Create notification element
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fa ${
                type === "success"
                    ? "fa-check-circle"
                    : type === "error"
                    ? "fa-exclamation-circle"
                    : "fa-info-circle"
            }"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fa fa-times"></i>
        </button>
    `;

    // Add to page
    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Doctor section functions
function loadDoctors() {
    const doctorsContainer = document.getElementById("doctorsContainer");

    if (!doctorsContainer) return;

    doctorsContainer.innerHTML = `
        <div class="loading-container">
            <i class="fa fa-spinner fa-spin fa-2x"></i>
            <p>Loading doctors...</p>
        </div>
    `;

    const formData = new FormData();
    formData.append("action", "get_doctors");

    fetch("api.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (data.success && data.doctors) {
                displayDoctors(data.doctors);
            } else {
                doctorsContainer.innerHTML = `
                    <div class="no-data-container">
                        <i class="fa fa-user-md fa-3x"></i>
                        <h3>No doctors found</h3>
                        <p>${
                            data.message || "There are no doctors to display."
                        }</p>
                    </div>
                `;
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            doctorsContainer.innerHTML = `
                <div class="error-container">
                    <i class="fa fa-exclamation-triangle fa-2x"></i>
                    <p>Failed to load doctors: ${error.message}</p>
                    <button class="btn btn-primary" onclick="loadDoctors()">
                        <i class="fa fa-refresh"></i> Retry
                    </button>
                </div>
            `;
        });
}

function displayDoctors(doctors) {
    const doctorsContainer = document.getElementById("doctorsContainer");

    if (!doctors || doctors.length === 0) {
        doctorsContainer.innerHTML = `
            <div class="no-data-container">
                <i class="fa fa-user-md fa-3x"></i>
                <h3>No doctors found</h3>
                <p>There are no doctors to display.</p>
            </div>
        `;
        return;
    }

    const doctorsHTML = doctors
        .map(
            (doctor) => `
        <div class="doctor-card">
            <div class="doctor-avatar">
                ${
                    doctor.doc_name || doctor.name
                        ? (doctor.doc_name || doctor.name)
                              .split(" ")
                              .map((n) => n[0])
                              .join("")
                              .toUpperCase()
                        : "DR"
                }
            </div>
            <div class="doctor-info">
                <h4>${doctor.doc_name || doctor.name || "Unknown Doctor"}</h4>
                <p class="specialty">${
                    doctor.doc_specia || doctor.specialty || "General Practice"
                }</p>
                <p class="contact">
                    <i class="fa fa-phone"></i>
                    ${doctor.contact_number || "No contact provided"}
                </p>
                <p class="email">
                    <i class="fa fa-envelope"></i>
                    ${doctor.doc_email || doctor.email || "No email provided"}
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
    `
        )
        .join("");

    doctorsContainer.innerHTML = doctorsHTML;
}
