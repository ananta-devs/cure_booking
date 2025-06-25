<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search your medicines by typing brand</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
</head>

<body>
    <?php
    session_start();
    $isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);
    include '../include/header.php';
    include '../styles.php';
    ?>

    <script>
        const USER_LOGGED_IN = <?php echo json_encode($isLoggedIn); ?>;
    </script>

    <section class="hero" data-aos="fade-up">
        <div class="container">
            <h1>Your Trusted Partner in Health & Wellness.</h1>
            <p>Find all your prescription medications, supplements, and healthcare essentials at competitive prices.</p>
            <form class="search-container">
                <input type="text" id="search-bar" placeholder="Search for medicines..." />
                <button type="submit" aria-label="Search"><i class="ri-search-line"></i></button>
            </form>
        </div>
    </section>

    <div class="container">
        <div id="cart-summary" class="cart-summary hidden">
            <div class="cart-header">
                <h3>Cart (<span id="cart-count">0</span> items)</h3>
                <div class="cart-actions">
                    <button id="view-cart-btn" class="btn secondary-btn">View Cart</button>
                    <button id="checkout-btn" class="btn primary-btn">Checkout ₹<span id="cart-total">0</span></button>
                </div>
            </div>
        </div>

        <br>
        <div id="loading" class="loading-indicator hidden">Loading medicines data...</div>
        <div id="error-message" class="error-message hidden"></div>
        <div id="results-container" class="results-container"></div>

        <div class="pagination-container">
            <button id="load-more-btn" class="hidden">Load More</button>
        </div>
    </div>

    <!-- Cart Modal -->
    <div id="cartModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="cart">&times;</span>
            <h2>Your Cart</h2>
            <div id="cart-items-container"></div>
            <div class="cart-footer">
                <div class="cart-total-section">
                    <h3>Total: ₹<span id="modal-cart-total">0</span></h3>
                </div>
                <div class="cart-buttons">
                    <button id="clear-cart-btn" class="btn secondary-btn">Clear Cart</button>
                    <button id="proceed-checkout-btn" class="btn primary-btn">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="order">&times;</span>
            <h2>Place Order</h2>
            <div id="order-summary"></div>
            <form id="orderForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="address">Delivery Address</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                <button type="submit" class="btn primary-btn">Confirm Order</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cache DOM elements
            const $ = id => document.getElementById(id);
            const elements = {
                searchInput: $('search-bar'),
                searchForm: document.querySelector('.search-container'),
                loadMoreBtn: $('load-more-btn'),
                loadingIndicator: $('loading'),
                errorMessage: $('error-message'),
                resultsContainer: $('results-container'),
                cartModal: $('cartModal'),
                orderModal: $('orderModal'),
                cartSummary: $('cart-summary'),
                cartCount: $('cart-count'),
                cartTotal: $('cart-total'),
                modalCartTotal: $('modal-cart-total'),
                cartItemsContainer: $('cart-items-container'),
                orderSummary: $('order-summary'),
                orderForm: $('orderForm')
            };

            const API_ENDPOINT = 'medicine_api.php';
            let allMedicines = [];
            let currentSearchResults = [];
            let currentPage = 1;
            const itemsPerPage = 9;
            let cart = [];

            // Initialize
            loadMedicinesData();
            updateCartDisplay();
            setupEventListeners();

            function setupEventListeners() {
                // Search
                elements.searchForm?.addEventListener('submit', e => {
                    e.preventDefault();
                    initiateSearch();
                });

                elements.searchInput.addEventListener('keypress', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        initiateSearch();
                    }
                });

                // Cart
                $('view-cart-btn').addEventListener('click', () => checkLoginStatus() && openModal('cart'));
                $('checkout-btn').addEventListener('click', () => checkLoginStatus() && openModal('order'));
                $('clear-cart-btn').addEventListener('click', clearCart);
                $('proceed-checkout-btn').addEventListener('click', () => {
                    closeModal('cart');
                    openModal('order');
                });

                // Modals
                document.querySelectorAll('.modal .close').forEach(btn => {
                    btn.addEventListener('click', () => closeModal(btn.getAttribute('data-modal')));
                });

                window.addEventListener('click', event => {
                    if (event.target === elements.cartModal) closeModal('cart');
                    else if (event.target === elements.orderModal) closeModal('order');
                });

                // Order form
                elements.orderForm?.addEventListener('submit', handleOrderSubmit);
                elements.loadMoreBtn.addEventListener('click', loadMoreResults);
            }

            function checkLoginStatus() {
                if (!USER_LOGGED_IN) {
                    window.location.href = '../user/login.php';
                    return false;
                }
                return true;
            }

            function openModal(type) {
                const modal = type === 'cart' ? elements.cartModal : elements.orderModal;
                if (type === 'cart') updateCartModal();
                else updateOrderSummary();

                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }

            function closeModal(type) {
                const modal = type === 'cart' ? elements.cartModal : elements.orderModal;
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }

            function handleOrderSubmit(e) {
                e.preventDefault();

                if (!checkLoginStatus() || cart.length === 0) {
                    if (cart.length === 0) alert('Your cart is empty!');
                    return;
                }

                const formData = new FormData(elements.orderForm);
                formData.append('cart', JSON.stringify(cart.map(item => ({
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity
                }))));
                formData.append('totalAmount', calculateCartTotal());

                const submitBtn = elements.orderForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Processing...';
                submitBtn.disabled = true;

                fetch(API_ENDPOINT, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('Your medicine order has been placed successfully!');
                            elements.orderForm.reset();
                            clearCart();
                            closeModal('order');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => alert('Error submitting form: ' + error.message))
                    .finally(() => {
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    });
            }

            // Cart functions
            function addToCart(medicine) {
                if (!checkLoginStatus()) return;

                const existingItem = cart.find(item => item.id === medicine.id);
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    cart.push({
                        id: medicine.id,
                        name: medicine.name,
                        price: parseFloat(medicine.price) || 0,
                        manufacturer: medicine.manufacturer_name,
                        composition: getCompositionText(medicine),
                        quantity: 1
                    });
                }

                updateCartDisplay();
                updateAllCartButtons();
                showNotification(`${medicine.name} added to cart!`);
            }

            function removeFromCart(medicineId) {
                cart = cart.filter(item => item.id !== medicineId);
                updateCartDisplay();
                updateCartModal();
                updateAllCartButtons();
            }

            function updateQuantity(medicineId, newQuantity) {
                const item = cart.find(item => item.id === medicineId);
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
            }

            function calculateCartTotal() {
                return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            }

            function updateCartDisplay() {
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                const total = calculateCartTotal();

                elements.cartCount.textContent = totalItems;
                elements.cartTotal.textContent = total.toFixed(0);
                elements.modalCartTotal.textContent = total.toFixed(0);

                elements.cartSummary.classList.toggle('hidden', totalItems === 0 || !USER_LOGGED_IN);
            }

            function updateAllCartButtons() {
                document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                    const medicineId = button.getAttribute('data-med-id');
                    updateCartButtonState(button, medicineId);
                });
            }

            function updateCartButtonState(button, medicineId) {
                if (!USER_LOGGED_IN) {
                    button.innerHTML = '<i class="ri-user-line"></i> Login to Add';
                    button.classList.remove('added-to-cart');
                    return;
                }

                const isInCart = cart.some(item => item.id == medicineId);
                button.innerHTML = isInCart ?
                    '<i class="ri-check-line"></i> Added' :
                    '<i class="ri-shopping-cart-line"></i> Add to Cart';
                button.classList.toggle('added-to-cart', isInCart);
            }

            function updateCartModal() {
                if (cart.length === 0) {
                    elements.cartItemsContainer.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                    return;
                }

                // Inject dynamic HTML with AOS attributes
                elements.cartItemsContainer.innerHTML = cart.map(item => `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h4>${item.name}</h4>
                            <p class="cart-item-manufacturer">${item.manufacturer}</p>
                            <p class="cart-item-composition">${item.composition}</p>
                        </div>
                        <div class="cart-item-controls">
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                                <span class="quantity">${item.quantity}</span>
                                <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                            </div>
                            <div class="cart-item-price">₹${(item.price * item.quantity).toFixed(0)}</div>
                                <button class="remove-btn" onclick="removeFromCart(${item.id})">&times;</button>
                        </div>
                    </div>
                `).join('');

            }

            function updateOrderSummary() {
                if (cart.length === 0) {
                    elements.orderSummary.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                    return;
                }

                const itemsList = cart.map(item => `
                    <div class="order-item">
                        <span class="order-item-name">${item.name} x${item.quantity}</span>
                        <span class="order-item-price">₹${(item.price * item.quantity).toFixed(0)}</span>
                    </div>
                `).join('');

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
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.textContent = message;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            }

            // Make functions globally available
            window.updateQuantity = updateQuantity;
            window.removeFromCart = removeFromCart;

            // Medicine data and search functions
            async function loadMedicinesData() {
                showLoading();
                clearError();

                try {
                    const response = await fetch(API_ENDPOINT);
                    if (!response.ok) throw new Error('Failed to load medicines data');

                    const data = await response.json();
                    if (data.error) throw new Error(data.message || 'Error loading data');

                    allMedicines = data.medicines || [];
                    displayRandomMedicines();
                } catch (error) {
                    showError('Error loading medicines data: ' + error.message);
                } finally {
                    hideLoading();
                }
            }

            function displayRandomMedicines() {
                const shuffled = [...allMedicines].sort(() => 0.5 - Math.random());
                const randomMedicines = shuffled.slice(0, itemsPerPage);
                displayResults(randomMedicines, 0); // Don't show count for random medicines
                elements.loadMoreBtn.classList.add('hidden');
            }

            function initiateSearch() {
                const query = elements.searchInput.value.trim();
                if (query.length === 0) {
                    displayRandomMedicines();
                    return;
                }
                if (query.length < 1) return;

                currentPage = 1;
                performSearch(query);
            }

            async function performSearch(query) {
                showLoading();
                clearError();

                try {
                    const response = await fetch(`${API_ENDPOINT}?query=${encodeURIComponent(query)}`);
                    if (!response.ok) throw new Error('Search request failed');

                    const data = await response.json();
                    if (data.error) throw new Error(data.message || 'Error during search');

                    currentSearchResults = data.medicines || [];

                    if (currentSearchResults.length === 0) {
                        showError('No medicines found. Try a different search term.');
                        clearResults();
                        elements.loadMoreBtn.classList.add('hidden');
                        return;
                    }

                    const resultsForPage = currentSearchResults.slice(0, itemsPerPage);
                    displayResults(resultsForPage, currentSearchResults.length, query);

                    elements.loadMoreBtn.classList.toggle('hidden', currentSearchResults.length <= itemsPerPage);
                } catch (error) {
                    showError('Error performing search: ' + error.message);
                } finally {
                    hideLoading();
                }
            }

            function loadMoreResults() {
                currentPage++;
                const startIndex = (currentPage - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;
                const nextPageResults = currentSearchResults.slice(startIndex, endIndex);

                nextPageResults.forEach(medicine => {
                    elements.resultsContainer.appendChild(createMedicineCard(medicine));
                });

                if (endIndex >= currentSearchResults.length) {
                    elements.loadMoreBtn.classList.add('hidden');
                }
            }

            function displayResults(medicines, totalCount = 0, query = '') {
                clearResults();
                if (medicines.length === 0) {
                    showError('No medicines found. Try a different search term.');
                    return;
                }

                // Only show count if there's a search query
                if (query.trim()) {
                    displaySearchResultsCount(totalCount || medicines.length, query);
                }

                medicines.forEach(medicine => {
                    elements.resultsContainer.appendChild(createMedicineCard(medicine));
                });
            }

            function displaySearchResultsCount(count, query = '') {
                const resultsCount = document.createElement('div');
                resultsCount.id = 'results-count';
                resultsCount.className = 'results-count';
                resultsCount.style.cssText = `
                    margin: 20px 0;
                    padding: 10px;
                    background: #f8f9fa;
                    border-radius: 5px;
                    color: #666;
                    font-size: 14px;
                `;

                resultsCount.innerHTML = query ?
                    `<p>Found <strong>${count}</strong> result${count !== 1 ? 's' : ''} for "<em>${query}</em>"</p>` :
                    `<p>Showing <strong>${count}</strong> medicine${count !== 1 ? 's' : ''}</p>`;

                elements.resultsContainer.parentNode.insertBefore(resultsCount, elements.resultsContainer);
            }

            function getCompositionText(medicine) {
                let composition = '';
                if (medicine.composition1) composition += medicine.composition1;
                if (medicine.composition2) {
                    if (composition) composition += ', ';
                    composition += medicine.composition2;
                }
                return composition || 'Composition not available';
            }

            function createMedicineCard(medicine) {
                const card = document.createElement('div');
                card.className = 'result-card';

                const addToCartBtn = document.createElement('button');
                addToCartBtn.className = 'add-to-cart-btn';
                addToCartBtn.setAttribute('data-med-id', medicine.id || '');
                updateCartButtonState(addToCartBtn, medicine.id);
                addToCartBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    addToCart(medicine);
                });

                card.innerHTML = `
                    <div class="details" data-aos="fade" data-aos-duration="1000">
                        <h3 class="name">${medicine.name}</h3>
                        <p class="medicine-manufacturer"><strong>Manufacturer:&nbsp</strong> ${medicine.manufacturer_name || 'N/A'}</p>
                        <p class="medicine-pack"><strong>Pack:</strong> ${medicine.pack_size || 'N/A'}</p>
                        <p class="price"><strong>Price:&nbsp</strong> ₹${medicine.price || 'N/A'}/-</p>
                        <p class="medicine-composition"><strong>Composition:</strong> ${getCompositionText(medicine)}</p>
                        ${medicine.label ? `<span class="label">${medicine.label}</span>` : ''}
                    </div>
                    <div class="button-container" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>
                `;

                // Important: Refresh AOS after DOM update
                setTimeout(() => {
                    AOS.refreshHard();
                }, 0);


                card.querySelector('.button-container').appendChild(addToCartBtn);
                return card;
            }

            // Utility functions
            function showLoading() {
                elements.loadingIndicator.classList.remove('hidden');
            }

            function hideLoading() {
                elements.loadingIndicator.classList.add('hidden');
            }

            function showError(msg) {
                elements.errorMessage.textContent = msg;
                elements.errorMessage.classList.remove('hidden');
            }

            function clearError() {
                elements.errorMessage.textContent = '';
                elements.errorMessage.classList.add('hidden');
            }

            function clearResults() {
                elements.resultsContainer.innerHTML = '';
                const existingCount = document.getElementById('results-count');
                if (existingCount) existingCount.remove();
            }
        });
    </script>
    <!---AOS Library --->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 1000,
        });
    </script>
    <?php
        include "../include/footer.php";
    ?>
</body>

</html>