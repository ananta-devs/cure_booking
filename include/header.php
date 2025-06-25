<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }

    /* Header Styles */
    header {
        background-color: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        position: sticky;
        top: 0;
        z-index: 1000;
        transition: box-shadow 0.3s ease-in-out;
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
    }

    .logo {
        font-size: 24px;
        font-weight: 700;
        color: #3b82f6;
        display: flex;
        align-items: center;
        text-decoration: none;
    }

    .logo img {
        height: 48px;
        width: 48px;
        margin-right: 10px;
    }

    .logo span {
        color: #1f2937;
    }

    .header-left {
        display: flex;
        align-items: center;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .nav-container {
        flex: 1;
        display: flex;
        justify-content: center;
    }

    .nav-links {
        display: flex;
        list-style: none;
        align-items: center;
        gap: 20px;
    }

    .nav-links a {
        text-decoration: none;
        color: #374151;
        padding: 8px 0;
        font-weight: 600;
        font-size: 15px;
        transition: color 0.3s ease, border-bottom 0.3s;
        position: relative;
    }

    .nav-links a:hover {
        color: #3b82f6;
    }

    .nav-links a::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -2px;
        width: 0;
        height: 2px;
        background-color: #3b82f6;
        transition: width 0.3s ease;
    }

    .nav-links a:hover::after,
    .nav-links a.active::after {
        width: 100%;
    }

    .nav-links a.active {
        color: #3b82f6;
        font-weight: 600;
    }

    .login-btn {
        background-color: #3b82f6;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        transition: background-color 0.3s ease;
    }

    .login-btn:hover {
        background-color: #2563eb;
    }

    .user-profile {
        display: flex;
        align-items: center;
    }

    .user-name {
        color: #111827;
        font-weight: 500;
        margin-right: 10px;
        font-size: 14px;
    }

    .user-dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #f9f9f9;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
        z-index: 1;
    }

    .dropdown-content a {
        color: black;
        font-size: 15px;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        letter-spacing: 0;
        transition: background-color 0.3s, letter-spacing 0.3s ease;
    }

    .dropdown-content a:hover {
        background-color: #f1f1f1;
        letter-spacing: 0.5px;
    }

    .show_dropdown {
        display: block;
    }

    .user-dropdown:hover .dropdown-content {
        display: block;
    }

    .arrow {
        display: inline-block;
        transition: transform 0.5s ease;
        margin-left: 5px;
    }

    /* Rotate arrow when hovered */
    .user-dropdown:hover .arrow {
        transform: rotate(180deg);
    }


    .user-btn {
        background-color: #3b82f6;
        color: white;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .user-btn:hover {
        background-color: #2563eb;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hamburger-menu {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 21px;
            cursor: pointer;
            margin-right: 15px;
        }

        @media (max-width: 768px) {
            .logo img {
                display: none;
            }
        }

        .close-menu {
            display: none;
            font-size: 24px;
            cursor: pointer;
            position: absolute;
            top: 20px;
            left: 20px;
            color: #3b82f6;
        }

        @media (max-width: 768px) {
            .close-menu {
                display: block;
            }
        }


        .hamburger-menu span {
            display: block;
            height: 3px;
            width: 100%;
            background-color: #3b82f6;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .header-container {
            position: relative;
        }

        .nav-container {
            position: fixed;
            top: 0;
            left: -100%;
            width: 80%;
            height: 100vh;
            background-color: white;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease;
            padding: 80px 20px 20px;
            overflow-y: auto;
            z-index: 1000;
        }

        .nav-container.active {
            left: 0;
        }

        .nav-links {
            flex-direction: column;
            gap: 15px;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }

        .overlay.active {
            display: block;
        }

        .hamburger-menu.active span:nth-child(1) {
            transform: translateY(9px) rotate(45deg);
        }

        .hamburger-menu.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-menu.active span:nth-child(3) {
            transform: translateY(-9px) rotate(-45deg);
        }
    }

    /* Show full name by default */
    .user-name.full-name {
        display: inline;
    }

    /* Hide initials by default */
    .user-name.short-name {
        display: none;
    }

    /* On mobile, show initials instead */
    @media (max-width: 768px) {
        .user-name.full-name {
            display: none;
        }

        .user-name.short-name {
            display: inline;
        }
    }
</style>


