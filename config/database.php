<?php
date_default_timezone_set('Asia/Manila');

class Database {
    private $host = "127.0.0.1";
    private $db_name = "restaurantpos";
    private $username = "root";
    private $password = "123456";
    private $charset = "utf8mb4";
    
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name ;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Set session timezone to match PHP timezone
            $now = new DateTime();
            $mins = $now->getOffset() / 60;
            $sgn = ($mins < 0 ? -1 : 1);
            $mins = abs($mins);
            $hrs = floor($mins / 60);
            $mins -= $hrs * 60;
            $offset = sprintf('%+d:%02d', $hrs * $sgn, $mins);
            $this->conn->exec("SET time_zone='$offset'");
        } catch(PDOException $exception) {
            // Log error silently instead of outputting to prevent headers issue
            error_log("Database connection error: " . $exception->getMessage());
            $this->conn = null;
        }
       //& "C:\xampp\php\php.exe" -S localhost:8000 -t "c:\Users\acgow\OneDrive\Desktop\Point of Sale Web" 
        return $this->conn;
    }
}
?>