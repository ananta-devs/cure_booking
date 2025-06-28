// merged_doctor_dashboard.js - Optimized doctor profile and password management
class DoctorDashboard {
    constructor() {
        this.doctorId = null;
        this.clinicsData = [];
        this.selectedClinicId = null;
        this.passwordModal = null;
        this.init();
    }

    async init() {
        try {
            // Initialize password modal
            this.initPasswordModal();
            
            // Load doctor profile
            await this.getLoggedInDoctorId();
            if (this.doctorId) {
                await this.loadDoctorProfile();
                await this.loadAppointmentCounts();
            } else {
                this.showError("No doctor logged in. Please login first.");
                setTimeout(() => window.location.href = "../login.php", 3000);
            }
        } catch (error) {
            console.error("Error initializing doctor dashboard:", error);
            this.showError("Failed to load doctor profile");
        }
    }

    // ==================== AUTHENTICATION METHODS ====================
    async getLoggedInDoctorId() {
        try {
            const response = await fetch("api.php?action=getLoggedInDoctor");
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            if (data.success && data.doctor_id) {
                this.doctorId = data.doctor_id;
            } else {
                throw new Error(data.message || "No doctor logged in");
            }
        } catch (error) {
            console.error("Error getting logged-in doctor ID:", error);
            const urlParams = new URLSearchParams(window.location.search);
            this.doctorId = urlParams.get("doctor_id");
            if (!this.doctorId) {
                throw new Error("No doctor logged in and no doctor ID provided");
            }
        }
    }

