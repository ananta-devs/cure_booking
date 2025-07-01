<?php
    session_start();
    $isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);
    include '../include/header.php';
    include '../styles.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Find Your Doctor</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <style>
        .success-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in-out;
        }

        .success-modal-content {
            background-color: #ffffff;
            margin: 10% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.4s ease-out;
            overflow: hidden;
        }

        .success-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            animation: bounceIn 0.6s ease-out 0.2s both;
        }

        .success-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0 0 10px 0;
            animation: slideInUp 0.5s ease-out 0.4s both;
        }

        .success-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            animation: slideInUp 0.5s ease-out 0.6s both;
        }

        .success-body {
            padding: 30px;
            text-align: center;
        }

        .success-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .success-detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .success-detail-item:last-child {
            margin-bottom: 0;
        }

        .success-detail-item i {
            color: #4CAF50;
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .success-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        .success-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .success-btn-primary {
            background-color: #4CAF50;
            color: white;
        }

        .success-btn-primary:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .success-btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .success-btn-secondary:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .success-modal-content {
                margin: 20% auto;
                width: 95%;
            }
            
            .success-header {
                padding: 25px 20px;
            }
            
            .success-icon {
                font-size: 3rem;
            }
            
            .success-title {
                font-size: 1.5rem;
            }
            
            .success-body {
                padding: 20px;
            }
            
            .success-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .success-btn {
                width: 100%;
            }
        }
        .search-container select {
            background: url('data:image/svg+xml;utf8,<svg fill="gray" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 12px center;
            background-size: 16px 16px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .search-container {
            display: flex;
            align-items: center;
            max-width: 400px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .search-container select {
            flex: 1;
            padding: 10px 12px;
            border: none;
            outline: none;
            font-size: 14px;
            background-color: #fff;
            color: #374151;
            appearance: none;
        }

        .search-container button {
            background-color: #3b82f6;
            border: none;
            padding: 10px 16px;
            cursor: pointer;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
        }

        .search-container button:hover {
            background-color: #2563eb;
        }

        .search-container select:focus {
            outline: none;
        }

        .search-container i {
            font-size: 18px;
        }

        .modal-content .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;       /* larger size for visibility */
            font-weight: bold;
            color: #3b82f6;
            cursor: pointer;
            transition: color 0.3s ease, transform 0.3s;
            z-index: 10;
        }

        .modal-content .close:hover {
            color: #ef4444;  /* red on hover */
            transform: scale(1.2);
        }

        .doctor-clinics-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .doctor-clinics-section h3 {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .clinics-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .clinic-item {
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-radius: 6px;
            border-left: 3px solid #3B82F6;
        }

        .clinic-name {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }

        .clinic-name i {
            color: #3B82F6;
            font-size: 0.9rem;
        }

        .clinic-name strong {
            color: #2c3e50;
            font-size: 0.95rem;
        }

        .clinic-location {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 20px;
        }

        .clinic-location i {
            color: #6c757d;
            font-size: 0.8rem;
        }

        .clinic-location span {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .clinic-availability {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 20px;
            margin-top: 5px;
        }

        .clinic-availability i {
            color: rgb(23, 176, 59);
            font-size: 0.8rem;
        }

        .clinic-availability span {
            color: #495057;
            font-size: 0.85rem;
        }

        .clinic-availability strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .primary-btn {
            background-color: #3B82F6;
            color: white;
        }

        .primary-btn:hover {
            background-color: #3B82F6;
        }

        @media (max-width: 768px) {

            .clinic-availability,
            .clinic-location {
                margin-left: 15px;
            }

            .clinic-availability {
                margin-top: 8px;
            }

            .clinic-item {
                padding: 10px 12px;
            }

            .clinic-name strong {
                font-size: 0.9rem;
            }

            .clinic-location span,
            .clinic-availability span {
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>

    <script>
        const USER_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const LOGIN_URL = '../user/login.php';
    </script>

    <section class="hero" data-aos="fade-up">
        <div class="container">
            <h1>Find the Right Doctor for Your Needs</h1>
            <p>Connect with top healthcare specialists in your area</p>
            <form class="search-container">
                <select id="search-bar">
                    <option value="">All Specializations</option>
                    <?php
                    $conn = new mysqli("localhost", "root", "", "cure_booking");
                    if (!$conn->connect_error) {
                        $result = $conn->query("SELECT DISTINCT doc_specia FROM doctor ORDER BY doc_specia");
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['doc_specia']) . '">' . htmlspecialchars($row['doc_specia']) . '</option>';
                            }
                        }
                        $conn->close();
                    }
                    ?>
                </select>
                <button type="submit" aria-label="Search"><i class="ri-search-line"></i></button>
            </form>
        </div>
    </section>

    <section class="doctors-section">
        <h2 data-aos="fade">Available Doctors</h2>
        <div id="doctors-container" class="doctors-container"></div>
    </section>

    <!-- Doctor Profile Modal -->
    <div id="doctor-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="modal-doctor-details"></div>
        </div>
    </div>

    <!-- Booking Modal -->
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
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender">
                        <option value="">Select a Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Others</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="clinic">Select Clinic</label>
                    <select name="clinic" id="clinic">
                        <option value="">Select a clinic</option>
                    </select>
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
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn primary-btn">Confirm Booking</button>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="success-modal">
        <div class="success-modal-content">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="success-title">Booking Successful!</h2>
                <p class="success-subtitle">Thank You</p>
            </div>
            <div class="success-body">
                <p>Your appointment has been confirmed successfully. You will receive a confirmation shortly.</p>
                <div id="successDetails" class="success-details">
                    <!-- Appointment details will be populated here -->
                </div>
                <div class="success-actions">
                    <button class="success-btn success-btn-primary" onclick="viewAppointments()">
                        <i class="fas fa-calendar-alt"></i> View My Appointments
                    </button>
                    <button class="success-btn success-btn-secondary" onclick="closeSuccessModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
    <!---AOS Library --->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 1000,
        });
    </script>
</body>
<?php
    include '../include/footer.php';
?>

</html>