<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=restaurantpos', 'root', '123456');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Fixing item 173 modifier...\n";
    
    // Update the modifier to have the correct FlavorID
    $stmt = $pdo->prepare("UPDATE modifiers SET FlavorID = ? WHERE ItemID = ? AND ModifierID = ?");
    $result = $stmt->execute([43, 173, 43]);
    
    echo "Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Verify the fix
    $stmt = $pdo->prepare("SELECT ModifierID, Name, FlavorID FROM modifiers WHERE ItemID = 173");
    $stmt->execute([173]);
    $modifier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Updated modifier: {$modifier['Name']} → FlavorID: " . ($modifier['FlavorID'] ?? 'NULL') . "\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
