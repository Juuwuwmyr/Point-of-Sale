<?php
// AuthController is already handled in app.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/MenuItem.php';

$database = new Database();
$db = $database->getConnection();
$category = new Category($db);
$menuItem = new MenuItem($db);

$categories = $category->getAll();
// $currentUser is already available from app.php

ob_start();
?>
<div class="pos-container">
    <div class="main-content">
        <div class="header">
            <h2>Point of Sale</h2>
            <div style="display: flex; gap: 12px;"></div>
        </div>

        <div class="pos-body">
            <div class="categories-sidebar">
                <div class="categories-header">
                    <span class="categories-header-icon">📋</span>
                    <span class="categories-header-title">Categories</span>
                </div>
                <div class="categories-container" id="categoriesContainer"></div>
            </div>

            <div class="menu-section">
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search menu items...">
                </div>
                <div class="menu-items" id="menuItems">
                    <div class="empty-state">
                        <div class="empty-state-icon">🍽️</div>
                        <div class="empty-state-text">Loading menu items...</div>
                    </div>
                </div>
            </div>

            <div class="order-section">
                <div class="order-options">
                    <div class="order-type-toggle">
                        <button class="toggle-btn" id="dineInBtn">Dine-In</button>
                        <button class="toggle-btn" id="takeOutBtn">Take-Out</button>
                    </div>
                    <input type="text" id="orderTableNumber" class="input-modern" placeholder="Table #">
                </div>
                <div class="order-header">
                    <h3>Current Order</h3>
                    <div class="order-stats" id="itemCount">0 items</div>
                </div>
                <div class="order-items" id="orderItems">
                    <div class="empty-state">
                        <div class="empty-state-icon">🛒</div>
                        <div class="empty-state-text">No items added yet</div>
                        <div class="empty-state-subtext">Click menu items to add to order</div>
                    </div>
                </div>
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal">₱0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="total">₱0.00</span>
                    </div>
                    <textarea id="orderNotes" class="order-notes" placeholder="Add order notes..."></textarea>
                    <div class="order-actions">
                        <button class="btn-action btn-clear" onclick="clearOrder()">
                            <span>🗑️</span> Clear
                        </button>
                        <button id="checkoutBtn" class="btn-action btn-process" style="display:none;" onclick="checkoutOrder()">
                            <span>🧾</span> Checkout
                        </button>
                        <button id="payBtn" class="btn-action btn-process" style="display:none;" onclick="payTakeOut()">
                            <span>💳</span> Pay
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modifier Modal Styling */
#modifiersModal label:hover {
    border-color: #0d9488 !important;
    background: #f0fdf4 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.15);
}

#modifiersModal label:has(input:checked) {
    border-color: #0d9488 !important;
    background: #f0fdf4 !important;
}

#modifiersModal input[type="checkbox"]:checked {
    accent-color: #0d9488;
}

#modifiersModal button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

#modifiersModal button[onclick*="closeModifiersModal"]:hover {
    background: #e2e8f0 !important;
}

#modifiersModal button[onclick*="addModifiersAndToCart"]:hover {
    background: #0f766e !important;
}
</style>

<div id="modifiersModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; padding: 0;">
        <div style="padding: 24px; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; background: white;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 id="modifierModalTitle" style="margin: 0; color: #1e293b; font-size: 1.25rem;">Select Modifiers</h3>
                    <div id="modalItemName" style="color: #64748b; font-size: 14px; margin-top: 4px; font-weight: 500;">Item name</div>
                    <div id="modalItemPrice" style="color: #0d9488; font-size: 20px; font-weight: 700; margin-top: 8px;">₱0.00</div>
                </div>
                <button onclick="closeModifiersModal()" style="background: #f1f5f9; border: none; font-size: 20px; cursor: pointer; color: #64748b; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">×</button>
            </div>
        </div>
        <div id="modifiersContainer" style="padding: 24px; color: #94a3b8; max-height: 400px; overflow-y: auto;">Loading modifiers...</div>
        <div style="padding: 20px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; gap: 12px;">
            <button onclick="closeModifiersModal()" style="flex: 1; padding: 14px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600; color: #475569; transition: all 0.2s;">Cancel</button>
            <button onclick="addModifiersAndToCart()" style="flex: 1; padding: 14px; background: #0d9488; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s;">Add to Cart</button>
        </div>
    </div>
</div>

<input type="hidden" id="orderType" value="1">
<input type="hidden" id="pendingItemId" value="">
<input type="hidden" id="pendingItemName" value="">
<input type="hidden" id="pendingItemPrice" value="">
<input type="hidden" id="pendingItemIsFree" value="0">
<input type="hidden" id="cashierName" value="<?php echo htmlspecialchars($currentUser['FullName'] ?? ($currentUser['username'] ?? 'Cashier')); ?>">

<div id="paymentModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
  <div style="background:#ffffff; width:460px; border-radius:16px; padding:28px; box-shadow:0 20px 40px rgba(0,0,0,0.15); border:1px solid #e2e8f0; max-width:90vw; max-height:90vh; overflow-y:auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
      <div style="color:#1e293b; font-weight:700; font-size:20px; display:flex; align-items:center; gap:10px;">
        <span style="background:#0d9488; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff;">💳</span>
        Payment
      </div>
      <button onclick="closePaymentModal()" style="background:none; border:none; color:#64748b; font-size:22px; cursor:pointer; width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center;">×</button>
    </div>
    <input type="hidden" id="payTotalAmount" />
    <div style="background:#f8fafc; border-radius:12px; padding:18px; margin-bottom:16px; border:1px solid #e2e8f0; text-align:center;">
      <div style="color:#64748b; font-size:12px; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">TOTAL AMOUNT</div>
      <div style="color:#0d9488; font-size:30px; font-weight:800;" id="payTotal">₱0.00</div>
    </div>
    <div style="margin-bottom:16px;">
      <label style="color:#374151; font-weight:600; display:block; margin-bottom:8px; font-size:14px;">Payment Method</label>
      <select id="payMethod" class="form-control" style="background:#fff; border:2px solid #e2e8f0; color:#1e293b; padding:12px 14px; border-radius:10px; font-size:14px; width:100%; transition:all 0.2s;">
        <option value="Cash">Cash</option>
        <option value="GCash">GCash</option>
        <option value="Card">Card</option>
      </select>
    </div>
    <div style="margin-bottom:16px;">
      <label style="color:#374151; font-weight:600; display:block; margin-bottom:8px; font-size:14px;">Amount Paid</label>
      <input type="number" id="payAmount" class="form-control" step="0.01" min="0" oninput="computeChange()" style="background:#fff; border:2px solid #e2e8f0; color:#1e293b; padding:12px 14px; border-radius:10px; font-size:16px; width:100%; transition:all 0.2s;">
    </div>
    <div style="background:#f0fdf4; border-radius:12px; padding:18px; margin-bottom:16px; border:1px solid #bbf7d0;">
      <div style="color:#16a34a; font-size:12px; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">CHANGE</div>
      <div style="color:#16a34a; font-size:24px; font-weight:800;" id="payChange">₱0.00</div>
    </div>
    <div style="display:flex; gap:12px;">
      <button class="btn btn-secondary" onclick="closePaymentModal()" style="flex:1;">Cancel</button>
      <button class="btn btn-primary" onclick="submitPayment()" style="flex:1;">Process Payment</button>
    </div>
  </div>
