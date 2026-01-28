<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireProfesor();

$db = new Database();

// Obtener módulos asignados
$db->query("SELECT am.*, m.nombre as modulo_nombre, m.clave as modulo_clave 
            FROM asignacion_modulos am 
            INNER JOIN modulos m ON am.modulo_id = m.id 
            WHERE am.profesor_id = :profesor_id 
            AND am.ciclo_escolar = :ciclo
            ORDER BY am.grado, am.grupo, m.nombre");
$db->bind(':profesor_id', $_SESSION['user_id']);
$db->bind(':ciclo', CYCLE_ACTUAL);
$modulos_asignados = $db->resultSet();

// Estadísticas
$db->query("SELECT COUNT(DISTINCT alumno_id) as total FROM calificaciones WHERE profesor_id = :id");
$db->bind(':id', $_SESSION['user_id']);
$total_alumnos = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM calificaciones WHERE profesor_id = :id AND mes = :mes");
$db->bind(':id', $_SESSION['user_id']);
$db->bind(':mes', date('F'));
$calificaciones_mes = $db->single()['total'];

$db->query("SELECT AVG(calificacion) as promedio FROM calificaciones WHERE profesor_id = :id");
$db->bind(':id', $_SESSION['user_id']);
$promedio_general = $db->single()['promedio'];

// Calificaciones por mes
$meses_calificaciones = [];
$meses = getMonths();
foreach ($meses as $mes) {
    $db->query("SELECT COUNT(*) as total FROM calificaciones WHERE profesor_id = :id AND mes = :mes");
    $db->bind(':id', $_SESSION['user_id']);
    $db->bind(':mes', $mes);
    $meses_calificaciones[$mes] = $db->single()['total'];
}

// Últimas calificaciones cargadas
$db->query("SELECT c.*, a.nombre as alumno_nombre, a.apellido_paterno as alumno_ap, m.nombre as modulo_nombre
            FROM calificaciones c
            INNER JOIN alumnos a ON c.alumno_id = a.id
            INNER JOIN modulos m ON c.modulo_id = m.id
            WHERE c.profesor_id = :profesor_id
            ORDER BY c.fecha_registro DESC
            LIMIT 10");
$db->bind(':profesor_id', $_SESSION['user_id']);
$ultimas_calificaciones = $db->resultSet();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Profesor - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebarProfesor.php'; ?>
    
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
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-0">Bienvenido, <?php echo $_SESSION['user_name']; ?></h2>
                    <p class="text-muted">Panel de control del profesor</p>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0 gradient-primary">
                        <div class="card-icon bg-white text-primary">
                            <i class="fas fa-book-open fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Módulos Asignados</div>
                            <div class="card-value text-white"><?php echo count($modulos_asignados); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0 gradient-info">
                        <div class="card-icon bg-white text-info">
                            <i class="fas fa-user-graduate fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Alumnos Totales</div>
                            <div class="card-value text-white"><?php echo $total_alumnos; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0 gradient-secondary">
                        <div class="card-icon bg-white text-secondary">
                            <i class="fas fa-pen-alt fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Calificaciones este Mes</div>
                            <div class="card-value text-white"><?php echo $calificaciones_mes; ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0 gradient-accent">
                        <div class="card-icon bg-white text-accent">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title text-white">Promedio General</div>
                            <div class="card-value text-white"><?php echo number_format($promedio_general ?? 0, 2); ?></div>
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
                                <a href="cargar_calificaciones.php" class="btn-action gradient-primary">
                                    <i class="fas fa-edit"></i>
                                    <span>Cargar Calificaciones</span>
                                </a>
                                <a href="ver_modulos.php" class="btn-action gradient-info">
                                    <i class="fas fa-book"></i>
                                    <span>Ver Mis Módulos</span>
                                </a>
                                <a href="perfil.php" class="btn-action gradient-secondary">
                                    <i class="fas fa-user"></i>
                                    <span>Mi Perfil</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Calificaciones por Mes Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Mis Calificaciones por Mes</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="calificacionesChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Últimas Calificaciones -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimas Calificaciones</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($ultimas_calificaciones) > 0): ?>
                                <?php foreach ($ultimas_calificaciones as $cal): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($cal['alumno_ap']); ?></strong>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($cal['modulo_nombre']); ?></small>
                                            </div>
                                            <span class="badge <?php echo $cal['calificacion'] >= 60 ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo number_format($cal['calificacion'], 1); ?>
                                            </span>
                                        </div>
                                        <small class="text-muted mt-2 d-block">
                                            <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($cal['fecha_registro'])); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-calendar-alt me-1"></i><?php echo $cal['mes']; ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p class="mb-0">No hay calificaciones registradas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Assigned Modules -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Mis Módulos Asignados</h5>
                            <span class="badge bg-primary"><?php echo count($modulos_asignados); ?> módulos</span>
                        </div>
                        <div class="card-body">
                            <?php if (count($modulos_asignados) > 0): ?>
                                <div class="row">
                                    <?php foreach ($modulos_asignados as $modulo): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card border-0">
                                                <div class="card-header gradient-primary text-white">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($modulo['modulo_nombre']); ?></h6>
                                                    <small class="text-white-50"><?php echo htmlspecialchars($modulo['modulo_clave']); ?></small>
                                                </div>
                                                <div class="card-body">
                                                    <p class="mb-2"><strong>Grado:</strong> <?php echo htmlspecialchars($modulo['grado']); ?></p>
                                                    <p class="mb-2"><strong>Grupo:</strong> <?php echo htmlspecialchars($modulo['grupo']); ?></p>
                                                    <p class="mb-3"><strong>Ciclo:</strong> <?php echo htmlspecialchars($modulo['ciclo_escolar']); ?></p>
                                                    <a href="cargar_calificaciones.php?modulo=<?php echo $modulo['modulo_id']; ?>&grado=<?php echo urlencode($modulo['grado']); ?>&grupo=<?php echo $modulo['grupo']; ?>" 
                                                       class="btn btn-sm btn-primary w-100">
                                                        <i class="fas fa-edit me-2"></i>Cargar Calificaciones
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No tienes módulos asignados actualmente. Contacta al administrador.
                                </div>
                            <?php endif; ?>
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
                type: 'line',
                 {
                    labels: <?php echo json_encode(array_keys($meses_calificaciones)); ?>,
                    datasets: [{
                        label: 'Calificaciones Registradas',
                         <?php echo json_encode(array_values($meses_calificaciones)); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 3,
                        pointBackgroundColor: 'rgba(52, 152, 219, 1)',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.3
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