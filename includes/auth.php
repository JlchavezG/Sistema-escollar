<?php
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($email, $password) {
        $this->db->query("SELECT * FROM usuarios WHERE email = :email");
        $this->db->bind(':email', $email);
        $row = $this->db->single();
        
        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['nombre'] . ' ' . $row['apellido_paterno'];
            $_SESSION['user_type'] = $row['tipo'];
            $_SESSION['user_email'] = $row['email'];
            return true;
        }
        
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'administrativo';
    }
    
    public function isProfesor() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'profesor';
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit();
        }
    }
    
    public function requireProfesor() {
        $this->requireLogin();
        if (!$this->isProfesor()) {
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit();
        }
    }
}
?>