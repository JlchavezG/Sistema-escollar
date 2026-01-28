<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$message = '';
$message_type = '';

// Manejar acciones CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'create' || $action == 'update') {
            $nombre = sanitizeInput($_POST['nombre']);
            $contenido_html = $_POST['contenido_html'];
            $css_personalizado = $_POST['css_personalizado'];
            
            try {
                if ($action == 'create') {
                    $db->query("INSERT INTO plantillas_boletas (nombre, contenido_html, css_personalizado, activa, created_by, created_at) 
                                VALUES (:nombre, :contenido_html, :css_personalizado, 1, :created_by, NOW())");
                    $db->bind(':created_by', $_SESSION['user_id']);
                } else {
                    $id = intval($_POST['id']);
                    $db->query("UPDATE plantillas_boletas SET nombre = :nombre, contenido_html = :contenido_html, 
                                css_personalizado = :css_personalizado WHERE id = :id");
                    $db->bind(':id', $id);
                }
                
                $db->bind(':nombre', $nombre);
                $db->bind(':contenido_html', $contenido_html);
                $db->bind(':css_personalizado', $css_personalizado);
                
                if ($db->execute()) {
                    $message = $action == 'create' ? 'Plantilla creada exitosamente' : 'Plantilla actualizada exitosamente';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } elseif ($action == 'delete') {
            $id = intval($_POST['id']);
            
            $db->query("DELETE FROM plantillas_boletas WHERE id = :id");
            $db->bind(':id', $id);
            
            if ($db->execute()) {
                $message = 'Plantilla eliminada exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al eliminar plantilla';
                $message_type = 'danger';
            }
        } elseif ($action == 'toggle_active') {
            $id = intval($_POST['id']);
            
            $db->query("SELECT activa FROM plantillas_boletas WHERE id = :id");
            $db->bind(':id', $id);
            $current = $db->single();
            
            $new_active = $current['activa'] == 1 ? 0 : 1;
            
            $db->query("UPDATE plantillas_boletas SET activa = :activa WHERE id = :id");
            $db->bind(':activa', $new_active);
            $db->bind(':id', $id);
            
            if ($db->execute()) {
                $message = 'Estado de plantilla actualizado';
                $message_type = 'success';
            }
        }
    }
}

// Obtener todas las plantillas
$db->query("SELECT pb.*, u.nombre as creador_nombre, u.apellido_paterno as creador_ap
            FROM plantillas_boletas pb
            LEFT JOIN usuarios u ON pb.created_by = u.id
            ORDER BY pb.created_at DESC");
$plantillas = $db->resultSet();

// Obtener plantilla para editar
$edit_plantilla = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $db->query("SELECT * FROM plantillas_boletas WHERE id = :id");
    $db->bind(':id', $id);
    $edit_plantilla = $db->single();
}

// Plantilla por defecto para preview
$default_template = '
<div class="boleta-container">
    <div class="header">
        <h2>BOLETA DE CALIFICACIONES</h2>
        <p>Ciclo Escolar: {{ciclo_escolar}}</p>
    </div>
    
    <div class="student-info">
        <div class="info-row">
            <span class="label">Nombre del Alumno:</span>
            <span class="value">{{nombre_alumno}}</span>
        </div>
        <div class="info-row">
            <span class="label">Grado y Grupo:</span>
            <span class="value">{{grado}} - {{grupo}}</span>
        </div>
        <div class="info-row">
            <span class="label">Mes:</span>
            <span class="value">{{mes}}</span>
        </div>
    </div>
    
    <div class="grades-table">
        <table>
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Calificación</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                {{calificaciones_rows}}
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <div class="signature">
            <p>_________________________</p>
            <p>Profesor(a)</p>
        </div>
        <div class="signature">
            <p>_________________________</p>
            <p>Padre/Madre/Tutor</p>
        </div>
    </div>
</div>
';

$default_css = '
.boleta-container {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
    border: 3px solid var(--primary-color);
    border-radius: var(--border-radius-xl);
    background: var(--color-white);
}

.header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--primary-color);
}

.header h2 {
    color: var(--primary-color);
    margin-bottom: 10px;
    font-size: var(--font-size-xxl);
}

.header p {
    color: var(--color-text-secondary);
    margin: 0;
}

.student-info {
    background: var(--color-gray-light);
    padding: var(--spacing-md);
    border-radius: var(--border-radius-md);
    margin-bottom: var(--spacing-md);
}