</div>

<script>
// E.U.T RESTAURANT POS SYSTEM
let currentOrder = [];
let currentCategoryId = null;
function updateActionButtons() {
    const t = document.getElementById('orderType').value;
    const checkout = document.getElementById('checkoutBtn');
    const pay = document.getElementById('payBtn');
    // Both Dine-In and Take-Out should use checkout (go to kitchen first)
    checkout.style.display = 'inline-flex';
    pay.style.display = 'none';
}

function loadCategories() {
    const container = document.getElementById('categoriesContainer');
    if (!container) return;
    container.innerHTML = '';
    
    // Add "All Categories" option first
    const allCategoriesCard = document.createElement('div');
    allCategoriesCard.className = 'category-card';
    allCategoriesCard.dataset.categoryId = 'all';
    allCategoriesCard.innerHTML = `
        <div class="category-top-row">
            <div class="category-icon">📋</div>
            <div class="category-count" id="cat-count-all">0</div>
        </div>
        <div class="category-name">All Categories</div>
    `;
    allCategoriesCard.addEventListener('click', () => selectCategory(allCategoriesCard));
    container.appendChild(allCategoriesCard);
    
    const categories = <?php echo json_encode($categories); ?>;
    
    categories.forEach((category) => {
        const card = document.createElement('div');
        card.className = 'category-card';
        card.dataset.categoryId = category.CategoryID;
        let icon = '🍽️';
        const categoryName = category.CategoryName.toLowerCase();
        if (categoryName.includes('beverage') || categoryName.includes('drink')) icon = '🥤';
        else if (categoryName.includes('food') || categoryName.includes('meal')) icon = '🍔';
        else if (categoryName.includes('dessert') || categoryName.includes('sweet')) icon = '🍰';
        else if (categoryName.includes('breakfast')) icon = '🍳';
        else if (categoryName.includes('snack') || categoryName.includes('appetizer')) icon = '🍿';
        else if (categoryName.includes('soup')) icon = '🥣';
        else if (categoryName.includes('salad')) icon = '🥗';
        else if (categoryName.includes('pasta')) icon = '🍝';
        else if (categoryName.includes('pizza')) icon = '🍕';
        else if (categoryName.includes('seafood')) icon = '🦐';
        
        card.innerHTML = `
            <div class="category-top-row">
                <div class="category-icon">${icon}</div>
                <div class="category-count" id="cat-count-${category.CategoryID}">0</div>
            </div>
            <div class="category-name">${escapeHtml(category.CategoryName)}</div>
        `;
        card.addEventListener('click', () => selectCategory(card));
        container.appendChild(card);
    });
}

function selectCategory(categoryCard) {
    document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
    categoryCard.classList.add('active');
    currentCategoryId = categoryCard.dataset.categoryId;
    loadMenuItems();
}

function selectDefaultCategory() {
    const container = document.getElementById('categoriesContainer');
    if (!container || container.children.length === 0) return;
    
    // Select "All Categories" by default
    const allCategoriesCard = container.querySelector('[data-category-id="all"]');
    if (allCategoriesCard) {
        allCategoriesCard.classList.add('active');
        currentCategoryId = 'all';
        loadMenuItems();
        return;
    }
    
    // Fallback to first category if "All Categories" not found
    let first = null;
    for (let i = 0; i < container.children.length; i++) {
        const cat = container.children[i];
        const countEl = document.getElementById(`cat-count-${cat.dataset.categoryId}`);
        if (countEl && parseInt(countEl.textContent) > 0) { first = cat; break; }
    }
    if (!first) first = container.children[0];
    first.classList.add('active');
    currentCategoryId = first.dataset.categoryId;
    loadMenuItems();
}

async function updateCategoryCounts() {
    try {
        const response = await fetch(`controllers/POSController.php?action=getAllCategories`);
        const counts = await response.json();
        counts.forEach(cat => {
            const el = document.getElementById(`cat-count-${cat.CategoryID}`);
            if (el) el.textContent = cat.count;
        });
        
        // Update "All Categories" count with total items
        const allCountEl = document.getElementById('cat-count-all');
        if (allCountEl) {
            const totalCount = counts.reduce((sum, cat) => sum + parseInt(cat.count), 0);
            allCountEl.textContent = totalCount;
        }
    } catch (e) { console.error(e); }
}

async function loadMenuItems() {
    const searchTerm = document.getElementById('searchInput').value;
    const container = document.getElementById('menuItems');
    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⏳</div><div class="empty-state-text">Loading...</div></div>';
    try {
        let url;
        if (currentCategoryId === 'all' || !currentCategoryId) {
            // Load all menu items
            url = `controllers/POSController.php?action=getAllMenuItems&search=${encodeURIComponent(searchTerm)}`;
        } else {
            // Load items from specific category
            url = `controllers/POSController.php?action=getMenuItems&category_id=${currentCategoryId}&search=${encodeURIComponent(searchTerm)}`;
        }
        
        const response = await fetch(url);
        const menuItems = await response.json();
        displayMenuItems(menuItems);
    } catch (e) {
        container.innerHTML = `<div class="empty-state"><div class="empty-state-icon">⚠️</div><div class="empty-state-text">Failed to load</div><button onclick="loadMenuItems()" style="margin-top:16px;padding:8px 16px;background:#2c3e50;color:white;border:none;border-radius:6px;cursor:pointer;">Try Again</button></div>`;
    }
}

function displayMenuItems(menuItems) {
    const container = document.getElementById('menuItems');
    container.innerHTML = '';
    if (!menuItems || menuItems.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🍽️</div><div class="empty-state-text">No items found</div></div>';
        return;
    }
    menuItems.forEach(item => container.appendChild(createMenuItemCard(item)));
}

