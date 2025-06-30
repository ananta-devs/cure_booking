// Configuration
const API_BASE_URL = "http://localhost/cure_booking/home/api.php";

// DOM elements
const searchInput = document.getElementById("searchInput");
const searchResults = document.getElementById("searchResults");
const detailsSection = document.getElementById("detailsSection");
const detailsContent = document.getElementById("detailsContent");

let searchTimeout;

// Event listeners
searchInput.addEventListener("input", function () {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length >= 2) {
        searchTimeout = setTimeout(() => performSearch(query), 300);
    } else {
        hideSearchResults();
    }
});

document.addEventListener("click", (e) => {
    if (!e.target.closest(".search-box")) hideSearchResults();
});

// Utility functions
function showElement(element, content) {
    element.innerHTML = content;
    element.style.display = "block";
}

function hideElement(element) {
    element.style.display = "none";
}

function hideSearchResults() {
    hideElement(searchResults);
}

function hideDetails() {
    hideElement(detailsSection);
    searchInput.focus();
}

// API functions
async function apiRequest(action, params = {}) {
    const url = new URL(API_BASE_URL);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
    
    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return response.json();
}

async function performSearch(query) {
    showElement(searchResults, '<div class="search-loading">🔍 Searching...</div>');
    
    try {
        const data = await apiRequest('search', { query });
        displaySearchResults(data);
    } catch (error) {
        console.error("Search error:", error);
        showElement(searchResults, '<div class="error-message">Search failed. Please check your connection and try again.</div>');
    }
}

function displaySearchResults(data) {
    if (data.doctors.length === 0 && data.clinics.length === 0) {
        showElement(searchResults, '<div class="no-results">No results found. Try different search terms.</div>');
        return;
    }

    const doctorResults = data.doctors.map(doctor => `
        <div class="search-result-item" onclick="showDoctorDetails(${doctor.doc_id})">
            <div class="result-info">
                <div class="result-name">${doctor.doc_name}</div>
                <div class="result-specialty">${doctor.doc_specia}</div>
                <div class="result-location">${doctor.location || "Location not specified"}</div>
            </div>
            <div style="text-align: right;">
                <div class="result-type">Doctor</div>
                <div class="result-fee">₹${doctor.fees}</div>
            </div>
        </div>
    `).join('');

    const clinicResults = data.clinics.map(clinic => `
        <div class="search-result-item" onclick="showClinicDetails(${clinic.clinic_id})">
            <div class="result-info">
                <div class="result-name">${clinic.clinic_name}</div>
                <div class="result-specialty">Clinic</div>
                <div class="result-location">${clinic.location}</div>
            </div>
            <div style="text-align: right;">
                <div class="result-type clinic">Clinic</div>
                <div class="result-fee">${clinic.contact_number}</div>
            </div>
        </div>
    `).join('');

    showElement(searchResults, doctorResults + clinicResults);
}

async function showDoctorDetails(doctorId) {
    hideSearchResults();
    showElement(detailsContent, '<div class="search-loading">Loading details...</div>');
    detailsSection.style.display = "block";

    try {
        const data = await apiRequest('doctor_details', { id: doctorId });
        if (data.error) throw new Error(data.error);
        displayDoctorDetails(data);
    } catch (error) {
        console.error("Doctor details error:", error);
        showElement(detailsContent, '<div class="error-message">Failed to load doctor details. Please try again.</div>');
    }
}

async function showClinicDetails(clinicId) {
    hideSearchResults();
    showElement(detailsContent, '<div class="search-loading">Loading details...</div>');
    detailsSection.style.display = "block";

    try {
        const data = await apiRequest('clinic_details', { id: clinicId });
        if (data.error) throw new Error(data.error);
        
        displayClinicDetails(data);
        loadClinicDoctors(clinicId);
    } catch (error) {
        console.error("Clinic details error:", error);
        showElement(detailsContent, '<div class="error-message">Failed to load clinic details. Please try again.</div>');
    }
}

