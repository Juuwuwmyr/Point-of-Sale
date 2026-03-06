<?php

class MenuItem {
    private $conn;
    private $table_name = "menuitems";
    
    public $ItemID;
    public $ItemName;
    public $Description;
    public $CategoryID;
    public $Price;
    public $Cost;
    public $ImagePath;
    public $IsAvailable;
    public $IsFree;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (ItemName, Description, CategoryID, Price, Cost, ImagePath, IsAvailable, IsFree) 
                  VALUES (:itemname, :description, :categoryid, :price, :cost, :imagepath, :isavailable, :isfree)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":itemname", $this->ItemName);
        $stmt->bindParam(":description", $this->Description);
        $stmt->bindParam(":categoryid", $this->CategoryID);
        
        // Handle NULL values for Price and Cost
        $price = $this->Price === '' ? null : $this->Price;
        $cost = $this->Cost === '' ? null : $this->Cost;
        
        $stmt->bindParam(":price", $price);
        $stmt->bindParam(":cost", $cost);
        $stmt->bindParam(":imagepath", $this->ImagePath);
        $stmt->bindParam(":isavailable", $this->IsAvailable);
        $stmt->bindParam(":isfree", $this->IsFree);
        
        if($stmt->execute()) {
            $this->ItemID = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    public function getByCategory($categoryId, $availableOnly = false, $searchTerm = "") {
        if($categoryId == 0) {
            // Show all items including those with no category
            $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        } else {
            // Show items from specific category
            $query = "SELECT * FROM " . $this->table_name . " WHERE CategoryID = :categoryid";
        }
        
        if($availableOnly) {
            $query .= " AND IsAvailable = 1";
        }
        
        if(!empty($searchTerm)) {
            $query .= " AND ItemName LIKE :search";
        }
        
        $query .= " ORDER BY ItemName";
        
        $stmt = $this->conn->prepare($query);
        
        if($categoryId != 0) {
            $stmt->bindParam(":categoryid", $categoryId);
        }
        
        if(!empty($searchTerm)) {
            $searchParam = "%" . $searchTerm . "%";
            $stmt->bindParam(":search", $searchParam);
        }
        
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the results
        error_log("Query: " . $query);
        error_log("Results: " . json_encode($results));
        
        return $results;
    }
    
    public function getAll($availableOnly = false) {
        $query = "SELECT mi.*, c.CategoryName 
                  FROM " . $this->table_name . " mi
                  LEFT JOIN categories c ON mi.CategoryID = c.CategoryID";
        
        if($availableOnly) {
            $query .= " WHERE mi.IsAvailable = 1";
        }
        
        $query .= " ORDER BY mi.ItemName";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $query = "SELECT mi.*, c.CategoryName 
                  FROM " . $this->table_name . " mi
                  LEFT JOIN categories c ON mi.CategoryID = c.CategoryID
                  WHERE mi.ItemID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET ItemName = :itemname, Description = :description, CategoryID = :categoryid, 
                  Price = :price, Cost = :cost, ImagePath = :imagepath, IsAvailable = :isavailable, IsFree = :isfree
                  WHERE ItemID = :itemid";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":itemname", $this->ItemName);
        $stmt->bindParam(":description", $this->Description);
        $stmt->bindParam(":categoryid", $this->CategoryID);
        
        // Handle NULL values for Price and Cost
        $price = $this->Price === '' ? null : $this->Price;
        $cost = $this->Cost === '' ? null : $this->Cost;
        
        $stmt->bindParam(":price", $price);
        $stmt->bindParam(":cost", $cost);
        $stmt->bindParam(":imagepath", $this->ImagePath);
        $stmt->bindParam(":isavailable", $this->IsAvailable);
        $stmt->bindParam(":isfree", $this->IsFree);
        $stmt->bindParam(":itemid", $this->ItemID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE ItemID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->ItemID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function toggleAvailability() {
        $this->ItemID = $itemId;
        $query = "UPDATE " . $this->table_name . " 
                  SET IsAvailable = NOT IsAvailable 
                  WHERE ItemID = :itemid";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":itemid", $this->ItemID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function updateAvailability() {
        $query = "UPDATE " . $this->table_name . " 
                  SET IsAvailable = :isavailable 
                  WHERE ItemID = :itemid";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":itemid", $this->ItemID);
        $stmt->bindParam(":isavailable", $this->IsAvailable);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?>
