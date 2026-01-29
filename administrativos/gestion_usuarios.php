<?php
require_once '../includes/config.php';
$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$message = '';
$message_type = '';

// Manejar acciones (crear, editar, eliminar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'create' || $action == 'update') {
            $nombre = sanitizeInput($_POST['nombre']);
            $apellido_paterno = sanitizeInput($_POST['apellido_paterno']);
            $apellido_materno = sanitizeInput($_POST['apellido_materno']);
            $email = sanitizeInput($_POST['email']);
            $telefono = sanitizeInput($_POST['telefono']);
            $tipo = sanitizeInput($_POST['tipo']);
            
            $password = $action == 'create' ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
            
            try {
                if ($action == 'create') {
                    $db->query("INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, email, password, tipo, telefono, created_at) 
                                VALUES (:nombre, :apellido_paterno, :apellido_materno, :email, :password, :tipo, :telefono, NOW())");
                    $db->bind(':password', $password);
                } else {
                    $id = intval($_POST['id']);
                    $db->query("UPDATE usuarios SET nombre = :nombre, apellido_paterno = :apellido_paterno, 
                                apellido_materno = :apellido_materno, email = :email, tipo = :tipo, telefono = :telefono, 
                                updated_at = NOW() WHERE id = :id");
                    $db->bind(':id', $id);
                }
                
                $db->bind(':nombre', $nombre);
                $db->bind(':apellido_paterno', $apellido_paterno);
                $db->bind(':apellido_materno', $apellido_materno);
                $db->bind(':email', $email);
                $db->bind(':tipo', $tipo);
                $db->bind(':telefono', $telefono);
                
                if ($db->execute()) {
                    $message = $action == 'create' ? 'Usuario creado exitosamente' : 'Usuario actualizado exitosamente';
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } elseif ($action == 'delete') {
            $id = intval($_POST['id']);
            
            if ($id == $_SESSION['user_id']) {
                $message = 'No puedes eliminarte a ti mismo';
                $message_type = 'warning';
            } else {
                $db->query("DELETE FROM usuarios WHERE id = :id");
                $db->bind(':id', $id);
                
                if ($db->execute()) {
                    $message = 'Usuario eliminado exitosamente';
                    $message_type = 'success';
                } else {
                    $message = 'Error al eliminar usuario';
                    $message_type = 'danger';
                }
            }
        } elseif ($action == 'change_password') {
            $id = intval($_POST['id']);
            $new_password = $_POST['new_password'];
            
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $db->query("UPDATE usuarios SET password = :password, updated_at = NOW() WHERE id = :id");
            $db->bind(':password', $password_hash);
            $db->bind(':id', $id);
            
            if ($db->execute()) {
                $message = 'Contraseña actualizada exitosamente';
                $message_type = 'success';
            } else {
                $message = 'Error al actualizar contraseña';
                $message_type = 'danger';
            }
        }
    }
}

// Obtener todos los usuarios
$db->query("SELECT * FROM usuarios ORDER BY tipo DESC, apellido_paterno ASC");
$usuarios = $db->resultSet();

// Obtener usuario para editar (si existe)
$edit_user = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $db->query("SELECT * FROM usuarios WHERE id = :id");
    $db->bind(':id', $id);
    $edit_user = $db->single();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
     <!-- Sidebar Toggle JS -->
    <script src="../assets/js/sidebar-toggle.js" defer></script>
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
                            <i class="fas fa-users me-3"></i>
                            Gestión de Usuarios
                        </h2>
                        <p class="text-muted mb-0 mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Administra profesores y personal administrativo del sistema
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus me-2"></i>Nuevo Usuario
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid mb-4">
                <?php
                $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'profesor'");
                $total_profesores = $db->single()['total'];
                
                $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'administrativo'");
                $total_administrativos = $db->single()['total'];
                ?>
                
                <div class="stat-item">
                    <i class="fas fa-chalkboard-teacher fa-2x text-info"></i>
                    <div class="stat-number"><?php echo $total_profesores; ?></div>
                    <div class="stat-label">Profesores</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-user-tie fa-2x text-primary"></i>
                    <div class="stat-number"><?php echo $total_administrativos; ?></div>
                    <div class="stat-label">Administrativos</div>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-users fa-2x text-secondary"></i>
                    <div class="stat-number"><?php echo $total_profesores + $total_administrativos; ?></div>
                    <div class="stat-label">Total Usuarios</div>
                </div>
            </div>
            
            <!-- Users List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Usuarios</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Nombre Completo</th>
                                    <th>Correo Electrónico</th>
                                    <th>Teléfono</th>
                                    <th>Tipo</th>
                                    <th>Registrado</th>
                                    <th style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($usuarios) > 0): ?>
                                    <?php $contador = 1; ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo $contador++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($usuario['apellido_paterno'] . ' ' . $usuario['apellido_materno']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($usuario['nombre']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['telefono'] ?: 'N/A'); ?></td>
                                            <td>
                                                <?php if ($usuario['tipo'] == 'profesor'): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-chalkboard-teacher me-1"></i>Profesor
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-user-tie me-1"></i>Administrativo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#userModal"
                                                            onclick='editUser(<?php echo json_encode($usuario); ?>)'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            data-bs-toggle="modal" data-bs-target="#passwordModal"
                                                            onclick='changePassword(<?php echo $usuario['id']; ?>, "<?php echo htmlspecialchars($usuario['nombre']); ?>")'>
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick='deleteUser(<?php echo $usuario['id']; ?>, "<?php echo htmlspecialchars($usuario['nombre']); ?>")'>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-users-slash fa-3x mb-3"></i>
                                                <p>No hay usuarios registrados</p>
                                                <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#userModal">
                                                    <i class="fas fa-plus me-2"></i>Crear Primer Usuario
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
    
    <!-- Modal: Crear/Editar Usuario -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" id="modal_action" value="create">
                    <input type="hidden" name="id" id="user_id">
                    
                    <div class="modal-header gradient-primary">
                        <h5 class="modal-title text-white" id="modal_title">
                            <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Nombre(s) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" id="nombre" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Apellido Paterno <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="apellido_paterno" id="apellido_paterno" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Apellido Materno</label>
                                <input type="text" class="form-control" name="apellido_materno" id="apellido_materno">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="telefono" placeholder="555-1234">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tipo de Usuario <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo" id="tipo" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="profesor">Profesor</option>
                                    <option value="administrativo">Administrativo</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3" id="password_field">
                                <label class="form-label fw-bold">Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password" 
                                           placeholder="Mínimo 8 caracteres" minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Solo para nuevo usuario. Dejar vacío para actualizar.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><span id="btn_save_text">Guardar Usuario</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal: Cambiar Contraseña -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="id" id="pwd_user_id">
                    
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-key me-2"></i>Cambiar Contraseña
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <p id="pwd_user_name" class="mb-4"></p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nueva Contraseña <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="new_password" id="new_password" 
                                       required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Confirmar Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" required minlength="8">
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const icon = this.querySelector('i');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        document.getElementById('togglePwd').addEventListener('click', function() {
            const pwd = document.getElementById('new_password');
            const icon = this.querySelector('i');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        // Edit user
        function editUser(user) {
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Usuario';
            document.getElementById('modal_action').value = 'update';
            document.getElementById('btn_save_text').textContent = 'Actualizar Usuario';
            document.getElementById('password_field').style.display = 'none';
            
            document.getElementById('user_id').value = user.id;
            document.getElementById('nombre').value = user.nombre;
            document.getElementById('apellido_paterno').value = user.apellido_paterno;
            document.getElementById('apellido_materno').value = user.apellido_materno;
            document.getElementById('email').value = user.email;
            document.getElementById('telefono').value = user.telefono;
            document.getElementById('tipo').value = user.tipo;
        }
        
        // Reset modal on close
        document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario';
            document.getElementById('modal_action').value = 'create';
            document.getElementById('btn_save_text').textContent = 'Guardar Usuario';
            document.getElementById('password_field').style.display = 'block';
            document.getElementById('user_id').value = '';
        });
        
        // Change password
        function changePassword(id, name) {
            document.getElementById('pwd_user_id').value = id;
            document.getElementById('pwd_user_name').innerHTML = '<strong>Usuario:</strong> ' + name;
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
        }
        
        // Delete user with confirmation
        function deleteUser(id, name) {
            if (confirm('¿Estás seguro de que deseas eliminar al usuario "' + name + '"?\nEsta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'delete';
                
                const userId = document.createElement('input');
                userId.type = 'hidden';
                userId.name = 'id';
                userId.value = id;
                
                form.appendChild(action);
                form.appendChild(userId);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Validate password confirmation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const pwd = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (pwd !== confirm && confirm !== '') {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
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