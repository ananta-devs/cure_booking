<?php
    session_start();

    // Check if user is logged in (for both doctor and clinic)
    if (!isset($_SESSION['logged_in']) && !isset($_SESSION['clinic_logged_in'])) {
        header('Location: ../login.php');
        exit;
    }

    // Database configuration
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cure_booking";

    // Initialize user data variables
    $user_data = [];
    $user_type = '';

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Determine user type and fetch data
        if (isset($_SESSION['clinic_logged_in']) && $_SESSION['user_type'] === 'clinic') {
            $user_type = 'clinic';
            $stmt = $pdo->prepare("SELECT * FROM clinics WHERE clinic_id = :id");
            $stmt->bindParam(':id', $_SESSION['clinic_id'], PDO::PARAM_INT);
            $stmt->execute();
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif (isset($_SESSION['logged_in']) && $_SESSION['user_type'] === 'doctor') {
            $user_type = 'doctor';
            $stmt = $pdo->prepare("SELECT * FROM doctor WHERE id = :id");
            $stmt->bindParam(':id', $_SESSION['doctor_id'], PDO::PARAM_INT);
            $stmt->execute();
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error_message = "Database connection failed";
    }

    // Handle AJAX requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'changePassword') {
            handlePasswordChange($pdo, $user_type);
        } elseif ($action === 'updateImage') {
            handleImageUpload($pdo, $user_type);
        }
        
        exit;
    }

    function handlePasswordChange($pdo, $user_type) {
        $currentPassword = trim($_POST['currentPassword'] ?? '');
        $newPassword = trim($_POST['newPassword'] ?? '');
        
        // Validation
        if (empty($currentPassword) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            return;
        }
        
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            return;
        }
        
        try {
            if ($user_type === 'clinic') {
                $stmt = $pdo->prepare("SELECT clinic_pass FROM clinics WHERE clinic_id = :id");
                $stmt->bindParam(':id', $_SESSION['clinic_id'], PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $storedPassword = $result['clinic_pass'];
                
                // Verify current password
                $passwordMatch = false;
                if (password_verify($currentPassword, $storedPassword)) {
                    $passwordMatch = true;
                } elseif ($currentPassword === $storedPassword) {
                    $passwordMatch = true;
                }
                
                if (!$passwordMatch) {
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                    return;
                }
                
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE clinics SET clinic_pass = :password, updated_at = NOW() WHERE clinic_id = :id");
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':id', $_SESSION['clinic_id'], PDO::PARAM_INT);
                
            } else { // doctor
                $stmt = $pdo->prepare("SELECT doc_pass FROM doctor WHERE id = :id");
                $stmt->bindParam(':id', $_SESSION['doctor_id'], PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $storedPassword = $result['doc_pass'];
                
                // Verify current password
                $passwordMatch = false;
                if (password_verify($currentPassword, $storedPassword)) {
                    $passwordMatch = true;
                } elseif ($currentPassword === $storedPassword) {
                    $passwordMatch = true;
                }
                
                if (!$passwordMatch) {
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                    return;
                }
                
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE doctor SET doc_pass = :password WHERE id = :id");
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':id', $_SESSION['doctor_id'], PDO::PARAM_INT);
            }
            
            if ($updateStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password']);
            }
            
        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    }

    function handleImageUpload($pdo, $user_type) {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
            return;
        }
        
        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF allowed']);
            return;
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
            return;
        }
        
        // Create upload directory
        $uploadDir = $user_type === 'clinic' ? 'uploads/clinics/' : 'uploads/doctors/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $user_type . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            try {
                // Update database
                if ($user_type === 'clinic') {
                    $stmt = $pdo->prepare("UPDATE clinics SET profile_image = :image, updated_at = NOW() WHERE clinic_id = :id");
                    $stmt->bindParam(':image', $filepath);
                    $stmt->bindParam(':id', $_SESSION['clinic_id'], PDO::PARAM_INT);
                } else {
                    $stmt = $pdo->prepare("UPDATE doctor SET doc_img = :image WHERE id = :id");
                    $stmt->bindParam(':image', $filepath);
                    $stmt->bindParam(':id', $_SESSION['doctor_id'], PDO::PARAM_INT);
                }
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Profile image updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update database']);
                }
                
            } catch (PDOException $e) {
                error_log("Image upload database error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        }
    }

    // Helper function to get display data based on user type
    function getDisplayData($user_data, $user_type) {
        if ($user_type === 'clinic') {
            return [
                'name' => $user_data['clinic_name'] ?? 'Clinic Name',
                'email' => $user_data['clinic_email'] ?? 'email@example.com',
                'phone' => $user_data['contact_number'] ?? 'Phone not available',
                'location' => $user_data['location'] ?? 'Location not specified',
                'timing' => $user_data['available_timing'] ?? 'Timing not specified',
                'about' => $user_data['about'] ?? 'About information not available',
                'image' => $user_data['profile_image'] ?? null
            ];
        } else {
            return [
                'name' => 'Dr. ' . ($user_data['doc_name'] ?? 'Doctor Name'),
                'email' => $user_data['doc_email'] ?? 'email@example.com',
                'phone' => $user_data['doc_phone'] ?? 'Phone not available',
                'location' => $user_data['doc_location'] ?? 'Location not specified',
                'timing' => $user_data['doc_timing'] ?? 'Timing not specified',
                'about' => $user_data['doc_about'] ?? 'About information not available',
                'image' => $user_data['doc_img'] ?? null
            ];
        }
    }

    $display_data = getDisplayData($user_data, $user_type);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Settings - <?php echo htmlspecialchars($display_data['name']); ?></title>
        <link rel="stylesheet" href="styles.css" />
        <link
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
            rel="stylesheet"
        />
        <style>
            /* clinic profile */
            .profile-content {
                display: flex;
                grid-template-columns: 1fr 2fr;
                gap: 30px;
                padding: 30px;
            }
            .profile-card,
            .form-section {
                background: white;
                border-radius: 15px;
                padding: 30px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }
            .profile-card {
                text-align: center;
                height: fit-content;
            }
            .profile-image {
                width: 150px;
                height: 150px;
                border-radius: 50%;
                margin: 0 auto 20px;
                position: relative;
                overflow: hidden;
                border: 5px solid #4facfe;
            }
            .profile-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .profile-image .placeholder {
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 3rem;
            }
            .upload-btn {
                position: absolute;
                bottom: 5px;
                right: 5px;
                background: #4facfe;
                color: white;
                border: none;
                border-radius: 50%;
                width: 35px;
                height: 35px;
                cursor: pointer;
            }
            .upload-btn:hover {
                background: #0066cc;
                transform: scale(1.1);
            }
            .profile-name {
                font-size: 1.5rem;
                font-weight: bold;
                color: #333;
                margin-bottom: 10px;
            }
            .profile-info {
                margin: 15px 0;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 8px;
                text-align: left;
            }
            .profile-info strong {
                color: #4facfe;
            }
            .status-badge {
                display: inline-block;
                background: linear-gradient(135deg, #00c851 0%, #00ff88 100%);
                color: white;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 0.9rem;
                font-weight: bold;
            }
            .form-section h2 {
                color: #333;
                margin-bottom: 25px;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 1.4rem;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #555;
                font-weight: 600;
            }
            .form-control {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                font-size: 1rem;
            }
            .form-control:focus {
                outline: none;
                border-color: #4facfe;
                box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
            }
            .btn {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 10px;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);
            }
            .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
            }
            .btn-danger {
                background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
            }
            .btn-secondary {
                background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);
            }
            .alert {
                padding: 15px;
                border-radius: 10px;
                margin-bottom: 20px;
                display: none;
            }
            .alert-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .alert-danger {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .input-group {
                position: relative;
            }
            .toggle-password {
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: #666;
                cursor: pointer;
            }
            #imageInput {
                display: none;
            }
            @media (max-width: 768px) {
                .profile-content {
                    grid-template-columns: 1fr;
                }
                .container {
                    margin: 10px;
                }
            }
        </style>
    </head>
    <body>
        <?php include './include/top-header.php'; ?>

        <div class="container">
            <?php include './include/sidebar.php'; ?>
            <!-- Main Content -->
            <main class="main-content">
                <div id="settings-section" class="content-section active">
                    <div class="profile-content">
                        <div class="profile-card">
                            <div class="profile-image">
                                <?php if (!empty($display_data['image']) && file_exists($display_data['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($display_data['image']); ?>" alt="Profile" />
                                <?php else: ?>
                                    <div class="placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <button
                                    class="upload-btn"
                                    onclick="document.getElementById('imageInput').click()"
                                >
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>

                            <div class="profile-name"><?php echo htmlspecialchars($display_data['name']); ?></div>
                            <div class="profile-info">
                                <strong>Email: <?php echo htmlspecialchars($display_data['email']); ?></strong>
                            </div>
                            <div class="profile-info">
                                <strong>Phone: <?php echo htmlspecialchars($display_data['phone']); ?></strong>
                            </div>
                            <div class="profile-info">
                                <strong>Location: <?php echo htmlspecialchars($display_data['location']); ?></strong>
                            </div>
                            <div class="profile-info">
                                <strong>Available: <?php echo htmlspecialchars($display_data['timing']); ?></strong>
                            </div>
                            <div class="profile-info">
                                <strong>About: <?php echo htmlspecialchars($display_data['about']); ?></strong>
                            </div>
                            <div class="status-badge">
                                <i class="fas fa-check-circle"></i> Active
                            </div>

                            <form
                                id="imageForm"
                                enctype="multipart/form-data"
                                style="margin-top: 20px"
                            >
                                <input
                                    type="file"
                                    id="imageInput"
                                    name="image"
                                    accept="image/*"
                                    onchange="uploadImage()"
                                />
                                <div
                                    class="alert alert-success"
                                    id="imageSuccessAlert"
                                ></div>
                                <div
                                    class="alert alert-danger"
                                    id="imageErrorAlert"
                                ></div>
                            </form>
                        </div>

                        <div class="form-section">
                            <h2><i class="fas fa-key"></i> Change Password</h2>
                            <div
                                class="alert alert-success"
                                id="passwordSuccessAlert"
                            ></div>
                            <div
                                class="alert alert-danger"
                                id="passwordErrorAlert"
                            ></div>

                            <form id="passwordForm">
                                <div class="form-group">
                                    <label
                                        ><i class="fas fa-lock"></i> Current
                                        Password</label
                                    >
                                    <div class="input-group">
                                        <input
                                            type="password"
                                            id="currentPassword"
                                            name="currentPassword"
                                            class="form-control"
                                            required
                                        />
                                        <button
                                            type="button"
                                            class="toggle-password"
                                            onclick="togglePassword('currentPassword')"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label
                                        ><i class="fas fa-key"></i> New
                                        Password</label
                                    >
                                    <div class="input-group">
                                        <input
                                            type="password"
                                            id="newPassword"
                                            name="newPassword"
                                            class="form-control"
                                            required
                                            minlength="6"
                                        />
                                        <button
                                            type="button"
                                            class="toggle-password"
                                            onclick="togglePassword('newPassword')"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label
                                        ><i class="fas fa-check"></i> Confirm
                                        Password</label
                                    >
                                    <div class="input-group">
                                        <input
                                            type="password"
                                            id="confirmPassword"
                                            class="form-control"
                                            required
                                            minlength="6"
                                        />
                                        <button
                                            type="button"
                                            class="toggle-password"
                                            onclick="togglePassword('confirmPassword')"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div
                                    style="text-align: center; margin-top: 20px"
                                >
                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                        id="changePasswordBtn"
                                    >
                                        <i class="fas fa-key"></i> Change
                                        Password
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        onclick="resetForm()"
                                        style="margin-left: 10px"
                                    >
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <script>
            function uploadImage() {
                const file = document.getElementById("imageInput").files[0];
                if (!file) return;

                if (file.size > 5 * 1024 * 1024) {
                    showAlert(
                        "Image size must be less than 5MB",
                        "error",
                        "image"
                    );
                    return;
                }

                if (
                    ![
                        "image/jpeg",
                        "image/jpg",
                        "image/png",
                        "image/gif",
                    ].includes(file.type)
                ) {
                    showAlert(
                        "Please select a valid image file",
                        "error",
                        "image"
                    );
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    document.querySelector(".profile-image").innerHTML = `
                    <img src="${e.target.result}" alt="Profile">
                    <button class="upload-btn" onclick="document.getElementById('imageInput').click()"><i class="fas fa-camera"></i></button>
                `;
                };
                reader.readAsDataURL(file);

                const formData = new FormData();
                formData.append("action", "updateImage");
                formData.append("image", file);

                fetch(window.location.href, { method: "POST", body: formData })
                    .then((response) => response.json())
                    .then((data) =>
                        showAlert(
                            data.message,
                            data.success ? "success" : "error",
                            "image"
                        )
                    )
                    .catch(() => showAlert("Upload failed", "error", "image"));
            }

            function togglePassword(fieldId) {
                const field = document.getElementById(fieldId);
                const icon = field.nextElementSibling.querySelector("i");

                if (field.type === "password") {
                    field.type = "text";
                    icon.className = "fas fa-eye-slash";
                } else {
                    field.type = "password";
                    icon.className = "fas fa-eye";
                }
            }

            function showAlert(message, type, section) {
                const alertId = `${section}${
                    type === "success" ? "Success" : "Error"
                }Alert`;
                const otherAlertId = `${section}${
                    type === "success" ? "Error" : "Success"
                }Alert`;

                document.getElementById(otherAlertId).style.display = "none";
                const alertDiv = document.getElementById(alertId);
                alertDiv.textContent = message;
                alertDiv.style.display = "block";

                setTimeout(() => (alertDiv.style.display = "none"), 5000);
            }

            function resetForm() {
                document.getElementById("passwordForm").reset();
                document.getElementById("passwordSuccessAlert").style.display =
                    "none";
                document.getElementById("passwordErrorAlert").style.display =
                    "none";
            }

            document
                .getElementById("passwordForm")
                .addEventListener("submit", function (e) {
                    e.preventDefault();

                    const currentPassword =
                        document.getElementById("currentPassword").value;
                    const newPassword =
                        document.getElementById("newPassword").value;
                    const confirmPassword =
                        document.getElementById("confirmPassword").value;

                    if (newPassword !== confirmPassword) {
                        showAlert(
                            "Passwords do not match",
                            "error",
                            "password"
                        );
                        return;
                    }

                    if (newPassword.length < 6) {
                        showAlert(
                            "Password must be at least 6 characters",
                            "error",
                            "password"
                        );
                        return;
                    }

                    if (currentPassword === newPassword) {
                        showAlert(
                            "New password must be different",
                            "error",
                            "password"
                        );
                        return;
                    }

                    const changeBtn =
                        document.getElementById("changePasswordBtn");
                    changeBtn.disabled = true;
                    changeBtn.innerHTML =
                        '<i class="fas fa-spinner fa-spin"></i> Changing...';

                    const formData = new FormData(this);
                    formData.append("action", "changePassword");

                    fetch(window.location.href, {
                        method: "POST",
                        body: formData,
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            changeBtn.disabled = false;
                            changeBtn.innerHTML =
                                '<i class="fas fa-key"></i> Change Password';

                            if (data.success) {
                                showAlert(data.message, "success", "password");
                                this.reset();
                            } else {
                                showAlert(data.message, "error", "password");
                            }
                        })
                        .catch(() => {
                            changeBtn.disabled = false;
                            changeBtn.innerHTML =
                                '<i class="fas fa-key"></i> Change Password';
                            showAlert(
                                "Password change failed",
                                "error",
                                "password"
                            );
                        });
                });
        </script>
    </body>
</html>