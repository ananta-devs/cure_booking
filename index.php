<?php
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cure_booking";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
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
    <!-- <link rel="icon" href="assets/logo.png"> -->
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="chatbot.css">

</head>
<body>
    <?php include './include/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Your Health, Our Priority</h1>
                <p>Find and book appointments with doctors, get online consultation, order medicines, book lab tests, and more.</p>
                
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search doctors, clinics etc.">
                    <button class="search-btn">Search</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <div class="service-card">
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
                
                <div class="service-card">
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
                
                <div class="service-card">
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

                <div class="service-card">
                    <a href="http://localhost/cure_booking/surgery/surgery.php">
                        <div class="service-img">
                            <img src="http://localhost/cure_booking/assets/surgery-img.jpg" alt="Surgery">
                        </div>
                        <div class="service-content">
                            <h3>Surgeries</h3>
                            <p>Book consultations with top surgeons</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section class="doctors">
        <div class="container">
            <h2 class="section-title">Popular Doctors</h2>
            <div class="doctors-grid">
                <?php if (!empty($doctors)): ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="doctor-card">
                            <div class="doctor-img">
                                <?php if (!empty($doctor['doc_img'])): ?>
                                    <img src="http://localhost/adminhub/manage-doctors/uploads/<?php echo htmlspecialchars($doctor['doc_img']); ?>" 
                                        alt="<?php echo htmlspecialchars($doctor['doc_name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="doctor-content">
                                <h3><?php echo htmlspecialchars($doctor['doc_name']); ?></h3>
                                <div class="doctor-specia"><?php echo htmlspecialchars($doctor['doc_specia']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="doctor-card">
                        <div class="doctor-img">
                            <img src="assets/icons/cardiology.png" alt="Doctor">
                        </div>
                        <div class="doctor-content">
                            <h3>No doctors available</h3>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <a href="http://localhost/cure_booking/find-doctor/doctors.php" class="all-docs-btn"><u>View All Doctors</u></a>
        </div>
    </section>

    <!-- App Section -->
    <section class="app-section">
        <div class="container app-container">
            <div class="app-content">
                <h2>Download the CureBooking App</h2>
                <p>Book appointments, order medicines, consult with doctors, 
                    and manage your health records - all from the convenience of your smartphone.</p>
                <div class="app-buttons">
                    <a href="#" class="app-btn">
                        <div>
                            <span class="app-btn-text-small">Get it on</span>
                            <span class="app-btn-text-large">Google Play</span>
                        </div>
                    </a>
                    <a href="#" class="app-btn">
                        <div>
                            <span class="app-btn-text-small">Download on the</span>
                            <span class="app-btn-text-large">App Store</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="app-image">
                <img src="/api/placeholder/300/500" alt="Mobile App">
            </div>
        </div>
    </section>

    <!-- Chatbot -->
    <div class="chatbot-container">
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
    <script>
        class Chatbot {
            constructor() {
                this.chatbotToggle = document.getElementById('chatbotToggle');
                this.chatbotWindow = document.getElementById('chatbotWindow');
                this.chatbotClose = document.getElementById('chatbotClose');
                this.chatbotInput = document.getElementById('chatbotInput');
                this.chatbotSend = document.getElementById('chatbotSend');
                this.chatbotMessages = document.getElementById('chatbotMessages');
                this.typingIndicator = document.getElementById('typingIndicator');

                // Check if all elements exist
                if (!this.checkElements()) {
                    console.error('Chatbot elements not found');
                    return;
                }

                this.responses = {
                    'find a doctor': 'I can help you find the right doctor! You can browse our doctors by specialty or search for specific conditions. Would you like me to redirect you to our doctors page?',
                    'book appointment': 'To book an appointment, first select a doctor from our doctors page. You\'ll then be able to choose an available time slot that works for you.',
                    'order medicines': 'You can order medicines through our pharmacy section. We deliver medications right to your doorstep. Would you like me to take you to the medicines page?',
                    'lab tests': 'We offer various lab tests with home sample collection. You can book tests online and get your reports digitally.',
                    'surgery': 'For surgical consultations, we have experienced surgeons available. You can book a consultation to discuss your surgical needs.',
                    'emergency': 'For medical emergencies, please call emergency services immediately. For urgent but non-emergency care, you can book priority appointments.',
                    'help': 'I can assist you with:<br>• Finding and booking doctors<br>• Ordering medicines<br>• Booking lab tests<br>• Surgery consultations<br>• General health queries',
                    'hello': 'Hello! Welcome to CureBooking. How can I assist you with your healthcare needs today?',
                    'hi': 'Hi there! I\'m here to help you with all your healthcare needs. What can I do for you?',
                    'thanks': 'You\'re welcome! Is there anything else I can help you with?',
                    'thank you': 'You\'re very welcome! Feel free to ask if you need any other assistance.',
                    'default': 'I\'m here to help with your healthcare needs. You can ask me about finding doctors, booking appointments, ordering medicines, or any other health-related queries.'
                };

                this.initializeEventListeners();
            }

            checkElements() {
                const elements = [
                    this.chatbotToggle,
                    this.chatbotWindow,
                    this.chatbotClose,
                    this.chatbotInput,
                    this.chatbotSend,
                    this.chatbotMessages,
                    this.typingIndicator
                ];

                return elements.every(element => element !== null);
            }

            initializeEventListeners() {
                // Toggle chatbot window
                this.chatbotToggle.addEventListener('click', () => {
                    this.toggleChatbot();
                });

                // Close chatbot
                this.chatbotClose.addEventListener('click', () => {
                    this.closeChatbot();
                });

                // Send message on button click
                this.chatbotSend.addEventListener('click', () => {
                    this.sendMessage();
                });

                // Send message on Enter key
                this.chatbotInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.sendMessage();
                    }
                });

                // Quick options event delegation
                this.chatbotMessages.addEventListener('click', (e) => {
                    if (e.target.classList.contains('quick-option')) {
                        const message = e.target.getAttribute('data-message');
                        this.sendQuickMessage(message);
                    }
                });

                // Close chatbot when clicking outside
                document.addEventListener('click', (e) => {
                    if (!this.chatbotWindow.contains(e.target) && 
                        !this.chatbotToggle.contains(e.target) && 
                        this.chatbotWindow.classList.contains('open')) {
                        this.closeChatbot();
                    }
                });
            }

            toggleChatbot() {
                this.chatbotWindow.classList.toggle('open');
            }

            closeChatbot() {
                this.chatbotWindow.classList.remove('open');
            }

            sendMessage() {
                const message = this.chatbotInput.value.trim();
                if (message === '') return;

                // Add user message
                this.addMessage(message, 'user');
                this.chatbotInput.value = '';

                // Show typing and get response
                this.showTypingAndRespond(message);
            }

            sendQuickMessage(message) {
                if (!message) return;
                
                this.addMessage(message, 'user');
                this.showTypingAndRespond(message);
            }

            showTypingAndRespond(message) {
                this.showTyping();

                // Simulate response delay
                setTimeout(() => {
                    this.hideTyping();
                    const response = this.getResponse(message.toLowerCase());
                    this.addMessage(response, 'bot');
                }, 1000 + Math.random() * 1000);
            }

            getResponse(message) {
                for (let key in this.responses) {
                    if (message.includes(key)) {
                        return this.responses[key];
                    }
                }
                return this.responses['default'];
            }

            addMessage(message, sender) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}`;
                messageDiv.innerHTML = `<div class="message-content">${message}</div>`;
                
                this.chatbotMessages.appendChild(messageDiv);
                this.scrollToBottom();
            }

            showTyping() {
                this.typingIndicator.style.display = 'flex';
                this.scrollToBottom();
            }

            hideTyping() {
                this.typingIndicator.style.display = 'none';
            }

            scrollToBottom() {
                this.chatbotMessages.scrollTop = this.chatbotMessages.scrollHeight;
            }
        }

        // Initialize chatbot when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing chatbot...');
            const chatbot = new Chatbot();
            
            // Make it globally available for debugging
            window.chatbot = chatbot;
            console.log('Chatbot initialized successfully');
        });
    </script>
</body>
</html>