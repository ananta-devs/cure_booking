<?php
    session_start();

    // Check authentication
    if (!$_SESSION['logged_in'] ?? false) {
        header("Location: http://localhost/cure_booking/user/login.php");
        exit();
    }

    // Get and clear messages
    $message = $_SESSION['success'] ?? '';
    $error = $_SESSION['error'] ?? '';
    unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Card</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="profile_card.css">

</head>
<body>
    <div class="profile-card">
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="return">
            <a href="#" onclick="window.location.replace('../index.php'); return false;">
                <i class="fa-solid fa-arrow-left"></i>Back to home
            </a>
        </div>

        <div class="profile-header">
            <div class="profile-info">
                <h1 id="profile-name">Loading...</h1>
                <div class="joined-date" id="join-date">Loading...</div>
            </div>
            <button class="edit-name-btn" onclick="editField('name')">Edit Name</button>
        </div>

        <div class="contact-item">
            <div class="contact-icon phone-icon">
                <div class="icon phone-svg"></div>
            </div>
            <div class="contact-info">
                <div class="contact-label">Mobile Number</div>
                <div class="contact-value" id="mobile-value">Loading...</div>
            </div>
            <button class="action-btn" onclick="editField('mobile')">Edit</button>
        </div>

        <div class="contact-item">
            <div class="contact-icon email-icon">
                <div class="icon email-svg"></div>
            </div>
            <div class="contact-info">
                <div class="contact-label">Primary Email address</div>
                <div class="contact-value" id="email-value">Loading...</div>
            </div>
        </div>

        <div class="contact-item">
            <div class="contact-icon password-icon">
                <div class="icon password-svg"></div>
            </div>
            <div class="contact-info">
                <div class="contact-label">Password</div>
                <div class="contact-value placeholder">Change password</div>
            </div>
            <button class="action-btn" onclick="editField('password')">Change</button>
        </div>
    </div>

    <div id="modal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title" id="modal-title">Edit</h3>
            <form id="modal-form" method="POST" action="api.php"></form>
            <div class="modal-actions">
                <button class="btn-secondary" type="button" onclick="closeModal()">Cancel</button>
                <button class="btn-primary" type="button" onclick="saveField()">Save</button>
            </div>
        </div>
    </div>

    <script>
        let profileData = {};
        let currentField = '';

        async function fetchProfileData() {
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_profile'
                });

                const data = await response.json();
                if (data.success) {
                    profileData = data.user;
                    updateProfileDisplay();
                } else {
                    showError('Failed to load profile data');
                }
            } catch (error) {
                showError('Error loading profile data');
            }
        }

        function updateProfileDisplay() {
            document.getElementById('profile-name').textContent = `Hi ${profileData.name}!`;
            
            const joinDate = new Date(profileData.created_at);
            document.getElementById('join-date').textContent = 
                `Joined in ${joinDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}`;
            
            const mobileEl = document.getElementById('mobile-value');
            mobileEl.textContent = profileData.mobile || 'Not provided';
            mobileEl.className = profileData.mobile ? 'contact-value' : 'contact-value placeholder';
            
            document.getElementById('email-value').textContent = profileData.email;
        }

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'message error';
            errorDiv.textContent = message;
            
            document.querySelector('.profile-card').insertBefore(errorDiv, document.querySelector('.return'));
            setTimeout(() => errorDiv.remove(), 5000);
        }

        function showSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'message success';
            successDiv.textContent = message;
            
            document.querySelector('.profile-card').insertBefore(successDiv, document.querySelector('.return'));
            setTimeout(() => successDiv.remove(), 5000);
        }

        function editField(field) {
            currentField = field;
            const modal = document.getElementById('modal');
            const title = document.getElementById('modal-title');
            const form = document.getElementById('modal-form');
            
            const forms = {
                name: () => `
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-input" value="${profileData.name}" required>
                        <input type="hidden" name="mobile" value="${profileData.mobile || ''}">
                    </div>`,
                mobile: () => `
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" name="mobile" class="form-input" value="${profileData.mobile || ''}">
                        <input type="hidden" name="name" value="${profileData.name}">
                    </div>`,
                email: () => `
                    <input type="hidden" name="action" value="update_email">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" value="${profileData.email}" required>
                    </div>`,
                password: () => `
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" id="new-password" class="form-input" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm-password" class="form-input" required minlength="6">
                    </div>`
            };

            const titles = {
                name: 'Edit Name',
                mobile: 'Edit Mobile Number',
                email: 'Edit Primary Email',
                password: 'Change Password'
            };

            title.textContent = titles[field];
            form.innerHTML = forms[field]();
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        async function saveField() {
            const form = document.getElementById('modal-form');
            
            if (currentField === 'password') {
                const newPass = document.getElementById('new-password').value;
                const confirm = document.getElementById('confirm-password').value;
                
                if (newPass !== confirm) {
                    alert('New passwords do not match');
                    return;
                }
            }
            
            try {
                const formData = new FormData(form);
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(data.message);
                    closeModal();
                    
                    // Update profile data and display
                    if (currentField === 'name' || currentField === 'mobile') {
                        // Refresh profile data after successful update
                        await fetchProfileData();
                    }
                } else {
                    showError(data.message || 'An error occurred');
                }
            } catch (error) {
                showError('Network error occurred');
                console.error('Error:', error);
            }
        }

        // Event listeners
        window.onclick = e => e.target.classList.contains('modal') && closeModal();
        document.addEventListener('keydown', e => {
            if (e.key === 'Enter' && document.getElementById('modal').style.display === 'block') {
                saveField();
            }
            if (e.key === 'Escape' && document.getElementById('modal').style.display === 'block') {
                closeModal();
            }
        });

        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => msg.style.display = 'none');
        }, 5000);

        // Initialize
        document.addEventListener('DOMContentLoaded', fetchProfileData);
    </script>
</body>
</html>