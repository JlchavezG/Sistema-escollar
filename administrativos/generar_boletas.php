<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$message = '';
$message_type = '';
$boleta_generada = null;
$alumnos_boleta = [];

// Obtener plantillas activas
$db->query("SELECT * FROM plantillas_boletas WHERE activa = 1 ORDER BY created_at DESC");
$plantillas = $db->resultSet();

// Obtener grados y grupos con alumnos
$db->query("SELECT DISTINCT grado, grupo FROM alumnos ORDER BY grado, grupo");
$grados_grupos = $db->resultSet();

// Obtener meses
$meses = getMonths();

// Generar boleta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generar_boleta'])) {
    $grado = sanitizeInput($_POST['grado']);
    $grupo = sanitizeInput($_POST['grupo']);
    $mes = sanitizeInput($_POST['mes']);
    $plantilla_id = intval($_POST['plantilla_id']);
    $ciclo_escolar = sanitizeInput($_POST['ciclo_escolar']);
    
    // Obtener plantilla seleccionada
    $db->query("SELECT * FROM plantillas_boletas WHERE id = :id");
    $db->bind(':id', $plantilla_id);
    $plantilla = $db->single();
    
    if (!$plantilla) {
        $message = 'Plantilla no encontrada';
        $message_type = 'danger';
    } else {
        // Obtener alumnos del grado y grupo
        $db->query("SELECT * FROM alumnos WHERE grado = :grado AND grupo = :grupo ORDER BY apellido_paterno, nombre");
        $db->bind(':grado', $grado);
        $db->bind(':grupo', $grupo);
        $alumnos = $db->resultSet();
        
        if (count($alumnos) == 0) {
            $message = 'No hay alumnos en este grado y grupo';
            $message_type = 'warning';
        } else {
            $alumnos_boleta = $alumnos;
            $boleta_generada = [
                'grado' => $grado,
                'grupo' => $grupo,
                'mes' => $mes,
                'ciclo_escolar' => $ciclo_escolar,
                'plantilla' => $plantilla,
                'alumnos' => $alumnos
            ];
            
            $message = 'Boleta generada exitosamente para ' . count($alumnos) . ' alumnos';
            $message_type = 'success';
        }
    }
}