function createMenuItemCard(item) {
    const div = document.createElement('div');
    div.className = 'menu-item';
    const imageHtml = item.ImagePath && item.ImagePath.trim() ? 
        `<div class="menu-item-image"><img src="${item.ImagePath}" alt="${item.ItemName}" onerror="this.parentElement.innerHTML='<div class=\\'placeholder-icon\\'>🍔</div>'"></div>` :
        `<div class="menu-item-image"><div class="placeholder-icon">🍔</div></div>`;
    let badges = '';
    if (item.IsFree == 1) badges += '<span style="display:inline-block;background:#27ae60;color:white;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:600;margin-right:6px;">FREE</span>';
    if (item.ModifierCount && item.ModifierCount > 0) badges += `<span style="display:inline-block;background:#3498db;color:white;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:600;">⚙️ ${item.ModifierCount} Mod${item.ModifierCount !== 1 ? 's' : ''}</span>`;
    if (item.FlavorCount && item.FlavorCount > 0) badges += ` <span style="display:inline-block;background:#8b5cf6;color:white;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:600;">🧂 ${item.FlavorCount} Flavor${item.FlavorCount !== 1 ? 's' : ''}</span>`;
    
    // Hide price for items with modifiers, show "Select modifiers" instead
    const hasModifiers = item.ModifierCount && item.ModifierCount > 0;
    const hasFlavors = item.FlavorCount && item.FlavorCount > 0;
    const needsConfig = hasModifiers || hasFlavors;
    const priceValue = parseFloat(item.Price);
    const priceDisplay = needsConfig ? 
        '<span style="color:#3498db;font-weight:600;">Select options</span>' :
        (item.IsFree == 1 || priceValue === 0 || isNaN(priceValue) ? '<span style="color:#27ae60;font-weight:600;">FREE</span>' : '₱' + priceValue.toFixed(2));
    
    const availContent = item.IsAvailable == 1 ? `
        <div class="menu-item-content">
            <div class="menu-item-name">${escapeHtml(item.ItemName)}</div>
            <div style="margin-bottom:8px;min-height:20px;">${badges}</div>
            <div class="menu-item-price">${priceDisplay}</div>
            <div class="menu-item-actions">
                <div class="quantity-control">
                    <button class="quantity-btn" onclick="event.stopPropagation();updateQuantity('${item.ItemID}',-1,event)">−</button>
                    <div class="quantity-display" id="qty-${item.ItemID}">1</div>
                    <button class="quantity-btn" onclick="event.stopPropagation();updateQuantity('${item.ItemID}',1,event)">+</button>
                </div>
                <button class="add-to-cart-btn" onclick="event.stopPropagation();addToCart('${item.ItemID}', '${escapeHtml(item.ItemName)}', ${item.Price}, ${item.IsFree})">
                    <span>${needsConfig ? '⚙️' : '🛒'}</span>
                    <span>${needsConfig ? 'Configure' : 'Add to Cart'}</span>
                </button>
            </div>
        </div>
    ` : `
        <div class="menu-item-content">
            <div class="menu-item-name">${escapeHtml(item.ItemName)}</div>
            <div style="margin-bottom:8px;">${badges}</div>
            <div class="menu-item-price">${priceDisplay}</div>
            <div class="menu-item-actions"><span class="unavailable-text">Unavailable</span></div>
        </div>
    `;
    div.innerHTML = imageHtml + availContent;
    return div;
}

function addToCart(itemId, itemName, price, event) { if (event) event.stopPropagation(); checkModifiersAndAdd(itemId, itemName, price, event); }
async function checkModifiersAndAdd(itemId, itemName, price, event) {
    try {
        // Check for modifiers
        console.log("Fetching modifiers for item:", itemId);
        const modifierRes = await fetch(`controllers/POSController.php?action=getModifiers&item_id=${itemId}`);
        const modifierData = await modifierRes.json();
        console.log("Modifier data received:", modifierData);
        
        // Check for flavors
        const flavorRes = await fetch(`controllers/POSController.php?action=getFlavors&item_id=${itemId}`);
        const flavorData = await flavorRes.json();
        console.log("Flavor data received:", flavorData);
        
        const hasModifiers = modifierData.success && modifierData.modifiers && modifierData.modifiers.length > 0;
        const hasFlavors = flavorData.success && flavorData.flavors && flavorData.flavors.length > 0;
        
        console.log("Has modifiers:", hasModifiers, "Has flavors:", hasFlavors);
        
        if (hasModifiers || hasFlavors) {
            showModifiersModal(itemId, itemName, price, modifierData.success ? modifierData.modifiers : [], parseInt(modifierData.isFree) || 0, flavorData.success ? flavorData.flavors : [], flavorData.defaultFlavor);
        } else {
            addToCartDirect(itemId, itemName, price, event);
        }
    } catch (e) { 
        console.error("Error checking modifiers:", e);
        addToCartDirect(itemId, itemName, price, event); 
    }
}

