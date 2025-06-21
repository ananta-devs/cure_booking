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
    <!-- <link rel="stylesheet" href="form.css"> -->
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
            --text-color: #333;
            --light-bg: #f9f9f9;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f5f7fa;
            /* padding: 20px; */
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .form-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section h2 {
            color: var(--secondary-color);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
            gap: 20px;
        }
        
        .form-group {
            flex: 1 1 300px;
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .radio-group {
            display: flex;
            gap: 15px;
            margin-top: 5px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
        }
        
        .radio-option input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }
        
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }
        
        button:hover {
            background-color: var(--secondary-color);
        }
        
        .error {
            color: var(--error-color);
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        input.invalid, select.invalid {
            border-color: var(--error-color);
        }
        
        .confirmation {
            display: none;
            text-align: center;
            padding: 20px;
            background-color: var(--success-color);
            color: white;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .required::after {
            content: "*";
            color: var(--error-color);
            margin-left: 3px;
        }
        
        .loading {
            text-align: center;
            color: var(--secondary-color);
            font-style: italic;
            margin: 5px 0;
            display: none;
        }
        
        .doctor-info {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: var(--light-bg);
            border-radius: 5px;
            font-size: 14px;
        }
        
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            .form-group {
                flex: 1 1 100%;
            }
            .container {
                padding: 20px 15px;
            }
        }
    </style>
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
            const specialityLoading = document.getElementById('specialityLoading');
            const doctorLoading = document.getElementById('doctorLoading');
            const doctorInfo = document.getElementById('doctorInfo');
            const appointmentForm = document.getElementById('appointmentForm');
            
            document.getElementById('preferredDate').min = new Date().toISOString().split('T')[0];
            
            fetchSpecialities();
            
            specialitySelect.addEventListener('change', function() {
                if (this.value) {
                    fetchDoctors(this.value);
                } else {
                    clearDoctorSelect();
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
                } else {
                    doctorInfo.style.display = 'none';
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
            
            function clearDoctorSelect() {
                doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
                doctorInfo.style.display = 'none';
            }
            
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