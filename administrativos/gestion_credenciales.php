<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin(); // Sistemas y Administrativos tienen acceso

// Verificar si el usuario actual es sistemas (para mostrar más opciones)
$is_sistemas = $auth->isSistemas();

$db = new Database();
$message = '';
$message_type = '';

// Manejar acciones CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'create' || $action == 'update') {
        $nombre = sanitizeInput($_POST['nombre']);
        $contenido_html = $_POST['contenido_html'];
        $css_personalizado = $_POST['css_personalizado'];
        
        try {
            if ($action == 'create') {
                $db->query("INSERT INTO plantillas_credenciales (nombre, contenido_html, css_personalizado, activa, created_by, created_at) 
                            VALUES (:nombre, :contenido_html, :css_personalizado, 1, :created_by, NOW())");
                $db->bind(':created_by', $_SESSION['user_id']);
            } else {
                $id = intval($_POST['id']);
                $db->query("UPDATE plantillas_credenciales SET nombre = :nombre, contenido_html = :contenido_html, 
                            css_personalizado = :css_personalizado WHERE id = :id");
                $db->bind(':id', $id);
            }
            
            $db->bind(':nombre', $nombre);
            $db->bind(':contenido_html', $contenido_html);
            $db->bind(':css_personalizado', $css_personalizado);
            
            if ($db->execute()) {
                $message = $action == 'create' ? 'Plantilla de credencial creada exitosamente' : 'Plantilla actualizada exitosamente';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        
        $db->query("DELETE FROM plantillas_credenciales WHERE id = :id");
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            $message = 'Plantilla eliminada exitosamente';
            $message_type = 'success';
        } else {
            $message = 'Error al eliminar plantilla';
            $message_type = 'danger';
        }
    } elseif ($action == 'toggle_active') {
        $id = intval($_POST['id']);
        
        $db->query("SELECT activa FROM plantillas_credenciales WHERE id = :id");
        $db->bind(':id', $id);
        $current = $db->single();
        
        $new_active = $current['activa'] == 1 ? 0 : 1;
        
        $db->query("UPDATE plantillas_credenciales SET activa = :activa WHERE id = :id");
        $db->bind(':activa', $new_active);
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            $message = 'Estado de plantilla actualizado';
            $message_type = 'success';
        }
    }
}

// Obtener todas las plantillas de credenciales
$db->query("SELECT pc.*, u.nombre as creador_nombre, u.apellido_paterno as creador_ap
            FROM plantillas_credenciales pc
            LEFT JOIN usuarios u ON pc.created_by = u.id
            ORDER BY pc.created_at DESC");
$plantillas = $db->resultSet();

// Obtener plantilla para editar
$edit_plantilla = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $db->query("SELECT * FROM plantillas_credenciales WHERE id = :id");
    $db->bind(':id', $id);
    $edit_plantilla = $db->single();
}

