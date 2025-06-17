let currentEditId = null;
let selectedDoctorForAppointment = null;

document.addEventListener("DOMContentLoaded", function () {
    setupEventListeners();
    showAppointments(); // Show appointments section by default
});

function setupEventListeners() {
    document.getElementById("viewAppointmentsBtn").addEventListener("click", showAppointments);
    document.getElementById("addAppointmentBtn").addEventListener("click", showAppointmentForm);
    document.getElementById("allDoctorsBtn").addEventListener("click", showDoctors);
    document.getElementById("cancelAppointmentBtn").addEventListener("click", showAppointments);

    document.getElementById("appointmentForm").addEventListener("submit", handleAppointmentSubmit);
    document.getElementById("specialityType").addEventListener("change", handleSpecialtyChange);
    document.getElementById("doctor").addEventListener("change", handleDoctorChange);
    document.getElementById("preferredDate").addEventListener("change", handleDateChange);

    document.getElementById("filterBtn").addEventListener("click", filterAppointments);
}

function showAppointments() {
    hideAllSections();
    document.getElementById("appointmentsSection").style.display = "block";
    updateButtonStates("viewAppointmentsBtn");
}

function showAppointmentForm() {
    hideAllSections();
    document.getElementById("appointmentFormSection").style.display = "block";
    resetForm();
    currentEditId = null;
    updateButtonStates("addAppointmentBtn");
}

function showDoctors() {
    hideAllSections();
    document.getElementById("doctorsSection").style.display = "block";
    updateButtonStates("allDoctorsBtn");
    setTimeout(addDoctorSearch, 100);
}

function hideAllSections() {
    document.getElementById("appointmentsSection").style.display = "none";
    document.getElementById("appointmentFormSection").style.display = "none";
    document.getElementById("doctorsSection").style.display = "none";
}

function updateButtonStates(activeButtonId) {
    const buttons = ["viewAppointmentsBtn", "addAppointmentBtn", "allDoctorsBtn"];
    buttons.forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.classList.remove("active");
        }
    });
    
    const activeButton = document.getElementById(activeButtonId);
    if (activeButton) {
        activeButton.classList.add("active");
    }
}

function handleSpecialtyChange() {
    const specialty = document.getElementById("specialityType").value;
    const doctorSelect = document.getElementById("doctor");

    doctorSelect.innerHTML = '<option value="">Select Doctor</option>';

    if (specialty) {
        const filteredDoctors = doctors.filter(d => d.doc_specia === specialty);
        filteredDoctors.forEach(doctor => {
            const option = document.createElement("option");
            option.value = doctor.doc_id;
            option.textContent = doctor.doc_name;
            doctorSelect.appendChild(option);
        });
    }

    document.getElementById("time").innerHTML = '<option value="">Select Time</option>';
    document.getElementById("doctorInfo").style.display = "none";
}

function handleDoctorChange() {
    const doctorId = document.getElementById("doctor").value;
    const doctorInfo = document.getElementById("doctorInfo");

    if (doctorId) {
        const doctor = doctors.find(d => d.doc_id == doctorId);
        if (doctor) {
            doctorInfo.innerHTML = `
                <strong>${doctor.doc_name}</strong><br>
                ${doctor.doc_specia} • ${doctor.experience} years experience<br>
                ${doctor.location} • ${doctor.education}
            `;
            doctorInfo.style.display = "block";
        }

        if (document.getElementById("preferredDate").value) {
            loadTimeSlots();
        }
    } else {
        doctorInfo.style.display = "none";
        document.getElementById("time").innerHTML = '<option value="">Select Time</option>';
    }
}

function handleDateChange() {
    const date = document.getElementById("preferredDate").value;
    const doctorId = document.getElementById("doctor").value;

    if (date && doctorId) {
        loadTimeSlots();
    }
}