<!-- Header -->
<header>
    <div class="container header-container">
        <div class="header-left">
            <div class="hamburger-menu" id="hamburgerMenu">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <a href="http://localhost/cure_booking/index.php" class="logo">
                <img src="http://localhost/cure_booking/assets/curebookinglogo.png" alt="CureBooking Logo">
                <div>
                    <p>Cure<span>Booking</span></p>
                </div>
            </a>
        </div>
        <div class="nav-container" id="navContainer">
            <div class="close-menu" id="closeMenu">✖</div>
            <ul class="nav-links">
                <li><a href="http://localhost/cure_booking/index.php">Home</a></li>
                <li><a href="http://localhost/cure_booking/find-doctor/doctors.php">Find Doctors</a></li>
                <li><a href="#">Telemedicine</a></li>
                <li><a href="http://localhost/cure_booking/medicines/medicines.php">Medicines</a></li>
                <li><a href="http://localhost/cure_booking/lab-new/lab.php">Lab Tests</a></li>
                <li><a href="http://localhost/cure_booking/find-clinic/clinics.php">Clinics</a></li>
            </ul>
        </div>
        <div class="header-right">
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <!-- User is logged in - show user profile -->
                <?php
                // Function to get short name (initials with dots)
                function getShortName($fullName)
                {
                    // Clean extra spaces
                    $fullName = trim(preg_replace('/\s+/', ' ', $fullName));

                    // Split the name into words
                    $words = explode(' ', $fullName);
                    $initials = '';

                    // Loop to get each initial
                    foreach ($words as $word) {
                        if (!empty($word)) {
                            $initials .= strtoupper(substr($word, 0, 1)) . '. ';
                        }
                    }

                    return trim($initials);
                }

                // Fetch full name from session
                $fullName = $_SESSION['user_name'];

                // Get the short name using the function
                $initials = getShortName($fullName);
                ?>

                <div class="user-profile">
                    <!-- Full Name (Visible on Desktop) -->
                    <span class="user-name full-name"><?php echo htmlspecialchars($fullName); ?></span>

                    <!-- Compressed Name (Visible on Mobile) -->
                    <span class="user-name short-name"><?php echo htmlspecialchars($initials); ?></span>

                    <div class="user-dropdown">
                        <button class="user-btn">
                            <?php echo htmlspecialchars(substr($fullName, 0, 1)); ?>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="dropdown-content" id="userDropdownContent">
                            <a href="http://localhost/cure_booking/user/profile_card.php">My Profile</a>
                            <a href="http://localhost/cure_booking/user/my-appointments.php">My Appointments</a>
                            <a href="http://localhost/cure_booking/user/my-orders.php">My Orders</a>
                            <a href="http://localhost/cure_booking/user/logout.php">Logout</a>
                        </div>
                    </div>
                </div>



            <?php else: ?>
                <!-- User is not logged in - show login button -->
                <a href="http://localhost/cure_booking/user/login.php">
                    <button class="login-btn">Login / Signup</button>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="overlay" id="overlay"></div>
</header>
<script>
    // Mobile Menu Toggle
    const hamburgerMenu = document.getElementById("hamburgerMenu");
    const navContainer = document.getElementById("navContainer");
    const overlay = document.getElementById("overlay");
    const closeMenu = document.getElementById("closeMenu");
    // Profile Dropdown Toggle
    const userBtn = document.querySelector(".user-btn");
    const userDropdownContent = document.getElementById("userDropdownContent");

    // Toggle dropdown on profile button click (arrow click also works)
    userBtn.addEventListener("click", function(e) {
        e.stopPropagation(); // Prevent the event from reaching window
        userDropdownContent.classList.toggle("show_dropdown"); // Toggle open/close
    });

    // Close dropdown when clicking outside
    window.addEventListener("click", function() {
        userDropdownContent.classList.remove("show_dropdown"); // Close if open
    });

    // Optional: Prevent closing when clicking inside dropdown
    userDropdownContent.addEventListener("click", function(e) {
        e.stopPropagation();
    });

    // Close menu when clicking the close icon
    closeMenu.addEventListener("click", function() {
        hamburgerMenu.classList.remove("active");
        navContainer.classList.remove("active");
        overlay.classList.remove("active");
        document.body.style.overflow = "";
    });


    hamburgerMenu.addEventListener("click", function() {
        this.classList.toggle("active");
        navContainer.classList.toggle("active");
        overlay.classList.toggle("active");
        document.body.style.overflow = navContainer.classList.contains("active") ?
            "hidden" :
            "";
    });

    overlay.addEventListener("click", function() {
        hamburgerMenu.classList.remove("active");
        navContainer.classList.remove("active");
        this.classList.remove("active");
        document.body.style.overflow = "";
    });

    // Close menu when clicking on links
    const navLinks = document.querySelectorAll(".nav-links a");
    navLinks.forEach((link) => {
        link.addEventListener("click", function() {
            hamburgerMenu.classList.remove("active");
            navContainer.classList.remove("active");
            overlay.classList.remove("active");
            document.body.style.overflow = "";
        });
    });

    // Set active nav link based on current URL - Improved version
    document.addEventListener("DOMContentLoaded", function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll(".nav-links a");

        navLinks.forEach(link => {
            const linkPath = new URL(link.href).pathname;

            // Check if the current path includes the link path
            // Or if we're on the index page and the link is to the home page
            if (currentPath.includes(linkPath) && linkPath !== "/" && linkPath !== "/index.php") {
                link.classList.add("active");
            } else if ((currentPath === "/" || currentPath.endsWith("index.php")) &&
                (linkPath === "/" || linkPath.endsWith("index.php"))) {
                link.classList.add("active");
            }
        });
    });
</script>