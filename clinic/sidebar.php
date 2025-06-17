<!-- <aside class="sidebar" id="sidebar">
    <div class="logo">
        <h1>🏥 CureBooking</h1>
    </div>
            
    <nav>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="#" class="nav-link active" data-section="dashboard">
                    <i class="fa fa-line-chart"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-section="doctors">
                    <i class="fa fa-user"></i>
                    Manage Doctors
                </a>
            </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="lab">
                        <i class="fa fa-flask"></i>
                        Lab Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="settings">
                        <i class="fa fa-cog"></i>
                        Settings
                    </a>
                </li>
            </ul>
                    
            <div class="sign-out">
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link" id="signOutBtn">
                            <i class="fa fa-sign-out"></i>
                                Sign Out
                        </a>
                    </li>
                </ul>
            </div>
    </nav>
</aside> -->

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

    // Handle sign out
    // signOutBtn.addEventListener("click", (e) => {
    //     e.preventDefault();
    //     if (confirm("Are you sure you want to sign out?")) {
    //         alert(
    //             "Signing out... (In a real application, this would redirect to login page)"
    //         );
    //     }
    // });

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
