<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
$auth = new AuthController();
$auth->requireLogin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Order.php';

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

$pageTitle = 'Order Manager - Dine-In';
$pageStyles = ['pages/orders.css'];
$pageScripts = [];

ob_start();
?>
<div class="orders-page page-content">
    <div class="page-header">
        <div>
            <h1>Order Manager (Dine-In)</h1>
            <div class="page-subtitle">Manage dine-in orders, print, and mark paid</div>
        </div>
        <div class="header-actions">
            <div id="readyOrdersBadge" class="ready-orders-badge" style="display: none;">
                <span class="badge-icon">🔔</span>
                <span id="readyCount">0</span> Ready
            </div>
            <button class="btn btn-secondary" onclick="loadOrders()">Refresh</button>
        </div>
    </div>

    <div class="filters-bar">
        <div class="filter-item search-box">
            <label class="filter-label">Search Orders</label>
            <div style="position: relative;">
                <i class="fas fa-search filter-icon"></i>
                <input type="text" id="orderSearch" class="form-control" placeholder="Search order # or table">
            </div>
        </div>
        <div class="filter-item">
            <label class="filter-label">Status</label>
            <div class="select-wrapper">
                <select id="statusFilter" class="form-control">
                    <option value="">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="Preparing">Preparing</option>
                    <option value="Ready">Ready</option>
                    <option value="Served">Served</option>
                    <option value="Paid">Paid</option>
                </select>
                <i class="fas fa-chevron-down select-icon"></i>
            </div>
        </div>
        <div class="filter-item">
            <label class="filter-label">Date & Time</label>
            <div style="position: relative;">
                <input type="datetime-local" id="dateFilter" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
        </div>
    </div>

    <div id="ordersContainer" class="orders-grid">
        <div class="empty-state">
            <div class="empty-state-icon">⏳</div>
            <div class="empty-state-text">Loading orders...</div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">
                <span class="modal-icon">💳</span>
                <h2>Payment</h2>
            </div>
            <button class="modal-close" onclick="closePaymentModal()">&times;</button>
        </div>
        
        <input type="hidden" id="payOrderId" />
        
        <div class="payment-summary">
            <div class="summary-label">TOTAL AMOUNT</div>
            <div class="summary-value" id="payTotal">₱0.00</div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select id="payMethod" class="form-control custom-select">
                <option value="Cash">Cash</option>
                <option value="GCash">GCash</option>
                <option value="Card">Card</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Amount Paid</label>
            <div class="input-with-symbol">
                <span class="currency-symbol">₱</span>
                <input id="payAmount" type="number" step="0.01" min="0" class="form-control" oninput="computeChange()">
            </div>
        </div>
        
        <div class="change-display">
            <div class="change-label">CHANGE</div>
            <div class="change-value" id="payChange">₱0.00</div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePaymentModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn btn-primary btn-block" onclick="submitPayment()">
                <i class="fas fa-credit-card"></i> Process Payment
            </button>
        </div>
    </div>
</div>

<script>
let knownReadyOrders = new Set();

async function loadOrders() {
    const container = document.getElementById('ordersContainer');
    // Don't clear innerHTML if it's an auto-refresh to avoid flicker
    if (!window._isAutoRefreshing) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⏳</div><div class="empty-state-text">Loading...</div></div>';
    }
    
    const status = document.getElementById('statusFilter').value;
    const dateVal = document.getElementById('dateFilter').value;
    
    // Replace T with space for MySQL compatibility if needed, 
    // although DATE() on the backend should handle the date part regardless.
    const date = dateVal.replace('T', ' ');
    
    const actualStatus = status || '';
    const actualDate = date || '';
    
    try {
        const res = await fetch(`controllers/POSController.php?action=getOrders&status=${encodeURIComponent(actualStatus)}&date=${encodeURIComponent(actualDate)}`);
        
        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        
        const data = await res.json();
        
        // Check for NEW ready orders to play sound
        checkForNewReadyOrders(data);
        
        window._orders = data;
        renderOrders();
        updateReadyBadge(data);
    } catch (e) {
        console.error('Error loading orders:', e);
        if (!window._isAutoRefreshing) {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⚠️</div><div class="empty-state-text">Failed to load orders</div></div>';
        }
    } finally {
        window._isAutoRefreshing = false;
    }
}

