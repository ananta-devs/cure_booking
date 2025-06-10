
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
            header("Location: http://localhost/adminhub/login.php");
            exit();
        }

        include '../include/sidebar.php';
        include '../include/database_connection.php';
    ?>

	<section id="content">
		<?php include '../include/top-header.php'; ?>

		<main>
			<div class="container">
                <h1>Doctor Registration Form</h1>
                
                <form id="doctorForm" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="doc_name">Doctor Name *</label>
                            <input type="text" id="doc_name" name="doc_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="doc_specia">Specialization *</label>
                            <input type="text" id="doc_specia" name="doc_specia" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="doc_email">Email</label>
                            <input type="email" id="doc_email" name="doc_email">
                        </div>
                        
                        <div class="form-group">
                            <label for="fees">Consultation Fees *</label>
                            <input type="number" id="fees" name="fees" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select a gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="experience">Experience (Years)</label>
                            <input type="number" id="experience" name="experience">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location">
                        </div>
                        
                        <div class="form-group">
                            <label for="education">Education</label>
                            <input type="text" id="education" name="education">
                        </div>
                    </div>
                    
                    <div class="form-row">
						<div class="form-group">
							<label for="doc_pass">Doctos's Login Password *</label>
							<input type="text" id="doc_pass" name="doc_pass" required>
						</div>
						<div class="form-group">
                            <label for="doc_img">Profile Image</label>
                            <input type="file" id="doc_img" name="doc_img" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="bio">Biography</label>
                            <textarea id="bio" name="bio"></textarea>
                        </div>
                    </div>
                    
                    <div class="availability-container">
                        <div class="availability-title"><h3>Availability Schedule</h3></div>
                        <div class="availability-grid">
                            <div class="day-row">
                                <div class="day-label">Monday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="monday_11:00-13:00" name="availability[monday][11:00-13:00]">
                                        <label for="monday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="monday_14:00-16:00" name="availability[monday][14:00-16:00]">
                                        <label for="monday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="monday_17:00-19:00" name="availability[monday][17:00-19:00]">
                                        <label for="monday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="day-row">
                                <div class="day-label">Tuesday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="tuesday_11:00-13:00" name="availability[tuesday][11:00-13:00]">
                                        <label for="tuesday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="tuesday_14:00-16:00" name="availability[tuesday][14:00-16:00]">
                                        <label for="tuesday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="tuesday_17:00-19:00" name="availability[tuesday][17:00-19:00]">
                                        <label for="tuesday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="day-row">
                                <div class="day-label">Wednesday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="wednesday_11:00-13:00" name="availability[wednesday][11:00-13:00]">
                                        <label for="wednesday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="wednesday_14:00-16:00" name="availability[wednesday][14:00-16:00]">
                                        <label for="wednesday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="wednesday_17:00-19:00" name="availability[wednesday][17:00-19:00]">
                                        <label for="wednesday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="day-row">
                                <div class="day-label">Thursday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="thursday_11:00-13:00" name="availability[thursday][11:00-13:00]">
                                        <label for="thursday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="thursday_14:00-16:00" name="availability[thursday][14:00-16:00]">
                                        <label for="thursday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="thursday_17:00-19:00" name="availability[thursday][17:00-19:00]">
                                        <label for="thursday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="day-row">
                                <div class="day-label">Friday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="friday_11:00-13:00" name="availability[friday][11:00-13:00]">
                                        <label for="friday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="friday_14:00-16:00" name="availability[friday][14:00-16:00]">
                                        <label for="friday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="friday_17:00-19:00" name="availability[friday][17:00-19:00]">
                                        <label for="friday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="day-row">
                                <div class="day-label">Saturday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="saturday_11:00-13:00" name="availability[saturday][11:00-13:00]">
                                        <label for="saturday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="saturday_14:00-16:00" name="availability[saturday][14:00-16:00]">
                                        <label for="saturday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="saturday_17:00-19:00" name="availability[saturday][17:00-19:00]">
                                        <label for="saturday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="day-row">
                                <div class="day-label">Sunday</div>
                                <div class="time-slots">
                                    <div class="time-slot">
                                        <input type="checkbox" id="sunday_11:00-13:00" name="availability[sunday][11:00-13:00]">
                                        <label for="sunday_11:00-13:00">11:00-13:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="sunday_14:00-16:00" name="availability[sunday][14:00-16:00]">
                                        <label for="sunday_14:00-16:00">14:00-16:00</label>
                                    </div>
                                    <div class="time-slot">
                                        <input type="checkbox" id="sunday_17:00-19:00" name="availability[sunday][17:00-19:00]">
                                        <label for="sunday_17:00-19:00">17:00-19:00</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="btn-container">
                        <button type="submit" class="btn">Save Doctor</button>
                        <button type="button" class="btn btn-secondary" id="resetBtn">Reset Form</button>
                    </div>
                </form>
                
                <div id="message"></div>
            </div>
		</main>
	</section>
	
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const doctorForm = document.getElementById('doctorForm');
            const resetBtn = document.getElementById('resetBtn');
            const messageDiv = document.getElementById('message');
            
            // Form submission with validation
            doctorForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (validateForm()) {
                    // Using FormData to handle file uploads
                    const formData = new FormData(doctorForm);
                    formData.append('action', 'add');

                    // AJAX submission
                    fetch('get_doctor.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showMessage(data.message, 'success');
                            doctorForm.reset();
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
                doctorForm.reset();
                messageDiv.style.display = 'none';
            });
            
            // Form validation function
            function validateForm() {
                let isValid = true;
                const requiredFields = ['doc_name', 'doc_specia', 'fees'];
                
                // Check required fields
                requiredFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (!input.value.trim()) {
                        markInvalid(input, `${getFieldLabel(field)} is required`);
                        isValid = false;
                    } else {
                        markValid(input);
                    }
                });
                
                // Validate email if provided
                const emailInput = document.getElementById('doc_email');
                if (emailInput.value.trim() && !isValidEmail(emailInput.value)) {
                    markInvalid(emailInput, 'Please enter a valid email address');
                    isValid = false;
                }
                
                // Validate fees (positive number)
                const feesInput = document.getElementById('fees');
                if (parseFloat(feesInput.value) < 0) {
                    markInvalid(feesInput, 'Fees cannot be negative');
                    isValid = false;
                }
                
                return isValid;
            }
            
            // Helper functions for validation
            function markInvalid(input, message) {
                input.style.borderColor = '#dc3545';
                
                // Create or update error message
                let errorMsg = input.parentElement.querySelector('.error-text');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'error-text';
                    errorMsg.style.color = '#dc3545';
                    errorMsg.style.fontSize = '14px';
                    errorMsg.style.marginTop = '5px';
                    input.parentElement.appendChild(errorMsg);
                }
                errorMsg.textContent = message;
            }
            
            function markValid(input) {
                input.style.borderColor = '#ddd';
                const errorMsg = input.parentElement.querySelector('.error-text');
                if (errorMsg) {
                    errorMsg.remove();
                }
            }
            
            function isValidEmail(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email.toLowerCase());
            }
            
            function getFieldLabel(fieldId) {
                const labels = {
                    'doc_name': 'Doctor Name',
                    'doc_specia': 'Specialization',
                    'fees': 'Consultation Fees'
                };
                return labels[fieldId] || fieldId;
            }
            
            // Display message function
            function showMessage(message, type) {
                messageDiv.textContent = message;
                messageDiv.className = type;
                messageDiv.style.display = 'block';
                
                // Scroll to message
                messageDiv.scrollIntoView({ behavior: 'smooth' });
                
                // Hide message after 5 seconds
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
            
            // Add input event listeners for real-time validation
            document.getElementById('doc_email').addEventListener('input', function() {
                if (this.value.trim() && !isValidEmail(this.value)) {
                    markInvalid(this, 'Please enter a valid email address');
                } else {
                    markValid(this);
                }
            });
            
            document.getElementById('fees').addEventListener('input', function() {
                if (parseFloat(this.value) < 0) {
                    markInvalid(this, 'Fees cannot be negative');
                } else {
                    markValid(this);
                }
            });
        });
    </script>
</body>
</html>