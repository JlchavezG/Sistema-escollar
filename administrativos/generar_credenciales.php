<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$db = new Database();

// Obtener plantillas activas
$db->query("SELECT id, nombre FROM plantillas_credenciales WHERE activa = 1 ORDER BY nombre");
$plantillas = $db->resultSet();

// Obtener plantilla seleccionada
$plantilla_id = isset($_GET['plantilla']) ? intval($_GET['plantilla']) : 0;
$plantilla_seleccionada = null;
if ($plantilla_id > 0) {
    $db->query("SELECT * FROM plantillas_credenciales WHERE id = :id");
    $db->bind(':id', $plantilla_id);
    $plantilla_seleccionada = $db->single();
}

// Obtener alumnos (con filtros)
$grado = isset($_GET['grado']) ? sanitizeInput($_GET['grado']) : '';
$grupo = isset($_GET['grupo']) ? sanitizeInput($_GET['grupo']) : '';

$query = "SELECT * FROM alumnos WHERE 1=1";
$params = [];

if ($grado) {
    $query .= " AND grado = :grado";
    $params[':grado'] = $grado;
}
if ($grupo) {
    $query .= " AND grupo = :grupo";
    $params[':grupo'] = $grupo;
}
$query .= " ORDER BY apellido_paterno, nombre";

