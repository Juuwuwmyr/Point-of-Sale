<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthController.php';

class UserController {
    private $db;
    private $user;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        $this->auth = new AuthController();
        $this->auth->requireLogin();
        $this->auth->requireAdmin();
    }
    
    public function index() {
        $users = $this->user->getAll();
        $currentUser = $this->auth->getCurrentUser();
        
        include '../views/admin/users.php';
    }
    
    public function addUser() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->user->Username = $_POST['username'];
            $this->user->Password = $_POST['password'];
            $this->user->FullName = $_POST['fullname'];
            $this->user->RoleID = $_POST['role_id'];
            
            if($this->user->create()) {
                $_SESSION['success'] = "User added successfully";
            } else {
                $_SESSION['error'] = "Failed to add user";
            }
            
            header('Location: ../app.php?page=users');
            exit;
        }
    }
    
    public function updateUser() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->user->UserID = $_POST['user_id'];
            $this->user->Username = $_POST['username'];
            $this->user->FullName = $_POST['fullname'];
            $this->user->RoleID = $_POST['role_id'];
            $this->user->IsActive = isset($_POST['is_active']) ? 1 : 0;
            
            if(!empty($_POST['password'])) {
                $this->user->Password = $_POST['password'];
            }
            
            if($this->user->update()) {
                $_SESSION['success'] = "User updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update user";
            }
            
            header('Location: ../app.php?page=users');
            exit;
        }
    }
    
    public function deleteUser() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->user->UserID = $_POST['user_id'];
            
            if($this->user->delete()) {
                $_SESSION['success'] = "User deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete user";
            }
            
            header('Location: ../app.php?page=users');
            exit;
        }
    }
    
    public function toggleUserStatus() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $userId = $_POST['user_id'];
            $isActive = $_POST['is_active'];
            
            $query = "UPDATE users SET IsActive = :is_active WHERE UserID = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":is_active", $isActive);
            $stmt->bindParam(":user_id", $userId);
            
            if($stmt->execute()) {
                $_SESSION['success'] = "User status updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update user status";
            }
            
            header('Location: ../app.php?page=users');
            exit;
        }
    }
    
    public function getRoles() {
        $query = "SELECT * FROM roles ORDER BY RoleName";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle form submissions
if(isset($_POST['action'])) {
    $controller = new UserController();
    
    switch($_POST['action']) {
        case 'add_user':
            $controller->addUser();
            break;
        case 'update_user':
            $controller->updateUser();
            break;
        case 'delete_user':
            $controller->deleteUser();
            break;
        case 'toggle_status':
            $controller->toggleUserStatus();
            break;
    }
} else {
    $controller = new UserController();
    $controller->index();
}
?>
