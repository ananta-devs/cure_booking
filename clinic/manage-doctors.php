<?php
    // Start session and include API functions
    session_start();
    if (!isset($_SESSION['clinic_logged_in']) || !isset($_SESSION['clinic_id'])) {
        header("Location: http://localhost/cure_booking/login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Clinic Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="stl.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include './include/top-header.php'; ?>

    <div class="container">
        <?php include './include/sidebar.php'; ?>
        
        <main class="main-content">
            <div id="manage-appointments-section" class="content-section active">
                <div class="header">
                    <h1>Manage Appointments</h1>
                    <p>View and manage appointment statuses for your clinic</p>
                    <div class="quick-actions">
                        <button class="action-btn active" id="viewAppointmentsBtn">
                            <i class="fa fa-calendar-check"></i> View Appointments
                        </button>
                        <button class="action-btn" id="allDoctorsBtn">
                            <i class="fa fa-id-card"></i> Clinic Doctors
                        </button>
                    </div>
                </div>
                
                <div id="message"></div>

                <!-- Appointments List Section -->
                <div id="appointmentsSection" class="section-content">
                    <h2>Appointments List</h2>
                    <div class="appointments-container">
                        <div class="appointments-header">
                            <div class="search-filter">
                                <input type="date" id="filterDate" placeholder="Filter by date">
                                <select id="filterStatus">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="no_show">No Show</option>
                                </select>
                                <select id="filterDoctor">
                                    <option value="">All Doctors</option>
                                </select>
                                <button id="filterBtn" class="filter-btn">
                                    <i class="fa fa-filter"></i> Filter
                                </button>
                                <button id="clearFilterBtn" class="filter-btn secondary">
                                    <i class="fa fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        <div id="appointmentsContainer" class="appointments-table"></div>
                    </div>
                </div>

                <!-- All Doctors Section -->
                <div id="doctorsSection" class="section-content" style="display: none;">
                    <h2>Clinic Doctors</h2>
                    <div id="doctorsContainer" class="doctors-container"></div>
                </div>

                <!-- View Appointment Modal -->
                <div id="appointmentModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><i class="fa fa-calendar-check"></i> Appointment Details</h2>
                            <button class="modal-close" onclick="closeModal('appointmentModal')">&times;</button>
                        </div>
                        <div class="modal-body" id="appointmentModalBody"></div>
                    </div>
                </div>

                <!-- Update Status Modal -->
                <div id="statusModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><i class="fa fa-edit"></i> Update Appointment Status</h2>
                            <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="statusUpdateForm">
                                <input type="hidden" id="appointmentId" name="appointment_id">
                                <div class="form-group">
                                    <label for="newStatus" class="required">New Status:</label>
                                    <select id="newStatus" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="no_show">No Show</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-modal btn-secondary" onclick="closeModal('statusModal')">
                                <i class="fa fa-times"></i> Cancel
                            </button>
                            <button class="btn-modal btn-primary" onclick="saveStatusUpdate()">
                                <i class="fa fa-save"></i> Update Status
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Book Appointment Modal -->
                <div id="bookingModal" class="modal">
                    <div class="modal-content booking-modal">
                        <div class="modal-header">
                            <h2><i class="fa fa-calendar-plus"></i> Book Appointment</h2>
                            <button class="modal-close" onclick="closeModal('bookingModal')">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="bookingForm">
                                <input type="hidden" id="bookingDoctorId" name="doctor_id">
                                <input type="hidden" id="bookingClinicId" name="clinic_id">
                                
                                <div class="booking-info-section">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="bookingClinicName">Clinic Name:</label>
                                            <input type="text" id="bookingClinicName" name="clinic_name" readonly class="form-control-readonly" placeholder="Current Clinic">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="bookingDoctorName">Doctor Name:</label>
                                            <input type="text" id="bookingDoctorName" name="doctor_name" readonly class="form-control-readonly">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="bookingDoctorSpecialty">Specialization:</label>
                                        <input type="text" id="bookingDoctorSpecialty" name="doctor_specialization" readonly class="form-control-readonly">
                                    </div>
                                </div>

                                <div class="booking-form-section">
                                    <h3><i class="fa fa-user"></i> Patient Information</h3>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="patientName" class="required">Patient Name:</label>
                                            <input type="text" id="patientName" name="patient_name" required placeholder="Enter patient's full name">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="patientGender" class="required">Gender:</label>
                                            <select id="patientGender" name="gender" required>
                                                <option value="">Select Gender</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="patientPhone" class="required">Phone Number:</label>
                                            <input type="tel" id="patientPhone" name="patient_phone" required placeholder="Enter phone number" pattern="[0-9]{10}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="patientEmail" class="required">Email Address:</label>
                                            <input type="email" id="patientEmail" name="patient_email" required placeholder="Enter email address">
                                        </div>
                                    </div>
                                </div>

                                <div class="booking-form-section">
                                    <h3><i class="fa fa-calendar"></i> Appointment Details</h3>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="appointmentDate" class="required">Appointment Date:</label>
                                            <input type="date" id="appointmentDate" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="appointmentTime" class="required">Appointment Time:</label>
                                            <select id="appointmentTime" name="appointment_time" required>
                                                <option value="">Select Time</option>
                                            </select>
                                        </div>
                                    </div>
                                   
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-modal btn-secondary" onclick="closeModal('bookingModal')">
                                <i class="fa fa-times"></i> Cancel
                            </button>
                            <button class="btn-modal btn-primary" onclick="saveAppointmentBooking()">
                                <i class="fa fa-calendar-check"></i> Book Appointment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="script.js"></script>
</body>
</html>