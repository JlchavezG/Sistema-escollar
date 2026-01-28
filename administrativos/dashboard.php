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
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
            --info-color: #3182ce;
            --warning-color: #f6ad55;
            --danger-color: #f56565;
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
            transition: all 0.3s;
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
            transition: all 0.3s;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border: none;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .stats-card .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }
        
        .stats-card .card-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            position: relative;
            z-index: 1;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-action {
            padding: 20px;
            border-radius: 15px;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
            color: white;
            text-align: center;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .btn-action i {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .btn-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .recent-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .recent-list .list-group-item {
            border-radius: 10px !important;
            margin-bottom: 8px;
            border: 1px solid #e9ecef;
            padding: 12px 15px;
        }
        
        .recent-list .list-group-item:last-child {
            margin-bottom: 0;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
            }
            .main-content {
                margin-left: 220px;
            }
            .stats-card {
                margin-bottom: 15px;
            }
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
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
                    <a class="nav-link active" href="dashboard.php">
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
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-0">Dashboard Administrativo</h2>
                    <p class="text-muted">Bienvenido, <?php echo $_SESSION['user_name']; ?></p>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
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
                    <div class="card stats-card border-0" style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);">
                        <div class="card-icon bg-white text-success">
                            <i class="fas fa-chalkboard-teacher fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Profesores</div>
                            <div class="card-value text-white"><?php echo $stats['profesores']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0" style="background: linear-gradient(135deg, #3182ce 0%, #2b6cb0 100%);">
                        <div class="card-icon bg-white text-info">
                            <i class="fas fa-user-tie fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Administrativos</div>
                            <div class="card-value text-white"><?php echo $stats['administrativos']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0" style="background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);">
                        <div class="card-icon bg-white text-warning">
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
                    <div class="card recent-list">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="gestion_alumnos.php" class="btn-action" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Registrar Alumno</span>
                                </a>
                                <a href="gestion_usuarios.php" class="btn-action" style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Registrar Profesor</span>
                                </a>
                                <a href="asignar_modulos.php" class="btn-action" style="background: linear-gradient(135deg, #3182ce 0%, #2b6cb0 100%);">
                                    <i class="fas fa-book"></i>
                                    <span>Asignar Módulos</span>
                                </a>
                                <a href="generar_boletas.php" class="btn-action" style="background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%);">
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
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fas fa-chart-bar me-2"></i>Calificaciones por Mes</h5>
                        <canvas id="calificacionesChart" height="200"></canvas>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="recent-list">
                        <div class="card-header bg-white border-0">
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
                    <div class="recent-list">
                        <div class="card-header bg-white border-0">
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
                    <div class="recent-list">
                        <div class="card-header bg-white border-0">
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
                data: {
                    labels: <?php echo json_encode(array_keys($meses_calificaciones)); ?>,
                    datasets: [{
                        label: 'Calificaciones Registradas',
                        data: <?php echo json_encode(array_values($meses_calificaciones)); ?>,
                        backgroundColor: 'rgba(102, 126, 234, 0.7)',
                        borderColor: 'rgba(102, 126, 234, 1)',
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
</body>
</html>