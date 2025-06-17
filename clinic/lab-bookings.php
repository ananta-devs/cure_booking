<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
        <link
                href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
                rel="stylesheet"
            />
            <link rel="stylesheet" href="styles.css" />
    </head>
    <body>
        <?php
            include './top-header.php';
        ?>

        <div class="container">
            <?php
                include './sidebar.php';
            ?>
            <!-- Main Content -->
            <main class="main-content">
                <!-- Dashboard Section -->
            <div id="lab-bookings-section" class="content-section active">
                <div class="header">
                    <h1>Lab Bookings</h1>
                    <p>Manage laboratory test bookings and results</p> <br>
                    <div class="quick-actions">
                        <button class="action-btn">
                            <i class="fa fa-calendar-check"></i> 
                            Schedule Tests
                        </button>
                        <button class="action-btn">
                            <i class="fa fa-plus"></i> 
                            Book Lab Test
                        </button>
                    </div>
                </div>
                <div id="bookingModal" class="modal">
                    <div class="modal-content">
                        <h2>Book Test</h2>
                        <div id="modalTestInfo"></div>
                        <form id="bookingForm">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required value="">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required value="">
                            </div>
                            <div class="form-group">
                                <label for="address">Address for Sample Collection</label>
                                <textarea id="address" name="address" required></textarea>
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
            </div>
            </main>
        </div>

    </body>
</html> 
