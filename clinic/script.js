// Global variables
let currentDoctorId = null;
let currentSpecialty = null;
let isEditMode = false;
let currentEditAppointmentId = null;

// DOM Content Loaded Event
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    initializeDateRestrictions();
    showAppointmentsSection();
});

// Initialize all event listeners
function initializeEventListeners() {
    // Navigation buttons
    document.getElementById('viewAppointmentsBtn')?.addEventListener('click', showAppointmentsSection);
    document.getElementById('allDoctorsBtn')?.addEventListener('click', showDoctorsSection);

    // Form elements
    document.getElementById('appointmentForm')?.addEventListener('submit', handleAppointmentSubmit);
    document.getElementById('specialityType')?.addEventListener('change', () => handleSpecialtyChange());
    document.getElementById('doctor')?.addEventListener('change', () => handleDoctorChange());
    document.getElementById('preferredDate')?.addEventListener('change', () => handleDateChange());
    document.getElementById('cancelAppointmentBtn')?.addEventListener('click', cancelAppointmentForm);
    document.getElementById('filterBtn')?.addEventListener('click', filterAppointments);

    // Update modal listeners
    document.getElementById('updateSpecialityType')?.addEventListener('change', () => handleSpecialtyChange(true));
    document.getElementById('updateDoctor')?.addEventListener('change', () => handleDoctorChange(true));
    document.getElementById('updatePreferredDate')?.addEventListener('change', () => handleDateChange(true));

    // Modal close events
    document.addEventListener('click', handleModalClicks);
    document.addEventListener('keydown', (e) => e.key === 'Escape' && closeDoctorModal());

    // Form validation
    addFormValidation();
}

// Handle modal clicks
function handleModalClicks(event) {
    const doctorModal = document.getElementById('doctorModal');
    const updateModal = document.getElementById('updateAppointmentModal');
    
    if (doctorModal && event.target === doctorModal) closeDoctorModal();
    if (updateModal && event.target === updateModal) closeUpdateModal();
}

// Initialize date restrictions
function initializeDateRestrictions() {
    const dateInput = document.getElementById('preferredDate');
    if (dateInput) {
        dateInput.setAttribute('min', new Date().toISOString().split('T')[0]);
    }
}

// Navigation functions
function showSection(activeSection, inactiveSection, activeBtn, inactiveBtn) {
    document.getElementById(activeSection).style.display = 'block';
    document.getElementById(inactiveSection).style.display = 'none';
    document.getElementById(activeBtn).classList.add('active');
    document.getElementById(inactiveBtn).classList.remove('active');
    hideAppointmentForm();
}

function showAppointmentsSection() {
    showSection('appointmentsSection', 'doctorsSection', 'viewAppointmentsBtn', 'allDoctorsBtn');
}

function showDoctorsSection() {
    showSection('doctorsSection', 'appointmentsSection', 'allDoctorsBtn', 'viewAppointmentsBtn');
}

// Show/Hide appointment form
function showAppointmentForm() {
    const form = document.getElementById('appointmentForm');
    const doctorsGrid = document.getElementById('doctorsGrid');
    
    if (!form) return;
    
    form.style.display = 'block';
    if (doctorsGrid) doctorsGrid.style.display = 'none';
    form.scrollIntoView({ behavior: 'smooth' });
}

function hideAppointmentForm() {
    const form = document.getElementById('appointmentForm');
    const doctorsGrid = document.getElementById('doctorsGrid');
    
    if (form) {
        form.style.display = 'none';
        resetForm();
    }
    if (doctorsGrid) doctorsGrid.style.display = 'grid';
}

// Appointment functions
function viewAppointment(appointmentId) {
    const row = Array.from(document.querySelectorAll('#appointmentsTableBody tr'))
        .find(row => row.cells[0].textContent == appointmentId);
    
    if (row) {
        const cells = row.cells;
        const appointmentData = {
            id: cells[0].textContent,
            patientName: cells[1].textContent,
            doctor: cells[2].textContent.split('\n')[0],
            date: cells[3].textContent,
            time: cells[4].textContent,
            status: cells[5].textContent.trim()
        };
        showAppointmentDetails(appointmentData);
    }
}

