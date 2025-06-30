document.addEventListener("DOMContentLoaded", function () {
    setTimeout(debugUploadForm, 1000);
});

// Lab Bookings JavaScript Integration
document.addEventListener("DOMContentLoaded", function () {
    console.log("Lab bookings page loaded");

    // Initialize the page
    loadBookings();

    // Auto-refresh every 30 seconds
    setInterval(loadBookings, 30000);

    // Initialize file upload handlers
    initializeFileUpload();

    // Initialize form handlers
    initializeFormHandlers();

    // Initialize filter handlers
    initializeFilterHandlers();
});

// Global variables
let allBookings = [];
let filteredBookings = [];

// Load all bookings from API
async function loadBookings() {
    try {
        console.log("Loading bookings...");
        showRefreshIndicator();

        const response = await fetch("lab-api.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "action=get_bookings",
        });

        console.log("Response status:", response.status);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log("Raw response:", text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error("JSON parse error:", parseError);
            throw new Error("Invalid JSON response from server");
        }

        console.log("Parsed data:", data);

        if (data.success) {
            allBookings = data.data || [];
            filteredBookings = allBookings;
            displayBookings(allBookings);
            updateStatistics(allBookings);
            console.log("Bookings loaded successfully:", allBookings.length);
        } else {
            showError(
                "Failed to load bookings: " + (data.message || "Unknown error")
            );
        }
    } catch (error) {
        console.error("Error loading bookings:", error);
        showError("Network error while loading bookings: " + error.message);

        // Show empty state
        displayBookings([]);
    } finally {
        hideRefreshIndicator();
    }
}

// Display bookings in table
function displayBookings(bookings) {
    console.log("Displaying bookings:", bookings.length);

    const table = document.querySelector(".bookings-table");
    if (!table) {
        console.error("Bookings table not found");
        return;
    }

    // Remove existing tbody if exists
    const existingTbody = table.querySelector("tbody");
    if (existingTbody) {
        existingTbody.remove();
    }

    const newTbody = document.createElement("tbody");

    if (!bookings || bookings.length === 0) {
        newTbody.innerHTML = `
            <tr>
                <td colspan="9" class="no-data">
                    <div class="empty-state">
                        <i class="fa fa-flask"></i>
                        <h3>No lab bookings found</h3>
                        <p>No lab bookings available for this clinic</p>
                    </div>
                </td>
            </tr>
        `;
    } else {
        bookings.forEach((booking, index) => {
            console.log(`Creating row ${index}:`, booking);
            const row = createBookingRow(booking);
            newTbody.appendChild(row);
        });
    }

    table.appendChild(newTbody);
}

