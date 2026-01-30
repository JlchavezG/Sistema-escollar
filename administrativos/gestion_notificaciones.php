<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$is_sistemas = $auth->isSistemas();
$user_id = $_SESSION['user_id'];

$db = new Database();
$message = '';
$message_type = '';

// Manejar eliminación (soft delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_notificacion'])) {
    $notificacion_id = intval($_POST['id']);
    
    // Verificar que la notificación exista y sea del usuario actual (o sistemas)
    $db->query("SELECT n.*, u.tipo as remitente_tipo 
                FROM notificaciones n
                INNER JOIN usuarios u ON n.remitente_id = u.id
                WHERE n.id = :id");
    $db->bind(':id', $notificacion_id);
    $notificacion = $db->single();
    
    if (!$notificacion) {
        $message = 'Notificación no encontrada';
        $message_type = 'danger';
    } elseif ($notificacion['leido'] && !$is_sistemas) {
        $message = 'No puedes eliminar una notificación que ya fue leída por el profesor';
        $message_type = 'warning';
    } elseif ($notificacion['remitente_id'] != $user_id && !$is_sistemas) {
        $message = 'No tienes permisos para eliminar esta notificación';
        $message_type = 'danger';
    } else {
        // Soft delete
        $db->query("UPDATE notificaciones 
                    SET eliminado = TRUE, 
                        fecha_eliminacion = NOW(), 
                        eliminado_por = :user_id 
                    WHERE id = :id");
        $db->bind(':id', $notificacion_id);
        $db->bind(':user_id', $user_id);
        
        if ($db->execute()) {
            $message = 'Notificación eliminada exitosamente';
            $message_type = 'success';
        } else {
            $message = 'Error al eliminar la notificación';
            $message_type = 'danger';
        }
    }
}

// Manejar edición (solo si no ha sido leída)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_notificacion'])) {
    $notificacion_id = intval($_POST['id']);
    $asunto = sanitizeInput($_POST['asunto']);
    $mensaje = sanitizeInput($_POST['mensaje']);
    $tipo = sanitizeInput($_POST['tipo']);
    
    // Verificar que la notificación exista, no esté eliminada y no haya sido leída
    $db->query("SELECT * FROM notificaciones 
                WHERE id = :id 
                AND eliminado = FALSE 
                AND leido = FALSE 
                AND (remitente_id = :user_id OR :is_sistemas = 1)");
    $db->bind(':id', $notificacion_id);
    $db->bind(':user_id', $user_id);
    $db->bind(':is_sistemas', $is_sistemas ? 1 : 0);
    $notificacion = $db->single();
    
    if (!$notificacion) {
        $message = 'No se puede editar esta notificación (ya fue leída o no tienes permisos)';
        $message_type = 'warning';
    } else {
        // Actualizar notificación
        $db->query("UPDATE notificaciones 
                    SET asunto = :asunto, 
                        mensaje = :mensaje, 
                        tipo = :tipo, 
                        editado = TRUE, 
                        fecha_edicion = NOW(), 
                        editado_por = :user_id,
                        version = version + 1
                    WHERE id = :id");
        $db->bind(':id', $notificacion_id);
        $db->bind(':asunto', $asunto);
        $db->bind(':mensaje', $mensaje);
        $db->bind(':tipo', $tipo);
        $db->bind(':user_id', $user_id);
        
        if ($db->execute()) {
            $message = 'Notificación actualizada exitosamente';
            $message_type = 'success';
        } else {
            $message = 'Error al actualizar la notificación';
            $message_type = 'danger';
        }
    }
}

// Obtener notificaciones según rol
if ($is_sistemas) {
    // Sistemas ve TODAS las notificaciones
    $db->query("SELECT n.*, 
                CONCAT(ur.nombre, ' ', ur.apellido_paterno) as remitente,
                CONCAT(ud.nombre, ' ', ud.apellido_paterno) as destinatario,
                ur.tipo as tipo_remitente
                FROM notificaciones n
                INNER JOIN usuarios ur ON n.remitente_id = ur.id
                INNER JOIN usuarios ud ON n.destinatario_id = ud.id
                WHERE n.eliminado = FALSE
                ORDER BY n.fecha_envio DESC");
    $notificaciones = $db->resultSet();
} else {
    // Administrativos solo ven las que ellos enviaron
    $db->query("SELECT n.*, 
                CONCAT(ur.nombre, ' ', ur.apellido_paterno) as remitente,
                CONCAT(ud.nombre, ' ', ud.apellido_paterno) as destinatario
                FROM notificaciones n
                INNER JOIN usuarios ur ON n.remitente_id = ur.id
                INNER JOIN usuarios ud ON n.destinatario_id = ud.id
                WHERE n.remitente_id = :user_id 
                AND n.eliminado = FALSE
                ORDER BY n.fecha_envio DESC");
    $db->bind(':user_id', $user_id);
    $notificaciones = $db->resultSet();
}

// Estadísticas
$db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN leido = TRUE THEN 1 ELSE 0 END) as leidas,
    SUM(CASE WHEN leido = FALSE THEN 1 ELSE 0 END) as no_leidas,
    SUM(CASE WHEN editado = TRUE THEN 1 ELSE 0 END) as editadas
    FROM notificaciones 
    WHERE eliminado = FALSE 
    " . ($is_sistemas ? "" : "AND remitente_id = :user_id"));
if (!$is_sistemas) {
    $db->bind(':user_id', $user_id);
}
$stats = $db->single();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Notificaciones - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="../assets/js/sidebar-toggle.js" defer></script>
    <style>
        .notification-card {
            border-left: 4px solid var(--primary-color);
            transition: all var(--transition-fast);
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .notification-card:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-lg);
        }
        
        .notification-card.leida {
            border-left-color: var(--success-color);
            background: rgba(39, 174, 96, 0.05);
        }
        
        .notification-card.no-leida {
            border-left-color: var(--warning-color);
            background: rgba(243, 156, 18, 0.08);
            border-left-width: 6px;
        }
        
        .notification-card.editada {
            border-left-color: var(--info-color);
            background: rgba(52, 152, 219, 0.05);
        }
        
        .notification-card.eliminada {
            opacity: 0.6;
            border-left-color: var(--danger-color);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .notification-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-full);
            font-weight: var(--font-weight-bold);
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .badge-informativa { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); color: #1565c0; border: 1px solid #90caf9; }
        .badge-urgente { background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); color: #b71c1c; border: 1px solid #ef9a9a; }
        .badge-recordatorio { background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%); color: #5d4037; border: 1px solid #ffd54f; }
        .badge-importante { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #1b5e20; border: 1px solid #a5d6a7; }
        
        .notification-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.85rem;
            color: var(--color-text-secondary);
            margin: 0.5rem 0;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .action-btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: var(--border-radius-md);
            transition: all var(--transition-fast);
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .action-view {
            background: var(--primary-color);
            color: white;
        }
        
        .action-edit {
            background: var(--warning-color);
            color: white;
        }
        
        .action-delete {
            background: var(--danger-color);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--color-text-secondary);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.6;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
        }
        
        .filter-bar {
            background: var(--color-white);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-md);
        }
        
        .version-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: var(--border-radius-full);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebarAdmin.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <div class="container-fluid p-4">
            <!-- Mensajes -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'exclamation-triangle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-bell me-3"></i>
                            Gestión de Notificaciones
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Administra, edita y monitorea todas las notificaciones enviadas a profesores
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="enviar_notificacion.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Nueva Notificación
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-item">
                    <i class="fas fa-envelope fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Enviadas</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-eye fa-2x text-success"></i>
                    <div class="stat-number"><?php echo $stats['leidas'] ?? 0; ?></div>
                    <div class="stat-label">Leídas</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-eye-slash fa-2x text-warning"></i>
                    <div class="stat-number"><?php echo $stats['no_leidas'] ?? 0; ?></div>
                    <div class="stat-label">No Leídas</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-edit fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $stats['editadas'] ?? 0; ?></div>
                    <div class="stat-label">Editadas</div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="?filtro=todas" class="btn btn-sm btn-outline-primary <?php echo !isset($_GET['filtro']) || $_GET['filtro'] == 'todas' ? 'active' : ''; ?>">
                                <i class="fas fa-list me-1"></i>Todas
                            </a>
                            <a href="?filtro=no-leidas" class="btn btn-sm btn-outline-warning <?php echo isset($_GET['filtro']) && $_GET['filtro'] == 'no-leidas' ? 'active' : ''; ?>">
                                <i class="fas fa-eye-slash me-1"></i>No Leídas
                            </a>
                            <a href="?filtro=leidas" class="btn btn-sm btn-outline-success <?php echo isset($_GET['filtro']) && $_GET['filtro'] == 'leidas' ? 'active' : ''; ?>">
                                <i class="fas fa-eye me-1"></i>Leídas
                            </a>
                            <a href="?filtro=editadas" class="btn btn-sm btn-outline-info <?php echo isset($_GET['filtro']) && $_GET['filtro'] == 'editadas' ? 'active' : ''; ?>">
                                <i class="fas fa-edit me-1"></i>Editadas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notifications List -->
            <?php if (count($notificaciones) > 0): ?>
                <div class="row">
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <?php
                        $clases = 'card notification-card';
                        if ($notificacion['leido']) $clases .= ' leida';
                        else $clases .= ' no-leida';
                        if ($notificacion['editado']) $clases .= ' editada';
                        if ($notificacion['eliminado']) $clases .= ' eliminada';
                        ?>
                        
                        <div class="col-lg-6 mb-3">
                            <div class="<?php echo $clases; ?>">
                                <?php if ($notificacion['version'] > 1): ?>
                                    <div class="version-tag">
                                        <i class="fas fa-code-branch me-1"></i>v<?php echo $notificacion['version']; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="notification-header">
                                        <div>
                                            <span class="notification-badge badge-<?php echo $notificacion['tipo']; ?>">
                                                <i class="fas fa-<?php 
                                                    echo $notificacion['tipo'] == 'urgente' ? 'exclamation-triangle' : 
                                                    ($notificacion['tipo'] == 'recordatorio' ? 'clock' : 
                                                    ($notificacion['tipo'] == 'importante' ? 'star' : 'info-circle')); 
                                                ?>"></i>
                                                <?php echo ucfirst($notificacion['tipo']); ?>
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($notificacion['fecha_envio'])); ?>
                                        </small>
                                    </div>
                                    
                                    <h5 class="card-title mb-2"><?php echo htmlspecialchars($notificacion['asunto']); ?></h5>
                                    
                                    <div class="notification-meta">
                                        <div>
                                            <i class="fas fa-paper-plane me-1 text-primary"></i>
                                            <strong>De:</strong> <?php echo htmlspecialchars($notificacion['remitente']); ?>
                                            <?php if ($is_sistemas && $notificacion['tipo_remitente'] ?? '' == 'administrativo'): ?>
                                                <span class="badge bg-primary ms-1">Admin</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-user-graduate me-1 text-success"></i>
                                            <strong>Para:</strong> <?php echo htmlspecialchars($notificacion['destinatario']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="card-text mb-3" style="max-height: 80px; overflow: hidden;">
                                        <?php echo nl2br(htmlspecialchars(substr($notificacion['mensaje'], 0, 150))); ?>
                                        <?php if (strlen($notificacion['mensaje']) > 150): ?>...<?php endif; ?>
                                    </div>
                                    
                                    <div class="notification-actions">
                                        <!-- Ver detalles (modal) -->
                                        <button class="btn action-btn action-view" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $notificacion['id']; ?>">
                                            <i class="fas fa-eye me-1"></i>Ver
                                        </button>
                                        
                                        <!-- Editar (solo si no leída) -->
                                        <?php if (!$notificacion['leido'] && ($notificacion['remitente_id'] == $user_id || $is_sistemas)): ?>
                                            <a href="enviar_notificacion.php?edit=<?php echo $notificacion['id']; ?>" class="btn action-btn action-edit">
                                                <i class="fas fa-edit me-1"></i>Editar
                                            </a>
                                        <?php endif; ?>
                                        
                                        <!-- Eliminar (con confirmación) -->
                                        <?php if (($notificacion['remitente_id'] == $user_id || $is_sistemas) && !$notificacion['eliminado']): ?>
                                            <button class="btn action-btn action-delete" onclick="confirmDelete(<?php echo $notificacion['id']; ?>, '<?php echo htmlspecialchars($notificacion['asunto']); ?>')">
                                                <i class="fas fa-trash me-1"></i>Eliminar
                                            </button>
                                        <?php endif; ?>
                                        
                                        <!-- Estado -->
                                        <span class="badge bg-<?php echo $notificacion['leido'] ? 'success' : 'warning'; ?> ms-auto">
                                            <i class="fas fa-<?php echo $notificacion['leido'] ? 'check-circle' : 'clock'; ?> me-1"></i>
                                            <?php echo $notificacion['leido'] ? 'Leída' : 'No leída'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal: Ver Detalles -->
                        <div class="modal fade" id="viewModal<?php echo $notificacion['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="fas fa-envelope-open-text me-2"></i>
                                            <?php echo htmlspecialchars($notificacion['asunto']); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div class="d-flex justify-content-between mb-2">
                                                <strong><i class="fas fa-paper-plane me-1"></i>Remitente:</strong>
                                                <span><?php echo htmlspecialchars($notificacion['remitente']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <strong><i class="fas fa-user-graduate me-1"></i>Destinatario:</strong>
                                                <span><?php echo htmlspecialchars($notificacion['destinatario']); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <strong><i class="fas fa-flag me-1"></i>Prioridad:</strong>
                                                <span>
                                                    <span class="notification-badge badge-<?php echo $notificacion['tipo']; ?>">
                                                        <i class="fas fa-<?php 
                                                            echo $notificacion['tipo'] == 'urgente' ? 'exclamation-triangle' : 
                                                            ($notificacion['tipo'] == 'recordatorio' ? 'clock' : 
                                                            ($notificacion['tipo'] == 'importante' ? 'star' : 'info-circle')); 
                                                        ?>"></i>
                                                        <?php echo ucfirst($notificacion['tipo']); ?>
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <strong><i class="fas fa-clock me-1"></i>Enviada:</strong>
                                                <span><?php echo date('d/m/Y H:i', strtotime($notificacion['fecha_envio'])); ?></span>
                                            </div>
                                            <?php if ($notificacion['leido'] && $notificacion['fecha_lectura']): ?>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <strong><i class="fas fa-eye me-1"></i>Leída:</strong>
                                                    <span><?php echo date('d/m/Y H:i', strtotime($notificacion['fecha_lectura'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($notificacion['editado'] && $notificacion['fecha_edicion']): ?>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <strong><i class="fas fa-edit me-1"></i>Editada:</strong>
                                                    <span><?php echo date('d/m/Y H:i', strtotime($notificacion['fecha_edicion'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h6 class="mb-3"><i class="fas fa-comment me-2"></i>Mensaje Completo:</h6>
                                        <div class="border p-3 rounded" style="background: #f8f9fa;">
                                            <?php echo nl2br(htmlspecialchars($notificacion['mensaje'])); ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="fas fa-times me-1"></i>Cerrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No hay notificaciones</h3>
                    <p class="lead">Aún no has enviado ninguna notificación a los profesores</p>
                    <a href="enviar_notificacion.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i>Enviar Primera Notificación
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar la notificación:</p>
                    <h5 class="text-danger" id="notificacionTitulo"></h5>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Atención:</strong> 
                        <?php if ($is_sistemas): ?>
                            Esta acción eliminará permanentemente la notificación. Si ya fue leída por el profesor, ellos ya vieron el contenido original.
                        <?php else: ?>
                            Solo puedes eliminar notificaciones que <strong>aún no han sido leídas</strong> por el profesor. Si ya fue leída, no podrás eliminarla.
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <form method="POST" action="" id="deleteForm">
                        <input type="hidden" name="id" id="deleteId">
                        <input type="hidden" name="eliminar_notificacion" value="1">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Eliminar Permanentemente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        function confirmDelete(id, titulo) {
            document.getElementById('deleteId').value = id;
            document.getElementById('notificacionTitulo').textContent = titulo;
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        }
    </script>
    
    <!-- Dark Mode Toggle -->
    <button class="dark-mode-toggle" id="darkModeToggle" title="Cambiar modo">
        <i class="fas fa-moon"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const body = document.body;
            
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                body.setAttribute('data-theme', 'dark');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            
            darkModeToggle.addEventListener('click', function() {
                if (body.getAttribute('data-theme') === 'dark') {
                    body.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                    darkModeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                } else {
                    body.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                }
            });
        });
    </script>
</body>
</html>