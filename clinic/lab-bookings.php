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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sty_lab.css">
    <link rel="stylesheet" href="lab.css">
    <style>
        
    </style>
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
                
                <!-- <form id="uploadReportForm" enctype="multipart/form-data">
                    <input type="hidden" id="uploadBookingId" name="booking_id" value="">
                    
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
                </form> -->
                
            </div>
        </div>
    </div>
    <div id="uploadReportModal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fa fa-upload"></i> Upload Lab Report</h3>
                            <button type="button" class="close-modal" onclick="closeModal('uploadReportModal')">
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
                                    <button type="button" class="btn btn-secondary" onclick="closeModal('uploadReportModal')">
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
                <button class="modal-close" onclick="closeStatusModal()">
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
                                <option value="">All Statuses</option>
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Sample Collected">Sample Collected</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Upload Done">Upload Done</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="dateFilter">Collection Date</label>
                            <input type="date" id="dateFilter">
                        </div>
                        
                        <div class="filter-group">
                            <label for="searchFilter">Search</label>
                            <input type="text" id="searchFilter" placeholder="Name, Phone, Booking ID...">
                        </div>
                        
                        <div class="filter-buttons">
                            <button type="button" class="filter-btn filter-btn-apply" onclick="applyFilters()">
                                <i class="fa fa-filter"></i> Apply
                            </button>
                            <button type="button" class="filter-btn filter-btn-clear" onclick="clearFilters()">
                                <i class="fa fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Actions -->
                <div class="table-actions">
                    <div class="bulk-actions">
                        <button type="button" class="export-btn" onclick="exportBookings()">
                            <i class="fa fa-download"></i> Export CSV
                        </button>
                    </div>
                    
                    <div class="table-controls">
                        <button type="button" class="filter-btn filter-btn-apply" onclick="loadBookings()" title="Refresh">
                            <i class="fa fa-sync-alt"></i> Refresh
                        </button>
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
    <!-- Upload Report Modal -->


<!-- Add these CSS styles -->
<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: white;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee;
    }

    .modal-header h3 {
        margin: 0;
        color: #333;
    }

    .close-modal {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #999;
    }

    .close-modal:hover {
        color: #333;
    }

    .modal-body {
        padding: 20px;
    }

    .booking-info {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        font-weight: bold;
        color: #495057;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #333;
    }

    .form-group input[type="file"] {
        width: 100%;
        padding: 8px;
        border: 2px dashed #ddd;
        border-radius: 4px;
        background-color: #fafafa;
    }

    .form-group input[type="file"]:focus {
        border-color: #007bff;
        outline: none;
    }

    .file-help {
        display: block;
        margin-top: 5px;
        color: #6c757d;
        font-size: 0.875em;
    }

    .file-info {
        margin-bottom: 20px;
    }

    .file-selected {
        background-color: #e8f5e8;
        border: 1px solid #c3e6c3;
        padding: 10px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .file-selected i {
        color: #28a745;
    }

    .file-selected small {
        color: #6c757d;
        margin-left: auto;
    }

    .file-details {
        font-size: 0.875em;
        color: #6c757d;
        margin-top: 4px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.2s;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
    }

    .btn-primary:hover:not(:disabled) {
        background-color: #0056b3;
    }

    .btn-primary:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #545b62;
    }

    /* Toast notifications */
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        padding: 12px 20px;
        border-radius: 4px;
        color: white;
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 300px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .toast-success {
        background-color: #28a745;
    }

    .toast-error {
        background-color: #dc3545;
    }

    .close-toast {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        margin-left: auto;
    }

    .close-toast:hover {
        opacity: 0.8;
    }
</style>

    <!-- Include the JavaScript file -->
    <script src="lab-bookings.js"></script>
<script>
    console.log('Debug: Page loaded, checking for required functions...');
    console.log('loadBookings function exists:', typeof loadBookings);
    console.log('Current URL:', window.location.href);

    // Test API directly
    fetch('lab-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_bookings'
    })
    .then(response => response.text())
    .then(text => {
        console.log('API Raw Response:', text);
        try {
            const data = JSON.parse(text);
            console.log('API Parsed Data:', data);
        } catch(e) {
            console.error('API Response is not valid JSON:', e);
        }
    })
    .catch(error => {
        console.error('API Request failed:', error);
    });
</script>
</body>
</html>

<?php
    $conn->close();
?>