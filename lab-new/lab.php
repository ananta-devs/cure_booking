<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Lab Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
    <style>
        .order-summary-container,
        .checkout-summary {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        }


        .order-item,
        .checkout-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
        border-bottom: none;
        }

        .order-total,
        .checkout-total {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #ddd;
        text-align: center;
        font-size: 18px;
        color: #3b82f6;
        }
    </style>
</head>

<body>
    <?php
        session_start();
        include '../include/header.php';
        include '../styles.php';

        // Check if user is logged in and get user info
        $isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['username']) || isset($_SESSION['logged_in']);
        $userInfo = $isLoggedIn ? [
            'id' => $_SESSION['user_id'] ?? 0,
            'name' => $_SESSION['name'] ?? $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? $_SESSION['username'] ?? '',
            'username' => $_SESSION['user_name'] ?? ''
        ] : [];
    ?>

    <section class="hero">
        <div class="container" data-aos="fade-up">
            <h1 >Book Your Lab Tests Online in Minutes.</h1>
            <p>Schedule diagnostic tests at your convenience, and get accurate results.</p>
            <form class="search-container">
                <input type="text" id="search-bar" placeholder="Search for lab tests..." />
                <button type="submit" aria-label="Search"><i class="ri-search-line"></i></button>
            </form>
        </div>
    </section>

    <div class="container">
        <!-- Cart Summary -->
        <div id="cart-summary" class="cart-summary hidden">
            <div class="cart-header">
                <h3>Cart (<span id="cart-count">0</span> tests)</h3>
                <div class="cart-actions">
                    <button id="view-cart-btn" class="btn secondary-btn">View Cart</button>
                    <button id="checkout-btn" class="btn primary-btn">Checkout ₹<span id="cart-total">0</span></button>
                </div>
            </div>
        </div>

        <div id="loading" class="loading-indicator hidden">Loading lab test data...</div>
        <div id="error-message" class="error-message hidden"></div>
        <div id="results-container" class="results-container"></div>
        <div class="pagination-container">
            <button id="load-more-btn" class="hidden">Load More</button>
        </div>
    </div>

    <!-- Cart Modal -->
    <div id="cartModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="cart">&times;</span>
            <h2>Your Cart</h2>
            <div id="cart-items-container"></div>
            <div class="cart-footer">
                <div class="cart-total-section">
                    <h3>Total: ₹<span id="modal-cart-total">0</span></h3>
                </div>
                <div class="cart-buttons">
                    <button id="clear-cart-btn" class="btn secondary-btn">Clear Cart</button>
                    <button id="proceed-checkout-btn" class="btn primary-btn">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Book Test</h2>
            <div id="modalTestInfo"></div>
            <form id="bookingForm" action="api.php" method="post">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required >
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="address">Address for Sample Collection</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                <div class="form-group">
                    <label for="clinic_id">Choose a clinic </label>
                    <select name="clinic_id" id="clinic_id">
                        <option value="">Choose</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="time">Time Slot</label>
                        <select id="time" name="time" required>
                            <option value="">Select Time</option>
                            <option value="07:00-09:00">07:00-09:00 AM</option>
                            <option value="09:00-11:00">09:00-11:00 AM</option>
                            <option value="11:00-13:00">11:00-01:00 PM</option>
                            <option value="13:00-15:00">01:00-03:00 PM</option>
                            <option value="15:00-17:00">03:00-05:00 PM</option>
                            <option value="17:00-19:00">05:00-07:00 PM</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn primary-btn">Confirm Booking</button>
            </form>
        </div>
    </div>
    
    <script>
        const isUserLoggedIn = <?= json_encode($isLoggedIn) ?>;
        const userInfo = <?= json_encode($userInfo) ?>;
    </script>

    <script src="script.js"></script>

    <!---AOS Library --->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
<?php
include '../include/footer.php';
?>

</html>