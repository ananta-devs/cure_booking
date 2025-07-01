<?php
    session_start();

    // Check if clinic is logged in
    if (!isset($_SESSION['clinic_id'])) {
        header("Location: login.php");
        exit();
    }

    // Database connection
    include_once './include/database_connection.php';

    // Get clinic info
    $clinic_id = (int)$_SESSION['clinic_id'];
    $clinic_name = $_SESSION['clinic_name'] ?? 'Unknown Clinic';

    include './include/top-header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Bookings - <?php echo htmlspecialchars($clinic_name); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-notification" id="toastNotification">
        <i class="fa fa-check-circle"></i>
        <span class="toast-message"></span>
    </div>

    <!-- Auto-refresh indicator -->
    <div class="refresh-indicator" id="refreshIndicator">
        <i class="fa fa-sync-alt"></i> Auto-refreshing...
    </div>

    <!-- Booking Details Modal -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fa fa-file-medical"></i> Booking Details</h2>
                <button class="modal-close" onclick="closeModal('bookingModal')">
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
    <div id="uploadReportModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa fa-upload"></i> Upload Lab Report</h3>
                <button class="modal-close" onclick="closeModal('uploadReportModal')">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div id="uploadBookingInfo" class="booking-info">
                    Booking ID: 
                </div>
                
                <form id="uploadReportForm" enctype="multipart/form-data">
                    <input type="hidden" id="uploadBookingId" name="booking_id">
                    
                    <div class="form-group">
                        <label for="reportFile">Select Report File:</label>
                        <input type="file" 
                            id="reportFile" 
                            name="report_file" 
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                            required>
                        <small class="file-help">
                            Allowed formats: PDF, DOC, DOCX, JPG, PNG (Max: 10MB)
                        </small>
                    </div>
                    
                    <div id="fileInfo" class="file-info">
                        <!-- File information will be displayed here -->
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary close-modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" disabled>
                            <i class="fa fa-upload"></i> Upload Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Status Update Modal -->
    <div class="modal-overlay" id="statusUpdateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa fa-edit"></i> Update Booking Status</h3>
                <button class="modal-close" onclick="closeModal('statusUpdateModal')">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="booking-info" id="statusBookingInfo">
                    <!-- Booking info will be populated here -->
                </div>

                <form id="statusUpdateForm">
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
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class="fa fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="status-btn status-btn-update">
                            <i class="fa fa-check"></i> Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container">
        <?php include './include/sidebar.php'; ?>
        
        <main class="main-content">
            <div id="lab-bookings-section" class="content-section active">
                <div class="header">
                    <h1><i class="fa fa-flask"></i> Lab Bookings</h1>
                    <p>Manage laboratory test bookings and results for <?php echo htmlspecialchars($clinic_name); ?></p>
                </div>

                <!-- Statistics Section -->
                <div class="stats-section" id="statsSection">
                    <div class="stat-card">
                        <div class="stat-number" id="totalBookings">0</div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="pendingBookings">0</div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="completedBookings">0</div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="todayBookings">0</div>
                        <div class="stat-label">Today's Collections</div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="statusFilter">Status</label>
                            <select id="statusFilter">
                                <option>All Statuses</option>
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Sample Collected">Sample Collected</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Upload Done">Upload Done</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                    </div>
                </div>

                <!-- Table Container -->
                <div id="scheduleContainer" class="schedule-Container">
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
                                <!-- Table rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Include the JavaScript file -->
    <script src="lab-bookings.js"></script>
</body>
</html>

<?php
    $conn->close();
?>