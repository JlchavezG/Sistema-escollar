<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireProfesor();

$db = new Database();

// Obtener módulos asignados
$db->query("SELECT am.*, m.nombre as modulo_nombre, m.clave as modulo_clave, 
            COUNT(DISTINCT c.alumno_id) as alumnos_con_calificaciones,
            COUNT(DISTINCT a.id) as total_alumnos
            FROM asignacion_modulos am
            INNER JOIN modulos m ON am.modulo_id = m.id
            LEFT JOIN alumnos a ON a.grado = am.grado AND a.grupo = am.grupo
            LEFT JOIN calificaciones c ON c.modulo_id = am.modulo_id 
                AND c.profesor_id = am.profesor_id 
                AND c.grado = am.grado 
                AND c.grupo = am.grupo
            WHERE am.profesor_id = :profesor_id
            AND am.ciclo_escolar = :ciclo
            GROUP BY am.id, m.id
            ORDER BY am.grado, am.grupo, m.nombre");
$db->bind(':profesor_id', $_SESSION['user_id']);
$db->bind(':ciclo', CYCLE_ACTUAL);
$modulos_asignados = $db->resultSet();

// Estadísticas generales
$total_modulos = count($modulos_asignados);

$db->query("SELECT COUNT(DISTINCT a.id) as total 
            FROM alumnos a
            INNER JOIN asignacion_modulos am ON a.grado = am.grado AND a.grupo = am.grupo
            WHERE am.profesor_id = :profesor_id
            AND am.ciclo_escolar = :ciclo");
$db->bind(':profesor_id', $_SESSION['user_id']);
$db->bind(':ciclo', CYCLE_ACTUAL);
$total_alumnos_distintos = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM calificaciones WHERE profesor_id = :profesor_id");
$db->bind(':profesor_id', $_SESSION['user_id']);
$total_calificaciones = $db->single()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Módulos - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
     <!-- Sidebar Toggle JS -->
    <script src="../assets/js/sidebar-toggle.js" defer></script>
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
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-book me-3"></i>
                            Mis Módulos Asignados
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Lista de módulos/materias que tienes asignados para este ciclo escolar
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-item">
                    <i class="fas fa-book-open fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $total_modulos; ?></div>
                    <div class="stat-label">Módulos Asignados</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-user-graduate fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $total_alumnos_distintos; ?></div>
                    <div class="stat-label">Alumnos Totales</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-edit fa-2x text-success"></i>
                    <div class="stat-number"><?php echo $total_calificaciones; ?></div>
                    <div class="stat-label">Calificaciones Cargadas</div>
                </div>
            </div>
            
            <!-- Modules List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Módulos</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($modulos_asignados) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Módulo</th>
                                        <th>Grado - Grupo</th>
                                        <th>Ciclo Escolar</th>
                                        <th>Alumnos</th>
                                        <th>Progreso</th>
                                        <th style="width: 150px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($modulos_asignados as $modulo): ?>
                                        <tr>
                                            <td><?php echo $contador++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($modulo['modulo_nombre']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($modulo['modulo_clave']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($modulo['grado']); ?>
                                                </span>
                                                <span class="badge bg-success ms-1">
                                                    <?php echo htmlspecialchars($modulo['grupo']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($modulo['ciclo_escolar']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $modulo['total_alumnos']; ?> total
                                                </span>
                                                <?php if ($modulo['alumnos_con_calificaciones'] > 0): ?>
                                                    <span class="badge bg-success ms-1">
                                                        <?php echo $modulo['alumnos_con_calificaciones']; ?> con calif.
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $progreso = $modulo['total_alumnos'] > 0 ? 
                                                    round(($modulo['alumnos_con_calificaciones'] / $modulo['total_alumnos']) * 100) : 0;
                                                ?>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar <?php echo $progreso > 50 ? 'bg-success' : 'bg-warning'; ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $progreso; ?>%" 
                                                         aria-valuenow="<?php echo $progreso; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <small class="text-muted mt-1 d-block"><?php echo $progreso; ?>% completado</small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="cargar_calificaciones.php?modulo=<?php echo $modulo['modulo_id']; ?>&grado=<?php echo urlencode($modulo['grado']); ?>&grupo=<?php echo $modulo['grupo']; ?>" 
                                                       class="btn btn-sm btn-primary" title="Cargar Calificaciones">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-info" 
                                                            onclick="showModuleDetails(<?php echo htmlspecialchars(json_encode($modulo)); ?>)" 
                                                            title="Ver Detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-book fa-3x mb-3"></i>
                            <p>No tienes módulos asignados actualmente</p>
                            <p class="small">Contacta al administrador para que te asigne módulos</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Detalles del Módulo -->
    <div class="modal fade" id="moduleDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-book me-2"></i>Detalles del Módulo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="moduleDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        function showModuleDetails(modulo) {
            const content = `
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Nombre del Módulo:</strong>
                    </div>
                    <div class="col-6 text-muted">${modulo.modulo_nombre}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Clave:</strong>
                    </div>
                    <div class="col-6 text-muted">${modulo.modulo_clave}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Grado:</strong>
                    </div>
                    <div class="col-6 text-muted">${modulo.grado}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Grupo:</strong>
                    </div>
                    <div class="col-6 text-muted">${modulo.grupo}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Ciclo Escolar:</strong>
                    </div>
                    <div class="col-6 text-muted">${modulo.ciclo_escolar}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Total de Alumnos:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-info">${modulo.total_alumnos}</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>Alumnos con Calificaciones:</strong>
                    </div>
                    <div class="col-6">
                        <span class="badge bg-success">${modulo.alumnos_con_calificaciones}</span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <strong>Progreso:</strong>
                    </div>
                    <div class="col-6">
                        ${Math.round((modulo.alumnos_con_calificaciones / modulo.total_alumnos) * 100) || 0}%
                    </div>
                </div>
            `;
            
            document.getElementById('moduleDetailsContent').innerHTML = content;
            const modal = new bootstrap.Modal(document.getElementById('moduleDetailsModal'));
            modal.show();
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