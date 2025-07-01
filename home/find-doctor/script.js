// DOM Elements
const elements = {
    doctorsContainer: document.getElementById("doctors-container"),
    searchInput: document.getElementById("search-bar"),
    modal: document.getElementById("doctor-modal"),
    closeModal: document.querySelector(".close-modal"),
    modalDoctorDetails: document.getElementById("modal-doctor-details"),
    bookingModal: document.getElementById("bookingModal"),
    bookingForm: document.getElementById("bookingForm"),
    closeBookingModal: document.querySelector("#bookingModal .close"),
    searchForm: document.querySelector(".search-container"),
};

let currentDoctors = [];
let selectedDoctorForBooking = null;

// Initialize
document.addEventListener("DOMContentLoaded", () => {
    fetchDoctors();
    setupEventListeners();
});

function setupEventListeners() {
    elements.searchForm.addEventListener("submit", (e) => {
        e.preventDefault();
        filterDoctors();
    });

    if (elements.closeModal) {
        elements.closeModal.addEventListener("click", () =>
            closeModal(elements.modal)
        );
    }

    if (elements.closeBookingModal) {
        elements.closeBookingModal.addEventListener("click", () =>
            closeModal(elements.bookingModal)
        );
    }

    window.addEventListener("click", (e) => {
        if (e.target === elements.modal) closeModal(elements.modal);
        if (e.target === elements.bookingModal)
            closeModal(elements.bookingModal);
    });

    if (elements.bookingForm) {
        elements.bookingForm.addEventListener("submit", handleBookingSubmit);
    }

    const clinicSelect = document.querySelector("#clinic");
    const dateInput = document.querySelector("#date");

    if (clinicSelect) clinicSelect.addEventListener("change", loadTimeSlots);
    if (dateInput) dateInput.addEventListener("change", loadTimeSlots);
}

function closeModal(modal) {
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = "auto";
    }
}

