<?php
    session_start();

    // Database configuration
    $db_config = [
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'cure_booking'
    ];

    function getDbConnection($config) {
        $conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
        if ($conn->connect_error) {
            return false;
        }
        return $conn;
    }

    function validateUser() {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || empty($_SESSION['user_email'])) {
            return false;
        }
        return $_SESSION['user_email'];
    }

    // Handle AJAX cancel appointment request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'cancel_appointment') {
        header('Content-Type: application/json');
        
        $user_email = validateUser();
        if (!$user_email) {
            echo json_encode(['success' => false, 'message' => 'User not logged in']);
            exit();
        }

        $conn = getDbConnection($db_config);
        if (!$conn) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit();
        }

        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        if (!$appointment_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit();
        }

        // Check appointment validity and ownership
        $check_sql = "SELECT status, appointment_date, appointment_time FROM appointments WHERE id = ? AND booked_by_email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $appointment_id, $user_email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            exit();
        }

        $appointment = $result->fetch_assoc();

        // Validate cancellation eligibility
        if (strtolower($appointment['status']) === 'cancelled') {
            echo json_encode(['success' => false, 'message' => 'Appointment already cancelled']);
            exit();
        }

        if (strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']) <= time()) {
            echo json_encode(['success' => false, 'message' => 'Cannot cancel past appointments']);
            exit();
        }

        // Cancel appointment
        $update_sql = "UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND booked_by_email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("is", $appointment_id, $user_email);

        if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
        }
        
        $conn->close();
        exit();
    }

    // Regular page access validation
    $user_email = validateUser();
    if (!$user_email) {
        header('Location: ../login.php');
        exit();
    }

    $conn = getDbConnection($db_config);
    if (!$conn) {
        die("Database connection failed");
    }

    // Fetch appointments
    $sql = "SELECT id, doctor_name, doctor_specialization, clinic_name, patient_name, patient_phone, 
                patient_email, appointment_date, appointment_time, booking_date, booked_by_name, 
                status, created_at 
            FROM appointments 
            WHERE booked_by_email = ? 
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    function getAppointmentStatus($appointment) {
        $db_status = strtolower($appointment['status']);
        $appointment_time = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
        
        if ($db_status === 'cancelled') return ['cancelled', 'Cancelled'];
        if ($db_status === 'completed') return ['completed', 'Completed'];
        if ($db_status === 'no_show') return ['no-show', 'No Show'];
        if ($appointment_time > time()) return ['upcoming', $db_status === 'confirmed' ? 'Confirmed' : 'Pending'];
        return ['past', 'Completed'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | My Appointments</title>
    <link rel="stylesheet" href="appointment.css">
</head>
<body>
    <?php include '../include/header.php'; ?>

    <div class="appointments-container">
        <div class="page-header">
            <h1>My Appointments</h1>
            <p>View and manage appointments you've booked</p>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="appointments-grid">
                <?php while ($appointment = $result->fetch_assoc()): 
                    [$status, $status_text] = getAppointmentStatus($appointment);
                ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div class="doctor-info">
                                <div class="doctor-name">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></div>
                                <div class="doctor-specialization"><?= htmlspecialchars($appointment['doctor_specialization']) ?></div>
                            </div>
                            <div class="appointment-status status-<?= $status ?>">
                                <?= $status_text ?>
                            </div>
                        </div>

                        <div class="appointment-details">
                            <div class="detail-item">
                                <div class="detail-icon">📅</div>
                                <div class="detail-text">
                                    <div class="detail-label">Date</div>
                                    <?= date('M d, Y', strtotime($appointment['appointment_date'])) ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">⏰</div>
                                <div class="detail-text">
                                    <div class="detail-label">Time</div>
                                    <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">👤</div>
                                <div class="detail-text">
                                    <div class="detail-label">Patient</div>
                                    <?= htmlspecialchars($appointment['patient_name']) ?>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-icon">📞</div>
                                <div class="detail-text">
                                    <div class="detail-label">Phone</div>
                                    <?= htmlspecialchars($appointment['patient_phone']) ?>
                                </div>
                            </div>

                            <?php if ($appointment['clinic_name']): ?>
                            <div class="detail-item">
                                <div class="detail-icon">🏥</div>
                                <div class="detail-text">
                                    <div class="detail-label">Clinic</div>
                                    <?= htmlspecialchars($appointment['clinic_name']) ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="detail-item">
                                <div class="detail-icon">🆔</div>
                                <div class="detail-text">
                                    <div class="detail-label">ID</div>
                                    #<?= $appointment['id'] ?>
                                </div>
                            </div>
                        </div>

                        <div class="booking-date">
                            Booked: <?= date('M d, Y', strtotime($appointment['booking_date'])) ?>
                            <?php if ($appointment['booked_by_name']): ?>
                                <small>by: <?= htmlspecialchars($appointment['booked_by_name']) ?></small>
                            <?php endif; ?>
                        </div>

                        <?php if ($status === 'upcoming'): ?>
                        <div class="appointment-actions">
                            <button class="action-btn cancel-btn" onclick="cancelAppointment(<?= $appointment['id'] ?>)">
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
                <p>You haven't booked any appointments yet.</p>
                <a href="../find-doctor/doctors.php" class="book-appointment-btn">Book Appointment</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function cancelAppointment(appointmentId) {
            if (!confirm('Cancel this appointment? This cannot be undone.')) return;
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Cancelling...';
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'cancel_appointment');
            formData.append('appointment_id', appointmentId);
            
            fetch('my-appointments.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Appointment cancelled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                btn.textContent = originalText;
                btn.disabled = false;
            });
        }
    </script>

    <style>
        .cancel-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>