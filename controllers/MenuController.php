<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/MenuItem.php';
require_once __DIR__ . '/../models/Modifier.php';
require_once __DIR__ . '/../models/Flavor.php';
require_once __DIR__ . '/AuthController.php';

class MenuController {
    private $db;
    private $category;
    private $menuItem;
    private $modifier;
    private $flavor;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->category = new Category($this->db);
        $this->menuItem = new MenuItem($this->db);
        $this->modifier = new Modifier($this->db);
        $this->flavor = new Flavor($this->db);
        $this->auth = new AuthController();
        $this->auth->requireLogin();
        if (!$this->auth->canAccessMenu()) {
            $_SESSION['error'] = "Access denied. Menu management requires Admin or Manager role.";
            header('Location: ../app.php');
            exit;
        }
    }
    
    public function index() {
        $categories = $this->category->getAll();
        $menuItems = $this->menuItem->getAll();
        $currentUser = $this->auth->getCurrentUser();
        
        include '../views/admin/menu.php';
    }
    
    public function addCategory() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->category->CategoryName = $_POST['category_name'];
            $this->category->Description = $_POST['description'] ?? '';
            
            // Handle image upload
            if(isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
                $uploadDir = '../assets/images/';
                if(!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['category_image']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if(move_uploaded_file($_FILES['category_image']['tmp_name'], $targetPath)) {
                    $this->category->ImagePath = $targetPath;
                }
            }
            
            if($this->category->create()) {
                $_SESSION['success'] = "Category added successfully";
            } else {
                $_SESSION['error'] = "Failed to add category";
            }
            
            header('Location: ../app.php?page=menu');
            exit;
        }
    }
    
    public function getCategory() {
        header('Content-Type: application/json');
        
        $categoryId = $_GET['id'] ?? 0;
        
        if ($categoryId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
            return;
        }
        
        try {
            $category = $this->category->getById($categoryId);
            
            if ($category) {
                echo json_encode(['success' => true, 'category' => $category]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Category not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    public function updateCategory() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->category->CategoryID = $_POST['category_id'];
            $this->category->CategoryName = $_POST['category_name'];
            $this->category->Description = $_POST['description'] ?? '';
            
            // Handle image upload
            if(isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
                $uploadDir = '../assets/images/';
                if(!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['category_image']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if(move_uploaded_file($_FILES['category_image']['tmp_name'], $targetPath)) {
                    $this->category->ImagePath = $targetPath;
                }
            }
            
            if($this->category->update()) {
                $_SESSION['success'] = "Category updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update category";
            }
            
            header('Location: ../app.php?page=menu');
            exit;
        }
    }
    
    public function deleteCategory() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->category->CategoryID = $_POST['category_id'];
            
            if($this->category->delete()) {
                $_SESSION['success'] = "Category deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete category";
            }
            
            header('Location: ../app.php?page=menu');
            exit;
        }
    }
    
    public function addMenuItem() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->menuItem->ItemName = $_POST['item_name'];
            $this->menuItem->Description = $_POST['description'] ?? '';
            $this->menuItem->CategoryID = $_POST['category_id'];
            
            // Handle price logic - null if modifiers exist
            $hasModifiers = isset($_POST['modifiers']) && !empty($_POST['modifiers']);
            $isFree = isset($_POST['is_free']) && $_POST['is_free'];
            
            if ($isFree) {
                $this->menuItem->Price = 0;
            } else {
                $rawPrice = isset($_POST['price']) ? trim((string)$_POST['price']) : '';
                $this->menuItem->Price = ($rawPrice === '') ? null : $rawPrice;
            }
            
            $this->menuItem->Cost = $_POST['cost'] ?? 0;
            $this->menuItem->IsAvailable = isset($_POST['is_available']) ? 1 : 0;
            $this->menuItem->IsFree = $isFree ? 1 : 0;
            
            // Handle image upload
            if(isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
                $uploadDir = '../assets/images/';
                if(!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['item_image']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if(move_uploaded_file($_FILES['item_image']['tmp_name'], $targetPath)) {
                    $this->menuItem->ImagePath = $targetPath;
                }
            }
            
            if($this->menuItem->create()) {
                $itemId = $this->db->lastInsertId();
                
                // Add modifiers if provided
                if ($hasModifiers) {
                    $this->addModifiersToItem($itemId, $_POST['modifiers']);
                }
                
                // Add flavors if provided
                if (isset($_POST['flavors']) && !empty($_POST['flavors'])) {
                    $this->addItemFlavors($itemId, $_POST['flavors']);
                }
                
                $_SESSION['success'] = "Menu item added successfully";
            } else {
                $_SESSION['error'] = "Failed to add menu item";
            }
            
            header('Location: ../app.php?page=menu');
            exit;
        }
    }
    
    public function updateMenuItem() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->menuItem->ItemID = $_POST['item_id'];
            $this->menuItem->ItemName = $_POST['item_name'];
            $this->menuItem->Description = $_POST['description'] ?? '';
            $this->menuItem->CategoryID = $_POST['category_id'];
            
            // Handle price logic - null if modifiers exist
            $hasModifiers = isset($_POST['modifiers']) && !empty($_POST['modifiers']);
            $isFree = isset($_POST['is_free']) && $_POST['is_free'];
            
            if ($isFree) {
                $this->menuItem->Price = 0;
            } else {
                $rawPrice = isset($_POST['price']) ? trim((string)$_POST['price']) : '';
                $this->menuItem->Price = ($rawPrice === '') ? null : $rawPrice;
            }
            
            $this->menuItem->Cost = $_POST['cost'] ?? 0;
            $this->menuItem->IsAvailable = isset($_POST['is_available']) ? 1 : 0;
            $this->menuItem->IsFree = $isFree ? 1 : 0;
            
            // Handle image upload
            if(isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
                $uploadDir = '../assets/images/';
                if(!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['item_image']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if(move_uploaded_file($_FILES['item_image']['tmp_name'], $targetPath)) {
                    $this->menuItem->ImagePath = $targetPath;
                }
            }
            
            if($this->menuItem->update()) {
                if (isset($_POST['modifiers'])) {
                    $this->removeItemModifiers($_POST['item_id']);
                    if (!empty($_POST['modifiers'])) {
                        $this->addModifiersToItem($_POST['item_id'], $_POST['modifiers']);
                    }
                }
                
                if (isset($_POST['flavors'])) {
                    $stmt = $this->db->prepare("DELETE FROM item_flavors WHERE ItemID = ?");
                    $stmt->execute([$_POST['item_id']]);
                    if (!empty($_POST['flavors'])) {
                        $this->addItemFlavors($_POST['item_id'], $_POST['flavors']);
                    }
                }
                
                $_SESSION['success'] = "Menu item updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update menu item";
            }
            
            header('Location: ../app.php?page=menu');
            exit;
        }
    }
    
    public function deleteMenuItem() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->menuItem->ItemID = $_POST['item_id'];
            
            if($this->menuItem->delete()) {
                $_SESSION['success'] = "Menu item deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete menu item";
            }
            
            header('Location: ../app.php?page=menu');
            exit;
        }
    }
    
    public function toggleAvailability() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->menuItem->ItemID = $_POST['item_id'];
            
            if($this->menuItem->toggleAvailability()) {
                $_SESSION['success'] = "Menu item availability updated";
            } else {
                $_SESSION['error'] = "Failed to update menu item availability";
            }
            
            header('Location: ../app.php?page=menu');
            exit;
        }
    }

    public function addModifier() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $itemId = $_POST['item_id'];
            $name = $_POST['name'];
            $price = $_POST['price'] ?? 0;
            $affectsPrice = $_POST['affects_price'] ?? 0;
            
            $stmt = $this->db->prepare("INSERT INTO modifiers (ItemID, Name, Price, AffectsPrice, IsAvailable) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$itemId, $name, $price, $affectsPrice]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        }
    }
    
    public function deleteModifier() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $modifierId = $_POST['modifier_id'];
            
            $stmt = $this->db->prepare("DELETE FROM modifiers WHERE ModifierID = ?");
            $stmt->execute([$modifierId]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        }
    }
    
