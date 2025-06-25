// change_password.js - Handle change password functionality
class ChangePasswordModal {
    constructor() {
        this.modal = null;
        this.overlay = null;
        this.isOpen = false;
        this.init();
    }

    init() {
        this.createModal();
        this.bindEvents();
    }

    createModal() {
        // Create modal HTML
        const modalHTML = `
            <div id="changePasswordOverlay" class="modal-overlay">
                <div class="modal">
                    <div class="modal-header">
                        <h2 class="modal-title">Change Password</h2>
                        <button type="button" class="modal-close" id="closeModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="successMessage" class="success-message">
                            Password changed successfully!
                        </div>
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
                                <div class="password-requirements">
                                    <div class="requirement" id="lengthReq">
                                        <div class="requirement-icon"></div>
                                        At least 8 characters
                                    </div>
                                    <div class="requirement" id="uppercaseReq">
                                        <div class="requirement-icon"></div>
                                        One uppercase letter
                                    </div>
                                    <div class="requirement" id="lowercaseReq">
                                        <div class="requirement-icon"></div>
                                        One lowercase letter
                                    </div>
                                    <div class="requirement" id="numberReq">
                                        <div class="requirement-icon"></div>
                                        One number
                                    </div>
                                    <div class="requirement" id="specialReq">
                                        <div class="requirement-icon"></div>
                                        One special character
                                    </div>
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
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML("beforeend", modalHTML);

        // Get references
        this.overlay = document.getElementById("changePasswordOverlay");
        this.modal = this.overlay.querySelector(".modal");
    }

    bindEvents() {
        // Open modal button
        const changePasswordBtn = document.getElementById("changePasswordBtn");
        if (changePasswordBtn) {
            changePasswordBtn.addEventListener("click", () => this.openModal());
        }

        // Close modal events
        document
            .getElementById("closeModal")
            .addEventListener("click", () => this.closeModal());
        document
            .getElementById("cancelBtn")
            .addEventListener("click", () => this.closeModal());

        // Close on overlay click
        this.overlay.addEventListener("click", (e) => {
            if (e.target === this.overlay) {
                this.closeModal();
            }
        });

        // Form submission
        document
            .getElementById("changePasswordForm")
            .addEventListener("submit", (e) => {
                e.preventDefault();
                this.handleSubmit();
            });

        // Password strength checking
        document
            .getElementById("newPassword")
            .addEventListener("input", (e) => {
                this.checkPasswordStrength(e.target.value);
            });

        // Real-time validation
        document
            .getElementById("currentPassword")
            .addEventListener("blur", () => {
                this.validateCurrentPassword();
            });

        document
            .getElementById("confirmPassword")
            .addEventListener("input", () => {
                this.validateConfirmPassword();
            });

        // Close on Escape key
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && this.isOpen) {
                this.closeModal();
            }
        });
    }

    openModal() {
        this.overlay.classList.add("show");
        this.isOpen = true;
        document.body.style.overflow = "hidden";

        // Focus on first input
        setTimeout(() => {
            document.getElementById("currentPassword").focus();
        }, 100);
    }

    closeModal() {
        this.overlay.classList.remove("show");
        this.isOpen = false;
        document.body.style.overflow = "";

        // Reset form
        this.resetForm();
    }

    resetForm() {
        const form = document.getElementById("changePasswordForm");
        form.reset();

        // Clear error messages
        document.querySelectorAll(".error-message").forEach((msg) => {
            msg.classList.remove("show");
            msg.textContent = "";
        });

        // Clear success message
        document.getElementById("successMessage").classList.remove("show");

        // Reset password strength indicator
        document.getElementById("passwordStrengthBar").className =
            "password-strength-bar";

        // Reset requirements
        document.querySelectorAll(".requirement").forEach((req) => {
            req.classList.remove("met");
        });

        // Reset input states
        document.querySelectorAll(".form-input").forEach((input) => {
            input.classList.remove("error");
        });
    }

    checkPasswordStrength(password) {
        const strengthBar = document.getElementById("passwordStrengthBar");
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password),
        };

        // Update requirement indicators
        document
            .getElementById("lengthReq")
            .classList.toggle("met", requirements.length);
        document
            .getElementById("uppercaseReq")
            .classList.toggle("met", requirements.uppercase);
        document
            .getElementById("lowercaseReq")
            .classList.toggle("met", requirements.lowercase);
        document
            .getElementById("numberReq")
            .classList.toggle("met", requirements.number);
        document
            .getElementById("specialReq")
            .classList.toggle("met", requirements.special);

        // Calculate strength
        const metRequirements =
            Object.values(requirements).filter(Boolean).length;

        // Update strength bar
        strengthBar.className = "password-strength-bar";

        if (metRequirements === 0) {
            strengthBar.classList.add("strength-weak");
        } else if (metRequirements <= 2) {
            strengthBar.classList.add("strength-weak");
        } else if (metRequirements === 3) {
            strengthBar.classList.add("strength-medium");
        } else if (metRequirements === 4) {
            strengthBar.classList.add("strength-strong");
        } else {
            strengthBar.classList.add("strength-very-strong");
        }
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

        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password),
        };

        const metRequirements =
            Object.values(requirements).filter(Boolean).length;

        if (metRequirements < 4) {
            this.showError(
                input,
                error,
                "Password must meet at least 4 requirements"
            );
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
            this.showError(
                confirmInput,
                error,
                "Please confirm your new password"
            );
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

        // Validate all fields
        const isCurrentPasswordValid = this.validateCurrentPassword();
        const isNewPasswordValid = this.validateNewPassword();
        const isConfirmPasswordValid = this.validateConfirmPassword();

        if (
            !isCurrentPasswordValid ||
            !isNewPasswordValid ||
            !isConfirmPasswordValid
        ) {
            return;
        }

        // Check if new password is different from current
        const currentPassword =
            document.getElementById("currentPassword").value;
        const newPassword = document.getElementById("newPassword").value;

        if (currentPassword === newPassword) {
            this.showError(
                document.getElementById("newPassword"),
                document.getElementById("newPasswordError"),
                "New password must be different from current password"
            );
            return;
        }

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = "Changing...";

        try {
            const response = await fetch("api.php?action=changePassword", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    currentPassword: currentPassword,
                    newPassword: newPassword,
                }),
            });

            const data = await response.json();

            if (data.success) {
                // Show success message
                document.getElementById("successMessage").classList.add("show");

                // Reset form
                document.getElementById("changePasswordForm").reset();

                // Close modal after 2 seconds
                setTimeout(() => {
                    this.closeModal();
                }, 2000);
            } else {
                // Show error based on response
                if (data.error === "invalid_current_password") {
                    this.showError(
                        document.getElementById("currentPassword"),
                        document.getElementById("currentPasswordError"),
                        "Current password is incorrect"
                    );
                } else {
                    alert(
                        data.message ||
                            "Failed to change password. Please try again."
                    );
                }
            }
        } catch (error) {
            console.error("Error changing password:", error);
            alert("An error occurred. Please try again later.");
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = "Change Password";
        }
    }
}

// Initialize change password modal when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
    new ChangePasswordModal();
});
