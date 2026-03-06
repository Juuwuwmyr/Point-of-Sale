<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/AuthController.php';

class InventoryController {
    private $db;
    private $inventory;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->inventory = new Inventory($this->db);
        $this->auth = new AuthController();
        $this->auth->requireLogin();
        if (!$this->auth->canAccessInventory()) {
            $_SESSION['error'] = "Access denied. Inventory requires Admin or Manager role.";
            header('Location: ../app.php');
            exit;
        }
    }
    
    public function index() {
        $inventoryItems = $this->inventory->getAll();
        $lowStockItems = $this->inventory->getLowStock();
        $currentUser = $this->auth->getCurrentUser();
        
        include '../views/admin/inventory.php';
    }
    
    public function addInventoryItem() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->inventory->ItemName = $_POST['item_name'];
            $this->inventory->Description = $_POST['description'] ?? '';
            $this->inventory->Quantity = $_POST['quantity'];
            $this->inventory->Unit = $_POST['unit'];
            $this->inventory->ReorderLevel = $_POST['reorder_level'] ?? 0;
            $this->inventory->CostPerUnit = $_POST['cost_per_unit'] ?? 0;
            $this->inventory->Supplier = $_POST['supplier'] ?? '';
            
            if($this->inventory->create()) {
                $_SESSION['success'] = "Inventory item added successfully";
            } else {
                $_SESSION['error'] = "Failed to add inventory item";
            }
            
            header('Location: ../app.php?page=inventory');
            exit;
        }
    }
    
    public function updateInventoryItem() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->inventory->InventoryID = $_POST['inventory_id'];
            $this->inventory->ItemName = $_POST['item_name'];
            $this->inventory->Description = $_POST['description'] ?? '';
            $this->inventory->Quantity = $_POST['quantity'];
            $this->inventory->Unit = $_POST['unit'];
            $this->inventory->ReorderLevel = $_POST['reorder_level'] ?? 0;
            $this->inventory->CostPerUnit = $_POST['cost_per_unit'] ?? 0;
            $this->inventory->Supplier = $_POST['supplier'] ?? '';
            
            if($this->inventory->update()) {
                $_SESSION['success'] = "Inventory item updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update inventory item";
            }
            
            header('Location: ../app.php?page=inventory');
            exit;
        }
    }
    
    public function deleteInventoryItem() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->inventory->InventoryID = $_POST['inventory_id'];
            
            if($this->inventory->delete()) {
                $_SESSION['success'] = "Inventory item deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete inventory item";
            }
            
            header('Location: ../app.php?page=inventory');
            exit;
        }
    }
    
    public function updateStock() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->inventory->InventoryID = $_POST['inventory_id'];
            $newQuantity = $_POST['new_quantity'];
            
            if($this->inventory->updateQuantity($newQuantity)) {
                $_SESSION['success'] = "Stock updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update stock";
            }
            
            header('Location: ../app.php?page=inventory');
            exit;
        }
    }
}

// Handle form submissions
if(isset($_POST['action'])) {
    $controller = new InventoryController();
    
    switch($_POST['action']) {
        case 'add_inventory':
            $controller->addInventoryItem();
            break;
        case 'update_inventory':
            $controller->updateInventoryItem();
            break;
        case 'delete_inventory':
            $controller->deleteInventoryItem();
            break;
        case 'update_stock':
            $controller->updateStock();
            break;
    }
} else {
    $controller = new InventoryController();
    $controller->index();
}
?>