// Create booking table row
function createBookingRow(booking) {
    const row = document.createElement("tr");

    // Ensure safe values
    const bookingId = booking.booking_id || "N/A";
    const customerName = booking.customer_name || "N/A";
    const email = booking.email || "N/A";
    const phone = booking.phone || "N/A";
    const collectionDate = booking.formatted_collection_date || "N/A";
    const timeSlot = booking.time_slot || "N/A";
    const testCount = booking.test_count || 0;
    const tests = booking.tests || "N/A";
    const amount = booking.total_amount || 0;
    const status = booking.status || "Pending";
    const statusClass = booking.status_class || "status-pending";
    const hasReport = booking.has_report || false;
    const createdDate = booking.formatted_created_date || "N/A";

    // Check if upload button should be disabled
    const isUploadDisabled = status === "Upload Done";
    const uploadBtnClass = isUploadDisabled
        ? "action-btn upload-btn disabled"
        : "action-btn upload-btn";
    const uploadBtnClick = isUploadDisabled
        ? ""
        : `onclick="showUploadModal('${bookingId}')"`;
    const uploadBtnTitle = isUploadDisabled
        ? "Report already uploaded"
        : "Upload Report";

    row.innerHTML = `
        <td>
            <div class="booking-id">${bookingId}</div>
            <div class="booking-date">${createdDate}</div>
        </td>
        <td>
            <div class="patient-name">${customerName}</div>
            <div class="patient-email">${email}</div>
        </td>
        <td>
            <div class="phone">${phone}</div>
        </td>
        <td>
            <div class="collection-date">${collectionDate}</div>
        </td>
        <td>
            <div class="time-slot">${timeSlot}</div>
        </td>
        <td>
            <div class="tests-info">
                <div class="test-count">${testCount} test(s)</div>
                <div class="test-names">${tests}</div>
            </div>
        </td>
        <td>
            <div class="amount">₹${amount}</div>
        </td>
        <td>
            <span class="status-badge ${statusClass}">${status}</span>
            ${
                hasReport
                    ? '<div class="report-indicator"><i class="fa fa-file-alt"></i> Report Available</div>'
                    : ""
            }
        </td>
        <td>
            <div class="action-buttons">
                <button class="action-btn-lab view-btn" onclick="viewBookingDetails('${bookingId}')" title="View Details">
                    <i class="fa fa-eye"></i>
                </button>
                <button class="action-btn-lab status-btn" onclick="showStatusModal('${bookingId}', '${status}')" title="Update Status">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="${uploadBtnClass}" ${uploadBtnClick} title="${uploadBtnTitle}" ${
        isUploadDisabled ? "disabled" : ""
    }>
                    <i class="fa fa-upload"></i>
                </button>
                <button class="action-btn delete-btn" onclick="deleteBooking('${bookingId}')" title="Delete Booking">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </td>
    `;
    return row;
}

// Update statistics
function updateStatistics(bookings) {
    const today = new Date().toISOString().split("T")[0];

    const stats = {
        total: bookings.length,
        pending: bookings.filter((b) => b.status === "Pending").length,
        completed: bookings.filter((b) => b.status === "Completed").length,
        today: bookings.filter((b) => b.sample_collection_date === today)
            .length,
    };

    // Safely update stat elements
    const totalEl = document.getElementById("totalBookings");
    const pendingEl = document.getElementById("pendingBookings");
    const completedEl = document.getElementById("completedBookings");
    const todayEl = document.getElementById("todayBookings");

    if (totalEl) totalEl.textContent = stats.total;
    if (pendingEl) pendingEl.textContent = stats.pending;
    if (completedEl) completedEl.textContent = stats.completed;
    if (todayEl) todayEl.textContent = stats.today;
}

// View booking details
async function viewBookingDetails(bookingId) {
    try {
        console.log("Loading details for booking:", bookingId);

        const response = await fetch(
            `lab-api.php?action=get_booking_details&booking_id=${bookingId}`
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showBookingModal(data.data);
        } else {
            showError(
                "Failed to load booking details: " +
                    (data.message || "Unknown error")
            );
        }
    } catch (error) {
        console.error("Error loading booking details:", error);
        showError(
            "Network error while loading booking details: " + error.message
        );
    }
}

