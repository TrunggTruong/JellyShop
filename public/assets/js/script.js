// Where our server-side code (API) is located
const BACKEND_URL = './api';
const AUTH_ENDPOINT = './api/auth.php';

// Authentication state
let authState = {
    isAuthenticated: false,
    customer: null,
    initializing: true
};

// Cached DOM references for auth & cart interactions
const loginTrigger = document.getElementById('login-trigger');
const registerTrigger = document.getElementById('register-trigger');
const userMenu = document.getElementById('user-menu');
const userNameEl = document.getElementById('user-name');
const userEmailEl = document.getElementById('user-email');
const logoutBtn = document.getElementById('logout-btn');
const openCustomerPortalBtn = document.getElementById('open-customer-portal');
const loginModal = document.getElementById('login-modal');
const registerModal = document.getElementById('register-modal');
const loginCloseBtn = document.getElementById('login-close-btn');
const registerCloseBtn = document.getElementById('register-close-btn');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const registerErrorBox = document.getElementById('register-error');
const checkoutForm = document.getElementById('checkout-form');
const portalModal = document.getElementById('customer-portal');
const portalTabs = document.querySelectorAll('.portal-tab');
const portalCloseBtn = document.getElementById('portal-close-btn');
const profileFullNameEl = document.getElementById('profile-full-name');
const profileEmailEl = document.getElementById('profile-email');
const ordersListEl = document.getElementById('orders-list');
const ordersEmptyEl = document.getElementById('orders-empty');

let customerProfile = null;
let customerOrders = [];

function updateAuthUI() {
    if (loginTrigger) {
        loginTrigger.classList.toggle('hidden', authState.isAuthenticated);
    }
    if (registerTrigger) {
        registerTrigger.classList.toggle('hidden', authState.isAuthenticated);
    }
    if (userMenu) {
        userMenu.classList.toggle('hidden', !authState.isAuthenticated);
    }
    
    if (authState.isAuthenticated && authState.customer) {
        if (userNameEl) {
            userNameEl.textContent = authState.customer.full_name || 'Customer';
        }
        if (userEmailEl) {
            userEmailEl.textContent = authState.customer.email || '';
        }
    } else {
        if (userNameEl) userNameEl.textContent = '';
        if (userEmailEl) userEmailEl.textContent = '';
    }
}

function prefillCheckoutForm(force = false) {
    if (!checkoutForm) return;
    const nameField = checkoutForm.querySelector('[name="name"]');
    const phoneField = checkoutForm.querySelector('[name="phone"]');
    const addressField = checkoutForm.querySelector('[name="address"]');
    
    if (!authState.isAuthenticated || !customerProfile) {
        return;
    }
    
    if (nameField && (force || !nameField.value)) {
        nameField.value = customerProfile.full_name || '';
    }
    if (phoneField && (force || !phoneField.value)) {
        phoneField.value = customerProfile.phone || '';
    }
    if (addressField && (force || !addressField.value)) {
        addressField.value = customerProfile.address || '';
    }
}

async function refreshCustomerProfile(options = {}) {
    if (!authState.isAuthenticated) {
        customerProfile = null;
        return;
    }
    try {
        const response = await fetch('./api/customer.php?action=profile', {
            credentials: 'same-origin'
        });
        if (!response.ok) {
            throw new Error('profile_fetch_failed');
        }
        const data = await response.json();
        if (!data.success) {
            throw new Error('profile_fetch_failed');
        }
        customerProfile = data.profile;
        if (authState.customer) {
            authState.customer.full_name = data.profile.full_name;
            authState.customer.email = data.profile.email;
        }
        populateProfileInfo();
        prefillCheckoutForm(true);
        updateAuthUI();
    } catch (error) {
        if (!options.silent) {
            console.error('Profile load error:', error);
            showNotification('Unable to load profile information', 'error');
        }
    }
}

async function refreshCustomerOrders(options = {}) {
    if (!authState.isAuthenticated) {
        customerOrders = [];
        populateOrdersList();
        return;
    }
    try {
        const response = await fetch('./api/customer.php?action=orders', {
            credentials: 'same-origin'
        });
        if (!response.ok) {
            throw new Error('orders_fetch_failed');
        }
        const data = await response.json();
        if (!data.success) {
            throw new Error('orders_fetch_failed');
        }
        customerOrders = data.orders || [];
        populateOrdersList();
    } catch (error) {
        if (!options.silent) {
            console.error('Orders load error:', error);
            showNotification('Unable to load order history', 'error');
        }
    }
}

function populateProfileInfo() {
    if (!customerProfile) {
        if (profileFullNameEl) profileFullNameEl.textContent = '-';
        if (profileEmailEl) profileEmailEl.textContent = '-';
        return;
    }
    if (profileFullNameEl) profileFullNameEl.textContent = customerProfile.full_name || '-';
    if (profileEmailEl) profileEmailEl.textContent = customerProfile.email || '-';
}

function populateOrdersList() {
    if (!ordersListEl || !ordersEmptyEl) return;
    ordersListEl.innerHTML = '';
    
    if (!customerOrders || customerOrders.length === 0) {
        ordersEmptyEl.classList.remove('hidden');
        return;
    }
    
    ordersEmptyEl.classList.add('hidden');
    
    customerOrders.forEach(order => {
        const li = document.createElement('li');
        const isCancelled = Number(order.cancelled) === 1;
        const isShipped = Number(order.shipped) === 1;
        let statusText = 'Đang xử lý';
        let statusClass = '';
        if (isCancelled) {
            statusText = 'Canceled';
            statusClass = 'cancelled';
        } else if (isShipped) {
            statusText = 'Shipped';
            statusClass = 'shipped';
        }
        li.innerHTML = `
            <div class="order-meta">
                <span class="order-code">Mã: <strong>${order.order_code || order.id}</strong></span>
                <span class="order-date">${formatOrderDate(order.created_at)}</span>
            </div>
            <div class="order-details">
                <div class="order-total">${formatCurrency(order.total_price || 0)} VND</div>
                <div class="order-status ${statusClass}">
                    ${statusText}
                </div>
            </div>
        `;
        ordersListEl.appendChild(li);
    });
}