function displayDoctorDetails(doctor) {
    const bioSection = doctor.bio ? `
        <div class="detail-item" style="grid-column: 1 / -1;">
            <h4>About</h4>
            <p>${doctor.bio}</p>
        </div>` : '';

    const clinicsSection = doctor.schedules?.length > 0 ? `
        <div class="clinic-list">
            <h3>🏥 Available at Clinics</h3>
            ${doctor.schedules.map(schedule => `
                <div class="clinic-item">
                    <div class="clinic-info">
                        <h4>${schedule.clinic_name}</h4>
                        <p>${schedule.location}</p>
                    </div>
                    <div class="clinic-badge">Available</div>
                </div>
            `).join('')}
        </div>
    ` : doctor.clinics ? `
        <div class="clinic-list">
            <h3>🏥 Available at Clinics</h3>
            <div class="clinic-item">
                <div class="clinic-info">
                    <h4>Clinics</h4>
                    <p>${doctor.clinics}</p>
                </div>
                <div class="clinic-badge">Available</div>
            </div>
        </div>
    ` : '';

    const html = `
        <div class="details-card">
            <div class="details-header">
                <div class="details-avatar">👨‍⚕️</div>
                <div class="details-info">
                    <h2>${doctor.doc_name}</h2>
                    <p>${doctor.doc_specia}</p>
                </div>
            </div>
            
            <div class="details-content">
                <div class="detail-item">
                    <h4>Consultation Fee</h4>
                    <p>₹${doctor.fees}</p>
                </div>
                <div class="detail-item">
                    <h4>Experience</h4>
                    <p>${doctor.experience || "Not specified"}</p>
                </div>
                <div class="detail-item">
                    <h4>Location</h4>
                    <p>${doctor.location || "Not specified"}</p>
                </div>
                <div class="detail-item">
                    <h4>Education</h4>
                    <p>${doctor.education || "Not specified"}</p>
                </div>
                ${bioSection}
            </div>
            ${clinicsSection}
        </div>
    `;

    detailsContent.innerHTML = html;
}

function displayClinicDetails(clinic) {
    const html = `
        <div class="details-card">
            <div class="details-header">
                <div class="details-avatar">🏥</div>
                <div class="details-info">
                    <h2>${clinic.clinic_name}</h2>
                    <p>Medical Clinic</p>
                </div>
            </div>
            
            <div class="details-content">
                <div class="detail-item">
                    <h4>Location</h4>
                    <p>${clinic.location}</p>
                </div>
                <div class="detail-item">
                    <h4>Contact</h4>
                    <p>${clinic.contact_number}</p>
                </div>
                <div class="detail-item">
                    <h4>Available Timing</h4>
                    <p>${clinic.available_timing || "Contact for timings"}</p>
                </div>
                <div class="detail-item">
                    <h4>Total Doctors</h4>
                    <p>${clinic.doctor_count} doctors available</p>
                </div>
            </div>
        </div>
    `;

    detailsContent.innerHTML = html;
}

async function loadClinicDoctors(clinicId) {
    try {
        const data = await apiRequest('clinic_doctors', { id: clinicId });

        if (data.doctors?.length > 0) {
            const doctorsHtml = `
                <div class="details-card">
                    <div class="clinic-list">
                        <h3>👨‍⚕️ Available Doctors</h3>
                        ${data.doctors.map(doctor => `
                            <div class="clinic-item" onclick="showDoctorDetails(${doctor.doc_id})" style="cursor: pointer;">
                                <div class="clinic-info">
                                    <h4>${doctor.doc_name}</h4>
                                    <p>${doctor.doc_specia} • ₹${doctor.fees}</p>
                                </div>
                                <div class="clinic-badge">View Details</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            detailsContent.innerHTML += doctorsHtml;
        }
    } catch (error) {
        console.error("Error loading clinic doctors:", error);
    }
}

// Initialize
document.addEventListener("DOMContentLoaded", () => searchInput.focus());