function checkForNewReadyOrders(orders) {
    let hasNewReady = false;
    orders.forEach(o => {
        if (String(o.Status).toLowerCase() === 'ready') {
            if (!knownReadyOrders.has(o.OrderID)) {
                knownReadyOrders.add(o.OrderID);
                hasNewReady = true;
            }
        } else {
            // Remove from known if no longer ready (e.g. served or paid)
            knownReadyOrders.delete(o.OrderID);
        }
    });

    if (hasNewReady) {
        playNotificationSound();
    }
}

function updateReadyBadge(orders) {
    const readyCount = orders.filter(o => String(o.Status).toLowerCase() === 'ready').length;
    const badge = document.getElementById('readyOrdersBadge');
    const countSpan = document.getElementById('readyCount');
    
    if (readyCount > 0) {
        countSpan.textContent = readyCount;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function playNotificationSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
        oscillator.frequency.exponentialRampToValueAtTime(440, audioCtx.currentTime + 0.5); // A4

        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);

        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);

        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.5);
    } catch (e) {
        console.warn('Audio context blocked or not supported:', e);
    }
}

function renderOrders() {
    const container = document.getElementById('ordersContainer');
    const q = (document.getElementById('orderSearch').value || '').toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    let all = window._orders || [];
    
    console.log('=== RENDER ORDERS DEBUG ===');
    console.log('All orders before type filter:', all.length);
    
    // Show both Dine-In and Take-Out on this page
    console.log('Showing both Dine-In and Take-Out orders');
    
    // Then filter by status if selected
    if (statusFilter) {
        all = all.filter(o => o.Status === statusFilter);
        console.log('Orders after status filter (' + statusFilter + '):', all.length);
    } else {
        console.log('No status filter applied');
    }
    
    const filtered = all.filter(o => {
        if (!q) return true;
        const t = [
            o.OrderNumber || '',
            o.TableNumber || '',
            o.CashierName || ''
        ].join(' ').toLowerCase();
        return t.includes(q);
    });
    console.log('Orders after search filter:', filtered.length);
    
    if (filtered.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🍽️</div><div class="empty-state-text">No orders found</div></div>';
        return;
    }
    container.innerHTML = '';
    filtered.forEach(o => container.appendChild(orderCard(o)));
}

function orderCard(order) {
    const div = document.createElement('div');
    const isReady = String(order.Status || '').toLowerCase() === 'ready';
    div.className = `order-card ${isReady ? 'is-ready' : ''}`;
    const statusClass = 'status-' + String(order.Status || '').toLowerCase();
    
    // Format date for display
    const dateObj = new Date(order.OrderDate);
    const timeStr = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    
    div.innerHTML = `
        <div class="order-card-header">
            <div class="order-num">#${order.OrderNumber}</div>
            <div class="status-container">
                ${isReady ? '<span class="ready-dot"></span>' : ''}
                <span class="status-badge ${statusClass}">${order.Status}</span>
            </div>
        </div>
        <div class="order-meta">
            <div class="meta">
                <span class="meta-icon"><i class="fas fa-chair"></i></span>
                <span>Table: <strong>${order.TableNumber || '—'}</strong></span>
            </div>
            <div class="meta">
                <span class="meta-icon"><i class="fas fa-clock"></i></span>
                <span>Time: <strong>${timeStr}</strong></span>
            </div>
        </div>
        <div class="order-total">₱${parseFloat(order.TotalAmount || 0).toFixed(2)}</div>
        <div class="order-actions">
            <button class="btn btn-secondary" onclick="viewOrderDetails(${order.OrderID})" title="View Details">
                <i class="fas fa-eye"></i> View
            </button>
            
            ${order.Status !== 'Paid' ? `
                <button class="btn btn-primary" onclick="openPaymentModal(${order.OrderID}, ${parseFloat(order.TotalAmount || 0)})" title="Mark as Paid">
                    <i class="fas fa-check-circle"></i> Pay
                </button>
            ` : ''}
            <button class="btn btn-danger" onclick="deleteOrder(${order.OrderID})" title="Delete Order">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    `;
    return div;
}