function showModifiersModal(itemId, itemName, price, modifiers, isFree = 0, flavors = [], defaultFlavor = null) {
    console.log("Showing modifiers modal with modifiers:", modifiers);
    console.log("Modifiers with FlavorID:", modifiers.map(m => ({ 
        id: m.ModifierID, 
        name: m.Name, 
        flavorId: m.FlavorID 
    })));
    
    // Make flavors globally available for filtering
    window.currentFlavors = flavors;
    
    document.getElementById('pendingItemId').value = itemId;
    document.getElementById('pendingItemName').value = itemName;
    document.getElementById('pendingItemPrice').value = price;
    document.getElementById('pendingItemIsFree').value = isFree;
    document.getElementById('modifierModalTitle').textContent = 'Select Options';
    document.getElementById('modalItemName').textContent = itemName;
    
    // Display original price, handling NULL values
    const priceValue = parseFloat(price);
    const priceDisplay = isFree ? 'FREE' : (isNaN(priceValue) || priceValue === 0 ? 'Select modifiers' : '₱' + priceValue.toFixed(2));
    document.getElementById('modalItemPrice').textContent = priceDisplay;
    
    let html = '';
    
    // Add flavors section if available
    if (flavors.length > 0) {
        html += '<div><h4 style="margin:0 0 12px 0;color:#1e293b;font-size:16px;">🧂 Flavors</h4><div style="display:flex;flex-direction:column;gap:12px;">';
        
        flavors.forEach(f => {
            const priceAdj = parseFloat(f.PriceAdjustment);
            const priceDisplay = !isNaN(priceAdj) && priceAdj > 0 ? `₱${priceAdj.toFixed(2)}` : 'No charge';
            const isDefault = defaultFlavor && defaultFlavor.FlavorID == f.FlavorID;
            const flavorName = f.FlavorName || 'Unknown Flavor';
            
            html += `<label style="display:flex;align-items:center;padding:12px;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;transition:all 0.2s;background:${isDefault ? '#f0fdf4' : 'white'};">
                <input type="checkbox" name="selected_flavor" data-flavor-id="${f.FlavorID}" data-flavor-name="${flavorName}" data-flavor-price="${priceAdj}" onchange="handleFlavorChange()" ${isDefault ? 'checked' : ''} style="margin-right:12px;width:18px;height:18px;cursor:pointer;">
                <div style="flex:1;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-weight:600;color:#1e293b;font-size:14px;">${flavorName}</div>
                        <div style="font-size:12px;color:#64748b;">${priceDisplay}</div>
                    </div>
                    ${isDefault ? '<span style="background:#0d9488;color:white;padding:2px 6px;border-radius:4px;font-size:10px;">Default</span>' : ''}
                </div>
            </label>`;
        });
        
        // Add custom flavor input
        html += `<div style="display:flex;align-items:center;padding:12px;border:2px dashed #cbd5e1;border-radius:8px;background:#f8fafc;">
            <button type="button" onclick="addCustomFlavor()" style="margin-right:12px;width:36px;height:36px;border:none;background:#0d9488;color:white;border-radius:50%;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;transition:all 0.2s;">+</button>
            <div style="flex:1;">
                <input type="text" id="customFlavorInput" placeholder="Add custom flavor..." style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:4px;font-size:14px;display:none;">
                <div style="font-weight:600;color:#64748b;font-size:14px;">Add custom flavor</div>
                <div style="font-size:12px;color:#94a3b8;">Click + to add a custom flavor</div>
            </div>
        </div>`;
        
        html += '</div></div>';
    }
    
    // Add modifiers section if available
    if (modifiers.length > 0) {
        html += '<div><h4 style="margin:0 0 12px 0;color:#1e293b;font-size:16px;">➕ Modifiers</h4><div style="display:flex;flex-direction:column;gap:12px;">';
        
        modifiers.forEach(m => {
            // CRITICAL: Get the FlavorID from database field first
            const linkedFlavorId = m.FlavorID ? String(m.FlavorID) : '';
            const parsed = parseModifierEncodedName(m.Name);
            const encodedFlavorId = parsed.flavorId;
            
            // Use database FlavorID if available, otherwise use encoded flavor ID
            const finalFlavorId = linkedFlavorId || encodedFlavorId;
            // Use parsed name for display, but keep original name for filtering
            const displayName = encodedFlavorId ? parsed.name : m.Name;
            const originalName = m.Name; // Always keep original name for filtering
            const ap = parseInt(m.AffectsPrice) === 1;
            const pr = parseFloat(m.Price);
            const label = ap ? (isNaN(pr) || pr === 0 ? 'No charge' : `₱${pr.toFixed(2)}`) : 'No charge';
            
            console.log(`Modifier ${displayName} has flavorId: ${finalFlavorId} (db:${linkedFlavorId}, encoded:${encodedFlavorId})`);
            
            html += `<label data-modifier-row="1" style="display:flex;align-items:center;padding:16px;border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;transition:all 0.2s;background:white;">
                <input type="checkbox" 
                    data-modifier-id="${m.ModifierID}" 
                    data-modifier-name="${originalName}" 
                    data-modifier-flavor-id="${finalFlavorId}" 
                    data-modifier-price="${pr}" 
                    data-modifier-affects-price="${ap?1:0}" 
                    onchange="updateModalPrice()" 
                    style="margin-right:16px;width:20px;height:20px;cursor:pointer;">
                <div style="flex:1;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-weight:600;color:#1e293b;font-size:16px;margin-bottom:2px;">${displayName}</div>
                        <div style="font-size:14px;color:#64748b;font-weight:500;">${label}</div>
                    </div>
                    <div style="width:8px;height:8px;background:#0d9488;border-radius:50%;opacity:${ap ? '1' : '0.3'};"></div>
                </div>
            </label>`;
        });
        html += '</div></div>';
    }
    
    // Show message if neither modifiers nor flavors are available
    if (!modifiers.length && !flavors.length) {
        html = '<p style="color:#64748b;text-align:center;padding:40px 0;">No options available</p>';
    }
    
    // Set the HTML content
    console.log('HTML being set:', html);
    document.getElementById('modifiersContainer').innerHTML = html;
    
    // DEBUG: Check if modifiers were actually added to the DOM
    console.log('=== POST-HTML DEBUG ===');
    console.log('Container innerHTML length:', document.getElementById('modifiersContainer').innerHTML.length);
    console.log('Modifier rows after HTML set:', document.querySelectorAll('[data-modifier-row="1"]').length);
    
    // Check if the modifier HTML was actually added
    const hasModifierHTML = html.includes('data-modifier-row="1"');
    console.log('HTML contains modifier rows:', hasModifierHTML);
    
    if (!hasModifierHTML) {
        console.log('WARNING: No modifier HTML found in generated content!');
        console.log('Modifiers array length:', modifiers.length);
        console.log('Modifiers data:', modifiers);
    }
    
    // DEBUG: Check initial state of modifier rows
    console.log('=== MODAL OPEN DEBUG ===');
    console.log('Initial modifier rows:', document.querySelectorAll('[data-modifier-row="1"]').length);
    document.querySelectorAll('[data-modifier-row="1"]').forEach((row, index) => {
        const cb = row.querySelector('input[data-modifier-id]');
        if (cb) {
            console.log(`Initial Row ${index}: display=${row.style.display}, modifier=${cb.getAttribute('data-modifier-name')}`);
        }
    });
    console.log('=== END MODAL OPEN DEBUG ===');
    
    // Apply filtering after setting content
    filterModifiersForSelectedFlavor();
    
    document.getElementById('modifiersModal').style.display = 'flex';

    // Log the generated HTML to see if flavor IDs are being set
    console.log("Generated modifier HTML with flavor IDs");
    
    handleFlavorChange();
}
function handleFlavorChange() {
    filterModifiersForSelectedFlavor();
    updateModalPrice();
}

function filterModifiersForSelectedFlavor() {
    const selectedFlavors = document.querySelectorAll('input[type="checkbox"][name="selected_flavor"]:checked');
    const selectedFlavorIds = Array.from(selectedFlavors).map(f => f.getAttribute('data-flavor-id') || '');

    console.log('=== FILTERING DEBUG ===');
    console.log('Selected flavors:', selectedFlavorIds);

    document.querySelectorAll('[data-modifier-row="1"]').forEach((row, index) => {
        const checkbox = row.querySelector('input[type="checkbox"]');
        const modifierName = checkbox.getAttribute('data-modifier-name') || '';
        const linkedFlavorId = checkbox.getAttribute('data-modifier-flavor-id') || '';
        
        console.log(`Row ${index}: Name="${modifierName}", FlavorID="${linkedFlavorId}"`);
        
        if (selectedFlavorIds.length === 0) {
            // No flavors selected: show ONLY modifiers that truly have no flavor association
            const hasEncodedFlavor = modifierName.includes('__FLAVORID:');
            const hasNumericFlavorId = linkedFlavorId && /^\d+$/.test(linkedFlavorId);
            
            // NEW: Check if this item has flavors at all - if it does, modifiers should be linked
            const itemHasFlavors = window.currentFlavors && window.currentFlavors.length > 0;
            
            console.log(`  No flavors selected - hasEncodedFlavor: ${hasEncodedFlavor}, hasNumericFlavorId: ${hasNumericFlavorId}, itemHasFlavors: ${itemHasFlavors}`);
            
            // Hide if modifier has any flavor association OR if item has flavors (modifier should be linked)
            if (hasEncodedFlavor || hasNumericFlavorId || itemHasFlavors) {
                checkbox.checked = false;
                row.style.display = 'none';
                console.log(`  → HIDING modifier (has flavor association)`);
            } else {
                row.style.display = '';
                console.log(`  → SHOWING modifier (truly no flavor association)`);
            }
        } else {
            // Flavors selected: show modifiers that match ANY selected flavor
            const shouldShow = selectedFlavorIds.some(flavorId => 
                linkedFlavorId && String(linkedFlavorId) === String(flavorId)
            );
            
            console.log(`  Checking flavor match: modifier FlavorID="${linkedFlavorId}" vs selected=${selectedFlavorIds.join(', ')}`);
            console.log(`  Should show: ${shouldShow}`);
            
            if (shouldShow) {
                row.style.display = '';
                console.log(`  → SHOWING modifier (matches selected flavor)`);
            } else {
                checkbox.checked = false;
                row.style.display = 'none';
                console.log(`  → HIDING modifier (doesn't match selected flavors)`);
            }
        }
    });
    console.log('=== END FILTERING DEBUG ===');
}
function parseModifierEncodedName(rawName) {
    const raw = String(rawName || '');
    const m = raw.match(/^__FLAVORID:(\d+|temp_\d+)__\|(.*)$/);
    if (m) {
        return { flavorId: m[1], name: m[2] };
    }
    return { flavorId: '', name: raw };
}