function formatOrderDate(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

function switchPortalTab(tab) {
    portalTabs.forEach(button => {
        const target = button.getAttribute('data-portal-tab');
        button.classList.toggle('active', target === tab);
    });
    document.querySelectorAll('.portal-pane').forEach(pane => {
        pane.classList.toggle('active', pane.id === `portal-${tab}`);
    });
}

async function openCustomerPortal(tab = 'profile') {
    if (!authState.isAuthenticated) {
        showNotification('Please login to view customer portal!', 'info');
        showAuthModal('login');
        return;
    }
    
    if (portalModal) {
        portalModal.classList.remove('hidden');
    }
    
    switchPortalTab(tab);
    
    if (tab === 'orders') {
        await refreshCustomerOrders();
    } else {
        if (!customerProfile) {
            await refreshCustomerProfile();
        } else {
            populateProfileInfo();
        }
    }
}

function closeCustomerPortal() {
    if (portalModal) {
        portalModal.classList.add('hidden');
    }
}

function showAuthModal(mode = 'login') {
    hideAuthModal();
    if (mode === 'register') {
        if (registerModal) registerModal.classList.remove('hidden');
    } else {
        if (loginModal) loginModal.classList.remove('hidden');
    }
}

function hideAuthModal() {
    if (loginModal) loginModal.classList.add('hidden');
    if (registerModal) registerModal.classList.add('hidden');
    clearRegisterError();
}

function setRegisterError(message = '') {
    if (!registerErrorBox) return;
    const hasMessage = Boolean(message);
    registerErrorBox.textContent = hasMessage ? message : '';
    registerErrorBox.classList.toggle('hidden', !hasMessage);
}

function clearRegisterError() {
    setRegisterError('');
}

function resetCartForGuest() {
    shoppingCart = {};
    updateShoppingCartDisplay();
    const cartPanel = document.getElementById('cart');
    if (cartPanel) {
        cartPanel.classList.add('hidden');
    }
    showCartConfirmation();
}

async function fetchAuthStatus() {
    try {
        const response = await fetch(AUTH_ENDPOINT, {
            credentials: 'same-origin'
        });
        if (!response.ok) {
            throw new Error('auth_status_failed');
        }
        const data = await response.json();
        authState.isAuthenticated = Boolean(data.authenticated && data.customer);
        authState.customer = data.customer || null;
    } catch (error) {
        console.error('Auth status error:', error);
        authState.isAuthenticated = false;
        authState.customer = null;
    } finally {
        if (authState.isAuthenticated) {
            await refreshCustomerProfile({ silent: true });
        } else {
            customerProfile = null;
            customerOrders = [];
        }
        authState.initializing = false;
        updateAuthUI();
        prefillCheckoutForm();
    }
}

async function sendAuthRequest(payload, fallbackMessage = 'An error occurred, please try again') {
    const response = await fetch(AUTH_ENDPOINT, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload)
    });
    
    let data = {};
    try {
        data = await response.json();
    } catch (e) {
        // Ignore JSON parse error, will use fallback message
    }
    
    if (!response.ok || data.success !== true) {
        const message = data.message || data.error || fallbackMessage;
        throw new Error(message);
    }
    
    return data;
}

// Auth related event bindings
if (loginTrigger) {
    loginTrigger.addEventListener('click', () => showAuthModal('login'));
}

if (registerTrigger) {
    registerTrigger.addEventListener('click', () => showAuthModal('register'));
}

if (loginCloseBtn) {
    loginCloseBtn.addEventListener('click', hideAuthModal);
}

if (registerCloseBtn) {
    registerCloseBtn.addEventListener('click', hideAuthModal);
}

if (loginModal) {
    loginModal.addEventListener('click', (event) => {
        if (event.target === loginModal) {
            hideAuthModal();
        }
    });
}

if (registerModal) {
    registerModal.addEventListener('click', (event) => {
        if (event.target === registerModal) {
            hideAuthModal();
        }
    });
}

if (openCustomerPortalBtn) {
    openCustomerPortalBtn.addEventListener('click', () => openCustomerPortal('profile'));
}

if (portalCloseBtn) {
    portalCloseBtn.addEventListener('click', closeCustomerPortal);
}

if (portalModal) {
    portalModal.addEventListener('click', (event) => {
        if (event.target === portalModal) {
            closeCustomerPortal();
        }
    });
}

portalTabs.forEach(tab => {
    tab.addEventListener('click', (event) => {
        const target = tab.getAttribute('data-portal-tab') || 'profile';
        switchPortalTab(target);
        if (target === 'orders') {
            refreshCustomerOrders();
        } else {
            if (!customerProfile) {
                refreshCustomerProfile();
            } else {
                populateProfileInfo();
            }
        }
    });
});

