<?php
require_once 'includes/config.php';
$auth = new Auth();
$auth->requireLogin(); // Verifica sesión (sin iniciarla)

// Redirección automática según rol
if ($_SESSION['user_role'] == 'profesor') {
    header('Location: profesores/dashboard.php');
    exit();
} else {
    // Tanto 'administrativo' como 'sistemas' van al panel de administrativos
    header('Location: administrativos/dashboard.php');
    exit();
}
?>