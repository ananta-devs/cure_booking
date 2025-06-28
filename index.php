<?php
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cure_booking";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Fetch doctors from database (limit to 6 for the homepage)
    $stmt = $pdo->prepare("SELECT doc_id, doc_name, doc_specia, doc_img, fees FROM doctor LIMIT 6");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Find Doctors & Book Appointments Online</title>
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="chatbot.css">
    <link rel="stylesheet" href="search.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
</head>

<body>
    <?php include './include/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-content" data-aos="fade-up">
                <h1>Your Health, Our Priority</h1>
                <p>Find and book appointments with doctors, get online consultation, order medicines, book lab tests, and more.</p>

                <div class="search-box" data-aos="flip-down">
                    <input type="text" class="search-input" placeholder="Search doctors, clinics etc.">
                    <button class="search-btn">Search</button>
                </div>
            </div>

            <div class="model" data-aos="fade-left">
                <img src="http://localhost/cure_booking/assets/appointment_img2.png" />
            </div>
        </div>
    </section>


    <!-- Services Section -->
    <section class="services" data-aos="fade-up">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <div class="service-card" data-aos="fade-up">
                    <a href="http://localhost/cure_booking/find-doctor/doctors.php">
                        <div class="service-img">
                            <img src="http://localhost/cure_booking/assets/doctor-img.jpg" alt="Find Doctors Near You">
                        </div>
                        <div class="service-content">
                            <h3>Find Doctors Near You</h3>
                            <p>Book appointments with qualified doctors</p>
                        </div>
                    </a>
                </div>

                <div class="service-card" data-aos="fade-up">
                    <a href="http://localhost/cure_booking/medicines/medicines.php">
                        <div class="service-img">
                            <img src="http://localhost/cure_booking/assets/medicine-img.jpg" alt="Medicines">
                        </div>
                        <div class="service-content">
                            <h3>Medicines</h3>
                            <p>Order medicines and health products</p>
                        </div>
                    </a>
                </div>

                <div class="service-card" data-aos="fade-up">
                    <a href="http://localhost/cure_booking/find-doctor/doctors.php">
                        <div class="service-img">
                            <img src="http://localhost/cure_booking/assets/lab-img.jpg" alt="Lab Tests">
                        </div>
                        <div class="service-content">
                            <h3>Lab Tests</h3>
                            <p>Book tests and get samples collected</p>
                        </div>
                    </a>
                </div>

                <div class="service-card" data-aos="fade-up">
                    <a href="http://localhost/cure_booking/find-clinic/clinics.php">
                        <div class="service-img">
                            <img src="http://localhost/cure_booking/assets/clinic-img.jpg" alt="Clinic">
                        </div>
                        <div class="service-content">
                            <h3>Clinics</h3>
                            <p>Find clinics near you</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section class="doctors" data-aos="fade-up">
        <div class="container">
            <h2 class="section-title">Popular Doctors</h2>
            <div class="doctors-grid">
                <?php if (!empty($doctors)): ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="slide-content">
                            <div class="card-wrapper">
                                <div class="card">
                                    <div class="image-content">
                                        <span class="doc-overlay"></span>
                                        <div class="card-image">
                                            <?php if (!empty($doctor['doc_img'])): ?>
                                                <img class="card-img" src="http://localhost/cure_booking/adminhub/manage-doctors/uploads/<?php echo htmlspecialchars($doctor['doc_img']); ?>"
                                                    alt="<?php echo htmlspecialchars($doctor['doc_name']); ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-content">
                                        <h3><?php echo htmlspecialchars($doctor['doc_name']); ?></h3>
                                        <div class="doctor-specia"><?php echo htmlspecialchars($doctor['doc_specia']); ?></div>
                                        <button class="button">View More</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="doctor-card" data-aos="fade-up">
                        <div class="doctor-img">
                            <img src="assets/icons/cardiology.png" alt="Doctor">
                        </div>
                        <div class="doctor-content">
                            <h3>No doctors available</h3>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="all-docs-wrapper" data-aos="fade-up">
                <form action="http://localhost/cure_booking/find-doctor/doctors.php" method="get">
                    <button type="submit" class="all-docs-btn">View All Doctors</button>
                </form>
            </div>

            <!----<a href="http://localhost/cure_booking/find-doctor/doctors.php" class="all-docs-btn" data-aos="fade-up"><u>View All Doctors</u></a>--->
        </div>
    </section>

    <!-- App Section -->
    <section class="app-section">
        <div class="container app-container">
            <div class="app-content" data-aos="fade-right">
                <h2>Download the CureBooking App</h2>
                <p>Book appointments, order medicines, consult with doctors,
                    and manage your health records - all from the convenience of your smartphone.</p>
                <div class="app-buttons">
                    <a href="https://play.google.com/store/" target="blank" class="app-btn">
                        <img class="store" src="http://localhost/cure_booking/assets/play-store.png" />
                        <div class="btn-text">
                            <span class="app-btn-text-small">Get it on</span>
                            <span class="app-btn-text-large">Google Play</span>
                        </div>
                    </a>
                    <a href="https://www.apple.com/app-store/" target="blank" class="app-btn appstore">
                        <img class="store" src="http://localhost/cure_booking/assets/app-store.png" alt="App Store Logo" />
                        <div class="btn-text">
                            <span class="app-btn-text-small">Get it on</span>
                            <span class="app-btn-text-large">App Store</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="app-image" data-aos="fade-left">
                <img src="http://localhost/cure_booking/assets/phone-desktop.png" alt="Mobile App">
            </div>
        </div>
    </section>

    <!-- Chatbot -->
    <div class="chatbot-container"
        data-aos="fade-left"
        data-aos-duration="1000"
        data-aos-once="true"
        data-aos-offset="0"
        data-aos-anchor-placement="top-bottom">
        <button class="chatbot-toggle" id="chatbotToggle">💬</button>
        <div class="chatbot-window" id="chatbotWindow">
            <div class="chatbot-header">
                <h3>🏥 Health Assistant</h3>
                <button class="chatbot-close" id="chatbotClose">×</button>
            </div>
            <div class="chatbot-content">
                <div class="chatbot-messages" id="chatbotMessages">
                    <div class="message bot">
                        <div class="message-content">
                            Hi! I'm your CureBooking assistant. How can I help you today?
                            <div class="quick-options">
                                <div class="quick-option" data-message="Find a doctor">Find a doctor</div>
                                <div class="quick-option" data-message="Book appointment">Book appointment</div>
                                <div class="quick-option" data-message="Order medicines">Order medicines</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="typing-indicator" id="typingIndicator">
                    <span>Assistant is typing</span>
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            </div>
            <div class="chatbot-input">
                <input type="text" id="chatbotInput" placeholder="Type your message...">
                <button class="chatbot-send" id="chatbotSend">Send</button>
            </div>
        </div>
    </div>

    <?php include './include/footer.php'; ?>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 1000
        });
    </script>
    
    <script src="chatbot.js"></script>

    <!-- search js -->
    <script>
        // Configuration
        const API_BASE_URL = "http://localhost/cure_booking/api.php";

        // DOM elements - Support both search inputs
        const searchInput = document.getElementById("searchInput") || document.querySelector(".search-input");
        const searchResults = document.getElementById("searchResults");
        const detailsSection = document.getElementById("detailsSection");
        const detailsContent = document.getElementById("detailsContent");

        // Search functionality
        let searchTimeout;

        // Initialize search functionality
        function initializeSearch() {
            if (!searchInput) {
                console.warn("Search input not found");
                return;
            }

            // Create search results container if it doesn't exist
            if (!searchResults) {
                createSearchResultsContainer();
            }

            // Create details section if it doesn't exist
            if (!detailsSection) {
                createDetailsSection();
            }

            // Add event listeners
            searchInput.addEventListener("input", handleSearchInput);
            searchInput.addEventListener("focus", handleSearchFocus);

            // Handle search button click (for hero section)
            const searchBtn = document.querySelector(".search-btn");
            if (searchBtn) {
                searchBtn.addEventListener("click", handleSearchButtonClick);
            }

            // Handle Enter key press
            searchInput.addEventListener("keypress", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    handleSearchButtonClick();
                }
            });
        }

        function createSearchResultsContainer() {
            const container = document.createElement("div");
            container.id = "searchResults";
            container.className = "search-results";
            container.style.display = "none";

            // Insert after search box
            const searchBox = document.querySelector(".search-box");
            if (searchBox) {
                searchBox.appendChild(container);
            } else {
                document.body.appendChild(container);
            }

            // Update global reference
            window.searchResults = container;
        }

        function createDetailsSection() {
            const section = document.createElement("div");
            section.id = "detailsSection";
            section.className = "details-section";
            section.style.display = "none";

            const content = document.createElement("div");
            content.id = "detailsContent";
            content.className = "details-content";

            const closeBtn = document.createElement("button");
            closeBtn.className = "details-close";
            closeBtn.innerHTML = "×";
            closeBtn.onclick = hideDetails;

            section.appendChild(closeBtn);
            section.appendChild(content);
            document.body.appendChild(section);

            // Update global references
            window.detailsSection = section;
            window.detailsContent = content;
        }

        function handleSearchInput() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length >= 2) {
                searchTimeout = setTimeout(() => performSearch(query), 300);
            } else {
                hideSearchResults();
            }
        }

        function handleSearchFocus() {
            const query = this.value.trim();
            if (query.length >= 2) {
                performSearch(query);
            }
        }

        function handleSearchButtonClick() {
            const query = searchInput.value.trim();
            if (query.length >= 2) {
                performSearch(query);
            } else {
                alert("Please enter at least 2 characters to search");
            }
        }

        // Hide search results when clicking outside
        document.addEventListener("click", (e) => {
            if (!e.target.closest(".search-box") && !e.target.closest(".search-results")) {
                hideSearchResults();
            }
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
            const resultsContainer = document.getElementById("searchResults") || window.searchResults;
            if (resultsContainer) {
                resultsContainer.innerHTML = '<div class="search-loading">🔍 Searching...</div>';
                resultsContainer.style.display = "block";
            }
        }

        function hideSearchResults() {
            const resultsContainer = document.getElementById("searchResults") || window.searchResults;
            if (resultsContainer) {
                resultsContainer.style.display = "none";
            }
        }

        function showSearchError(message) {
            const resultsContainer = document.getElementById("searchResults") || window.searchResults;
            if (resultsContainer) {
                resultsContainer.innerHTML = `<div class="error-message">${message}</div>`;
                resultsContainer.style.display = "block";
            }
        }

        function displaySearchResults(data) {
            const resultsContainer = document.getElementById("searchResults") || window.searchResults;
            if (!resultsContainer) return;

            if (data.doctors.length === 0 && data.clinics.length === 0) {
                resultsContainer.innerHTML = '<div class="no-results">No results found. Try different search terms.</div>';
                resultsContainer.style.display = "block";
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

            resultsContainer.innerHTML = html;
            resultsContainer.style.display = "block";
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
            const detailsContentEl = document.getElementById("detailsContent") || window.detailsContent;
            const detailsSectionEl = document.getElementById("detailsSection") || window.detailsSection;

            if (detailsContentEl && detailsSectionEl) {
                detailsContentEl.innerHTML = '<div class="search-loading">Loading details...</div>';
                detailsSectionEl.style.display = "block";
            }
        }

        function showDetailsError(message) {
            const detailsContentEl = document.getElementById("detailsContent") || window.detailsContent;
            const detailsSectionEl = document.getElementById("detailsSection") || window.detailsSection;

            if (detailsContentEl && detailsSectionEl) {
                detailsContentEl.innerHTML = `<div class="error-message">${message}</div>`;
                detailsSectionEl.style.display = "block";
            }
        }

        function displayDoctorDetails(doctor) {
            const detailsContentEl = document.getElementById("detailsContent") || window.detailsContent;
            const detailsSectionEl = document.getElementById("detailsSection") || window.detailsSection;

            if (!detailsContentEl || !detailsSectionEl) return;

            let html = `
                <div class="details-card">
                <span class="popup-close" onclick="closeClinicPopup()"><i class="fa-solid fa-xmark"></i></span>
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
                            <p>${doctor.experience || "Not specified"} ${doctor.experience == 1 ? "Year" : "Years"}</p>
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
            detailsContentEl.innerHTML = html;
            detailsSectionEl.style.display = "block";
        }

        function closeClinicPopup() {
            const popup = document.querySelector('.details-card');
            if (popup) {
                popup.style.display = 'none';
            }
        }

        function displayClinicDetails(clinic, clinicId) {
            const detailsContentEl = document.getElementById("detailsContent") || window.detailsContent;
            const detailsSectionEl = document.getElementById("detailsSection") || window.detailsSection;

            if (!detailsContentEl || !detailsSectionEl) return;

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

            detailsContentEl.innerHTML = html;
            detailsSectionEl.style.display = "block";
        }

        async function loadClinicDoctors(clinicId) {
            const detailsContentEl = document.getElementById("detailsContent") || window.detailsContent;
            if (!detailsContentEl) return;

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
                    detailsContentEl.innerHTML += doctorsHtml;
                }
            } catch (error) {
                console.error("Error loading clinic doctors:", error);
            }
        }

        function hideDetails() {
            const detailsSectionEl = document.getElementById("detailsSection") || window.detailsSection;
            if (detailsSectionEl) {
                detailsSectionEl.style.display = "none";
            }
            if (searchInput) {
                searchInput.focus();
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener("DOMContentLoaded", initializeSearch);

        // Also initialize if called after DOM is already loaded
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initializeSearch);
        } else {
            initializeSearch();
        }
    </script>
</body>

</html>