function loadTimeSlots() {
    const timeSelect = document.getElementById("time");
    const timeSlots = [
        "09:00", "09:30", "10:00", "10:30", "11:00", "11:30",
        "12:00", "12:30", "14:00", "14:30", "15:00", "15:30",
        "16:00", "16:30", "17:00", "17:30"
    ];

    timeSelect.innerHTML = '<option value="">Select Time</option>';

    timeSlots.forEach(slot => {
        const option = document.createElement("option");
        option.value = slot;
        option.textContent = formatTime(slot);
        timeSelect.appendChild(option);
    });
}

function handleAppointmentSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const appointmentData = {
        action: currentEditId ? "update_appointment" : "book_appointment",
        patient_name: formData.get("firstName"),
        patient_phone: formData.get("phone"),
        patient_email: formData.get("email"),
        doctor_id: formData.get("doctor"),
        appointment_date: formData.get("preferredDate"),
        appointment_time: formData.get("time"),
        gender: formData.get("gender"),
    };

    if (currentEditId) {
        appointmentData.id = currentEditId;
    }

    if (!validateAppointmentForm(appointmentData)) {
        return;
    }

    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(appointmentData),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, "success");
                resetForm();
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message, "error");
            }
        })
        .catch(error => {
            showMessage("An error occurred. Please try again.", "error");
        });
}

function validateAppointmentForm(data) {
    let isValid = true;

    document.querySelectorAll(".error").forEach(error => {
        error.style.display = "none";
    });

    if (!data.patient_name || data.patient_name.trim().length < 2) {
        showFieldError("firstNameError", "Please enter a valid patient name");
        isValid = false;
    }

    if (!data.patient_phone || !isValidPhone(data.patient_phone)) {
        showFieldError("phoneError", "Please enter a valid 10-digit phone number");
        isValid = false;
    }

    if (!data.patient_email || !isValidEmail(data.patient_email)) {
        showFieldError("emailError", "Please enter a valid email address");
        isValid = false;
    }

    if (!data.gender) {
        showFieldError("genderError", "Please select gender");
        isValid = false;
    }

    if (!data.doctor_id) {
        showMessage("Please select a doctor", "error");
        isValid = false;
    }

    if (!data.appointment_date) {
        showFieldError("preferredDateError", "Please select a preferred date");
        isValid = false;
    }

    if (!data.appointment_time) {
        showMessage("Please select a time slot", "error");
        isValid = false;
    }

    return isValid;
}

function showFieldError(errorId, message) {
    const errorElement = document.getElementById(errorId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = "block";
    }
}

