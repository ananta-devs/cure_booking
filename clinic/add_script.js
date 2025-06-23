// Book Appointment JavaScript Functions

// Function to save appointment booking
function saveAppointmentBooking() {
    const form = document.getElementById("bookingForm");
    const formData = new FormData(form);

    // Add action parameter
    formData.append("action", "book_appointment");

    // Validate required fields
    const requiredFields = [
        "patient_name",
        "patient_phone",
        "patient_email",
        "gender",
        "appointment_date",
        "appointment_time",
    ];

    for (let field of requiredFields) {
        const value = formData.get(field);
        if (!value || value.trim() === "") {
            showMessage(
                `Please fill in the ${field.replace("_", " ")} field.`,
                "error"
            );
            return;
        }
    }

    // Validate email format
    const email = formData.get("patient_email");
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showMessage("Please enter a valid email address.", "error");
        return;
    }

    // Validate phone number (10 digits)
    const phone = formData.get("patient_phone");
    const phoneRegex = /^[0-9]{10}$/;
    if (!phoneRegex.test(phone)) {
        showMessage("Please enter a valid 10-digit phone number.", "error");
        return;
    }

    // Validate appointment date (not in past)
    const appointmentDate = new Date(formData.get("appointment_date"));
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (appointmentDate < today) {
        showMessage("Appointment date cannot be in the past.", "error");
        return;
    }

    // Show loading state
    const submitButton = document.querySelector("#bookingModal .btn-primary");
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Booking...';
    submitButton.disabled = true;

    // Send AJAX request
    fetch("api.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                showMessage(data.message, "success");
                closeBookingModal();

                // Refresh appointments list
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage(
                    data.message || "Failed to book appointment.",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            showMessage(
                "An error occurred while booking the appointment.",
                "error"
            );
        })
        .finally(() => {
            // Reset button state
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        });
}

// Function to show booking modal - FIXED VERSION
function showBookingModal(doctorData) {
    console.log("showBookingModal called with:", doctorData);

    // Parse doctor data if it's a string (from PHP JSON encoding)
    let doctor;
    if (typeof doctorData === "string") {
        try {
            doctor = JSON.parse(doctorData);
        } catch (error) {
            console.error("Error parsing doctor data:", error);
            showMessage("Error loading doctor information.", "error");
            return;
        }
    } else {
        doctor = doctorData;
    }

    const modal = document.getElementById("bookingModal");
    if (!modal) {
        console.error("Booking modal not found");
        showMessage("Modal not found. Please refresh the page.", "error");
        return;
    }

    // Populate doctor information
    const doctorIdField = document.getElementById("bookingDoctorId");
    const doctorNameField = document.getElementById("bookingDoctorName");
    const doctorSpecialtyField = document.getElementById(
        "bookingDoctorSpecialty"
    );

    if (doctorIdField) doctorIdField.value = doctor.doc_id || "";
    if (doctorNameField) doctorNameField.value = `Dr. ${doctor.doc_name || ""}`;
    if (doctorSpecialtyField)
        doctorSpecialtyField.value = doctor.doc_specia || "";

    // Get clinic info from session or set default
    const clinicIdField = document.getElementById("bookingClinicId");
    const clinicNameField = document.getElementById("bookingClinicName");

    if (clinicIdField)
        clinicIdField.value = typeof clinic_id !== "undefined" ? clinic_id : "";
    if (clinicNameField)
        clinicNameField.value =
            typeof clinic_name !== "undefined" ? clinic_name : "Current Clinic";

    // Reset form
    const form = document.getElementById("bookingForm");
    if (form) form.reset();

    // Re-populate the read-only fields after reset
    if (doctorIdField) doctorIdField.value = doctor.doc_id || "";
    if (doctorNameField) doctorNameField.value = `Dr. ${doctor.doc_name || ""}`;
    if (doctorSpecialtyField)
        doctorSpecialtyField.value = doctor.doc_specia || "";
    if (clinicIdField)
        clinicIdField.value = typeof clinic_id !== "undefined" ? clinic_id : "";
    if (clinicNameField)
        clinicNameField.value =
            typeof clinic_name !== "undefined" ? clinic_name : "Current Clinic";

    // Set minimum date to today
    const today = new Date().toISOString().split("T")[0];
    const appointmentDateField = document.getElementById("appointmentDate");
    if (appointmentDateField) appointmentDateField.setAttribute("min", today);

    // Clear time slots
    const timeSelect = document.getElementById("appointmentTime");
    if (timeSelect)
        timeSelect.innerHTML = '<option value="">Select Time</option>';

    // Show modal
    modal.style.display = "block";
    modal.classList.add("show");

    console.log("Modal should now be visible");
}

// Function to close booking modal
function closeBookingModal() {
    const modal = document.getElementById("bookingModal");
    if (modal) {
        modal.style.display = "none";
        modal.classList.remove("show");
    }

    // Reset form
    const form = document.getElementById("bookingForm");
    if (form) form.reset();
}

