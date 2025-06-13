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

// Get user information
$user_email = $_SESSION['user_email'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Get user's lab orders with items
$stmt = $pdo->prepare("
    SELECT 
        lo.id,
        lo.booking_id,
        lo.customer_name,
        lo.phone,
        lo.email,
        lo.address,
        lo.sample_collection_date,
        lo.time_slot,
        lo.total_amount,
        lo.status,
        lo.created_at,
        lo.notes,
        'lab' as order_type,
        GROUP_CONCAT(
            CONCAT(loi.test_name, ' (₹', loi.test_price, ')')
            SEPARATOR ', '
        ) as item_details
    FROM lab_orders lo
    LEFT JOIN lab_order_items loi ON lo.id = loi.order_id
    WHERE lo.email = ?
    GROUP BY lo.id
    ORDER BY lo.created_at DESC
");

$stmt->execute([$user_email]);
$lab_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's medicine orders with items
$medicine_stmt = $pdo->prepare("
    SELECT 
        mo.id,
        mo.order_number,
        mo.name as customer_name,
        mo.phone,
        mo.email,
        mo.address,
        mo.total_amount,
        mo.status,
        mo.order_date,
        mo.created_at,
        mo.updated_at,
        'medicine' as order_type,
        COALESCE(
            GROUP_CONCAT(
                CONCAT(moi.medicine_name, ' (₹', moi.medicine_price, ' × ', moi.quantity, ')')
                SEPARATOR ', '
            ),
            'Order details not available'
        ) as item_details
    FROM medicine_orders mo
    LEFT JOIN medicine_order_items moi ON mo.id = moi.order_id
    WHERE (mo.booked_by_user_id = ? AND mo.booked_by_user_id IS NOT NULL) 
       OR mo.email = ?
    GROUP BY mo.id
    ORDER BY mo.created_at DESC
");

$medicine_stmt->execute([$user_id, $user_email]);
$medicine_orders = $medicine_stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine all orders
$all_orders = array_merge($lab_orders, $medicine_orders);

// Sort all orders by creation date (newest first)
usort($all_orders, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Status color mapping for lab orders
function getLabStatusColor($status) {
    switch($status) {
        case 'Pending':
            return '#ff9800';
        case 'Confirmed':
            return '#2196f3';
        case 'Sample Collected':
            return '#9c27b0';
        case 'In Progress':
            return '#607d8b';
        case 'Completed':
            return '#4caf50';
        case 'Cancelled':
            return '#f44336';
        default:
            return '#757575';
    }
}

// Status color mapping for medicine orders
function getMedicineStatusColor($status) {
    switch(strtolower($status)) {
        case 'pending':
            return '#ff9800';
        case 'confirmed':
            return '#2196f3';
        case 'shipped':
            return '#9c27b0';
        case 'delivered':
            return '#4caf50';
        case 'cancelled':
            return '#f44336';
        default:
            return '#757575';
    }
}

// Status icon mapping for lab orders
function getLabStatusIcon($status) {
    switch($status) {
        case 'Pending':
            return '⏳';
        case 'Confirmed':
            return '✅';
        case 'Sample Collected':
            return '🩸';
        case 'In Progress':
            return '🔬';
        case 'Completed':
            return '✅';
        case 'Cancelled':
            return '❌';
        default:
            return '📋';
    }
}

// Status icon mapping for medicine orders
function getMedicineStatusIcon($status) {
    switch(strtolower($status)) {
        case 'pending':
            return '⏳';
        case 'confirmed':
            return '✅';
        case 'shipped':
            return '🚚';
        case 'delivered':
            return '📦';
        case 'cancelled':
            return '❌';
        default:
            return '💊';
    }
}

// Get appropriate status color
function getStatusColor($status, $type) {
    return $type === 'lab' ? getLabStatusColor($status) : getMedicineStatusColor($status);
}

// Get appropriate status icon
function getStatusIcon($status, $type) {
    return $type === 'lab' ? getLabStatusIcon($status) : getMedicineStatusIcon($status);
}

// Function to get short item preview (first few items)
function getShortItemPreview($item_details, $maxLength = 80) {
    if (empty($item_details)) {
        return 'No items available';
    }
    
    // Split items by comma and take first few
    $items = explode(', ', $item_details);
    $preview = '';
    $itemCount = 0;
    
    foreach ($items as $item) {
        if (strlen($preview . $item) > $maxLength && $itemCount > 0) {
            $remaining = count($items) - $itemCount;
            if ($remaining > 0) {
                $preview .= "... +{$remaining} more";
            }
            break;
        }
        
        if ($itemCount > 0) {
            $preview .= ', ';
        }
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
    <title>My Orders - CureBooking</title>
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            color: #512da8;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        .order-type-filter {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .filter-btn {
            padding: 0.5rem 1.5rem;
            border: 2px solid #512da8;
            background: white;
            color: #512da8;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: #512da8;
            color: white;
        }

        /* Updated Order Cards */
        .order-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .order-card.lab-order {
            border-left: 4px solid #512da8;
        }

        .order-card.medicine-order {
            border-left: 4px solid #e74c3c;
        }

        /* Updated Header Layout */
        .order-header {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            background: #fafafa;
            border-bottom: 1px solid #eee;
        }

        .order-icon {
            font-size: 1.5rem;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .order-icon.lab {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
        }

        .order-icon.medicine {
            background: linear-gradient(135deg, #fd79a8, #e84393);
        }

        .order-main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0; /* Allows text to wrap properly */
        }

        .order-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .order-basic-info h3 {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 4px;
        }

        .order-id {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .order-amount {
            font-size: 1.3rem;
            font-weight: bold;
            color: #27ae60;
            text-align: right;
            flex-shrink: 0;
        }

        .items-preview {
            font-size: 0.85rem;
            color: #555;
            line-height: 1.4;
            margin: 4px 0;
        }

        .status-and-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .more-details-btn {
            background: none;
            border: 1px solid #ddd;
            color: #666;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .more-details-btn:hover {
            background: #f0f0f0;
            border-color: #bbb;
        }

        .more-details-btn.active {
            background: #512da8;
            color: white;
            border-color: #512da8;
        }

        /* Expandable Details */
        .order-details {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .order-details.expanded {
            max-height: 1000px;
            padding: 20px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
        }

        .detail-label {
            font-weight: 600;
            color: #333;
            min-width: 100px;
            font-size: 0.9rem;
        }

        .detail-value {
            color: #666;
            font-size: 0.9rem;
        }

        .items-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }

        .items-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .items-list {
            color: #666;
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .notes-section {
            margin-top: 16px;
            padding: 12px;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }

        .notes-section h5 {
            color: #856404;
            margin-bottom: 4px;
            font-size: 0.9rem;
        }

        .notes-section p {
            color: #856404;
            margin: 0;
            font-size: 0.85rem;
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .no-orders h3 {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .no-orders p {
            color: #999;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #74b9ff;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            margin: 0.5rem;
            transition: background 0.3s ease;
        }

        .back-btn:hover {
            background: #0984e3;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .order-header {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
            }

            .order-title-row {
                flex-direction: column;
                gap: 8px;
            }

            .order-amount {
                text-align: left;
            }

            .status-and-details {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .detail-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-label {
                min-width: auto;
            }

            .order-type-filter {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../include/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1>My Orders</h1>
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
                                    <h3>
                                        <?php if ($order['order_type'] === 'lab'): ?>
                                            Lab Tests
                                        <?php else: ?>
                                            Medicines
                                        <?php endif; ?>
                                    </h3>
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
                                <button class="more-details-btn" onclick="toggleDetails(<?php echo $index; ?>)">
                                    More Details
                                </button>
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
                                <span class="detail-value">
                                    <?php echo date('d M Y, g:i A', strtotime($order['created_at'])); ?>
                                </span>
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

                        <?php if ($order['item_details']): ?>
                            <div class="items-section">
                                <div class="items-title">
                                    <?php if ($order['order_type'] === 'lab'): ?>
                                        🧪 All Ordered Tests:
                                    <?php else: ?>
                                        💊 All Ordered Medicines:
                                    <?php endif; ?>
                                </div>
                                <div class="items-list">
                                    <?php echo htmlspecialchars($order['item_details']); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($order['order_type'] === 'lab' && !empty($order['notes'])): ?>
                            <div class="notes-section">
                                <h5>📝 Additional Notes:</h5>
                                <p><?php echo htmlspecialchars($order['notes']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-orders">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                <h3>No Orders Found</h3>
                <p>You haven't placed any orders yet. Start by exploring our services!</p>
                <br>
                <a href="http://localhost/cure_booking/lab-new/lab.php" class="back-btn">
                    🧪 Browse Lab Tests
                </a>
                <a href="http://localhost/cure_booking/medicine/medicine.php" class="back-btn" style="background: #e74c3c;">
                    💊 Browse Medicines
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterOrders(type) {
            // Update active button
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show/hide orders
            const orders = document.querySelectorAll('.order-card');
            orders.forEach(order => {
                if (type === 'all') {
                    order.classList.remove('hidden');
                } else {
                    if (order.dataset.type === type) {
                        order.classList.remove('hidden');
                    } else {
                        order.classList.add('hidden');
                    }
                }
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