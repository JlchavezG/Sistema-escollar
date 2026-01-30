<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

// Verificar si el usuario actual es sistemas
$is_sistemas = $auth->isSistemas();

$db = new Database();
$message = '';
$message_type = '';

// Modo edición
$edit_mode = false;
$notificacion_editar = null;

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $notificacion_id = intval($_GET['edit']);

    $db->query("SELECT n.*, u.tipo as remitente_tipo 
                FROM notificaciones n
                INNER JOIN usuarios u ON n.remitente_id = u.id
                WHERE n.id = :id 
                AND n.eliminado = FALSE 
                AND n.leido = FALSE
                AND (n.remitente_id = :user_id OR :is_sistemas = 1)");
    $db->bind(':id', $notificacion_id);
    $db->bind(':user_id', $_SESSION['user_id']);
    $db->bind(':is_sistemas', $is_sistemas ? 1 : 0);
    $notificacion_editar = $db->single();

    if ($notificacion_editar) {
        $edit_mode = true;
        // Pre-cargar datos en variables para el formulario
        $destinatario_id_pre = $notificacion_editar['destinatario_id'];
        $asunto_pre = $notificacion_editar['asunto'];
        $mensaje_pre = $notificacion_editar['mensaje'];
        $tipo_pre = $notificacion_editar['tipo'];
    } else {
        $message = 'No se puede editar esta notificación. Puede que ya haya sido leída o no tengas permisos.';
        $message_type = 'warning';
    }
}