.info-row {
    display: flex;
    margin-bottom: var(--spacing-xs);
}

.info-row .label {
    font-weight: var(--font-weight-bold);
    color: var(--text-color);
    width: 200px;
}

.info-row .value {
    color: var(--color-text-secondary);
}

.grades-table {
    margin-bottom: var(--spacing-xl);
}

.grades-table table {
    width: 100%;
    border-collapse: collapse;
}

.grades-table th {
    background: var(--primary-color);
    color: var(--color-white);
    padding: var(--spacing-sm);
    text-align: left;
    font-weight: var(--font-weight-semibold);
}

.grades-table td {
    padding: var(--spacing-sm);
    border-bottom: 1px solid var(--border-color);
}

.grades-table tr:last-child td {
    border-bottom: none;
}

.grades-table tr:hover {
    background: var(--color-gray-light);
}

.footer {
    display: flex;
    justify-content: space-between;
    margin-top: var(--spacing-xl);
    padding-top: var(--spacing-md);
    border-top: 2px solid var(--primary-color);
}

.signature {
    text-align: center;
}

.signature p:first-child {
    margin-bottom: var(--spacing-md);
    font-weight: var(--font-weight-bold);
}

.signature p:last-child {
    color: var(--color-text-secondary);
    font-size: var(--font-size-sm);
}
';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantillas de Boletas - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/theme/monokai.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .CodeMirror {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-md);
            height: 400px;
            margin-bottom: var(--spacing-md);
        }
        
        .preview-container {
            background: var(--color-white);
            padding: var(--spacing-xl);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            margin-top: var(--spacing-md);
            max-height: 600px;
            overflow-y: auto;
        }
        
        .modal-xl {
            max-width: 95%;
        }
        
        .examples-list {
            max-height: 300px;
            overflow-y: auto;
            padding: var(--spacing-md);
            background: var(--color-gray-light);
            border-radius: var(--border-radius-md);
            margin-top: var(--spacing-md);
        }
        
        .examples-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .examples-list li {
            padding: var(--spacing-xs) 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .examples-list li:last-child {
            border-bottom: none;
        }
        
        .examples-list .clave {
            font-weight: var(--font-weight-bold);
            color: var(--primary-color);
            min-width: 60px;
            display: inline-block;
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
                            <i class="fas fa-palette me-3"></i>
                            Plantillas de Boletas
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Crea y edita plantillas personalizadas para las boletas de calificaciones
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal">
                            <i class="fas fa-plus me-2"></i>Nueva Plantilla
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Templates List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Mis Plantillas</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Nombre</th>
                                    <th>Creador</th>
                                    <th>Fecha Creación</th>
                                    <th>Estado</th>
                                    <th style="width: 200px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($plantillas) > 0): ?>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($plantillas as $plantilla): ?>
                                        <tr>
                                            <td><?php echo $contador++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($plantilla['nombre']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($plantilla['created_by']): ?>
                                                    <small><?php echo htmlspecialchars($plantilla['creador_ap'] . ' ' . $plantilla['creador_nombre']); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Sistema</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($plantilla['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($plantilla['activa']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Activa
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times-circle me-1"></i>Inactiva
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#templateModal"
                                                            onclick='editTemplate(<?php echo json_encode($plantilla); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick='previewTemplate(<?php echo json_encode($plantilla['contenido_html']); ?>, <?php echo json_encode($plantilla['css_personalizado']); ?>)'>
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            onclick='toggleActive(<?php echo $plantilla['id']; ?>)'>
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick='deleteTemplate(<?php echo $plantilla['id']; ?>, "<?php echo htmlspecialchars($plantilla['nombre']); ?>")'>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-palette fa-3x mb-3"></i>
                                                <p>No hay plantillas creadas</p>
                                                <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#templateModal">
                                                    <i class="fas fa-plus me-2"></i>Crear Primera Plantilla
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Crear/Editar Plantilla -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" id="modal_action" value="create">
                    <input type="hidden" name="id" id="template_id">
                    
                    <div class="modal-header gradient-primary">
                        <h5 class="modal-title text-white" id="modal_title">
                            <i class="fas fa-palette me-2"></i>Nueva Plantilla de Boleta
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nombre de la Plantilla <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" id="nombre" 
                                       placeholder="Ej: Boleta Primaria Moderna" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Contenido HTML</label>
                            <textarea class="form-control d-none" name="contenido_html" id="contenido_html" rows="15"><?php echo htmlspecialchars($default_template); ?></textarea>
                            <div id="html_editor"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">CSS Personalizado (Opcional)</label>
                            <textarea class="form-control d-none" name="css_personalizado" id="css_personalizado" rows="10"><?php echo htmlspecialchars($default_css); ?></textarea>
                            <div id="css_editor"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Vista Previa</label>
                            <div class="preview-container" id="preview_container">
                                <!-- Preview will be loaded here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-info" onclick="updatePreview()">
                            <i class="fas fa-eye me-2"></i>Actualizar Vista Previa
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><span id="btn_save_text">Guardar Plantilla</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal: Preview Template -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Vista Previa de Plantilla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="preview_modal_content" style="background: var(--color-white); padding: var(--spacing-xl);"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/lib/codemirror.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/htmlmixed/htmlmixed.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/css/css.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.2/mode/javascript/javascript.js"></script>
    <script>
        // Initialize CodeMirror editors
        var htmlEditor = CodeMirror.fromTextArea(document.getElementById('contenido_html'), {
            mode: 'htmlmixed',
            theme: 'monokai',
            lineNumbers: true,
            lineWrapping: true,
            viewportMargin: Infinity
        });
        
        var cssEditor = CodeMirror.fromTextArea(document.getElementById('css_personalizado'), {
            mode: 'css',
            theme: 'monokai',
            lineNumbers: true,
            lineWrapping: true,
            viewportMargin: Infinity
        });
        
        // Update preview
        function updatePreview() {
            var html = htmlEditor.getValue();
            var css = cssEditor.getValue();
            
            var previewHtml = `
                <style>
                    ${css}
                </style>
                ${html.replace(/\{\{[^}]+\}\}/g, '<span style="color: var(--warning-color); background: rgba(243, 156, 18, 0.1); padding: 2px 6px; border-radius: 3px; font-weight: bold;">$&</span>')}
            `;
            
            document.getElementById('preview_container').innerHTML = previewHtml;
        }
        
        // Preview template modal
        function previewTemplate(html, css) {
            var previewHtml = `
                <style>
                    ${css || ''}
                </style>
                ${html.replace(/\{\{[^}]+\}\}/g, '<span style="color: var(--warning-color); background: rgba(243, 156, 18, 0.1); padding: 2px 6px; border-radius: 3px; font-weight: bold;">$&</span>')}
            `;
            
            document.getElementById('preview_modal_content').innerHTML = previewHtml;
            var previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
        }
        
        // Edit template
        function editTemplate(plantilla) {
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-palette me-2"></i>Editar Plantilla';
            document.getElementById('modal_action').value = 'update';
            document.getElementById('btn_save_text').textContent = 'Actualizar Plantilla';
            
            document.getElementById('template_id').value = plantilla.id;
            document.getElementById('nombre').value = plantilla.nombre;
            
            htmlEditor.setValue(plantilla.contenido_html || '');
            cssEditor.setValue(plantilla.css_personalizado || '');
            
            updatePreview();
        }
        
        // Reset modal on close
        document.getElementById('templateModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-palette me-2"></i>Nueva Plantilla de Boleta';
            document.getElementById('modal_action').value = 'create';
            document.getElementById('btn_save_text').textContent = 'Guardar Plantilla';
            document.getElementById('template_id').value = '';
            document.getElementById('nombre').value = '';
            
            htmlEditor.setValue(`<?php echo addslashes($default_template); ?>`);
            cssEditor.setValue(`<?php echo addslashes($default_css); ?>`);
            updatePreview();
        });
        
        // Delete template with confirmation
        function deleteTemplate(id, nombre) {
            if (confirm('¿Estás seguro de que deseas eliminar la plantilla "' + nombre + '"?\nEsta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'delete';
                
                const templateId = document.createElement('input');
                templateId.type = 'hidden';
                templateId.name = 'id';
                templateId.value = id;
                
                form.appendChild(action);
                form.appendChild(templateId);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Toggle active status
        function toggleActive(id) {
            if (confirm('¿Deseas cambiar el estado de esta plantilla?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'toggle_active';
                
                const templateId = document.createElement('input');
                templateId.type = 'hidden';
                templateId.name = 'id';
                templateId.value = id;
                
                form.appendChild(action);
                form.appendChild(templateId);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Initialize preview on load
        updatePreview();
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