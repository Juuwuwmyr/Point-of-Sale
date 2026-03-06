<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
$auth = new AuthController();
$auth->requireLogin();
if (!$auth->canAccessMenu()) {
    header('Location: app.php');
    exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/MenuItem.php';

$database = new Database();
$db = $database->getConnection();
$category = new Category($db);
$menuItem = new MenuItem($db);

$categories = $category->getAll();
$menuItems = $menuItem->getAll();
$availableCount = count(array_filter($menuItems, fn($m) => $m['IsAvailable'] ?? 1));

$pageTitle = 'Menu & Category - E.U.T Restaurant POS';
$pageStyles = ['pages/menu.css', 'style.css'];
$pageScripts = [];

ob_start();
?>
<style>
/* Compact Modal Design */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-overlay.show {
    display: flex;
}

.modal-box {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    animation: modalSlide 0.2s ease-out;
}

.modal-box.wide {
    max-width: 900px;
}

@keyframes modalSlide {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
    border-radius: 12px 12px 0 0;
    position: sticky;
    top: 0;
    z-index: 10;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
    padding: 0;
    line-height: 1;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #e2e8f0;
    color: #1e293b;
}

/* Compact Form Layout */
.modal-sections {
    padding: 1rem 1.5rem;
}

.modal-section {
    margin-bottom: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
}

.modal-section:last-child {
    margin-bottom: 0;
}

.section-header {
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
}

.section-divider {
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, #cbd5e1 0%, #e2e8f0 100%);
}

.section-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.form-group label {
    font-size: 0.8rem;
    font-weight: 500;
    color: #475569;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-control {
    padding: 0.5rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.2s;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}

.form-control:disabled {
    background: #f1f5f9;
    cursor: not-allowed;
}

.form-text {
    font-size: 0.7rem;
    color: #64748b;
    margin-top: 0.125rem;
}

/* Compact Options Grid */
.options-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.option-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.option-header {
    padding: 0.5rem 0.75rem;
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.option-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: #334155;
}

.badge {
    background: #3b82f6;
    color: white;
    padding: 0.125rem 0.375rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
}

.option-content {
    padding: 0.75rem;
}

/* Compact Forms */
.flavor-form, .modifier-form {
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px dashed #e2e8f0;
}

.flavors-list, .modifiers-list {
    min-height: 80px;
    max-height: 150px;
    overflow-y: auto;
}

.modifier-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modifier-card:last-child {
    margin-bottom: 0;
}

.modifier-info {
    flex: 1;
    min-width: 0;
}

.modifier-name {
    font-size: 0.8rem;
    font-weight: 500;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.modifier-price {
    font-size: 0.7rem;
    color: #64748b;
}

.modifier-actions {
    display: flex;
    gap: 0.25rem;
}

.modifier-actions .btn-sm {
    padding: 0.125rem 0.375rem;
    font-size: 0.7rem;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 6px;
}

.empty-state-icon {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
    opacity: 0.5;
}

.empty-state-text {
    font-size: 0.8rem;
    font-weight: 500;
    color: #475569;
}

.empty-state-subtext {
    font-size: 0.7rem;
    color: #94a3b8;
}

/* Toggle Switch */
.toggle-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 500;
    color: #475569;
}

.toggle-switch {
    width: 36px;
    height: 20px;
    background: #cbd5e1;
    border-radius: 20px;
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
}

.toggle-switch.active {
    background: #3b82f6;
}

.toggle-switch::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.2s;
}

.toggle-switch.active::after {
    transform: translateX(16px);
}

.toggle-switch input {
    display: none;
}

/* Image Preview */
.image-preview {
    margin-top: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 0.5rem;
    background: #f8fafc;
}

.image-preview img {
    max-width: 100%;
    max-height: 100px;
    border-radius: 4px;
    display: block;
    margin: 0 auto;
}

.image-preview small {
    display: block;
    text-align: center;
    font-size: 0.7rem;
    color: #64748b;
    margin-top: 0.25rem;
}

/* Modal Footer */
.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    background: #f8fafc;
    border-radius: 0 0 12px 12px;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border-color: #2563eb;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
    border-color: #cbd5e1;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

.btn-warning {
    background: #f59e0b;
    color: white;
    border-color: #d97706;
}

.btn-warning:hover {
    background: #d97706;
}

