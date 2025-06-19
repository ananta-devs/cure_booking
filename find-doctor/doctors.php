<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Find Your Doctor</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <style>
        .doctor-clinics-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .doctor-clinics-section h3 {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .clinics-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .clinic-item {
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-radius: 6px;
            border-left: 3px solid #007bff;
        }
        .clinic-name {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        .clinic-name i {
            color: #007bff;
            font-size: 0.9rem;
        }
        .clinic-name strong {
            color: #2c3e50;
            font-size: 0.95rem;
        }
        .clinic-location {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 20px;
        }
        .clinic-location i {
            color: #6c757d;
            font-size: 0.8rem;
        }
        .clinic-location span {
            color: #6c757d;
            font-size: 0.85rem;
        }
        .clinic-availability {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 20px;
            margin-top: 5px;
        }
        .clinic-availability i {
            color: #28a745;
            font-size: 0.8rem;
        }
        .clinic-availability span {
            color: #495057;
            font-size: 0.85rem;
        }
        .clinic-availability strong {
            color: #2c3e50;
            font-weight: 600;
        }
        .doctor-clinics-section .clinic-item:only-child .clinic-availability {
            margin-left: 0;
            justify-content: center;
            background-color: #e8f5e8;
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #28a745;
        }
        @media (max-width: 768px) {
            .clinic-availability {
                margin-left: 15px;
                margin-top: 8px;
            }
            .clinic-availability span {
                font-size: 0.8rem;
            }
            .doctor-clinics-section .clinic-item:only-child .clinic-availability {
                margin-left: 0;
                padding: 8px;
            }
            .clinic-item {
                padding: 10px 12px;
            }
            .clinic-name strong {
                font-size: 0.9rem;
            }
            .clinic-location {
                margin-left: 15px;
            }
            .clinic-location span {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php 
        session_start();
        $isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);
        include '../include/header.php'; 
        include '../styles.php'; 
    ?>

    <script>
        // Pass login status to JavaScript
        const USER_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const LOGIN_URL = '../user/login.php'; // Adjust path as needed;
    </script>

    <section class="hero">
        <div class="container">
            <h1>Find the Right Doctor for Your Needs</h1>
            <p>Connect with top healthcare specialists in your area</p>
            <form class="search-container">
                <select id="search-bar">
                    <option value="">All Specializations</option>
                    <?php
                        $conn = new mysqli("localhost", "root", "", "cure_booking");
                        if (!$conn->connect_error) {
                            $result = $conn->query("SELECT DISTINCT doc_specia FROM doctor ORDER BY doc_specia");
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['doc_specia']) . '">' . htmlspecialchars($row['doc_specia']) . '</option>';
                                }
                            }
                            $conn->close();
                        }
                    ?>
                </select>
                <button type="submit" aria-label="Search"><i class="ri-search-line" id="search-icon"></i></button>
            </form>
        </div>
    </section>

    <section class="doctors-section">
        <h2>Available Doctors</h2>
        <div id="doctors-container" class="doctors-container"></div>
    </section>

    <div id="doctor-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="modal-doctor-details"></div>
        </div>
    </div>

    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="modalTestInfo"></div>
            <form id="bookingForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="clinic">Select Clinic</label>
                    <select name="clinic" id="clinic">
                        <option value="">Select a clinic</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="time">Time Slot</label>
                        <select id="time" name="time" required>
                            <option value="">Select Time</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn primary-btn">Confirm Booking</button>
            </form>
        </div>
    </div>

    <script>
        const elements = {
            doctorsContainer: document.getElementById('doctors-container'),
            searchInput: document.getElementById('search-bar'),
            modal: document.getElementById('doctor-modal'),
            closeModal: document.querySelector('.close-modal'),
            modalDoctorDetails: document.getElementById('modal-doctor-details'),
            bookingModal: document.getElementById('bookingModal'),
            bookingForm: document.getElementById('bookingForm'),
            modalTestInfo: document.getElementById('modalTestInfo'),
            closeBookingModal: document.querySelector('#bookingModal .close'),
            searchForm: document.querySelector('.search-container')
        };

        let currentDoctors = [];
        let selectedDoctorForBooking = null;

        document.addEventListener('DOMContentLoaded', () => {
            fetchDoctors();
            setupEventListeners();
        });

        function setupEventListeners() {
            elements.searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                filterDoctors();
            });
            
            elements.closeModal.addEventListener('click', closeMainModal);
            elements.closeBookingModal.addEventListener('click', closeBookingModalHandler);
            
            window.addEventListener('click', (e) => {
                if (e.target === elements.modal) closeMainModal();
                if (e.target === elements.bookingModal) closeBookingModalHandler();
            });

            elements.bookingForm.addEventListener('submit', handleBookingSubmit);
            document.querySelector('#clinic').addEventListener('change', loadTimeSlots);
            document.querySelector('#date').addEventListener('change', loadTimeSlots);
        }

        function closeMainModal() {
            elements.modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function closeBookingModalHandler() {
            elements.bookingModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Fixed login check function
        function checkLoginStatus() {
            return USER_LOGGED_IN;
        }

        async function fetchDoctors(filters = {}) {
            elements.doctorsContainer.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading doctors...</div>';
            
            const queryParams = new URLSearchParams({action: 'get_doctors'});
            if (filters.specialization) queryParams.append('specialization', filters.specialization);
            
            try {
                const response = await fetch(`api.php?${queryParams.toString()}`);
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                
                const responseText = await response.text();
                let doctors;
                
                try {
                    doctors = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Parse error:', parseError);
                    elements.doctorsContainer.innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Invalid response format from server.</p>
                        </div>
                    `;
                    return;
                }
                
                if (doctors.success === false || doctors.error) {
                    throw new Error(doctors.message || doctors.error || 'Unknown error');
                }
                
                currentDoctors = doctors;
                displayDoctors(doctors);
            } catch (error) {
                console.error('Fetch error:', error);
                elements.doctorsContainer.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Failed to load doctors. Please try again later.</p>
                        <small>Error: ${error.message}</small>
                    </div>
                `;
            }
        }

        function filterDoctors() {
            const specialization = elements.searchInput.value;
            fetchDoctors({ specialization });
        }

        function displayDoctors(doctorsToDisplay) {
            elements.doctorsContainer.innerHTML = '';
            
            if (!doctorsToDisplay || doctorsToDisplay.length === 0) {
                elements.doctorsContainer.innerHTML = '<div class="error-message"><i class="fas fa-user-md"></i><p>No doctors found matching your criteria.</p></div>';
                return;
            }
            
            doctorsToDisplay.forEach(doctor => {
                const doctorCard = createDoctorCard(doctor);
                elements.doctorsContainer.appendChild(doctorCard);
            });
        }

        function createDoctorCard(doctor) {
            const card = document.createElement('div');
            card.className = 'doctor-card';
            
            card.innerHTML = `
                <div class="doctor-info">
                    <h3 class="doctor-name">${doctor.name}</h3>
                    <p class="doctor-specialty">${doctor.specialty}</p>
                    ${doctor.location ? `
                        <div class="doctor-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${doctor.location}</span>
                        </div>
                    ` : ''}
                    ${doctor.experience ? `
                        <div class="doctor-experience">
                            <i class="fas fa-user-md"></i>
                            <span>${doctor.experience} Years Experience</span>
                        </div>
                    ` : ''}
                    ${doctor.fees ? `
                        <div class="doctor-fees">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Consultation: ${doctor.fees}/-</span>
                        </div>
                    ` : ''}
                    <div class="doctor-actions">
                        <button class="view-profile-btn" data-id="${doctor.id}">View Profile</button>
                        <button class="book-btn" data-id="${doctor.id}">Book Now</button>
                    </div>
                </div>
            `;
            
            const viewProfileBtn = card.querySelector('.view-profile-btn');
            const bookBtn = card.querySelector('.book-btn');
            
            viewProfileBtn.addEventListener('click', () => openDoctorModal(doctor.id));
            bookBtn.addEventListener('click', () => handleBookButtonClick(doctor.id));
            
            return card;
        }

        // Fixed book button click handler
        function handleBookButtonClick(doctorId) {
            console.log('Book button clicked. Login status:', USER_LOGGED_IN);
            
            if (!USER_LOGGED_IN) {
                window.location.href = LOGIN_URL;
            }
            
            openBookingForm(doctorId);
        }

        async function openDoctorModal(doctorId) {
            const doctor = currentDoctors.find(doc => doc.id == doctorId);
            if (!doctor) return;
            
            elements.modalDoctorDetails.innerHTML = `
                <div class="loading-modal">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading doctor details...</p>
                </div>
            `;
            
            elements.modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            try {
                const availabilityData = await fetchDoctorAvailability(doctorId);
                const clinicNames = doctor.clinic_names ? doctor.clinic_names.split(', ') : [];
                const clinicLocations = doctor.clinic_locations ? doctor.clinic_locations.split(', ') : [];
                
                let clinicInfoHTML = '';
                if (clinicNames.length > 0) {
                    clinicInfoHTML = `
                        <div class="doctor-clinics-section">
                            <h3>Available at Clinics:</h3>
                            <div class="clinics-list">
                    `;
                    
                    clinicNames.forEach((clinicName, index) => {
                        const clinicLocation = clinicLocations[index] || 'Location not specified';
                        let availabilityText = 'Please contact clinic for availability';
                        
                        if (availabilityData && availabilityData[clinicName]) {
                            const clinicAvailability = availabilityData[clinicName];
                            const availableDays = Object.keys(clinicAvailability).filter(day => 
                                Object.values(clinicAvailability[day]).some(slot => slot === true)
                            );
                            
                            if (availableDays.length > 0) {
                                const formattedDays = availableDays.map(day => 
                                    day.charAt(0).toUpperCase() + day.slice(1)
                                );
                                availabilityText = formattedDays.join(', ');
                            }
                        } else if (doctor.availability && Array.isArray(doctor.availability)) {
                            availabilityText = doctor.availability.join(', ');
                        }
                        
                        clinicInfoHTML += `
                            <div class="clinic-item">
                                <div class="clinic-name">
                                    <i class="fas fa-hospital"></i>
                                    <strong>${clinicName}</strong>
                                </div>
                                <div class="clinic-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${clinicLocation}</span>
                                </div>
                                <div class="clinic-availability">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><strong>Available Days:</strong> ${availabilityText}</span>
                                </div>
                            </div>
                        `;
                    });
                    
                    clinicInfoHTML += '</div></div>';
                } else if (doctor.availability && Array.isArray(doctor.availability) && doctor.availability.length > 0) {
                    clinicInfoHTML = `
                        <div class="doctor-clinics-section">
                            <h3>Availability:</h3>
                            <div class="clinic-item">
                                <div class="clinic-availability">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><strong>Available Days:</strong> ${doctor.availability.join(', ')}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                elements.modalDoctorDetails.innerHTML = `
                    <div class="doctor-profile">
                        <div class="doctor-profile-info">
                            <h2 class="doctor-profile-name">${doctor.name}</h2>
                            <p class="doctor-specialty">${doctor.specialty}</p>
                            
                            <div class="doctor-profile-details">
                                ${doctor.education ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span>${doctor.education}</span>
                                    </div>
                                ` : ''}
                                ${doctor.location ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>${doctor.location}</span>
                                    </div>
                                ` : ''}
                                ${doctor.experience ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-user-md"></i>
                                        <span>${doctor.experience} Years Experience</span>
                                    </div>
                                ` : ''}
                                ${doctor.email ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-envelope"></i>
                                        <span>${doctor.email}</span>
                                    </div>
                                ` : ''}
                                ${doctor.fees ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>Consultation Fee: ${doctor.fees}/-</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    ${doctor.bio ? `
                        <div class="doctor-bio">
                            <h3>About Doctor</h3>
                            <p>${doctor.bio}</p>
                        </div>
                    ` : ''}

                    ${clinicInfoHTML}
                `;
                
            } catch (error) {
                console.error('Error loading doctor details:', error);
                const basicClinicInfo = createBasicClinicInfo(doctor);
                
                elements.modalDoctorDetails.innerHTML = `
                    <div class="doctor-profile">
                        <div class="doctor-profile-info">
                            <h2 class="doctor-profile-name">${doctor.name}</h2>
                            <p class="doctor-specialty">${doctor.specialty}</p>
                            
                            <div class="doctor-profile-details">
                                ${doctor.education ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span>${doctor.education}</span>
                                    </div>
                                ` : ''}
                                ${doctor.location ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>${doctor.location}</span>
                                    </div>
                                ` : ''}
                                ${doctor.experience ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-user-md"></i>
                                        <span>${doctor.experience} Years Experience</span>
                                    </div>
                                ` : ''}
                                ${doctor.email ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-envelope"></i>
                                        <span>${doctor.email}</span>
                                    </div>
                                ` : ''}
                                ${doctor.fees ? `
                                    <div class="doctor-profile-detail">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>Consultation Fee: ${doctor.fees}/-</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    ${doctor.bio ? `
                        <div class="doctor-bio">
                            <h3>About Doctor</h3>
                            <p>${doctor.bio}</p>
                        </div>
                    ` : ''}
                    
                    ${basicClinicInfo}
                `;
            }
        }

        async function fetchDoctorAvailability(doctorId) {
            try {
                const response = await fetch(`api.php?action=get_doctor_availability&doctor_id=${doctorId}`);
                if (!response.ok) throw new Error('Failed to fetch availability');
                const data = await response.json();
                return data.success ? data.availability : null;
            } catch (error) {
                console.error('Error fetching doctor availability:', error);
                return null;
            }
        }

        function createBasicClinicInfo(doctor) {
            const clinicNames = doctor.clinic_names ? doctor.clinic_names.split(', ') : [];
            const clinicLocations = doctor.clinic_locations ? doctor.clinic_locations.split(', ') : [];
            
            if (clinicNames.length === 0) {
                if (doctor.availability && Array.isArray(doctor.availability) && doctor.availability.length > 0) {
                    return `
                        <div class="doctor-clinics-section">
                            <h3>Availability:</h3>
                            <div class="clinic-item">
                                <div class="clinic-availability">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><strong>Available Days:</strong> ${doctor.availability.join(', ')}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }
                return '';
            }
            
            let clinicInfoHTML = `
                <div class="doctor-clinics-section">
                    <h3>Available at Clinics:</h3>
                    <div class="clinics-list">
            `;
            
            clinicNames.forEach((clinicName, index) => {
                const clinicLocation = clinicLocations[index] || 'Location not specified';
                const availabilityText = doctor.availability && Array.isArray(doctor.availability) && doctor.availability.length > 0 
                    ? doctor.availability.join(', ') 
                    : 'Please contact clinic for availability';
                
                clinicInfoHTML += `
                    <div class="clinic-item">
                        <div class="clinic-name">
                            <i class="fas fa-hospital"></i>
                            <strong>${clinicName}</strong>
                        </div>
                        <div class="clinic-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${clinicLocation}</span>
                        </div>
                        <div class="clinic-availability">
                            <i class="fas fa-calendar-alt"></i>
                            <span><strong>Available Days:</strong> ${availabilityText}</span>
                        </div>
                    </div>
                `;
            });
            
            clinicInfoHTML += '</div></div>';
            return clinicInfoHTML;
        }

        function openBookingForm(doctorId) {
            const doctor = currentDoctors.find(doc => doc.id == doctorId);
            if (!doctor) return;
            
            selectedDoctorForBooking = doctor;
            
            elements.modalTestInfo.innerHTML = `
                <div class="booking-doctor-info">
                    <h2>Book Appointment</h2>
                    <h3>Doctor: ${doctor.name}</h3>
                    <p>Specialty: ${doctor.specialty}</p>
                    ${doctor.fees ? `<p>Consultation Fee: ${doctor.fees}/-</p>` : ''}
                </div>
            `;
            
            const clinicDetails = doctor.clinic_details ? doctor.clinic_details.split('||') : [];
            const clinicSelect = document.querySelector('#clinic');
            
            clinicSelect.innerHTML = '<option value="">Select a clinic</option>';
            
            if (clinicDetails.length > 0) {
                clinicDetails.forEach(detail => {
                    const [clinic_id, clinic_name, clinic_location] = detail.split('|');
                    const option = document.createElement('option');
                    option.value = clinic_name;
                    option.textContent = `${clinic_name} - ${clinic_location || 'Location not specified'}`;
                    clinicSelect.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.value = 'General Clinic';
                option.textContent = 'General Clinic';
                clinicSelect.appendChild(option);
            }
            
            const timeSelect = document.querySelector('#time');
            timeSelect.innerHTML = '<option value="">Select date and clinic first</option>';
            
            const dateInput = document.querySelector('#date');
            const today = new Date();
            const minDate = today.toISOString().split('T')[0];
            dateInput.min = minDate;
            
            const maxDate = new Date();
            maxDate.setMonth(maxDate.getMonth() + 3);
            dateInput.max = maxDate.toISOString().split('T')[0];
            
            let doctorIdInput = document.querySelector('#doctor_id');
            if (!doctorIdInput) {
                doctorIdInput = document.createElement('input');
                doctorIdInput.type = 'hidden';
                doctorIdInput.id = 'doctor_id';
                doctorIdInput.name = 'doctor_id';
                elements.bookingForm.appendChild(doctorIdInput);
            }
            doctorIdInput.value = doctor.id;
            
            elements.bookingModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        async function loadTimeSlots() {
            const clinicSelect = document.querySelector('#clinic');
            const dateInput = document.querySelector('#date');
            const timeSelect = document.querySelector('#time');
            
            if (!clinicSelect || !dateInput || !timeSelect || !selectedDoctorForBooking) return;
            
            const selectedClinic = clinicSelect.value;
            const selectedDate = dateInput.value;
            
            if (!selectedClinic || !selectedDate) {
                timeSelect.innerHTML = '<option value="">Select date and clinic first</option>';
                return;
            }
            
            timeSelect.innerHTML = '<option value="">Loading time slots...</option>';
            timeSelect.disabled = true;
            
            try {
                const response = await fetch(`api.php?action=get_time_slots&doctor_id=${selectedDoctorForBooking.id}&clinic_name=${encodeURIComponent(selectedClinic)}&date=${selectedDate}`);
                
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                
                const data = await response.json();
                
                timeSelect.innerHTML = '';
                timeSelect.disabled = false;
                
                if (data.success && data.time_slots && data.time_slots.length > 0) {
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Select a time slot';
                    timeSelect.appendChild(defaultOption);
                    
                    data.time_slots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.value;
                        option.textContent = slot.label;
                        timeSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = data.message || 'No available time slots';
                    timeSelect.appendChild(option);
                }
            } catch (error) {
                console.error('Error loading time slots:', error);
                timeSelect.innerHTML = '<option value="">Error loading time slots</option>';
                timeSelect.disabled = false;
            }
        }

        async function handleBookingSubmit(e) {
            e.preventDefault();
            
            const submitBtn = elements.bookingForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Booking...';
            
            try {
                const formData = new FormData(elements.bookingForm);
                const response = await fetch('api.php?action=book_appointment', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                
                const data = await response.json();
                
                if (data.success) {
                    elements.bookingModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    
                    // Reset form
                    elements.bookingForm.reset();
                    
                    // Optionally redirect to appointments page or show confirmation
                    setTimeout(() => {
                        if (confirm('Appointment booked successfully! Would you like to view your appointments?')) {
                            window.location.href = '../user/appointments.php';
                        }
                    }, 1000);
                    
                } else {
                    showNotification('error', data.message);
                    
                    // If redirect to login is required
                    if (data.redirect_to_login) {
                        setTimeout(() => {
                            window.location.href = '../user/login.php';
                        }, 2000);
                    }
                }
            } catch (error) {
                console.error('Booking error:', error);
                showNotification('error', 'Failed to book appointment. Please try again.');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        }

        function showNotification(type, message) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => notification.remove());
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>