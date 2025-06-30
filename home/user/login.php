<?php
    session_start();
    if (isset($_SESSION['error'])) {
        echo '<div class="alert error">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="alert success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap");

        * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Montserrat", sans-serif;
        }

        body {
        background-color: #c9d6ff;
        background: linear-gradient(to right, #e2e2e2, #c9d6ff);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        height: 100vh;
        padding: 10px;
        }

        .container {
        background-color: #fff;
        border-radius: 30px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
        position: relative;
        overflow: hidden;
        width: 768px;
        max-width: 100%;
        min-height: 480px;
        }

        .container p {
        font-size: 14px;
        line-height: 20px;
        letter-spacing: 0.3px;
        margin: 20px 0;
        }

        .container span {
        font-size: 12px;
        }

        .container a {
        color: #333;
        font-size: 13px;
        text-decoration: none;
        margin: 15px 0 10px;
        }

        .container button {
        background-color: #3b82f6;
        color: #fff;
        font-size: 12px;
        padding: 10px 45px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-top: 10px;
        cursor: pointer;
        }

        .container button.hidden {
        background-color: transparent;
        border-color: #fff;
        }

        .container form {
        background-color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 0 40px;
        height: 100%;
        }

        .container input {
        background-color: #eee;
        border: none;
        margin: 8px 0;
        padding: 10px 15px;
        font-size: 13px;
        border-radius: 8px;
        width: 100%;
        outline: none;
        }

        .form-container {
        position: absolute;
        top: 0;
        height: 100%;
        transition: all 0.6s ease-in-out;
        }

        .sign-in {
        left: 0;
        width: 50%;
        z-index: 2;
        }

        .container.active .sign-in {
        transform: translateX(100%);
        }

        .sign-up {
        left: 0;
        width: 50%;
        opacity: 0;
        z-index: 1;
        }

        .container.active .sign-up {
        transform: translateX(100%);
        opacity: 1;
        z-index: 5;
        animation: move 0.6s;
        }

        /* OTP Verification Form */
        .otp-verification {
        left: 0;
        width: 50%;
        opacity: 0;
        z-index: 1;
        display: none;
        }

        .container.otp-active .otp-verification {
        display: block;
        opacity: 1;
        z-index: 5;
        }

        .container.otp-active .sign-up {
        opacity: 0;
        z-index: 1;
        }

        .otp-input-group {
        display: flex;
        gap: 10px;
        margin: 20px 0;
        }

        .otp-input {
        width: 50px !important;
        height: 50px;
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        border: 2px solid #ddd;
        border-radius: 8px;
        background-color: #f9f9f9;
        }

        .otp-input:focus {
        border-color: #3b82f6;
        background-color: #fff;
        }

        .resend-otp {
        color: #3b82f6;
        text-decoration: underline;
        cursor: pointer;
        font-size: 12px;
        margin-top: 10px;
        }

        .resend-otp:hover {
        color: #3b82f6;
        }

        .timer {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
        }

        @keyframes move {
        0%,
        49.99% {
            opacity: 0;
            z-index: 1;
        }
        50%,
        100% {
            opacity: 1;
            z-index: 5;
        }
        }

        .social-icons {
        margin: 20px 0;
        }

        .social-icons a {
        border: 1px solid #ccc;
        border-radius: 20%;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        margin: 0 3px;
        width: 40px;
        height: 40px;
        }

        .toggle-container {
        position: absolute;
        top: 0;
        left: 50%;
        width: 50%;
        height: 100%;
        overflow: hidden;
        transition: all 0.6s ease-in-out;
        border-radius: 150px 0 0 100px;
        z-index: 1000;
        }

        .container.active .toggle-container {
        transform: translateX(-100%);
        border-radius: 0 150px 100px 0;
        }

        .toggle {
        background-color: #3b82f6;
        height: 100%;
        background: linear-gradient(to right, #3b82f6, #2563eb);
        color: #fff;
        position: relative;
        left: -100%;
        height: 100%;
        width: 200%;
        transform: translateX(0);
        transition: all 0.6s ease-in-out;
        }

        .container.active .toggle {
        transform: translateX(50%);
        }

        .toggle-panel {
        position: absolute;
        width: 50%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 0 30px;
        text-align: center;
        top: 0;
        transform: translateX(0);
        transition: all 0.6s ease-in-out;
        }

        .toggle-left {
        transform: translateX(-200%);
        }

        .container.active .toggle-left {
        transform: translateX(0);
        }

        .toggle-right {
        right: 0;
        transform: translateX(0);
        }

        .container.active .toggle-right {
        transform: translateX(200%);
        }

        /* Alert styles */
        .alert {
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        text-align: center;
        font-size: 14px;
        }

        .alert.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        }

        .alert.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
        .container {
            min-height: 480px;
            width: 95%;
        }

        .form-container {
            width: 100%;
        }

        .sign-in,
        .sign-up,
        .otp-verification {
            width: 100%;
            left: 0;
        }

        .toggle-container {
            display: none;
        }

        .container.active .sign-in {
            transform: translateX(-100%);
            z-index: 1;
            opacity: 0;
        }

        .sign-up {
            transform: translateX(100%);
        }

        .container.active .sign-up {
            transform: translateX(0);
        }

        .container form {
            padding: 0 20px;
        }

        .mobile-toggle {
            display: block;
            text-align: center;
            margin-top: 20px;
        }

        .mobile-toggle a {
            color: #3b82f6;
            font-weight: 600;
        }

        .otp-input-group {
            gap: 5px;
        }

        .otp-input {
            width: 40px !important;
            height: 40px;
            font-size: 16px;
        }
        }

        /* Hide mobile toggle by default */
        .mobile-toggle {
        display: none;
        }
    </style>
    <title>Modern Login Page | CureBooking</title>
