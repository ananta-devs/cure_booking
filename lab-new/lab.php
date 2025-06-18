<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CureBooking | Lab Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
        session_start();
        // Add this after session_start() in lab.php
        echo "<script>console.log('Session data:', " . json_encode($_SESSION) . ");</script>";
        include '../include/header.php';
        include '../styles.php';
        
        // Check if user is logged in
        $isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['username']) || isset($_SESSION['logged_in']);
        
        // Get user information for JavaScript
        $userInfo = [];
        if ($isLoggedIn) {
            $userInfo = [
                'id' => $_SESSION['user_id'] ?? 0,
                'name' => $_SESSION['name'] ?? $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? '',
                'email' => $_SESSION['user_email'] ?? $_SESSION['username'] ?? '',
                'username' => $_SESSION['user_name'] ?? ''
            ];
        }
    ?>

    <section class="hero">
        <div class="container">
            <h1>Book Your Lab Tests Online in Minutes.</h1>
            <p>Schedule diagnostic tests at your convenience, and get accurate results.</p>
            <form class="search-container">
                <input type="text" id="search-bar" placeholder="Search for lab tests..."/>
                <button type="submit" aria-label="Search"><i class="ri-search-line" id="search-icon"></i></button>
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
            <div id="cart-items-container">
                <!-- Cart items will be populated by JavaScript -->
            </div>
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
                    <input type="text" id="name" name="name" required value="<?php echo $isLoggedIn ? htmlspecialchars($userInfo['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo $isLoggedIn ? htmlspecialchars($userInfo['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="address">Address for Sample Collection</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                <div class="form-group">
                    <label for="clinic_id">Choose a clinic (Optional - Leave blank for home collection)</label>
                    <select name="clinic_id" id="clinic_id">
                        <option value="">Home Collection (No clinic)</option>
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
        // Pass PHP data to JavaScript
        const isUserLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
        const userInfo = <?php echo json_encode($userInfo); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            // DOM elements
            const searchInput = document.getElementById('search-bar');
            const loadMoreBtn = document.getElementById('load-more-btn');
            const loadingIndicator = document.getElementById('loading');
            const errorMessage = document.getElementById('error-message');
            const resultsContainer = document.getElementById('results-container');
            const modal = document.getElementById('bookingModal');
            const cartModal = document.getElementById('cartModal');
            const modalTestInfo = document.getElementById('modalTestInfo');
            const bookingForm = document.getElementById('bookingForm');
            const searchForm = document.querySelector('.search-container');
            const clinicSelect = document.getElementById('clinic_id');
            
            // Cart elements
            const cartSummary = document.getElementById('cart-summary');
            const cartCount = document.getElementById('cart-count');
            const cartTotal = document.getElementById('cart-total');
            const modalCartTotal = document.getElementById('modal-cart-total');
            const cartItemsContainer = document.getElementById('cart-items-container');
            const viewCartBtn = document.getElementById('view-cart-btn');
            const checkoutBtn = document.getElementById('checkout-btn');
            const clearCartBtn = document.getElementById('clear-cart-btn');
            const proceedCheckoutBtn = document.getElementById('proceed-checkout-btn');
            
            // Variables
            let allLabTests = [];
            let currentSearchResults = [];
            let currentPage = 1;
            const itemsPerPage = 9;
            let cart = [];
            let clinics = [];
            
            // Initialize
            loadLabTestsData();
            loadClinics();
            if (isUserLoggedIn) updateCartDisplay();
            
            // Event listeners
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    initiateSearch();
                });
            }
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    initiateSearch();
                }
            });

            // Cart event listeners
            if (isUserLoggedIn) {
                if (viewCartBtn) viewCartBtn.addEventListener('click', () => openModal('cart'));
                if (checkoutBtn) checkoutBtn.addEventListener('click', proceedToCheckout);
                if (clearCartBtn) clearCartBtn.addEventListener('click', clearCart);
                if (proceedCheckoutBtn) proceedCheckoutBtn.addEventListener('click', () => {
                    closeModal('cart');
                    proceedToCheckout();
                });
            }

            // Modal event listeners
            document.querySelectorAll('.modal .close').forEach(btn => {
                btn.addEventListener('click', function() {
                    const modalType = this.getAttribute('data-modal');
                    closeModal(modalType === 'cart' ? 'cart' : 'booking');
                    if (modalType !== 'cart') bookingForm.reset();
                });
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    bookingForm.reset();
                    closeModal('booking');
                } else if (event.target === cartModal) {
                    closeModal('cart');
                }
            });
            
            loadMoreBtn.addEventListener('click', loadMoreResults);

            // Form submission
            if (bookingForm) {
                bookingForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!isUserLoggedIn) {
                        window.location.href = '../user/login.php';
                        return;
                    }
                    
                    if (cart.length === 0) {
                        alert('Your cart is empty!');
                        return;
                    }
                    
                    const formData = new FormData(bookingForm);
                    formData.append('action', 'save_booking');
                    formData.append('cart', JSON.stringify(cart));
                    formData.append('totalAmount', calculateCartTotal().toFixed(2));
                    
                    const submitBtn = bookingForm.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.textContent;
                    submitBtn.textContent = 'Processing...';
                    submitBtn.disabled = true;
                    
                    fetch('api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        submitBtn.textContent = originalBtnText;
                        submitBtn.disabled = false;
                        
                        if (data.status === 'success') {
                            alert(data.message);
                            bookingForm.reset();
                            closeModal('booking');
                            clearCart();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        submitBtn.textContent = originalBtnText;
                        submitBtn.disabled = false;
                        alert('Error submitting form: ' + error.message);
                    });
                });
            }

            // Set minimum date to today
            const dateInput = document.getElementById('date');
            if (dateInput) {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                dateInput.min = `${yyyy}-${mm}-${dd}`;
            }

            // Functions
            async function loadClinics() {
                try {
                    const response = await fetch('api.php?action=get_clinics');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        clinics = data.clinics || [];
                        populateClinicSelect();
                    } else {
                        console.error('Error loading clinics:', data.message);
                    }
                } catch (error) {
                    console.error('Error loading clinics:', error);
                }
            }

            function populateClinicSelect() {
                if (!clinicSelect) return;
                
                // Clear existing options except the first one (Home Collection)
                while (clinicSelect.children.length > 1) {
                    clinicSelect.removeChild(clinicSelect.lastChild);
                }
                
                // Add clinic options
                clinics.forEach(clinic => {
                    const option = document.createElement('option');
                    option.value = clinic.id;
                    option.textContent = `${clinic.name} - ${clinic.location}`;
                    clinicSelect.appendChild(option);
                });
            }

            function proceedToCheckout() {
                if (!isUserLoggedIn) {
                    alert('Please login to book lab tests.');
                    window.location.href = '../user/login.php';
                    return;
                }
                
                if (cart.length === 0) {
                    alert('Your cart is empty!');
                    return;
                }
                
                const cartSummaryHtml = `
                    <div class="checkout-summary">
                        <h3>Order Summary</h3>
                        <div class="checkout-items">
                            ${cart.map(item => `
                                <div class="checkout-item">
                                    <span class="item-name">${item.name}</span>
                                    <span class="item-price">₹${item.price.toFixed(0)}</span>
                                </div>
                            `).join('')}
                        </div>
                        <div class="checkout-total">
                            <strong>Total: ₹${calculateCartTotal().toFixed(0)}</strong>
                        </div>
                    </div>
                `;
                
                modalTestInfo.innerHTML = cartSummaryHtml;
                openModal('booking');
            }

            function closeModal(modalType) {
                if (modalType === 'cart') {
                    cartModal.style.display = 'none';
                } else {
                    modal.style.display = 'none';
                }
                document.body.style.overflow = '';
            }
            
            function openModal(modalType) {
                if (modalType === 'cart') {
                    if (!isUserLoggedIn) {
                        window.location.href = '../user/login.php';
                        return;
                    }
                    updateCartModal();
                    cartModal.style.display = 'block';
                } else {
                    if (!isUserLoggedIn) {
                        window.location.href = '../user/login.php';
                        return;
                    }
                    modal.style.display = 'block';
                }
                document.body.style.overflow = 'hidden';
            }

            function addToCart(labTest) {
                if (!isUserLoggedIn) {
                    sessionStorage.setItem('returnUrl', window.location.href);
                    window.location.href = '../user/login.php';
                    return;
                }
                
                const existingItem = cart.find(item => item.id === labTest.id);
                if (existingItem) {
                    showNotification(`${labTest.name} is already in your cart!`);
                    return;
                }
                
                cart.push({
                    id: labTest.id,
                    name: labTest.name,
                    price: parseFloat(labTest.price) || 0,
                    sample: labTest.sample,
                    description: labTest.description,
                    quantity: 1
                });
                
                updateCartDisplay();
                showNotification(`${labTest.name} added to cart!`);
            }
            
            function removeFromCart(testId) {
                if (!isUserLoggedIn) {
                    window.location.href = '../user/login.php';
                    return;
                }
                
                cart = cart.filter(item => item.id !== testId);
                updateCartDisplay();
                updateCartModal();
                showNotification('Test removed from cart');
            }
            
            function clearCart() {
                if (!isUserLoggedIn) {
                    window.location.href = '../user/login.php';
                    return;
                }
                
                cart = [];
                updateCartDisplay();
                updateCartModal();
                showNotification('Cart cleared');
            }
            
            function calculateCartTotal() {
                return cart.reduce((total, item) => total + item.price, 0);
            }
            
            function updateCartDisplay() {
                if (!isUserLoggedIn) {
                    if (cartSummary) cartSummary.classList.add('hidden');
                    return;
                }
                
                const totalItems = cart.length;
                const total = calculateCartTotal();
                
                if (cartCount) cartCount.textContent = totalItems;
                if (cartTotal) cartTotal.textContent = total.toFixed(0);
                if (modalCartTotal) modalCartTotal.textContent = total.toFixed(0);
                
                if (totalItems > 0) {
                    if (cartSummary) cartSummary.classList.remove('hidden');
                } else {
                    if (cartSummary) cartSummary.classList.add('hidden');
                }
            }
            
            function updateCartModal() {
                if (!cartItemsContainer) return;
                
                if (cart.length === 0) {
                    cartItemsContainer.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                    return;
                }
                
                cartItemsContainer.innerHTML = cart.map(item => `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h4>${item.name}</h4>
                            <p class="cart-item-sample">Sample: ${item.sample}</p>
                            <p class="cart-item-description">${item.description}</p>
                        </div>
                        <div class="cart-item-controls">
                            <div class="cart-item-price">₹${item.price.toFixed(0)}</div>
                            <button class="remove-btn" onclick="removeFromCart(${item.id})">&times;</button>
                        </div>
                    </div>
                `).join('');
            }
            
            function showNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.textContent = message;
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #4CAF50;
                    color: white;
                    padding: 12px 20px;
                    border-radius: 5px;
                    z-index: 10000;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    animation: slideIn 0.3s ease-out;
                `;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
            
            // Make functions global for onclick handlers
            window.removeFromCart = removeFromCart;
            window.addToCart = addToCart;

            async function loadLabTestsData() {
                showLoading();
                clearError();
                
                try {
                    const response = await fetch('api.php?action=get_tests&limit=1000');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }   
                    
                    const data = await response.json();
                    
                    if (data.status === 'error') {
                        throw new Error(data.message);
                    }
                    
                    allLabTests = data.tests || [];
                    
                    if (allLabTests.length === 0) {
                        showError('No lab tests data available. Please check your database.');
                        return;
                    }
                    
                    displayRandomLabTests();
                    
                } catch (error) {
                    console.error('Error loading lab tests:', error);
                    showError('Error loading lab tests data. Please refresh the page and try again.');
                } finally {
                    hideLoading();
                }
            }

            function displayRandomLabTests() {
                const shuffled = [...allLabTests].sort(() => 0.5 - Math.random());
                const randomLabTests = shuffled.slice(0, itemsPerPage);
                clearError();
                displayResults(randomLabTests);
                loadMoreBtn.classList.add('hidden');
            }

            function initiateSearch() {
                const query = searchInput.value.trim();
                
                if (query.length < 1) {
                    clearError();
                    displayRandomLabTests();
                    return;
                }
                
                currentPage = 1;
                performSearch(query);
            }

            function performSearch(query) {
                query = query.toLowerCase();
                
                currentSearchResults = allLabTests.filter(labTest => {
                    const labName = (labTest.name || '').toLowerCase();
                    const labSample = (labTest.sample || '').toLowerCase();
                    const labDescription = (labTest.description || '').toLowerCase();
                    
                    return labName.includes(query) || 
                        labSample.includes(query) || 
                        labDescription.includes(query) ||
                        query.split(' ').some(word => 
                            word.length > 2 && (
                                labName.includes(word) || 
                                labSample.includes(word) || 
                                labDescription.includes(word)
                            )
                        );
                });
                
                clearError();
                
                if (currentSearchResults.length === 0) {
                    showError(`No lab tests found for "${searchInput.value}". Try different keywords like "blood", "urine", "sugar", etc.`);
                    clearResults();
                    loadMoreBtn.classList.add('hidden');
                    return;
                }
                
                // Sort results by relevance
                currentSearchResults.sort((a, b) => {
                    const aName = (a.name || '').toLowerCase();
                    const bName = (b.name || '').toLowerCase();
                    const queryLower = query.toLowerCase();
                    
                    if (aName === queryLower && bName !== queryLower) return -1;
                    if (bName === queryLower && aName !== queryLower) return 1;
                    if (aName.startsWith(queryLower) && !bName.startsWith(queryLower)) return -1;
                    if (bName.startsWith(queryLower) && !aName.startsWith(queryLower)) return 1;
                    
                    return aName.localeCompare(bName);
                });
                
                const resultsForCurrentPage = currentSearchResults.slice(0, itemsPerPage);
                displayResults(resultsForCurrentPage);
                showSearchResultsCount(currentSearchResults.length, searchInput.value);
                
                if(currentSearchResults.length > itemsPerPage) {
                    loadMoreBtn.classList.remove('hidden');
                } else {
                    loadMoreBtn.classList.add('hidden');
                }
            }

            function showSearchResultsCount(count, query) {
                const existingInfo = document.querySelector('.search-results-info');
                if (existingInfo) existingInfo.remove();
                
                const resultsInfo = document.createElement('div');
                resultsInfo.className = 'search-results-info';
                resultsInfo.style.cssText = `
                    margin: 20px 0;
                    padding: 10px;
                    background: #f8f9fa;
                    border-radius: 5px;
                    color: #666;
                    font-size: 14px;
                `;
                resultsInfo.innerHTML = `Found <strong>${count}</strong> lab test${count !== 1 ? 's' : ''} for "<strong>${query}</strong>"`;
                
                resultsContainer.parentNode.insertBefore(resultsInfo, resultsContainer);
            }

            function loadMoreResults() {
                currentPage++;
                const startIndex = (currentPage - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;
                const nextPageResults = currentSearchResults.slice(startIndex, endIndex);
                appendResults(nextPageResults);
                
                if(endIndex >= currentSearchResults.length) {
                    loadMoreBtn.classList.add('hidden');
                }
            }

            function displayResults(labTests) {
                clearResults();
                
                if (labTests.length === 0) {
                    showError('No lab tests found. Try a different search term.');
                    return;
                }
                
                labTests.forEach(labTest => {
                    const labTestCard = createLabTestCard(labTest);
                    resultsContainer.appendChild(labTestCard);
                });
            }
            
            function appendResults(labTests) {
                if (labTests.length === 0) return;
                
                labTests.forEach(labTest => {
                    const labTestCard = createLabTestCard(labTest);
                    resultsContainer.appendChild(labTestCard);
                });
            }

            function createLabTestCard(labTest) {
                const card = document.createElement('div');
                card.className = 'result-card';
                
                const details = document.createElement('div');
                details.className = 'details';
                
                details.innerHTML = `
                    <h3 class="name">${labTest.name}</h3>
                    <div class="lab-sample"><strong>Sample:</strong> ${labTest.sample || 'N/A'}</div>
                    <div class="price"><strong>Price:</strong> ₹${labTest.price || 'N/A'}/-</div>
                    <div class="lab-desc">${labTest.description || 'No description available'}</div>
                `;
                
                const buttonContainer = document.createElement('div');
                buttonContainer.className = 'button-container';
                
                const isInCart = cart.some(item => item.id === labTest.id);
                
                const addToCartBtn = document.createElement('button');
                addToCartBtn.className = isInCart ? 'add-to-cart-btn in-cart' : 'add-to-cart-btn';
                addToCartBtn.innerHTML = isInCart ? 
                    '<i class="ri-check-line"></i> Added' : 
                    '<i class="ri-shopping-cart-line"></i> Add to Cart';
                addToCartBtn.setAttribute('data-test-id', labTest.id || '');
                addToCartBtn.disabled = isInCart;
                
                addToCartBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (!isInCart) {
                        addToCart({
                            id: labTest.id,
                            name: labTest.name,
                            price: labTest.price,
                            sample: labTest.sample,
                            description: labTest.description
                        });
                        this.innerHTML = '<i class="ri-check-line"></i> Added';
                        this.className = 'add-to-cart-btn in-cart';
                        this.disabled = true;
                    }
                });
                
                buttonContainer.appendChild(addToCartBtn);
                card.appendChild(details);
                card.appendChild(buttonContainer);
                
                return card;
            }

            function showLoading() {
                loadingIndicator.classList.remove('hidden');
            }
            
            function hideLoading() {
                loadingIndicator.classList.add('hidden');
            }
            
            function showError(message) {
                errorMessage.innerHTML = `
                    <div style="padding: 20px; text-align: center;">
                        <i class="ri-error-warning-line" style="font-size: 24px; color: #e74c3c; margin-bottom: 10px;"></i>
                        <p style="margin: 0; color: #666;">${message}</p>
                    </div>
                `;
                errorMessage.classList.remove('hidden');
            }
            
            function clearError() {
                errorMessage.textContent = '';
                errorMessage.classList.add('hidden');
            }
            
            function clearResults() {
                resultsContainer.innerHTML = '';
            }
        });
    </script>
</body>
</html>