if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(loginForm);
        const email = (formData.get('email') || '').toString().trim();
        const password = (formData.get('password') || '').toString();
        
        if (!email || !password) {
            showNotification('Please enter email and password', 'error');
            return;
        }
        
        try {
            const data = await sendAuthRequest({
                action: 'login',
                email,
                password
            }, 'Unable to login');
            
            authState.isAuthenticated = true;
            authState.customer = data.customer;
            hideAuthModal();
            loginForm.reset();
            await refreshCustomerProfile({ silent: true });
            updateAuthUI();
            prefillCheckoutForm(true);
            showNotification('Login successful!', 'success', 2000);
        } catch (error) {
            showNotification(error.message || 'Unable to login', 'error');
        }
    });
}

if (registerForm) {
    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearRegisterError();
        const formData = new FormData(registerForm);
        const fullName = (formData.get('full_name') || '').toString().trim();
        const email = (formData.get('email') || '').toString().trim();
        const password = (formData.get('password') || '').toString();
        const confirmPassword = (formData.get('confirm_password') || '').toString();
        
        if (!fullName || !email || !password || !confirmPassword) {
            setRegisterError('Please fill in all information');
            return;
        }
        
        if (password !== confirmPassword) {
            setRegisterError('Passwords do not match');
            return;
        }
        
        try {
            const data = await sendAuthRequest({
                action: 'register',
                full_name: fullName,
                email,
                password,
                confirm_password: confirmPassword
            }, 'Unable to register');
            
            authState.isAuthenticated = true;
            authState.customer = data.customer;
            hideAuthModal();
            registerForm.reset();
            await refreshCustomerProfile({ silent: true });
            updateAuthUI();
            prefillCheckoutForm(true);
            showNotification('Registration successful! You are now logged in.', 'success', 2500);
        } catch (error) {
            setRegisterError(error.message || 'Unable to register');
        }
    });
}


if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
        try {
            await sendAuthRequest({ action: 'logout' }, 'Unable to logout');
        } catch (error) {
            showNotification(error.message || 'Unable to logout', 'error');
            return;
        }
        
        authState.isAuthenticated = false;
        authState.customer = null;
        customerProfile = null;
        customerOrders = [];
        updateAuthUI();
        resetCartForGuest();
        closeCustomerPortal();
        showNotification('Logged out', 'success', 2000);
    });
}

// List of products from the server
let productList = [];

// Shopping cart data
let shoppingCart = {};

// List of product categories we have
let productCategories = [];

// Which page we're currently showing (starts at 1)
let currentPageNumber = 1;

// Number of products to show per page
const PRODUCTS_PER_PAGE = 9;

// Total number of products in database
let totalProductCount = 0;

// All products grouped by category (for category display)
let allProductsByCategory = {};

/**
 * Formats a number as currency with VND format
 * Uses fallback for older browsers that don't support toLocaleString with locale
 * @param {number} num - The number to format
 * @returns {string} Formatted number string
 */
function formatCurrency(num) {
    var number = Number(num) || 0;
    // Try to use modern toLocaleString with locale
    try {
        if (typeof number.toLocaleString === 'function') {
            return number.toLocaleString('vi-VN');
        }
    } catch (e) {
        // Fallback if locale not supported
    }
    // Fallback: simple number formatting with commas
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Shows a notification in the center of the page
 * @param {string} message - The message to display
 * @param {string} type - Type of notification: 'success', 'error', or 'info'
 * @param {number} duration - How long to show the notification in milliseconds (default: 3000)
 */
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.classList.remove('hidden');
    
    // Allow clicking to close
    const closeHandler = () => {
        notification.classList.add('hidden');
        notification.removeEventListener('click', closeHandler);
    };
    notification.addEventListener('click', closeHandler);
    
    // Auto-hide after duration
    setTimeout(() => {
        notification.classList.add('hidden');
        notification.removeEventListener('click', closeHandler);
    }, duration);
}

/**Gets products from the server based on:

@param {string} searchText 
 @param {string} categoryFilter 
 @param {number} pageNumber 
 */
async function getProductsFromServer(searchText='', categoryFilter='', pageNumber=1){
    const searchParams = new URLSearchParams();
    
    // Only add search text if user typed something
    if(searchText) {
        searchParams.set('q', searchText);
    }
    
    // Only add category if user selected one
    if(categoryFilter) {
        searchParams.set('category', categoryFilter);
    }
    
    // Always tell the server which page we want and how many items per page
    searchParams.set('page', pageNumber);
    searchParams.set('per_page', PRODUCTS_PER_PAGE);
    
    try {
        // Ask the server for products
        const serverResponse = await fetch(`${BACKEND_URL}/products.php?${searchParams.toString()}`);
        
        // Check if response is OK
        if (!serverResponse.ok) {
            const errorData = await serverResponse.json().catch(() => ({}));
            throw new Error(errorData.message || `Server error: ${serverResponse.status}`);
        }
        
        //  Get the products data and convert it from JSON
        const responseData = await serverResponse.json();
        
        // Check if response has error
        if (responseData.error) {
            throw new Error(responseData.message || 'Database connection failed');
        }
        
        productList = Array.isArray(responseData) ? responseData : [];
        
        //  Get the total number of products (helps with pagination)
        const totalProducts = parseInt(serverResponse.headers.get('X-Total-Count') || '0', 10);
        totalProductCount = totalProducts;
        currentPageNumber = pageNumber;
        
        //  Update what the user sees on the page
        showProductsOnPage();
        updatePageButtons();
        
        // Show message if no products
        if (productList.length === 0 && pageNumber === 1) {
            showNotification('No products found. Please run install.php to add sample products.', 'info', 5000);
        }
    } catch (error) {
        // If something goes wrong, show an error message
        console.error('Could not get products:', error);
        showNotification('Unable to load products: ' + error.message + '. Please check database connection.', 'error', 8000);
        productList = [];
        showProductsOnPage();
    }
}

