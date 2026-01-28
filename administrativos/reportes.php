<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$db = new Database();

// Reporte: Calificaciones por módulo
$db->query("SELECT m.nombre as modulo, m.clave, COUNT(c.id) as total_calificaciones, 
            AVG(c.calificacion) as promedio, MAX(c.calificacion) as maxima, MIN(c.calificacion) as minima
            FROM calificaciones c
            INNER JOIN modulos m ON c.modulo_id = m.id
            GROUP BY m.id, m.nombre, m.clave
            ORDER BY m.clave");
$reporte_modulos = $db->resultSet();

// Reporte: Calificaciones por profesor
$db->query("SELECT u.nombre, u.apellido_paterno, u.apellido_materno, 
            COUNT(c.id) as total_calificaciones, AVG(c.calificacion) as promedio
            FROM calificaciones c
            INNER JOIN usuarios u ON c.profesor_id = u.id
            GROUP BY u.id, u.nombre, u.apellido_paterno, u.apellido_materno
            ORDER BY u.apellido_paterno");
$reporte_profesores = $db->resultSet();

// Reporte: Calificaciones por grado
$db->query("SELECT grado, COUNT(*) as total_calificaciones, AVG(calificacion) as promedio
            FROM calificaciones
            GROUP BY grado
            ORDER BY grado");
$reporte_grados = $db->resultSet();

// Reporte: Calificaciones por mes
$db->query("SELECT mes, COUNT(*) as total_calificaciones, AVG(calificacion) as promedio
            FROM calificaciones
            GROUP BY mes
            ORDER BY FIELD(mes, 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                           'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre')");
$reporte_meses = $db->resultSet();

// Reporte: Alumnos por grado/grupo
$db->query("SELECT grado, grupo, COUNT(*) as total_alumnos
            FROM alumnos
            GROUP BY grado, grupo
            ORDER BY grado, grupo");
$reporte_alumnos_grupos = $db->resultSet();

// Estadísticas generales
$db->query("SELECT COUNT(*) as total FROM calificaciones");
$total_calificaciones = $db->single()['total'];

$db->query("SELECT AVG(calificacion) as promedio_general FROM calificaciones");
$promedio_general_result = $db->single();
$promedio_general = $promedio_general_result['promedio_general'] ?? 0;

$db->query("SELECT COUNT(DISTINCT alumno_id) as total FROM calificaciones");
$alumnos_con_calificaciones = $db->single()['total'];

$db->query("SELECT COUNT(DISTINCT profesor_id) as total FROM calificaciones");
$profesores_con_calificaciones = $db->single()['total'];

// Función helper para formatear números seguros
function safeNumberFormat($number, $decimals = 2) {
    if ($number === null || $number === '') {
        return number_format(0, $decimals);
    }
    return number_format($number, $decimals);
}

// Función helper para obtener valor seguro
function safeValue($value, $default = 0) {
    return $value !== null && $value !== '' ? $value : $default;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-card {
            background: var(--color-white);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-md);
        }
        
        .chart-container {
            background: var(--color-white);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-md);
        }
        
        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
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
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-chart-bar me-3"></i>
                            Reportes y Estadísticas
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Análisis detallado de calificaciones y rendimiento escolar
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Mensaje informativo si no hay datos -->
            <?php if ($total_calificaciones == 0): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading">⚠️ No hay datos para mostrar</h5>
                            <p class="mb-0">Aún no se han registrado calificaciones en el sistema. Para generar reportes necesitas:</p>
                            <ol class="mb-0 mt-2">
                                <li>Registrar alumnos en "Gestión de Alumnos"</li>
                                <li>Crear módulos en "Gestión de Módulos"</li>
                                <li>Asignar módulos a profesores en "Asignar Módulos"</li>
                                <li>Los profesores deben cargar calificaciones</li>
                            </ol>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Estadísticas Generales -->
            <div class="stats-grid mb-4">
                <div class="stat-item">
                    <i class="fas fa-edit fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $total_calificaciones; ?></div>
                    <div class="stat-label">Total Calificaciones</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-chart-line fa-2x text-success"></i>
                    <div class="stat-number"><?php echo safeNumberFormat($promedio_general); ?></div>
                    <div class="stat-label">Promedio General</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-user-graduate fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $alumnos_con_calificaciones; ?></div>
                    <div class="stat-label">Alumnos Evaluados</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-chalkboard-teacher fa-2x text-warning"></i>
                    <div class="stat-number"><?php echo $profesores_con_calificaciones; ?></div>
                    <div class="stat-label">Profesores Activos</div>
                </div>
            </div>
            
            <!-- Charts - Solo mostrar si hay datos -->
            <?php if ($total_calificaciones > 0): ?>
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fas fa-chart-pie me-2"></i>Calificaciones por Módulo</h5>
                        <canvas id="modulosChart" height="250"></canvas>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fas fa-chart-bar me-2"></i>Calificaciones por Mes</h5>
                        <canvas id="mesesChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fas fa-layer-group me-2"></i>Alumnos por Grado</h5>
                        <canvas id="gradosChart" height="250"></canvas>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fas fa-chart-line me-2"></i>Promedio por Grado</h5>
                        <canvas id="promedioGradosChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Reporte: Calificaciones por Módulo -->
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0"><i class="fas fa-book me-2"></i>Calificaciones por Módulo</h4>
                    <button class="btn btn-sm btn-primary" onclick="exportTable('modulosTable')">
                        <i class="fas fa-download me-1"></i>Exportar
                    </button>
                </div>
                <div class="table-container">
                    <?php if (count($reporte_modulos) > 0): ?>
                        <table class="table table-hover table-sm" id="modulosTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Clave</th>
                                    <th>Módulo</th>
                                    <th>Total Calificaciones</th>
                                    <th>Promedio</th>
                                    <th>Máxima</th>
                                    <th>Mínima</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reporte_modulos as $modulo): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($modulo['clave']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($modulo['modulo']); ?></td>
                                        <td><?php echo safeValue($modulo['total_calificaciones'], 0); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo safeNumberFormat($modulo['promedio']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?php echo safeNumberFormat($modulo['maxima']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">
                                                <?php echo safeNumberFormat($modulo['minima']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay datos de calificaciones por módulo</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reporte: Calificaciones por Profesor -->
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Calificaciones por Profesor</h4>
                    <button class="btn btn-sm btn-primary" onclick="exportTable('profesoresTable')">
                        <i class="fas fa-download me-1"></i>Exportar
                    </button>
                </div>
                <div class="table-container">
                    <?php if (count($reporte_profesores) > 0): ?>
                        <table class="table table-hover table-sm" id="profesoresTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Profesor</th>
                                    <th>Total Calificaciones</th>
                                    <th>Promedio General</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reporte_profesores as $profesor): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($profesor['apellido_paterno'] . ' ' . $profesor['apellido_materno']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($profesor['nombre']); ?></small>
                                        </td>
                                        <td><?php echo safeValue($profesor['total_calificaciones'], 0); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo safeNumberFormat($profesor['promedio']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay datos de calificaciones por profesor</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reporte: Alumnos por Grado/Grupo -->
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Alumnos por Grado y Grupo</h4>
                    <button class="btn btn-sm btn-primary" onclick="exportTable('alumnosTable')">
                        <i class="fas fa-download me-1"></i>Exportar
                    </button>
                </div>
                <div class="table-container">
                    <?php if (count($reporte_alumnos_grupos) > 0): ?>
                        <table class="table table-hover table-sm" id="alumnosTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Grado</th>
                                    <th>Grupo</th>
                                    <th>Total Alumnos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reporte_alumnos_grupos as $grupo): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($grupo['grado']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($grupo['grupo']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo safeValue($grupo['total_alumnos'], 0); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay datos de alumnos por grado y grupo</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        <?php if ($total_calificaciones > 0): ?>
        // Chart.js - Calificaciones por Módulo
        const ctxModulos = document.getElementById('modulosChart').getContext('2d');
        new Chart(ctxModulos, {
            type: 'doughnut',
             {
                labels: <?php echo json_encode(array_column($reporte_modulos, 'clave')); ?>,
                datasets: [{
                     <?php echo json_encode(array_map(function($v) { return $v ?: 0; }, array_column($reporte_modulos, 'total_calificaciones'))); ?>,
                    backgroundColor: [
                        '#3498db', '#2c3e50', '#f39c12', '#e74c3c', '#27ae60',
                        '#16a085', '#8e44ad', '#2980b9', '#d35400', '#c0392b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Chart.js - Calificaciones por Mes
        const ctxMeses = document.getElementById('mesesChart').getContext('2d');
        new Chart(ctxMeses, {
            type: 'bar',
             {
                labels: <?php echo json_encode(array_column($reporte_meses, 'mes')); ?>,
                datasets: [{
                    label: 'Calificaciones Registradas',
                     <?php echo json_encode(array_map(function($v) { return $v ?: 0; }, array_column($reporte_meses, 'total_calificaciones'))); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 2,
                    borderRadius: 8
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
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Chart.js - Alumnos por Grado
        const ctxGrados = document.getElementById('gradosChart').getContext('2d');
        new Chart(ctxGrados, {
            type: 'pie',
             {
                labels: <?php echo json_encode(array_column($reporte_grados, 'grado')); ?>,
                datasets: [{
                     <?php echo json_encode(array_map(function($v) { return $v ?: 0; }, array_column($reporte_grados, 'total_calificaciones'))); ?>,
                    backgroundColor: [
                        '#3498db', '#2c3e50', '#f39c12', '#e74c3c', '#27ae60',
                        '#16a085', '#8e44ad', '#2980b9', '#d35400', '#c0392b',
                        '#2ecc71', '#3498db', '#9b59b6', '#34495e', '#1abc9c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Chart.js - Promedio por Grado
        const ctxPromedioGrados = document.getElementById('promedioGradosChart').getContext('2d');
        new Chart(ctxPromedioGrados, {
            type: 'line',
             {
                labels: <?php echo json_encode(array_column($reporte_grados, 'grado')); ?>,
                datasets: [{
                    label: 'Promedio de Calificaciones',
                     <?php echo json_encode(array_map(function($v) { return $v !== null ? parseFloat($v) : 0; }, array_column($reporte_grados, 'promedio'))); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 3,
                    pointBackgroundColor: 'rgba(52, 152, 219, 1)',
                    pointRadius: 5,
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
                        beginAtZero: false,
                        min: 0,
                        max: 100
                    }
                }
            }
        });
        <?php endif; ?>
        
        // Export table to CSV
        function exportTable(tableId) {
            const table = document.getElementById(tableId);
            if (!table) {
                alert('No hay datos para exportar');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    row.push(cols[j].innerText);
                }
                
                csv.push(row.join(','));
            }
            
            downloadCSV(csv.join('\n'), tableId + '.csv');
        }
        
        function downloadCSV(csv, filename) {
            const csvFile = new Blob([csv], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
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