function editAppointment(appointmentId) {
    currentEditAppointmentId = appointmentId;
    isEditMode = true;
    
    fetchAppointmentDetails(appointmentId).then(appointmentData => {
        if (appointmentData) {
            const modalData = {
                id: appointmentData.id,
                patientName: appointmentData.patient_name,
                doctor: appointmentData.doctor_name || appointmentData.doc_name,
                specialty: appointmentData.doctor_specialization || appointmentData.doc_specia,
                date: formatDateForDisplay(appointmentData.appointment_date),
                time: formatTimeForDisplay(appointmentData.appointment_time),
                status: appointmentData.status,
                phone: appointmentData.patient_phone,
                email: appointmentData.patient_email,
                gender: appointmentData.gender
            };
            
            populateUpdateModal(modalData);
            showUpdateModal();
        } else {
            showMessage('Error loading appointment details', 'error');
        }
    });
}

function populateUpdateModal(appointmentData) {
    // Set all basic fields
    document.getElementById('updateAppointmentId').value = appointmentData.id;
    document.getElementById('updateFirstName').value = appointmentData.patientName;
    document.getElementById('updatePhone').value = appointmentData.phone || '';
    document.getElementById('updateEmail').value = appointmentData.email || '';
    document.getElementById('updateStatus').value = appointmentData.status.toLowerCase();
    
    // Set gender radio buttons
    document.querySelectorAll('input[name="updateGender"]').forEach(radio => {
        radio.checked = radio.value === appointmentData.gender;
    });
    
    // Convert and set date
    const formattedDate = appointmentData.date.includes('/') 
        ? appointmentData.date.split('/').reverse().join('-')
        : appointmentData.date;
    document.getElementById('updatePreferredDate').value = formattedDate;
    
    // Set specialty and chain async operations
    document.getElementById('updateSpecialityType').value = appointmentData.specialty;
    
    handleSpecialtyChange(true)
        .then(() => new Promise(resolve => {
            setTimeout(() => {
                const doctorSelect = document.getElementById('updateDoctor');
                const doctorOption = Array.from(doctorSelect.options)
                    .find(option => option.textContent.includes(appointmentData.doctor));
                
                if (doctorOption) {
                    doctorSelect.value = doctorOption.value;
                    handleDoctorChange(true);
                    resolve(doctorOption.value);
                } else {
                    resolve(null);
                }
            }, 200);
        }))
        .then(doctorId => {
            if (doctorId && formattedDate) {
                return loadTimeSlots(doctorId, formattedDate, 'update', currentEditAppointmentId);
            }
        })
        .then(() => new Promise(resolve => {
            setTimeout(() => {
                const timeSelect = document.getElementById('updateTime');
                const timeOption = Array.from(timeSelect.options)
                    .find(option => 
                        option.textContent.includes(appointmentData.time) || 
                        option.value === appointmentData.time ||
                        formatTimeSlot(option.value) === appointmentData.time
                    );
                
                if (timeOption) timeSelect.value = timeOption.value;
                resolve();
            }, 200);
        }))
        .catch(error => {
            console.error('Error loading update modal data:', error);
            showMessage('Error loading appointment details', 'error');
        });
}

