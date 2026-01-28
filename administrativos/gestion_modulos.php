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
            $clave = strtoupper(sanitizeInput($_POST['clave']));
            $descripcion = sanitizeInput($_POST['descripcion']);
            
            try {
                if ($action == 'create') {
                    $db->query("INSERT INTO modulos (nombre, clave, descripcion, created_at) 
                                VALUES (:nombre, :clave, :descripcion, NOW())");
                } else {
                    $id = intval($_POST['id']);
                    $db->query("UPDATE modulos SET nombre = :nombre, clave = :clave, 
                                descripcion = :descripcion WHERE id = :id");
                    $db->bind(':id', $id);
                }
                
                $db->bind(':nombre', $nombre);
                $db->bind(':clave', $clave);
                $db->bind(':descripcion', $descripcion);
                
                if ($db->execute()) {
                    $message = $action == 'create' ? 'Módulo creado exitosamente' : 'Módulo actualizado exitosamente';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = 'Ya existe un módulo con esta clave. Las claves deben ser únicas.';
                } else {
                    $message = 'Error: ' . $e->getMessage();
                }
                $message_type = 'danger';
            }
        } elseif ($action == 'delete') {
            $id = intval($_POST['id']);
            
            // Verificar si el módulo está en uso
            $db->query("SELECT COUNT(*) as total FROM asignacion_modulos WHERE modulo_id = :id");
            $db->bind(':id', $id);
            $en_uso = $db->single()['total'];
            
            if ($en_uso > 0) {
                $message = 'No se puede eliminar este módulo porque está asignado a profesores. Primero elimina las asignaciones.';
                $message_type = 'warning';
            } else {
                $db->query("DELETE FROM modulos WHERE id = :id");
                $db->bind(':id', $id);
                
                if ($db->execute()) {
                    $message = 'Módulo eliminado exitosamente';
                    $message_type = 'success';
                } else {
                    $message = 'Error al eliminar módulo';
                    $message_type = 'danger';
                }
            }
        } elseif ($action == 'create_examples') {
            // Crear módulos de ejemplo directamente desde el sistema
            $modulos_ejemplo = [
                ['Matemáticas', 'MAT', 'Materia de Matemáticas'],
                ['Español', 'ESP', 'Materia de Español'],
                ['Historia', 'HIS', 'Materia de Historia'],
                ['Geografía', 'GEO', 'Materia de Geografía'],
                ['Ciencias Naturales', 'CNA', 'Materia de Ciencias Naturales'],
                ['Física', 'FIS', 'Materia de Física'],
                ['Química', 'QUI', 'Materia de Química'],
                ['Biología', 'BIO', 'Materia de Biología'],
                ['Inglés', 'ING', 'Materia de Inglés'],
                ['Educación Física', 'EDF', 'Materia de Educación Física'],
                ['Artes Visuales', 'ARV', 'Materia de Artes Visuales'],
                ['Música', 'MUS', 'Materia de Música'],
                ['Tecnología', 'TEC', 'Materia de Tecnología'],
                ['Formación Cívica y Ética', 'FCE', 'Materia de Formación Cívica y Ética'],
                ['Programación', 'PRO', 'Materia de Programación'],
                ['Robótica', 'ROB', 'Materia de Robótica'],
                ['Literatura', 'LIT', 'Materia de Literatura'],
                ['Álgebra', 'ALG', 'Materia de Álgebra'],
                ['Cálculo', 'CAL', 'Materia de Cálculo'],
                ['Estadística', 'EST', 'Materia de Estadística']
            ];
            
            $created = 0;
            $existing = 0;
            
            foreach ($modulos_ejemplo as $modulo) {
                $db->query("SELECT id FROM modulos WHERE clave = :clave");
                $db->bind(':clave', $modulo[1]);
                $exists = $db->single();
                
                if (!$exists) {
                    $db->query("INSERT INTO modulos (nombre, clave, descripcion, created_at) 
                                VALUES (:nombre, :clave, :descripcion, NOW())");
                    $db->bind(':nombre', $modulo[0]);
                    $db->bind(':clave', $modulo[1]);
                    $db->bind(':descripcion', $modulo[2]);
                    
                    if ($db->execute()) {
                        $created++;
                    }
                } else {
                    $existing++;
                }
            }
            
            $message = "✅ Módulos de ejemplo creados exitosamente<br>
                        <strong>Nuevos:</strong> $created<br>
                        <strong>Ya existentes:</strong> $existing<br>
                        <strong>Total:</strong> " . ($created + $existing);
            $message_type = 'success';
        }
    }
}