// Show booking details modal
function showBookingModal(booking) {
    const modal = document.getElementById("bookingModal");
    const modalBody = document.getElementById("modalBody");

    if (!modal || !modalBody) {
        console.error("Modal elements not found");
        return;
    }

    modalBody.innerHTML = `
        <div class="booking-detail-content">
            <div class="detail-section">
                <h3><i class="fa fa-user"></i> Customer Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Name:</label>
                        <span>${booking.customer_name || "N/A"}</span>
                    </div>
                    <div class="detail-item">
                        <label>Phone:</label>
                        <span>${booking.phone || "N/A"}</span>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span>${booking.email || "N/A"}</span>
                    </div>
                    <div class="detail-item">
                        <label>Address:</label>
                        <span>${booking.address || "N/A"}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fa fa-calendar"></i> Booking Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Booking ID:</label>
                        <span>${booking.booking_id || "N/A"}</span>
                    </div>
                    <div class="detail-item">
                        <label>Collection Date:</label>
                        <span>${
                            booking.formatted_collection_date || "N/A"
                        }</span>
                    </div>
                    <div class="detail-item">
                        <label>Time Slot:</label>
                        <span>${booking.time_slot || "N/A"}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="status-badge ${getStatusClass(
                            booking.status || "Pending"
                        )}">${booking.status || "Pending"}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fa fa-flask"></i> Test Details</h3>
                <div class="tests-list">
                    ${
                        booking.tests && booking.tests.length > 0
                            ? booking.tests
                                  .map(
                                      (test) =>
                                          `<div class="test-item">${test}</div>`
                                  )
                                  .join("")
                            : '<div class="no-tests">No tests found</div>'
                    }
                </div>
                <div class="total-amount">
                    <strong>Total Amount: ₹${booking.total_amount || 0}</strong>
                </div>
            </div>
            
            ${
                booking.has_report
                    ? `
                <div class="detail-section">
                    <h3><i class="fa fa-file-alt"></i> Report Information</h3>
                    <div class="report-info">
                        <div class="report-item">
                            <label>Report File:</label>
                            <span>${booking.report_file || "N/A"}</span>
                        </div>
                        <div class="report-item">
                            <label>Uploaded At:</label>
                            <span>${
                                booking.formatted_report_date || "N/A"
                            }</span>
                        </div>
                        <div class="report-actions">
                            <a href="uploads/lab_reports/${
                                booking.report_file
                            }" target="_blank" class="btn btn-primary">
                                <i class="fa fa-download"></i> Download Report
                            </a>
                        </div>
                    </div>
                </div>
            `
                    : ""
            }
            
            <div class="detail-section">
                <h3><i class="fa fa-info-circle"></i> Booking Timeline</h3>
                <div class="timeline-info">
                    <div class="timeline-item">
                        <label>Created:</label>
                        <span>${booking.formatted_created_date || "N/A"}</span>
                    </div>
                    <div class="timeline-item">
                        <label>Last Updated:</label>
                        <span>${booking.formatted_updated_date || "N/A"}</span>
                    </div>
                </div>
            </div>
        </div>
    `;

    modal.style.display = "flex";
}

function showStatusModal(bookingId, currentStatus) {
    const modal = document.getElementById("statusUpdateModal");
    const bookingInfo = document.getElementById("statusBookingInfo");
    const statusSelect = document.getElementById("statusSelect");
    const hiddenBookingId = document.getElementById("statusBookingId");

    if (!modal || !bookingInfo || !statusSelect || !hiddenBookingId) {
        console.error("Status modal elements not found");
        return;
    }

    // Set booking information
    bookingInfo.textContent = `Booking ID: ${bookingId}`;
    hiddenBookingId.value = bookingId;

    // Set current status
    statusSelect.value = currentStatus;

    // Show modal
    modal.style.display = "flex";
}

// Show upload report modal
// function showUploadModal(bookingId) {
//     const modal = document.getElementById("uploadReportModal");
//     const bookingInfo = document.getElementById("uploadBookingInfo");
//     const hiddenBookingId = document.getElementById("uploadBookingId");
//     const fileInput = document.getElementById("reportFile");

//     if (!modal || !bookingInfo || !hiddenBookingId) {
//         console.error('Upload modal elements not found');
//         return;
//     }

//     // Set booking information
//     bookingInfo.textContent = `Booking ID: ${bookingId}`;
//     hiddenBookingId.value = bookingId;

//     // Reset file input
//     if (fileInput) {
//         fileInput.value = '';
//     }

