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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            background: #f5f7fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h3 {
            color: white;
            margin: 10px 0;
            font-weight: 600;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu .nav-item {
            margin-bottom: 5px;
        }
        
        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 0 25px 25px 0;
            margin-right: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .sidebar-menu .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.15);
            margin-right: 0;
        }
        
        .sidebar-menu .nav-link:hover:not(.active) {
            color: white;
            background: rgba(255,255,255,0.1);
            margin-right: 0;
        }
        
        .sidebar-menu .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 280px;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .modulo-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid var(--primary-color);
        }
        
        .modulo-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .clave-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
        }
        
        .badge-en-uso {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            border-radius: 15px 15px 0 0 !important;
            padding: 25px;
        }
        
        .btn-custom {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .btn-example {
            background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);
            border: none;
            color: white;
            padding: 15px;
            border-radius: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .btn-example:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(246, 173, 85, 0.4);
        }
        
        .btn-example i {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .examples-modal {
            background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);
        }
        
        .examples-list {
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .examples-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .examples-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
        }
        
        .examples-list li:last-child {
            border-bottom: none;
        }
        
        .examples-list .clave {
            font-weight: 600;
            color: var(--primary-color);
            min-width: 60px;
        }
        
        .examples-list .nombre {
            color: #495057;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
            }
            .main-content {
                margin-left: 220px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .quick-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo mb-3">
                <i class="fas fa-graduation-cap fa-3x"></i>
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
                    <a class="nav-link active" href="gestion_modulos.php">
                        <i class="fas fa-book-open"></i> Gestión de Módulos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="asignar_modulos.php">
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
                            <i class="fas fa-book-open me-3" style="color: var(--primary-color);"></i>
                            Gestión de Módulos/Materias
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Administra las materias que se imparten en la escuela
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <button type="button" class="btn btn-warning btn-custom" data-bs-toggle="modal" data-bs-target="#examplesModal">
                                <i class="fas fa-magic me-2"></i>Módulos Ejemplo
                            </button>
                            <button type="button" class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#moduloModal">
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
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
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
                                        <tr class="modulo-card">
                                            <td><?php echo $contador++; ?></td>
                                            <td>
                                                <span class="clave-badge"><?php echo htmlspecialchars($modulo['clave']); ?></span>
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
                    
                    <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
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
                    
                    <div class="modal-header examples-modal">
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
                        
                        <div class="examples-list">
                            <ul>
                                <li><span class="clave">MAT</span> <span class="nombre">Matemáticas</span></li>
                                <li><span class="clave">ESP</span> <span class="nombre">Español</span></li>
                                <li><span class="clave">HIS</span> <span class="nombre">Historia</span></li>
                                <li><span class="clave">GEO</span> <span class="nombre">Geografía</span></li>
                                <li><span class="clave">CNA</span> <span class="nombre">Ciencias Naturales</span></li>
                                <li><span class="clave">FIS</span> <span class="nombre">Física</span></li>
                                <li><span class="clave">QUI</span> <span class="nombre">Química</span></li>
                                <li><span class="clave">BIO</span> <span class="nombre">Biología</span></li>
                                <li><span class="clave">ING</span> <span class="nombre">Inglés</span></li>
                                <li><span class="clave">EDF</span> <span class="nombre">Educación Física</span></li>
                                <li><span class="clave">ARV</span> <span class="nombre">Artes Visuales</span></li>
                                <li><span class="clave">MUS</span> <span class="nombre">Música</span></li>
                                <li><span class="clave">TEC</span> <span class="nombre">Tecnología</span></li>
                                <li><span class="clave">FCE</span> <span class="nombre">Formación Cívica y Ética</span></li>
                                <li><span class="clave">PRO</span> <span class="nombre">Programación</span></li>
                                <li><span class="clave">ROB</span> <span class="nombre">Robótica</span></li>
                                <li><span class="clave">LIT</span> <span class="nombre">Literatura</span></li>
                                <li><span class="clave">ALG</span> <span class="nombre">Álgebra</span></li>
                                <li><span class="clave">CAL</span> <span class="nombre">Cálculo</span></li>
                                <li><span class="clave">EST</span> <span class="nombre">Estadística</span></li>
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
</body>
</html>