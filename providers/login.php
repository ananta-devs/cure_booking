<?php
// login.php - Login page display
session_start();

// Check if user is already logged in
// if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['doctor_id'])) {
//     header('Location: .php');
//     exit;
// }

// Get role from URL parameter
$role = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';

// Validate role - only allow 'doctor' or 'clinic'
if (!in_array($role, ['doctor', 'clinic'])) {
    $role = 'doctor'; // Default to doctor if no valid role specified
}

// Set page title and header based on role
$pageTitle = ucfirst($role) . ' Login';
$headerText = $role === 'doctor' ? 'Doctor Login' : 'Clinic Login';
$welcomeText = $role === 'doctor' ? 'Welcome Back, Doctor' : 'Welcome Back';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $pageTitle; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: #333;
            font-size: 28px;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .role-indicator {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            display: inline-block;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group.error input {
            border-color: #e74c3c;
            background: #fdf2f2;
        }

        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .form-group.error .error-message {
            display: block;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .forgot-password {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #764ba2;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .signup-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            color: #764ba2;
        }

        /* Message styles for server responses */
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
            font-size: 14px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .login-header h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="role-indicator"><?php echo ucfirst($role); ?> Portal</div>
            <h2><?php echo $welcomeText; ?></h2>
            <p>Please sign in to your account</p>
        </div>

        <!-- Message container for server responses -->
        <div class="message" id="loginMessage"></div>

        <form id="loginForm" method="post">
            <!-- Hidden input to pass role to handler -->
            <input type="hidden" id="loginType" name="login_type" value="<?php echo $role; ?>" />
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required />
                <div class="error-message">Please enter a valid email address</div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required />
                <div class="error-message">
                    Password must be at least 6 characters long
                </div>
            </div>

            <div class="remember-forgot">
                <p>Don't remember your password?</p>
                <a href="#" class="forgot-password">Click here</a>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">Sign In</button>
        </form>
    </div>

    <script>
        const loginForm = document.getElementById("loginForm");
        const emailInput = document.getElementById("email");
        const passwordInput = document.getElementById("password");
        const loginBtn = document.getElementById("loginBtn");
        const messageElement = document.getElementById("loginMessage");
        const loginTypeInput = document.getElementById("loginType");

        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function validatePassword(password) {
            return password.length >= 6;
        }

        function showError(input, show = true) {
            const formGroup = input.parentElement;
            if (show) {
                formGroup.classList.add("error");
            } else {
                formGroup.classList.remove("error");
            }
        }

        function showMessage(message, type) {
            messageElement.textContent = message;
            messageElement.className = `message ${type}`;
            messageElement.style.display = "block";
            
            // Auto-hide success messages after 3 seconds
            if (type === "success") {
                setTimeout(() => {
                    messageElement.style.display = "none";
                }, 3000);
            }
        }

        function hideMessage() {
            messageElement.style.display = "none";
            messageElement.className = "message";
        }

        // Real-time validation
        emailInput.addEventListener("input", function () {
            if (this.value.length > 0) {
                showError(this, !validateEmail(this.value));
            } else {
                showError(this, false);
            }
        });

        passwordInput.addEventListener("input", function () {
            if (this.value.length > 0) {
                showError(this, !validatePassword(this.value));
            } else {
                showError(this, false);
            }
        });

        // Form submission handler
        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();
            const loginType = loginTypeInput.value;

            // Hide any existing messages
            hideMessage();

            // Client-side validation
            const isEmailValid = validateEmail(email);
            const isPasswordValid = validatePassword(password);

            showError(emailInput, !isEmailValid);
            showError(passwordInput, !isPasswordValid);

            if (!isEmailValid || !isPasswordValid) {
                showMessage("Please fix the errors above and try again.", "error");
                return;
            }

            // Disable button and show loading state
            loginBtn.disabled = true;
            loginBtn.textContent = "Signing In...";

            // Create form data
            const formData = new FormData();
            formData.append("email", email);
            formData.append("password", password);
            formData.append("login_type", loginType);

            // Send login request to login_handler.php
            fetch("login_handler.php", {
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
                        // Show success message
                        showMessage(data.message || "Login successful!", "success");

                        // Redirect to dashboard after a short delay
                        setTimeout(() => {
                            window.location.href = data.redirect || "dashboard.php";
                        }, 1000);
                    } else {
                        // Display error message
                        showMessage(data.message || "Login failed. Please try again.", "error");
                    }
                })
                .catch((error) => {
                    console.error("Login error:", error);
                    showMessage(
                        "Connection error. Please check your network and try again.",
                        "error"
                    );
                })
                .finally(() => {
                    // Re-enable button
                    loginBtn.disabled = false;
                    loginBtn.textContent = "Sign In";
                });
        });

        // Forgot password link
        document
            .querySelector(".forgot-password")
            .addEventListener("click", function (e) {
                e.preventDefault();
                showMessage("Forgot password functionality will be implemented soon.", "error");
            });

        // Log current role for debugging
        console.log("Current login type:", loginTypeInput.value);

    </script>
</body>
</html>