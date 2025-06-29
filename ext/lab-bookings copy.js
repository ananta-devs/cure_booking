// Optimized Lab Bookings JavaScript Integration
// Enhanced performance, maintainability, and user experience

class LabBookingsManager {
    constructor() {
        this.allBookings = [];
        this.filteredBookings = [];
        this.searchTimeout = null;
        this.refreshInterval = null;
        this.abortController = null;
        
        // Cache DOM elements
        this.elements = {};
        this.cacheElements();
        
        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    // Cache frequently used DOM elements
    cacheElements() {
        const selectors = {
            bookingsTable: '.bookings-table',
            bookingsTableBody: '.bookings-table tbody',
            refreshIndicator: '#refreshIndicator',
            bookingModal: '#bookingModal',
            statusUpdateModal: '#statusUpdateModal',
            uploadModal: '#uploadModal',
            modalBody: '#modalBody',
            statusBookingInfo: '#statusBookingInfo',
            uploadBookingInfo: '#uploadBookingInfo',
            statusSelect: '#statusSelect',
            statusBookingId: '#statusBookingId',
            uploadBookingId: '#uploadBookingId',
            statusUpdateForm: '#statusUpdateForm',
            uploadReportForm: '#uploadReportForm',
            reportFile: '#reportFile',
            fileUploadWrapper: '.file-upload-wrapper',
            uploadProgress: '#uploadProgress',
            progressFill: '.progress-fill',
            progressText: '.progress-text',
            toastNotification: '#toastNotification',
            statusFilter: '#statusFilter',
            dateFilter: '#dateFilter',
            searchFilter: '#searchFilter',
            totalBookings: '#totalBookings',
            pendingBookings: '#pendingBookings',
            completedBookings: '#completedBookings',
            todayBookings: '#todayBookings'
        };

        // Cache elements that exist
        Object.keys(selectors).forEach(key => {
            const element = document.querySelector(selectors[key]);
            if (element) {
                this.elements[key] = element;
            }
        });
    }

    // Initialize the application
    init() {
        this.loadBookings();
        this.setupAutoRefresh();
        this.initializeEventListeners();
        this.setupKeyboardShortcuts();
    }

    // Setup auto-refresh with cleanup
    setupAutoRefresh() {
        // Clear existing interval
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        // Auto-refresh every 30 seconds
        this.refreshInterval = setInterval(() => this.loadBookings(), 30000);
        
        // Handle page visibility change to pause/resume refresh
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(this.refreshInterval);
            } else {
                this.refreshInterval = setInterval(() => this.loadBookings(), 30000);
            }
        });
    }

    // Initialize all event listeners
    initializeEventListeners() {
        this.initializeFormHandlers();
        this.initializeFileUpload();
        this.initializeModalHandlers();
        this.initializeFilterHandlers();
        this.initializeUtilityHandlers();
    }

    // Load bookings with abort controller for cancellation
    async loadBookings() {
        try {
            // Cancel previous request if still pending
            if (this.abortController) {
                this.abortController.abort();
            }
            
            this.abortController = new AbortController();
            this.showRefreshIndicator();

            const response = await fetch('lab-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_bookings',
                signal: this.abortController.signal
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.allBookings = data.data;
                this.filteredBookings = [...this.allBookings];
                this.displayBookings(this.filteredBookings);
                this.updateStatistics(this.filteredBookings);
            } else {
                this.showError(`Failed to load bookings: ${data.message}`);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error loading bookings:', error);
                this.showError('Network error while loading bookings');
            }
        } finally {
            this.hideRefreshIndicator();
            this.abortController = null;
        }
    }

    // Optimized display bookings with virtual scrolling consideration
    displayBookings(bookings) {
        const tbody = this.elements.bookingsTableBody;
        if (!tbody && !this.elements.bookingsTable) return;

        // Remove existing tbody
        if (tbody) {
            tbody.remove();
        }

        const newTbody = document.createElement('tbody');
        const fragment = document.createDocumentFragment();

        if (bookings.length === 0) {
            newTbody.innerHTML = this.getEmptyStateHTML();
        } else {
            // Use document fragment for better performance
            bookings.forEach(booking => {
                const row = this.createBookingRow(booking);
                fragment.appendChild(row);
            });
            newTbody.appendChild(fragment);
        }

        this.elements.bookingsTable.appendChild(newTbody);
        this.elements.bookingsTableBody = newTbody;
    }

    // Optimized row creation with template literals
    createBookingRow(booking) {
        const row = document.createElement('tr');
        row.className = `booking-row status-${booking.status.toLowerCase().replace(/\s+/g, '-')}`;
        row.dataset.bookingId = booking.booking_id;
        
        row.innerHTML = `
            <td>
                <div class="booking-id">${this.escapeHtml(booking.booking_id)}</div>
                <div class="booking-date">${this.escapeHtml(booking.formatted_created_date)}</div>
            </td>
            <td>
                <div class="patient-name">${this.escapeHtml(booking.customer_name)}</div>
                <div class="patient-email">${this.escapeHtml(booking.email)}</div>
            </td>
            <td>
                <div class="phone">${this.escapeHtml(booking.phone)}</div>
            </td>
            <td>
                <div class="collection-date">${this.escapeHtml(booking.formatted_collection_date)}</div>
            </td>
            <td>
                <div class="time-slot">${this.escapeHtml(booking.time_slot)}</div>
            </td>
            <td>
                <div class="tests-info">
                    <div class="test-count">${booking.test_count} test(s)</div>
                    <div class="test-names" title="${this.escapeHtml(booking.tests || 'N/A')}">${this.truncateText(booking.tests || 'N/A', 50)}</div>
                </div>
            </td>
            <td>
                <div class="amount">₹${this.formatCurrency(booking.total_amount)}</div>
            </td>
            <td>
                <span class="status-badge ${this.getStatusClass(booking.status)}">${this.escapeHtml(booking.status)}</span>
                ${booking.has_report ? '<div class="report-indicator"><i class="fa fa-file-alt"></i> Report Available</div>' : ''}
            </td>
            <td>
                <div class="action-buttons">
                    ${this.createActionButtons(booking.booking_id)}
                </div>
            </td>
        `;
        
        return row;
    }

    // Create action buttons with proper event delegation
    createActionButtons(bookingId) {
        return `
            <button class="action-btn view-btn" data-action="view" data-booking-id="${bookingId}" title="View Details">
                <i class="fa fa-eye"></i>
            </button>
            <button class="action-btn status-btn" data-action="status" data-booking-id="${bookingId}" title="Update Status">
                <i class="fa fa-edit"></i>
            </button>
            <button class="action-btn upload-btn" data-action="upload" data-booking-id="${bookingId}" title="Upload Report">
                <i class="fa fa-upload"></i>
            </button>
            <button class="action-btn delete-btn" data-action="delete" data-booking-id="${bookingId}" title="Delete Booking">
                <i class="fa fa-trash"></i>
            </button>
        `;
    }

    // Initialize form handlers with validation
    initializeFormHandlers() {
        // Status update form
        if (this.elements.statusUpdateForm) {
            this.elements.statusUpdateForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const bookingId = this.elements.statusBookingId?.value;
                const status = this.elements.statusSelect?.value;

                if (!bookingId || !status) {
                    this.showError('Please select a status');
                    return;
                }

                await this.updateBookingStatus(bookingId, status);
            });
        }

        // Upload report form with progress
        if (this.elements.uploadReportForm) {
            this.elements.uploadReportForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const fileInput = this.elements.reportFile;

                if (!fileInput?.files.length) {
                    this.showError('Please select a file to upload');
                    return;
                }

                await this.uploadReport(formData);
            });
        }
    }

    // Initialize file upload with drag and drop
    initializeFileUpload() {
        const fileInput = this.elements.reportFile;
        const uploadWrapper = this.elements.fileUploadWrapper;

        if (!fileInput || !uploadWrapper) return;

        fileInput.addEventListener('change', (e) => {
            this.handleFileSelect(e.target.files[0]);
        });

        // Enhanced drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadWrapper.addEventListener(eventName, this.preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadWrapper.addEventListener(eventName, () => {
                uploadWrapper.classList.add('drag-over');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadWrapper.addEventListener(eventName, () => {
                uploadWrapper.classList.remove('drag-over');
            }, false);
        });

        uploadWrapper.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                this.handleFileSelect(files[0]);
            }
        }, false);
    }

    // Initialize modal handlers with event delegation
    initializeModalHandlers() {
        // Event delegation for action buttons
        if (this.elements.bookingsTable) {
            this.elements.bookingsTable.addEventListener('click', (e) => {
                const actionBtn = e.target.closest('.action-btn');
                if (!actionBtn) return;

                const action = actionBtn.dataset.action;
                const bookingId = actionBtn.dataset.bookingId;

                switch (action) {
                    case 'view':
                        this.viewBookingDetails(bookingId);
                        break;
                    case 'status':
                        this.showStatusModal(bookingId);
                        break;
                    case 'upload':
                        this.showUploadModal(bookingId);
                        break;
                    case 'delete':
                        this.deleteBooking(bookingId);
                        break;
                }
            });
        }

        // Modal close handlers
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.closeAllModals();
            }
        });

        // Escape key handler
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    // Initialize filter handlers with debouncing
    initializeFilterHandlers() {
        // Search with debouncing
        if (this.elements.searchFilter) {
            this.elements.searchFilter.addEventListener('input', () => {
                this.debounce(() => this.applyFilters(), 300);
            });
        }

        // Status and date filters
        [this.elements.statusFilter, this.elements.dateFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', () => this.applyFilters());
            }
        });
    }

    // Initialize utility handlers
    initializeUtilityHandlers() {
        // Export functionality
        const exportBtn = document.getElementById('exportBookings');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportBookings());
        }

        // Clear filters
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => this.clearFilters());
        }

        // Manual refresh
        const refreshBtn = document.getElementById('refreshBookings');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadBookings());
        }
    }

    // Setup keyboard shortcuts
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                this.loadBookings();
            }
            
            // Ctrl/Cmd + E for export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                this.exportBookings();
            }
            
            // Ctrl/Cmd + F for search focus
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                this.elements.searchFilter?.focus();
            }
        });
    }

    // Optimized filtering with better performance
    applyFilters() {
        const statusFilter = this.elements.statusFilter?.value || '';
        const dateFilter = this.elements.dateFilter?.value || '';
        const searchFilter = this.elements.searchFilter?.value.toLowerCase() || '';

        this.filteredBookings = this.allBookings.filter(booking => {
            const matchStatus = !statusFilter || booking.status === statusFilter;
            const matchDate = !dateFilter || booking.sample_collection_date === dateFilter;
            const matchSearch = !searchFilter || this.matchesSearch(booking, searchFilter);

            return matchStatus && matchDate && matchSearch;
        });

        this.displayBookings(this.filteredBookings);
        this.updateStatistics(this.filteredBookings);
    }

    // Optimized search matching
    matchesSearch(booking, searchTerm) {
        const searchFields = [
            booking.customer_name,
            booking.phone,
            booking.booking_id,
            booking.email,
            booking.tests
        ];

        return searchFields.some(field => 
            field && field.toLowerCase().includes(searchTerm)
        );
    }

    // Update statistics with better calculation
    updateStatistics(bookings) {
        const today = new Date().toISOString().split('T')[0];
        
        const stats = bookings.reduce((acc, booking) => {
            acc.total++;
            if (booking.status === 'Pending') acc.pending++;
            if (booking.status === 'Completed') acc.completed++;
            if (booking.sample_collection_date === today) acc.today++;
            return acc;
        }, { total: 0, pending: 0, completed: 0, today: 0 });

        // Update DOM elements safely
        this.updateElement(this.elements.totalBookings, stats.total);
        this.updateElement(this.elements.pendingBookings, stats.pending);
        this.updateElement(this.elements.completedBookings, stats.completed);
        this.updateElement(this.elements.todayBookings, stats.today);
    }

    // Enhanced API methods with better error handling
    async updateBookingStatus(bookingId, status) {
        try {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('booking_id', bookingId);
            formData.append('status', status);

            const response = await this.fetchWithTimeout('lab-api.php', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Status updated successfully');
                this.closeStatusModal();
                await this.loadBookings();
            } else {
                this.showError(`Failed to update status: ${data.message}`);
            }
        } catch (error) {
            console.error('Error updating status:', error);
            this.showError('Network error while updating status');
        }
    }

    // Enhanced upload with progress tracking
    async uploadReport(formData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            this.showUploadProgress();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    this.updateUploadProgress(percentComplete);
                }
            });

            xhr.onload = () => {
                this.hideUploadProgress();

                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            this.showSuccess('Report uploaded successfully');
                            this.closeUploadModal();
                            this.loadBookings();
                            resolve(data);
                        } else {
                            this.showError(`Failed to upload report: ${data.message}`);
                            reject(new Error(data.message));
                        }
                    } catch (e) {
                        this.showError('Invalid response from server');
                        reject(e);
                    }
                } else {
                    this.showError('Network error while uploading report');
                    reject(new Error(`HTTP ${xhr.status}`));
                }
            };

            xhr.onerror = () => {
                this.hideUploadProgress();
                this.showError('Network error while uploading report');
                reject(new Error('Network error'));
            };

            formData.append('action', 'upload_report');
            xhr.open('POST', 'lab-api.php');
            xhr.send(formData);
        });
    }

    // Enhanced file validation
    handleFileSelect(file) {
        if (!file) return;

        const validationResult = this.validateFile(file);
        if (!validationResult.valid) {
            this.showError(validationResult.message);
            this.resetFileUpload();
            return;
        }

        this.showSelectedFile(file);
    }

    validateFile(file) {
        const allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        const maxSize = 10 * 1024 * 1024; // 10MB

        if (!allowedTypes.includes(file.type)) {
            return {
                valid: false,
                message: 'Invalid file type. Only PDF, JPG, PNG, DOC, DOCX allowed'
            };
        }

        if (file.size > maxSize) {
            return {
                valid: false,
                message: 'File size too large. Maximum 10MB allowed'
            };
        }

        return { valid: true };
    }

    // Enhanced toast notifications
    showToast(message, type = 'success', duration = 4000) {
        const toast = this.elements.toastNotification;
        if (!toast) {
            // Fallback to alert if toast element doesn't exist
            alert(`${type.toUpperCase()}: ${message}`);
            return;
        }

        const messageSpan = toast.querySelector('.toast-message');
        if (messageSpan) {
            messageSpan.textContent = message;
        }

        toast.className = `toast-notification toast-${type}`;
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, duration);
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    // Enhanced export functionality
    exportBookings() {
        if (this.filteredBookings.length === 0) {
            this.showError('No bookings to export');
            return;
        }

        try {
            const headers = [
                'Booking ID', 'Patient Name', 'Phone', 'Email', 
                'Collection Date', 'Time Slot', 'Tests', 'Amount', 'Status'
            ];

            const csvContent = [
                headers.join(','),
                ...this.filteredBookings.map(booking => [
                    this.escapeCsv(booking.booking_id),
                    this.escapeCsv(booking.customer_name),
                    this.escapeCsv(booking.phone),
                    this.escapeCsv(booking.email),
                    this.escapeCsv(booking.formatted_collection_date),
                    this.escapeCsv(booking.time_slot),
                    this.escapeCsv(booking.tests || 'N/A'),
                    booking.total_amount,
                    this.escapeCsv(booking.status)
                ].join(','))
            ].join('\n');

            this.downloadFile(csvContent, `lab-bookings-${new Date().toISOString().split('T')[0]}.csv`, 'text/csv');
            this.showSuccess('Bookings exported successfully');
        } catch (error) {
            console.error('Export error:', error);
            this.showError('Failed to export bookings');
        }
    }

    // Utility methods
    debounce(func, wait) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(func, wait);
    }

    async fetchWithTimeout(url, options, timeout = 10000) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    escapeCsv(text) {
        if (typeof text !== 'string') return text;
        return `"${text.replace(/"/g, '""')}"`;
    }

    truncateText(text, maxLength) {
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-IN').format(amount);
    }

    updateElement(element, value) {
        if (element) {
            element.textContent = value;
        }
    }

    downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    getStatusClass(status) {
        const statusMap = {
            'Pending': 'status-pending',
            'Confirmed': 'status-confirmed',
            'Sample Collected': 'status-collected',
            'In Progress': 'status-progress',
            'Upload Done': 'status-uploaded',
            'Completed': 'status-completed',
            'Cancelled': 'status-cancelled'
        };
        return statusMap[status] || 'status-pending';
    }

    getEmptyStateHTML() {
        return `
            <tr>
                <td colspan="9" class="no-data">
                    <div class="empty-state">
                        <i class="fa fa-flask"></i>
                        <h3>No bookings found</h3>
                        <p>No lab bookings match your current filters</p>
                    </div>
                </td>
            </tr>
        `;
    }

    // Modal methods
    async viewBookingDetails(bookingId) {
        try {
            const response = await this.fetchWithTimeout(
                `lab-api.php?action=get_booking_details&booking_id=${encodeURIComponent(bookingId)}`
            );
            const data = await response.json();

            if (data.success) {
                this.showBookingModal(data.data);
            } else {
                this.showError(`Failed to load booking details: ${data.message}`);
            }
        } catch (error) {
            console.error('Error loading booking details:', error);
            this.showError('Network error while loading booking details');
        }
    }

    showBookingModal(booking) {
        const modal = this.elements.bookingModal;
        const modalBody = this.elements.modalBody;

        if (!modal || !modalBody) return;

        modalBody.innerHTML = this.generateBookingModalContent(booking);
        modal.style.display = 'flex';
    }

    generateBookingModalContent(booking) {
        return `
            <div class="booking-detail-content">
                <div class="detail-section">
                    <h3><i class="fa fa-user"></i> Customer Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Name:</label>
                            <span>${this.escapeHtml(booking.customer_name)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span>${this.escapeHtml(booking.phone)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span>${this.escapeHtml(booking.email)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Address:</label>
                            <span>${this.escapeHtml(booking.address)}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3><i class="fa fa-calendar"></i> Booking Information</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Booking ID:</label>
                            <span>${this.escapeHtml(booking.booking_id)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Collection Date:</label>
                            <span>${this.escapeHtml(booking.formatted_collection_date)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Time Slot:</label>
                            <span>${this.escapeHtml(booking.time_slot)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span class="status-badge ${this.getStatusClass(booking.status)}">${this.escapeHtml(booking.status)}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3><i class="fa fa-flask"></i> Test Details</h3>
                    <div class="tests-list">
                        ${booking.tests && booking.tests.length > 0
                            ? booking.tests.map(test => `<div class="test-item">${this.escapeHtml(test)}</div>`).join('')
                            : '<div class="no-tests">No tests found</div>'
                        }
                    </div>
                    <div class="total-amount">
                        <strong>Total Amount: ₹${this.formatCurrency(booking.total_amount)}</strong>
                    </div>
                </div>
                
                ${booking.has_report ? `
                    <div class="detail-section">
                        <h3><i class="fa fa-file-alt"></i> Report Information</h3>
                        <div class="report-info">
                            <div class="report-item">
                                <label>Report File:</label>
                                <span>${this.escapeHtml(booking.report_file)}</span>
                            </div>
                            <div class="report-item">
                                <label>Uploaded At:</label>
                                <span>${this.escapeHtml(booking.formatted_report_date)}</span>
                            </div>
                            <div class="report-actions">
                                <a href="uploads/reports/${encodeURIComponent(booking.report_file)}" target="_blank" class="btn btn-primary">
                                    <i class="fa fa-download"></i> Download Report
                                </a>
                            </div>
                        </div>
                    </div>
                ` : ''}
                
                <div class="detail-section">
                    <h3><i class="fa fa-info-circle"></i> Booking Timeline</h3>
                    <div class="timeline-info">
                        <div class="timeline-item">
                            <label>Created:</label>
                            <span>${this.escapeHtml(booking.formatted_created_date)}</span>
                        </div>
                        <div class="timeline-item">
                            <label>Last Updated:</label>
                            <span>${this.escapeHtml(booking.formatted_updated_date)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    showStatusModal(bookingId) {
        const booking = this.allBookings.find(b => b.booking_id === bookingId);
        if (!booking) return;

        const modal = this.elements.statusUpdateModal;
        const bookingInfo = this.elements.statusBookingInfo;
        const statusSelect = this.elements.statusSelect;
        const hiddenBookingId = this.elements.statusBookingId;

        if (!modal || !bookingInfo || !statusSelect || !hiddenBookingId) return;

        // Set booking ID
        hiddenBookingId.value = bookingId;

        // Set current status
        statusSelect.value = booking.status;

        // Update booking info
        bookingInfo.innerHTML = `
            <div class="booking-info-item">
                <strong>Booking ID:</strong> ${this.escapeHtml(bookingId)}
            </div>
            <div class="booking-info-item">
                <strong>Current Status:</strong> 
                <span class="status-badge ${this.getStatusClass(booking.status)}">${this.escapeHtml(booking.status)}</span>
            </div>
        `;

        modal.style.display = 'flex';
    }

    showUploadModal(bookingId) {
        const modal = this.elements.uploadModal;
        const hiddenBookingId = this.elements.uploadBookingId;
        const bookingInfo = this.elements.uploadBookingInfo;

        if (!modal || !hiddenBookingId || !bookingInfo) return;

        // Set booking ID
        hiddenBookingId.value = bookingId;

        // Update booking info
        bookingInfo.innerHTML = `
            <div class="upload-booking-item">
                <strong>Booking ID:</strong> ${this.escapeHtml(bookingId)}
            </div>
            <div class="upload-booking-item">
                <strong>Upload Report:</strong> Select a report file to upload for this booking
            </div>
        `;

        // Reset form
        if (this.elements.uploadReportForm) {
            this.elements.uploadReportForm.reset();
        }
        this.resetFileUpload();

        modal.style.display = 'flex';
    }

    async deleteBooking(bookingId) {
        const confirmed = await this.showConfirmDialog(
            'Delete Booking',
            'Are you sure you want to delete this booking? This action cannot be undone.',
            'Delete',
            'Cancel'
        );

        if (!confirmed) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete_booking');
            formData.append('booking_id', bookingId);

            const response = await this.fetchWithTimeout('lab-api.php', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Booking deleted successfully');
                await this.loadBookings();
            } else {
                this.showError(`Failed to delete booking: ${data.message}`);
            }
        } catch (error) {
            console.error('Error deleting booking:', error);
            this.showError('Network error while deleting booking');
        }
    }

    // Enhanced confirmation dialog
    showConfirmDialog(title, message, confirmText = 'OK', cancelText = 'Cancel') {
        return new Promise((resolve) => {
            // Create modal if it doesn't exist
            let confirmModal = document.getElementById('confirmModal');
            if (!confirmModal) {
                confirmModal = this.createConfirmModal();
                document.body.appendChild(confirmModal);
            }

            const titleEl = confirmModal.querySelector('.confirm-title');
            const messageEl = confirmModal.querySelector('.confirm-message');
            const confirmBtn = confirmModal.querySelector('.confirm-btn');
            const cancelBtn = confirmModal.querySelector('.cancel-btn');

            titleEl.textContent = title;
            messageEl.textContent = message;
            confirmBtn.textContent = confirmText;
            cancelBtn.textContent = cancelText;

            const cleanup = () => {
                confirmModal.style.display = 'none';
                confirmBtn.removeEventListener('click', confirmHandler);
                cancelBtn.removeEventListener('click', cancelHandler);
            };

            const confirmHandler = () => {
                cleanup();
                resolve(true);
            };

            const cancelHandler = () => {
                cleanup();
                resolve(false);
            };

            confirmBtn.addEventListener('click', confirmHandler);
            cancelBtn.addEventListener('click', cancelHandler);

            confirmModal.style.display = 'flex';
        });
    }

    createConfirmModal() {
        const modal = document.createElement('div');
        modal.id = 'confirmModal';
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content confirm-modal">
                <div class="modal-header">
                    <h3 class="confirm-title"></h3>
                </div>
                <div class="modal-body">
                    <p class="confirm-message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
                    <button type="button" class="btn btn-danger confirm-btn">Delete</button>
                </div>
            </div>
        `;
        return modal;
    }

    // File upload methods
    showSelectedFile(file) {
        const placeholder = document.querySelector('.file-upload-placeholder');
        const selected = document.querySelector('.file-selected');
        const fileName = document.querySelector('.file-name');

        if (placeholder) placeholder.style.display = 'none';
        if (selected) {
            selected.style.display = 'flex';
            if (fileName) fileName.textContent = file.name;
        }
    }

    resetFileUpload() {
        if (this.elements.reportFile) {
            this.elements.reportFile.value = '';
        }

        const placeholder = document.querySelector('.file-upload-placeholder');
        const selected = document.querySelector('.file-selected');

        if (placeholder) placeholder.style.display = 'block';
        if (selected) selected.style.display = 'none';
    }

    removeSelectedFile() {
        this.resetFileUpload();
    }

    // Upload progress methods
    showUploadProgress() {
        const uploadProgress = this.elements.uploadProgress;
        const submitBtn = document.querySelector('.upload-btn-submit');

        if (uploadProgress) uploadProgress.style.display = 'block';
        if (submitBtn) submitBtn.disabled = true;
    }

    hideUploadProgress() {
        const uploadProgress = this.elements.uploadProgress;
        const submitBtn = document.querySelector('.upload-btn-submit');

        if (uploadProgress) uploadProgress.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
    }

    updateUploadProgress(percent) {
        const progressFill = this.elements.progressFill;
        const progressText = this.elements.progressText;

        if (progressFill) progressFill.style.width = `${percent}%`;
        if (progressText) progressText.textContent = `Uploading... ${Math.round(percent)}%`;
    }

    // Modal control methods
    closeAllModals() {
        this.closeModal();
        this.closeStatusModal();
        this.closeUploadModal();
    }

    closeModal() {
        if (this.elements.bookingModal) {
            this.elements.bookingModal.style.display = 'none';
        }
    }

    closeStatusModal() {
        if (this.elements.statusUpdateModal) {
            this.elements.statusUpdateModal.style.display = 'none';
        }
    }

    closeUploadModal() {
        if (this.elements.uploadModal) {
            this.elements.uploadModal.style.display = 'none';
        }
    }

    // Filter control methods
    clearFilters() {
        if (this.elements.statusFilter) this.elements.statusFilter.value = '';
        if (this.elements.dateFilter) this.elements.dateFilter.value = '';
        if (this.elements.searchFilter) this.elements.searchFilter.value = '';

        this.filteredBookings = [...this.allBookings];
        this.displayBookings(this.filteredBookings);
        this.updateStatistics(this.filteredBookings);
    }

    // Refresh indicator methods
    showRefreshIndicator() {
        if (this.elements.refreshIndicator) {
            this.elements.refreshIndicator.classList.add('show');
        }
    }

    hideRefreshIndicator() {
        if (this.elements.refreshIndicator) {
            this.elements.refreshIndicator.classList.remove('show');
        }
    }

    // Cleanup method for proper resource management
    destroy() {
        // Clear intervals
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }

        // Clear timeouts
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Abort any pending requests
        if (this.abortController) {
            this.abortController.abort();
        }

        // Remove event listeners
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        document.removeEventListener('keydown', this.handleKeydown);
        document.removeEventListener('click', this.handleDocumentClick);

        // Clear cached elements
        this.elements = {};
        this.allBookings = [];
        this.filteredBookings = [];
    }
}

// Global functions for backward compatibility (if needed)
window.labBookingsManager = null;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    window.labBookingsManager = new LabBookingsManager();
});

// Expose global functions for backward compatibility
window.viewBookingDetails = function(bookingId) {
    if (window.labBookingsManager) {
        window.labBookingsManager.viewBookingDetails(bookingId);
    }
};

window.showStatusModal = function(bookingId, currentStatus) {
    if (window.labBookingsManager) {
        window.labBookingsManager.showStatusModal(bookingId);
    }
};

window.showUploadModal = function(bookingId) {
    if (window.labBookingsManager) {
        window.labBookingsManager.showUploadModal(bookingId);
    }
};

window.deleteBooking = function(bookingId) {
    if (window.labBookingsManager) {
        window.labBookingsManager.deleteBooking(bookingId);
    }
};

window.removeSelectedFile = function() {
    if (window.labBookingsManager) {
        window.labBookingsManager.removeSelectedFile();
    }
};

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.labBookingsManager) {
        window.labBookingsManager.destroy();
    }
});