/**
 * Fetches all products to extract unique categories
 * Categories are used to populate the category filter dropdown
 */
/**
 * Gets all available product categories from the server
 * This helps us build the dropdown menu where users can filter products
 * Also groups all products by category for display
 */
async function getProductCategories(){
    try {
        // Step 1: Get all products from the server
        const serverResponse = await fetch(`${BACKEND_URL}/products.php`);
        
        if (!serverResponse.ok) {
            const errorData = await serverResponse.json().catch(() => ({}));
            throw new Error(errorData.message || `Server error: ${serverResponse.status}`);
        }
        
        const responseData = await serverResponse.json();
        
        // Check if response has error
        if (responseData.error) {
            throw new Error(responseData.message || 'Database connection failed');
        }
        
        const allProducts = Array.isArray(responseData) ? responseData : [];
        
        // Step 2: Make a list of unique categories
        // If a product doesn't have a category, we'll call it "Traditional"
        const uniqueCategories = new Set();
        allProductsByCategory = {};
        
        allProducts.forEach(product => {
            const category = product.category || 'Traditional';
            uniqueCategories.add(category);
            
            // Group products by category
            if (!allProductsByCategory[category]) {
                allProductsByCategory[category] = [];
            }
            allProductsByCategory[category].push(product);
        });
        
        // Step 3: Convert our Set to an array for easier use
        productCategories = Array.from(uniqueCategories);
        
        // Step 4: Populate the category dropdown in header
        setupCategoryDropdown();
        
        // Step 5: Show products grouped by category
        if (allProducts.length > 0) {
            showProductsByCategory();
        } else {
            // If no products, show message
            const productsContainer = document.getElementById('products-container');
            if (productsContainer) {
                productsContainer.innerHTML = '<p style="text-align:center; padding:40px; color:#666;">No products available. Please run install.php to add sample products.</p>';
            }
        }
    } catch (error) {
        console.error('Could not get categories:', error);
        showNotification('Unable to load products: ' + error.message, 'error', 5000);
        const productsContainer = document.getElementById('products-container');
        if (productsContainer) {
            productsContainer.innerHTML = '<p style="text-align:center; padding:40px; color:#d32f2f;">Error loading products. Please check database connection and run install.php if needed.</p>';
        }
    }
}

/**
 * Populates the category dropdown in the header
 */
function setupCategoryDropdown(){
    const categorySelect = document.getElementById('category-select');
    if (!categorySelect) return;
    
    // Clear existing options except "All Categories"
    categorySelect.innerHTML = '<option value="">All Categories</option>';
    
    // Add all categories
    productCategories.forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        categorySelect.appendChild(option);
    });
    
    // Set up search button event listener
    const searchBtn = document.getElementById('search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', performSearch);
    }
    
    // Allow Enter key to trigger search
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    
    // When category changes, auto-search if there's a search term
    if (categorySelect) {
        categorySelect.addEventListener('change', () => {
            const searchText = document.getElementById('search')?.value.trim() || '';
            if (searchText) {
                performSearch();
            } else {
                // If no search text, show all products by category
                const selectedCategory = categorySelect.value;
                if (selectedCategory) {
                    getProductsFromServer('', selectedCategory, 1);
                } else {
                    showProductsByCategory();
                }
            }
        });
    }
}

/**
 * Performs search with current search text and category filter
 */
function performSearch() {
    const searchText = document.getElementById('search')?.value.trim() || '';
    const selectedCategory = document.getElementById('category-select')?.value || '';
    
    if (searchText || selectedCategory) {
        getProductsFromServer(searchText, selectedCategory, 1);
    } else {
        // If no search/filter, show all products grouped by category
        showProductsByCategory();
    }
}

/**
 * Renders product cards in the products grid
 * Creates a card for each product with image, name, price, description, and add to cart button
 */
/**
 * Shows all our products on the page (for search/filter results)
 * Each product gets its own card with an image, name, price, and add to cart button
 */
function showProductsOnPage(){
    // Step 1: Get the container where we'll show our products
    const productsContainer = document.getElementById('products-container');
    if (!productsContainer) return;
    
    // Step 2: Clear out any old products
    productsContainer.innerHTML = '';
    
    // Step 3: Create a single grid for search results
    const grid = document.createElement('div');
    grid.className = 'grid';
    
    // Step 4: Create a card for each product
    productList.forEach(product => {
        const productCard = createProductCard(product);
        grid.appendChild(productCard);
    });
    
    productsContainer.appendChild(grid);
}

/**
 * Shows products grouped by category
 */
function showProductsByCategory(){
    const productsContainer = document.getElementById('products-container');
    if (!productsContainer) return;
    
    // Clear container
    productsContainer.innerHTML = '';
    
    // Don't show by category if we're searching/filtering
    // (This function should only be called when not searching)
    
    // Show all products grouped by category
    const sortedCategories = Object.keys(allProductsByCategory).sort();
    
    sortedCategories.forEach(category => {
        const products = allProductsByCategory[category];
        if (products.length === 0) return;
        
        // Create category section
        const categorySection = document.createElement('div');
        categorySection.className = 'category-section';
        
        // Category title
        const categoryTitle = document.createElement('h2');
        categoryTitle.className = 'category-title';
        categoryTitle.textContent = category;
        categorySection.appendChild(categoryTitle);
        
        // Product grid for this category
        const grid = document.createElement('div');
        grid.className = 'grid';
        
        products.forEach(product => {
            const productCard = createProductCard(product);
            grid.appendChild(productCard);
        });
        
        categorySection.appendChild(grid);
        productsContainer.appendChild(categorySection);
    });
}

