<?php
    session_start();
    include_once 'database_connection.php';

    // Check if user is logged in and get clinic_id
    if (!isset($_SESSION['clinic_logged_in']) || !isset($_SESSION['clinic_id'])) {
        header("Location: http://localhost/cure_booking/login.php");
        exit();
    }

    $clinic_id = $_SESSION['clinic_id']; // Get the logged-in clinic ID

    // Fetch appointments with doctor and patient details for the logged-in clinic
    function getAppointments($conn, $clinic_id) {
        $query = "SELECT 
                    a.id, a.patient_name, a.patient_phone, a.patient_email,
                    a.appointment_date, a.appointment_time, a.status, a.gender,
                    d.doc_name, d.doc_specia as doctor_specialization, d.doc_id
                FROM appointments a 
                JOIN doctor d ON a.doctor_id = d.doc_id 
                WHERE a.clinic_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $clinic_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $appointments = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $appointments[] = $row;
            }
        }
        
        return $appointments;
    }

    // Fetch doctors assigned to the logged-in clinic
    function getDoctors($conn, $clinic_id) {
        $query = "SELECT DISTINCT d.* 
                FROM doctor d 
                JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
                WHERE dca.clinic_id = ? 
                ORDER BY d.doc_name";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $clinic_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $doctors = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $doctors[] = $row;
            }
        }
        
        return $doctors;
    }

    // Fetch specialties from doctors assigned to the logged-in clinic
    function getSpecialties($conn, $clinic_id) {
        $query = "SELECT DISTINCT d.doc_specia 
                FROM doctor d 
                JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
                WHERE dca.clinic_id = ? 
                ORDER BY d.doc_specia";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $clinic_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $specialties = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $specialties[] = $row['doc_specia'];
            }
        }
        
        return $specialties;
    }

    // Handle AJAX requests
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        switch ($_POST['action']) {
            case 'book_appointment':
                $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
                $patient_phone = mysqli_real_escape_string($conn, $_POST['patient_phone']);
                $patient_email = mysqli_real_escape_string($conn, $_POST['patient_email']);
                $doctor_id = (int)$_POST['doctor_id'];
                $appointment_date = $_POST['appointment_date'];
                $appointment_time = $_POST['appointment_time'];
                $gender = mysqli_real_escape_string($conn, $_POST['gender']);
                
                // Get doctor and clinic information
                $doctor_query = "SELECT d.doc_name, d.doc_specia, c.clinic_name 
                               FROM doctor d 
                               JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
                               JOIN clinics c ON dca.clinic_id = c.clinic_id 
                               WHERE d.doc_id = ? AND dca.clinic_id = ?";
                $doctor_stmt = mysqli_prepare($conn, $doctor_query);
                mysqli_stmt_bind_param($doctor_stmt, "ii", $doctor_id, $clinic_id);
                mysqli_stmt_execute($doctor_stmt);
                $doctor_result = mysqli_stmt_get_result($doctor_stmt);
                $doctor_info = mysqli_fetch_assoc($doctor_result);
                
                if (!$doctor_info) {
                    echo json_encode(['success' => false, 'message' => 'Doctor not found or not assigned to this clinic']);
                    exit();
                }
                
                // Assuming you have user session data for booked_by fields
                $booked_by_user_id = $_SESSION['user_id'] ?? 0; // You'll need to set this in your session
                $booked_by_email = $_SESSION['user_email'] ?? '';
                $booked_by_name = $_SESSION['user_name'] ?? '';
                
                $query = "INSERT INTO appointments (
                    doctor_id, doctor_name, doctor_specialization, clinic_id, clinic_name,
                    patient_name, patient_phone, patient_email, appointment_date, appointment_time, 
                    gender, booked_by_user_id, booked_by_email, booked_by_name, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "issisissssisss", 
                    $doctor_id, $doctor_info['doc_name'], $doctor_info['doc_specia'], 
                    $clinic_id, $doctor_info['clinic_name'], $patient_name, $patient_phone, 
                    $patient_email, $appointment_date, $appointment_time, $gender,
                    $booked_by_user_id, $booked_by_email, $booked_by_name
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Appointment booked successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error booking appointment: ' . mysqli_error($conn)]);
                }
                exit();
                
            case 'update_appointment':
                $id = (int)$_POST['id'];
                $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name']);
                $patient_phone = mysqli_real_escape_string($conn, $_POST['patient_phone']);
                $patient_email = mysqli_real_escape_string($conn, $_POST['patient_email']);
                $doctor_id = (int)$_POST['doctor_id'];
                $appointment_date = $_POST['appointment_date'];
                $appointment_time = $_POST['appointment_time'];
                $gender = mysqli_real_escape_string($conn, $_POST['gender']);
                
                // Verify the appointment belongs to this clinic
                $verify_query = "SELECT id FROM appointments WHERE id = ? AND clinic_id = ?";
                $verify_stmt = mysqli_prepare($conn, $verify_query);
                mysqli_stmt_bind_param($verify_stmt, "ii", $id, $clinic_id);
                mysqli_stmt_execute($verify_stmt);
                $verify_result = mysqli_stmt_get_result($verify_stmt);
                
                if (mysqli_num_rows($verify_result) === 0) {
                    echo json_encode(['success' => false, 'message' => 'Appointment not found or access denied']);
                    exit();
                }
                
                // Get doctor information
                $doctor_query = "SELECT doc_name, doc_specia FROM doctor WHERE doc_id = ?";
                $doctor_stmt = mysqli_prepare($conn, $doctor_query);
                mysqli_stmt_bind_param($doctor_stmt, "i", $doctor_id);
                mysqli_stmt_execute($doctor_stmt);
                $doctor_result = mysqli_stmt_get_result($doctor_stmt);
                $doctor_info = mysqli_fetch_assoc($doctor_result);
                
                $query = "UPDATE appointments SET 
                         patient_name=?, patient_phone=?, patient_email=?, 
                         doctor_id=?, doctor_name=?, doctor_specialization=?,
                         appointment_date=?, appointment_time=?, gender=?
                         WHERE id=? AND clinic_id=?";
                
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssissssssii", 
                    $patient_name, $patient_phone, $patient_email, 
                    $doctor_id, $doctor_info['doc_name'], $doctor_info['doc_specia'],
                    $appointment_date, $appointment_time, $gender, $id, $clinic_id
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Appointment updated successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating appointment: ' . mysqli_error($conn)]);
                }
                exit();
                
            case 'delete_appointment':
                $id = (int)$_POST['id'];
                
                // Verify the appointment belongs to this clinic before deleting
                $query = "DELETE FROM appointments WHERE id = ? AND clinic_id = ?";
                
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $id, $clinic_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    if (mysqli_stmt_affected_rows($stmt) > 0) {
                        echo json_encode(['success' => true, 'message' => 'Appointment deleted successfully!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Appointment not found or access denied']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting appointment: ' . mysqli_error($conn)]);
                }
                exit();
                
            case 'get_doctors_by_specialty':
                $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
                $query = "SELECT d.doc_id, d.doc_name, d.doc_specia, d.experience, d.location, d.education 
                         FROM doctor d 
                         JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
                         WHERE d.doc_specia = ? AND dca.clinic_id = ? 
                         ORDER BY d.doc_name";
                
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "si", $specialty, $clinic_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                $doctors = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $doctors[] = $row;
                }
                
                echo json_encode($doctors);
                exit();
        }
    }

    // Fetch data for page load - now filtered by clinic
    $appointments = getAppointments($conn, $clinic_id);
    $doctors = getDoctors($conn, $clinic_id);
    $specialties = getSpecialties($conn, $clinic_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Doctors - Appointment System</title>
    <link rel="stylesheet" href="styles.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
</head>
<body>
    <?php include './top-header.php'; ?>

    <div class="container">
        <?php include './sidebar.php'; ?>
        
        <main class="main-content">
            <div id="manage-doctors-section" class="content-section active">
                <div class="header">
                    <h1>Manage Doctors</h1>
                    <p>Add, edit, and manage doctor information for your clinic</p>
                    <div class="quick-actions">
                        <button class="action-btn" id="viewAppointmentsBtn">
                            <i class="fa fa-calendar-check"></i> View Appointments
                        </button>
                        <button class="action-btn" id="addAppointmentBtn">
                            <i class="fa fa-plus"></i> Add Appointment
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

                <!-- Add Appointment Form Section -->
                <div id="appointmentFormSection" class="section-content" style="display: none;">
                    <h2>Add New Appointment</h2>
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
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" id="cancelAppointmentBtn" class="btn-secondary">Cancel</button>
                            <button type="submit">Book Appointment</button>
                        </div>
                    </form>
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
                                    <img src="<?php echo $doctor['doc_img'] ? 'uploads/doctors/' . htmlspecialchars($doctor['doc_img']) : 'https://via.placeholder.com/80'; ?>" 
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
                                        <button class="btn-book-appointment" onclick="bookAppointmentWithDoctorFromCard(<?php echo $doctor['doc_id']; ?>, '<?php echo htmlspecialchars($doctor['doc_specia']); ?>')">
                                            <i class="fa fa-calendar-plus"></i> Book Appointment
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

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

    <script>
        const appointments = <?php echo json_encode($appointments); ?>;
        const doctors = <?php echo json_encode($doctors); ?>;
        const specialties = <?php echo json_encode($specialties); ?>;
        const appointmentStatuses = <?php echo json_encode(array_column($appointments, 'status')); ?>;        
    </script>
    <script src="script.js"></script>
</body>
</html>