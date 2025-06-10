<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: http://localhost/adminhub/login.php");
    exit();
}
include '../include/database_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management | Cure Booking</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include '../include/sidebar.php'; ?>
    
    <section id="content">
        <?php include '../include/top-header.php'; ?>
        <main>
            <div class="container">
                <h1 class="page-title">Admin Management</h1>
                
                <div class="success-message" id="successMsg" style="display:none;">Admin added successfully!</div>
                <div class="error-alert" id="errorMsg" style="display:none;"></div>
                
                <form id="adminForm" method="POST" action="admin_actions.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_admin">
                    <div class="two-columns">
                        <div class="column">
                            <div class="form-group">
                                <label for="adminName">Full Name</label>
                                <input type="text" id="adminName" name="adminName" required>
                            </div>
                            <div class="form-group">
                                <label for="adminEmail">Email Address</label>
                                <input type="email" id="adminEmail" name="adminEmail" required>
                            </div>
                            <div class="form-group">
                                <label for="adminPhone">Phone Number</label>
                                <input type="tel" id="adminPhone" name="adminPhone" required>
                            </div>
                        </div>
                        <div class="column">
                            <div class="form-group">
                                <label for="adminPassword">Password</label>
                                <input type="password" id="adminPassword" name="adminPassword" required>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="strengthBar"></div>
                                </div>
                                <div class="password-strength-text" id="strengthText">Password strength</div>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password</label>
                                <input type="password" id="confirmPassword" name="confirmPassword" required>
                            </div>
                            <div class="form-group">
                                <label for="adminImage">Profile Image</label>
                                <input type="file" id="adminImage" name="adminImage" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn">Add New Admin</button>
                </form>
            </div>
        </main>
    </section>

    <script>
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const form = document.getElementById('adminForm');
        const successMsg = document.getElementById('successMsg');
        const errorMsg = document.getElementById('errorMsg');

        document.getElementById('adminPassword').addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            const colors = ['#e74c3c', '#f39c12', '#3498db', '#2ecc71'];
            const texts = ['Weak', 'Moderate', 'Good', 'Strong'];
            const index = Math.floor(strength / 25) - 1;
            
            if (index >= 0) {
                strengthBar.style.backgroundColor = colors[index];
                strengthText.textContent = texts[index];
                strengthText.style.color = colors[index];
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';
            
            fetch('admin_actions.php', {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMsg.style.display = 'block';
                    form.reset();
                    setTimeout(() => successMsg.style.display = 'none', 3000);
                } else {
                    errorMsg.textContent = data.message;
                    errorMsg.style.display = 'block';
                }
            })
            .catch(error => {
                errorMsg.textContent = 'An error occurred. Please try again.';
                errorMsg.style.display = 'block';
            });
        });
    </script>
</body>
</html>