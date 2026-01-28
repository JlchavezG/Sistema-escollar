<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$message = '';
$message_type = '';

// Manejar acciones CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'create' || $action == 'update') {
            $profesor_id = intval($_POST['profesor_id']);
            $modulo_id = intval($_POST['modulo_id']);
            $grado = sanitizeInput($_POST['grado']);
            $grupo = sanitizeInput($_POST['grupo']);
            $ciclo_escolar = sanitizeInput($_POST['ciclo_escolar']);
            
            try {
                if ($action == 'create') {
                    $db->query("INSERT INTO asignacion_modulos (profesor_id, modulo_id, grado, grupo, ciclo_escolar) 
                                VALUES (:profesor_id, :modulo_id, :grado, :grupo, :ciclo_escolar)");
                } else {
                    $id = intval($_POST['id']);
                    $db->query("UPDATE asignacion_modulos SET profesor_id = :profesor_id, modulo_id = :modulo_id, 
                                grado = :grado, grupo = :grupo, ciclo_escolar = :ciclo_escolar WHERE id = :id");
                    $db->bind(':id', $id);
                }
                
                $db->bind(':profesor_id', $profesor_id);
                $db->bind(':modulo_id', $modulo_id);
                $db->bind(':grado', $grado);
                $db->bind(':grupo', $grupo);
                $db->bind(':ciclo_escolar', $ciclo_escolar);
                
                if ($db->execute()) {
                    $message = $action == 'create' ? 'Asignación creada exitosamente' : 'Asignación actualizada exitosamente';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = 'Este profesor ya tiene asignado este módulo para el mismo grado y grupo';
                } else {
                    $message = 'Error: ' . $e->getMessage();
                }
                $message_type = 'danger';
            }
        } elseif ($action == 'delete') {
            $id = intval($_POST['id']);
            
            $db->query("DELETE FROM asignacion_modulos WHERE id = :id");
            $db->bind(':id', $id);
            
            if ($db->execute()) {
                $message = 'Asignación eliminada exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al eliminar asignación';
                $message_type = 'danger';
            }
        }
    }
}

// Obtener todas las asignaciones con información detallada
$db->query("SELECT am.*, u.nombre as profesor_nombre, u.apellido_paterno as profesor_ap, 
            u.apellido_materno as profesor_am, m.nombre as modulo_nombre, m.clave as modulo_clave
            FROM asignacion_modulos am
            INNER JOIN usuarios u ON am.profesor_id = u.id
            INNER JOIN modulos m ON am.modulo_id = m.id
            ORDER BY am.grado, am.grupo, m.nombre");
$asignaciones = $db->resultSet();

// Obtener profesores
$db->query("SELECT id, nombre, apellido_paterno, apellido_materno FROM usuarios WHERE tipo = 'profesor' ORDER BY apellido_paterno");
$profesores = $db->resultSet();

// Obtener módulos
$db->query("SELECT id, nombre, clave FROM modulos ORDER BY clave");
$modulos = $db->resultSet();

// Obtener asignación para editar
$edit_asignacion = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $db->query("SELECT * FROM asignacion_modulos WHERE id = :id");
    $db->bind(':id', $id);
    $edit_asignacion = $db->single();
}

// Estadísticas
$db->query("SELECT COUNT(*) as total FROM asignacion_modulos");
$total_asignaciones = $db->single()['total'];

$db->query("SELECT COUNT(DISTINCT profesor_id) as total FROM asignacion_modulos");
$profesores_con_asignaciones = $db->single()['total'];

