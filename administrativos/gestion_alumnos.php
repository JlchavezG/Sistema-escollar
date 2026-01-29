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
            $nombre = sanitizeInput($_POST['nombre']);
            $apellido_paterno = sanitizeInput($_POST['apellido_paterno']);
            $apellido_materno = sanitizeInput($_POST['apellido_materno']);
            $fecha_nacimiento = $_POST['fecha_nacimiento'];
            $grado = sanitizeInput($_POST['grado']);
            $grupo = sanitizeInput($_POST['grupo']);
            $tutor_nombre = sanitizeInput($_POST['tutor_nombre']);
            $tutor_telefono = sanitizeInput($_POST['tutor_telefono']);
            
            try {
                if ($action == 'create') {
                    $db->query("INSERT INTO alumnos (nombre, apellido_paterno, apellido_materno, fecha_nacimiento, grado, grupo, tutor_nombre, tutor_telefono, created_at) 
                                VALUES (:nombre, :apellido_paterno, :apellido_materno, :fecha_nacimiento, :grado, :grupo, :tutor_nombre, :tutor_telefono, NOW())");
                } else {
                    $id = intval($_POST['id']);
                    $db->query("UPDATE alumnos SET nombre = :nombre, apellido_paterno = :apellido_paterno, 
                                apellido_materno = :apellido_materno, fecha_nacimiento = :fecha_nacimiento, 
                                grado = :grado, grupo = :grupo, tutor_nombre = :tutor_nombre, tutor_telefono = :tutor_telefono 
                                WHERE id = :id");
                    $db->bind(':id', $id);
                }
                
                $db->bind(':nombre', $nombre);
                $db->bind(':apellido_paterno', $apellido_paterno);
                $db->bind(':apellido_materno', $apellido_materno);
                $db->bind(':fecha_nacimiento', $fecha_nacimiento);
                $db->bind(':grado', $grado);
                $db->bind(':grupo', $grupo);
                $db->bind(':tutor_nombre', $tutor_nombre);
                $db->bind(':tutor_telefono', $tutor_telefono);
                
                if ($db->execute()) {
                    $message = $action == 'create' ? 'Alumno registrado exitosamente' : 'Alumno actualizado exitosamente';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } elseif ($action == 'delete') {
            $id = intval($_POST['id']);
            
            $db->query("DELETE FROM alumnos WHERE id = :id");
            $db->bind(':id', $id);
            
            if ($db->execute()) {
                $message = 'Alumno eliminado exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al eliminar alumno';
                $message_type = 'danger';
            }
        }
    }
}

// Obtener todos los alumnos
$db->query("SELECT * FROM alumnos ORDER BY grado, grupo, apellido_paterno ASC");
$alumnos = $db->resultSet();

// Obtener alumno para editar
$edit_alumno = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $db->query("SELECT * FROM alumnos WHERE id = :id");
    $db->bind(':id', $id);
    $edit_alumno = $db->single();
}

