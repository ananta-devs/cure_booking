<?php
    // clinic_dashboard.php - Basic clinic dashboard
    session_start();

    // Check if clinic is logged in
    if (!isset($_SESSION['clinic_logged_in']) || $_SESSION['clinic_logged_in'] !== true || !isset($_SESSION['clinic_id'])) {
        header('Location: http://localhost/cure_booking/login.php');
        exit;
    }

    // Get clinic data from session
    $clinic_id = $_SESSION['clinic_id'];
    $clinic_name = $_SESSION['clinic_name'];
    $clinic_email = $_SESSION['clinic_email'];
    $clinic_location = $_SESSION['clinic_location'] ?? '';
    $contact_number = $_SESSION['contact_number'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Dashboard - <?php echo htmlspecialchars($clinic_name); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 300;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .clinic-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #2193b0;
        }

        .info-item label {
            font-weight: 600;
            color: #555;
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            color: #333;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #2193b0;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-card p {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-top: 30px;
        }

        .quick-actions h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
            color: white;
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
            display: block;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 147, 176, 0.3);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .container {
                padding: 0 15px;
            }

            .welcome-card, .quick-actions {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Clinic Dashboard</h1>
            <a href="http://localhost/cure_booking/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h2>Welcome back, <?php echo htmlspecialchars($clinic_name); ?>!</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Manage your clinic operations from this dashboard.
            </p>
            
            <div class="clinic-info">
                <div class="info-item">
                    <label>Clinic ID</label>
                    <span><?php echo htmlspecialchars($clinic_id); ?></span>
                </div>
                <div class="info-item">
                    <label>Clinic Name</label>
                    <span><?php echo htmlspecialchars($clinic_name); ?></span>
                </div>
                <div class="info-item">
                    <label>Email Address</label>
                    <span><?php echo htmlspecialchars($clinic_email); ?></span>
                </div>
                <div class="info-item">
                    <label>Address</label>
                    <span><?php echo htmlspecialchars($clinic_location ?: 'Not provided'); ?></span>
                </div>
                <div class="info-item">
                    <label>Phone Number</label>
                    <span><?php echo htmlspecialchars($contact_number ?: 'Not provided'); ?></span>
                </div>
                <div class="info-item">
                    <label>Login Time</label>
                    <span><?php echo date('M d, Y - H:i:s', $_SESSION['clinic_login_time']); ?></span>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>0</h3>
                <p>Total Appointments</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Doctors</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Patients</p>
            </div>
            <div class="stat-card">
                <h3>0</h3>
                <p>Today's Appointments</p>
            </div>
        </div>

        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <a href="#" class="action-btn">Manage Appointments</a>
                <a href="#" class="action-btn">View Doctors</a>
                <a href="#" class="action-btn">Patient Records</a>
                <a href="#" class="action-btn">Clinic Settings</a>
                <a href="#" class="action-btn">Reports</a>
                <a href="#" class="action-btn">Billing</a>
            </div>
        </div>
    </div>

    <script>
        // Simple notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
                color: ${type === 'success' ? '#155724' : '#721c24'};
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                z-index: 1000;
                max-width: 300px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Welcome message
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('Welcome to your clinic dashboard!', 'success');
        });

        // Handle quick action clicks (placeholder)
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                showNotification('This feature will be implemented soon.');
            });
        });
    </script>
</body>
</html>