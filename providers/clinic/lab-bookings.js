// Optimized Lab Bookings JavaScript
document.addEventListener("DOMContentLoaded", function () {
    addDisabledUploadButtonStyles();
    loadBookings();
    setInterval(loadBookings, 30000);
    initializeEventHandlers();
});

// Global variables
let allBookings = [];

// Load bookings from API
async function loadBookings() {
    try {
        showRefreshIndicator();
        const response = await fetch("lab-api.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=get_bookings",
        });

        if (!response.ok)
            throw new Error(`HTTP error! status: ${response.status}`);

        const text = await response.text();
        const data = JSON.parse(text);

        if (data.success) {
            allBookings = data.data || [];
            displayBookings(allBookings);
            updateStatistics(allBookings);
        } else {
            showError(
                "Failed to load bookings: " + (data.message || "Unknown error")
            );
        }
    } catch (error) {
        console.error("Error loading bookings:", error);
        showError("Network error: " + error.message);
        displayBookings([]);
    } finally {
        hideRefreshIndicator();
    }
}

// Display bookings in table
function displayBookings(bookings) {
    const table = document.querySelector(".bookings-table");
    if (!table) return;

    // Remove existing tbody
    const existingTbody = table.querySelector("tbody");
    if (existingTbody) existingTbody.remove();

    const tbody = document.createElement("tbody");

    if (!bookings?.length) {
        tbody.innerHTML = `
            <tr><td colspan="9" class="no-data">
                <div class="empty-state">
                    <i class="fa fa-flask"></i>
                    <h3>No lab bookings found</h3>
                    <p>No lab bookings available for this clinic</p>
                </div>
            </td></tr>`;
    } else {
        bookings.forEach((booking) =>
            tbody.appendChild(createBookingRow(booking))
        );
    }

    table.appendChild(tbody);
}

// Create booking table row
function createBookingRow(booking) {
    const row = document.createElement("tr");
    const {
        booking_id: id = "N/A",
        customer_name: name = "N/A",
        email = "N/A",
        phone = "N/A",
        formatted_collection_date: collectionDate = "N/A",
        time_slot: timeSlot = "N/A",
        test_count: testCount = 0,
        tests = "N/A",
        total_amount: amount = 0,
        status = "Pending",
        status_class: statusClass = "status-pending",
        has_report: hasReport = false,
        formatted_created_date: createdDate = "N/A",
    } = booking;

    // Determine if upload button should be enabled
    const isUploadEnabled = status === "Sample Collected";
    const uploadButtonClass = isUploadEnabled
        ? "upload-btn"
        : "upload-btn disabled";
    const uploadButtonTitle = isUploadEnabled
        ? "Upload Report"
        : "Upload only available after Sample Collection";

    row.innerHTML = `
        <td><div class="booking-id">${id}</div><div class="booking-date">${createdDate}</div></td>
        <td><div class="patient-name">${name}</div><div class="patient-email">${email}</div></td>
        <td><div class="phone">${phone}</div></td>
        <td><div class="collection-date">${collectionDate}</div></td>
        <td><div class="time-slot">${timeSlot}</div></td>
        <td><div class="tests-info">
            <div class="test-count">${testCount} test(s)</div>
            <div class="test-names">${tests}</div>
        </div></td>
        <td><div class="amount">₹${amount}</div></td>
        <td>
            <span class="status-badge ${statusClass}">${status}</span>
            ${
                hasReport
                    ? '<div class="report-indicator"><i class="fa fa-file-alt"></i> Report Available</div>'
                    : ""
            }
        </td>
        <td><div class="action-buttons">
            <button class="action-btn view-btn" onclick="viewBookingDetails('${id}')" title="View Details">
                <i class="fa fa-eye"></i>
            </button>
            <button class="action-btn status-btn" onclick="showStatusModal('${id}', '${status}')" title="Update Status">
                <i class="fa fa-edit"></i>
            </button>
            <button class="action-btn ${uploadButtonClass}" 
                onclick="${
                    isUploadEnabled
                        ? `showUploadModal('${id}')`
                        : "return false;"
                }" 
                title="${uploadButtonTitle}"
                ${!isUploadEnabled ? "disabled" : ""}>
                <i class="fa fa-upload"></i>
            </button>
        </div></td>`;
    return row;
}

