<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<link rel="stylesheet" href="styles.css">
	<link rel="stylesheet" href="form.css">
	<title>AdminHub</title>
</head>
<body>
    <?php
        session_start();
        if (!isset($_SESSION['adm_id'])) {
            header("Location: http://localhost/cure_booking/adminhub/login.php");
            exit();
        }

        include '../include/sidebar.php';
        include '../include/database_connection.php';
    ?>

	<section id="content">
		<?php include '../include/top-header.php'; ?>

		<main>
			<div class="container">
                <h1>Clinic Registration Form</h1>
                
                <form id="clinicForm" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="clinic_name">Clinic's Name</label>
                            <input type="text" id="clinic_name" name="clinic_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="available_timing">Available Timing</label>
                            <select id="available_timing" name="available_timing" required>
                                <option value="">Select opening hours</option>
                                <option value="08:00 AM - 06:00 PM">08:00 AM - 06:00 PM</option>
                                <option value="09:00 AM - 09:00 PM">09:00 AM - 09:00 PM</option>
                                <option value="10:00 AM - 08:00 PM">10:00 AM - 08:00 PM</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="clinic_email">Clinic's Email</label>
                            <input type="email" id="clinic_email" name="clinic_email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="tel" id="contact_number" name="contact_number" required>
                        </div>
                    </div>
                    
                   
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" required>
                        </div>
                        
                        <div class="form-group">
							<label for="clinic_pass">Clinic's Login Password</label>
							<input type="password" id="clinic_pass" name="clinic_pass" required>
						</div>
                    
						<div class="form-group">
                            <label for="profile_image">Profile Image</label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        </div>
						
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="about">About</label>
                            <textarea id="about" name="about" rows="4" placeholder="Brief description about the clinic..."></textarea>
                        </div>
                    </div>

                    <div class="btn-container">
                        <button type="submit" class="btn">Save Clinic</button>
                        <button type="button" class="btn btn-secondary" id="resetBtn">Reset Form</button>
                    </div>
                </form>
                
                <div id="message" style="display: none; padding: 10px; margin: 10px 0; border-radius: 5px;"></div>
            </div>
		</main>
	</section>
	
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const clinicForm = document.getElementById('clinicForm');
            const resetBtn = document.getElementById('resetBtn');
            const messageDiv = document.getElementById('message');
            
            // Form submission with validation
            clinicForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (validateForm()) {
                    // Using FormData to handle file uploads
                    const formData = new FormData(clinicForm);
                    formData.append('action', 'add');

                    // Show loading message
                    showMessage('Processing...', 'info');

                    // AJAX submission to api.php
                    fetch('api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            showMessage(data.message, 'success');
                            clinicForm.reset();
                        } else {
                            showMessage(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showMessage('An error occurred while saving the data.', 'error');
                        console.error('Error:', error);
                    });
                }
            });
            
            // Reset form button
            resetBtn.addEventListener('click', function() {
                clinicForm.reset();
                messageDiv.style.display = 'none';
                clearAllValidation();
            });
            
            // Form validation function
            function validateForm() {
                let isValid = true;
                const requiredFields = ['clinic_name', 'clinic_email', 'contact_number', 'location', 'clinic_pass', 'available_timing'];
                
                // Clear previous validation
                clearAllValidation();
                
                // Check required fields
                requiredFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (!input || !input.value.trim()) {
                        markInvalid(input, `${getFieldLabel(field)} is required`);
                        isValid = false;
                    } else {
                        markValid(input);
                    }
                });
                
                // Validate email
                const emailInput = document.getElementById('clinic_email');
                if (emailInput && emailInput.value.trim() && !isValidEmail(emailInput.value)) {
                    markInvalid(emailInput, 'Please enter a valid email address');
                    isValid = false;
                }
                
                // Validate contact number (basic validation)
                const contactInput = document.getElementById('contact_number');
                if (contactInput && contactInput.value.trim() && !isValidPhone(contactInput.value)) {
                    markInvalid(contactInput, 'Please enter a valid phone number');
                    isValid = false;
                }
                
                // Validate password length
                const passwordInput = document.getElementById('clinic_pass');
                if (passwordInput && passwordInput.value.length < 6) {
                    markInvalid(passwordInput, 'Password must be at least 6 characters long');
                    isValid = false;
                }
                
                // Validate file if selected
                const fileInput = document.getElementById('profile_image');
                if (fileInput && fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if (!allowedTypes.includes(file.type)) {
                        markInvalid(fileInput, 'Only JPG, JPEG, PNG, GIF and WEBP files are allowed');
                        isValid = false;
                    } else if (file.size > maxSize) {
                        markInvalid(fileInput, 'File size must be less than 5MB');
                        isValid = false;
                    } else {
                        markValid(fileInput);
                    }
                }
                
                return isValid;
            }
            
            // Helper functions for validation
            function markInvalid(input, message) {
                if (!input) return;
                
                input.style.borderColor = '#dc3545';
                
                // Create or update error message
                let errorMsg = input.parentElement.querySelector('.error-text');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'error-text';
                    errorMsg.style.cssText = 'color: #dc3545; font-size: 14px; margin-top: 5px;';
                    input.parentElement.appendChild(errorMsg);
                }
                errorMsg.textContent = message;
            }
            
            function markValid(input) {
                if (!input) return;
                
                input.style.borderColor = '#28a745';
                const errorMsg = input.parentElement.querySelector('.error-text');
                if (errorMsg) {
                    errorMsg.remove();
                }
            }
            
            function clearAllValidation() {
                const inputs = document.querySelectorAll('#clinicForm input, #clinicForm select, #clinicForm textarea');
                inputs.forEach(input => {
                    input.style.borderColor = '';
                    const errorMsg = input.parentElement.querySelector('.error-text');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                });
            }
            
            function isValidEmail(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email.toLowerCase());
            }
            
            function isValidPhone(phone) {
                // More comprehensive phone validation
                const re = /^[\+]?[1-9][\d]{0,15}$/;
                return re.test(phone.replace(/[\s\-\(\)]/g, ''));
            }
            
            function getFieldLabel(fieldId) {
                const labels = {
                    'clinic_name': 'Clinic Name',
                    'clinic_email': 'Clinic Email',
                    'contact_number': 'Contact Number',
                    'location': 'Location',
                    'clinic_pass': 'Password',
                    'available_timing': 'Available Timing'
                };
                return labels[fieldId] || fieldId;
            }
            
            // Display message function
            function showMessage(message, type) {
                messageDiv.textContent = message;
                messageDiv.className = '';
                messageDiv.style.display = 'block';
                
                // Set colors based on type
                switch(type) {
                    case 'success':
                        messageDiv.style.cssText = 'display: block; padding: 10px; margin: 10px 0; border-radius: 5px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;';
                        break;
                    case 'error':
                        messageDiv.style.cssText = 'display: block; padding: 10px; margin: 10px 0; border-radius: 5px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;';
                        break;
                    case 'info':
                        messageDiv.style.cssText = 'display: block; padding: 10px; margin: 10px 0; border-radius: 5px; background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;';
                        break;
                    default:
                        messageDiv.style.cssText = 'display: block; padding: 10px; margin: 10px 0; border-radius: 5px; background-color: #f8f9fa; color: #495057; border: 1px solid #dee2e6;';
                }
                
                // Scroll to message
                messageDiv.scrollIntoView({ behavior: 'smooth' });
                
                // Hide message after 5 seconds (except for loading messages)
                if (type !== 'info') {
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 5000);
                }
            }
            
            // Add input event listeners for real-time validation
            const emailInput = document.getElementById('clinic_email');
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    if (this.value.trim() && !isValidEmail(this.value)) {
                        markInvalid(this, 'Please enter a valid email address');
                    } else if (this.value.trim()) {
                        markValid(this);
                    } else {
                        this.style.borderColor = '';
                        const errorMsg = this.parentElement.querySelector('.error-text');
                        if (errorMsg) errorMsg.remove();
                    }
                });
            }
            
            const contactInput = document.getElementById('contact_number');
            if (contactInput) {
                contactInput.addEventListener('input', function() {
                    if (this.value.trim() && !isValidPhone(this.value)) {
                        markInvalid(this, 'Please enter a valid phone number');
                    } else if (this.value.trim()) {
                        markValid(this);
                    } else {
                        this.style.borderColor = '';
                        const errorMsg = this.parentElement.querySelector('.error-text');
                        if (errorMsg) errorMsg.remove();
                    }
                });
            }
            
            const passwordInput = document.getElementById('clinic_pass');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    if (this.value.length < 6 && this.value.length > 0) {
                        markInvalid(this, 'Password must be at least 6 characters long');
                    } else if (this.value.length >= 6) {
                        markValid(this);
                    } else {
                        this.style.borderColor = '';
                        const errorMsg = this.parentElement.querySelector('.error-text');
                        if (errorMsg) errorMsg.remove();
                    }
                });
            }
            
            // File input validation
            const fileInput = document.getElementById('profile_image');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        const file = this.files[0];
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        
                        if (!allowedTypes.includes(file.type)) {
                            markInvalid(this, 'Only JPG, JPEG, PNG, GIF and WEBP files are allowed');
                        } else if (file.size > maxSize) {
                            markInvalid(this, 'File size must be less than 5MB');
                        } else {
                            markValid(this);
                        }
                    } else {
                        this.style.borderColor = '';
                        const errorMsg = this.parentElement.querySelector('.error-text');
                        if (errorMsg) errorMsg.remove();
                    }
                });
            }
        });
    </script>

    <style>
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .form-group input:valid {
            border-color: #28a745;
        }
        
        .form-group input:invalid {
            border-color: #dc3545;
        }
        
        .error-text {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</body>
</html>