private function addModifiersToItem($itemId, $modifiers) {
    // First remove existing modifiers
    $this->removeItemModifiers($itemId);
    
    // Add new modifiers
    foreach ($modifiers as $modifier) {
        // Check if the name is encoded with flavor ID (from your old system)
        $name = $modifier['name'];
        $flavorId = null;
        
        // Parse encoded name format __FLAVORID:123__|Flavor Name
        if (preg_match('/^__FLAVORID:(\d+)__\|(.*)$/', $name, $matches)) {
            $flavorId = $matches[1];
            $name = $matches[2]; // Clean name without encoding
        }
        
        // Also check if flavor_id was sent directly (from our updated JS)
        if (isset($modifier['flavor_id']) && !empty($modifier['flavor_id'])) {
            $flavorId = trim((string)$modifier['flavor_id']);
            if ($flavorId !== '' && !ctype_digit($flavorId)) {
                $flavorId = null;
            }
        }
        
        $price = $modifier['price'] ?? 0;
        $affectsPrice = $modifier['affects_price'] ?? 0;
        
        // Include FlavorID in the INSERT statement
        $stmt = $this->db->prepare("INSERT INTO modifiers (ItemID, Name, Price, AffectsPrice, FlavorID, IsAvailable) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$itemId, $name, $price, $affectsPrice, $flavorId]);
        
        // Ensure the item's flavors include any flavor referenced by a modifier
        if (!empty($flavorId)) {
            try {
                $this->flavor->addItemFlavor($itemId, (int)$flavorId, 0);
            } catch (\Throwable $e) {
                // Ignore linking errors to avoid blocking save
            }
        }
    }
}
    public function getModifiersWithFlavors($itemId) {
    header('Content-Type: application/json');
    
    $query = "SELECT m.*, f.FlavorName 
              FROM modifiers m 
              LEFT JOIN flavors f ON m.FlavorID = f.FlavorID 
              WHERE m.ItemID = ? AND m.IsAvailable = 1";
    
    $stmt = $this->db->prepare($query);
    $stmt->execute([$itemId]);
    $modifiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'modifiers' => $modifiers]);
}
    private function removeItemModifiers($itemId) {
        $stmt = $this->db->prepare("DELETE FROM modifiers WHERE ItemID = ?");
        $stmt->execute([$itemId]);
    }
    
    // Flavor Management Methods
    public function getFlavors() {
        header('Content-Type: application/json');
        $flavors = $this->flavor->getAll();
        echo json_encode(['success' => true, 'flavors' => $flavors]);
    }

    public function getMenuItem($itemId) {
        header('Content-Type: application/json');

        if (!$itemId || (int)$itemId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
            return;
        }

        $item = $this->menuItem->getById((int)$itemId);
        if ($item) {
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
    }
    
    public function getItemFlavors($itemId) {
        header('Content-Type: application/json');
        $flavors = $this->flavor->getItemFlavors($itemId);
        echo json_encode(['success' => true, 'flavors' => $flavors]);
    }
    
    private function addItemFlavors($itemId, $flavors) {
        // Remove existing flavors
        $stmt = $this->db->prepare("DELETE FROM item_flavors WHERE ItemID = ?");
        $stmt->execute([$itemId]);
        
        // Add new flavors
        foreach($flavors as $flavorData) {
            $flavorId = $flavorData['flavor_id'];
            $isDefault = $flavorData['is_default'] ?? 0;
            $this->flavor->addItemFlavor($itemId, $flavorId, $isDefault);
        }
    }
    
    public function addFlavor() {
        // Turn off error display and capture errors
        ini_set('display_errors', 0);
        error_reporting(E_ALL);
        
        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start fresh output buffer
        ob_start();
        
        // Set JSON header
        header('Content-Type: application/json');
        
        try {
            if($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid request method.'
                ]);
                return;
            }
            
            // Validate required field
            if (!isset($_POST['flavor_name']) || empty(trim($_POST['flavor_name']))) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Flavor name is required.'
                ]);
                return;
            }
            
            $this->flavor->FlavorName = trim($_POST['flavor_name']);
            $this->flavor->PriceAdjustment = 0; // No price adjustment
            $this->flavor->IsAvailable = isset($_POST['is_available']) ? 1 : 0;
            
            if($this->flavor->create()) {
                echo json_encode([
                    'success' => true,
                    'flavor_id' => $this->db->lastInsertId(),
                    'message' => 'Flavor added successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add flavor.'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        
        // Clean output buffer and send response
        ob_end_flush();
        exit;
    }
    
    public function deleteFlavor() {
        if(isset($_GET['id'])) {
            $this->flavor->FlavorID = $_GET['id'];
            if($this->flavor->delete()) {
                $_SESSION['success'] = "Flavor deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete flavor.";
            }
        }
        header('Location: ../app.php?page=menu');
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch($action) {
            case 'add_category':
                $this->addCategory();
                break;
            case 'get_category':
                $this->getCategory();
                break;
            case 'update_category':
                $this->updateCategory();
                break;
            case 'delete_category':
                $this->deleteCategory();
                break;
            case 'add_menu_item':
                $this->addMenuItem();
                break;
            case 'update_menu_item':
                $this->updateMenuItem();
                break;
            case 'delete_menu_item':
                $this->deleteMenuItem();
                break;
            case 'toggle_availability':
                $this->toggleMenuItemAvailability();
                break;
            case 'add_modifier':
                $this->addModifier();
                break;
            case 'delete_modifier':
                $this->deleteModifier();
                break;
            case 'add_flavor':
                $this->addFlavor();
                break;
            case 'delete_flavor':
                $this->deleteFlavor();
                break;
            case 'get_flavors':
                $this->getFlavors();
                break;
            case 'get_item_flavors':
                $this->getItemFlavors($_GET['item_id'] ?? 0);
                break;
            case 'get_menu_item':
                $this->getMenuItem($_GET['item_id'] ?? 0);
                break;
            default:
                header('Location: ../app.php?page=menu');
                break;
        }
    }
    
    public function toggleMenuItemAvailability() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $itemId = $_POST['item_id'] ?? 0;
        $isAvailable = $_POST['is_available'] ?? 0;
        
        if ($itemId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
            return;
        }
        
        try {
            $this->menuItem->ItemID = $itemId;
            $this->menuItem->IsAvailable = $isAvailable;
            
            if ($this->menuItem->updateAvailability()) {
                echo json_encode(['success' => true, 'message' => 'Item availability updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update item availability']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

// Handle form submissions
if(isset($_POST['action'])) {
    if ($_POST['action'] === 'add_flavor') {
        // Direct call to addFlavor to avoid HTML output
        $controller = new MenuController();
        $controller->addFlavor();
    } else {
        $controller = new MenuController();
        $controller->handleRequest();
    }
} elseif (isset($_GET['action'])) {
    $controller = new MenuController();
    $controller->handleRequest();
} else {
    $controller = new MenuController();
    $controller->index();
}
?>
