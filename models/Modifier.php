<?php

class Modifier {
    private $db;

    public $ModifierID;
    public $ItemID;
    public $Name;
    public $Price;
    public $AffectsPrice;
    public $FlavorID;  // Add this property
    public $IsAvailable;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get all modifiers for an item - UPDATED to include FlavorID
    public function getByItemID($itemID) {
        $query = "SELECT 
                    ModifierID, 
                    ItemID, 
                    Name, 
                    Price, 
                    AffectsPrice, 
                    FlavorID, 
                    IsAvailable 
                  FROM modifiers 
                  WHERE ItemID = :item_id AND IFNULL(IsAvailable, 1) = 1 
                  ORDER BY Name ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':item_id' => $itemID]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single modifier - UPDATED to include FlavorID
    public function getByID($modifierID) {
        $query = "SELECT 
                    ModifierID, 
                    ItemID, 
                    Name, 
                    Price, 
                    IFNULL(AffectsPrice, 1) AS AffectsPrice, 
                    FlavorID, 
                    IFNULL(IsAvailable, 1) AS IsAvailable 
                  FROM modifiers 
                  WHERE ModifierID = :modifier_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':modifier_id' => $modifierID]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create new modifier - UPDATED to include FlavorID
    public function create() {
        $query = "INSERT INTO modifiers (ItemID, Name, Price, AffectsPrice, FlavorID, IsAvailable) 
                  VALUES (:item_id, :name, :price, :affects_price, :flavor_id, 1)";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':item_id' => $this->ItemID,
            ':name' => $this->Name,
            ':price' => $this->Price,
            ':affects_price' => $this->AffectsPrice,
            ':flavor_id' => $this->FlavorID ?? null
        ]);
    }

    // Update modifier - UPDATED to include FlavorID
    public function update() {
        $query = "UPDATE modifiers 
                  SET Name = :name, 
                      Price = :price, 
                      AffectsPrice = :affects_price, 
                      FlavorID = :flavor_id,
                      IsAvailable = :is_available 
                  WHERE ModifierID = :modifier_id";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            ':name' => $this->Name,
            ':price' => $this->Price,
            ':affects_price' => $this->AffectsPrice,
            ':flavor_id' => $this->FlavorID ?? null,
            ':is_available' => $this->IsAvailable,
            ':modifier_id' => $this->ModifierID
        ]);
    }

    // Delete modifier
    public function delete($modifierID) {
        $query = "DELETE FROM modifiers WHERE ModifierID = :modifier_id";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([':modifier_id' => $modifierID]);
    }

    // Get all modifiers for an item (returns array for JSON)
    public function getByItemIDAsArray($itemID) {
        return $this->getByItemID($itemID);
    }
}
?>