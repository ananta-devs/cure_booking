<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);
include '../include/header.php';
include '../styles.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Find Your Doctor</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <style>
        .success-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in-out;
        }

        .success-modal-content {
            background-color: #ffffff;
            margin: 10% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.4s ease-out;
            overflow: hidden;
        }

        .success-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            animation: bounceIn 0.6s ease-out 0.2s both;
        }

        .success-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0 0 10px 0;
            animation: slideInUp 0.5s ease-out 0.4s both;
        }

        .success-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            animation: slideInUp 0.5s ease-out 0.6s both;
        }

        .success-body {
            padding: 30px;
            text-align: center;
        }

        .success-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .success-detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .success-detail-item:last-child {
            margin-bottom: 0;
        }

        .success-detail-item i {
            color: #4CAF50;
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .success-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .success-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .success-btn-primary {
            background-color: #4CAF50;
            color: white;
        }

        .success-btn-primary:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .success-btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .success-btn-secondary:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }

            50% {
                opacity: 1;
                transform: scale(1.05);
            }

            70% {
                transform: scale(0.9);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .success-modal-content {
                margin: 20% auto;
                width: 95%;
            }

            .success-header {
                padding: 25px 20px;
            }

            .success-icon {
                font-size: 3rem;
            }

            .success-title {
                font-size: 1.5rem;
            }

            .success-body {
                padding: 20px;
            }

            .success-actions {
                flex-direction: column;
                gap: 10px;
            }

            .success-btn {
                width: 100%;
            }
        }

        .search-container select {
            background: url('data:image/svg+xml;utf8,<svg fill="gray" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 12px center;
            background-size: 16px 16px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .search-container {
            display: flex;
            align-items: center;
            max-width: 400px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .search-container select {
            flex: 1;
            padding: 10px 12px;
            border: none;
            outline: none;
            font-size: 14px;
            background-color: #fff;
            color: #374151;
            appearance: none;
        }

        .search-container button {
            background-color: #3b82f6;
            border: none;
            padding: 10px 16px;
            cursor: pointer;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
        }

        .search-container button:hover {
            background-color: #2563eb;
        }

        .search-container select:focus {
            outline: none;
        }

        .search-container i {
            font-size: 18px;
        }

        .modal-content .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            /* larger size for visibility */
            font-weight: bold;
            color: #3b82f6;
            cursor: pointer;
            transition: color 0.3s ease, transform 0.3s;
            z-index: 10;
        }

        .modal-content .close:hover {
            color: #ef4444;
            /* red on hover */
            transform: scale(1.2);
        }

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
            border-left: 3px solid #3B82F6;
        }

        .clinic-name {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }

        .clinic-name i {
            color: #3B82F6;
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
            color: rgb(23, 176, 59);
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

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .primary-btn {
            background-color: #3B82F6;
            color: white;
        }

        .primary-btn:hover {
            background-color: #3B82F6;
        }

        @media (max-width: 768px) {

            .clinic-availability,
            .clinic-location {
                margin-left: 15px;
            }

            .clinic-availability {
                margin-top: 8px;
            }

            .clinic-item {
                padding: 10px 12px;
            }

            .clinic-name strong {
                font-size: 0.9rem;
            }

            .clinic-location span,
            .clinic-availability span {
                font-size: 0.8rem;
            }
        }

        .doctor-profile-photo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            margin-right: 100px;
        }

        .doctor-profile-photo img {
            width: 200px;
            height: 200px;
            border-radius: 6px;
            object-fit: cover;
            border: 2px solid #3B82F6;
            /* blue border like your theme */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

    </style>
</head>

<body>

    <script>
        const USER_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const LOGIN_URL = '../user/login.php';
    </script>

    <section class="hero" data-aos="fade-up">
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
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['doc_specia']) . '">' . htmlspecialchars($row['doc_specia']) . '</option>';
                            }
                        }
                        $conn->close();
                    }
                    ?>
                </select>
                <button type="submit" aria-label="Search"><i class="ri-search-line"></i></button>
            </form>
        </div>
    </section>

    <section class="doctors-section">
        <h2 data-aos="fade">Available Doctors</h2>
        <div id="doctors-container" class="doctors-container"></div>
    </section>

    <!-- Doctor Profile Modal -->
    <div id="doctor-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="modal-doctor-details"></div>
        </div>
    </div>

    <!-- Booking Modal -->
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

    <!-- Success Modal -->
    <div id="successModal" class="success-modal">
        <div class="success-modal-content">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="success-title">Booking Successful!</h2>
                <p class="success-subtitle">Thank You</p>
            </div>
            <div class="success-body">
                <p>Your appointment has been confirmed successfully. You will receive a confirmation shortly.</p>
                <div id="successDetails" class="success-details">
                    <!-- Appointment details will be populated here -->
                </div>
                <div class="success-actions">
                    <button class="success-btn success-btn-primary" onclick="viewAppointments()">
                        <i class="fas fa-calendar-alt"></i> View My Appointments
                    </button>
                    <button class="success-btn success-btn-secondary" onclick="closeSuccessModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
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

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            fetchDoctors();
            setupEventListeners();
        });

        function setupEventListeners() {
            elements.searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                filterDoctors();
            });

            elements.closeModal.addEventListener('click', () => closeModal(elements.modal));
            elements.closeBookingModal.addEventListener('click', () => closeModal(elements.bookingModal));

            window.addEventListener('click', (e) => {
                if (e.target === elements.modal) closeModal(elements.modal);
                if (e.target === elements.bookingModal) closeModal(elements.bookingModal);
            });

            elements.bookingForm.addEventListener('submit', handleBookingSubmit);
            document.querySelector('#clinic').addEventListener('change', loadTimeSlots);
            document.querySelector('#date').addEventListener('change', loadTimeSlots);
        }

        function closeModal(modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        async function fetchDoctors(filters = {}) {
            elements.doctorsContainer.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading doctors...</div>';

            const queryParams = new URLSearchParams({
                action: 'get_doctors'
            });
            if (filters.specialization) queryParams.append('specialization', filters.specialization);

            try {
                const response = await fetch(`api.php?${queryParams.toString()}`);
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

                const responseText = await response.text();
                const doctors = JSON.parse(responseText);

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
                    </div>
                `;
            }
        }

        function filterDoctors() {
            const specialization = elements.searchInput.value;
            fetchDoctors({
                specialization
            });
        }

        function displayDoctors(doctorsToDisplay) {
            if (!doctorsToDisplay || doctorsToDisplay.length === 0) {
                elements.doctorsContainer.innerHTML = '<div class="error-message"><i class="fas fa-user-md"></i><p>No doctors found matching your criteria.</p></div>';
                return;
            }

            elements.doctorsContainer.innerHTML = '';
            doctorsToDisplay.forEach(doctor => {
                elements.doctorsContainer.appendChild(createDoctorCard(doctor));
            });
        }

        function createDoctorCard(doctor) {
            const card = document.createElement('div');
            card.className = 'doctor-card';

            card.innerHTML = `
                <div class="doctor-info">
                <div data-aos="zoom-in" data-aos-duration="800">
                    <h3 class="doctor-name">${doctor.name}</h3>
                    <p class="doctor-specialty">${doctor.specialty}</p>
                    ${doctor.location ? `<div class="doctor-location"><i class="fas fa-map-marker-alt"></i><span>${doctor.location}</span></div>` : ''}
                    ${doctor.experience ? `<div class="doctor-experience"><i class="fas fa-user-md"></i><span>${doctor.experience} Years Experience</span></div>` : ''}
                    ${doctor.fees ? `<div class="doctor-fees"><i class="fas fa-money-bill-wave"></i><span>Consultation: ₹ ${doctor.fees}/-</span></div>` : ''}
                </div>
                    <div class="doctor-actions" data-aos="fade" data-aos-duration="800">
                        <button class="view-profile-btn" data-id="${doctor.id}">View Profile</button>
                        <button class="book-btn" data-id="${doctor.id}">Book Now</button>
                    </div>
                </div>
            `;
            setTimeout(() => {
                AOS.refreshHard();
            }, 0);

            card.querySelector('.view-profile-btn').addEventListener('click', () => openDoctorModal(doctor.id));
            card.querySelector('.book-btn').addEventListener('click', () => {
                if (!USER_LOGGED_IN) {
                    window.location.href = LOGIN_URL;
                    return;
                }
                openBookingForm(doctor.id);
            });

            return card;
        }

        async function openDoctorModal(doctorId) {
            const doctor = currentDoctors.find(doc => doc.id == doctorId);
            if (!doctor) return;

            elements.modalDoctorDetails.innerHTML = '<div class="loading-modal"><i class="fas fa-spinner fa-spin"></i><p>Loading doctor details...</p></div>';
            elements.modal.style.display = 'block';
            document.body.style.overflow = 'hidden';

            try {
                const availabilityData = await fetchDoctorAvailability(doctorId);
                const clinicInfoHTML = createClinicInfo(doctor, availabilityData);

                elements.modalDoctorDetails.innerHTML = `
                    <div class="doctor-profile">
                        <div class="doctor-profile-info">
                            <h2 class="doctor-profile-name">${doctor.name}</h2>
                            <p class="doctor-specialty">${doctor.specialty}</p>
                            <div class="doctor-profile-details">
                                ${createProfileDetail('fas fa-graduation-cap', doctor.education)}
                                ${createProfileDetail('fas fa-map-marker-alt', doctor.location)}
                                ${createProfileDetail('fas fa-user-md', doctor.experience ? `${doctor.experience} Years Experience` : null)}
                                ${createProfileDetail('fas fa-envelope', doctor.email)}
                                ${createProfileDetail('fas fa-money-bill-wave', doctor.fees ? `Consultation Fee: ₹ ${doctor.fees}/-` : null)}
                            </div>
                        </div>
                        <div class="doctor-profile-photo">
                            <img src="http://localhost/cure_booking/adminhub/manage-doctors/uploads/${doctor.doc_img}" alt="${doctor.name}" />
                        </div>
                    </div>
                    ${doctor.bio ? `<div class="doctor-bio"><h3>About Doctor</h3><p>${doctor.bio}</p></div>` : ''}
                    ${clinicInfoHTML}
                `;
            } catch (error) {
                console.error('Error loading doctor details:', error);
                elements.modalDoctorDetails.innerHTML = createBasicDoctorProfile(doctor);
            }
        }

        function createProfileDetail(iconClass, content) {
            return content ? `<div class="doctor-profile-detail"><i class="${iconClass}"></i><span>${content}</span></div>` : '';
        }

        function createClinicInfo(doctor, availabilityData) {
            const clinicNames = doctor.clinic_names ? doctor.clinic_names.split(', ') : [];
            const clinicLocations = doctor.clinic_locations ? doctor.clinic_locations.split(', ') : [];

            if (clinicNames.length === 0) {
                return doctor.availability && Array.isArray(doctor.availability) && doctor.availability.length > 0 ?
                    `<div class="doctor-clinics-section"><h3>Availability:</h3><div class="clinic-item"><div class="clinic-availability"><i class="fas fa-calendar-alt"></i><span><strong>Available Days:</strong> ${doctor.availability.join(', ')}</span></div></div></div>` :
                    '';
            }

            let clinicInfoHTML = '<div class="doctor-clinics-section"><h3>Available at Clinics:</h3><div class="clinics-list">';

            clinicNames.forEach((clinicName, index) => {
                const clinicLocation = clinicLocations[index] || 'Location not specified';
                let availabilityText = 'Please contact clinic for availability';

                if (availabilityData && availabilityData[clinicName]) {
                    const availableDays = Object.keys(availabilityData[clinicName]).filter(day =>
                        Object.values(availabilityData[clinicName][day]).some(slot => slot === true)
                    );
                    if (availableDays.length > 0) {
                        availabilityText = availableDays.map(day => day.charAt(0).toUpperCase() + day.slice(1)).join(', ');
                    }
                } else if (doctor.availability && Array.isArray(doctor.availability)) {
                    availabilityText = doctor.availability.join(', ');
                }

                clinicInfoHTML += `
                    <div class="clinic-item">
                        <div class="clinic-name"><i class="fas fa-hospital"></i><strong>${clinicName}</strong></div>
                        <div class="clinic-location"><i class="fas fa-map-marker-alt"></i><span>${clinicLocation}</span></div>
                        <div class="clinic-availability"><i class="fas fa-calendar-alt"></i><span><strong>Available Days:</strong> ${availabilityText}</span></div>
                    </div>
                `;
            });

            return clinicInfoHTML + '</div></div>';
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

            populateClinicSelect(doctor);
            setupDateInput();
            addHiddenDoctorId(doctor.id);

            elements.bookingModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function populateClinicSelect(doctor) {
            const clinicSelect = document.querySelector('#clinic');
            const clinicDetails = doctor.clinic_details ? doctor.clinic_details.split('||') : [];

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
        }

        function setupDateInput() {
            const dateInput = document.querySelector('#date');
            const timeSelect = document.querySelector('#time');

            const today = new Date();
            dateInput.min = today.toISOString().split('T')[0];

            const maxDate = new Date();
            maxDate.setMonth(maxDate.getMonth() + 3);
            dateInput.max = maxDate.toISOString().split('T')[0];

            timeSelect.innerHTML = '<option value="">Select date and clinic first</option>';
        }


        function addHiddenDoctorId(doctorId) {
            let doctorIdInput = document.querySelector('#doctor_id');
            if (!doctorIdInput) {
                doctorIdInput = document.createElement('input');
                doctorIdInput.type = 'hidden';
                doctorIdInput.id = 'doctor_id';
                doctorIdInput.name = 'doctor_id';
                elements.bookingForm.appendChild(doctorIdInput);
            }
            doctorIdInput.value = doctorId;
        }

        async function loadTimeSlots() {
            const clinicSelect = document.querySelector('#clinic');
            const dateInput = document.querySelector('#date');
            const timeSelect = document.querySelector('#time');

            if (!selectedDoctorForBooking || !clinicSelect.value || !dateInput.value) {
                timeSelect.innerHTML = '<option value="">Select date and clinic first</option>';
                return;
            }

            timeSelect.innerHTML = '<option value="">Loading time slots...</option>';
            timeSelect.disabled = true;

            try {
                const response = await fetch(`api.php?action=get_time_slots&doctor_id=${selectedDoctorForBooking.id}&clinic_name=${encodeURIComponent(clinicSelect.value)}&date=${dateInput.value}`);

                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

                const data = await response.json();

                timeSelect.innerHTML = '';
                timeSelect.disabled = false;

                if (data.success && data.time_slots && data.time_slots.length > 0) {
                    timeSelect.innerHTML = '<option value="">Select a time slot</option>';
                    data.time_slots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.value;
                        option.textContent = slot.label;
                        timeSelect.appendChild(option);
                    });
                } else {
                    timeSelect.innerHTML = `<option value="">${data.message || 'No available time slots'}</option>`;
                }
            } catch (error) {
                console.error('Error loading time slots:', error);
                timeSelect.innerHTML = '<option value="">Error loading time slots</option>';
                timeSelect.disabled = false;
            }
        }

        // CORRECTED handleBookingSubmit function
        async function handleBookingSubmit(e) {
            e.preventDefault();

            const submitBtn = elements.bookingForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            try {
                const formData = new FormData(elements.bookingForm);
                const response = await fetch('api.php?action=book_appointment', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

                const data = await response.json();

                if (data.success) {
                    // Close the booking modal
                    closeModal(elements.bookingModal);

                    // Reset the form
                    elements.bookingForm.reset();

                    // Show the success modal with details
                    showSuccessModal(data);

                } else {
                    // Enhanced error handling for daily limit
                    let errorMessage = data.message;
                    if (data.message && data.message.includes('maximum limit of 4 appointments per day')) {
                        errorMessage = 'Daily Booking Limit Reached!\n\nYou can book up to 4 appointments per day. Please choose a different date to continue booking.';
                    }

                    showNotification('error', errorMessage);

                    if (data.redirect_to_login) {
                        setTimeout(() => window.location.href = '../user/login.php', 2000);
                    }
                }
            } catch (error) {
                console.error('Booking error:', error);
                showNotification('error', 'Failed to book appointment. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        }

        // CORRECTED setupDateInput function
        function setupDateInput() {
            const dateInput = document.querySelector('#date');
            const timeSelect = document.querySelector('#time');

            const today = new Date();
            dateInput.min = today.toISOString().split('T')[0];

            const maxDate = new Date();
            maxDate.setMonth(maxDate.getMonth() + 3);
            dateInput.max = maxDate.toISOString().split('T')[0];

            timeSelect.innerHTML = '<option value="">Select date and clinic first</option>';

            // Add event listener to show booking limit info when date changes
            dateInput.addEventListener('change', async function() {
                const selectedDate = this.value;
                const userEmail = document.querySelector('#email').value;

                if (selectedDate && userEmail) {
                    const bookingStatus = await checkDailyBookingStatus(userEmail, selectedDate);
                    if (bookingStatus && bookingStatus.success) {
                        const count = bookingStatus.daily_count || 0;
                        const remaining = 4 - count;


                        if (!document.querySelector('.daily-booking-status')) {
                            dateInput.parentNode.appendChild(statusDiv);
                        }

                        if (remaining === 0) {
                            timeSelect.innerHTML = '<option value="">Daily booking limit reached - choose another date</option>';
                            timeSelect.disabled = true;
                        } else {
                            timeSelect.disabled = false;
                        }
                    }
                }
            });
        }

        // HELPER FUNCTIONS (add these to your script)
        function showNotification(type, message) {
            document.querySelectorAll('.notification').forEach(n => n.remove());
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span style="white-space: pre-line;">${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 7000);
        }

        async function checkDailyBookingStatus(email, date) {
            try {
                const response = await fetch(`api.php?action=check_daily_bookings&email=${encodeURIComponent(email)}&date=${date}`);
                if (!response.ok) throw new Error('Failed to check booking status');

                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Error checking daily booking status:', error);
                return null;
            }
        }

        function showSuccessModal(data) {
            const successModal = document.getElementById('successModal');
            const successDetails = document.getElementById('successDetails');

            // Populate appointment details if available
            if (data.appointment_details) {
                const details = data.appointment_details;
                successDetails.innerHTML = `
                    <div class="success-detail-item">
                        <i class="fas fa-user-md"></i>
                        <span><strong>Doctor:</strong> ${details.doctor_name}</span>
                    </div>
                    <div class="success-detail-item">
                        <i class="fas fa-calendar"></i>
                        <span><strong>Date:</strong> ${new Date(details.appointment_date).toLocaleDateString()}</span>
                    </div>
                    <div class="success-detail-item">
                        <i class="fas fa-clock"></i>
                        <span><strong>Time:</strong> ${details.appointment_time}</span>
                    </div>
                    <div class="success-detail-item">
                        <i class="fas fa-hospital"></i>
                        <span><strong>Clinic:</strong> ${details.clinic_name}</span>
                    </div>
                `;
            } else {
                successDetails.innerHTML = `
                    <div class="success-detail-item">
                        <i class="fas fa-info-circle"></i>
                        <span>Appointment details will be sent to your email shortly.</span>
                    </div>
                `;
            }

            // Show the modal
            successModal.style.display = 'block';
            document.body.style.overflow = 'hidden';

            // Auto-close after 8 seconds (optional)
            setTimeout(() => {
                if (successModal.style.display === 'block') {
                    closeSuccessModal();
                }
            }, 30000);
        }

        // Function to close success modal
        function closeSuccessModal() {
            const successModal = document.getElementById('successModal');
            successModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Function to redirect to appointments page
        function viewAppointments() {
            window.location.href = '../user/my-appointments.php';
        }

        // Add event listener to close modal when clicking outside
        window.addEventListener('click', (e) => {
            const successModal = document.getElementById('successModal');
            if (e.target === successModal) {
                closeSuccessModal();
            }
        });
    </script>
    <!---AOS Library --->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 1000,
        });
    </script>
</body>
<?php
include '../include/footer.php';
?>

</html>