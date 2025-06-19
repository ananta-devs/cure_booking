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
    <title>Manage Doctors - Appointment System</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="stl.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
</head>
<body>
    <?php include './include/top-header.php'; ?>

    <div class="container">
        <?php include './include/sidebar.php'; ?>
        
        <main class="main-content">
            <div id="manage-doctors-section" class="content-section active">
                <div class="header">
                    <h1>Manage Doctors</h1>
                    <p>Add, edit, and manage doctor information for your clinic</p>
                    <div class="quick-actions">
                        <button class="action-btn" id="viewAppointmentsBtn">
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
                                <button id="filterBtn" class="filter-btn">Filter</button>
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
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['doc_name']); ?><br>
                                            <small><?php echo htmlspecialchars($appointment['doctor_specialization']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td><span class="status-badge status-<?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-view" onclick="viewAppointment(<?php echo $appointment['id']; ?>)">View</button>
                                                <button class="btn-edit" onclick="editAppointment(<?php echo $appointment['id']; ?>)">Edit</button>
                                                <button class="btn-delete" onclick="deleteAppointment(<?php echo $appointment['id']; ?>)">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Update Appointment Modal -->
                <div id="updateAppointmentModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Update Appointment</h3>
                            <button class="modal-close" onclick="closeUpdateModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="updateAppointmentForm">
                                <input type="hidden" id="updateAppointmentId" name="appointment_id">
                                
                                <div class="form-section">
                                    <h4>Patient Information</h4>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="updateFirstName" class="required">Patient Name</label>
                                            <input type="text" id="updateFirstName" name="firstName" required />
                                            <div class="error" id="updateFirstNameError">Please enter patient name</div>
                                        </div>
                                        <div class="form-group">
                                            <label class="required">Gender</label>
                                            <div class="radio-group">
                                                <div class="radio-option">
                                                    <input type="radio" id="updateGenderMale" name="updateGender" value="male" required />
                                                    <label for="updateGenderMale">Male</label>
                                                </div>
                                                <div class="radio-option">
                                                    <input type="radio" id="updateGenderFemale" name="updateGender" value="female" />
                                                    <label for="updateGenderFemale">Female</label>
                                                </div>
                                                <div class="radio-option">
                                                    <input type="radio" id="updateGenderOther" name="updateGender" value="other" />
                                                    <label for="updateGenderOther">Other</label>
                                                </div>
                                            </div>
                                            <div class="error" id="updateGenderError">Please select gender</div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="updatePhone" class="required">Phone Number</label>
                                            <input type="tel" id="updatePhone" name="phone" required />
                                            <div class="error" id="updatePhoneError">Please enter a valid phone number</div>
                                        </div>
                                        <div class="form-group">
                                            <label for="updateEmail" class="required">Email Address</label>
                                            <input type="email" id="updateEmail" name="email" required />
                                            <div class="error" id="updateEmailError">Please enter a valid email address</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h4>Appointment Details</h4>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="updateSpecialityType" class="required">Speciality</label>
                                            <select id="updateSpecialityType" name="specialityType" required>
                                                <option value="">Select Speciality</option>
                                                <?php foreach ($specialties as $specialty): ?>
                                                <option value="<?php echo htmlspecialchars($specialty); ?>"><?php echo htmlspecialchars($specialty); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="error" id="updateSpecialityTypeError">Please select a speciality</div>
                                        </div>
                                        <div class="form-group">
                                            <label for="updateDoctor" class="required">Preferred Doctor</label>
                                            <select id="updateDoctor" name="doctor" required>
                                                <option value="">Select Doctor</option>
                                            </select>
                                            <div class="doctor-info" id="updateDoctorInfo"></div>
                                            <div class="error" id="updateDoctorError">Please select a doctor</div>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="updatePreferredDate" class="required">Preferred Date</label>
                                            <input type="date" id="updatePreferredDate" name="preferredDate" required min="<?php echo date('Y-m-d'); ?>" />
                                            <div class="error" id="updatePreferredDateError">Please select a preferred date</div>
                                        </div>
                                        <div class="form-group">
                                            <label for="updateTime" class="required">Time Slot</label>
                                            <select id="updateTime" name="time" required>
                                                <option value="">Select Time</option>
                                            </select>
                                            <div class="time-loading" id="updateTimeLoading" style="display: none;">
                                                <i class="fa fa-spinner fa-spin"></i> Loading available slots...
                                            </div>
                                            <div class="error" id="updateTimeError">Please select a time slot</div>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="updateStatus" class="required">Status</label>
                                            <select id="updateStatus" name="status" required>
                                                <option value="pending">Pending</option>
                                                <option value="confirmed">Confirmed</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                                <option value="no_show">No Show</option>
                                            </select>
                                            <div class="error" id="updateStatusError">Please select a status</div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" onclick="closeUpdateModal()">Cancel</button>
                            <button type="button" class="btn-primary" onclick="submitUpdateAppointment()">
                                <i class="fa fa-save"></i> Update Appointment
                            </button>
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
                                    <p>No doctors are currently assigned to your clinic.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($doctors as $doctor): ?>
                                <div class="doctor-card">
                                    <img src="<?php echo $doctor['doc_img'] ? 'http://localhost/cure_booking/adminhub//manage-doctors/uploads/' . htmlspecialchars($doctor['doc_img']) : 'https://via.placeholder.com/80'; ?>" 
                                        alt="<?php echo htmlspecialchars($doctor['doc_name']); ?>" class="doctor-image">
                                    <div class="doctor-name"><?php echo htmlspecialchars($doctor['doc_name']); ?></div>
                                    <div class="doctor-specialty"><?php echo htmlspecialchars($doctor['doc_specia']); ?></div>
                                    <div class="doctor-info">
                                        <div><strong>Experience:</strong> <?php echo htmlspecialchars($doctor['experience']); ?> years</div>
                                        <div><strong>Location:</strong> <?php echo htmlspecialchars($doctor['location']); ?></div>
                                        <div><strong>Education:</strong> <?php echo htmlspecialchars($doctor['education']); ?></div>
                                        <div><strong>Email:</strong> <?php echo htmlspecialchars($doctor['doc_email']); ?></div>
                                    </div>
                                    <div class="doctor-card-actions">
                                        <button class="btn-view-doctor" onclick="showDoctorModal(<?php echo htmlspecialchars(json_encode($doctor)); ?>)">
                                            <i class="fa fa-eye"></i> View Details
                                        </button>
                                        <!-- <button class="btn-book-appointment" onclick="bookAppointmentWithDoctorFromCard(<?php echo $doctor['doc_id']; ?>, '<?php echo htmlspecialchars($doctor['doc_specia']); ?>')">
                                            <i class="fa fa-calendar-plus"></i> Book Appointment
                                        </button> -->
                                        <button class="btn-book-appointment" onclick="bookAppointmentWithDoctorFromCard('<?php echo $doctor['doc_id']; ?>', '<?php echo $doctor['doc_specia']; ?>', '<?php echo $doctor['doc_name']; ?>')">
                                            <i class="fa fa-calendar-plus"></i>Book Appointment
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <form id="appointmentForm">
                        <div class="form-section">
                            <h3>Patient Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName" class="required">Name</label>
                                    <input type="text" id="firstName" name="firstName" required />
                                    <div class="error" id="firstNameError">Please enter patient name</div>
                                </div>
                                <div class="form-group">
                                    <label class="required">Gender</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" id="genderMale" name="gender" value="male" required />
                                            <label for="genderMale">Male</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" id="genderFemale" name="gender" value="female" />
                                            <label for="genderFemale">Female</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" id="genderOther" name="gender" value="other" />
                                            <label for="genderOther">Other</label>
                                        </div>
                                    </div>
                                    <div class="error" id="genderError">Please select gender</div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone" class="required">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" required />
                                    <div class="error" id="phoneError">Please enter a valid phone number</div>
                                </div>
                                <div class="form-group">
                                    <label for="email" class="required">Email Address</label>
                                    <input type="email" id="email" name="email" required />
                                    <div class="error" id="emailError">Please enter a valid email address</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Appointment Details</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="specialityType" class="required">Speciality</label>
                                    <select id="specialityType" name="specialityType" required>
                                        <option value="">Select Speciality</option>
                                        <?php foreach ($specialties as $specialty): ?>
                                        <option value="<?php echo htmlspecialchars($specialty); ?>"><?php echo htmlspecialchars($specialty); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="doctor" class="required">Preferred Doctor</label>
                                    <select id="doctor" name="doctor" required>
                                        <option value="">Select Doctor</option>
                                    </select>
                                    <div class="doctor-info" id="doctorInfo"></div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="preferredDate" class="required">Preferred Date</label>
                                    <input type="date" id="preferredDate" name="preferredDate" required min="<?php echo date('Y-m-d'); ?>" />
                                    <div class="error" id="preferredDateError">Please select a preferred date</div>
                                </div>
                                <div class="form-group">
                                    <label for="time" class="required">Time Slot</label>
                                    <select id="time" name="time" required>
                                        <option value="">Select Time</option>
                                    </select>
                                    <div class="time-loading" id="timeLoading" style="display: none;">
                                        <i class="fa fa-spinner fa-spin"></i> Loading available slots...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" id="cancelAppointmentBtn" class="btn-secondary">Cancel</button>
                            <button type="submit">Book Appointment</button>
                        </div>
                    </form>
                </div>

                <?php
                    echo "<script>console.log('Doctor data:', " . json_encode($doctor) . ");</script>";
                ?>

                <!-- Doctor Modal -->
                <div id="doctorModal" class="modal">
                    <div class="doctor-modal-content">
                        <div class="doctor-modal-header">
                            <h3>Doctor Details</h3>
                            <button class="doctor-modal-close" onclick="closeDoctorModal()">&times;</button>
                        </div>
                        <div class="doctor-modal-body" id="doctorModalBody">
                            <!-- Doctor details will be populated here -->
                        </div>
                        <div class="doctor-modal-footer">
                            <button class="btn-close" onclick="closeDoctorModal()">Close</button>
                            <button class="btn-book-modal" onclick="bookAppointmentWithDoctor()">
                                <i class="fa fa-calendar-plus"></i> Book Appointment
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