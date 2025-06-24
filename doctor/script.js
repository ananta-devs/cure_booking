// Doctor Profile JavaScript
// Global variables
let selectedFile = null;
let availabilityData = [];

// Utility Functions
function showAlert(message, type = 'info', elementId = null) {
    const alertElement = elementId ? document.getElementById(elementId) : null;
    
    if (alertElement) {
        alertElement.className = `alert alert-${type}`;
        alertElement.innerHTML = `<i class="fas fa-${getAlertIcon(type)}"></i> ${message}`;
        alertElement.style.display = 'block';
        
        if (type === 'success') {
            setTimeout(() => {
                alertElement.style.display = 'none';
            }, 5000);
        }
    } else {
        // Fallback to console if no element specified
        console.log(`${type.toUpperCase()}: ${message}`);
    }
}

function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function formatDate(date) {
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    return date.toLocaleDateString('en-US', options);
}

function formatTime(time) {
    if (!time) return 'Not specified';
    
    // Handle 24-hour format (HH:MM or HH:MM:SS)
    const timeParts = time.split(':');
    if (timeParts.length >= 2) {
        const hours = parseInt(timeParts[0]);
        const minutes = timeParts[1];
        const period = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours === 0 ? 12 : (hours > 12 ? hours - 12 : hours);
        return `${displayHours}:${minutes} ${period}`;
    }
    
    return time;
}

function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Request failed:', error);
            throw error;
        });
}

// Image Upload Functions
function toggleImageUpload() {
    const uploadArea = document.getElementById('imageUploadArea');
    if (uploadArea.style.display === 'block') {
        cancelImageUpload();
    } else {
        uploadArea.style.display = 'block';
        document.getElementById('doctorImageInput').click();
    }
}

function handleImageSelect(input) {
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadArea = document.getElementById('imageUploadArea');
    
    if (input.files && input.files[0]) {
        selectedFile = input.files[0];
        
        // Validate file
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!validTypes.includes(selectedFile.type)) {
            showAlert('Please select a valid image file (JPG, PNG, or GIF)', 'error');
            cancelImageUpload();
            return;
        }
        
        if (selectedFile.size > maxSize) {
            showAlert('File size too large. Maximum size is 5MB', 'error');
            cancelImageUpload();
            return;
        }
        
        uploadBtn.disabled = false;
        uploadArea.style.display = 'block';
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const currentImage = document.getElementById('doctor-image');
            const currentIcon = document.getElementById('doctor-icon');
            
            if (currentImage) {
                currentImage.src = e.target.result;
            } else if (currentIcon) {
                currentIcon.style.display = 'none';
                const imgElement = document.createElement('img');
                imgElement.src = e.target.result;
                imgElement.alt = 'Doctor Image Preview';
                imgElement.id = 'doctor-image';
                currentIcon.parentNode.insertBefore(imgElement, currentIcon);
            }
        };
        reader.readAsDataURL(selectedFile);
    }
}

function uploadImage() {
    if (!selectedFile) {
        showAlert('Please select an image first', 'error');
        return;
    }
    
    const uploadBtn = document.getElementById('uploadBtn');
    const progressArea = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    progressArea.style.display = 'block';
    
    const formData = new FormData();
    formData.append('doctor_image', selectedFile);
    formData.append('action', 'upload_image');
    
    // Simulate upload progress
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 90) progress = 90;
        progressFill.style.width = progress + '%';
    }, 200);
    
    fetch('api_handler.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(progressInterval);
        progressFill.style.width = '100%';
        
        setTimeout(() => {
            if (data.success) {
                showAlert(data.message, 'success');
                cancelImageUpload();
                
                // Update image display
                const currentImage = document.getElementById('doctor-image');
                const currentIcon = document.getElementById('doctor-icon');
                
                if (data.image_path) {
                    if (currentImage) {
                        currentImage.src = data.image_path;
                    } else if (currentIcon) {
                        currentIcon.style.display = 'none';
                        const imgElement = document.createElement('img');
                        imgElement.src = data.image_path;
                        imgElement.alt = 'Doctor Image';
                        imgElement.id = 'doctor-image';
                        currentIcon.parentNode.insertBefore(imgElement, currentIcon);
                    }
                }
            } else {
                showAlert(data.message || 'Upload failed', 'error');
            }
        }, 500);
    })
    .catch(error => {
        clearInterval(progressInterval);
        console.error('Upload error:', error);
        showAlert('Upload failed. Please try again.', 'error');
    })
    .finally(() => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload';
        setTimeout(() => {
            progressArea.style.display = 'none';
            progressFill.style.width = '0%';
        }, 1000);
    });
}