    logout() {
        if (confirm("Are you sure you want to logout?")) {
            fetch("api.php?action=logout", { method: "POST" })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = "../login.php";
                    } else {
                        alert("Logout failed. Please try again.");
                    }
                })
                .catch(error => {
                    console.error("Logout error:", error);
                    window.location.href = "../login.php";
                });
        }
    }

    // ==================== DOCTOR PROFILE METHODS ====================
    async loadDoctorProfile() {
        try {
            const response = await fetch(`api.php?action=getDoctorProfile&doctor_id=${this.doctorId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            if (data.success) {
                this.displayDoctorInfo(data.doctor);
                this.clinicsData = data.clinics;
                this.displayClinicList(data.clinics);
                this.addEventListeners();
            } else {
                throw new Error(data.message || "Failed to load doctor data");
            }
        } catch (error) {
            console.error("Error loading doctor profile:", error);
            this.showError("Unable to load doctor profile. Please try again later.");
        }
    }

    async loadAppointmentCounts() {
        try {
            const response = await fetch("api.php?action=getDoctorAppointmentCounts");
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            this.displayAppointmentCounts(data.success ? data : { today_count: 0, tomorrow_count: 0 });
        } catch (error) {
            console.error("Error loading appointment counts:", error);
            this.displayAppointmentCounts({ today_count: 0, tomorrow_count: 0 });
        }
    }

    displayDoctorInfo(doctor) {
        const doctorImg = document.getElementById("doctorImg");
        if (doctor.doc_img) {
            doctorImg.src = `http://localhost/cure_booking/adminhub/manage-doctors/uploads/${doctor.doc_img}`;
            doctorImg.alt = `Dr. ${doctor.doc_name}`;
        } else {
            doctorImg.src = "https://via.placeholder.com/120x120?text=Doctor";
            doctorImg.alt = "Default Doctor Image";
        }

        const updates = {
            doctorName: `Dr. ${doctor.doc_name}`,
            doctorSpecialization: doctor.doc_specia,
            doctorExperience: `${doctor.experience || 0} years of medical experience`,
            doctorLocation: doctor.location || "Location not specified",
            doctorFees: doctor.fees > 0 ? `₹${doctor.fees} consultation fee` : "Free consultation",
            doctorBio: doctor.bio || "No bio available",
            doctorEducation: doctor.education || "Education not specified",
            doctorEmail: doctor.doc_email || "Email not available"
        };

        Object.entries(updates).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }

    displayAppointmentCounts(data) {
        const todayCount = document.getElementById("todayCount");
        const tomorrowCount = document.getElementById("tomorrowCount");

        if (todayCount) {
            todayCount.textContent = data.today_count || 0;
            if (data.today_count > 0) todayCount.classList.add("has-appointments");
        }

        if (tomorrowCount) {
            tomorrowCount.textContent = data.tomorrow_count || 0;
            if (data.tomorrow_count > 0) tomorrowCount.classList.add("has-appointments");
        }

        // Update tooltips
        const todayItem = document.querySelector(".appointment-count-item:first-child");
        const tomorrowItem = document.querySelector(".appointment-count-item:last-child");
        
        if (todayItem) todayItem.title = `You have ${data.today_count} appointment(s) scheduled for today`;
        if (tomorrowItem) tomorrowItem.title = `You have ${data.tomorrow_count} appointment(s) scheduled for tomorrow`;
    }

    // ==================== CLINIC MANAGEMENT METHODS ====================
    displayClinicList(clinics) {
        const container = document.getElementById("clinicList");
        if (!clinics || clinics.length === 0) {
            container.innerHTML = '<div class="error">No clinic assignments found</div>';
            return;
        }

        const clinicsHTML = clinics.map((clinic, index) => `
            <div class="clinic-item" data-clinic-id="${index}">
                <div class="clinic-name">${clinic.clinic_name}</div>
                <div class="clinic-location">${clinic.location}</div>
                <div class="clinic-timing">${clinic.available_timing}</div>
            </div>
        `).join("");
        
        container.innerHTML = clinicsHTML;
        this.addClinicClickListeners();
    }

    addClinicClickListeners() {
        document.querySelectorAll(".clinic-item").forEach(item => {
            item.addEventListener("click", e => {
                const clinicId = parseInt(e.currentTarget.dataset.clinicId);
                this.showClinicAvailability(clinicId);
            });
        });
    }

    showClinicAvailability(clinicId) {
        // Update UI state
        document.querySelectorAll(".clinic-item").forEach(item => item.classList.remove("active"));
        const selectedItem = document.querySelector(`[data-clinic-id="${clinicId}"]`);
        if (selectedItem) selectedItem.classList.add("active");

        const clinic = this.clinicsData[clinicId];
        if (!clinic) return;

        document.getElementById("selectedClinicName").textContent = `${clinic.clinic_name} - Availability Schedule`;
        
        const availabilityDetails = document.getElementById("availabilityDetails");
        availabilityDetails.style.display = "block";
        
        this.displayAvailabilitySchedule(clinic);
        this.selectedClinicId = clinicId;
        
        availabilityDetails.scrollIntoView({ behavior: "smooth" });
    }

    displayAvailabilitySchedule(clinic) {
        const container = document.getElementById("availabilitySchedule");
        const schedule = JSON.parse(clinic.availability_schedule);
        
        const days = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
        const timeSlots = ["11:00-13:00", "14:00-16:00", "17:00-19:00"];

        const weeklyScheduleHTML = days.map(day => {
            const daySchedule = schedule[day] || {};
            const slotsHTML = timeSlots.map(slot => {
                const isAvailable = daySchedule[slot] === true;
                const slotClass = isAvailable ? "available" : "unavailable";
                const slotText = this.formatTimeSlot(slot);
                return `<div class="time-slot ${slotClass}" title="${isAvailable ? "Available for booking" : "Not available"}">${slotText}</div>`;
            }).join("");

            return `
                <div class="day-schedule">
                    <div class="day-name">${this.capitalizeFirst(day)}</div>
                    <div class="time-slots">${slotsHTML}</div>
                </div>
            `;
        }).join("");
        
        container.innerHTML = weeklyScheduleHTML;
    }

    closeAvailabilityDetails() {
        document.getElementById("availabilityDetails").style.display = "none";
        document.querySelectorAll(".clinic-item").forEach(item => item.classList.remove("active"));
        this.selectedClinicId = null;
    }

    // ==================== PASSWORD MODAL METHODS ====================
    initPasswordModal() {
        this.createPasswordModal();
        this.bindPasswordEvents();
    }

    createPasswordModal() {
        const modalHTML = `
            <div id="changePasswordOverlay" class="modal-overlay">
                <div class="modal">
                    <div class="modal-header">
                        <h2 class="modal-title">Change Password</h2>
                        <button type="button" class="modal-close" id="closeModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="successMessage" class="success-message">Password changed successfully!</div>
                        <form id="changePasswordForm">
                            <div class="form-group">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <input type="password" id="currentPassword" class="form-input" required>
                                <div class="error-message" id="currentPasswordError"></div>
                            </div>
                            <div class="form-group">
                                <label for="newPassword" class="form-label">New Password</label>
                                <input type="password" id="newPassword" class="form-input" required>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                <div class="error-message" id="newPasswordError"></div>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <input type="password" id="confirmPassword" class="form-input" required>
                                <div class="error-message" id="confirmPasswordError"></div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn" form="changePasswordForm">Change Password</button>
                    </div>
                </div>
            </div>`;

        document.body.insertAdjacentHTML("beforeend", modalHTML);
        this.passwordModal = document.getElementById("changePasswordOverlay");
    }

    bindPasswordEvents() {
        // Modal controls
        document.getElementById("closeModal").addEventListener("click", () => this.closePasswordModal());
        document.getElementById("cancelBtn").addEventListener("click", () => this.closePasswordModal());
        this.passwordModal.addEventListener("click", e => e.target === this.passwordModal && this.closePasswordModal());

        // Form handling
        document.getElementById("changePasswordForm").addEventListener("submit", e => {
            e.preventDefault();
            this.handlePasswordSubmit();
        });

        // Real-time validation
        document.getElementById("newPassword").addEventListener("input", e => this.checkPasswordStrength(e.target.value));
        document.getElementById("currentPassword").addEventListener("blur", () => this.validateCurrentPassword());
        document.getElementById("confirmPassword").addEventListener("input", () => this.validateConfirmPassword());

        // Escape key
        document.addEventListener("keydown", e => e.key === "Escape" && this.isPasswordModalOpen() && this.closePasswordModal());
    }

    openPasswordModal() {
        this.passwordModal.classList.add("show");
        document.body.style.overflow = "hidden";
        setTimeout(() => document.getElementById("currentPassword").focus(), 100);
    }

    closePasswordModal() {
        this.passwordModal.classList.remove("show");
        document.body.style.overflow = "";
        this.resetPasswordForm();
    }

    isPasswordModalOpen() {
        return this.passwordModal.classList.contains("show");
    }

    resetPasswordForm() {
        document.getElementById("changePasswordForm").reset();
        document.querySelectorAll(".error-message, .form-input, .requirement").forEach(el => {
            el.classList.remove("show", "error", "met");
            if (el.classList.contains("error-message")) el.textContent = "";
        });
        document.getElementById("successMessage").classList.remove("show");
        document.getElementById("passwordStrengthBar").className = "password-strength-bar";
    }

    checkPasswordStrength(password) {
        const strengthBar = document.getElementById("passwordStrengthBar");
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        Object.keys(requirements).forEach(req => {
            const el = document.getElementById(`${req}Req`);
            if (el) el.classList.toggle("met", requirements[req]);
        });

        const strength = Object.values(requirements).filter(Boolean).length;
        const strengthClasses = ["strength-weak", "strength-weak", "strength-weak", "strength-medium", "strength-strong", "strength-very-strong"];
        strengthBar.className = `password-strength-bar ${strengthClasses[strength] || ""}`;
    }

    validateCurrentPassword() {
        const input = document.getElementById("currentPassword");
        const error = document.getElementById("currentPasswordError");
        
        if (!input.value.trim()) {
            this.showValidationError(input, error, "Current password is required");
            return false;
        }
        this.clearValidationError(input, error);
        return true;
    }

    validateNewPassword() {
        const input = document.getElementById("newPassword");
        const error = document.getElementById("newPasswordError");
        const password = input.value;

        if (!password) {
            this.showValidationError(input, error, "New password is required");
            return false;
        }

        const requirements = [
            password.length >= 8,
            /[A-Z]/.test(password),
            /[a-z]/.test(password),
            /\d/.test(password),
            /[!@#$%^&*(),.?":{}|<>]/.test(password)
        ];

        if (requirements.filter(Boolean).length < 5) {
            this.showValidationError(input, error, 'Password must contain at least one small and one capital alphabet and numeric digit and one special character (!@#$^&*(),.?":{}|<>).');
            return false;
        }

        this.clearValidationError(input, error);
        return true;
    }

    validateConfirmPassword() {
        const newPassword = document.getElementById("newPassword").value;
        const confirmInput = document.getElementById("confirmPassword");
        const error = document.getElementById("confirmPasswordError");

        if (!confirmInput.value) {
            this.showValidationError(confirmInput, error, "Please confirm your new password");
            return false;
        }

        if (newPassword !== confirmInput.value) {
            this.showValidationError(confirmInput, error, "Passwords do not match");
            return false;
        }

        this.clearValidationError(confirmInput, error);
        return true;
    }

    async handlePasswordSubmit() {
        const submitBtn = document.getElementById("submitBtn");
        const currentPassword = document.getElementById("currentPassword").value;
        const newPassword = document.getElementById("newPassword").value;

        if (![this.validateCurrentPassword(), this.validateNewPassword(), this.validateConfirmPassword()].every(Boolean)) {
            return;
        }

        if (currentPassword === newPassword) {
            this.showValidationError(
                document.getElementById("newPassword"),
                document.getElementById("newPasswordError"),
                "New password must be different from current password"
            );
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "Changing...";

        try {
            const response = await fetch("api.php?action=changePassword", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ currentPassword, newPassword })
            });

            const data = await response.json();

            if (data.success) {
                document.getElementById("successMessage").classList.add("show");
                document.getElementById("changePasswordForm").reset();
                setTimeout(() => this.closePasswordModal(), 2000);
            } else {
                if (data.error === "invalid_current_password") {
                    this.showValidationError(
                        document.getElementById("currentPassword"),
                        document.getElementById("currentPasswordError"),
                        "Current password is incorrect"
                    );
                } else {
                    alert(data.message || "Failed to change password. Please try again.");
                }
            }
        } catch (error) {
            console.error("Error changing password:", error);
            alert("An error occurred. Please try again later.");
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = "Change Password";
        }
    }

    // ==================== EVENT LISTENERS ====================
    addEventListeners() {
        // Logout functionality
        const logoutBtn = document.getElementById("logoutBtn");
        if (logoutBtn) logoutBtn.addEventListener("click", () => this.logout());

        // Change password functionality
        const changePasswordBtn = document.getElementById("changePasswordBtn");
        if (changePasswordBtn) changePasswordBtn.addEventListener("click", () => this.openPasswordModal());

        // Close availability details
        const closeAvailabilityBtn = document.getElementById("closeAvailabilityBtn");
        if (closeAvailabilityBtn) closeAvailabilityBtn.addEventListener("click", () => this.closeAvailabilityDetails());
    }

    // ==================== UTILITY METHODS ====================
    formatTimeSlot(slot) {
        const [start, end] = slot.split("-");
        return `${this.formatTime(start)} - ${this.formatTime(end)}`;
    }

    formatTime(time) {
        const [hours, minutes] = time.split(":");
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? "PM" : "AM";
        const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
        return `${displayHour}:${minutes} ${ampm}`;
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    showValidationError(input, errorElement, message) {
        input.classList.add("error");
        errorElement.textContent = message;
        errorElement.classList.add("show");
    }

    clearValidationError(input, errorElement) {
        input.classList.remove("error");
        errorElement.classList.remove("show");
        errorElement.textContent = "";
    }

    showError(message) {
        const clinicContainer = document.getElementById("clinicList");
        clinicContainer.innerHTML = `<div class="error">${message}</div>`;

        document.getElementById("availabilityDetails").style.display = "none";

        const errorUpdates = {
            doctorName: "Error loading profile",
            doctorSpecialization: "",
            doctorExperience: "",
            doctorLocation: "",
            doctorFees: "",
            doctorBio: message,
            doctorEducation: "",
            doctorEmail: "",
            todayCount: "-",
            tomorrowCount: "-"
        };

        Object.entries(errorUpdates).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }
}

// Initialize the doctor dashboard when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
    new DoctorDashboard();
});