</head>

<body>
    <div class="container" id="container">
        <!-- Sign Up Form -->
        <div class="form-container sign-up">
            <form id="signup-form">
                <h1>Create Account</h1>
                <span>or use your email for registration</span>
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Sign Up</button>
                <div class="mobile-toggle">
                    <p>Already have an account? <a href="#" id="mobile-login">Sign In</a></p>
                </div>
            </form>
        </div>

        <!-- OTP Verification Form -->
        <div class="form-container otp-verification">
            <form id="otp-form">
                <h1>Verify Your Email</h1>
                <span>Enter the 6-digit code sent to your email</span>
                <div class="otp-input-group">
                    <input type="text" class="otp-input" maxlength="1" name="otp1" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp2" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp3" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp4" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp5" required>
                    <input type="text" class="otp-input" maxlength="1" name="otp6" required>
                </div>
                <button type="submit">Verify OTP</button>
                <div class="timer" id="timer">Resend OTP in <span id="countdown">60</span>s</div>
                <a href="#" class="resend-otp" id="resend-otp" style="display: none;">Resend OTP</a>
                <div class="mobile-toggle">
                    <p><a href="#" id="back-to-signup">Back to Sign Up</a></p>
                </div>
            </form>
        </div>

        <!-- Sign In Form -->
        <div class="form-container sign-in">
            <form id="signin-form">
                <h1>Sign In</h1>
                <span>or use your email password</span>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <a href="pass.php">Forget Your Password?</a>
                <button type="submit">Sign In</button>
                <div class="mobile-toggle">
                    <p>Don't have an account? <a href="#" id="mobile-register">Sign Up</a></p>
                </div>
            </form>
        </div>

        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all of site features</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, Friend!</h1>
                    <p>Register with your personal details to use all of site features</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');
        const mobileRegisterBtn = document.getElementById('mobile-register');
        const mobileLoginBtn = document.getElementById('mobile-login');
        const backToSignupBtn = document.getElementById('back-to-signup');
        const resendOtpBtn = document.getElementById('resend-otp');
        const signupForm = document.getElementById('signup-form');
        const otpForm = document.getElementById('otp-form');
        const signinForm = document.getElementById('signin-form');

        // Regular desktop toggles
        registerBtn.addEventListener('click', () => {
            container.classList.add("active");
        });

        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
        });

        // Mobile toggles
        if(mobileRegisterBtn) {
            mobileRegisterBtn.addEventListener('click', (e) => {
                e.preventDefault();
                container.classList.add("active");
            });
        }

        if(mobileLoginBtn) {
            mobileLoginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                container.classList.remove("active");
            });
        }

        // Back to signup from OTP
        backToSignupBtn.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.remove("otp-active");
            container.classList.add("active");
        });

        // OTP Input handling
        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                // Only allow digits
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                if (e.target.value.length === 1) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '') {
                    if (index > 0) {
                        otpInputs[index - 1].focus();
                    }
                }
            });

            // Prevent non-numeric input
            input.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        });

        // Signup form submission
        signupForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(signupForm);
            formData.append('action', 'signup_otp'); // Use signup_otp action
            
            // Disable submit button
            const submitBtn = signupForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating Account...';
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show OTP verification form
                    container.classList.remove("active");
                    container.classList.add("otp-active");
                    startCountdown();
                    showAlert(data.message, 'success');
                } else {
                    // Show error message
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        // Signin form submission
        signinForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(signinForm);
            formData.append('action', 'signin'); // Use signin action
            
            // Disable submit button
            const submitBtn = signinForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing In...';
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Redirect to dashboard after successful login
                    setTimeout(() => {
                        window.location.href = 'http://localhost/cure_booking/home/index.php';
                    }, 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        // OTP form submission
        otpForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(otpForm);
            formData.append('action', 'verify_otp');
            
            // Disable submit button
            const submitBtn = otpForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Verifying...';
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Redirect to sign in after successful verification
                    setTimeout(() => {
                        container.classList.remove("otp-active");
                        container.classList.remove("active");
                        // Clear OTP inputs
                        otpInputs.forEach(input => input.value = '');
                        // Clear signup form
                        signupForm.reset();
                    }, 2000);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        // Countdown timer
        let countdownInterval;
        function startCountdown() {
            let timeLeft = 60;
            const countdownElement = document.getElementById('countdown');
            const timerElement = document.getElementById('timer');
            const resendButton = document.getElementById('resend-otp');
            
            // Hide resend button and show timer
            timerElement.style.display = 'block';
            resendButton.style.display = 'none';
            
            countdownInterval = setInterval(() => {
                timeLeft--;
                countdownElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    timerElement.style.display = 'none';
                    resendButton.style.display = 'inline';
                }
            }, 1000);
        }

        // Resend OTP
        resendOtpBtn.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Disable button temporarily
            resendOtpBtn.style.pointerEvents = 'none';
            resendOtpBtn.textContent = 'Sending...';
            
            // Create form data for resend OTP
            const formData = new FormData();
            formData.append('action', 'resend_otp');
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    startCountdown();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable button after request completes
                setTimeout(() => {
                    resendOtpBtn.style.pointerEvents = 'auto';
                    resendOtpBtn.textContent = 'Resend OTP';
                }, 2000);
            });
        });

        // Show alert function
        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.custom-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `custom-alert ${type}`;
            alertDiv.textContent = message;
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                z-index: 9999;
                min-width: 300px;
                text-align: center;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                animation: slideDown 0.3s ease-in-out;
                ${type === 'success' ? 'background-color: #4CAF50;' : 'background-color: #f44336;'}
            `;
            
            // Add slide down animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideDown {
                    from {
                        transform: translateX(-50%) translateY(-100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(-50%) translateY(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.style.animation = 'slideUp 0.3s ease-in-out';
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 300);
                }
            }, 5000);
        }

        // Show appropriate toggle links based on screen size
        function adjustForScreenSize() {
            const mobileToggleElements = document.querySelectorAll('.mobile-toggle');
            
            if (window.innerWidth <= 768) {
                mobileToggleElements.forEach(el => {
                    el.style.display = 'block';
                });
            } else {
                mobileToggleElements.forEach(el => {
                    el.style.display = 'none';
                });
            }
        }

        // Run on page load and window resize
        window.addEventListener('load', adjustForScreenSize);
        window.addEventListener('resize', adjustForScreenSize);

        // Auto-hide PHP alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            });
        }, 5000);

        // Check if OTP form should be shown on page load (in case of refresh)
        <?php if (isset($_SESSION['show_otp']) && $_SESSION['show_otp'] === true): ?>
        container.classList.add("otp-active");
        startCountdown();
        <?php endif; ?>
    </script>
</body>

</html>