function addCustomFlavor() {
    const input = document.getElementById('customFlavorInput');
    const container = input.parentElement.parentElement;
    
    if (input.style.display === 'none') {
        // Show input field
        input.style.display = 'block';
        input.focus();
        container.querySelector('div > div:last-child').style.display = 'none';
        container.querySelector('button').textContent = '✓';
    } else {
        // Add the custom flavor
        const flavorName = input.value.trim();
        if (flavorName) {
            // Create a new checkbox for the custom flavor
            const flavorsContainer = container.parentElement;
            const customFlavorHtml = `<label data-custom-flavor="1" style="display:flex;align-items:center;padding:12px;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;transition:all 0.2s;background:#f0fdf4;">
                <input type="checkbox" name="selected_flavor" data-flavor-id="custom_${Date.now()}" data-flavor-name="${flavorName}" data-flavor-price="0" onchange="handleFlavorChange()" checked style="margin-right:12px;width:18px;height:18px;cursor:pointer;">
                <div style="flex:1;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-weight:600;color:#1e293b;font-size:14px;">${flavorName}</div>
                        <div style="font-size:12px;color:#64748b;">Custom flavor</div>
                    </div>
                    <span style="background:#8b5cf6;color:white;padding:2px 6px;border-radius:4px;font-size:10px;">Custom</span>
                </div>
            </label>`;
            
            // Insert before the custom flavor input
            flavorsContainer.insertAdjacentHTML('beforeend', customFlavorHtml);
            
            // Reset the input
            input.value = '';
            input.style.display = 'none';
            container.querySelector('div > div:last-child').style.display = 'block';
            container.querySelector('button').textContent = '+';
            
            // Update price
            handleFlavorChange();
        }
    }
}

function closeModifiersModal() {
    document.getElementById('modifiersModal').style.display = 'none';
    document.querySelectorAll('input[type="checkbox"][data-modifier-id]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[type="checkbox"][name="selected_flavor"]').forEach(cb => cb.checked = false);
    
    // Reset custom flavor input
    const customFlavorInput = document.getElementById('customFlavorInput');
    if (customFlavorInput) {
        customFlavorInput.value = '';
        customFlavorInput.style.display = 'none';
        const container = customFlavorInput.parentElement.parentElement;
        container.querySelector('div > div:last-child').style.display = 'block';
        container.querySelector('button').textContent = '+';
        
        // Remove any custom flavors that were added
        container.parentElement.querySelectorAll('label[data-custom-flavor]').forEach(label => label.remove());
    }
}

function updateModalPrice() {
    const basePrice = parseFloat(document.getElementById('pendingItemPrice').value);
    const isFree = parseInt(document.getElementById('pendingItemIsFree').value) === 1;

    // Determine base using flavor override logic
    const baseValue = isNaN(basePrice) ? 0 : basePrice;
    let base = baseValue;
    let selectedFlavors = [];
    let selectedFlavorIds = [];
    const selectedFlavorCheckboxes = document.querySelectorAll('input[type="checkbox"][name="selected_flavor"]:checked');
    
    if (selectedFlavorCheckboxes.length > 0) {
        selectedFlavorCheckboxes.forEach(flavor => {
            const rawFlavorId = flavor.getAttribute('data-flavor-id');
            const flavorId = rawFlavorId || '';
            const flavorName = (flavor.getAttribute('data-flavor-name') || '').trim();
            const flavorPrice = parseFloat(flavor.getAttribute('data-flavor-price'));
            
            if (flavorId) {
                selectedFlavors.push({
                    FlavorID: flavorId,
                    Name: flavorName,
                    PriceAdjustment: flavorPrice || 0
                });
                selectedFlavorIds.push(flavorId);
                
                // Add flavor price to base
                if (!isNaN(flavorPrice) && flavorPrice > 0) {
                    base += flavorPrice;
                }
            }
        });
    } else {
        console.log('No flavors selected, using base price:', baseValue);
    }
    
    console.log('Final base price:', base);
    
    // Calculate modifiers total
    let modTotal = 0;
    
    document.querySelectorAll('input[type="checkbox"][data-modifier-id]:checked').forEach(cb => {
        const affectsPrice = cb.getAttribute('data-modifier-affects-price') === '1';
        const modifierPrice = parseFloat(cb.getAttribute('data-modifier-price'));
        const linkedFlavorId = cb.getAttribute('data-modifier-flavor-id');
        
        // Only include modifiers that match selected flavors OR have no flavor
        const shouldInclude = selectedFlavorIds.length === 0 ? 
            (!linkedFlavorId) : // No flavors selected: only include modifiers with no flavor
            selectedFlavorIds.some(flavorId => linkedFlavorId && String(linkedFlavorId) === String(flavorId)); // Flavors selected: include if matches any
        
        if (shouldInclude && affectsPrice && !isNaN(modifierPrice)) {
            modTotal += modifierPrice;
        }
    });
    
    const computedPrice = base + modTotal;
    
    // Update display
    const priceEl = document.getElementById('modalItemPrice');
    if (isFree) {
        priceEl.textContent = computedPrice > 0 ? ('FREE + ₱' + computedPrice.toFixed(2)) : 'FREE';
    } else {
        priceEl.textContent = '₱' + computedPrice.toFixed(2);
    }
}

