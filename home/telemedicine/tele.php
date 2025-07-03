<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Telemedicine</title>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f4f4;
            color: #333;
            width: 100%;
            overflow-x: hidden;
        }

        .cont {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
            padding-top: 50px;
        }

        .c_logo {
            color: rgb(35, 11, 11);
            font-size: 80px;
        }

        .under-logo,
        .bottom-p {
            color: rgb(33, 28, 28);
        }

        .bottom-p {
            padding-top: 50px;
            padding-bottom: 30px;
        }

        span {
            color: #0069d9;
        }

        .loading {
            height: 300px;
            width: 300px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .cont {
                padding: 20px;
            }

            .c_logo {
                font-size: 50px;
            }

            .loading {
                height: 200px;
                width: 200px;
            }
        }
    </style>
</head>

<body>
    <?php include '../include/header.php'; ?>

    <div class="cont">
        <h1 class="c_logo"><span>Cure</span>Booking</h1>
        <h3 class="under-logo">Telemedicine is not currently available.</h3>
        <p class="bottom-p">This Feature will be available soon.</p>
        <img class="loading" data-aos="fade" src="http://localhost/cure_booking/home/assets/duck.gif" />
    </div>

    <?php include '../include/footer.php'; ?>
</body>

<!---AOS Library --->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        once: true,
        duration: 1000,
    });
</script>

</html>
