<?php
    // Start session
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../login.php');
        exit();
    }

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cure_booking";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $user_email = $_SESSION['user_email'] ?? '';

    // Validate user email
    if (empty($user_email)) {
        header('Location: ../login.php');
        exit();
    }

    // Fetch user's appointments
    $sql = "SELECT 
                a.id, 
                a.doctor_id, 
                a.doctor_name, 
                a.doctor_specialization, 
                a.clinic_id, 
                a.clinic_name, 
                a.patient_name, 
                a.patient_phone, 
                a.patient_email, 
                a.gender, 
                a.appointment_date, 
                a.appointment_time, 
                a.booking_date, 
                a.booked_by_email, 
                a.booked_by_name, 
                a.status, 
                a.created_at, 
                a.updated_at 
            FROM appointments a 
            WHERE a.booked_by_email = ? 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - CureBooking</title>
    <link rel="stylesheet" href="appointment.css">
</head>
<body>
    <?php include '../include/header.php'; ?>

    <div class="appointments-container">
        <div class="page-header">
            <h1>My Appointments</h1>
            <p>View and manage appointments you've booked for others</p>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="appointments-grid">
                <?php while ($appointment = $result->fetch_assoc()): ?>
                    <?php
                    // Determine appointment status based on date and time
                    $appointment_datetime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
                    $appointment_timestamp = strtotime($appointment_datetime);
                    $current_timestamp = time();
                    
                    // Check database status first, then determine by time
                    $db_status = strtolower($appointment['status']);
                    
                    if ($db_status === 'cancelled') {
                        $status = 'cancelled';
                        $status_text = 'Cancelled';
                    } elseif ($db_status === 'completed') {
                        $status = 'completed';
                        $status_text = 'Completed';
                    } elseif ($db_status === 'no_show') {
                        $status = 'no-show';
                        $status_text = 'No Show';
                    } elseif ($appointment_timestamp > $current_timestamp) {
                        $status = 'upcoming';
                        $status_text = ($db_status === 'confirmed') ? 'Confirmed' : 'Pending';
                    } else {
                        $status = 'past';
                        $status_text = 'Completed';
                    }
                    ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div class="doctor-info">
                                <div class="doctor-name">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                                <div class="doctor-specialization"><?php echo htmlspecialchars($appointment['doctor_specialization']); ?></div>
                            </div>
                            <div class="appointment-status status-<?php echo $status; ?>">
                                <?php echo $status_text; ?>
                            </div>
                        </div>

                        <div class="appointment-details">
                            <div class="detail-item">
                                <div class="detail-icon">📅</div>
                                <div class="detail-text">
                                    <div class="detail-label">Appointment Date</div>
                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">⏰</div>
                                <div class="detail-text">
                                    <div class="detail-label">Time</div>
                                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">👤</div>
                                <div class="detail-text">
                                    <div class="detail-label">Patient Name</div>
                                    <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">📞</div>
                                <div class="detail-text">
                                    <div class="detail-label">Patient Phone</div>
                                    <?php echo htmlspecialchars($appointment['patient_phone']); ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">✉️</div>
                                <div class="detail-text">
                                    <div class="detail-label">Patient Email</div>
                                    <?php echo htmlspecialchars($appointment['patient_email']); ?>
                                </div>
                            </div>

                            <?php if (!empty($appointment['clinic_name'])): ?>
                            <div class="detail-item">
                                <div class="detail-icon">🏥</div>
                                <div class="detail-text">
                                    <div class="detail-label">Clinic</div>
                                    <?php echo htmlspecialchars($appointment['clinic_name']); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="detail-item">
                                <div class="detail-icon">🆔</div>
                                <div class="detail-text">
                                    <div class="detail-label">Appointment ID</div>
                                    #<?php echo $appointment['id']; ?>
                                </div>
                            </div>
                        </div>

                        <div class="booking-date">
                            Booked on: <?php echo date('M d, Y \a\t h:i A', strtotime($appointment['booking_date'])); ?>
                            <?php if (!empty($appointment['booked_by_name'])): ?>
                                <br><small style="color: #512da8;">Booked by: You (<?php echo htmlspecialchars($appointment['booked_by_name']); ?>)</small>
                            <?php endif; ?>
                        </div>

                        <?php if ($status === 'upcoming' && $db_status !== 'cancelled'): ?>
                        <div class="appointment-actions">
                            <button class="action-btn cancel-btn" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)">
                                Cancel Appointment
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-appointments">
                <div class="no-appointments-icon">📋</div>
                <h3>No Appointments Found</h3>
                <p>You haven't booked any appointments for others yet.</p>
                <a href="../find-doctor/doctors.php" class="book-appointment-btn">Book Your First Appointment</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add JavaScript functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('My Appointments page loaded');
            console.log('Total appointments displayed: <?php echo $result->num_rows; ?>');
        });

        // Cancel appointment function
        function cancelAppointment(appointmentId) {
            if (confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
                // Show loading state
                const cancelBtn = event.target;
                const originalText = cancelBtn.textContent;
                cancelBtn.textContent = 'Cancelling...';
                cancelBtn.disabled = true;
                
                fetch('cancel-appointment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        appointment_id: appointmentId
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Appointment cancelled successfully!');
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert('Error cancelling appointment: ' + (data.message || 'Unknown error'));
                        // Restore button state
                        cancelBtn.textContent = originalText;
                        cancelBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the appointment. Please try again.');
                    // Restore button state
                    cancelBtn.textContent = originalText;
                    cancelBtn.disabled = false;
                });
            }
        }

        const style = document.createElement('style');
        style.textContent = `
            .cancel-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
        `;
        document.head.appendChild(style);

        // Print appointment details function
        function printAppointment(appointmentId) {
            window.print();
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>