// Helper functions for date/time formatting
function formatDateForDisplay(dateStr) {
    if (dateStr.includes('-')) {
        const parts = dateStr.split('-');
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}

function formatTimeForDisplay(timeStr) {
    if (timeStr.includes(':')) {
        const parts = timeStr.split(':');
        const hour = parseInt(parts[0]);
        const minute = parts[1];
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
        return `${displayHour}:${minute} ${ampm}`;
    }
    return timeStr;
}

function fetchAppointmentDetails(appointmentId) {
    const formData = new FormData();
    formData.append('action', 'get_appointment_details');
    formData.append('appointment_id', appointmentId);
    
    return fetch('api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => data.success ? data.appointment : null)
        .catch(() => null);
}

// Unified specialty change handler
// function handleSpecialtyChange(isUpdate = false) {
//     const prefix = isUpdate ? 'update' : '';
//     const specialty = document.getElementById(`${prefix}SpecialityType`).value;
//     const doctorSelect = document.getElementById(`${prefix}Doctor`);
//     const timeSelect = document.getElementById(`${prefix}Time`);
    
//     doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
//     timeSelect.innerHTML = '<option value="">Select Time</option>';
    
//     if (!specialty) return Promise.resolve();
    
//     const formData = new FormData();
//     formData.append('action', 'get_doctors_by_specialty');
//     formData.append('specialty', specialty);
    
//     return fetch('api.php', { method: 'POST', body: formData })
//         .then(response => response.json())
//         .then(doctors => {
//             doctors.forEach(doctor => {
//                 const option = document.createElement('option');
//                 option.value = doctor.doc_id;
//                 option.textContent = `${doctor.doc_name} - ${doctor.experience}y exp`;
//                 doctorSelect.appendChild(option);
//             });
//         })
//         .catch(error => {
//             console.error('Error loading doctors:', error);
//             showMessage('Error loading doctors', 'error');
//         });
// }

function handleSpecialtyChange(isUpdate = false) {
    const prefix = isUpdate ? 'update' : '';
    const specialty = document.getElementById(`${prefix}SpecialityType`).value;
    const doctorSelect = document.getElementById(`${prefix}Doctor`);
    const timeSelect = document.getElementById(`${prefix}Time`);
    
    doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
    timeSelect.innerHTML = '<option value="">Select Time</option>';
    
    if (!specialty) return Promise.resolve();
    
    const formData = new FormData();
    formData.append('action', 'get_doctors_by_specialty');
    formData.append('specialty', specialty);
    
    return fetch('api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(doctors => {
            // Clear loading state
            doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
            
            // Add doctors to select
            doctors.forEach(doctor => {
                const option = document.createElement('option');
                option.value = doctor.doc_id;
                option.textContent = `${doctor.doc_name} - ${doctor.experience}y exp`;
                doctorSelect.appendChild(option);
            });
            
            return doctors; // Return doctors for chaining
        })
        .catch(error => {
            console.error('Error loading doctors:', error);
            doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
            showMessage('Error loading doctors', 'error');
            throw error; // Re-throw to handle in calling function
        });
}


// Unified doctor change handler
function handleDoctorChange(isUpdate = false) {
    const prefix = isUpdate ? 'update' : '';
    const doctorId = document.getElementById(`${prefix}Doctor`).value;
    const date = document.getElementById(`${prefix}PreferredDate`).value;
    const doctorInfo = document.getElementById(`${prefix}DoctorInfo`);
    
    if (doctorInfo) {
        const selectedOption = document.querySelector(`#${prefix}Doctor option[value="${doctorId}"]`);
        doctorInfo.innerHTML = selectedOption ? `<small>Selected: ${selectedOption.textContent}</small>` : '';
    }
    
    if (doctorId && date) {
        const excludeId = isUpdate ? currentEditAppointmentId : null;
        loadTimeSlots(doctorId, date, prefix, excludeId);
    } else {
        document.getElementById(`${prefix}Time`).innerHTML = '<option value="">Select Time</option>';
    }
}

// Unified date change handler
function handleDateChange(isUpdate = false) {
    const prefix = isUpdate ? 'update' : '';
    const doctorId = document.getElementById(`${prefix}Doctor`).value;
    const date = document.getElementById(`${prefix}PreferredDate`).value;
    
    if (doctorId && date) {
        const excludeId = isUpdate ? currentEditAppointmentId : null;
        loadTimeSlots(doctorId, date, prefix, excludeId);
    }
}

// Time slots loading
function loadTimeSlots(doctorId, date, prefix = '', excludeAppointmentId = null) {
    const timeSelect = document.getElementById(`${prefix}Time`);
    const timeLoading = document.getElementById(`${prefix}TimeLoading`);
    
    if (timeLoading) timeLoading.style.display = 'block';
    timeSelect.innerHTML = '<option value="">Loading...</option>';
    
    const formData = new FormData();
    formData.append('action', 'get_time_slots');
    formData.append('doctor_id', doctorId);
    formData.append('appointment_date', date);
    if (excludeAppointmentId) formData.append('exclude_appointment_id', excludeAppointmentId);
    
    return fetch('api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (timeLoading) timeLoading.style.display = 'none';
            
            timeSelect.innerHTML = '<option value="">Select Time</option>';
            
            if (data.success && data.time_slots?.length) {
                data.time_slots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot;
                    option.textContent = formatTimeSlot(slot);
                    timeSelect.appendChild(option);
                });
            } else {
                timeSelect.innerHTML = '<option value="">No slots available</option>';
                if (!data.success) showMessage(data.message || 'Error loading time slots', 'error');
            }
        })
        .catch(error => {
            if (timeLoading) timeLoading.style.display = 'none';
            console.error('Error:', error);
            timeSelect.innerHTML = '<option value="">Error loading slots</option>';
            showMessage('Error loading time slots', 'error');
        });
}

