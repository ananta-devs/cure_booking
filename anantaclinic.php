<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Clinics - Healthcare Network</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }

        .header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .search-filter {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input, .filter-select {
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            background: rgba(255,255,255,0.9);
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
        }

        .search-input:focus, .filter-select:focus {
            background: white;
            box-shadow: 0 0 20px rgba(255,255,255,0.3);
        }

        .loading {
            text-align: center;
            color: white;
            font-size: 1.2rem;
            margin: 40px 0;
        }

        .clinics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .clinic-card {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .clinic-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4CAF50, #2196F3, #FF9800);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .clinic-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .clinic-card:hover::before {
            transform: scaleX(1);
        }

        .clinic-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .clinic-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            color: white;
        }

        .clinic-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .clinic-info {
            margin-bottom: 15px;
        }

        .clinic-info p {
            margin-bottom: 8px;
            color: #666;
            display: flex;
            align-items: center;
        }

        .clinic-info i {
            margin-right: 8px;
            width: 16px;
            color: #4CAF50;
        }

        .clinic-services {
            margin-bottom: 15px;
        }

        .services-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .services-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .service-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .clinic-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }

        .btn-secondary {
            background: rgba(0,123,255,0.1);
            color: #007bff;
            border: 1px solid #007bff;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .no-results {
            text-align: center;
            color: white;
            font-size: 1.2rem;
            margin: 40px 0;
            padding: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .search-filter {
                flex-direction: column;
            }
            
            .search-input {
                width: 100%;
                min-width: auto;
            }
            
            .clinics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Our Clinic Network</h1>
            <p>Find quality healthcare services near you</p>
        </div>

        <div class="search-filter">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search clinics by name or location...">
            <select id="cityFilter" class="filter-select">
                <option value="">All Cities</option>
            </select>
            <select id="serviceFilter" class="filter-select">
                <option value="">All Services</option>
            </select>
        </div>

        <div id="loading" class="loading" style="display: none;">
            Loading clinics...
        </div>

        <div id="clinicsContainer" class="clinics-grid">
            <!-- Clinics will be loaded here -->
        </div>

        <div id="noResults" class="no-results" style="display: none;">
            <h3>No clinics found</h3>
            <p>Try adjusting your search criteria</p>
        </div>
    </div>

    <script>
        // Sample clinic data - In real implementation, this would come from PHP/database
        let clinicsData = [
            {
                id: 1,
                name: "Central Medical Center",
                address: "123 Main Street",
                city: "New York",
                phone: "+1 (555) 123-4567",
                email: "info@centralmedical.com",
                services: ["General Medicine", "Cardiology", "Pediatrics", "Emergency Care"],
                rating: 4.8,
                hours: "24/7"
            },
            {
                id: 2,
                name: "Family Health Clinic",
                address: "456 Oak Avenue",
                city: "Los Angeles",
                phone: "+1 (555) 987-6543",
                email: "contact@familyhealth.com",
                services: ["Family Medicine", "Pediatrics", "Women's Health", "Vaccination"],
                rating: 4.6,
                hours: "Mon-Fri 8AM-6PM"
            },
            {
                id: 3,
                name: "Specialized Care Institute",
                address: "789 Pine Road",
                city: "Chicago",
                phone: "+1 (555) 456-7890",
                email: "info@specializedcare.com",
                services: ["Orthopedics", "Neurology", "Oncology", "Radiology"],
                rating: 4.9,
                hours: "Mon-Sat 7AM-8PM"
            },
            {
                id: 4,
                name: "Community Wellness Center",
                address: "321 Elm Street",
                city: "Houston",
                phone: "+1 (555) 234-5678",
                email: "hello@communitywellness.com",
                services: ["Mental Health", "Physical Therapy", "Nutrition", "Wellness Programs"],
                rating: 4.7,
                hours: "Mon-Fri 9AM-7PM"
            },
            {
                id: 5,
                name: "Metro Emergency Hospital",
                address: "567 Cedar Boulevard",
                city: "Phoenix",
                phone: "+1 (555) 345-6789",
                email: "emergency@metrohospital.com",
                services: ["Emergency Care", "Trauma Surgery", "ICU", "Ambulatory Care"],
                rating: 4.5,
                hours: "24/7"
            }
        ];

        let filteredClinics = [...clinicsData];
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadClinics();
            populateFilters();
            setupEventListeners();
        });

        function loadClinics() {
            // Simulate loading
            showLoading(true);
            
            setTimeout(() => {
                renderClinics(filteredClinics);
                showLoading(false);
            }, 800);
        }

        function renderClinics(clinics) {
            const container = document.getElementById('clinicsContainer');
            const noResults = document.getElementById('noResults');
            
            if (clinics.length === 0) {
                container.innerHTML = '';
                noResults.style.display = 'block';
                return;
            }
            
            noResults.style.display = 'none';
            
            container.innerHTML = clinics.map(clinic => `
                <div class="clinic-card" onclick="viewClinicDetails(${clinic.id})">
                    <div class="clinic-header">
                        <div class="clinic-icon">🏥</div>
                        <h3 class="clinic-name">${clinic.name}</h3>
                    </div>
                    
                    <div class="clinic-info">
                        <p><i>📍</i> ${clinic.address}, ${clinic.city}</p>
                        <p><i>📞</i> ${clinic.phone}</p>
                        <p><i>✉️</i> ${clinic.email}</p>
                        <p><i>⭐</i> Rating: ${clinic.rating}/5.0</p>
                        <p><i>🕒</i> ${clinic.hours}</p>
                    </div>
                    
                    <div class="clinic-services">
                        <div class="services-title">Services:</div>
                        <div class="services-list">
                            ${clinic.services.map(service => 
                                `<span class="service-tag">${service}</span>`
                            ).join('')}
                        </div>
                    </div>
                    
                    <div class="clinic-actions">
                        <button class="btn btn-primary" onclick="event.stopPropagation(); bookAppointment(${clinic.id})">
                            Book Appointment
                        </button>
                        <button class="btn btn-secondary" onclick="event.stopPropagation(); getDirections('${clinic.address}, ${clinic.city}')">
                            Get Directions
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function populateFilters() {
            const cities = [...new Set(clinicsData.map(clinic => clinic.city))];
            const services = [...new Set(clinicsData.flatMap(clinic => clinic.services))];
            
            const cityFilter = document.getElementById('cityFilter');
            const serviceFilter = document.getElementById('serviceFilter');
            
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                cityFilter.appendChild(option);
            });
            
            services.forEach(service => {
                const option = document.createElement('option');
                option.value = service;
                option.textContent = service;
                serviceFilter.appendChild(option);
            });
        }

        function setupEventListeners() {
            const searchInput = document.getElementById('searchInput');
            const cityFilter = document.getElementById('cityFilter');
            const serviceFilter = document.getElementById('serviceFilter');
            
            searchInput.addEventListener('input', filterClinics);
            cityFilter.addEventListener('change', filterClinics);
            serviceFilter.addEventListener('change', filterClinics);
        }

        function filterClinics() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const selectedCity = document.getElementById('cityFilter').value;
            const selectedService = document.getElementById('serviceFilter').value;
            
            filteredClinics = clinicsData.filter(clinic => {
                const matchesSearch = clinic.name.toLowerCase().includes(searchTerm) ||
                                    clinic.address.toLowerCase().includes(searchTerm) ||
                                    clinic.city.toLowerCase().includes(searchTerm);
                
                const matchesCity = !selectedCity || clinic.city === selectedCity;
                
                const matchesService = !selectedService || clinic.services.includes(selectedService);
                
                return matchesSearch && matchesCity && matchesService;
            });
            
            renderClinics(filteredClinics);
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
            document.getElementById('clinicsContainer').style.display = show ? 'none' : 'grid';
        }

        function viewClinicDetails(clinicId) {
            const clinic = clinicsData.find(c => c.id === clinicId);
            alert(`Viewing details for: ${clinic.name}\n\nThis would typically open a detailed view page.`);
        }

        function bookAppointment(clinicId) {
            const clinic = clinicsData.find(c => c.id === clinicId);
            alert(`Booking appointment at: ${clinic.name}\n\nThis would typically open a booking form.`);
        }

        function getDirections(address) {
            const encodedAddress = encodeURIComponent(address);
            window.open(`https://maps.google.com/maps?q=${encodedAddress}`, '_blank');
        }

        // Simulate real-time updates
        setInterval(() => {
            // This could be used to refresh clinic data from the server
            // fetchClinicsFromServer();
        }, 30000);
    </script>

    <!-- PHP Backend Code (would be in separate files) -->
    <!--
    <?php
    // config/database.php
    class Database {
        private $host = "localhost";
        private $db_name = "clinic_management";
        private $username = "root";
        private $password = "";
        private $conn;

        public function getConnection() {
            $this->conn = null;
            try {
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                     $this->username, $this->password);
                $this->conn->exec("set names utf8");
            } catch(PDOException $exception) {
                echo "Connection error: " . $exception->getMessage();
            }
            return $this->conn;
        }
    }

    // api/get_clinics.php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    include_once '../config/database.php';

    $database = new Database();
    $db = $database->getConnection();

    // Get filter parameters
    $city = isset($_GET['city']) ? $_GET['city'] : '';
    $service = isset($_GET['service']) ? $_GET['service'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Build query with filters
    $query = "SELECT c.*, GROUP_CONCAT(s.service_name SEPARATOR ',') as services 
              FROM clinics c 
              LEFT JOIN clinic_services cs ON c.id = cs.clinic_id 
              LEFT JOIN services s ON cs.service_id = s.id 
              WHERE 1=1";

    $params = array();

    if (!empty($city)) {
        $query .= " AND c.city = :city";
        $params[':city'] = $city;
    }

    if (!empty($search)) {
        $query .= " AND (c.name LIKE :search OR c.address LIKE :search OR c.city LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $query .= " GROUP BY c.id ORDER BY c.name";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process services for each clinic
        foreach ($clinics as &$clinic) {
            if ($clinic['services']) {
                $clinic['services'] = explode(',', $clinic['services']);
            } else {
                $clinic['services'] = array();
            }
        }

        // Filter by service if specified
        if (!empty($service)) {
            $clinics = array_filter($clinics, function($clinic) use ($service) {
                return in_array($service, $clinic['services']);
            });
        }

        echo json_encode(array(
            'success' => true,
            'data' => array_values($clinics),
            'count' => count($clinics)
        ));

    } catch(PDOException $exception) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Error: ' . $exception->getMessage()
        ));
    }

    // Database schema creation script
    /*
    CREATE DATABASE clinic_management;
    USE clinic_management;

    CREATE TABLE clinics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(255),
        rating DECIMAL(2,1) DEFAULT 0,
        hours TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    CREATE TABLE services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_name VARCHAR(255) NOT NULL UNIQUE
    );

    CREATE TABLE clinic_services (
        clinic_id INT,
        service_id INT,
        PRIMARY KEY (clinic_id, service_id),
        FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    );

    -- Sample data
    INSERT INTO clinics (name, address, city, phone, email, rating, hours) VALUES
    ('Central Medical Center', '123 Main Street', 'New York', '+1 (555) 123-4567', 'info@centralmedical.com', 4.8, '24/7'),
    ('Family Health Clinic', '456 Oak Avenue', 'Los Angeles', '+1 (555) 987-6543', 'contact@familyhealth.com', 4.6, 'Mon-Fri 8AM-6PM'),
    ('Specialized Care Institute', '789 Pine Road', 'Chicago', '+1 (555) 456-7890', 'info@specializedcare.com', 4.9, 'Mon-Sat 7AM-8PM');

    INSERT INTO services (service_name) VALUES
    ('General Medicine'), ('Cardiology'), ('Pediatrics'), ('Emergency Care'),
    ('Family Medicine'), ('Women\'s Health'), ('Vaccination'), ('Orthopedics'),
    ('Neurology'), ('Oncology'), ('Radiology'), ('Mental Health'),
    ('Physical Therapy'), ('Nutrition'), ('Wellness Programs'), ('Trauma Surgery'),
    ('ICU'), ('Ambulatory Care');
    */
    ?>
    -->
</body>
</html>