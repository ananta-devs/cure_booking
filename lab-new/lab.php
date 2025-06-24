<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Lab Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
</head>

<body>
    <?php
    session_start();
    include '../include/header.php';
    include '../styles.php';

    // Check if user is logged in and get user info
    $isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['username']) || isset($_SESSION['logged_in']);
    $userInfo = $isLoggedIn ? [
        'id' => $_SESSION['user_id'] ?? 0,
        'name' => $_SESSION['name'] ?? $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? $_SESSION['username'] ?? '',
        'username' => $_SESSION['user_name'] ?? ''
    ] : [];
    ?>

    <section class="hero">
        <div class="container" data-aos="fade-up">
            <h1 >Book Your Lab Tests Online in Minutes.</h1>
            <p>Schedule diagnostic tests at your convenience, and get accurate results.</p>
            <form class="search-container">
                <input type="text" id="search-bar" placeholder="Search for lab tests..." />
                <button type="submit" aria-label="Search"><i class="ri-search-line"></i></button>
            </form>
        </div>
    </section>

    <div class="container">
        <!-- Cart Summary -->
        <div id="cart-summary" class="cart-summary hidden">
            <div class="cart-header">
                <h3>Cart (<span id="cart-count">0</span> tests)</h3>
                <div class="cart-actions">
                    <button id="view-cart-btn" class="btn secondary-btn">View Cart</button>
                    <button id="checkout-btn" class="btn primary-btn">Checkout ₹<span id="cart-total">0</span></button>
                </div>
            </div>
        </div>

        <div id="loading" class="loading-indicator hidden">Loading lab test data...</div>
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

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Book Test</h2>
            <div id="modalTestInfo"></div>
            <form id="bookingForm" action="api.php" method="post">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required value="<?= $isLoggedIn ? htmlspecialchars($userInfo['name']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= $isLoggedIn ? htmlspecialchars($userInfo['email']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="address">Address for Sample Collection</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                <div class="form-group">
                    <label for="clinic_id">Choose a clinic </label>
                    <select name="clinic_id" id="clinic_id">
                        <option value="">Choose</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="time">Time Slot</label>
                        <select id="time" name="time" required>
                            <option value="">Select Time</option>
                            <option value="07:00-09:00">07:00-09:00 AM</option>
                            <option value="09:00-11:00">09:00-11:00 AM</option>
                            <option value="11:00-13:00">11:00-01:00 PM</option>
                            <option value="13:00-15:00">01:00-03:00 PM</option>
                            <option value="15:00-17:00">03:00-05:00 PM</option>
                            <option value="17:00-19:00">05:00-07:00 PM</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn primary-btn">Confirm Booking</button>
            </form>
        </div>
    </div>

    <script>
        const isUserLoggedIn = <?= json_encode($isLoggedIn) ?>;
        const userInfo = <?= json_encode($userInfo) ?>;

        class LabTestManager {
            constructor() {
                this.allLabTests = [];
                this.currentSearchResults = [];
                this.currentPage = 1;
                this.itemsPerPage = 9;
                this.cart = [];
                this.clinics = [];

                this.initElements();
                this.init();
            }

            initElements() {
                this.elements = {
                    searchInput: document.getElementById('search-bar'),
                    loadMoreBtn: document.getElementById('load-more-btn'),
                    loadingIndicator: document.getElementById('loading'),
                    errorMessage: document.getElementById('error-message'),
                    resultsContainer: document.getElementById('results-container'),
                    modal: document.getElementById('bookingModal'),
                    cartModal: document.getElementById('cartModal'),
                    modalTestInfo: document.getElementById('modalTestInfo'),
                    bookingForm: document.getElementById('bookingForm'),
                    searchForm: document.querySelector('.search-container'),
                    clinicSelect: document.getElementById('clinic_id'),
                    cartSummary: document.getElementById('cart-summary'),
                    cartCount: document.getElementById('cart-count'),
                    cartTotal: document.getElementById('cart-total'),
                    modalCartTotal: document.getElementById('modal-cart-total'),
                    cartItemsContainer: document.getElementById('cart-items-container'),
                    dateInput: document.getElementById('date')
                };
            }

            async init() {
                this.bindEvents();
                this.setMinDate();
                await Promise.all([
                    this.loadLabTestsData(),
                    this.loadClinics()
                ]);
                if (isUserLoggedIn) this.updateCartDisplay();
            }

            bindEvents() {
                const {
                    searchForm,
                    searchInput,
                    loadMoreBtn,
                    bookingForm
                } = this.elements;

                // Search events
                searchForm?.addEventListener('submit', e => {
                    e.preventDefault();
                    this.initiateSearch();
                });
                searchInput?.addEventListener('keypress', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.initiateSearch();
                    }
                });

                // Cart events (only if logged in)
                if (isUserLoggedIn) {
                    document.getElementById('view-cart-btn')?.addEventListener('click', () => this.openModal('cart'));
                    document.getElementById('checkout-btn')?.addEventListener('click', () => this.proceedToCheckout());
                    document.getElementById('clear-cart-btn')?.addEventListener('click', () => this.clearCart());
                    document.getElementById('proceed-checkout-btn')?.addEventListener('click', () => {
                        this.closeModal('cart');
                        this.proceedToCheckout();
                    });
                }

                // Modal events
                document.querySelectorAll('.modal .close').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const modalType = btn.getAttribute('data-modal');
                        this.closeModal(modalType === 'cart' ? 'cart' : 'booking');
                        if (modalType !== 'cart') bookingForm?.reset();
                    });
                });

                window.addEventListener('click', event => {
                    if (event.target === this.elements.modal) {
                        bookingForm?.reset();
                        this.closeModal('booking');
                    } else if (event.target === this.elements.cartModal) {
                        this.closeModal('cart');
                    }
                });

                loadMoreBtn?.addEventListener('click', () => this.loadMoreResults());

                // Form submission
                bookingForm?.addEventListener('submit', e => this.handleFormSubmit(e));
            }

            setMinDate() {
                if (this.elements.dateInput) {
                    const today = new Date().toISOString().split('T')[0];
                    this.elements.dateInput.min = today;
                }
            }

            async loadClinics() {
                try {
                    const response = await fetch('api.php?action=get_clinics');
                    const data = await response.json();

                    if (data.status === 'success') {
                        this.clinics = data.clinics || [];
                        this.populateClinicSelect();
                    }
                } catch (error) {
                    console.error('Error loading clinics:', error);
                }
            }

            populateClinicSelect() {
                const {
                    clinicSelect
                } = this.elements;
                if (!clinicSelect) return;

                // Clear existing options except the first one
                while (clinicSelect.children.length > 1) {
                    clinicSelect.removeChild(clinicSelect.lastChild);
                }

                this.clinics.forEach(clinic => {
                    const option = document.createElement('option');
                    option.value = clinic.id;
                    option.textContent = `${clinic.name} - ${clinic.location}`;
                    clinicSelect.appendChild(option);
                });
            }

            async loadLabTestsData() {
                this.showLoading();
                this.clearError();

                try {
                    const response = await fetch('api.php?action=get_tests&limit=1000');
                    const data = await response.json();

                    if (data.status === 'error') throw new Error(data.message);

                    this.allLabTests = data.tests || [];

                    if (this.allLabTests.length === 0) {
                        this.showError('No lab tests data available. Please check your database.');
                        return;
                    }

                    this.displayRandomLabTests();

                } catch (error) {
                    console.error('Error loading lab tests:', error);
                    this.showError('Error loading lab tests data. Please refresh the page and try again.');
                } finally {
                    this.hideLoading();
                }
            }

            displayRandomLabTests() {
                const shuffled = [...this.allLabTests].sort(() => 0.5 - Math.random());
                const randomLabTests = shuffled.slice(0, this.itemsPerPage);
                this.clearError();
                this.displayResults(randomLabTests);
                this.elements.loadMoreBtn?.classList.add('hidden');
            }

            initiateSearch() {
                const query = this.elements.searchInput.value.trim();

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

                this.currentSearchResults = this.allLabTests.filter(labTest => {
                    const fields = [
                        labTest.name?.toLowerCase() || '',
                        labTest.sample?.toLowerCase() || '',
                        labTest.description?.toLowerCase() || ''
                    ];

                    return fields.some(field => field.includes(searchTerms)) ||
                        searchTerms.split(' ').some(word =>
                            word.length > 2 && fields.some(field => field.includes(word))
                        );
                });

                this.clearError();

                if (this.currentSearchResults.length === 0) {
                    this.showError(`No lab tests found for "${query}". Try different keywords like "blood", "urine", "sugar", etc.`);
                    this.clearResults();
                    this.elements.loadMoreBtn?.classList.add('hidden');
                    return;
                }

                // Sort by relevance
                this.currentSearchResults.sort((a, b) => {
                    const aName = (a.name || '').toLowerCase();
                    const bName = (b.name || '').toLowerCase();
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

                this.elements.loadMoreBtn?.classList.toggle('hidden', this.currentSearchResults.length <= this.itemsPerPage);
            }

            showSearchResultsCount(count, query) {
                const existingInfo = document.querySelector('.search-results-info');
                existingInfo?.remove();

                const resultsInfo = document.createElement('div');
                resultsInfo.className = 'search-results-info';
                resultsInfo.style.cssText = `
                    margin: 20px 0; padding: 10px; background: #f8f9fa;
                    border-radius: 5px; color: #666; font-size: 14px;
                `;
                resultsInfo.innerHTML = `Found <strong>${count}</strong> lab test${count !== 1 ? 's' : ''} for "<strong>${query}</strong>"`;

                this.elements.resultsContainer.parentNode.insertBefore(resultsInfo, this.elements.resultsContainer);
            }

            loadMoreResults() {
                this.currentPage++;
                const startIndex = (this.currentPage - 1) * this.itemsPerPage;
                const endIndex = startIndex + this.itemsPerPage;
                const nextPageResults = this.currentSearchResults.slice(startIndex, endIndex);
                this.appendResults(nextPageResults);

                if (endIndex >= this.currentSearchResults.length) {
                    this.elements.loadMoreBtn?.classList.add('hidden');
                }
            }

            displayResults(labTests) {
                this.clearResults();
                if (labTests.length === 0) {
                    this.showError('No lab tests found. Try a different search term.');
                    return;
                }

                labTests.forEach(labTest => {
                    const labTestCard = this.createLabTestCard(labTest);
                    this.elements.resultsContainer.appendChild(labTestCard);
                });
            }

            appendResults(labTests) {
                labTests.forEach(labTest => {
                    const labTestCard = this.createLabTestCard(labTest);
                    this.elements.resultsContainer.appendChild(labTestCard);
                });
            }

            createLabTestCard(labTest) {
                const card = document.createElement('div');
                card.className = 'result-card';

                const isInCart = this.cart.some(item => item.id === labTest.id);

                card.innerHTML = `
                    <div class="details" data-aos="fade" data-aos-duration="1000">
                        <h3 class="name">${labTest.name}</h3>
                        <div class="lab-sample"><strong>Sample:</strong> ${labTest.sample || 'N/A'}</div>
                        <div class="price"><strong>Price:</strong> ₹${labTest.price || 'N/A'}/-</div>
                        <div class="lab-desc">${labTest.description || 'No description available'}</div>
                    </div>
                    <div class="button-container">
                        <button class="add-to-cart-btn ${isInCart ? 'in-cart' : ''}" 
                                data-test-id="${labTest.id}" ${isInCart ? 'disabled' : ''}>
                            <i class="ri-${isInCart ? 'check' : 'shopping-cart'}-line"></i> 
                            ${isInCart ? 'Added' : 'Add to Cart'}
                        </button>
                    </div>
                `;
                // Important: Refresh AOS after DOM update
                setTimeout(() => {
                    AOS.refreshHard();
                }, 0);

                const addBtn = card.querySelector('.add-to-cart-btn');
                addBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    if (!isInCart && !this.cart.some(item => item.id === labTest.id)) {
                        this.addToCart(labTest);
                    }
                });

                return card;
            }

            addToCart(labTest) {
                if (!isUserLoggedIn) {
                    sessionStorage.setItem('returnUrl', window.location.href);
                    window.location.href = '../user/login.php';
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
                    quantity: 1
                });

                this.updateCartDisplay();
                this.showNotification(`${labTest.name} added to cart!`);

                // Update button state
                const btn = document.querySelector(`[data-test-id="${labTest.id}"]`);
                if (btn) {
                    btn.innerHTML = '<i class="ri-check-line"></i> Added';
                    btn.className = 'add-to-cart-btn in-cart';
                    btn.disabled = true;
                }
            }

            removeFromCart(testId) {
                if (!isUserLoggedIn) {
                    window.location.href = '../user/login.php';
                    return;
                }

                this.cart = this.cart.filter(item => item.id !== testId);
                this.updateCartDisplay();
                this.updateCartModal();
                this.updateButtonStates(); // Add this line
                this.showNotification('Test removed from cart');
            }

            clearCart() {
                if (!isUserLoggedIn) {
                    window.location.href = '../user/login.php';
                    return;
                }

                this.cart = [];
                this.updateCartDisplay();
                this.updateCartModal();
                this.updateButtonStates(); // Add this line
                this.showNotification('Cart cleared');
            }

            updateButtonStates() {
                document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
                    const testId = parseInt(btn.getAttribute('data-test-id'));
                    const isInCart = this.cart.some(item => item.id === testId);

                    if (isInCart) {
                        btn.innerHTML = '<i class="ri-check-line"></i> Added';
                        btn.className = 'add-to-cart-btn in-cart';
                        btn.disabled = true;
                    } else {
                        btn.innerHTML = '<i class="ri-shopping-cart-line"></i> Add to Cart';
                        btn.className = 'add-to-cart-btn';
                        btn.disabled = false;
                    }
                });
            }

            calculateCartTotal() {
                return this.cart.reduce((total, item) => total + item.price, 0);
            }

            updateCartDisplay() {
                if (!isUserLoggedIn) {
                    this.elements.cartSummary?.classList.add('hidden');
                    return;
                }

                const totalItems = this.cart.length;
                const total = this.calculateCartTotal();

                if (this.elements.cartCount) this.elements.cartCount.textContent = totalItems;
                if (this.elements.cartTotal) this.elements.cartTotal.textContent = total.toFixed(0);
                if (this.elements.modalCartTotal) this.elements.modalCartTotal.textContent = total.toFixed(0);

                this.elements.cartSummary?.classList.toggle('hidden', totalItems === 0);
            }

            updateCartModal() {
                if (!this.elements.cartItemsContainer) return;

                if (this.cart.length === 0) {
                    this.elements.cartItemsContainer.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                    return;
                }

                this.elements.cartItemsContainer.innerHTML = this.cart.map(item => `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h4>${item.name}</h4>
                            <p class="cart-item-sample">Sample: ${item.sample}</p>
                            <p class="cart-item-description">${item.description}</p>
                        </div>
                        <div class="cart-item-controls">
                            <div class="cart-item-price">₹${item.price.toFixed(0)}</div>
                            <button class="remove-btn" onclick="labManager.removeFromCart(${item.id})">&times;</button>
                        </div>
                    </div>
                `).join('');
            }

            proceedToCheckout() {
                if (!isUserLoggedIn) {
                    alert('Please login to book lab tests.');
                    window.location.href = '../user/login.php';
                    return;
                }

                if (this.cart.length === 0) {
                    alert('Your cart is empty!');
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
                            `).join('')}
                        </div>
                        <div class="checkout-total">
                            <strong>Total: ₹${this.calculateCartTotal().toFixed(0)}</strong>
                        </div>
                    </div>
                `;

                this.elements.modalTestInfo.innerHTML = cartSummaryHtml;
                this.openModal('booking');
            }

            openModal(modalType) {
                if (!isUserLoggedIn) {
                    window.location.href = '../user/login.php';
                    return;
                }

                if (modalType === 'cart') {
                    this.updateCartModal();
                    this.elements.cartModal.style.display = 'block';
                } else {
                    this.elements.modal.style.display = 'block';
                }
                document.body.style.overflow = 'hidden';
            }

            closeModal(modalType) {
                if (modalType === 'cart') {
                    this.elements.cartModal.style.display = 'none';
                } else {
                    this.elements.modal.style.display = 'none';
                }
                document.body.style.overflow = '';
            }

            async handleFormSubmit(e) {
                e.preventDefault();

                if (!isUserLoggedIn) {
                    window.location.href = '../user/login.php';
                    return;
                }

                if (this.cart.length === 0) {
                    alert('Your cart is empty!');
                    return;
                }

                const formData = new FormData(this.elements.bookingForm);
                formData.append('action', 'save_booking');
                formData.append('cart', JSON.stringify(this.cart));
                formData.append('totalAmount', this.calculateCartTotal().toFixed(2));

                const submitBtn = this.elements.bookingForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.textContent;
                submitBtn.textContent = 'Processing...';
                submitBtn.disabled = true;

                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        alert(data.message);
                        this.elements.bookingForm.reset();
                        this.closeModal('booking');
                        this.cart = []; // Clear cart
                        this.updateCartDisplay();
                        this.updateButtonStates(); // Update button states
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    alert('Error submitting form: ' + error.message);
                } finally {
                    submitBtn.textContent = originalBtnText;
                    submitBtn.disabled = false;
                }
            }

            showNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'notification';
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
                this.elements.loadingIndicator?.classList.remove('hidden');
            }

            hideLoading() {
                this.elements.loadingIndicator?.classList.add('hidden');
            }

            showError(message) {
                this.elements.errorMessage.innerHTML = `
                    <div style="padding: 20px; text-align: center;">
                        <i class="ri-error-warning-line" style="font-size: 24px; color: #e74c3c; margin-bottom: 10px;"></i>
                        <p style="margin: 0; color: #666;">${message}</p>
                    </div>
                `;
                this.elements.errorMessage?.classList.remove('hidden');
            }

            clearError() {
                if (this.elements.errorMessage) {
                    this.elements.errorMessage.textContent = '';
                    this.elements.errorMessage.classList.add('hidden');
                }
            }

            clearResults() {
                if (this.elements.resultsContainer) {
                    this.elements.resultsContainer.innerHTML = '';
                }
            }
        }

        // Initialize the application
        let labManager;
        document.addEventListener('DOMContentLoaded', () => {
            labManager = new LabTestManager();
        });
    </script>

    <!---AOS Library --->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
<?php
include '../include/footer.php';
?>

</html>