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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    
    switch ($action) {
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
            
        case 'signup':
            // Sign up logic
            $name = $conn->real_escape_string($_POST['name']);
            $email = $conn->real_escape_string($_POST['email']);
            $password = $_POST['password'];
            
            // Check if user already exists
            $check_query = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $_SESSION['error'] = "Email already exists!";
                header("Location: http://localhost/cure_booking/user/login.php");
                exit();
            }
            
            // Hash password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success'] = "Account created successfully! Please sign in.";
                header("Location: http://localhost/cure_booking/user/login.php");
            } else {
                $_SESSION['error'] = "Error creating account. Please try again.";
                header("Location: http://localhost/cure_booking/user/login.php");
            }
            
            $insert_stmt->close();
            $check_stmt->close();
            break;
            
        case 'signin':
            // Sign in logic
            $email = $conn->real_escape_string($_POST['email']);
            $password = $_POST['password'];
            
            // Get user data
            $login_query = "SELECT id, name, email, password FROM users WHERE email = ?";
            $login_stmt = $conn->prepare($login_query);
            $login_stmt->bind_param("s", $email);
            $login_stmt->execute();
            $login_result = $login_stmt->get_result();
            
            if ($login_result->num_rows == 1) {
                $user = $login_result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    // Login successful - Set session variables
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Redirect to dashboard
                    header("Location: http://localhost/cure_booking/index.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Invalid email or password!";
                    header("Location: http://localhost/cure_booking/user/login.php");
                }
            } else {
                $_SESSION['error'] = "Invalid email or password!";
                header("Location: http://localhost/cure_booking/user/login.php");
            }
            
            $login_stmt->close();
            break;
            
        case 'update_profile':
            // Check if user is logged in
            if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                header("Location: http://localhost/cure_booking/user/login.php");
                exit();
            }
            
            $user_id = $_SESSION['user_id'];
            $field = $_POST['field'];
            $value = $conn->real_escape_string($_POST['value']);
            
            switch ($field) {
                case 'name':
                    $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
                    $stmt->bind_param("si", $value, $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['user_name'] = $value;
                        $_SESSION['success'] = "Name updated successfully!";
                    } else {
                        $_SESSION['error'] = "Error updating name.";
                    }
                    $stmt->close();
                    break;
                    
                case 'email':
                    // Check if email already exists
                    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $check_stmt->bind_param("si", $value, $user_id);
                    $check_stmt->execute();
                    
                    if ($check_stmt->get_result()->num_rows > 0) {
                        $_SESSION['error'] = "Email already exists!";
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                        $stmt->bind_param("si", $value, $user_id);
                        
                        if ($stmt->execute()) {
                            $_SESSION['user_email'] = $value;
                            $_SESSION['success'] = "Email updated successfully!";
                        } else {
                            $_SESSION['error'] = "Error updating email.";
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                    break;
                    
                case 'mobile':
                    $stmt = $conn->prepare("UPDATE users SET mobile = ? WHERE id = ?");
                    $stmt->bind_param("si", $value, $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Mobile number updated successfully!";
                    } else {
                        $_SESSION['error'] = "Error updating mobile number.";
                    }
                    $stmt->close();
                    break;
                    
                default:
                    $_SESSION['error'] = "Invalid field specified.";
                    break;
            }
            
            // Redirect back to profile page
            header("Location: http://localhost/cure_booking/user/profile_card.php");
            exit();
            
        case 'change_password':
            // Check if user is logged in
            if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                header("Location: http://localhost/cure_booking/user/login.php");
                exit();
            }
            
            $user_id = $_SESSION['user_id'];
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Get current password
            $pass_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $pass_stmt->bind_param("i", $user_id);
            $pass_stmt->execute();
            $user_data = $pass_stmt->get_result()->fetch_assoc();
            
            if (password_verify($current_password, $user_data['password'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 6) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->bind_param("si", $hashed_password, $user_id);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success'] = "Password changed successfully!";
                        } else {
                            $_SESSION['error'] = "Error changing password.";
                        }
                        $stmt->close();
                    } else {
                        $_SESSION['error'] = "Password must be at least 6 characters long.";
                    }
                } else {
                    $_SESSION['error'] = "New passwords do not match.";
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect.";
            }
            
            $pass_stmt->close();
            
            // Redirect back to profile page
            header("Location: http://localhost/cure_booking/user/profile_card.php");
            exit();
            
        case 'logout':
            // Logout logic
            session_destroy();
            header("Location: http://localhost/cure_booking/user/login.php");
            exit();
            
        default:
            $_SESSION['error'] = "Invalid action specified.";
            header("Location: http://localhost/cure_booking/user/login.php");
            exit();
    }
}

$conn->close();
?>