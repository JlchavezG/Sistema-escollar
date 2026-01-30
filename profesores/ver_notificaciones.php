<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireProfesor();

$db = new Database();
$message = '';
$message_type = '';

// Marcar notificación como leída si se pasa el parámetro
if (isset($_GET['leer'])) {
    $notificacion_id = intval($_GET['leer']);
    $db->query("UPDATE notificaciones SET leido = TRUE, fecha_lectura = NOW() WHERE id = :id AND destinatario_id = :destinatario_id");
    $db->bind(':id', $notificacion_id);
    $db->bind(':destinatario_id', $_SESSION['user_id']);
    $db->execute();
    
    // Redirigir para evitar reenvío del formulario
    header('Location: ver_notificaciones.php');
    exit();
}

// Obtener todas las notificaciones del profesor
$db->query("SELECT n.*, u.nombre as remitente_nombre, u.apellido_paterno as remitente_ap 
            FROM notificaciones n
            INNER JOIN usuarios u ON n.remitente_id = u.id
            WHERE n.destinatario_id = :destinatario_id
            ORDER BY n.fecha_envio DESC");
$db->bind(':destinatario_id', $_SESSION['user_id']);
$notificaciones = $db->resultSet();

// Contar no leídas
$db->query("SELECT COUNT(*) as total FROM notificaciones WHERE destinatario_id = :destinatario_id AND leido = FALSE");
$db->bind(':destinatario_id', $_SESSION['user_id']);
$no_leidas = $db->single()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Notificaciones - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="../assets/js/sidebar-toggle.js" defer></script>
    <style>
        .notification-card {
            border-left: 4px solid var(--primary-color);
            transition: all var(--transition-fast);
            margin-bottom: 1rem;
        }
        
        .notification-card:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-lg);
        }
        
        .notification-card.unread {
            background: rgba(52, 152, 219, 0.08);
            border-left-color: var(--secondary-color);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }
        
        .notification-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-full);
            font-weight: var(--font-weight-bold);
            font-size: 0.8rem;
        }
        
        .badge-informativa { background: #e3f2fd; color: #1976d2; }
        .badge-urgente { background: #ffebee; color: #c62828; }
        .badge-recordatorio { background: #fff8e1; color: #5d4037; }
        .badge-importante { background: #e8f5e9; color: #2e7d32; }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .empty-notifications {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--color-text-secondary);
        }
        
        .empty-notifications i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.6;
        }
        
        .mark-all-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: var(--border-radius-lg);
            font-weight: var(--font-weight-bold);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-fast);
        }
        
        .mark-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebarProfesor.php'; ?>
    
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
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-bell me-3"></i>
                            Mis Notificaciones
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Mantente informado de las comunicaciones importantes de la administración
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <?php if ($no_leidas > 0): ?>
                        <a href="ver_notificaciones.php?marcar_todas=1" class="mark-all-btn">
                            <i class="fas fa-check-double me-1"></i>Marcar todas como leídas (<?php echo $no_leidas; ?>)
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-item">
                    <i class="fas fa-envelope-open-text fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo count($notificaciones); ?></div>
                    <div class="stat-label">Total de Notificaciones</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-eye-slash fa-2x text-warning"></i>
                    <div class="stat-number"><?php echo $no_leidas; ?></div>
                    <div class="stat-label">No Leídas</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-check-circle fa-2x text-success"></i>
                    <div class="stat-number"><?php echo count($notificaciones) - $no_leidas; ?></div>
                    <div class="stat-label">Leídas</div>
                </div>
            </div>
            
            <!-- Notifications List -->
            <?php if (count($notificaciones) > 0): ?>
                <div class="row">
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="card notification-card <?php echo $notificacion['leido'] ? '' : 'unread'; ?>">
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
                                    
                                    <p class="card-text text-muted mb-3">
                                        <i class="fas fa-user me-1 text-primary"></i>
                                        <strong>De:</strong> <?php echo htmlspecialchars($notificacion['remitente_ap'] . ' ' . $notificacion['remitente_nombre']); ?>
                                    </p>
                                    
                                    <div class="card-text mb-3">
                                        <?php echo nl2br(htmlspecialchars($notificacion['mensaje'])); ?>
                                    </div>
                                    
                                    <div class="notification-actions">
                                        <?php if (!$notificacion['leido']): ?>
                                            <a href="ver_notificaciones.php?leer=<?php echo $notificacion['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-check me-1"></i>Marcar como leída
                                            </a>
                                        <?php endif; ?>
                                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-1"></i>Volver al Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No tienes notificaciones</h3>
                    <p class="lead">Recibirás notificaciones importantes de la administración en este panel</p>
                    <a href="dashboard.php" class="btn btn-primary mt-3">
                        <i class="fas fa-home me-2"></i>Volver al Dashboard
                    </a>
                </div>
            <?php endif; ?>
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