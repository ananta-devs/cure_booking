<?php
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cure_booking";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Fetch doctors from database (limit to 6 for the homepage)
    $stmt = $pdo->prepare("SELECT id, doc_name, doc_specia, doc_img, fees FROM doctor LIMIT 6");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Find Doctors & Book Appointments Online</title>
    <!-- <link rel="icon" href="assets/logo.png"> -->
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include './include/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Your Health, Our Priority</h1>
                <p>Find and book appointments with doctors, get online consultation, order medicines, book lab tests, and more.</p>
                
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search doctors, clinics, hospitals, etc.">
                    <button class="search-btn">Search</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <a href="http://localhost/cure_booking/find-doctor/doctors.php">
                        <div class="service-img">
                            <img src="http://localhost/cure_booking/assets/doctor-img.jpg" alt="Find Doctors Near You">
                        </div>
                        <div class="service-content">
                            <h3>Find Doctors Near You</h3>
                            <p>Book appointments with qualified doctors</p>
                        </div>
                    </a>
                </div>
                
                <div class="service-card">
                    <a href="http://localhost/cure_booking/medicines/medicines.php">
                        <div class="service-img">
                            <img src="http://localhost/cure_booking/assets/medicine-img.jpg" alt="Medicines">
                        </div>
                        <div class="service-content">
                            <h3>Medicines</h3>
                            <p>Order medicines and health products</p>
                        </div>
                    </a>
                </div>
                
                <div class="service-card">
                    <a href="http://localhost/cure_booking/find-doctor/doctors.php">
                        <div class="service-img">
                            <img src="http://localhost/cure_booking/assets/lab-img.jpg" alt="Lab Tests">
                        </div>
                        <div class="service-content">
                            <h3>Lab Tests</h3>
                            <p>Book tests and get samples collected</p>
                        </div>
                    </a>
                </div>

                <div class="service-card">
                    <a href="http://localhost/cure_booking/surgery/surgery.php">
                        <div class="service-img">
                            <img src="http://localhost/cure_booking/assets/surgery-img.jpg" alt="Surgery">
                        </div>
                        <div class="service-content">
                            <h3>Surgeries</h3>
                            <p>Book consultations with top surgeons</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section class="doctors">
        <div class="container">
            <h2 class="section-title">Popular Doctors</h2>
            <div class="doctors-grid">
                <?php if (!empty($doctors)): ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="doctor-card">
                            <div class="doctor-img">
                                <?php if (!empty($doctor['doc_img'])): ?>
                                    <img src="http://localhost/adminhub/manage-doctors/uploads/<?php echo htmlspecialchars($doctor['doc_img']); ?>" 
                                        alt="<?php echo htmlspecialchars($doctor['doc_name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="doctor-content">
                                <h3><?php echo htmlspecialchars($doctor['doc_name']); ?></h3>
                                <div class="doctor-specia"><?php echo htmlspecialchars($doctor['doc_specia']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="doctor-card">
                        <div class="doctor-img">
                            <img src="assets/icons/cardiology.png" alt="Doctor">
                        </div>
                        <div class="doctor-content">
                            <h3>No doctors available</h3>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <a href="http://localhost/cure_booking/find-doctor/doctors.php" class="all-docs-btn"><u>View All Doctors</u></a>
        </div>
    </section>

    <!-- App Section -->
    <section class="app-section">
        <div class="container app-container">
            <div class="app-content">
                <h2>Download the CureBooking App</h2>
                <p>Book appointments, order medicines, consult with doctors, 
                    and manage your health records - all from the convenience of your smartphone.</p>
                <div class="app-buttons">
                    <a href="#" class="app-btn">
                        <div>
                            <span class="app-btn-text-small">Get it on</span>
                            <span class="app-btn-text-large">Google Play</span>
                        </div>
                    </a>
                    <a href="#" class="app-btn">
                        <div>
                            <span class="app-btn-text-small">Download on the</span>
                            <span class="app-btn-text-large">App Store</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="app-image">
                <img src="/api/placeholder/300/500" alt="Mobile App">
            </div>
        </div>
    </section>

    <?php include './include/footer.php'; ?>
    
    <script>
        // Mobile Menu Toggle
        const hamburgerMenu = document.getElementById("hamburgerMenu");
        const navContainer = document.getElementById("navContainer");
        const overlay = document.getElementById("overlay");

        hamburgerMenu.addEventListener("click", function () {
        this.classList.toggle("active");
        navContainer.classList.toggle("active");
        overlay.classList.toggle("active");
        document.body.style.overflow = navContainer.classList.contains("active")
            ? "hidden"
            : "";
        });

        overlay.addEventListener("click", function () {
        hamburgerMenu.classList.remove("active");
        navContainer.classList.remove("active");
        this.classList.remove("active");
        document.body.style.overflow = "";
        });

        // Close menu when clicking on links
        const navLinks = document.querySelectorAll(".nav-links a");
        navLinks.forEach((link) => {
        link.addEventListener("click", function () {
            hamburgerMenu.classList.remove("active");
            navContainer.classList.remove("active");
            overlay.classList.remove("active");
            document.body.style.overflow = "";
        });
        });
    </script>
</body>
</html>