<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireProfesor();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $db = new Database();
    $db->query("UPDATE notificaciones SET leido = TRUE, fecha_lectura = NOW() WHERE id = :id AND destinatario_id = :destinatario_id");
    $db->bind(':id', intval($_POST['id']));
    $db->bind(':destinatario_id', $_SESSION['user_id']);
    
    if ($db->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al marcar notificación']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
}
?>