/**
 * Creates a product card element
 * @param {Object} product - Product data
 * @returns {HTMLElement} Product card element
 */
function createProductCard(product) {
    const productCard = document.createElement('div');
    productCard.className = 'card';
    
    // Store product data in data attributes for easy access
    const productData = JSON.stringify({
        id: product.id,
        name: product.name,
        price: product.price,
        description: product.description,
        image: product.image,
        category: product.category
    });
    
    productCard.innerHTML = `
        <img 
            src="${product.image || 'images/placeholder.svg'}" 
            alt="Hình ảnh ${product.name}"
            title="${product.name}"
        />
        <h3>${product.name}</h3>
        <div class="price">${formatCurrency(product.price)} VND</div>
        <div class="desc">${product.description || 'Chưa có mô tả'}</div>
        <div class="cat">Type: ${product.category || 'Tổng hợp'}</div>
        <button 
            data-id="${product.id}"
            data-product='${productData}'
            title="Add ${product.name} to cart"
        >
            Buy Now
        </button>
    `;
    
    return productCard;
}

/**
 * Renders pagination controls
 * Creates page number buttons and handles page navigation
 */
/**
 * Creates the page number buttons at the bottom of the product list
 * This helps users navigate through all our products
 * Only shows when searching/filtering
 */
function updatePageButtons(){
    // Check if we're searching/filtering
    const searchText = document.getElementById('search')?.value.trim() || '';
    const categoryFilter = document.getElementById('category-select')?.value || '';
    
    // Only show pagination when searching/filtering
    if (!searchText && !categoryFilter) {
        const pageButtonsContainer = document.getElementById('pagination');
        if (pageButtonsContainer) {
            pageButtonsContainer.innerHTML = '';
        }
        return;
    }
    
    // Step 1: Calculate how many pages we need
    const totalPages = Math.max(1, Math.ceil(totalProductCount / PRODUCTS_PER_PAGE));
    
    // Step 2: Find or create the container for our page buttons
    let pageButtonsContainer = document.getElementById('pagination');
    if(!pageButtonsContainer){
        // If container doesn't exist, create it
        pageButtonsContainer = document.createElement('div');
        pageButtonsContainer.id = 'pagination';
        pageButtonsContainer.style.margin = '16px 0';
        pageButtonsContainer.style.textAlign = 'center';
        
        // Add it after the products container
        const productsElement = document.getElementById('products-container');
        if (productsElement) {
            productsElement.parentNode.insertBefore(
                pageButtonsContainer, 
                productsElement.nextSibling
            );
        }
    }
    
    // Step 3: If we only have one page, hide the buttons
    if(totalPages <= 1){
        pageButtonsContainer.innerHTML = '';
        return;
    }
    
    // Step 4: Create the page number buttons
    let buttonsHTML = '';
    for(let pageNum = 1; pageNum <= totalPages; pageNum++){
        // Make the current page button look different
        const isCurrentPage = pageNum === currentPageNumber;
        buttonsHTML += `
            <button 
                class="page-btn" 
                data-page="${pageNum}" 
                title="Đến trang ${pageNum}"
                style="
                    margin: 0 4px;
                    padding: 6px 10px;
                    ${isCurrentPage ? 'font-weight:700;background:#eee' : ''}
                "
            >
                ${pageNum}
            </button>
        `;
    }
    pageButtonsContainer.innerHTML = buttonsHTML;
    
    // Step 5: Make the buttons work when clicked
    pageButtonsContainer.querySelectorAll('.page-btn').forEach(button => {
        button.addEventListener('click', () => {
            // Get which page number was clicked
            const clickedPage = parseInt(button.getAttribute('data-page'), 10);
            
            // Keep the current search and category filter
            const searchText = document.getElementById('search')?.value.trim() || '';
            const categoryFilter = document.getElementById('category-select')?.value || '';
            
            // Load the new page of products
            getProductsFromServer(searchText, categoryFilter, clickedPage);
        });
    });
}

/**
 * Updates the cart UI
 * Calculates total price and item count, then displays cart items
 */
/**
 * Updates what's shown in the shopping cart
 * Shows all items, their quantities, and calculates the total price
 */
function updateShoppingCartDisplay(){
    // Step 1: Get the list where we show cart items
    const cartItemsList = document.getElementById('cart-items');
    if (!cartItemsList) return;
    
    cartItemsList.innerHTML = '';
    
    // Step 2: Keep track of totals
    let totalPrice = 0;
    let totalItems = 0;
    
    // Step 3: Go through each item in the cart
    for(const productId in shoppingCart){
        const cartItem = shoppingCart[productId];
        
        // Validate cart item data
        if (!cartItem || !cartItem.name || !cartItem.qty || !cartItem.price) {
            console.warn('Invalid cart item:', cartItem);
            continue;
        }
        
        // Ensure price and quantity are numbers
        const price = Number(cartItem.price) || 0;
        const qty = Number(cartItem.qty) || 0;
        
        // Add up quantities and prices
        totalItems += qty;
        totalPrice += qty * price;
        
        // Create an entry for this item
        const listItem = document.createElement('li');
        listItem.setAttribute('data-product-id', productId);
        listItem.innerHTML = `
            <div class="cart-item-info">
                <span class="cart-item-name">${cartItem.name}</span>
                <span class="cart-item-qty">x ${qty}</span>
            </div>
            <div class="cart-item-right">
                <span class="item-price">
                    ${formatCurrency(qty * price)} VND
                </span>
                <button class="cart-item-delete" data-product-id="${productId}" title="Remove this product">×</button>
            </div>
        `;
        cartItemsList.appendChild(listItem);
    }
    
    // Step 4: Update the total price and item count
    const cartTotalEl = document.getElementById('cart-total');
    const cartCountEl = document.getElementById('cart-count');
    
    if (cartTotalEl) {
        cartTotalEl.textContent = formatCurrency(totalPrice);
    }
    if (cartCountEl) {
        cartCountEl.textContent = totalItems;
    }
}

