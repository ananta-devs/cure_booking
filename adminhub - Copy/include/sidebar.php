<!-- Modified sidebar.php -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Poppins:wght@400;500;600;700&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    a {
        text-decoration: none;
    }

    li {
        list-style: none;
    }

    :root {
        --poppins: 'Poppins', sans-serif;
        --lato: 'Lato', sans-serif;

        --light: #F9F9F9;
        --blue: #3C91E6;
        --light-blue: #CFE8FF;
        --grey: #eee;
        --dark-grey: #AAAAAA;
        --dark: #342E37;
        --red: #DB504A;
        --yellow: #FFCE26;
        --light-yellow: #FFF2C6;
        --orange: #FD7238;
        --light-orange: #FFE0D3;
    }

    html {
        overflow-x: hidden;
    }

    body.dark {
        --light: #0C0C1E;
        --grey: #060714;
        --dark: #FBFBFB;
    }

    body {
        background: var(--grey);
        overflow-x: hidden;
    }

    /* SIDEBAR */
    #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 280px;
        height: 100%;
        background: var(--light);
        z-index: 2000;
        font-family: var(--lato);
        transition: .3s ease;
        overflow-x: hidden;
        scrollbar-width: none;
    }
    #sidebar::-webkit-scrollbar {
        display: none;
    }
    #sidebar.hide {
        width: 60px;
        display:none;
    }
    #sidebar .brand {
        font-size: 24px;
        font-weight: 700;
        height: 56px;
        display: flex;
        align-items: center;
        color: var(--blue);
        position: sticky;
        top: 0;
        left: 0;
        background: var(--light);
        z-index: 500;
        padding-bottom: 20px;
        box-sizing: content-box;
    }
    #sidebar .brand .bx {
        min-width: 60px;
        display: flex;
        justify-content: center;
    }
    
    /* Close button in sidebar */
    #sidebar .close-btn {
        position: absolute;
        top: 16px;
        right: 15px;
        font-size: 24px;
        color: var(--dark);
        cursor: pointer;
        display: none; /* Hidden by default */
    }
    #sidebar:not(.hide) .close-btn {
        display: block; /* Show when sidebar is visible */
    }
    
    #sidebar .side-menu {
        width: 100%;
        margin-top: 48px;
    }
    #sidebar .side-menu li {
        min-height: 48px;
        background: transparent;
        margin-left: 6px;
        border-radius: 48px 0 0 48px;
        padding: 4px;
        position: relative;
    }
    #sidebar .side-menu li.active {
        background: var(--grey);
        position: relative;
        height: 50px;
    }
    #sidebar .side-menu li.active::before {
        content: '';
        position: absolute;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        top: -40px;
        right: 0;
        box-shadow: 20px 20px 0 var(--grey);
        z-index: -1;
    }
    #sidebar .side-menu li.active::after {
        content: '';
        position: absolute;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        bottom: -40px;
        right: 0;
        box-shadow: 20px -20px 0 var(--grey);
        z-index: -1;
    }
    #sidebar .side-menu li a {
        width: 100%;
        height: 100%;
        background: var(--light);
        display: flex;
        align-items: center;
        border-radius: 48px;
        font-size: 16px;
        color: var(--dark);
        white-space: nowrap;
        overflow-x: hidden;
    }
    #sidebar .side-menu.top li.active a {
        color: var(--blue);
    }
    #sidebar.hide .side-menu li a {
        width: calc(48px - (4px * 2));
        transition: width .3s ease;
    }
    #sidebar .side-menu li a.logout {
        color: var(--red);
    }
    #sidebar .side-menu.top li a:hover {
        color: var(--blue);
    }
    #sidebar .side-menu li a .bx {
        min-width: calc(60px  - ((4px + 6px) * 2));
        display: flex;
        justify-content: center;
    }

    /* Dropdown menu styles */
    #sidebar .side-menu li.dropdown {
        height: auto;
    }
    
    #sidebar .side-menu li.dropdown > a {
        cursor: pointer;
    }
    
    #sidebar .side-menu li .submenu {
        display: none;
        padding-left: 20px;
        margin-top: 5px;
    }
    
    #sidebar .side-menu li .submenu.show {
        display: block;
    }
    
    #sidebar .side-menu li .submenu li {
        height: 36px;
        margin-bottom: 5px;
    }
    
    #sidebar .side-menu li .submenu li a:hover {
        color: var(--blue);
    }
    
    /* Handle dropdown in collapsed sidebar */
    #sidebar.hide .side-menu li.dropdown:hover .submenu {
        position: absolute;
        left: 60px;
        top: 0;
        width: 200px;
        background: var(--light);
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        z-index: 2001;
        display: block;
        padding: 10px;
    }
    @media screen and (min-width: 769px) {
    /* Keep sidebar visible on large screens */
    #sidebar {
        width: 280px !important; /* Use !important to override any inline styles */
    }
    
    /* Ensure the content is properly shifted with sidebar visible */
    #content {
        width: calc(100% - 280px);
        left: 280px;
    }
}
</style>

