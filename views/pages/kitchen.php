<?php
// AuthController is already handled in app.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Order.php';

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

// Get kitchen orders (Pending and Ready)
$kitchenOrders = $order->getKitchenOrders();

ob_start();
?>

<style>
/* Kitchen Page - Fixed Scrolling Layout */
.kitchen-page {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #f1f5f9;
}

.kitchen-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 20px;
    gap: 20px;
    min-height: 0; /* Critical for Firefox */
    height: 100%;
}

/* Fixed Header - No Scroll */
.kitchen-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
    border: 1px solid #e2e8f0;
}

.kitchen-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.kitchen-icon {
    font-size: 28px;
    background: #3b82f6;
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.kitchen-title h1 {
    margin: 0;
    color: #1e293b;
    font-size: 22px;
    font-weight: 700;
}

.kitchen-subtitle {
    margin: 2px 0 0 0;
    color: #64748b;
    font-size: 13px;
}

/* Stats Cards */
.kitchen-stats {
    display: flex;
    gap: 12px;
}

.stat-card {
    background: white;
    padding: 10px 16px;
    border-radius: 10px;
    text-align: center;
    min-width: 90px;
    border: 1px solid #e2e8f0;
}

.stat-card.pending {
    border-top: 4px solid #f59e0b;
    background: #fffbeb;
}

.stat-card.ready {
    border-top: 4px solid #10b981;
    background: #f0fdf4;
}

.stat-number {
    font-size: 22px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.2;
}

.stat-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

/* Scrollable Orders Container */
.kitchen-orders {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 4px 4px 8px 4px;
    min-height: 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Order Cards - Slate Gray for Better Visibility */
.kitchen-order-card {
    background: #475569; /* Lighter Slate Gray */
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    border: 1px solid #64748b;
    border-left: 8px solid #f59e0b;
    transition: all 0.2s ease;
    flex-shrink: 0;
    color: #f8fafc;
}

.kitchen-order-card.ready {
    border-left-color: #10b981;
}

.kitchen-order-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    transform: translateY(-2px);
    border-color: #94a3b8;
}

/* Slate Order Header */
.order-header {
    padding: 14px 16px;
    background: #334155; /* Darker than card body */
    border-bottom: 1px solid #64748b;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-radius: 12px 12px 0 0;
}

.order-number {
    font-size: 22px;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 6px;
}

.order-type {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.type-badge {
    background: #3b82f6;
    color: white;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 700;
}

.type-badge.dinein {
    background: #8b5cf6;
}

.type-badge.takeout {
    backgro.kitchen-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.item-quantity {
    background: #334155;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-weight: 800;
    color: #93c5fd; /* Even brighter blue */
    font-size: 0.875rem;
}

.item-name {
    font-weight: 700;
    color: #ffffff;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.modifier-tag {
    background: #334155;
    border: 1px solid #64748b;
    color: #cbd5e1;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
}

.order-actions {
    padding: 1.25rem;
    background: #334155;
    border-top: 1px solid #64748b;
}

.kitchen-btn {
    width: 100%;
    padding: 0.75rem;
    border-radius: 12px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.ready-btn { background: var(--primary); color: white; }
.ready-btn:hover { background: var(--primary-hover); }

}

.table-badge {
    background: #3b82f6;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
}

.order-status {
    text-align: right;
    min-width: 120px;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 6px;
    display: inline-block;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #f59e0b;
}

.status-badge.ready {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.order-time {
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    background: #334155;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-block;
}

/* Compact Order Items */
.order-items {
    padding: 12px 16px;
    background: white;
}

.kitchen-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 10px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.kitchen-item:last-child {
    margin-bottom: 0;
}

.item-quantity {
    background: #3b82f6;
    color: white;
    min-width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 16px;
    flex-shrink: 0;
}

.item-details {
    flex: 1;
    min-width: 0;
}

.item-name {
    font-weight: 700;
    color: #1f2937;
    font-size: 15px;
    margin-bottom: 4px;
    line-height: 1.3;
}

.item-notes {
    color: #dc2626;
    font-size: 13px;
    font-weight: 600;
    background: #fef2f2;
    padding: 4px 8px;
    border-radius: 6px;
    border-left: 3px solid #dc2626;
    margin-top: 6px;
}

.item-variant {
    margin-top: 4px;
    font-size: 14px;
}

.variant-label {
    color: #64748b;
    font-weight: 600;
}

.variant-value {
    color: #0f172a;
    font-weight: 700;
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
}

.item-modifiers {
    margin-top: 6px;
}

.modifiers-label {
    display: block;
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 4px;
}

.modifiers-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.modifier-tag {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
}

/* Compact Order Actions */
.order-actions {
    padding: 12px 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    text-align: right;
}

.kitchen-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    min-width: 160px;
}

.ready-btn {
    background: #f59e0b;
    color: white;
}

.ready-btn:hover {
    background: #d97706;
}

.complete-btn {
    background: #10b981;
    color: white;
}

.complete-btn:hover {
    background: #059669;
}

/* Empty State */
.empty-kitchen {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
    background: white;
    border-radius: 12px;
    border: 2px dashed #cbd5e1;
}

.empty-icon {
    font-size: 60px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-kitchen h3 {
    color: #1f2937;
    margin-bottom: 8px;
    font-size: 20px;
    font-weight: 700;
}

.empty-kitchen p {
    font-size: 14px;
    color: #64748b;
}

/* Notification */
.kitchen-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    font-size: 14px;
    z-index: 9999;
    transform: translateX(120%);
    transition: transform 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    max-width: 300px;
}

.kitchen-notification.show {
    transform: translateX(0);
}

.kitchen-notification.success {
    background: #10b981;
}

.kitchen-notification.error {
    background: #ef4444;
}

.kitchen-notification.info {
    background: #3b82f6;
}

/* Responsive */
@media (max-width: 768px) {
    .kitchen-container {
        padding: 12px;
    }
    
    .kitchen-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .kitchen-stats {
        width: 100%;
        justify-content: center;
    }
    
    .order-header {
        flex-direction: column;
        gap: 12px;
    }
    
    .order-status {
        text-align: left;
    }
    
    .kitchen-btn {
        width: 100%;
        min-width: auto;
    }
    
    .kitchen-notification {
        left: 20px;
        right: 20px;
        max-width: none;
    }
}

/* Custom Scrollbar */
.kitchen-orders::-webkit-scrollbar {
    width: 8px;
}

.kitchen-orders::-webkit-scrollbar-track {
    background: #e2e8f0;
    border-radius: 10px;
}

.kitchen-orders::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 10px;
}

.kitchen-orders::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}
</style>

<div class="kitchen-page">
    <div class="kitchen-container">
        <!-- Fixed Header -->
        <div class="kitchen-header">
            <div class="kitchen-title">
                <div class="kitchen-icon">👨‍🍳</div>
                <div>
                    <h1>Kitchen Display</h1>
                    <p class="kitchen-subtitle">Active Orders - What to Cook</p>
                </div>
            </div>
            <div class="kitchen-stats">
                <div class="stat-card pending">
                    <div class="stat-number" id="pending-count"><?= count(array_filter($kitchenOrders, fn($o) => $o['Status'] === 'Pending')) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card ready">
                    <div class="stat-number" id="ready-count"><?= count(array_filter($kitchenOrders, fn($o) => $o['Status'] === 'Ready')) ?></div>
                    <div class="stat-label">Ready</div>
                </div>
            </div>
        </div>

        <!-- Scrollable Orders -->
        <div class="kitchen-orders" id="kitchenOrders">
            <?php if (empty($kitchenOrders)): ?>
                <div class="empty-kitchen">
                    <div class="empty-icon">🍽️</div>
                    <h3>No Active Orders</h3>
                    <p>All orders have been completed!</p>
                </div>
            <?php else: ?>
                <?php foreach ($kitchenOrders as $orderData): ?>
                    <?php 
                    $orderItems = [];
                    $tempOrder = new Order($db);
                    $tempOrder->OrderID = $orderData['OrderID'];
                    // Exclude cancelled items from kitchen display
                    $orderItems = $tempOrder->getOrderItems(true);
                    ?>
                    
                    <div class="kitchen-order-card <?= $orderData['Status'] === 'Ready' ? 'ready' : 'pending' ?>" 
                         data-order-id="<?= $orderData['OrderID'] ?>"
                         data-order-number="<?= htmlspecialchars($orderData['OrderNumber']) ?>">
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-number">#<?= htmlspecialchars($orderData['OrderNumber']) ?></div>
                                <div class="order-type">
                                    <span class="type-badge <?= strtolower(str_replace('-', '', $orderData['TypeName'])) ?>">
                                        <?= htmlspecialchars($orderData['TypeName']) ?>
                                    </span>
                                    <?php if ($orderData['TableNumber']): ?>
                                        <span class="table-badge">Table <?= htmlspecialchars($orderData['TableNumber']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="order-status">
                                <div class="status-badge <?= $orderData['Status'] === 'Ready' ? 'ready' : 'pending' ?>">
                                    <?= $orderData['Status'] === 'Ready' ? '🔔 Ready' : '👨‍🍳 Cooking' ?>
                                </div>
                                <div class="order-time">
                                    <?= date('h:i A', strtotime($orderData['OrderDate'])) ?>
                                </div>
                            </div>
                        </div>

                        <div class="order-items">
                            <?php foreach ($orderItems as $item): ?>
                                <?php 
                                    $variantInfo = '';
                                    $modifiersInfo = [];
                                    
                                    // Try to parse JSON from Notes
                                    $notes = $item['Notes'] ?? '';
                                    if (!empty($notes) && strpos($notes, '{') === 0) {
                                        $data = json_decode($notes, true);
                                        if ($data) {
                                            // Handle single flavor (backward compatibility)
                                            if (!empty($data['flavor'])) {
                                                $variantInfo = $data['flavor']['Name'];
                                            }
                                            // Handle multiple flavors
                                            if (!empty($data['flavors'])) {
                                                $flavorNames = array_map(function($f) {
                                                    return $f['Name'];
                                                }, $data['flavors']);
                                                $variantInfo = implode(', ', $flavorNames);
                                            }
                                            if (!empty($data['modifiers'])) {
                                                foreach ($data['modifiers'] as $mod) {
                                                    $modifiersInfo[] = $mod['Name'];
                                                }
                                            }
                                        }
                                    }
                                ?>
                                <div class="kitchen-item">
                                    <div class="item-quantity"><?= $item['Quantity'] ?>x</div>
                                    <div class="item-details">
                                        <div class="item-name"><?= htmlspecialchars($item['ItemName']) ?></div>
                                        
                                        <?php if (!empty($variantInfo)): ?>
                                            <div class="item-variant">
                                                <span class="variant-label">Variant:</span> 
                                                <span class="variant-value"><?= htmlspecialchars($variantInfo) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($modifiersInfo)): ?>
                                            <div class="item-modifiers">
                                                <span class="modifiers-label">Add-ons:</span>
                                                <div class="modifiers-list">
                                                    <?php foreach ($modifiersInfo as $modName): ?>
                                                        <span class="modifier-tag">+ <?= htmlspecialchars($modName) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($notes) && strpos($notes, '{') !== 0): ?>
                                            <div class="item-notes">📝 <?= htmlspecialchars($notes) ?></div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($notes) && strpos($notes, '{') === 0): ?>
                                            <?php 
                                                $data = json_decode($notes, true);
                                                if ($data) {
                                                    if (!empty($data['text'])): ?>
                                                        <div class="item-notes">📝 <?= htmlspecialchars($data['text']) ?></div>
                                                    <?php endif; ?>
                                                <?php
                                                }
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-actions">
                            <?php if ($orderData['Status'] === 'Pending'): ?>
                                <button class="kitchen-btn ready-btn" onclick="updateOrderStatus(<?= $orderData['OrderID'] ?>, 'Ready', this)">
                                    🔔 Mark Ready
                                </button>
                            <?php else: ?>
                                <button class="kitchen-btn complete-btn" onclick="updateOrderStatus(<?= $orderData['OrderID'] ?>, 'Paid', this)">
                                    ✅ Complete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let refreshInterval;
