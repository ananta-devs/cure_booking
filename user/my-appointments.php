<?php
    // Start session
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: http://localhost/cure_booking/user/login.php");
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

    // Get user's booked appointments (appointments booked BY this user for others)
    $user_id = $_SESSION['user_id'] ?? 0; // Assuming you store user ID in session
    $user_email = $_SESSION['user_email'] ?? ''; // User's email who made the booking

    // Fetch appointments booked by the logged-in user
    $sql = "SELECT * FROM appointments WHERE booked_by_user_id = ? OR booked_by_email = ? ORDER BY booking_date DESC, appointment_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - CureBooking</title>
    <style>
         * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            /* font-family: 'Arial', sans-serif; */
            background-color: #f5f5f5;
            line-height: 1.6;
        }

        .appointments-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            color: #512da8;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        .appointments-grid {
            display: grid;
            gap: 20px;
        }

        .appointment-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #512da8;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .appointment-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .doctor-info {
            flex: 1;
        }

        .doctor-name {
            font-size: 20px;
            font-weight: 600;
            color: #2d2d32;
            margin-bottom: 5px;
        }

        .doctor-specialization {
            color: #512da8;
            font-weight: 500;
            background: #f3f0ff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-block;
        }

        .appointment-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-upcoming {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-completed {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-cancelled {
            background: #ffebee;
            color: #d32f2f;
        }

        .status-past {
            background: #f5f5f5;
            color: #757575;
        }

        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-icon {
            width: 18px;
            height: 18px;
            background: #512da8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            flex-shrink: 0;
        }

        .detail-text {
            color: #555;
        }

        .detail-label {
            font-weight: 500;
            color: #333;
        }

        .no-appointments {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .no-appointments-icon {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-appointments h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .no-appointments p {
            color: #999;
            margin-bottom: 20px;
        }

        .book-appointment-btn {
            background: #512da8;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: background 0.2s ease;
        }

        .book-appointment-btn:hover {
            background: #4527a0;
        }

        .booking-date {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .appointments-container {
                margin: 20px auto;
                padding: 0 15px;
            }

            .appointment-card {
                padding: 20px;
            }

            .appointment-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .appointment-details {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .doctor-name {
                font-size: 18px;
            }
        }
    </style>
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
                    // Determine appointment status based on date
                    $appointment_datetime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
                    $appointment_timestamp = strtotime($appointment_datetime);
                    $current_timestamp = time();
                    
                    if ($appointment_timestamp > $current_timestamp) {
                        $status = 'upcoming';
                        $status_text = 'Upcoming';
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

                            <div class="detail-item">
                                <div class="detail-icon">🏥</div>
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
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-appointments">
                <div class="no-appointments-icon">📋</div>
                <h3>No Appointments Found</h3>
                <p>You haven't booked any appointments for others yet.</p>
                <a href="find-doctor/doctors.php" class="book-appointment-btn">Book Your First Appointment</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add any JavaScript functionality here if needed
        document.addEventListener('DOMContentLoaded', function() {
            // You can add features like:
            // - Cancel appointment functionality
            // - Reschedule appointment
            // - Print appointment details
            // - Download appointment receipt
            
            console.log('My Appointments page loaded');
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>