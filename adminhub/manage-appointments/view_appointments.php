<?php
    // Include database connection
    require_once '../include/database_connection.php';

    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['adm_id'])) {
        header("Location: http://localhost/adminhub/login.php");
        exit();
    }

    // Check connection
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Handle status update if form submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'updateStatus') {
        $appointmentId = $_POST['id'];
        $newStatus = $_POST['new_status'];
        
        $updateSql = "UPDATE appointments SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $newStatus, $appointmentId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Appointment status updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating status: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }

    // Get all appointments with clinic information and doctor fees using doctor_id
    $sql = "SELECT a.*, c.clinic_name, c.location as clinic_location, c.contact_number as clinic_contact, 
                   d.fees as doctor_fees, d.doc_name as doctor_name_from_table, d.doc_specia as doctor_spec_from_table
            FROM appointments a 
            LEFT JOIN clinics c ON a.clinic_id = c.clinic_id 
            LEFT JOIN doctor d ON a.doctor_id = d.doc_id
            ORDER BY a.booking_date DESC";
    $result = $conn->query($sql);
    
    // Count appointments by status - normalized to match database enum values
    $countSql = "SELECT 
        CASE 
            WHEN status IS NULL OR status = '' OR status = 'pending' THEN 'pending'
            WHEN status = 'confirmed' THEN 'confirmed'
            WHEN status = 'completed' THEN 'completed'
            WHEN status = 'cancelled' THEN 'cancelled'
            ELSE status 
        END as normalized_status, 
        COUNT(*) as count 
        FROM appointments 
        GROUP BY normalized_status";
    $countResult = $conn->query($countSql);
    
    $statusCounts = [
        'pending' => 0,
        'confirmed' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'Total' => 0
    ];
    
    if ($countResult->num_rows > 0) {
        while($row = $countResult->fetch_assoc()) {
            $status = $row['normalized_status'];
            if (isset($statusCounts[$status])) {
                $statusCounts[$status] = $row['count'];
            }
            $statusCounts['Total'] += $row['count'];
        }
    }

    // Function to get display status
    function getDisplayStatus($dbStatus) {
        switch($dbStatus) {
            case 'pending':
            case '':
            case null:
                return 'Pending';
            case 'confirmed':
                return 'Confirmed';
            case 'completed':
                return 'Completed';
            case 'cancelled':
                return 'Cancelled';
            default:
                return 'Pending';
        }
    }

    // Function to get status class
    function getStatusClass($dbStatus) {
        switch($dbStatus) {
            case 'pending':
            case '':
            case null:
                return 'pending';
            case 'confirmed':
                return 'confirmed';
            case 'completed':
                return 'completed';
            case 'cancelled':
                return 'cancelled';
            default:
                return 'pending';
        }
    }

    // Function to format fees
    function formatFees($fees) {
        if ($fees === null || $fees === '' || $fees == 0) {
            return 'Not Set';
        }
        return '₹' . number_format($fees, 0);
    }

    // Function to get doctor name (prioritize from appointments table, fallback to doctors table)
    function getDoctorName($appointmentDoctorName, $doctorTableName) {
        return !empty($appointmentDoctorName) ? $appointmentDoctorName : 
               (!empty($doctorTableName) ? $doctorTableName : 'Not Assigned');
    }

    // Function to get doctor specialization (prioritize from appointments table, fallback to doctors table)
    function getDoctorSpecialization($appointmentDoctorSpec, $doctorTableSpec) {
        return !empty($appointmentDoctorSpec) ? $appointmentDoctorSpec : 
               (!empty($doctorTableSpec) ? $doctorTableSpec : 'Not Specified');
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CureBooking | View Appointments</title>
        <link rel="stylesheet" href="styles.css">
        <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <!-- SIDEBAR -->
        <?php include '../include/sidebar.php'; ?>
        <!-- CONTENT -->
        <section id="content">            
            <!-- NAVBAR -->
            <?php include '../include/top-header.php'; ?>

            <!-- MAIN -->
            <main>
                <div class="container">
                    <header>
                        <h1>Appointment Management</h1>
                        <p class="subtitle">Manage and update the status of patient appointments</p>
                        
                        <div class="status-summary">
                            <div class="status-card pending">
                                <h3>Pending</h3>
                                <p id="pending-count"><?php echo $statusCounts['pending']; ?></p>
                            </div>
                            <div class="status-card confirmed">
                                <h3>Confirmed</h3>
                                <p id="confirmed-count"><?php echo $statusCounts['confirmed']; ?></p>
                            </div>
                            <div class="status-card completed">
                                <h3>Completed</h3>
                                <p id="completed-count"><?php echo $statusCounts['completed']; ?></p>
                            </div>
                            <div class="status-card cancelled">
                                <h3>Cancelled</h3>
                                <p id="cancelled-count"><?php echo $statusCounts['cancelled']; ?></p>
                            </div>
                            <div class="status-card total">
                                <h3>Total</h3>
                                <p id="total-count"><?php echo $statusCounts['Total']; ?></p>
                            </div>
                        </div>
                    </header>
                    
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                            <?php 
                                echo $_SESSION['message']; 
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="appointments-container">
                        <?php if ($result->num_rows > 0): ?>
                            <table class="appointments-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Patient Name</th>
                                        <th>Doctor</th>
                                        <th>Clinic</th>
                                        <th>Date & Time</th>
                                        <th>Appointment Fees</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): 
                                        $displayStatus = getDisplayStatus($row['status']);
                                        $statusClass = getStatusClass($row['status']);
                                        $dbStatus = $row['status'] ?: 'pending';
                                        $clinicName = $row['clinic_name'] ?: 'Not Assigned';
                                        $appointmentFees = formatFees($row['doctor_fees']);
                                        $doctorName = getDoctorName($row['doctor_name'], $row['doctor_name_from_table']);
                                        $doctorSpecialization = getDoctorSpecialization($row['doctor_specialization'], $row['doctor_spec_from_table']);
                                    ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                            <td>
                                                <div class="doctor-info">
                                                    <strong><?php echo htmlspecialchars($doctorName); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($doctorSpecialization); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($clinicName); ?></td>
                                            <td>
                                                <div class="appointment-datetime">
                                                    <span class="date"><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></span>
                                                    <span class="time"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="appointment-fees <?php echo ($row['doctor_fees'] !== null && $row['doctor_fees'] !== '' && $row['doctor_fees'] > 0) ? 'fees-set' : 'fees-not-set'; ?>">
                                                    <?php echo $appointmentFees; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status status-<?php echo $statusClass; ?>">
                                                    <?php echo $displayStatus; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <div class="actions-wrapper">
                                                        <button class="btn-view" onclick="viewAppointmentDetails(
                                                                '<?php echo $row['id']; ?>',
                                                                '<?php echo htmlspecialchars($row['patient_name'], ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($row['patient_phone'], ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($row['patient_email'], ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($doctorName, ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($doctorSpecialization, ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($clinicName, ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($row['clinic_location'] ?: 'Not Available', ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($row['clinic_contact'] ?: 'Not Available', ENT_QUOTES); ?>',
                                                                '<?php echo date('M d, Y', strtotime($row['appointment_date'])); ?>',
                                                                '<?php echo date('h:i A', strtotime($row['appointment_time'])); ?>',
                                                                '<?php echo date('M d, Y', strtotime($row['booking_date'])); ?>',
                                                                '<?php echo $displayStatus; ?>',
                                                                '<?php echo $dbStatus; ?>',
                                                                '<?php echo htmlspecialchars($appointmentFees, ENT_QUOTES); ?>'
                                                            )">
                                                                <i class='bx bx-show'></i> View
                                                            </button>
                                                            
                                                            <?php if($dbStatus == 'pending'): ?>
                                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                                    <input type="hidden" name="action" value="updateStatus">
                                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="confirmed">
                                                                    <button type="submit" class="btn-accept"><i class='bx bx-check'></i> Confirm</button>
                                                                </form>
                                                                
                                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                                    <input type="hidden" name="action" value="updateStatus">
                                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="cancelled">
                                                                    <button type="submit" class="btn-reject"><i class='bx bx-x'></i> Cancel</button>
                                                                </form>
                                                            <?php elseif($dbStatus == 'confirmed'): ?>
                                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                                    <input type="hidden" name="action" value="updateStatus">
                                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="completed">
                                                                    <button type="submit" class="btn-complete"><i class='bx bx-check-double'></i> Complete</button>
                                                                </form>
                                                                
                                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                                    <input type="hidden" name="action" value="updateStatus">
                                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="cancelled">
                                                                    <button type="submit" class="btn-reject"><i class='bx bx-x'></i> Absend</button>
                                                                </form>
                                                        <?php else: ?>
                                                            <button class="btn-disabled" disabled><i class='bx bx-lock'></i> No Actions</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class='bx bx-calendar-x'></i>
                                <h3>No Appointments Found</h3>
                                <p>There are currently no appointments in the system.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Appointment Details Modal -->
                <div id="appointmentModal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <div class="modal-header">
                            <h2>Appointment Details</h2>
                        </div>
                        <div class="details-grid">
                            <div class="detail-item">
                                <div class="detail-label">Patient Name</div>
                                <div id="modalPatientName" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Phone Number</div>
                                <div id="modalPhone" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div id="modalEmail" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Doctor Name</div>
                                <div id="modalDoctorName" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Specialization</div>
                                <div id="modalSpecialization" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Clinic Name</div>
                                <div id="modalClinicName" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Clinic Location</div>
                                <div id="modalClinicLocation" class="detail-value"></div>
                            </div>
                            <!-- <div class="detail-item">
                                <div class="detail-label">Clinic Contact</div>
                                <div id="modalClinicContact" class="detail-value"></div>
                            </div> -->
                            <div class="detail-item">
                                <div class="detail-label">Appointment Date</div>
                                <div id="modalAppointmentDate" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Appointment Time</div>
                                <div id="modalAppointmentTime" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Appointment Fees</div>
                                <div id="modalAppointmentFees" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Booking Date</div>
                                <div id="modalBookingDate" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div id="modalStatus" class="detail-value"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div id="modalActions"></div>
                        </div>
                    </div>
                </div>
            </main>
        </section>

        <script>
            const modal = document.getElementById("appointmentModal");
            const closeBtn = document.getElementsByClassName("close")[0];
            
            function viewAppointmentDetails(id, patientName, patientPhone, patientEmail, doctorName, 
                                    specialization, clinicName, clinicLocation, clinicContact, 
                                    appointmentDate, appointmentTime, bookingDate, status, dbStatus, appointmentFees) {
                // Populate modal with appointment details
                document.getElementById("modalPatientName").textContent = patientName;
                document.getElementById("modalPhone").textContent = patientPhone;
                document.getElementById("modalEmail").textContent = patientEmail;
                document.getElementById("modalDoctorName").textContent = doctorName;
                document.getElementById("modalSpecialization").textContent = specialization;
                document.getElementById("modalClinicName").textContent = clinicName;
                document.getElementById("modalClinicLocation").textContent = clinicLocation;
                // document.getElementById("modalClinicContact").textContent = clinicContact;
                document.getElementById("modalAppointmentDate").textContent = appointmentDate;
                document.getElementById("modalAppointmentTime").textContent = appointmentTime;
                document.getElementById("modalAppointmentFees").textContent = appointmentFees;
                document.getElementById("modalBookingDate").textContent = bookingDate;
                
                // Set status with proper styling
                const statusElem = document.getElementById("modalStatus");
                statusElem.innerHTML = '<span class="status status-' + getStatusClass(dbStatus) + '">' + status + '</span>';
                
                // Generate action buttons based on status
                const actionsContainer = document.getElementById("modalActions");
                actionsContainer.innerHTML = "";
                
                if (dbStatus === 'pending') {
                    // Confirm button
                    const confirmBtn = createActionButton(id, 'confirmed', 'btn-accept', 'bx-check', 'Confirm');
                    actionsContainer.appendChild(confirmBtn);
                    
                    // Cancel button
                    const cancelBtn = createActionButton(id, 'cancelled', 'btn-reject', 'bx-x', 'Cancel');
                    actionsContainer.appendChild(cancelBtn);
                    
                } else if (dbStatus === 'confirmed') {
                    // Complete button
                    const completeBtn = createActionButton(id, 'completed', 'btn-complete', 'bx-check-double', 'Complete');
                    actionsContainer.appendChild(completeBtn);
                    
                    // Cancel button
                    const cancelBtn = createActionButton(id, 'cancelled', 'btn-reject', 'bx-x', 'Cancel');
                    actionsContainer.appendChild(cancelBtn);
                    
                } else {
                    // No actions for completed/cancelled appointments
                    const noActionMsg = document.createElement("p");
                    noActionMsg.textContent = "No actions available for " + status.toLowerCase() + " appointments.";
                    noActionMsg.className = "no-actions";
                    actionsContainer.appendChild(noActionMsg);
                }
                
                // Show modal
                modal.style.display = "block";
            }
            
            function createActionButton(id, newStatus, buttonClass, iconClass, buttonText) {
                const form = document.createElement("form");
                form.action = window.location.pathname;
                form.method = "post";
                form.className = "status-form";
                form.style.display = "inline";
                
                const actionInput = document.createElement("input");
                actionInput.type = "hidden";
                actionInput.name = "action";
                actionInput.value = "updateStatus";
                
                const idInput = document.createElement("input");
                idInput.type = "hidden";
                idInput.name = "id";
                idInput.value = id;
                
                const statusInput = document.createElement("input");
                statusInput.type = "hidden";
                statusInput.name = "new_status";
                statusInput.value = newStatus;
                
                const button = document.createElement("button");
                button.type = "submit";
                button.className = buttonClass;
                button.innerHTML = "<i class='bx " + iconClass + "'></i> " + buttonText;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                form.appendChild(statusInput);
                form.appendChild(button);
                
                return form;
            }
            
            function getStatusClass(dbStatus) {
                switch(dbStatus) {
                    case 'pending':
                    case '':
                    case null:
                        return 'pending';
                    case 'confirmed':
                        return 'confirmed';
                    case 'completed':
                        return 'completed';
                    case 'cancelled':
                        return 'cancelled';
                    default:
                        return 'pending';
                }
            }
            
            function closeModal() {
                modal.style.display = "none";
            }
            
            // Event listeners
            closeBtn.onclick = closeModal;
            
            window.onclick = function(event) {
                if (event.target == modal) {
                    closeModal();
                }
            }
            
            // Auto-hide alerts after 5 seconds
            document.addEventListener('DOMContentLoaded', function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    setTimeout(function() {
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 500);
                    }, 5000);
                });
            });
        </script>
    </body>
</html>