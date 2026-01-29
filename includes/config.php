<?php
// ========================================
// CONFIGURACIÓN CENTRALIZADA - SISTEMA ESCOLAR
// ¡NO MODIFICAR SIN BACKUP!
// ========================================

// INICIAR SESIÓN SOLO UNA VEZ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CONSTANTES DE LA APLICACIÓN
define('APP_NAME', 'Sistema Escolar');
define('APP_VERSION', '2.0.0');
define('CYCLE_ACTUAL', '2025-2026');

// ========================================
// CONFIGURACIÓN DE BASE DE DATOS
// ¡VERIFICA QUE COINCIDA CON TU BASE DE DATOS!
// ========================================
define('DB_HOST', 'localhost');      // Host de MySQL (XAMPP: localhost)
define('DB_USER', 'root');           // Usuario de MySQL (XAMPP: root)
define('DB_PASS', '');               // Contraseña (XAMPP: vacío)
define('DB_NAME', 'sistema_escolar'); // ¡NOMBRE CORREGIDO! (no "escollar")

// RUTAS ABSOLUTAS
define('BASE_PATH', __DIR__);
define('INCLUDES_PATH', BASE_PATH);
define('ASSETS_PATH', dirname(BASE_PATH) . '/assets');

// INCLUSIÓN DE ARCHIVOS (¡NO CAMBIAR EL ORDEN!)
require_once __DIR__ . '/database.php';  // Requiere constantes DB_*
require_once __DIR__ . '/auth.php';      // Requiere Database
require_once __DIR__ . '/functions.php'; // Funciones auxiliares

// ZONA HORARIA
date_default_timezone_set('America/Mexico_City');

// MANEJO DE ERRORES (desactivar en producción)
if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>