function formatTimeSlot(timeSlot) {
    const [hours, minutes] = timeSlot.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
    return `${displayHour}:${minutes} ${ampm}`;
}

// Form submission handler
function handleAppointmentSubmit(event) {
    event.preventDefault();
    
    if (!validateForm()) return;
    
    const formData = new FormData();
    formData.append('action', isEditMode ? 'update_appointment' : 'book_appointment');
    formData.append('patient_name', document.getElementById('firstName').value);
    formData.append('patient_phone', document.getElementById('phone').value);
    formData.append('patient_email', document.getElementById('email').value);
    formData.append('doctor_id', document.getElementById('doctor').value);
    formData.append('appointment_date', document.getElementById('preferredDate').value);
    formData.append('appointment_time', document.getElementById('time').value);
    formData.append('gender', document.querySelector('input[name="gender"]:checked').value);
    
    if (isEditMode && currentEditAppointmentId) {
        formData.append('id', currentEditAppointmentId);
    }
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = isEditMode ? 'Updating...' : 'Booking...';
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                resetForm();
                hideAppointmentForm();
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred while processing the appointment', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
}

function submitUpdateAppointment() {
    if (!validateUpdateForm()) return;
    
    const formData = new FormData();
    formData.append('action', 'update_appointment');
    formData.append('id', document.getElementById('updateAppointmentId').value);
    formData.append('patient_name', document.getElementById('updateFirstName').value);
    formData.append('patient_phone', document.getElementById('updatePhone').value);
    formData.append('patient_email', document.getElementById('updateEmail').value);
    formData.append('doctor_id', document.getElementById('updateDoctor').value);
    formData.append('appointment_date', document.getElementById('updatePreferredDate').value);
    formData.append('appointment_time', document.getElementById('updateTime').value);
    formData.append('gender', document.querySelector('input[name="updateGender"]:checked').value);
    formData.append('status', document.getElementById('updateStatus').value);
    
    const submitBtn = document.querySelector('.btn-primary');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                closeUpdateModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred while updating the appointment', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
}

function deleteAppointment(appointmentId) {
    if (!confirm('Are you sure you want to delete this appointment?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_appointment');
    formData.append('id', appointmentId);
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            showMessage(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1500);
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred while deleting the appointment', 'error');
        });
}

