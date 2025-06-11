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
        <link rel="stylesheet" href="style.css">
        <title>Modern Login Page | CureBooking</title>
    
    </head>

    <body>
    

        <div class="container" id="container">
            <!-- Sign Up Form -->
            <div class="form-container sign-up">
                <form action="otp-handler.php" method="POST" id="signup-form">
                    <input type="hidden" name="action" value="signup">
                    <h1>Create Account</h1>
                    <span>or use your email for registration</span>
                    <input type="text" name="name" placeholder="Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Sign Up</button>
                    <div class="mobile-toggle">
                        <p>Already have an account? <a href="login.php" id="mobile-login">Sign In</a></p>
                    </div>
                </form>
            </div>

            <!-- OTP Verification Form -->
            <div class="form-container otp-verification">
                <form action="otp-handler.php" method="POST" id="otp-form">
                    <input type="hidden" name="action" value="verify_otp">
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
                <form action="otp-handler.php" method="POST">
                    <input type="hidden" name="action" value="signin">
                    <h1>Sign In</h1>
                    <span>or use your email password</span>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <a href="pass.html">Forget Your Password?</a>
                    <button type="submit">Sign In</button>
                    <div class="mobile-toggle">
                        <p>Don't have an account? <a href="index.php" id="mobile-register">Sign Up</a></p>
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
                
                fetch('otp-handler.php', {
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
                });
            });

            // OTP form submission
            otpForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const formData = new FormData(otpForm);
                
                fetch('otp-handler.php', {
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
                        }, 2000);
                    } else {
                        showAlert(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'error');
                });
            });

            // Countdown timer
            let countdownInterval;
            function startCountdown() {
                let timeLeft = 60; // Changed from 20 to 60 seconds
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
                
                // Create form data with resend_otp action
                const formData = new FormData();
                formData.append('action', 'resend_otp');
                
                fetch('otp-handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('OTP resent successfully!', 'success');
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
                const existingAlerts = document.querySelectorAll('.alert');
                existingAlerts.forEach(alert => alert.remove());
                
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert ${type}`;
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
                    ${type === 'success' ? 'background-color: #4CAF50;' : 'background-color: #f44336;'}
                `;
                
                document.body.appendChild(alertDiv);
                
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
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

            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.display = 'none';
                });
            }, 5000);
        </script>
    </body>

</html>