// Payment modal
function openPaymentModal(orderId, total) {
    const m = document.getElementById('paymentModal');
    document.getElementById('payOrderId').value = orderId;
    document.getElementById('payTotal').textContent = '₱' + (parseFloat(total)||0).toFixed(2);
    document.getElementById('payAmount').value = (parseFloat(total)||0).toFixed(2);
    document.getElementById('payChange').textContent = '₱0.00';
    m.style.display = 'flex';
    computeChange();
}
function closePaymentModal() {
    const m = document.getElementById('paymentModal');
    m.style.display = 'none';
}
function computeChange() {
    const total = parseFloat(document.getElementById('payTotal').textContent.replace(/[₱,]/g,''))||0;
    const paid = parseFloat(document.getElementById('payAmount').value)||0;
    const change = Math.max(0, paid - total);
    document.getElementById('payChange').textContent = '₱' + change.toFixed(2);
}
async function submitPayment() {
    const orderId = document.getElementById('payOrderId').value;
    const method = document.getElementById('payMethod').value || 'Cash';
    const total = parseFloat(document.getElementById('payTotal').textContent.replace(/[₱,]/g,''))||0;
    const amt = parseFloat(document.getElementById('payAmount').value)||0;
    if (amt < total) { alert('Amount paid is less than total'); return; }
    try {
        const res = await fetch('controllers/POSController.php?action=updateOrderStatus', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_id: orderId, status: 'Paid' }) });
        const r = await res.json();
        if (r.success) {
            // Load details and print with payment info
            const det = await (await fetch(`controllers/POSController.php?action=getOrderDetails&order_id=${orderId}`)).json();
            const items = (det.items || []).map(i => ({
                ItemName: i.ItemName,
                UnitPrice: parseFloat(i.UnitPrice) || 0,
                Quantity: parseInt(i.Quantity) || 0,
                Notes: i.Notes
            }));
            printReceipt({
                orderId: det.order?.OrderID,
                orderNumber: det.order?.OrderNumber,
                orderType: det.order?.TypeName || 'DINE-IN',
                tableNumber: det.order?.TableNumber || '',
                cashier: det.order?.CashierName || '',
                date: det.order?.OrderDate,
                items,
                subtotal: items.reduce((s,it)=>s+(it.UnitPrice*it.Quantity),0),
                tax: 0,
                total: items.reduce((s,it)=>s+(it.UnitPrice*it.Quantity),0),
                payment: { method, amountPaid: amt, change: amt - total }
            });
            closePaymentModal();
            loadOrders();
        } else {
            alert('Failed to mark as paid');
        }
    } catch (e) { alert('Error'); }
}

async function viewAndPrint(orderId) {
    try {
        const res = await fetch(`controllers/POSController.php?action=getOrderDetails&order_id=${orderId}`);
        const data = await res.json();
        if (!data || !data.order) { alert('Order not found'); return; }
        const items = (data.items || []).map(i => ({
            ItemName: i.ItemName,
            UnitPrice: parseFloat(i.UnitPrice) || 0,
            Quantity: parseInt(i.Quantity) || 0,
            Notes: i.Notes
        }));
        printReceipt({
            orderId: data.order.OrderID,
            orderNumber: data.order.OrderNumber,
            orderType: data.order.TypeName || 'DINE-IN',
            tableNumber: data.order.TableNumber || '',
            cashier: data.order.CashierName || '',
            date: data.order.OrderDate,
            items,
            subtotal: items.reduce((s,it)=>s+(it.UnitPrice*it.Quantity),0),
            tax: 0,
            total: items.reduce((s,it)=>s+(it.UnitPrice*it.Quantity),0),
            payment: data.order.Status === 'Paid' ? { 
                method: data.order.PaymentMethod || 'Cash',
                amountPaid: parseFloat(data.order.AmountPaid) || data.order.TotalAmount,
                change: (parseFloat(data.order.AmountPaid) || data.order.TotalAmount) - data.order.TotalAmount
            } : null
        });
    } catch (e) {
        alert('Failed to load order details');
    }
}

async function viewOrderDetails(orderId) {
    try {
        const res = await fetch(`controllers/POSController.php?action=getOrderDetails&order_id=${orderId}`);
        const data = await res.json();
        if (!data || !data.order) { alert('Order not found'); return; }
        
        showOrderDetailsModal(data);
    } catch (e) {
        alert('Failed to load order details');
    }
}

