<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['nombre']) || !isset($data['contenido_html'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

try {
    $db = new Database();
    $db->query("INSERT INTO plantillas_boletas (nombre, contenido_html, css_personalizado, activa, created_by, created_at) 
                VALUES (:nombre, :contenido_html, :css_personalizado, :activa, :created_by, NOW())");
    
    $db->bind(':nombre', sanitizeInput($data['nombre']));
    $db->bind(':contenido_html', $data['contenido_html']);
    $db->bind(':css_personalizado', $data['css_personalizado'] ?? '');
    $db->bind(':activa', isset($data['activa']) ? (int)$data['activa'] : 0);
    $db->bind(':created_by', $_SESSION['user_id']);
    
    if ($db->execute()) {
        // Si se establece como activa, desactivar otras plantillas
        if (isset($data['activa']) && $data['activa'] == 1) {
            $db->query("UPDATE plantillas_boletas SET activa = 0 WHERE id != :id");
            $db->bind(':id', $db->lastInsertId());
            $db->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Plantilla creada exitosamente', 'id' => $db->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la plantilla']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>