<?php
session_start();

// Check if clinic is logged in
if (!isset($_SESSION['clinic_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cure_booking";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

$clinic_id = (int)$_SESSION['clinic_id'];
$clinic_name = $_SESSION['clinic_name'] ?? 'Unknown Clinic';

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    $_SESSION['error'] = "Invalid booking ID.";
    header("Location: lab-bookings.php");
    exit();
}

// Get booking details
$booking_sql = "SELECT * FROM lab_orders WHERE id = ? AND clinic_id = ?";
$booking_stmt = $conn->prepare($booking_sql);
$booking_stmt->bind_param("ii", $booking_id, $clinic_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

if ($booking_result->num_rows === 0) {
    $_SESSION['error'] = "Booking not found or access denied.";
    header("Location: lab-bookings.php");
    exit();
}

$booking = $booking_result->fetch_assoc();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['report_file'])) {
    $upload_dir = 'uploads/reports/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['report_file'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // Validate file
    if ($file_error !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "File upload error.";
    } else {
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['error'] = "Invalid file type. Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG";
        } elseif ($file_size > 10 * 1024 * 1024) { // 10MB limit
            $_SESSION['error'] = "File size too large. Maximum 10MB allowed.";
        } else {
            // Generate unique filename
            $new_filename = $booking['booking_id'] . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Update database
                $update_sql = "UPDATE lab_orders SET 
                              report_file = ?, 
                              report_uploaded_at = CURRENT_TIMESTAMP, 
                              status = 'Upload Done',
                              updated_at = CURRENT_TIMESTAMP 
                              WHERE id = ? AND clinic_id = ?";
                
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sii", $new_filename, $booking_id, $clinic_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['success'] = "Report uploaded successfully and status updated to 'Upload Done'.";
                    header("Location: lab-bookings.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to update database.";
                    unlink($upload_path); // Delete uploaded file
                }
            } else {
                $_SESSION['error'] = "Failed to upload file.";
            }
        }
    }
}

include './top-header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Report - <?php echo htmlspecialchars($booking['booking_id']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .upload-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .upload-header h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .upload-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .upload-body {
            padding: 40px;
        }
        
        .booking-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .booking-info h3 {
            margin: 0 0 15px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
        }
        
        .upload-form {
            background: white;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .upload-form:hover {
            border-color: #007bff;
            background: #f8f9ff;
        }
        
        .upload-form.dragover {
            border-color: #007bff;
            background: #e6f3ff;
        }
        
        .upload-icon {
            font-size: 4rem;
            color: #007bff;
            margin-bottom: 20px;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin: 20px 0;
        }
        
        .file-input {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: 500;
        }
        
        .file-input-label:hover {
            background: #0056b3;
        }
        
        .file-info {
            margin-top: 20px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 6px;
            display: none;
        }
        
        .file-info.show {
            display: block;
        }
        
        .upload-restrictions {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }
        
        .upload-restrictions ul {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        
        .upload-restrictions li {
            padding: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .upload-restrictions li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        @media (max-width: 768px) {
            .upload-container {
                margin: 10px;
            }
            
            .upload-header,
            .upload-body {
                padding: 20px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include './sidebar.php'; ?>
        
        <main class="main-content">
            <div class="upload-container">
                <div class="upload-header">
                    <h1><i class="fa fa-upload"></i> Upload Lab Report</h1>
                    <p>Upload the lab test report for booking <?php echo htmlspecialchars($booking['booking_id']); ?></p>
                </div>
                
                <div class="upload-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error">
                            <i class="fa fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Booking Information -->
                    <div class="booking-info">
                        <h3><i class="fa fa-info-circle"></i> Booking Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Booking ID</span>
                                <span class="info-value"><?php echo htmlspecialchars($booking['booking_id']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Patient Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($booking['phone']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Collection Date</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($booking['sample_collection_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Current Status</span>
                                <span class="info-value">
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $booking['status'])); ?>">
                                        <?php echo htmlspecialchars($booking['status']); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Total Amount</span>
                                <span class="info-value">₹<?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Form -->
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="upload-form" id="uploadArea">
                            <div class="upload-icon">
                                <i class="fa fa-cloud-upload-alt"></i>
                            </div>
                            <h3>Upload Lab Report</h3>
                            <p>Drag and drop your file here or click to select</p>
                            
                            <div class="file-input-wrapper">
                                <input type="file" id="report_file" name="report_file" class="file-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                <label for="report_file" class="file-input-label">
                                    <i class="fa fa-folder-open"></i> Choose File
                                </label>
                            </div>
                            
                            <div class="file-info" id="fileInfo">
                                <strong>Selected file:</strong> <span id="fileName"></span><br>
                                <strong>Size:</strong> <span id="fileSize"></span>
                            </div>
                            
                            <div class="upload-restrictions">
                                <strong>File Requirements:</strong>
                                <ul>
                                    <li>Supported formats: PDF, DOC, DOCX, JPG, JPEG, PNG</li>
                                    <li>Maximum file size: 10MB</li>
                                    <li>Ensure the report is clear and readable</li>
                                    <li>Include patient name and test details</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> Upload Report
                            </button>
                            <a href="lab-bookings.php" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Bookings
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // File input handling
        const fileInput = document.getElementById('report_file');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadArea = document.getElementById('uploadArea');
        
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.classList.add('show');
                uploadArea.style.borderColor = '#28a745';
                uploadArea.style.backgroundColor = '#f8fff8';
            } else {
                fileInfo.classList.remove('show');
                uploadArea.style.borderColor = '#ddd';
                uploadArea.style.backgroundColor = 'white';
            }
        });
        
        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            if (!file) {
                e.preventDefault();
                alert('Please select a file to upload.');
                return;
            }
            
            // Check file size (10MB = 10 * 1024 * 1024 bytes)
            if (file.size > 10 * 1024 * 1024) {
                e.preventDefault();
                alert('File size is too large. Maximum allowed size is 10MB.');
                return;
            }
            
            // Check file type
            const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            if (!allowedTypes.includes(fileExtension)) {
                e.preventDefault();
                alert('Invalid file type. Please upload PDF, DOC, DOCX, JPG, JPEG, or PNG files only.');
                return;
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>