function showOrderDetailsModal(data) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.cssText = `
        display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;
    `;
    
    const itemsHtml = (data.items || []).map((item, index) => {
        let notesDisplay = '';
        if (item.Notes && item.Notes.startsWith('{')) {
            try {
                const parsed = JSON.parse(item.Notes);
                let parts = [];
                if (parsed.flavor) parts.push(`Flavor: ${parsed.flavor.Name}`);
                if (parsed.flavors && parsed.flavors.length > 0) parts.push(`Flavors: ${parsed.flavors.map(f => f.Name).join(', ')}`);
                if (parsed.modifiers && parsed.modifiers.length > 0) parts.push(`Add-ons: ${parsed.modifiers.map(m => m.Name).join(', ')}`);
                if (parsed.text) parts.push(`Notes: ${parsed.text}`);
                notesDisplay = parts.length > 0 ? parts.join('<br>') : '';
            } catch (e) {
                notesDisplay = item.Notes;
            }
        } else if (item.Notes) {
            notesDisplay = item.Notes;
        }

        return `
        <div class="order-item-row" data-item-id="${item.OrderDetailID}" data-index="${index}">
            <div class="item-info">
                <div class="item-name">${item.ItemName}</div>
                <div class="item-details">
                    Qty: ${item.Quantity} × ₱${parseFloat(item.UnitPrice).toFixed(2)} = ₱${(item.Quantity * item.UnitPrice).toFixed(2)}
                </div>
                ${notesDisplay ? `<div class="item-notes">${notesDisplay}</div>` : ''}
            </div>
            <div class="item-actions">
                ${data.order.Status !== 'Paid' && data.order.Status !== 'Ready' ? `
                    <button class="btn btn-sm btn-warning" onclick="cancelOrderItem(${item.OrderDetailID}, ${index})" title="Cancel Item">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                ` : ''}
            </div>
        </div>
    `}).join('');
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; padding: 0;">
            <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0;">Order #${data.order.OrderNumber}</h3>
                    <div style="color: #64748b; font-size: 14px;">
                        Table: ${data.order.TableNumber || 'Take Out'} | Status: ${data.order.Status}
                    </div>
                </div>
                <button onclick="this.closest('.modal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
            </div>
            <div style="padding: 20px;">
                <h4 style="margin-bottom: 15px;">Order Items</h4>
                <div class="order-items-list">
                    ${itemsHtml}
                </div>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold;">
                        <span>Total:</span>
                        <span>₱${parseFloat(data.order.TotalAmount || 0).toFixed(2)}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

async function cancelOrderItem(itemDetailId, index) {
    if (!confirm('Are you sure you want to cancel this item?')) return;
    
    try {
        const res = await fetch('controllers/POSController.php?action=cancelOrderItem', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_detail_id: itemDetailId })
        });
        
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        
        const result = await res.json();
        
        if (result.success) {
            // Remove the item row from the modal entirely
            const itemRow = document.querySelector(`[data-item-id="${itemDetailId}"]`);
            if (itemRow) {
                itemRow.style.transition = 'opacity 0.3s ease';
                itemRow.style.opacity = '0';
                setTimeout(() => itemRow.remove(), 300);
            }
            // Refresh the orders list
            loadOrders();
        } else {
            alert('Failed to cancel item: ' + (result.message || 'Unknown error'));
        }
    } catch (e) {
        alert('Error cancelling item: ' + e.message);
    }
}

function formatMoney(n) { 
    const num = parseFloat(n) || 0;
    return num % 1 === 0 ? num.toString() : num.toFixed(2); 
}

