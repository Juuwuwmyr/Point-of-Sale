<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/AuthController.php';

class OrderController {
    private $db;
    private $order;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->order = new Order($this->db);
        $this->auth = new AuthController();
    }
    
    public function updateOrderStatus() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $orderId = $_POST['order_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if ($orderId <= 0 || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }
        
        try {
            if ($this->order->updateOrderStatus($orderId, $status)) {
                echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

// Handle requests
if (isset($_POST['action'])) {
    $controller = new OrderController();
    
    switch ($_POST['action']) {
        case 'update_status':
            $controller->updateOrderStatus();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
}
?>
