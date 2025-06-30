<?php
    session_start();

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cure_booking";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Also set up PDO connection for OTP functionality
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("PDO Connection failed: " . $e->getMessage());
    }

    // Include email configuration if it exists
    if (file_exists('email_config.php')) {
        require_once 'email_config.php';
    }

    // Function to generate random OTP
    function generateOTP($length = 6) {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    // Function to send OTP email using PHPMailer
    function sendOTPEmail($email, $name, $otp, $isResend = false) {
        $subject = "Email Verification - CureBooking";
        $messageText = $isResend ? "Here is your new verification code:" : "Thank you for signing up with CureBooking! To complete your registration, please verify your email address using the OTP below:";
        
        $message = "
        <html>
        <head>
            <title>Email Verification</title>
        </head>
        <body>
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background-color: #512da8; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1>CureBooking</h1>
                    <h2>Email Verification</h2>
                </div>
                <div style='background-color: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;'>
                    <p>Dear $name,</p>
                    <p>$messageText</p>
                    <div style='background-color: white; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; border: 2px dashed #512da8;'>
                        <h1 style='color: #512da8; font-size: 36px; margin: 0; letter-spacing: 5px;'>$otp</h1>
                    </div>
                    <p>This OTP will expire in 10 minutes for security reasons.</p>
                    <p>If you didn't request this verification, please ignore this email.</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    <p style='font-size: 12px; color: #666;'>
                        This is an automated email. Please do not reply to this email.<br>
                        © 2024 CureBooking. All rights reserved.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Use the PHPMailer function from email_config.php if available
        if (function_exists('sendEmailWithGmail')) {
            return sendEmailWithGmail($email, $subject, $message, $name);
        } else {
            // Fallback to basic mail() function
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: noreply@curebooking.com" . "\r\n";
            return mail($email, $subject, $message, $headers);
        }
    }

    // Function to send password reset OTP email
    function sendPasswordResetOTPEmail($email, $name, $otp, $isResend = false) {
        $subject = "Password Reset - CureBooking";
        $messageText = $isResend ? "Here is your new password reset verification code:" : "You have requested to reset your password. Please use the verification code below to proceed:";
        
        $message = "
        <html>
        <head>
            <title>Password Reset</title>
        </head>
        <body>
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background-color: #512da8; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1>CureBooking</h1>
                    <h2>Password Reset</h2>
                </div>
                <div style='background-color: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;'>
                    <p>Dear $name,</p>
                    <p>$messageText</p>
                    <div style='background-color: white; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; border: 2px dashed #512da8;'>
                        <h1 style='color: #512da8; font-size: 36px; margin: 0; letter-spacing: 5px;'>$otp</h1>
                    </div>
                    <p>This OTP will expire in 10 minutes for security reasons.</p>
                    <p>If you didn't request this password reset, please ignore this email and your password will remain unchanged.</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    <p style='font-size: 12px; color: #666;'>
                        This is an automated email. Please do not reply to this email.<br>
                        © 2024 CureBooking. All rights reserved.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Use the PHPMailer function from email_config.php if available
        if (function_exists('sendEmailWithGmail')) {
            return sendEmailWithGmail($email, $subject, $message, $name);
        } else {
            // Fallback to basic mail() function
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: noreply@curebooking.com" . "\r\n";
            return mail($email, $subject, $message, $headers);
        }
    }

    // Function to validate email format
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Function to validate password strength
    function validatePassword($password) {
        return strlen($password) >= 6;
    }

    // Function to validate strong password (for password reset)
    function validateStrongPassword($password) {
        return strlen($password) >= 8 && 
            preg_match('/[A-Z]/', $password) && 
            preg_match('/[0-9]/', $password) && 
            preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password);
    }

    // Function to sanitize input
    function sanitizeInput($input) {
        return trim(htmlspecialchars($input));
    }

    // Set JSON response header for OTP actions
    $otp_actions = ['signup_otp', 'verify_otp', 'resend_otp', 'signin_otp', 'check_session', 'request_password_reset', 'verify_password_reset_otp', 'resend_password_reset_otp', 'reset_password'];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = $_POST['action'] ?? '';
        
        // Set JSON header for OTP-related actions
        if (in_array($action, $otp_actions)) {
            header('Content-Type: application/json');
        }
        
        switch ($action) {
            case 'request_password_reset':
                try {
                    $email = sanitizeInput($_POST['email'] ?? '');
                    
                    if (empty($email)) {
                        echo json_encode(['success' => false, 'message' => 'Email is required']);
                        exit;
                    }
                    
                    if (!validateEmail($email)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                        exit;
                    }
                    
                    // Check if user exists
                    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if (!$user) {
                        echo json_encode(['success' => false, 'message' => 'No account found with this email address']);
                        exit;
                    }
                    
                    // Generate OTP for password reset
                    $otp = generateOTP();
                    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    // Store password reset data in session
                    $_SESSION['password_reset'] = [
                        'user_id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'otp' => $otp,
                        'otp_expiry' => $otp_expiry,
                        'verified' => false
                    ];
                    
                    error_log("Password reset OTP generated: $otp for email: $email");
                    
                    // Send OTP email
                    if (sendPasswordResetOTPEmail($user['email'], $user['name'], $otp)) {
                        echo json_encode(['success' => true, 'message' => 'Verification code sent to your email']);
                    } else {
                        // For testing purposes, still allow proceed even if email fails
                        echo json_encode(['success' => true, 'message' => 'Verification code generated (email may not be configured). OTP: ' . $otp]);
                    }
                    
                } catch (PDOException $e) {
                    error_log("Database error in request_password_reset: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
                } catch (Exception $e) {
                    error_log("General error in request_password_reset: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
                }
                exit();
                
            case 'verify_password_reset_otp':
                try {
                    $entered_otp = sanitizeInput($_POST['otp'] ?? '');
                    
                    if (empty($entered_otp)) {
                        echo json_encode(['success' => false, 'message' => 'Please enter the verification code']);
                        exit;
                    }
                    
                    if (strlen($entered_otp) != 6) {
                        echo json_encode(['success' => false, 'message' => 'Verification code must be 6 digits long']);
                        exit;
                    }
                    
                    if (!isset($_SESSION['password_reset'])) {
                        echo json_encode(['success' => false, 'message' => 'Session expired. Please start the password reset process again.']);
                        exit;
                    }
                    
                    $reset_data = $_SESSION['password_reset'];
                    
                    error_log("Comparing password reset OTPs - Entered: $entered_otp, Stored: " . $reset_data['otp']);
                    
                    // Check if OTP is expired
                    if (strtotime($reset_data['otp_expiry']) < time()) {
                        unset($_SESSION['password_reset']);
                        echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please start the password reset process again.']);
                        exit;
                    }
                    
                    // Verify OTP
                    if ($entered_otp === $reset_data['otp']) {
                        // Mark as verified
                        $_SESSION['password_reset']['verified'] = true;
                        echo json_encode(['success' => true, 'message' => 'Verification code verified successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please check and try again.']);
                    }
                    
                } catch (Exception $e) {
                    error_log("Error in verify_password_reset_otp: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred during verification']);
                }
                exit();
                
            case 'resend_password_reset_otp':
                try {
                    if (!isset($_SESSION['password_reset'])) {
                        echo json_encode(['success' => false, 'message' => 'Session expired. Please start the password reset process again.']);
                        exit;
                    }
                    
                    $reset_data = $_SESSION['password_reset'];
                    
                    // Generate new OTP
                    $new_otp = generateOTP();
                    $new_otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    // Update session with new OTP
                    $_SESSION['password_reset']['otp'] = $new_otp;
                    $_SESSION['password_reset']['otp_expiry'] = $new_otp_expiry;
                    $_SESSION['password_reset']['verified'] = false;
                    
                    error_log("Resent password reset OTP: $new_otp for email: " . $reset_data['email']);
                    
                    // Send new OTP email
                    if (sendPasswordResetOTPEmail($reset_data['email'], $reset_data['name'], $new_otp, true)) {
                        echo json_encode(['success' => true, 'message' => 'New verification code sent successfully']);
                    } else {
                        // For testing, still return success with OTP
                        echo json_encode(['success' => true, 'message' => 'New verification code generated: ' . $new_otp]);
                    }
                    
                } catch (Exception $e) {
                    error_log("Error in resend_password_reset_otp: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred while sending verification code. Please try again.']);
                }
                exit();
                
            case 'reset_password':
                try {
                    $new_password = $_POST['new_password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';
                    
                    if (empty($new_password) || empty($confirm_password)) {
                        echo json_encode(['success' => false, 'message' => 'Both password fields are required']);
                        exit;
                    }
                    
                    if ($new_password !== $confirm_password) {
                        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                        exit;
                    }
                    
                    if (!validateStrongPassword($new_password)) {
                        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long and contain uppercase letter, number, and special character']);
                        exit;
                    }
                    
                    if (!isset($_SESSION['password_reset']) || !$_SESSION['password_reset']['verified']) {
                        echo json_encode(['success' => false, 'message' => 'Please verify your email first before resetting password']);
                        exit;
                    }
                    
                    $reset_data = $_SESSION['password_reset'];
                    
                    // Update password in database
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $reset_data['user_id']]);
                    
                    // Clear password reset session data
                    unset($_SESSION['password_reset']);
                    
                    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
                    
                } catch (PDOException $e) {
                    error_log("Database error in reset_password: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
                } catch (Exception $e) {
                    error_log("General error in reset_password: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred while resetting password']);
                }
                exit();

            case 'get_profile':
                // Check if user is logged in
                if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                    exit();
                }
                
                $user_id = $_SESSION['user_id'];
                
                // Fetch user data
                $user_stmt = $conn->prepare("SELECT name, email, mobile, created_at FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $result = $user_stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'user' => $user
                    ]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
                
                $user_stmt->close();
                exit();

            case 'signup_otp':
                try {
                    // Add debugging
                    error_log("Signup OTP called with data: " . print_r($_POST, true));
                    
                    $name = sanitizeInput($_POST['name'] ?? '');
                    $email = sanitizeInput($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';

                    // Validate input
                    if (empty($name) || empty($email) || empty($password)) {
                        echo json_encode(['success' => false, 'message' => 'All fields are required']);
                        exit;
                    }

                    if (!validateEmail($email)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                        exit;
                    }

                    if (!validatePassword($password)) {
                        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                        exit;
                    }

                    // Check if email already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'Email already registered']);
                        exit;
                    }

                    // Generate OTP
                    $otp = generateOTP();
                    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                    // Store user data temporarily in session
                    $_SESSION['temp_user'] = [
                        'name' => $name,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'otp' => $otp,
                        'otp_expiry' => $otp_expiry
                    ];

                    error_log("Generated OTP: $otp for email: $email");

                    // Send OTP email
                    if (sendOTPEmail($email, $name, $otp)) {
                        $_SESSION['show_otp'] = true;
                        echo json_encode(['success' => true, 'message' => 'OTP sent to your email']);
                    } else {
                        // For testing purposes, still allow proceed even if email fails
                        $_SESSION['show_otp'] = true;
                        echo json_encode(['success' => true, 'message' => 'OTP generated (email may not be configured). OTP: ' . $otp]);
                    }

                } catch (PDOException $e) {
                    error_log("Database error in signup: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
                } catch (Exception $e) {
                    error_log("General error in signup: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred during signup']);
                }
                exit();

            case 'verify_otp':
                try {
                    // Add debugging
                    error_log("Verify OTP called with data: " . print_r($_POST, true));
                    
                    // Collect OTP from individual inputs or single input
                    $entered_otp = '';
                    
                    // Check if OTP is sent as individual digits (otp1, otp2, etc.)
                    if (isset($_POST['otp1'])) {
                        for ($i = 1; $i <= 6; $i++) {
                            if (isset($_POST["otp$i"])) {
                                $entered_otp .= sanitizeInput($_POST["otp$i"]);
                            }
                        }
                    } else {
                        // Check if OTP is sent as a single field
                        $entered_otp = sanitizeInput($_POST['otp'] ?? '');
                    }
                    
                    if (empty($entered_otp)) {
                        echo json_encode(['success' => false, 'message' => 'Please enter the OTP']);
                        exit;
                    }

                    if (strlen($entered_otp) != 6) {
                        echo json_encode(['success' => false, 'message' => 'OTP must be 6 digits long']);
                        exit;
                    }

                    if (!isset($_SESSION['temp_user'])) {
                        echo json_encode(['success' => false, 'message' => 'Session expired. Please sign up again.']);
                        exit;
                    }

                    $temp_user = $_SESSION['temp_user'];
                    
                    error_log("Comparing OTPs - Entered: $entered_otp, Stored: " . $temp_user['otp']);
                    
                    // Check if OTP is expired
                    if (strtotime($temp_user['otp_expiry']) < time()) {
                        unset($_SESSION['temp_user'], $_SESSION['show_otp']);
                        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please sign up again.']);
                        exit;
                    }

                    // Verify OTP
                    if ($entered_otp === $temp_user['otp']) {
                        // Insert user into database
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_verified, created_at) VALUES (?, ?, ?, 1, NOW())");
                        $stmt->execute([
                            $temp_user['name'],
                            $temp_user['email'],
                            $temp_user['password']
                        ]);

                        // Clear temporary session data
                        unset($_SESSION['temp_user'], $_SESSION['show_otp']);
                        
                        echo json_encode(['success' => true, 'message' => 'Email verified successfully! You can now sign in.']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please check and try again.']);
                    }
                    
                } catch (PDOException $e) {
                    error_log("Database error in verify_otp: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
                } catch (Exception $e) {
                    error_log("General error in verify_otp: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred during verification']);
                }
                exit();

            case 'resend_otp':
                try {
                    // Check if temp user data exists in session
                    if (!isset($_SESSION['temp_user'])) {
                        echo json_encode(['success' => false, 'message' => 'Session expired. Please sign up again.']);
                        exit;
                    }

                    $temp_user = $_SESSION['temp_user'];
                    
                    // Generate new OTP
                    $new_otp = generateOTP();
                    $new_otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    
                    // Update session with new OTP
                    $_SESSION['temp_user']['otp'] = $new_otp;
                    $_SESSION['temp_user']['otp_expiry'] = $new_otp_expiry;
                    
                    error_log("Resent OTP: $new_otp for email: " . $temp_user['email']);
                    
                    // Send new OTP email
                    if (sendOTPEmail($temp_user['email'], $temp_user['name'], $new_otp, true)) {
                        echo json_encode(['success' => true, 'message' => 'New OTP sent successfully']);
                    } else {
                        // For testing, still return success with OTP
                        echo json_encode(['success' => true, 'message' => 'New OTP generated: ' . $new_otp]);
                    }
                    
                } catch (Exception $e) {
                    error_log("Error in resend_otp: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred while sending OTP. Please try again.']);
                }
                exit();

            case 'signin':
                try {
                    $email = sanitizeInput($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';

                    if (empty($email) || empty($password)) {
                        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                        exit;
                    }

                    if (!validateEmail($email)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                        exit;
                    }

                    $stmt = $pdo->prepare("SELECT id, name, email, password, is_verified FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        if ($user['is_verified'] == 0) {
                            echo json_encode(['success' => false, 'message' => 'Please verify your email before signing in']);
                            exit;
                        }

                        // Set session variables
                        $_SESSION['logged_in'] = true;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Welcome back, ' . $user['name'] . '!',
                            'user' => [
                                'id' => $user['id'],
                                'name' => $user['name'],
                                'email' => $user['email']
                            ]
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                    }

                } catch (PDOException $e) {
                    error_log("Database error in signin: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
                } catch (Exception $e) {
                    error_log("General error in signin: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred during sign in']);
                }
                exit();

            case 'check_session':
                try {
                    if (isset($_SESSION['user_id'])) {
                        echo json_encode([
                            'success' => true,
                            'authenticated' => true,
                            'user' => [
                                'id' => $_SESSION['user_id'],
                                'name' => $_SESSION['user_name'],
                                'email' => $_SESSION['user_email']
                            ]
                        ]);
                    } else {
                        echo json_encode([
                            'success' => true,
                            'authenticated' => false
                        ]);
                    }
                    
                } catch (Exception $e) {
                    error_log("Error in check_session: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'An error occurred while checking session']);
                }
                exit();
                
            case 'signup':
                // Updated original signup logic to use signup_otp instead
                header('Content-Type: application/json');
                
                $name = sanitizeInput($_POST['name'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                // Validate input
                if (empty($name) || empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit;
                }

                if (!validateEmail($email)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                    exit;
                }

                if (!validatePassword($password)) {
                    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                    exit;
                }
                
                // Check if user already exists
                $check_query = "SELECT id FROM users WHERE email = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists!']);
                    exit;
                }
                
                // Generate OTP and proceed with OTP verification
                $otp = generateOTP();
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                // Store user data temporarily in session
                $_SESSION['temp_user'] = [
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'otp' => $otp,
                    'otp_expiry' => $otp_expiry
                ];

                // Send OTP email
                if (sendOTPEmail($email, $name, $otp)) {
                    $_SESSION['show_otp'] = true;
                    echo json_encode(['success' => true, 'message' => 'Please verify your email to complete registration']);
                } else {
                    // For testing purposes, still allow proceed even if email fails
                    $_SESSION['show_otp'] = true;
                    echo json_encode(['success' => true, 'message' => 'OTP generated (email may not be configured). OTP: ' . $otp]);
                }
                
                $check_stmt->close();
                exit();

            

            case 'update_profile':
                // Check if user is logged in
                if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                    exit();
                }
                
                header('Content-Type: application/json');
                
                $user_id = $_SESSION['user_id'];
                $name = sanitizeInput($_POST['name'] ?? '');
                $mobile = sanitizeInput($_POST['mobile'] ?? '');
                
                // Validate input
                if (empty($name)) {
                    echo json_encode(['success' => false, 'message' => 'Name is required']);
                    exit();
                }
                
                // Validate mobile if provided
                if (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
                    echo json_encode(['success' => false, 'message' => 'Mobile number must be 10 digits']);
                    exit();
                }
                
                // Update user profile
                $update_stmt = $conn->prepare("UPDATE users SET name = ?, mobile = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $name, $mobile, $user_id);
                
                if ($update_stmt->execute()) {
                    // Update session data
                    $_SESSION['user_name'] = $name;
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Profile updated successfully',
                        'user' => [
                            'name' => $name,
                            'mobile' => $mobile
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
                }
                
                $update_stmt->close();
                exit();

            case 'change_password':
                // Check if user is logged in
                if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                    exit();
                }
                
                header('Content-Type: application/json');
                
                $user_id = $_SESSION['user_id'];
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                // Validate input
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    echo json_encode(['success' => false, 'message' => 'All password fields are required']);
                    exit();
                }
                
                if ($new_password !== $confirm_password) {
                    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
                    exit();
                }
                
                if (!validatePassword($new_password)) {
                    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
                    exit();
                }
                
                // Get current password from database
                $password_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $password_stmt->bind_param("i", $user_id);
                $password_stmt->execute();
                $password_result = $password_stmt->get_result();
                
                if ($password_result->num_rows === 1) {
                    $user_data = $password_result->fetch_assoc();
                    
                    if (password_verify($current_password, $user_data['password'])) {
                        // Update password
                        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_password_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update_password_stmt->bind_param("si", $hashed_new_password, $user_id);
                        
                        if ($update_password_stmt->execute()) {
                            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update password']);
                        }
                        
                        $update_password_stmt->close();
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
                
                $password_stmt->close();
                exit();

            case 'delete_account':
                // Check if user is logged in
                if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                    exit();
                }
                
                header('Content-Type: application/json');
                
                $user_id = $_SESSION['user_id'];
                $password = $_POST['password'] ?? '';
                
                if (empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Password is required to delete account']);
                    exit();
                }
                
                // Verify password before deletion
                $verify_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $verify_stmt->bind_param("i", $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                
                if ($verify_result->num_rows === 1) {
                    $user_data = $verify_result->fetch_assoc();
                    
                    if (password_verify($password, $user_data['password'])) {
                        // Delete user account
                        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                        $delete_stmt->bind_param("i", $user_id);
                        
                        if ($delete_stmt->execute()) {
                            // Clear session
                            session_unset();
                            session_destroy();
                            
                            echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
                        }
                        
                        $delete_stmt->close();
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
                
                $verify_stmt->close();
                exit();

            default:
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }
    }

    // Handle GET requests
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'check_session':
                header('Content-Type: application/json');
                if (isset($_SESSION['user_id'])) {
                    echo json_encode([
                        'success' => true,
                        'authenticated' => true,
                        'user' => [
                            'id' => $_SESSION['user_id'],
                            'name' => $_SESSION['user_name'],
                            'email' => $_SESSION['user_email']
                        ]
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'authenticated' => false
                    ]);
                }
                exit();
                
            case 'get_profile':
                // Check if user is logged in
                if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                    exit();
                }
                
                $user_id = $_SESSION['user_id'];
                
                // Fetch user data
                $user_stmt = $conn->prepare("SELECT name, email, mobile, created_at FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $result = $user_stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'user' => $user
                    ]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
                
                $user_stmt->close();
                exit();
                
            default:
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid GET action']);
                exit();
        }
    }

    // Close database connection
    $conn->close();
?>