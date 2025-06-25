// script_doctor.js - Updated to show clinic list with clickable availability
class DoctorProfile {
    constructor() {
        this.doctorId = null;
        this.clinicsData = [];
        this.selectedClinicId = null;
        this.init();
    }

    async init() {
        try {
            // First get the logged-in doctor ID from session
            await this.getLoggedInDoctorId();
            if (this.doctorId) {
                await this.loadDoctorProfile();
            } else {
                this.showError("No doctor logged in. Please login first.");
                // Redirect to login page after 3 seconds
                setTimeout(() => {
                    window.location.href = "../login.php"; // Adjust path as needed
                }, 3000);
            }
        } catch (error) {
            console.error("Error initializing doctor profile:", error);
            this.showError("Failed to load doctor profile");
        }
    }

    async getLoggedInDoctorId() {
        try {
            const response = await fetch("api.php?action=getLoggedInDoctor");

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.doctor_id) {
                this.doctorId = data.doctor_id;
            } else {
                throw new Error(data.message || "No doctor logged in");
            }
        } catch (error) {
            console.error("Error getting logged-in doctor ID:", error);
            // Fallback: try to get from URL params (for backward compatibility)
            const urlParams = new URLSearchParams(window.location.search);
            this.doctorId = urlParams.get("doctor_id");

            if (!this.doctorId) {
                throw new Error(
                    "No doctor logged in and no doctor ID provided"
                );
            }
        }
    }

    async loadDoctorProfile() {
        try {
            const response = await fetch(
                `api.php?action=getDoctorProfile&doctor_id=${this.doctorId}`
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.displayDoctorInfo(data.doctor);
                this.clinicsData = data.clinics;
                this.displayClinicList(data.clinics);
                this.addLogoutFunctionality();
            } else {
                throw new Error(data.message || "Failed to load doctor data");
            }
        } catch (error) {
            console.error("Error loading doctor profile:", error);
            this.showError(
                "Unable to load doctor profile. Please try again later."
            );
        }
    }

    displayDoctorInfo(doctor) {
        // Update doctor image
        const doctorImg = document.getElementById("doctorImg");
        if (doctor.doc_img) {
            doctorImg.src = `uploads/doctors/${doctor.doc_img}`;
            doctorImg.alt = `Dr. ${doctor.doc_name}`;
        } else {
            doctorImg.src = "https://via.placeholder.com/120x120?text=Doctor";
            doctorImg.alt = "Default Doctor Image";
        }

        // Update basic info in header
        document.getElementById(
            "doctorName"
        ).textContent = `Dr. ${doctor.doc_name}`;
        document.getElementById("doctorSpecialization").textContent =
            doctor.doc_specia;

        // Update detailed info in content section
        document.getElementById("doctorExperience").textContent = `${
            doctor.experience || 0
        } years of medical experience`;
        document.getElementById("doctorLocation").textContent =
            doctor.location || "Location not specified";
        document.getElementById("doctorFees").textContent =
            doctor.fees > 0
                ? `₹${doctor.fees} consultation fee`
                : "Free consultation";
        document.getElementById("doctorBio").textContent =
            doctor.bio || "No bio available";
        document.getElementById("doctorEducation").textContent =
            doctor.education || "Education not specified";
        document.getElementById("doctorEmail").textContent =
            doctor.doc_email || "Email not available";
    }

    displayClinicList(clinics) {
        const container = document.getElementById("clinicList");

        if (!clinics || clinics.length === 0) {
            container.innerHTML =
                '<div class="error">No clinic assignments found</div>';
            return;
        }

        const clinicsHTML = clinics
            .map((clinic, index) => this.createClinicListItem(clinic, index))
            .join("");
        container.innerHTML = clinicsHTML;

        // Add click event listeners to clinic items
        this.addClinicClickListeners();
    }

    createClinicListItem(clinic, index) {
        return `
            <div class="clinic-item" data-clinic-id="${index}">
                <div class="clinic-name">${clinic.clinic_name}</div>
                <div class="clinic-location">${clinic.location}</div>
                <div class="clinic-timing">${clinic.available_timing}</div>
            </div>
        `;
    }

    addClinicClickListeners() {
        const clinicItems = document.querySelectorAll(".clinic-item");
        clinicItems.forEach((item) => {
            item.addEventListener("click", (e) => {
                const clinicId = parseInt(e.currentTarget.dataset.clinicId);
                this.showClinicAvailability(clinicId);
            });
        });
    }

    showClinicAvailability(clinicId) {
        // Remove active class from all clinic items
        document.querySelectorAll(".clinic-item").forEach((item) => {
            item.classList.remove("active");
        });

        // Add active class to selected clinic
        const selectedItem = document.querySelector(
            `[data-clinic-id="${clinicId}"]`
        );
        if (selectedItem) {
            selectedItem.classList.add("active");
        }

        // Get clinic data
        const clinic = this.clinicsData[clinicId];
        if (!clinic) return;

        // Update selected clinic name
        document.getElementById(
            "selectedClinicName"
        ).textContent = `${clinic.clinic_name} - Availability Schedule`;

        // Show availability details
        const availabilityDetails = document.getElementById(
            "availabilityDetails"
        );
        availabilityDetails.style.display = "block";

        // Display schedule
        this.displayAvailabilitySchedule(clinic);
        this.selectedClinicId = clinicId;

        // Add close button functionality
        this.addCloseButtonListener();

        // Scroll to availability details
        availabilityDetails.scrollIntoView({ behavior: "smooth" });
    }
    addCloseButtonListener() {
        const closeBtn = document.getElementById("closeAvailabilityBtn");
        if (closeBtn) {
            closeBtn.addEventListener(
                "click",
                this.closeAvailabilityDetails.bind(this)
            );
        }
    }

    closeAvailabilityDetails() {
        // Hide the availability details section
        const availabilityDetails = document.getElementById(
            "availabilityDetails"
        );
        availabilityDetails.style.display = "none";

        // Remove active class from all clinic items
        document.querySelectorAll(".clinic-item").forEach((item) => {
            item.classList.remove("active");
        });

        // Reset selected clinic ID
        this.selectedClinicId = null;
    }

    displayAvailabilitySchedule(clinic) {
        const container = document.getElementById("availabilitySchedule");
        const schedule = JSON.parse(clinic.availability_schedule);
        const weeklyScheduleHTML = this.createWeeklySchedule(schedule);
        container.innerHTML = weeklyScheduleHTML;
    }

    createWeeklySchedule(schedule) {
        const days = [
            "monday",
            "tuesday",
            "wednesday",
            "thursday",
            "friday",
            "saturday",
            "sunday",
        ];
        const timeSlots = ["11:00-13:00", "14:00-16:00", "17:00-19:00"];

        return days
            .map((day) => {
                const daySchedule = schedule[day] || {};
                const slotsHTML = timeSlots
                    .map((slot) => {
                        const isAvailable = daySchedule[slot] === true;
                        const slotClass = isAvailable
                            ? "available"
                            : "unavailable";
                        const slotText = this.formatTimeSlot(slot);

                        return `<div class="time-slot ${slotClass}" title="${
                            isAvailable
                                ? "Available for booking"
                                : "Not available"
                        }">${slotText}</div>`;
                    })
                    .join("");

                return `
                <div class="day-schedule">
                    <div class="day-name">${this.capitalizeFirst(day)}</div>
                    <div class="time-slots">
                        ${slotsHTML}
                    </div>
                </div>
            `;
            })
            .join("");
    }

    formatTimeSlot(slot) {
        const [start, end] = slot.split("-");
        return `${this.formatTime(start)} - ${this.formatTime(end)}`;
    }

    formatTime(time) {
        const [hours, minutes] = time.split(":");
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? "PM" : "AM";
        const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
        return `${displayHour}:${minutes} ${ampm}`;
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    addLogoutFunctionality() {
        // Add logout button if it doesn't exist
        const logoutBtn = document.getElementById("logoutBtn");
        if (logoutBtn) {
            logoutBtn.addEventListener("click", this.logout);
        }
    }

    logout() {
        if (confirm("Are you sure you want to logout?")) {
            fetch("api.php?action=logout", {
                method: "POST",
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        window.location.href = "../login.php"; // Adjust path as needed
                    } else {
                        alert("Logout failed. Please try again.");
                    }
                })
                .catch((error) => {
                    console.error("Logout error:", error);
                    // Force logout on client side even if server request fails
                    window.location.href = "../login.php";
                });
        }
    }

    showError(message) {
        const clinicContainer = document.getElementById("clinicList");
        clinicContainer.innerHTML = `<div class="error">${message}</div>`;

        // Hide availability details
        document.getElementById("availabilityDetails").style.display = "none";

        // Update doctor info with error state
        document.getElementById("doctorName").textContent =
            "Error loading profile";
        document.getElementById("doctorSpecialization").textContent = "";
        document.getElementById("doctorExperience").textContent = "";
        document.getElementById("doctorLocation").textContent = "";
        document.getElementById("doctorFees").textContent = "";
        document.getElementById("doctorBio").textContent = message;
        document.getElementById("doctorEducation").textContent = "";
        document.getElementById("doctorEmail").textContent = "";
    }
}

// Initialize the doctor profile when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
    new DoctorProfile();
});
