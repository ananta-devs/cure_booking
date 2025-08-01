<?php
// Email configuration

// Set the default timezone
date_default_timezone_set('Asia/Kolkata'); 

// PHPMailer configuration
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;



require_once 'phpmailer/src/Exception.php';
require_once 'phpmailer/src/PHPMailer.php';
require_once 'phpmailer/src/SMTP.php';

function sendEmailWithGmail($to, $subject, $body, $recipientName = '') {
    $mail = new PHPMailer(true);

    try {
        // Enable verbose debug output (comment out in production)
        $mail->SMTPDebug = 0; // Set to 2 for debugging, 0 for production
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply.curebooking@gmail.com';     
        $mail->Password   = 'zurn mbvz ewly ztlr';          
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('noreply.curebooking@gmail.com', 'CureBooking');
        $mail->addAddress($to, $recipientName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Alternative function using basic PHP mail() - use this if PHPMailer doesn't work
function sendEmailBasic($to, $subject, $body, $recipientName = '') {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: CureBooking <noreply.curebooking@gmail.com>" . "\r\n";

    return mail($to, $subject, $body, $headers);
}

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to sanitize email content
function sanitizeEmailContent($content) {
    return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
}
?>