//     // Show modal
//     modal.style.display = "flex";
// }
function showUploadModal(bookingId) {
    console.log("Showing upload modal for booking:", bookingId);

    const modal = document.getElementById("uploadReportModal");
    const bookingInfo = document.getElementById("uploadBookingInfo");
    const hiddenBookingId = document.getElementById("uploadBookingId");
    const fileInput = document.getElementById("reportFile");
    const fileInfo = document.getElementById("fileInfo");

    if (!modal) {
        console.error("Upload modal not found");
        return;
    }

    if (!hiddenBookingId) {
        console.error("Hidden booking ID input not found");
        return;
    }

    // Set booking information
    if (bookingInfo) {
        bookingInfo.textContent = `Booking ID: ${bookingId}`;
    }

    hiddenBookingId.value = bookingId;

    // Reset file input and info
    if (fileInput) {
        fileInput.value = "";
    }

    if (fileInfo) {
        fileInfo.innerHTML = "";
    }

    // Disable upload button initially
    const uploadBtn = document.querySelector(
        '#uploadReportForm button[type="submit"]'
    );
    if (uploadBtn) {
        uploadBtn.disabled = true;
    }

    // Show modal
    modal.style.display = "flex";

    console.log("Upload modal shown");
}

// Update booking status
async function updateBookingStatus() {
    const bookingId = document.getElementById("statusBookingId").value;
    const status = document.getElementById("statusSelect").value;

    if (!bookingId || !status) {
        showError("Please select a status");
        return;
    }

    try {
        showLoader("Updating status...");

        const formData = new FormData();
        formData.append("action", "update_status");
        formData.append("booking_id", bookingId);
        formData.append("status", status);

        const response = await fetch("lab-api.php", {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showSuccess("Status updated successfully");
            closeModal("statusUpdateModal");
            loadBookings(); // Refresh the bookings list
        } else {
            showError(
                "Failed to update status: " + (data.message || "Unknown error")
            );
        }
    } catch (error) {
        console.error("Error updating status:", error);
        showError("Network error while updating status: " + error.message);
    } finally {
        hideLoader();
    }
}

// Upload report
// async function uploadReport() {
//     const bookingId = document.getElementById("uploadBookingId").value;
//     const fileInput = document.getElementById("reportFile");

//     if (!bookingId) {
//         showError("Booking ID is missing");
//         return;
//     }

//     if (!fileInput || !fileInput.files[0]) {
//         showError("Please select a file to upload");
//         return;
//     }

//     const file = fileInput.files[0];

//     // Validate file size (10MB max)
//     if (file.size > 10 * 1024 * 1024) {
//         showError("File size too large. Maximum 10MB allowed");
//         return;
//     }

//     // Validate file type
//     const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png',
//                          'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

//     if (!allowedTypes.includes(file.type)) {
//         showError("Invalid file type. Only PDF, JPG, PNG, DOC, DOCX allowed");
//         return;
//     }

//     try {
//         showLoader("Uploading report...");

//         const formData = new FormData();
//         formData.append('action', 'upload_report');
//         formData.append('booking_id', bookingId);
//         formData.append('report_file', file);

//         const response = await fetch("lab-api.php", {
//             method: "POST",
//             body: formData
//         });

//         if (!response.ok) {
//             throw new Error(`HTTP error! status: ${response.status}`);
//         }

//         const data = await response.json();

//         if (data.success) {
//             showSuccess("Report uploaded successfully");
//             closeModal("uploadReportModal");
//             loadBookings(); // Refresh the bookings list
//         } else {
//             showError("Failed to upload report: " + (data.message || 'Unknown error'));
//         }
//     } catch (error) {
//         console.error("Error uploading report:", error);
//         showError("Network error while uploading report: " + error.message);
//     } finally {
//         hideLoader();
//     }
// }
async function uploadReport() {
    const bookingId = document.getElementById("uploadBookingId")?.value;
    const fileInput = document.getElementById("reportFile");

    console.log("Upload initiated for booking:", bookingId);
    console.log("File input element:", fileInput);

    if (!bookingId) {
        showError("Booking ID is missing");
        return;
    }

    if (!fileInput) {
        showError("File input element not found");
        return;
    }

    if (!fileInput.files || !fileInput.files[0]) {
        showError("Please select a file to upload");
        return;
    }

    const file = fileInput.files[0];
    console.log("Selected file:", {
        name: file.name,
        size: file.size,
        type: file.type,
    });

    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
        showError("File size too large. Maximum 10MB allowed");
        return;
    }

    // Validate file type - check both extension and MIME type
    const fileName = file.name.toLowerCase();
    const allowedExtensions = [
        ".pdf",
        ".jpg",
        ".jpeg",
        ".png",
        ".doc",
        ".docx",
    ];
    const allowedMimes = [
        "application/pdf",
        "image/jpeg",
        "image/jpg",
        "image/png",
        "application/msword",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    ];

    const hasValidExtension = allowedExtensions.some((ext) =>
        fileName.endsWith(ext)
    );
    const hasValidMime = allowedMimes.includes(file.type);

    if (!hasValidExtension && !hasValidMime) {
        showError(`Invalid file type. Only PDF, JPG, PNG, DOC, DOCX allowed. 
                   File type detected: ${file.type}`);
        return;
    }

    try {
        showLoader("Uploading report...");

        // Create FormData with proper field names
        const formData = new FormData();
        formData.append("action", "upload_report");
        formData.append("booking_id", bookingId);
        formData.append("report_file", file, file.name); // Include filename

        console.log("FormData created:", {
            action: formData.get("action"),
            booking_id: formData.get("booking_id"),
            file: formData.get("report_file"),
        });

        const response = await fetch("lab-api.php", {
            method: "POST",
            body: formData,
            // Don't set Content-Type header - let browser set it with boundary
        });

        console.log("Response status:", response.status);
        console.log("Response headers:", response.headers);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Get response as text first to check for any PHP errors
        const responseText = await response.text();
        console.log("Raw response:", responseText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error("JSON parse error:", parseError);
            console.error("Response text:", responseText);
            throw new Error(
                "Invalid JSON response from server. Check server logs."
            );
        }

        console.log("Parsed response:", data);

        if (data.success) {
            showSuccess("Report uploaded successfully");
            closeModal("uploadReportModal");
            loadBookings(); // Refresh the bookings list
        } else {
            showError(
                "Failed to upload report: " + (data.message || "Unknown error")
            );
        }
    } catch (error) {
        console.error("Error uploading report:", error);
        showError("Network error while uploading report: " + error.message);
    } finally {
        hideLoader();
    }
}

