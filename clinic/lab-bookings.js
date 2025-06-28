
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
                const lastActivity = localStorage.getItem("lastActivity");
                const now = Date.now();

                if (!lastActivity || now - parseInt(lastActivity) < 10 * 60 * 1000) {
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
            const indicator = document.getElementById("refreshIndicator");
            if (indicator) {
                indicator.classList.add("show");
                setTimeout(() => {
                    indicator.classList.remove("show");
                }, 2000);
            }
        }

        function trackUserActivity() {
            localStorage.setItem("lastActivity", Date.now().toString());
        }

        // Track user activity
        document.addEventListener("click", trackUserActivity);
        document.addEventListener("keypress", trackUserActivity);
        document.addEventListener("scroll", trackUserActivity);

        // Page visibility API to pause/resume refresh when tab is not visible
        document.addEventListener("visibilitychange", () => {
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
        window.addEventListener("load", () => {
            trackUserActivity();
            startAutoRefresh();
        });

        // Upload Modal Functions
        function openUploadModal(bookingId, bookingIdStr, customerName) {
            const modal = document.getElementById("uploadModal");
            const bookingInfo = document.getElementById("uploadBookingInfo");
            const bookingIdInput = document.getElementById("uploadBookingId");

            if (!modal || !bookingInfo || !bookingIdInput) {
                console.error("Upload modal elements not found");
                return;
            }

            // Populate booking info
            bookingInfo.innerHTML = `
                <div class="upload-info-card">
                    <h4><i class="fa fa-vial"></i> Booking ID: ${bookingIdStr}</h4>
                    <p><strong>Patient:</strong> ${customerName}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-sample-collected">Sample Collected</span></p>
                </div>
            `;

            // Set booking ID
            bookingIdInput.value = bookingId;

            // Reset form
            const uploadForm = document.getElementById("uploadReportForm");
            if (uploadForm) {
                uploadForm.reset();
            }
            resetFileUpload();

            // Show modal
            modal.classList.add("show");
        }

        function closeUploadModal() {
            const modal = document.getElementById("uploadModal");
            if (modal) {
                modal.classList.remove("show");
            }
            resetFileUpload();
        }

        // File Upload Handling
        document.addEventListener("DOMContentLoaded", function() {
            const reportFileInput = document.getElementById("reportFile");
            if (reportFileInput) {
                reportFileInput.addEventListener("change", function (e) {
                    const file = e.target.files[0];
                    const fileDisplay = document.querySelector(".file-upload-display");
                    const placeholder = document.querySelector(".file-upload-placeholder");
                    const selectedDiv = document.querySelector(".file-selected");
                    const fileName = document.querySelector(".file-name");

                    if (file) {
                        // Check file size (10MB limit)
                        if (file.size > 10 * 1024 * 1024) {
                            alert("File size must be less than 10MB");
                            this.value = "";
                            return;
                        }

                        if (fileName) fileName.textContent = file.name;
                        if (placeholder) placeholder.style.display = "none";
                        if (selectedDiv) selectedDiv.style.display = "flex";
                        if (fileDisplay) fileDisplay.classList.add("file-selected-state");
                    }
                });
            }
        });

        function removeSelectedFile() {
            const reportFileInput = document.getElementById("reportFile");
            if (reportFileInput) {
                reportFileInput.value = "";
            }
            resetFileUpload();
        }

        function resetFileUpload() {
            const fileDisplay = document.querySelector(".file-upload-display");
            const placeholder = document.querySelector(".file-upload-placeholder");
            const selectedDiv = document.querySelector(".file-selected");

            if (placeholder) placeholder.style.display = "block";
            if (selectedDiv) selectedDiv.style.display = "none";
            if (fileDisplay) fileDisplay.classList.remove("file-selected-state");

            // Hide progress
            const uploadProgress = document.getElementById("uploadProgress");
            if (uploadProgress) {
                uploadProgress.style.display = "none";
            }
        }

        // Handle form submission
        document.addEventListener("DOMContentLoaded", function() {
            const uploadForm = document.getElementById("uploadReportForm");
            if (uploadForm) {
                uploadForm.addEventListener("submit", function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector(".upload-btn-submit");
                    const progressDiv = document.getElementById("uploadProgress");
                    const progressBar = document.querySelector(".progress-fill");
                    const progressText = document.querySelector(".progress-text");

                    if (!submitBtn) return;

                    // Show progress
                    if (progressDiv) progressDiv.style.display = "block";
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Uploading...';

                    // Create XMLHttpRequest for progress tracking
                    const xhr = new XMLHttpRequest();

                    // Track upload progress
                    xhr.upload.addEventListener("progress", function (e) {
                        if (e.lengthComputable && progressBar && progressText) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            progressBar.style.width = percentComplete + "%";
                            progressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
                        }
                    });

                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    if (progressText) progressText.textContent = "Upload Complete!";
                                    if (progressBar) progressBar.style.background = "#28a745";

                                    setTimeout(() => {
                                        closeUploadModal();
                                        location.reload(); // Refresh to show updated status
                                    }, 1500);
                                } else {
                                    alert("Upload failed: " + response.message);
                                    resetUploadForm();
                                }
                            } catch (e) {
                                alert("Upload failed: Invalid response");
                                resetUploadForm();
                            }
                        } else {
                            alert("Upload failed: Server error");
                            resetUploadForm();
                        }
                    };

                    xhr.onerror = function () {
                        alert("Upload failed: Network error");
                        resetUploadForm();
                    };

                    function resetUploadForm() {
                        if (progressDiv) progressDiv.style.display = "none";
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa fa-upload"></i> Upload Report';
                        if (progressBar) {
                            progressBar.style.width = "0%";
                            progressBar.style.background = "#007bff";
                        }
                        if (progressText) progressText.textContent = "Uploading... 0%";
                    }

                    xhr.open("POST", "upload-report.php"); // Use specific upload endpoint
                    xhr.send(formData);
                });
            }
        });

        // Drag and drop functionality
        document.addEventListener("DOMContentLoaded", function() {
            const fileUploadWrapper = document.querySelector(".file-upload-wrapper");
            
            if (fileUploadWrapper) {
                fileUploadWrapper.addEventListener("dragover", function (e) {
                    e.preventDefault();
                    this.classList.add("drag-over");
                });

                fileUploadWrapper.addEventListener("dragleave", function (e) {
                    e.preventDefault();
                    this.classList.remove("drag-over");
                });

                fileUploadWrapper.addEventListener("drop", function (e) {
                    e.preventDefault();
                    this.classList.remove("drag-over");

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const reportFileInput = document.getElementById("reportFile");
                        if (reportFileInput) {
                            reportFileInput.files = files;
                            reportFileInput.dispatchEvent(new Event("change"));
                        }
                    }
                });
            }
        });

        // Modal functions - Updated to use lab_api.php
        function viewBooking(bookingId) {
            if (!bookingId) {
                alert("Please select a booking to view");
                return;
            }

            const modal = document.getElementById("bookingModal");
            const modalBody = document.getElementById("modalBody");

            if (!modal || !modalBody) {
                console.error("Modal elements not found");
                return;
            }

            // Show loading spinner
            modalBody.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Loading booking details...</p>
                </div>
            `;

            // Show modal
            modal.classList.add("show");

            // Fetch booking details using lab_api.php
            fetch(`lab_api.php?ajax=get_booking_details&booking_id=${bookingId}`)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Failed to fetch booking details");
                    }
                    return response.json();
                })
                .then((data) => {
                    displayBookingDetails(data);
                })
                .catch((error) => {
                    modalBody.innerHTML = `
                        <div class="error-message">
                            <i class="fa fa-exclamation-triangle"></i>
                            <p>Error loading booking details: ${error.message}</p>
                            <button onclick="closeModal()" class="btn-primary">Close</button>
                        </div>
                    `;
                });
        }

        function displayBookingDetails(booking) {
            const modalBody = document.getElementById("modalBody");
            if (!modalBody) return;

            // Parse test details
            let testDetailsHtml = "";
            if (booking.test_details) {
                const tests = booking.test_details.split("|");
                testDetailsHtml = tests.map((test) => `<li>${test}</li>`).join("");
            }

            modalBody.innerHTML = `
                <div class="booking-details">
                    <div class="detail-section">
                        <h3><i class="fa fa-user"></i> Patient Information</h3>
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
                                <label>Age:</label>
                                <span>${booking.age || "N/A"}</span>
                            </div>
                            <div class="detail-item">
                                <label>Gender:</label>
                                <span>${booking.gender || "N/A"}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fa fa-calendar"></i> Booking Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Booking ID:</label>
                                <span class="booking-id">${booking.booking_id}</span>
                            </div>
                            <div class="detail-item">
                                <label>Collection Date:</label>
                                <span>${new Date(booking.sample_collection_date).toLocaleDateString()}</span>
                            </div>
                            <div class="detail-item">
                                <label>Time Slot:</label>
                                <span>${booking.time_slot}</span>
                            </div>
                            <div class="detail-item">
                                <label>Status:</label>
                                <span class="status-badge status-${booking.status.toLowerCase().replace(/\s+/g, "-")}">${booking.status}</span>
                            </div>
                            <div class="detail-item">
                                <label>Created:</label>
                                <span>${new Date(booking.created_at).toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fa fa-vial"></i> Test Information</h3>
                        <div class="test-details">
                            ${testDetailsHtml ? `<ul class="test-list">${testDetailsHtml}</ul>` : "<p>No test details available</p>"}
                        </div>
                        <div class="detail-item">
                            <label>Total Amount:</label>
                            <span class="amount">₹${parseFloat(booking.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                    
                    ${booking.address ? `
                        <div class="detail-section">
                            <h3><i class="fa fa-map-marker"></i> Address</h3>
                            <p class="address">${booking.address}</p>
                        </div>
                    ` : ""}
                </div>
                
                <div class="modal-actions">
                    <button onclick="closeModal()" class="btn-secondary">Close</button>
                    ${booking.status !== "Cancelled" && booking.status !== "Completed" ? `
                        <button onclick="openStatusUpdateModal(${booking.id}, '${booking.booking_id}', '${booking.customer_name}', '${booking.status}')" class="btn-primary">
                            <i class="fa fa-edit"></i> Update Status
                        </button>
                    ` : ""}
                </div>
            `;
        }

        function closeModal() {
            const modal = document.getElementById("bookingModal");
            if (modal) {
                modal.classList.remove("show");
            }
        }

        // Status Update Modal Functions
        function openStatusUpdateModal(bookingId, bookingIdStr, customerName, currentStatus) {
            const modal = document.getElementById("statusUpdateModal");
            const bookingInfo = document.getElementById("statusBookingInfo");
            const bookingIdInput = document.getElementById("statusBookingId");
            const statusSelect = document.getElementById("statusSelect");

            if (!modal || !bookingInfo || !bookingIdInput || !statusSelect) {
                console.error("Status update modal elements not found");
                return;
            }

            // Populate booking info
            bookingInfo.innerHTML = `
                <div class="status-info-card">
                    <h4><i class="fa fa-vial"></i> Booking ID: ${bookingIdStr}</h4>
                    <p><strong>Patient:</strong> ${customerName}</p>
                    <p><strong>Current Status:</strong> <span class="status-badge status-${currentStatus.toLowerCase().replace(/\s+/g, "-")}">${currentStatus}</span></p>
                </div>
            `;

            // Set booking ID
            bookingIdInput.value = bookingId;

            // Set current status as selected
            statusSelect.value = currentStatus;

            // Show modal
            modal.classList.add("show");
        }

        function closeStatusModal() {
            const modal = document.getElementById("statusUpdateModal");
            if (modal) {
                modal.classList.remove("show");
            }
        }

        // Handle status update form submission - Updated to use lab_api.php
        document.addEventListener("DOMContentLoaded", function() {
            const statusUpdateForm = document.getElementById("statusUpdateForm");
            if (statusUpdateForm) {
                statusUpdateForm.addEventListener("submit", function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector(".status-btn-update");

                    if (!submitBtn) return;

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';

                    // Use lab_api.php for status updates
                    fetch("lab_api.php", {
                        method: "POST",
                        body: formData,
                    })
                    .then((response) => {
                        // Check if response is JSON or HTML (redirect)
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.includes("application/json")) {
                            return response.json();
                        } else {
                            // If it's a redirect or HTML response, consider it successful
                            if (response.ok) {
                                return { success: true, message: "Status updated successfully" };
                            } else {
                                throw new Error("Server returned an error");
                            }
                        }
                    })
                    .then((data) => {
                        if (data.success !== false) { // Consider success if not explicitly false
                            closeStatusModal();
                            
                            // Show success message
                            const successMsg = document.createElement('div');
                            successMsg.className = 'alert alert-success';
                            successMsg.innerHTML = '<i class="fa fa-check"></i> Status updated successfully!';
                            successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px;';
                            document.body.appendChild(successMsg);
                            
                            setTimeout(() => {
                                successMsg.remove();
                                location.reload(); // Refresh to show updated status
                            }, 2000);
                        } else {
                            alert("Update failed: " + (data.message || "Unknown error"));
                        }
                    })
                    .catch((error) => {
                        alert("Update failed: " + error.message);
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa fa-check"></i> Update Status';
                    });
                });
            }
        });

        // Cancel booking function - Updated to use lab_api.php
        function cancelBooking(bookingId, bookingIdStr) {
            if (confirm(`Are you sure you want to cancel booking ${bookingIdStr}?`)) {
                const formData = new FormData();
                formData.append("booking_id", bookingId);
                formData.append("status", "Cancelled");

                fetch("lab_api.php", {
                    method: "POST",
                    body: formData,
                })
                .then((response) => {
                    // Handle both JSON and redirect responses
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.includes("application/json")) {
                        return response.json();
                    } else {
                        if (response.ok) {
                            return { success: true, message: "Booking cancelled successfully" };
                        } else {
                            throw new Error("Server returned an error");
                        }
                    }
                })
                .then((data) => {
                    if (data.success !== false) {
                        // Show success message
                        const successMsg = document.createElement('div');
                        successMsg.className = 'alert alert-success';
                        successMsg.innerHTML = '<i class="fa fa-check"></i> Booking cancelled successfully!';
                        successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px;';
                        document.body.appendChild(successMsg);
                        
                        setTimeout(() => {
                            successMsg.remove();
                            location.reload();
                        }, 2000);
                    } else {
                        alert("Cancel failed: " + (data.message || "Unknown error"));
                    }
                })
                .catch((error) => {
                    alert("Cancel failed: " + error.message);
                });
            }
        }

        // Pagination function
        function goToPage(page) {
            const url = new URL(window.location);
            url.searchParams.set("page", page);
            window.location.href = url.toString();
        }

        // Clear filters function
        function clearFilters() {
            const url = new URL(window.location);
            url.searchParams.delete("search");
            url.searchParams.delete("date_filter");
            url.searchParams.delete("status_filter");
            url.searchParams.delete("page");
            window.location.href = url.toString();
        }

        // Close modals when clicking outside
        window.addEventListener("click", function (e) {
            const bookingModal = document.getElementById("bookingModal");
            const uploadModal = document.getElementById("uploadModal");
            const statusModal = document.getElementById("statusUpdateModal");

            if (e.target === bookingModal) {
                closeModal();
            }
            if (e.target === uploadModal) {
                closeUploadModal();
            }
            if (e.target === statusModal) {
                closeStatusModal();
            }
        });

        // Keyboard shortcuts
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                closeModal();
                closeUploadModal();
                closeStatusModal();
            }
        });

        // Initialize tooltips and other UI enhancements
        document.addEventListener("DOMContentLoaded", function () {
            console.log("Lab booking system initialized with lab_api.php integration");
        });