<section id="sidebar">
    <!-- Add close button inside sidebar -->
    <i class='bx bx-x close-btn'></i>
    
    <a href="#" class="brand">
        <i class='bx bxs-smile'></i>
        <span class="text">AdminHub</span>
    </a>
    <ul class="side-menu top">
        <li class="active">
            <a href="http://localhost/cure_booking/adminhub/index.php">
                <i class='bx bxs-dashboard'></i>
                <span class="text">Dashboard</span>
            </a>
        </li>
        <li class="dropdown">
            <a>
                <i class='bx bxs-group'></i>
                <span class="text">Appointments</span>
            </a>
            <ul class="submenu">
                <li>
                    <a href="http://localhost/cure_booking/adminhub/manage-appointments/add-appointment.php">
                        <i class='bx bxs-user-plus'></i>
                        <span class="text">Add Appointment</span>
                    </a>
                </li>
                <li>
                    <a href="http://localhost/cure_booking/adminhub/manage-appointments/view_appointments.php">
                        <i class='bx bx-list-ul'></i>
                        <span class="text">Appointments List</span>
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="http://localhost/cure_booking/adminhub/lab-bookings/view_lab_bookings.php">
                <i class='bx bxs-doughnut-chart'></i>
                <span class="text">Lab Bookings</span>
            </a>
        </li>
        <li>
            <a href="http://localhost/cure_booking/adminhub/medicine-orders/view_medicine_orders.php">
                <i class='bx bxs-message-dots'></i>
                <span class="text">Medicine Orders</span>
            </a>
        </li>
        <li class="dropdown">
            <a>
                <i class='bx bxs-group'></i>
                <span class="text">Manage Doctors</span>
            </a>
            <ul class="submenu">
                <li>
                    <a href="http://localhost/cure_booking/adminhub/manage-doctors/add-doctors.php">
                        <i class='bx bxs-user-plus'></i>
                        <span class="text">Add Doctors</span>
                    </a>
                </li>
                <li>
                    <a href="http://localhost/cure_booking/adminhub/manage-doctors/doctors_list.php">
                        <i class='bx bx-list-ul'></i>
                        <span class="text">Doctors List</span>
                    </a>
                </li>
            </ul>
        </li>
        <li class="dropdown">
            <a>
                <i class='bx bxs-group'></i>
                <span class="text">Manage Clinics</span>
            </a>
            <ul class="submenu">
                <li>
                    <a href="http://localhost/cure_booking/adminhub/manage-clinics/add-clinics.php">
                        <i class='bx bxs-user-plus'></i>
                        <span class="text">Add Clinics</span>
                    </a>
                </li>
                <li>
                    <a href="http://localhost/cure_booking/adminhub/manage-clinics/show_clinics.php">
                        <i class='bx bx-list-ul'></i>
                        <span class="text">Clinic List</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
    <ul class="side-menu">
        <li>
            <a href="http://localhost/cure_booking/adminhub/sett/admin.php">
                <i class='bx bxs-cog'></i>
                <span class="text">Settings</span>
            </a>
        </li>
        <li>
            <a href="http://localhost/cure_booking/adminhub/logout.php" class="logout">
                <i class='bx bxs-log-out-circle'></i>
                <span class="text">Logout</span>
            </a>
        </li>
    </ul>
</section>

