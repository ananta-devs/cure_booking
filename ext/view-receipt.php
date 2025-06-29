<?php
// view-receipt.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Check if order ID and type are provided
if (!isset($_GET['order_id']) || !isset($_GET['type'])) {
    die("Invalid request");
}

$order_id = (int)$_GET['order_id'];
$order_type = $_GET['type'];

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

if ($order_type === 'lab') {
    // Get lab order details with items
    $stmt = $pdo->prepare("
        SELECT 
            lo.*,
            loi.test_name,
            loi.test_price
        FROM lab_orders lo
        LEFT JOIN lab_order_items loi ON lo.id = loi.order_id
        WHERE lo.id = ? AND ((lo.booked_by_user_id = ? AND lo.booked_by_user_id IS NOT NULL) OR lo.email = ?)
        ORDER BY loi.id
    ");
    $stmt->execute([$order_id, $user_id, $user_email]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Get medicine order details with items
    $stmt = $pdo->prepare("
        SELECT 
            mo.*,
            mo.name as customer_name,
            mo.order_date as created_at,
            moi.medicine_name as test_name,
            moi.medicine_price as test_price,
            moi.quantity
        FROM medicine_orders mo
        LEFT JOIN medicine_order_items moi ON mo.id = moi.order_id
        WHERE mo.id = ? AND ((mo.booked_by_user_id = ? AND mo.booked_by_user_id IS NOT NULL) OR mo.email = ?)
        ORDER BY moi.id
    ");
    $stmt->execute([$order_id, $user_id, $user_email]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$order_items) {
    die("Order not found or access denied");
}

$order = $order_items[0]; // First row contains order info
$receipt_number = $order_type === 'lab' ? 'LAB-' . $order['id'] : 'MED-' . $order['order_number'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $receipt_number; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #512da8, #667eea);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .receipt-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }
        
        .receipt-header .content {
            position: relative;
            z-index: 1;
        }
        
        .company-name {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .company-tagline {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .receipt-number {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 25px;
            margin-top: 15px;
            display: inline-block;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .receipt-body {
            padding: 40px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #512da8;
        }
        
        .info-section h3 {
            color: #512da8;
            margin-bottom: 15px;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
        }
        
        .info-value {
            color: #666;
            text-align: right;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            font-size: 0.9em;
            text-transform: uppercase;
        }
        
        .status-pending { background: #ff9800; }
        .status-confirmed { background: #2196f3; }
        .status-completed { background: #4caf50; }
        .status-delivered { background: #4caf50; }
        .status-cancelled { background: #f44336; }
        .status-shipped { background: #9c27b0; }
        
        .items-section {
            margin: 30px 0;
        }
        
        .items-section h3 {
            color: #512da8;
            margin-bottom: 20px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .items-table th {
            background: #512da8;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .items-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .items-table tr:hover {
            background: #e3f2fd;
        }
        
        .total-section {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            margin: 30px 0;
        }
        
        .total-amount {
            font-size: 2em;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .notes-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .notes-section h4 {
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1em;
        }
        
        .btn-primary {
            background: #512da8;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3f1a83;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }
        
        .footer {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            color: #666;
        }
        
        .footer .thank-you {
            font-size: 1.3em;
            font-weight: bold;
            color: #512da8;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .items-table {
                font-size: 0.9em;
            }
            
            .items-table th,
            .items-table td {
                padding: 10px 8px;
            }
            
            .company-name {
                font-size: 2em;
            }
            
            .total-amount {
                font-size: 1.5em;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .action-buttons {
                display: none;
            }
            
            .receipt-header {
                background: #512da8 !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="content">
                <div class="company-name">🏥 CUREBOOKING</div>
                <div class="company-tagline">Your Health, Our Priority</div>
                <div class="receipt-number">Receipt: <?php echo $receipt_number; ?></div>
            </div>
        </div>

        <div class="receipt-body">
            <div class="info-grid">
                <div class="info-section">
                    <h3>📋 Order Information</h3>
                    <div class="info-item">
                        <span class="info-label">Order Type:</span>
                        <span class="info-value"><?php echo strtoupper($order_type); ?> ORDER</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value"><?php echo date('d M Y, g:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </span>
                    </div>
                    <?php if ($order_type === 'lab'): ?>
                        <div class="info-item">
                            <span class="info-label">Collection Date:</span>
                            <span class="info-value"><?php echo date('d M Y', strtotime($order['sample_collection_date'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Time Slot:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['time_slot']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-section">
                    <h3>👤 Patient Details</h3>
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['name'] ?? $order['customer_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                    </div>
                    <?php if (isset($order['address'])): ?>
                    <div class="info-item">
                        <span class="info-label">Address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['address']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($order_type === 'lab' && isset($order['age'])): ?>
                    <div class="info-item">
                        <span class="info-label">Age:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['age']); ?> years</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Gender:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['gender']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="items-section">
                <h3>🛍️ <?php echo $order_type === 'lab' ? 'Lab Tests' : 'Medicines'; ?> Ordered</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th><?php echo $order_type === 'lab' ? 'Test Name' : 'Medicine Name'; ?></th>
                            <?php if ($order_type === 'medicine'): ?>
                            <th>Quantity</th>
                            <?php endif; ?>
                            <th>Price</th>
                            <?php if ($order_type === 'medicine'): ?>
                            <th>Subtotal</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach ($order_items as $item): 
                            if (!empty($item['test_name'])):
                                $item_price = (float)$item['test_price'];
                                $quantity = $order_type === 'medicine' ? (int)($item['quantity'] ?? 1) : 1;
                                $subtotal = $item_price * $quantity;
                                $grand_total += $subtotal;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['test_name']); ?></td>
                            <?php if ($order_type === 'medicine'): ?>
                            <td><?php echo $quantity; ?></td>
                            <?php endif; ?>
                            <td>₹<?php echo number_format($item_price, 2); ?></td>
                            <?php if ($order_type === 'medicine'): ?>
                            <td>₹<?php echo number_format($subtotal, 2); ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div>Total Amount</div>
                <div class="total-amount">₹<?php echo number_format($grand_total, 2); ?></div>
            </div>

            <?php if (!empty($order['special_instructions']) || !empty($order['notes'])): ?>
            <div class="notes-section">
                <h4>📝 Special Instructions</h4>
                <p><?php echo htmlspecialchars($order['special_instructions'] ?? $order['notes']); ?></p>
            </div>
            <?php endif; ?>

            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">
                    🖨️ Print Receipt
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    🏠 Back to Dashboard
                </a>
                <a href="<?php echo $order_type; ?>-orders.php" class="btn btn-success">
                    📋 View All Orders
                </a>
            </div>
        </div>

        <div class="footer">
            <div class="thank-you">Thank you for choosing CureBooking!</div>
            <p>For any queries, please contact us at support@curebooking.com or call +91-XXXXXXXXXX</p>
            <p style="margin-top: 10px; font-size: 0.9em;">
                This is a computer-generated receipt and does not require a signature.
            </p>
        </div>
    </div>

    <script>
        // Auto-print functionality (optional)
        function printReceipt() {
            window.print();
        }

        // Add keyboard shortcut for printing
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printReceipt();
            }
        });

        // Smooth scroll to top when page loads
        window.addEventListener('load', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>