function isValidPhone(phone) {
    const phoneRegex = /^[0-9]{10,11}$/;
    return phoneRegex.test(phone.replace(/\D/g, ""));
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function resetForm() {
    document.getElementById("appointmentForm").reset();
    document.getElementById("doctorInfo").style.display = "none";
    document.getElementById("time").innerHTML = '<option value="">Select Time</option>';
    document.querySelectorAll(".error").forEach(error => {
        error.style.display = "none";
    });
}

function showMessage(message, type) {
    const messageDiv = document.getElementById("message");
    messageDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    messageDiv.style.display = "block";

    setTimeout(() => {
        messageDiv.style.display = "none";
    }, 5000);
}

function filterAppointments() {
    const filterDate = document.getElementById("filterDate").value;
    const filterStatus = document.getElementById("filterStatus").value;

    const rows = document.querySelectorAll("#appointmentsTableBody tr");

    rows.forEach(row => {
        let showRow = true;

        // Date filter
        if (filterDate) {
            const appointmentDate = row.cells[3].textContent.trim();
            const formattedFilterDate = new Date(filterDate).toLocaleDateString("en-GB");
            if (appointmentDate !== formattedFilterDate) {
                showRow = false;
            }
        }

        // Status filter - Fixed logic
        if (filterStatus && showRow) {
            const statusBadge = row.querySelector(".status-badge");
            if (statusBadge) {
                // Get the status from the badge text content (more reliable)
                const statusText = statusBadge.textContent.trim().toLowerCase();
                if (statusText !== filterStatus.toLowerCase()) {
                    showRow = false;
                }
            }
        }

        row.style.display = showRow ? "" : "none";
    });
}


function viewAppointment(id) {
    const appointment = appointments.find(app => app.id == id);
    if (!appointment) return;

    const details = `
        <strong>Appointment Details</strong><br><br>
        <strong>ID:</strong> ${appointment.id}<br>
        <strong>Patient:</strong> ${appointment.patient_name}<br>
        <strong>Phone:</strong> ${appointment.patient_phone}<br>
        <strong>Email:</strong> ${appointment.patient_email}<br>
        <strong>Doctor:</strong> ${appointment.doc_name}<br>
        <strong>Specialization:</strong> ${appointment.doctor_specialization}<br>
        <strong>Date:</strong> ${new Date(appointment.appointment_date).toLocaleDateString("en-GB")}<br>
        <strong>Time:</strong> ${formatTime(appointment.appointment_time)}<br>
        <strong>Status:</strong> ${appointment.status.toUpperCase()}<br>
    `;

    showModal("Appointment Details", details);
}

function editAppointment(id) {
    const appointment = appointments.find(app => app.id == id);
    if (!appointment) return;

    currentEditId = id;
    showAppointmentForm();

    setTimeout(() => {
        document.getElementById("firstName").value = appointment.patient_name;
        document.getElementById("phone").value = appointment.patient_phone;
        document.getElementById("email").value = appointment.patient_email;
        document.getElementById("preferredDate").value = appointment.appointment_date;

        const genderRadio = document.querySelector(`input[name="gender"][value="${appointment.gender}"]`);
        if (genderRadio) genderRadio.checked = true;

        const doctor = doctors.find(d => d.doc_id == appointment.doc_id);
        if (doctor) {
            document.getElementById("specialityType").value = doctor.doc_specia;
            handleSpecialtyChange();

            setTimeout(() => {
                document.getElementById("doctor").value = appointment.doc_id;
                handleDoctorChange();

                setTimeout(() => {
                    document.getElementById("time").value = appointment.appointment_time;
                }, 100);
            }, 100);
        }

        showMessage("Editing appointment. Make changes and submit to update.", "info");
    }, 100);
}

function deleteAppointment(id) {
    if (confirm("Are you sure you want to delete this appointment?")) {
        fetch("", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "delete_appointment",
                id: id,
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, "success");
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(data.message, "error");
                }
            })
            .catch(error => {
                showMessage("An error occurred. Please try again.", "error");
            });
    }
}

function showModal(title, content) {
    let modal = document.getElementById("appointmentModal");
    if (!modal) {
        modal = document.createElement("div");
        modal.id = "appointmentModal";
        modal.className = "modal";
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalTitle">${title}</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body" id="modalBody">${content}</div>
                <div class="modal-footer">
                    <button class="btn-secondary" onclick="closeModal()">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector(".close").addEventListener("click", closeModal);
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    } else {
        document.getElementById("modalTitle").textContent = title;
        document.getElementById("modalBody").innerHTML = content;
    }

    modal.style.display = "block";
}

function closeModal() {
    const modal = document.getElementById("appointmentModal");
    if (modal) {
        modal.style.display = "none";
    }
}

function formatTime(timeString) {
    if (!timeString) return "";

    const [hours, minutes] = timeString.split(":");
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? "PM" : "AM";
    const displayHour = hour % 12 || 12;

    return `${displayHour}:${minutes} ${ampm}`;
}

function showInputError(inputId, errorMessage) {
    const input = document.getElementById(inputId);
    const errorDiv = input.parentNode.querySelector(".error");

    if (input && errorDiv) {
        input.classList.add("error-input");
        errorDiv.textContent = errorMessage;
        errorDiv.style.display = "block";
    }
}

