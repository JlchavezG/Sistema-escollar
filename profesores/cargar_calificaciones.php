<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireProfesor();

$db = new Database();

// Obtener parámetros
$modulo_id = isset($_GET['modulo']) ? intval($_GET['modulo']) : 0;
$grado = isset($_GET['grado']) ? $_GET['grado'] : '';
$grupo = isset($_GET['grupo']) ? $_GET['grupo'] : '';

// Obtener información del módulo
$modulo_info = null;
if ($modulo_id > 0 && $grado && $grupo) {
    $db->query("SELECT m.*, am.grado, am.grupo FROM modulos m 
                INNER JOIN asignacion_modulos am ON m.id = am.modulo_id 
                WHERE m.id = :modulo_id AND am.profesor_id = :profesor_id 
                AND am.grado = :grado AND am.grupo = :grupo");
    $db->bind(':modulo_id', $modulo_id);
    $db->bind(':profesor_id', $_SESSION['user_id']);
    $db->bind(':grado', $grado);
    $db->bind(':grupo', $grupo);
    $modulo_info = $db->single();
}

// Obtener alumnos del grupo
$db->query("SELECT * FROM alumnos WHERE grado = :grado AND grupo = :grupo ORDER BY apellido_paterno, nombre");
$db->bind(':grado', $grado);
$db->bind(':grupo', $grupo);
$alumnos = $db->resultSet();

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
if ($modulo_id > 0 && $grado && $grupo) {
    $db->query("SELECT * FROM calificaciones WHERE modulo_id = :modulo_id AND profesor_id = :profesor_id 
                AND grado = :grado AND grupo = :grupo AND mes = :mes");
    $db->bind(':modulo_id', $modulo_id);
    $db->bind(':profesor_id', $_SESSION['user_id']);
    $db->bind(':grado', $grado);
    $db->bind(':grupo', $grupo);
    $db->bind(':mes', date('F'));
    $calificaciones_existentes = $db->resultSet();

    // Convertir a array asociativo para fácil acceso
    $calificaciones_array = [];
    foreach ($calificaciones_existentes as $cal) {
        $calificaciones_array[$cal['alumno_id']] = $cal;
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
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

        .module-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .grade-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .grade-table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .grade-table tbody tr:hover {
            background: #f8f9fa;
        }

        .form-control-grade {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s;
        }

        .form-control-grade:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-control-grade:valid {
            border-color: #48bb78;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .month-selector {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                margin-left: 220px;
            }

            .grade-table {
                font-size: 0.9rem;
            }

            .form-control-grade {
                padding: 8px;
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="cargar_calificaciones.php">
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
                    <a href="dashboard.php" class="btn btn-outline-secondary mb-3">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                    </a>
                    <h2 class="mb-0">Cargar Calificaciones</h2>
                    <p class="text-muted">Módulo: <?php echo $modulo_info ? $modulo_info['nombre'] : 'No especificado'; ?></p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($modulo_info): ?>
                <!-- Module Info -->
                <div class="module-header mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-2"><?php echo $modulo_info['nombre']; ?></h4>
                            <p class="mb-1"><strong>Clave:</strong> <?php echo $modulo_info['clave']; ?></p>
                            <p class="mb-1"><strong>Grado:</strong> <?php echo $modulo_info['grado']; ?></p>
                            <p class="mb-0"><strong>Grupo:</strong> <?php echo $modulo_info['grupo']; ?></p>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-light text-dark fs-6">
                                <i class="fas fa-calendar me-2"></i><?php echo CYCLE_ACTUAL; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Month Selector -->
                <div class="month-selector mb-4">
                    <form method="GET" action="">
                        <input type="hidden" name="modulo" value="<?php echo $modulo_id; ?>">
                        <input type="hidden" name="grado" value="<?php echo $grado; ?>">
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

                <!-- Grade Form -->
                <form method="POST" action="">
                    <input type="hidden" name="mes" value="<?php echo $mes_actual; ?>">

                    <div class="grade-table">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Alumno</th>
                                        <th style="width: 200px;">Calificación</th>
                                        <th style="width: 50px;">Status</th>
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
                                                    <strong><?php echo $alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $alumno['nombre']; ?></small>
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
                                    placeholder="Observaciones generales para todas las calificaciones..."><?php echo $cal_existente ? $cal_existente['observaciones'] : ''; ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Guardar Todas las Calificaciones
                                </button>
                            </div>

                            <div class="mt-3 text-center text-muted small">
                                <i class="fas fa-info-circle me-2"></i>
                                Las calificaciones se guardarán para el mes de <strong><?php echo $mes_actual; ?></strong>
                            </div>
                        </div>
                    </div>
                </form>

            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No se encontró información del módulo o no tienes permisos para acceder.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/calificaciones.js"></script>
</body>

</html>