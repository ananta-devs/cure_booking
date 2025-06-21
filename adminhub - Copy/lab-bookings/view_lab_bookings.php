<?php

    // Include database connection
    require_once '../include/database_connection.php';

    // Start session for potential flash messages
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['adm_id'])) {
        header("Location: http://localhost/cure_booking/adminhub/login.php");
        exit();
    }

    // Check connection
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Handle status update if form submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'updateStatus') {
        $bookingId = $_POST['booking_id'];
        $newStatus = $_POST['new_status'];
        
        // SQL to update booking status
        $updateSql = "UPDATE lab_orders SET status = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ss", $newStatus, $bookingId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Booking status updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating status: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect to avoid form resubmission
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }

    // Enhanced query to get all bookings with their test items
    $sql = "SELECT 
                lo.*,
                GROUP_CONCAT(DISTINCT loi.test_name ORDER BY loi.test_name SEPARATOR ', ') as test_names,
                GROUP_CONCAT(DISTINCT CONCAT(loi.test_name, ' (₹', FORMAT(loi.test_price, 2), ')') ORDER BY loi.test_name SEPARATOR '<br>') as test_details,
                COUNT(DISTINCT loi.id) as total_tests,
                SUM(loi.subtotal) as calculated_total
            FROM lab_orders lo 
            LEFT JOIN lab_order_items loi ON lo.id = loi.order_id 
            GROUP BY lo.id, lo.booking_id, lo.customer_name, lo.phone, lo.email, lo.address, 
                     lo.sample_collection_date, lo.time_slot, lo.total_amount, lo.status, lo.created_at
            ORDER BY lo.created_at DESC";
    $result = $conn->query($sql);
    
    // Count bookings by status
    $countSql = "SELECT status, COUNT(*) as count FROM lab_orders GROUP BY status";
    $countResult = $conn->query($countSql);
    
    $statusCounts = [
        'Pending' => 0,
        'Confirmed' => 0,
        'Sample Collected' => 0,
        'In Progress' => 0,
        'Completed' => 0,
        'Cancelled' => 0,
        'Total' => 0
    ];
    
    if ($countResult->num_rows > 0) {
        while($row = $countResult->fetch_assoc()) {
            $statusCounts[$row['status']] = $row['count'];
            $statusCounts['Total'] += $row['count'];
        }
    }

    // Enhanced function to get test details for a specific order
    function getTestDetailsForOrder($conn, $orderId) {
        $testSql = "SELECT test_name, test_price, sample_type, quantity, subtotal 
                   FROM lab_order_items 
                   WHERE order_id = ? 
                   ORDER BY test_name";
        $stmt = $conn->prepare($testSql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $testResult = $stmt->get_result();
        
        $tests = [];
        while($testRow = $testResult->fetch_assoc()) {
            $tests[] = $testRow;
        }
        return $tests;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Order Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="styles.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .test-list {
            max-width: 300px;
            position: relative;
        }
        
        .test-preview {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .test-names-container {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.9em;
        }
        
        .test-summary {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 3px;
        }
        
        .test-count {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
            white-space: nowrap;
        }
        
        .test-total {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.75em;
            font-weight: bold;
            white-space: nowrap;
        }
        
        .test-details {
            font-size: 0.85em;
            color: #666;
            margin-top: 2px;
        }
        
        .modal-tests {
            margin-top: 15px;
        }
        
        .test-item {
            background: #f8f9fa;
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .test-item h4 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 1.05em;
        }
        
        .test-item-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px;
        }
        
        .test-item p {
            margin: 0;
            font-size: 0.9em;
            color: #666;
        }
        
        .test-item .price-highlight {
            color: #28a745;
            font-weight: bold;
        }
        
        .amount-highlight {
            font-weight: bold;
            color: #28a745;
            font-size: 1.05em;
        }
        
        .modal-tests-summary {
            background: #f0f8ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #d0e7ff;
        }
        
        .tests-total-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
        }
        
        /* Tooltip for test names */
        .test-tooltip {
            position: relative;
            cursor: pointer;
        }
        
        .test-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        .tooltip-text {
            visibility: hidden;
            opacity: 0;
            width: 300px;
            background-color: #333;
            color: white;
            text-align: left;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -150px;
            transition: opacity 0.3s;
            font-size: 0.85em;
            line-height: 1.4;
        }
        
        .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .test-list {
                max-width: 200px;
            }
            
            .test-item-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <?php include '../include/sidebar.php'; ?>
    <!-- SIDEBAR -->
    
    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include '../include/top-header.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="container">
                <header>
                    <h1>Lab Order Management</h1>
                    <p>Manage and update the status of lab bookings</p>
                    
                    <div class="status-summary">
                        <div class="status-card pending">
                            <h3>Pending</h3>
                            <p id="pending-count"><?php echo $statusCounts['Pending']; ?></p>
                        </div>
                        <div class="status-card confirmed">
                            <h3>Confirmed</h3>
                            <p id="confirmed-count"><?php echo $statusCounts['Confirmed']; ?></p>
                        </div>
                        <div class="status-card completed">
                            <h3>Completed</h3>
                            <p id="completed-count"><?php echo $statusCounts['Completed']; ?></p>
                        </div>
                        <div class="status-card total">
                            <h3>Total</h3>
                            <p id="total-count"><?php echo $statusCounts['Total']; ?></p>
                        </div>
                    </div>
                </header>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                        <?php 
                            echo $_SESSION['message']; 
                            unset($_SESSION['message']);
                            unset($_SESSION['message_type']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="bookings-container">
                    <?php if ($result->num_rows > 0): ?>
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer Name</th>
                                    <th>Tests Ordered</th>
                                    <th>Total Amount</th>
                                    <th>Collection Date</th>
                                    <th>Time Slot</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td class="test-list">
                                            <div class="test-preview">
                                                <div class="test-tooltip test-names-container" title="<?php echo htmlspecialchars($row['test_names'] ?: 'No tests'); ?>">
                                                    <?php 
                                                        if ($row['test_names']) {
                                                            $testNames = explode(', ', $row['test_names']);
                                                            if (count($testNames) > 2) {
                                                                echo htmlspecialchars($testNames[0] . ', ' . $testNames[1] . '...');
                                                            } else {
                                                                echo htmlspecialchars($row['test_names']);
                                                            }
                                                        } else {
                                                            echo 'No tests found';
                                                        }
                                                    ?>
                                                    <span class="tooltip-text"><?php echo htmlspecialchars($row['test_names'] ?: 'No tests available'); ?></span>
                                                </div>
                                                <div class="test-summary">
                                                    <span class="test-count"><?php echo intval($row['total_tests']); ?> test<?php echo intval($row['total_tests']) != 1 ? 's' : ''; ?></span>
                                                    <?php if ($row['calculated_total'] && $row['calculated_total'] != $row['total_amount']): ?>
                                                        <span class="test-total">Items: ₹<?php echo number_format($row['calculated_total'], 2); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="amount-highlight">₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['sample_collection_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['time_slot']); ?></td>
                                        <td>
                                            <span class="status status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button class="btn-view" onclick="viewBookingDetails('<?php echo $row['id']; ?>')">
                                                <i class='bx bx-show'></i> View
                                            </button>
                                            
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                                                    <input type="hidden" name="new_status" value="Confirmed">
                                                    <button type="submit" class="btn-accept"><i class='bx bx-check'></i>Confirm</button>
                                                </form>
                                                
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                                                    <input type="hidden" name="new_status" value="Cancelled">
                                                    <button type="submit" class="btn-reject"><i class='bx bx-x'></i>Cancel</button>
                                                </form>
                                            <?php elseif($row['status'] == 'Confirmed'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                                                    <input type="hidden" name="new_status" value="Sample Collected">
                                                    <button type="submit" class="btn-accept"><i class='bx bx-vial'></i>Collected</button>
                                                </form>
                                            <?php elseif($row['status'] == 'Sample Collected'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                                                    <input type="hidden" name="new_status" value="Sample Collected">
                                                    <button type="submit" class="btn-accept"><i class='bx bx-vial'></i>Sample Collected</button>
                                                </form>
                                            <?php elseif($row['status'] == 'Sample Collected'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                                                    <input type="hidden" name="new_status" value="Completed">
                                                    <button type="submit" class="btn-accept"><i class='bx bx-check-circle'></i>Complete</button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn-disabled" disabled>No Actions</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>No Orders Found</h3>
                            <p>There are currently no lab orders in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Booking Details Modal -->
            <div id="bookingModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div class="modal-header">
                        <h2>Order Details</h2>
                        <p id="modalBookingId" class="subtitle"></p>
                    </div>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Customer Name</div>
                            <div id="modalCustomerName" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Phone Number</div>
                            <div id="modalPhone" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div id="modalEmail" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Address</div>
                            <div id="modalAddress" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Sample Collection Date</div>
                            <div id="modalDate" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Time Slot</div>
                            <div id="modalTime" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div id="modalTotalAmount" class="detail-value amount-highlight"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Booking Created</div>
                            <div id="modalBookingTime" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div id="modalStatus" class="detail-value"></div>
                        </div>
                    </div>
                    <div class="modal-tests">
                        <h3>Ordered Tests</h3>
                        <div class="modal-tests-summary">
                            <div id="modalTestsSummary"></div>
                        </div>
                        <div id="modalTestsList"></div>
                        <div class="tests-total-container">
                            <span>Tests Total:</span>
                            <span id="modalTestsTotal" class="amount-highlight"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div id="modalActions"></div>
                    </div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script>
        // Modal functionality
        const modal = document.getElementById("bookingModal");
        const closeBtn = document.getElementsByClassName("close")[0];

        function viewBookingDetails(orderId) {
            // Show loading state
            document.getElementById("modalTestsList").innerHTML = '<div style="text-align: center; padding: 20px;">Loading tests...</div>';
            
            // Fetch order details via AJAX
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const order = data.order;
                        const tests = data.tests;
                        
                        // Populate modal with booking details
                        document.getElementById("modalBookingId").textContent = "ID: " + order.booking_id;
                        document.getElementById("modalCustomerName").textContent = order.customer_name || 'N/A';
                        document.getElementById("modalPhone").textContent = order.phone || 'N/A';
                        document.getElementById("modalEmail").textContent = order.email || 'N/A';
                        document.getElementById("modalAddress").textContent = order.address || 'N/A';
                        document.getElementById("modalDate").textContent = new Date(order.sample_collection_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                        document.getElementById("modalTime").textContent = order.time_slot || 'N/A';
                        document.getElementById("modalTotalAmount").textContent = "₹" + parseFloat(order.total_amount || 0).toFixed(2);
                        document.getElementById("modalBookingTime").textContent = order.created_at || 'N/A';

                        // Set status with appropriate styling
                        const statusElem = document.getElementById("modalStatus");
                        statusElem.textContent = order.status || 'Unknown';
                        statusElem.className = "detail-value status status-" + (order.status || 'unknown').toLowerCase().replace(' ', '-');

                        // Populate tests summary
                        const summaryElem = document.getElementById("modalTestsSummary");
                        summaryElem.innerHTML = `<strong>${tests.length} test${tests.length !== 1 ? 's' : ''} ordered</strong>`;

                        // Populate tests list
                        const testsContainer = document.getElementById("modalTestsList");
                        testsContainer.innerHTML = "";
                        
                        let testsTotal = 0;
                        
                        if (tests.length > 0) {
                            tests.forEach((test, index) => {
                                const testDiv = document.createElement("div");
                                testDiv.className = "test-item";
                                
                                const subtotal = parseFloat(test.subtotal || 0);
                                testsTotal += subtotal;
                                
                                testDiv.innerHTML = `
                                    <h4>${test.test_name || 'Unnamed Test'}</h4>
                                    <div class="test-item-details">
                                        <p><strong>Price:</strong> <span class="price-highlight">₹${parseFloat(test.test_price || 0).toFixed(2)}</span></p>
                                        <p><strong>Sample Type:</strong> ${test.sample_type || 'Not specified'}</p>
                                        <p><strong>Quantity:</strong> ${test.quantity || 1}</p>
                                        <p><strong>Subtotal:</strong> <span class="price-highlight">₹${subtotal.toFixed(2)}</span></p>
                                    </div>
                                `;
                                testsContainer.appendChild(testDiv);
                            });
                        } else {
                            testsContainer.innerHTML = '<div class="test-item"><p style="text-align: center; color: #999;">No tests found for this order</p></div>';
                        }
                        
                        // Update tests total
                        document.getElementById("modalTestsTotal").textContent = "₹" + testsTotal.toFixed(2);

                        // Show the modal
                        modal.style.display = "block";
                    } else {
                        alert("Error loading order details: " + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error loading order details. Please try again.");
                });
        }

        function closeModal() {
            modal.style.display = "none";
        }

        // Close modal when clicking on X
        closeBtn.onclick = closeModal;

        // Close modal when clicking outside of it
        window.onclick = function (event) {
            if (event.target == modal) {
                closeModal();
            }
        };

        // Auto dismiss alerts after 5 seconds
        document.addEventListener("DOMContentLoaded", function () {
            const alerts = document.querySelectorAll(".alert");
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    alert.style.opacity = "0";
                    alert.style.transition = "opacity 0.5s";
                    setTimeout(function () {
                        alert.style.display = "none";
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>