// Obtener calificaciones para un alumno específico
function getCalificacionesAlumno($alumno_id, $grado, $grupo, $mes) {
    global $db;
    
    $db->query("SELECT c.*, m.nombre as modulo_nombre, m.clave as modulo_clave, u.nombre as profesor_nombre,
                u.apellido_paterno as profesor_ap
                FROM calificaciones c
                INNER JOIN modulos m ON c.modulo_id = m.id
                INNER JOIN usuarios u ON c.profesor_id = u.id
                WHERE c.alumno_id = :alumno_id 
                AND c.grado = :grado 
                AND c.grupo = :grupo 
                AND c.mes = :mes
                ORDER BY m.clave");
    $db->bind(':alumno_id', $alumno_id);
    $db->bind(':grado', $grado);
    $db->bind(':grupo', $grupo);
    $db->bind(':mes', $mes);
    
    return $db->resultSet();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Boletas - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .generator-card {
            background: var(--color-white);
            border-radius: var(--border-radius-xl);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-md);
        }
        
        .preview-container {
            background: var(--color-white);
            padding: var(--spacing-xxl);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-xl);
            max-height: 700px;
            overflow-y: auto;
        }
        
        .info-section {
            background: var(--color-gray-light);
            padding: var(--spacing-md);
            border-radius: var(--border-radius-md);
            margin-bottom: var(--spacing-md);
        }
        
        .grade-badge {
            display: inline-block;
            background: var(--primary-color);
            color: var(--color-white);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-full);
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-sm);
            margin: var(--spacing-xs);
        }
        
        .calificacion-aprobada {
            background: var(--success-color) !important;
        }
        
        .calificacion-reprobada {
            background: var(--danger-color) !important;
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
            <!-- Mensajes -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-file-alt me-3"></i>
                            Generar Boletas Mensuales
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Genera boletas de calificaciones para los alumnos por grado, grupo y mes
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Generator Form -->
            <div class="generator-card">
                <h4 class="mb-4"><i class="fas fa-cog me-2"></i>Configuración de Boleta</h4>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Grado <span class="text-danger">*</span></label>
                            <select class="form-select" name="grado" required>
                                <option value="">Seleccionar Grado...</option>
                                <?php foreach (getGrades() as $grade): ?>
                                    <option value="<?php echo $grade; ?>"><?php echo $grade; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Grupo <span class="text-danger">*</span></label>
                            <select class="form-select" name="grupo" required>
                                <option value="">Seleccionar Grupo...</option>
                                <?php foreach (getGroups() as $group): ?>
                                    <option value="<?php echo $group; ?>"><?php echo $group; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Mes <span class="text-danger">*</span></label>
                            <select class="form-select" name="mes" required>
                                <option value="">Seleccionar Mes...</option>
                                <?php foreach ($meses as $mes): ?>
                                    <option value="<?php echo $mes; ?>"><?php echo $mes; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Plantilla <span class="text-danger">*</span></label>
                            <select class="form-select" name="plantilla_id" required>
                                <option value="">Seleccionar Plantilla...</option>
                                <?php if (count($plantillas) > 0): ?>
                                    <?php foreach ($plantillas as $plantilla): ?>
                                        <option value="<?php echo $plantilla['id']; ?>">
                                            <?php echo htmlspecialchars($plantilla['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No hay plantillas activas</option>
                                <?php endif; ?>
                            </select>
                            <?php if (count($plantillas) == 0): ?>
                                <small class="text-danger">⚠️ No hay plantillas activas. Crea una en "Plantillas de Boletas"</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Ciclo Escolar <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ciclo_escolar" value="<?php echo CYCLE_ACTUAL; ?>" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" name="generar_boleta" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Generar Vista Previa
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if ($boleta_generada): ?>
                <!-- Preview Section -->
                <div class="preview-container" id="boletaPreview">
                    <style>
                        <?php echo $boleta_generada['plantilla']['css_personalizado']; ?>
                    </style>
                    
                    <?php
                    $html_template = $boleta_generada['plantilla']['contenido_html'];
                    
                    foreach ($boleta_generada['alumnos'] as $alumno):
                        // Reemplazar placeholders generales
                        $html = $html_template;
                        $html = str_replace('{{ciclo_escolar}}', $boleta_generada['ciclo_escolar'], $html);
                        $html = str_replace('{{nombre_alumno}}', $alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ' ' . $alumno['nombre'], $html);
                        $html = str_replace('{{grado}}', $boleta_generada['grado'], $html);
                        $html = str_replace('{{grupo}}', $boleta_generada['grupo'], $html);
                        $html = str_replace('{{mes}}', $boleta_generada['mes'], $html);
                        
                        // Obtener calificaciones del alumno
                        $calificaciones = getCalificacionesAlumno($alumno['id'], $boleta_generada['grado'], $boleta_generada['grupo'], $boleta_generada['mes']);
                        
                        // Generar filas de calificaciones
                        $rows_html = '';
                        if (count($calificaciones) > 0):
                            foreach ($calificaciones as $cal):
                                $calificacion_class = $cal['calificacion'] >= 60 ? 'calificacion-aprobada' : 'calificacion-reprobada';
                                $rows_html .= '
                                <tr>
                                    <td>' . htmlspecialchars($cal['modulo_nombre']) . '</td>
                                    <td><span class="grade-badge ' . $calificacion_class . '">' . number_format($cal['calificacion'], 1) . '</span></td>
                                    <td>' . htmlspecialchars($cal['observaciones'] ?: '-') . '</td>
                                </tr>';
                            endforeach;
                        else:
                            $rows_html = '<tr><td colspan="3" class="text-center text-muted">No hay calificaciones registradas para este mes</td></tr>';
                        endif;
                        
                        $html = str_replace('{{calificaciones_rows}}', $rows_html, $html);
                        
                        echo $html;
                        echo '<div style="page-break-after: always; margin: 30px 0;"></div>';
                    endforeach;
                    ?>
                </div>
                
                <!-- Actions -->
                <div class="generator-card">
                    <h5 class="mb-3"><i class="fas fa-tools me-2"></i>Acciones</h5>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print me-2"></i>Imprimir Boletas
                        </button>
                        <button class="btn btn-primary" onclick="downloadPDF()">
                            <i class="fas fa-download me-2"></i>Descargar PDF
                        </button>
                        <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                            <i class="fas fa-redo me-2"></i>Nueva Búsqueda
                        </button>
                    </div>
                </div>
                
                <!-- Alumnos Info -->
                <div class="info-section">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Información de la Boleta Generada</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Grado:</strong> <?php echo $boleta_generada['grado']; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Grupo:</strong> <?php echo $boleta_generada['grupo']; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Mes:</strong> <?php echo $boleta_generada['mes']; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Total Alumnos:</strong> <?php echo count($boleta_generada['alumnos']); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!$boleta_generada && count($plantillas) > 0): ?>
                <!-- Info Section -->
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Instrucciones</h5>
                    <ol class="mb-0 mt-2">
                        <li>Selecciona el grado y grupo para los que deseas generar boletas</li>
                        <li>Elige el mes del período evaluado</li>
                        <li>Selecciona una plantilla de boleta (crea una en "Plantillas de Boletas" si no hay)</li>
                        <li>Haz clic en "Generar Vista Previa" para ver las boletas antes de imprimir</li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('boletaPreview');
            const opt = {
                margin: 10,
                filename: 'boletas_<?php echo date('Y-m-d'); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Show loading
            const originalText = event.target.innerHTML;
            event.target.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando PDF...';
            event.target.disabled = true;
            
            html2pdf().set(opt).from(element).save().then(() => {
                event.target.innerHTML = originalText;
                event.target.disabled = false;
            });
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