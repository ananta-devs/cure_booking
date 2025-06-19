<style>
    /* Mobile Header */
    .mobile-header {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }
    @media (max-width: 768px) {
        .mobile-header {
            display: flex;
        }

        .sidebar-overlay.active {
            display: block;
        }
    }
</style>
<!-- Mobile Header -->
<header class="mobile-header">
    <div class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="mobile-logo">🏥 CureBooking</div>
</header>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
