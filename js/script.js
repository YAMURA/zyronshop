// ==================== API ENDPOINTS ====================
const API = {
    base: window.location.origin + '/cluddy-shop/api/',
    auth: 'auth.php',
    products: 'products.php',
    orders: 'orders.php',
    deposit: 'deposit.php',
    feedback: 'feedback.php',
    support: 'support.php',
    creator: 'creator.php',
    reseller: 'reseller.php'
};

// ==================== GLOBAL FUNCTIONS ====================
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existing = document.querySelectorAll('.notification');
    existing.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'} text-white`;
    notification.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info-circle'} mr-2"></i>${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 3000);
}

function showLoading(show) {
    const loader = document.getElementById('loadingOverlay');
    if (loader) {
        loader.style.display = show ? 'flex' : 'none';
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ==================== AUTH FUNCTIONS ====================
async function login(username, password) {
    showLoading(true);
    try {
        const response = await fetch(API.base + API.auth, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', username, password })
        });
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('user', JSON.stringify(data.user));
            showNotification('Login successful!', 'success');
            setTimeout(() => {
                window.location.href = data.user.role === 'admin' ? 'admin.html' : 'dashboard.html';
            }, 1000);
        } else {
            showNotification(data.message || 'Login failed', 'error');
        }
    } catch (error) {
        showNotification('Network error. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

async function register(userData) {
    showLoading(true);
    try {
        const response = await fetch(API.base + API.auth, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'register', ...userData })
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Registration successful! Please login.', 'success');
            setTimeout(() => window.location.href = 'login.html', 1500);
        } else {
            showNotification(data.message || 'Registration failed', 'error');
        }
    } catch (error) {
        showNotification('Network error. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

function logout() {
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

function isLoggedIn() {
    return localStorage.getItem('user') !== null;
}

function getUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
}

function isAdmin() {
    const user = getUser();
    return user && user.role === 'admin';
}

// ==================== PRODUCT FUNCTIONS ====================
async function loadProducts(category = null) {
    showLoading(true);
    try {
        let url = API.base + API.products;
        if (category) url += `?category=${category}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            displayProducts(data.products);
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showNotification('Failed to load products', 'error');
    } finally {
        showLoading(false);
    }
}

function displayProducts(products) {
    const container = document.getElementById('productsGrid');
    if (!container) return;
    
    if (!products || products.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-400 col-span-full py-8">No products available.</div>';
        return;
    }
    
    container.innerHTML = products.map(product => {
        const finalPrice = product.price * (1 - (product.discount || 0) / 100);
        const statusClass = product.status === 'maintenance' ? 'status-maintenance' : 'status-active';
        const statusText = product.status === 'maintenance' ? 'Maintenance' : 'Active';
        return `
            <div class="product-card p-4" onclick="viewProduct(${product.id})">
                <span class="status-badge ${statusClass}">${statusText}</span>
                <img src="${product.image_url || 'https://via.placeholder.com/300x200'}" class="w-full h-40 object-cover rounded-xl mb-3">
                <h3 class="text-white font-semibold mb-1">${escapeHtml(product.name)}</h3>
                <div class="flex justify-between items-center">
                    <span class="text-purple-400 font-bold">${formatCurrency(finalPrice)}</span>
                    ${product.discount > 0 ? `<span class="text-gray-400 text-sm line-through">${formatCurrency(product.price)}</span>` : ''}
                </div>
                <p class="text-gray-500 text-xs mt-2">${escapeHtml(product.description.substring(0, 50))}...</p>
                <div class="mt-3 flex justify-between items-center">
                    <span class="text-green-400 text-xs">${product.stock > 0 ? 'In Stock' : 'Out of Stock'}</span>
                </div>
            </div>
        `;
    }).join('');
}

async function viewProduct(productId) {
    const user = getUser();
    if (!user) {
        showNotification('Please login to purchase products', 'info');
        setTimeout(() => window.location.href = 'login.html', 1500);
        return;
    }
    
    try {
        const response = await fetch(`${API.base + API.products}?id=${productId}`);
        const data = await response.json();
        
        if (data.success) {
            showProductModal(data.product);
        }
    } catch (error) {
        showNotification('Failed to load product details', 'error');
    }
}

function showProductModal(product) {
    const finalPrice = product.price * (1 - (product.discount || 0) / 100);
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content glass-card p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-white">${escapeHtml(product.name)}</h2>
                <button onclick="this.closest('.modal').remove()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            <div class="space-y-4">
                <img src="${product.image_url}" class="w-full h-48 object-cover rounded-xl">
                <p class="text-gray-300">${escapeHtml(product.description)}</p>
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-2xl font-bold text-purple-400">${formatCurrency(finalPrice)}</span>
                        ${product.discount > 0 ? `<span class="text-gray-400 line-through ml-2">${formatCurrency(product.price)}</span>` : ''}
                    </div>
                    <span class="text-green-400 text-sm">${product.stock > 0 ? 'In Stock' : 'Out of Stock'}</span>
                </div>
                <div class="flex gap-3">
                    <input type="number" id="productQty" value="1" min="1" class="w-20 rounded-xl p-2 bg-white/5 border border-white/10 text-white text-center">
                    <button onclick="buyProduct(${product.id}, '${escapeHtml(product.name)}', ${finalPrice})" class="btn-primary flex-1 py-3 rounded-xl text-white font-semibold">
                        Buy Now
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function buyProduct(productId, productName, price) {
    const user = getUser();
    if (!user) {
        showNotification('Please login first', 'error');
        window.location.href = 'login.html';
        return;
    }
    
    // Show payment modal
    showPaymentModal(productId, productName, price);
}

function showPaymentModal(productId, productName, price) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-content glass-card p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-white">Complete Payment</h2>
                <button onclick="this.closest('.modal').remove()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            <div class="space-y-4">
                <div class="p-3 rounded-lg bg-white/5">
                    <p class="text-white">Product: ${escapeHtml(productName)}</p>
                    <p class="text-white">Amount: ${formatCurrency(price)}</p>
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Select Payment Method</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="selectPaymentMethod('gcash')" class="payment-method p-3 rounded-xl bg-green-500/20 border border-green-500/30 text-green-400">
                            <i class="bi bi-phone"></i> GCash
                        </button>
                        <button onclick="selectPaymentMethod('binance')" class="payment-method p-3 rounded-xl bg-yellow-500/20 border border-yellow-500/30 text-yellow-400">
                            <i class="bi bi-currency-bitcoin"></i> Binance
                        </button>
                    </div>
                </div>
                <div id="paymentInstructions" class="text-sm text-gray-400"></div>
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Your Telegram Username</label>
                    <input type="text" id="telegramUser" class="input-field w-full rounded-xl p-3 text-white" placeholder="@username">
                </div>
                <div>
                    <label class="block text-gray-300 text-sm mb-2">Upload Payment Proof</label>
                    <input type="file" id="paymentProof" accept="image/*" class="w-full rounded-xl p-2 bg-white/5 border border-white/10 text-white">
                </div>
                <button onclick="submitOrder(${productId}, ${price})" class="btn-primary w-full py-3 rounded-xl text-white font-semibold">
                    Submit Order
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    window.selectedPaymentMethod = null;
    window.currentProductId = productId;
    window.currentProductPrice = price;
    window.currentProductName = productName;
}

function selectPaymentMethod(method) {
    window.selectedPaymentMethod = method;
    const instructions = document.getElementById('paymentInstructions');
    
    if (method === 'gcash') {
        instructions.innerHTML = `
            <div class="p-3 rounded-lg bg-green-500/10 border border-green-500/20">
                <p class="text-green-400 font-semibold">GCash Payment:</p>
                <p>Number: <strong>09167314020</strong></p>
                <p>Name: <strong>M** J** E**</strong></p>
                <p class="text-xs mt-2">Send exact amount and include your Telegram username</p>
            </div>
        `;
    } else {
        instructions.innerHTML = `
            <div class="p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
                <p class="text-yellow-400 font-semibold">Binance Payment:</p>
                <p>Wallet: <strong>0x742d35Cc6634C0532925a3b844Bc9e7595f0b2a6</strong></p>
                <p>Network: <strong>BEP20</strong></p>
                <p class="text-xs mt-2">Send exact USDT amount and screenshot</p>
            </div>
        `;
    }
}

async function submitOrder(productId, amount) {
    const telegramUser = document.getElementById('telegramUser')?.value;
    const proof = document.getElementById('paymentProof')?.files[0];
    const user = getUser();
    
    if (!window.selectedPaymentMethod) {
        showNotification('Select payment method', 'error');
        return;
    }
    if (!telegramUser) {
        showNotification('Enter your Telegram username', 'error');
        return;
    }
    if (!proof) {
        showNotification('Upload payment proof', 'error');
        return;
    }
    
    showLoading(true);
    
    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('product_id', productId);
    formData.append('amount', amount);
    formData.append('payment_method', window.selectedPaymentMethod);
    formData.append('telegram_user', telegramUser);
    formData.append('receipt', proof);
    
    try {
        const response = await fetch(API.base + API.orders, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Order placed! Awaiting admin approval.', 'success');
            document.querySelector('.modal')?.remove();
            setTimeout(() => window.location.href = 'orders.html', 2000);
        } else {
            showNotification(data.message || 'Failed to place order', 'error');
        }
    } catch (error) {
        showNotification('Network error. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

// ==================== ORDER FUNCTIONS ====================
async function loadUserOrders() {
    const user = getUser();
    if (!user) return;
    
    showLoading(true);
    try {
        const response = await fetch(`${API.base + API.orders}?user_id=${user.id}`);
        const data = await response.json();
        
        if (data.success) {
            displayOrders(data.orders);
        }
    } catch (error) {
        console.error('Error loading orders:', error);
    } finally {
        showLoading(false);
    }
}

function displayOrders(orders) {
    const container = document.getElementById('ordersList');
    if (!container) return;
    
    if (!orders || orders.length === 0) {
        container.innerHTML = '<p class="text-gray-400 text-center py-8">No orders found.</p>';
        return;
    }
    
    container.innerHTML = orders.map(order => `
        <div class="p-4 rounded-xl bg-white/5">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <p class="text-white font-semibold">${escapeHtml(order.product_name)}</p>
                    <p class="text-gray-400 text-sm">Order #${order.order_id}</p>
                </div>
                <span class="status-badge status-${order.status}">${order.status.toUpperCase()}</span>
            </div>
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-purple-400">${formatCurrency(order.amount)}</p>
                    <p class="text-gray-500 text-xs">${order.payment_method.toUpperCase()}</p>
                </div>
                <div class="text-right">
                    <p class="text-gray-500 text-xs">${formatDate(order.timestamp)}</p>
                </div>
            </div>
        </div>
    `).join('');
}

// ==================== DEPOSIT FUNCTIONS ====================
async function submitDeposit(amount, method, proof) {
    const user = getUser();
    if (!user) return;
    
    showLoading(true);
    
    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('amount', amount);
    formData.append('method', method);
    formData.append('receipt', proof);
    
    try {
        const response = await fetch(API.base + API.deposit, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Deposit request submitted! Awaiting admin approval.', 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showNotification(data.message || 'Failed to submit deposit', 'error');
        }
    } catch (error) {
        showNotification('Network error. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

// ==================== FEEDBACK FUNCTIONS ====================
async function submitFeedback(message, rating, image) {
    const user = getUser();
    if (!user) return;
    
    showLoading(true);
    
    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('message', message);
    formData.append('rating', rating);
    if (image) formData.append('image', image);
    
    try {
        const response = await fetch(API.base + API.feedback, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Thank you for your feedback!', 'success');
            return true;
        } else {
            showNotification(data.message || 'Failed to submit feedback', 'error');
            return false;
        }
    } catch (error) {
        showNotification('Network error. Please try again.', 'error');
        return false;
    } finally {
        showLoading(false);
    }
}

// ==================== SUPPORT FUNCTIONS ====================
async function sendSupportMessage(message, image) {
    const user = getUser();
    if (!user) return;
    
    showLoading(true);
    
    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('message', message);
    if (image) formData.append('image', image);
    
    try {
        const response = await fetch(API.base + API.support, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Message sent! Support will respond soon.', 'success');
            return true;
        } else {
            showNotification(data.message || 'Failed to send message', 'error');
            return false;
        }
    } catch (error) {
        showNotification('Network error. Please try again.', 'error');
        return false;
    } finally {
        showLoading(false);
    }
}

// ==================== CREATOR PROGRAM ====================
async function submitCreatorApplication(data) {
    const user = getUser();
    if (!user) return;
    
    showLoading(true);
    
    try {
        const response = await fetch(API.base + API.creator, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'apply', ...data })
        });
        const result = await response.json();
        
        if (result.success) {
            showNotification('Application submitted! We will contact you on Telegram.', 'success');
            return true;
        } else {
            showNotification(result.message || 'Failed to submit application', 'error');
            return false;
        }
    } catch (error) {
        showNotification('Network error. Please try again.', 'error');
        return false;
    } finally {
        showLoading(false);
    }
}

// ==================== RESELLER PROGRAM ====================
async function submitResellerApplication(data) {
    const user = getUser();
    if (!user) return;
    
    showLoading(true);
    
    try {
        const response = await fetch(API.base + API.reseller, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'apply', ...data })
        });
        const result = await response.json();
        
        if (result.success) {
            showNotification('Application submitted! We will contact you on Telegram.', 'success');
            return true;
        } else {
            showNotification(result.message || 'Failed to submit application', 'error');
            return false;
        }
    } catch (error) {
        showNotification('Network error. Please try again.', 'error');
        return false;
    } finally {
        showLoading(false);
    }
}

// ==================== ADMIN FUNCTIONS ====================
async function adminGetPendingOrders() {
    if (!isAdmin()) return;
    
    showLoading(true);
    try {
        const response = await fetch(API.base + API.admin + '?action=pending_orders');
        const data = await response.json();
        
        if (data.success) {
            return data.orders;
        }
    } catch (error) {
        console.error('Error loading pending orders:', error);
    } finally {
        showLoading(false);
    }
    return [];
}

async function adminApproveOrder(orderId) {
    if (!isAdmin()) return;
    
    showLoading(true);
    try {
        const response = await fetch(API.base + API.admin, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'approve_order', order_id: orderId })
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Order approved!', 'success');
            return true;
        } else {
            showNotification('Failed to approve order', 'error');
            return false;
        }
    } catch (error) {
        showNotification('Network error', 'error');
        return false;
    } finally {
        showLoading(false);
    }
}

async function adminDeclineOrder(orderId) {
    if (!isAdmin()) return;
    
    showLoading(true);
    try {
        const response = await fetch(API.base + API.admin, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'decline_order', order_id: orderId })
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Order declined', 'warning');
            return true;
        } else {
            showNotification('Failed to decline order', 'error');
            return false;
        }
    } catch (error) {
        showNotification('Network error', 'error');
        return false;
    } finally {
        showLoading(false);
    }
}

async function adminAddProduct(productData) {
    if (!isAdmin()) return;
    
    showLoading(true);
    
    const formData = new FormData();
    formData.append('action', 'add');
    Object.keys(productData).forEach(key => {
        formData.append(key, productData[key]);
    });
    
    try {
        const response = await fetch(API.base + API.admin, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Product added successfully!', 'success');
            return true;
        } else {
            showNotification(data.message || 'Failed to add product', 'error');
            return false;
        }
    } catch (error) {
        showNotification('Network error', 'error');
        return false;
    } finally {
        showLoading(false);
    }
}

// ==================== UTILITY FUNCTIONS ====================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

// ==================== MUSIC PLAYER ====================
let isMusicPlaying = false;
let musicFile = localStorage.getItem('cluddy_music_url');

function loadMusic() {
    if (musicFile) {
        const audio = document.getElementById('bgMusic');
        if (audio) {
            audio.src = musicFile;
            audio.load();
        }
    }
}

function toggleMusic() {
    const music = document.getElementById('bgMusic');
    const icon = document.getElementById('musicIcon');
    const text = document.getElementById('musicText');
    
    if (!music) return;
    
    if (isMusicPlaying) {
        music.pause();
        if (icon) icon.className = 'bi bi-music-note';
        if (text) text.textContent = 'Play Music';
    } else {
        if (musicFile) {
            music.play().catch(e => console.log('Playback failed:', e));
            if (icon) icon.className = 'bi bi-music-note-beamed music-playing';
            if (text) text.textContent = 'Playing...';
        }
    }
    isMusicPlaying = !isMusicPlaying;
}

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', () => {
    // Load music
    loadMusic();
    
    // Hide splash screen if exists
    const splash = document.getElementById('splashScreen');
    if (splash) {
        setTimeout(() => {
            splash.style.opacity = '0';
            setTimeout(() => splash.style.display = 'none', 500);
        }, 1500);
    }
    
    // Load products on products page
    if (document.getElementById('productsGrid')) {
        loadProducts();
    }
    
    // Load orders on orders page
    if (document.getElementById('ordersList')) {
        loadUserOrders();
    }
    
    // Set current user name if logged in
    const user = getUser();
    if (user) {
        const userNameElements = document.querySelectorAll('.user-name');
        userNameElements.forEach(el => el.textContent = user.fullname || user.username);
        
        const balanceElements = document.querySelectorAll('.user-balance');
        balanceElements.forEach(el => el.textContent = formatCurrency(user.balance));
    }
});

// ==================== SIDEBAR TOGGLE ====================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    if (sidebar && overlay) {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    }
}