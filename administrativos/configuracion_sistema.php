<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireSistemas();

$db = new Database();
$message = '';
$message_type = '';

// Obtener configuración actual
$db->query("SELECT * FROM configuracion WHERE id = 1");
$config = $db->single();

// Obtener plantillas activas para dropdown
$db->query("SELECT id, nombre FROM plantillas_boletas WHERE activa = 1 ORDER BY nombre");
$plantillas_activas = $db->resultSet();

// Manejar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_config'])) {
    try {
        $db->query("UPDATE configuracion SET 
            nombre_institucion = :nombre_institucion,
            ciclo_escolar = :ciclo_escolar,
            anio_fundacion = :anio_fundacion,
            telefono_contacto = :telefono_contacto,
            email_contacto = :email_contacto,
            min_calificacion_aprobatoria = :min_calificacion_aprobatoria,
            escala_calificaciones = :escala_calificaciones,
            meses_ciclo_escolar = :meses_ciclo_escolar,
            grados_activos = :grados_activos,
            tiempo_sesion = :tiempo_sesion,
            longitud_min_password = :longitud_min_password,
            password_requiere_numeros = :password_requiere_numeros,
            password_requiere_especiales = :password_requiere_especiales,
            intentos_fallidos_max = :intentos_fallidos_max,
            plantilla_predeterminada = :plantilla_predeterminada,
            mostrar_observaciones = :mostrar_observaciones,
            firmas_requeridas = :firmas_requeridas,
            incluir_logo_boletas = :incluir_logo_boletas,
            tema_predeterminado = :tema_predeterminado,
            logo_sistema = :logo_sistema,
            color_primario = :color_primario,
            color_secundario = :color_secundario,
            ver_calificaciones_otros = :ver_calificaciones_otros,
            editar_alumnos_asignados = :editar_alumnos_asignados,
            exportar_reportes = :exportar_reportes,
            eliminar_registros = :eliminar_registros,
            acceso_ip_restringido = :acceso_ip_restringido,
            rangos_ip_permitidos = :rangos_ip_permitidos,
            updated_at = NOW()
            WHERE id = 1");
        
        // Bind todos los parámetros
        $db->bind(':nombre_institucion', sanitizeInput($_POST['nombre_institucion']));
        $db->bind(':ciclo_escolar', sanitizeInput($_POST['ciclo_escolar']));
        $db->bind(':anio_fundacion', sanitizeInput($_POST['anio_fundacion']));
        $db->bind(':telefono_contacto', sanitizeInput($_POST['telefono_contacto']));
        $db->bind(':email_contacto', sanitizeInput($_POST['email_contacto']));
        $db->bind(':min_calificacion_aprobatoria', intval($_POST['min_calificacion_aprobatoria']));
        $db->bind(':escala_calificaciones', sanitizeInput($_POST['escala_calificaciones']));
        $db->bind(':meses_ciclo_escolar', sanitizeInput($_POST['meses_ciclo_escolar']));
        $db->bind(':grados_activos', sanitizeInput($_POST['grados_activos']));
        $db->bind(':tiempo_sesion', intval($_POST['tiempo_sesion']));
        $db->bind(':longitud_min_password', intval($_POST['longitud_min_password']));
        $db->bind(':password_requiere_numeros', isset($_POST['password_requiere_numeros']) ? 1 : 0);
        $db->bind(':password_requiere_especiales', isset($_POST['password_requiere_especiales']) ? 1 : 0);
        $db->bind(':intentos_fallidos_max', intval($_POST['intentos_fallidos_max']));
        $db->bind(':plantilla_predeterminada', !empty($_POST['plantilla_predeterminada']) ? intval($_POST['plantilla_predeterminada']) : null);
        $db->bind(':mostrar_observaciones', isset($_POST['mostrar_observaciones']) ? 1 : 0);
        $db->bind(':firmas_requeridas', sanitizeInput($_POST['firmas_requeridas']));
        $db->bind(':incluir_logo_boletas', isset($_POST['incluir_logo_boletas']) ? 1 : 0);
        $db->bind(':tema_predeterminado', sanitizeInput($_POST['tema_predeterminado']));
        $db->bind(':logo_sistema', sanitizeInput($_POST['logo_sistema']));
        $db->bind(':color_primario', sanitizeInput($_POST['color_primario']));
        $db->bind(':color_secundario', sanitizeInput($_POST['color_secundario']));
        $db->bind(':ver_calificaciones_otros', isset($_POST['ver_calificaciones_otros']) ? 1 : 0);
        $db->bind(':editar_alumnos_asignados', isset($_POST['editar_alumnos_asignados']) ? 1 : 0);
        $db->bind(':exportar_reportes', isset($_POST['exportar_reportes']) ? 1 : 0);
        $db->bind(':eliminar_registros', isset($_POST['eliminar_registros']) ? 1 : 0);
        $db->bind(':acceso_ip_restringido', isset($_POST['acceso_ip_restringido']) ? 1 : 0);
        $db->bind(':rangos_ip_permitidos', sanitizeInput($_POST['rangos_ip_permitidos']));
        
        if ($db->execute()) {
            $message = 'Configuración actualizada exitosamente. Los cambios tomarán efecto en la próxima sesión.';
            $message_type = 'success';
            
            // Recargar configuración
            $db->query("SELECT * FROM configuracion WHERE id = 1");
            $config = $db->single();
        }
    } catch (PDOException $e) {
        $message = 'Error al actualizar configuración: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Estadísticas del sistema (sin cambios)
$db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'sistemas'");
$total_sistemas = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'administrativo'");
$total_administrativos = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'profesor'");
$total_profesores = $db->single()['total'];

$db->query("SELECT COUNT(*) as total FROM alumnos");
$total_alumnos = $db->single()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="../assets/js/sidebar-toggle.js" defer></script>
    <style>
        .config-section {
            display: none;
        }
        .config-section.active {
            display: block;
        }
        .nav-pills .nav-link {
            color: var(--text-color);
            font-weight: var(--font-weight-medium);
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--color-white);
        }
        .config-card {
            border-left: 4px solid var(--primary-color);
            transition: transform var(--transition-fast);
        }
        .config-card:hover {
            transform: translateX(5px);
        }
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .permission-item {
            background: var(--color-gray-light);
            padding: 15px;
            border-radius: var(--border-radius-md);
            border-left: 3px solid var(--primary-color);
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
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
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
            <!-- Mensajes -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">
                            <i class="fas fa-cog me-3"></i>
                            Configuración del Sistema
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-shield-alt me-2"></i>
                            Panel de administración exclusivo para personal de sistemas - Control total del sistema
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- System Stats -->
            <div class="stats-grid mb-4">
                <div class="stat-item">
                    <i class="fas fa-user-shield fa-2x text-danger"></i>
                    <div class="stat-number"><?php echo $total_sistemas; ?></div>
                    <div class="stat-label">Usuarios Sistemas</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-user-tie fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $total_administrativos; ?></div>
                    <div class="stat-label">Administrativos</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-chalkboard-teacher fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $total_profesores; ?></div>
                    <div class="stat-label">Profesores</div>
                </div>
                <div class="stat-item">
                    <i class="fas fa-user-graduate fa-2x text-success"></i>
                    <div class="stat-number"><?php echo $total_alumnos; ?></div>
                    <div class="stat-label">Alumnos Totales</div>
                </div>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="card mb-4">
                <div class="card-header">
                    <ul class="nav nav-pills mb-0" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">General</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="academico-tab" data-bs-toggle="pill" data-bs-target="#academico" type="button" role="tab">Académico</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="seguridad-tab" data-bs-toggle="pill" data-bs-target="#seguridad" type="button" role="tab">Seguridad</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="boletas-tab" data-bs-toggle="pill" data-bs-target="#boletas" type="button" role="tab">Boletas</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="permisos-tab" data-bs-toggle="pill" data-bs-target="#permisos" type="button" role="tab">Permisos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="apariencia-tab" data-bs-toggle="pill" data-bs-target="#apariencia" type="button" role="tab">Apariencia</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="tab-content" id="configTabsContent">
                            <!-- PESTAÑA 1: GENERAL -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Nombre de la Institución <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nombre_institucion" 
                                               value="<?php echo htmlspecialchars($config['nombre_institucion'] ?? 'Sistema Escolar'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Ciclo Escolar Actual <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="ciclo_escolar" 
                                               value="<?php echo htmlspecialchars($config['ciclo_escolar'] ?? date('Y') . '-' . (date('Y')+1)); ?>" required>
                                        <small class="text-muted">Formato: 2025-2026</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Año de Fundación</label>
                                        <input type="text" class="form-control" name="anio_fundacion" 
                                               value="<?php echo htmlspecialchars($config['anio_fundacion'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Teléfono de Contacto</label>
                                        <input type="text" class="form-control" name="telefono_contacto" 
                                               value="<?php echo htmlspecialchars($config['telefono_contacto'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Email de Contacto</label>
                                        <input type="email" class="form-control" name="email_contacto" 
                                               value="<?php echo htmlspecialchars($config['email_contacto'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- PESTAÑA 2: ACADÉMICO -->
                            <div class="tab-pane fade" id="academico" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Calificación Mínima Aprobatoria <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="min_calificacion_aprobatoria" 
                                               value="<?php echo htmlspecialchars($config['min_calificacion_aprobatoria'] ?? 60); ?>" min="0" max="100" required>
                                        <small class="text-muted">Valor mínimo para aprobar (0-100)</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Escala de Calificaciones <span class="text-danger">*</span></label>
                                        <select class="form-select" name="escala_calificaciones" required>
                                            <option value="0-100" <?php echo ($config['escala_calificaciones'] ?? '0-100') == '0-100' ? 'selected' : ''; ?>>0 - 100</option>
                                            <option value="0-10" <?php echo ($config['escala_calificaciones'] ?? '') == '0-10' ? 'selected' : ''; ?>>0 - 10</option>
                                            <option value="letras" <?php echo ($config['escala_calificaciones'] ?? '') == 'letras' ? 'selected' : ''; ?>>Letras (A-F)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Meses del Ciclo Escolar</label>
                                        <input type="text" class="form-control" name="meses_ciclo_escolar" 
                                               value="<?php echo htmlspecialchars($config['meses_ciclo_escolar'] ?? '08,09,10,11,12,01,02,03,04,05'); ?>">
                                        <small class="text-muted">Meses separados por comas (01=Enero, 08=Agosto)</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Grados Activos</label>
                                        <input type="text" class="form-control" name="grados_activos" 
                                               value="<?php echo htmlspecialchars($config['grados_activos'] ?? 'Primaria,Secundaria,Bachillerato'); ?>">
                                        <small class="text-muted">Separados por comas</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- PESTAÑA 3: SEGURIDAD -->
                            <div class="tab-pane fade" id="seguridad" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Tiempo de Sesión (minutos) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="tiempo_sesion" 
                                               value="<?php echo htmlspecialchars($config['tiempo_sesion'] ?? 30); ?>" min="5" max="120" required>
                                        <small class="text-muted">Inactividad antes de cerrar sesión</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Longitud Mínima de Contraseña <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="longitud_min_password" 
                                               value="<?php echo htmlspecialchars($config['longitud_min_password'] ?? 8); ?>" min="6" max="20" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Intentos Fallidos Máximos <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="intentos_fallidos_max" 
                                               value="<?php echo htmlspecialchars($config['intentos_fallidos_max'] ?? 5); ?>" min="1" max="10" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="password_requiere_numeros" id="password_numeros" 
                                                   <?php echo ($config['password_requiere_numeros'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="password_numeros">
                                                <strong>Requerir números en contraseñas</strong>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="password_requiere_especiales" id="password_especiales" 
                                                   <?php echo ($config['password_requiere_especiales'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="password_especiales">
                                                <strong>Requerir caracteres especiales en contraseñas</strong>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="acceso_ip_restringido" id="acceso_ip" 
                                                   <?php echo ($config['acceso_ip_restringido'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="acceso_ip">
                                                <strong>Restringir acceso por IP</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">Especificar rangos IP permitidos (ej: 192.168.1.0/24, 10.0.0.5)</small>
                                        <textarea class="form-control mt-2" name="rangos_ip_permitidos" rows="2"><?php echo htmlspecialchars($config['rangos_ip_permitidos'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- PESTAÑA 4: BOLETAS -->
                            <div class="tab-pane fade" id="boletas" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Plantilla Predeterminada</label>
                                        <select class="form-select" name="plantilla_predeterminada">
                                            <option value="">Seleccionar plantilla...</option>
                                            <?php foreach ($plantillas_activas as $plantilla): ?>
                                                <option value="<?php echo $plantilla['id']; ?>" 
                                                    <?php echo ($config['plantilla_predeterminada'] ?? 0) == $plantilla['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($plantilla['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Se usará al generar boletas sin selección</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Firmas Requeridas</label>
                                        <select class="form-select" name="firmas_requeridas">
                                            <option value="director" <?php echo ($config['firmas_requeridas'] ?? 'director') == 'director' ? 'selected' : ''; ?>>Solo Director</option>
                                            <option value="director,supervisor" <?php echo ($config['firmas_requeridas'] ?? '') == 'director,supervisor' ? 'selected' : ''; ?>>Director y Supervisor</option>
                                            <option value="todas" <?php echo ($config['firmas_requeridas'] ?? '') == 'todas' ? 'selected' : ''; ?>>Todas las autoridades</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="mostrar_observaciones" id="mostrar_obs" 
                                                   <?php echo ($config['mostrar_observaciones'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="mostrar_obs">
                                                <strong>Mostrar sección de observaciones en boletas</strong>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="incluir_logo_boletas" id="incluir_logo" 
                                                   <?php echo ($config['incluir_logo_boletas'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="incluir_logo">
                                                <strong>Incluir logo de la institución en boletas</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- PESTAÑA 5: PERMISOS -->
                            <div class="tab-pane fade" id="permisos" role="tabpanel">
                                <div class="permission-grid">
                                    <div class="permission-item">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="ver_calificaciones_otros" id="ver_calif" 
                                                   <?php echo ($config['ver_calificaciones_otros'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="ver_calif">
                                                Profesores ven calificaciones de otros
                                            </label>
                                        </div>
                                        <small class="text-muted">Permite a profesores ver reportes completos de calificaciones</small>
                                    </div>
                                    <div class="permission-item">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="editar_alumnos_asignados" id="editar_alumnos" 
                                                   <?php echo ($config['editar_alumnos_asignados'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="editar_alumnos">
                                                Profesores editan sus alumnos
                                            </label>
                                        </div>
                                        <small class="text-muted">Permite modificar datos de alumnos asignados</small>
                                    </div>
                                    <div class="permission-item">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="exportar_reportes" id="exportar_rep" 
                                                   <?php echo ($config['exportar_reportes'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="exportar_rep">
                                                Permitir exportar reportes
                                            </label>
                                        </div>
                                        <small class="text-muted">Habilita botones de exportación CSV/PDF</small>
                                    </div>
                                    <div class="permission-item">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="eliminar_registros" id="eliminar_reg" 
                                                   <?php echo ($config['eliminar_registros'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="eliminar_reg">
                                                Permitir eliminar registros
                                            </label>
                                        </div>
                                        <small class="text-muted">Controla botones de eliminación por rol</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- PESTAÑA 6: APARIENCIA -->
                            <div class="tab-pane fade" id="apariencia" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tema Predeterminado</label>
                                        <select class="form-select" name="tema_predeterminado">
                                            <option value="light" <?php echo ($config['tema_predeterminado'] ?? 'light') == 'light' ? 'selected' : ''; ?>>Claro (Light)</option>
                                            <option value="dark" <?php echo ($config['tema_predeterminado'] ?? '') == 'dark' ? 'selected' : ''; ?>>Oscuro (Dark)</option>
                                            <option value="auto" <?php echo ($config['tema_predeterminado'] ?? '') == 'auto' ? 'selected' : ''; ?>>Auto (Según preferencia)</option>
                                        </select>
                                        <small class="text-muted">Tema inicial para nuevos usuarios</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Logo del Sistema</label>
                                        <input type="text" class="form-control" name="logo_sistema" 
                                               value="<?php echo htmlspecialchars($config['logo_sistema'] ?? ''); ?>">
                                        <small class="text-muted">Ruta del logo para login y header (ej: uploads/logo.png)</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Color Primario</label>
                                        <input type="color" class="form-control form-control-color" name="color_primario" 
                                               value="<?php echo htmlspecialchars($config['color_primario'] ?? '#2c3e50'); ?>" title="Color primario">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Color Secundario</label>
                                        <input type="color" class="form-control form-control-color" name="color_secundario" 
                                               value="<?php echo htmlspecialchars($config['color_secundario'] ?? '#3498db'); ?>" title="Color secundario">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atención:</strong> Los cambios en esta configuración afectarán a todo el sistema y a todos los usuarios. 
                            Algunos cambios requerirán cerrar y reabrir sesión para aplicar.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                            <button type="submit" name="update_config" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Information Card -->
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Acceso Restringido - Nivel de Acceso: MÁXIMO</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-lock me-2"></i>
                        <strong>Área exclusiva para personal de Sistemas</strong>
                    </div>
                    <p class="mb-3">Esta sección permite configurar parámetros críticos que afectan a todo el sistema escolar:</p>
                    <ul class="mb-4">
                        <li><strong>Control académico:</strong> Calificaciones, escalas, ciclos escolares</li>
                        <li><strong>Seguridad del sistema:</strong> Políticas de contraseña, sesiones, acceso por IP</li>
                        <li><strong>Personalización de boletas:</strong> Plantillas, firmas, observaciones</li>
                        <li><strong>Gestión de permisos:</strong> Control de acceso por roles y funcionalidades</li>
                        <li><strong>Apariencia institucional:</strong> Colores, logo, tema predeterminado</li>
                    </ul>
                    <div class="text-center">
                        <span class="badge bg-danger fs-6">
                            <i class="fas fa-shield-alt me-1"></i>Configuración Crítica - Requiere Autorización
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
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