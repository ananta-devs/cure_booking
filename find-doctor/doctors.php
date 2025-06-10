<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Find Your Doctor</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
</head>
<body>
    <?php include '../include/header.php'; ?>
    <?php include '../styles.php'; ?>
    
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
                            $sql = "SELECT DISTINCT doc_specia FROM doctor ORDER BY doc_specia";
                            $result = $conn->query($sql);
                            
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
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="time">Time Slot</label>
                        <select id="time" name="time" required>
                            <option value="">Select Time</option>
                            <option value="11:00-13:00">11:00-01:00 PM</option>
                            <option value="14:00-16:00">02:00-04:00 PM</option>
                            <option value="17:00-19:00">05:00-07:00 PM</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn primary-btn">Confirm Booking</button>
            </form>
        </div>
    </div>

    <script>
        // Replace the entire <script> section in doctors.php with this updated version

        const doctorsContainer = document.getElementById('doctors-container');
        const searchInput = document.getElementById('search-bar');
        const modal = document.getElementById('doctor-modal');
        const closeModal = document.querySelector('.close-modal');
        const modalDoctorDetails = document.getElementById('modal-doctor-details');
        const bookingModal = document.getElementById('bookingModal');
        const bookingForm = document.getElementById('bookingForm');
        const modalTestInfo = document.getElementById('modalTestInfo');
        const closeBookingModal = document.querySelector('#bookingModal .close');
        const searchForm = document.querySelector('.search-container');

        let currentDoctors = [];
        let selectedDoctorForBooking = null;

        document.addEventListener('DOMContentLoaded', () => {
            fetchDoctors();
            setupEventListeners();
        });

        function setupEventListeners() {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                filterDoctors();
            });
            
            closeModal.addEventListener('click', () => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
            
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
                if (e.target === bookingModal) {
                    bookingModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });

            closeBookingModal.addEventListener('click', () => {
                bookingModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });

            bookingForm.addEventListener('submit', handleBookingSubmit);
        }

        // Add login status check function
        async function checkLoginStatus() {
            try {
                const response = await fetch('check_login.php', {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                const data = await response.json();
                return data.logged_in === true;
            } catch (error) {
                console.error('Login check error:', error);
                return false;
            }
        }

        async function fetchDoctors(filters = {}) {
            doctorsContainer.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading doctors...</div>';
            
            const queryParams = new URLSearchParams();
            queryParams.append('action', 'get_doctors');
            if (filters.specialization) queryParams.append('specialization', filters.specialization);
            
            try {
                const response = await fetch(`api.php?${queryParams.toString()}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                const responseText = await response.text();
                
                let doctors;
                try {
                    doctors = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Parse error:', parseError);
                    console.error('Response text:', responseText);
                    doctorsContainer.innerHTML = `
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
                doctorsContainer.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Failed to load doctors. Please try again later.</p>
                        <small>Error: ${error.message}</small>
                    </div>
                `;
            }
        }

        function filterDoctors() {
            const specialization = searchInput.value;
            fetchDoctors({ specialization });
        }

        function displayDoctors(doctorsToDisplay) {
            doctorsContainer.innerHTML = '';
            
            if (!doctorsToDisplay || doctorsToDisplay.length === 0) {
                doctorsContainer.innerHTML = '<div class="error-message"><i class="fas fa-user-md"></i><p>No doctors found matching your criteria.</p></div>';
                return;
            }
            
            doctorsToDisplay.forEach(doctor => {
                const doctorCard = createDoctorCard(doctor);
                doctorsContainer.appendChild(doctorCard);
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
            
            // Updated book button click handler with login check
            bookBtn.addEventListener('click', async () => {
                const originalText = bookBtn.textContent;
                bookBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
                bookBtn.disabled = true;
                
                try {
                    const isLoggedIn = await checkLoginStatus();
                    
                    if (!isLoggedIn) {
                        // Redirect to login page immediately
                        window.location.href = '../user/login.php';
                        return;
                    }
                    
                    // Reset button state before opening modal
                    bookBtn.textContent = originalText;
                    bookBtn.disabled = false;
                    
                    // User is logged in, proceed with booking
                    openBookingForm(doctor.id);
                } catch (error) {
                    console.error('Login check failed:', error);
                    // Redirect to login page as fallback
                    window.location.href = '../user/login.php';
                }
            });
            
            return card;
        }

        function openDoctorModal(doctorId) {
            const doctor = currentDoctors.find(doc => doc.id == doctorId);
            
            if (!doctor) return;
            
            modalDoctorDetails.innerHTML = `
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
                ${doctor.availability && doctor.availability.length > 0 ? `
                    <div class="doctor-availability">
                        <h3>Available Days</h3>
                        <div class="availability-tags">
                            ${doctor.availability.map(day => `<span class="availability-tag">${day}</span>`).join('')}
                        </div>
                    </div>
                ` : ''}
            `;
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // This function will only be called for logged-in users
        function openBookingForm(doctorId) {
            const doctor = currentDoctors.find(doc => doc.id == doctorId);
            
            if (!doctor) return;
            
            selectedDoctorForBooking = doctor;
            
            modalTestInfo.innerHTML = `
                <div class="booking-doctor-info">
                    <h2>Book Appointment</h2>
                    <h3>Doctor: ${doctor.name}</h3>
                    <p>Specialty: ${doctor.specialty}</p>
                    ${doctor.fees ? `<p>Consultation Fee: ${doctor.fees}/-</p>` : ''}
                </div>
            `;
            
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date').min = today;
            
            bookingModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function handleBookingSubmit(e) {
            e.preventDefault();
            
            if (!selectedDoctorForBooking) {
                showBookingError('No doctor selected. Please try again.');
                return;
            }
            
            const formData = new FormData(bookingForm);
            formData.append('doctor_id', selectedDoctorForBooking.id);
            
            const submitBtn = bookingForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('book_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showBookingSuccess(data);
                } else {
                    if (data.redirect_to_login) {
                        // Session expired, redirect to login
                        window.location.href = '../user/login.php';
                    } else {
                        showBookingError(data.message || 'An error occurred. Please try again.');
                    }
                }
            })
            .catch(error => {
                console.error('Booking error:', error);
                showBookingError('An error occurred. Please try again.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            });
        }

        function showBookingSuccess(data = {}) {
            const formContent = bookingForm.innerHTML;
            
            modalTestInfo.style.display = 'none';
            
            bookingForm.innerHTML = `
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h3>Booking Successful!</h3>
                    <div class="booking-details">
                        <p><strong>Patient:</strong> ${data.patient_name || 'N/A'}</p>
                        <p><strong>Doctor:</strong> ${data.doctor_name || selectedDoctorForBooking.name}</p>
                        <p><strong>Date:</strong> ${data.appointment_date || 'N/A'}</p>
                        <p><strong>Time:</strong> ${data.appointment_time || 'N/A'}</p>
                        ${data.booked_by ? `<p><strong>Booked by:</strong> ${data.booked_by}</p>` : ''}
                    </div>
                    <p>We'll send a confirmation to your email.</p>
                    <button type="button" class="btn primary-btn" id="close-success-btn">Close</button>
                </div>
            `;
            
            document.getElementById('close-success-btn').addEventListener('click', () => {
                bookingModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                setTimeout(() => {
                    bookingForm.innerHTML = formContent;
                    bookingForm.reset();
                    modalTestInfo.style.display = 'block';
                    selectedDoctorForBooking = null;
                }, 300);
            });
        }

        function showBookingError(message) {
            const existingError = bookingForm.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <p>${message}</p>
            `;
            
            bookingForm.prepend(errorDiv);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>