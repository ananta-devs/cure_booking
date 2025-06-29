class LabTestManager {
    constructor() {
        this.allLabTests = [];
        this.currentSearchResults = [];
        this.currentPage = 1;
        this.itemsPerPage = 9;
        this.cart = [];
        this.clinics = [];
        this.lastSearchQuery = "";
        this.noResultsDisplayed = false;

        this.initElements();
        this.init();
    }

    initElements() {
        const getElement = (id) => document.getElementById(id);
        
        this.elements = {
            searchInput: getElement("search-bar"),
            loadMoreBtn: getElement("load-more-btn"),
            loadingIndicator: getElement("loading"),
            errorMessage: getElement("error-message"),
            resultsContainer: getElement("results-container"),
            modal: getElement("bookingModal"),
            cartModal: getElement("cartModal"),
            modalTestInfo: getElement("modalTestInfo"),
            bookingForm: getElement("bookingForm"),
            searchForm: document.querySelector(".search-container"),
            clinicSelect: getElement("clinic_id"),
            cartSummary: getElement("cart-summary"),
            cartCount: getElement("cart-count"),
            cartTotal: getElement("cart-total"),
            modalCartTotal: getElement("modal-cart-total"),
            cartItemsContainer: getElement("cart-items-container"),
            dateInput: getElement("date"),
        };
    }

    async init() {
        this.bindEvents();
        this.setMinDate();
        await Promise.all([this.loadLabTestsData(), this.loadClinics()]);
        if (isUserLoggedIn) this.updateCartDisplay();
    }

    bindEvents() {
        const { searchForm, searchInput, loadMoreBtn, bookingForm } = this.elements;

        // Search events
        searchForm?.addEventListener("submit", this.handleSearchSubmit.bind(this));
        searchInput?.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
                this.initiateSearch();
            }
        });
        searchInput?.addEventListener("input", this.handleSearchInputChange.bind(this));

        // Cart events (only if logged in)
        if (isUserLoggedIn) {
            const cartEvents = [
                ["view-cart-btn", () => this.openModal("cart")],
                ["checkout-btn", this.proceedToCheckout.bind(this)],
                ["clear-cart-btn", this.clearCart.bind(this)],
                ["proceed-checkout-btn", () => {
                    this.closeModal("cart");
                    this.proceedToCheckout();
                }]
            ];
            
            cartEvents.forEach(([id, handler]) => {
                document.getElementById(id)?.addEventListener("click", handler);
            });
        }

        // Modal events
        document.querySelectorAll(".modal .close").forEach((btn) => {
            btn.addEventListener("click", () => {
                const modalType = btn.getAttribute("data-modal");
                this.closeModal(modalType === "cart" ? "cart" : "booking");
                if (modalType !== "cart") bookingForm?.reset();
            });
        });

        // Click outside modal to close
        window.addEventListener("click", (event) => {
            if (event.target === this.elements.modal) {
                bookingForm?.reset();
                this.closeModal("booking");
            } else if (event.target === this.elements.cartModal) {
                this.closeModal("cart");
            }
        });

        loadMoreBtn?.addEventListener("click", this.loadMoreResults.bind(this));
        bookingForm?.addEventListener("submit", this.handleFormSubmit.bind(this));
    }

    handleSearchSubmit(e) {
        e.preventDefault();
        this.initiateSearch();
    }

    handleSearchInputChange() {
        const currentQuery = this.elements.searchInput?.value.trim() || "";

        if (currentQuery === "") {
            this.displayRandomLabTests();
            this.lastSearchQuery = "";
            this.noResultsDisplayed = false;
            this.clearError();
            this.elements.loadMoreBtn?.classList.add("hidden");
            document.querySelector(".search-results-info")?.remove();
            return;
        }

        if (this.noResultsDisplayed) {
            this.noResultsDisplayed = false;
        }
    }

    setMinDate() {
        if (this.elements.dateInput) {
            this.elements.dateInput.min = new Date().toISOString().split("T")[0];
        }
    }

    async loadClinics() {
        try {
            const response = await fetch("api.php?action=get_clinics");
            const data = await response.json();

            if (data.status === "success") {
                this.clinics = data.clinics || [];
                this.populateClinicSelect();
            }
        } catch (error) {
            console.error("Error loading clinics:", error);
        }
    }

    populateClinicSelect() {
        const { clinicSelect } = this.elements;
        if (!clinicSelect) return;

        // Clear existing options except the first one
        while (clinicSelect.children.length > 1) {
            clinicSelect.removeChild(clinicSelect.lastChild);
        }

        this.clinics.forEach((clinic) => {
            const option = document.createElement("option");
            option.value = clinic.id;
            option.textContent = `${clinic.name} - ${clinic.location}`;
            clinicSelect.appendChild(option);
        });
    }

    async loadLabTestsData() {
        this.showLoading();
        this.clearError();

        try {
            const response = await fetch("api.php?action=get_tests&limit=1000");
            const data = await response.json();

            if (data.status === "error") throw new Error(data.message);

            this.allLabTests = data.tests || [];

            if (this.allLabTests.length === 0) {
                this.showError("No lab tests data available. Please check your database.");
                return;
            }

            this.displayRandomLabTests();
        } catch (error) {
            console.error("Error loading lab tests:", error);
            this.showError("Error loading lab tests data. Please refresh the page and try again.");
        } finally {
            this.hideLoading();
        }
    }

    displayRandomLabTests() {
        const shuffled = [...this.allLabTests].sort(() => 0.5 - Math.random());
        const randomLabTests = shuffled.slice(0, this.itemsPerPage);
        this.clearError();
        this.displayResults(randomLabTests);
        this.elements.loadMoreBtn?.classList.add("hidden");
        this.noResultsDisplayed = false;
    }

    initiateSearch() {
        const query = this.elements.searchInput.value.trim();
        this.lastSearchQuery = query;

        if (query.length < 1) {
            this.clearError();
            this.displayRandomLabTests();
            return;
        }

        this.currentPage = 1;
        this.performSearch(query);
    }

    performSearch(query) {
        const searchTerms = query.toLowerCase();

        this.currentSearchResults = this.allLabTests.filter((labTest) => {
            const fields = [
                labTest.name?.toLowerCase() || "",
                labTest.sample?.toLowerCase() || "",
                labTest.description?.toLowerCase() || ""
            ];

            return fields.some(field => field.includes(searchTerms)) ||
                   searchTerms.split(" ").some(word => 
                       word.length > 2 && fields.some(field => field.includes(word))
                   );
        });

        this.clearError();

        if (this.currentSearchResults.length === 0) {
            this.showError(`No lab tests found for "${query}". Try different keywords like "blood", "urine", "sugar", etc.`);
            this.clearResults();
            this.elements.loadMoreBtn?.classList.add("hidden");
            this.noResultsDisplayed = true;
            return;
        }

        this.noResultsDisplayed = false;

        // Sort by relevance
        this.currentSearchResults.sort((a, b) => {
            const aName = (a.name || "").toLowerCase();
            const bName = (b.name || "").toLowerCase();
            const queryLower = searchTerms.toLowerCase();

            if (aName === queryLower && bName !== queryLower) return -1;
            if (bName === queryLower && aName !== queryLower) return 1;
            if (aName.startsWith(queryLower) && !bName.startsWith(queryLower)) return -1;
            if (bName.startsWith(queryLower) && !aName.startsWith(queryLower)) return 1;

            return aName.localeCompare(bName);
        });

        const resultsForCurrentPage = this.currentSearchResults.slice(0, this.itemsPerPage);
        this.displayResults(resultsForCurrentPage);
        this.showSearchResultsCount(this.currentSearchResults.length, query);

        this.elements.loadMoreBtn?.classList.toggle("hidden", this.currentSearchResults.length <= this.itemsPerPage);
    }

    showSearchResultsCount(count, query) {
        document.querySelector(".search-results-info")?.remove();

        const resultsInfo = document.createElement("div");
        resultsInfo.className = "search-results-info";
        resultsInfo.style.cssText = `
            margin: 20px 0; padding: 10px; background: #f8f9fa;
            border-radius: 5px; color: #666; font-size: 14px;
        `;
        resultsInfo.innerHTML = `Found <strong>${count}</strong> lab test${count !== 1 ? "s" : ""} for "<strong>${query}</strong>"`;

        this.elements.resultsContainer.parentNode.insertBefore(resultsInfo, this.elements.resultsContainer);
    }

    loadMoreResults() {
        this.currentPage++;
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const nextPageResults = this.currentSearchResults.slice(startIndex, endIndex);
        this.appendResults(nextPageResults);

        if (endIndex >= this.currentSearchResults.length) {
            this.elements.loadMoreBtn?.classList.add("hidden");
        }
    }

    displayResults(labTests) {
        this.clearResults();
        if (labTests.length === 0) {
            this.showError("No lab tests found. Try a different search term.");
            return;
        }

        labTests.forEach((labTest) => {
            this.elements.resultsContainer.appendChild(this.createLabTestCard(labTest));
        });
    }

    appendResults(labTests) {
        labTests.forEach((labTest) => {
            this.elements.resultsContainer.appendChild(this.createLabTestCard(labTest));
        });
    }

    createLabTestCard(labTest) {
        const card = document.createElement("div");
        card.className = "result-card";

        const isInCart = this.cart.some(item => item.id === labTest.id);
        
        const getButtonConfig = () => {
            if (!isUserLoggedIn) {
                return {
                    content: '<i class="ri-user-line"></i> Login to Add',
                    class: "add-to-cart-btn login-required",
                    disabled: false
                };
            }
            if (isInCart) {
                return {
                    content: '<i class="ri-check-line"></i> Added',
                    class: "add-to-cart-btn in-cart",
                    disabled: true
                };
            }
            return {
                content: '<i class="ri-shopping-cart-line"></i> Add to Cart',
                class: "add-to-cart-btn",
                disabled: false
            };
        };

        const buttonConfig = getButtonConfig();

        card.innerHTML = `
            <div class="details" data-aos="fade" data-aos-duration="1000">
                <h3 class="name">${labTest.name}</h3>
                <div class="lab-sample"><strong>Sample:</strong> ${labTest.sample || "N/A"}</div>
                <div class="price"><strong>Price:</strong> ₹${labTest.price || "N/A"}/-</div>
                <div class="lab-desc">${labTest.description || "No description available"}</div>
            </div>
            <div class="button-container">
                <button class="${buttonConfig.class}" data-test-id="${labTest.id}" ${buttonConfig.disabled ? "disabled" : ""}>
                    ${buttonConfig.content}
                </button>
            </div>
        `;

        setTimeout(() => AOS.refreshHard(), 0);

        card.querySelector(".add-to-cart-btn").addEventListener("click", (e) => {
            e.stopPropagation();

            if (!isUserLoggedIn) {
                sessionStorage.setItem("returnUrl", window.location.href);
                window.location.href = "../user/login.php";
                return;
            }

            if (!isInCart && !this.cart.some(item => item.id === labTest.id)) {
                this.addToCart(labTest);
            }
        });

        return card;
    }

    addToCart(labTest) {
        if (!isUserLoggedIn) {
            sessionStorage.setItem("returnUrl", window.location.href);
            window.location.href = "../user/login.php";
            return;
        }

        if (this.cart.some(item => item.id === labTest.id)) {
            this.showNotification(`${labTest.name} is already in your cart!`);
            return;
        }

        this.cart.push({
            id: labTest.id,
            name: labTest.name,
            price: parseFloat(labTest.price) || 0,
            sample: labTest.sample,
            description: labTest.description,
            quantity: 1,
        });

        this.updateCartDisplay();
        this.showNotification(`${labTest.name} added to cart!`);

        // Update button state
        const btn = document.querySelector(`[data-test-id="${labTest.id}"]`);
        if (btn) {
            btn.innerHTML = '<i class="ri-check-line"></i> Added';
            btn.className = "add-to-cart-btn in-cart";
            btn.style.cssText = "background: #374151; color: #ffffff;";
            btn.disabled = true;
        }
    }

    removeFromCart(testId) {
        if (!isUserLoggedIn) {
            window.location.href = "../user/login.php";
            return;
        }

        this.cart = this.cart.filter(item => item.id !== testId);
        this.updateCartDisplay();
        this.updateCartModal();
        this.updateButtonStates();
        this.showNotification("Test removed from cart");
    }

    clearCart() {
        if (!isUserLoggedIn) {
            window.location.href = "../user/login.php";
            return;
        }

        this.cart = [];
        this.updateCartDisplay();
        this.updateCartModal();
        this.updateButtonStates();
        this.showNotification("Cart cleared");
        
        // Close cart modal if it's open and cart is empty
        if (this.elements.cartModal && this.elements.cartModal.style.display === "block") {
            this.closeModal("cart");
        }
    }

    updateButtonStates() {
        document.querySelectorAll(".add-to-cart-btn").forEach((btn) => {
            const testId = parseInt(btn.getAttribute("data-test-id"));
            const isInCart = this.cart.some(item => item.id === testId);

            if (isInCart) {
                btn.innerHTML = '<i class="ri-check-line"></i> Added';
                btn.className = "add-to-cart-btn in-cart";
                btn.disabled = true;
            } else {
                btn.innerHTML = '<i class="ri-shopping-cart-line"></i> Add to Cart';
                btn.className = "add-to-cart-btn";
                btn.style.cssText = "background: #3B82F6; color: white;";
                btn.disabled = false;
            }
        });
    }

    calculateCartTotal() {
        return this.cart.reduce((total, item) => total + item.price, 0);
    }

    updateCartDisplay() {
        if (!isUserLoggedIn) {
            this.elements.cartSummary?.classList.add("hidden");
            return;
        }

        const totalItems = this.cart.length;
        const total = this.calculateCartTotal();

        if (this.elements.cartCount) this.elements.cartCount.textContent = totalItems;
        if (this.elements.cartTotal) this.elements.cartTotal.textContent = total.toFixed(0);
        if (this.elements.modalCartTotal) this.elements.modalCartTotal.textContent = total.toFixed(0);

        this.elements.cartSummary?.classList.toggle("hidden", totalItems === 0);
    }

    updateCartModal() {
        if (!this.elements.cartItemsContainer) return;

        if (this.cart.length === 0) {
            
            this.closeModal("cart");
        
            return;
        }

        this.elements.cartItemsContainer.innerHTML = this.cart
            .map(item => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p class="cart-item-sample">Sample: ${item.sample}</p>
                        <p class="cart-item-description">${item.description}</p>
                    </div>
                    <div class="cart-item-controls">
                        <div class="cart-item-price">₹${item.price.toFixed(0)}</div>
                        <button class="remove-btn" onclick="labManager.removeFromCart(${item.id})">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            `).join("");
    }

    proceedToCheckout() {
        if (!isUserLoggedIn) {
            alert("Please login to book lab tests.");
            window.location.href = "../user/login.php";
            return;
        }

        if (this.cart.length === 0) {
            return;
        }

        const cartSummaryHtml = `
            <div class="checkout-summary">
                <h3>Order Summary</h3>
                <div class="checkout-items">
                    ${this.cart.map(item => `
                        <div class="checkout-item">
                            <span class="item-name">${item.name}</span>
                            <span class="item-price">₹${item.price.toFixed(0)}</span>
                        </div>
                    `).join("")}
                </div>
                <div class="checkout-total">
                    <strong>Total: ₹${this.calculateCartTotal().toFixed(0)}</strong>
                </div>
            </div>
        `;

        this.elements.modalTestInfo.innerHTML = cartSummaryHtml;
        this.openModal("booking");
    }

    openModal(modalType) {
        if (!isUserLoggedIn) {
            window.location.href = "../user/login.php";
            return;
        }

        if (modalType === "cart") {
            this.updateCartModal();
            this.elements.cartModal.style.display = "block";
        } else {
            this.elements.modal.style.display = "block";
        }
        document.body.style.overflow = "hidden";
    }

    closeModal(modalType) {
        if (modalType === "cart") {
            this.elements.cartModal.style.display = "none";
        } else {
            this.elements.modal.style.display = "none";
        }
        document.body.style.overflow = "";
    }

    async handleFormSubmit(e) {
        e.preventDefault();

        if (!isUserLoggedIn) {
            window.location.href = "../user/login.php";
            return;
        }

        const formData = new FormData(this.elements.bookingForm);
        formData.append("action", "save_booking");
        formData.append("cart", JSON.stringify(this.cart));
        formData.append("totalAmount", this.calculateCartTotal().toFixed(2));

        const submitBtn = this.elements.bookingForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;
        submitBtn.textContent = "Processing...";
        submitBtn.disabled = true;

        try {
            const response = await fetch("api.php", {
                method: "POST",
                body: formData,
            });
            const data = await response.json();

            if (data.status === "success") {
                this.showThankYouMessage();
                this.elements.bookingForm.reset();
                this.closeModal("booking");
                this.cart = [];
                this.updateCartDisplay();
                this.updateButtonStates();
            } else {
                alert("Error: " + data.message);
            }
        } catch (error) {
            alert("Error submitting form: " + error.message);
        } finally {
            submitBtn.textContent = originalBtnText;
            submitBtn.disabled = false;
        }
    }

    showThankYouMessage() {
        const thankYouOverlay = document.createElement("div");
        thankYouOverlay.className = "thank-you-overlay";
        thankYouOverlay.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8); display: flex; align-items: center;
            justify-content: center; z-index: 10001; animation: fadeIn 0.3s ease-out;
        `;

        const thankYouModal = document.createElement("div");
        thankYouModal.className = "thank-you-modal";
        thankYouModal.style.cssText = `
            background: white; border-radius: 15px; text-align: center;
            padding: 40px 30px; max-width: 400px; width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: scaleIn 0.3s ease-out;
        `;

        thankYouModal.innerHTML = `
            <div style="margin-bottom: 20px;">
                <i class="ri-checkbox-circle-fill" style="font-size: 60px; color: #4CAF50;"></i>
            </div>
            <h2 style="color: #333; margin-bottom: 15px; font-size: 24px;">Order Submitted</h2>
            <h3 style="color: #4CAF50; margin-bottom: 20px; font-size: 20px;">Thank You!</h3>
            <p style="color: #666; margin-bottom: 25px; line-height: 1.5;">
                Your lab test booking has been successfully submitted. 
                We will contact you shortly to confirm your appointment.
            </p>
        `;

        thankYouOverlay.appendChild(thankYouModal);
        document.body.appendChild(thankYouOverlay);
        document.body.style.overflow = "hidden";

        // Add CSS animations if not already present
        if (!document.querySelector("#thank-you-styles")) {
            const styles = document.createElement("style");
            styles.id = "thank-you-styles";
            styles.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes scaleIn {
                    from { transform: scale(0.7); opacity: 0; }
                    to { transform: scale(1); opacity: 1; }
                }
                .thank-you-modal button:hover {
                    background: #45a049 !important;
                    transform: translateY(-2px);
                }
            `;
            document.head.appendChild(styles);
        }

        // Auto close after 3 seconds
        setTimeout(() => {
            if (thankYouOverlay.parentNode) {
                thankYouOverlay.remove();
                document.body.style.overflow = "";
            }
        }, 3000);
    }

    showNotification(message) {
        const notification = document.createElement("div");
        notification.className = "notification";
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed; top: 20px; right: 20px; background: #4CAF50;
            color: white; padding: 12px 20px; border-radius: 5px; z-index: 10000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); animation: slideIn 0.3s ease-out;
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    showLoading() {
        this.elements.loadingIndicator?.classList.remove("hidden");
    }

    hideLoading() {
        this.elements.loadingIndicator?.classList.add("hidden");
    }

    showError(message) {
        this.elements.errorMessage.innerHTML = `
            <div style="padding: 20px; text-align: center;">
                <i class="ri-error-warning-line" style="font-size: 24px; color: #e74c3c; margin-bottom: 10px;"></i>
                <p style="margin: 0; color: #666;">${message}</p>
            </div>
        `;
        this.elements.errorMessage?.classList.remove("hidden");
    }

    clearError() {
        if (this.elements.errorMessage) {
            this.elements.errorMessage.textContent = "";
            this.elements.errorMessage.classList.add("hidden");
        }
    }

    clearResults() {
        if (this.elements.resultsContainer) {
            this.elements.resultsContainer.innerHTML = "";
        }
    }
}

// Initialize the application
let labManager;
document.addEventListener("DOMContentLoaded", () => {
    labManager = new LabTestManager();
});