// Delete booking
async function deleteBooking(bookingId) {
    if (
        !confirm(
            `Are you sure you want to delete booking ${bookingId}? This action cannot be undone.`
        )
    ) {
        return;
    }

    try {
        showLoader("Deleting booking...");

        const formData = new FormData();
        formData.append("action", "delete_booking");
        formData.append("booking_id", bookingId);

        const response = await fetch("lab-api.php", {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showSuccess("Booking deleted successfully");
            loadBookings(); // Refresh the bookings list
        } else {
            showError(
                "Failed to delete booking: " + (data.message || "Unknown error")
            );
        }
    } catch (error) {
        console.error("Error deleting booking:", error);
        showError("Network error while deleting booking: " + error.message);
    } finally {
        hideLoader();
    }
}

// Initialize form handlers
function initializeFormHandlers() {
    // Status update form
    const statusForm = document.getElementById("statusUpdateForm");
    if (statusForm) {
        statusForm.addEventListener("submit", function (e) {
            e.preventDefault();
            updateBookingStatus();
        });
    }

    // Upload report form
    const uploadForm = document.getElementById("uploadReportForm");
    if (uploadForm) {
        uploadForm.addEventListener("submit", function (e) {
            e.preventDefault();
            uploadReport();
        });
    }
}

// Initialize file upload handlers
// function initializeFileUpload() {
//     const fileInput = document.getElementById("reportFile");
//     if (fileInput) {
//         fileInput.addEventListener("change", function(e) {
//             const file = e.target.files[0];
//             if (file) {
//                 // Show file info
//                 const fileInfo = document.getElementById("fileInfo");
//                 if (fileInfo) {
//                     fileInfo.innerHTML = `
//                         <div class="file-selected">
//                             <i class="fa fa-file"></i>
//                             <span>${file.name}</span>
//                             <small>(${formatFileSize(file.size)})</small>
//                         </div>
//                     `;
//                 }
//             }
//         });
//     }
// }
function initializeFileUpload() {
    const fileInput = document.getElementById("reportFile");
    if (fileInput) {
        fileInput.addEventListener("change", function (e) {
            const file = e.target.files[0];
            console.log("File selected:", file);

            if (file) {
                // Validate file immediately
                const fileName = file.name.toLowerCase();
                const allowedExtensions = [
                    ".pdf",
                    ".jpg",
                    ".jpeg",
                    ".png",
                    ".doc",
                    ".docx",
                ];
                const hasValidExtension = allowedExtensions.some((ext) =>
                    fileName.endsWith(ext)
                );

                if (!hasValidExtension) {
                    showError(
                        "Invalid file type. Only PDF, JPG, PNG, DOC, DOCX allowed."
                    );
                    e.target.value = ""; // Clear the input
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    showError("File size too large. Maximum 10MB allowed.");
                    e.target.value = ""; // Clear the input
                    return;
                }

                // Show file info
                const fileInfo = document.getElementById("fileInfo");
                if (fileInfo) {
                    fileInfo.innerHTML = `
                        <div class="file-selected">
                            <i class="fa fa-file"></i>
                            <span>${file.name}</span>
                            <small>(${formatFileSize(file.size)})</small>
                            <div class="file-details">
                                Type: ${file.type || "Unknown"}
                            </div>
                        </div>
                    `;
                }

                // Enable upload button
                const uploadBtn = document.querySelector(
                    '#uploadReportForm button[type="submit"]'
                );
                if (uploadBtn) {
                    uploadBtn.disabled = false;
                }
            } else {
                // Disable upload button if no file
                const uploadBtn = document.querySelector(
                    '#uploadReportForm button[type="submit"]'
                );
                if (uploadBtn) {
                    uploadBtn.disabled = true;
                }
            }
        });
    }

    // Debug: Log if file input is found
    console.log("File input element found:", !!fileInput);
}
function debugUploadForm() {
    console.log("=== Upload Form Debug ===");
    console.log("Modal:", document.getElementById("uploadReportModal"));
    console.log(
        "Booking ID input:",
        document.getElementById("uploadBookingId")
    );
    console.log("File input:", document.getElementById("reportFile"));
    console.log("Form:", document.getElementById("uploadReportForm"));
    console.log("File info div:", document.getElementById("fileInfo"));
    console.log(
        "Upload button:",
        document.querySelector('#uploadReportForm button[type="submit"]')
    );
}

// Initialize filter handlers
function initializeFilterHandlers() {
    // Status filter
    const statusFilter = document.getElementById("statusFilter");
    if (statusFilter) {
        statusFilter.addEventListener("change", filterBookings);
    }

    // Date filter
    const dateFilter = document.getElementById("dateFilter");
    if (dateFilter) {
        dateFilter.addEventListener("change", filterBookings);
    }

    // Search input
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
        searchInput.addEventListener("input", debounce(filterBookings, 300));
    }

    // Refresh button
    const refreshBtn = document.getElementById("refreshBtn");
    if (refreshBtn) {
        refreshBtn.addEventListener("click", function () {
            loadBookings();
        });
    }
}

