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
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        color: #4a5568;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        background: #f7fafc;
        color: #2d3748;
    }

    .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .nav-link i {
        margin-right: 0.75rem;
    }

    .sign-out {
        margin-top: auto;
        border-top: 1px solid #e2e8f0;
        flex-shrink: 0;
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
        top: 0;
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
        }
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
                class="nav-link active"
                ><i class="fas fa-home"></i>Dashboard</a
            >
        </li>
        <li>
            <a
                href="http://localhost/cure_booking/clinic/manage-doctors.php"
                class="nav-link"
                ><i class="fas fa-user-md"></i>Manage Doctors</a
            >
        </li>
        <li>
            <a
                href="http://localhost/cure_booking/clinic/lab-bookings.php"
                class="nav-link"
                ><i class="fas fa-flask"></i>Lab Bookings</a
            >
        </li>
        <li>
            <a
                href="http://localhost/cure_booking/clinic/settings.php"
                class="nav-link"
                ><i class="fas fa-cog"></i>Settings</a
            >
        </li>
    </ul>

    <div class="sign-out">
        <a href="http://localhost/cure_booking/logout.php" class="nav-link" id="signOutBtn"
            ><i class="fas fa-sign-out-alt"></i>Sign Out</a
        >
    </div>
</nav>

<!-- Top Header -->
<header class="top-header">
    <div class="clinic-info">
        <h2>MediCare Plus Hospital</h2>
        <p>Advanced Healthcare Solutions</p>
    </div>
    <div class="current-date">
        <div class="date" id="currentDate"></div>
        <div class="time" id="currentTime"></div>
    </div>
</header>

<script>
    // Navigation functionality
    const navLinks = document.querySelectorAll(".nav-link[data-section]");
    const contentSections = document.querySelectorAll(".content-section");
    const signOutBtn = document.getElementById("signOutBtn");
    const hamburger = document.getElementById("hamburger");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

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

        document.getElementById("currentDate").textContent =
            now.toLocaleDateString("en-US", dateOptions);
        document.getElementById("currentTime").textContent =
            now.toLocaleTimeString("en-US", timeOptions);
    }

    // Update date and time immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Handle hamburger menu
    hamburger.addEventListener("click", () => {
        hamburger.classList.toggle("active");
        sidebar.classList.toggle("active");
        sidebarOverlay.classList.toggle("active");
        document.body.style.overflow = sidebar.classList.contains("active")
            ? "hidden"
            : "auto";
    });

    // Handle sidebar overlay click
    sidebarOverlay.addEventListener("click", closeSidebar);

    // Close sidebar function
    function closeSidebar() {
        hamburger.classList.remove("active");
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
        document.body.style.overflow = "auto";
    }

    // Handle navigation
    navLinks.forEach((link) => {
        link.addEventListener("click", (e) => {
            e.preventDefault();

            // Remove active class from all links
            navLinks.forEach((navLink) => navLink.classList.remove("active"));

            // Add active class to clicked link
            link.classList.add("active");

            // Hide all sections
            contentSections.forEach((section) => {
                section.classList.remove("active");
            });

            // Show selected section
            const targetSection = document.getElementById(
                link.dataset.section + "-section"
            );
            if (targetSection) {
                setTimeout(() => {
                    targetSection.classList.add("active");
                }, 150);
            }

            // Close sidebar on mobile after navigation
            if (window.innerWidth <= 768) {
                setTimeout(closeSidebar, 300);
            }
        });
    });

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
</script>
