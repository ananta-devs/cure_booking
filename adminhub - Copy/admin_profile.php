<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=cure_booking", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'updateImage') {
        $adminId = $_SESSION['adm_id'];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = 'sett/admin_images/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            if (getimagesize($_FILES['image']['tmp_name']) === false) {
                echo json_encode(['success' => false, 'message' => 'Invalid image file']);
                exit;
            }
            
            $imageName = date('YmdHis') . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $uploadPath = $uploadDir . $imageName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("UPDATE admin SET adm_img = ? WHERE adm_id = ?");
                $stmt->execute([$imageName, $adminId]);
                echo json_encode(['success' => true, 'message' => 'Image updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            }
        }
        exit;
    }
    
    if ($_POST['action'] === 'changePassword') {
        $adminId = $_SESSION['adm_id'];
        $currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';
        
        $stmt = $pdo->prepare("SELECT adm_pass FROM admin WHERE adm_id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
            exit;
        }
        
        $storedPassword = $admin['adm_pass'];
        $isPasswordCorrect = password_get_info($storedPassword)['algo'] !== null 
            ? password_verify($currentPassword, $storedPassword)
            : ($storedPassword === $currentPassword);
        
        if (!$isPasswordCorrect) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
        
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin SET adm_pass = ? WHERE adm_id = ?");
        $stmt->execute([$hashedNewPassword, $adminId]);
        
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM admin WHERE adm_id = ?");
$stmt->execute([$_SESSION['adm_id']]);
$currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Cure Booking</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: rgba(255, 255, 255, 0.95); border-radius: 20px; backdrop-filter: blur(10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 30px; text-align: center; color: white; position: relative; }
        .back-btn { position: absolute; top: 20px; left: 20px; background: rgba(255,255,255,0.2); color: white; border: none; padding: 10px 15px; border-radius: 10px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .back-btn:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .profile-content { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; padding: 30px; }
        .profile-card, .form-section { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .profile-card { text-align: center; height: fit-content; }
        .profile-image { width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 20px; position: relative; overflow: hidden; border: 5px solid #4facfe; }
        .profile-image img { width: 100%; height: 100%; object-fit: cover; }
        .profile-image .placeholder { width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; }
        .upload-btn { position: absolute; bottom: 5px; right: 5px; background: #4facfe; color: white; border: none; border-radius: 50%; width: 35px; height: 35px; cursor: pointer; }
        .upload-btn:hover { background: #0066cc; transform: scale(1.1); }
        .profile-name { font-size: 1.5rem; font-weight: bold; color: #333; margin-bottom: 10px; }
        .profile-info { margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 8px; text-align: left; }
        .profile-info strong { color: #4facfe; }
        .status-badge { display: inline-block; background: linear-gradient(135deg, #00c851 0%, #00ff88 100%); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; }
        .form-section h2 { color: #333; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; font-size: 1.4rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #555; font-weight: 600; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; }
        .form-control:focus { outline: none; border-color: #4facfe; box-shadow: 0 0 0 3px rgba(79,172,254,0.1); }
        .btn { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(79,172,254,0.3); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%); }
        .btn-secondary { background: linear-gradient(135deg, #6c757d 0%, #868e96 100%); }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: none; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .input-group { position: relative; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #666; cursor: pointer; }
        #imageInput { display: none; }
        @media (max-width: 768px) { .profile-content { grid-template-columns: 1fr; } .header h1 { font-size: 2rem; } .container { margin: 10px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="http://localhost/cure_booking/adminhub/index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h1><i class="fas fa-user-cog"></i> Admin Profile</h1>
        </div>

        <div class="profile-content">
            <div class="profile-card">
                <div class="profile-image">
                    <?php if ($currentAdmin && $currentAdmin['adm_img']): ?>
                        <img src="sett/admin_images/<?php echo htmlspecialchars($currentAdmin['adm_img']); ?>" alt="Profile">
                    <?php else: ?>
                        <div class="placeholder"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                    <button class="upload-btn" onclick="document.getElementById('imageInput').click()"><i class="fas fa-camera"></i></button>
                </div>
                
                <div class="profile-name"><?php echo $currentAdmin ? htmlspecialchars($currentAdmin['adm_name']) : 'Admin User'; ?></div>
                <div class="profile-info"><strong>Email:</strong> <?php echo $currentAdmin ? htmlspecialchars($currentAdmin['adm_email']) : 'N/A'; ?></div>
                <div class="profile-info"><strong>Phone:</strong> <?php echo $currentAdmin ? htmlspecialchars($currentAdmin['adm_ph']) : 'N/A'; ?></div>
                <div class="status-badge"><i class="fas fa-check-circle"></i> Active</div>

                <form id="imageForm" enctype="multipart/form-data" style="margin-top: 20px;">
                    <input type="file" id="imageInput" name="image" accept="image/*" onchange="uploadImage()">
                    <div class="alert alert-success" id="imageSuccessAlert"></div>
                    <div class="alert alert-danger" id="imageErrorAlert"></div>
                </form>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-key"></i> Change Password</h2>
                <div class="alert alert-success" id="passwordSuccessAlert"></div>
                <div class="alert alert-danger" id="passwordErrorAlert"></div>

                <form id="passwordForm">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <div class="input-group">
                            <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('currentPassword')"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <div class="input-group">
                            <input type="password" id="newPassword" name="newPassword" class="form-control" required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword('newPassword')"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-check"></i> Confirm Password</label>
                        <div class="input-group">
                            <input type="password" id="confirmPassword" class="form-control" required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <button type="submit" class="btn btn-danger" id="changePasswordBtn"><i class="fas fa-key"></i> Change Password</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()" style="margin-left: 10px;"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function uploadImage() {
            const file = document.getElementById('imageInput').files[0];
            if (!file) return;
            
            if (file.size > 5 * 1024 * 1024) {
                showAlert('Image size must be less than 5MB', 'error', 'image');
                return;
            }
            
            if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif'].includes(file.type)) {
                showAlert('Please select a valid image file', 'error', 'image');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = e => {
                document.querySelector('.profile-image').innerHTML = `
                    <img src="${e.target.result}" alt="Profile">
                    <button class="upload-btn" onclick="document.getElementById('imageInput').click()"><i class="fas fa-camera"></i></button>
                `;
            };
            reader.readAsDataURL(file);
            
            const formData = new FormData();
            formData.append('action', 'updateImage');
            formData.append('image', file);
            
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => showAlert(data.message, data.success ? 'success' : 'error', 'image'))
                .catch(() => showAlert('Upload failed', 'error', 'image'));
        }

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function showAlert(message, type, section) {
            const alertId = `${section}${type === 'success' ? 'Success' : 'Error'}Alert`;
            const otherAlertId = `${section}${type === 'success' ? 'Error' : 'Success'}Alert`;
            
            document.getElementById(otherAlertId).style.display = 'none';
            const alertDiv = document.getElementById(alertId);
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            
            setTimeout(() => alertDiv.style.display = 'none', 5000);
        }

        function resetForm() {
            document.getElementById('passwordForm').reset();
            document.getElementById('passwordSuccessAlert').style.display = 'none';
            document.getElementById('passwordErrorAlert').style.display = 'none';
        }

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                showAlert('Passwords do not match', 'error', 'password');
                return;
            }
            
            if (newPassword.length < 6) {
                showAlert('Password must be at least 6 characters', 'error', 'password');
                return;
            }
            
            if (currentPassword === newPassword) {
                showAlert('New password must be different', 'error', 'password');
                return;
            }
            
            const changeBtn = document.getElementById('changePasswordBtn');
            changeBtn.disabled = true;
            changeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
            
            const formData = new FormData(this);
            formData.append('action', 'changePassword');
            
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    changeBtn.disabled = false;
                    changeBtn.innerHTML = '<i class="fas fa-key"></i> Change Password';
                    
                    if (data.success) {
                        showAlert(data.message, 'success', 'password');
                        this.reset();
                    } else {
                        showAlert(data.message, 'error', 'password');
                    }
                })
                .catch(() => {
                    changeBtn.disabled = false;
                    changeBtn.innerHTML = '<i class="fas fa-key"></i> Change Password';
                    showAlert('Password change failed', 'error', 'password');
                });
        });
    </script>
</body>
</html>