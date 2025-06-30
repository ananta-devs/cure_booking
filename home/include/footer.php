<style>
  /* Footer Base */
  footer {
    background-color: #1f2937; /* Darker for contrast */
    color: #e5e7eb;
    padding: 60px 20px 30px;
    font-family: 'Inter', sans-serif;
    font-size: 15px;
    line-height: 1.7;
  }

  /* Grid Layout */
  .footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto 40px;
    padding: 0 15px;
  }

  /* Footer Column Headings */
  .footer-column h4 {
    font-size: 18px;
    margin-bottom: 18px;
    font-weight: 600;
    color: #3b82f6;
  }

  /* Footer Links */
  .footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .footer-links li {
    margin-bottom: 10px;
  }

  .footer-links a {
    color: #d1d5db;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s ease, padding-left 0.3s ease;
  }

  .footer-links a:hover {
    color: #ffffff;
    padding-left: 5px;
  }

  /* Footer Bottom */
  .footer-bottom {
    border-top: 1px solid #374151;
    padding-top: 20px;
    text-align: center;
    font-size: 14px;
    color: #9ca3af;
    max-width: 1200px;
    margin: 0 auto;
  }

  /* Responsive Tweaks */
  @media screen and (max-width: 768px) {
    .footer-grid {
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 30px;
    }
    .footer-column h4 {
      font-size: 16px;
    }
    .footer-links a {
      font-size: 13.5px;
    }
  }

  @media screen and (max-width: 480px) {
    .footer-grid {
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    .footer-bottom {
      font-size: 12.5px;
    }
  }

  @media screen and (max-width: 340px) {
    .footer-grid {
      grid-template-columns: 1fr;
    }
  }

</style>

<!-- Footer -->
 <head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
 </head>
<footer>
        <div class="footer-grid">
            <div class="footer-column">
                <h4>CureBooking</h4>
                <ul class="footer-links">
                    <li><a href="http://localhost/cure_booking/home/company/aboutUS.php">About Us</a></li>
                    <li><a href="http://localhost/cure_booking/home/company/careers.php">Careers</a></li>
                    <li><a href="http://localhost/cure_booking/home/company/press.php">Press</a></li>
                    <li><a href="http://localhost/cure_booking/home/company/contactUS.php">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>For Patients</h4>
                <ul class="footer-links">
                    <li><a href="http://localhost/cure_booking/home/find-doctor/doctors.php">Search for Doctors</a></li>
                    <li><a href="http://localhost/cure_booking/home/lab-tests/lab.php">Book Lab Tests</a></li>
                    <li><a href="http://localhost/cure_booking/home/medicines/medicines.php">Order Medicines</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>For Doctors</h4>
                <ul class="footer-links">
                     <li ><a href="http://localhost/cure_booking/providers/login.php?role=doctor">CureBooking Profile</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>For Clinics</h4>
                <ul class="footer-links">
                    <li><a href="http://localhost/cure_booking/providers/login.php?role=clinic">CureBooking Profile</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>More</h4>
                <ul class="footer-links">
                    <li><a href="http://localhost/cure_booking/home/more/help.php">Help</a></li>
                    <li><a href="http://localhost/cure_booking/home/more/terms.php">Terms & Conditions</a></li>
                    <li><a href="http://localhost/cure_booking/home/more/privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Social</h4>
                <ul class="footer-links">
                    <li><a href="https://www.facebook.com/" target="blank">Facebook</a></li>
                    <li><a href="https://x.com/i/flow/login" target="blank">X-handle</a></li>
                    <li><a href="https://www.instagram.com/" target="blank">Instagram</a></li>
                    <li><a href="https://www.youtube.com/" target="blank">Youtube</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 CureBooking. All Rights Reserved.</p>
        </div>
</footer>
