<?php
    session_start();
    header('Content-Type: application/json');

    // Include database connection - fix the path
    require_once './include/database_connection.php';

    // Get form data
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate input
    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required'
        ]);
        exit;
    }

    try {
        // Check if PDO connection exists
        if (!isset($pdo)) {
            throw new Exception('Database connection not available');
        }
        
        // Use the PDO connection from database_connection.php
        $db = $pdo;
        
        // Prepare query
        $query = "SELECT adm_id, adm_name, adm_pass FROM admin WHERE adm_email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':email', $email);
        
        // Execute query
        $stmt->execute();
        
        // Check if admin exists
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password (using plain text comparison for now)
            if ($password == $row['adm_pass']) {
                // Password is correct, set session variables
                $_SESSION['adm_id'] = $row['adm_id'];
                $_SESSION['adm_name'] = $row['adm_name'];
                $_SESSION['isAdmin'] = true;
                
                // Return success
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful'
                ]);
            } else {
                // Password is incorrect
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ]);
            }
        } else {
            // Admin not found
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email or password'
            ]);
        }
    } catch (PDOException $e) {
        // Log the actual error for debugging
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error'
        ]);
    } catch (Exception $e) {
        // Log the actual error for debugging
        error_log("General error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'System error occurred'
        ]);
    }
?>