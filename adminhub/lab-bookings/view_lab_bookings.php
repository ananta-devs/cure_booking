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

    // Handle file upload
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'uploadReport') {
        $bookingId = $_POST['booking_id'];
        $uploadDir = './uploads/lab_reports/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        if (isset($_FILES['lab_report']) && $_FILES['lab_report']['error'] == 0) {
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $allowedExtensions = ['pdf', 'doc', 'docx'];
            
            $fileType = $_FILES['lab_report']['type'];
            $fileName = $_FILES['lab_report']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($fileType, $allowedTypes) && in_array($fileExtension, $allowedExtensions)) {
                // Generate unique filename
                $newFileName = $bookingId . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['lab_report']['tmp_name'], $uploadPath)) {
                    // Update database with report path and status
                    $updateSql = "UPDATE lab_orders SET report_file = ?, status = 'Upload Done' WHERE booking_id = ?";
                    $stmt = $conn->prepare($updateSql);
                    $stmt->bind_param("ss", $newFileName, $bookingId);
                    
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Lab report uploaded successfully and status updated!";
                        $_SESSION['message_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Error updating database: " . $conn->error;
                        $_SESSION['message_type'] = "error";
                    }
                } else {
                    $_SESSION['message'] = "Error uploading file.";
                    $_SESSION['message_type'] = "error";
                }
            } else {
                $_SESSION['message'] = "Invalid file type. Only PDF, DOC, and DOCX files are allowed.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Please select a file to upload.";
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect to avoid form resubmission
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
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
                COUNT(DISTINCT loi.id) as total_tests
            FROM lab_orders lo 
            LEFT JOIN lab_order_items loi ON lo.id = loi.order_id 
            GROUP BY lo.id, lo.booking_id, lo.customer_name, lo.phone, lo.email, lo.address, 
                     lo.sample_collection_date, lo.time_slot, lo.total_amount, lo.status, lo.created_at, lo.report_file
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
        $testSql = "SELECT test_name, test_price, sample_type, quantity
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
    <title>CureBooking | Lab Orders</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="styles.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
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
                                <th>Booked Clinic</th>
                                <th>Status</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td class="test-list">
                                        <div class="single-test-display">
                                            <?php 
                                                if ($row['test_names']) {
                                                    $testNames = explode(', ', $row['test_names']);
                                                    $firstTest = $testNames[0];
                                                    $totalTests = intval($row['total_tests']);
                                                    
                                                    echo '<div class="primary-test-name" title="' . htmlspecialchars($row['test_names']) . '">';
                                                    echo htmlspecialchars($firstTest);
                                                    echo '</div>';
                                                    
                                                    if ($totalTests > 1) {
                                                        echo '<div class="test-count-badge">';
                                                        echo '<i class="bx bx-plus"></i>';
                                                        echo ($totalTests - 1) . ' more';
                                                        echo '</div>';
                                                    }
                                                } else {
                                                    echo '<div class="primary-test-name">No tests found</div>';
                                                }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="amount-highlight">₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['sample_collection_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['time_slot']); ?></td>
                                    <td><?php echo htmlspecialchars($row['clinic_name']); ?></td>
                                    <td>
                                        <span class="status status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions-column">
                                        <div class="actions">
                                            <button class="btn-view" onclick="viewBookingDetails('<?php echo $row['id']; ?>')">
                                                <i class='bx bx-show'></i>
                                            </button>
                                            
                                            <?php if(!empty($row['report_file']) && ($row['status'] == 'Upload Done' || $row['status'] == 'Completed')): ?>
                                                <button class="btn-view" onclick="viewReport('<?php echo htmlspecialchars($row['report_file']); ?>', '<?php echo htmlspecialchars($row['customer_name']); ?>')">
                                                    <i class='bx bx-file-blank'></i>Report
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                                                    <input type="hidden" name="new_status" value="Confirmed">
                                                    <button type="submit" class="btn-accept">
                                                        <i class='bx bx-check'></i>
                                                    </button>
                                                </form>
                                                
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                                                    <input type="hidden" name="new_status" value="Cancelled">
                                                    <button type="submit" class="btn-reject">
                                                        <i class='bx bx-x'></i>
                                                    </button>
                                                </form>
                                            <?php elseif($row['status'] == 'Confirmed'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                                                    <input type="hidden" name="new_status" value="Sample Collected">
                                                    <button type="submit" class="btn-accept">
                                                        <i class='bx bx-vial'></i>
                                                    </button>
                                                </form>
                                            <?php elseif($row['status'] == 'Sample Collected'): ?>
                                                <button class="btn-complete" onclick="openUploadModal('<?php echo htmlspecialchars($row['booking_id']); ?>', '<?php echo htmlspecialchars($row['customer_name']); ?>')">
                                                    <i class='bx bx-upload'></i>
                                                </button>
                                            <?php elseif($row['status'] == 'Upload Done'): ?>
                                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="status-form">
                                                    <input type="hidden" name="action" value="updateStatus">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                                                    <input type="hidden" name="new_status" value="Completed">
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-test-tube'></i>
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
                    <div class="modal-body">
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
                                <div class="detail-label">Booked Clinic</div>
                                <div id="modalBookedClinic" class="detail-value"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div id="modalStatus" class="detail-value"></div>
                            </div>
                        </div>
                        <div class="modal-tests">
                            <h3>Ordered Tests</h3>
                            <div id="modalTestsList"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lab Report Upload Modal -->
            <div id="uploadModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeUploadModal()">&times;</span>
                    <div class="modal-header">
                        <h2>Upload Lab Report</h2>
                        <p id="uploadModalSubtitle" class="subtitle"></p>
                    </div>
                    <div class="modal-body">
                        <form id="uploadForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="uploadReport">
                            <input type="hidden" id="uploadBookingId" name="booking_id" value="">
                            
                            <div class="details-grid">
                                <div class="detail-item">
                                    <div class="detail-label">Customer Name</div>
                                    <div id="uploadCustomerName" class="detail-value"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Booking ID</div>
                                    <div id="uploadBookingIdDisplay" class="detail-value"></div>
                                </div>
                            </div>

                            <div class="modal-tests">
                                <h3>Select Lab Report File</h3>
                                <div class="test-item">
                                    <div class="detail-item">
                                        <div class="detail-label">
                                            <i class='bx bx-file'></i> Lab Report (PDF, DOC, DOCX)
                                        </div>
                                        <div class="detail-value">
                                            <input type="file" 
                                                   id="lab_report" 
                                                   name="lab_report" 
                                                   accept=".pdf,.doc,.docx" 
                                                   required
                                                   style="width: 100%; padding: 10px; border: 2px dashed var(--primary-color); border-radius: 5px; background-color: #f8f9fc; cursor: pointer;">
                                        </div>
                                    </div>
                                    <div class="test-item-details">
                                        <p style="color: var(--secondary-color); font-size: 12px; margin-top: 10px;">
                                            <i class='bx bx-info-circle'></i> 
                                            Supported formats: PDF, DOC, DOCX. Maximum file size: 10MB
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-reject" onclick="closeUploadModal()">
                            <i class='bx bx-x'></i> Cancel
                        </button>
                        <button type="submit" form="uploadForm" class="btn-accept" id="uploadButton">
                            <i class='bx bx-upload'></i> Upload Report
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Viewer Modal -->
            <div id="reportModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeReportModal()">&times;</span>
                    <div class="modal-header">
                        <h2>Lab Report</h2>
                        <p id="reportModalSubtitle" class="subtitle"></p>
                    </div>
                    <div class="modal-body">
                        <div class="report-viewer" id="reportViewer">
                            <!-- Report will be loaded here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-view" id="downloadReportBtn">
                            <i class='bx bx-download'></i> Download Report
                        </button>
                        <button type="button" class="btn-reject" onclick="closeReportModal()">
                            <i class='bx bx-x'></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

<script>
    const modal = document.getElementById("bookingModal");
    const uploadModal = document.getElementById("uploadModal");
    const reportModal = document.getElementById("reportModal");
    const closeBtns = document.getElementsByClassName("close");
    let currentOrderId = null;

    // Report Viewer Functions
    function viewReport(reportFile, customerName) {
        const reportPath = './uploads/lab_reports/' + reportFile;
        const fileExtension = reportFile.split('.').pop().toLowerCase();
        
        document.getElementById("reportModalSubtitle").textContent = `Report for ${customerName}`;
        
        const reportViewer = document.getElementById("reportViewer");
        const downloadBtn = document.getElementById("downloadReportBtn");
        
        // Set up download button
        downloadBtn.onclick = function() {
            window.open(reportPath, '_blank');
        };
        
        if (fileExtension === 'pdf') {
            // Display PDF in iframe
            reportViewer.innerHTML = `
                <div style="text-align: center; margin-bottom: 20px;">
                    <iframe src="${reportPath}" 
                            style="width: 100%; height: 600px; border: none; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);" 
                            type="application/pdf">
                        <p>Your browser does not support PDFs. 
                           <a href="${reportPath}" target="_blank">Download the PDF</a> instead.
                        </p>
                    </iframe>
                </div>
            `;
        } else {
            // For DOC/DOCX files, show download option
            reportViewer.innerHTML = `
                <div style="text-align: center; padding: 60px 20px;">
                    <i class='bx bx-file-blank' style="font-size: 64px; color: var(--primary-color); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--dark-color); margin-bottom: 15px;">Lab Report</h3>
                    <p style="color: var(--secondary-color); margin-bottom: 25px;">
                        This is a ${fileExtension.toUpperCase()} document for ${customerName}
                    </p>
                    <p style="color: var(--secondary-color); font-size: 14px; margin-bottom: 30px;">
                        <i class='bx bx-info-circle'></i> 
                        ${fileExtension.toUpperCase()} files cannot be previewed in browser. Click download to view the report.
                    </p>
                    <button class="btn-view" onclick="window.open('${reportPath}', '_blank')" style="padding: 12px 24px; font-size: 16px;">
                        <i class='bx bx-download'></i> Download & View Report
                    </button>
                </div>
            `;
        }
        
        reportModal.style.display = "block";
        document.body.style.overflow = "hidden";
    }

    function closeReportModal() {
        reportModal.style.display = "none";
        document.body.style.overflow = "auto";
        
        // Clear the report viewer
        document.getElementById("reportViewer").innerHTML = "";
        document.getElementById("reportModalSubtitle").textContent = "";
    }

    // Upload Modal Functions
    function openUploadModal(bookingId, customerName) {
        document.getElementById("uploadBookingId").value = bookingId;
        document.getElementById("uploadBookingIdDisplay").textContent = bookingId;
        document.getElementById("uploadCustomerName").textContent = customerName;
        document.getElementById("uploadModalSubtitle").textContent = `Complete order for ${customerName}`;
        
        uploadModal.style.display = "block";
        document.body.style.overflow = "hidden";
    }

    function closeUploadModal() {
        uploadModal.style.display = "none";
        document.body.style.overflow = "auto";
        
        // Reset form
        document.getElementById("uploadForm").reset();
        document.getElementById("uploadBookingId").value = "";
        document.getElementById("uploadBookingIdDisplay").textContent = "";
        document.getElementById("uploadCustomerName").textContent = "";
        document.getElementById("uploadModalSubtitle").textContent = "";
    }

    // File input validation
    document.getElementById("lab_report").addEventListener("change", function() {
        const file = this.files[0];
        const uploadButton = document.getElementById("uploadButton");
        
        if (file) {
            const fileSize = file.size / 1024 / 1024; // Convert to MB
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            
            if (fileSize > 10) {
                alert("File size must be less than 10MB");
                this.value = "";
                uploadButton.disabled = true;
                return;
            }
            
            if (!allowedTypes.includes(file.type)) {
                alert("Only PDF, DOC, and DOCX files are allowed");
                this.value = "";
                uploadButton.disabled = true;
                return;
            }
            
            uploadButton.disabled = false;
            uploadButton.innerHTML = `<i class='bx bx-upload'></i> Upload ${file.name}`;
        } else {
            uploadButton.disabled = true;
            uploadButton.innerHTML = `<i class='bx bx-upload'></i> Upload Report`;
        }
    });

    // Existing booking modal functions
    function viewBookingDetails(orderId) {
        currentOrderId = orderId;
        
        // Show modal first with loading state
        modal.style.display = "block";
        document.body.style.overflow = "hidden"; // Prevent background scrolling
        
        // Reset modal content with loading state
        resetModalContent();
        showLoadingState();
        
        // Fetch order details via AJAX
        fetch('api.php?order_id=' + orderId)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                hideLoadingState();
                
                if (data.success) {
                    populateModalWithData(data.order, data.tests);
                } else {
                    showErrorState(data.message || 'Failed to load order details');
                }
            })
            .catch(error => {
                hideLoadingState();
                console.error('Error fetching order details:', error);
                showErrorState('Failed to load order details. Please try again.');
            });
    }

    function resetModalContent() {
        // Clear all modal fields
        const fields = [
            'modalBookingId', 'modalCustomerName', 'modalPhone', 'modalEmail',
            'modalAddress', 'modalDate', 'modalTime', 'modalTotalAmount',
            'modalBookingTime', 'modalStatus', 'modalTestsList'
        ];
        
        fields.forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) {
                element.innerHTML = '';
            }
        });
    }

    function showLoadingState() {
        document.getElementById("modalBookingId").innerHTML = 'Loading...';
        document.getElementById("modalTestsList").innerHTML = `
            <div class="loading-tests">
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <div style="width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <span>Loading order details...</span>
                </div>
            </div>
        `;
    }

    function hideLoadingState() {
        const loadingElement = document.querySelector('.loading-tests');
        if (loadingElement) {
            loadingElement.remove();
        }
    }

    function showErrorState(message) {
        document.getElementById("modalTestsList").innerHTML = `
            <div class="error-state" style="text-align: center; padding: 40px; color: #e74c3c;">
                <i class='bx bx-error-circle' style="font-size: 48px; margin-bottom: 16px;"></i>
                <p>${message}</p>
                <button onclick="viewBookingDetails(${currentOrderId})" class="btn-view" style="margin-top: 16px;">
                    <i class='bx bx-refresh'></i> Retry
                </button>
            </div>
        `;
    }

    function populateModalWithData(order, tests) {
        // Populate order details
        document.getElementById("modalBookingId").textContent = `Booking ID: ${order.booking_id}`;
        document.getElementById("modalCustomerName").textContent = order.customer_name;
        document.getElementById("modalPhone").textContent = order.phone;
        document.getElementById("modalEmail").textContent = order.email;
        document.getElementById("modalAddress").textContent = order.address;
        document.getElementById("modalDate").textContent = new Date(order.sample_collection_date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        document.getElementById("modalTime").textContent = order.time_slot;
        document.getElementById("modalTotalAmount").textContent = `₹${parseFloat(order.total_amount).toFixed(2)}`;
        document.getElementById("modalBookingTime").textContent = new Date(order.created_at).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        document.getElementById("modalBookedClinic").textContent = order.clinic_name;
        // Set status with appropriate styling
        const statusElement = document.getElementById("modalStatus");
        statusElement.innerHTML = `<span class="status status-${order.status.toLowerCase().replace(' ', '-')}">${order.status}</span>`;
        
        // Populate tests list
        let testsHtml = '';
        let totalCalculated = 0;
        
        if (tests && tests.length > 0) {
            tests.forEach(test => {
                // const subtotal = parseFloat(test.subtotal);
                // totalCalculated += subtotal;
                
                testsHtml += `
                    <div class="test-item">
                        <div class="test-main-info">
                            <div class="test-name">
                                <i class='bx bx-test-tube'></i>
                                ${test.test_name}
                            </div>
                            <div class="test-price">₹${parseFloat(test.test_price).toFixed(2)}</div>
                        </div>
                        <div class="test-item-details">
                            <div class="test-detail">
                                <span class="detail-label">Sample Type:</span>
                                <span class="detail-value">${test.sample_type}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            testsHtml = `
                <div class="no-tests" style="text-align: center; padding: 40px; color: var(--secondary-color);">
                    <i class='bx bx-test-tube' style="font-size: 48px; margin-bottom: 16px;"></i>
                    <p>No test details found for this order.</p>
                </div>
            `;
        }
        
        document.getElementById("modalTestsList").innerHTML = testsHtml;
    }

    // Close modal event handlers
    for (let i = 0; i < closeBtns.length; i++) {
        closeBtns[i].onclick = function() {
            modal.style.display = "none";
            uploadModal.style.display = "none";
            reportModal.style.display = "none";
            document.body.style.overflow = "auto";
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        }
        if (event.target == uploadModal) {
            closeUploadModal();
        }
        if (event.target == reportModal) {
            closeReportModal();
        }
    }

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    });

    // Add CSS animations for loading spinner
    document.head.appendChild(style);

    // Form validation for status updates
    document.querySelectorAll('.status-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const action = this.querySelector('input[name="new_status"]').value;
            let confirmMessage = '';
            
            switch(action) {
                case 'Confirmed':
                    confirmMessage = 'Are you sure you want to confirm this booking?';
                    break;
                case 'Cancelled':
                    confirmMessage = 'Are you sure you want to cancel this booking? This action cannot be undone.';
                    break;
                case 'Sample Collected':
                    confirmMessage = 'Confirm that the sample has been collected from the customer?';
                    break;
                case 'Completed':
                    confirmMessage = 'Mark this order as completed? The customer will be notified.';
                    break;
                default:
                    confirmMessage = 'Are you sure you want to update the status?';
            }
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });

    // Upload form validation
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        const fileInput = document.getElementById('lab_report');
        
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Please select a file to upload.');
            return;
        }
        
        const file = fileInput.files[0];
        const fileSize = file.size / 1024 / 1024; // Convert to MB
        
        if (fileSize > 10) {
            e.preventDefault();
            alert('File size must be less than 10MB.');
            return;
        }
        
        if (!confirm('Are you sure you want to upload this lab report? This will mark the order as completed.')) {
            e.preventDefault();
        }
    });
</script>

</body>
</html>