<style>
    /* Footer */
    footer {
        background-color: #2d2d32;
        color: white;
        padding: 60px 0 20px;
        text-align: center; /* Center all content by default */
    }
    
    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(194px, 1fr));
        gap: 40px;
        margin-bottom: 40px;
        max-width: 1200px; /* Add max-width for larger screens */
        margin-left: auto; /* Center the grid */
        margin-right: auto;
    }
    
    .footer-column h4 {
        font-size: 18px;
        margin-bottom: 20px;
        color: #512da8;
        text-align: center; /* Center the headings */
    }
    
    .footer-links {
        list-style: none;
        padding: 0; /* Remove default padding */
        text-align: center; /* Center the list items */
    }
    
    .footer-links li {
        margin-bottom: 10px;
    }
    
    .footer-links a {
        color: #b4b4b4;
        text-decoration: none;
        font-size: 14px;
        display: inline-block; /* Better for centering */
    }
    
    .footer-links a:hover {
        color: white;
    }
    
    .footer-bottom {
        border-top: 1px solid #414146;
        padding-top: 20px;
        text-align: center;
        font-size: 14px;
        color: #b4b4b4;
        max-width: 1200px; /* Match grid max-width */
        margin-left: auto;
        margin-right: auto;
    }

    /* Responsive adjustments */
    @media screen and (max-width: 768px) {
        .footer-grid {
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            padding: 0 15px; /* Add some padding on smaller screens */
        }
        
        footer {
            padding: 30px 0 15px;
        }
        
        .footer-bottom {
            padding: 20px 15px 0;
        }
    }

    @media screen and (max-width: 480px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .footer-column h4 {
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .footer-links a {
            font-size: 13px;
        }
        
        .footer-bottom {
            font-size: 12px;
        }
    }

    @media screen and (max-width: 320px) {
        .footer-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<!-- Footer -->
<footer>
        <div class="footer-grid">
            <div class="footer-column">
                <h4>CureBooking</h4>
                <ul class="footer-links">
                    <li><a href="http://localhost/cure_booking/company/aboutUS.php">About Us</a></li>
                    <!-- <li><a href="#">Careers</a></li> -->
                    <li><a href="http://localhost/cure_booking/company/press.php">Press</a></li>
                    <li><a href="http://localhost/cure_booking/company/contactUS.php">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>For Patients</h4>
                <ul class="footer-links">
                    <li><a href="http://localhost/cure_booking/find-doctor/doctors.php"">Search for Doctors</a></li>
                    <li><a href="http://localhost/cure_booking/lab-new/lab.php">Book Lab Tests</a></li>
                    <li><a href="http://localhost/cure_booking/medicines/medicines.php">Order Medicines</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>For Doctors</h4>
                <ul class="footer-links">
                     <li ><a href="http://localhost/cure_booking/login.php?role=doctor">CureBooking Profile</a></li>
                </ul>
                <h4>For Clinics</h4>
                <ul class="footer-links">
                    <li><a href="http://localhost/cure_booking/login.php?role=clinic">CureBooking Profile</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>More</h4>
                <ul class="footer-links">
                    <li><a href="http://localhost/cure_booking/more/help.php">Help</a></li>
                    <li><a href="http://localhost/cure_booking/more/terms.php">Terms & Conditions</a></li>
                    <li><a href="http://localhost/cure_booking/more/privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Social</h4>
                <ul class="footer-links">
                    <li><a href="https://www.facebook.com/">Facebook</a></li>
                    <li><a href="https://x.com/i/flow/login">X-handle</a></li>
                    <li><a href="https://www.instagram.com/">Instagram</a></li>
                    <li><a href="https://www.youtube.com/">Youtube</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 CureBooking. All Rights Reserved.</p>
        </div>
</footer>
