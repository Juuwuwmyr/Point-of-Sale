<?php

class User {
    private $conn;
    private $table_name = "users";
    
    public $UserID;
    public $Username;
    public $Password;
    public $FullName;
    public $RoleID;
    public $IsActive;
    public $RoleName;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($username, $password) {
        if (!$this->conn) {
            return false;
        }
        $query = "SELECT u.*, r.RoleName 
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  WHERE u.Username = :username AND u.IsActive = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row && password_verify($password, $row['Password'])) {
            $this->UserID = $row['UserID'];
            $this->Username = $row['Username'];
            $this->FullName = $row['FullName'];
            $this->RoleID = $row['RoleID'];
            $this->IsActive = $row['IsActive'];
            $this->RoleName = $row['RoleName'];
            return true;
        }
        
        return false;
    }
    
    public function create() {
        if (!$this->conn) {
            return false;
        }
        $query = "INSERT INTO " . $this->table_name . " 
                  (Username, Password, FullName, RoleID, IsActive) 
                  VALUES (:username, :password, :fullname, :roleid, 1)";
        
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash($this->Password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":username", $this->Username);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":fullname", $this->FullName);
        $stmt->bindParam(":roleid", $this->RoleID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function getAll() {
        if (!$this->conn) {
            return [];
        }
        $query = "SELECT u.*, r.RoleName 
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  ORDER BY u.UserID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        if (!$this->conn) {
            return null;
        }
        $query = "SELECT u.*, r.RoleName 
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.RoleID = r.RoleID
                  WHERE u.UserID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update() {
        if (!$this->conn) {
            return false;
        }
        $query = "UPDATE " . $this->table_name . " 
                  SET Username = :username, FullName = :fullname, RoleID = :roleid, IsActive = :isactive";
        
        if(!empty($this->Password)) {
            $query .= ", Password = :password";
        }
        
        $query .= " WHERE UserID = :userid";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":username", $this->Username);
        $stmt->bindParam(":fullname", $this->FullName);
        $stmt->bindParam(":roleid", $this->RoleID);
        $stmt->bindParam(":isactive", $this->IsActive);
        $stmt->bindParam(":userid", $this->UserID);
        
        if(!empty($this->Password)) {
            $hashed_password = password_hash($this->Password, PASSWORD_DEFAULT);
            $stmt->bindParam(":password", $hashed_password);
        }
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function delete() {
        if (!$this->conn) {
            return false;
        }
        $query = "DELETE FROM " . $this->table_name . " WHERE UserID = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->UserID);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function isAdmin() {
        return $this->RoleName === "Admin";
    }
}
?>