let isUpdating = false;

function updateOrderStatus(orderId, newStatus, button) {
    if (isUpdating) return;
    isUpdating = true;
    
    // Disable button to prevent double-click
    button.disabled = true;
    button.style.opacity = '0.5';
    
    fetch('controllers/OrderController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Order updated successfully!', 'success');
            
            // Find and remove the order card
            const orderCard = button.closest('.kitchen-order-card');
            if (orderCard) {
                orderCard.style.transition = 'all 0.3s ease';
                orderCard.style.opacity = '0';
                orderCard.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    orderCard.remove();
                    updateKitchenStats();
                    
                    // Show empty state if no orders left
                    const remainingOrders = document.querySelectorAll('.kitchen-order-card').length;
                    if (remainingOrders === 0) {
                        const ordersContainer = document.getElementById('kitchenOrders');
                        ordersContainer.innerHTML = `
                            <div class="empty-kitchen">
                                <div class="empty-icon">🍽️</div>
                                <h3>No Active Orders</h3>
                                <p>All orders have been completed!</p>
                            </div>
                        `;
                    }
                }, 300);
            }
        } else {
            showNotification('Failed to update order: ' + (data.message || 'Unknown error'), 'error');
            button.disabled = false;
            button.style.opacity = '1';
        }
    })
    .catch(error => {
        console.error('Error updating order:', error);
        showNotification('Error updating order status', 'error');
        button.disabled = false;
        button.style.opacity = '1';
    })
    .finally(() => {
        isUpdating = false;
    });
}

