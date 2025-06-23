<?php
    // Start session and include API functions
    session_start();
    include_once 'api.php'; // This includes all the API functions and database connection

    if (!isset($_SESSION['clinic_logged_in']) || !isset($_SESSION['clinic_id'])) {
        header("Location: http://localhost/cure_booking/login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Appointments - Clinic Dashboard</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="stl.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
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
                                    <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo htmlspecialchars($doctor['doc_id']); ?>">
                                        Dr. <?php echo htmlspecialchars($doctor['doc_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button id="filterBtn" class="filter-btn">
                                    <i class="fa fa-filter"></i> Filter
                                </button>
                                <button id="clearFilterBtn" class="filter-btn secondary">
                                    <i class="fa fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        <div class="appointments-table">
                            <table id="appointmentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Patient Name</th>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTableBody">
                                    <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                        <td>
                                                <strong><?php echo htmlspecialchars($appointment['patient_name']); ?></strong>
                                                <small><?php echo htmlspecialchars($appointment['patient_phone']); ?></small>
            
                                        </td>
                                        <td>

                                                <strong><?php echo htmlspecialchars($appointment['doc_name']); ?></strong>
                                                <small><?php echo htmlspecialchars($appointment['doctor_specialization']); ?></small>

                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-view" onclick="viewAppointment(<?php echo $appointment['id']; ?>)" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <button class="btn-edit" onclick="updateAppointmentStatus(<?php echo $appointment['id']; ?>, '<?php echo $appointment['status']; ?>')" title="Update Status">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <?php if (in_array($appointment['status'], ['pending', 'confirmed'])): ?>
                                                <button class="btn-delete" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)" title="Cancel Appointment">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- All Doctors Section -->
                <div id="doctorsSection" class="section-content" style="display: none;">
                    <h2>Clinic Doctors</h2>
                    <div class="doctors-container">
                        <div class="doctors-grid" id="doctorsGrid">
                            <?php if (empty($doctors)): ?>
                                <div class="no-doctors-message">
                                    <i class="fa fa-user-md fa-3x"></i>
                                    <h3>No Doctors Found</h3>
                                    <p>No doctors are currently assigned to your clinic.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($doctors as $doctor): ?>
                                <div class="doctor-card">
                                    <div class="doctor-image-container">
                                        <img src="<?php echo $doctor['doc_img'] ? 'http://localhost/cure_booking/adminhub/manage-doctors/uploads/' . htmlspecialchars($doctor['doc_img']) : 'https://via.placeholder.com/120x120?text=Dr'; ?>" 
                                            alt="<?php echo htmlspecialchars($doctor['doc_name']); ?>" class="doctor-image">
                                    </div>
                                    <div class="doctor-details">
                                        <h3 class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['doc_name']); ?></h3>
                                        <p class="doctor-specialty"><?php echo htmlspecialchars($doctor['doc_specia']); ?></p>
                                        <div class="doctor-info">
                                            <div class="info-item">
                                                <i class="fa fa-graduation-cap"></i>
                                                <span><?php echo htmlspecialchars($doctor['experience']); ?> years experience</span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fa fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($doctor['location']); ?></span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fa fa-envelope"></i>
                                                <span><?php echo htmlspecialchars($doctor['doc_email']); ?></span>
                                            </div>
                                        </div>
                                        <div class="doctor-card-actions">
                                            <button class="btn-view-doctor" onclick="showDoctorModal(<?php echo htmlspecialchars(json_encode($doctor)); ?>)">
                                                <i class="fa fa-eye"></i> View Details
                                            </button>
                                            <button class="btn-book-appointment" onclick="showBookingModal(<?php echo htmlspecialchars(json_encode($doctor)); ?>)">
                                                <i class="fa fa-calendar-plus"></i> Book Appointment
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- View Appointment Modal -->
                <div id="appointmentModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><i class="fa fa-calendar-check"></i> Appointment Details</h2>
                            <button class="modal-close" onclick="closeAppointmentModal()">&times;</button>
                        </div>
                        <div class="modal-body" id="appointmentModalBody">
                            <!-- Appointment details will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Update Status Modal -->
                <div id="statusModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><i class="fa fa-edit"></i> Update Appointment Status</h2>
                            <button class="modal-close" onclick="closeStatusModal()">&times;</button>
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
                                <div class="form-group">
                                    <label for="statusNote">Note (Optional):</label>
                                    <textarea id="statusNote" name="note" rows="3" placeholder="Add a note about this status change..."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-modal btn-secondary" onclick="closeStatusModal()">
                                <i class="fa fa-times"></i> Cancel
                            </button>
                            <button class="btn-modal btn-primary" onclick="saveStatusUpdate()">
                                <i class="fa fa-save"></i> Update Status
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Doctor Modal -->
                <div id="doctorModal" class="modal">
                    <div class="doctor-modal-content">
                        <div class="doctor-modal-header">
                            <h3><i class="fa fa-user-md"></i> Doctor Details</h3>
                            <button class="doctor-modal-close" onclick="closeDoctorModal()">&times;</button>
                        </div>
                        <div class="doctor-modal-body" id="doctorModalBody">
                            <!-- Doctor details will be populated here -->
                        </div>
                        <div class="doctor-modal-footer">
                            <button class="btn-modal btn-secondary" onclick="closeDoctorModal()">
                                <i class="fa fa-times"></i> Close
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Book Appointment Modal -->
                <div id="bookingModal" class="modal">
                    <div class="modal-content booking-modal">
                        <div class="modal-header">
                            <h2><i class="fa fa-calendar-plus"></i> Book Appointment</h2>
                            <button class="modal-close" onclick="closeBookingModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="bookingForm">
                                <!-- Hidden fields for doctor and clinic info -->
                                <input type="hidden" id="bookingDoctorId" name="doctor_id">
                                <input type="hidden" id="bookingClinicId" name="clinic_id">
                                
                                <!-- Doctor and Clinic Info (Read-only) -->
                                <div class="booking-info-section">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="bookingClinicName">Clinic Name:</label>
                                            <input type="text" id="bookingClinicName" name="clinic_name" readonly class="form-control-readonly">
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

                                <!-- Patient Information -->
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

                                <!-- Appointment Details -->
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
                                                <option value="09:00">09:00 AM</option>
                                                <option value="09:30">09:30 AM</option>
                                                <option value="10:00">10:00 AM</option>
                                                <option value="10:30">10:30 AM</option>
                                                <option value="11:00">11:00 AM</option>
                                                <option value="11:30">11:30 AM</option>
                                                <option value="12:00">12:00 PM</option>
                                                <option value="12:30">12:30 PM</option>
                                                <option value="14:00">02:00 PM</option>
                                                <option value="14:30">02:30 PM</option>
                                                <option value="15:00">03:00 PM</option>
                                                <option value="15:30">03:30 PM</option>
                                                <option value="16:00">04:00 PM</option>
                                                <option value="16:30">04:30 PM</option>
                                                <option value="17:00">05:00 PM</option>
                                                <option value="17:30">05:30 PM</option>
                                                <option value="18:00">06:00 PM</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="appointmentNotes">Additional Notes (Optional):</label>
                                        <textarea id="appointmentNotes" name="notes" rows="3" placeholder="Any specific requirements or notes for the appointment..."></textarea>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-modal btn-secondary" onclick="closeBookingModal()">
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
    <script src="add_script.js"></script>
</body>
</html>