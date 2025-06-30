document.addEventListener("DOMContentLoaded", function () {
    // Cache DOM elements
    const $ = (id) => document.getElementById(id);
    const elements = {
        searchInput: $("search-bar"),
        searchForm: document.querySelector(".search-container"),
        loadMoreBtn: $("load-more-btn"),
        loadingIndicator: $("loading"),
        errorMessage: $("error-message"),
        resultsContainer: $("results-container"),
        cartModal: $("cartModal"),
        orderModal: $("orderModal"),
        cartSummary: $("cart-summary"),
        cartCount: $("cart-count"),
        cartTotal: $("cart-total"),
        modalCartTotal: $("modal-cart-total"),
        cartItemsContainer: $("cart-items-container"),
        orderSummary: $("order-summary"),
        orderForm: $("orderForm"),
    };

    const API_ENDPOINT = "medicine_api.php";
    let allMedicines = [];
    let currentSearchResults = [];
    let currentPage = 1;
    const itemsPerPage = 9;
    let cart = [];
    let lastSearchQuery = "";
    let noResultsDisplayed = false;

    // Initialize
    loadMedicinesData();
    updateCartDisplay();
    setupEventListeners();

    function setupEventListeners() {
        // Search events
        elements.searchForm?.addEventListener("submit", handleSearchSubmit);
        elements.searchInput?.addEventListener(
            "keypress",
            handleSearchKeypress
        );
        elements.searchInput?.addEventListener(
            "input",
            handleSearchInputChange
        );

        // Cart buttons
        const cartButtons = [
            "view-cart-btn",
            "checkout-btn",
            "clear-cart-btn",
            "proceed-checkout-btn",
        ];
        const cartActions = [
            () => openModal("cart"),
            () => openModal("order"),
            clearCart,
            () => {
                closeModal("cart");
                openModal("order");
            },
        ];

        cartButtons.forEach((id, index) => {
            const btn = $(id);
            if (btn) {
                const action =
                    index < 2
                        ? () => checkLoginStatus() && cartActions[index]()
                        : cartActions[index];
                btn.addEventListener("click", action);
            }
        });

        // Modal events
        document.querySelectorAll(".modal .close").forEach((btn) => {
            btn.addEventListener("click", () =>
                closeModal(btn.getAttribute("data-modal"))
            );
        });

        window.addEventListener("click", handleModalOutsideClick);
        elements.orderForm?.addEventListener("submit", handleOrderSubmit);
        elements.loadMoreBtn?.addEventListener("click", loadMoreResults);
    }

    function handleSearchSubmit(e) {
        e.preventDefault();
        initiateSearch();
    }

    function handleSearchKeypress(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            initiateSearch();
        }
    }

    function handleSearchInputChange() {
        const currentQuery = elements.searchInput?.value.trim() || "";

        // If search input is empty, display random medicines
        if (currentQuery === "") {
            displayRandomMedicines();
            lastSearchQuery = "";
            noResultsDisplayed = false;
            clearError();

            // Hide load more button when showing random medicines
            if (elements.loadMoreBtn) {
                elements.loadMoreBtn.classList.add("hidden");
            }
            return;
        }

        // If user starts typing after a no-results state, clear the no-results flag
        if (noResultsDisplayed) {
            noResultsDisplayed = false;
        }
    }

    function handleModalOutsideClick(event) {
        const modals = [
            { element: elements.cartModal, type: "cart" },
            { element: elements.orderModal, type: "order" },
        ];

        modals.forEach(({ element, type }) => {
            if (event.target === element) closeModal(type);
        });
    }

    function refreshToRandomMedicines() {
        clearError();
        clearResults();
        displayRandomMedicines();
        lastSearchQuery = "";
        noResultsDisplayed = false;
        showLoadingBriefly();
    }

    function showLoadingBriefly() {
        showLoading();
        setTimeout(hideLoading, 300);
    }

    function checkLoginStatus() {
        if (typeof USER_LOGGED_IN === "undefined" || !USER_LOGGED_IN) {
            window.location.href = "../user/login.php";
            return false;
        }
        return true;
    }

    function openModal(type) {
        const modals = {
            cart: elements.cartModal,
            order: elements.orderModal,
        };

        const modal = modals[type];
        if (!modal) return;

        if (type === "cart") updateCartModal();
        else if (type === "order") updateOrderSummary();

        modal.style.display = "block";
        document.body.style.overflow = "hidden";
    }

    function closeModal(type) {
        const modals = {
            cart: elements.cartModal,
            order: elements.orderModal,
        };

        const modal = modals[type];
        if (!modal) return;

        modal.style.display = "none";
        document.body.style.overflow = "";
    }

    function handleOrderSubmit(e) {
        e.preventDefault();

        if (!checkLoginStatus() || cart.length === 0) {
            if (cart.length === 0) alert("Your cart is empty!");
            return;
        }

        const formData = new FormData(elements.orderForm);
        const requiredFields = ["name", "phone", "email", "address"];

        if (!requiredFields.every((field) => formData.get(field))) {
            alert("Please fill in all required fields");
            return;
        }

        const orderData = {
            action: "place_order",
            name: formData.get("name"),
            phone: formData.get("phone"),
            email: formData.get("email"),
            address: formData.get("address"),
            cart: JSON.stringify(
                cart.map((item) => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity,
                    manufacturer: item.manufacturer,
                    composition: item.composition,
                }))
            ),
            totalAmount: calculateCartTotal(),
        };

        submitOrder(orderData);
    }

    function submitOrder(orderData) {
        const submitBtn = elements.orderForm.querySelector(
            'button[type="submit"]'
        );
        const originalText = submitBtn.textContent;

        submitBtn.textContent = "Processing...";
        submitBtn.disabled = true;

        fetch(API_ENDPOINT, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams(orderData),
        })
            .then((response) => {
                if (!response.ok)
                    throw new Error(`HTTP error! status: ${response.status}`);
                return response.text();
            })
            .then((text) => {
                const data = JSON.parse(text);
                if (data.status === "success") {
                    showThankYouOverlay();
                    elements.orderForm.reset();
                    closeModal("order");
                    clearCart();
                } else {
                    alert(
                        "Error: " + (data.message || "Unknown error occurred")
                    );
                }
            })
            .catch((error) => {
                alert("Error submitting order: " + error.message);
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
    }

    function showThankYouOverlay() {
        document.querySelector(".thank-you-overlay")?.remove();

        if (!document.querySelector("#dynamic-animations")) {
            const style = document.createElement("style");
            style.id = "dynamic-animations";
            style.textContent = `
                @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                @keyframes scaleIn { from { transform: scale(0.7); opacity: 0; } to { transform: scale(1); opacity: 1; } }
            `;
            document.head.appendChild(style);
        }

        const overlay = document.createElement("div");
        overlay.className = "thank-you-overlay";
        overlay.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8); display: flex; align-items: center;
            justify-content: center; z-index: 10001; animation: fadeIn 0.3s ease-out;
        `;

        const modal = document.createElement("div");
        modal.style.cssText = `
            background: white; border-radius: 15px; text-align: center;
            padding: 40px 30px; max-width: 400px; width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); animation: scaleIn 0.3s ease-out;
        `;

        modal.innerHTML = `
            <div style="margin-bottom: 20px;">
                <i class="ri-checkbox-circle-fill" style="font-size: 60px; color: #4CAF50;"></i>
            </div>
            <h2 style="color: #333; margin-bottom: 15px; font-size: 24px;">Order Submitted</h2>
            <h3 style="color: #4CAF50; margin-bottom: 20px; font-size: 20px;">Thank You!</h3>
            <p style="color: #666; margin-bottom: 25px; line-height: 1.5;">
                Your medicine order has been placed successfully! We will contact you shortly to confirm your order.
            </p>
            
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        document.body.style.overflow = "hidden";

        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.remove();
                document.body.style.overflow = "";
            }
        }, 3000);
    }

    // Cart functions
    function addToCart(medicine) {
        if (!checkLoginStatus()) return;

        const existingItem = cart.find((item) => item.id === medicine.id);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: medicine.id,
                name: medicine.name,
                price: parseFloat(medicine.price) || 0,
                manufacturer: medicine.manufacturer_name,
                composition: getCompositionText(medicine),
                quantity: 1,
            });
        }

        updateCartDisplay();
        updateAllCartButtons();
        showNotification(`${medicine.name} added to cart!`);
    }

    function removeFromCart(medicineId) {
        cart = cart.filter((item) => item.id !== medicineId);
        updateCartDisplay();
        updateCartModal();
        updateAllCartButtons();

        // Check if cart is empty after removal
        if (cart.length === 0) {
            showNotification("Cart cleared!");
            // Auto-close cart modal if it's open and cart is empty
            if (
                elements.cartModal &&
                elements.cartModal.style.display === "block"
            ) {
                closeModal("cart");
            }
        }
    }

    function updateQuantity(medicineId, newQuantity) {
        const item = cart.find((item) => item.id === medicineId);
        if (!item) return;

        if (newQuantity <= 0) {
            removeFromCart(medicineId);
        } else {
            item.quantity = newQuantity;
            updateCartDisplay();
            updateCartModal();
            updateAllCartButtons();
        }
    }

    function clearCart() {
        cart = [];
        updateCartDisplay();
        updateCartModal();
        updateAllCartButtons();

        // Show notification that cart was cleared
        showNotification("Cart cleared!");

        // Auto-close cart modal if it's open and cart is empty
        if (
            elements.cartModal &&
            elements.cartModal.style.display === "block"
        ) {
            closeModal("cart");
        }
    }

    function calculateCartTotal() {
        return cart.reduce(
            (total, item) => total + item.price * item.quantity,
            0
        );
    }

    function updateCartDisplay() {
        if (
            !elements.cartCount ||
            !elements.cartTotal ||
            !elements.modalCartTotal
        )
            return;

        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const total = calculateCartTotal();

        elements.cartCount.textContent = totalItems;
        elements.cartTotal.textContent = total.toFixed(0);
        elements.modalCartTotal.textContent = total.toFixed(0);

        if (elements.cartSummary) {
            const shouldShow =
                totalItems > 0 &&
                typeof USER_LOGGED_IN !== "undefined" &&
                USER_LOGGED_IN;
            elements.cartSummary.classList.toggle("hidden", !shouldShow);
        }
    }

    function updateAllCartButtons() {
        document.querySelectorAll(".add-to-cart-btn").forEach((button) => {
            const medicineId = button.getAttribute("data-med-id");
            updateCartButtonState(button, medicineId);
        });
    }

    function updateCartButtonState(button, medicineId) {
        if (typeof USER_LOGGED_IN === "undefined" || !USER_LOGGED_IN) {
            button.innerHTML = '<i class="ri-user-line"></i> Login to Add';
            button.classList.remove("added-to-cart");
            return;
        }

        const isInCart = cart.some((item) => item.id == medicineId);
        const config = isInCart
            ? {
                  html: '<i class="ri-check-line"></i> Added',
                  class: "add-to-cart-btn in-cart",
                  bg: "#374151",
                  disabled: true,
              }
            : {
                  html: '<i class="ri-shopping-cart-line"></i> Add to Cart',
                  class: "add-to-cart-btn",
                  bg: "#3B82F6",
                  disabled: false,
              };

        button.innerHTML = config.html;
        button.className = config.class;
        button.style.background = config.bg;
        button.style.color = "white";
        button.disabled = config.disabled;
    }

    function updateCartModal() {
        if (!elements.cartItemsContainer) return;

        if (cart.length === 0) {
            elements.cartItemsContainer.innerHTML =
                '<p class="empty-cart">Your cart is empty</p>';
            return;
        }

        elements.cartItemsContainer.innerHTML = cart
            .map(
                (item) => `
            <div class="cart-item">
                <div class="cart-item-info">
                    <h4>${item.name}</h4>
                    <p class="cart-item-manufacturer">${item.manufacturer}</p>
                    <p class="cart-item-composition">${item.composition}</p>
                </div>
                <div class="cart-item-controls">
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="updateQuantity(${
                            item.id
                        }, ${item.quantity - 1})">-</button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn" onclick="updateQuantity(${
                            item.id
                        }, ${item.quantity + 1})">+</button>
                    </div>
                    <div class="cart-item-price">₹${(
                        item.price * item.quantity
                    ).toFixed(0)}</div>
                    <button class="remove-btn" onclick="removeFromCart(${
                        item.id
                    })"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        `
            )
            .join("");
    }

    function updateOrderSummary() {
        if (!elements.orderSummary) return;

        if (cart.length === 0) {
            elements.orderSummary.innerHTML =
                '<p class="empty-cart">Your cart is empty</p>';
            return;
        }

        const itemsList = cart
            .map(
                (item) => `
            <div class="order-item">
                <span class="order-item-name">${item.name} x${
                    item.quantity
                }</span>
                <span class="order-item-price">₹${(
                    item.price * item.quantity
                ).toFixed(0)}</span>
            </div>
        `
            )
            .join("");

        elements.orderSummary.innerHTML = `
            <div class="order-summary-container">
                <h3>Order Summary</h3>
                ${itemsList}
                <div class="order-total">
                    <strong>Total: ₹${calculateCartTotal().toFixed(0)}</strong>
                </div>
            </div>
        `;
    }

    function showNotification(message) {
        document.querySelector(".notification")?.remove();

        const notification = document.createElement("div");
        notification.className = "notification";
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed; top: 20px; right: 20px; background: #4CAF50;
            color: white; padding: 15px 20px; border-radius: 5px;
            z-index: 10000; animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    // Make functions globally available
    window.updateQuantity = updateQuantity;
    window.removeFromCart = removeFromCart;
    window.addToCart = addToCart;

    // Medicine data and search functions
    async function loadMedicinesData() {
        showLoading();
        clearError();

        try {
            const response = await fetch(API_ENDPOINT);
            if (!response.ok) throw new Error("Failed to load medicines data");

            const data = await response.json();
            if (data.error)
                throw new Error(data.message || "Error loading data");

            allMedicines = data.medicines || [];
            displayRandomMedicines();
        } catch (error) {
            showError("Error loading medicines data: " + error.message);
        } finally {
            hideLoading();
        }
    }

    function displayRandomMedicines() {
        const shuffled = [...allMedicines].sort(() => 0.5 - Math.random());
        const randomMedicines = shuffled.slice(0, itemsPerPage);
        displayResults(randomMedicines, 0);
        elements.loadMoreBtn?.classList.add("hidden");
        noResultsDisplayed = false;
    }

    function initiateSearch() {
        const query = elements.searchInput?.value.trim() || "";
        lastSearchQuery = query;

        if (query.length === 0) {
            refreshToRandomMedicines();
            return;
        }

        currentPage = 1;
        performSearch(query);
    }

    async function performSearch(query) {
        showLoading();
        clearError();

        try {
            const response = await fetch(
                `${API_ENDPOINT}?query=${encodeURIComponent(query)}`
            );
            if (!response.ok) throw new Error("Search request failed");

            const data = await response.json();
            if (data.error)
                throw new Error(data.message || "Error during search");

            currentSearchResults = data.medicines || [];

            if (currentSearchResults.length === 0) {
                showError("No medicines found. Try a different search term.");
                clearResults();
                noResultsDisplayed = true;
                elements.loadMoreBtn?.classList.add("hidden");
                return;
            }

            noResultsDisplayed = false;
            const resultsForPage = currentSearchResults.slice(0, itemsPerPage);
            displayResults(resultsForPage, currentSearchResults.length, query);

            elements.loadMoreBtn?.classList.toggle(
                "hidden",
                currentSearchResults.length <= itemsPerPage
            );
        } catch (error) {
            showError("Error performing search: " + error.message);
            noResultsDisplayed = true;
        } finally {
            hideLoading();
        }
    }

    function loadMoreResults() {
        currentPage++;
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const nextPageResults = currentSearchResults.slice(
            startIndex,
            endIndex
        );

        nextPageResults.forEach((medicine) => {
            elements.resultsContainer?.appendChild(
                createMedicineCard(medicine)
            );
        });

        if (endIndex >= currentSearchResults.length) {
            elements.loadMoreBtn?.classList.add("hidden");
        }
    }

    function displayResults(medicines, totalCount = 0, query = "") {
        clearResults();
        if (medicines.length === 0) {
            showError("No medicines found. Try a different search term.");
            noResultsDisplayed = true;
            return;
        }

        if (query.trim()) {
            displaySearchResultsCount(totalCount || medicines.length, query);
        }

        medicines.forEach((medicine) => {
            elements.resultsContainer?.appendChild(
                createMedicineCard(medicine)
            );
        });
    }

    function displaySearchResultsCount(count, query = "") {
        const resultsCount = document.createElement("div");
        resultsCount.id = "results-count";
        resultsCount.className = "results-count";
        resultsCount.style.cssText = `
            margin: 20px 0; padding: 10px; background: #f8f9fa;
            border-radius: 5px; color: #666; font-size: 14px;
        `;

        resultsCount.innerHTML = query
            ? `<p>Found <strong>${count}</strong> result${
                  count !== 1 ? "s" : ""
              } for "<em>${query}</em>"</p>`
            : `<p>Showing <strong>${count}</strong> medicine${
                  count !== 1 ? "s" : ""
              }</p>`;

        if (elements.resultsContainer?.parentNode) {
            elements.resultsContainer.parentNode.insertBefore(
                resultsCount,
                elements.resultsContainer
            );
        }
    }

    function getCompositionText(medicine) {
        const compositions = [
            medicine.composition1,
            medicine.composition2,
        ].filter(Boolean);
        return compositions.length
            ? compositions.join(", ")
            : "Composition not available";
    }

    function createMedicineCard(medicine) {
        const card = document.createElement("div");
        card.className = "result-card";

        const addToCartBtn = document.createElement("button");
        addToCartBtn.className = "add-to-cart-btn";
        addToCartBtn.setAttribute("data-med-id", medicine.id || "");
        updateCartButtonState(addToCartBtn, medicine.id);
        addToCartBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            addToCart(medicine);
        });

        card.innerHTML = `
            <div class="details" data-aos="fade" data-aos-duration="1000">
                <h3 class="name">${medicine.name}</h3>
                <p class="medicine-manufacturer"><strong>Manufacturer:&nbsp</strong> ${
                    medicine.manufacturer_name || "N/A"
                }</p>
                <p class="medicine-pack"><strong>Pack:</strong> ${
                    medicine.pack_size || "N/A"
                }</p>
                <p class="price"><strong>Price:&nbsp</strong> ₹${
                    medicine.price || "N/A"
                }/-</p>
                <p class="medicine-composition"><strong>Composition:</strong> ${getCompositionText(
                    medicine
                )}</p>
                ${
                    medicine.label
                        ? `<span class="label">${medicine.label}</span>`
                        : ""
                }
            </div>
            <div class="button-container" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>
        `;

        setTimeout(() => {
            if (typeof AOS !== "undefined") AOS.refreshHard();
        }, 0);

        card.querySelector(".button-container")?.appendChild(addToCartBtn);
        return card;
    }

    // Utility functions
    function showLoading() {
        elements.loadingIndicator?.classList.remove("hidden");
    }

    function hideLoading() {
        elements.loadingIndicator?.classList.add("hidden");
    }

    function showError(msg) {
        if (elements.errorMessage) {
            elements.errorMessage.textContent = msg;
            elements.errorMessage.classList.remove("hidden");
        }
    }

    function clearError() {
        if (elements.errorMessage) {
            elements.errorMessage.textContent = "";
            elements.errorMessage.classList.add("hidden");
        }
    }

    function clearResults() {
        if (elements.resultsContainer) {
            elements.resultsContainer.innerHTML = "";
        }
        document.getElementById("results-count")?.remove();
    }
});
