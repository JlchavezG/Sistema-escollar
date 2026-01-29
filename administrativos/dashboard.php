<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$db = new Database();

// Estadísticas generales
$db->query("SELECT COUNT(*) as total FROM alumnos");
$stats['alumnos'] = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'profesor'");
$stats['profesores'] = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'administrativo'");
$stats['administrativos'] = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM modulos");
$stats['modulos'] = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM calificaciones WHERE mes = :mes");
$db->bind(':mes', date('F'));
$stats['calificaciones_mes'] = $db->single()['total'];

// Últimos alumnos registrados
$db->query("SELECT * FROM alumnos ORDER BY created_at DESC LIMIT 5");
$ultimos_alumnos = $db->resultSet();

// Últimos profesores registrados
$db->query("SELECT * FROM usuarios WHERE tipo = 'profesor' ORDER BY created_at DESC LIMIT 5");
$ultimos_profesores = $db->resultSet();

// Calificaciones por mes
$meses_calificaciones = [];
$meses = getMonths();
foreach ($meses as $mes) {
    $db->query("SELECT COUNT(*) as total FROM calificaciones WHERE mes = :mes");
    $db->bind(':mes', $mes);
    $meses_calificaciones[$mes] = $db->single()['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Sidebar Toggle JS -->
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
                <!-- Sidebar Toggle Button -->
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
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-0">Dashboard Administrativo</h2>
                    <p class="text-muted">Bienvenido, <?php echo $_SESSION['user_name']; ?></p>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0 gradient-primary">
                        <div class="card-icon bg-white text-primary">
                            <i class="fas fa-user-graduate fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Total Alumnos</div>
                            <div class="card-value text-white"><?php echo $stats['alumnos']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0 gradient-info">
                        <div class="card-icon bg-white text-info">
                            <i class="fas fa-chalkboard-teacher fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Profesores</div>
                            <div class="card-value text-white"><?php echo $stats['profesores']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0 gradient-secondary">
                        <div class="card-icon bg-white text-secondary">
                            <i class="fas fa-user-tie fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Administrativos</div>
                            <div class="card-value text-white"><?php echo $stats['administrativos']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0 gradient-accent">
                        <div class="card-icon bg-white text-accent">
                            <i class="fas fa-book-open fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Módulos</div>
                            <div class="card-value text-white"><?php echo $stats['modulos']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="gestion_alumnos.php" class="btn-action gradient-primary">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Registrar Alumno</span>
                                </a>
                                <a href="gestion_usuarios.php" class="btn-action gradient-success">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Registrar Profesor</span>
                                </a>
                                <a href="asignar_modulos.php" class="btn-action gradient-info">
                                    <i class="fas fa-book"></i>
                                    <span>Asignar Módulos</span>
                                </a>
                                <a href="generar_boletas.php" class="btn-action gradient-warning">
                                    <i class="fas fa-file-pdf"></i>
                                    <span>Generar Boletas</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Recent Activity -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Calificaciones por Mes</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="calificacionesChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Últimos Alumnos</h5>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php if (count($ultimos_alumnos) > 0): ?>
                                <?php foreach ($ultimos_alumnos as $alumno): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($alumno['apellido_paterno']); ?></strong>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($alumno['nombre']); ?></small>
                                            </div>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($alumno['grado']); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center text-muted">
                                    No hay alumnos registrados
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Últimos Profesores</h5>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php if (count($ultimos_profesores) > 0): ?>
                                <?php foreach ($ultimos_profesores as $profesor): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($profesor['apellido_paterno']); ?></strong>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($profesor['nombre']); ?></small>
                                            </div>
                                            <span class="badge bg-success">Profesor</span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center text-muted">
                                    No hay profesores registrados
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Sistema</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Ciclo Escolar:</strong>
                                    <p class="text-muted mb-0"><?php echo CYCLE_ACTUAL; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Fecha Actual:</strong>
                                    <p class="text-muted mb-0"><?php echo date('d/m/Y'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Versión:</strong>
                                    <p class="text-muted mb-0"><?php echo APP_VERSION; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Calificaciones este mes:</strong>
                                    <p class="text-muted mb-0"><?php echo $stats['calificaciones_mes']; ?></p>
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
    <script>
        // Chart.js para calificaciones por mes
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('calificacionesChart').getContext('2d');
            const calificacionesChart = new Chart(ctx, {
                type: 'bar',
                 {
                    labels: <?php echo json_encode(array_keys($meses_calificaciones)); ?>,
                    datasets: [{
                        label: 'Calificaciones Registradas',
                         <?php echo json_encode(array_values($meses_calificaciones)); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.7)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
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