function addDisabledUploadButtonStyles() {
    const style = document.createElement("style");
    style.textContent = `
        .upload-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed !important;
            background-color: #ccc !important;
            color: #666 !important;
        }
        
        .upload-btn.disabled:hover {
            background-color: #ccc !important;
            color: #666 !important;
            transform: none !important;
        }
        
        .upload-btn.disabled i {
            color: #666 !important;
        }
    `;
    document.head.appendChild(style);
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

    [
        "totalBookings",
        "pendingBookings",
        "completedBookings",
        "todayBookings",
    ].forEach((id, i) => {
        const el = document.getElementById(id);
        if (el) el.textContent = Object.values(stats)[i];
    });
}

// View booking details
async function viewBookingDetails(bookingId) {
    try {
        const response = await fetch(
            `lab-api.php?action=get_booking_details&booking_id=${bookingId}`
        );
        if (!response.ok)
            throw new Error(`HTTP error! status: ${response.status}`);

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
        showError("Network error: " + error.message);
    }
}

// Show booking details modal
function showBookingModal(booking) {
    const modal = document.getElementById("bookingModal");
    const modalBody = document.getElementById("modalBody");
    if (!modal || !modalBody) return;

    modalBody.innerHTML = `
        <div class="booking-detail-content">
            <div class="detail-section">
                <h3><i class="fa fa-user"></i> Customer Information</h3>
                <div class="detail-grid">
                    <div class="detail-item"><label>Name:</label><span>${
                        booking.customer_name || "N/A"
                    }</span></div>
                    <div class="detail-item"><label>Phone:</label><span>${
                        booking.phone || "N/A"
                    }</span></div>
                    <div class="detail-item"><label>Email:</label><span>${
                        booking.email || "N/A"
                    }</span></div>
                    <div class="detail-item"><label>Address:</label><span>${
                        booking.address || "N/A"
                    }</span></div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fa fa-calendar"></i> Booking Information</h3>
                <div class="detail-grid">
                    <div class="detail-item"><label>Booking ID:</label><span>${
                        booking.booking_id || "N/A"
                    }</span></div>
                    <div class="detail-item"><label>Collection Date:</label><span>${
                        booking.formatted_collection_date || "N/A"
                    }</span></div>
                    <div class="detail-item"><label>Time Slot:</label><span>${
                        booking.time_slot || "N/A"
                    }</span></div>
                    <div class="detail-item"><label>Status:</label>
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
                        booking.tests?.length > 0
                            ? booking.tests
                                  .map(
                                      (test) =>
                                          `<div class="test-item">${test}</div>`
                                  )
                                  .join("")
                            : '<div class="no-tests">No tests found</div>'
                    }
                </div>
                <div class="total-amount"><strong>Total Amount: ₹${
                    booking.total_amount || 0
                }</strong></div>
            </div>
            
            ${
                booking.has_report
                    ? `
                <div class="detail-section">
                    <h3><i class="fa fa-file-alt"></i> Report Information</h3>
                    <div class="report-info">
                        <div class="report-item"><label>Report File:</label><span>${
                            booking.report_file || "N/A"
                        }</span></div>
                        <div class="report-item"><label>Uploaded At:</label><span>${
                            booking.formatted_report_date || "N/A"
                        }</span></div>
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
                    <div class="timeline-item"><label>Created:</label><span>${
                        booking.formatted_created_date || "N/A"
                    }</span></div>
                    <div class="timeline-item"><label>Last Updated:</label><span>${
                        booking.formatted_updated_date || "N/A"
                    }</span></div>
                </div>
            </div>
        </div>`;

    modal.style.display = "flex";
}

// Show status modal - Fixed to work with the correct modal ID
function showStatusModal(bookingId, currentStatus) {
    const modal = document.getElementById("statusUpdateModal");
    const elements = {
        bookingInfo: document.getElementById("statusBookingInfo"),
        statusSelect: document.getElementById("statusSelect"),
        hiddenBookingId: document.getElementById("statusBookingId"),
    };

    if (
        !modal ||
        !elements.bookingInfo ||
        !elements.statusSelect ||
        !elements.hiddenBookingId
    ) {
        console.error("Status modal elements not found", elements);
        return;
    }

    elements.bookingInfo.textContent = `Booking ID: ${bookingId}`;
    elements.hiddenBookingId.value = bookingId;
    elements.statusSelect.value = currentStatus;
    modal.style.display = "flex";
}

// Show upload modal - Fixed to work with the correct modal
function showUploadModal(bookingId) {
    console.log("Opening upload modal for booking:", bookingId);

    const modal = document.getElementById("uploadReportModal");
    if (!modal) {
        console.error("Upload modal not found");
        return;
    }

    const elements = {
        bookingInfo: document.getElementById("uploadBookingInfo"),
        hiddenBookingId: document.getElementById("uploadBookingId"),
        fileInput: document.getElementById("reportFile"),
        fileInfo: document.getElementById("fileInfo"),
        uploadBtn: document.querySelector(
            '#uploadReportForm button[type="submit"]'
        ),
    };

    console.log("Modal elements found:", elements);

    // Set booking info and ID
    if (elements.bookingInfo) {
        elements.bookingInfo.textContent = `Booking ID: ${bookingId}`;
    }

    if (elements.hiddenBookingId) {
        elements.hiddenBookingId.value = bookingId;
    }

    // Reset form
    if (elements.fileInput) {
        elements.fileInput.value = "";
    }

    if (elements.fileInfo) {
        elements.fileInfo.innerHTML = "";
    }

    // Keep upload button disabled until file is selected
    if (elements.uploadBtn) {
        elements.uploadBtn.disabled = true;
    }

    modal.style.display = "flex";
}

// Update booking status
async function updateBookingStatus() {
    const bookingId = document.getElementById("statusBookingId")?.value;
    const status = document.getElementById("statusSelect")?.value;

    if (!bookingId || !status) {
        showError("Please select a status");
        return;
    }

    await performApiAction(
        "update_status",
        { booking_id: bookingId, status },
        "Updating status...",
        "Status updated successfully",
        "statusUpdateModal"
    );
}

// Upload report - Fixed validation and error handling
async function uploadReport() {
    const bookingId = document.getElementById("uploadBookingId")?.value;
    const fileInput = document.getElementById("reportFile");

    console.log("Upload report called", {
        bookingId,
        fileInput,
        files: fileInput?.files,
    });

    if (!bookingId) {
        showError("Booking ID is missing");
        return;
    }

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        showError("Please select a file to upload");
        return;
    }

    const file = fileInput.files[0];
    console.log("Selected file:", file);

    const validation = validateFile(file);
    if (!validation.valid) {
        showError(validation.message);
        return;
    }

    const formData = new FormData();
    formData.append("action", "upload_report");
    formData.append("booking_id", bookingId);
    formData.append("report_file", file);

    console.log("Uploading with FormData:", {
        action: "upload_report",
        booking_id: bookingId,
        file_name: file.name,
    });

    await performApiAction(
        null,
        formData,
        "Uploading report...",
        "Report uploaded successfully",
        "uploadReportModal"
    );
}

// Generic API action handler
async function performApiAction(
    action,
    data,
    loadingMsg,
    successMsg,
    modalToClose = null
) {
    try {
        showLoader(loadingMsg);

        const formData = data instanceof FormData ? data : new FormData();
        if (action && !(data instanceof FormData)) {
            formData.append("action", action);
            Object.entries(data).forEach(([key, value]) =>
                formData.append(key, value)
            );
        }

        console.log("Performing API action:", action || "FormData", formData);

        const response = await fetch("lab-api.php", {
            method: "POST",
            body: formData,
        });

        if (!response.ok)
            throw new Error(`HTTP error! status: ${response.status}`);

        const text = await response.text();
        console.log("API response text:", text);

        const result = JSON.parse(text);
        console.log("API response:", result);

        if (result.success) {
            showSuccess(successMsg);
            if (modalToClose) closeModal(modalToClose);
            loadBookings();
        } else {
            showError(
                "Operation failed: " + (result.message || "Unknown error")
            );
        }
    } catch (error) {
        console.error("API Error:", error);
        showError("Network error: " + error.message);
    } finally {
        hideLoader();
    }
}

// File validation - Fixed to be more permissive
function validateFile(file) {
    console.log("Validating file:", {
        name: file.name,
        type: file.type,
        size: file.size,
    });

    // Check file size first (10MB limit)
    if (file.size > 10 * 1024 * 1024) {
        return {
            valid: false,
            message: "File size too large. Maximum 10MB allowed",
        };
    }

    // Check file extension
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
        return {
            valid: false,
            message: `Invalid file type. Only PDF, JPG, PNG, DOC, DOCX allowed. Selected: ${fileName}`,
        };
    }

    console.log("File validation passed");
    return { valid: true };
}

// Initialize all event handlers
function initializeEventHandlers() {
    console.log("Initializing event handlers");

    // Form handlers
    const statusForm = document.getElementById("statusUpdateForm");
    const uploadForm = document.getElementById("uploadReportForm");

    if (statusForm) {
        statusForm.addEventListener("submit", (e) => {
            e.preventDefault();
            updateBookingStatus();
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener("submit", (e) => {
            e.preventDefault();
            uploadReport();
        });
    }

    // File upload handler - Fixed to properly enable/disable button
    const fileInput = document.getElementById("reportFile");
    if (fileInput) {
        console.log("Setting up file input handler");
        fileInput.addEventListener("change", function (e) {
            console.log("File input changed:", e.target.files);

            const file = e.target.files[0];
            const uploadBtn = document.querySelector(
                '#uploadReportForm button[type="submit"]'
            );
            const fileInfo = document.getElementById("fileInfo");

            if (file) {
                console.log("File selected:", file.name);

                const validation = validateFile(file);
                if (!validation.valid) {
                    console.log("File validation failed:", validation.message);
                    showError(validation.message);
                    e.target.value = "";
                    if (uploadBtn) uploadBtn.disabled = true;
                    if (fileInfo) fileInfo.innerHTML = "";
                    return;
                }

                console.log("File validation passed, updating UI");

                // Update file info display
                if (fileInfo) {
                    fileInfo.innerHTML = `
                        <div class="file-selected">
                            <i class="fa fa-file"></i>
                            <span>${file.name}</span>
                            <small>(${formatFileSize(file.size)})</small>
                            <div class="file-details">Type: ${
                                file.type || "Unknown"
                            }</div>
                        </div>`;
                }

                // Enable upload button
                if (uploadBtn) {
                    uploadBtn.disabled = false;
                    console.log("Upload button enabled");
                }
            } else {
                console.log("No file selected");
                if (uploadBtn) {
                    uploadBtn.disabled = true;
                    console.log("Upload button disabled");
                }
                if (fileInfo) fileInfo.innerHTML = "";
            }
        });
    } else {
        console.error("File input not found");
    }

    // Filter handlers
    ["statusFilter"].forEach((id) => {
        const element = document.getElementById(id);
        if (element) element.addEventListener("change", filterBookings);
    });

    // Initialize modal close handlers
    initializeModalCloseHandlers();
}

function initializeModalCloseHandlers() {
    console.log("Initializing modal close handlers");

    // Handle clicks on modal overlay/background
    document.addEventListener("click", function (e) {
        // Close modal when clicking on overlay background
        if (
            e.target.classList.contains("modal") ||
            e.target.classList.contains("modal-overlay")
        ) {
            e.target.style.display = "none";
        }

        // Handle close button clicks
        if (
            e.target.classList.contains("close-modal") ||
            e.target.classList.contains("modal-close")
        ) {
            const modal =
                e.target.closest(".modal") ||
                e.target.closest(".modal-overlay");
            if (modal) {
                modal.style.display = "none";
            }
        }

        // Handle close icon inside buttons
        if (
            e.target.parentElement &&
            (e.target.parentElement.classList.contains("close-modal") ||
                e.target.parentElement.classList.contains("modal-close"))
        ) {
            const modal =
                e.target.closest(".modal") ||
                e.target.closest(".modal-overlay");
            if (modal) {
                modal.style.display = "none";
            }
        }
    });

    // Handle ESC key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            document
                .querySelectorAll(
                    '.modal[style*="flex"], .modal-overlay[style*="flex"], .modal[style*="block"]'
                )
                .forEach((modal) => {
                    modal.style.display = "none";
                });
        }
    });
}

// Filter bookings - FIXED VERSION
function filterBookings() {
    const statusFilter = document.getElementById("statusFilter");

    let filtered = [...allBookings];

    if (statusFilter?.value && statusFilter.value !== "All Statuses") {
        filtered = filtered.filter(
            (booking) => booking.status === statusFilter.value
        );
    }

    displayBookings(filtered);

    // FIXED: Always pass allBookings to keep total count accurate
    updateStatistics(allBookings);
}

// Utility functions
function closeModal(modalId = null) {
    if (modalId) {
        // Close specific modal by ID
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = "none";
            console.log("Closed modal:", modalId);
        }
    } else {
        // Close all open modals if no specific ID provided
        document
            .querySelectorAll(
                '.modal[style*="flex"], .modal-overlay[style*="flex"], .modal[style*="block"]'
            )
            .forEach((modal) => {
                modal.style.display = "none";
            });
        console.log("Closed all open modals");
    }
}

function getStatusClass(status) {
    const statusMap = {
        Pending: "status-pending",
        Confirmed: "status-confirmed",
        "Sample Collected": "status-collected",
        "In Progress": "status-progress",
        "Upload Done": "status-uploaded",
        Completed: "status-completed",
        Cancelled: "status-cancelled",
    };
    return statusMap[status] || "status-pending";
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
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// UI feedback functions
function showLoader(message = "Loading...") {
    const loader = document.getElementById("loadingOverlay");
    if (loader) {
        loader.style.display = "flex";
        console.log("Showing loader:", message);
    }
}

function hideLoader() {
    const loader = document.getElementById("loadingOverlay");
    if (loader) {
        loader.style.display = "none";
        console.log("Hiding loader");
    }
}

function showRefreshIndicator() {
    const indicator = document.getElementById("refreshIndicator");
    if (indicator) indicator.style.display = "block";
}

function hideRefreshIndicator() {
    const indicator = document.getElementById("refreshIndicator");
    if (indicator) indicator.style.display = "none";
}

function showSuccess(message) {
    showToast(message, "success", 3000);
}

function showError(message) {
    showToast(message, "error", 5000);
    console.error("Error:", message);
}

function showToast(message, type, duration) {
    // Remove existing toasts
    document.querySelectorAll(".toast").forEach((toast) => toast.remove());

    const toast = document.createElement("div");
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === "success" ? "#4CAF50" : "#f44336"};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 400px;
    `;

    toast.innerHTML = `
        <i class="fa fa-${
            type === "success" ? "check-circle" : "exclamation-circle"
        }"></i>
        <span>${message}</span>
        <button class="close-toast" onclick="this.parentElement.remove()" style="
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-left: 10px;
        ">
            <i class="fa fa-times"></i>
        </button>`;

    document.body.appendChild(toast);
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, duration);
}
