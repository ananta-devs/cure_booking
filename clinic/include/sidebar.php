<?php
    // Get clinic name from session
    $clinic_name = $_SESSION['clinic_name'] ?? 'Clinic Name';
    $clinic_location = $_SESSION['clinic_location'] ?? 'NULL';

    // Get current page to set active state
    $current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Sidebar */
    .sidebar {
        width: 280px;
        background: white;
        box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 1000;
        overflow-y: auto;
    }

    .logo {
        border-bottom: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .logo h1 {
        font-size: 1.5rem;
        color: #2d3748;
        padding: 20px;
    }

    .nav-menu {
        list-style: none;
        flex: 1;
        padding: 0;
        margin: 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        color: #4a5568;
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        border-left: 4px solid transparent;
    }

    /* Enhanced hover effect */
    .nav-link:hover {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        color: #2d3748;
        transform: translateX(8px);
        border-left-color: #667eea;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
    }

    /* Ripple effect on click */
    .nav-link::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(102, 126, 234, 0.2);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.4s ease, height 0.4s ease;
        z-index: 0;
    }

    .nav-link.clicked::before {
        width: 300px;
        height: 300px;
    }

    /* Active state */
    .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-left-color: #4c51bf;
        transform: translateX(8px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
    }

    .nav-link.active:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        transform: translateX(12px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    /* Icon styling */
    .nav-link i {
        margin-right: 0.75rem;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }

    .nav-link:hover i {
        transform: scale(1.1);
    }

    /* Text styling */
    .nav-link span {
        position: relative;
        z-index: 1;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .nav-link:hover span {
        font-weight: 600;
    }

    /* Pulse effect for active item */
    .nav-link.active {
        animation: subtle-pulse 2s infinite;
    }

    @keyframes subtle-pulse {
        0%, 100% {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }
        50% {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
    }

    /* Sign out section */
    .sign-out {
        margin-top: auto;
        border-top: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .sign-out .nav-link:hover {
        background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
        color: #c53030;
        border-left-color: #e53e3e;
    }

    /* Top Header */
    .top-header {
        background: white;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        left: 280px;
        right: 0;
        z-index: 900;
    }

    .current-date {
        text-align: right;
    }

    .current-date .date {
        font-size: 1.2rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.25rem;
    }

    .current-date .time {
        color: #718096;
        font-size: 0.9rem;
    }

    /* Mobile Styles */
    @media (max-width: 768px) {
        .sidebar {
            left: -280px;
            transition: left 0.3s ease;
        }

        .sidebar.active {
            left: 0;
        }
        
        .top-header {
            left: 0;
            padding: 0 1rem;
            top:59px;
        }

        .nav-link:hover {
            transform: translateX(4px);
        }

        .nav-link.active:hover {
            transform: translateX(6px);
        }
    }
    @media (max-width: 480px) {
        .top-header {
            left: 0;
            top: 59px; /* Ensure it's at the top */
            height: 60px; /* Further reduce height for small screens */
            min-height: 60px; /* Ensure minimum height */
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
        /* Adjust date/time display for small screens */
        .current-date .date {
            font-size: 1rem;
            margin-bottom: 0.1rem;
        }
        
        .current-date .time {
            font-size: 0.8rem;
        }
        
    }

    /* Extra small screens */
    @media (max-width: 360px) {
        .top-header {
            height: 50px;
            padding: 0 0.5rem;
        }
        
        .top-header .clinic-info h2 {
            font-size: 1rem;
            max-width: 120px;
        }
        
        .top-header .clinic-info p {
            font-size: 0.7rem;
        }
        
        .current-date .date {
            font-size: 0.9rem;
        }
        
        .current-date .time {
            font-size: 0.7rem;
        }
    }
    /* Smooth transitions for all interactive elements */
    * {
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="logo">
        <h1>CureBooking</h1>
    </div>

    <ul class="nav-menu">
        <li>
            <a
                href="http://localhost/cure_booking/clinic/home.php"
                class="nav-link <?php echo ($current_page == 'home.php') ? 'active' : ''; ?>"
                ><i class="fas fa-home"></i><span>Dashboard</span></a
            >
        </li>
        <li>
            <a
                href="http://localhost/cure_booking/clinic/manage-doctors.php"
                class="nav-link <?php echo ($current_page == 'manage-doctors.php') ? 'active' : ''; ?>"
                ><i class="fas fa-user-md"></i><span>Manage Doctors</span></a
            >
        </li>
        <li>
            <a
                href="http://localhost/cure_booking/clinic/lab-bookings.php"
                class="nav-link <?php echo ($current_page == 'lab-bookings.php') ? 'active' : ''; ?>"
                ><i class="fas fa-flask"></i><span>Lab Bookings</span></a
            >
        </li>
        <li>
            <a
                href="http://localhost/cure_booking/clinic/settings.php"
                class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"
                ><i class="fas fa-cog"></i><span>Settings</span></a
            >
        </li>
    </ul>

    <div class="sign-out">
        <a href="http://localhost/cure_booking/logout.php" class="nav-link" id="signOutBtn"
            ><i class="fas fa-sign-out-alt"></i><span>Sign Out</span></a
        >
    </div>
</nav>

<!-- Top Header -->
<header class="top-header">
    <div class="clinic-info">
        <h2><?php echo htmlspecialchars($clinic_name)?></h2>
        <p><?php echo htmlspecialchars($clinic_location)?></p>
    </div>
    <div class="current-date">
        <div class="date" id="currentDate"></div>
        <div class="time" id="currentTime"></div>
    </div>
</header>

<script>
    // Enhanced navigation functionality with localStorage for active state persistence
    const allNavLinks = document.querySelectorAll(".nav-link");
    const signOutBtn = document.getElementById("signOutBtn");
    const hamburger = document.getElementById("hamburger");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    // Store active navigation state
    function setActiveNav(href) {
        // Store in memory instead of localStorage for this session
        window.activeNavHref = href;
    }

    // Get active navigation state
    function getActiveNav() {
        return window.activeNavHref || window.location.href;
    }

    // Update active navigation on page load
    function updateActiveNav() {
        const currentHref = window.location.href;
        const currentPath = window.location.pathname;
        
        allNavLinks.forEach(link => {
            link.classList.remove('active');
            
            // Check if this link matches current page
            const linkHref = link.getAttribute('href');
            const linkPath = new URL(linkHref, window.location.origin).pathname;
            
            if (linkPath === currentPath || linkHref === currentHref) {
                link.classList.add('active');
            }
        });
    }

    // Add click ripple effect and active state management to all nav links
    allNavLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't prevent default for regular navigation
            
            // Add clicked class for ripple effect
            this.classList.add('clicked');
            
            // Store the clicked navigation
            setActiveNav(this.getAttribute('href'));
            
            // Update active states immediately
            allNavLinks.forEach(navLink => navLink.classList.remove('active'));
            this.classList.add('active');
            
            // Remove the ripple class after animation completes
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 400);
        });

        // Add focus effect for keyboard navigation
        link.addEventListener('focus', function() {
            this.style.outline = '2px solid #667eea';
            this.style.outlineOffset = '2px';
        });

        link.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
    });

    // Initialize active state on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateActiveNav();
    });

    // Date and time update function
    function updateDateTime() {
        const now = new Date();
        const dateOptions = {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        };
        const timeOptions = {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: true,
        };

        const dateElement = document.getElementById("currentDate");
        const timeElement = document.getElementById("currentTime");
        
        if (dateElement) {
            dateElement.textContent = now.toLocaleDateString("en-US", dateOptions);
        }
        if (timeElement) {
            timeElement.textContent = now.toLocaleTimeString("en-US", timeOptions);
        }
    }

    // Update date and time immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Handle hamburger menu
    if (hamburger) {
        hamburger.addEventListener("click", () => {
            hamburger.classList.toggle("active");
            sidebar.classList.toggle("active");
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle("active");
            }
            document.body.style.overflow = sidebar.classList.contains("active")
                ? "hidden"
                : "auto";
        });
    }

    // Handle sidebar overlay click
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", closeSidebar);
    }

    // Close sidebar function
    function closeSidebar() {
        if (hamburger) hamburger.classList.remove("active");
        sidebar.classList.remove("active");
        if (sidebarOverlay) sidebarOverlay.classList.remove("active");
        document.body.style.overflow = "auto";
    }

    // Handle window resize
    window.addEventListener("resize", () => {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });

    // Add keyboard navigation
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeSidebar();
        }
    });

    // Smooth scroll behavior for better UX
    document.documentElement.style.scrollBehavior = 'smooth';

    // Handle back/forward browser navigation
    window.addEventListener('popstate', function() {
        updateActiveNav();
    });
</script>