function addModifiersAndToCart() {
    const itemId = document.getElementById('pendingItemId').value;
    const itemName = document.getElementById('pendingItemName').value;
    const basePrice = parseFloat(document.getElementById('pendingItemPrice').value);
    
    const isFree = parseInt(document.getElementById('pendingItemIsFree').value) === 1;
    
    // Handle NULL base price
    const baseValue = isNaN(parseFloat(document.getElementById('pendingItemPrice').value)) ? 0 : parseFloat(document.getElementById('pendingItemPrice').value);
    
    // Get selected flavors
    let selectedFlavors = [];
    let flavorTotal = 0;
    const selectedFlavorCheckboxes = document.querySelectorAll('input[type="checkbox"][name="selected_flavor"]:checked');
    
    selectedFlavorCheckboxes.forEach(flavor => {
        const fid = flavor.getAttribute('data-flavor-id');
        if (fid) {
            selectedFlavors.push({
                FlavorID: fid,
                Name: flavor.getAttribute('data-flavor-name'),
                PriceAdjustment: parseFloat(flavor.getAttribute('data-flavor-price')) || 0
            });
            flavorTotal += parseFloat(flavor.getAttribute('data-flavor-price')) || 0;
        }
    });
    
    // Get selected modifiers - ONLY those that match selected flavors OR have no flavor
    const selected = [];
    let modTotal = 0;
    const selectedFlavorIds = selectedFlavors.map(f => f.FlavorID);
    
    document.querySelectorAll('input[type="checkbox"][data-modifier-id]:checked').forEach(cb => {
        const linkedFlavorId = cb.getAttribute('data-modifier-flavor-id');
        const modPrice = parseFloat(cb.getAttribute('data-modifier-price'));
        
        // CRITICAL FIX: Only include modifiers that match the selected flavors OR have no flavor
        const shouldInclude = selectedFlavorIds.length === 0 ? 
            (!linkedFlavorId) : // No flavors selected: only include modifiers with no flavor
            selectedFlavorIds.some(flavorId => linkedFlavorId && String(linkedFlavorId) === String(flavorId)); // Flavors selected: include if matches any
        
        if (shouldInclude) {
            selected.push({ 
                ModifierID: cb.getAttribute('data-modifier-id'), 
                Name: cb.getAttribute('data-modifier-name'), 
                Price: modPrice,
                AffectsPrice: cb.getAttribute('data-modifier-affects-price') === '1',
                FlavorID: linkedFlavorId
            });
            if (cb.getAttribute('data-modifier-affects-price') === '1') {
                modTotal += modPrice;
            }
        }
    });
    
    const qtyEl = document.getElementById(`qty-${itemId}`);
    let qty = qtyEl ? Math.max(1, parseInt(qtyEl.textContent) || 1) : 1;

    const base = baseValue + flavorTotal;
    const computedPrice = base + modTotal;
    
    // Create a display name that includes flavor info
    let displayName = itemName;
    if (selectedFlavors.length > 0) {
        const flavorNames = selectedFlavors.map(f => f.Name).join(', ');
        displayName += ` (${flavorNames})`;
    }
    
    const exist = currentOrder.find(i => 
        i.ItemID == itemId && 
        i.DisplayName === displayName && 
        JSON.stringify(i.Modifiers) === JSON.stringify(selected)
    );
    
    if (exist) {
        exist.quantity += qty;
        const qtyEl = document.getElementById(`qty-${itemId}`);
        if (qtyEl) qtyEl.textContent = exist.quantity;
    } else {
        currentOrder.push({
            ItemID: itemId,
            DisplayName: displayName, 
            Price: computedPrice,
            BasePrice: base,
            FlavorAdjustment: flavorTotal,
            Flavors: selectedFlavors,
            Modifiers: selected, 
            quantity: qty, 
            notes: '',
            IsFree: isFree
        });
    }
    
    if (qtyEl) qtyEl.textContent = '1';
    updateOrderDisplay();
}
function addToCartDirect(itemId, itemName, price, event) {
    if (event) event.stopPropagation();
    const qtyEl = document.getElementById(`qty-${itemId}`);
    let qty = qtyEl ? Math.max(1, parseInt(qtyEl.textContent) || 1) : 1;
    
    // Get item details to check if it's free
    fetch(`controllers/POSController.php?action=getModifiers&item_id=${itemId}`)
        .then(r => r.json())
        .then(data => {
            const isFree = parseInt(data.isFree) || 0; // Convert to integer
            const exist = currentOrder.find(i => i.ItemID == itemId && (!i.DisplayName || i.DisplayName === itemName));
            if (exist) exist.quantity += qty;
            else currentOrder.push({ 
                ItemID: itemId, 
                ItemName: itemName, 
                DisplayName: itemName, 
                Price: parseFloat(price) || 0, 
                BasePrice: parseFloat(price) || 0, 
                Modifiers: [], 
                quantity: qty, 
                notes: '',
                IsFree: isFree
            });
            if (qtyEl) qtyEl.textContent = '1';
            updateOrderDisplay();
        })
        .catch(e => {
            // Fallback if fetch fails
            const exist = currentOrder.find(i => i.ItemID == itemId && (!i.DisplayName || i.DisplayName === itemName));
            if (exist) exist.quantity += qty;
            else currentOrder.push({ 
                ItemID: itemId, 
                ItemName: itemName, 
                DisplayName: itemName, 
                Price: parseFloat(price) || 0, 
                BasePrice: parseFloat(price) || 0, 
                Modifiers: [], 
                quantity: qty, 
                notes: '',
                IsFree: 0
            });
            if (qtyEl) qtyEl.textContent = '1';
            updateOrderDisplay();
        });
}

function updateQuantity(itemId, change, event) {
    if (event) event.stopPropagation();
    const el = document.getElementById(`qty-${itemId}`);
    if (el) {
        let q = Math.max(1, Math.min(99, (parseInt(el.textContent) || 1) + change));
        el.textContent = q;
    }
}

function updateOrderDisplay() {
    const container = document.getElementById('orderItems');
    if (currentOrder.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🛒</div><div class="empty-state-text">No items yet</div><div class="empty-state-subtext">Click menu items to add</div></div>';
    } else {
        container.innerHTML = '';
        currentOrder.forEach((item, i) => container.appendChild(createOrderItemElement(item, i)));
    }
    const subtotal = currentOrder.reduce((s,i) => s + (i.Price * i.quantity), 0);
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('total').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('itemCount').textContent = currentOrder.reduce((s,i)=>s+i.quantity,0) + ' items';
}