<script>
    // Handle sidebar toggle - we're keeping only what's needed for the sidebar
    const sidebar = document.getElementById('sidebar');
    const sidebarCloseBtn = document.querySelector('#sidebar .close-btn');

    // Close sidebar when X button is clicked
    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', function() {
            sidebar.classList.add('hide');
            // Show the hamburger menu in top-header when closing sidebar
            const menuBar = document.querySelector('#content nav .bx.bx-menu, #content nav .bx.bx-x');
            if (menuBar) {
                menuBar.classList.remove('bx-x');
                menuBar.classList.add('bx-menu');
            }
            // Hide overlay when sidebar is closed
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        });
    }

    // Get all dropdown menu items
    const dropdowns = document.querySelectorAll('#sidebar .side-menu li.dropdown > a');

    // Add click event to dropdown menu headers
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            const submenu = this.nextElementSibling;
            
            // Toggle submenu visibility
            submenu.classList.toggle('show');
            
            // Do NOT set the dropdown parent as active
            // Instead, we'll only set the dropdown parent as active
            // when a child item is active, in the setActiveMenuItem function
        });
    });

    // Handle responsive behavior
    window.addEventListener('load', function() {
        if (window.innerWidth < 768) {
            sidebar.classList.add('hide');
        }
    });

    window.addEventListener('resize', function() {
        if (this.innerWidth < 768) {
            sidebar.classList.add('hide');
        }
    });

    // Handle showing dropdown menus on hover when sidebar is collapsed
    const dropdownItems = document.querySelectorAll('#sidebar .side-menu li.dropdown');
    dropdownItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            if (sidebar.classList.contains('hide')) {
                this.querySelector('.submenu').classList.add('show');
            }
        });
        
        item.addEventListener('mouseleave', function() {
            if (sidebar.classList.contains('hide')) {
                this.querySelector('.submenu').classList.remove('show');
            }
        });
    });

    // Script to ensure sidebar behavior 
    document.addEventListener('DOMContentLoaded', function() {
        // Check screen size and adjust layout
        function adjustLayout() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            
            if (window.innerWidth > 768) {
                // Large screens: Show sidebar and adjust content
                if (sidebar) {
                    sidebar.classList.remove('hide');
                    sidebar.style.width = '280px';
                }
                
                if (content) {
                    content.style.width = 'calc(100% - 280px)';
                    content.style.left = '280px';
                }
            } else {
                // Small screens: Default mobile behavior
                if (content) {
                    content.style.width = '100%';
                    content.style.left = '0';
                }
            }
        }
        
        // Run on page load
        adjustLayout();
        
        // Run on resize
        window.addEventListener('resize', adjustLayout);
        
        // Set active menu item based on current URL
        function setActiveMenuItem() {
            // Get current URL path
            const currentPath = window.location.pathname;
            
            // Get all menu items with links (both top-level and submenu)
            const allMenuLinks = document.querySelectorAll('#sidebar .side-menu li a[href]');
            
            // Remove active class from all menu items
            document.querySelectorAll('#sidebar .side-menu li').forEach(item => {
                item.classList.remove('active');
            });
            
            // Find matching link and set ONLY its parent li as active
            allMenuLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath || 
                    currentPath.includes(link.getAttribute('href')) && 
                    link.getAttribute('href') !== '#' && 
                    link.getAttribute('href').length > 1) {
                    
                    // Set ONLY the parent li as active
                    const parentLi = link.closest('li');
                    parentLi.classList.add('active');
                    
                    // If it's in a submenu, make the submenu visible but DON'T add active to parent dropdown
                    const parentDropdown = parentLi.closest('.dropdown');
                    if (parentDropdown) {
                        // Don't add active class to the dropdown parent
                        // parentDropdown.classList.add('active');
                        
                        // Just make the submenu visible
                        parentLi.closest('.submenu').classList.add('show');
                    }
                }
            });
            
            // Add click handler to make clicked items active
            allMenuLinks.forEach(link => {
                if (link.getAttribute('href') !== '#') {
                    link.addEventListener('click', function(e) {
                        // If it's not a dropdown toggle (which has no href)
                        // Remove active class from all menu items
                        document.querySelectorAll('#sidebar .side-menu li').forEach(item => {
                            item.classList.remove('active');
                        });
                        
                        // Set ONLY the parent li as active
                        const parentLi = this.closest('li');
                        parentLi.classList.add('active');
                        
                        // If it's in a submenu, make the submenu visible but DON'T add active to parent dropdown
                        const parentDropdown = parentLi.closest('.dropdown');
                        if (parentDropdown) {
                            // Don't add active class to the dropdown parent
                            // parentDropdown.classList.add('active');
                            
                            // Just make the submenu visible
                            parentLi.closest('.submenu').classList.add('show');
                        }
                        
                        // If in mobile view, hide sidebar after click
                        if (window.innerWidth < 768) {
                            sidebar.classList.add('hide');
                        }
                        
                        // Store active item in localStorage to persist across page loads
                        localStorage.setItem('activeMenuItem', this.getAttribute('href'));
                    });
                }
            });
            
            // Check if there's a stored active item in localStorage
            const storedActiveItem = localStorage.getItem('activeMenuItem');
            if (storedActiveItem) {
                const matchingLink = document.querySelector(`#sidebar .side-menu li a[href="${storedActiveItem}"]`);
                if (matchingLink) {
                    // Remove active class from all menu items
                    document.querySelectorAll('#sidebar .side-menu li').forEach(item => {
                        item.classList.remove('active');
                    });
                    
                    // Set ONLY the parent li as active
                    const parentLi = matchingLink.closest('li');
                    parentLi.classList.add('active');
                    
                    // If it's in a submenu, make the submenu visible but DON'T add active to parent dropdown
                    const parentDropdown = parentLi.closest('.dropdown');
                    if (parentDropdown) {
                        // Don't add active class to the dropdown parent
                        // parentDropdown.classList.add('active');
                        
                        // Just make the submenu visible
                        parentLi.closest('.submenu').classList.add('show');
                    }
                }
            }
        }
        
        // Set active menu item when page loads
        setActiveMenuItem();
    });
</script>