/**
 * Sets up all the buttons on the page:
 * - Add to cart buttons
 * - Open/close cart buttons
 * This uses event delegation for better performance
 */
document.addEventListener('click', event => {
    // Step 1: Handle "Add to Cart" button clicks
    if(event.target.matches('[data-id]')){
        if (!authState.isAuthenticated) {
            showNotification('Please login to add products to cart!', 'info');
            showAuthModal('login');
            return;
        }
        // Get which product was clicked
        const productId = event.target.getAttribute('data-id');
        let clickedProduct = null;
        
        // Try to get product data from button's data attribute first
        const productDataAttr = event.target.getAttribute('data-product');
        if (productDataAttr) {
            try {
                clickedProduct = JSON.parse(productDataAttr);
            } catch (e) {
                console.warn('Could not parse product data from attribute:', e);
            }
        }
        
        // If not found in data attribute, try to find in productList
        if (!clickedProduct) {
            clickedProduct = productList.find(product => product.id == productId);
        }
        
        // If still not found, try to find in allProductsByCategory
        if (!clickedProduct) {
            for (const category in allProductsByCategory) {
                clickedProduct = allProductsByCategory[category].find(product => product.id == productId);
                if (clickedProduct) break;
            }
        }
        
        // Validate product exists
        if (!clickedProduct) {
            console.error('Product not found:', productId);
            showNotification('Product not found. Please try again!', 'error');
            return;
        }
        
        // Validate product data
        if (!clickedProduct.name || clickedProduct.price === undefined || clickedProduct.price === null) {
            console.error('Invalid product data:', clickedProduct);
            showNotification('Invalid product information!', 'error');
            return;
        }
        
        // If this is the first time adding this product
        if(!shoppingCart[productId]) {
            shoppingCart[productId] = {
                id: clickedProduct.id,
                name: clickedProduct.name,
                price: Number(clickedProduct.price) || 0,
                qty: 0  // Start with quantity 0
            };
        }
        
        // Add one more of this product
        shoppingCart[productId].qty++;
        
        // Show the updated cart
        updateShoppingCartDisplay();
        
        // Show confirmation notification
        showNotification(`Added "${clickedProduct.name}" to cart!`, 'success', 2000);
        
        const cartPanel = document.getElementById('cart');
        if (cartPanel) {
            cartPanel.classList.remove('hidden');
            showCartConfirmation();
        }
    }
    
    // Step 2: Handle the "Close Cart" button
    if(event.target.id === 'close-cart'){
        // Hide the shopping cart panel
        const cartPanel = document.getElementById('cart');
        cartPanel.classList.add('hidden');
        // Reset to confirmation step
        showCartConfirmation();
    }
    
    // Step 3: Handle the "Show/Hide Cart" button
    if(event.target.classList.contains('cart-btn')){
        if (!authState.isAuthenticated) {
            showNotification('Please login to use cart!', 'info');
            showAuthModal('login');
            return;
        }
        // Toggle the cart visibility
        const cartPanel = document.getElementById('cart');
        cartPanel.classList.toggle('hidden');
        // Reset to confirmation step when opening
        if (!cartPanel.classList.contains('hidden')) {
            showCartConfirmation();
        }
    }
    
    // Step 4: Handle "Confirm Cart" button
    if(event.target.id === 'confirm-cart'){
        if (!authState.isAuthenticated) {
            showNotification('Please login to continue ordering!', 'info');
            showAuthModal('login');
            return;
        }
        // Check if cart is empty
        if(Object.keys(shoppingCart).length === 0){
            showNotification('Cart is empty. Please add products first!', 'error');
            return;
        }
        // Show checkout form
        showCheckoutForm();
    }
    
    // Step 5: Handle "Back to Cart" button
    if(event.target.id === 'back-to-cart'){
        showCartConfirmation();
    }
    
    // Step 6: Handle "Delete Item" button in cart
    if(event.target && event.target.classList && event.target.classList.contains('cart-item-delete')){
        const productId = event.target.getAttribute('data-product-id');
        if (productId && shoppingCart[productId]) {
            // Remove the item from cart
            delete shoppingCart[productId];
            // Update the display
            updateShoppingCartDisplay();
            // Show notification
            showNotification('Product removed from cart!', 'success', 2000);
        }
    }
});

/**
 * Shows the cart confirmation step
 */
function showCartConfirmation(){
    const confirmation = document.getElementById('cart-confirmation');
    const checkoutForm = document.getElementById('checkout-form');
    
    if (confirmation) confirmation.classList.remove('hidden');
    if (checkoutForm) checkoutForm.classList.add('hidden');
}

/**
 * Shows the checkout form step
 */
function showCheckoutForm(){
    const confirmation = document.getElementById('cart-confirmation');
    const checkoutForm = document.getElementById('checkout-form');
    
    if (confirmation) confirmation.classList.add('hidden');
    if (checkoutForm) checkoutForm.classList.remove('hidden');
}

/**
 * Validation functions for checkout form
 */

/**
 * Validates customer name
 * - At least 2 characters
 * - Contains only letters, spaces, Vietnamese characters, and common name characters
 * - Cannot be only numbers or special characters
 */