// Function to load available time slots when date is selected
function loadTimeSlots() {
    const doctorId = document.getElementById("bookingDoctorId").value;
    const appointmentDate = document.getElementById("appointmentDate").value;
    const timeSelect = document.getElementById("appointmentTime");

    if (!doctorId || !appointmentDate) {
        timeSelect.innerHTML = '<option value="">Select Time</option>';
        return;
    }

    // Show loading
    timeSelect.innerHTML = '<option value="">Loading...</option>';
    timeSelect.disabled = true;

    const formData = new FormData();
    formData.append("action", "get_time_slots");
    formData.append("doctor_id", doctorId);
    formData.append("appointment_date", appointmentDate);

    fetch("api.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            timeSelect.innerHTML = '<option value="">Select Time</option>';

            if (data.success && data.time_slots.length > 0) {
                data.time_slots.forEach((slot) => {
                    const option = document.createElement("option");
                    option.value = slot;

                    // Format time for display
                    const timeObj = new Date(`2000-01-01 ${slot}:00`);
                    const displayTime = timeObj.toLocaleTimeString("en-US", {
                        hour: "numeric",
                        minute: "2-digit",
                        hour12: true,
                    });

                    option.textContent = displayTime;
                    timeSelect.appendChild(option);
                });
            } else {
                const option = document.createElement("option");
                option.value = "";
                option.textContent = "No slots available";
                option.disabled = true;
                timeSelect.appendChild(option);
            }
        })
        .catch((error) => {
            console.error("Error loading time slots:", error);
            timeSelect.innerHTML =
                '<option value="">Error loading slots</option>';
        })
        .finally(() => {
            timeSelect.disabled = false;
        });
}

// Function to show messages
function showMessage(message, type = "info") {
    const messageDiv = document.getElementById("message");

    // Clear existing messages
    messageDiv.innerHTML = "";

    // Create message element
    const msgElement = document.createElement("div");
    msgElement.className = `alert alert-${type}`;
    msgElement.innerHTML = `
        <i class="fa fa-${
            type === "success"
                ? "check-circle"
                : type === "error"
                ? "exclamation-circle"
                : "info-circle"
        }"></i>
        ${message}
        <button type="button" class="close" onclick="this.parentElement.remove()">
            <span>&times;</span>
        </button>
    `;

    messageDiv.appendChild(msgElement);

    // Auto-hide success messages after 5 seconds
    if (type === "success") {
        setTimeout(() => {
            if (msgElement.parentElement) {
                msgElement.remove();
            }
        }, 5000);
    }

    // Scroll to message
    messageDiv.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

// Event listeners
document.addEventListener("DOMContentLoaded", function () {
    // Date change event for time slot loading
    const appointmentDateInput = document.getElementById("appointmentDate");
    if (appointmentDateInput) {
        appointmentDateInput.addEventListener("change", loadTimeSlots);
    }

    // Modal close events
    const bookingModal = document.getElementById("bookingModal");
    if (bookingModal) {
        // Close modal when clicking outside
        window.addEventListener("click", function (event) {
            if (event.target === bookingModal) {
                closeBookingModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener("keydown", function (event) {
            if (
                event.key === "Escape" &&
                bookingModal.style.display === "block"
            ) {
                closeBookingModal();
            }
        });
    }

    // Form validation on input
    const bookingForm = document.getElementById("bookingForm");
    if (bookingForm) {
        // Real-time phone validation
        const phoneInput = document.getElementById("patientPhone");
        if (phoneInput) {
            phoneInput.addEventListener("input", function () {
                this.value = this.value.replace(/[^0-9]/g, "").slice(0, 10);
            });
        }

        // Real-time email validation
        const emailInput = document.getElementById("patientEmail");
        if (emailInput) {
            emailInput.addEventListener("blur", function () {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailRegex.test(this.value)) {
                    this.style.borderColor = "#dc3545";
                    showMessage("Please enter a valid email address.", "error");
                } else {
                    this.style.borderColor = "";
                }
            });
        }
    }
});

// Additional utility functions for the booking system

// Function to validate form before submission
function validateBookingForm() {
    const form = document.getElementById("bookingForm");
    const inputs = form.querySelectorAll("input[required], select[required]");
    let isValid = true;

    inputs.forEach((input) => {
        if (!input.value.trim()) {
            input.style.borderColor = "#dc3545";
            isValid = false;
        } else {
            input.style.borderColor = "";
        }
    });

    return isValid;
}

// Function to reset booking form
function resetBookingForm() {
    const form = document.getElementById("bookingForm");
    form.reset();

    // Clear any error styling
    const inputs = form.querySelectorAll("input, select, textarea");
    inputs.forEach((input) => {
        input.style.borderColor = "";
    });

    // Reset time slots
    const timeSelect = document.getElementById("appointmentTime");
    timeSelect.innerHTML = '<option value="">Select Time</option>';
}
