<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['email'])) {
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

    $user_email = $_SESSION['email'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;

    // Debug: Show current session info
    echo "<!-- DEBUG: Session Email: " . $user_email . " -->";
    echo "<!-- DEBUG: Session User ID: " . $user_id . " -->";

    // Get ALL lab orders with items (for testing)
    $stmt = $pdo->prepare("
        SELECT 
            lo.id, lo.booking_id, lo.customer_name, lo.phone, lo.email, lo.address,
            lo.sample_collection_date, lo.time_slot, lo.total_amount, lo.status, lo.created_at,
            lo.booked_by_email, lo.booked_by_name,
            'lab' as order_type,
            GROUP_CONCAT(CONCAT(loi.test_name, ' (₹', loi.test_price, ')') SEPARATOR ', ') as item_details,
            CASE WHEN lo.email = ? THEN 'self' ELSE 'other' END as booking_for
        FROM lab_orders lo
        LEFT JOIN lab_order_items loi ON lo.id = loi.order_id
        GROUP BY lo.id
        ORDER BY lo.created_at DESC
    ");
    $stmt->execute([$user_email]);
    $lab_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Total lab orders in DB: " . count($lab_orders) . " -->";

    // Get ALL medicine orders with items (for testing)
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
            GROUP BY mo.id
            ORDER BY mo.created_at DESC
        ");
        $medicine_stmt->execute([$user_email]);
        $medicine_orders = $medicine_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<!-- DEBUG: Total medicine orders in DB: " . count($medicine_orders) . " -->";
    } catch(PDOException $e) {
        error_log("Medicine orders error: " . $e->getMessage());
        echo "<!-- DEBUG: Medicine orders error: " . $e->getMessage() . " -->";
        $medicine_orders = [];
    }

    // Now filter for current user
    $user_lab_orders = array_filter($lab_orders, function($order) use ($user_email) {
        return $order['email'] === $user_email || $order['booked_by_email'] === $user_email;
    });

    $user_medicine_orders = array_filter($medicine_orders, function($order) use ($user_email) {
        return $order['email'] === $user_email || $order['booked_by_email'] === $user_email;
    });

    echo "<!-- DEBUG: User's lab orders: " . count($user_lab_orders) . " -->";
    echo "<!-- DEBUG: User's medicine orders: " . count($user_medicine_orders) . " -->";

    // Show what emails exist in the database
    $all_emails = [];
    foreach ($lab_orders as $order) {
        if (!empty($order['email'])) $all_emails[] = $order['email'];
        if (!empty($order['booked_by_email'])) $all_emails[] = $order['booked_by_email'];
    }
    foreach ($medicine_orders as $order) {
        if (!empty($order['email'])) $all_emails[] = $order['email'];
        if (!empty($order['booked_by_email'])) $all_emails[] = $order['booked_by_email'];
    }
    $all_emails = array_unique($all_emails);
    echo "<!-- DEBUG: All emails in DB: " . implode(', ', $all_emails) . " -->";

    // Combine and sort all user orders
    $all_orders = array_merge($user_lab_orders, $user_medicine_orders);
    usort($all_orders, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // For testing: Show ALL orders if no user orders found
    if (empty($all_orders)) {
        echo "<!-- DEBUG: No user orders found, showing all orders for testing -->";
        $all_orders = array_merge($lab_orders, $medicine_orders);
        usort($all_orders, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
    }

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
            return in_array($status, ['Upload Done', 'Sample Collected', 'Completed']);
        }
        return in_array(strtolower($status), ['confirmed', 'shipped', 'delivered']);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - CureBooking </title>
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="stylesheet" href="order.css">
</head>
<body>
    <?php include '../include/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1>My Orders </h1>
            <p>Track your lab tests and medicine orders</p>
            <p><small>Session Email: <?php echo htmlspecialchars($user_email); ?></small></p>
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
                                    <small style="color: #666;">Customer: <?php echo htmlspecialchars($order['email']); ?></small>
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
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-orders">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                <h3>No Orders Found</h3>
                <p>Current session email: <?php echo htmlspecialchars($user_email); ?></p>
                <p>Check the browser console for debug information.</p>
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
    </script>
</body>
</html>