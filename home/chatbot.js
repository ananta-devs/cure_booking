class Chatbot {
    constructor() {
        this.chatbotToggle = document.getElementById("chatbotToggle");
        this.chatbotWindow = document.getElementById("chatbotWindow");
        this.chatbotClose = document.getElementById("chatbotClose");
        this.chatbotInput = document.getElementById("chatbotInput");
        this.chatbotSend = document.getElementById("chatbotSend");
        this.chatbotMessages = document.getElementById("chatbotMessages");
        this.typingIndicator = document.getElementById("typingIndicator");

        // Check if all elements exist
        if (!this.checkElements()) {
            console.error("Chatbot elements not found");
            return;
        }

        this.responses = {
            "find a doctor":
                "I can help you find the right doctor! You can browse our doctors by specialty or search for specific conditions. Would you like me to redirect you to our doctors page?",
            "book appointment":
                "To book an appointment, first select a doctor from our doctors page. You'll then be able to choose an available time slot that works for you.",
            "order medicines":
                "You can order medicines through our pharmacy section. We deliver medications right to your doorstep. Would you like me to take you to the medicines page?",
            "lab tests":
                "We offer various lab tests with home sample collection. You can book tests online and get your reports digitally.",
            surgery:
                "For surgical consultations, we have experienced surgeons available. You can book a consultation to discuss your surgical needs.",
            emergency:
                "For medical emergencies, please call emergency services immediately. For urgent but non-emergency care, you can book priority appointments.",
            help: "I can assist you with:<br>• Finding and booking doctors<br>• Ordering medicines<br>• Booking lab tests<br>• Surgery consultations<br>• General health queries",
            hello: "Hello! Welcome to CureBooking. How can I assist you with your healthcare needs today?",
            hi: "Hi there! I'm here to help you with all your healthcare needs. What can I do for you?",
            thanks: "You're welcome! Is there anything else I can help you with?",
            "thank you":
                "You're very welcome! Feel free to ask if you need any other assistance.",
            default:
                "I'm here to help with your healthcare needs. You can ask me about finding doctors, booking appointments, ordering medicines, or any other health-related queries.",
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
            this.typingIndicator,
        ];

        return elements.every((element) => element !== null);
    }

    initializeEventListeners() {
        // Toggle chatbot window
        this.chatbotToggle.addEventListener("click", () => {
            this.toggleChatbot();
        });

        // Close chatbot
        this.chatbotClose.addEventListener("click", () => {
            this.closeChatbot();
        });

        // Send message on button click
        this.chatbotSend.addEventListener("click", () => {
            this.sendMessage();
        });

        // Send message on Enter key
        this.chatbotInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                this.sendMessage();
            }
        });

        // Quick options event delegation
        this.chatbotMessages.addEventListener("click", (e) => {
            if (e.target.classList.contains("quick-option")) {
                const message = e.target.getAttribute("data-message");
                this.sendQuickMessage(message);
            }
        });

        // Close chatbot when clicking outside
        document.addEventListener("click", (e) => {
            if (
                !this.chatbotWindow.contains(e.target) &&
                !this.chatbotToggle.contains(e.target) &&
                this.chatbotWindow.classList.contains("open")
            ) {
                this.closeChatbot();
            }
        });
    }

    toggleChatbot() {
        this.chatbotWindow.classList.toggle("open");
    }

    closeChatbot() {
        this.chatbotWindow.classList.remove("open");
    }

    sendMessage() {
        const message = this.chatbotInput.value.trim();
        if (message === "") return;

        // Add user message
        this.addMessage(message, "user");
        this.chatbotInput.value = "";

        // Show typing and get response
        this.showTypingAndRespond(message);
    }

    sendQuickMessage(message) {
        if (!message) return;

        this.addMessage(message, "user");
        this.showTypingAndRespond(message);
    }

    showTypingAndRespond(message) {
        this.showTyping();

        // Simulate response delay
        setTimeout(() => {
            this.hideTyping();
            const response = this.getResponse(message.toLowerCase());
            this.addMessage(response, "bot");
        }, 1000 + Math.random() * 1000);
    }

    getResponse(message) {
        for (let key in this.responses) {
            if (message.includes(key)) {
                return this.responses[key];
            }
        }
        return this.responses["default"];
    }

    addMessage(message, sender) {
        const messageDiv = document.createElement("div");
        messageDiv.className = `message ${sender}`;
        messageDiv.innerHTML = `<div class="message-content">${message}</div>`;

        this.chatbotMessages.appendChild(messageDiv);
        this.scrollToBottom();
    }

    showTyping() {
        this.typingIndicator.style.display = "flex";
        this.scrollToBottom();
    }

    hideTyping() {
        this.typingIndicator.style.display = "none";
    }

    scrollToBottom() {
        this.chatbotMessages.scrollTop = this.chatbotMessages.scrollHeight;
    }
}

// Initialize chatbot when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    console.log("Initializing chatbot...");
    const chatbot = new Chatbot();

    // Make it globally available for debugging
    window.chatbot = chatbot;
    console.log("Chatbot initialized successfully");
});
