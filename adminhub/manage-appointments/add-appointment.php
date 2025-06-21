<?php
    // Include database connection
    require_once '../include/database_connection.php';

    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['adm_id'])) {
        header("Location: http://localhost/adminhub/login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
	<!-- Boxicons -->
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <title>Doctor Appointment Form</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="form.css">
</head>
<body>
    <?php

		//SIDEBAR
		include '../include/sidebar.php';
		
    ?>

    <section id="content">
        <!-- NAVBAR -->
        <?php
        include '../include/top-header.php';
        ?>
        <main>
            <div class="container">
                <h1>Doctor Appointment Request Form</h1>
                    
                <form id="appointmentForm">
                    <div class="form-section">
                        <h2>Patient Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName" class="required">Name</label>
                                <input type="text" id="firstName" name="firstName" required>
                                <div class="error" id="firstNameError">Please enter your name</div>
                            </div>
                            <div class="form-group">
                                <label class="required">Gender</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="genderMale" name="gender" value="male" required>
                                        <label for="genderMale">Male</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="genderFemale" name="gender" value="female">
                                        <label for="genderFemale">Female</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="genderOther" name="gender" value="other">
                                        <label for="genderOther">Other</label>
                                    </div>
                                </div>
                                <div class="error" id="genderError">Please select your gender</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone" class="required">Phone Number</label>
                                <input type="tel" id="phone" name="phone" required>
                                <div class="error" id="phoneError">Please enter a valid phone number</div>
                            </div>
                            <div class="form-group">
                                <label for="email" class="required">Email Address</label>
                                <input type="email" id="email" name="email" required>
                                <div class="error" id="emailError">Please enter a valid email address</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>Appointment Details</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="specialityType" class="required">Speciality</label>
                                <select id="specialityType" name="specialityType" required>
                                    <option value="">Select Speciality</option>
                                </select>
                                <div class="loading" id="specialityLoading">Loading specialities...</div>
                            </div>
                            <div class="form-group">
                                <label for="doctor" class="required">Preferred Doctor</label>
                                <select id="doctor" name="doctor" required>
                                    <option value="">Select Doctor</option>
                                </select>
                                <div class="loading" id="doctorLoading">Loading doctors...</div>
                                <div class="doctor-info" id="doctorInfo"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="clinic">Select Clinic</label>
                                <select name="clinic" id="clinic">
                                <option value="">Select a clinic</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="preferredDate" class="required">Preferred Date</label>
                                <input type="date" id="preferredDate" name="preferredDate" required>
                                <div class="error" id="preferredDateError">Please select a preferred date</div>
                            </div>
                            <div class="form-group">
                                <label for="time" class="required">Time Slot</label>
                                <select id="time" name="time" required>
                                    <option value="">Select Time</option>
                                    <option value="11:00-13:00">11:00-01:00 PM</option>
                                    <option value="14:00-16:00">02:00-04:00 PM</option>
                                    <option value="17:00-19:00">05:00-07:00 PM</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    
                    <button type="submit">Book appointment</button>
                </form>
            </div>
    </main>


    </section>    

 <script>
     document.addEventListener('DOMContentLoaded', function() {
        const specialitySelect = document.getElementById('specialityType');
        const doctorSelect = document.getElementById('doctor');
        const clinicSelect = document.getElementById('clinic');
        const dateInput = document.getElementById('preferredDate');
        const timeSelect = document.getElementById('time');
        const specialityLoading = document.getElementById('specialityLoading');
        const doctorLoading = document.getElementById('doctorLoading');
        const doctorInfo = document.getElementById('doctorInfo');
        const appointmentForm = document.getElementById('appointmentForm');
        
        // Set minimum date to today
        dateInput.min = new Date().toISOString().split('T')[0];
        
        // Initialize by fetching specialities and clinics
        fetchSpecialities();
        fetchClinics();
        
        specialitySelect.addEventListener('change', function() {
            if (this.value) {
                fetchDoctors(this.value);
            } else {
                clearDoctorSelect();
                clearClinicSelect();
                resetDateInput();
            }
        });
        
        doctorSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (this.value) {
                const fees = selectedOption.getAttribute('data-fees');
                if (fees) {
                    doctorInfo.innerHTML = `Consultation Fee: ₹${fees}`;
                    doctorInfo.style.display = 'block';
                } else {
                    doctorInfo.style.display = 'none';
                }
                
                // Fetch clinics for the selected doctor
                fetchDoctorClinics(this.value);
            } else {
                doctorInfo.style.display = 'none';
                clearClinicSelect();
                resetDateInput();
            }
        });
        
        // Add event listener for clinic selection
        clinicSelect.addEventListener('change', function() {
            const doctorId = doctorSelect.value;
            const clinicId = this.value;
            
            if (doctorId && clinicId) {
                // Fetch and set available dates for this doctor-clinic combination
                fetchDoctorClinicSchedule(doctorId, clinicId);
            } else if (doctorId && !clinicId) {
                // If doctor selected but no clinic, allow all dates (or fetch all doctor's available dates)
                resetDateInput();
            } else {
                resetDateInput();
            }
            
            // Clear time selection when clinic changes
            clearTimeSelection();
        });
        
        // Add event listener for date selection to fetch available time slots
        dateInput.addEventListener('change', function() {
            const doctorId = doctorSelect.value;
            const clinicId = clinicSelect.value;
            const selectedDate = this.value;
            
            if (doctorId && selectedDate) {
                fetchAvailableTimeSlots(doctorId, clinicId, selectedDate);
            }
        });
        
        function fetchSpecialities() {
            specialityLoading.style.display = 'block';
            
            fetch('api.php?action=get_specialities')
                .then(response => response.json())
                .then(data => {
                    specialityLoading.style.display = 'none';
                    if (data.status === 'success' && data.data) {
                        populateSpecialityOptions(data.data);
                    }
                })
                .catch(error => {
                    specialityLoading.style.display = 'none';
                    console.error('Error fetching specialities:', error);
                });
        }
        
        function fetchDoctors(speciality) {
            doctorLoading.style.display = 'block';
            clearDoctorSelect();
            clearClinicSelect();
            resetDateInput();
            doctorInfo.style.display = 'none';
            
            fetch(`api.php?action=get_doctors&speciality=${encodeURIComponent(speciality)}`)
                .then(response => response.json())
                .then(data => {
                    doctorLoading.style.display = 'none';
                    if (data.status === 'success' && data.data) {
                        populateDoctorOptions(data.data);
                    }
                })
                .catch(error => {
                    doctorLoading.style.display = 'none';
                    console.error('Error fetching doctors:', error);
                });
        }
        
        function fetchClinics() {
            fetch('api.php?action=get_clinics')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        populateClinicOptions(data.data, 'all');
                    }
                })
                .catch(error => {
                    console.error('Error fetching clinics:', error);
                });
        }
        
        function fetchDoctorClinics(doctorId) {
            clearClinicSelect();
            resetDateInput();
            
            fetch(`api.php?action=get_doctor_clinics&doctor_id=${doctorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        if (data.data.length > 0) {
                            populateClinicOptions(data.data, 'doctor');
                        } else {
                            fetchClinics();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching doctor clinics:', error);
                    fetchClinics();
                });
        }
        
        function fetchDoctorClinicSchedule(doctorId, clinicId) {
            fetch(`api.php?action=get_doctor_clinic_schedule&doctor_id=${doctorId}&clinic_id=${clinicId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        setAvailableDates(data.data);
                    } else {
                        console.error('No schedule data available');
                        resetDateInput();
                    }
                })
                .catch(error => {
                    console.error('Error fetching doctor-clinic schedule:', error);
                    resetDateInput();
                });
        }
        
        function fetchAvailableTimeSlots(doctorId, clinicId, date) {
            clearTimeSelection();
            
            const url = `api.php?action=get_available_slots&doctor_id=${doctorId}&date=${date}${clinicId ? '&clinic_id=' + clinicId : ''}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        populateTimeSlots(data.data);
                    } else {
                        // No available slots
                        timeSelect.innerHTML = '<option value="">No available time slots</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching available time slots:', error);
                    timeSelect.innerHTML = '<option value="">Error loading time slots</option>';
                });
        }
        
        function setAvailableDates(scheduleData) {
            // Reset date input
            dateInput.removeAttribute('disabled');
            dateInput.value = '';
            
            // Remove any existing date restriction
            if (dateInput.removeEventListener) {
                dateInput.removeEventListener('input', restrictDateSelection);
            }
            
            // Parse schedule data and determine available days
            const availableDays = [];
            const dayMapping = {
                'sunday': 0, 'monday': 1, 'tuesday': 2, 'wednesday': 3,
                'thursday': 4, 'friday': 5, 'saturday': 6
            };
            
            if (scheduleData.availability_schedule) {
                const schedule = JSON.parse(scheduleData.availability_schedule);
                
                Object.keys(schedule).forEach(day => {
                    const daySchedule = schedule[day];
                    // Check if any time slot is available for this day
                    const hasAvailableSlot = Object.values(daySchedule).some(slot => slot === true);
                    if (hasAvailableSlot && dayMapping.hasOwnProperty(day)) {
                        availableDays.push(dayMapping[day]);
                    }
                });
            }
            
            if (availableDays.length === 0) {
                // No available days - disable date input
                dateInput.disabled = true;
                dateInput.placeholder = 'No available dates for this clinic';
                return;
            }
            
            // Add event listener to restrict date selection
            dateInput.addEventListener('input', function(e) {
                restrictDateSelection(e, availableDays);
            });
            
            // Also add change event for better compatibility
            dateInput.addEventListener('change', function(e) {
                restrictDateSelection(e, availableDays);
            });
            
            // Set a custom validation message
            dateInput.title = `Available days: ${availableDays.map(day => 
                ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][day]
            ).join(', ')}`;
        }
        
        function restrictDateSelection(event, availableDays) {
            const selectedDate = event.target.value;
            if (selectedDate) {
                const date = new Date(selectedDate);
                const dayOfWeek = date.getDay();
                
                if (!availableDays.includes(dayOfWeek)) {
                    // Invalid day selected
                    event.target.setCustomValidity('Doctor is not available on this day at the selected clinic. Available days: ' + 
                        availableDays.map(day => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][day]).join(', '));
                    event.target.value = ''; // Clear the invalid selection
                    clearTimeSelection();
                } else {
                    event.target.setCustomValidity('');
                }
            }
        }
        
        function resetDateInput() {
            dateInput.removeAttribute('disabled');
            dateInput.value = '';
            dateInput.placeholder = '';
            dateInput.title = '';
            dateInput.setCustomValidity('');
            clearTimeSelection();
            
            // Remove event listeners
            dateInput.removeEventListener('input', restrictDateSelection);
            dateInput.removeEventListener('change', restrictDateSelection);
            
            // Reset to minimum date only
            dateInput.min = new Date().toISOString().split('T')[0];
        }
        
        function clearTimeSelection() {
            timeSelect.innerHTML = '<option value="">Select Time</option>';
        }
        
        function populateTimeSlots(slots) {
            timeSelect.innerHTML = '<option value="">Select Time</option>';
            
            if (slots.length === 0) {
                const option = document.createElement('option');
                option.disabled = true;
                option.textContent = 'No available time slots';
                timeSelect.appendChild(option);
            } else {
                slots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.value;
                    option.textContent = slot.label;
                    timeSelect.appendChild(option);
                });
            }
        }
        
        function populateSpecialityOptions(specialities) {
            specialitySelect.innerHTML = '<option value="">Select Speciality</option>';
            specialities.forEach(item => {
                const option = document.createElement('option');
                option.value = item.doc_specia;
                option.textContent = item.doc_specia;
                specialitySelect.appendChild(option);
            });
        }
        
        function populateDoctorOptions(doctors) {
            doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
            
            if (doctors.length === 0) {
                const option = document.createElement('option');
                option.disabled = true;
                option.textContent = 'No doctors available for this speciality';
                doctorSelect.appendChild(option);
            } else {
                doctors.forEach(doctor => {
                    const option = document.createElement('option');
                    option.value = doctor.id;
                    option.textContent = doctor.doc_name;
                    option.setAttribute('data-fees', doctor.fees);
                    doctorSelect.appendChild(option);
                });
            }
        }
        
        function populateClinicOptions(clinics, type = 'all') {
            clinicSelect.innerHTML = '<option value="">Select a clinic</option>';
            
            if (clinics.length === 0) {
                if (type === 'doctor') {
                    const option = document.createElement('option');
                    option.disabled = true;
                    option.textContent = 'No clinics assigned to this doctor';
                    clinicSelect.appendChild(option);
                }
            } else {
                clinics.forEach(clinic => {
                    const option = document.createElement('option');
                    option.value = clinic.clinic_id;
                    option.textContent = `${clinic.clinic_name} - ${clinic.location}`;
                    clinicSelect.appendChild(option);
                });
            }
        }
        
        function clearDoctorSelect() {
            doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
            doctorInfo.style.display = 'none';
        }
        
        function clearClinicSelect() {
            clinicSelect.innerHTML = '<option value="">Select a clinic</option>';
        }
        
        // Form submission logic remains the same
        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let isValid = true;
            const requiredFields = ['firstName', 'gender', 'phone', 'email', 'specialityType', 'doctor', 'preferredDate', 'time'];
            
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    if (field === 'gender') {
                        const checked = document.querySelector(`input[name=${field}]:checked`);
                        if (!checked) {
                            isValid = false;
                            document.getElementById('genderError').style.display = 'block';
                        } else {
                            document.getElementById('genderError').style.display = 'none';
                        }
                    } else {
                        if (!element.value.trim()) {
                            isValid = false;
                            element.classList.add('invalid');
                            const errorElement = document.getElementById(field + 'Error');
                            if (errorElement) errorElement.style.display = 'block';
                        } else {
                            element.classList.remove('invalid');
                            const errorElement = document.getElementById(field + 'Error');
                            if (errorElement) errorElement.style.display = 'none';
                        }
                    }
                }
            });
            
            const emailInput = document.getElementById('email');
            if (emailInput && emailInput.value) {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                    isValid = false;
                    emailInput.classList.add('invalid');
                    document.getElementById('emailError').style.display = 'block';
                }
            }
            
            if (isValid) {
                const formData = new FormData(this);
                formData.append('action', 'save_appointment');
                
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.textContent = 'Processing...';
                
                fetch('api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                    
                    if (data.status === 'success') {
                        const successMessage = document.createElement('div');
                        successMessage.className = 'confirmation';
                        successMessage.textContent = data.message;
                        successMessage.style.display = 'block';
                        
                        this.appendChild(successMessage);
                        this.reset();
                        clearDoctorSelect();
                        clearClinicSelect();
                        resetDateInput();
                        doctorInfo.style.display = 'none';
                        
                        setTimeout(() => {
                            successMessage.scrollIntoView({ behavior: 'smooth' });
                            setTimeout(() => successMessage.remove(), 5000);
                        }, 100);
                    } else {
                        alert(data.message || 'An error occurred while booking the appointment.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                    alert('An error occurred. Please try again later.');
                });
            }
        });
    });
    </script>
</body>
</html>