// Modificar el handler de POST para soportar edición
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_notificacion'])) {
    $destinatario_id = intval($_POST['destinatario_id']);
    $asunto = sanitizeInput($_POST['asunto']);
    $mensaje = sanitizeInput($_POST['mensaje']);
    $tipo = sanitizeInput($_POST['tipo']);

    try {
        // Verificar que el destinatario sea profesor
        $db->query("SELECT id, nombre, apellido_paterno FROM usuarios WHERE id = :id AND tipo = 'profesor'");
        $db->bind(':id', $destinatario_id);
        $destinatario = $db->single();

        if (!$destinatario) {
            throw new Exception('El destinatario no es un profesor válido');
        }

        if ($edit_mode && isset($_POST['notificacion_id'])) {
            // Actualizar notificación existente
            $notificacion_id = intval($_POST['notificacion_id']);

            $db->query("UPDATE notificaciones 
                        SET asunto = :asunto, 
                            mensaje = :mensaje, 
                            tipo = :tipo, 
                            editado = TRUE, 
                            fecha_edicion = NOW(), 
                            editado_por = :user_id,
                            version = version + 1
                        WHERE id = :id 
                        AND remitente_id = :user_id 
                        AND leido = FALSE 
                        AND eliminado = FALSE");
            $db->bind(':id', $notificacion_id);
            $db->bind(':asunto', $asunto);
            $db->bind(':mensaje', $mensaje);
            $db->bind(':tipo', $tipo);
            $db->bind(':user_id', $_SESSION['user_id']);

            if ($db->execute() && $db->rowCount() > 0) {
                $message = "Notificación actualizada exitosamente";
                $message_type = 'success';
            } else {
                throw new Exception('No se pudo actualizar la notificación. Puede que ya haya sido leída.');
            }
        } else {
            // Insertar nueva notificación
            $db->query("INSERT INTO notificaciones (remitente_id, destinatario_id, asunto, mensaje, tipo, fecha_envio) 
                        VALUES (:remitente_id, :destinatario_id, :asunto, :mensaje, :tipo, NOW())");
            $db->bind(':remitente_id', $_SESSION['user_id']);
            $db->bind(':destinatario_id', $destinatario_id);
            $db->bind(':asunto', $asunto);
            $db->bind(':mensaje', $mensaje);
            $db->bind(':tipo', $tipo);

            if ($db->execute()) {
                $message = "Notificación enviada exitosamente a {$destinatario['nombre']} {$destinatario['apellido_paterno']}";
                $message_type = 'success';
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
// Obtener profesores para el dropdown
$db->query("SELECT id, nombre, apellido_paterno, apellido_materno, email FROM usuarios WHERE tipo = 'profesor' ORDER BY apellido_paterno");
$profesores = $db->resultSet();

// Manejar envío de notificación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_notificacion'])) {
    $destinatario_id = intval($_POST['destinatario_id']);
    $asunto = sanitizeInput($_POST['asunto']);
    $mensaje = sanitizeInput($_POST['mensaje']);
    $tipo = sanitizeInput($_POST['tipo']);

    try {
        // Verificar que el destinatario sea profesor
        $db->query("SELECT id, nombre, apellido_paterno FROM usuarios WHERE id = :id AND tipo = 'profesor'");
        $db->bind(':id', $destinatario_id);
        $destinatario = $db->single();

        if (!$destinatario) {
            throw new Exception('El destinatario no es un profesor válido');
        }

        // Insertar notificación
        $db->query("INSERT INTO notificaciones (remitente_id, destinatario_id, asunto, mensaje, tipo, fecha_envio) 
                    VALUES (:remitente_id, :destinatario_id, :asunto, :mensaje, :tipo, NOW())");
        $db->bind(':remitente_id', $_SESSION['user_id']);
        $db->bind(':destinatario_id', $destinatario_id);
        $db->bind(':asunto', $asunto);
        $db->bind(':mensaje', $mensaje);
        $db->bind(':tipo', $tipo);

        if ($db->execute()) {
            $message = "Notificación enviada exitosamente a {$destinatario['nombre']} {$destinatario['apellido_paterno']}";
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Notificación - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/plus.css">
    <script src="../assets/js/sidebar-toggle.js" defer></script>
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
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="notification-form">
                <!-- Header mejorado SIN círculo problemático -->
                <div class="form-header">
                    <i class="fas fa-bell"></i>
                    <h2>Enviar Notificación a Profesor</h2>
                    <p class="mb-0">Comunica información importante directamente al panel de tus profesores</p>
                </div>

                <form method="POST" action="">
                    <?php if ($edit_mode && $notificacion_editar): ?>
                        <input type="hidden" name="notificacion_id" value="<?php echo $notificacion_editar['id']; ?>">
                    <?php endif; ?>

                    <!-- ... resto del formulario ... -->

                    <div class="form-section">
                        <h5 class="mb-3"><i class="fas fa-user-graduate me-2"></i>Destinatario</h5>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Profesor <span class="text-danger">*</span></label>
                            <select class="form-select" name="destinatario_id" required <?php echo $edit_mode ? 'disabled' : ''; ?>>
                                <option value="">Seleccionar profesor...</option>
                                <?php foreach ($profesores as $profesor): ?>
                                    <option value="<?php echo $profesor['id']; ?>"
                                        <?php echo ($edit_mode && $profesor['id'] == $destinatario_id_pre) ? 'selected' : ''; ?>
                                        <?php echo (!$edit_mode && $profesor['id'] == ($destinatario_id ?? '')) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($profesor['apellido_paterno'] . ' ' . $profesor['apellido_materno'] . ', ' . $profesor['nombre']); ?>
                                        (<?php echo htmlspecialchars($profesor['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="destinatario_id" value="<?php echo $destinatario_id_pre; ?>">
                                <small class="text-muted">No se puede cambiar el destinatario de una notificación editada</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-section">
                        <h5 class="mb-3"><i class="fas fa-envelope me-2"></i>Contenido</h5>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Asunto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="asunto"
                                value="<?php echo $edit_mode ? htmlspecialchars($asunto_pre) : (isset($asunto) ? htmlspecialchars($asunto) : ''); ?>"
                                placeholder="Ej: Recordatorio de entrega de calificaciones" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Mensaje <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="mensaje" rows="6" required><?php
                                                                                            echo $edit_mode ? htmlspecialchars($mensaje_pre) : (isset($mensaje) ? htmlspecialchars($mensaje) : '');
                                                                                            ?></textarea>
                            <small class="text-muted">Máximo 1000 caracteres. Sé claro y conciso.</small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h5 class="mb-3"><i class="fas fa-flag me-2"></i>Prioridad</h5>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Notificación <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo" required>
                                <option value="informativa" <?php echo ($edit_mode && $tipo_pre == 'informativa') ? 'selected' : ''; ?>>Informativa - Comunicados generales</option>
                                <option value="recordatorio" <?php echo ($edit_mode && $tipo_pre == 'recordatorio') ? 'selected' : ''; ?>>Recordatorio - Fechas importantes</option>
                                <option value="importante" <?php echo ($edit_mode && $tipo_pre == 'importante') ? 'selected' : ''; ?>>Importante - Requiere atención</option>
                                <option value="urgente" <?php echo ($edit_mode && $tipo_pre == 'urgente') ? 'selected' : ''; ?>>Urgente - Acción inmediata requerida</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="gestion_notificaciones.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <button type="submit" name="enviar_notificacion" class="btn btn-<?php echo $edit_mode ? 'warning' : 'primary'; ?> btn-lg">
                            <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'paper-plane'; ?> me-2"></i>
                            <?php echo $edit_mode ? 'Actualizar Notificación' : 'Enviar Notificación'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Card Mejorada -->
            <div class="card mt-4 border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Consejos para Notificaciones Efectivas</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3 mt-1">
                                    <div class="bg-light-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
                                        <i class="fas fa-check-circle text-primary fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-primary">Claridad y Concisión</h6>
                                    <p class="mb-0 text-muted">Sé directo y específico. Evita textos largos que puedan perder la atención del profesor.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3 mt-1">
                                    <div class="bg-light-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
                                        <i class="fas fa-clock text-primary fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-primary">Anticipación</h6>
                                    <p class="mb-0 text-muted">Envía recordatorios con suficiente tiempo para que los profesores puedan organizar sus actividades.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3 mt-1">
                                    <div class="bg-light-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
                                        <i class="fas fa-exclamation-triangle text-primary fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-primary">Prioridad Adecuada</h6>
                                    <p class="mb-0 text-muted">Reserva "Urgente" para situaciones críticas. El uso excesivo reduce su efectividad.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3 mt-1">
                                    <div class="bg-light-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
                                        <i class="fas fa-bell text-primary fa-lg"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-primary">Seguimiento</h6>
                                    <p class="mb-0 text-muted">Los profesores reciben notificaciones en tiempo real y pueden acceder al historial completo en su panel.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

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