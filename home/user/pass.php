<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>Forgot Password | CureBooking</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container" id="container">
        <div class="password-reset-container">
            <div class="reset-content">
                <div class="progress-steps">
                    <div class="step active" id="step1">1</div>
                    <div class="step" id="step2">2</div>
                    <div class="step" id="step3">3</div>
                </div>

                <!-- Email Request Section -->
                <div id="emailSection">
                    <h1>Forgot Password</h1>
                    <p>Enter your email address and we'll send you a verification code.</p>
                    
                    <form id="emailForm">
                        <input type="email" id="resetEmail" placeholder="Email" required>
                        <button type="submit" id="requestOtpBtn">Request Verification Code</button>
                    </form>
                    
                    <div id="emailErrorMessage" class="error-message" style="display: none;"></div>
                </div>

                <!-- OTP Verification Section -->
                <div id="otpSection" class="otp-section" style="display: none;">
                    <h1>Verify OTP</h1>
                    <p class="otp-message">
                        We've sent a verification code to your email. Please enter it below.
                    </p>
                    
                    <form id="otpForm">
                        <div class="otp-input-container">
                            <input type="text" id="otpInput" placeholder="Enter 6-digit Verification Code" maxlength="6" required>
                            <i class="fa-solid fa-circle-check verification-icon-success" id="verificationIconSuccess"></i>
                            <i class="fa-solid fa-circle-xmark verification-icon-error" id="verificationIconError"></i>
                        </div>
                        <button type="submit" id="verifyOtpBtn">Verify Code</button>
                    </form>
                    
                    <p class="otp-message">
                        Didn't receive the code? <a href="#" id="resendOtp">Resend</a>
                    </p>
                    
                    <div id="otpErrorMessage" class="error-message" style="display: none;"></div>
                </div>

                <!-- New Password Section -->
                <div id="newPasswordSection" class="new-password-section" style="display: none;">
                    <h1>Reset Password</h1>
                    <p>Create a new secure password for your account.</p>
                    
                    <form id="newPasswordForm">
                        <input type="password" id="newPassword" placeholder="New Password" required>
                        <input type="password" id="confirmPassword" placeholder="Confirm New Password" required>
                        
                        <div class="password-requirements">
                            <p>Password must contain:</p>
                            <ul>
                                <li id="length"><i class="fa-solid fa-circle-check"></i> At least 8 characters</li>
                                <li id="uppercase"><i class="fa-solid fa-circle-check"></i> At least 1 uppercase letter</li>
                                <li id="number"><i class="fa-solid fa-circle-check"></i> At least 1 number</li>
                                <li id="special"><i class="fa-solid fa-circle-check"></i> At least 1 special character</li>
                                <li id="match"><i class="fa-solid fa-circle-check"></i> Passwords match</li>
                            </ul>
                        </div>
                        
                        <button type="submit" id="resetPasswordBtn">Reset Password</button>
                    </form>
                    
                    <div id="passwordErrorMessage" class="error-message" style="display: none;"></div>
                </div>
                
                <div class="success-message" id="successMessage" style="display: none;">
                    <i class="fa-solid fa-circle-check"></i>
                    <h2>Password Reset Successful!</h2>
                    <p>Your password has been successfully reset. You can now sign in with your new password.</p>
                </div>
                
                <div class="back-link">
                    <a href="login.php" id="backToLogin"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get all necessary elements
        const emailSection = document.getElementById('emailSection');
        const otpSection = document.getElementById('otpSection');
        const newPasswordSection = document.getElementById('newPasswordSection');
        const successMessage = document.getElementById('successMessage');
        
        // Form elements
        const emailForm = document.getElementById('emailForm');
        const otpForm = document.getElementById('otpForm');
        const newPasswordForm = document.getElementById('newPasswordForm');
        
        // Step indicators
        const step1 = document.getElementById('step1');
        const step2 = document.getElementById('step2');
        const step3 = document.getElementById('step3');
        
        // Password validation elements
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const passwordRequirements = document.querySelectorAll('.password-requirements li');
        
        // OTP verification elements
        const otpInput = document.getElementById('otpInput');
        const verificationIconSuccess = document.getElementById('verificationIconSuccess');
        const verificationIconError = document.getElementById('verificationIconError');
        
        // Error message elements
        const emailErrorMessage = document.getElementById('emailErrorMessage');
        const otpErrorMessage = document.getElementById('otpErrorMessage');
        const passwordErrorMessage = document.getElementById('passwordErrorMessage');
        
        // Utility function to show error messages
        function showError(element, message) {
            element.textContent = message;
            element.style.display = 'block';
            setTimeout(() => {
                element.style.display = 'none';
            }, 5000);
        }
        
        // Utility function to show loading state
        function setButtonLoading(button, isLoading) {
            if (isLoading) {
                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            } else {
                button.disabled = false;
                button.innerHTML = button.getAttribute('data-original-text');
            }
        }
        
        // Store original button texts
        document.getElementById('requestOtpBtn').setAttribute('data-original-text', 'Request Verification Code');
        document.getElementById('verifyOtpBtn').setAttribute('data-original-text', 'Verify Code');
        document.getElementById('resetPasswordBtn').setAttribute('data-original-text', 'Reset Password');
        
        // Email form submission - Request OTP
        emailForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const email = document.getElementById('resetEmail').value;
            const requestOtpBtn = document.getElementById('requestOtpBtn');
            
            setButtonLoading(requestOtpBtn, true);
            
            try {
                const formData = new FormData();
                formData.append('action', 'request_password_reset');
                formData.append('email', email);
                
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Hide email section, show OTP section
                    emailSection.style.display = 'none';
                    otpSection.style.display = 'block';
                    otpSection.classList.add('fadeIn');
                    
                    // Update step indicators
                    step1.classList.remove('active');
                    step2.classList.add('active');
                } else {
                    showError(emailErrorMessage, result.message);
                }
            } catch (error) {
                showError(emailErrorMessage, 'Network error. Please try again.');
                console.error('Error:', error);
            } finally {
                setButtonLoading(requestOtpBtn, false);
            }
        });
        
        // Reset verification icons
        function resetVerificationIcons() {
            verificationIconSuccess.classList.remove('icon-show');
            verificationIconError.classList.remove('icon-show');
        }
        
        // OTP form submission - Verify OTP
        otpForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const enteredOTP = otpInput.value;
            const verifyOtpBtn = document.getElementById('verifyOtpBtn');
            
            // Reset icons first
            resetVerificationIcons();
            setButtonLoading(verifyOtpBtn, true);
            
            try {
                const formData = new FormData();
                formData.append('action', 'verify_password_reset_otp');
                formData.append('otp', enteredOTP);
                
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success icon
                    verificationIconSuccess.classList.add('icon-show');
                    
                    // Proceed to next step after a short delay
                    setTimeout(() => {
                        // Success - show new password section
                        otpSection.style.display = 'none';
                        newPasswordSection.style.display = 'block';
                        newPasswordSection.classList.add('fadeIn');
                        
                        // Update step indicators
                        step2.classList.remove('active');
                        step3.classList.add('active');
                    }, 1000);
                } else {
                    // Show error icon and message
                    verificationIconError.classList.add('icon-show');
                    showError(otpErrorMessage, result.message);
                }
            } catch (error) {
                verificationIconError.classList.add('icon-show');
                showError(otpErrorMessage, 'Network error. Please try again.');
                console.error('Error:', error);
            } finally {
                setButtonLoading(verifyOtpBtn, false);
            }
        });
        
        // Resend OTP
        document.getElementById('resendOtp').addEventListener('click', async function(event) {
            event.preventDefault();
            
            try {
                const formData = new FormData();
                formData.append('action', 'resend_password_reset_otp');
                
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Reset the verification icons and input
                    resetVerificationIcons();
                    otpInput.value = '';
                    
                    // Show success message
                    showError(otpErrorMessage, 'New verification code sent successfully!');
                    otpErrorMessage.style.color = 'green';
                    setTimeout(() => {
                        otpErrorMessage.style.color = '';
                    }, 5000);
                } else {
                    showError(otpErrorMessage, result.message);
                }
            } catch (error) {
                showError(otpErrorMessage, 'Network error. Please try again.');
                console.error('Error:', error);
            }
        });
        
        // Reset verification icons when typing in OTP input
        otpInput.addEventListener('input', function() {
            resetVerificationIcons();
            // Only allow numbers and limit to 6 digits
            this.value = this.value.replace(/\D/g, '').substring(0, 6);
        });
        
        // Password validations
        newPassword.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
        
        function validatePassword() {
            const password = newPassword.value;
            const confirm = confirmPassword.value;
            
            // Length validation
            if (password.length >= 8) {
                passwordRequirements[0].classList.add('valid');
            } else {
                passwordRequirements[0].classList.remove('valid');
            }
            
            // Uppercase validation
            if (/[A-Z]/.test(password)) {
                passwordRequirements[1].classList.add('valid');
            } else {
                passwordRequirements[1].classList.remove('valid');
            }
            
            // Number validation
            if (/[0-9]/.test(password)) {
                passwordRequirements[2].classList.add('valid');
            } else {
                passwordRequirements[2].classList.remove('valid');
            }
            
            // Special character validation
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                passwordRequirements[3].classList.add('valid');
            } else {
                passwordRequirements[3].classList.remove('valid');
            }
            
            // Password match validation
            if (password === confirm && password !== '') {
                passwordRequirements[4].classList.add('valid');
            } else {
                passwordRequirements[4].classList.remove('valid');
            }
        }
        
        // New password form submission
        newPasswordForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const password = newPassword.value;
            const confirm = confirmPassword.value;
            const resetPasswordBtn = document.getElementById('resetPasswordBtn');
            
            // Check if all requirements are met
            const requirements = [
                password.length >= 8,
                /[A-Z]/.test(password),
                /[0-9]/.test(password),
                /[!@#$%^&*(),.?":{}|<>]/.test(password),
                password === confirm && password !== ''
            ];
            
            if (!requirements.every(req => req === true)) {
                showError(passwordErrorMessage, 'Please ensure all password requirements are met.');
                return;
            }
            
            setButtonLoading(resetPasswordBtn, true);
            
            try {
                const formData = new FormData();
                formData.append('action', 'reset_password');
                formData.append('new_password', password);
                formData.append('confirm_password', confirm);
                
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Success - show completion message
                    newPasswordSection.style.display = 'none';
                    successMessage.style.display = 'block';
                    successMessage.classList.add('fadeIn');
                    
                    // Redirect after success
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 3000);
                } else {
                    showError(passwordErrorMessage, result.message);
                }
            } catch (error) {
                showError(passwordErrorMessage, 'Network error. Please try again.');
                console.error('Error:', error);
            } finally {
                setButtonLoading(resetPasswordBtn, false);
            }
        });
        
        // Back to login
        document.getElementById('backToLogin').addEventListener('click', function(event) {
            event.preventDefault();
            window.location.href = 'login.php';
        });
    </script>

</body>

</html>