function printReceipt(data) {
    const w = window.open('', 'PRINT', 'height=500,width=350');
    const itemsHtml = data.items.map(it => {
        const MAX_NAME_LENGTH = 20;
        let name = (it.ItemName || '').trim();
        if (name.length > MAX_NAME_LENGTH) {
            name = name.slice(0, MAX_NAME_LENGTH - 1) + '…';
        }

        const qty = String(it.Quantity);
        const unit = formatMoney(it.UnitPrice);
        const total = formatMoney(it.UnitPrice * it.Quantity);
        const isCancelled = (it.Status || '').toLowerCase() === 'cancelled';
        
        // Add CANCELLED prefix for cancelled items
        const displayName = isCancelled ? `CANCELLED ${name}` : name;
        
        let extras = '';
        const notes = it.Notes || '';
        if (notes.startsWith('{')) {
            try {
                const parsed = JSON.parse(notes);
                
                // Handle single flavor (backward compatibility)
                if (parsed.flavor) {
                    extras += `<div class="row mono" style="font-size:9px;"><span class="left">  > ${parsed.flavor.Name}</span></div>`;
                }
                
                // Handle multiple flavors
                if (parsed.flavors && parsed.flavors.length > 0) {
                    const flavorBullets = parsed.flavors.map(f => `• ${f.Name}`).join(' ');
                    extras += `<div class="row mono" style="font-size:9px;"><span class="left">  > ${flavorBullets}</span></div>`;
                }
                
                if (parsed.modifiers && parsed.modifiers.length > 0) {
                    parsed.modifiers.forEach(m => {
                        extras += `<div class="row mono" style="font-size:9px;"><span class="left">  + ${m.Name}</span></div>`;
                    });
                }
                
                // Handle text notes
                if (parsed.text) {
                    extras += `<div class="row mono" style="font-size:9px;"><span class="left">  * ${parsed.text}</span></div>`;
                }
            } catch(e) {}
        } else if (notes) {
            extras += `<div class="row mono" style="font-size:9px;"><span class="left">  * ${notes}</span></div>`;
        }

        return `
            <div class="row mono" ${isCancelled ? 'style="text-decoration: line-through; color: #dc2626;"' : ''}>
                <span class="left">${escapeHtml(displayName)}</span>
                <span class="right-cols">
                    <span class="col-qty">${qty}</span>
                    <span class="col-unit">${unit}</span>
                    <span class="col-total">${total}</span>
                </span>
            </div>
            ${extras}
        `;
    }).join('');
    const isDineIn = (data.orderType || '').toUpperCase() === 'DINE-IN';
    const fbUrl = 'https://www.facebook.com/EatUnwindTea';
    const qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=3x3&data=' + encodeURIComponent(fbUrl);
    const dateText = new Date(data.date).toLocaleString('en-US', { month:'2-digit', day:'2-digit', year:'2-digit', hour:'2-digit', minute:'2-digit', hour12:true });
    const html = `
<html>
<head>
<meta charset="utf-8">
<title>Receipt</title>
<style>
@page { margin: 0; }
body { margin: 0; font-family: Arial, sans-serif; font-size: 9px; }
.receipt { width: 45mm; padding: 2px 0; margin: 1; }
.center { text-align: center; }
.divider { border-top: 1px solid #000; margin: 1px 0; }
.row { display:flex; justify-content:space-between; font-size: 9px; }
.left { word-break: break-all; flex: 1; }
.right { text-align:right; }
.total-row { display:flex; justify-content:space-between; font-weight: bold; font-size: 9px; }
.title { font-weight:bold; font-size:10px; }
.qr { display:flex; justify-content:center; margin: 1px 0; }
.qr img { width: 2px; height: 2px; }
.mono { font-family: monospace; font-size: 9px; }
.cols { display:flex; justify-content:space-between; font-weight:bold; font-size: 9px; }
.cols .c-left { flex:1; word-break: break-all; }
.cols .c-right { width:22ch; display:inline-flex; justify-content:flex-end; }
.right-cols { display:inline-flex; width:22ch; justify-content:flex-end; }
.col-qty { width:4ch; text-align:right; }
.col-unit { width:7ch; text-align:right; }
.col-total { width:9ch; text-align:right; }
.band { text-align:center; font-weight:bold; margin: 3px 0; font-size: 9px; }

/* Order Details Modal Styles */
.order-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 10px;
    background: #f8fafc;
}

.order-item-row:hover {
    background: #f1f5f9;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 4px;
}

.item-details {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 4px;
}

.item-notes {
    color: #0d9488;
    font-size: 12px;
    font-style: italic;
}

.item-actions {
    margin-left: 12px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.btn-warning {
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-warning:hover {
    background: #d97706;
}
</style>
</head>
<body onload="window.focus(); window.print(); setTimeout(()=>window.close(), 300);">
<div class="receipt">
    <div class="center title">Eat Unwind Tea</div>
    <div class="center" style="font-size:8px;">Apostol Street Poblacion 1</div>
    <div class="center" style="font-size:8px;">Phone: +639-052-883-320</div>
    <div class="center" style="font-size:8px;">For more info, visit our Facebook</div>
    <div class="divider"></div>
    ${data.orderId ? `<div><span style="font-weight:bold;">ORDER No:</span> ${data.orderId}</div>` : ``}
    <div><span style="font-weight:bold;">Order #:</span> ${data.orderNumber}</div>
    <div class="band">${(data.orderType||'').toUpperCase()}</div>
    ${isDineIn && data.tableNumber ? `<div style="text-align:center;font-weight:bold;">TABLE No: ${data.tableNumber}</div>` : ``}
    <div><span style="font-weight:bold;">DATE:</span> ${dateText}</div>
    ${data.cashier ? `<div><span style="font-weight:bold;">CASHIER:</span> ${escapeHtml(data.cashier)}</div>` : ``}
    <div class="divider"></div>
    <div class="cols mono">
        <span class="c-left">ITEM</span>
        <span class="c-right">
            <span class="col-qty">QTY</span>
            <span class="col-unit">UNIT</span>
            <span class="col-total">TOTAL</span>
        </span>
    </div>
    <div class="divider"></div>
    ${itemsHtml}
    <div class="divider"></div>
    <div class="row"><span style="font-weight:bold;">SUBTOTAL:</span><span style="font-weight:bold;">₱${formatMoney(data.subtotal)}</span></div>
    <div class="row"><span style="font-weight:bold;">TOTAL:</span><span style="font-weight:bold;">₱${formatMoney(data.total)}</span></div>
    <div class="divider"></div>
    <div style="font-weight:bold; font-size:9px;">PAYMENT DETAILS</div>
    <div class="row"><span style="font-weight:bold;">METHOD:</span><span style="font-weight:bold;">${escapeHtml(data.payment?.method || 'N/A')}</span></div>
    <div class="row"><span style="font-weight:bold;">AMOUNT PAID:</span><span style="font-weight:bold;">₱${formatMoney(data.payment?.amountPaid || 0)}</span></div>
    <div class="row"><span style="font-weight:bold;">CHANGE:</span><span style="font-weight:bold;">₱${formatMoney(data.payment?.change || 0)}</span></div>
    <div class="divider"></div>
    <div class="center" style="margin-top:2px; font-size:8px;">Thank you for dining with us!</div>
    <div class="center" style="font-size:7px;">Receipt #${String(data.orderNumber || '').slice(-8)}</div>
  </div>
</body>
</html>`;
    w.document.write(html);
    w.document.close();
}

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = String(text ?? '');
    return d.innerHTML;
}

