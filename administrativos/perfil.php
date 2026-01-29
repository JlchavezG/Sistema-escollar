<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$message = '';
$message_type = '';

// Obtener información del usuario ACTUAL (no del session)
$db->query("SELECT * FROM usuarios WHERE id = :id");
$db->bind(':id', $_SESSION['user_id']);
$usuario = $db->single();

// Manejar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nombre = sanitizeInput($_POST['nombre']);
    $apellido_paterno = sanitizeInput($_POST['apellido_paterno']);
    $apellido_materno = sanitizeInput($_POST['apellido_materno']);
    $telefono = sanitizeInput($_POST['telefono']);
    $email = sanitizeInput($_POST['email']);
    
    try {
        $db->query("UPDATE usuarios SET nombre = :nombre, apellido_paterno = :apellido_paterno, 
                    apellido_materno = :apellido_materno, telefono = :telefono, email = :email, 
                    updated_at = NOW() WHERE id = :id");
        $db->bind(':nombre', $nombre);
        $db->bind(':apellido_paterno', $apellido_paterno);
        $db->bind(':apellido_materno', $apellido_materno);
        $db->bind(':telefono', $telefono);
        $db->bind(':email', $email);
        $db->bind(':id', $_SESSION['user_id']);
        
        if ($db->execute()) {
            // Actualizar sesión con nuevos datos
            $_SESSION['user_name'] = $nombre . ' ' . $apellido_paterno;
            $_SESSION['user_email'] = $email;
            
            $message = 'Perfil actualizado exitosamente';
            $message_type = 'success';
            
            // Recargar datos del usuario
            $db->query("SELECT * FROM usuarios WHERE id = :id");
            $db->bind(':id', $_SESSION['user_id']);
            $usuario = $db->single();
        }
    } catch (PDOException $e) {
        $message = 'Error al actualizar perfil: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Manejar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verificar contraseña actual
    if (!password_verify($current_password, $usuario['password'])) {
        $message = 'La contraseña actual es incorrecta';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Las nuevas contraseñas no coinciden';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 8) {
        $message = 'La nueva contraseña debe tener al menos 8 caracteres';
        $message_type = 'danger';
    } else {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $db->query("UPDATE usuarios SET password = :password, updated_at = NOW() WHERE id = :id");
            $db->bind(':password', $password_hash);
            $db->bind(':id', $_SESSION['user_id']);
            
            if ($db->execute()) {
                $message = 'Contraseña actualizada exitosamente';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error al actualizar contraseña: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?php echo APP_NAME; ?></title>
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
                            <i class="fas fa-user me-3"></i>
                            Mi Perfil
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Gestiona tu información personal y configuración de cuenta
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Información Personal -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Información Personal</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Nombre(s) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nombre" 
                                               value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Apellido Paterno <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="apellido_paterno" 
                                               value="<?php echo htmlspecialchars($usuario['apellido_paterno']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Apellido Materno</label>
                                        <input type="text" class="form-control" name="apellido_materno" 
                                               value="<?php echo htmlspecialchars($usuario['apellido_materno']); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                        <small class="text-muted">Tu correo no será visible públicamente</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Teléfono</label>
                                        <input type="text" class="form-control" name="telefono" 
                                               value="<?php echo htmlspecialchars($usuario['telefono']); ?>" 
                                               placeholder="555-1234">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Actualizar Perfil
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Cambio de Contraseña -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-key me-2"></i>Cambiar Contraseña</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Contraseña Actual <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nueva Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="new_password" 
                                           minlength="8" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="confirm_password" 
                                           minlength="8" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información de Cuenta - CORREGIDO -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información de Cuenta</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <strong>Tipo de Usuario:</strong>
                            <div class="mt-2">
                                <?php 
                                // ✅ CORREGIDO: Mostrar tipo dinámico según la BD
                                if ($usuario['tipo'] == 'profesor') {
                                    echo '<span class="badge bg-info"><i class="fas fa-chalkboard-teacher me-1"></i>Profesor</span>';
                                } elseif ($usuario['tipo'] == 'administrativo') {
                                    echo '<span class="badge bg-primary"><i class="fas fa-user-tie me-1"></i>Administrativo</span>';
                                } else { // sistemas
                                    echo '<span class="badge bg-danger"><i class="fas fa-user-shield me-1"></i>Sistemas</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <strong>Fecha de Registro:</strong>
                            <div class="mt-2 text-muted">
                                <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <strong>Última Actualización:</strong>
                            <div class="mt-2 text-muted">
                                <?php echo $usuario['updated_at'] ? date('d/m/Y H:i', strtotime($usuario['updated_at'])) : 'Nunca'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Acceso Rápido -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acceso Rápido</h5>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="dashboard.php" class="btn-action gradient-primary">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                        <?php if ($_SESSION['user_role'] === 'sistemas'): ?>
                        <a href="gestion_usuarios.php" class="btn-action gradient-danger">
                            <i class="fas fa-users-cog"></i>
                            <span>Gestión de Usuarios</span>
                        </a>
                        <a href="configuracion_sistema.php" class="btn-action gradient-warning">
                            <i class="fas fa-cog"></i>
                            <span>Configuración</span>
                        </a>
                        <?php else: ?>
                        <a href="gestion_usuarios.php" class="btn-action gradient-info">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Gestión de Profesores</span>
                        </a>
                        <?php endif; ?>
                        <a href="gestion_alumnos.php" class="btn-action gradient-secondary">
                            <i class="fas fa-user-graduate"></i>
                            <span>Gestión de Alumnos</span>
                        </a>
                        <a href="reportes.php" class="btn-action gradient-accent">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reportes</span>
                        </a>
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