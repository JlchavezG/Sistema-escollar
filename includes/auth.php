<?php
class Auth {
    // NOTA: session_start() se maneja ÚNICAMENTE en config.php
    // NOTA: Constantes DB_* se definen en config.php ANTES de incluir este archivo
    
    public function login($email, $password) {
        $db = new Database(); // Database ya tiene acceso a DB_HOST, etc.
        $db->query("SELECT * FROM usuarios WHERE email = :email");
        $db->bind(':email', $email);
        $user = $db->single();
        
        if ($user && password_verify($password, $user['password'])) {
            // Session ya está iniciada por config.php
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido_paterno'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['tipo']; // 'profesor', 'administrativo', o 'sistemas'
            $_SESSION['user_tipo'] = $user['tipo']; // Para compatibilidad
            
            // Redirección por rol
            if ($user['tipo'] == 'profesor') {
                header('Location: profesores/dashboard.php');
            } else {
                header('Location: administrativos/dashboard.php');
            }
            exit();
        }
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function logout() {
        // Session ya está iniciada por config.php
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Método para requerir login (GENÉRICO - sin verificar rol específico)
    public function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }
    }
    
    // Método actualizado: permite administrativos Y sistemas
    public function requireAdmin() {
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrativo', 'sistemas'])) {
            header('Location: login.php');
            exit();
        }
    }
    
    // Nuevo método: solo para rol sistemas
    public function requireSistemas() {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'sistemas') {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requireProfesor() {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'profesor') {
            header('Location: login.php');
            exit();
        }
    }
    
    // Verificar si es sistemas (para mostrar/ocultar elementos en UI)
    public function isSistemas() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'sistemas';
    }
}
?>