.btn-danger {
    background: #ef4444;
    color: white;
    border-color: #dc2626;
}

.btn-danger:hover {
    background: #dc2626;
}

/* Responsive */
@media (max-width: 768px) {
    .modal-box {
        max-width: 100%;
        margin: 1rem;
    }
    
    .options-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="menu-page page-content">
    <div class="page-header">
        <h1>Menu & Category Management</h1>
    </div>
    <div style="margin:12px 0;">
        <input id="menuSearch" class="form-control" type="text" placeholder="Search categories or menu items" />
    </div>

    <?php
    if (isset($_SESSION['success'])) {
        echo '<div class="alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    ?>

    <div class="menu-stats">
        <div class="stat-card">
            <div class="stat-number"><?= count($categories) ?></div>
            <div class="stat-label">Categories</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($menuItems) ?></div>
            <div class="stat-label">Menu Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-number available-count"><?= $availableCount ?></div>
            <div class="stat-label">Available</div>
        </div>
    </div>

    <div class="menu-grid">
        <div class="menu-card">
            <div class="card-head">
                <h3>Categories</h3>
                <button class="btn btn-primary btn-sm" onclick="openCategoryModal()">+ Add</button>
            </div>
            <div class="card-scroll">
            <?php foreach ($categories as $cat): ?>
            <div class="item-row" data-search="<?= htmlspecialchars(($cat['CategoryName'] ?? '') . ' ' . ($cat['Description'] ?? '')) ?>">
                <div class="item-info">
                    <div class="name"><?= htmlspecialchars($cat['CategoryName']) ?></div>
                    <div class="detail"><?= htmlspecialchars($cat['Description'] ?? 'No description') ?></div>
                </div>
                <div class="item-actions">
                    <button class="btn btn-sm btn-warning" onclick="editCategory(<?= $cat['CategoryID'] ?>)">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?= $cat['CategoryID'] ?>)">Del</button>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="menu-card">
            <div class="card-head">
                <h3>Menu Items</h3>
                <button class="btn btn-primary btn-sm" onclick="openMenuItemModal()">+ Add Item</button>
            </div>
            <div class="card-scroll">
            <?php foreach ($menuItems as $item): ?>
            <div class="item-row" data-search="<?= htmlspecialchars(($item['ItemName'] ?? '') . ' ' . ($item['CategoryName'] ?? '')) ?>">
                <div class="item-info">
                    <div class="name"><?= htmlspecialchars($item['ItemName']) ?></div>
                    <div class="detail">
                        <?= htmlspecialchars($item['CategoryName']) ?> |
                        <?php
                            if (isset($item['IsFree']) && $item['IsFree']) {
                                echo '<span style="color:#0d9488">FREE</span>';
                            } elseif (!isset($item['Price']) || $item['Price'] === null || $item['Price'] === '') {
                                echo '<span style="color:#64748b">ADD ON</span>';
                            } else {
                                echo '₱' . number_format((float)$item['Price'], 2);
                            }
                        ?> |
                        Cost: ₱<?= number_format($item['Cost'] ?? 0, 2) ?>
                    </div>
                </div>
                <div class="item-actions">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-right:0.5rem;">
                        <span style="font-size:0.75rem;">Avail:</span>
                        <div class="toggle-switch <?= $item['IsAvailable'] ? 'active' : '' ?>" onclick="toggleAvail(<?= $item['ItemID'] ?>)"></div>
                    </div>

                    <button class="btn btn-sm btn-warning" onclick="editMenuItem(<?= $item['ItemID'] ?>)">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteMenuItem(<?= $item['ItemID'] ?>)">Del</button>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal - Compact -->
<div id="categoryModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="categoryModalTitle">Add Category</h3>
            <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
        </div>
        <form id="categoryForm" action="controllers/MenuController.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="categoryAction" value="add_category">
            <input type="hidden" name="category_id" id="categoryId">
            
            <div class="modal-sections">
                <div class="modal-section">
                    <div class="section-header">
                        <div class="section-title">📁 Category Details</div>
                        <div class="section-divider"></div>
                    </div>
                    <div class="section-content">
                        <div class="form-group">
                            <label for="category_name">Category Name *</label>
                            <input type="text" name="category_name" id="category_name" class="form-control" required placeholder="Enter category name">
                        </div>
                        <div class="form-group">
                            <label for="cat_description">Description</label>
                            <textarea name="description" id="cat_description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="category_image">Category Image</label>
                            <input type="file" name="category_image" id="category_image" accept="image/*" class="form-control" onchange="previewCategoryImage(event)">
                            <div id="categoryImagePreview" class="image-preview" style="display: none;">
                                <img id="categoryPreviewImg" src="" alt="Preview">
                                <small>Current image</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Menu Item Modal - Compact -->
<div id="menuItemModal" class="modal-overlay">
    <div class="modal-box wide">
        <div class="modal-header">
            <h3 id="menuItemModalTitle">Add Menu Item</h3>
            <button class="modal-close" onclick="closeMenuItemModal()">&times;</button>
        </div>
        <form id="menuItemForm" action="controllers/MenuController.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="menuItemAction" value="add_menu_item">
            <input type="hidden" name="item_id" id="itemId">
            
            <div class="modal-sections">
                <!-- Basic Information -->
                <div class="modal-section">
                    <div class="section-header">
                        <div class="section-title">📝 Basic Info</div>
                        <div class="section-divider"></div>
                    </div>
                    <div class="section-content">
                        <div class="form-group">
                            <label for="item_name">Item Name *</label>
                            <input type="text" name="item_name" id="item_name" class="form-control" required placeholder="Enter item name">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_id">Category *</label>
                                <select name="category_id" id="category_id" class="form-control" required>
                                    <option value="">Select category</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= $c['CategoryID'] ?>"><?= htmlspecialchars($c['CategoryName']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="item_image">Image</label>
                                <input type="file" name="item_image" id="item_image" accept="image/*" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="modal-section">
                    <div class="section-header">
                        <div class="section-title">💰 Pricing</div>
                        <div class="section-divider"></div>
                    </div>
                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Price</label>
                                <input type="number" name="price" id="price" class="form-control" step="0.01" min="0" placeholder="0.00">
                                <small class="form-text" id="priceHelp">Leave empty for modifier-based pricing</small>
                            </div>
                            <div class="form-group">
                                <label for="cost">Cost</label>
                                <input type="number" name="cost" id="cost" class="form-control" step="0.01" min="0" placeholder="0.00" value="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="toggle-label">
                                <div class="toggle-switch" id="is_free_toggle">
                                    <input type="checkbox" name="is_free" id="is_free" onchange="togglePricingFields()">
                                    <span class="toggle-slider"></span>
                                </div>
                                <span class="toggle-text">Free Item</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Flavors & Modifiers -->
                <div class="modal-section">
                    <div class="section-header">
                        <div class="section-title">🍽 Options</div>
                        <div class="section-divider"></div>
                    </div>
                    <div class="section-content">
                        <div class="options-grid">
                            <!-- Flavors -->
                            <div class="option-card">
                                <div class="option-header">
                                    <div class="option-title">🧂 Flavors</div>
                                    <span class="badge" id="flavorCount">0</span>
                                </div>
                                <div class="option-content">
                                    <div class="flavor-form">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <input type="text" id="flavor_name" class="form-control" placeholder="Flavor name">
                                            </div>
                                            <div class="form-group">
                                                <input type="number" id="flavor_price" class="form-control" placeholder="Price adj." step="0.01" min="0" value="0">
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addFlavor()" style="width:100%;margin-top:0.25rem;">
                                            <span>➕</span> Add Flavor
                                        </button>
                                    </div>
                                    <div class="flavors-list" id="itemFlavorsList">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">🍦</div>
                                            <div class="empty-state-text">No flavors added</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modifiers -->
                            <div class="option-card">
                                <div class="option-header">
                                    <div class="option-title">➕ Modifiers</div>
                                    <span class="badge" id="modifierCount">0</span>
                                </div>
                                <div class="option-content">
                                    <div class="modifier-form">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <select id="modifier_from_flavor" class="form-control" onchange="applyFlavorToModifier()">
                                                    <option value="">Select flavor</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <input type="text" id="modifier_name" class="form-control" placeholder="Modifier name">
                                            </div>
                                            <div class="form-group">
                                                <input type="number" id="modifier_price" class="form-control" placeholder="Price" step="0.01" min="0" value="0">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <select id="modifier_affects_price" class="form-control">
                                                    <option value="1">Affects price</option>
                                                    <option value="0">No price effect</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="addModifierToItem()" style="width:100%;">
                                                    <span>+</span> Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modifiers-list" id="itemModifiersList">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">⚙️</div>
                                            <div class="empty-state-text">No modifiers added</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="modal-section">
                    <div class="section-header">
                        <div class="section-title">📋 Status</div>
                        <div class="section-divider"></div>
                    </div>
                    <div class="section-content">
                        <div class="form-group">
                            <label class="toggle-label">
                                <div class="toggle-switch" id="is_available_toggle">
                                    <input type="checkbox" name="is_available" id="is_available" value="1" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                <span class="toggle-text">Available in POS</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeMenuItemModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <span>💾</span> Save Item
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Simple client-side search for categories and menu items
function filterMenuPage() {
    const q = (document.getElementById('menuSearch')?.value || '').toLowerCase();
    document.querySelectorAll('.menu-card .item-row[data-search]').forEach(row => {
        const hay = (row.getAttribute('data-search') || '').toLowerCase();
        row.style.display = hay.includes(q) ? '' : 'none';
    });
}
document.addEventListener('DOMContentLoaded', function() {
    const ms = document.getElementById('menuSearch');
    if (ms) ms.addEventListener('input', filterMenuPage);
});
var currentItemId = null;
var itemModifiers = [];
var itemFlavors = [];
var availableFlavors = [];

function escapeHtml(text) {
    const str = String(text ?? '');
    return str
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function scrollToTopThen(cb) {
    try {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (e) {
        window.scrollTo(0, 0);
    }
    setTimeout(() => {
        if (typeof cb === 'function') cb();
    }, 200);
}

function openCategoryModal(id) {
    const modal = document.getElementById('categoryModal');
    const title = document.getElementById('categoryModalTitle');
    const action = document.getElementById('categoryAction');
    const categoryId = document.getElementById('categoryId');
    const nameInput = document.getElementById('category_name');
    const descInput = document.getElementById('cat_description');
    const imagePreview = document.getElementById('categoryImagePreview');
    const previewImg = document.getElementById('categoryPreviewImg');
    
    document.getElementById('categoryForm').reset();
    imagePreview.style.display = 'none';
    
    if (id) {
        title.textContent = 'Edit Category';
        action.value = 'update_category';
        categoryId.value = id;
        
        fetch(`controllers/MenuController.php?action=get_category&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const category = data.category;
                    nameInput.value = category.CategoryName || '';
                    descInput.value = category.Description || '';
                    
                    if (category.ImagePath) {
                        previewImg.src = category.ImagePath;
                        imagePreview.style.display = 'block';
                    }
                } else {
                    alert('Failed to load category data');
                }
            })
            .catch(() => alert('Error loading category data'));
    } else {
        title.textContent = 'Add Category';
        action.value = 'add_category';
        categoryId.value = '';
    }
    
    modal.classList.add('show');
}

function previewCategoryImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('categoryPreviewImg');
    const previewContainer = document.getElementById('categoryImagePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function closeCategoryModal() { 
    document.getElementById('categoryModal').classList.remove('show'); 
}

function editCategory(id) { 
    scrollToTopThen(() => openCategoryModal(id)); 
}

function deleteCategory(id) {
    if (!confirm('Delete this category?')) return;
    const f = document.createElement('form'); 
    f.method = 'POST'; 
    f.action = 'controllers/MenuController.php';
    const a = document.createElement('input'); 
    a.type = 'hidden'; 
    a.name = 'action'; 
    a.value = 'delete_category';
    const i = document.createElement('input'); 
    i.type = 'hidden'; 
    i.name = 'category_id'; 
    i.value = id;
    f.appendChild(a); 
    f.appendChild(i); 
    document.body.appendChild(f); 
    f.submit();
}

function openMenuItemModal(id) {
    itemModifiers = [];
    itemFlavors = [];
    document.getElementById('menuItemModalTitle').textContent = id ? 'Edit Menu Item' : 'Add Menu Item';
    document.getElementById('menuItemAction').value = id ? 'update_menu_item' : 'add_menu_item';
    document.getElementById('itemId').value = id || '';
    
    if (!id) {
        document.getElementById('menuItemForm').reset();
        updateModifierDisplay();
        updatePriceField();
        loadFlavors();
    } else {
        loadMenuItemData(id);
    }
    document.getElementById('menuItemModal').classList.add('show');
}

function loadMenuItemData(itemId) {
    fetch(`controllers/MenuController.php?action=get_menu_item&item_id=${itemId}`)
        .then(r => r.json())
        .then(data => {
            if (data && data.success && data.item) {
                const it = data.item;
                document.getElementById('item_name').value = it.ItemName ?? '';
                document.getElementById('category_id').value = it.CategoryID ?? '';
                document.getElementById('cost').value = it.Cost ?? '';

                const freeCheckbox = document.getElementById('is_free');
                freeCheckbox.checked = parseInt(it.IsFree || 0, 10) === 1;

                const availableCheckbox = document.getElementById('is_available');
                availableCheckbox.checked = parseInt(it.IsAvailable || 0, 10) === 1;

                const p = document.getElementById('price');
                p.value = (it.Price === null || typeof it.Price === 'undefined') ? '' : it.Price;
                updatePriceField();
                togglePricingFields();
            }
        })
        .catch(() => {});

    fetch('controllers/POSController.php?action=getModifiers&item_id=' + itemId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.modifiers) {
                itemModifiers = data.modifiers.map(m => ({
                    ...m,
                    Price: parseFloat(m.Price) || 0,
                    AffectsPrice: parseInt(m.AffectsPrice ?? 0, 10) || 0
                }));
                updateModifierDisplay();
                updatePriceField();
            }
        })
        .catch(() => {});
    
    loadItemFlavors(itemId);
    loadFlavors();
}

function closeMenuItemModal() { 
    document.getElementById('menuItemModal').classList.remove('show'); 
    itemModifiers = [];
    itemFlavors = [];
}

function editMenuItem(id) { 
    scrollToTopThen(() => openMenuItemModal(id)); 
}

function deleteMenuItem(id) {
    if (!confirm('Delete this menu item?')) return;
    const f = document.createElement('form'); 
    f.method = 'POST'; 
    f.action = 'controllers/MenuController.php';
    const a = document.createElement('input'); 
    a.type = 'hidden'; 
    a.name = 'action'; 
    a.value = 'delete_menu_item';
    const i = document.createElement('input'); 
    i.type = 'hidden'; 
    i.name = 'item_id'; 
    i.value = id;
    f.appendChild(a); 
    f.appendChild(i); 
    document.body.appendChild(f); 
    f.submit();
}

function toggleAvail(id) {
    const toggleElement = document.querySelector(`[onclick="toggleAvail(${id})"]`);
    const isCurrentlyActive = toggleElement.classList.contains('active');
    const newAvailability = isCurrentlyActive ? 0 : 1;
    
    fetch('controllers/MenuController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_availability&item_id=${id}&is_available=${newAvailability}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (newAvailability === 1) {
                toggleElement.classList.add('active');
            } else {
                toggleElement.classList.remove('active');
            }
            
            const availableCountElement = document.querySelector('.available-count');
            if (availableCountElement) {
                const currentCount = parseInt(availableCountElement.textContent);
                availableCountElement.textContent = newAvailability === 1 ? currentCount + 1 : currentCount - 1;
            }
        } else {
            alert('Failed to update availability');
        }
    })
    .catch(() => alert('Error updating availability'));
}

function togglePricingFields() {
    const freeCheckbox = document.getElementById('is_free');
    const priceField = document.getElementById('price');
    const hasModifiers = itemModifiers.length > 0;
    const hasPricedFlavor = (itemFlavors || []).some(f => (parseFloat(f.PriceAdjustment) || 0) > 0);
    priceField.required = false;
    
    if (!freeCheckbox.checked && (hasModifiers || hasPricedFlavor)) {
        priceField.disabled = true;
        priceField.value = '';
        document.getElementById('priceHelp').textContent = 'Price managed by options';
    } else if (freeCheckbox.checked) {
        priceField.disabled = true;
        priceField.value = 0;
        document.getElementById('priceHelp').textContent = 'Free item';
    } else {
        priceField.disabled = false;
        document.getElementById('priceHelp').textContent = 'Leave empty for option-based pricing';
    }
}

function addModifierToItem() {
    const name = document.getElementById('modifier_name').value.trim();
    if (!name) { alert('Enter modifier name'); return; }
    
    const price = parseFloat(document.getElementById('modifier_price').value) || 0;
    const affectsRaw = parseInt(document.getElementById('modifier_affects_price').value, 10) || 0;
    const affects = price > 0 ? 1 : affectsRaw;
    
    const flavorSelect = document.getElementById('modifier_from_flavor');
    const flavorId = (flavorSelect?.value || '').trim();
    
    const modifier = {
        ModifierID: 'temp_' + Date.now() + Math.random(),
        Name: name,
        DisplayName: name,
        FlavorID: flavorId || null,
        Price: price,
        AffectsPrice: affects,
        EncodedName: flavorId ? `__FLAVORID:${flavorId}__|${name}` : name
    };
    
    itemModifiers.push(modifier);
    document.getElementById('modifier_name').value = '';
    document.getElementById('modifier_price').value = '0';
    
    updateModifierDisplay();
    updatePriceField();
}

function removeModifierFromItem(modifierId) {
    itemModifiers = itemModifiers.filter(m => m.ModifierID !== modifierId);
    updateModifierDisplay();
    updatePriceField();
}

function updateModifierDisplay() {
    const list = document.getElementById('itemModifiersList');
    const count = document.getElementById('modifierCount');
    
    count.textContent = itemModifiers.length;
    
    if (itemModifiers.length > 0) {
        list.innerHTML = itemModifiers.map(m => {
            const flavor = m.FlavorID ? itemFlavors.find(f => f.FlavorID == m.FlavorID) : null;
            const flavorName = flavor ? flavor.FlavorName : 'No flavor';
            
            return `
                <div class="modifier-card">
                    <div class="modifier-info">
                        <div class="modifier-name">${escapeHtml(m.DisplayName || m.Name)}</div>
                        <div class="modifier-price">
                            ${(parseInt(m.AffectsPrice ?? 0, 10) === 1) ? 
                                (parseFloat(m.Price) > 0 ? '₱' + parseFloat(m.Price).toFixed(2) : 'No charge') : 
                                'No charge'}
                            · ${escapeHtml(flavorName)}
                        </div>
                    </div>
                    <div class="modifier-actions">
                        <button class="btn btn-sm btn-danger" onclick="removeModifierFromItem('${m.ModifierID}')">🗑️</button>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        list.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-text">No modifiers added</div>
            </div>
        `;
    }
}

function updatePriceField() {
    const priceField = document.getElementById('price');
    const freeCheckbox = document.getElementById('is_free');
    const hasModifiers = itemModifiers.length > 0;
    const hasPricedFlavor = (itemFlavors || []).some(f => (parseFloat(f.PriceAdjustment) || 0) > 0);
    
    if (!freeCheckbox.checked && (hasModifiers || hasPricedFlavor)) {
        priceField.disabled = true;
        priceField.required = false;
        priceField.value = '';
        document.getElementById('priceHelp').textContent = 'Price managed by options';
    } else {
        togglePricingFields();
    }
}

// Flavor Functions
function loadFlavors() {
    fetch('controllers/MenuController.php?action=get_flavors')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                availableFlavors = data.flavors;
                displayFlavors();
            }
        })
        .catch(() => {});
}

function displayFlavors() {
    const container = document.getElementById('itemFlavorsList');
    const count = document.getElementById('flavorCount');
    
    count.textContent = itemFlavors.length;
    
    if (itemFlavors.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-text">No flavors added</div>
            </div>
        `;
        updateModifierFlavorDropdown();
        return;
    }
    
    container.innerHTML = itemFlavors.map(flavor => {
        const priceAdj = parseFloat(flavor.PriceAdjustment);
        const priceDisplay = !isNaN(priceAdj) && priceAdj > 0 ? `₱${priceAdj.toFixed(2)}` : 'No charge';
        const isDefault = flavor.IsDefault === 1;
        
        return `
            <div class="modifier-card">
                <div class="modifier-info">
                    <div class="modifier-name">
                        <label style="display:flex;align-items:center;">
                            <input type="checkbox" 
                                   value="${flavor.FlavorID}" 
                                   data-flavor-name="${escapeHtml(flavor.FlavorName)}"
                                   data-flavor-price="${!isNaN(priceAdj) ? priceAdj : 0}"
                                   checked
                                   onchange="toggleFlavor(this.value, this.dataset.flavorName, parseFloat(this.dataset.flavorPrice) || 0)"
                                   style="margin-right:4px;">
                            ${escapeHtml(flavor.FlavorName)}
                        </label>
                    </div>
                    <div class="modifier-price">${priceDisplay}</div>
                </div>
                <div class="modifier-actions">
                    <label style="display:flex;align-items:center;margin-right:4px;">
                        <input type="radio" 
                               name="default_flavor" 
                               value="${flavor.FlavorID}" 
                               ${isDefault ? 'checked' : ''}
                               onchange="setDefaultFlavor(${flavor.FlavorID})"
                               style="margin-right:2px;">
                        <span style="font-size:10px;">Default</span>
                    </label>
                    <button class="btn btn-sm btn-danger" onclick="removeFlavor('${flavor.FlavorID}')">🗑️</button>
                </div>
            </div>
        `;
    }).join('');

    updateModifierFlavorDropdown();
}

function updateModifierFlavorDropdown() {
    const select = document.getElementById('modifier_from_flavor');
    if (!select) return;

    const byId = new Map();
    itemFlavors.forEach(f => {
        const id = f.FlavorID;
        const name = (f.FlavorName && String(f.FlavorName).trim()) ? String(f.FlavorName).trim() : '';
        if (!id || !name) return;
        byId.set(String(id), {
            id: String(id),
            name,
            price: parseFloat(f.PriceAdjustment) || 0
        });
    });

    const options = Array.from(byId.values()).sort((a, b) => a.name.localeCompare(b.name));
    select.innerHTML = '<option value="">Select flavor</option>' + options.map(o => {
        const p = !isNaN(o.price) && o.price > 0 ? ` (₱${o.price.toFixed(2)})` : '';
        return `<option value="${o.id}">${escapeHtml(o.name)}${p}</option>`;
    }).join('');

    select.disabled = options.length === 0;
}

function applyFlavorToModifier() {
    const select = document.getElementById('modifier_from_flavor');
    if (!select) return;
    const priceInput = document.getElementById('modifier_price');
    const affectsInput = document.getElementById('modifier_affects_price');
    if (priceInput) priceInput.disabled = false;
    if (affectsInput) affectsInput.disabled = false;
}

function toggleFlavor(flavorId, flavorName, priceAdjustment) {
    const checkbox = event.target;
    const existingIndex = itemFlavors.findIndex(f => f.FlavorID == flavorId);
    
    if (checkbox.checked) {
        if (existingIndex === -1) {
            itemFlavors.push({
                FlavorID: flavorId,
                FlavorName: flavorName,
                PriceAdjustment: parseFloat(priceAdjustment) || 0,
                IsDefault: itemFlavors.length === 0 ? 1 : 0
            });
        }
    } else {
        if (existingIndex !== -1) {
            itemFlavors.splice(existingIndex, 1);
        }
    }
    
    displayFlavors();
}

function setDefaultFlavor(flavorId) {
    itemFlavors.forEach(flavor => {
        flavor.IsDefault = flavor.FlavorID == flavorId ? 1 : 0;
    });
}

function addFlavor() {
    const name = document.getElementById('flavor_name').value.trim();
    const price = parseFloat(document.getElementById('flavor_price').value);
    
    if (!name) {
        alert('Please enter a flavor name');
        return;
    }

    const priceAdj = !isNaN(price) && price >= 0 ? price : 0;
    
    const tempFlavor = {
        FlavorID: 'temp_' + Date.now(),
        FlavorName: name,
        PriceAdjustment: priceAdj,
        IsDefault: itemFlavors.length === 0 ? 1 : 0
    };
    
    itemFlavors.push(tempFlavor);
    displayFlavors();
    
    document.getElementById('flavor_name').value = '';
    document.getElementById('flavor_price').value = '0';
    document.getElementById('flavor_name').focus();
}

function removeFlavor(flavorId) {
    const index = itemFlavors.findIndex(f => f.FlavorID === flavorId);
    if (index !== -1) {
        itemFlavors.splice(index, 1);
        displayFlavors();
    }
}

function loadItemFlavors(itemId) {
    fetch(`controllers/MenuController.php?action=get_item_flavors&item_id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                itemFlavors = data.flavors.map(flavor => ({
                    ...flavor,
                    FlavorName: flavor.FlavorName ?? flavor.Name ?? '',
                    IsDefault: parseInt(flavor.IsDefault, 10) || 0
                }));
                displayFlavors();
            }
        })
        .catch(() => {});
}

document.getElementById('menuItemForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const freeChecked = document.getElementById('is_free').checked;
    const priceVal = (document.getElementById('price').value || '').trim();
    const hasModifiers = itemModifiers.length > 0;
    const hasPricedFlavor = (itemFlavors || []).some(f => (parseFloat(f.PriceAdjustment) || 0) > 0);
    
    if (!freeChecked && !hasModifiers && !hasPricedFlavor && priceVal === '') {
        alert('Please add base price, a priced flavor, or a modifier-based price.');
        return;
    }

    const tempFlavorIdMap = {};
    for (let flavor of itemFlavors) {
        if (String(flavor.FlavorID).startsWith('temp_')) {
            try {
                const flavorName = flavor.FlavorName ?? flavor.Name ?? '';
                const priceAdj = parseFloat(flavor.PriceAdjustment) || 0;
                const oldId = String(flavor.FlavorID);
                
                const response = await fetch('controllers/add_flavor_standalone.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `flavor_name=${encodeURIComponent(flavorName)}&price_adjustment=${priceAdj}&is_available=1`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const newId = String(result.flavor_id);
                    tempFlavorIdMap[oldId] = newId;
                    flavor.FlavorID = newId;
                } else {
                    throw new Error(result.message || 'Unknown error');
                }
            } catch (error) {
                alert('Error creating flavor. Please try again.');
                return;
            }
        }
    }

    if (itemModifiers.length > 0) {
        itemModifiers = itemModifiers.map(m => {
            const key = String(m.FlavorID || '');
            if (key && key.startsWith('temp_') && tempFlavorIdMap[key]) {
                return { ...m, FlavorID: tempFlavorIdMap[key] };
            }
            return m;
        });
    }

    const modifiersByFlavor = {};
    itemModifiers.forEach(modifier => {
        const flavorId = modifier.FlavorID || 'no_flavor';
        if (!modifiersByFlavor[flavorId]) {
            modifiersByFlavor[flavorId] = [];
        }
        modifiersByFlavor[flavorId].push(modifier);
    });

    itemFlavors.forEach((flavor, index) => {
        const flavorIdInput = document.createElement('input');
        flavorIdInput.type = 'hidden';
        flavorIdInput.name = `flavors[${index}][flavor_id]`;
        flavorIdInput.value = flavor.FlavorID;
        e.target.appendChild(flavorIdInput);

        const isDefaultInput = document.createElement('input');
        isDefaultInput.type = 'hidden';
        isDefaultInput.name = `flavors[${index}][is_default]`;
        isDefaultInput.value = flavor.IsDefault ? 1 : 0;
        e.target.appendChild(isDefaultInput);
    });

    let modifierIndex = 0;
    Object.entries(modifiersByFlavor).forEach(([flavorId, modifiers]) => {
        modifiers.forEach(modifier => {
            const nameInput = document.createElement('input');
            nameInput.type = 'hidden';
            nameInput.name = `modifiers[${modifierIndex}][name]`;
            nameInput.value = modifier.Name;
            
            const priceInput = document.createElement('input');
            priceInput.type = 'hidden';
            priceInput.name = `modifiers[${modifierIndex}][price]`;
            priceInput.value = modifier.Price;
            
            const affectsInput = document.createElement('input');
            affectsInput.type = 'hidden';
            affectsInput.name = `modifiers[${modifierIndex}][affects_price]`;
            affectsInput.value = modifier.AffectsPrice;
            
            const flavorIdInput = document.createElement('input');
            flavorIdInput.type = 'hidden';
            flavorIdInput.name = `modifiers[${modifierIndex}][flavor_id]`;
            flavorIdInput.value = flavorId !== 'no_flavor' ? flavorId : '';
            
            e.target.appendChild(nameInput);
            e.target.appendChild(priceInput);
            e.target.appendChild(affectsInput);
            e.target.appendChild(flavorIdInput);
            
            modifierIndex++;
        });
    });

    e.target.submit();
});

document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', function(e) { 
    if (e.target === this) this.classList.remove('show'); 
}));
</script>
<?php
$pageContent = ob_get_clean();
?>