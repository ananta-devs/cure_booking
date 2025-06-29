<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <title>Contact Us - CureBooking</title>
    <link rel="stylesheet" href="career.css">
</head>

<body>
    <?php include '../include/header.php'; ?>
    <div class="contact-container" data-aos="fade-up">
        <p>Career <span>Options</span></p>
    </div>

    <div class="contact-content">
        <img data-aos="fade-right" src="http://localhost/cure_booking/assets/contact_image.png" class="contact-img" alt="Contact Image">

        <div class="contact-details" data-aos="fade-left">
            <p class="heading">Our Office</p>
            <p>
                3rd Floor, Shyam Plaza<br>
                G.T. Road, Near Curzon Gate<br>
                Burdwan, West Bengal - 713101<br>
                India
            </p>
            <p>Tel: +91-80056 78901<br>Email: curebooking@outlook.com</p>

            <p class="heading">Careers at CureBooking</p>
            <p>Learn more about our teams and job openings.</p>
            <button class="xplore" onclick="openPopup()">Explore Jobs</button>
        </div>
    </div>
    <!-- Popup Modal -->
    <div id="popupModal" class="popup-modal">
        <div class="popup-content" data-aos="zoom-in">
            <span class="popup-close" onclick="closePopup()">×</span>
            <h2>Future Scope at CureBooking</h2>
            <p>We aim to grow CureBooking into a national and international healthcare platform, creating future career opportunities in AI,
                mobile apps, telemedicine, and smart health solutions. We plan to build diverse, collaborative teams and offer exciting growth
                paths for developers, designers, healthcare experts, and more. Join us to shape the future of healthcare.</p>
            <button class="popup-btn" onclick="closePopup()">Close</button>
        </div>
    </div>
    <?php include '../include/footer.php'; ?>
</body>
<script>
    function openPopup() {
        document.getElementById('popupModal').style.display = 'flex';
    }

    function closePopup() {
        document.getElementById('popupModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const popup = document.getElementById('popupModal');
        if (event.target === popup) {
            popup.style.display = 'none';
        }
    }
</script>
<!-- AOS Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 1000,
        once: true
    });
</script>

</html>