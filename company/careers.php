<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <title>Contact Us - CureBooking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            color: #374151;
        }

        .contact-container {
            text-align: center;
            margin-top: 40px;
        }

        .contact-container p {
            font-size: 1.5rem;
            color: #6b7280;
        }

        .contact-container span {
            color: #374151;
            font-weight: 600;
        }

        .contact-content {
            margin: 40px 0 80px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 40px;
            font-size: 0.875rem;
        }

        .contact-content img {
            width: 100%;
            max-width: 350px;
        }

        .contact-img {
            border-radius: 6px;
        }

        .contact-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            gap: 20px;
        }

        .contact-details p {
            color: #6b7280;
        }

        .contact-details .heading {
            font-weight: 600;
            font-size: 1.125rem;
            color: #4b5563;
        }

        .contact-details button {
            border: 1px solid black;
            padding: 16px 32px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.4s ease;
        }

        .contact-details button:hover {
            background-color: black;
            color: white;
        }

        @media (min-width: 768px) {
            .contact-content {
                flex-direction: row;
                justify-content: center;
            }
        }

        /* Popup Modal */
        .popup-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .popup-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            text-align: center;
            position: relative;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .popup-content p {
            margin-top: 10px;
        }

        .popup-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
            cursor: pointer;
            transition: transform 0.3s, color 0.3s;
        }

        .popup-close:hover {
            color: #ef4444;
        }

    </style>
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
        <div class="popup-content">
            <span class="popup-close" onclick="closePopup()">×</span>
            <h2>Future Scope</h2>
            <p>We aim to grow CureBooking into a national and international healthcare platform, creating future career opportunities in AI,
                mobile apps, telemedicine, and smart health solutions. We plan to build diverse, collaborative teams and offer exciting growth
                paths for developers, designers, healthcare experts, and more. Join us to shape the future of healthcare.</p>
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