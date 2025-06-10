<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search your medicines by typing brand</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <?php
        // Start session to check if user is logged in
        session_start();
        
        // Check if user is logged in (adjust this condition based on your login system)
        $isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['logged_in']);
        
        include '../include/header.php';
        include '../styles.php';
    ?>
    
    <!-- Pass login status to JavaScript -->
    <script>
        const USER_LOGGED_IN = <?php echo json_encode($isLoggedIn); ?>;
        const LOGIN_URL = 'login.php'; // Adjust path as needed
    </script>
    
    <section class="hero">
        <div class="container">
            <h1>Your Trusted Partner in Health & Wellness.</h1>
            <p>
                Find all your prescription medications, supplements, and healthcare essentials at competitive prices.
            </p>
            <form class="search-container">
                <input type="text" id="search-bar" placeholder="Search for medicines..."/>
                <button type="submit" aria-label="Search"><i class="ri-search-line" id="search-icon"></i></button>
            </form>
        </div>
    </section>

    <!-- Cart Summary -->
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
        
        <div id="results-container" class="results-container">
            <!-- Search results will be displayed here -->
        </div>
        
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
    
    <!-- Order Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="order">&times;</span>
            <h2>Place Order</h2>
            <div id="order-summary">
                <!-- Order summary will be populated by JavaScript -->
            </div>
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
            const searchInput = document.getElementById('search-bar');
            const searchIcon = document.getElementById('search-icon');
            const loadMoreBtn = document.getElementById('load-more-btn');
            const loadingIndicator = document.getElementById('loading');
            const errorMessage = document.getElementById('error-message');
            const resultsContainer = document.getElementById('results-container');
            const cartModal = document.getElementById('cartModal');
            const orderModal = document.getElementById('orderModal');
            const cartSummary = document.getElementById('cart-summary');
            const cartCount = document.getElementById('cart-count');
            const cartTotal = document.getElementById('cart-total');
            const modalCartTotal = document.getElementById('modal-cart-total');
            const cartItemsContainer = document.getElementById('cart-items-container');
            const orderSummary = document.getElementById('order-summary');
            const orderForm = document.getElementById('orderForm');
            const searchForm = document.querySelector('.search-container');
            
            // Cart functionality
            const viewCartBtn = document.getElementById('view-cart-btn');
            const checkoutBtn = document.getElementById('checkout-btn');
            const clearCartBtn = document.getElementById('clear-cart-btn');
            const proceedCheckoutBtn = document.getElementById('proceed-checkout-btn');
            
            const API_ENDPOINT = 'medicine_api.php';
            let allMedicines = [];
            let currentSearchResults = [];
            let currentPage = 1;
            const itemsPerPage = 9;
            const minSearchChars = 1;
            let cart = [];
            
            loadMedicinesData();
            updateCartDisplay();
            
            // Handle form submission (search button click)
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    initiateSearch();
                });
            }
            
            // Keep the Enter key functionality
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    initiateSearch();
                }
            });

            // Cart event listeners - with login check
            viewCartBtn.addEventListener('click', () => {
                if (checkLoginStatus()) {
                    openModal('cart');
                }
            });
            
            checkoutBtn.addEventListener('click', () => {
                if (checkLoginStatus()) {
                    openModal('order');
                }
            });
            
            clearCartBtn.addEventListener('click', clearCart);
            proceedCheckoutBtn.addEventListener('click', () => {
                closeModal('cart');
                openModal('order');
            });

            // Function to check if user is logged in - now redirects directly
            function checkLoginStatus() {
                if (!USER_LOGGED_IN) {
                    // Direct redirect to login page
                    window.location.href =' ../user//login.php';
                    return false;
                }
                return true;
            }

            // Close modal functionality
            function closeModal(modalType) {
                let modal;
                switch(modalType) {
                    case 'cart':
                        modal = cartModal;
                        break;
                    case 'order':
                        modal = orderModal;
                        break;
                }
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
            
            function openModal(modalType) {
                let modal;
                switch(modalType) {
                    case 'cart':
                        modal = cartModal;
                        updateCartModal();
                        break;
                    case 'order':
                        modal = orderModal;
                        updateOrderSummary();
                        break;
                }
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            
            // Close modals with X button
            document.querySelectorAll('.modal .close').forEach(btn => {
                btn.addEventListener('click', function() {
                    const modalType = this.getAttribute('data-modal');
                    closeModal(modalType);
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === cartModal) {
                    closeModal('cart');
                } else if (event.target === orderModal) {
                    closeModal('order');
                }
            });

            // Order form submission 
            if (orderForm) {
                orderForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Check login status before processing order
                    if (!checkLoginStatus()) {
                        return;
                    }
                    
                    if (cart.length === 0) {
                        alert('Your cart is empty!');
                        return;
                    }
                    
                    // Get form data
                    const formData = new FormData(orderForm);
                    
                    // Add cart information to form data
                    const cartData = cart.map(item => ({
                        name: item.name,
                        price: item.price,
                        quantity: item.quantity
                    }));
                    
                    formData.append('cart', JSON.stringify(cartData));
                    formData.append('totalAmount', calculateCartTotal());
                    
                    // Show loading state
                    const submitBtn = orderForm.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.textContent;
                    submitBtn.textContent = 'Processing...';
                    submitBtn.disabled = true;
                    
                    // Send data to the server
                    fetch(API_ENDPOINT, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Reset button state
                        submitBtn.textContent = originalBtnText;
                        submitBtn.disabled = false;
                        
                        if (data.status === 'success') {
                            alert('Your medicine order has been placed successfully!');
                            orderForm.reset();
                            clearCart();
                            closeModal('order');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        // Reset button state
                        submitBtn.textContent = originalBtnText;
                        submitBtn.disabled = false;
                        alert('Error submitting form: ' + error.message);
                    });
                });
            }

            // Cart management functions
            function addToCart(medicine) {
                // Check if user is logged in before adding to cart - now redirects directly
                if (!checkLoginStatus()) {
                    return;
                }
                
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
                showNotification(`${medicine.name} added to cart!`);
            }
            
            function removeFromCart(medicineId) {
                cart = cart.filter(item => item.id !== medicineId);
                updateCartDisplay();
                updateCartModal();
            }
            
            function updateQuantity(medicineId, newQuantity) {
                const item = cart.find(item => item.id === medicineId);
                if (item) {
                    if (newQuantity <= 0) {
                        removeFromCart(medicineId);
                    } else {
                        item.quantity = newQuantity;
                        updateCartDisplay();
                        updateCartModal();
                    }
                }
            }
            
            function clearCart() {
                cart = [];
                updateCartDisplay();
                updateCartModal();
            }
            
            function calculateCartTotal() {
                return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            }
            
            function updateCartDisplay() {
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                const total = calculateCartTotal();
                
                cartCount.textContent = totalItems;
                cartTotal.textContent = total.toFixed(0);
                modalCartTotal.textContent = total.toFixed(0);
                
                // Only show cart summary if user is logged in and has items
                if (totalItems > 0 && USER_LOGGED_IN) {
                    cartSummary.classList.remove('hidden');
                } else {
                    cartSummary.classList.add('hidden');
                }
            }
            
            function updateCartModal() {
                if (cart.length === 0) {
                    cartItemsContainer.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                    return;
                }
                
                cartItemsContainer.innerHTML = cart.map(item => `
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
                    orderSummary.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
                    return;
                }
                
                const itemsList = cart.map(item => `
                    <div class="order-item">
                        <span class="order-item-name">${item.name} x${item.quantity}</span>
                        <span class="order-item-price">₹${(item.price * item.quantity).toFixed(0)}</span>
                    </div>
                `).join('');
                
                orderSummary.innerHTML = `
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
                // Create notification element
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.textContent = message;
                document.body.appendChild(notification);
                
                // Remove notification after 3 seconds
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
            
            // Make functions global for onclick handlers
            window.updateQuantity = updateQuantity;
            window.removeFromCart = removeFromCart;

            async function loadMedicinesData() {
                showLoading();
                clearError();

                try {
                    const response = await fetch(API_ENDPOINT);
                    if (!response.ok) {
                        throw new Error('Failed to load medicines data');
                    }   
                    const data = await response.json();
                    
                    if (data.error) {
                        throw new Error(data.message || 'Error loading data');
                    }
                    
                    // Fix: Extract the medicines array from the response
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
                clearError();
                
                // Show count for random medicines
                displaySearchResultsCount(randomMedicines.length);
                
                displayResultsOnly(randomMedicines); // Use a helper function to avoid double count display
                loadMoreBtn.classList.add('hidden');
            }

            function displayResultsOnly(medicines) {
                clearResults();
                medicines.forEach(medicine => {
                    const medicineCard = createMedicineCard(medicine);
                    resultsContainer.appendChild(medicineCard);
                });
            }

            function initiateSearch() {
                const query = searchInput.value.trim();
                if (query.length < minSearchChars) {
                    if(query.length === 0) {
                        clearError();
                        displayRandomMedicines();
                    }
                    return;
                }
                currentPage = 1;
                performSearch(query);
            }

            async function performSearch(query) {
                showLoading();
                clearError();
                
                try {
                    const response = await fetch(`${API_ENDPOINT}?query=${encodeURIComponent(query)}`);
                    if (!response.ok) {
                        throw new Error('Search request failed');
                    }
                    
                    const data = await response.json();
                    
                    if (data.error) {
                        throw new Error(data.message || 'Error during search');
                    }
                    
                    // Fix: Extract the medicines array from the response
                    currentSearchResults = data.medicines || [];
                    
                    if (currentSearchResults.length === 0) {
                        showError('No medicines found. Try a different search term.');
                        clearResults();
                        loadMoreBtn.classList.add('hidden');
                        return;
                    }
                    
                    const resultsForCurrentPage = currentSearchResults.slice(0, itemsPerPage);
                    displayResults(resultsForCurrentPage);
                    
                    if(currentSearchResults.length > itemsPerPage) {
                        loadMoreBtn.classList.remove('hidden');
                    } else {
                        loadMoreBtn.classList.add('hidden');
                    }
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
                appendResults(nextPageResults);
                if(endIndex >= currentSearchResults.length) {
                    loadMoreBtn.classList.add('hidden');
                }
            }

            function displayResults(medicines) {
                clearResults();
                if (medicines.length === 0) {
                    showError('No medicines found. Try a different search term.');
                    return;
                }
                
                // Show results count
                const query = searchInput.value.trim();
                const totalCount = currentSearchResults.length || medicines.length;
                displaySearchResultsCount(totalCount, query);
                
                medicines.forEach(medicine => {
                    const medicineCard = createMedicineCard(medicine);
                    resultsContainer.appendChild(medicineCard);
                });
            }
            
            function displaySearchResultsCount(count, query = '') {
                // Remove existing results count if any
                const existingCount = document.getElementById('results-count');
                if (existingCount) {
                    existingCount.remove();
                }
                
                // Create results count element
                const resultsCount = document.createElement('div');
                resultsCount.id = 'results-count';
                resultsCount.className = 'results-count';
                
                // Apply inline styles
                resultsCount.style.cssText = `
                    margin: 20px 0;
                    padding: 10px;
                    background: #f8f9fa;
                    border-radius: 5px;
                    color: #666;
                    font-size: 14px;
                `;
                
                if (query) {
                    resultsCount.innerHTML = `
                        <p>Found <strong>${count}</strong> result${count !== 1 ? 's' : ''} for "<em>${query}</em>"</p>
                    `;
                } else {
                    resultsCount.innerHTML = `
                        <p>Showing <strong>${count}</strong> medicine${count !== 1 ? 's' : ''}</p>
                    `;
                }
                
                // Insert before results container
                const resultsContainer = document.getElementById('results-container');
                resultsContainer.parentNode.insertBefore(resultsCount, resultsContainer);
            }


            function appendResults(medicines) {
                if (medicines.length === 0) return;
                medicines.forEach(medicine => {
                    const medicineCard = createMedicineCard(medicine);
                    resultsContainer.appendChild(medicineCard);
                });
            }

            function getCompositionText(medicine) {
                let compositionText = '';
                if(medicine.composition1) {
                    compositionText += medicine.composition1;
                }
                if(medicine.composition2) {
                    if(compositionText) compositionText += ', ';
                    compositionText += medicine.composition2;
                }
                if(!compositionText) {
                    compositionText = 'Composition not available';
                }
                return compositionText;
            }

            function createMedicineCard(medicine) {
                const card = document.createElement('div');
                card.className = 'result-card';
                
                const details = document.createElement('div');
                details.className = 'details';
                
                const compositionText = getCompositionText(medicine);
                
                details.innerHTML = `
                    <h3 class="name">${medicine.name}</h3>
                    <p class="medicine-manufacturer"><strong>Manufacturer:&nbsp</strong> ${medicine.manufacturer_name || 'N/A'}</p>
                    <p class="medicine-pack"><strong>Pack:</strong> ${medicine.pack_size || 'N/A'}</p>
                    <p class="price"><strong>Price:&nbsp</strong> ₹${medicine.price || 'N/A'}/-</p>
                    <p class="medicine-composition"><strong>Composition:</strong> ${compositionText}</p>
                    ${medicine.label ? `<span class="label">${medicine.label}</span>` : ''}
                `;

                const buttonContainer = document.createElement('div');
                buttonContainer.className = 'button-container';
                buttonContainer.style.cssText = 'display: flex; gap: 10px; flex-wrap: wrap;';


                const addToCartBtn = document.createElement('button');
                addToCartBtn.className = 'add-to-cart-btn';
                addToCartBtn.textContent = USER_LOGGED_IN ? 'Add to Cart' : 'Login to Add';
                addToCartBtn.setAttribute('data-med-id', medicine.id || '');
                
                addToCartBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    addToCart(medicine);
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
                errorMessage.textContent = message;
                errorMessage.classList.remove('hidden');
            }
            
            function clearError() {
                errorMessage.textContent = '';
                errorMessage.classList.add('hidden');
            }
            
            function clearResults() {
                resultsContainer.innerHTML = '';
                const existingCount = document.getElementById('results-count');
                if (existingCount) {
                    existingCount.remove();
                }
            }
            
            loadMoreBtn.addEventListener('click', loadMoreResults);
        });
    </script>
</body>
</html>