function createOrderItemElement(item, index) {
    const div = document.createElement('div');
    div.className = 'order-item';
    const dn = item.DisplayName || item.ItemName;
    
    // Build flavor display
    let flavorDisplay = '';
    if (item.Flavor) {
        // Handle single flavor (backward compatibility)
        flavorDisplay = `<div style="font-size:11px;color:#64748b;margin-bottom:2px;">🧂 ${item.Flavor.Name}</div>`;
    } else if (item.Flavors && item.Flavors.length > 0) {
        // Handle multiple flavors - display horizontally with bullets
        const flavorBullets = item.Flavors.map(f => `• ${f.Name}`).join(' ');
        flavorDisplay = `<div style="font-size:11px;color:#64748b;margin-bottom:2px;">🧂 ${flavorBullets}</div>`;
    }
    
    // Build modifier display
    let modifierDisplay = '';
    if (item.Modifiers && item.Modifiers.length > 0) {
        const modifierNames = item.Modifiers.map(m => m.Name).join(', ');
        modifierDisplay = `<div style="font-size:11px;color:#64748b;margin-bottom:2px;">➕ ${modifierNames}</div>`;
    }
    
    // Calculate price display
    let priceDisplay = '';
    const modifierCost = item.Modifiers ? item.Modifiers.filter(m=>m.AffectsPrice).reduce((s,m)=>s+m.Price,0) : 0;
    const totalAddons = modifierCost; // Removed flavorCost
    
    // Use the item's Price property for the base price
    const basePrice = item.Price || 0;
    
    if (item.IsFree && totalAddons > 0) {
        priceDisplay = `FREE + ₱${totalAddons.toFixed(2)}`;
    } else if (item.IsFree) {
        priceDisplay = 'FREE';
    } else if (basePrice === 0 && totalAddons > 0) {
        priceDisplay = `₱${totalAddons.toFixed(2)}`;
    } else if (totalAddons > 0) {
        priceDisplay = `₱${basePrice.toFixed(2)} + ₱${totalAddons.toFixed(2)}`;
    } else {
        priceDisplay = `₱${basePrice.toFixed(2)}`;
    }
    
    div.innerHTML = `
        <div class="order-item-info">
            <div class="order-item-name">${dn}</div>
            ${flavorDisplay}
            ${modifierDisplay}
            <div class="order-item-price" style="${(item.IsFree||basePrice===0)?'color:#27ae60;font-weight:600;':''}">${priceDisplay} x ${item.quantity}</div>
        </div>
        <div class="order-item-actions">
            <button onclick="updateOrderQuantity(${index},-1,event)" class="order-item-qty-btn">−</button>
            <span class="order-item-qty">${item.quantity}</span>
            <button onclick="updateOrderQuantity(${index},1,event)" class="order-item-qty-btn">+</button>
            <button onclick="removeFromOrder(${index},event)" class="remove-btn">×</button>
        </div>
    `;
    return div;
}

function updateOrderQuantity(index, change, event) {
    if (event) event.stopPropagation();
    currentOrder[index].quantity += change;
    if (currentOrder[index].quantity <= 0) currentOrder.splice(index, 1);
    updateOrderDisplay();
}

function removeFromOrder(index, event) {
    if (event) event.stopPropagation();
    currentOrder.splice(index, 1);
    updateOrderDisplay();
}

function clearOrder() {
    if (currentOrder.length && confirm('Clear order?')) { currentOrder = []; updateOrderDisplay(); }
}

async function checkoutOrder() {
    if (currentOrder.length === 0) { alert('Add items first.'); return; }
    try {
        const payload = createOrderPayload();
        console.log('🔍 Checkout payload:', payload);
        
        const res = await fetch('controllers/POSController.php?action=createOrder', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' }, 
            body: JSON.stringify(payload) 
        });
        
        console.log('🔍 Response status:', res.status);
        console.log('🔍 Response ok:', res.ok);
        
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        
        const result = await res.json();
        console.log('🔍 Checkout result:', result);
        
        if (result.success) {
            alert('✅ Order created! Order #' + result.order_number);
            currentOrder = [];
            updateOrderDisplay();
            document.getElementById('orderNotes').value = '';
        } else {
            alert('❌ ' + (result.message || 'Error'));
        }
    } catch (e) { 
        console.error('🔍 Checkout error:', e);
        alert('Error: ' + e.message + '. Please check if you are logged in.'); 
    }
}

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    updateCategoryCounts().then(selectDefaultCategory);
    const si = document.getElementById('searchInput');
    if (si) {
        let _t = null;
        si.addEventListener('input', () => {
            clearTimeout(_t);
            _t = setTimeout(loadMenuItems, 250);
        });
    }
    updateActionButtons();
});

const dineInBtn = document.getElementById('dineInBtn');
const takeOutBtn = document.getElementById('takeOutBtn');
const tableInput = document.getElementById('orderTableNumber');
if (dineInBtn && takeOutBtn) {
    dineInBtn.addEventListener('click', () => {
        dineInBtn.classList.add('active'); takeOutBtn.classList.remove('active');
        tableInput.value = ''; tableInput.disabled = false;
        document.getElementById('orderType').value = '1';
        updateActionButtons();
    });
    takeOutBtn.addEventListener('click', () => {
        takeOutBtn.classList.add('active'); dineInBtn.classList.remove('active');
        tableInput.value = 'Take-Out'; tableInput.disabled = true;
        document.getElementById('orderType').value = '2';
        updateActionButtons();
    });
}

function createOrderPayload() {
    // For Take-Out, create unique table name to avoid consolidation
    const orderType = document.getElementById('orderType').value;
    let tableNumber = document.getElementById('orderTableNumber').value || 'Take Out';
    
    if (orderType === '2') { // Take-Out
        // Use a short unique suffix to avoid exceeding DB column length
        const suffix = String(Date.now()).slice(-6);
        tableNumber = 'TO-' + suffix;
    }
    
    return {
        order_type_id: orderType,
        table_number: tableNumber,
        notes: document.getElementById('orderNotes').value,
        total_amount: currentOrder.reduce((s,i)=>s+(i.Price*i.quantity),0),
        items: currentOrder.map(i => ({ item_id: i.ItemID, quantity: i.quantity, unit_price: i.Price, modifiers: i.Modifiers || [], flavors: i.Flavors || [], notes: i.notes }))
    };
}

async function payTakeOut() {
    console.log('🔍 payTakeOut() called');
    if (currentOrder.length === 0) { 
        console.log('🔍 No items in order');
        alert('Add items first.'); 
        return; 
    }
    
    console.log('🔍 Opening payment modal...');
    const totalToPay = currentOrder.reduce((s,i)=>s+(i.Price*i.quantity),0);
    console.log('🔍 Total to pay:', totalToPay);
    
    openPaymentModal(totalToPay);
}

// Payment Modal Functions
function openPaymentModal(total) {
    console.log('🔍 openPaymentModal() called with total:', total);
    const m = document.getElementById('paymentModal');
    console.log('🔍 Payment modal element:', m);
    
    if (!m) {
        console.log('🔍 ERROR: Payment modal not found!');
        return;
    }
    
    document.getElementById('payTotalAmount').value = total;
    document.getElementById('payTotal').textContent = '₱' + (parseFloat(total)||0).toFixed(2);
    document.getElementById('payAmount').value = (parseFloat(total)||0).toFixed(2);
    document.getElementById('payMethod').value = 'Cash';
    m.style.display = 'flex';
    console.log('🔍 Payment modal display set to flex');
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
    const total = parseFloat(document.getElementById('payTotal').textContent.replace(/[₱,]/g,''))||0;
    const method = document.getElementById('payMethod').value || 'Cash';
    const amt = parseFloat(document.getElementById('payAmount').value)||0;
    if (amt < total) { alert('Amount paid is less than total'); return; }
    const change = amt - total;

    try {
        const res = await fetch('controllers/POSController.php?action=createOrder', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(createOrderPayload()) });
        const result = await res.json();
        if (result.success) {
            await fetch('controllers/POSController.php?action=updateOrderStatus', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_id: result.order_id, status: 'Paid' }) });
            closePaymentModal();
            printReceipt({
                orderId: result.order_id,
                orderNumber: result.order_number,
                orderType: 'TAKE-OUT',
                tableNumber: '',
                date: new Date(),
                items: JSON.parse(JSON.stringify(currentOrder)),
                subtotal: total,
                tax: 0,
                total: total,
                payment: {
                    method: method,
                    amountPaid: amt,
                    change: change
                }
            });
            currentOrder = [];
            updateOrderDisplay();
        } else alert(result.message || 'Error');
    } catch (e) { alert('Error. Try again.'); }
}

