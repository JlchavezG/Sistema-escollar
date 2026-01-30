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
    <title>Crear Credencial con Asistente - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="../assets/js/sidebar-toggle.js" defer></script>
    <style>
        .builder-container {
            display: grid;
            grid-template-columns: 320px 1fr;
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
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .preview-panel {
            background: var(--color-white);
            border-radius: var(--border-radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 800px;
        }
        
        /* CONTENEDOR PRINCIPAL - BASE PARA AMBOS MODOS */
        .preview-credencial {
            position: relative;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            background: white;
            font-family: 'Arial', sans-serif;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 3px solid #2c3e50;
        }
        
        /* ¡CRÍTICO! Contenedor interno que maneja el layout */
        .credencial-content {
            width: 100%;
            height: 100%;
            display: flex;
            position: relative;
        }
        
        /* ===== VERTICAL LAYOUT (DE ARRIBA HACIA ABAJO) ===== */
        .preview-credencial.vertical {
            width: 350px;
            height: 220px;
        }
        
        .preview-credencial.vertical .credencial-content {
            flex-direction: column; /* ¡Elementos de arriba hacia abajo! */
        }
        
        .preview-credencial.vertical .cred-section {
            width: 100%;
            padding: 5px;
            box-sizing: border-box; /* ¡Incluye padding en el width! */
        }
        
        .preview-credencial.vertical .cred-header {
            text-align: center;
            padding: 8px;
            border-bottom: 2px solid var(--primary-color);
            margin-bottom: 5px;
        }
        
        .preview-credencial.vertical .cred-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 5px;
            text-align: center;
        }
        
        .preview-credencial.vertical .cred-footer {
            text-align: center;
            padding-top: 8px;
            border-top: 1px solid #eee;
            margin-top: auto;
            font-size: 10px;
        }
        
        /* ===== HORIZONTAL LAYOUT (DOS COLUMNAS LADO A LADO) ===== */
        .preview-credencial.horizontal {
            width: 450px;
            height: 180px;
        }
        
        .preview-credencial.horizontal .credencial-content {
            flex-direction: row; /* ¡Elementos en dos columnas! */
        }
        
        .preview-credencial.horizontal .cred-left {
            width: 40%;
            height: 100%;
            border-right: 1px solid #eee;
            display: flex;
            flex-direction: column;
            padding: 8px;
            box-sizing: border-box; /* ¡CRÍTICO PARA QUE NO SE SALGA! */
        }
        
        .preview-credencial.horizontal .cred-right {
            width: 60%;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 8px;
            box-sizing: border-box; /* ¡CRÍTICO PARA QUE NO SE SALGA! */
        }
        
        .preview-credencial.horizontal .cred-header {
            text-align: center;
            padding: 3px 0;
            border-bottom: 1px solid var(--primary-color);
            font-size: 10px;
            margin-bottom: 5px;
        }
        
        .preview-credencial.horizontal .cred-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3px;
            text-align: center;
        }
        
        .preview-credencial.horizontal .cred-footer {
            text-align: center;
            padding-top: 3px;
            font-size: 8px;
            margin-top: auto;
        }
        
        /* Elementos comunes */
        .photo-placeholder {
            width: 100%;
            height: 100%;
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 10px;
            font-weight: bold;
        }
        
        .barcode-placeholder {
            height: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 9px;
            font-weight: bold;
            width: 100%;
        }
        
        .student-name {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .student-detail {
            font-size: 11px;
            margin: 1px 0;
        }
        
        .matricula {
            background: #e3f2fd;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
            color: var(--secondary-color);
            margin-top: 3px;
            font-size: 12px;
            display: inline-block;
        }
        
        /* Controles y elementos arrastrables */
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
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--color-text-secondary);
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.6;
        }
        
        .orientation-controls {
            background: var(--color-gray-light);
            padding: 15px;
            border-radius: var(--border-radius-md);
            margin-bottom: 20px;
            border: 2px solid var(--primary-color);
        }
        
        .orientation-option {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        
        .orientation-option:hover {
            background: rgba(52, 152, 219, 0.1);
            border-color: var(--secondary-color);
        }
        
        .orientation-preview {
            width: 70px;
            height: 45px;
            border: 3px solid var(--primary-color);
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 10px;
            background: white;
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }
        
        .orientation-preview::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 80%;
            height: 60%;
            background: var(--primary-color);
            opacity: 0.2;
            transform: translate(-50%, -50%);
            border-radius: 2px;
        }
        
        .orientation-preview.horizontal {
            width: 90px;
            height: 35px;
        }
        
        .orientation-preview.horizontal::before {
            width: 60%;
            height: 80%;
        }
        
        .dimensions-badge {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        /* ¡CRÍTICO! Permitir drop en todo el contenedor */
        .preview-credencial,
        .credencial-content {
            pointer-events: auto !important;
        }
        
        /* Estilo para el botón de eliminar en la vista previa */
        .remove-section {
            position: absolute;
            top: 4px;
            right: 4px;
            background: var(--danger-color);
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
            z-index: 100;
        }
        
        .remove-section:hover {
            background: #c0392b;
            transform: scale(1.1);
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
                            <i class="fas fa-id-card me-3"></i>
                            Crear Credencial con Asistente Visual
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Diseña credenciales estudiantiles sin necesidad de código. Selecciona orientación y arrastra elementos.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <button id="saveTemplateBtn" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Guardar Credencial
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="builder-container">
                <!-- Panel de Controles -->
                <div class="controls-panel">
                    <h5 class="mb-3"><i class="fas fa-sliders-h me-2"></i>Configuración</h5>
                    
                    <!-- Orientación de la Credencial -->
                    <div class="orientation-controls mb-4">
                        <label class="form-label fw-bold mb-2">Orientación de la Credencial</label>
                        <div class="orientation-option">
                            <div class="orientation-preview">VERTICAL</div>
                            <div>
                                <input type="radio" id="orientationVertical" name="orientation" value="vertical" checked>
                                <label for="orientationVertical" class="ms-2 fw-bold">Vertical (Estándar)</label>
                                <div><span class="dimensions-badge">8.5 × 5.4 cm</span></div>
                                <small class="text-muted">Ideal para credenciales tradicionales</small>
                            </div>
                        </div>
                        <div class="orientation-option">
                            <div class="orientation-preview horizontal">HORIZONTAL</div>
                            <div>
                                <input type="radio" id="orientationHorizontal" name="orientation" value="horizontal">
                                <label for="orientationHorizontal" class="ms-2 fw-bold">Horizontal (Carnet)</label>
                                <div><span class="dimensions-badge">10.5 × 4.5 cm</span></div>
                                <small class="text-muted">Estilo carnet, más ancho que alto</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logo de la Escuela -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Logo de la Escuela</label>
                        <input type="file" class="form-control" id="schoolLogo" accept="image/*">
                        <img id="logoPreview" class="logo-preview mt-2" src="image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='60' viewBox='0 0 120 60'%3E%3Crect width='120' height='60' fill='%23e9ecef'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial' font-size='12' fill='%236c757d' text-anchor='middle' dominant-baseline='middle'%3ELogo%3C/text%3E%3C/svg%3E" alt="Vista previa">
                    </div>
                    
                    <!-- Información de la Escuela -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Nombre de la Escuela</label>
                        <input type="text" class="form-control" id="schoolName" value="Sistema Escolar">
                        
                        <label class="form-label fw-bold mt-3">Encabezado</label>
                        <input type="text" class="form-control" id="headerText" value="Credencial Estudiantil">
                        
                        <label class="form-label fw-bold mt-3">Ciclo Escolar</label>
                        <input type="text" class="form-control" id="schoolYear" value="<?php echo CYCLE_ACTUAL; ?>">
                    </div>
                    
                    <!-- Colores -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Colores de la Credencial</label>
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
                    
                    <!-- Elementos Arrastrables -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="mb-3"><i class="fas fa-object-group me-2"></i>Elementos para la Credencial</h6>
                        <div class="alert alert-info small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Arrastra los elementos a la zona de diseño
                        </div>
                        <div class="drag-item" draggable="true" data-type="header">
                            <i class="fas fa-building me-2"></i>Encabezado
                        </div>
                        <div class="drag-item" draggable="true" data-type="photo">
                            <i class="fas fa-camera me-2"></i>Foto del Estudiante
                        </div>
                        <div class="drag-item" draggable="true" data-type="student-info">
                            <i class="fas fa-user-graduate me-2"></i>Información del Alumno
                        </div>
                        <div class="drag-item" draggable="true" data-type="barcode">
                            <i class="fas fa-barcode me-2"></i>Código de Barras
                        </div>
                        <div class="drag-item" draggable="true" data-type="footer">
                            <i class="fas fa-info-circle me-2"></i>Pie de Página
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Vista Previa -->
                <div class="preview-panel">
                    <h5 class="mb-4 text-center"><i class="fas fa-eye me-2"></i>Vista Previa de la Credencial</h5>
                    <!-- ¡ESTRUCTURA CORREGIDA CON CONTENEDOR INTERNO! -->
                    <div class="preview-credencial vertical" id="credencialPreview">
                        <div class="credencial-content" id="credencialContent">
                            <div class="empty-state">
                                <i class="fas fa-id-card fa-3x mb-3"></i>
                                <p>Selecciona orientación y arrastra elementos<br>para diseñar tu credencial</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <div class="badge bg-primary" id="dimensionsBadge">8.5 × 5.4 cm (Vertical)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal: Guardar Credencial -->
    <div class="modal fade" id="saveTemplateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header gradient-primary">
                    <h5 class="modal-title text-white"><i class="fas fa-save me-2"></i>Guardar Plantilla de Credencial</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la Plantilla</label>
                        <input type="text" class="form-control" id="templateName" placeholder="Ej: Credencial Primaria 2025" required>
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
            let templateData = {
                logo: null,
                schoolName: 'Sistema Escolar',
                headerText: 'Credencial Estudiantil',
                schoolYear: '<?php echo CYCLE_ACTUAL; ?>',
                primaryColor: '#2c3e50',
                secondaryColor: '#3498db',
                orientation: 'vertical',
                sections: []
            };
            
            // ¡REFERENCIAS CORRECTAS A LOS CONTENEDORES!
            const credencialPreview = document.getElementById('credencialPreview');
            const credencialContent = document.getElementById('credencialContent');
            const dimensionsBadge = document.getElementById('dimensionsBadge');
            
            // ¡CONFIGURAR DROP ZONE EN EL CONTENEDOR DE CONTENIDO!
            credencialContent.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = '#3498db';
                this.style.backgroundColor = 'rgba(52, 152, 219, 0.1)';
            });
            
            credencialContent.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = '';
                this.style.backgroundColor = '';
            });
            
            credencialContent.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = '';
                this.style.backgroundColor = '';
                
                const sectionType = e.dataTransfer.getData('text/plain');
                if (sectionType) {
                    addSection(sectionType);
                }
            });
            
            // Hacer elementos arrastrables
            document.querySelectorAll('.drag-item').forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('text/plain', this.getAttribute('data-type'));
                    e.dataTransfer.effectAllowed = 'move';
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
            
            // Configurar orientación
            document.getElementById('orientationVertical').addEventListener('change', function() {
                if (this.checked) {
                    templateData.orientation = 'vertical';
                    credencialPreview.className = 'preview-credencial vertical';
                    dimensionsBadge.textContent = '8.5 × 5.4 cm (Vertical)';
                    dimensionsBadge.className = 'badge bg-primary';
                    updatePreview();
                }
            });
            
            document.getElementById('orientationHorizontal').addEventListener('change', function() {
                if (this.checked) {
                    templateData.orientation = 'horizontal';
                    credencialPreview.className = 'preview-credencial horizontal';
                    dimensionsBadge.textContent = '10.5 × 4.5 cm (Horizontal)';
                    dimensionsBadge.className = 'badge bg-success';
                    updatePreview();
                }
            });
            
            // Función para agregar sección
            function addSection(type) {
                const section = {
                    id: Date.now() + Math.random().toString(36).substr(2, 5),
                    type: type
                };
                
                templateData.sections.push(section);
                renderSections();
            }
            
            // Renderizar secciones - ¡ESTRUCTURA CORREGIDA!
            function renderSections() {
                // Limpiar contenido
                while (credencialContent.firstChild) {
                    credencialContent.removeChild(credencialContent.firstChild);
                }
                
                // Si no hay secciones, mostrar mensaje
                if (templateData.sections.length === 0) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <i class="fas fa-id-card fa-3x mb-3"></i>
                        <p>${templateData.orientation === 'vertical' ? 'Vertical' : 'Horizontal'} - Arrastra elementos para comenzar</p>
                    `;
                    credencialContent.appendChild(emptyState);
                    return;
                }
                
                // ¡CREAR ESTRUCTURA SEGÚN ORIENTACIÓN!
                if (templateData.orientation === 'horizontal') {
                    // ¡DOS COLUMNAS LADO A LADO!
                    const leftCol = document.createElement('div');
                    leftCol.className = 'cred-left';
                    
                    const rightCol = document.createElement('div');
                    rightCol.className = 'cred-right';
                    
                    // Distribuir secciones
                    templateData.sections.forEach(section => {
                        const sectionEl = createSectionElement(section);
                        if (section.type === 'photo') {
                            leftCol.appendChild(sectionEl);
                        } else if (section.type === 'header' || section.type === 'footer') {
                            // Clonar para ambas columnas (versión pequeña)
                            const leftClone = sectionEl.cloneNode(true);
                            // Ajustar estilos para versión pequeña en columna izquierda
                            if (section.type === 'header') {
                                const headerDiv = leftClone.querySelector('.cred-header');
                                if (headerDiv) headerDiv.style.fontSize = '9px';
                                headerDiv.style.padding = '2px 0';
                            }
                            leftCol.appendChild(leftClone);
                            rightCol.appendChild(sectionEl);
                        } else {
                            rightCol.appendChild(sectionEl);
                        }
                    });
                    
                    credencialContent.appendChild(leftCol);
                    credencialContent.appendChild(rightCol);
                } else {
                    // ¡UNA SOLA COLUMNA DE ARRIBA HACIA ABAJO!
                    templateData.sections.forEach(section => {
                        const sectionEl = createSectionElement(section);
                        credencialContent.appendChild(sectionEl);
                    });
                }
            }
            
            // Crear elemento de sección
            function createSectionElement(section) {
                const sectionEl = document.createElement('div');
                sectionEl.className = 'cred-section';
                
                let content = '';
                switch(section.type) {
                    case 'header':
                        content = `
                            <div class="cred-header">
                                ${templateData.logo ? `<img src="${templateData.logo}" alt="Logo" style="max-height: 25px; margin-bottom: 3px;">` : ''}
                                <div style="font-weight: bold; font-size: 13px; color: ${templateData.primaryColor};">${templateData.schoolName}</div>
                                <div style="font-size: 10px; margin-top: 2px;">${templateData.headerText}</div>
                            </div>
                        `;
                        break;
                    case 'photo':
                        content = `
                            <div class="cred-body">
                                <div class="photo-container" style="width: 100%; height: 100%;">
                                    <div class="photo-placeholder">FOTO ESTUDIANTE</div>
                                </div>
                            </div>
                        `;
                        break;
                    case 'student-info':
                        content = `
                            <div class="cred-body">
                                <div class="info-container">
                                    <div class="student-name">{{nombre_alumno}}</div>
                                    <div class="student-detail"><strong>Grado:</strong> {{grado}}</div>
                                    <div class="student-detail"><strong>Grupo:</strong> {{grupo}}</div>
                                    <div class="matricula"><strong>Matrícula:</strong> {{matricula}}</div>
                                </div>
                            </div>
                        `;
                        break;
                    case 'barcode':
                        content = `
                            <div class="cred-body">
                                <div class="barcode-container">
                                    <div class="barcode-placeholder">CÓDIGO: {{codigo_barra}}</div>
                                </div>
                            </div>
                        `;
                        break;
                    case 'footer':
                        content = `
                            <div class="cred-footer">
                                <div>${templateData.schoolYear} • Documento Oficial</div>
                            </div>
                        `;
                        break;
                }
                
                sectionEl.innerHTML = content;
                
                // Agregar botón de eliminar
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-section';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.dataset.id = section.id;
                removeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    templateData.sections = templateData.sections.filter(s => s.id !== this.dataset.id);
                    renderSections();
                });
                sectionEl.appendChild(removeBtn);
                
                return sectionEl;
            }
            
            // Actualizar vista previa
            function updatePreview() {
                renderSections();
            }
            
            // Event listeners para controles
            document.getElementById('schoolLogo').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        templateData.logo = event.target.result;
                        document.getElementById('logoPreview').src = templateData.logo;
                        updatePreview();
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            document.getElementById('schoolName').addEventListener('input', function() {
                templateData.schoolName = this.value;
                updatePreview();
            });
            
            document.getElementById('headerText').addEventListener('input', function() {
                templateData.headerText = this.value;
                updatePreview();
            });
            
            document.getElementById('schoolYear').addEventListener('input', function() {
                templateData.schoolYear = this.value;
                updatePreview();
            });
            
            document.getElementById('primaryColor').addEventListener('input', function() {
                templateData.primaryColor = this.value;
                updatePreview();
            });
            
            document.getElementById('secondaryColor').addEventListener('input', function() {
                templateData.secondaryColor = this.value;
                updatePreview();
            });
            
            // Guardar plantilla
            document.getElementById('saveTemplateBtn').addEventListener('click', function() {
                if (templateData.sections.length === 0) {
                    alert('⚠️ Debes agregar al menos un elemento a la credencial');
                    return;
                }
                document.getElementById('templateName').value = 'Credencial ' + (templateData.orientation === 'horizontal' ? 'Horizontal' : 'Vertical');
                new bootstrap.Modal(document.getElementById('saveTemplateModal')).show();
            });
            
            document.getElementById('confirmSaveTemplate').addEventListener('click', function() {
                const templateName = document.getElementById('templateName').value.trim();
                if (!templateName) {
                    alert('⚠️ Ingresa un nombre para la plantilla');
                    return;
                }
                
                // Generar HTML según orientación
                let htmlContent = '';
                if (templateData.orientation === 'horizontal') {
                    htmlContent = `
                        <div class="credencial-container horizontal" style="width: 450px; height: 180px; display: flex; flex-direction: row; border: 3px solid ${templateData.primaryColor}; border-radius: 12px; overflow: hidden; font-family: Arial, sans-serif;">
                            <div class="cred-left" style="width: 40%; height: 100%; border-right: 1px solid #eee; padding: 8px; box-sizing: border-box; display: flex; flex-direction: column;">
                                ${templateData.sections.some(s => s.type === 'header') ? `<div class="cred-header" style="text-align: center; padding: 3px 0; border-bottom: 1px solid ${templateData.primaryColor}; font-size: 10px; margin-bottom: 5px;">${templateData.logo ? `<img src="${templateData.logo}" style="max-height: 20px; margin-bottom: 2px;">` : ''}<div style="font-weight: bold; font-size: 12px; color: ${templateData.primaryColor};">${templateData.schoolName}</div><div style="font-size: 9px;">${templateData.headerText}</div></div>` : ''}
                                <div class="cred-body" style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                                    <div class="photo-container" style="width: 100%; height: 100%;">
                                        <div style="width: 100%; height: 100%; background: #f8f9fa; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 10px; font-weight: bold;">FOTO</div>
                                    </div>
                                </div>
                                ${templateData.sections.some(s => s.type === 'footer') ? `<div class="cred-footer" style="text-align: center; padding-top: 3px; font-size: 8px; margin-top: auto;">${templateData.schoolYear} • Documento Oficial</div>` : ''}
                            </div>
                            <div class="cred-right" style="width: 60%; height: 100%; padding: 8px; box-sizing: border-box; display: flex; flex-direction: column;">
                                <div class="cred-body" style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                                    <div class="info-container">
                                        <div style="font-weight: bold; color: ${templateData.primaryColor}; font-size: 14px; margin-bottom: 2px;">{{nombre_alumno}}</div>
                                        <div style="font-size: 11px; margin: 1px 0;"><strong>Grado:</strong> {{grado}}</div>
                                        <div style="font-size: 11px; margin: 1px 0;"><strong>Grupo:</strong> {{grupo}}</div>
                                        <div style="background: #e3f2fd; padding: 3px 6px; border-radius: 3px; font-weight: bold; color: ${templateData.secondaryColor}; margin-top: 3px; font-size: 12px; display: inline-block;"><strong>Matrícula:</strong> {{matricula}}</div>
                                    </div>
                                </div>
                                <div class="cred-body" style="padding-top: 5px; border-top: 1px solid #eee;">
                                    <div class="barcode-container" style="width: 100%;">
                                        <div style="height: 20px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 9px; font-weight: bold; width: 100%;">CÓDIGO: {{codigo_barra}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // Vertical layout
                    htmlContent = `
                        <div class="credencial-container vertical" style="width: 350px; height: 220px; display: flex; flex-direction: column; border: 3px solid ${templateData.primaryColor}; border-radius: 12px; overflow: hidden; font-family: Arial, sans-serif;">
                            ${templateData.sections.map(section => {
                                switch(section.type) {
                                    case 'header': return `<div class="cred-header" style="text-align: center; padding: 8px; border-bottom: 2px solid ${templateData.primaryColor}; margin-bottom: 5px; width: 100%;">${templateData.logo ? `<img src="${templateData.logo}" style="max-height: 25px; margin-bottom: 3px;">` : ''}<div style="font-weight: bold; font-size: 13px; color: ${templateData.primaryColor};">${templateData.schoolName}</div><div style="font-size: 10px;">${templateData.headerText}</div></div>`;
                                    case 'photo': return `<div class="cred-body" style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:5px; width:100%;"><div class="photo-container" style="width:100px; height:120px;"><div style="width:100%; height:100%; background:#f8f9fa; border:2px dashed #ddd; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#999; font-size:10px; font-weight:bold;">FOTO</div></div></div>`;
                                    case 'student-info': return `<div class="cred-body" style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:5px; width:100%;"><div class="info-container"><div style="font-weight:bold; color:${templateData.primaryColor}; font-size:14px; margin-bottom:2px;">{{nombre_alumno}}</div><div style="font-size:11px; margin:1px 0;"><strong>Grado:</strong> {{grado}}</div><div style="font-size:11px; margin:1px 0;"><strong>Grupo:</strong> {{grupo}}</div><div style="background:#e3f2fd; padding:3px 6px; border-radius:3px; font-weight:bold; color:${templateData.secondaryColor}; margin-top:3px; font-size:12px; display:inline-block;"><strong>Matrícula:</strong> {{matricula}}</div></div></div>`;
                                    case 'barcode': return `<div class="cred-body" style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:5px; width:100%;"><div class="barcode-container" style="width:100%;"><div style="height:20px; background:#f8f9fa; border-radius:4px; display:flex; align-items:center; justify-content:center; color:#999; font-size:9px; font-weight:bold; width:100%;">CÓDIGO: {{codigo_barra}}</div></div></div>`;
                                    case 'footer': return `<div class="cred-footer" style="text-align:center; padding-top:8px; border-top:1px solid #eee; margin-top:auto; width:100%; font-size:10px;">${templateData.schoolYear} • Documento Oficial</div>`;
                                    default: return '';
                                }
                            }).join('')}
                        </div>
                    `;
                }
                
                // Guardar plantilla
                fetch('guardar_plantilla_credencial.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        nombre: templateName,
                        contenido_html: htmlContent,
                        css_personalizado: '',
                        activa: document.getElementById('setAsActive').checked ? 1 : 0
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ¡Plantilla de credencial creada exitosamente!');
                        window.location.href = 'gestion_credenciales.php';
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(e => {
                    console.error(e);
                    alert('❌ Error al guardar la plantilla');
                });
            });
            
            // Inicializar con ejemplo
            setTimeout(() => {
                addSection('header');
                addSection('photo');
                addSection('student-info');
                addSection('barcode');
                addSection('footer');
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