function cancelImageUpload() {
    const uploadArea = document.getElementById('imageUploadArea');
    const uploadBtn = document.getElementById('uploadBtn');
    const fileInput = document.getElementById('doctorImageInput');
    
    uploadArea.style.display = 'none';
    uploadBtn.disabled = true;
    fileInput.value = '';
    selectedFile = null;
    
    // Reset progress
    document.getElementById('uploadProgress').style.display = 'none';
    document.getElementById('progressFill').style.width = '0%';
}

// Password Change Functions
function openPasswordModal() {
    document.getElementById('passwordModal').style.display = 'flex';
    document.getElementById('currentPassword').focus();
    
    // Clear previous alerts and form
    const alert = document.getElementById('passwordAlert');
    if (alert) alert.style.display = 'none';
    document.getElementById('passwordForm').reset();
    
    // Reset password strength indicator
    updatePasswordStrength();
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
    document.getElementById('passwordForm').reset();
    
    // Clear alerts
    const alert = document.getElementById('passwordAlert');
    if (alert) alert.style.display = 'none';
}

function updatePasswordStrength() {
    const password = document.getElementById('newPassword').value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    if (!password) {
        strengthBar.style.width = '0%';
        strengthBar.className = 'strength-bar';
        strengthText.textContent = '';
        return;
    }
    
    let strength = 0;
    let strengthLabel = '';
    let strengthClass = '';
    
    // Length check
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 10;
    
    // Character variety checks
    if (/[a-z]/.test(password)) strength += 15;
    if (/[A-Z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^A-Za-z0-9]/.test(password)) strength += 20;
    
    // Determine strength level
    if (strength < 30) {
        strengthLabel = 'Weak';
        strengthClass = 'weak';
    } else if (strength < 60) {
        strengthLabel = 'Fair';
        strengthClass = 'fair';
    } else if (strength < 80) {
        strengthLabel = 'Good';
        strengthClass = 'good';
    } else {
        strengthLabel = 'Strong';
        strengthClass = 'strong';
    }
    
    strengthBar.style.width = strength + '%';
    strengthBar.className = `strength-bar ${strengthClass}`;
    strengthText.textContent = strengthLabel;
}