function formatMoney(n) { return (parseFloat(n)||0).toFixed(2); }

function printReceipt(data) {
    const w = window.open('', 'PRINT', 'height=600,width=400');
    const itemsHtml = data.items.map(it => {
        const name = (it.DisplayName || it.ItemName || '').slice(0, 16);
        const qty = String(it.quantity).padStart(3, ' ');
        const unit = formatMoney(it.Price).padStart(6, ' ');
        const total = formatMoney(it.Price * it.quantity).padStart(7, ' ');
        let lines = `<div class="row mono"><span class="left">${escapeHtml(name).padEnd(16,' ')}</span><span class="right" style="flex:none;width:16ch;text-align:right;">${qty} ${unit} ${total}</span></div>`;
        
        // Handle single flavor (backward compatibility)
        if (it.Flavor && it.Flavor.Name) {
            lines += `<div class="sub">- Flavor: ${escapeHtml(it.Flavor.Name)}</div>`;
        }
        
        // Handle multiple flavors
        if (it.Flavors && it.Flavors.length > 0) {
            const flavorBullets = it.Flavors.map(f => `• ${f.Name}`).join(' ');
            lines += `<div class="sub">- Flavors: ${escapeHtml(flavorBullets)}</div>`;
        }
        
        if (it.Modifiers && it.Modifiers.length) {
            it.Modifiers.forEach(m => {
                const lbl = m.AffectsPrice && parseFloat(m.Price) > 0 ? `+${formatMoney(m.Price)}` : 'No Charge';
                lines += `<div class="sub">- ${escapeHtml(m.Name)}${m.AffectsPrice && parseFloat(m.Price) > 0 ? `<span class="sub-right">+${formatMoney(m.Price)}</span>` : ' ' + lbl}</div>`;
            });
        }
        return lines;
    }).join('');
    const isDineIn = (data.orderType || '').toUpperCase() === 'DINE-IN';
    const typeLine = isDineIn ? 'Type: [✓] DINE-IN   [ ] TAKE-OUT' : 'Type: [ ] DINE-IN   [✓] TAKE-OUT';
    const fbUrl = 'https://www.facebook.com/EatUnwindTea';
    const qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + encodeURIComponent(fbUrl);
    const dateText = new Date(data.date).toLocaleString('en-US', { month:'2-digit', day:'2-digit', year:'2-digit', hour:'2-digit', minute:'2-digit', hour12:true });
    const html = `
<html>
<head>
<meta charset="utf-8">
<title>Receipt</title>
<style>
 @page { margin: 0; }
 body { margin: 0; font-family: Arial, sans-serif; font-size: 11px; }
 .receipt { width: 58mm; padding: 6px 8px; }
 .center { text-align: center; }
 .divider { border-top: 1px solid #000; margin: 6px 0; }
 .band { text-align:center; font-weight:bold; margin: 6px 0; font-size: 12px; }
 .row { display:flex; justify-content:space-between; font-size: 11px; }
 .left { white-space: pre; }
 .right { text-align:right; }
 .sub { margin-left: 8px; font-size: 10px; display:flex; justify-content:space-between; }
 .sub-right { margin-left: auto; }
 .total-row { display:flex; justify-content:space-between; font-weight: bold; font-size: 11px; }
 .title { font-weight:bold; font-size:13px; }
 .mono { font-family: monospace; font-size: 11px; }
 .qr { display:flex; justify-content:center; margin: 6px 0; }
 .qr img { width: 80px; height: 80px; }
 .cols { display:flex; justify-content:space-between; font-weight:bold; font-size: 11px; }
 .cols .c-left { flex:1; }
 .cols .c-right { width:16ch; text-align:right; }
</style>
</head>
<body onload="window.focus(); window.print(); setTimeout(()=>window.close(), 300);">
<div class="receipt">
    <div class="center title">Eat Unwind Tea</div>
    <div class="center" style="font-size:10px;">Apostol Street Poblacion 1</div>
    <div class="center" style="font-size:10px;">Phone: +639-052-883-320</div>
    <div class="qr"><img src="${qrSrc}" alt="QR"></div>
    <div class="center" style="font-size:9px;">For more info, visit our Facebook</div>
    <div class="divider"></div>
    <div class="band">${(data.orderType||'').toUpperCase()}</div>
    <div class="divider"></div>
    ${data.orderId ? `<div><span style="font-weight:bold;">ORDER No:</span> ${data.orderId}</div>` : ``}
    <div><span style="font-weight:bold;">DATE:</span> ${dateText}</div>
    ${isDineIn && data.tableNumber ? `<div style="font-weight:bold;">TABLE No: ${data.tableNumber}</div>` : ``}
    <div class="divider"></div>
    <div class="cols mono"><span class="c-left">ITEM</span><span class="c-right">QTY  PRICE  TOTAL</span></div>
    <div class="divider"></div>
    ${itemsHtml}
    <div class="divider"></div>
    <div class="row"><span style="font-weight:bold;">SUBTOTAL:</span><span style="font-weight:bold;">₱${formatMoney(data.subtotal)}</span></div>
    <div class="row"><span style="font-weight:bold;">TOTAL:</span><span style="font-weight:bold;">₱${formatMoney(data.total)}</span></div>
    <div class="divider"></div>
    <div style="font-weight:bold; font-size:11px;">PAYMENT DETAILS</div>
    <div class="row"><span style="font-weight:bold;">METHOD:</span><span style="font-weight:bold;">${escapeHtml(data.payment?.method || 'N/A')}</span></div>
    <div class="row"><span style="font-weight:bold;">AMOUNT PAID:</span><span style="font-weight:bold;">₱${formatMoney(data.payment?.amountPaid || 0)}</span></div>
    <div class="row"><span style="font-weight:bold;">CHANGE:</span><span style="font-weight:bold;">₱${formatMoney(data.payment?.change || 0)}</span></div>
    <div class="divider"></div>
    <div class="center" style="margin-top:4px;">Thank you for dining with us!</div>
    <div class="center" style="font-size:9px;">Receipt #${String(data.orderNumber || '').slice(-8)}</div>
  </div>
</body>
</html>`;
    w.document.write(html);
    w.document.close();
}
</script>
<?php
$pageContent = ob_get_clean();
?>
