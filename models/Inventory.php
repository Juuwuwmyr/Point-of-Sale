<?php

class Inventory {
    private $conn;
    private $table_name = "inventory";
    
    public $InventoryID;
    public $ItemName;
    public $Description;
    public $Quantity;
    public $Unit;
    public $ReorderLevel;
    public $CostPerUnit;
    public $Supplier;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (ItemName, Description, Quantity, Unit, ReorderLevel, CostPerUnit, Supplier) 
                  VALUES (:itemname, :description, :quantity, :unit, :reorderlevel, :costperunit, :supplier)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":itemname", $this->ItemName);
        $stmt->bindParam(":description", $this->Description);
        $stmt->bindParam(":quantity", $this->Quantity);
        $stmt->bindParam(":unit", $this->Unit);
        $stmt->bindParam(":reorderlevel", $this->ReorderLevel);
        $stmt->bindParam(":costperunit", $this->CostPerUnit);
        $stmt->bindParam(":supplier", $this->Supplier);
        
        if($stmt->execute()) {
            $this->InventoryID = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY ItemName";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getLowStock() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Quantity <= ReorderLevel 
                  ORDER BY ItemName";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE InventoryID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET ItemName = :itemname, Description = :description, Quantity = :quantity, 
                  Unit = :unit, ReorderLevel = :reorderlevel, CostPerUnit = :costperunit, Supplier = :supplier
                  WHERE InventoryID = :inventoryid";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":itemname", $this->ItemName);
        $stmt->bindParam(":description", $this->Description);
        $stmt->bindParam(":quantity", $this->Quantity);
        $stmt->bindParam(":unit", $this->Unit);
        $stmt->bindParam(":reorderlevel", $this->ReorderLevel);
        $stmt->bindParam(":costperunit", $this->CostPerUnit);
        $stmt->bindParam(":supplier", $this->Supplier);
        $stmt->bindParam(":inventoryid", $this->InventoryID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function updateQuantity($quantity) {
        $query = "UPDATE " . $this->table_name . " SET Quantity = :quantity WHERE InventoryID = :inventoryid";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quantity", $quantity);
        $stmt->bindParam(":inventoryid", $this->InventoryID);
        
        return $stmt->execute();
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE InventoryID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->InventoryID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?>
