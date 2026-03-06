<?php

class Flavor {
    private $conn;
    private $table_name = "flavors";
    
    public $FlavorID;
    public $FlavorName;
    public $PriceAdjustment;
    public $IsAvailable;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (FlavorName, PriceAdjustment, IsAvailable) 
                  VALUES (:flavorname, :priceadjustment, :isavailable)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":flavorname", $this->FlavorName);
        $stmt->bindParam(":priceadjustment", $this->PriceAdjustment);
        $stmt->bindParam(":isavailable", $this->IsAvailable);
        
        if($stmt->execute()) {
            $this->FlavorID = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE IsAvailable = 1 ORDER BY FlavorName";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE FlavorID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET FlavorName = :flavorname, PriceAdjustment = :priceadjustment, IsAvailable = :isavailable
                  WHERE FlavorID = :flavorid";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":flavorname", $this->FlavorName);
        $stmt->bindParam(":priceadjustment", $this->PriceAdjustment);
        $stmt->bindParam(":isavailable", $this->IsAvailable);
        $stmt->bindParam(":flavorid", $this->FlavorID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE FlavorID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->FlavorID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function getItemFlavors($itemId) {
        $query = "SELECT f.*, ifl.IsDefault FROM " . $this->table_name . " f
                  INNER JOIN item_flavors ifl ON f.FlavorID = ifl.FlavorID
                  WHERE ifl.ItemID = :itemid AND f.IsAvailable = 1
                  ORDER BY f.FlavorName";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":itemid", $itemId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addItemFlavor($itemId, $flavorId, $isDefault = 0) {
        $query = "INSERT INTO item_flavors (ItemID, FlavorID, IsDefault) 
                  VALUES (:itemid, :flavorid, :isdefault)
                  ON DUPLICATE KEY UPDATE IsDefault = :isdefault";
        
        $stmt = $this->conn->prepare($query);
        
        // Create proper variables for bindParam (required for reference)
        $itemIdVar = $itemId;
        $flavorIdVar = $flavorId;
        $isDefaultVar = (int)$isDefault;
        
        $stmt->bindParam(":itemid", $itemIdVar);
        $stmt->bindParam(":flavorid", $flavorIdVar);
        $stmt->bindParam(":isdefault", $isDefaultVar);
        
        return $stmt->execute();
    }
    
    public function removeItemFlavor($itemId, $flavorId) {
        $query = "DELETE FROM item_flavors WHERE ItemID = :itemid AND FlavorID = :flavorid";
        
        $stmt = $this->conn->prepare($query);
        
        // Create proper variables for bindParam (required for reference)
        $itemIdVar = $itemId;
        $flavorIdVar = $flavorId;
        
        $stmt->bindParam(":itemid", $itemIdVar);
        $stmt->bindParam(":flavorid", $flavorIdVar);
        
        return $stmt->execute();
    }
    
    public function getItemDefaultFlavor($itemId) {
        $query = "SELECT f.* FROM " . $this->table_name . " f
                  INNER JOIN item_flavors ifl ON f.FlavorID = ifl.FlavorID
                  WHERE ifl.ItemID = :itemid AND ifl.IsDefault = 1
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":itemid", $itemId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