function handlePasswordChange(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'change_password');
    
    const submitBtn = document.getElementById('changePasswordBtn');
    const originalText = submitBtn.innerHTML;
    
    // Validate passwords match
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    
    if (newPassword !== confirmPassword) {
        showAlert('New passwords do not match', 'error', 'passwordAlert');
        return;
    }
    
    if (newPassword.length < 6) {
        showAlert('New password must be at least 6 characters long', 'error', 'passwordAlert');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
    
    fetch('api_handler.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success', 'passwordAlert');
            setTimeout(() => {
                closePasswordModal();
            }, 2000);
        } else {
            showAlert(data.message || 'Password change failed', 'error', 'passwordAlert');
        }
    })
    .catch(error => {
        console.error('Password change error:', error);
        showAlert('An error occurred. Please try again.', 'error', 'passwordAlert');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Booking Statistics Functions
function loadBookingStats() {
    const statElements = {
        todayBookings: document.getElementById('todayBookings'),
        tomorrowBookings: document.getElementById('tomorrowBookings'),
        upcomingToday: document.getElementById('upcomingToday')
    };
    
    // Show loading state
    Object.values(statElements).forEach(el => {
        if (el) el.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    });
    
    makeRequest('api_handler.php?action=get_booking_stats')
        .then(data => {
            if (data.success) {
                if (statElements.todayBookings) {
                    statElements.todayBookings.textContent = data.today_bookings || 0;
                }
                if (statElements.tomorrowBookings) {
                    statElements.tomorrowBookings.textContent = data.tomorrow_bookings || 0;
                }
                if (statElements.upcomingToday) {
                    statElements.upcomingToday.textContent = data.upcoming_today || 0;
                }
            } else {
                Object.values(statElements).forEach(el => {
                    if (el) el.textContent = 'Error';
                });
                showAlert('Failed to load booking statistics', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading booking stats:', error);
            Object.values(statElements).forEach(el => {
                if (el) el.textContent = 'Error';
            });
        });
}

// Availability Functions
function refreshAvailability() {
    const refreshIcon = document.getElementById('refreshIcon');
    const loadingElement = document.getElementById('availabilityLoading');
    const gridElement = document.getElementById('availabilityGrid');
    
    refreshIcon.classList.add('fa-spin');
    loadingElement.style.display = 'block';
    gridElement.style.display = 'none';
    
    // Simulate API call for availability data
    setTimeout(() => {
        loadAvailabilitySchedule();
        refreshIcon.classList.remove('fa-spin');
    }, 1000);
}

function loadAvailabilitySchedule() {
    const loadingElement = document.getElementById('availabilityLoading');
    const gridElement = document.getElementById('availabilityGrid');
    
    // Show loading
    loadingElement.style.display = 'block';
    gridElement.style.display = 'none';
    
    // Since the availability data is already loaded from PHP, we'll use it
    // In a real scenario, you might fetch this via AJAX
    setTimeout(() => {
        renderAvailabilityGrid();
        loadingElement.style.display = 'none';
        gridElement.style.display = 'block';
    }, 500);
}


// Appointment Management Functions
function updateAppointmentStatus(appointmentId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_appointment_status');
    formData.append('appointment_id', appointmentId);
    formData.append('status', newStatus);
    
    return fetch('api_handler.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Refresh booking stats after status update
            loadBookingStats();
        } else {
            showAlert(data.message || 'Failed to update appointment status', 'error');
        }
        return data;
    })
    .catch(error => {
        console.error('Error updating appointment status:', error);
        showAlert('An error occurred while updating appointment status', 'error');
        throw error;
    });
}

// Date and Time Functions
function updateCurrentDate() {
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        const now = new Date();
        dateElement.textContent = formatDate(now);
    }
}

// Modal Functions
function handleModalOutsideClick(event) {
    const modal = document.getElementById('passwordModal');
    if (event.target === modal) {
        closePasswordModal();
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Update current date
    updateCurrentDate();
    
    // Load initial data
    loadBookingStats();
    loadAvailabilitySchedule();
    
    // Password form event listener
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', handlePasswordChange);
    }
    
    // Password strength checker
    const newPasswordInput = document.getElementById('newPassword');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', updatePasswordStrength);
    }
    
    // Modal outside click handler
    document.addEventListener('click', handleModalOutsideClick);
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('passwordModal');
            if (modal && modal.style.display === 'flex') {
                closePasswordModal();
            }
        }
    });
    
    // Auto-refresh booking stats every 5 minutes
    setInterval(loadBookingStats, 5 * 60 * 1000);
    
    console.log('Doctor profile page initialized successfully');
});

// Global error handler
window.addEventListener('error', function(event) {
    console.error('Global error:', event.error);
});

// Handle session expiration
function handleSessionExpired(data) {
    if (data.redirect) {
        showAlert('Session expired. Redirecting to login...', 'warning');
        setTimeout(() => {
            window.location.href = data.redirect;
        }, 2000);
    }
}

// Utility function to handle API responses with potential redirects
function handleApiResponse(data) {
    if (data.redirect && !data.success) {
        handleSessionExpired(data);
        return false;
    }
    return true;
}