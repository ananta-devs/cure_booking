<?php
    // Get admin profile image
    $adm_id = $_SESSION['adm_id'];
    $adm_img = "default.png"; // Default image
    
    if (!empty($adm_id)) {
        $query = "SELECT adm_img FROM admin WHERE adm_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $adm_id);
        $stmt->execute();
        $adminResult = $stmt->get_result();
        
        if ($adminResult && $adminResult->num_rows > 0) {
            $adminRow = $adminResult->fetch_assoc();
            if (!empty($adminRow['adm_img'])) {
                $adm_img = $adminRow['adm_img'];
            }
        }
        $stmt->close();
    }

    $image_path = "http://localhost/adminhub/sett/admin_images/" . $adm_img;
?>
<!-- Fixed top-header.php -->
<style>
    /* NAVBAR */
    #content nav {
        height: 56px;
        background: var(--light);
        padding: 0 24px;
        display: flex;
        align-items: center;
        grid-gap: 24px;
        font-family: var(--lato);
        position: sticky;
        top: 0;
        left: 0;
        z-index: 1000;
    }
    #content nav::before {
        content: '';
        position: absolute;
        width: 40px;
        height: 40px;
        bottom: -40px;
        left: 0;
        border-radius: 50%;
        box-shadow: -20px -20px 0 var(--light);
    }
    #content nav a {
        color: var(--dark);
    }
    #content nav .bx.bx-menu,
    #content nav .bx.bx-x {
        cursor: pointer;
        color: var(--dark);
        font-size: 24px;
        padding: 8px;
        margin-left: -12px;
        display: none; /* Hide by default on large screens */
    }
    #content nav .nav-link {
        font-size: 16px;
        transition: .3s ease;
    }
    #content nav .nav-link:hover {
        color: var(--blue);
    }
    #content nav form {
        max-width: 400px;
        width: 100%;
        margin-right: auto;
    }
    #content nav form .form-input {
        display: flex;
        align-items: center;
        height: 36px;
    }
    #content nav form .form-input input {
        flex-grow: 1;
        padding: 0 16px;
        height: 100%;
        border: none;
        background: var(--grey);
        border-radius: 36px 0 0 36px;
        outline: none;
        width: 100%;
        color: var(--dark);
    }
    #content nav form .form-input button {
        width: 36px;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        background: var(--blue);
        color: var(--light);
        font-size: 18px;
        border: none;
        outline: none;
        border-radius: 0 36px 36px 0;
        cursor: pointer;
    }
    #content nav .notification {
        font-size: 20px;
        position: relative;
    }
    #content nav .notification .num {
        position: absolute;
        top: -6px;
        right: -6px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 2px solid var(--light);
        background: var(--red);
        color: var(--light);
        font-weight: 700;
        font-size: 12px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    #content nav .profile img {
        width: 36px;
        height: 36px;
        object-fit: cover;
        border-radius: 50%;
    }
    #content nav .switch-mode {
        display: block;
        min-width: 50px;
        height: 25px;
        border-radius: 25px;
        background: var(--grey);
        cursor: pointer;
        position: relative;
    }
    #content nav .switch-mode::before {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        bottom: 2px;
        width: calc(25px - 4px);
        background: var(--blue);
        border-radius: 50%;
        transition: all .3s ease;
    }
    #content nav #switch-mode:checked + .switch-mode::before {
        left: calc(100% - (25px - 4px) - 2px);
    }
    
    /* Sidebar overlay styling */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    /* Add responsive styles for mobile */
    @media screen and (max-width: 768px) {
        /* Ensure navbar is always visible on mobile */
        #content nav {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            z-index: 1500 !important;
        }
        
        #content nav form {
            max-width: 300px;
        }
        
        /* Show hamburger menu only on smaller screens */
        #content nav .bx.bx-menu,
        #content nav .bx.bx-x {
            display: inline-block !important;
        }
        
        /* Adjust content to account for fixed navbar */
        #content {
            padding-top: 56px;
        }
    }
    
    /* Additional styling for the mobile search form */
