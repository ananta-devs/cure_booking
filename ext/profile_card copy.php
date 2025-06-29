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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }

        .return {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .return a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }

        .return i { margin-right: 8px; }

        .profile-header {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .profile-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .joined-date {
            color: #6b7280;
            font-size: 14px;
        }

        .edit-name-btn, .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .edit-name-btn {
            background: #f3f4f6;
            color: #6b7280;
        }

        .edit-name-btn:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .action-btn {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn:hover { background: #fecaca; }

        .contact-item {
            display: flex;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }

        .contact-item:hover { background: #f9fafb; }
        .contact-item:last-child { border-bottom: none; }

        .contact-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .phone-icon { background: #fef3c7; }
        .email-icon { background: #dbeafe; }
        .password-icon { background: #e0e7ff; }

        .contact-info {
            flex: 1;
        }

        .contact-label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .contact-value {
            color: #1f2937;
            font-size: 16px;
            font-weight: 500;
        }

        .contact-value.placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            padding: 24px;
            width: 90%;
            max-width: 400px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-secondary, .btn-primary {
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .btn-secondary {
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
        }

        .btn-primary {
            border: none;
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover { background: #2563eb; }
        .btn-secondary:hover { background: #f9fafb; }

        .icon {
            width: 20px;
            height: 20px;
        }

        .phone-svg {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23d97706'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'/%3E%3C/svg%3E") no-repeat center/contain;
        }

        .email-svg {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%232563eb'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'/%3E%3C/svg%3E") no-repeat center/contain;
        }

        .password-svg {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234f46e5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'/%3E%3C/svg%3E") no-repeat center/contain;
        }

        .message {
            margin: 16px 24px;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .profile-card { max-width: 100%; }
            .profile-header, .contact-item { padding: 16px 20px; }
            .message { margin: 12px 20px; }
        }
            <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }

        .profile-header {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .profile-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .joined-date {
            color: #6b7280;
            font-size: 14px;
        }

        .edit-name-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: #f3f4f6;
            color: #6b7280;
            margin-top: 4px;
        }

        .edit-name-btn:hover {
            background-color: #e5e7eb;
            color: #374151;
        }

        .contact-item {
            display: flex;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s ease;
        }

        .contact-item:hover {
            background-color: #f9fafb;
        }

        .contact-item:last-child {
            border-bottom: none;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .phone-icon { background-color: #fef3c7; }
        .email-icon { background-color: #dbeafe; }
        .password-icon { background-color: #e0e7ff; }

        .contact-info {
            flex: 1;
        }

        .contact-label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .contact-value {
            color: #1f2937;
            font-size: 16px;
            font-weight: 500;
        }

        .contact-value.placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: #fee2e2;
            color: #dc2626;
        }

        .action-btn:hover {
            background-color: #fecaca;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            padding: 24px;
            width: 90%;
            max-width: 400px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-secondary {
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            padding: 8px 16px;
            border: none;
            background: #3b82f6;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary:hover { background: #2563eb; }
        .btn-secondary:hover { background: #f9fafb; }

        .icon {
            width: 20px;
            height: 20px;
        }

        .phone-svg {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23d97706'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'/%3E%3C/svg%3E") no-repeat center;
            background-size: contain;
        }

        .email-svg {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%232563eb'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'/%3E%3C/svg%3E") no-repeat center;
            background-size: contain;
        }

        .password-svg {
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234f46e5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'/%3E%3C/svg%3E") no-repeat center;
            background-size: contain;
        }

        .message {
            margin: 16px 24px;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
        }

        .success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        @media (min-width: 768px) {
            .profile-card { max-width: 650px; }
            .profile-header, .contact-item { padding: 24px 32px; }
            .message { margin: 20px 32px; }
        }

        @media (min-width: 1024px) {
            .profile-card { max-width: 800px; }
            .profile-header { padding: 40px; }
            .profile-header h1 { font-size: 28px; }
            .contact-item { padding: 28px 40px; }
            .contact-icon { width: 48px; height: 48px; margin-right: 20px; }
            .contact-label { font-size: 15px; }
            .contact-value { font-size: 17px; }
            .message { margin: 24px 40px; padding: 16px; }
        }

        @media (min-width: 1280px) {
            .profile-card { max-width: 900px; }
            .profile-header { padding: 48px; }
            .profile-header h1 { font-size: 32px; }
            .contact-item { padding: 32px 48px; }
            .contact-icon { width: 52px; height: 52px; margin-right: 24px; }
            .contact-label { font-size: 16px; }
            .contact-value { font-size: 18px; }
            .action-btn { padding: 10px 20px; font-size: 15px; }
            .edit-name-btn { padding: 8px 16px; font-size: 14px; }
            .message { margin: 28px 48px; padding: 18px; font-size: 15px; }
        }

        @media (min-width: 1536px) {
            .profile-card { max-width: 1000px; }
        }
    </style>
    </style>
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

        function editField(field) {
            currentField = field;
            const modal = document.getElementById('modal');
            const title = document.getElementById('modal-title');
            const form = document.getElementById('modal-form');
            
            const forms = {
                name: () => `
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="field" value="name">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="value" class="form-input" value="${profileData.name}" required>
                    </div>`,
                mobile: () => `
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="field" value="mobile">
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" name="value" class="form-input" value="${profileData.mobile || ''}">
                    </div>`,
                email: () => `
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="field" value="email">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="value" class="form-input" value="${profileData.email}" required>
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

        function saveField() {
            const form = document.getElementById('modal-form');
            
            if (currentField === 'password') {
                const newPass = document.getElementById('new-password').value;
                const confirm = document.getElementById('confirm-password').value;
                
                if (newPass !== confirm) {
                    alert('New passwords do not match');
                    return;
                }
            }
            
            form.submit();
        }

        // Event listeners
        window.onclick = e => e.target.classList.contains('modal') && closeModal();
        document.addEventListener('keydown', e => {
            if (e.key === 'Enter' && document.getElementById('modal').style.display === 'block') {
                saveField();
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