$db->query("SELECT COUNT(DISTINCT modulo_id) as total FROM asignacion_modulos");
$modulos_asignados = $db->single()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Módulos - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo mb-3">
                <i class="fas fa-graduation-cap fa-2x"></i>
            </div>
            <h3><?php echo APP_NAME; ?></h3>
            <p class="text-white-50 small">Admin Panel</p>
        </div>
        
        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="gestion_usuarios.php">
                        <i class="fas fa-users"></i> Gestión de Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="gestion_alumnos.php">
                        <i class="fas fa-user-graduate"></i> Gestión de Alumnos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="gestion_modulos.php">
                        <i class="fas fa-book-open"></i> Gestión de Módulos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="asignar_modulos.php">
                        <i class="fas fa-book"></i> Asignar Módulos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="generar_boletas.php">
                        <i class="fas fa-file-alt"></i> Generar Boletas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="plantillas_boletas.php">
                        <i class="fas fa-palette"></i> Plantillas de Boletas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reportes.php">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                               data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#">
                                    <i class="fas fa-cog me-2"></i> Configuración
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
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-book me-3"></i>
                            Asignación de Módulos
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Asigna módulos/materias a profesores por grado y grupo
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#asignacionModal">
                            <i class="fas fa-plus me-2"></i>Nueva Asignación
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-item">
                    <i class="fas fa-book fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $total_asignaciones; ?></div>
                    <div class="stat-label">Total Asignaciones</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-chalkboard-teacher fa-2x text-success"></i>
                    <div class="stat-number"><?php echo $profesores_con_asignaciones; ?></div>
                    <div class="stat-label">Profesores Activos</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-layer-group fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $modulos_asignados; ?></div>
                    <div class="stat-label">Módulos Asignados</div>
                </div>
            </div>
            
            <!-- Assignments List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Asignaciones</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Profesor</th>
                                    <th>Módulo</th>
                                    <th>Grado - Grupo</th>
                                    <th>Ciclo Escolar</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($asignaciones) > 0): ?>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($asignaciones as $asignacion): ?>
                                        <tr>
                                            <td><?php echo $contador++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($asignacion['profesor_ap'] . ' ' . $asignacion['profesor_am']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($asignacion['profesor_nombre']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($asignacion['modulo_clave']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($asignacion['modulo_nombre']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($asignacion['grado']); ?> - <?php echo htmlspecialchars($asignacion['grupo']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($asignacion['ciclo_escolar']); ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#asignacionModal"
                                                            onclick='editAsignacion(<?php echo json_encode($asignacion); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick='deleteAsignacion(<?php echo $asignacion['id']; ?>, "<?php echo htmlspecialchars($asignacion['modulo_nombre']); ?>")'>
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
                                                <i class="fas fa-book fa-3x mb-3"></i>
                                                <p>No hay asignaciones registradas</p>
                                                <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#asignacionModal">
                                                    <i class="fas fa-plus me-2"></i>Crear Primera Asignación
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Crear/Editar Asignación -->
    <div class="modal fade" id="asignacionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" id="modal_action" value="create">
                    <input type="hidden" name="id" id="asignacion_id">
                    
                    <div class="modal-header gradient-primary">
                        <h5 class="modal-title text-white" id="modal_title">
                            <i class="fas fa-book me-2"></i>Nueva Asignación de Módulo
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Profesor <span class="text-danger">*</span></label>
                                <select class="form-select" name="profesor_id" id="profesor_id" required>
                                    <option value="">Seleccionar Profesor...</option>
                                    <?php foreach ($profesores as $profesor): ?>
                                        <option value="<?php echo $profesor['id']; ?>">
                                            <?php echo htmlspecialchars($profesor['apellido_paterno'] . ' ' . $profesor['apellido_materno'] . ' - ' . $profesor['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Módulo/Materia <span class="text-danger">*</span></label>
                                <select class="form-select" name="modulo_id" id="modulo_id" required>
                                    <option value="">Seleccionar Módulo...</option>
                                    <?php foreach ($modulos as $modulo): ?>
                                        <option value="<?php echo $modulo['id']; ?>">
                                            <?php echo htmlspecialchars($modulo['clave'] . ' - ' . $modulo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Grado <span class="text-danger">*</span></label>
                                <select class="form-select" name="grado" id="grado" required>
                                    <option value="">Seleccionar Grado...</option>
                                    <?php foreach (getGrades() as $grade): ?>
                                        <option value="<?php echo $grade; ?>"><?php echo $grade; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Grupo <span class="text-danger">*</span></label>
                                <select class="form-select" name="grupo" id="grupo" required>
                                    <option value="">Seleccionar Grupo...</option>
                                    <?php foreach (getGroups() as $group): ?>
                                        <option value="<?php echo $group; ?>"><?php echo $group; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Ciclo Escolar <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ciclo_escolar" id="ciclo_escolar" 
                                       value="<?php echo CYCLE_ACTUAL; ?>" required>
                                <small class="text-muted">Formato: 2025-2026</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><span id="btn_save_text">Crear Asignación</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Edit asignación
        function editAsignacion(asignacion) {
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-book me-2"></i>Editar Asignación';
            document.getElementById('modal_action').value = 'update';
            document.getElementById('btn_save_text').textContent = 'Actualizar Asignación';
            
            document.getElementById('asignacion_id').value = asignacion.id;
            document.getElementById('profesor_id').value = asignacion.profesor_id;
            document.getElementById('modulo_id').value = asignacion.modulo_id;
            document.getElementById('grado').value = asignacion.grado;
            document.getElementById('grupo').value = asignacion.grupo;
            document.getElementById('ciclo_escolar').value = asignacion.ciclo_escolar;
        }
        
        // Reset modal on close
        document.getElementById('asignacionModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-book me-2"></i>Nueva Asignación de Módulo';
            document.getElementById('modal_action').value = 'create';
            document.getElementById('btn_save_text').textContent = 'Crear Asignación';
            document.getElementById('asignacion_id').value = '';
        });
        
        // Delete asignación with confirmation
        function deleteAsignacion(id, modulo) {
            if (confirm('¿Estás seguro de que deseas eliminar la asignación del módulo "' + modulo + '"?\nEsta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'delete';
                
                const asignacionId = document.createElement('input');
                asignacionId.type = 'hidden';
                asignacionId.name = 'id';
                asignacionId.value = id;
                
                form.appendChild(action);
                form.appendChild(asignacionId);
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