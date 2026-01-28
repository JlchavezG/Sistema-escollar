<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireProfesor();

$db = new Database();

// Obtener parámetros (con manejo de errores mejorado)
$modulo_id = isset($_GET['modulo']) ? intval($_GET['modulo']) : 0;
$grado = isset($_GET['grado']) ? urldecode($_GET['grado']) : '';
$grupo = isset($_GET['grupo']) ? $_GET['grupo'] : '';

// Verificar si el profesor tiene módulos asignados
$db->query("SELECT COUNT(*) as total FROM asignacion_modulos WHERE profesor_id = :profesor_id AND ciclo_escolar = :ciclo");
$db->bind(':profesor_id', $_SESSION['user_id']);
$db->bind(':ciclo', CYCLE_ACTUAL);
$tiene_modulos = $db->single()['total'] > 0;

// Obtener información del módulo (con verificación mejorada)
$modulo_info = null;
$modulo_valido = false;

if ($modulo_id > 0 && $grado && $grupo) {
    $db->query("SELECT m.*, am.grado, am.grupo, am.id as asignacion_id 
                FROM modulos m 
                INNER JOIN asignacion_modulos am ON m.id = am.modulo_id 
                WHERE m.id = :modulo_id 
                AND am.profesor_id = :profesor_id 
                AND am.grado = :grado 
                AND am.grupo = :grupo 
                AND am.ciclo_escolar = :ciclo");
    $db->bind(':modulo_id', $modulo_id);
    $db->bind(':profesor_id', $_SESSION['user_id']);
    $db->bind(':grado', $grado);
    $db->bind(':grupo', $grupo);
    $db->bind(':ciclo', CYCLE_ACTUAL);
    $modulo_info = $db->single();
    
    $modulo_valido = $modulo_info !== false && $db->rowCount() > 0;
}

// Obtener alumnos del grupo
$alumnos = [];
if ($grado && $grupo) {
    $db->query("SELECT * FROM alumnos WHERE grado = :grado AND grupo = :grupo ORDER BY apellido_paterno, nombre");
    $db->bind(':grado', $grado);
    $db->bind(':grupo', $grupo);
    $alumnos = $db->resultSet();
}

// Procesar envío de calificaciones
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();
        
        $mes = sanitizeInput($_POST['mes']);
        $observaciones_generales = sanitizeInput($_POST['observaciones_generales']);
        
        foreach ($_POST['calificaciones'] as $alumno_id => $calificacion) {
            if ($calificacion !== '') {
                $calificacion = floatval($calificacion);
                
                // Verificar si ya existe una calificación para este alumno en este mes
                $db->query("SELECT id FROM calificaciones 
                            WHERE alumno_id = :alumno_id AND modulo_id = :modulo_id 
                            AND profesor_id = :profesor_id AND mes = :mes 
                            AND grado = :grado AND grupo = :grupo");
                $db->bind(':alumno_id', $alumno_id);
                $db->bind(':modulo_id', $modulo_id);
                $db->bind(':profesor_id', $_SESSION['user_id']);
                $db->bind(':mes', $mes);
                $db->bind(':grado', $grado);
                $db->bind(':grupo', $grupo);
                
                if ($db->rowCount() > 0) {
                    // Actualizar calificación existente
                    $db->query("UPDATE calificaciones SET calificacion = :calificacion, 
                                observaciones = :observaciones WHERE alumno_id = :alumno_id 
                                AND modulo_id = :modulo_id AND profesor_id = :profesor_id 
                                AND mes = :mes AND grado = :grado AND grupo = :grupo");
                } else {
                    // Insertar nueva calificación
                    $db->query("INSERT INTO calificaciones (alumno_id, modulo_id, profesor_id, grado, grupo, ciclo_escolar, mes, calificacion, observaciones) 
                                VALUES (:alumno_id, :modulo_id, :profesor_id, :grado, :grupo, :ciclo, :mes, :calificacion, :observaciones)");
                }
                
                $db->bind(':calificacion', $calificacion);
                $db->bind(':observaciones', $observaciones_generales);
                $db->execute();
            }
        }
        
        $db->endTransaction();
        $message = 'Calificaciones guardadas exitosamente';
        $message_type = 'success';
        
    } catch (Exception $e) {
        $db->cancelTransaction();
        $message = 'Error al guardar las calificaciones: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Obtener calificaciones existentes para el mes actual
$calificaciones_existentes = [];
$calificaciones_array = []; // Inicializar array vacío
$observaciones_generales_value = ''; // Inicializar variable para observaciones

if ($modulo_valido) {
    $db->query("SELECT * FROM calificaciones WHERE modulo_id = :modulo_id AND profesor_id = :profesor_id 
                AND grado = :grado AND grupo = :grupo AND mes = :mes");
    $db->bind(':modulo_id', $modulo_id);
    $db->bind(':profesor_id', $_SESSION['user_id']);
    $db->bind(':grado', $grado);
    $db->bind(':grupo', $grupo);
    $db->bind(':mes', date('F'));
    $calificaciones_existentes = $db->resultSet();
    
    // Convertir a array asociativo para fácil acceso y obtener observaciones
    foreach ($calificaciones_existentes as $cal) {
        $calificaciones_array[$cal['alumno_id']] = $cal;
        // Obtener las observaciones de la primera calificación (serán las mismas para todas)
        if (empty($observaciones_generales_value)) {
            $observaciones_generales_value = $cal['observaciones'] ?? '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Calificaciones - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
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
                    <a href="dashboard.php" class="btn btn-outline-secondary mb-3">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                    </a>
                    <h2 class="mb-0">Cargar Calificaciones</h2>
                    <?php if ($modulo_valido): ?>
                        <p class="text-muted">Módulo: <?php echo htmlspecialchars($modulo_info['nombre']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Mensaje de bienvenida si no hay módulos -->
            <?php if (!$tiene_modulos): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading">⚠️ No tienes módulos asignados</h5>
                            <p class="mb-0">Actualmente no tienes ningún módulo asignado. Por favor, contacta al administrador del sistema para que te asigne módulos.</p>
                            <p class="mb-0 mt-2"><strong>Pasos para el administrador:</strong></p>
                            <ol class="mb-0 mt-2">
                                <li>Ir a "Asignar Módulos"</li>
                                <li>Seleccionar tu nombre como profesor</li>
                                <li>Asignar los módulos, grado y grupo correspondientes</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Volver al Dashboard
                    </a>
                </div>
                
            <?php elseif (!$modulo_valido && ($modulo_id > 0 || $grado || $grupo)): ?>
                <!-- Error: Módulo no válido -->
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading">❌ Acceso no autorizado</h5>
                            <p class="mb-0">No se encontró información del módulo o no tienes permisos para acceder a esta sección.</p>
                            <p class="mb-0 mt-2">Verifica que:</p>
                            <ul class="mb-0 mt-2">
                                <li>El módulo esté correctamente asignado a tu perfil</li>
                                <li>Los parámetros de grado y grupo sean correctos</li>
                                <li>El ciclo escolar esté activo</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="ver_modulos.php" class="btn btn-info">
                        <i class="fas fa-book me-2"></i>Ver Mis Módulos
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-home me-2"></i>Volver al Dashboard
                    </a>
                </div>
                
            <?php elseif (!$modulo_id || !$grado || !$grupo): ?>
                <!-- Sin parámetros - mostrar instrucciones -->
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading">ℹ️ Selecciona un módulo para cargar calificaciones</h5>
                            <p class="mb-0">Para cargar calificaciones, primero debes seleccionar un módulo de tu lista de módulos asignados.</p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="ver_modulos.php" class="btn btn-primary">
                        <i class="fas fa-book me-2"></i>Ir a Mis Módulos
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-home me-2"></i>Volver al Dashboard
                    </a>
                </div>
                
            <?php else: ?>
                <!-- Formulario de calificaciones (módulo válido) -->
                <?php if ($modulo_info): ?>
                    <!-- Module Info -->
                    <div class="page-header mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="mb-2"><?php echo htmlspecialchars($modulo_info['nombre']); ?></h4>
                                <p class="mb-1"><strong>Clave:</strong> <?php echo htmlspecialchars($modulo_info['clave']); ?></p>
                                <p class="mb-1"><strong>Grado:</strong> <?php echo htmlspecialchars($modulo_info['grado']); ?></p>
                                <p class="mb-0"><strong>Grupo:</strong> <?php echo htmlspecialchars($modulo_info['grupo']); ?></p>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <span class="badge bg-primary fs-6">
                                    <i class="fas fa-calendar me-2"></i><?php echo CYCLE_ACTUAL; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Month Selector -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="">
                                <input type="hidden" name="modulo" value="<?php echo $modulo_id; ?>">
                                <input type="hidden" name="grado" value="<?php echo urlencode($grado); ?>">
                                <input type="hidden" name="grupo" value="<?php echo $grupo; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <label for="mes" class="form-label mb-0 fw-bold">
                                            <i class="fas fa-calendar-alt me-2"></i>Mes:
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="form-select" id="mes" name="mes" onchange="this.form.submit()">
                                            <?php 
                                            $meses = getMonths();
                                            $mes_actual = isset($_GET['mes']) ? $_GET['mes'] : date('F');
                                            foreach ($meses as $mes):
                                            ?>
                                                <option value="<?php echo $mes; ?>" <?php echo $mes == $mes_actual ? 'selected' : ''; ?>>
                                                    <?php echo $mes; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Selecciona el mes para ver/guardar calificaciones
                                        </small>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Grade Form -->
                    <form method="POST" action="">
                        <input type="hidden" name="mes" value="<?php echo $mes_actual; ?>">
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Alumnos</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 50px;">#</th>
                                                <th>Alumno</th>
                                                <th style="width: 200px;">Calificación</th>
                                                <th style="width: 100px;">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($alumnos) > 0): ?>
                                                <?php $contador = 1; ?>
                                                <?php foreach ($alumnos as $alumno): ?>
                                                    <?php 
                                                    $cal_existente = isset($calificaciones_array[$alumno['id']]) ? $calificaciones_array[$alumno['id']] : null;
                                                    $cal_value = $cal_existente ? $cal_existente['calificacion'] : '';
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $contador++; ?></td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($alumno['nombre']); ?></small>
                                                        </td>
                                                        <td>
                                                            <input type="number" 
                                                                   class="form-control form-control-grade" 
                                                                   name="calificaciones[<?php echo $alumno['id']; ?>]" 
                                                                   value="<?php echo $cal_value; ?>"
                                                                   min="0" 
                                                                   max="100" 
                                                                   step="0.1"
                                                                   placeholder="0.0"
                                                                   required>
                                                        </td>
                                                        <td>
                                                            <?php if ($cal_existente): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check"></i> Guardado
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">
                                                                    <i class="fas fa-clock"></i> Nuevo
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-5">
                                                        <div class="alert alert-info mb-0">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            No hay alumnos registrados en este grupo
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Observations and Save Button -->
                                <div class="p-4 bg-light border-top">
                                    <div class="mb-3">
                                        <label for="observaciones_generales" class="form-label fw-bold">
                                            <i class="fas fa-sticky-note me-2"></i>Observaciones Generales (Opcional)
                                        </label>
                                        <textarea class="form-control" 
                                                  id="observaciones_generales" 
                                                  name="observaciones_generales" 
                                                  rows="3" 
                                                  placeholder="Observaciones generales para todas las calificaciones..."><?php echo htmlspecialchars($observaciones_generales_value); ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Guardar Todas las Calificaciones
                                        </button>
                                    </div>
                                    
                                    <div class="mt-3 text-center text-muted small">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Las calificaciones se guardarán para el mes de <strong><?php echo $mes_actual; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Validación de calificaciones
        document.addEventListener('DOMContentLoaded', function() {
            // Validar que las calificaciones estén entre 0 y 100
            const gradeInputs = document.querySelectorAll('.form-control-grade');
            
            gradeInputs.forEach(input => {
                input.addEventListener('input', function() {
                    let value = parseFloat(this.value);
                    
                    if (value > 100) {
                        this.value = 100;
                        alert('La calificación máxima es 100');
                    } else if (value < 0) {
                        this.value = 0;
                        alert('La calificación mínima es 0');
                    }
                    
                    // Validación visual
                    if (value >= 0 && value <= 100) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                });
                
                input.addEventListener('blur', function() {
                    if (this.value && (parseFloat(this.value) < 0 || parseFloat(this.value) > 100)) {
                        this.value = '';
                        this.classList.remove('is-valid', 'is-invalid');
                    }
                });
            });
            
            // Atajo de teclado para guardar (Ctrl + S)
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    const saveButton = document.querySelector('button[type="submit"]');
                    if (saveButton) {
                        saveButton.click();
                        alert('Guardando calificaciones...');
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