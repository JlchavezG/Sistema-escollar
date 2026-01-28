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
            AND am.ciclo_escolar = :ciclo");
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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Profesor - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 0 25px 25px 0;
            margin-right: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            margin-right: 0;
            border-radius: 0 25px 25px 0;
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stats-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            border: none;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .stats-card .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }

        .stats-card .card-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        .module-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            transition: all 0.3s;
            margin-bottom: 20px;
            background: white;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .module-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-action {
            flex: 1;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .btn-action i {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
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
            <p class="text-white-50 small">Profesor Panel</p>
        </div>

        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cargar_calificaciones.php">
                        <i class="fas fa-edit"></i> Cargar Calificaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ver_modulos.php">
                        <i class="fas fa-book"></i> Mis Módulos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="perfil.php">
                        <i class="fas fa-user"></i> Mi Perfil
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
                                <li><a class="dropdown-item" href="perfil.php">
                                        <i class="fas fa-user me-2"></i> Mi Perfil
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
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
                    <div class="card stats-card border-0">
                        <div class="card-icon bg-primary text-white">
                            <i class="fas fa-book-open fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title">Módulos Asignados</div>
                            <div class="card-value"><?php echo $stats['modulos_asignados'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0">
                        <div class="card-icon bg-success text-white">
                            <i class="fas fa-user-graduate fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title">Alumnos Totales</div>
                            <div class="card-value"><?php echo $total_alumnos; ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0">
                        <div class="card-icon bg-info text-white">
                            <i class="fas fa-pen-alt fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title">Calificaciones este Mes</div>
                            <div class="card-value"><?php echo $calificaciones_mes; ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card stats-card border-0">
                        <div class="card-icon bg-warning text-white">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <div class="card-title">Promedio General</div>
                            <div class="card-value"><?php echo number_format($promedio_general ?? 0, 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="cargar_calificaciones.php" class="btn btn-primary btn-action">
                                    <i class="fas fa-edit"></i>
                                    <span>Cargar Calificaciones</span>
                                </a>
                                <a href="ver_modulos.php" class="btn btn-success btn-action">
                                    <i class="fas fa-book"></i>
                                    <span>Ver Mis Módulos</span>
                                </a>
                                <a href="perfil.php" class="btn btn-info btn-action">
                                    <i class="fas fa-user"></i>
                                    <span>Mi Perfil</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Modules -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Módulos Asignados</h5>
                            <span class="badge bg-primary"><?php echo count($modulos_asignados); ?> módulos</span>
                        </div>
                        <div class="card-body">
                            <?php if (count($modulos_asignados) > 0): ?>
                                <div class="row">
                                    <?php foreach ($modulos_asignados as $modulo): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card module-card">
                                                <div class="module-header">
                                                    <h6 class="mb-1"><?php echo $modulo['modulo_nombre']; ?></h6>
                                                    <small class="text-white-50"><?php echo $modulo['modulo_clave']; ?></small>
                                                </div>
                                                <div class="card-body">
                                                    <p class="mb-2"><strong>Grado:</strong> <?php echo $modulo['grado']; ?></p>
                                                    <p class="mb-2"><strong>Grupo:</strong> <?php echo $modulo['grupo']; ?></p>
                                                    <p class="mb-3"><strong>Ciclo:</strong> <?php echo $modulo['ciclo_escolar']; ?></p>
                                                    <a href="cargar_calificaciones.php?modulo=<?php echo $modulo['modulo_id']; ?>&grado=<?php echo $modulo['grado']; ?>&grupo=<?php echo $modulo['grupo']; ?>"
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
                                    No tienes módulos asignados actualmente.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>

</html>