function updateKitchenStats() {
    const pendingCount = document.querySelectorAll('.kitchen-order-card.pending').length;
    const readyCount = document.querySelectorAll('.kitchen-order-card.ready').length;
    
    const pendingStat = document.getElementById('pending-count');
    const readyStat = document.getElementById('ready-count');
    
    if (pendingStat) pendingStat.textContent = pendingCount;
    if (readyStat) readyStat.textContent = readyCount;
}

function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.kitchen-notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `kitchen-notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Hide and remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function refreshKitchenData() {
    if (isUpdating) return;
    
    fetch(window.location.href + '?refresh=' + Date.now(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Get new orders container
        const newOrders = doc.querySelector('.kitchen-orders');
        const newPending = doc.getElementById('pending-count');
        const newReady = doc.getElementById('ready-count');
        
        if (newOrders) {
            const currentOrders = document.getElementById('kitchenOrders');
            if (currentOrders) {
                // Fade out
                currentOrders.style.transition = 'opacity 0.2s ease';
                currentOrders.style.opacity = '0.5';
                
                setTimeout(() => {
                    currentOrders.innerHTML = newOrders.innerHTML;
                    currentOrders.style.opacity = '1';
                    
                    // Update stats
                    if (newPending) document.getElementById('pending-count').textContent = newPending.textContent;
                    if (newReady) document.getElementById('ready-count').textContent = newReady.textContent;
                    
                    // Show subtle notification
                    if (!document.hidden) {
                        showNotification('Orders refreshed', 'info');
                    }
                }, 200);
            }
        }
    })
    .catch(error => {
        console.error('Error refreshing kitchen data:', error);
    });
}

function startAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    refreshInterval = setInterval(refreshKitchenData, 30000); // Refresh every 30 seconds
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
    
    // Handle visibility change
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoRefresh();
        } else {
            refreshKitchenData(); // Refresh immediately when visible
            startAutoRefresh();
        }
    });
    
    // Add keyboard shortcut (Ctrl+R)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'r' && !e.altKey && !e.shiftKey) {
            e.preventDefault();
            refreshKitchenData();
            showNotification('Manual refresh triggered', 'info');
        }
    });
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
});
</script>

<?php
$pageContent = ob_get_clean();
$pageTitle = 'Kitchen Display';
$pageStyles = ['app-layout.css'];
$pageScripts = [];
?>