async function fetchDoctors(filters = {}) {
    elements.doctorsContainer.innerHTML =
        '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading doctors...</div>';

    const queryParams = new URLSearchParams({ action: "get_doctors" });
    if (filters.specialization)
        queryParams.append("specialization", filters.specialization);

    try {
        const response = await fetch(`api.php?${queryParams.toString()}`);
        if (!response.ok)
            throw new Error(`HTTP error! Status: ${response.status}`);

        const responseText = await response.text();

        if (
            responseText.trim().startsWith("<") ||
            responseText.includes("<br")
        ) {
            throw new Error("Server returned an error instead of JSON data");
        }

        const doctors = JSON.parse(responseText);

        if (doctors.success === false || doctors.error) {
            throw new Error(
                doctors.message || doctors.error || "Unknown error"
            );
        }

        currentDoctors = doctors;
        displayDoctors(doctors);
    } catch (error) {
        console.error("Fetch error:", error);
        elements.doctorsContainer.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <p>Failed to load doctors. Please try again later.</p>
                <small>Error: ${error.message}</small>
            </div>
        `;
    }
}

function filterDoctors() {
    const specialization = elements.searchInput.value;
    fetchDoctors({ specialization });
}

function displayDoctors(doctorsToDisplay) {
    if (!doctorsToDisplay || doctorsToDisplay.length === 0) {
        elements.doctorsContainer.innerHTML =
            '<div class="error-message"><i class="fas fa-user-md"></i><p>No doctors found matching your criteria.</p></div>';
        return;
    }

    elements.doctorsContainer.innerHTML = "";
    doctorsToDisplay.forEach((doctor) => {
        elements.doctorsContainer.appendChild(createDoctorCard(doctor));
    });
}

function createDoctorCard(doctor) {
    const card = document.createElement("div");
    card.className = "doctor-card";

    card.innerHTML = `
        <div class="doctor-info">
            <div data-aos="zoom-in" data-aos-duration="800">
                <h3 class="doctor-name">${doctor.name}</h3>
                <p class="doctor-specialty">${doctor.specialty}</p>
                ${
                    doctor.location
                        ? `<div class="doctor-location"><i class="fas fa-map-marker-alt"></i><span>${doctor.location}</span></div>`
                        : ""
                }
                ${
                    doctor.experience
                        ? `<div class="doctor-experience"><i class="fas fa-user-md"></i><span>${doctor.experience} Years Experience</span></div>`
                        : ""
                }
                ${
                    doctor.fees
                        ? `<div class="doctor-fees"><i class="fas fa-money-bill-wave"></i><span>Consultation: ₹ ${doctor.fees}/-</span></div>`
                        : ""
                }
            </div>
            <div class="doctor-actions" data-aos="fade" data-aos-duration="800">
                <button class="view-profile-btn" data-id="${
                    doctor.id
                }">View Profile</button>
                <button class="book-btn" data-id="${
                    doctor.id
                }">Book Now</button>
            </div>
        </div>
    `;

    setTimeout(() => {
        if (typeof AOS !== "undefined") AOS.refreshHard();
    }, 0);

    card.querySelector(".view-profile-btn").addEventListener("click", () =>
        openDoctorModal(doctor.id)
    );
    card.querySelector(".book-btn").addEventListener("click", () => {
        if (typeof USER_LOGGED_IN !== "undefined" && !USER_LOGGED_IN) {
            if (typeof LOGIN_URL !== "undefined") {
                window.location.href = LOGIN_URL;
            } else {
                alert("Please login to book appointment");
            }
            return;
        }
        openBookingForm(doctor.id);
    });

    return card;
}

async function openDoctorModal(doctorId) {
    const doctor = currentDoctors.find((doc) => doc.id == doctorId);
    if (!doctor) return;

    elements.modalDoctorDetails.innerHTML =
        '<div class="loading-modal"><i class="fas fa-spinner fa-spin"></i><p>Loading doctor details...</p></div>';
    elements.modal.style.display = "block";
    document.body.style.overflow = "hidden";

    try {
        const availabilityData = await fetchDoctorAvailability(doctorId);
        const clinicInfoHTML = createClinicInfo(doctor, availabilityData);

        elements.modalDoctorDetails.innerHTML = `
            <div class="doctor-profile">
                <div class="doctor-profile-info">
                    <h2 class="doctor-profile-name">${doctor.name}</h2>
                    <p class="doctor-specialty">${doctor.specialty}</p>
                    <div class="doctor-profile-details">
                        ${createProfileDetail(
                            "fas fa-graduation-cap",
                            doctor.education
                        )}
                        ${createProfileDetail(
                            "fas fa-map-marker-alt",
                            doctor.location
                        )}
                        ${createProfileDetail(
                            "fas fa-user-md",
                            doctor.experience
                                ? `${doctor.experience} Years Experience`
                                : null
                        )}
                        ${createProfileDetail("fas fa-envelope", doctor.email)}
                        ${createProfileDetail(
                            "fas fa-money-bill-wave",
                            doctor.fees
                                ? `Consultation Fee: ₹ ${doctor.fees}/-`
                                : null
                        )}
                    </div>
                </div>
                <div class="doctor-profile-photo" 
                    style="display: flex;
                    justify-content: center;
                    align-items: center;
                    margin-top: 30px;
                    margin-right: 100px;">
                    <img src="http://localhost/cure_booking/adminhub/manage-doctors/uploads/${
                        doctor.doc_img
                    }" alt="${doctor.name}"
                    style="width: 200px;
                            height: 200px;
                            border-radius: 6px;
                            object-fit: cover;
                            border: 2px solid #3B82F6;
                            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);"/>
                </div>
            </div>
            ${
                doctor.bio
                    ? `<div class="doctor-bio"><h3>About Doctor</h3><p>${doctor.bio}</p></div>`
                    : ""
            }
            ${clinicInfoHTML}
        `;
    } catch (error) {
        console.error("Error loading doctor details:", error);
        elements.modalDoctorDetails.innerHTML =
            createBasicDoctorProfile(doctor);
    }
}

function createBasicDoctorProfile(doctor) {
    return `
        <div class="doctor-profile">
            <div class="doctor-profile-info">
                <h2 class="doctor-profile-name">${doctor.name}</h2>
                <p class="doctor-specialty">${doctor.specialty}</p>
                <div class="doctor-profile-details">
                    ${createProfileDetail(
                        "fas fa-graduation-cap",
                        doctor.education
                    )}
                    ${createProfileDetail(
                        "fas fa-map-marker-alt",
                        doctor.location
                    )}
                    ${createProfileDetail(
                        "fas fa-user-md",
                        doctor.experience
                            ? `${doctor.experience} Years Experience`
                            : null
                    )}
                    ${createProfileDetail("fas fa-envelope", doctor.email)}
                    ${createProfileDetail(
                        "fas fa-money-bill-wave",
                        doctor.fees
                            ? `Consultation Fee: ₹ ${doctor.fees}/-`
                            : null
                    )}
                </div>
            </div>
        </div>
        ${
            doctor.bio
                ? `<div class="doctor-bio"><h3>About Doctor</h3><p>${doctor.bio}</p></div>`
                : ""
        }
    `;
}

function createProfileDetail(iconClass, content) {
    return content
        ? `<div class="doctor-profile-detail"><i class="${iconClass}"></i><span>${content}</span></div>`
        : "";
}

function createClinicInfo(doctor, availabilityData) {
    const clinicNames = doctor.clinic_names
        ? doctor.clinic_names.split(", ")
        : [];
    const clinicLocations = doctor.clinic_locations
        ? doctor.clinic_locations.split(", ")
        : [];

    if (clinicNames.length === 0) {
        return doctor.availability &&
            Array.isArray(doctor.availability) &&
            doctor.availability.length > 0
            ? `<div class="doctor-clinics-section"><h3>Availability:</h3><div class="clinic-item"><div class="clinic-availability"><i class="fas fa-calendar-alt"></i><span><strong>Available Days:</strong> ${doctor.availability.join(
                  ", "
              )}</span></div></div></div>`
            : "";
    }

    let clinicInfoHTML =
        '<div class="doctor-clinics-section"><h3>Available at Clinics:</h3><div class="clinics-list">';

    clinicNames.forEach((clinicName, index) => {
        const clinicLocation =
            clinicLocations[index] || "Location not specified";
        const clinicAvailability =
            availabilityData.availability &&
            availabilityData.availability[clinicName]
                ? formatAvailabilitySchedule(
                      availabilityData.availability[clinicName]
                  )
                : null;

        clinicInfoHTML += `
            <div class="clinic-item">
                <div class="clinic-header">
                    <h4 class="clinic-name">${clinicName}</h4>
                    <p class="clinic-location"><i class="fas fa-map-marker-alt"></i> ${clinicLocation}</p>
                </div>
                ${
                    clinicAvailability
                        ? `<div class="clinic-availability">${clinicAvailability}</div>`
                        : ""
                }
            </div>
        `;
    });

    return clinicInfoHTML + "</div></div>";
}

function formatAvailabilitySchedule(schedule) {
    if (!schedule || typeof schedule !== "object") return "";

    let formattedSchedule = '<div class="availability-schedule">';

    const dayOrder = [
        "monday",
        "tuesday",
        "wednesday",
        "thursday",
        "friday",
        "saturday",
        "sunday",
    ];
    const availableDays = [];

    dayOrder.forEach((day) => {
        if (schedule[day] && Object.keys(schedule[day]).length > 0) {
            const slots = Object.keys(schedule[day]).filter(
                (slot) => schedule[day][slot]
            );
            if (slots.length > 0) {
                availableDays.push({
                    day: day.charAt(0).toUpperCase() + day.slice(1),
                    slots: slots.sort(),
                });
            }
        }
    });

    if (availableDays.length === 0) {
        return '<div class="no-availability">Schedule not available</div>';
    }

    formattedSchedule +=
        '<h5><i class="fas fa-calendar-alt"></i> Weekly Schedule:</h5>';
    availableDays.forEach((dayInfo) => {
        formattedSchedule += `
            <div class="day-schedule">
                <strong>${dayInfo.day}:</strong> 
                <span class="time-slots">${dayInfo.slots.join(", ")}</span>
            </div>
        `;
    });

    return formattedSchedule + "</div>";
}

async function fetchDoctorAvailability(doctorId) {
    try {
        const response = await fetch(
            `api.php?action=get_doctor_availability&doctor_id=${doctorId}`
        );

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || "Failed to fetch availability");
        }

        return data;
    } catch (error) {
        console.error("Error fetching doctor availability:", error);
        return { availability: null };
    }
}

async function openBookingForm(doctorId) {
    const doctor = currentDoctors.find((doc) => doc.id == doctorId);
    if (!doctor) {
        alert("Doctor not found");
        return;
    }

    selectedDoctorForBooking = doctor;

    // Populate doctor info in booking form
    const doctorNameSpan = document.querySelector("#selectedDoctorName");
    const doctorSpecialtySpan = document.querySelector(
        "#selectedDoctorSpecialty"
    );

    if (doctorNameSpan) doctorNameSpan.textContent = doctor.name;
    if (doctorSpecialtySpan) doctorSpecialtySpan.textContent = doctor.specialty;

    // Set doctor_id in hidden field
    let doctorIdInput = document.querySelector("#doctor_id");
    if (doctorIdInput) {
        doctorIdInput.value = doctor.id;
    } else {
        // Create the hidden input if it doesn't exist
        const form = document.querySelector("#bookingForm");
        if (form) {
            doctorIdInput = document.createElement("input");
            doctorIdInput.type = "hidden";
            doctorIdInput.id = "doctor_id";
            doctorIdInput.name = "doctor_id";
            doctorIdInput.value = doctor.id;
            form.appendChild(doctorIdInput);
        }
    }

    // Populate clinic options
    await populateClinicOptions(doctor);

    // Set date constraints
    const dateInput = document.querySelector("#date");
    if (dateInput) {
        const today = new Date().toISOString().split("T")[0];
        dateInput.min = today;

        const maxDate = new Date();
        maxDate.setMonth(maxDate.getMonth() + 3);
        dateInput.max = maxDate.toISOString().split("T")[0];
        dateInput.value = "";
    }

    // Clear previous selections
    const clinicSelect = document.querySelector("#clinic");
    const timeSelect = document.querySelector("#time");

    if (clinicSelect) clinicSelect.value = "";
    if (timeSelect) {
        timeSelect.innerHTML = '<option value="">Select time slot</option>';
        timeSelect.disabled = true;
    }

    // Show booking modal
    elements.bookingModal.style.display = "block";
    document.body.style.overflow = "hidden";
}

async function populateClinicOptions(doctor) {
    const clinicSelect = document.querySelector("#clinic");
    if (!clinicSelect) return;

    clinicSelect.innerHTML = '<option value="">Select clinic</option>';

    if (doctor.clinic_names) {
        const clinicNames = doctor.clinic_names.split(", ");
        const clinicLocations = doctor.clinic_locations
            ? doctor.clinic_locations.split(", ")
            : [];

        clinicNames.forEach((clinicName, index) => {
            const location = clinicLocations[index] || "";
            const displayText = location
                ? `${clinicName} - ${location}`
                : clinicName;

            const option = document.createElement("option");
            option.value = clinicName;
            option.textContent = displayText;
            clinicSelect.appendChild(option);
        });
    } else {
        const option = document.createElement("option");
        option.value = "General Clinic";
        option.textContent = "General Clinic";
        clinicSelect.appendChild(option);
    }
}

async function loadTimeSlots() {
    const clinicSelect = document.querySelector("#clinic");
    const dateInput = document.querySelector("#date");
    const timeSelect = document.querySelector("#time");

    if (!clinicSelect || !dateInput || !timeSelect || !selectedDoctorForBooking)
        return;

    const clinic = clinicSelect.value;
    const date = dateInput.value;

    if (!clinic || !date) {
        timeSelect.innerHTML = '<option value="">Select time slot</option>';
        timeSelect.disabled = true;
        return;
    }

    timeSelect.innerHTML = '<option value="">Loading time slots...</option>';
    timeSelect.disabled = true;

    try {
        const response = await fetch(
            `api.php?action=get_time_slots&doctor_id=${
                selectedDoctorForBooking.id
            }&clinic_name=${encodeURIComponent(clinic)}&date=${date}`
        );

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || "Failed to load time slots");
        }

        timeSelect.innerHTML = '<option value="">Select time slot</option>';

        if (data.time_slots && data.time_slots.length > 0) {
            let hasAvailableSlots = false;

            data.time_slots.forEach((slot) => {
                const option = document.createElement("option");
                option.value = slot.value;

                if (slot.available) {
                    option.textContent = slot.label;
                    option.disabled = false;
                    hasAvailableSlots = true;
                } else {
                    option.textContent = `${slot.label} - All slots full`;
                    option.disabled = true;
                    option.style.color = "#999";
                }

                timeSelect.appendChild(option);
            });

            if (hasAvailableSlots) {
                timeSelect.disabled = false;

                // Add helpful message option
                const infoOption = document.createElement("option");
                infoOption.value = "";
                //infoOption.textContent = '── Note: Times auto-assigned in 20min intervals ──';
                infoOption.disabled = true;
                infoOption.style.fontStyle = "italic";
                infoOption.style.color = "#666";
                timeSelect.appendChild(infoOption);
            } else {
                timeSelect.innerHTML =
                    '<option value="">All slots are full for this date</option>';
                timeSelect.disabled = true;
            }
        } else {
            timeSelect.innerHTML =
                '<option value="">No slots available</option>';
        }
    } catch (error) {
        console.error("Error loading time slots:", error);
        timeSelect.innerHTML = '<option value="">Error loading slots</option>';
        showNotification(
            "Failed to load time slots. Please try again.",
            "error"
        );
    }
}

async function handleBookingSubmit(e) {
    e.preventDefault();

    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;

    submitButton.textContent = "Booking...";
    submitButton.disabled = true;

    try {
        const formData = new FormData(e.target);

        // Ensure doctor_id is included
        let doctorId = formData.get("doctor_id");
        if (!doctorId && selectedDoctorForBooking) {
            doctorId = selectedDoctorForBooking.id;
            formData.set("doctor_id", doctorId);
        }

        // Validate required fields
        const requiredFields = [
            "doctor_id",
            "name",
            "phone",
            "email",
            "gender",
            "date",
            "time",
            "clinic",
        ];
        const missingFields = requiredFields.filter(
            (field) => !formData.get(field) || formData.get(field).trim() === ""
        );

        if (missingFields.length > 0) {
            throw new Error(
                `Missing required fields: ${missingFields.join(", ")}`
            );
        }

        formData.append("action", "book_appointment");

        const response = await fetch("api.php", {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            showNotification(
                "Appointment booked successfully! Your exact time has been assigned.",
                "success"
            );
            closeModal(elements.bookingModal);
            e.target.reset();

            if (result.appointment_details) {
                showAppointmentConfirmationWithTimeInfo(
                    result.appointment_details
                );
            }
        } else {
            throw new Error(result.message || "Booking failed");
        }
    } catch (error) {
        console.error("Booking error:", error);
        showNotification(`Booking failed: ${error.message}`, "error");
    } finally {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }
}

function showAppointmentConfirmationWithTimeInfo(details) {
    const confirmationHTML = `
        <div class="appointment-confirmation-card" style="background: #ffffff;
                    border-radius: 16px;
                    padding: 24px 28px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                    max-width: 480px;
                    margin: 0 auto;
                    font-family: 'Segoe UI', sans-serif;
                    color: #333;
                    animation: fadeInScale 0.4s ease;">
            <div class="confirmation-header" style="text-align: center;
                    margin-bottom: 20px;">
                <i class="fas fa-check-circle success-icon" style="color: #4CAF50;
                    font-size: 40px;
                    margin-bottom: 10px;"></i>
                <h3 style="font-size: 22px;
                    margin: 0;
                    color: #333;">Appointment Confirmed</h3>
            </div>

            <div class="confirmation-body">
                <div class="confirmation-info" style="margin-bottom: 20px;">
                    <p style="margin: 8px 0;
                            font-size: 15px;"><strong>Patient:</strong> ${details.patient_name}</p>
                    <p style="margin: 8px 0;
                            font-size: 15px;"><strong>Doctor:</strong> ${details.doctor_name}</p>
                    <p style="margin: 8px 0;
                            font-size: 15px;"><strong>Date:</strong> ${details.appointment_date}</p>
                    <p style="margin: 8px 0;
                            font-size: 15px;"><strong>Assigned Time:</strong> ${details.appointment_time}</p>
                    <p style="margin: 8px 0;
                            font-size: 15px;"><strong>Clinic:</strong> ${details.clinic_name}</p>
                    <p style="margin: 8px 0;
                            font-size: 15px;"><strong>Appointment ID:</strong> #${details.appointment_id}</p>
                </div>

                <div class="confirmation-extra-info" style="background-color: #f0f7ff;
                    border-left: 4px solid #2196F3;
                    padding: 12px 16px;
                    border-radius: 8px;
                    font-size: 14px;
                    color: #333;
                    margin-bottom: 16px;">
                    <p><i class="fas fa-info-circle"></i> <strong>Time Assignment:</strong></p>
                    <p>Your appointment time has been automatically assigned based on availability. Appointments are scheduled in 20-minute intervals.</p>
                </div>
                <p class="confirmation-note" style="font-size: 14px;
                    color: #D84315;
                    font-weight: 500;
                    text-align: center;">
                    📌 Please arrive 15 minutes before your appointment time.
                </p>
            </div>
        </div>
    `;

    showModal(confirmationHTML, "Appointment Confirmation");
}

function showNotification(message, type = "info") {
    // Remove existing notifications
    document
        .querySelectorAll(".notification")
        .forEach((notif) => notif.remove());

    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;

    const icon =
        type === "success"
            ? "fas fa-check-circle"
            : type === "error"
            ? "fas fa-exclamation-circle"
            : "fas fa-info-circle";

    notification.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function showModal(content, title = "") {
    let modal = document.getElementById("customModal");
    if (!modal) {
        modal = document.createElement("div");
        modal.id = "customModal";
        modal.className = "modal";
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title"></h2>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body"></div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector(".close").addEventListener("click", () => {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        });

        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.style.display = "none";
                document.body.style.overflow = "auto";
            }
        });
    }

    modal.querySelector(".modal-title").textContent = title;
    modal.querySelector(".modal-body").innerHTML = content;
    modal.style.display = "block";
    document.body.style.overflow = "hidden";
}
