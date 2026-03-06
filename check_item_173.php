<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=restaurantpos', 'root', '123456');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Checking item 173...\n";
    
    // Check item 173 details
    $stmt = $pdo->prepare("SELECT ItemID, ItemName FROM menuitems WHERE ItemID = ?");
    $stmt->execute([173]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Item: {$item['ItemID']} - {$item['ItemName']}\n";
    
    // Check flavors for item 173
    $stmt = $pdo->prepare("
        SELECT f.FlavorID, f.FlavorName 
        FROM item_flavors ifl 
        JOIN flavors f ON ifl.FlavorID = f.FlavorID 
        WHERE ifl.ItemID = ?
    ");
    $stmt->execute([173]);
    $flavors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Available flavors:\n";
    foreach ($flavors as $flavor) {
        echo "  FlavorID: {$flavor['FlavorID']} - {$flavor['FlavorName']}\n";
    }
    
    // Check modifiers for item 173
    $stmt = $pdo->prepare("SELECT ModifierID, Name, FlavorID FROM modifiers WHERE ItemID = ?");
    $stmt->execute([173]);
    $modifiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Modifiers:\n";
    foreach ($modifiers as $modifier) {
        echo "  ModifierID: {$modifier['ModifierID']} - {$modifier['Name']} → FlavorID: " . ($modifier['FlavorID'] ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
