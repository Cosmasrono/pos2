@extends('layouts.app')

@section('title', 'Wing POS')

@section('page-title', 'Point of Sale (POS)')

@section('content')
<style>
    body {
        background: radial-gradient(circle at 10% 20%, rgba(79, 70, 229, 0.05) 0%, rgba(248, 250, 252, 1) 90.2%);
        min-height: 100vh;
    }
    
    .pos-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    }

    .search-input-wrapper {
        position: relative;
    }

    .search-input-wrapper i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
    }

    #productSearch {
        padding-left: 45px;
        height: 50px;
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        background: white;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    #productSearch:focus {
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        border-color: var(--primary);
    }

    .product-item {
        border: none;
        border-radius: 12px;
        margin-bottom: 8px;
        background: white;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .product-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
        border-color: rgba(79, 70, 229, 0.2);
    }

    .qty-selector {
        background: #f1f5f9;
        border: none;
        text-align: center;
        width: 60px;
    }

    .cart-table th {
        background: #f8fafc;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border: none;
    }

    .checkout-summary {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
    }
</style>
<div class="container-fluid">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Offline banner — shown automatically when network is lost -->
    <div id="offlineBanner" class="alert alert-warning alert-dismissible d-none mb-3 d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-wifi-off fs-5"></i>
        <div>
            <strong>Offline Mode</strong> — Sales will be saved locally and synced automatically when internet returns.
            M-Pesa payments are unavailable offline.
        </div>
    </div>

    <div class="row">
        <!-- Left Panel - Product Search & Cart -->
        <div class="col-md-8">
            <!-- Product Search -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Search Products</h5>
                </div>
                <div class="card-body">
                    <div class="search-input-wrapper">
                        <i class="bi bi-search"></i>
                        <input type="text" id="productSearch" class="form-control" placeholder="Search by product name, code, or barcode...">
                    </div>
                    <div id="searchResults" class="mt-2">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sale Items Cart -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sale Items</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-danger" id="clearCart">Clear</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="cart-table">
                                <tr>
                                    <th>Product</th>
                                    <th width="100" class="text-center">Qty</th>
                                    <th width="120" class="text-end">Unit Price</th>
                                    <th width="120" class="text-end">Total</th>
                                    <th width="80" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="cartItems">
                            </tbody>
                            <tfoot>
                                <tr id="emptyCart">
                                    <td colspan="5" class="text-center text-muted">No items added yet</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Checkout -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Checkout</h5>
                </div>
                <div class="card-body">
                    <form id="posForm" method="POST" action="{{ route('sales.store') }}">
                        @csrf

                        <!-- Totals Section -->
                        <div class="checkout-summary mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <strong id="subtotal" class="text-dark">KES 0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Discount</span>
                                <div class="input-group input-group-sm" style="width: 120px;">
                                    <span class="input-group-text bg-transparent border-0 pe-0">KES</span>
                                    <input type="number" name="discount" id="discount" class="form-control form-control-sm border-0 bg-transparent text-end fw-bold" value="0" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2" id="tradeInDisplay" style="display: none !important;">
                                <span class="text-success fw-bold">Trade-in Credit</span>
                                <strong id="tradeInAmountText" class="text-success">- KES 0.00</strong>
                            </div>
                            <hr class="opacity-10">
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <h5 class="mb-0 fw-bold">Total</h5>
                                <h4 class="text-primary mb-0 fw-bold" id="totalAmount">KES 0.00</h4>
                            </div>
                        </div>

                        <!-- Trade-in Section -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-success w-100 rounded-3 mb-2" data-bs-toggle="modal" data-bs-target="#tradeInModal">
                                <i class="bi bi-arrow-repeat"></i> Add Trade-in
                            </button>
                            <div id="tradeInList" class="small"></div>
                        </div>

                        <!-- Promotion Selection -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">🎁 PROMOTION</label>
                            <select name="promotion_id" id="promotion" class="form-select border-0 bg-light rounded-3">
                                <option value="" data-type="fixed" data-value="0">No Promotion</option>
                                @foreach($promotions ?? [] as $promotion)
                                    <option value="{{ $promotion->id }}" 
                                            data-type="{{ $promotion->type }}" 
                                            data-value="{{ $promotion->value }}"
                                            data-min="{{ $promotion->min_spend }}">
                                        {{ $promotion->name }} 
                                        ({{ $promotion->type === 'percentage' ? $promotion->value.'%' : 'KES '.$promotion->value }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Customer Selection -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">👤 CUSTOMER</label>
                            <select name="customer_id" id="customer" class="form-select border-0 bg-light rounded-3">
                                <option value="">Walk-in Customer</option>
                                @foreach($customers ?? [] as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">PAYMENT METHOD</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="cash" checked autocomplete="off">
                                    <label class="btn btn-outline-light text-dark w-100 border bg-white" for="pay_cash">💵 Cash</label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="pay_mpesa" value="mpesa" autocomplete="off">
                                    <label class="btn btn-outline-light text-dark w-100 border bg-white" for="pay_mpesa">📱 M-Pesa</label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="pay_card" value="card" autocomplete="off">
                                    <label class="btn btn-outline-light text-dark w-100 border bg-white" for="pay_card">💳 Card</label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="pay_credit" value="credit" autocomplete="off">
                                    <label class="btn btn-outline-light text-dark w-100 border bg-white" for="pay_credit">📋 Credit</label>
                                </div>
                            </div>
                            <input type="hidden" name="payment_method" id="paymentMethod" value="cash">
                        </div>

                        <!-- M-Pesa Phone Number (Hidden by default) -->
                        <div class="mb-3" id="mpesaPhoneField" style="display: none;">
                            <label class="form-label">M-Pesa Phone Number</label>
                            <input type="tel" name="mpesa_phone" id="mpesaPhone" class="form-control" placeholder="07XXXXXXXX or 2547XXXXXXXX">
                            <small class="text-muted">Enter the phone number to receive STK Push</small>
                        </div>

                        <!-- Amount Tendered -->
                        <div class="mb-3" id="amountTenderedField">
                            <label class="form-label">Amount Tendered</label>
                            <input type="number" name="amount_tendered" id="amountTendered" class="form-control" step="0.01" min="0">
                        </div>

                        <!-- Change Due -->
                        <div class="mb-4" id="changeDueField">
                            <div class="d-flex justify-content-between">
                                <strong>Change Due</strong>
                                <strong class="text-success" id="changeDue">KES 0.00</strong>
                            </div>
                        </div>

                        <!-- Hidden Cart Data -->
                        <input type="hidden" name="cart_data" id="cartData">
                        <input type="hidden" name="trade_in_data" id="tradeInData">
                        <input type="hidden" name="trade_in_amount" id="tradeInAmountInput">
                        <input type="hidden" name="subtotal_amount" id="subtotalInput">
                        <input type="hidden" name="tax_amount" id="taxInput">
                        <input type="hidden" name="total_amount" id="totalInput">

                        <!-- Action Buttons -->
                        @if(!($hasActiveShift ?? true))
                            <div class="alert alert-warning">
                                ⚠️ Open a shift to complete sales
                            </div>
                        @endif

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm" id="completeSaleBtn" @if(!($hasActiveShift ?? true)) disabled @endif>
                                <i class="bi bi-check-circle"></i> Complete Sale
                            </button>
                            <button type="button" class="btn btn-link text-muted btn-sm" id="cancelBtn">Reset Cart</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upsell Suggestion Toast -->
<div class="position-fixed bottom-0 start-0 p-3" style="z-index: 1100; max-width: 340px;">
    <div id="upsellToast" class="toast border-0 shadow-lg" role="alert" data-bs-autohide="false">
        <div class="toast-header bg-primary text-white">
            <strong class="me-auto">Customers also buy</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body p-0" id="upsellBody"></div>
    </div>
</div>

<!-- Trade-in Modal -->
<div class="modal fade" id="tradeInModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">🔄 Device Trade-in</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="tradeInForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">DEVICE MODEL</label>
                        <input type="text" id="tradeInModel" class="form-control" placeholder="e.g. iPhone 13 Pro" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">IMEI / SERIAL NUMBER</label>
                        <input type="text" id="tradeInImei" class="form-control" placeholder="Enter device identifier">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">CONDITION</label>
                            <select id="tradeInCondition" class="form-select">
                                <option value="Excellent">Excellent</option>
                                <option value="Good" selected>Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Broken">Broken</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">TRADE-IN VALUE (KES)</label>
                            <input type="number" id="tradeInValue" class="form-control fw-bold text-success" step="0.01" min="0" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="addTradeInBtn">Add to Sale</button>
            </div>
        </div>
    </div>
</div>

<!-- M-Pesa Payment Modal -->
<div class="modal fade" id="mpesaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">📱 M-Pesa Payment</h5>
            </div>
            <div class="modal-body text-center">
                <div id="mpesaStatusPending" style="display: none;">
                    <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mb-3">Waiting for Payment...</h5>
                    <p class="text-muted">
                        1. Check your phone for M-Pesa prompt<br>
                        2. Enter your M-Pesa PIN<br>
                        3. Confirm the payment
                    </p>
                    <p class="fw-bold" id="mpesaPhoneDisplay"></p>
                    <p class="fw-bold text-success" id="mpesaAmountDisplay"></p>
                </div>
                <div id="mpesaStatusSuccess" style="display: none;">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 text-success">Payment Successful!</h5>
                    <p id="mpesaReceiptNumber"></p>
                </div>
                <div id="mpesaStatusFailed" style="display: none;">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 text-danger">Payment Failed</h5>
                    <p id="mpesaErrorMessage" class="text-muted"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="mpesaCancelBtn">Cancel</button>
                <button type="button" class="btn btn-success" id="mpesaFinishBtn" style="display: none;">Complete Sale</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dexie is bundled via app.js — available as window.Dexie
    const db = new Dexie("WingPOS_DB");
    db.version(2).stores({
        products: 'id, name, sku, barcode, price',
        sales_queue: '++id, customer_id, cart_data, payment_method, total_amount, status',
        cart: 'id, name, price, quantity'
    });

    let cart = [];
    let tradeIns = [];
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Trade-in Logic
    const addTradeInBtn = document.getElementById('addTradeInBtn');
    const tradeInList = document.getElementById('tradeInList');
    const tradeInDisplay = document.getElementById('tradeInDisplay');
    const tradeInAmountText = document.getElementById('tradeInAmountText');

    addTradeInBtn.addEventListener('click', function() {
        const model = document.getElementById('tradeInModel').value.trim();
        const imei = document.getElementById('tradeInImei').value.trim();
        const condition = document.getElementById('tradeInCondition').value;
        const value = parseFloat(document.getElementById('tradeInValue').value) || 0;

        if (!model || value <= 0) {
            alert('Please enter model and a valid value.');
            return;
        }

        const tradeIn = {
            id: Date.now(),
            model_name: model,
            imei_serial: imei,
            condition: condition,
            value: value
        };

        tradeIns.push(tradeIn);
        updateTradeInUI();
        
        // Reset and close modal
        document.getElementById('tradeInForm').reset();
        bootstrap.Modal.getInstance(document.getElementById('tradeInModal')).hide();
    });

    function updateTradeInUI() {
        if (tradeIns.length === 0) {
            tradeInList.innerHTML = '';
            tradeInDisplay.setAttribute('style', 'display: none !important');
            updateTotals();
            return;
        }

        tradeInDisplay.setAttribute('style', 'display: flex !important');
        
        let html = '<div class="list-group mb-2">';
        tradeIns.forEach((item, index) => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center p-2 border-dashed bg-light">
                    <div>
                        <div class="fw-bold">${item.model_name}</div>
                        <div class="x-small text-muted">${item.imei_serial || 'No IMEI'} | ${item.condition}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-success fw-bold">KES ${item.value.toLocaleString()}</span>
                        <button type="button" class="btn btn-link text-danger p-0" onclick="removeTradeIn(${index})">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        tradeInList.innerHTML = html;
        
        updateTotals();
    }

    window.removeTradeIn = function(index) {
        tradeIns.splice(index, 1);
        updateTradeInUI();
    };

    // API Headers Helper
    const apiHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN
    };

    // ── Cart Functions (offline-first) ──────────────────────────────────────
    // All cart operations write to IndexedDB first, then try to sync to server.
    // When offline, the local state is the source of truth.

    async function loadCartFromDB() {
        if (!navigator.onLine) {
            cart = await db.cart.toArray();
            updateCartUI();
            return;
        }
        try {
            const response = await fetch('/api/cart');
            if (response.ok) {
                cart = await response.json();
                await db.cart.clear();
                if (cart.length) await db.cart.bulkPut(cart);
                updateCartUI();
            }
        } catch (e) {
            // Network failed — fall back to local cache
            cart = await db.cart.toArray();
            updateCartUI();
        }
    }

    async function syncAddToCart(productId, quantity) {
        const product = await db.products.get(parseInt(productId));
        if (!product) return;

        const existing = cart.findIndex(i => i.id == productId);
        if (existing >= 0) {
            cart[existing].quantity += quantity;
        } else {
            cart.push({ id: parseInt(productId), name: product.name, price: parseFloat(product.price), quantity, stock: product.stock });
        }
        await db.cart.clear();
        if (cart.length) await db.cart.bulkPut(cart);
        updateCartUI();

        if (navigator.onLine) {
            fetch('/api/cart', { method: 'POST', headers: apiHeaders, body: JSON.stringify({ product_id: productId, quantity }) }).catch(() => {});
        }
    }

    async function syncUpdateQuantity(productId, quantity) {
        const idx = cart.findIndex(i => i.id == productId);
        if (idx >= 0) {
            cart[idx].quantity = quantity;
            await db.cart.clear();
            if (cart.length) await db.cart.bulkPut(cart);
            updateCartUI();
        }

        if (navigator.onLine) {
            fetch(`/api/cart/${productId}`, { method: 'PUT', headers: apiHeaders, body: JSON.stringify({ quantity }) }).catch(() => {});
        }
    }

    async function syncRemoveItem(productId) {
        cart = cart.filter(i => i.id != productId);
        await db.cart.clear();
        if (cart.length) await db.cart.bulkPut(cart);
        updateCartUI();

        if (navigator.onLine) {
            fetch(`/api/cart/${productId}`, { method: 'DELETE', headers: apiHeaders }).catch(() => {});
        }
    }

    async function syncClearCart() {
        cart = [];
        tradeIns = [];
        await db.cart.clear();
        updateCartUI();
        updateTradeInUI();

        if (navigator.onLine) {
            fetch('/api/cart', { method: 'DELETE', headers: apiHeaders }).catch(() => {});
        }
    }

    // Product Search Functionality
    const productSearch = document.getElementById('productSearch');
    const searchResults = document.getElementById('searchResults');

    // Load state from DB and then products
    loadCartFromDB();
    
    // 2. Sync Products to Local DB. Returns how many products were cached.
    async function syncProductsWithLocal() {
        if (!navigator.onLine) return null;

        try {
            const response = await fetch('{{ route("pos.products") }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const products = await response.json();
            await db.products.clear();
            if (products.length) await db.products.bulkPut(products);
            console.log('Local product cache updated:', products.length);
            return products.length;
        } catch (e) {
            console.error('Failed to sync products:', e);
            return null; // signal sync failure so we fall back to whatever is cached
        }
    }

    // Sync first (when online), THEN render — otherwise we render an empty cache
    // before the fetch has finished writing to it.
    (async () => {
        await syncProductsWithLocal();
        await loadAllProducts();
    })();

    async function loadAllProducts() {
        try {
            const products = await db.products.toArray();
            if (products.length > 0) {
                displaySearchResults(products);
            } else if (navigator.onLine) {
                searchResults.innerHTML = '<div class="alert alert-warning">No products available for your branch. Add stock to this branch, then refresh.</div>';
            } else {
                searchResults.innerHTML = '<div class="alert alert-warning">No products in local cache. Please go online to sync.</div>';
            }
        } catch (e) {
            console.error('Dexie load error:', e);
            searchResults.innerHTML = '<div class="alert alert-danger">Error loading local products.</div>';
        }
    }

    productSearch.addEventListener('input', async function() {
        const query = this.value.trim().toLowerCase();
        
        if (query.length < 1) {
            loadAllProducts();
            return;
        }

        // Search in local IndexedDB
        const results = await db.products
            .filter(p => 
                p.name.toLowerCase().includes(query) || 
                (p.sku && p.sku.toLowerCase().includes(query)) ||
                (p.barcode && p.barcode.toLowerCase().includes(query))
            )
            .toArray();
            
        displaySearchResults(results);
    });

    function displaySearchResults(products) {
        if (products.length === 0) {
            searchResults.innerHTML = '<div class="alert alert-info">No products found</div>';
            return;
        }

        let html = '<div class="list-group list-group-flush rounded-3 overflow-hidden border-0 shadow-sm">';
        products.forEach((product, idx) => {
            html += `
                <div class="list-group-item product-item p-3 border-0 mb-1" data-id="${product.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="product-icon bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-box-seam text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">${product.name}</h6>
                                <small class="text-muted">Code: ${product.code || 'N/A'} | Stock: ${product.stock || 0}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-4">
                            <div class="text-end">
                                <div class="fw-bold text-primary">KES ${parseFloat(product.price).toLocaleString()}</div>
                                <small class="text-muted">Unit Price</small>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="number" class="form-control form-control-sm qty-selector rounded-pill" 
                                       data-index="${idx}" value="1" min="1" max="${product.stock || 999}" 
                                       style="width: 70px;">
                                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 add-to-cart-btn" 
                                        data-id="${product.id}" data-name="${product.name}" 
                                        data-price="${product.price}">
                                    <i class="bi bi-plus-lg"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        searchResults.innerHTML = html;

        // Add click handlers to add-to-cart buttons
        document.querySelectorAll('.add-to-cart-btn').forEach((btn, idx) => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const container = this.closest('.product-item');
                const qtyInput = container.querySelector('.qty-selector');
                const quantity = parseInt(qtyInput.value) || 1;
                
                syncAddToCart(this.dataset.id, quantity);
                fetchUpsellSuggestions();

                // Reset quantity but keep search results
                qtyInput.value = '1';
                // productSearch.value = ''; // Don't clear search
                // loadAllProducts(); // Don't reload everything
            });
        });

        // Add Enter key support to qty-selectors
        document.querySelectorAll('.qty-selector').forEach(input => {
            input.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    const container = this.closest('.product-item');
                    const btn = container.querySelector('.add-to-cart-btn');
                    btn.click();
                }
            });
        });
    }

    // Update Cart UI Display
    function updateCartUI() {
        const cartItems = document.getElementById('cartItems');
        const emptyCart = document.getElementById('emptyCart');
        
        if (cart.length === 0) {
            cartItems.innerHTML = '';
            emptyCart.style.display = 'table-row';
            updateTotals();
            return;
        }
        
        emptyCart.style.display = 'none';
        
        let html = '';
        cart.forEach((item, index) => {
            const total = item.price * item.quantity;
            html += `
                <tr>
                    <td class="align-middle">
                        <div class="fw-bold text-dark">${item.name}</div>
                    </td>
                    <td class="align-middle">
                        <input type="number" class="form-control form-control-sm qty-input rounded-pill text-center mx-auto" 
                               data-id="${item.id}" value="${item.quantity}" min="1" style="width: 70px;">
                    </td>
                    <td class="align-middle text-end">KES ${item.price.toFixed(2)}</td>
                    <td class="align-middle text-end fw-bold">KES ${total.toFixed(2)}</td>
                    <td class="align-middle text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle remove-item px-2" data-id="${item.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        cartItems.innerHTML = html;
        
        // Add event listeners
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.dataset.id;
                const newQty = parseInt(this.value) || 1;
                syncUpdateQuantity(productId, newQty);
            });
        });
        
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = parseInt(this.dataset.id);
                syncRemoveItem(productId);
            });
        });
        
        updateTotals();
    }

    // Update Totals
    function updateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        let discount = parseFloat(document.getElementById('discount').value) || 0;
        
        // Trade-in logic
        const tradeInAmount = tradeIns.reduce((sum, item) => sum + item.value, 0);
        document.getElementById('tradeInAmountText').textContent = `- KES ${tradeInAmount.toFixed(2)}`;

        // Handle Promotion
        const promoSelect = document.getElementById('promotion');
        const selectedOption = promoSelect.options[promoSelect.selectedIndex];
        const promoType = selectedOption.dataset.type;
        const promoValue = parseFloat(selectedOption.dataset.value) || 0;
        const minSpend = parseFloat(selectedOption.dataset.min) || 0;
        
        let promoDiscount = 0;
        if (subtotal >= minSpend) {
            if (promoType === 'percentage') {
                promoDiscount = (subtotal * promoValue) / 100;
            } else {
                promoDiscount = promoValue;
            }
        }
        
        const totalDiscount = discount + promoDiscount;
        const total = Math.max(0, subtotal - totalDiscount - tradeInAmount);
        
        document.getElementById('subtotal').textContent = `KES ${subtotal.toFixed(2)}`;
        document.getElementById('totalAmount').textContent = `KES ${total.toFixed(2)}`;
        
        // Update hidden inputs
        document.getElementById('subtotalInput').value = subtotal.toFixed(2);
        document.getElementById('taxInput').value = '0';
        document.getElementById('totalInput').value = total.toFixed(2);
        document.getElementById('cartData').value = JSON.stringify(cart);
        document.getElementById('tradeInData').value = JSON.stringify(tradeIns);
        document.getElementById('tradeInAmountInput').value = tradeInAmount.toFixed(2);
        
        calculateChange();
    }

    // Calculate Change
    function calculateChange() {
        const total = parseFloat(document.getElementById('totalInput').value) || 0;
        const tendered = parseFloat(document.getElementById('amountTendered').value) || 0;
        const change = tendered - total;
        
        document.getElementById('changeDue').textContent = `KES ${Math.max(0, change).toFixed(2)}`;
    }

    // Event Listeners
    document.getElementById('discount').addEventListener('input', updateTotals);
    document.getElementById('promotion').addEventListener('change', updateTotals);
    document.getElementById('amountTendered').addEventListener('input', calculateChange);
    
    document.getElementById('clearCart').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear the cart?')) {
            syncClearCart();
        }
    });

    document.getElementById('cancelBtn').addEventListener('click', function() {
        if (cart.length > 0 && !confirm('Are you sure you want to cancel? All items will be cleared.')) {
            return;
        }
        syncClearCart();
        document.getElementById('posForm').reset();
    });

    // Payment Method Change
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const mpesaField = document.getElementById('mpesaPhoneField');
            const amountTenderedField = document.getElementById('amountTenderedField');
            const changeDueField = document.getElementById('changeDueField');
            const paymentMethodInput = document.getElementById('paymentMethod');
            
            // Sync with hidden input
            paymentMethodInput.value = this.value;
            
            if (this.value === 'mpesa') {
                mpesaField.style.display = 'block';
                document.getElementById('mpesaPhone').required = true;
                amountTenderedField.style.display = 'none';
                changeDueField.style.display = 'none';
            } else {
                mpesaField.style.display = 'none';
                document.getElementById('mpesaPhone').required = false;
                amountTenderedField.style.display = 'block';
                changeDueField.style.display = 'block';
            }
        });
    });

    // 4. Offline Queue & Sync Functions
    async function queueOfflineSale(formData) {
        const sale = {
            customer_id: formData.get('customer_id'),
            cart_data: formData.get('cart_data'),
            payment_method: formData.get('payment_method'),
            total_amount: formData.get('total_amount'),
            discount: formData.get('discount'),
            promotion_id: formData.get('promotion_id'),
            mpesa_phone: formData.get('mpesa_phone'),
            trade_in_data: formData.get('trade_in_data'),
            trade_in_amount: formData.get('trade_in_amount'),
            subtotal_amount: formData.get('subtotal_amount'),
            tax_amount: formData.get('tax_amount'),
            status: 'queued',
            timestamp: new Date().toISOString()
        };
        
        await db.sales_queue.add(sale);
        Swal.fire({
            title: 'Offline Mode',
            text: 'Sale queued locally and will sync when internet returns.',
            icon: 'info'
        });
        syncClearCart();
    }

    async function processSalesQueue() {
        if (!navigator.onLine) return;
        
        const queuedSales = await db.sales_queue.toArray();
        if (queuedSales.length === 0) return;

        console.log(`Syncing ${queuedSales.length} queued sales...`);
        
        for (const sale of queuedSales) {
            try {
                const formData = new FormData();
                for (const key in sale) {
                    if (key !== 'id' && key !== 'status' && key !== 'timestamp') {
                        formData.append(key, sale[key]);
                    }
                }
                
                const response = await fetch('{{ route("sales.store") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: formData
                });
                
                if (response.ok) {
                    await db.sales_queue.delete(sale.id);
                    console.log('Sale synced successfully');
                }
            } catch (e) {
                console.error('Failed to sync sale:', e);
            }
        }
    }

    window.addEventListener('online', () => {
        document.getElementById('offlineBanner').classList.add('d-none');
        processSalesQueue();
        syncProductsWithLocal();
    });

    window.addEventListener('offline', () => {
        document.getElementById('offlineBanner').classList.remove('d-none');
    });

    // Show banner immediately if already offline when page loads
    if (!navigator.onLine) {
        document.getElementById('offlineBanner').classList.remove('d-none');
    }

    // Form Submission
    document.getElementById('posForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (cart.length === 0) {
            alert('Please add items to the cart before completing the sale.');
            return;
        }
        
        const total = parseFloat(document.getElementById('totalInput').value) || 0;
        const paymentMethod = document.getElementById('paymentMethod').value;

        // If Offline, save to the offline store and let the service worker sync it.
        if (!navigator.onLine) {
            const fd       = new FormData(this);
            const tendered = parseFloat(document.getElementById('amountTendered')?.value) || 0;

            const payload = {
                items:           cart.map(i => ({ id: i.id, quantity: i.quantity, price: i.price })),
                customer_id:     fd.get('customer_id') || null,
                payment_method:  paymentMethod,
                tax_amount:      parseFloat(fd.get('tax_amount')) || 0,
                total_amount:    total,
                discount:        parseFloat(fd.get('discount')) || 0,
                change_amount:   paymentMethod === 'cash' ? Math.max(0, tendered - total) : 0,
                amount_tendered: tendered,
                promotion_id:    fd.get('promotion_id') || null,
                trade_ins:       tradeIns,
                trade_in_amount: parseFloat(fd.get('trade_in_amount')) || 0,
                notes:           fd.get('mpesa_phone') || null,
            };

            try {
                const localId = await OfflinePOS.saveSale(payload);
                await OfflinePOS.updateBadge();
                await OfflinePOS.requestSync();

                Swal.fire({
                    title: 'Saved Offline',
                    html: `Sale saved on this device (<strong>LOCAL-${localId}</strong>) and will sync automatically when you reconnect.`,
                    icon: 'success'
                });
                syncClearCart();
            } catch (err) {
                Swal.fire({ title: 'Offline Save Failed', text: err.message, icon: 'error' });
            }
            return;
        }
        
        // Handle M-Pesa Payment
        if (paymentMethod === 'mpesa') {
            const phone = document.getElementById('mpesaPhone').value.trim();
            
            if (!phone) {
                alert('Please enter M-Pesa phone number');
                return;
            }
            
            // Initiate M-Pesa STK Push
            await initiateMpesaPayment(phone, total);
            return;
        }
        
        // For other payment methods, validate amount tendered
        const tendered = parseFloat(document.getElementById('amountTendered').value) || 0;
        
        if (tendered < total) {
            alert('Amount tendered is less than total amount.');
            return;
        }
        
        // Submit the form normally
        this.submit();
    });
    
    // M-Pesa Payment Functions
    let mpesaCheckoutRequestId = null;
    let mpesaPollingInterval = null;
    
    async function initiateMpesaPayment(phone, amount) {
        try {
            const modal = new bootstrap.Modal(document.getElementById('mpesaModal'));
            showMpesaStatus('pending');
            modal.show();
            
            document.getElementById('mpesaPhoneDisplay').textContent = `Phone: ${phone}`;
            document.getElementById('mpesaAmountDisplay').textContent = `Amount: KES ${amount.toFixed(2)}`;
            
            // Make the actual API call to initiate STK Push
 const response = await fetch('/api/mpesa/initiate', {
    method: 'POST',
    credentials: 'include',  // ← THIS IS CRITICAL
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({
        phone_number: phone,
        amount: amount
    })
});

            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);

            // Check if response is HTML (error page)
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('text/html')) {
                const htmlResponse = await response.text();
                console.error('Received HTML response instead of JSON:', htmlResponse.substring(0, 500));
                showMpesaStatus('failed');
                document.getElementById('mpesaErrorMessage').textContent = 'Server error: API returned HTML. Check browser console for details.';
                return;
            }

            const data = await response.json();
            console.log('M-Pesa Response:', data);

            if (data.success) {
                console.log('STK Push initiated:', data);
                mpesaCheckoutRequestId = data.data.checkout_request_id;
                
                // Poll for payment status
                pollMpesaStatus(mpesaCheckoutRequestId);
            } else {
                console.error('STK Push failed:', data.message);
                showMpesaStatus('failed');
                document.getElementById('mpesaErrorMessage').textContent = data.message || 'Failed to initiate payment';
                if (data.debug_response) {
                    console.error('Debug response:', data.debug_response);
                }
            }
            
        } catch (error) {
            console.error('M-Pesa Error:', error);
            showMpesaStatus('failed');
            document.getElementById('mpesaErrorMessage').textContent = 'Error: ' + error.message;
        }
    }

    async function pollMpesaStatus(checkoutRequestId) {
        // Poll every 3 seconds for payment status
        mpesaPollingInterval = setInterval(async () => {
            try {
                const response = await fetch(`/api/mpesa/status/${checkoutRequestId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    if (data.status === 'confirmed') {
                        clearInterval(mpesaPollingInterval);
                        showMpesaStatus('success');
                        document.getElementById('mpesaReceiptNumber').textContent = 'Transaction ID: ' + (data.data.transaction_code || 'Confirmed');
                        document.getElementById('mpesaFinishBtn').style.display = 'block';
                    } else if (data.status === 'failed') {
                        clearInterval(mpesaPollingInterval);
                        showMpesaStatus('failed');
                        document.getElementById('mpesaErrorMessage').textContent = 'Payment was declined';
                    }
                }
            } catch (error) {
                console.error('Status polling error:', error);
            }
        }, 3000);
    }
    
    function showMpesaStatus(status) {
        document.getElementById('mpesaStatusPending').style.display = status === 'pending' ? 'block' : 'none';
        document.getElementById('mpesaStatusSuccess').style.display = status === 'success' ? 'block' : 'none';
        document.getElementById('mpesaStatusFailed').style.display = status === 'failed' ? 'block' : 'none';
    }
    
    // M-Pesa Modal Handlers
    document.getElementById('mpesaCancelBtn').addEventListener('click', function() {
        if (mpesaPollingInterval) {
            clearInterval(mpesaPollingInterval);
        }
        bootstrap.Modal.getInstance(document.getElementById('mpesaModal')).hide();
    });
    
    document.getElementById('mpesaFinishBtn').addEventListener('click', function() {
        // Set M-Pesa as payment method and submit form
        document.getElementById('amountTendered').value = document.getElementById('totalInput').value;
        document.getElementById('posForm').submit();
    });

    // ── Upsell Suggestions ──────────────────────────────────────────────────
    let upsellDebounce = null;

    async function fetchUpsellSuggestions() {
        if (!navigator.onLine || cart.length === 0) return;

        clearTimeout(upsellDebounce);
        upsellDebounce = setTimeout(async () => {
            try {
                const ids = cart.map(i => i.id);
                const params = ids.map(id => `product_ids[]=${id}`).join('&');
                const res  = await fetch(`/api/sales/upsell?${params}`);
                const data = await res.json();

                if (!data || data.length === 0) return;

                let html = '<ul class="list-group list-group-flush">';
                data.forEach(item => {
                    html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <div>
                                <div class="fw-bold small">${item.name}</div>
                                <div class="text-muted" style="font-size:0.75rem;">Bought together ${item.frequency}×</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-primary fw-bold small">KES ${parseFloat(item.price).toLocaleString()}</span>
                                <button type="button" class="btn btn-sm btn-primary py-0 px-2"
                                        onclick="addUpsellItem(${item.id}, '${item.name.replace(/'/g, "\\'")}', ${item.price})">
                                    + Add
                                </button>
                            </div>
                        </li>`;
                });
                html += '</ul>';

                document.getElementById('upsellBody').innerHTML = html;
                const toast = new bootstrap.Toast(document.getElementById('upsellToast'));
                toast.show();
            } catch (e) {
                // silently ignore — upsell is best-effort
            }
        }, 800);
    }

    window.addUpsellItem = async function(id, name, price) {
        await syncAddToCart(id, 1);
        bootstrap.Toast.getInstance(document.getElementById('upsellToast'))?.hide();
    };
});
</script>
@endpush