// Estadísticas - ¡CORREGIDO: Agregada consulta para total_alumnos!
$db->query("SELECT COUNT(*) as total FROM plantillas_credenciales");
$total_plantillas = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM plantillas_credenciales WHERE activa = 1");
$total_activas = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM alumnos"); // ✅ ¡CONSULTA AGREGADA!
$total_alumnos = $db->single()['total']; // ✅ ¡VARIABLE DEFINIDA!
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Credenciales - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
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
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="perfil.php">
                                    <i class="fas fa-user me-2"></i> Mi Perfil
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                </a></li>
                            </ul>
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
            
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-id-card me-3"></i>
                            Gestión de Credenciales Estudiantiles
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Diseña, gestiona e imprime credenciales para los estudiantes
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <!-- Botón para crear con asistente visual -->
                            <a href="crear_credencial_asistente.php" class="btn btn-success" title="Crear con Asistente Visual">
                                <i class="fas fa-magic me-1"></i>Crear con Asistente
                            </a>
                            
                            <!-- Botón para generar credenciales -->
                            <a href="generar_credenciales.php" class="btn btn-primary">
                                <i class="fas fa-print me-1"></i>Generar Credenciales
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics - ¡CORREGIDO: Ahora $total_alumnos está definido! -->
            <div class="stats-grid mb-4">
                <div class="stat-item">
                    <i class="fas fa-id-card fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $total_plantillas; ?></div>
                    <div class="stat-label">Plantillas Totales</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-toggle-on fa-2x text-success"></i>
                    <div class="stat-number"><?php echo $total_activas; ?></div>
                    <div class="stat-label">Plantillas Activas</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-users fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $total_alumnos; ?></div> <!-- ✅ ¡AHORA FUNCIONA! -->
                    <div class="stat-label">Alumnos Registrados</div>
                </div>
            </div>
            
            <!-- Plantillas List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Mis Plantillas de Credenciales</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Nombre</th>
                                    <th>Creador</th>
                                    <th>Fecha Creación</th>
                                    <th>Estado</th>
                                    <th style="width: 200px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($plantillas) > 0): ?>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($plantillas as $plantilla): ?>
                                        <tr>
                                            <td><?php echo $contador++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($plantilla['nombre']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($plantilla['created_by']): ?>
                                                    <small><?php echo htmlspecialchars($plantilla['creador_ap'] . ' ' . $plantilla['creador_nombre']); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Sistema</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($plantilla['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($plantilla['activa']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Activa
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times-circle me-1"></i>Inactiva
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="generar_credenciales.php?plantilla=<?php echo $plantilla['id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="Generar con esta plantilla">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="previewTemplate(<?php echo htmlspecialchars(json_encode($plantilla['contenido_html'])); ?>, <?php echo htmlspecialchars(json_encode($plantilla['css_personalizado'])); ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            onclick="toggleActive(<?php echo $plantilla['id']; ?>)">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="deleteTemplate(<?php echo $plantilla['id']; ?>, '<?php echo htmlspecialchars($plantilla['nombre']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-id-card fa-3x mb-3"></i>
                                                <p>No hay plantillas de credenciales creadas</p>
                                                <div class="d-flex justify-content-center gap-3 mt-3">
                                                    <a href="crear_credencial_asistente.php" class="btn btn-success">
                                                        <i class="fas fa-magic me-2"></i>Crear con Asistente Visual
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips para Credenciales Efectivas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-camera fa-2x text-info me-3 mt-1"></i>
                                <div>
                                    <h6>foto del Estudiante</h6>
                                    <p class="mb-0">Incluye espacio para foto tamaño pasaporte</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-barcode fa-2x text-info me-3 mt-1"></i>
                                <div>
                                    <h6>Código de Barras/QR</h6>
                                    <p class="mb-0">Agrega código único para validación rápida</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-shield-alt fa-2x text-info me-3 mt-1"></i>
                                <div>
                                    <h6>Elementos de Seguridad</h6>
                                    <p class="mb-0">Incluye holograma o marca de agua</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Preview Template -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Vista Previa de Credencial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="preview_modal_content" style="background: var(--color-white); padding: 20px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Preview template modal
        function previewTemplate(html, css) {
            var previewHtml = `
                <style>
                    ${css || ''}
                </style>
                <div style="max-width: 350px; margin: 0 auto;">
                    ${html.replace(/\{\{[^}]+\}\}/g, '<span style="color: var(--warning-color); background: rgba(243, 156, 18, 0.1); padding: 2px 6px; border-radius: 3px; font-weight: bold; display: inline-block; margin: 2px 0;">$&</span>')}
                </div>
            `;
            
            document.getElementById('preview_modal_content').innerHTML = previewHtml;
            var previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
        }
        
        // Toggle active status
        function toggleActive(id) {
            if (confirm('¿Deseas cambiar el estado de esta plantilla de credencial?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'toggle_active';
                
                const templateId = document.createElement('input');
                templateId.type = 'hidden';
                templateId.name = 'id';
                templateId.value = id;
                
                form.appendChild(action);
                form.appendChild(templateId);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Delete template with confirmation
        function deleteTemplate(id, nombre) {
            if (confirm('¿Estás seguro de que deseas eliminar la plantilla de credencial "' + nombre + '"?\nEsta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'delete';
                
                const templateId = document.createElement('input');
                templateId.type = 'hidden';
                templateId.name = 'id';
                templateId.value = id;
                
                form.appendChild(action);
                form.appendChild(templateId);
                document.body.appendChild(form);
                form.submit();
            }
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