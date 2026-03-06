<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

session_start();

class AuthController {
    private $db;
    private $user;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }
    
    public function login() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($this->db === null) {
                $_SESSION['error'] = "Database connection failed. PDO MySQL driver not available.";
                header('Location: ../views/login.php');
                exit;
            }
            
            if(empty($username) || empty($password)) {
                $_SESSION['error'] = "Username and password are required";
                header('Location: ../views/login.php');
                exit;
            }
            
            if($this->user->login($username, $password)) {
                $_SESSION['user_id'] = $this->user->UserID;
                $_SESSION['username'] = $this->user->Username;
                $_SESSION['fullname'] = $this->user->FullName;
                $_SESSION['role'] = $this->user->RoleName;
                $_SESSION['role_id'] = $this->user->RoleID;
                $_SESSION['is_admin'] = (strtolower($this->user->RoleName ?? '') === 'admin');
                $_SESSION['is_manager'] = (strtolower($this->user->RoleName) === 'manager');
                $_SESSION['is_cashier'] = (strtolower($this->user->RoleName) === 'cashier');
                
                header('Location: ../app.php');
                exit;
            } else {
                $_SESSION['error'] = "Invalid username or password";
                header('Location: ../views/login.php');
                exit;
            }
        }
    }
    
    public function logout() {
        session_destroy();
        header('Location: ../views/login.php');
        exit;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if(!$this->isLoggedIn()) {
            header('Location: ../views/login.php');
            exit;
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if(!$_SESSION['is_admin']) {
            $_SESSION['error'] = "Access denied. Admin privileges required.";
            header('Location: ../app.php');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'fullname' => $_SESSION['fullname'] ?? 'User',
                'role' => $_SESSION['role'] ?? 'Cashier',
                'is_admin' => $_SESSION['is_admin'] ?? false,
                'is_manager' => $_SESSION['is_manager'] ?? false,
                'is_cashier' => $_SESSION['is_cashier'] ?? false
            ];
        }
        return null;
    }

    /** Admin: full access. Manager: POS + Orders only. Cashier: POS only. */
    public function canAccessDashboard() {
        return ($_SESSION['is_admin'] ?? false);
    }
    public function canAccessKitchen() {
        return ($_SESSION['is_admin'] ?? false);
    }
    public function canAccessUsers() {
        return $_SESSION['is_admin'] ?? false;
    }
    public function canAccessMenu() {
        return ($_SESSION['is_admin'] ?? false);
    }
    public function canAccessInventory() {
        return ($_SESSION['is_admin'] ?? false);
    }
    public function canAccessReports() {
        return ($_SESSION['is_admin'] ?? false);
    }
}

if(isset($_POST['action']) && $_POST['action'] == 'login') {
    $auth = new AuthController();
    $auth->login();
}

if(isset($_GET['action']) && $_GET['action'] == 'logout') {
    $auth = new AuthController();
    $auth->logout();
}
?>