function clearInputError(inputId) {
    const input = document.getElementById(inputId);
    const errorDiv = input.parentNode.querySelector(".error");

    if (input && errorDiv) {
        input.classList.remove("error-input");
        errorDiv.style.display = "none";
    }
}

function showDoctorModal(doctor) {
    const modal = document.getElementById('doctorModal');
    const modalBody = document.getElementById('doctorModalBody');
    
    selectedDoctorForAppointment = doctor;
    
    const doctorProfileHTML = `
        <div class="doctor-profile-section">
            <div class="doctor-profile-image">
                <img src="${doctor.doc_img ? 'uploads/doctors/' + doctor.doc_img : 'https://via.placeholder.com/120'}" 
                     alt="${doctor.doc_name}" />
            </div>
            <div class="doctor-profile-info">
                <div class="doctor-profile-name">${doctor.doc_name}</div>
                <div class="doctor-profile-specialty">${doctor.doc_specia}</div>
                <div class="doctor-profile-experience">${doctor.experience} Years Experience</div>
            </div>
        </div>
        
        <div class="doctor-details-grid">
            <div class="doctor-detail-item">
                <div class="doctor-detail-label">Email</div>
                <div class="doctor-detail-value">${doctor.doc_email}</div>
            </div>
            <div class="doctor-detail-item">
                <div class="doctor-detail-label">Phone</div>
                <div class="doctor-detail-value">${doctor.doc_phone || 'Not provided'}</div>
            </div>
            <div class="doctor-detail-item">
                <div class="doctor-detail-label">Location</div>
                <div class="doctor-detail-value">${doctor.location}</div>
            </div>
            <div class="doctor-detail-item">
                <div class="doctor-detail-label">Education</div>
                <div class="doctor-detail-value">${doctor.education}</div>
            </div>
            <div class="doctor-detail-item">
                <div class="doctor-detail-label">Specialization</div>
                <div class="doctor-detail-value">${doctor.doc_specia}</div>
            </div>
            <div class="doctor-detail-item">
                <div class="doctor-detail-label">Experience</div>
                <div class="doctor-detail-value">${doctor.experience} years in practice</div>
            </div>
        </div>
    `;
    
    modalBody.innerHTML = doctorProfileHTML;
    modal.style.display = 'block';
    
    const modalContent = modal.querySelector('.doctor-modal-content');
    modalContent.style.animation = 'modalSlideIn 0.3s ease-out';
}

function closeDoctorModal() {
    const modal = document.getElementById('doctorModal');
    const modalContent = modal.querySelector('.doctor-modal-content');
    
    modalContent.style.animation = 'modalSlideOut 0.3s ease-in';
    
    setTimeout(() => {
        modal.style.display = 'none';
        selectedDoctorForAppointment = null;
    }, 250);
}

function bookAppointmentWithDoctor() {
    if (selectedDoctorForAppointment) {
        closeDoctorModal();
        showAppointmentForm();
        
        setTimeout(() => {
            document.getElementById('specialityType').value = selectedDoctorForAppointment.doc_specia;
            handleSpecialtyChange();
            
            setTimeout(() => {
                document.getElementById('doctor').value = selectedDoctorForAppointment.doc_id;
                handleDoctorChange();
            }, 100);
        }, 100);
        
        showMessage(`Booking appointment with Dr. ${selectedDoctorForAppointment.doc_name}`, 'info');
    }
}

function bookAppointmentWithDoctorFromCard(doctorId, specialty) {
    const doctor = doctors.find(d => d.doc_id == doctorId);
    if (doctor) {
        selectedDoctorForAppointment = doctor;
        showAppointmentForm();
        
        setTimeout(() => {
            document.getElementById('specialityType').value = specialty;
            handleSpecialtyChange();
            
            setTimeout(() => {
                document.getElementById('doctor').value = doctorId;
                handleDoctorChange();
            }, 100);
        }, 100);
        
        showMessage(`Booking appointment with Dr. ${doctor.doc_name}`, 'info');
    }
}