@media screen and (max-width: 576px) {
	#content nav form .form-input input {
		display: none;
	}

	#content nav form .form-input button {
		width: auto;
		height: auto;
		background: transparent;
		border-radius: none;
		color: var(--dark);
	}

	#content nav form.show .form-input input {
		display: block;
		width: 100%;
	}
	#content nav form.show .form-input button {
		width: 36px;
		height: 100%;
		border-radius: 0 36px 36px 0;
		color: var(--light);
		background: var(--red);
	}

	#content nav form.show ~ .notification,
	#content nav form.show ~ .profile {
		display: none;
	}

	#content main .box-info {
		grid-template-columns: 1fr;
	}

	#content main .table-data .head {
		min-width: 420px;
	}
	#content main .table-data .order table {
		min-width: 420px;
	}
	#content main .table-data .todo .todo-list {
		min-width: 420px;
	}
}
</style>

<!-- NAVBAR -->
<nav>
    <i class='bx bx-menu'></i>
    <form action="#">
        <div class="form-input">
            <input type="search" placeholder="Search...">
            <button type="button" class="search-btn"><i class='bx bx-search'></i></button>
        </div>
    </form>
    <input type="checkbox" id="switch-mode" hidden>
    <label for="switch-mode" class="switch-mode"></label>
            <a href="http://localhost/adminhub/admin_profile.php" class="profile">
				<img src="<?php echo htmlspecialchars($image_path); ?>" alt="Admin Profile">
			</a>
</nav>
<!-- NAVBAR -->

<!-- Add overlay for mobile sidebar background -->
<div class="sidebar-overlay"></div>

