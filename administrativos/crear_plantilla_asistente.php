<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Plantilla con Asistente - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="../assets/js/sidebar-toggle.js" defer></script>
    <style>
        .builder-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .controls-panel {
            background: var(--color-white);
            border-radius: var(--border-radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-md);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .preview-panel {
            background: var(--color-white);
            border-radius: var(--border-radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-md);
            min-height: 800px;
        }
        
        .preview-boleta {
            border: 3px solid #2c3e50;
            border-radius: 15px;
            padding: 30px;
            background: var(--color-white);
            font-family: 'Arial', sans-serif;
            position: relative;
            min-height: 700px;
        }
        
        .drag-item {
            background: var(--color-gray-light);
            border-radius: var(--border-radius-md);
            padding: 12px;
            margin-bottom: 10px;
            cursor: move;
            transition: all var(--transition-fast);
            border: 2px dashed transparent;
            user-select: none;
            draggable: true;
        }
        
        .drag-item:hover {
            background: var(--color-blue-light);
            border-color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .drag-item:active {
            opacity: 0.8;
            transform: scale(0.98);
        }
        
        .drop-zone {
            min-height: 100px;
            border: 3px dashed var(--primary-color);
            border-radius: var(--border-radius-md);
            margin: 20px 0;
            padding: 25px;
            background: rgba(44, 62, 80, 0.03);
            transition: all var(--transition-fast);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .drop-zone.drag-over {
            background: rgba(52, 152, 219, 0.15);
            border-color: var(--secondary-color);
            transform: scale(1.02);
        }
        
        .drop-zone p {
            color: var(--color-text-secondary);
            margin: 0;
            font-style: italic;
        }
        
        .drop-zone i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 10px;
            opacity: 0.7;
        }
        
        .section-preview {
            background: var(--color-white);
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-md);
            padding: 20px;
            margin: 15px 0;
            position: relative;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
        }
        
        .section-preview:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }
        
        .remove-section {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--danger-color);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
            z-index: 10;
        }
        
        .remove-section:hover {
            background: #c0392b;
            transform: scale(1.1);
        }
        
        .color-picker-group {
            display: flex;
            gap: 15px;
            align-items: center;
            margin: 15px 0;
        }
        
        .logo-preview {
            max-width: 150px;
            max-height: 100px;
            margin-top: 10px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-md);
            background: var(--color-gray-light);
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--color-text-secondary);
        }
        
        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 15px;
            opacity: 0.6;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Estilo para el área de secciones (contenedor del drop zone) */
        #sectionsContainer {
            min-height: 400px;
            margin: 20px 0;
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
                            <a class="nav-link" href="plantillas_boletas.php">
                                <i class="fas fa-arrow-left me-2"></i>Volver a Plantillas
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
                            <i class="fas fa-magic me-3"></i>
                            Crear Plantilla con Asistente Visual
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Diseña tu boleta sin necesidad de código. Arrastra elementos, configura colores y guarda tu plantilla.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <button id="saveTemplateBtn" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Guardar Plantilla
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="builder-container">
                <!-- Panel de Controles -->
                <div class="controls-panel">
                    <h5 class="mb-3"><i class="fas fa-sliders-h me-2"></i>Configuración</h5>
                    
                    <!-- Logo de la Escuela -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Logo de la Escuela</label>
                        <input type="file" class="form-control" id="schoolLogo" accept="image/*">
                        <img id="logoPreview" class="logo-preview mt-2" src="image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='150' height='100' viewBox='0 0 150 100'%3E%3Crect width='150' height='100' fill='%23e9ecef'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial' font-size='14' fill='%236c757d' text-anchor='middle' dominant-baseline='middle'%3ELogo%3C/text%3E%3C/svg%3E" alt="Vista previa">
                    </div>
                    
                    <!-- Información de la Escuela -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Nombre de la Escuela</label>
                        <input type="text" class="form-control" id="schoolName" value="Sistema Escolar">
                        
                        <label class="form-label fw-bold mt-3">Encabezado</label>
                        <input type="text" class="form-control" id="headerText" value="Boleta de Calificaciones">
                        
                        <label class="form-label fw-bold mt-3">Ciclo Escolar</label>
                        <input type="text" class="form-control" id="schoolYear" value="<?php echo CYCLE_ACTUAL; ?>">
                    </div>
                    
                    <!-- Colores -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Colores de la Escuela</label>
                        <div class="color-picker-group">
                            <div>
                                <label>Primario</label>
                                <input type="color" class="form-control form-control-color" id="primaryColor" value="#2c3e50" title="Color primario">
                            </div>
                            <div>
                                <label>Secundario</label>
                                <input type="color" class="form-control form-control-color" id="secondaryColor" value="#3498db" title="Color secundario">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Autoridades -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Autoridades</label>
                        <div class="mb-2">
                            <input type="text" class="form-control mb-2" placeholder="Director" id="directorName">
                            <input type="text" class="form-control mb-2" placeholder="Supervisor" id="supervisorName">
                            <input type="text" class="form-control" placeholder="Secretario" id="secretaryName">
                        </div>
                    </div>
                    
                    <!-- Elementos Arrastrables -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="mb-3"><i class="fas fa-object-group me-2"></i>Elementos para la Boleta</h6>
                        <div class="alert alert-info small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Arrastra los elementos a la zona de diseño para construir tu boleta
                        </div>
                        <div class="drag-item" draggable="true" data-type="student-info">
                            <i class="fas fa-user-graduate me-2"></i>Información del Alumno
                        </div>
                        <div class="drag-item" draggable="true" data-type="grades-table">
                            <i class="fas fa-table me-2"></i>Tabla de Calificaciones
                        </div>
                        <div class="drag-item" draggable="true" data-type="comments">
                            <i class="fas fa-comment me-2"></i>Observaciones
                        </div>
                        <div class="drag-item" draggable="true" data-type="signatures">
                            <i class="fas fa-signature me-2"></i>Firmas de Autoridades
                        </div>
                        <div class="drag-item" draggable="true" data-type="footer">
                            <i class="fas fa-info-circle me-2"></i>Pie de Página
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Vista Previa -->
                <div class="preview-panel">
                    <h5 class="mb-4 text-center"><i class="fas fa-eye me-2"></i>Vista Previa de la Boleta</h5>
                    <div class="preview-boleta" id="boletaPreview">
                        <!-- Header se generará dinámicamente -->
                        <div id="headerPreview"></div>
                        
                        <!-- Contenedor de secciones (drop zone) -->
                        <div id="sectionsContainer" class="drop-zone">
                            <i class="fas fa-plus-circle"></i>
                            <p>Suelta los elementos aquí para agregarlos a la boleta</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Guardar Plantilla -->
    <div class="modal fade" id="saveTemplateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header gradient-primary">
                    <h5 class="modal-title text-white"><i class="fas fa-save me-2"></i>Guardar Plantilla</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la Plantilla</label>
                        <input type="text" class="form-control" id="templateName" placeholder="Ej: Boleta Primaria Moderna" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="setAsActive" checked>
                        <label class="form-check-label" for="setAsActive">
                            Establecer como plantilla activa
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="confirmSaveTemplate">
                        <i class="fas fa-save me-1"></i>Guardar Plantilla
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables para la plantilla
            let templateData = {
                logo: null,
                schoolName: 'Sistema Escolar',
                headerText: 'Boleta de Calificaciones',
                schoolYear: '<?php echo CYCLE_ACTUAL; ?>',
                primaryColor: '#2c3e50',
                secondaryColor: '#3498db',
                directorName: '',
                supervisorName: '',
                secretaryName: '',
                sections: []
            };
            
            // Referencias a elementos del DOM
            const sectionsContainer = document.getElementById('sectionsContainer');
            const headerPreview = document.getElementById('headerPreview');
            
            // Configurar zona de soltar (SOLO UNA VEZ)
            sectionsContainer.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('drag-over');
            });
            
            sectionsContainer.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('drag-over');
            });
            
            sectionsContainer.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('drag-over');
                
                const sectionType = e.dataTransfer.getData('text/plain');
                if (sectionType) {
                    addSection(sectionType);
                }
            });
            
            // Hacer elementos arrastrables
            const dragItems = document.querySelectorAll('.drag-item');
            dragItems.forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('text/plain', this.getAttribute('data-type'));
                    e.dataTransfer.effectAllowed = 'move';
                    
                    // Feedback visual
                    setTimeout(() => {
                        this.style.opacity = '0.6';
                        this.style.transform = 'scale(0.95)';
                    }, 0);
                });
                
                item.addEventListener('dragend', function() {
                    this.style.opacity = '1';
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Función para agregar sección
            function addSection(type) {
                const section = {
                    id: Date.now() + Math.random().toString(36).substr(2, 5),
                    type: type
                };
                
                templateData.sections.push(section);
                renderSections();
                updateHeader();
            }
            
            // Renderizar secciones en el contenedor
            function renderSections() {
                // Limpiar contenedor excepto el mensaje inicial si está vacío
                if (templateData.sections.length === 0) {
                    sectionsContainer.innerHTML = `
                        <i class="fas fa-plus-circle"></i>
                        <p>Suelta los elementos aquí para agregarlos a la boleta</p>
                    `;
                    sectionsContainer.classList.add('drop-zone');
                    return;
                }
                
                // Quitar clase drop-zone cuando hay secciones
                sectionsContainer.classList.remove('drop-zone');
                sectionsContainer.innerHTML = '';
                
                // Renderizar secciones en orden
                templateData.sections.forEach(section => {
                    const sectionEl = document.createElement('div');
                    sectionEl.className = 'section-preview';
                    sectionEl.dataset.id = section.id;
                    
                    let content = '';
                    switch(section.type) {
                        case 'student-info':
                            content = `
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                    <div><strong>Alumno:</strong> {{nombre_alumno}}</div>
                                    <div><strong>Grado:</strong> {{grado}}</div>
                                    <div><strong>Grupo:</strong> {{grupo}}</div>
                                    <div><strong>Mes:</strong> {{mes}}</div>
                                </div>
                            `;
                            break;
                        case 'grades-table':
                            content = `
                                <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                                    <thead>
                                        <tr style="background: ${templateData.primaryColor}; color: white;">
                                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Materia</th>
                                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Calificación</th>
                                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="padding: 10px; border: 1px solid #ddd;">{{materia}}</td>
                                            <td style="padding: 10px; border: 1px solid #ddd;">{{calificacion}}</td>
                                            <td style="padding: 10px; border: 1px solid #ddd;">{{observaciones}}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            `;
                            break;
                        case 'comments':
                            content = `
                                <div style="margin: 15px 0; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                                    <strong>Observaciones Generales:</strong>
                                    <p class="mt-2 mb-0">{{observaciones_generales}}</p>
                                </div>
                            `;
                            break;
                        case 'signatures':
                            content = `
                                <div style="display: flex; justify-content: space-between; margin-top: 30px; flex-wrap: wrap; gap: 20px;">
                                    <div style="text-align: center; flex: 1; min-width: 200px;">
                                        <div style="border-top: 2px solid #000; padding-top: 8px;">
                                            <div><strong>${templateData.directorName || 'Director'}</strong></div>
                                            <div>Director</div>
                                        </div>
                                    </div>
                                    <div style="text-align: center; flex: 1; min-width: 200px;">
                                        <div style="border-top: 2px solid #000; padding-top: 8px;">
                                            <div><strong>${templateData.supervisorName || 'Supervisor'}</strong></div>
                                            <div>Supervisor</div>
                                        </div>
                                    </div>
                                    <div style="text-align: center; flex: 1; min-width: 200px;">
                                        <div style="border-top: 2px solid #000; padding-top: 8px;">
                                            <div><strong>${templateData.secretaryName || 'Secretario'}</strong></div>
                                            <div>Secretario</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'footer':
                            content = `
                                <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid ${templateData.primaryColor};">
                                    <small>Ciclo Escolar: {{ciclo_escolar}}</small><br>
                                    <small>Documento generado electrónicamente</small>
                                </div>
                            `;
                            break;
                    }
                    
                    sectionEl.innerHTML = `
                        ${content}
                        <button class="remove-section" data-id="${section.id}" title="Eliminar sección">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    sectionsContainer.appendChild(sectionEl);
                    
                    // Agregar evento para eliminar sección
                    sectionEl.querySelector('.remove-section').addEventListener('click', function(e) {
                        e.stopPropagation();
                        const id = this.dataset.id;
                        templateData.sections = templateData.sections.filter(s => s.id !== id);
                        renderSections();
                        updateHeader();
                    });
                });
                
                // Agregar mensaje al final si hay secciones
                if (templateData.sections.length > 0) {
                    const messageEl = document.createElement('div');
                    messageEl.style.textAlign = 'center';
                    messageEl.style.marginTop = '20px';
                    messageEl.style.color = 'var(--color-text-secondary)';
                    messageEl.innerHTML = '<i class="fas fa-hand-point-up me-2"></i>Arrastra más elementos aquí para agregarlos';
                    sectionsContainer.appendChild(messageEl);
                }
            }
            
            // Actualizar header
            function updateHeader() {
                headerPreview.innerHTML = `
                    <div style="text-align: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid ${templateData.primaryColor};">
                        ${templateData.logo ? `<img src="${templateData.logo}" alt="Logo" style="max-height: 80px; margin-bottom: 15px;">` : ''}
                        <div style="font-size: 28px; font-weight: bold; color: ${templateData.primaryColor}; margin-bottom: 5px;">${templateData.schoolName}</div>
                        <div style="font-size: 20px; color: ${templateData.secondaryColor}; margin-bottom: 5px;">${templateData.headerText}</div>
                        <div style="margin-top: 5px; font-size: 16px;">Ciclo Escolar: ${templateData.schoolYear}</div>
                    </div>
                `;
            }
            
            // Event listeners para los controles
            document.getElementById('schoolLogo').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        templateData.logo = event.target.result;
                        document.getElementById('logoPreview').src = templateData.logo;
                        updateHeader();
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            document.getElementById('schoolName').addEventListener('input', function() {
                templateData.schoolName = this.value;
                updateHeader();
            });
            
            document.getElementById('headerText').addEventListener('input', function() {
                templateData.headerText = this.value;
                updateHeader();
            });
            
            document.getElementById('schoolYear').addEventListener('input', function() {
                templateData.schoolYear = this.value;
                updateHeader();
            });
            
            document.getElementById('primaryColor').addEventListener('input', function() {
                templateData.primaryColor = this.value;
                updateHeader();
                renderSections(); // Actualizar tablas y otros elementos con color
            });
            
            document.getElementById('secondaryColor').addEventListener('input', function() {
                templateData.secondaryColor = this.value;
                updateHeader();
            });
            
            document.getElementById('directorName').addEventListener('input', function() {
                templateData.directorName = this.value;
                renderSections();
            });
            
            document.getElementById('supervisorName').addEventListener('input', function() {
                templateData.supervisorName = this.value;
                renderSections();
            });
            
            document.getElementById('secretaryName').addEventListener('input', function() {
                templateData.secretaryName = this.value;
                renderSections();
            });
            
            // Guardar plantilla
            document.getElementById('saveTemplateBtn').addEventListener('click', function() {
                if (templateData.sections.length === 0) {
                    alert('⚠️ Debes agregar al menos un elemento a la boleta antes de guardar');
                    return;
                }
                document.getElementById('templateName').value = 'Plantilla ' + new Date().toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
                const modal = new bootstrap.Modal(document.getElementById('saveTemplateModal'));
                modal.show();
            });
            
            document.getElementById('confirmSaveTemplate').addEventListener('click', function() {
                const templateName = document.getElementById('templateName').value.trim();
                const setActive = document.getElementById('setAsActive').checked;
                
                if (!templateName) {
                    alert('⚠️ Por favor ingresa un nombre para la plantilla');
                    return;
                }
                
                // Generar HTML completo para la plantilla
                let sectionsHtml = '';
                templateData.sections.forEach(section => {
                    switch(section.type) {
                        case 'student-info':
                            sectionsHtml += `
                                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px;">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                        <div><strong>Alumno:</strong><br>{{nombre_alumno}}</div>
                                        <div><strong>Grado:</strong><br>{{grado}}</div>
                                        <div><strong>Grupo:</strong><br>{{grupo}}</div>
                                        <div><strong>Mes:</strong><br>{{mes}}</div>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'grades-table':
                            sectionsHtml += `
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                    <thead>
                                        <tr style="background: ${templateData.primaryColor}; color: white;">
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Materia</th>
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Calificación</th>
                                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{calificaciones_rows}}
                                    </tbody>
                                </table>
                            `;
                            break;
                        case 'comments':
                            sectionsHtml += `
                                <div style="margin-bottom: 25px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 8px;">
                                    <strong style="color: #856404; display: block; margin-bottom: 10px;">Observaciones Generales:</strong>
                                    <p style="margin: 0; line-height: 1.6;">{{observaciones_generales}}</p>
                                </div>
                            `;
                            break;
                        case 'signatures':
                            sectionsHtml += `
                                <div style="display: flex; justify-content: space-between; margin: 30px 0 25px; flex-wrap: wrap; gap: 20px;">
                                    <div style="text-align: center; flex: 1; min-width: 200px;">
                                        <div style="border-top: 2px solid #000; padding-top: 8px; padding-left: 20px; padding-right: 20px;">
                                            <div style="font-weight: bold; margin-bottom: 5px;">${templateData.directorName || 'Director'}</div>
                                            <div>Director</div>
                                        </div>
                                    </div>
                                    <div style="text-align: center; flex: 1; min-width: 200px;">
                                        <div style="border-top: 2px solid #000; padding-top: 8px; padding-left: 20px; padding-right: 20px;">
                                            <div style="font-weight: bold; margin-bottom: 5px;">${templateData.supervisorName || 'Supervisor'}</div>
                                            <div>Supervisor</div>
                                        </div>
                                    </div>
                                    <div style="text-align: center; flex: 1; min-width: 200px;">
                                        <div style="border-top: 2px solid #000; padding-top: 8px; padding-left: 20px; padding-right: 20px;">
                                            <div style="font-weight: bold; margin-bottom: 5px;">${templateData.secretaryName || 'Secretario'}</div>
                                            <div>Secretario</div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'footer':
                            sectionsHtml += `
                                <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid ${templateData.primaryColor};">
                                    <small>Ciclo Escolar: {{ciclo_escolar}}</small><br>
                                    <small>Documento generado electrónicamente</small>
                                </div>
                            `;
                            break;
                    }
                });
                
                const templateHtml = `
                    <div style="font-family: Arial, sans-serif; max-width: 850px; margin: 0 auto; border: 3px solid ${templateData.primaryColor}; border-radius: 15px; padding: 30px; background: white; box-shadow: 0 5px 25px rgba(0,0,0,0.1);">
                        <div style="text-align: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid ${templateData.primaryColor};">
                            ${templateData.logo ? `<img src="${templateData.logo}" alt="Logo" style="max-height: 80px; margin-bottom: 15px;">` : ''}
                            <div style="font-size: 28px; font-weight: bold; color: ${templateData.primaryColor}; margin-bottom: 5px;">${templateData.schoolName}</div>
                            <div style="font-size: 20px; color: ${templateData.secondaryColor}; margin-bottom: 5px;">${templateData.headerText}</div>
                            <div style="font-size: 16px;">Ciclo Escolar: {{ciclo_escolar}}</div>
                        </div>
                        
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div><strong>Alumno:</strong><br>{{nombre_alumno}}</div>
                                <div><strong>Grado:</strong><br>{{grado}}</div>
                                <div><strong>Grupo:</strong><br>{{grupo}}</div>
                                <div><strong>Mes:</strong><br>{{mes}}</div>
                            </div>
                        </div>
                        
                        ${sectionsHtml}
                        
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid ${templateData.primaryColor}; font-size: 14px; color: #666;">
                            <div>Documento generado electrónicamente</div>
                            <div style="margin-top: 5px;">Sistema Escolar &copy; ${new Date().getFullYear()}</div>
                        </div>
                    </div>
                `;
                
                // CSS personalizado
                const templateCss = `
                    body {
                        font-family: Arial, sans-serif;
                        background: #f5f7fa;
                        padding: 20px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    th, td {
                        padding: 12px;
                        text-align: left;
                        border: 1px solid #ddd;
                    }
                    th {
                        background: ${templateData.primaryColor};
                        color: white;
                    }
                    tr:nth-child(even) {
                        background: #f8f9fa;
                    }
                `;
                
                // Enviar a servidor
                fetch('guardar_plantilla_asistente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        nombre: templateName,
                        contenido_html: templateHtml,
                        css_personalizado: templateCss,
                        activa: setActive ? 1 : 0
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ¡Plantilla creada exitosamente!');
                        window.location.href = 'plantillas_boletas.php';
                    } else {
                        alert('❌ Error al guardar la plantilla: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error al guardar la plantilla. Verifica la consola para más detalles.');
                });
            });
            
            // Inicializar con secciones de ejemplo
            setTimeout(() => {
                addSection('student-info');
                addSection('grades-table');
                addSection('signatures');
            }, 300);
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