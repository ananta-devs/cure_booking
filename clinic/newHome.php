<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthCare Clinic Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            overflow: hidden;
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            height: 100vh;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            align-items: center;
            padding: 0 1rem;
            z-index: 1001;
        }

        .mobile-logo {
            font-size: 1.2rem;
            font-weight: 600;
            color: #4a5568;
        }

        .hamburger {
            width: 24px;
            height: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
        }

        .hamburger span {
            display: block;
            height: 3px;
            background: #4a5568;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .logo {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            flex-shrink: 0;
        }

        .logo h1 {
            font-size: 1.5rem;
            color: #2d3748;
        }

        .nav-menu {
            list-style: none;
            padding: 1rem 0;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            z-index: 900;
        }

        .clinic-info h2 {
            font-size: 1.5rem;
            color: #2d3748;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
        }

        .clinic-info p {
            color: #718096;
            font-size: 0.9rem;
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

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            margin-top: 80px;
            height: calc(100vh - 80px);
            overflow-y: auto;
            padding: 2rem;
        }

        .content-section {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: #718096;
            font-size: 1.1rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #4a5568;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .trend-up {
            color: #38a169;
        }

        .trend-down {
            color: #e53e3e;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            background: white;
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            font-size: 1rem;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Activity Section */
        .activity-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 1.5rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: #718096;
            font-size: 0.9rem;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            .sidebar {
                left: -280px;
                transition: left 0.3s ease;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .top-header {
                left: 0;
                padding: 0 1rem;
            }
            
            .main-content {
                margin-left: 0;
                margin-top: 140px;
                padding: 1rem;
                height: calc(100vh - 140px);
            }
            
            .clinic-info h2 {
                font-size: 1.2rem;
            }
            
            .current-date .date {
                font-size: 1rem;
            }
            
            .header {
                padding: 1.5rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .activity-section {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0.5rem;
                margin-top: 140px;
                height: calc(100vh - 140px);
            }
            
            .top-header {
                flex-direction: column;
                height: 100px;
                padding: 1rem;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .clinic-info, .current-date {
                text-align: center;
            }
            
            .clinic-info h2 {
                font-size: 1.1rem;
                margin-bottom: 0.1rem;
            }
            
            .clinic-info p {
                font-size: 0.8rem;
            }
            
            .current-date .date {
                font-size: 0.9rem;
            }
            
            .current-date .time {
                font-size: 0.8rem;
            }
            
            .main-content {
                margin-top: 160px;
                height: calc(100vh - 160px);
            }
            
            .header {
                padding: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .activity-section {
                padding: 1rem;
            }
        }

        /* Custom scrollbar for main content */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }

        .main-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .main-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        .main-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Custom scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #f7fafc;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="mobile-logo">🏥 HealthCare</div>
    </header>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="logo">
                <h1>🏥 HealthCare Clinic</h1>
            </div>
            
            <ul class="nav-menu">
                <li><a href="#" class="nav-link active" data-section="dashboard"><i class="fas fa-home"></i>Dashboard</a></li>
                <li><a href="#" class="nav-link" data-section="manage-doctors"><i class="fas fa-user-md"></i>Manage Doctors</a></li>
                <li><a href="#" class="nav-link" data-section="lab-bookings"><i class="fas fa-flask"></i>Lab Bookings</a></li>
                <li><a href="#" class="nav-link" data-section="settings"><i class="fas fa-cog"></i>Settings</a></li>
            </ul>
            
            <div class="sign-out">
                <a href="#" class="nav-link" id="signOutBtn"><i class="fas fa-sign-out-alt"></i>Sign Out</a>
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
        
        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section active">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">127</div>
                        <div class="stat-label">Total Patients Today</div>
                        <div class="stat-trend trend-up">↗ +12% from yesterday</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">8</div>
                        <div class="stat-label">Active Doctors</div>
                        <div class="stat-trend trend-up">↗ All doctors available</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">34</div>
                        <div class="stat-label">Lab Tests Pending</div>
                        <div class="stat-trend trend-down">↘ -5 from morning</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">$12,450</div>
                        <div class="stat-label">Today's Revenue</div>
                        <div class="stat-trend trend-up">↗ +8% from average</div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-section">
                    <h2 class="section-title">Recent Activity</h2>
                    <div class="activity-item">
                        <div class="activity-icon" style="background: linear-gradient(135deg, #e6fffa, #b2f5ea); color: #319795;">👨‍⚕️</div>
                        <div class="activity-content">
                            <div class="activity-title">Dr. Johnson completed consultation with Patient #1247</div>
                            <div class="activity-time">2 minutes ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon" style="background: linear-gradient(135deg, #fef5e7, #fad089); color: #d69e2e;">🧪</div>
                        <div class="activity-content">
                            <div class="activity-title">Lab results ready for Patient #1245</div>
                            <div class="activity-time">15 minutes ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon" style="background: linear-gradient(135deg, #f0fff4, #c6f6d5); color: #38a169;">📅</div>
                        <div class="activity-content">
                            <div class="activity-title">New appointment scheduled with Dr. Williams</div>
                            <div class="activity-time">32 minutes ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon" style="background: linear-gradient(135deg, #fed7d7, #feb2b2); color: #e53e3e;">🚨</div>
                        <div class="activity-content">
                            <div class="activity-title">Emergency patient admitted to Room 205</div>
                            <div class="activity-time">1 hour ago</div>
                        </div>
                    </div>
                </div>

                <!-- Extra content to demonstrate scrolling -->
                <div class="activity-section" style="margin-top: 2rem;">
                    <h2 class="section-title">Additional Information</h2>
                    <div style="padding: 1rem 0;">
                        <p style="margin-bottom: 1rem; color: #4a5568;">This section demonstrates the scrollable main content area. The sidebar remains fixed while you can scroll through this content.</p>
                        <div style="height: 200px; background: linear-gradient(135deg, #f7fafc, #edf2f7); border-radius: 12px; margin: 1rem 0; display: flex; align-items: center; justify-content: center; color: #718096;">
                            <p>Sample content block</p>
                        </div>
                        <div style="height: 200px; background: linear-gradient(135deg, #e6fffa, #b2f5ea); border-radius: 12px; margin: 1rem 0; display: flex; align-items: center; justify-content: center; color: #319795;">
                            <p>Another content block</p>
                        </div>
                        <div style="height: 200px; background: linear-gradient(135deg, #fef5e7, #fad089); border-radius: 12px; margin: 1rem 0; display: flex; align-items: center; justify-content: center; color: #d69e2e;">
                            <p>More scrollable content</p>
                        </div>
                        <div style="height: 200px; background: linear-gradient(135deg, #f0fff4, #c6f6d5); border-radius: 12px; margin: 1rem 0; display: flex; align-items: center; justify-content: center; color: #38a169;">
                            <p>Final content block</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Placeholder sections for other content -->
            <div id="manage-doctors-section" class="content-section">
                <div class="header">
                    <h1>Manage Doctors</h1>
                    <p>Add, edit, and manage doctor information</p>
                </div>
            </div>

            <div id="lab-bookings-section" class="content-section">
                <div class="header">
                    <h1>Lab Bookings</h1>
                    <p>Manage laboratory test bookings and results</p>
                </div>
            </div>

            <div id="settings-section" class="content-section">
                <div class="header">
                    <h1>Settings</h1>
                    <p>Configure system settings and preferences</p>
                </div>
            </div>

        </main>
    </div>

    <script>
        // Navigation functionality
        const navLinks = document.querySelectorAll('.nav-link[data-section]');
        const contentSections = document.querySelectorAll('.content-section');
        const signOutBtn = document.getElementById('signOutBtn');
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Date and time update function
        function updateDateTime() {
            const now = new Date();
            const dateOptions = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            const timeOptions = { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true
            };
            
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', dateOptions);
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', timeOptions);
        }

        // Update date and time immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Handle hamburger menu
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : 'auto';
        });

        // Handle sidebar overlay click
        sidebarOverlay.addEventListener('click', closeSidebar);

        // Close sidebar function
        function closeSidebar() {
            hamburger.classList.remove('active');
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Handle navigation
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remove active class from all links
                navLinks.forEach(navLink => navLink.classList.remove('active'));
                
                // Add active class to clicked link
                link.classList.add('active');
                
                // Hide all sections
                contentSections.forEach(section => {
                    section.classList.remove('active');
                });
                
                // Show selected section
                const targetSection = document.getElementById(link.dataset.section + '-section');
                if (targetSection) {
                    setTimeout(() => {
                        targetSection.classList.add('active');
                    }, 150);
                }

                // Close sidebar on mobile after navigation
                if (window.innerWidth <= 768) {
                    setTimeout(closeSidebar, 300);
                }
            });
        });

        // Handle sign out
        signOutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('Are you sure you want to sign out?')) {
                alert('Signing out... (In a real application, this would redirect to login page)');
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });

        // Add keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });
    </script>
</body>
</html>