// Filter bookings based on filters
function filterBookings() {
    const statusFilter = document.getElementById("statusFilter");
    const dateFilter = document.getElementById("dateFilter");
    const searchInput = document.getElementById("searchInput");

    let filtered = [...allBookings];

    // Filter by status
    if (statusFilter && statusFilter.value !== "all") {
        filtered = filtered.filter(
            (booking) => booking.status === statusFilter.value
        );
    }

    // Filter by date
    if (dateFilter && dateFilter.value) {
        const selectedDate = dateFilter.value;
        filtered = filtered.filter(
            (booking) => booking.sample_collection_date === selectedDate
        );
    }

    // Filter by search term
    if (searchInput && searchInput.value.trim()) {
        const searchTerm = searchInput.value.trim().toLowerCase();
        filtered = filtered.filter(
            (booking) =>
                booking.booking_id.toLowerCase().includes(searchTerm) ||
                booking.customer_name.toLowerCase().includes(searchTerm) ||
                booking.phone.includes(searchTerm) ||
                booking.email.toLowerCase().includes(searchTerm)
        );
    }

    filteredBookings = filtered;
    displayBookings(filtered);
    updateStatistics(filtered);
}

// Utility functions
function closeModal(modalId) {
    console.log("Closing modal:", modalId);

    if (modalId) {
        // Close specific modal by ID
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = "none";
            console.log("Modal closed:", modalId);
        } else {
            console.error("Modal not found:", modalId);
        }
    } else {
        // Close all open modals if no specific ID provided
        const openModals = document.querySelectorAll(
            '.modal[style*="flex"], .modal-overlay[style*="flex"]'
        );
        openModals.forEach((modal) => {
            modal.style.display = "none";
        });
        console.log("All modals closed");
    }
}

