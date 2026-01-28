<?php
// Configuración del sistema
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_escolar');
define('APP_NAME', 'Sistema Escolar Integral');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/sistema-escolar/');
define('CYCLE_ACTUAL', '2025-2026');

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de errores (desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ RUTAS ABSOLUTAS CORREGIDAS
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
?>