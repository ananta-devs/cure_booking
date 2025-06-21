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

    // Get all appointments ordered by date (newest first)
    $sql = "SELECT * FROM appointments ORDER BY booking_date DESC";
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
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Appointment Management</title>
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
                                        <th>Specialization</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): 
                                        $displayStatus = getDisplayStatus($row['status']);
                                        $statusClass = getStatusClass($row['status']);
                                        $dbStatus = $row['status'] ?: 'pending';
                                    ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['doctor_specialization']); ?></td>
                                            <td>
                                                <?php 
                                                    echo date('M d, Y', strtotime($row['appointment_date'])) . ' at ' . 
                                                        date('h:i A', strtotime($row['appointment_time'])); 
                                                ?>
                                            </td>
                                            <td>
                                                <span class="status status-<?php echo $statusClass; ?>">
                                                    <?php echo $displayStatus; ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <button class="btn-view" onclick="viewAppointmentDetails(<?php 
                                                    echo json_encode([
                                                        'id' => $row['id'],
                                                        'patient_name' => $row['patient_name'],
                                                        'patient_phone' => $row['patient_phone'],
                                                        'patient_email' => $row['patient_email'],
                                                        'doctor_name' => $row['doctor_name'],
                                                        'doctor_specialization' => $row['doctor_specialization'],
                                                        'appointment_date' => date('M d, Y', strtotime($row['appointment_date'])),
                                                        'appointment_time' => date('h:i A', strtotime($row['appointment_time'])),
                                                        'booking_date' => date('M d, Y', strtotime($row['booking_date'])),
                                                        'status' => $displayStatus,
                                                        'db_status' => $dbStatus
                                                    ]); 
                                                ?>)">
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
                                                        <button type="submit" class="btn-reject"><i class='bx bx-x'></i> Cancel</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn-disabled" disabled><i class='bx bx-lock'></i> No Actions</button>
                                                <?php endif; ?>
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
                            <p id="modalAppointmentId" class="subtitle"></p>
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
                                <div class="detail-label">Appointment Date</div>
                                <div id="modalAppointmentDate" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Appointment Time</div>
                                <div id="modalAppointmentTime" class="detail-value"></div>
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
                                    specialization, appointmentDate, appointmentTime, bookingDate, status) {
                document.getElementById("modalAppointmentId").textContent = "ID: " + id;
                document.getElementById("modalPatientName").textContent = patientName;
                document.getElementById("modalPhone").textContent = patientPhone;
                document.getElementById("modalEmail").textContent = patientEmail;
                document.getElementById("modalDoctorName").textContent = doctorName;
                document.getElementById("modalSpecialization").textContent = specialization;
                document.getElementById("modalAppointmentDate").textContent = appointmentDate;
                document.getElementById("modalAppointmentTime").textContent = appointmentTime;
                document.getElementById("modalBookingDate").textContent = bookingDate;
                
                const statusElem = document.getElementById("modalStatus");
                statusElem.textContent = status;
                statusElem.className = "detail-value status status-" + status.toLowerCase();
                
                const actionsContainer = document.getElementById("modalActions");
                actionsContainer.innerHTML = "";
                
                if (status === "Pending") {
                    // Accept button
                    const acceptForm = document.createElement("form");
                    acceptForm.action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
                    acceptForm.method = "post";
                    acceptForm.className = "status-form";
                    
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
                    statusInput.value = "Accepted";
                    
                    const acceptBtn = document.createElement("button");
                    acceptBtn.type = "submit";
                    acceptBtn.className = "btn-accept";
                    acceptBtn.innerHTML = "<i class='bx bx-check'></i> Accept";
                    
                    acceptForm.appendChild(actionInput);
                    acceptForm.appendChild(idInput);
                    acceptForm.appendChild(statusInput);
                    acceptForm.appendChild(acceptBtn);
                    
                    // Reject button
                    const rejectForm = document.createElement("form");
                    rejectForm.action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
                    rejectForm.method = "post";
                    rejectForm.className = "status-form";
                    
                    const actionInput2 = document.createElement("input");
                    actionInput2.type = "hidden";
                    actionInput2.name = "action";
                    actionInput2.value = "updateStatus";
                    
                    const idInput2 = document.createElement("input");
                    idInput2.type = "hidden";
                    idInput2.name = "id";
                    idInput2.value = id;
                    
                    const statusInput2 = document.createElement("input");
                    statusInput2.type = "hidden";
                    statusInput2.name = "new_status";
                    statusInput2.value = "Rejected";
                    
                    const rejectBtn = document.createElement("button");
                    rejectBtn.type = "submit";
                    rejectBtn.className = "btn-reject";
                    rejectBtn.innerHTML = "<i class='bx bx-x'></i> Reject";
                    
                    rejectForm.appendChild(actionInput2);
                    rejectForm.appendChild(idInput2);
                    rejectForm.appendChild(statusInput2);
                    rejectForm.appendChild(rejectBtn);
                    
                    actionsContainer.appendChild(acceptForm);
                    actionsContainer.appendChild(rejectForm);
                } else if (status === "Accepted") {
                    // Complete button
                    const completeForm = document.createElement("form");
                    completeForm.action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
                    completeForm.method = "post";
                    completeForm.className = "status-form";
                    
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
                    statusInput.value = "Completed";
                    
                    const completeBtn = document.createElement("button");
                    completeBtn.type = "submit";
                    completeBtn.className = "btn-complete";
                    completeBtn.innerHTML = "<i class='bx bx-check-double'></i> Complete";
                    
                    completeForm.appendChild(actionInput);
                    completeForm.appendChild(idInput);
                    completeForm.appendChild(statusInput);
                    completeForm.appendChild(completeBtn);
                    
                    // Absent button
                    const absentForm = document.createElement("form");
                    absentForm.action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
                    absentForm.method = "post";
                    absentForm.className = "status-form";
                    
                    const actionInput2 = document.createElement("input");
                    actionInput2.type = "hidden";
                    actionInput2.name = "action";
                    actionInput2.value = "updateStatus";
                    
                    const idInput2 = document.createElement("input");
                    idInput2.type = "hidden";
                    idInput2.name = "id";
                    idInput2.value = id;
                    
                    const statusInput2 = document.createElement("input");
                    statusInput2.type = "hidden";
                    statusInput2.name = "new_status";
                    statusInput2.value = "Absent";
                    
                    const absentBtn = document.createElement("button");
                    absentBtn.type = "submit";
                    absentBtn.className = "btn-reject";
                    absentBtn.innerHTML = "<i class='bx bx-x'></i> Absent";
                    
                    absentForm.appendChild(actionInput2);
                    absentForm.appendChild(idInput2);
                    absentForm.appendChild(statusInput2);
                    absentForm.appendChild(absentBtn);
                    
                    actionsContainer.appendChild(completeForm);
                    actionsContainer.appendChild(absentForm);
                } else {
                    // No actions for completed, rejected, or absent appointments
                    const noActionMsg = document.createElement("p");
                    noActionMsg.textContent = "No actions available for " + status.toLowerCase() + " appointments.";
                    noActionMsg.className = "no-actions";
                    actionsContainer.appendChild(noActionMsg);
                }
                
                modal.style.display = "block";
            }
            
            function createStatusForm(id, status) {
                const form = document.createElement("form");
                form.action = window.location.pathname;
                form.method = "post";
                form.className = "status-form";
                
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
                statusInput.value = status;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                form.appendChild(statusInput);
                
                return form;
            }
            
            function closeModal() {
                modal.style.display = "none";
            }
            
            closeBtn.onclick = closeModal;
            
            window.onclick = function(event) {
                if (event.target == modal) {
                    closeModal();
                }
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    // Fade out alerts after 5 seconds
                    setTimeout(function() {
                        alert.style.opacity = '0';
                        // Remove from DOM after fade animation completes
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 500);
                    }, 5000);
                });
            });
        </script>
    </body>
</html>