function searchDoctors(searchTerm) {
    const doctorCards = document.querySelectorAll('.doctor-card');
    
    doctorCards.forEach(card => {
        const doctorName = card.querySelector('.doctor-name').textContent.toLowerCase();
        const doctorSpecialty = card.querySelector('.doctor-specialty').textContent.toLowerCase();
        const searchLower = searchTerm.toLowerCase();
        
        if (doctorName.includes(searchLower) || doctorSpecialty.includes(searchLower)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function addDoctorSearch() {
    const doctorsSection = document.getElementById('doctorsSection');
    const existingSearch = doctorsSection.querySelector('.doctor-search');
    
    if (!existingSearch) {
        const searchHTML = `
            <div class="doctor-search" style="margin-bottom: 20px;">
                <input type="text" id="doctorSearchInput" placeholder="Search doctors by name or specialty..." 
                       style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 300px; max-width: 100%;">
                <button onclick="clearDoctorSearch()" style="margin-left: 10px; padding: 10px 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                    Clear
                </button>
            </div>
        `;
        
        const doctorsContainer = doctorsSection.querySelector('.doctors-container');
        doctorsContainer.insertAdjacentHTML('afterbegin', searchHTML);
        
        document.getElementById('doctorSearchInput').addEventListener('input', function() {
            searchDoctors(this.value);
        });
    }
}

function clearDoctorSearch() {
    document.getElementById('doctorSearchInput').value = '';
    document.querySelectorAll('.doctor-card').forEach(card => {
        card.style.display = 'block';
    });
}

// Event listeners
document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        closeModal();
        const doctorModal = document.getElementById('doctorModal');
        if (doctorModal && doctorModal.style.display === 'block') {
            closeDoctorModal();
        }
    }

    if ((e.ctrlKey || e.metaKey) && e.key === "n") {
        e.preventDefault();
        showAppointmentForm();
    }

    if ((e.ctrlKey || e.metaKey) && e.key === "l") {
        e.preventDefault();
        showAppointments();
    }
});

document.addEventListener('click', function(e) {
    const appointmentModal = document.getElementById('appointmentModal');
    if (e.target === appointmentModal) {
        closeModal();
    }
    
    const doctorModal = document.getElementById('doctorModal');
    if (e.target === doctorModal) {
        closeDoctorModal();
    }
});

// Real-time form validation
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("appointmentForm");
    if (form) {
        const phoneInput = document.getElementById("phone");
        if (phoneInput) {
            phoneInput.addEventListener("input", function () {
                const phone = this.value.replace(/\D/g, "");
                if (phone.length >= 10) {
                    clearInputError("phone");
                }
            });
        }

        const emailInput = document.getElementById("email");
        if (emailInput) {
            emailInput.addEventListener("blur", function () {
                if (isValidEmail(this.value)) {
                    clearInputError("email");
                } else {
                    showInputError("email", "Please enter a valid email address");
                }
            });
        }

        const nameInput = document.getElementById("firstName");
        if (nameInput) {
            nameInput.addEventListener("input", function () {
                if (this.value.trim().length >= 2) {
                    clearInputError("firstName");
                }
            });
        }
    }
});

// Auto-refresh appointments every 5 minutes
setInterval(function () {
    if (document.getElementById("appointmentsSection").style.display !== "none") {
        location.reload();
    }
}, 300000);

// Add modal animation styles
const slideOutAnimation = `
@keyframes modalSlideOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-30px);
    }
}
`;

if (!document.querySelector('#modalAnimations')) {
    const style = document.createElement('style');
    style.id = 'modalAnimations';
    style.textContent = slideOutAnimation;
    document.head.appendChild(style);
}

// Export functions for testing (if needed)
if (typeof module !== "undefined" && module.exports) {
    module.exports = {
        formatTime,
        isValidEmail,
        isValidPhone,
        validateAppointmentForm,
    };
}