<?php
// Standalone flavor creation endpoint to avoid HTML output issues

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
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../models/Flavor.php';
    
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method.'
        ]);
        exit;
    }
    
    // Validate required field
    if (!isset($_POST['flavor_name']) || empty(trim($_POST['flavor_name']))) {
        echo json_encode([
            'success' => false,
            'message' => 'Flavor name is required.'
        ]);
        exit;
    }
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed.'
        ]);
        exit;
    }
    
    // Create flavor
    $flavor = new Flavor($db);
    $flavor->FlavorName = trim($_POST['flavor_name']);
    $flavor->PriceAdjustment = isset($_POST['price_adjustment']) ? floatval($_POST['price_adjustment']) : 0; // Use price from form
    $flavor->IsAvailable = isset($_POST['is_available']) ? 1 : 0;
    
    // DEBUG: Log what we're receiving
    error_log("DEBUG: Creating flavor - Name: " . $flavor->FlavorName . ", PriceAdj: " . $flavor->PriceAdjustment);
    error_log("DEBUG: POST data: " . json_encode($_POST));
    
    if($flavor->create()) {
        echo json_encode([
            'success' => true,
            'flavor_id' => $db->lastInsertId(),
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
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Clean output buffer and send response
ob_end_flush();
exit;
?>
