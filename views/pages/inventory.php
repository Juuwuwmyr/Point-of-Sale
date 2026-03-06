<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
$auth = new AuthController();
$auth->requireLogin();
if (!$auth->canAccessInventory()) {
    header('Location: app.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Inventory.php';

$database = new Database();
$db = $database->getConnection();
$inventory = new Inventory($db);

$inventoryItems = $inventory->getAll();
$lowStockItems = $inventory->getLowStock();

$pageTitle = 'Inventory - E.U.T Restaurant POS';
$pageStyles = ['pages/inventory.css', 'style.css'];
$pageScripts = [];

ob_start();
?>
<div class="inventory-page page-content">
    <div class="page-header">
        <h1>Inventory Management</h1>
        <button class="btn btn-primary" onclick="openInvModal()">+ Add Item</button>
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

    <div class="inv-stats">
        <div class="stat-card">
            <div class="stat-number"><?= count($inventoryItems) ?></div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($lowStockItems) ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($inventoryItems) - count($lowStockItems) ?></div>
            <div class="stat-label">Well Stocked</div>
        </div>
    </div>

    <div class="inv-grid">
        <div class="inv-card">
            <h3>Low Stock Alerts</h3>
            <?php if (!empty($lowStockItems)): ?>
                <?php foreach ($lowStockItems as $item): ?>
                <div class="inv-row low">
                    <div class="inv-info">
                        <div class="name"><?= htmlspecialchars($item['ItemName']) ?></div>
                        <div class="detail"><?= $item['Quantity'] ?> <?= htmlspecialchars($item['Unit']) ?> • Reorder at <?= $item['ReorderLevel'] ?></div>
                    </div>
                    <button class="btn btn-sm btn-warning" onclick="updateStock(<?= $item['InventoryID'] ?>)">Update</button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="inv-all-ok">All items well stocked</p>
            <?php endif; ?>
        </div>

        <div class="inv-card">
            <div class="card-head">
                <h3>Inventory Items</h3>
                <input type="text" id="searchInv" class="search-input" placeholder="Search..." onkeyup="filterInv()">
            </div>
            <div class="card-scroll">
            <?php foreach ($inventoryItems as $item): ?>
            <?php $isLow = $item['Quantity'] <= $item['ReorderLevel']; ?>
            <div class="inv-row <?= $isLow ? 'low' : '' ?>" data-name="<?= strtolower(htmlspecialchars($item['ItemName'])) ?>">
                <div class="inv-info">
                    <div class="name"><?= htmlspecialchars($item['ItemName']) ?>
                        <span class="stock-dot <?= $isLow ? 'stock-low' : 'stock-ok' ?>"></span>
                    </div>
                    <div class="detail"><?= htmlspecialchars($item['Description'] ?? '') ?> • <?= $item['Quantity'] ?> <?= htmlspecialchars($item['Unit']) ?> • ₱<?= number_format($item['CostPerUnit'] ?? 0, 2) ?>/unit</div>
                </div>
                <div style="display:flex;gap:0.5rem;">
                    <button class="btn btn-sm btn-warning" onclick="updateStock(<?= $item['InventoryID'] ?>)">Update Stock</button>
                    <button class="btn btn-sm btn-secondary" onclick="editInv(<?= $item['InventoryID'] ?>)">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteInv(<?= $item['InventoryID'] ?>)">Del</button>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div id="invModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="invModalTitle">Add Inventory Item</h3>
            <button class="modal-close" onclick="closeInvModal()">&times;</button>
        </div>
        <form id="invForm" action="controllers/InventoryController.php" method="POST">
            <input type="hidden" name="action" id="invAction" value="add_inventory">
            <input type="hidden" name="inventory_id" id="invId">
            <div class="form-row">
                <div class="form-group"><label>Item Name *</label><input type="text" name="item_name" class="form-control" required></div>
                <div class="form-group"><label>Unit *</label><input type="text" name="unit" class="form-control" placeholder="kg, pcs" required></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Quantity *</label><input type="number" name="quantity" class="form-control" step="0.01" min="0" required></div>
                <div class="form-group"><label>Reorder Level *</label><input type="number" name="reorder_level" class="form-control" step="0.01" min="0" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Cost/Unit</label><input type="number" name="cost_per_unit" class="form-control" step="0.01" min="0"></div>
                <div class="form-group"><label>Supplier</label><input type="text" name="supplier" class="form-control"></div>
            </div>
            <div style="display:flex;gap:0.5rem;margin-top:1rem;">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeInvModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="stockModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Update Stock</h3>
            <button class="modal-close" onclick="closeStockModal()">&times;</button>
        </div>
        <form action="controllers/InventoryController.php" method="POST">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="inventory_id" id="stockInvId">
            <div class="form-group"><label>New Quantity *</label><input type="number" name="new_quantity" class="form-control" step="0.01" min="0" required></div>
            <div style="display:flex;gap:0.5rem;margin-top:1rem;">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" class="btn btn-secondary" onclick="closeStockModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openInvModal(id) {
    document.getElementById('invModalTitle').textContent = id ? 'Edit Item' : 'Add Item';
    document.getElementById('invAction').value = id ? 'update_inventory' : 'add_inventory';
    document.getElementById('invId').value = id || '';
    if (!id) document.getElementById('invForm').reset();
    document.getElementById('invModal').classList.add('show');
}
function closeInvModal() { document.getElementById('invModal').classList.remove('show'); }
function editInv(id) { openInvModal(id); }
function deleteInv(id) {
    if (!confirm('Delete this item?')) return;
    const f = document.createElement('form'); f.method = 'POST'; f.action = 'controllers/InventoryController.php';
    const a = document.createElement('input'); a.type = 'hidden'; a.name = 'action'; a.value = 'delete_inventory';
    const i = document.createElement('input'); i.type = 'hidden'; i.name = 'inventory_id'; i.value = id;
    f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit();
}
function updateStock(id) {
    document.getElementById('stockInvId').value = id;
    document.getElementById('stockModal').classList.add('show');
}
function closeStockModal() { document.getElementById('stockModal').classList.remove('show'); }
function filterInv() {
    const q = document.getElementById('searchInv').value.toLowerCase();
    document.querySelectorAll('.inv-row[data-name]').forEach(r => {
        r.style.display = r.dataset.name.includes(q) ? 'flex' : 'none';
    });
}
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('show'); }));
</script>
<?php
$pageContent = ob_get_clean();
?>
