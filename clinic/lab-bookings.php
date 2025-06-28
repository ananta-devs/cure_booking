<?php
    session_start();

    // Check if clinic is logged in
    if (!isset($_SESSION['clinic_id'])) {
        header("Location: login.php");
        exit();
    }

    // // Database connection
    include_once './include/database_connection.php';

    // Get clinic info
    $clinic_id = (int)$_SESSION['clinic_id'];
    $clinic_name = $_SESSION['clinic_name'] ?? 'Unknown Clinic';

    // Handle report upload AJAX request
    if (isset($_POST['ajax']) && $_POST['ajax'] === 'upload_report') {
        $booking_id = (int)$_POST['booking_id'];
        
        // Verify booking belongs to clinic and has correct status
        $verify_sql = "SELECT id, booking_id, customer_name FROM lab_orders WHERE id = ? AND clinic_id = ? AND status = 'Sample Collected'";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("ii", $booking_id, $clinic_id);
        $verify_stmt->execute();
        $booking = $verify_stmt->get_result()->fetch_assoc();
        
        if (!$booking) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid booking or status']);
            exit();
        }
        
        // Handle file upload
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/reports/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX']);
                exit();
            }
            
            // Generate unique filename
            $filename = 'report_' . $booking_id . '_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['report_file']['tmp_name'], $filepath)) {
                // Update database
                $update_sql = "UPDATE lab_orders SET report_file = ?, status = 'Upload Done', updated_at = NOW() WHERE id = ? AND clinic_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sii", $filename, $booking_id, $clinic_id);
                
                if ($update_stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Report uploaded successfully']);
                } else {
                    // Delete uploaded file if database update fails
                    unlink($filepath);
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file selected or upload error']);
        }
        exit();
    }

    // Handle AJAX request for booking details
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_booking_details' && isset($_GET['booking_id'])) {
        $booking_id = (int)$_GET['booking_id'];
        
        // Get booking details with test items
        $detail_sql = "SELECT lo.*, 
                            GROUP_CONCAT(
                                CONCAT(loi.test_name, ' - ₹', loi.test_price) 
                                SEPARATOR '|'
                            ) as test_details
                    FROM lab_orders lo 
                    LEFT JOIN lab_order_items loi ON lo.id = loi.order_id 
                    WHERE lo.id = ? AND lo.clinic_id = ?
                    GROUP BY lo.id";
        
        try {
            $detail_stmt = $conn->prepare($detail_sql);
            $detail_stmt->bind_param("ii", $booking_id, $clinic_id);
            $detail_stmt->execute();
            $booking_details = $detail_stmt->get_result()->fetch_assoc();
            
            if ($booking_details) {
                header('Content-Type: application/json');
                echo json_encode($booking_details);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Booking not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        exit();
    }

    // Pagination and filters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $date_filter = !empty($_GET['date_filter']) ? $_GET['date_filter'] : '';
    $status_filter = !empty($_GET['status_filter']) ? $_GET['status_filter'] : '';
    $search_query = !empty($_GET['search']) ? trim($_GET['search']) : '';

    // Build WHERE conditions
    $where_conditions = ["lo.clinic_id = ?"];
    $params = [$clinic_id];
    $types = "i";

    if ($date_filter) {
        $where_conditions[] = "DATE(lo.sample_collection_date) = ?";
        $params[] = $date_filter;
        $types .= "s";
    }

    if ($status_filter) {
        $where_conditions[] = "lo.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }

    if ($search_query) {
        $where_conditions[] = "(lo.customer_name LIKE ? OR lo.phone LIKE ? OR lo.booking_id LIKE ?)";
        $search_param = '%' . $search_query . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= "sss";
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Get total count for pagination
    $count_sql = "SELECT COUNT(DISTINCT lo.id) as total FROM lab_orders lo WHERE $where_clause";
    try {
        $count_stmt = $conn->prepare($count_sql);
        if (!empty($params)) {
            $count_stmt->bind_param($types, ...$params);
        }
        $count_stmt->execute();
        $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $limit);
    } catch (Exception $e) {
        $total_records = 0;
        $total_pages = 0;
    }

    // Main query with pagination
    $sql = "SELECT lo.*, 
                COUNT(loi.id) as test_count,
                GROUP_CONCAT(loi.test_name SEPARATOR ', ') as test_names
            FROM lab_orders lo 
            LEFT JOIN lab_order_items loi ON lo.id = loi.order_id 
            WHERE $where_clause
            GROUP BY lo.id 
            ORDER BY lo.sample_collection_date DESC, lo.created_at DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    try {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $bookings_result = $stmt->get_result();
    } catch (Exception $e) {
        die('<div class="error-message">Error fetching bookings: ' . htmlspecialchars($e->getMessage()) . '</div>');
    }


    // Get available statuses
    $status_sql = "SELECT DISTINCT status FROM lab_orders WHERE clinic_id = ? ORDER BY status";
    try {
        $status_stmt = $conn->prepare($status_sql);
        $status_stmt->bind_param("i", $clinic_id);
        $status_stmt->execute();
        $available_statuses = array_column($status_stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'status');
    } catch (Exception $e) {
        $available_statuses = ['Pending', 'Confirmed', 'Sample Collected', 'In Progress', 'Upload Done', 'Completed', 'Cancelled'];
    }

    include './include/top-header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Bookings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sty_lab.css">
</head>
<body>
    <div class="refresh-indicator" id="refreshIndicator">
        <i class="fa fa-sync-alt"></i> Auto-refreshing...
    </div>

    <!-- Booking Details Modal -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Booking Details</h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Upload Modal -->
    <div class="modal-overlay" id="uploadModal">
        <div class="modal-content upload-modal-content">
            <div class="modal-header">
                <h2><i class="fa fa-upload"></i> Upload Lab Report</h2>
                <button class="modal-close" onclick="closeUploadModal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="upload-booking-info" id="uploadBookingInfo">
                    <!-- Booking info will be populated here -->
                </div>
                
                <form id="uploadReportForm" enctype="multipart/form-data">
                    <input type="hidden" id="uploadBookingId" name="booking_id" value="">
                    <input type="hidden" name="ajax" value="upload_report">
                    
                    <div class="upload-form-group">
                        <label for="reportFile" class="upload-label">
                            <i class="fa fa-file"></i> Select Report File
                        </label>
                        <div class="file-upload-wrapper">
                            <input type="file" id="reportFile" name="report_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <div class="file-upload-display">
                                <div class="file-upload-placeholder">
                                    <i class="fa fa-cloud-upload-alt fa-2x"></i>
                                    <p>Click to select file or drag and drop</p>
                                    <small>Supported formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</small>
                                </div>
                                <div class="file-selected" style="display: none;">
                                    <i class="fa fa-file-alt"></i>
                                    <span class="file-name"></span>
                                    <button type="button" class="remove-file" onclick="removeSelectedFile()">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="upload-progress" id="uploadProgress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <div class="progress-text">Uploading... 0%</div>
                    </div>
                    
                    <div class="upload-buttons">
                        <button type="button" class="upload-btn upload-btn-cancel" onclick="closeUploadModal()">
                            <i class="fa fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="upload-btn upload-btn-submit">
                            <i class="fa fa-upload"></i> Upload Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="status-update-modal" id="statusUpdateModal">
        <div class="status-modal-content">
            <div class="status-modal-header">
                <h3><i class="fa fa-edit"></i> Update Booking Status</h3>
                <button class="status-close-btn" onclick="closeStatusModal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <div class="booking-info" id="statusBookingInfo">
                <!-- Booking info will be populated here -->
            </div>

            <form id="statusUpdateForm" method="POST" action="update-booking-status.php">
                <input type="hidden" id="statusBookingId" name="booking_id" value="">
                
                <div class="status-form-group">
                    <label for="statusSelect">Select New Status:</label>
                    <select id="statusSelect" name="status" class="status-select" required>
                        <option value="">-- Select Status --</option>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Sample Collected">Sample Collected</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Upload Done">Upload Done</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="status-buttons">
                    <button type="button" class="status-btn status-btn-cancel" onclick="closeStatusModal()">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="status-btn status-btn-update">
                        <i class="fa fa-check"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <?php include './include/sidebar.php'; ?>
        
        <main class="main-content">
            <div id="lab-bookings-section" class="content-section active">
                    <div class="header">
                        <h1>Lab Bookings</h1>
                        <p>Manage laboratory test bookings and results for <?php echo htmlspecialchars($clinic_name); ?></p>
                    </div>
            </div>

                <div id="scheduleContainer" class="schedule-Container">
                    <?php if ($bookings_result->num_rows > 0): ?>
                        
                        <!-- Responsive table wrapper -->
                        <div class="table-responsive">
                            <table class="bookings-table">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Patient Name</th>
                                        <th>Phone</th>
                                        <th>Collection Date</th>
                                        <th>Time Slot</th>
                                        <th>Tests</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($booking['booking_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['phone']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['sample_collection_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['time_slot']); ?></td>
                                        <td>
                                            <small title="<?php echo htmlspecialchars($booking['test_names']); ?>">
                                                <?php echo $booking['test_count']; ?> test(s)
                                            </small>
                                        </td>
                                        <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $booking['status'])); ?>">
                                                <?php echo htmlspecialchars($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-sm btn-view" onclick="viewBooking(<?php echo $booking['id']; ?>)" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <?php if ($booking['status'] !== 'Cancelled' && $booking['status'] !== 'Completed'): ?>
                                                    <button class="btn-sm btn-cancel" onclick="cancelBooking(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['booking_id']); ?>')" title="Cancel Booking">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] === 'Sample Collected'): ?>
                                                    <button class="btn-sm upload-btn" onclick="openUploadModal(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['booking_id']); ?>', '<?php echo htmlspecialchars($booking['customer_name']); ?>')" title="Upload Report">
                                                        <i class="fa fa-upload"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] === 'Upload Done' && !empty($booking['report_file'])): ?>
                                                    <a href="uploads/reports/<?php echo htmlspecialchars($booking['report_file']); ?>" 
                                                    class="btn-sm" style="background: #17a2b8; color: white;" 
                                                    target="_blank" title="View Report">
                                                        <i class="fa fa-file-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Alternative card layout for very small screens -->
                        <div class="bookings-cards">
                            <?php 
                            // Reset result pointer for card layout
                            $bookings_result->data_seek(0);
                            while ($booking = $bookings_result->fetch_assoc()): 
                            ?>
                            <div class="booking-card">
                                <div class="booking-card-header">
                                    <div class="booking-card-title">
                                        <?php echo htmlspecialchars($booking['booking_id']); ?>
                                    </div>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $booking['status'])); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="booking-card-body">
                                    <div class="booking-card-row">
                                        <span class="booking-card-label">Patient:</span>
                                        <span class="booking-card-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                                    </div>
                                    <div class="booking-card-row">
                                        <span class="booking-card-label">Phone:</span>
                                        <span class="booking-card-value"><?php echo htmlspecialchars($booking['phone']); ?></span>
                                    </div>
                                    <div class="booking-card-row">
                                        <span class="booking-card-label">Date:</span>
                                        <span class="booking-card-value"><?php echo date('M d, Y', strtotime($booking['sample_collection_date'])); ?></span>
                                    </div>
                                    <div class="booking-card-row">
                                        <span class="booking-card-label">Time:</span>
                                        <span class="booking-card-value"><?php echo htmlspecialchars($booking['time_slot']); ?></span>
                                    </div>
                                    <div class="booking-card-row">
                                        <span class="booking-card-label">Tests:</span>
                                        <span class="booking-card-value"><?php echo $booking['test_count']; ?> test(s)</span>
                                    </div>
                                    <div class="booking-card-row">
                                        <span class="booking-card-label">Amount:</span>
                                        <span class="booking-card-value">₹<?php echo number_format($booking['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <div class="booking-card-actions">
                                    <button class="btn-sm btn-view" onclick="viewBooking(<?php echo $booking['id']; ?>)" title="View Details">
                                        <i class="fa fa-eye"></i> View
                                    </button>
                                    <?php if ($booking['status'] !== 'Cancelled' && $booking['status'] !== 'Completed'): ?>
                                        <button class="btn-sm btn-cancel" onclick="cancelBooking(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['booking_id']); ?>')" title="Cancel Booking">
                                            <i class="fa fa-times"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'Sample Collected'): ?>
                                        <button class="btn-sm upload-btn" onclick="openUploadModal(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['booking_id']); ?>', '<?php echo htmlspecialchars($booking['customer_name']); ?>')" title="Upload Report">
                                            <i class="fa fa-upload"></i> Upload
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'Upload Done' && !empty($booking['report_file'])): ?>
                                        <a href="uploads/reports/<?php echo htmlspecialchars($booking['report_file']); ?>" 
                                        class="btn-sm" style="background: #17a2b8; color: white;" 
                                        target="_blank" title="View Report">
                                            <i class="fa fa-file-alt"></i> Report
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <button onclick="goToPage(1)" title="First Page">
                                    <i class="fa fa-angle-double-left"></i>
                                </button>
                                <button onclick="goToPage(<?php echo $page - 1; ?>)" title="Previous Page">
                                    <i class="fa fa-angle-left"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <button onclick="goToPage(<?php echo $i; ?>)" class="<?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <button onclick="goToPage(<?php echo $page + 1; ?>)" title="Next Page">
                                    <i class="fa fa-angle-right"></i>
                                </button>
                                <button onclick="goToPage(<?php echo $total_pages; ?>)" title="Last Page">
                                    <i class="fa fa-angle-double-right"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="no-bookings">
                            <i class="fa fa-calendar-times fa-3x" style="color: #ddd; margin-bottom: 20px;"></i>
                            <h3>No Lab Bookings Found</h3>
                            <p>
                                <?php if ($search_query || $date_filter || $status_filter): ?>
                                    No bookings match your search criteria. <br>
                                    <button onclick="clearFilters()" class="filter-btn" style="margin-top: 10px;">
                                        Clear Filters
                                    </button>
                                <?php else: ?>
                                    There are no lab test bookings for <?php echo htmlspecialchars($clinic_name); ?> yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
        </main>
    </div>

    <script src="lab-bookings.js"></script>


    <!-- Add the closing tags -->
</body>
</html>

<?php
    $conn->close();
?>