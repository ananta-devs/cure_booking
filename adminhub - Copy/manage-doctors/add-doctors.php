<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<link rel="stylesheet" href="styles.css">
	<link rel="stylesheet" href="form.css">
	<title>AdminHub</title>
    <style>
        .clinic-selection-container {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        
        .clinic-item {
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background-color: white;
        }
        
        .clinic-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .clinic-checkbox {
            margin-right: 10px;
        }
        
        .clinic-info {
            flex-grow: 1;
        }
        
        .clinic-name {
            font-weight: bold;
            color: #333;
        }
        
        .clinic-details {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        
        .clinic-availability {
            margin-top: 10px;
            display: none;
        }
        
        .clinic-availability.active {
            display: block;
        }
        
        .day-section {
            margin: 10px 0;
        }
        
        .day-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px;
        }
        
        .time-slot-item {
            display: flex;
            align-items: center;
        }
        
        .time-slot-item input[type="checkbox"] {
            margin-right: 5px;
        }
        
        .time-slot-item.disabled {
            opacity: 0.5;
        }
        
        .time-slot-item.disabled input[type="checkbox"] {
            cursor: not-allowed;
        }
        
        .unavailable-text {
            font-size: 11px;
            color: #dc3545;
            margin-left: 5px;
        }
        
        .add-clinic-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .remove-clinic-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 12px;
        }
    </style>
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
        
        // Fetch clinics for dropdown
        $clinicsQuery = "SELECT clinic_id, clinic_name, clinic_email, location, available_timing FROM clinics WHERE status = 'active' ORDER BY clinic_name";
        $clinicsStmt = $pdo->prepare($clinicsQuery);
        $clinicsStmt->execute();
        $clinics = $clinicsStmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

	<section id="content">
		<?php include '../include/top-header.php'; ?>

		<main>
			<div class="container">
                <h1>Doctor Registration Form</h1>
                
                <form id="doctorForm" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="doc_name">Doctor's Name *</label>
                            <input type="text" id="doc_name" name="doc_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="doc_specia">Specialization *</label>
                            <input type="text" id="doc_specia" name="doc_specia" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="doc_email">Doctor's Email</label>
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
							<label for="doc_pass">Doctor's Login Password *</label>
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
                    
                    <!-- Clinic Selection Section -->
                    <div class="clinic-selection-container">
                        <h3>Clinic Assignments & Availability</h3>
                        <p>Select clinics and set availability for each clinic. Time slots cannot be shared between clinics.</p>
                        
                        <div id="clinicsContainer">
                            <!-- Clinic items will be dynamically added here -->
                        </div>
                        
                        <button type="button" class="add-clinic-btn" onclick="addClinicSelection()">
                            <i class='bx bx-plus'></i> Add Clinic
                        </button>
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
        // Available clinics data from PHP
        const availableClinics = <?php echo json_encode($clinics); ?>;
        const timeSlots = ['11:00-13:00', '14:00-16:00', '17:00-19:00'];
        const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        let clinicCounter = 0;
        let selectedTimeSlots = {}; // Track selected time slots across all clinics
        
        // Initialize selected time slots structure
        weekDays.forEach(day => {
            selectedTimeSlots[day] = {};
            timeSlots.forEach(slot => {
                selectedTimeSlots[day][slot] = null; // null means available, clinic_id means taken
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const doctorForm = document.getElementById('doctorForm');
            const resetBtn = document.getElementById('resetBtn');
            const messageDiv = document.getElementById('message');
            
            // Add first clinic selection by default
            addClinicSelection();
            
            // Form submission with validation
            // doctorForm.addEventListener('submit', function(e) {
            //     e.preventDefault();
                
            //     if (validateForm()) {
            //         // Using FormData to handle file uploads
            //         const formData = new FormData(doctorForm);
            //         formData.append('action', 'add');
                    
            //         // Add clinic assignments data
            //         const clinicAssignments = collectClinicAssignments();
            //         formData.append('clinic_assignments', JSON.stringify(clinicAssignments));

            //         // AJAX submission
            //         fetch('get_doctor.php', {
            //             method: 'POST',
            //             body: formData
            //         })
            //         .then(response => response.json())
            //         .then(data => {
            //             if (data.status === 'success') {
            //                 showMessage(data.message, 'success');
            //                 doctorForm.reset();
            //                 resetClinicSelections();
            //             } else {
            //                 showMessage(data.message, 'error');
            //             }
            //         })
            //         .catch(error => {
            //             showMessage('An error occurred while saving the data.', 'error');
            //             console.error('Error:', error);
            //         });
            //     }
            // });
            // Form submission with enhanced error handling and debugging
        doctorForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                // Show loading state
                const submitBtn = document.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Saving...';
                submitBtn.disabled = true;
                
                // Using FormData to handle file uploads
                const formData = new FormData(doctorForm);
                formData.append('action', 'add');
                
                // Add clinic assignments data
                const clinicAssignments = collectClinicAssignments();
                formData.append('clinic_assignments', JSON.stringify(clinicAssignments));

                // AJAX submission with enhanced error handling
                fetch('get_doctor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    // Check content type
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        // If not JSON, get text to see what was returned
                        return response.text().then(text => {
                            console.log('Non-JSON response:', text);
                            throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
                        });
                    }
                    
                    return response.json();
                })
                .then(data => {
                    console.log('Parsed response data:', data);
                    
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    
                    if (data.status === 'success') {
                        showMessage(data.message, 'success');
                        doctorForm.reset();
                        resetClinicSelections();
                    } else {
                        showMessage(data.message || 'Unknown error occurred', 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    
                    // Reset button state
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    
                    // Show more detailed error message
                    let errorMessage = 'An error occurred while saving the data.';
                    if (error.message) {
                        errorMessage += ' Details: ' + error.message;
                    }
                    
                    showMessage(errorMessage, 'error');
                });
            }
        });
                    
            // Reset form button
            resetBtn.addEventListener('click', function() {
                doctorForm.reset();
                messageDiv.style.display = 'none';
                resetClinicSelections();
            });
            
            // Form validation function
            function validateForm() {
                let isValid = true;
                const requiredFields = ['doc_name', 'doc_specia', 'fees', 'doc_pass'];
                
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
                
                // Validate clinic assignments
                const clinicAssignments = collectClinicAssignments();
                if (clinicAssignments.length === 0) {
                    showMessage('Please select at least one clinic and availability schedule.', 'error');
                    isValid = false;
                } else {
                    let hasAvailability = false;
                    clinicAssignments.forEach(assignment => {
                        Object.values(assignment.availability).forEach(daySlots => {
                            if (Object.values(daySlots).some(slot => slot === true)) {
                                hasAvailability = true;
                            }
                        });
                    });
                    
                    if (!hasAvailability) {
                        showMessage('Please set at least one time slot for the selected clinic(s).', 'error');
                        isValid = false;
                    }
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
                    'fees': 'Consultation Fees',
                    'doc_pass': 'Password'
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
        
        function addClinicSelection() {
            clinicCounter++;
            const container = document.getElementById('clinicsContainer');
            
            // Get available clinics (not already selected)
            const selectedClinicIds = Array.from(container.querySelectorAll('.clinic-select')).map(select => select.value).filter(v => v);
            const availableOptions = availableClinics.filter(clinic => !selectedClinicIds.includes(clinic.clinic_id.toString()));
            
            if (availableOptions.length === 0) {
                alert('All available clinics have been selected.');
                return;
            }
            
            const clinicItem = document.createElement('div');
            clinicItem.className = 'clinic-item';
            clinicItem.setAttribute('data-clinic-counter', clinicCounter);
            
            let optionsHtml = '<option value="">Select a clinic</option>';
            availableOptions.forEach(clinic => {
                optionsHtml += `<option value="${clinic.clinic_id}" data-name="${clinic.clinic_name}" data-location="${clinic.location}" data-timing="${clinic.available_timing}">${clinic.clinic_name} - ${clinic.location}</option>`;
            });
            
            clinicItem.innerHTML = `
                <div class="clinic-header">
                    <select class="clinic-select" name="clinic_assignments[${clinicCounter}][clinic_id]" onchange="handleClinicSelection(this, ${clinicCounter})">
                        ${optionsHtml}
                    </select>
                    ${clinicCounter > 1 ? `<button type="button" class="remove-clinic-btn" onclick="removeClinicSelection(${clinicCounter})">Remove</button>` : ''}
                </div>
                <div class="clinic-info" id="clinicInfo_${clinicCounter}" style="display: none;">
                    <div class="clinic-name" id="clinicName_${clinicCounter}"></div>
                    <div class="clinic-details" id="clinicDetails_${clinicCounter}"></div>
                </div>
                <div class="clinic-availability" id="clinicAvailability_${clinicCounter}">
                    ${generateAvailabilityHTML(clinicCounter)}
                </div>
            `;
            
            container.appendChild(clinicItem);
        }
        
        function removeClinicSelection(counter) {
            const clinicItem = document.querySelector(`[data-clinic-counter="${counter}"]`);
            const clinicSelect = clinicItem.querySelector('.clinic-select');
            const clinicId = clinicSelect.value;
            
            if (clinicId) {
                // Free up the time slots used by this clinic
                weekDays.forEach(day => {
                    timeSlots.forEach(slot => {
                        if (selectedTimeSlots[day][slot] === clinicId) {
                            selectedTimeSlots[day][slot] = null;
                        }
                    });
                });
                
                // Update all other clinic availability displays
                updateAllClinicAvailability();
            }
            
            clinicItem.remove();
        }
        
        function handleClinicSelection(selectElement, counter) {
            const clinicId = selectElement.value;
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            
            if (clinicId) {
                // Show clinic info
                const clinicInfo = document.getElementById(`clinicInfo_${counter}`);
                const clinicName = document.getElementById(`clinicName_${counter}`);
                const clinicDetails = document.getElementById(`clinicDetails_${counter}`);
                const clinicAvailability = document.getElementById(`clinicAvailability_${counter}`);
                
                clinicName.textContent = selectedOption.getAttribute('data-name');
                clinicDetails.textContent = `Location: ${selectedOption.getAttribute('data-location')} | Available: ${selectedOption.getAttribute('data-timing')}`;
                
                clinicInfo.style.display = 'block';
                clinicAvailability.classList.add('active');
                
                // Update availability options for this clinic
                updateClinicAvailability(counter, clinicId);
                
                // Update dropdown options for other clinics
                updateClinicDropdowns();
            } else {
                // Hide clinic info and availability
                document.getElementById(`clinicInfo_${counter}`).style.display = 'none';
                document.getElementById(`clinicAvailability_${counter}`).classList.remove('active');
            }
        }
        
        function updateClinicDropdowns() {
            const allSelects = document.querySelectorAll('.clinic-select');
            const selectedIds = Array.from(allSelects).map(select => select.value).filter(v => v);
            
            allSelects.forEach(select => {
                const currentValue = select.value;
                const availableOptions = availableClinics.filter(clinic => 
                    !selectedIds.includes(clinic.clinic_id.toString()) || clinic.clinic_id.toString() === currentValue
                );
                
                // Rebuild options
                select.innerHTML = '<option value="">Select a clinic</option>';
                availableOptions.forEach(clinic => {
                    const option = document.createElement('option');
                    option.value = clinic.clinic_id;
                    option.setAttribute('data-name', clinic.clinic_name);
                    option.setAttribute('data-location', clinic.location);
                    option.setAttribute('data-timing', clinic.available_timing);
                    option.textContent = `${clinic.clinic_name} - ${clinic.location}`;
                    if (clinic.clinic_id.toString() === currentValue) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            });
        }
        
        function generateAvailabilityHTML(counter) {
            let html = '';
            
            weekDays.forEach(day => {
                html += `
                    <div class="day-section">
                        <div class="day-title">${day.charAt(0).toUpperCase() + day.slice(1)}</div>
                        <div class="time-slots-grid">
                `;
                
                timeSlots.forEach(slot => {
                    html += `
                        <div class="time-slot-item" id="slot_${counter}_${day}_${slot.replace(':', '').replace('-', '_')}">
                            <input type="checkbox" 
                                   id="clinic_${counter}_${day}_${slot}" 
                                   name="clinic_assignments[${counter}][availability][${day}][${slot}]"
                                   onchange="handleTimeSlotChange('${day}', '${slot}', ${counter}, this)">
                            <label for="clinic_${counter}_${day}_${slot}">${slot}</label>
                            <span class="unavailable-text" style="display: none;">Unavailable</span>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            });
            
            return html;
        }
        
        function updateClinicAvailability(counter, clinicId) {
            weekDays.forEach(day => {
                timeSlots.forEach(slot => {
                    const slotId = `slot_${counter}_${day}_${slot.replace(':', '').replace('-', '_')}`;
                    const slotElement = document.getElementById(slotId);
                    const checkbox = slotElement.querySelector('input[type="checkbox"]');
                    const unavailableText = slotElement.querySelector('.unavailable-text');
                    
                    const isSlotTaken = selectedTimeSlots[day][slot] && selectedTimeSlots[day][slot] !== clinicId;
                    
                    if (isSlotTaken) {
                        slotElement.classList.add('disabled');
                        checkbox.disabled = true;
                        checkbox.checked = false;
                        unavailableText.style.display = 'inline';
                    } else {
                        slotElement.classList.remove('disabled');
                        checkbox.disabled = false;
                        unavailableText.style.display = 'none';
                    }
                });
            });
        }
        
        function updateAllClinicAvailability() {
            const allSelects = document.querySelectorAll('.clinic-select');
            allSelects.forEach(select => {
                if (select.value) {
                    const counter = select.closest('.clinic-item').getAttribute('data-clinic-counter');
                    updateClinicAvailability(counter, select.value);
                }
            });
        }
        
        function handleTimeSlotChange(day, slot, counter, checkbox) {
            const clinicSelect = checkbox.closest('.clinic-item').querySelector('.clinic-select');
            const clinicId = clinicSelect.value;
            
            if (checkbox.checked) {
                selectedTimeSlots[day][slot] = clinicId;
            } else {
                selectedTimeSlots[day][slot] = null;
            }
            
            // Update availability for all other clinics
            updateAllClinicAvailability();
        }
        
        function collectClinicAssignments() {
            const assignments = [];
            const clinicItems = document.querySelectorAll('.clinic-item');
            
            clinicItems.forEach(item => {
                const select = item.querySelector('.clinic-select');
                const clinicId = select.value;
                
                if (clinicId) {
                    const counter = item.getAttribute('data-clinic-counter');
                    const availability = {};
                    
                    weekDays.forEach(day => {
                        availability[day] = {};
                        timeSlots.forEach(slot => {
                            const checkbox = item.querySelector(`input[name="clinic_assignments[${counter}][availability][${day}][${slot}]"]`);
                            availability[day][slot] = checkbox ? checkbox.checked : false;
                        });
                    });
                    
                    assignments.push({
                        clinic_id: clinicId,
                        availability: availability
                    });
                }
            });
            
            return assignments;
        }
        
        function resetClinicSelections() {
            // Reset selected time slots
            weekDays.forEach(day => {
                selectedTimeSlots[day] = {};
                timeSlots.forEach(slot => {
                    selectedTimeSlots[day][slot] = null;
                });
            });
            
            // Clear clinic container and add first clinic selection
            const container = document.getElementById('clinicsContainer');
            container.innerHTML = '';
            clinicCounter = 0;
            addClinicSelection();
        }
    </script>
</body>
</html>