<!-- Include the updated script to handle sidebar toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Element references
    const menuBar = document.querySelector('#content nav .bx.bx-menu');
    const sidebar = document.getElementById('sidebar');
    const sidebarCloseBtn = document.querySelector('#sidebar .close-btn');
    const overlay = document.querySelector('.sidebar-overlay');
    const searchButton = document.querySelector('#content nav form .form-input button');
    const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
    const searchForm = document.querySelector('#content nav form');
    const switchMode = document.getElementById('switch-mode');

    // Function to toggle mobile search
    function toggleMobileSearch() {
        if (window.innerWidth < 576) {
            searchForm.classList.toggle('show');
            if (searchForm.classList.contains('show')) {
                searchButtonIcon.classList.remove('bx-search');
                searchButtonIcon.classList.add('bx-x');
            } else {
                searchButtonIcon.classList.remove('bx-x');
                searchButtonIcon.classList.add('bx-search');
            }
        }
    }

    // Function to handle search icon state
    function updateSearchIconState() {
        if (window.innerWidth > 576) {
            // For larger screens, always reset to search icon
            if (searchButtonIcon && searchButtonIcon.classList.contains('bx-x')) {
                searchButtonIcon.classList.remove('bx-x');
                searchButtonIcon.classList.add('bx-search');
            }
            if (searchForm) {
                searchForm.classList.remove('show');
            }
        }
    }

    // Function to update menu button state
    function updateMenuButtonState(sidebarVisible) {
        if (menuBar) {
            if (sidebarVisible) {
                // Show X button when sidebar is visible
                menuBar.classList.remove('bx-menu');
                menuBar.classList.add('bx-x');
            } else {
                // Show menu button when sidebar is hidden
                menuBar.classList.remove('bx-x');
                menuBar.classList.add('bx-menu');
            }
        }
    }

    // Initialize state based on screen size
    function initializeState() {
        // Ensure navbar is always visible
        const navbar = document.querySelector('#content nav');
        if (navbar) {
            navbar.style.display = 'flex';
            navbar.style.visibility = 'visible';
            navbar.style.opacity = '1';
        }
        
        if (sidebar) {
            // Only hide sidebar initially on smaller screens
            if (window.innerWidth < 768) {
                sidebar.classList.add('hide');
                if (overlay) overlay.classList.remove('active');
                updateMenuButtonState(false);
                
                // Ensure menu button is visible on mobile
                if (menuBar) {
                    menuBar.style.display = 'inline-block';
                }
            } else {
                // Ensure sidebar is visible on large screens
                sidebar.classList.remove('hide');
                // Hide menu button completely on large screens
                if (menuBar) {
                    menuBar.style.display = 'none';
                }
            }
        }
        
        // Always call this to ensure search icon state is correct on load
        updateSearchIconState();
    }

    // Run initialization
    initializeState();

    // TOGGLE SIDEBAR - modified for new functionality
    if (menuBar && sidebar) {
        menuBar.addEventListener('click', function () {
            if (sidebar.classList.contains('hide')) {
                // Show sidebar
                sidebar.classList.remove('hide');
                if (overlay) overlay.classList.add('active');
                updateMenuButtonState(true);
            } else {
                // Hide sidebar
                sidebar.classList.add('hide');
                if (overlay) overlay.classList.remove('active');
                updateMenuButtonState(false);
            }
        });
    }

    // Close sidebar when clicking on overlay (mobile only)
    if (overlay && sidebar && menuBar) {
        overlay.addEventListener('click', function() {
            sidebar.classList.add('hide');
            overlay.classList.remove('active');
            updateMenuButtonState(false);
        });
    }

    // Handle sidebar close button click
    if (sidebarCloseBtn && sidebar) {
        sidebarCloseBtn.addEventListener('click', function() {
            sidebar.classList.add('hide');
            if (overlay) overlay.classList.remove('active');
            updateMenuButtonState(false);
        });
    }

    // Search functionality - Fix for mobile
    if (searchButton) {
        searchButton.addEventListener('click', function (e) {
            e.preventDefault(); // Always prevent default
            toggleMobileSearch();
        });
    }
    
    // Handle form submission properly
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            // Only prevent default if we're showing the mobile search input
            if (window.innerWidth < 576 && !searchForm.classList.contains('show')) {
                e.preventDefault();
                toggleMobileSearch();
            }
            // Otherwise let the form submit normally
        });
    }

    // Handle responsive behavior on window resize
    window.addEventListener('resize', function () {
        // Ensure navbar is always visible
        const navbar = document.querySelector('#content nav');
        if (navbar) {
            navbar.style.display = 'flex';
            navbar.style.visibility = 'visible';
            navbar.style.opacity = '1';
        }
        
        // Update search icon state on every resize
        updateSearchIconState();
        
        // Large screens (> 768px)
        if (this.innerWidth > 768) {
            if (overlay) overlay.classList.remove('active');
            // Always show sidebar on large screens
            if (sidebar) sidebar.classList.remove('hide');
            // Always hide menu button on large screens
            if (menuBar) {
                menuBar.style.display = 'none';
            }
        } 
        // Small/Medium screens (<= 768px)
        else {
            if (sidebar) {
                // Only hide sidebar if it's not already hidden
                const isCurrentlyHidden = sidebar.classList.contains('hide');
                if (!isCurrentlyHidden) {
                    sidebar.classList.add('hide');
                }
                // Show menu button on small screens
                if (menuBar) {
                    menuBar.style.display = 'inline-block';
                    updateMenuButtonState(!isCurrentlyHidden);
                }
            }
            if (overlay) overlay.classList.remove('active');
        }
    });

    // Dark mode toggle
    if (switchMode) {
        switchMode.addEventListener('change', function () {
            if (this.checked) {
                document.body.classList.add('dark');
            } else {
                document.body.classList.remove('dark');
            }
        });
    }
    
    // Close search form when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 576 && 
            searchForm && 
            searchForm.classList.contains('show') && 
            !searchForm.contains(e.target) && 
            e.target !== searchButton) {
            toggleMobileSearch();
        }
    });
});
</script>