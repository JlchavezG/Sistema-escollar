<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireSistemas(); // Solo accesible por sistemas

$db = new Database();
$message = '';
$message_type = '';

// Obtener configuración actual
$db->query("SELECT * FROM configuracion WHERE id = 1");
$config = $db->single();

// Manejar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_config'])) {
    $ciclo_escolar = sanitizeInput($_POST['ciclo_escolar']);
    $nombre_institucion = sanitizeInput($_POST['nombre_institucion']);
    $anio_fundacion = sanitizeInput($_POST['anio_fundacion']);
    $telefono_contacto = sanitizeInput($_POST['telefono_contacto']);
    $email_contacto = sanitizeInput($_POST['email_contacto']);
    
    try {
        $db->query("UPDATE configuracion SET 
                    ciclo_escolar = :ciclo_escolar,
                    nombre_institucion = :nombre_institucion,
                    anio_fundacion = :anio_fundacion,
                    telefono_contacto = :telefono_contacto,
                    email_contacto = :email_contacto,
                    updated_at = NOW()
                    WHERE id = 1");
        
        $db->bind(':ciclo_escolar', $ciclo_escolar);
        $db->bind(':nombre_institucion', $nombre_institucion);
        $db->bind(':anio_fundacion', $anio_fundacion);
        $db->bind(':telefono_contacto', $telefono_contacto);
        $db->bind(':email_contacto', $email_contacto);
        
        if ($db->execute()) {
            $message = 'Configuración actualizada exitosamente';
            $message_type = 'success';
            
            // Actualizar constantes en sesión
            $_SESSION['config'] = [
                'ciclo_escolar' => $ciclo_escolar,
                'nombre_institucion' => $nombre_institucion
            ];
            
            // Recargar configuración
            $db->query("SELECT * FROM configuracion WHERE id = 1");
            $config = $db->single();
        }
    } catch (PDOException $e) {
        $message = 'Error al actualizar configuración: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Estadísticas del sistema
$db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'sistemas'");
$total_sistemas = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'administrativo'");
$total_administrativos = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'profesor'");
$total_profesores = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM alumnos");
$total_alumnos = $db->single()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - <?php echo APP_NAME; ?></title>
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
                            <i class="fas fa-cog me-3"></i>
                            Configuración del Sistema
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-shield-alt me-2"></i>
                            Panel de administración exclusivo para personal de sistemas
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- System Stats -->
            <div class="stats-grid mb-4">
                <div class="stat-item">
                    <i class="fas fa-user-shield fa-2x text-danger"></i>
                    <div class="stat-number"><?php echo $total_sistemas; ?></div>
                    <div class="stat-label">Usuarios Sistemas</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-user-tie fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $total_administrativos; ?></div>
                    <div class="stat-label">Administrativos</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-chalkboard-teacher fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $total_profesores; ?></div>
                    <div class="stat-label">Profesores</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-user-graduate fa-2x text-success"></i>
                    <div class="stat-number"><?php echo $total_alumnos; ?></div>
                    <div class="stat-label">Alumnos Totales</div>
                </div>
            </div>
            
            <!-- Configuration Form -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Configuración General</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Nombre de la Institución <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nombre_institucion" 
                                               value="<?php echo htmlspecialchars($config['nombre_institucion'] ?? 'Sistema Escolar'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Ciclo Escolar Actual <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="ciclo_escolar" 
                                               value="<?php echo htmlspecialchars($config['ciclo_escolar'] ?? date('Y') . '-' . (date('Y')+1)); ?>" required>
                                        <small class="text-muted">Formato: 2025-2026</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Año de Fundación</label>
                                        <input type="text" class="form-control" name="anio_fundacion" 
                                               value="<?php echo htmlspecialchars($config['anio_fundacion'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Teléfono de Contacto</label>
                                        <input type="text" class="form-control" name="telefono_contacto" 
                                               value="<?php echo htmlspecialchars($config['telefono_contacto'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Email de Contacto</label>
                                        <input type="email" class="form-control" name="email_contacto" 
                                               value="<?php echo htmlspecialchars($config['email_contacto'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Atención:</strong> Los cambios en esta configuración afectarán a todo el sistema. 
                                    Asegúrate de tener respaldo antes de realizar modificaciones importantes.
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="update_config" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Configuración
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Acceso Restringido</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <i class="fas fa-lock me-2"></i>
                                <strong>Área exclusiva para personal de Sistemas</strong>
                            </div>
                            <p class="mb-3">Esta sección permite:</p>
                            <ul class="mb-4">
                                <li>Configurar parámetros globales del sistema</li>
                                <li>Gestionar usuarios de tipo Administrativo y Sistemas</li>
                                <li>Realizar auditorías y monitoreo del sistema</li>
                                <li>Administrar backups y recuperación</li>
                            </ul>
                            <div class="text-center">
                                <span class="badge bg-danger fs-6">
                                    <i class="fas fa-shield-alt me-1"></i>Nivel de Acceso: MÁXIMO
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Versión del Sistema:</strong>
                                <p class="text-muted mb-0"><?php echo APP_VERSION; ?></p>
                            </div>
                            <div class="mb-3">
                                <strong>Última Actualización:</strong>
                                <p class="text-muted mb-0">
                                    <?php echo $config['updated_at'] ? date('d/m/Y H:i', strtotime($config['updated_at'])) : 'Nunca'; ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <strong>Base de Datos:</strong>
                                <p class="text-muted mb-0">MySQL</p>
                            </div>
                            <div>
                                <strong>PHP Version:</strong>
                                <p class="text-muted mb-0"><?php echo phpversion(); ?></p>
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
            const darkModeToggle