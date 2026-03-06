<?php

class Category {
    private $conn;
    private $table_name = "categories";
    
    public $CategoryID;
    public $CategoryName;
    public $Description;
    public $ImagePath;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (CategoryName, Description, ImagePath) 
                  VALUES (:categoryname, :description, :imagepath)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":categoryname", $this->CategoryName);
        $stmt->bindParam(":description", $this->Description);
        $stmt->bindParam(":imagepath", $this->ImagePath);
        
        if($stmt->execute()) {
            $this->CategoryID = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY CategoryName";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE CategoryID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET CategoryName = :categoryname, Description = :description, ImagePath = :imagepath
                  WHERE CategoryID = :categoryid";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":categoryname", $this->CategoryName);
        $stmt->bindParam(":description", $this->Description);
        $stmt->bindParam(":imagepath", $this->ImagePath);
        $stmt->bindParam(":categoryid", $this->CategoryID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE CategoryID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->CategoryID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?>