// Obtener todos los módulos
$db->query("SELECT * FROM modulos ORDER BY clave ASC");
$modulos = $db->resultSet();

// Obtener módulo para editar
$edit_modulo = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $db->query("SELECT * FROM modulos WHERE id = :id");
    $db->bind(':id', $id);
    $edit_modulo = $db->single();
}

// Estadísticas
$db->query("SELECT COUNT(*) as total FROM modulos");
$total_modulos = $db->single()['total'];

$db->query("SELECT COUNT(DISTINCT m.id) as total 
            FROM modulos m 
            INNER JOIN asignacion_modulos am ON m.id = am.modulo_id");
$modulos_en_uso = $db->single()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Módulos - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebarAdmin.php';?>
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
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'info-circle')); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-book-open me-3"></i>
                            Gestión de Módulos/Materias
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Administra las materias que se imparten en la escuela
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#examplesModal">
                                <i class="fas fa-magic me-2"></i>Módulos Ejemplo
                            </button>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#moduloModal">
                                <i class="fas fa-plus me-2"></i>Nuevo Módulo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-item">
                    <i class="fas fa-book-open fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $total_modulos; ?></div>
                    <div class="stat-label">Total Módulos</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-check-circle fa-2x text-success"></i>
                    <div class="stat-number"><?php echo $modulos_en_uso; ?></div>
                    <div class="stat-label">En Uso</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-layer-group fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $total_modulos - $modulos_en_uso; ?></div>
                    <div class="stat-label">Disponibles</div>
                </div>
            </div>
            
            <!-- Info Alert if no modules -->
            <?php if ($total_modulos == 0): ?>
                <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading">💡 ¡Comienza fácilmente!</h5>
                            <p class="mb-0">No tienes módulos registrados. Puedes crearlos manualmente o usar los módulos de ejemplo para empezar rápidamente.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Modulos List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Módulos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Clave</th>
                                    <th>Nombre del Módulo</th>
                                    <th>Descripción</th>
                                    <th>Fecha Creación</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($modulos) > 0): ?>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($modulos as $modulo): ?>
                                        <tr>
                                            <td><?php echo $contador++; ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($modulo['clave']); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($modulo['nombre']); ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($modulo['descripcion'], 0, 60) . (strlen($modulo['descripcion']) > 60 ? '...' : '')); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($modulo['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#moduloModal"
                                                            onclick='editModulo(<?php echo json_encode($modulo); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick='deleteModulo(<?php echo $modulo['id']; ?>, "<?php echo htmlspecialchars($modulo['nombre']); ?>")'>
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
                                                <i class="fas fa-book-open fa-3x mb-3"></i>
                                                <p>No hay módulos registrados</p>
                                                <div class="d-flex justify-content-center gap-3 mt-3">
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#moduloModal">
                                                        <i class="fas fa-plus me-2"></i>Crear Primer Módulo
                                                    </button>
                                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#examplesModal">
                                                        <i class="fas fa-magic me-2"></i>Crear Ejemplos
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
    
    <!-- Modal: Crear/Editar Módulo -->
    <div class="modal fade" id="moduloModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" id="modal_action" value="create">
                    <input type="hidden" name="id" id="modulo_id">
                    
                    <div class="modal-header gradient-primary">
                        <h5 class="modal-title text-white" id="modal_title">
                            <i class="fas fa-book-open me-2"></i>Crear Nuevo Módulo
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nombre del Módulo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" id="nombre" 
                                       placeholder="Ej: Matemáticas, Historia, etc." required>
                                <small class="text-muted">Nombre completo de la materia</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Clave Única <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="clave" id="clave" 
                                       placeholder="Ej: MAT, HIS, ESP" required maxlength="10">
                                <small class="text-muted">Clave corta para identificar el módulo (única)</small>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Descripción</label>
                                <textarea class="form-control" name="descripcion" id="descripcion" rows="3" 
                                          placeholder="Descripción breve del módulo..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><span id="btn_save_text">Guardar Módulo</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal: Crear Módulos de Ejemplo -->
    <div class="modal fade" id="examplesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_examples">
                    
                    <div class="modal-header gradient-accent">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-magic me-2"></i>Crear Módulos de Ejemplo
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Información:</strong> Esta acción creará 20 módulos de ejemplo comunes en escuelas.
                            Los módulos que ya existan no serán duplicados.
                        </div>
                        
                        <h5 class="mb-3">Módulos que se crearán:</h5>
                        
                        <div style="max-height: 300px; overflow-y: auto; background: var(--color-gray-light); padding: var(--spacing-md); border-radius: var(--border-radius-md); margin-top: var(--spacing-md);">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><strong>MAT</strong> - Matemáticas</li>
                                <li class="mb-2"><strong>ESP</strong> - Español</li>
                                <li class="mb-2"><strong>HIS</strong> - Historia</li>
                                <li class="mb-2"><strong>GEO</strong> - Geografía</li>
                                <li class="mb-2"><strong>CNA</strong> - Ciencias Naturales</li>
                                <li class="mb-2"><strong>FIS</strong> - Física</li>
                                <li class="mb-2"><strong>QUI</strong> - Química</li>
                                <li class="mb-2"><strong>BIO</strong> - Biología</li>
                                <li class="mb-2"><strong>ING</strong> - Inglés</li>
                                <li class="mb-2"><strong>EDF</strong> - Educación Física</li>
                                <li class="mb-2"><strong>ARV</strong> - Artes Visuales</li>
                                <li class="mb-2"><strong>MUS</strong> - Música</li>
                                <li class="mb-2"><strong>TEC</strong> - Tecnología</li>
                                <li class="mb-2"><strong>FCE</strong> - Formación Cívica y Ética</li>
                                <li class="mb-2"><strong>PRO</strong> - Programación</li>
                                <li class="mb-2"><strong>ROB</strong> - Robótica</li>
                                <li class="mb-2"><strong>LIT</strong> - Literatura</li>
                                <li class="mb-2"><strong>ALG</strong> - Álgebra</li>
                                <li class="mb-2"><strong>CAL</strong> - Cálculo</li>
                                <li class="mb-2"><strong>EST</strong> - Estadística</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Nota:</strong> Puedes editar o eliminar estos módulos después. Los módulos con claves duplicadas no se crearán.
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning" 
                                onclick="return confirm('¿Estás seguro de que deseas crear los módulos de ejemplo? Los módulos existentes no serán afectados.')">
                            <i class="fas fa-magic me-2"></i>Crear Módulos de Ejemplo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Edit modulo
        function editModulo(modulo) {
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-book-open me-2"></i>Editar Módulo';
            document.getElementById('modal_action').value = 'update';
            document.getElementById('btn_save_text').textContent = 'Actualizar Módulo';
            
            document.getElementById('modulo_id').value = modulo.id;
            document.getElementById('nombre').value = modulo.nombre;
            document.getElementById('clave').value = modulo.clave;
            document.getElementById('descripcion').value = modulo.descripcion;
        }
        
        // Reset modal on close
        document.getElementById('moduloModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-book-open me-2"></i>Crear Nuevo Módulo';
            document.getElementById('modal_action').value = 'create';
            document.getElementById('btn_save_text').textContent = 'Guardar Módulo';
            document.getElementById('modulo_id').value = '';
        });
        
        // Delete modulo with confirmation and usage check
        function deleteModulo(id, nombre) {
            if (confirm('¿Estás seguro de que deseas eliminar el módulo "' + nombre + '"?\n⚠️ Si está asignado a profesores, no podrás eliminarlo hasta remover las asignaciones.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'delete';
                
                const moduloId = document.createElement('input');
                moduloId.type = 'hidden';
                moduloId.name = 'id';
                moduloId.value = id;
                
                form.appendChild(action);
                form.appendChild(moduloId);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Auto-uppercase for clave field
        document.getElementById('clave').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
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