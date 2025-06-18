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
    die('<div class="error-message">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// Get clinic info
$clinic_id = (int)$_SESSION['clinic_id'];
$clinic_name = $_SESSION['clinic_name'] ?? 'Unknown Clinic';

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
    <link rel="stylesheet" href="style_lab.css">
    <style>
        .upload-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .upload-btn:hover {
            background: #218838;
            color: white;
        }
        
        .upload-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .status-upload-done {
            background: #17a2b8;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 0.85rem;
            min-width: 35px;
        }
        
    </style>
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

    <div class="container">
        <?php include './include/sidebar.php'; ?>
        
        <main class="main-content">
            <div id="lab-bookings-section" class="content-section active">
                <div class="header">
                    <h1>Lab Bookings</h1>
                    <p>Manage laboratory test bookings and results for <?php echo htmlspecialchars($clinic_name); ?></p>
                </div>

                <div id="scheduleContainer" class="schedule-container">
                    <?php if ($bookings_result->num_rows > 0): ?>
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
                                            <button class="btn-sm btn-edit" onclick="updateStatus(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['status']); ?>')" title="Update Status">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <?php if ($booking['status'] === 'Sample Collected'): ?>
                                                <a href="upload_report.php?booking_id=<?php echo $booking['id']; ?>" class="upload-btn" title="Upload Report">
                                                    <i class="fa fa-upload"></i> Upload
                                                </a>
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
            </div>
        </main>
    </div>

    <script>
        // Auto-refresh functionality
        let refreshInterval;
        let countdownInterval;
        let nextRefreshTime;
        
        function startAutoRefresh() {
            const REFRESH_INTERVAL = 5 * 60 * 1000; // 5 minutes in milliseconds
            
            // Clear existing intervals
            if (refreshInterval) clearInterval(refreshInterval);
            if (countdownInterval) clearInterval(countdownInterval);
            
            // Set next refresh time
            nextRefreshTime = Date.now() + REFRESH_INTERVAL;
            
            // Start the main refresh interval
            refreshInterval = setInterval(() => {
                // Check if user is still active (clicked or typed in last 10 minutes)
                const lastActivity = localStorage.getItem('lastActivity');
                const now = Date.now();
                
                if (!lastActivity || (now - parseInt(lastActivity)) < 10 * 60 * 1000) {
                    showRefreshIndicator();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    // User inactive, reset timer
                    nextRefreshTime = now + REFRESH_INTERVAL;
                }
            }, REFRESH_INTERVAL);
        }
        
        function showRefreshIndicator() {
            const indicator = document.getElementById('refreshIndicator');
            indicator.classList.add('show');
            
            setTimeout(() => {
                indicator.classList.remove('show');
            }, 2000);
        }
        
        function trackUserActivity() {
            localStorage.setItem('lastActivity', Date.now().toString());
        }
        
        // Track user activity
        document.addEventListener('click', trackUserActivity);
        document.addEventListener('keypress', trackUserActivity);
        document.addEventListener('scroll', trackUserActivity);
        
        // Page visibility API to pause/resume refresh when tab is not visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Pause auto-refresh when tab is hidden
                if (refreshInterval) clearInterval(refreshInterval);
            } else {
                // Resume auto-refresh when tab becomes visible
                trackUserActivity();
                startAutoRefresh();
            }
        });
        
        // Initialize auto-refresh when page loads
        window.addEventListener('load', () => {
            trackUserActivity();
            startAutoRefresh();
        });

        // Modal functions
        function viewBooking(bookingId) {
            if (!bookingId) {
                alert('Please select a booking to view');
                return;
            }

            const modal = document.getElementById('bookingModal');
            const modalBody = document.getElementById('modalBody');
            const modalTitle = document.getElementById('modalTitle');

            // Show modal with loading spinner
            modalBody.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            `;
            modal.classList.add('show');
            modalTitle.textContent = 'Loading Booking Details...';

            // Fetch booking details via AJAX
            fetch(`?ajax=get_booking_details&booking_id=${bookingId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch booking details');
                    }
                    return response.json();
                })
                .then(data => {
                    displayBookingDetails(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #dc3545;">
                            <i class="fa fa-exclamation-triangle fa-3x" style="margin-bottom: 20px;"></i>
                            <h3>Error Loading Details</h3>
                            <p>Unable to load booking details. Please try again.</p>
                            <button onclick="viewBooking(${bookingId})" class="filter-btn" style="margin-top: 15px;">
                                <i class="fa fa-refresh"></i> Retry
                            </button>
                        </div>
                    `;
                    modalTitle.textContent = 'Error';
                });
        }

        function displayBookingDetails(booking) {
            const modalBody = document.getElementById('modalBody');
            const modalTitle = document.getElementById('modalTitle');

            modalTitle.textContent = `Booking Information`;

            // Parse test details
            let testsList = '';
            if (booking.test_details) {
                const tests = booking.test_details.split('|');
                testsList = tests.map(test => {
                    const [name, price] = test.split(' - ');
                    return `
                        <li class="test-item">
                            <span class="test-name">${name}</span>
                            <span class="test-price">${price}</span>
                        </li>
                    `;
                }).join('');
            }

            modalBody.innerHTML = `
                <div class="booking-details">
                    <!-- Patient Information -->
                    <div class="detail-section">
                        <h3><i class="fa fa-user"></i> Patient Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Patient Name</span>
                                <span class="detail-value">${booking.customer_name || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone Number</span>
                                <span class="detail-value">${booking.phone || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value">${booking.email || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Gender</span>
                                <span class="detail-value">${booking.gender || 'N/A'}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Information -->
                    <div class="detail-section">
                        <h3><i class="fa fa-calendar"></i> Booking Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Booking ID</span>
                                <span class="detail-value">${booking.booking_id}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Collection Date</span>
                                <span class="detail-value">${new Date(booking.sample_collection_date).toLocaleDateString('en-US', {
                                    weekday: 'long',
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                })}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Time Slot</span>
                                <span class="detail-value">${booking.time_slot || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value">
                                    <span class="status-badge status-${booking.status.toLowerCase().replace(' ', '-')}">
                                        ${booking.status}
                                    </span>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Booking Date</span>
                                <span class="detail-value">${new Date(booking.created_at).toLocaleDateString('en-US')} at ${new Date(booking.created_at).toLocaleTimeString('en-US')}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="detail-section">
                        <h3><i class="fa fa-map-marker-alt"></i> Address Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Collection Address</span>
                                <span class="detail-value">${booking.address || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Area</span>
                                <span class="detail-value">${booking.area || 'N/A'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Pincode</span>
                                <span class="detail-value">${booking.pincode || 'N/A'}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Test Information -->
                    <div class="detail-section">
                        <h3><i class="fa fa-flask"></i> Test Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Total Amount</span>
                                <span class="detail-value" style="font-size: 1.2rem; color: #007bff; font-weight: 600;">
                                    ₹${parseFloat(booking.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}
                                </span>
                            </div>
                        </div>
                        ${testsList ? `
                            <div style="margin-top: 20px;">
                                <span class="detail-label">Tests Ordered</span>
                                <ul class="test-list">
                                    ${testsList}
                                </ul>
                            </div>
                        ` : ''}
                    </div>

                    ${booking.special_instructions ? `
                    <!-- Special Instructions -->
                    <div class="detail-section">
                        <h3><i class="fa fa-sticky-note"></i> Special Instructions</h3>
                        <div class="detail-value" style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef;">
                            ${booking.special_instructions}
                        </div>
                    </div>
                    ` : ''}

                    <!-- Action Buttons -->
                    <div class="detail-section">
                        <h3><i class="fa fa-cogs"></i> Actions</h3>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button onclick="updateStatus(${booking.id}, '${booking.status}')" class="filter-btn">
                                <i class="fa fa-edit"></i> Update Status
                            </button>
                            ${booking.status === 'Completed' ? `
                                <button onclick="viewReport(${booking.id})" class="filter-btn" style="background: #17a2b8;">
                                    <i class="fa fa-file-medical"></i> View Report
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        function closeModal() {
            const modal = document.getElementById('bookingModal');
            modal.classList.remove('show');
        }


        // Close modal when clicking outside
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // Other existing functions
        function updateStatus(bookingId, currentStatus) {
            if (!bookingId) {
                alert('Invalid booking ID');
                return;
            }
            
            const statuses = ['Pending', 'Confirmed', 'Sample Collected', 'In Progress', 'Completed', 'Cancelled'];
            const newStatus = prompt(`Update status for booking ${bookingId}:\n\nCurrent Status: ${currentStatus}\n\nEnter new status (${statuses.join(', ')}):`, currentStatus);
            
            if (newStatus && newStatus !== currentStatus && statuses.includes(newStatus)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'update-booking-status.php';
                
                const bookingIdInput = document.createElement('input');
                bookingIdInput.type = 'hidden';
                bookingIdInput.name = 'booking_id';
                bookingIdInput.value = bookingId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = newStatus;
                
                form.appendChild(bookingIdInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            } else if (newStatus && !statuses.includes(newStatus)) {
                alert('Invalid status. Please choose from: ' + statuses.join(', '));
            }
        }
        
        function clearFilters() {
            window.location.href = window.location.pathname;
        }
        
        function goToPage(page) {
            const url = new URL(window.location);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
        
        function refreshBookings() {
            showRefreshIndicator();
            setTimeout(() => location.reload(), 500);
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>