// Estadísticas por grado y grupo
$db->query("SELECT grado, grupo, COUNT(*) as total FROM alumnos GROUP BY grado, grupo ORDER BY grado, grupo");
$stats_grupos = $db->resultSet();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Alumnos - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebarAdmin.php'; ?>
    
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
            <!-- Mensajes CRUD -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Mensajes de Importación -->
            <?php if (isset($_SESSION['import_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['import_message_type']; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $_SESSION['import_message_type'] == 'success' ? 'check-circle' : ($_SESSION['import_message_type'] == 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                    <?php echo $_SESSION['import_message']; ?>
                    
                    <?php if (isset($_SESSION['import_errors']) && count($_SESSION['import_errors']) > 0): ?>
                        <div class="mt-3">
                            <strong>Errores detectados:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($_SESSION['import_errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php 
                unset($_SESSION['import_message']);
                unset($_SESSION['import_message_type']);
                unset($_SESSION['import_success_count']);
                unset($_SESSION['import_error_count']);
                unset($_SESSION['import_errors']);
                ?>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-user-graduate me-3"></i>
                            Gestión de Alumnos
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Registra y administra la información de los alumnos
                        </p>
                    </div>
                    <div class="col-md-12 text-md-end mt-3 mt-md-0">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <!-- Botón Descargar Plantilla CSV -->
                            <a href="descargar_plantilla_csv.php" class="btn btn-success" title="Descargar plantilla CSV">
                                <i class="fas fa-file-csv me-1"></i>Plantilla CSV
                            </a>
                            <!-- Botón Importar desde CSV (abre modal) -->
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importModal" title="Importar desde CSV">
                                <i class="fas fa-upload me-1"></i>Importar CSV
                            </button>
                            
                            <!-- Botón Nuevo Alumno -->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#alumnoModal">
                                <i class="fas fa-plus me-1"></i>Nuevo Alumno
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <?php
                $db->query("SELECT COUNT(*) as total FROM alumnos");
                $total_alumnos = $db->single()['total'];
                
                $db->query("SELECT COUNT(DISTINCT grado) as total FROM alumnos");
                $total_grados = $db->single()['total'];
                
                $db->query("SELECT COUNT(DISTINCT CONCAT(grado, grupo)) as total FROM alumnos");
                $total_grupos = $db->single()['total'];
                ?>
                
                <div class="stat-item">
                    <i class="fas fa-user-graduate fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $total_alumnos; ?></div>
                    <div class="stat-label">Total Alumnos</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-layer-group fa-2x text-secondary"></i>
                    <div class="stat-number"><?php echo $total_grados; ?></div>
                    <div class="stat-label">Grados</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-users fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $total_grupos; ?></div>
                    <div class="stat-label">Grupos</div>
                </div>
            </div>
            
            <!-- Alumnos List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Alumnos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Nombre Completo</th>
                                    <th>Fecha Nac.</th>
                                    <th>Grado</th>
                                    <th>Grupo</th>
                                    <th>Tutor</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($alumnos) > 0): ?>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($alumnos as $alumno): ?>
                                        <tr>
                                            <td><?php echo $contador++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($alumno['nombre']); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($alumno['fecha_nacimiento'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($alumno['grado']); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo htmlspecialchars($alumno['grupo']); ?></span>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($alumno['tutor_nombre'] ?: 'N/A'); ?></small><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($alumno['tutor_telefono'] ?: ''); ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#alumnoModal"
                                                            onclick='editAlumno(<?php echo json_encode($alumno); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick='deleteAlumno(<?php echo $alumno['id']; ?>, "<?php echo htmlspecialchars($alumno['nombre']); ?>")'>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-user-graduate fa-3x mb-3"></i>
                                                <p>No hay alumnos registrados</p>
                                                <div class="d-flex justify-content-center gap-3 mt-3">
                                                    <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#alumnoModal">
                                                        <i class="fas fa-plus me-2"></i>Registrar Primer Alumno
                                                    </button>
                                                    <button type="button" class="btn btn-success mt-2" onclick="window.location.href='descargar_plantilla_csv.php'">
                                                        <i class="fas fa-file-csv me-2"></i>Descargar Plantilla CSV
                                                    </button>
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
        </div>
    </div>
    
    <!-- Modal: Crear/Editar Alumno -->
    <div class="modal fade" id="alumnoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" id="modal_action" value="create">
                    <input type="hidden" name="id" id="alumno_id">
                    
                    <div class="modal-header gradient-primary">
                        <h5 class="modal-title text-white" id="modal_title">
                            <i class="fas fa-user-plus me-2"></i>Registrar Nuevo Alumno
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nombre(s) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" id="nombre" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Apellido Paterno <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="apellido_paterno" id="apellido_paterno" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Apellido Materno</label>
                                <input type="text" class="form-control" name="apellido_materno" id="apellido_materno">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento" required>
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
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nombre del Tutor</label>
                                <input type="text" class="form-control" name="tutor_nombre" id="tutor_nombre" placeholder="Nombre completo del tutor">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Teléfono del Tutor</label>
                                <input type="text" class="form-control" name="tutor_telefono" id="tutor_telefono" placeholder="555-1234">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><span id="btn_save_text">Registrar Alumno</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal: Importar Alumnos desde CSV -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="importar_alumnos_csv.php" enctype="multipart/form-data">
                    <div class="modal-header gradient-primary">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-file-csv me-2"></i>Importar Alumnos desde CSV
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Instrucciones:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Descarga la plantilla CSV con el botón correspondiente</li>
                                <li>Llena los datos en Excel, Google Sheets o Numbers</li>
                                <li>Guarda como CSV (Valores separados por comas)</li>
                                <li>Sube el archivo CSV aquí</li>
                                <li>Haz clic en "Importar Alumnos"</li>
                            </ol>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-file-csv me-1 text-success"></i>Archivo CSV (.csv)
                            </label>
                            <input type="file" class="form-control" name="archivo_csv" accept=".csv" required>
                            <small class="text-muted">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Máximo 5MB. Solo archivos .csv
                            </small>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>Formato requerido:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Primera fila: Encabezados (NOMBRE, APELLIDO_PATERNO, etc.)</li>
                                <li>Fecha: AAAA-MM-DD (ej: 2010-05-15)</li>
                                <li>Separador: Coma (,)</li>
                                <li>Codificación: UTF-8</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-cloud-upload-alt me-1"></i>Importar Alumnos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Edit alumno
        function editAlumno(alumno) {
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Alumno';
            document.getElementById('modal_action').value = 'update';
            document.getElementById('btn_save_text').textContent = 'Actualizar Alumno';
            
            document.getElementById('alumno_id').value = alumno.id;
            document.getElementById('nombre').value = alumno.nombre;
            document.getElementById('apellido_paterno').value = alumno.apellido_paterno;
            document.getElementById('apellido_materno').value = alumno.apellido_materno;
            document.getElementById('fecha_nacimiento').value = alumno.fecha_nacimiento;
            document.getElementById('grado').value = alumno.grado;
            document.getElementById('grupo').value = alumno.grupo;
            document.getElementById('tutor_nombre').value = alumno.tutor_nombre;
            document.getElementById('tutor_telefono').value = alumno.tutor_telefono;
        }
        
        // Reset modal on close
        document.getElementById('alumnoModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-user-plus me-2"></i>Registrar Nuevo Alumno';
            document.getElementById('modal_action').value = 'create';
            document.getElementById('btn_save_text').textContent = 'Registrar Alumno';
            document.getElementById('alumno_id').value = '';
        });
        
        // Delete alumno with confirmation
        function deleteAlumno(id, name) {
            if (confirm('¿Estás seguro de que deseas eliminar al alumno "' + name + '"?\nEsta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'delete';
                
                const alumnoId = document.createElement('input');
                alumnoId.type = 'hidden';
                alumnoId.name = 'id';
                alumnoId.value = id;
                
                form.appendChild(action);
                form.appendChild(alumnoId);
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