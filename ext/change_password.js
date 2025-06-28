// Optimized change_password.js
class ChangePasswordModal {
    constructor() {
        this.isOpen = false;
        this.init();
    }

    init() {
        this.createModal();
        this.bindEvents();
    }

    createModal() {
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
        this.overlay = document.getElementById("changePasswordOverlay");
    }

    bindEvents() {
        const changePasswordBtn = document.getElementById("changePasswordBtn");
        if (changePasswordBtn) {
            changePasswordBtn.addEventListener("click", () => this.openModal());
        }

        // Close events
        document.getElementById("closeModal").addEventListener("click", () => this.closeModal());
        document.getElementById("cancelBtn").addEventListener("click", () => this.closeModal());
        this.overlay.addEventListener("click", e => e.target === this.overlay && this.closeModal());

        // Form submission
        document.getElementById("changePasswordForm").addEventListener("submit", e => {
            e.preventDefault();
            this.handleSubmit();
        });

        // Real-time validation
        document.getElementById("newPassword").addEventListener("input", e => this.checkPasswordStrength(e.target.value));
        document.getElementById("currentPassword").addEventListener("blur", () => this.validateCurrentPassword());
        document.getElementById("confirmPassword").addEventListener("input", () => this.validateConfirmPassword());

        // Escape key
        document.addEventListener("keydown", e => e.key === "Escape" && this.isOpen && this.closeModal());
    }

    openModal() {
        this.overlay.classList.add("show");
        this.isOpen = true;
        document.body.style.overflow = "hidden";
        setTimeout(() => document.getElementById("currentPassword").focus(), 100);
    }

    closeModal() {
        this.overlay.classList.remove("show");
        this.isOpen = false;
        document.body.style.overflow = "";
        this.resetForm();
    }

    resetForm() {
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

        // Update requirement indicators (if they exist)
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
            this.showError(input, error, "Current password is required");
            return false;
        }
        this.clearError(input, error);
        return true;
    }

    validateNewPassword() {
        const input = document.getElementById("newPassword");
        const error = document.getElementById("newPasswordError");
        const password = input.value;

        if (!password) {
            this.showError(input, error, "New password is required");
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
            this.showError(input, error, 'Password must contain at least one small and one capital alphabet and numeric digit and one special character (!@#$^&*(),.?":{}|<>).');
            return false;
        }

        this.clearError(input, error);
        return true;
    }

    validateConfirmPassword() {
        const newPassword = document.getElementById("newPassword").value;
        const confirmInput = document.getElementById("confirmPassword");
        const error = document.getElementById("confirmPasswordError");

        if (!confirmInput.value) {
            this.showError(confirmInput, error, "Please confirm your new password");
            return false;
        }

        if (newPassword !== confirmInput.value) {
            this.showError(confirmInput, error, "Passwords do not match");
            return false;
        }

        this.clearError(confirmInput, error);
        return true;
    }

    showError(input, errorElement, message) {
        input.classList.add("error");
        errorElement.textContent = message;
        errorElement.classList.add("show");
    }

    clearError(input, errorElement) {
        input.classList.remove("error");
        errorElement.classList.remove("show");
        errorElement.textContent = "";
    }

    async handleSubmit() {
        const submitBtn = document.getElementById("submitBtn");
        const currentPassword = document.getElementById("currentPassword").value;
        const newPassword = document.getElementById("newPassword").value;

        // Validate all fields
        if (![this.validateCurrentPassword(), this.validateNewPassword(), this.validateConfirmPassword()].every(Boolean)) {
            return;
        }

        // Check if passwords are different
        if (currentPassword === newPassword) {
            this.showError(
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
                setTimeout(() => this.closeModal(), 2000);
            } else {
                if (data.error === "invalid_current_password") {
                    this.showError(
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
}

// Initialize on DOM load
document.addEventListener("DOMContentLoaded", () => new ChangePasswordModal());