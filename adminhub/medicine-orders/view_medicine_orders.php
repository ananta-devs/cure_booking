<?php
    // Include database connection
    require_once '../include/database_connection.php';

    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['adm_id'])) {
        header("Location: http://localhost/adminhub/login.php");
        exit();
    }

    // Check connection
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Handle status update if form submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'updateStatus') {
        $orderId = $_POST['id'];
        $newStatus = $_POST['new_status'];
        
        $updateSql = "UPDATE medicine_orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $newStatus, $orderId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Order status updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating status: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }

    // Get all orders with medicine details ordered by date (newest first)
    $sql = "SELECT 
                mo.*,
                GROUP_CONCAT(DISTINCT moi.medicine_name SEPARATOR ', ') as medicine_names,
                GROUP_CONCAT(DISTINCT CONCAT(moi.medicine_name, ' (₹', moi.medicine_price, ' x ', moi.quantity, ')') SEPARATOR ', ') as medicine_details
            FROM medicine_orders mo 
            LEFT JOIN medicine_order_items moi ON mo.id = moi.order_id 
            GROUP BY mo.id 
            ORDER BY mo.order_date DESC";
    $result = $conn->query($sql);
    
    // Count orders by status
    $countSql = "SELECT status, COUNT(*) as count FROM medicine_orders GROUP BY status";
    $countResult = $conn->query($countSql);
    
    $statusCounts = [
        'pending' => 0,
        'confirmed' => 0,
        'shipped' => 0,
        'delivered' => 0,
        'cancelled' => 0,
        'Total' => 0
    ];
    
    if ($countResult->num_rows > 0) {
        while($row = $countResult->fetch_assoc()) {
            $statusCounts[$row['status']] = $row['count'];
            $statusCounts['Total'] += $row['count'];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Order Management</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>

</head>
<body>
    <!-- SIDEBAR -->
    <?php include '../include/sidebar.php'; ?>
    
    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include '../include/top-header.php'; ?>

        <!-- MAIN -->
        <main>
            <div class="container">
                <header>
                    <h1>Medicine Order Management</h1>
                    <p>Manage and update the status of medicine orders</p>
                    
                    <div class="status-summary">
                        <div class="status-card pending">
                            <h3>Pending</h3>
                            <p id="pending-count"><?php echo $statusCounts['pending']; ?></p>
                        </div>
                        <div class="status-card confirmed">
                            <h3>Confirmed</h3>
                            <p id="confirmed-count"><?php echo $statusCounts['confirmed']; ?></p>
                        </div>
                        <div class="status-card shipped">
                            <h3>Shipped</h3>
                            <p id="shipped-count"><?php echo $statusCounts['shipped']; ?></p>
                        </div>
                        <div class="status-card delivered">
                            <h3>Delivered</h3>
                            <p id="delivered-count"><?php echo $statusCounts['delivered']; ?></p>
                        </div>
                        <div class="status-card cancelled">
                            <h3>Cancelled</h3>
                            <p id="cancelled-count"><?php echo $statusCounts['cancelled']; ?></p>
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
                
                <div class="orders-container">
                    <?php if ($result->num_rows > 0): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Customer Name</th>
                                    <th>Medicine(s)</th>
                                    <th>Total Amount</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['medicine_names'] ?: 'No medicines'); ?></td>
                                        <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                                        <td>
                                            <span class="status status-<?php echo strtolower($row['status']); ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button class="btn-view" onclick="viewOrderDetails(
                                                '<?php echo $row['id']; ?>', 
                                                '<?php echo htmlspecialchars($row['order_number'], ENT_QUOTES); ?>', 
                                                '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>', 
                                                '<?php echo htmlspecialchars($row['phone'], ENT_QUOTES); ?>', 
                                                '<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>', 
                                                '<?php echo htmlspecialchars($row['address'], ENT_QUOTES); ?>', 
                                                '<?php echo htmlspecialchars($row['medicine_details'] ?: 'No medicines', ENT_QUOTES); ?>', 
                                                '<?php echo number_format($row['total_amount'], 2); ?>', 
                                                '<?php echo date('M d, Y H:i', strtotime($row['order_date'])); ?>', 
                                                '<?php echo $row['status']; ?>')">
                                                <i class='bx bx-show'></i> View
                                            </button>
                                            
                                            <?php if($row['status'] == 'pending'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="new_status" value="confirmed">
                                                    <button type="submit" class="btn-accept"><i class='bx bx-check'></i>Confirm</button>
                                                </form>
                                                
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="new_status" value="cancelled">
                                                    <button type="submit" class="btn-reject"><i class='bx bx-x'></i>Cancel</button>
                                                </form>
                                            <?php elseif($row['status'] == 'confirmed'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="new_status" value="shipped">
                                                    <button type="submit" class="btn-ship"><i class='bx bx-package'></i>Ship</button>
                                                </form>
                                            <?php elseif($row['status'] == 'shipped'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="new_status" value="delivered">
                                                    <button type="submit" class="btn-deliver"><i class='bx bx-check-circle'></i>Mark Delivered</button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn-disabled" disabled><i class='bx bx-check'></i>No Actions</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>No Orders Found</h3>
                            <p>There are currently no medicine orders in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Details Modal -->
            <div id="orderModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div class="modal-header">
                        <h2>Order Details</h2>
                        <p id="modalOrderNumber" class="subtitle"></p>
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
                            <div class="detail-label">Medicine Details</div>
                            <div id="modalMedicineDetails" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div id="modalTotalAmount" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Order Date</div>
                            <div id="modalDate" class="detail-value"></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div id="modalStatus" class="detail-value"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div id="modalActions"></div>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <script>
        const modal = document.getElementById("orderModal");
        const closeBtn = document.getElementsByClassName("close")[0];
        
        function viewOrderDetails(orderId, orderNumber, name, phone, email, address, medicineDetails, totalAmount, date, status) {
            document.getElementById("modalOrderNumber").textContent = "Order: " + orderNumber;
            document.getElementById("modalCustomerName").textContent = name;
            document.getElementById("modalPhone").textContent = phone;
            document.getElementById("modalEmail").textContent = email;
            document.getElementById("modalAddress").textContent = address;
            document.getElementById("modalMedicineDetails").textContent = medicineDetails;
            document.getElementById("modalTotalAmount").textContent = "₹" + totalAmount;
            document.getElementById("modalDate").textContent = date;
            
            const statusElem = document.getElementById("modalStatus");
            statusElem.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusElem.className = "detail-value status status-" + status.toLowerCase();
            
            const actionsContainer = document.getElementById("modalActions");
            actionsContainer.innerHTML = "";
            
            if (status === "pending") {
                // Confirm button
                const confirmForm = document.createElement("form");
                confirmForm.action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
                confirmForm.method = "post";
                confirmForm.className = "status-form";
                confirmForm.innerHTML = `
                    <input type="hidden" name="action" value="updateStatus">
                    <input type="hidden" name="id" value="${orderId}">
                    <input type="hidden" name="new_status" value="confirmed">
                    <button type="submit" class="btn-accept">Confirm Order</button>
                `;
                
                // Cancel button
                const cancelForm = document.createElement("form");
                cancelForm.action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
                cancelForm.method = "post";
                cancelForm.className = "status-form";
                cancelForm.innerHTML = `
                    <input type="hidden" name="action" value="updateStatus">
                    <input type="hidden" name="id" value="${orderId}">
                    <input type="hidden" name="new_status" value="cancelled">
                    <button type="submit" class="btn-reject">Cancel Order</button>
                `;
                
                actionsContainer.appendChild(confirmForm);
                actionsContainer.appendChild(cancelForm);
            } else if (status === "confirmed") {
                const shipForm = document.createElement("form");
                shipForm.action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
                shipForm.method = "post";
                shipForm.className = "status-form";
                shipForm.innerHTML = `
                    <input type="hidden" name="action" value="updateStatus">
                    <input type="hidden" name="id" value="${orderId}">
                    <input type="hidden" name="new_status" value="shipped">
                    <button type="submit" class="btn-ship">Mark as Shipped</button>
                `;
                actionsContainer.appendChild(shipForm);
            } else if (status === "shipped") {
                const deliverForm = document.createElement("form");
                deliverForm.action = "<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>";
                deliverForm.method = "post";
                deliverForm.className = "status-form";
                deliverForm.innerHTML = `
                    <input type="hidden" name="action" value="updateStatus">
                    <input type="hidden" name="id" value="${orderId}">
                    <input type="hidden" name="new_status" value="delivered">
                    <button type="submit" class="btn-deliver">Mark as Delivered</button>
                `;
                actionsContainer.appendChild(deliverForm);
            }
            
            modal.style.display = "block";
        }
        
        function closeModal() {
            modal.style.display = "none";
        }
        
        closeBtn.onclick = closeModal;
        
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>