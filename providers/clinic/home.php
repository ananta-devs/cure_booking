<?php
    session_start();

    // Check if user is logged in (for both doctor and clinic)
    if (!isset($_SESSION['logged_in']) && !isset($_SESSION['clinic_logged_in'])) {
        header('Location: ../login.php');
        exit;
    }

    // Database connection
    include './include/database_connection.php'; // Assuming you have a database connection file

    // Get clinic ID from session
    $clinic_id = null;
    $clinic_name = null;
    
    if (isset($_SESSION['clinic_logged_in']) && $_SESSION['clinic_logged_in'] === true) {
        $clinic_id = $_SESSION['clinic_id'] ?? null; // Assuming clinic_id is stored in session
        $clinic_name = $_SESSION['clinic_name'] ?? null; // Assuming clinic_name is stored in session
    }

    // Get today's date
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // Build WHERE clause for clinic filtering
    $clinic_where = "";
    if ($clinic_id) {
        $clinic_where = " AND clinic_id = " . intval($clinic_id);
    }

    // Fetch Total Patients Today (filtered by clinic)
    $patients_today_query = "SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = '$today'" . $clinic_where;
    $patients_today_result = mysqli_query($conn, $patients_today_query);
    $patients_today = mysqli_fetch_assoc($patients_today_result)['count'];

    // Fetch Yesterday's Patients for comparison (filtered by clinic)
    $patients_yesterday_query = "SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = '$yesterday'" . $clinic_where;
    $patients_yesterday_result = mysqli_query($conn, $patients_yesterday_query);
    $patients_yesterday = mysqli_fetch_assoc($patients_yesterday_result)['count'];

    // Calculate percentage change for patients
    $patient_change = 0;
    if ($patients_yesterday > 0) {
        $patient_change = round((($patients_today - $patients_yesterday) / $patients_yesterday) * 100, 1);
    }

    // Fetch Active Doctors count (for clinic-specific dashboard, show doctors assigned to this clinic)
    if ($clinic_id) {
        $active_doctors_query = "
            SELECT COUNT(DISTINCT d.doc_id) as count 
            FROM doctor d 
            INNER JOIN doctor_clinic_assignments dca ON d.doc_id = dca.doctor_id 
            WHERE dca.clinic_id = " . intval($clinic_id);
    } else {
        $active_doctors_query = "SELECT COUNT(*) as count FROM doctor";
    }
    $active_doctors_result = mysqli_query($conn, $active_doctors_query);
    $active_doctors = mysqli_fetch_assoc($active_doctors_result)['count'];

    // Fetch Lab Tests Pending (filtered by clinic)
    $lab_pending_query = "SELECT COUNT(*) as count FROM lab_orders WHERE status IN ('Pending', 'Confirmed', 'Sample Collected', 'In Progress')";
    if ($clinic_id) {
        $lab_pending_query .= " AND clinic_id = " . intval($clinic_id);
    }
    $lab_pending_result = mysqli_query($conn, $lab_pending_query);
    $lab_pending = mysqli_fetch_assoc($lab_pending_result)['count'];

    // Fetch Today's Revenue (from medicine orders and lab orders completed today, filtered by clinic)
    $medicine_revenue_where = "";
    $lab_revenue_where = "";
    
    if ($clinic_id) {
        // Note: You might need to add clinic_id to medicine_orders table if it doesn't exist
        // For now, assuming medicine orders are not clinic-specific or you have a way to link them
        $lab_revenue_where = " AND clinic_id = " . intval($clinic_id);
    }

    $revenue_query = "
        SELECT 
            (COALESCE(medicine_revenue, 0) + COALESCE(lab_revenue, 0)) as total_revenue
        FROM (
            SELECT 
                (SELECT SUM(total_amount) FROM medicine_orders 
                 WHERE DATE(order_date) = '$today' AND status IN ('confirmed', 'shipped', 'delivered')) as medicine_revenue,
                (SELECT SUM(total_amount) FROM lab_orders 
                 WHERE DATE(created_at) = '$today' AND status = 'Completed' $lab_revenue_where) as lab_revenue
        ) as revenue_data
    ";
    $revenue_result = mysqli_query($conn, $revenue_query);
    $today_revenue = mysqli_fetch_assoc($revenue_result)['total_revenue'] ?? 0;

    // Calculate average daily revenue for comparison (last 7 days excluding today, filtered by clinic)
    $avg_revenue_query = "
        SELECT 
            AVG(daily_revenue) as avg_revenue
        FROM (
            SELECT 
                DATE(lo.created_at) as order_day,
                (COALESCE(SUM(mo.total_amount), 0) + COALESCE(SUM(lo.total_amount), 0)) as daily_revenue
            FROM lab_orders lo
            LEFT JOIN medicine_orders mo ON DATE(mo.order_date) = DATE(lo.created_at) 
                AND mo.status IN ('confirmed', 'shipped', 'delivered')
            WHERE lo.status = 'Completed' 
            AND DATE(lo.created_at) BETWEEN DATE_SUB('$today', INTERVAL 7 DAY) AND DATE_SUB('$today', INTERVAL 1 DAY)
            " . ($clinic_id ? "AND lo.clinic_id = " . intval($clinic_id) : "") . "
            GROUP BY DATE(lo.created_at)
        ) as daily_revenues
    ";
    $avg_revenue_result = mysqli_query($conn, $avg_revenue_query);
    $avg_revenue = mysqli_fetch_assoc($avg_revenue_result)['avg_revenue'] ?? 0;

    // Calculate revenue percentage change
    $revenue_change = 0;
    if ($avg_revenue > 0) {
        $revenue_change = round((($today_revenue - $avg_revenue) / $avg_revenue) * 100, 1);
    }

    // Fetch Recent Activity (last 10 activities, filtered by clinic)
    $recent_activity_query = "
        SELECT 'appointment' as type, 
               CONCAT('New appointment scheduled with Dr. ', doctor_name) as activity,
               created_at as activity_time,
               'appointment' as icon_type
        FROM appointments 
        WHERE DATE(created_at) = '$today' $clinic_where
        
        UNION ALL
        
        SELECT 'lab_order' as type,
               CASE 
                   WHEN status = 'Completed' THEN CONCAT('Lab results ready for ', customer_name)
                   WHEN status = 'Sample Collected' THEN CONCAT('Sample collected for ', customer_name)
                   WHEN status = 'Confirmed' THEN CONCAT('Lab test confirmed for ', customer_name)
                   ELSE CONCAT('Lab order ', status, ' for ', customer_name)
               END as activity,
               updated_at as activity_time,
               'lab' as icon_type
        FROM lab_orders 
        WHERE DATE(updated_at) = '$today'" . ($clinic_id ? " AND clinic_id = " . intval($clinic_id) : "") . "
        
        UNION ALL
        
        SELECT 'medicine_order' as type,
               CONCAT('Medicine order ', status, ' for ', name) as activity,
               updated_at as activity_time,
               'medicine' as icon_type
        FROM medicine_orders 
        WHERE DATE(updated_at) = '$today'
        " . ($clinic_id ? "AND booked_by_name IN (
            SELECT DISTINCT booked_by_name FROM appointments WHERE clinic_id = " . intval($clinic_id) . "
        )" : "") . "
        
        ORDER BY activity_time DESC 
        LIMIT 10
    ";
    $recent_activity_result = mysqli_query($conn, $recent_activity_query);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>CureBooking | <?php echo $clinic_name ? $clinic_name . ' - ' : ''; ?> - Clinic Dashboard</title>
        <link
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
            rel="stylesheet"
        />
        <link rel="stylesheet" href="styles.css" />
    </head>
    <body>
        <?php
            include './include/top-header.php';
        ?>

        <div class="container">
            <?php
                include './include/sidebar.php';
            ?>
            <!-- Main Content -->
            <main class="main-content">
                <!-- Dashboard Section -->
                <div id="dashboard-section" class="content-section active">
                    <?php if ($clinic_name): ?>
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; text-align: center;">
                        <h1 style="margin: 0; font-size: 1.5rem;"><?php echo htmlspecialchars($clinic_name); ?> Dashboard</h1>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Real-time overview of your clinic's activities</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $patients_today; ?></div>
                            <div class="stat-label">Total Patients Today</div>
                            <div class="stat-trend <?php echo $patient_change >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <?php 
                                echo $patient_change >= 0 ? '↗' : '↘'; 
                                echo ' ' . abs($patient_change) . '% from yesterday';
                                ?>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $active_doctors; ?></div>
                            <div class="stat-label"><?php echo $clinic_id ? 'Assigned Doctors' : 'Active Doctors'; ?></div>
                            <div class="stat-trend trend-up">
                                ↗ <?php echo $clinic_id ? 'Doctors available at your clinic' : 'All doctors available'; ?>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $lab_pending; ?></div>
                            <div class="stat-label">Lab Tests Pending</div>
                            <div class="stat-trend <?php echo $lab_pending > 0 ? 'trend-down' : 'trend-up'; ?>">
                                <?php 
                                if ($lab_pending == 0) {
                                    echo '↗ All tests completed';
                                } else {
                                    echo '↘ ' . $lab_pending . ' tests in progress';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">₹<?php echo number_format($today_revenue, 0); ?></div>
                            <div class="stat-label">Today's Revenue</div>
                            <div class="stat-trend <?php echo $revenue_change >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <?php 
                                echo $revenue_change >= 0 ? '↗' : '↘'; 
                                echo ' ' . abs($revenue_change) . '% from average';
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="activity-section">
                        <h2 class="section-title">Recent Activity<?php echo $clinic_name ? ' - ' . htmlspecialchars($clinic_name) : ''; ?></h2>
                        <?php 
                        if (mysqli_num_rows($recent_activity_result) > 0) {
                            while ($activity = mysqli_fetch_assoc($recent_activity_result)) {
                                // Determine icon and styling based on activity type
                                $icon_config = [
                                    'appointment' => [
                                        'icon' => '📅',
                                        'background' => 'linear-gradient(135deg, #f0fff4, #c6f6d5)',
                                        'color' => '#38a169'
                                    ],
                                    'lab' => [
                                        'icon' => '🧪',
                                        'background' => 'linear-gradient(135deg, #fef5e7, #fad089)',
                                        'color' => '#d69e2e'
                                    ],
                                    'medicine' => [
                                        'icon' => '💊',
                                        'background' => 'linear-gradient(135deg, #e6fffa, #b2f5ea)',
                                        'color' => '#319795'
                                    ]
                                ];
                                
                                $config = $icon_config[$activity['icon_type']] ?? $icon_config['appointment'];
                                
                                // Calculate time difference
                                $time_diff = time() - strtotime($activity['activity_time']);
                                if ($time_diff < 60) {
                                    $time_ago = $time_diff . ' seconds ago';
                                } elseif ($time_diff < 3600) {
                                    $time_ago = floor($time_diff / 60) . ' minutes ago';
                                } elseif ($time_diff < 86400) {
                                    $time_ago = floor($time_diff / 3600) . ' hours ago';
                                } else {
                                    $time_ago = floor($time_diff / 86400) . ' days ago';
                                }
                        ?>
                        <div class="activity-item">
                            <div
                                class="activity-icon"
                                style="
                                    background: <?php echo $config['background']; ?>;
                                    color: <?php echo $config['color']; ?>;
                                "
                            >
                                <?php echo $config['icon']; ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?php echo htmlspecialchars($activity['activity']); ?>
                                </div>
                                <div class="activity-time"><?php echo $time_ago; ?></div>
                            </div>
                        </div>
                        <?php 
                            }
                        } else { 
                        ?>
                        <div class="activity-item">
                            <div
                                class="activity-icon"
                                style="
                                    background: linear-gradient(135deg, #f7fafc, #edf2f7);
                                    color: #718096;
                                "
                            >
                                ℹ️
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    No recent activity today
                                </div>
                                <div class="activity-time">Check back later</div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>

                    <!-- Additional Dashboard Insights -->
                    <div class="activity-section" style="margin-top: 2rem">
                        <h2 class="section-title">Quick Insights</h2>
                        <div style="padding: 1rem 0">
                            <?php
                            // Get some additional insights (filtered by clinic)
                            $upcoming_appointments_query = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date > '$today' AND status = 'confirmed'" . $clinic_where;
                            $upcoming_appointments_result = mysqli_query($conn, $upcoming_appointments_query);
                            $upcoming_appointments = mysqli_fetch_assoc($upcoming_appointments_result)['count'];

                            $completed_appointments_query = "SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = '$today' AND status = 'completed'" . $clinic_where;
                            $completed_appointments_result = mysqli_query($conn, $completed_appointments_query);
                            $completed_appointments = mysqli_fetch_assoc($completed_appointments_result)['count'];

                            // For medicine orders, if clinic-specific filtering is needed, you might need to modify the query
                            $pending_medicine_orders_query = "SELECT COUNT(*) as count FROM medicine_orders WHERE status = 'pending'";
                            if ($clinic_id) {
                                // This assumes you have a way to link medicine orders to clinics
                                // You might need to modify this based on your business logic
                                $pending_medicine_orders_query .= " AND booked_by_email IN (
                                    SELECT DISTINCT booked_by_email FROM appointments WHERE clinic_id = " . intval($clinic_id) . "
                                )";
                            }
                            $pending_medicine_orders_result = mysqli_query($conn, $pending_medicine_orders_query);
                            $pending_medicine_orders = mysqli_fetch_assoc($pending_medicine_orders_result)['count'];
                            ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                <div style="background: linear-gradient(135deg, #f7fafc, #edf2f7); border-radius: 12px; padding: 1.5rem; text-align: center;">
                                    <h3 style="color: #2d3748; font-size: 2rem; margin: 0;"><?php echo $upcoming_appointments; ?></h3>
                                    <p style="color: #718096; margin: 0.5rem 0 0 0;">Upcoming Appointments</p>
                                </div>
                                
                                <div style="background: linear-gradient(135deg, #e6fffa, #b2f5ea); border-radius: 12px; padding: 1.5rem; text-align: center;">
                                    <h3 style="color: #319795; font-size: 2rem; margin: 0;"><?php echo $completed_appointments; ?></h3>
                                    <p style="color: #2c7a7b; margin: 0.5rem 0 0 0;">Completed Today</p>
                                </div>
                                
                                <div style="background: linear-gradient(135deg, #fef5e7, #fad089); border-radius: 12px; padding: 1.5rem; text-align: center;">
                                    <h3 style="color: #d69e2e; font-size: 2rem; margin: 0;"><?php echo $pending_medicine_orders; ?></h3>
                                    <p style="color: #b7791f; margin: 0.5rem 0 0 0;">Pending Medicine Orders</p>
                                </div>
                            </div>
                            
                            <p style="margin-bottom: 1rem; color: #4a5568">
                                Dashboard showing <?php echo $clinic_name ? 'clinic-specific' : 'real-time'; ?> data from your healthcare management system. 
                                All metrics are updated automatically based on current database records<?php echo $clinic_name ? ' for ' . htmlspecialchars($clinic_name) : ''; ?>.
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>

    </body>
</html>