<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/MenuItem.php';
require_once __DIR__ . '/../models/Modifier.php';
require_once __DIR__ . '/../models/Flavor.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/AuthController.php';

class POSController {
    private $db;
    private $category;
    private $menuItem;
    private $modifier;
    private $flavor;
    private $order;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->category = new Category($this->db);
        $this->menuItem = new MenuItem($this->db);
        $this->modifier = new Modifier($this->db);
        $this->flavor = new Flavor($this->db);
        $this->order = new Order($this->db);
        $this->auth = new AuthController();
        // Remove requireLogin() for API calls - JavaScript can't authenticate
    }
    
    public function getAllCategories() {
        try {
            $query = "SELECT 
                        c.CategoryID, 
                        c.CategoryName, 
                        COUNT(mi.ItemID) as count 
                      FROM categories c
                      LEFT JOIN menuitems mi ON c.CategoryID = mi.CategoryID AND mi.IsAvailable = 1
                      GROUP BY c.CategoryID
                      ORDER BY c.CategoryName";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($results);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }    
    public function getModifiers() {
        $itemId = $_GET['item_id'] ?? 0;
        
        header('Content-Type: application/json');
        
        if($itemId <= 0) {
            echo json_encode(['error' => 'Invalid item ID']);
            return;
        }

        try {
            // Get modifiers with FlavorID
            $query = "SELECT 
                        ModifierID,
                        ItemID,
                        Name,
                        Price,
                        AffectsPrice,
                        FlavorID,
                        IsAvailable
                      FROM modifiers 
                      WHERE ItemID = ? AND IFNULL(IsAvailable, 1) = 1 
                      ORDER BY Name ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$itemId]);
            $modifiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get item details including IsFree flag
            $itemDetails = $this->menuItem->getById($itemId);
            $isFree = isset($itemDetails['IsFree']) ? $itemDetails['IsFree'] : 0;
            
            $response = [
                'success' => true,
                'modifiers' => $modifiers,
                'isFree' => $isFree
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            // Log the error
            error_log("Error in getModifiers: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getFlavors() {
        $itemId = $_GET['item_id'] ?? 0;
        
        header('Content-Type: application/json');
        
        if($itemId <= 0) {
            echo json_encode(['error' => 'Invalid item ID']);
            return;
        }

        try {
            // Get flavors for this item
            $flavors = $this->flavor->getItemFlavors($itemId);
            
            // Get default flavor for this item
            $defaultFlavor = $this->flavor->getItemDefaultFlavor($itemId);
            
            echo json_encode([
                'success' => true,
                'flavors' => $flavors,
                'defaultFlavor' => $defaultFlavor
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    public function index() {
        $categories = $this->category->getAll();
        $currentUser = $this->auth->getCurrentUser();
        
        include '../views/pos/index.php';
    }
    
    public function getMenuItems() {
        $categoryId = $_GET['category_id'] ?? 0;
        $search = $_GET['search'] ?? '';
        $isAdmin = $_SESSION['is_admin'] ?? false;
        
        $menuItems = $this->menuItem->getByCategory($categoryId, !$isAdmin, $search);
        
        // Enhance each item with modifier count
        foreach($menuItems as &$item) {
            $modifiers = $this->modifier->getByItemIDAsArray($item['ItemID']);
            $item['ModifierCount'] = count($modifiers);

            $flavors = $this->flavor->getItemFlavors($item['ItemID']);
            $item['FlavorCount'] = count($flavors);
        }
        
        header('Content-Type: application/json');
        echo json_encode($menuItems);
    }
    
    public function getAllMenuItems() {
        $search = $_GET['search'] ?? '';
        $isAdmin = $_SESSION['is_admin'] ?? false;
        
        $menuItems = $this->menuItem->getByCategory(0, !$isAdmin, $search);
        
        foreach($menuItems as &$item) {
            $modifiers = $this->modifier->getByItemIDAsArray($item['ItemID']);
            $item['ModifierCount'] = count($modifiers);
            $flavors = $this->flavor->getItemFlavors($item['ItemID']);
            $item['FlavorCount'] = count($flavors);
        }
        
        header('Content-Type: application/json');
        echo json_encode($menuItems);
    }
    
    public function createOrder() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');
            if ($this->db === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection error']);
                return;
            }
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'User not logged in']);
                return;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            try {
                // Consolidate Dine-In orders by same table (do not create a new order)
                $incomingType = isset($data['order_type_id']) ? (string)$data['order_type_id'] : '1';
                $incomingTable = $data['table_number'] ?? 'Take Out';
                if ($incomingType === '1') {
                    $existing = $this->order->getByTableNumber($incomingTable);
                    if ($existing && strtoupper($existing['Status']) !== 'PAID' && strtoupper($existing['Status']) !== 'DELETED') {
                        $this->order->OrderID = (int)$existing['OrderID'];
                        $this->order->OrderNumber = $existing['OrderNumber'];
                        foreach($data['items'] as $item) {
                            $itemNotes = $item['notes'] ?? '';
                            
                            // Pack flavors and modifiers as JSON if they exist
                            $hasFlavors = !empty($item['flavors']) || !empty($item['flavor']);
                            $hasModifiers = !empty($item['modifiers']);
                            
                            if ($hasFlavors || $hasModifiers) {
                                $notesObj = [];
                                if (!empty($itemNotes)) {
                                    $notesObj['text'] = $itemNotes;
                                }
                                if (!empty($item['flavor'])) {
                                    $notesObj['flavor'] = $item['flavor'];
                                }
                                if (!empty($item['flavors'])) {
                                    $notesObj['flavors'] = $item['flavors'];
                                }
                                if (!empty($item['modifiers'])) {
                                    $notesObj['modifiers'] = $item['modifiers'];
                                }
                                $itemNotes = json_encode($notesObj);
                            }
                            
                            $this->order->addOrderItem(
                                $item['item_id'],
                                $item['quantity'],
                                $item['unit_price'],
                                $itemNotes
                            );
                        }
                        $this->order->updateTotalAmount();
                        echo json_encode([
                            'success' => true,
                            'order_id' => $this->order->OrderID,
                            'order_number' => $this->order->OrderNumber
                        ]);
                        return;
                    }
                }
                $this->order->OrderTypeID = $data['order_type_id'];
                $this->order->UserID = $_SESSION['user_id'];
                $this->order->TableNumber = $data['table_number'] ?? 'Take Out';
                $this->order->CustomerName = $data['customer_name'] ?? 'Take Out';
                $this->order->Status = 'Pending';
                $this->order->TotalAmount = $data['total_amount'];
                $this->order->Notes = $data['notes'] ?? '';
                
                if($this->order->create()) {
                    foreach($data['items'] as $item) {
                        $itemNotes = $item['notes'] ?? '';
                        
                        // Pack flavors and modifiers as JSON if they exist
                        $hasFlavors = !empty($item['flavors']) || !empty($item['flavor']);
                        $hasModifiers = !empty($item['modifiers']);
                        
                        if ($hasFlavors || $hasModifiers) {
                            $notesObj = [];
                            if (!empty($itemNotes)) {
                                $notesObj['text'] = $itemNotes;
                            }
                            if (!empty($item['flavor'])) {
                                $notesObj['flavor'] = $item['flavor'];
                            }
                            if (!empty($item['flavors'])) {
                                $notesObj['flavors'] = $item['flavors'];
                            }
                            if (!empty($item['modifiers'])) {
                                $notesObj['modifiers'] = $item['modifiers'];
                            }
                            $itemNotes = json_encode($notesObj);
                        }
                        
                        $this->order->addOrderItem(
                            $item['item_id'],
                            $item['quantity'],
                            $item['unit_price'],
                            $itemNotes
                        );
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'order_id' => $this->order->OrderID,
                        'order_number' => $this->order->OrderNumber
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create order']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
            }
        }
    }
    
    public function getOrders() {
        $status = $_GET['status'] ?? '';
        $dateFilter = $_GET['date'] ?? date('Y-m-d');
        
        $orders = $this->order->getAll($status, $dateFilter);
        
        header('Content-Type: application/json');
        echo json_encode($orders);
    }
    
    public function archiveAndClearToday() {
        header('Content-Type: application/json');
        if (!($_SESSION['is_admin'] ?? false)) {
            echo json_encode(['success' => false, 'message' => 'Admin only']);
            return;
        }
        try {
            $date = $_GET['date'] ?? date('Y-m-d');
            $result = $this->order->archiveAndDeleteSalesForDate($date);
            echo json_encode(['success' => true, 'date' => $date, 'result' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Reset dashboard to zero: empty sales.json and mark all Paid orders as Deleted.
     * Admin only.
     */
    public function resetDashboard() {
        header('Content-Type: application/json');
        if (!($_SESSION['is_admin'] ?? false)) {
            echo json_encode(['success' => false, 'message' => 'Admin only']);
            return;
        }
        try {
            $salesFile = __DIR__ . '/../data/sales.json';
            $dataDir = dirname($salesFile);
            if (!is_dir($dataDir)) {
                @mkdir($dataDir, 0755, true);
            }
            file_put_contents($salesFile, "[]");
            $updated = $this->order->markAllPaidAsDeleted();
            echo json_encode([
                'success' => true,
                'message' => 'Dashboard reset to zero.',
                'orders_marked_deleted' => $updated,
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function getDashboardStats() {
        header('Content-Type: application/json');
        try {
            $today = date('Y-m-d');
            // Today: DB + data/sales.json (main source)
            $daily = $this->order->getDailySales($today);
            $totalOrders = isset($daily['total_orders']) ? (int)$daily['total_orders'] : 0;
            $totalSales = isset($daily['total_sales']) ? (float)$daily['total_sales'] : 0.0;
            // Week (last 7 days) and overall from DB + sales.json
            $week = $this->order->getWeekSales(7);
            $overallSales = $this->order->getOverallSales();

            $todayOrders = $this->order->getAll('', $today);
            $openOrders = 0;
            foreach ($todayOrders as $o) {
                $status = strtoupper($o['Status'] ?? '');
                if ($status !== 'PAID' && $status !== 'DELETED') {
                    $openOrders++;
                }
            }
            
            $salesHistory = $this->order->getSalesHistory(7);
            // Retrieving 999 to show all items instead of limiting to 5
            $topItems = $this->order->getMostPurchasedItems($today, 999);
            // Recent orders include Deleted so the list is not affected when user deletes from orders page
            $recentOrders = $this->order->getRecentOrdersForDashboard($today, 10);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'totals' => [
                        'total_orders' => $totalOrders,
                        'total_sales' => $totalSales,
                        'week_orders' => (int)($week['total_orders'] ?? 0),
                        'week_sales' => (float)($week['total_sales'] ?? 0),
                        'overall_sales' => $overallSales,
                        'open_orders' => $openOrders,
                    ],
                    'sales_history' => $salesHistory,
                    'top_items' => $topItems,
                    'recent_orders' => $recentOrders,
                ],
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error loading dashboard stats',
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    public function getOrderDetails() {
        $orderId = $_GET['order_id'] ?? 0;
        
        $order = $this->order->getById($orderId);
        $this->order->OrderID = $orderId;
        // Exclude cancelled items so they don't reappear after refresh
        $orderItems = $this->order->getOrderItems(true);
        
        header('Content-Type: application/json');
        echo json_encode([
            'order' => $order,
            'items' => $orderItems
        ]);
    }
    
    public function cancelOrderItem() {
        header('Content-Type: application/json');
        try {
            if($_SERVER['REQUEST_METHOD'] == 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $orderDetailId = $data['order_detail_id'];
                
                // First, check if Status column exists in orderdetails table
                $checkColumnQuery = "SHOW COLUMNS FROM orderdetails LIKE 'Status'";
                $stmt = $this->db->prepare($checkColumnQuery);
                $stmt->execute();
                $columnExists = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$columnExists) {
                    // Add Status column if it doesn't exist
                    $alterQuery = "ALTER TABLE orderdetails ADD COLUMN Status VARCHAR(20) DEFAULT 'Active'";
                    $this->db->exec($alterQuery);
                }
                
                // Check current quantity of the order detail
                $checkQtyQuery = "SELECT Quantity FROM orderdetails WHERE OrderDetailID = :orderDetailId";
                $qtyStmt = $this->db->prepare($checkQtyQuery);
                $qtyStmt->bindParam(":orderDetailId", $orderDetailId);
                $qtyStmt->execute();
                $itemData = $qtyStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($itemData) {
                    $currentQty = (int)$itemData['Quantity'];
                    $actionTaken = '';
                    
                    if ($currentQty > 1) {
                        // Reduce quantity by 1
                        $updateQuery = "UPDATE orderdetails SET Quantity = Quantity - 1 WHERE OrderDetailID = :orderDetailId";
                        $actionTaken = 'partial';
                    } else {
                        // Mark as cancelled completely
                        $updateQuery = "UPDATE orderdetails SET Status = 'Cancelled' WHERE OrderDetailID = :orderDetailId";
                        $actionTaken = 'full';
                    }
                    
                    $stmt = $this->db->prepare($updateQuery);
                    $stmt->bindParam(":orderDetailId", $orderDetailId);
                    
                    if ($stmt->execute()) {
                        // Recalculate order total
                        $this->recalculateOrderTotal($orderDetailId);
                        
                        echo json_encode([
                            'success' => true, 
                            'action' => $actionTaken,
                            'new_quantity' => $currentQty - 1,
                            'message' => 'Order item updated successfully'
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to cancel order item']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Order item not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    private function recalculateOrderTotal($orderDetailId) {
        // Get the OrderID from the order detail
        $query = "SELECT OrderID FROM orderdetails WHERE OrderDetailID = :orderDetailId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":orderDetailId", $orderDetailId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $orderId = $result['OrderID'];
            
            // Check if Status column exists before using it in WHERE clause
            $checkColumnQuery = "SHOW COLUMNS FROM orderdetails LIKE 'Status'";
            $stmt = $this->db->prepare($checkColumnQuery);
            $stmt->execute();
            $columnExists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($columnExists) {
                // Use Status column if it exists
                $query = "SELECT SUM(Quantity * UnitPrice) as total FROM orderdetails 
                          WHERE OrderID = :orderId AND Status != 'Cancelled'";
            } else {
                // Fallback: don't use Status column
                $query = "SELECT SUM(Quantity * UnitPrice) as total FROM orderdetails 
                          WHERE OrderID = :orderId";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":orderId", $orderId);
            $stmt->execute();
            $newTotal = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
            
            // Update the order total
            $query = "UPDATE orders SET TotalAmount = :total WHERE OrderID = :orderId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":total", $newTotal);
            $stmt->bindParam(":orderId", $orderId);
            $stmt->execute();
        }
    }
    
    public function updateOrderStatus() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $this->order->OrderID = $data['order_id'];
            
            if($this->order->updateStatus($data['status'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        }
    }
    
    public function deleteOrder() {
        header('Content-Type: application/json');
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order id']);
            return;
        }
        $order = $this->order->getById($orderId);
        $this->order->OrderID = $orderId;
        $items = $this->order->getOrderItems(true);
        $itemsPack = [];
        foreach ($items as $it) {
            $itemsPack[] = [
                'item_id' => (int)($it['ItemID'] ?? 0),
                'item_name' => $it['ItemName'] ?? '',
                'quantity' => (int)($it['Quantity'] ?? 0),
                'unit_price' => (float)($it['UnitPrice'] ?? 0),
                'notes' => $it['Notes'] ?? ''
            ];
        }
        $delRecord = [
            'order_id' => (int)($order['OrderID'] ?? $orderId),
            'order_number' => $order['OrderNumber'] ?? null,
            'order_date' => $order['OrderDate'] ?? date('Y-m-d H:i:s'),
            'table_number' => $order['TableNumber'] ?? '',
            'cashier' => $order['CashierName'] ?? '',
            'status' => 'Deleted',
            'total_amount' => (float)($order['TotalAmount'] ?? 0),
            'deleted_at' => date('Y-m-d H:i:s'),
            'items' => $itemsPack
        ];
        $deletedFile = __DIR__ . '/../data/deleted_orders.json';
        $existing = [];
        if (file_exists($deletedFile)) {
            $raw = file_get_contents($deletedFile);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $existing = $decoded;
        }
        $existing[] = $delRecord;
        file_put_contents($deletedFile, json_encode($existing, JSON_PRETTY_PRINT));

        $salesFile = __DIR__ . '/../data/sales.json';
        $dataDir = dirname($salesFile);
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
        }
        $salesExisting = [];
        if (file_exists($salesFile)) {
            $rawS = file_get_contents($salesFile);
            $decodedS = json_decode($rawS, true);
            if (is_array($decodedS)) $salesExisting = $decodedS;
        }
        $salesExisting[] = [
            'order_date'   => $delRecord['order_date'],
            'total_amount' => $delRecord['total_amount'],
            'items'        => $itemsPack,
        ];
        file_put_contents($salesFile, json_encode($salesExisting, JSON_PRETTY_PRINT));

        if ($this->order->updateStatus('Deleted')) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
        }
    }
}

if(isset($_GET['action'])) {
    $controller = new POSController();
    
    switch($_GET['action']) {
        case 'getMenuItems':
            $controller->getMenuItems();
            break;
        case 'getAllMenuItems':
            $controller->getAllMenuItems();
            break;
        case 'getModifiers':
            $controller->getModifiers();
            break;
        case 'getFlavors':
            $controller->getFlavors();
            break;
        case 'createOrder':
            $controller->createOrder();
            break;
        case 'getOrders':
            $controller->getOrders();
            break;
        case 'getOrderDetails':
            $controller->getOrderDetails();
            break;
        case 'getDashboardStats':
            $controller->getDashboardStats();
            break;
        case 'cancelOrderItem':
    $controller->cancelOrderItem();
    break;
case 'updateOrderStatus':
            $controller->updateOrderStatus();
            break;
        case 'deleteOrder':
            $controller->deleteOrder();
            break;
        case 'getAllCategories':
            $controller->getAllCategories();
            break;
        case 'archiveAndClearToday':
            $controller->archiveAndClearToday();
            break;
        case 'resetDashboard':
            $controller->resetDashboard();
            break;
        default:
            $controller->index();
            break;
    }
} else {
    $controller = new POSController();
    $controller->index();
}
?>