function validateName(name) {
    if (!name || typeof name !== 'string') {
        return { valid: false, message: 'Please enter your full name' };
    }
    
    const trimmedName = name.trim();
    
    if (trimmedName.length < 2) {
        return { valid: false, message: 'Full name must be at least 2 characters' };
    }
    
    if (trimmedName.length > 100) {
        return { valid: false, message: 'Full name cannot exceed 100 characters' };
    }
    
    // Allow letters (including Vietnamese), spaces, apostrophes, hyphens, and dots
    // Vietnamese regex pattern: \p{L} matches any Unicode letter
    const namePattern = /^[\p{L}\s.'-]+$/u;
    
    if (!namePattern.test(trimmedName)) {
        return { valid: false, message: 'Full name can only contain letters, spaces and valid special characters' };
    }
    
    // Check if it's not just numbers or special characters
    const hasLetter = /[\p{L}]/u.test(trimmedName);
    if (!hasLetter) {
        return { valid: false, message: 'Full name must contain at least one letter' };
    }
    
    return { valid: true, message: '' };
}

/**
 * Validates Vietnamese phone number
 * - 10 digits starting with 0 (domestic format: 0xxxxxxxxx)
 * - Or international format: +84xxxxxxxxx (11 digits after +84)
 * - Common formats: 0xx xxxx xxx or +84 xx xxxx xxxx
 */
function validatePhone(phone) {
    if (!phone || typeof phone !== 'string') {
        return { valid: false, message: 'Please enter phone number' };
    }
    
    // Remove all spaces, dashes, and parentheses
    const cleanedPhone = phone.trim().replace(/[\s\-\(\)]/g, '');
    
    if (cleanedPhone.length === 0) {
        return { valid: false, message: 'Please enter phone number' };
    }
    
    // Check for Vietnamese domestic format: 0xxxxxxxxx (10 digits)
    const domesticPattern = /^0\d{9}$/;
    
    // Check for international format: +84xxxxxxxxx or +84xxxxxxxxxx
    const internationalPattern = /^\+84\d{9,10}$/;
    
    if (domesticPattern.test(cleanedPhone)) {
        // Validate first digit after 0 (should be 3, 5, 7, 8, or 9 for valid Vietnamese mobile)
        const secondDigit = cleanedPhone[1];
        if (!['3', '5', '7', '8', '9'].includes(secondDigit)) {
            return { valid: false, message: 'Invalid phone number. Vietnamese numbers usually start with 03, 05, 07, 08, 09' };
        }
        return { valid: true, message: '' };
    }
    
    if (internationalPattern.test(cleanedPhone)) {
        // For +84 format, remove +84 and check if it starts with valid digit
        const withoutPrefix = cleanedPhone.substring(3);
        const firstDigit = withoutPrefix[0];
        if (!['3', '5', '7', '8', '9'].includes(firstDigit)) {
            return { valid: false, message: 'Invalid phone number' };
        }
        return { valid: true, message: '' };
    }
    
    return { valid: false, message: 'Invalid phone number. Please enter 10 digits (starting with 0) or +84 format' };
}

/**
 * Validates delivery address
 * - At least 10 characters
 * - Contains meaningful content (not just numbers or special characters)
 */
function validateAddress(address) {
    if (!address || typeof address !== 'string') {
        return { valid: false, message: 'Please enter delivery address' };
    }
    
    const trimmedAddress = address.trim();
    
    if (trimmedAddress.length < 10) {
        return { valid: false, message: 'Address must be at least 10 characters' };
    }
    
    if (trimmedAddress.length > 500) {
        return { valid: false, message: 'Địa chỉ không được vượt quá 500 ký tự' };
    }
    
    // Check if address contains meaningful content (letters, Vietnamese characters)
    const hasLetter = /[\p{L}]/u.test(trimmedAddress);
    if (!hasLetter) {
        return { valid: false, message: 'Địa chỉ phải chứa chữ cái (không chỉ số hoặc ký tự đặc biệt)' };
    }
    
    // Check if it's not just repeated characters or whitespace
    const uniqueChars = new Set(trimmedAddress.replace(/\s/g, ''));
    if (uniqueChars.size < 3) {
        return { valid: false, message: 'Địa chỉ không hợp lệ' };
    }
    
    return { valid: true, message: '' };
}

/**
 * Validates all form fields and returns validation result
 */
function validateCheckoutForm(formData) {
    const name = formData.get('name') || '';
    const phone = formData.get('phone') || '';
    const address = formData.get('address') || '';
    
    const nameValidation = validateName(name);
    const phoneValidation = validatePhone(phone);
    const addressValidation = validateAddress(address);
    
    const errors = [];
    if (!nameValidation.valid) errors.push({ field: 'name', message: nameValidation.message });
    if (!phoneValidation.valid) errors.push({ field: 'phone', message: phoneValidation.message });
    if (!addressValidation.valid) errors.push({ field: 'address', message: addressValidation.message });
    
    return {
        valid: errors.length === 0,
        errors: errors
    };
}

/**
 * Shows validation error on a form field
 */
function showFieldError(fieldName, message) {
    const field = document.querySelector(`[name="${fieldName}"]`);
    if (!field) return;
    
    // Remove existing error class and message
    field.classList.remove('validation-error');
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Add error class
    field.classList.add('validation-error');
    
    // Add error message
    const errorElement = document.createElement('span');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    field.parentElement.appendChild(errorElement);
}

/**
 * Removes validation error from a form field
 */
function clearFieldError(fieldName) {
    const field = document.querySelector(`[name="${fieldName}"]`);
    if (!field) return;
    
    field.classList.remove('validation-error');
    const errorElement = field.parentElement.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

/**
 * Handles what happens when someone submits an order
 * This processes the checkout form and sends the order to our server
 */
if(checkoutForm) {
    // Add real-time validation on input fields
    const nameField = checkoutForm.querySelector('[name="name"]');
    const phoneField = checkoutForm.querySelector('[name="phone"]');
    const addressField = checkoutForm.querySelector('[name="address"]');
    
    if (nameField) {
        nameField.addEventListener('blur', function() {
            const validation = validateName(this.value);
            if (!validation.valid) {
                showFieldError('name', validation.message);
            } else {
                clearFieldError('name');
            }
        });
        nameField.addEventListener('input', function() {
            // Clear error when user starts typing
            if (this.classList.contains('validation-error')) {
                clearFieldError('name');
            }
        });
    }
    
    if (phoneField) {
        phoneField.addEventListener('blur', function() {
            const validation = validatePhone(this.value);
            if (!validation.valid) {
                showFieldError('phone', validation.message);
            } else {
                clearFieldError('phone');
            }
        });
        phoneField.addEventListener('input', function() {
            // Clear error when user starts typing
            if (this.classList.contains('validation-error')) {
                clearFieldError('phone');
            }
        });
    }
    
    if (addressField) {
        addressField.addEventListener('blur', function() {
            const validation = validateAddress(this.value);
            if (!validation.valid) {
                showFieldError('address', validation.message);
            } else {
                clearFieldError('address');
            }
        });
        addressField.addEventListener('input', function() {
            // Clear error when user starts typing
            if (this.classList.contains('validation-error')) {
                clearFieldError('address');
            }
        });
    }
    
    checkoutForm.addEventListener('submit', async (event) => {
        // Stop the form from submitting normally
        event.preventDefault();
        
        if (!authState.isAuthenticated) {
            showNotification('Please login to place order!', 'error');
            showAuthModal('login');
            return;
        }
        
        // Step 1: Check if the cart is empty
        if(Object.keys(shoppingCart).length === 0){
            showNotification('Cart is empty. Please add products before placing order!', 'error');
            return;
        }
        
        // Step 2: Validate form fields
        const formData = new FormData(checkoutForm);
        const validation = validateCheckoutForm(formData);
        
        if (!validation.valid) {
            // Show all validation errors
            validation.errors.forEach(error => {
                showFieldError(error.field, error.message);
            });
            
            // Show first error in notification
            const firstError = validation.errors[0];
            showNotification(firstError.message, 'error', 3000);
            
            // Scroll to first error field
            const firstErrorField = document.querySelector(`[name="${firstError.field}"]`);
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }
            
            return;
        }
        
        // Clear any existing errors
        clearFieldError('name');
        clearFieldError('phone');
        clearFieldError('address');
        
        try {
            // Step 3: Create the order information to send to server
            const orderData = {
                customer_name: formData.get('name').trim(),
                phone: formData.get('phone').trim().replace(/[\s\-\(\)]/g, ''),
                address: formData.get('address').trim(),
                items: Object.values(shoppingCart).map(item => ({
                    product_id: item.id,
                    quantity: item.qty,
                    price: item.price
                }))
            };
            
            // Step 4: Send the order to our server
            const response = await fetch(`${BACKEND_URL}/orders.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(orderData)
            });
            
            // Step 5: Handle the server's response
            if(response.ok){
                // Order was successful
                const responseData = await response.json();
                const orderCode = responseData.orderCode || responseData.orderId;
                showNotification('Order placed successfully! Order code: ' + orderCode, 'success', 5000);
                
                // Clear everything
                shoppingCart = {};  // Empty cart
                updateShoppingCartDisplay();
                checkoutForm.reset();  // Clear form
                document.getElementById('cart').classList.add('hidden');
                showCartConfirmation(); // Reset to confirmation step
                
                // Refresh the product list (in case quantities changed)
                const searchText = document.getElementById('search')?.value.trim() || '';
                const categoryFilter = document.getElementById('category-select')?.value || '';
                if (searchText || categoryFilter) {
                    getProductsFromServer(searchText, categoryFilter, currentPageNumber);
                } else {
                    getProductCategories(); // Reload all products by category
                }
            } else {
                // Something went wrong
                let errorMessage = 'Unable to place order. Please try again later!';
                
                try {
                    // Try to get error message from response
                    const responseText = await response.text();
                    if (responseText) {
                        try {
                            const errorData = JSON.parse(responseText);
                            if (errorData.message) {
                                errorMessage = errorData.message;
                            } else if (errorData.error) {
                                errorMessage = 'Error: ' + errorData.error;
                            }
                        } catch (e) {
                            // If not JSON, use the text as is (but limit length)
                            errorMessage = responseText.length > 100 ? responseText.substring(0, 100) + '...' : responseText;
                        }
                    }
                } catch (e) {
                    console.error('Error reading error response:', e);
                    errorMessage = 'Unable to place order. Please try again later!';
                }
                
                showNotification(errorMessage, 'error', 5000);
            }
        } catch (error) {
            console.error('Error placing order:', error);
            showNotification('An error occurred while placing order. Please try again later!', 'error');
        }
    });
}

/**
 * Start the application when the page loads
 * This gets the initial products and sets up the category filters
 */
fetchAuthStatus()
    .finally(() => {
        getProductCategories().catch(error => {
            console.error('Startup error:', error);
            showNotification('Unable to connect to server — please check your network connection and try again later!', 'error');
        });
    });
