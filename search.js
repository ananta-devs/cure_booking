// Configuration
const API_BASE_URL = "http://localhost/cure_booking/api.php";

// DOM elements
const searchInput = document.getElementById("searchInput");
const searchResults = document.getElementById("searchResults");
const detailsSection = document.getElementById("detailsSection");
const detailsContent = document.getElementById("detailsContent");

// Search functionality
let searchTimeout;

searchInput.addEventListener("input", function () {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length >= 2) {
        searchTimeout = setTimeout(() => performSearch(query), 300);
    } else {
        hideSearchResults();
    }
});

// Hide search results when clicking outside
document.addEventListener("click", (e) => {
    if (!e.target.closest(".search-box")) hideSearchResults();
});

async function performSearch(query) {
    showSearchLoading();
    
    try {
        const response = await fetch(`${API_BASE_URL}?action=search&query=${encodeURIComponent(query)}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const data = await response.json();
        displaySearchResults(data);
    } catch (error) {
        console.error("Search error:", error);
        showSearchError("Search failed. Please check your connection and try again.");
    }
}

function showSearchLoading() {
    searchResults.innerHTML = '<div class="search-loading">🔍 Searching...</div>';
    searchResults.style.display = "block";
}

function hideSearchResults() {
    searchResults.style.display = "none";
}

function showSearchError(message) {
    searchResults.innerHTML = `<div class="error-message">${message}</div>`;
    searchResults.style.display = "block";
}

function displaySearchResults(data) {
    if (data.doctors.length === 0 && data.clinics.length === 0) {
        searchResults.innerHTML = '<div class="no-results">No results found. Try different search terms.</div>';
        searchResults.style.display = "block";
        return;
    }

    let html = "";

    // Display doctors
    data.doctors.forEach(doctor => {
        html += `
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
        `;
    });

    // Display clinics
    data.clinics.forEach(clinic => {
        html += `
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
        `;
    });

    searchResults.innerHTML = html;
    searchResults.style.display = "block";
}

async function showDoctorDetails(doctorId) {
    hideSearchResults();
    showDetailsLoading();

    try {
        const response = await fetch(`${API_BASE_URL}?action=doctor_details&id=${doctorId}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const data = await response.json();
        data.error ? showDetailsError(data.error) : displayDoctorDetails(data);
    } catch (error) {
        console.error("Doctor details error:", error);
        showDetailsError("Failed to load doctor details. Please try again.");
    }
}

async function showClinicDetails(clinicId) {
    hideSearchResults();
    showDetailsLoading();

    try {
        const response = await fetch(`${API_BASE_URL}?action=clinic_details&id=${clinicId}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const data = await response.json();
        if (data.error) {
            showDetailsError(data.error);
        } else {
            displayClinicDetails(data, clinicId);
            loadClinicDoctors(clinicId);
        }
    } catch (error) {
        console.error("Clinic details error:", error);
        showDetailsError("Failed to load clinic details. Please try again.");
    }
}

function showDetailsLoading() {
    detailsContent.innerHTML = '<div class="search-loading">Loading details...</div>';
    detailsSection.style.display = "block";
}

function showDetailsError(message) {
    detailsContent.innerHTML = `<div class="error-message">${message}</div>`;
    detailsSection.style.display = "block";
}

function displayDoctorDetails(doctor) {
    let html = `
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
                ${doctor.bio ? `
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <h4>About</h4>
                    <p>${doctor.bio}</p>
                </div>` : ''}
            </div>
    `;

    // Display available clinics
    if (doctor.schedules?.length > 0) {
        html += `
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
        `;
    } else if (doctor.clinics) {
        html += `
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
        `;
    }

    html += "</div>";
    detailsContent.innerHTML = html;
    detailsSection.style.display = "block";
}

function displayClinicDetails(clinic, clinicId) {
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
    detailsSection.style.display = "block";
}

async function loadClinicDoctors(clinicId) {
    try {
        const response = await fetch(`${API_BASE_URL}?action=clinic_doctors&id=${clinicId}`);
        const data = await response.json();

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

function hideDetails() {
    detailsSection.style.display = "none";
    searchInput.focus();
}

// Initialize
document.addEventListener("DOMContentLoaded", () => searchInput.focus()); 