async function deleteOrder(orderId) {
    if (confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
        try {
            const res = await fetch(`controllers/POSController.php?action=deleteOrder&order_id=${orderId}`, {
                method: 'DELETE'
            });
            const result = await res.json();
            if (result.success) {
                alert('Order deleted successfully');
                loadOrders(); // Refresh the orders list
            } else {
                alert('Failed to delete order: ' + (result.message || 'Unknown error'));
            }
        } catch (e) {
            alert('Error deleting order. Please try again.');
        }
    }
}

let refreshTimer;
function startAutoRefresh() {
    if (refreshTimer) clearInterval(refreshTimer);
    refreshTimer = setInterval(() => {
        // Only refresh if no modal is open and not searching
        const modal = document.getElementById('paymentModal');
        const search = document.getElementById('orderSearch').value;
        if (modal.style.display !== 'flex' && !search) {
            window._isAutoRefreshing = true;
            loadOrders();
        }
    }, 10000); // Every 10 seconds
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('statusFilter').addEventListener('change', loadOrders);
    document.getElementById('dateFilter').addEventListener('change', loadOrders);
    document.getElementById('orderSearch').addEventListener('input', renderOrders);
    loadOrders();
    startAutoRefresh();
});
</script>
<?php
$pageContent = ob_get_clean();
?>
