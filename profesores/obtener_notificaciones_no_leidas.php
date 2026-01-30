<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireProfesor();

header('Content-Type: application/json');

$db = new Database();
$db->query("SELECT n.id, n.asunto, n.mensaje, n.tipo, n.fecha_envio, 
            CONCAT(u.nombre, ' ', u.apellido_paterno) as remitente
            FROM notificaciones n
            INNER JOIN usuarios u ON n.remitente_id = u.id
            WHERE n.destinatario_id = :destinatario_id 
            AND n.leido = FALSE
            AND n.fecha_envio > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY n.fecha_envio DESC
            LIMIT 5");
$db->bind(':destinatario_id', $_SESSION['user_id']);
$notificaciones = $db->resultSet();

echo json_encode([
    'success' => true,
    'notificaciones' => $notificaciones
]);
?>