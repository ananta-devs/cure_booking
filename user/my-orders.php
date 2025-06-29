<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_email'])) {
        header("Location: login.php");
        exit();
    }

    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cure_booking";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    $user_email = $_SESSION['user_email'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;

    // Get lab orders for the current user (both as customer and as booker)
    $stmt = $pdo->prepare("
        SELECT 
            lo.id, lo.booking_id, lo.customer_name, lo.phone, lo.email, lo.address,
            lo.sample_collection_date, lo.time_slot, lo.total_amount, lo.status, lo.created_at,
            lo.booked_by_email, lo.booked_by_name, lo.report_file,
            'lab' as order_type,
            GROUP_CONCAT(CONCAT(loi.test_name, ' (₹', loi.test_price, ')') SEPARATOR ', ') as item_details,
            CASE WHEN lo.email = ? THEN 'self' ELSE 'other' END as booking_for
        FROM lab_orders lo
        LEFT JOIN lab_order_items loi ON lo.id = loi.order_id
        WHERE lo.email = ? OR lo.booked_by_email = ?
        GROUP BY lo.id
        ORDER BY lo.created_at DESC
    ");
    $stmt->execute([$user_email, $user_email, $user_email]);
    $lab_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get medicine orders for the current user (both as customer and as booker)
    $medicine_orders = [];
    try {
        $medicine_stmt = $pdo->prepare("
            SELECT 
                mo.id, mo.order_number, mo.name as customer_name, mo.phone, mo.email, mo.address,
                mo.total_amount, mo.status, mo.order_date, mo.created_at,
                mo.booked_by_email, mo.booked_by_name,
                'medicine' as order_type,
                COALESCE(GROUP_CONCAT(CONCAT(moi.medicine_name, ' (₹', moi.medicine_price, ' × ', moi.quantity, ')') SEPARATOR ', '), 'Order details not available') as item_details,
                CASE WHEN mo.email = ? THEN 'self' ELSE 'other' END as booking_for
            FROM medicine_orders mo
            LEFT JOIN medicine_order_items moi ON mo.id = moi.order_id
            WHERE mo.email = ? OR mo.booked_by_email = ?
            GROUP BY mo.id
            ORDER BY mo.created_at DESC
        ");
        $medicine_stmt->execute([$user_email, $user_email, $user_email]);
        $medicine_orders = $medicine_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Medicine orders error: " . $e->getMessage());
        $medicine_orders = [];
    }

    // Combine and sort all user orders
    $all_orders = array_merge($lab_orders, $medicine_orders);
    usort($all_orders, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Status configurations
    $status_config = [
        'lab' => [
            'colors' => [
                'Pending' => '#ff9800', 'Confirmed' => '#2196f3', 'Sample Collected' => '#9c27b0',
                'In Progress' => '#607d8b', 'Upload Done' => '#4caf50', 'Completed' => '#4caf50',
                'Cancelled' => '#f44336'
            ],
            'icons' => [
                'Pending' => '⏳', 'Confirmed' => '✅', 'Sample Collected' => '🩸',
                'In Progress' => '🔬', 'Upload Done' => '📊', 'Completed' => '✅',
                'Cancelled' => '❌'
            ]
        ],
        'medicine' => [
            'colors' => [
                'pending' => '#ff9800', 'confirmed' => '#2196f3', 'shipped' => '#9c27b0',
                'delivered' => '#4caf50', 'cancelled' => '#f44336'
            ],
            'icons' => [
                'pending' => '⏳', 'confirmed' => '✅', 'shipped' => '🚚',
                'delivered' => '📦', 'cancelled' => '❌'
            ]
        ]
    ];

    // Helper functions
    function getStatusColor($status, $type) {
        global $status_config;
        $key = $type === 'medicine' ? strtolower($status) : $status;
        return $status_config[$type]['colors'][$key] ?? '#757575';
    }

    function getStatusIcon($status, $type) {
        global $status_config;
        $key = $type === 'medicine' ? strtolower($status) : $status;
        return $status_config[$type]['icons'][$key] ?? ($type === 'lab' ? '📋' : '💊');
    }

    function isLabReportAvailable($status) {
        return $status === 'Upload Done';
    }

    function isReceiptAvailable($status, $type) {
        if ($type === 'lab') {
            return in_array($status, ['Confirmed', 'Sample Collected', 'In Progress', 'Upload Done', 'Completed']);
        } else {
            return in_array(strtolower($status), ['confirmed', 'shipped', 'delivered']);
        }
    }

    function getShortItemPreview($item_details, $maxLength = 80) {
        if (empty($item_details) || $item_details === 'Order details not available') {
            return 'No items available';
        }
        
        $items = explode(', ', $item_details);
        $preview = '';
        $itemCount = 0;
        
        foreach ($items as $item) {
            if (strlen($preview . $item) > $maxLength && $itemCount > 0) {
                $remaining = count($items) - $itemCount;
                if ($remaining > 0) $preview .= "... +{$remaining} more";
                break;
            }
            if ($itemCount > 0) $preview .= ', ';
            $preview .= $item;
            $itemCount++;
        }
        return $preview;
    }

    function getLabReportUrl($report_file) {
        if (empty($report_file)) {
            return null;
        }
        return 'http://localhost/cure_booking/adminhub/lab-bookings/uploads/lab_reports/' . $report_file;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - CureBooking </title>
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="stylesheet" href="order.css">
    <style>
        .download-report-btn {
            background: linear-gradient(135deg, #4caf50, #45a049);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-left: 8px;
        }

        .download-report-btn:hover {
            background: linear-gradient(135deg, #45a049, #4caf50);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .download-report-btn:active {
            transform: translateY(0);
        }

        .download-receipt-btn {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-left: 8px;
        }

        .download-receipt-btn:hover {
            background: linear-gradient(135deg, #1976d2, #2196f3);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }

        .download-receipt-btn:active {
            transform: translateY(0);
        }

        .action-buttons {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .report-section,
        .receipt-section {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .report-section {
            border-left: 4px solid #4caf50;
        }

        .receipt-section {
            border-left: 4px solid #2196f3;
        }

        .report-available,
        .receipt-available {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .report-available p {
            margin: 0;
            color: #4caf50;
            font-weight: 500;
        }

        .receipt-available p {
            margin: 0;
            color: #2196f3;
            font-weight: 500;
        }

        .report-pending p {
            margin: 0;
            color: #ff9800;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .download-report-btn,
            .download-receipt-btn {
                margin-left: 0;
                justify-content: center;
            }

            .report-available,
            .receipt-available {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include '../include/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1>My Orders </h1>
            <p>Track your lab tests and medicine orders</p>
        </div>

        <div class="order-type-filter">
            <button class="filter-btn active" onclick="filterOrders('all')">All Orders</button>
            <button class="filter-btn" onclick="filterOrders('lab')">🧪 Lab Orders</button>
            <button class="filter-btn" onclick="filterOrders('medicine')">💊 Medicine Orders</button>
        </div>

        <?php if (!empty($all_orders)): ?>
            <?php foreach ($all_orders as $index => $order): ?>
                <div class="order-card <?php echo $order['order_type']; ?>-order" data-type="<?php echo $order['order_type']; ?>">
                    <div class="order-header">
                        <div class="order-icon <?php echo $order['order_type']; ?>">
                            <?php echo $order['order_type'] === 'lab' ? '🧪' : '💊'; ?>
                        </div>
                        
                        <div class="order-main-content">
                            <div class="order-title-row">
                                <div class="order-basic-info">
                                    <h3><?php echo $order['order_type'] === 'lab' ? 'Lab Tests' : 'Medicines'; ?></h3>
                                    <?php if ($order['order_type'] === 'lab' && !empty($order['booking_id'])): ?>
                                        <div class="booking-id">ID: <?php echo htmlspecialchars($order['booking_id']); ?></div>
                                    <?php elseif ($order['order_type'] === 'medicine' && !empty($order['order_number'])): ?>
                                        <div class="booking-id">Order: <?php echo htmlspecialchars($order['order_number']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($order['booking_for'] === 'other'): ?>
                                        <small style="color: #666;">Booked for: <?php echo htmlspecialchars($order['customer_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="order-amount">₹<?php echo number_format($order['total_amount'], 0); ?></div>
                            </div>
                            
                            <div class="items-preview">
                                <?php echo htmlspecialchars(getShortItemPreview($order['item_details'])); ?>
                            </div>
                            
                            <div class="status-and-details">
                                <div class="status-badge" style="background-color: <?php echo getStatusColor($order['status'], $order['order_type']); ?>">
                                    <?php echo getStatusIcon($order['status'], $order['order_type']); ?>
                                    <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                                </div>
                                <div class="action-buttons">
                                    <button class="more-details-btn" onclick="toggleDetails(<?php echo $index; ?>)">More Details</button>
                                    
                                    <?php if ($order['order_type'] === 'lab' && $order['status'] === 'Upload Done' && !empty($order['report_file'])): ?>
                                        <a href="<?php echo getLabReportUrl($order['report_file']); ?>" 
                                           class="download-report-btn" 
                                           download="<?php echo htmlspecialchars($order['booking_id'] . '_lab_report.pdf'); ?>"
                                           target="_blank"
                                           onclick="return downloadReport(this)">
                                            📄 Download Report
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (isReceiptAvailable($order['status'], $order['order_type'])): ?>
                                        <a href="download-receipt.php?order_id=<?php echo $order['id']; ?>&type=<?php echo $order['order_type']; ?>" 
                                           class="download-receipt-btn" 
                                           onclick="return downloadReceipt(this)">
                                            🧾 Download Receipt
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="order-details" id="details-<?php echo $index; ?>">
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">👤 Patient:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">📞 Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">📧 Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">📅 Order Date:</span>
                                <span class="detail-value"><?php echo date('d M Y, g:i A', strtotime($order['created_at'])); ?></span>
                            </div>
                            
                            <?php if ($order['order_type'] === 'lab'): ?>
                                <div class="detail-item">
                                    <span class="detail-label">🩸 Collection:</span>
                                    <span class="detail-value"><?php echo date('d M Y', strtotime($order['sample_collection_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">🕐 Time Slot:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['time_slot']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($order['booking_for'] === 'other'): ?>
                                <div class="detail-item">
                                    <span class="detail-label">📝 Booked by:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['booked_by_name'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">📧 Booker Email:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['booked_by_email'] ?? 'N/A'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <span class="detail-label">📍 Address:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['address']); ?></span>
                        </div>

                        <?php if ($order['item_details'] && $order['item_details'] !== 'Order details not available'): ?>
                            <div class="items-section">
                                <div class="items-title">
                                    <?php echo $order['order_type'] === 'lab' ? '🧪 All Ordered Tests:' : '💊 All Ordered Medicines:'; ?>
                                </div>
                                <div class="items-list"><?php echo htmlspecialchars($order['item_details']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($order['order_type'] === 'lab' && $order['status'] === 'Upload Done'): ?>
                            <div class="report-section">
                                <div class="items-title">📊 Lab Report:</div>
                                <?php if (!empty($order['report_file'])): ?>
                                    <div class="report-available">
                                        <p>✅ Your lab report is ready for download!</p>
                                        <a href="<?php echo getLabReportUrl($order['report_file']); ?>" 
                                           class="download-report-btn" 
                                           download="<?php echo htmlspecialchars($order['booking_id'] . '_lab_report.pdf'); ?>"
                                           target="_blank"
                                           onclick="return downloadReport(this)">
                                            📄 Download Lab Report
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="report-pending">
                                        <p>⏳ Report file is being processed. Please check back later.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isReceiptAvailable($order['status'], $order['order_type'])): ?>
                            <div class="receipt-section">
                                <div class="items-title">🧾 Receipt:</div>
                                <div class="receipt-available">
                                    <p>✅ Receipt is available for download</p>
                                    <a href="download-receipt.php?order_id=<?php echo $order['id']; ?>&type=<?php echo $order['order_type']; ?>" 
                                       class="download-receipt-btn" 
                                       onclick="return downloadReceipt(this)">
                                        🧾 Download Receipt
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-orders">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                <h3>No Orders Found</h3>
                <p>You haven't placed any orders yet.</p>
                <div style="margin-top: 1rem;">
                    <a href="../lab-booking/" style="color: #007bff; text-decoration: none; margin-right: 1rem;">📋 Book Lab Tests</a>
                    <a href="../medicine-booking/" style="color: #007bff; text-decoration: none;">💊 Order Medicines</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterOrders(type) {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            document.querySelectorAll('.order-card').forEach(order => {
                order.classList.toggle('hidden', type !== 'all' && order.dataset.type !== type);
            });
        }

        function toggleDetails(index) {
            const detailsElement = document.getElementById(`details-${index}`);
            const button = event.target;
            
            if (detailsElement.classList.contains('expanded')) {
                detailsElement.classList.remove('expanded');
                button.textContent = 'More Details';
                button.classList.remove('active');
            } else {
                detailsElement.classList.add('expanded');
                button.textContent = 'Less Details';
                button.classList.add('active');
            }
        }

        function downloadReport(element) {
            // Show loading state
            const originalText = element.innerHTML;
            element.innerHTML = '⏳ Downloading...';
            element.style.pointerEvents = 'none';
            
            // Reset button state after 2 seconds
            setTimeout(() => {
                element.innerHTML = originalText;
                element.style.pointerEvents = 'auto';
            }, 2000);
            
            // Check if file exists (basic check)
            fetch(element.href, { method: 'HEAD' })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('File not found');
                    }
                })
                .catch(error => {
                    alert('Sorry, the report file could not be found. Please contact support.');
                    element.innerHTML = originalText;
                    element.style.pointerEvents = 'auto';
                    return false;
                });
            
            return true;
        }

        function downloadReceipt(element) {
            // Show loading state
            const originalText = element.innerHTML;
            element.innerHTML = '⏳ Generating...';
            element.style.pointerEvents = 'none';
            
            // Reset button state after 2 seconds
            setTimeout(() => {
                element.innerHTML = originalText;
                element.style.pointerEvents = 'auto';
            }, 2000);
            
            return true;
        }
    </script>
</body>
</html>