<?php
session_start();
require_once 'config.php';
require_once 'email_config.php';

// Set content type to JSON for AJAX requests
header('Content-Type: application/json');

// Function to generate random OTP
function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Function to send OTP email using PHPMailer
function sendOTPEmail($email, $name, $otp) {
    $subject = "Email Verification - CureBooking";
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
                <p>Thank you for signing up with CureBooking! To complete your registration, please verify your email address using the OTP below:</p>
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

    // Use the PHPMailer function from email_config.php
    return sendEmailWithGmail($email, $subject, $message, $name);
}

// Function to send resend OTP email
function sendResendOTPEmail($email, $name, $otp) {
    $subject = "Email Verification - CureBooking";
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
                <p>Here is your new verification code:</p>
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

    // Use the PHPMailer function from email_config.php
    return sendEmailWithGmail($email, $subject, $message, $name);
}

// Main request handler
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'signup':
            try {
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];

                // Validate input
                if (empty($name) || empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    exit;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                    exit;
                }

                if (strlen($password) < 6) {
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

                // Send OTP email
                if (sendOTPEmail($email, $name, $otp)) {
                    $_SESSION['show_otp'] = true;
                    echo json_encode(['success' => true, 'message' => 'OTP sent to your email']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please check your email configuration.']);
                }

            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
            } catch (Exception $e) {
                error_log("General error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
            }
            break;

        case 'verify_otp':
            try {
                // Collect OTP from individual inputs (for the new verify_otp.php format)
                $entered_otp = '';
                for ($i = 1; $i <= 6; $i++) {
                    if (isset($_POST["otp$i"])) {
                        $entered_otp .= $_POST["otp$i"];
                    }
                }
                
                // If no individual inputs, try the single OTP field (for auth.php compatibility)
                if (empty($entered_otp) && isset($_POST['otp'])) {
                    $entered_otp = trim($_POST['otp']);
                }
                
                if (empty($entered_otp) || strlen($entered_otp) != 6) {
                    echo json_encode(['success' => false, 'message' => 'Please enter the complete 6-digit OTP']);
                    exit;
                }

                if (!isset($_SESSION['temp_user'])) {
                    echo json_encode(['success' => false, 'message' => 'Session expired. Please sign up again.']);
                    exit;
                }

                $temp_user = $_SESSION['temp_user'];
                
                // Check if OTP is expired
                if (strtotime($temp_user['otp_expiry']) < time()) {
                    unset($_SESSION['temp_user']);
                    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please sign up again.']);
                    exit;
                }

                // Verify OTP
                if ($entered_otp == $temp_user['otp']) {
                    // Insert user into database
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_verified) VALUES (?, ?, ?, 1)");
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
                error_log("Database error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
            } catch (Exception $e) {
                error_log("OTP verification error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'An error occurred during verification. Please try again.']);
            }
            break;

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
                
                // Send new OTP email
                if (sendResendOTPEmail($temp_user['email'], $temp_user['name'], $new_otp)) {
                    echo json_encode(['success' => true, 'message' => 'New OTP sent successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please check your email configuration.']);
                }
                
            } catch (Exception $e) {
                error_log("Resend OTP error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'An error occurred while sending OTP. Please try again.']);
            }
            break;

        case 'signin':
            try {
                $email = trim($_POST['email']);
                $password = $_POST['password'];

                if (empty($email) || empty($password)) {
                    // For signin, we might want to handle differently (redirect vs JSON)
                    // Check if it's an AJAX request
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                    } else {
                        $_SESSION['error'] = 'Email and password are required';
                        header('Location: login.php');
                    }
                    exit;
                }

                $stmt = $pdo->prepare("SELECT id, name, email, password, is_verified FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['is_verified'] == 0) {
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            echo json_encode(['success' => false, 'message' => 'Please verify your email before signing in']);
                        } else {
                            $_SESSION['error'] = 'Please verify your email before signing in';
                            header('Location: login.php');
                        }
                        exit;
                    }

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['success'] = 'Welcome back, ' . $user['name'] . '!';
                    
                    // Check if it's an AJAX request
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        echo json_encode(['success' => true, 'message' => 'Sign in successful', 'redirect' => 'dashboard.php']);
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                } else {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                    } else {
                        $_SESSION['error'] = 'Invalid email or password';
                        header('Location: login.php');
                    }
                    exit;
                }

            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
                } else {
                    $_SESSION['error'] = 'Database error occurred';
                    header('Location: login.php');
                }
                exit;
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
            break;
    }
} else {
    // Handle GET requests or direct access
    header('Location: login.php');
    exit;
}
?>