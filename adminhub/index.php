<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <!-- Boxicons -->
        <link
            href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css"
            rel="stylesheet"
        />
        <!-- My CSS -->
        <link rel="stylesheet" href="styles.css" />

        <title>CureBooking | Admin</title>
    </head>
    <body>
        <?php
            session_start();
            if (!isset($_SESSION['adm_id'])) {
                header("Location: http://localhost/cure_booking/adminhub/login.php");
                exit();
            }

            //SIDEBAR
            include './include/sidebar.php';
            
            // Include database connection
            include './include/database_connection.php';

            // Fetch dashboard statistics
            $today = date('Y-m-d');
            
            // Count total appointments
            $appointment_query = "SELECT COUNT(*) as total_appointments FROM appointments";
            $appointment_result = mysqli_query($conn, $appointment_query);
            $total_appointments = mysqli_fetch_assoc($appointment_result)['total_appointments'];
            
            // Count total medicine orders
            $medicine_query = "SELECT COUNT(*) as total_medicine_orders FROM medicine_orders";
            $medicine_result = mysqli_query($conn, $medicine_query);
            $total_medicine_orders = mysqli_fetch_assoc($medicine_result)['total_medicine_orders'];
            
            // Count total lab orders
            $lab_query = "SELECT COUNT(*) as total_lab_orders FROM lab_orders";
            $lab_result = mysqli_query($conn, $lab_query);
            $total_lab_orders = mysqli_fetch_assoc($lab_result)['total_lab_orders'];
            
            // Calculate total revenue (medicine + lab orders)
            $medicine_revenue_query = "SELECT SUM(total_amount) as medicine_revenue FROM medicine_orders";
            $medicine_revenue_result = mysqli_query($conn, $medicine_revenue_query);
            $medicine_revenue = mysqli_fetch_assoc($medicine_revenue_result)['medicine_revenue'] ?: 0;
            
            $lab_revenue_query = "SELECT SUM(total_amount) as lab_revenue FROM lab_orders";
            $lab_revenue_result = mysqli_query($conn, $lab_revenue_query);
            $lab_revenue = mysqli_fetch_assoc($lab_revenue_result)['lab_revenue'] ?: 0;
            
            $total_revenue = $medicine_revenue + $lab_revenue;
            
            // Fetch today's appointments
            $today_appointments_query = "SELECT COUNT(*) as today_appointments FROM appointments WHERE DATE(appointment_date) = '$today'";
            $today_appointments_result = mysqli_query($conn, $today_appointments_query);
            $today_appointments = mysqli_fetch_assoc($today_appointments_result)['today_appointments'];

            // Fetch recent orders (combined from all tables)
            $recent_orders_query = "
                (SELECT 'Medicine' as type, booked_by_name as name, DATE(order_date) as order_date, status, created_at 
                FROM medicine_orders ORDER BY created_at DESC LIMIT 3)
                UNION ALL
                (SELECT 'Lab Test' as type, booked_by_name as name, sample_collection_date as order_date, status, created_at 
                FROM lab_orders ORDER BY created_at DESC LIMIT 3)
                ORDER BY created_at DESC LIMIT 5
            ";
            $recent_orders_result = mysqli_query($conn, $recent_orders_query);
        ?>

        <!-- CONTENT -->
        <section id="content">
            <!-- NAVBAR -->
            <?php
			include './include/top-header.php';
		?>

            <!-- MAIN -->
            <main>
                <div class="head-title">
                    <div class="left">
                        <h1>Dashboard</h1>
                    </div>
                </div>

                <ul class="box-info">
                    <li>
                        <i class="bx bxs-calendar-check"></i>
                        <span class="text">
                            <p>Total Appointments</p>
                            <h3><?php echo $total_appointments; ?></h3>
                        </span>
                    </li>
                    <li>
                        <i class="bx bx-test-tube"></i>
                        <span class="text">
                            <p>Lab Bookings</p>
                            <h3><?php echo $total_lab_orders; ?></h3>
                        </span>
                    </li>
                    <li>
                        <i class="bx bx-capsule"></i>
                        <span class="text">
                            <p>Medicine Orders</p>
                            <h3><?php echo $total_medicine_orders; ?></h3>
                        </span>
                    </li>
                    <li>
                        <i class="bx bx-rupee"></i>
                        <span class="text">
                            <p>Total Revenue</p>
                            <h3>
                                <?php echo number_format($total_revenue, 2); ?>
                            </h3>
                        </span>
                    </li>
                </ul>

                <div class="table-data">
                    <div class="order">
                        <div class="head">
                            <h3>Recent Bookings</h3>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = mysqli_fetch_assoc($recent_orders_result)): ?>
                                <tr>
                                    <td>
                                        <?php if($order['type'] == 'Medicine'): ?>
                                        <i class="bx bx-capsule"></i>
                                        <?php else: ?>
                                        <i class="bx bx-test-tube"></i>
                                        <?php endif; ?>
                                        <span
                                            ><?php echo $order['type']; ?></span
                                        >
                                    </td>
                                    <td>
                                        <p>
                                            <?php echo htmlspecialchars($order['name']); ?>
                                        </p>
                                    </td>
                                    <td>
                                        <?php echo date('d-m-Y', strtotime($order['order_date'])); ?>
                                    </td>
                                    <td>
                                        <span
                                            class="status <?php echo strtolower($order['status']) == 'completed' || strtolower($order['status']) == 'confirmed' ? 'completed' : (strtolower($order['status']) == 'pending' ? 'pending' : 'process'); ?>"
                                        >
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="todo">
                        <div class="head">
                            <h3>Today's Appointments</h3>
                        </div>
                        <?php 
					// Fetch today's detailed appointments
					$today_detail_query = "SELECT patient_name, appointment_time, doctor_name, status 
										   FROM appointments 
										   WHERE DATE(appointment_date) = '$today' 
										   ORDER BY appointment_time ASC";
					$today_detail_result = mysqli_query($conn, $today_detail_query);
					?>
                        <ul class="todo-list">
                            <?php if(mysqli_num_rows($today_detail_result) >
                            0): ?>
                            <?php while($appointment = mysqli_fetch_assoc($today_detail_result)): ?>
                            <li
                                class="<?php echo strtolower($appointment['status']) == 'completed' ? 'completed' : 'not-completed'; ?>"
                            >
                                <div class="appointment-info">
                                    <p>
                                        <strong
                                            ><?php echo htmlspecialchars($appointment['patient_name']); ?></strong
                                        >
                                    </p>
                                    <small
                                        >Dr.
                                        <?php echo htmlspecialchars($appointment['doctor_name']); ?></small
                                    >
                                    <small
                                        ><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></small
                                    >
                                </div>
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </li>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <li class="no-appointments">
                                <p>No appointments scheduled for today</p>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </main>
        </section>
    </body>
</html>
