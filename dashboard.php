<?php
require_once 'includes/config.php';
$auth = new Auth();
$auth->requireLogin();

// Redirigir según el tipo de usuario
if ($auth->isAdmin()) {
    header('Location: administrativos/dashboard.php');
} else {
    header('Location: profesores/dashboard.php');
}
exit();
?>