function getStatusClass(status) {
    switch (status) {
        case "Pending":
            return "status-pending";
        case "Confirmed":
            return "status-confirmed";
        case "Sample Collected":
            return "status-collected";
        case "In Progress":
            return "status-progress";
        case "Upload Done":
            return "status-uploaded";
        case "Completed":
            return "status-completed";
        case "Cancelled":
            return "status-cancelled";
        default:
            return "status-pending";
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showLoader(message = "Loading...") {
    const loader = document.getElementById("loader");
    const loaderText = document.getElementById("loaderText");

    if (loader) {
        if (loaderText) {
            loaderText.textContent = message;
        }
        loader.style.display = "flex";
    }
}

function hideLoader() {
    const loader = document.getElementById("loader");
    if (loader) {
        loader.style.display = "none";
    }
}

function showRefreshIndicator() {
    const indicator = document.getElementById("refreshIndicator");
    if (indicator) {
        indicator.style.display = "block";
    }
}

function hideRefreshIndicator() {
    const indicator = document.getElementById("refreshIndicator");
    if (indicator) {
        indicator.style.display = "none";
    }
}

function showSuccess(message) {
    const toast = createToast(message, "success");
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function showError(message) {
    const toast = createToast(message, "error");
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function createToast(message, type) {
    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fa fa-${
            type === "success" ? "check-circle" : "exclamation-circle"
        }"></i>
        <span>${message}</span>
        <button class="close-toast" onclick="this.parentElement.remove()">
            <i class="fa fa-times"></i>
        </button>
    `;
    return toast;
}

function closeBookingModal() {
    closeModal("bookingModal");
}

function closeStatusModal() {
    closeModal("statusUpdateModal");
}

function closeUploadModal() {
    closeModal("uploadReportModal");
}

// Modal close handlers
document.addEventListener("click", function (e) {
    // Close modal when clicking outside
    if (e.target.classList.contains("modal")) {
        e.target.style.display = "none";
    }

    // Close modal when clicking close button
    if (e.target.classList.contains("close-modal")) {
        const modal = e.target.closest(".modal");
        if (modal) {
            modal.style.display = "none";
        }
    }
});

// Keyboard handlers
document.addEventListener("keydown", function (e) {
    // Close modal on Escape key
    if (e.key === "Escape") {
        const openModals = document.querySelectorAll('.modal[style*="flex"]');
        openModals.forEach((modal) => {
            modal.style.display = "none";
        });
    }
});
