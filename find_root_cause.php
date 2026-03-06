<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=restaurantpos', 'root', '123456');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Finding root cause - checking recent items...\n";
    
    // Get the most recent items that have both flavors and modifiers
    $stmt = $pdo->prepare("
        SELECT DISTINCT m.ItemID, mi.ItemName,
               COUNT(DISTINCT m.ModifierID) as modifier_count,
               COUNT(DISTINCT ifl.FlavorID) as flavor_count,
               COUNT(DISTINCT CASE WHEN m.FlavorID IS NULL THEN 1 END) as null_flavor_modifiers
        FROM modifiers m 
        JOIN menuitems mi ON m.ItemID = mi.ItemID 
        LEFT JOIN item_flavors ifl ON m.ItemID = ifl.ItemID
        WHERE m.ItemID >= 170
        GROUP BY m.ItemID, mi.ItemName
        HAVING modifier_count > 0 AND flavor_count > 0
        ORDER BY m.ItemID DESC
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent items with flavors and modifiers:\n";
    foreach ($items as $item) {
        echo "ItemID: {$item['ItemID']} - {$item['ItemName']}\n";
        echo "  Modifiers: {$item['modifier_count']}, Flavors: {$item['flavor_count']}\n";
        echo "  Modifiers with NULL FlavorID: {$item['null_flavor_modifiers']}\n";
        
        if ($item['null_flavor_modifiers'] > 0) {
            echo "  ❌ PROBLEM: Modifiers have NULL FlavorID\n";
            
            // Check specific modifiers for this item
            $stmt2 = $pdo->prepare("SELECT ModifierID, Name, FlavorID FROM modifiers WHERE ItemID = ? AND FlavorID IS NULL");
            $stmt2->execute([$item['ItemID']]);
            $problemModifiers = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($problemModifiers as $modifier) {
                echo "    - {$modifier['Name']} (ID: {$modifier['ModifierID']}) → FlavorID: NULL\n";
            }
        } else {
            echo "  ✅ OK: All modifiers have FlavorID\n";
        }
        echo "\n";
    }
    
    // Also check if there are any items with temp IDs in modifier names
    $stmt = $pdo->prepare("
        SELECT DISTINCT m.ItemID, mi.ItemName, COUNT(*) as temp_modifier_count
        FROM modifiers m 
        JOIN menuitems mi ON m.ItemID = mi.ItemID 
        WHERE m.Name LIKE '__FLAVORID:temp_%'
        AND m.ItemID >= 170
        GROUP BY m.ItemID, mi.ItemName
        ORDER BY m.ItemID DESC
    ");
    $stmt->execute();
    $tempItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($tempItems) > 0) {
        echo "Items with temp IDs in modifier names:\n";
        foreach ($tempItems as $item) {
            echo "ItemID: {$item['ItemID']} - {$item['ItemName']} → {$item['temp_modifier_count']} temp modifiers\n";
        }
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