$db->query($query);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$alumnos = $db->resultSet();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Credenciales - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="../assets/js/sidebar-toggle.js" defer></script>
    <style>
        .preview-credencial {
            width: 350px;
            height: 220px;
            border: 2px solid #2c3e50;
            border-radius: 10px;
            padding: 15px;
            background: white;
            margin: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            float: left;
            font-family: Arial, sans-serif;
        }
        .print-container {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            margin: 0 auto;
            background: white;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .print-container, .print-container * {
                visibility: visible;
            }
            .print-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
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
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_credenciales.php">
                                <i class="fas fa-arrow-left me-2"></i>Volver a Credenciales
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <div class="container-fluid p-4">
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-print me-3"></i>
                            Generar Credenciales Estudiantiles
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Selecciona plantilla, filtra alumnos y genera credenciales para imprimir
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Filtros y Selección -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros y Configuración</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Plantilla de Credencial <span class="text-danger">*</span></label>
                                <select class="form-select" name="plantilla" required>
                                    <option value="">Seleccionar plantilla...</option>
                                    <?php foreach ($plantillas as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo ($plantilla_id == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Grado</label>
                                <select class="form-select" name="grado">
                                    <option value="">Todos los grados</option>
                                    <?php foreach (getGrades() as $grade): ?>
                                        <option value="<?php echo $grade; ?>" <?php echo ($grado == $grade) ? 'selected' : ''; ?>>
                                            <?php echo $grade; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold">Grupo</label>
                                <select class="form-select" name="grupo">
                                    <option value="">Todos</option>
                                    <?php foreach (getGroups() as $group): ?>
                                        <option value="<?php echo $group; ?>" <?php echo ($grupo == $group) ? 'selected' : ''; ?>>
                                            <?php echo $group; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Filtrar Alumnos
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($plantilla_seleccionada && count($alumnos) > 0): ?>
            <!-- Vista Previa -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Vista Previa (Primera Credencial)</h5>
                </div>
                <div class="card-body text-center">
                    <div class="preview-credencial">
                        <?php
                        // Reemplazar placeholders con datos del primer alumno
                        $preview_html = $plantilla_seleccionada['contenido_html'];
                        $preview_html = str_replace('{{logo_escuela}}', '<img src="' . ($config['logo_sistema'] ?? 'image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'40\'%3E%3Crect width=\'100\' height=\'40\' fill=\'%232c3e50\'/%3E%3C/svg%3E') . '" alt="Logo" style="max-height: 30px;">', $preview_html);
                        $preview_html = str_replace('{{nombre_escuela}}', $config['nombre_institucion'] ?? 'Sistema Escolar', $preview_html);
                        $preview_html = str_replace('{{ciclo_escolar}}', $config['ciclo_escolar'] ?? date('Y') . '-' . (date('Y')+1), $preview_html);
                        $preview_html = str_replace('{{nombre_alumno}}', htmlspecialchars($alumnos[0]['apellido_paterno'] . ' ' . $alumnos[0]['nombre']), $preview_html);
                        $preview_html = str_replace('{{grado}}', htmlspecialchars($alumnos[0]['grado']), $preview_html);
                        $preview_html = str_replace('{{grupo}}', htmlspecialchars($alumnos[0]['grupo']), $preview_html);
                        $preview_html = str_replace('{{matricula}}', 'MAT-' . str_pad($alumnos[0]['id'], 6, '0', STR_PAD_LEFT), $preview_html);
                        $preview_html = str_replace('{{foto_alumno}}', '<div style="padding-top: 35px; color: #999; font-size: 10px;">FOTO</div>', $preview_html);
                        $preview_html = str_replace('{{codigo_barra}}', '<div style="padding-top: 10px; color: #999; font-size: 8px;">CÓDIGO: ' . $alumnos[0]['id'] . '</div>', $preview_html);
                        echo $preview_html;
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Generación -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Alumnos Seleccionados (<?php echo count($alumnos); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Nota:</strong> Se generarán <?php echo count($alumnos); ?> credenciales con la plantilla seleccionada. 
                        Cada hoja A4 contendrá 8 credenciales (4x2).
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button onclick="window.print()" class="btn btn-success btn-lg">
                            <i class="fas fa-print me-2"></i>Imprimir Credenciales
                        </button>
                        <button onclick="downloadPDF()" class="btn btn-primary btn-lg">
                            <i class="fas fa-download me-2"></i>Descargar PDF
                        </button>
                    </div>
                    
                    <!-- Contenedor de impresión (oculto hasta imprimir) -->
                    <div class="print-container d-none">
                        <?php foreach ($alumnos as $index => $alumno): ?>
                            <?php if ($index > 0 && $index % 8 == 0): ?>
                                <div style="page-break-after: always;"></div>
                            <?php endif; ?>
                            <div class="preview-credencial">
                                <?php
                                $cred_html = $plantilla_seleccionada['contenido_html'];
                                $cred_html = str_replace('{{logo_escuela}}', '<img src="' . ($config['logo_sistema'] ?? '') . '" alt="Logo" style="max-height: 30px;">', $cred_html);
                                $cred_html = str_replace('{{nombre_escuela}}', $config['nombre_institucion'] ?? 'Sistema Escolar', $cred_html);
                                $cred_html = str_replace('{{ciclo_escolar}}', $config['ciclo_escolar'] ?? date('Y') . '-' . (date('Y')+1), $cred_html);
                                $cred_html = str_replace('{{nombre_alumno}}', htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['nombre']), $cred_html);
                                $cred_html = str_replace('{{grado}}', htmlspecialchars($alumno['grado']), $cred_html);
                                $cred_html = str_replace('{{grupo}}', htmlspecialchars($alumno['grupo']), $cred_html);
                                $cred_html = str_replace('{{matricula}}', 'MAT-' . str_pad($alumno['id'], 6, '0', STR_PAD_LEFT), $cred_html);
                                $cred_html = str_replace('{{foto_alumno}}', '<div style="padding-top: 35px; color: #999; font-size: 10px;">FOTO</div>', $cred_html);
                                $cred_html = str_replace('{{codigo_barra}}', '<div style="padding-top: 10px; color: #999; font-size: 8px;">CÓDIGO: ' . $alumno['id'] . '</div>', $cred_html);
                                echo $cred_html;
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php elseif ($plantilla_id > 0): ?>
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <h5>No se encontraron alumnos con los filtros seleccionados</h5>
                <p class="mb-0">Intenta ajustar los filtros de grado y grupo</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.querySelector('.print-container');
            const opt = {
                margin: 5,
                filename: 'credenciales_' + new Date().toISOString().slice(0,10) + '.pdf',
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