// Doctor modal functions
function showDoctorModal(doctorData) {
    const modal = document.getElementById('doctorModal');
    const modalBody = document.getElementById('doctorModalBody');
    
    if (!modal || !modalBody) return;
    
    currentDoctorId = doctorData.doc_id;
    currentSpecialty = doctorData.doc_specia;
    
    modalBody.innerHTML = `
        <div class="doctor-details">
            <div class="doctor-header">
                <img src="${doctorData.doc_img ? 'uploads/doctors/' + doctorData.doc_img : 'https://via.placeholder.com/120'}" 
                     alt="${doctorData.doc_name}" class="modal-doctor-image">
                <div class="doctor-basic-info">
                    <h4>${doctorData.doc_name}</h4>
                    <p class="specialty">${doctorData.doc_specia}</p>
                    <p class="experience">${doctorData.experience} years experience</p>
                </div>
            </div>
            <div class="doctor-info-grid">
                <div class="info-item"><strong>Email:</strong><span>${doctorData.doc_email}</span></div>
                <div class="info-item"><strong>Location:</strong><span>${doctorData.location}</span></div>
                <div class="info-item"><strong>Education:</strong><span>${doctorData.education}</span></div>
                <div class="info-item"><strong>Specialization:</strong><span>${doctorData.doc_specia}</span></div>
            </div>
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeDoctorModal() {
    const modal = document.getElementById('doctorModal');
    if (modal) modal.style.display = 'none';
}

function bookAppointmentWithDoctor() {
    closeDoctorModal();
    bookAppointmentWithDoctorFromCard(currentDoctorId, currentSpecialty);
}

// function bookAppointmentWithDoctorFromCard(doctorId, specialty) {
//     const form = document.getElementById('appointmentForm');
//     if (!form) return;
    
//     currentDoctorId = doctorId;
//     currentSpecialty = specialty;
    
//     showAppointmentForm();
    
//     const specialtySelect = document.getElementById('specialityType');
//     const doctorSelect = document.getElementById('doctor');
    
//     if (!specialtySelect || !doctorSelect) return;
    
//     specialtySelect.value = specialty;
    
//     handleSpecialtyChange().then(() => {
//         setTimeout(() => {
//             doctorSelect.value = doctorId;
//             handleDoctorChange();
//         }, 100);
//     }).catch(error => {
//         console.error('Error in specialty change:', error);
//         showAppointmentForm();
//     });
// }

// Modal functions

// function bookAppointmentWithDoctorFromCard(doctorId, specialty) {
//     const form = document.getElementById('appointmentForm');
//     if (!form) return;
    
//     currentDoctorId = doctorId;
//     currentSpecialty = specialty;
    
//     showAppointmentForm();
    
//     const specialtySelect = document.getElementById('specialityType');
//     const doctorSelect = document.getElementById('doctor');
    
//     if (!specialtySelect || !doctorSelect) return;
    
//     // Set the specialty first
//     specialtySelect.value = specialty;
    
//     // Load doctors for the specialty and then select the specific doctor
//     handleSpecialtyChange().then(() => {
//         // Wait a bit longer for the doctors to load
//         setTimeout(() => {
//             // Set the doctor value
//             doctorSelect.value = doctorId;
            
//             // Trigger the doctor change event to update the UI
//             handleDoctorChange();
            
//             // Also trigger the change event manually to ensure any other listeners are called
//             const changeEvent = new Event('change', { bubbles: true });
//             doctorSelect.dispatchEvent(changeEvent);
            
//             // Update doctor info display if exists
//             const doctorInfo = document.getElementById('doctorInfo');
//             if (doctorInfo) {
//                 const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
//                 if (selectedOption && selectedOption.value) {
//                     doctorInfo.innerHTML = `<small>Selected: ${selectedOption.textContent}</small>`;
//                 }
//             }
//         }, 300); // Increased timeout to ensure doctors are loaded
//     }).catch(error => {
//         console.error('Error in specialty change:', error);
//         showMessage('Error loading doctor information', 'error');
//     });
// }

function bookAppointmentWithDoctorFromCard(doctorId, specialty) {
    console.log('bookAppointmentWithDoctorFromCard called with:', { doctorId, specialty });
    
    const form = document.getElementById('appointmentForm');
    if (!form) {
        console.error('Appointment form not found');
        return;
    }
    
    currentDoctorId = doctorId;
    currentSpecialty = specialty;
    
    showAppointmentForm();
    
    const specialtySelect = document.getElementById('specialityType');
    const doctorSelect = document.getElementById('doctor');
    
    if (!specialtySelect) {
        console.error('Specialty select not found');
        return;
    }
    if (!doctorSelect) {
        console.error('Doctor select not found');
        return;
    }
    
    console.log('Setting specialty to:', specialty);
    specialtySelect.value = specialty;
    
    // Manually trigger change event on specialty select
    const specialtyChangeEvent = new Event('change', { bubbles: true });
    specialtySelect.dispatchEvent(specialtyChangeEvent);
    
    // Load doctors and select the specific one
    loadDoctorsAndSelect(doctorId, specialty);
}

function loadDoctorsAndSelect(doctorId, specialty) {
    console.log('Loading doctors for specialty:', specialty);
    
    const formData = new FormData();
    formData.append('action', 'get_doctors_by_specialty');
    formData.append('specialty', specialty);
    
    fetch('api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(doctors => {
            console.log('Doctors loaded:', doctors);
            
            const doctorSelect = document.getElementById('doctor');
            doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
            
            // Populate doctors
            doctors.forEach(doctor => {
                const option = document.createElement('option');
                option.value = doctor.doc_id;
                option.textContent = `${doctor.doc_name} - ${doctor.experience}y exp`;
                doctorSelect.appendChild(option);
            });
            
            // Now select the specific doctor
            console.log('Selecting doctor with ID:', doctorId);
            doctorSelect.value = doctorId;
            
            // Verify the selection worked
            if (doctorSelect.value === doctorId.toString()) {
                console.log('Doctor selected successfully');
                
                // Trigger change event
                const doctorChangeEvent = new Event('change', { bubbles: true });
                doctorSelect.dispatchEvent(doctorChangeEvent);
                
                // Update doctor info display
                const doctorInfo = document.getElementById('doctorInfo');
                if (doctorInfo) {
                    const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
                    doctorInfo.innerHTML = `<small>Selected: ${selectedOption.textContent}</small>`;
                }
                
                // Also call handleDoctorChange directly
                handleDoctorChange();
            } else {
                console.error('Failed to select doctor. Available options:', 
                    Array.from(doctorSelect.options).map(opt => ({ value: opt.value, text: opt.textContent })));
            }
        })
        .catch(error => {
            console.error('Error loading doctors:', error);
            showMessage('Error loading doctors', 'error');
        });
}



function showUpdateModal() {
    const modal = document.getElementById('updateAppointmentModal');
    if (modal) modal.style.display = 'block';
}

function closeUpdateModal() {
    const modal = document.getElementById('updateAppointmentModal');
    if (modal) modal.style.display = 'none';
    
    const form = document.getElementById('updateAppointmentForm');
    if (form) form.reset();
    
    isEditMode = false;
    currentEditAppointmentId = null;
}

// Form validation
function validateFormFields(fields, prefix = '') {
    let isValid = true;
    
    // Clear previous errors
    fields.forEach(field => {
        const errorElement = document.getElementById(field.error);
        const inputElement = document.getElementById(field.id);
        if (errorElement) errorElement.style.display = 'none';
        if (inputElement) inputElement.classList.remove('error');
    });
    
    // Validate each field
    fields.forEach(field => {
        const element = document.getElementById(field.id);
        const errorElement = document.getElementById(field.error);
        
        if (element && !element.value.trim()) {
            isValid = false;
            element.classList.add('error');
            if (errorElement) errorElement.style.display = 'block';
        }
    });
    
    // Validate gender
    const genderName = prefix ? `${prefix}Gender` : 'gender';
    const genderChecked = document.querySelector(`input[name="${genderName}"]:checked`);
    const genderError = document.getElementById(`${prefix}GenderError`);
    if (!genderChecked) {
        isValid = false;
        if (genderError) genderError.style.display = 'block';
    }
    
    // Validate email and phone
    const emailField = document.getElementById(`${prefix}Email`);
    const phoneField = document.getElementById(`${prefix}Phone`);
    
    if (emailField?.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
        isValid = false;
        emailField.classList.add('error');
        const emailError = document.getElementById(`${prefix}EmailError`);
        if (emailError) {
            emailError.textContent = 'Please enter a valid email address';
            emailError.style.display = 'block';
        }
    }
    
    if (phoneField?.value && !/^[0-9]{10}$/.test(phoneField.value.replace(/\D/g, ''))) {
        isValid = false;
        phoneField.classList.add('error');
        const phoneError = document.getElementById(`${prefix}PhoneError`);
        if (phoneError) {
            phoneError.textContent = 'Please enter a valid 10-digit phone number';
            phoneError.style.display = 'block';
        }
    }
    
    return isValid;
}

function validateForm() {
    const fields = [
        { id: 'firstName', error: 'firstNameError' },
        { id: 'phone', error: 'phoneError' },
        { id: 'email', error: 'emailError' },
        { id: 'preferredDate', error: 'preferredDateError' }
    ];
    return validateFormFields(fields);
}

function validateUpdateForm() {
    const fields = [
        { id: 'updateFirstName', error: 'updateFirstNameError' },
        { id: 'updatePhone', error: 'updatePhoneError' },
        { id: 'updateEmail', error: 'updateEmailError' },
        { id: 'updatePreferredDate', error: 'updatePreferredDateError' },
        { id: 'updateSpecialityType', error: 'updateSpecialityTypeError' },
        { id: 'updateDoctor', error: 'updateDoctorError' },
        { id: 'updateTime', error: 'updateTimeError' },
        { id: 'updateStatus', error: 'updateStatusError' }
    ];
    return validateFormFields(fields, 'update');
}

function addFormValidation() {
    const inputs = document.querySelectorAll('#appointmentForm input, #appointmentForm select');
    
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', function() {
            this.classList.remove('error');
            const errorElement = document.getElementById(this.id + 'Error');
            if (errorElement) errorElement.style.display = 'none';
        });
    });
}

function validateField(field) {
    const errorElement = document.getElementById(field.id + 'Error');
    
    if (field.hasAttribute('required') && !field.value.trim()) {
        field.classList.add('error');
        if (errorElement) errorElement.style.display = 'block';
        return false;
    }
    
    field.classList.remove('error');
    if (errorElement) errorElement.style.display = 'none';
    return true;
}

// Utility functions
function resetForm() {
    const form = document.getElementById('appointmentForm');
    if (!form) return;
    
    form.reset();
    
    form.querySelectorAll('.error').forEach(el => el.style.display = 'none');
    form.querySelectorAll('input, select').forEach(el => el.classList.remove('error'));
    
    document.getElementById('doctor').innerHTML = '<option value="">Select Doctor</option>';
    document.getElementById('time').innerHTML = '<option value="">Select Time</option>';
    
    const doctorInfo = document.getElementById('doctorInfo');
    if (doctorInfo) doctorInfo.innerHTML = '';
    
    isEditMode = false;
    currentEditAppointmentId = null;
    currentDoctorId = null;
    currentSpecialty = null;
}

function cancelAppointmentForm() {
    hideAppointmentForm();
    resetForm();
}

function showAppointmentDetails(appointmentData) {
    alert(`Appointment Details:\n\nID: ${appointmentData.id}\nPatient: ${appointmentData.patientName}\nDoctor: ${appointmentData.doctor}\nDate: ${appointmentData.date}\nTime: ${appointmentData.time}\nStatus: ${appointmentData.status}`);
}

function filterAppointments() {
    const filterDate = document.getElementById('filterDate').value;
    const filterStatus = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('#appointmentsTableBody tr');
    
    rows.forEach(row => {
        const dateCell = row.cells[3].textContent;
        const statusCell = row.cells[5].textContent.toLowerCase().trim();
        
        let showRow = true;
        
        if (filterDate) {
            const rowDate = new Date(dateCell.split('/').reverse().join('-'));
            const filterDateObj = new Date(filterDate);
            showRow = showRow && (rowDate.toDateString() === filterDateObj.toDateString());
        }
        
        if (filterStatus) {
            showRow = showRow && statusCell.includes(filterStatus.toLowerCase());
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function showMessage(message, type = 'info') {
    const messageDiv = document.getElementById('message');
    if (!messageDiv) return;
    
    messageDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    messageDiv.style.display = 'block';
    
    setTimeout(() => messageDiv.style.display = 'none', 5000);
}