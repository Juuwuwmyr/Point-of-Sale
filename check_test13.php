<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=restaurantpos', 'root', '123456');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Checking item 'Test13'...\n";
    
    // Get the item details
    $stmt = $pdo->prepare("SELECT ItemID, ItemName FROM menuitems WHERE ItemName = ?");
    $stmt->execute(['Test13']);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo "Item 'Test13' not found!\n";
        exit;
    }
    
    echo "Item found: ID {$item['ItemID']} - {$item['ItemName']}\n\n";
    
    // Get flavors for this item
    $stmt = $pdo->prepare("
        SELECT f.FlavorID, f.FlavorName, f.PriceAdjustment, f.IsAvailable, ifl.IsDefault
        FROM flavors f
        JOIN item_flavors ifl ON f.FlavorID = ifl.FlavorID
        WHERE ifl.ItemID = ?
        ORDER BY f.FlavorName
    ");
    $stmt->execute([$item['ItemID']]);
    $flavors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Flavors for this item:\n";
    foreach ($flavors as $flavor) {
        echo "  FlavorID: {$flavor['FlavorID']} - {$flavor['FlavorName']} → PriceAdjustment: {$flavor['PriceAdjustment']} (Default: " . ($flavor['IsDefault'] ? 'Yes' : 'No') . ")\n";
    }
    
    echo "\n";
    
    // Get modifiers for this item
    $stmt = $pdo->prepare("
        SELECT ModifierID, Name, Price, FlavorID, AffectsPrice
        FROM modifiers
        WHERE ItemID = ?
        ORDER BY ModifierID
    ");
    $stmt->execute([$item['ItemID']]);
    $modifiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Modifiers for this item:\n";
    foreach ($modifiers as $modifier) {
        echo "  ModifierID: {$modifier['ModifierID']} - {$modifier['Name']} → Price: {$modifier['Price']}, FlavorID: " . ($modifier['FlavorID'] ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
