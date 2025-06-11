<?php
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
?>

<style>
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        /* padding: 20px; */
        width: 100%;
    }
        
    /* Header Styles */
    header {
        background-color: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }

    .logo {
        font-size: 22px;
        font-weight: 700;
        color: #512da8;
        z-index: 101;
        display: flex;
        align-items: center;
        text-decoration: none;
    }

    .logo img {
        height: 38px;
        width: 40px;
        margin-right: 8px;
    }

    .logo span {
        color: #2d2d32;
    }

    .header-left {
        display: flex;
        align-items: center;
    }

    .header-right {
        display: flex;
        align-items: center;
    }

    /* Navigation */
    .nav-container {
        flex: 1;
        display: flex;
        justify-content: center;
    }

    .nav-links {
        display: flex;
        list-style: none;
        align-items: center;
        margin: 0;
        padding: 0;
    }

    .nav-links li {
        margin: 0 15px;
    }

    .nav-links a {
        text-decoration: none;
        color: #2d2d32;
        font-weight: 500;
        padding: 5px 0;
        display: block;
        font-size: 15px;
    }

    .nav-links a:hover {
        color: #512da8;
    }

    .nav-links a.active {
        color: #512da8;
        font-weight: 600;
    }

    .login-btn {
        background-color: #512da8;
        color: white;
        border: none;
        padding: 6px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
    }

    .login-btn:hover {
        background-color: #4527a0;
    }

    /* User Profile Styles */
    .user-profile {
        position: relative;
        display: flex;
        align-items: center;
    }

    .user-name {
        color: #2d2d32;
        font-weight: 500;
        margin-right: 10px;
        font-size: 14px;
    }

    .user-dropdown {
        position: relative;
        display: inline-block;
    }

    .user-btn {
        background-color: #512da8;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .user-btn:hover {
        background-color: #4527a0;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        border-radius: 4px;
        z-index: 1000;
        border: 1px solid #e0e0e0;
    }

    .dropdown-content a {
        color: #2d2d32;
        padding: 10px 15px;
        text-decoration: none;
        display: block;
        font-size: 14px;
        border-bottom: 1px solid #f1f1f1;
    }

    .dropdown-content a:last-child {
        border-bottom: none;
    }

    .dropdown-content a:hover {
        background-color: #f8f9fa;
        color: #512da8;
    }

    .user-dropdown:hover .dropdown-content {
        display: block;
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
            z-index: 101;
            margin-right: 15px;
            padding-left: 5px;
        }
        
        .hamburger-menu span {
            display: block;
            height: 3px;
            width: 100%;
            background-color: #512da8;
            border-radius: 3px;
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
            z-index: 100;
            display: block;
        }
        
        .nav-container.active {
            left: 0;
        }
        
        .nav-links {
            flex-direction: column;
            width: 100%;
        }
        
        .nav-links li {
            margin: 0 0 10px 0;
            width: 100%;
        }
        
        .nav-links a {
            display: block;
            font-size: 16px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 99;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        /* When menu is active */
        .hamburger-menu.active span:nth-child(1) {
            transform: translateY(9px) rotate(45deg);
        }
        
        .hamburger-menu.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger-menu.active span:nth-child(3) {
            transform: translateY(-9px) rotate(-45deg);
        }
        
        .login-btn {
            font-size: 14px;
            margin-right: 5px;
        }

        .user-name {
            display: none;
        }

        .user-btn {
            font-size: 12px;
            padding: 5px 10px;
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
                <!-- <img src="http://localhost/sample/frontend/assets/logo.png" alt="CureBooking Logo"> -->
                Cure<span>Booking</span>
            </a>
        </div>
        <div class="nav-container" id="navContainer">
            <ul class="nav-links">
                <li><a href="http://localhost/cure_booking/find-doctor/doctors.php">Find Doctors</a></li>
                <li><a href="#">Video Consult</a></li>
                <li><a href="http://localhost/cure_booking/medicines/medicines.php">Medicines</a></li>
                <li><a href="http://localhost/cure_booking/lab-new/lab.php">Lab Tests</a></li>
            </ul>
        </div>
        <div class="header-right">
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <!-- User is logged in - show user profile -->
                <div class="user-profile">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <?php echo htmlspecialchars(substr($_SESSION['user_name'], 0, 1)); ?> ▼
                        </button>
                        <div class="dropdown-content">
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