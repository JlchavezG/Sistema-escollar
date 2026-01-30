<!-- Sidebar - Profesores -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo mb-3">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <h3><?php echo APP_NAME; ?></h3>
        <p class="text-white-50 small">Profesor Panel</p>
    </div>

    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cargar_calificaciones.php' ? 'active' : ''; ?>" href="cargar_calificaciones.php">
                    <i class="fas fa-edit"></i>
                    <span class="nav-text">Cargar Calificaciones</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ver_modulos.php' ? 'active' : ''; ?>" href="ver_modulos.php">
                    <i class="fas fa-book"></i>
                    <span class="nav-text">Mis Módulos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ver_notificaciones.php' ? 'active' : ''; ?>" href="ver_notificaciones.php">
                    <i class="fas fa-bell"></i>
                    <span class="nav-text">Notificaciones
                        <?php
                        // Mostrar contador de no leídas
                        $db_count = new Database();
                        $db_count->query("SELECT COUNT(*) as total FROM notificaciones WHERE destinatario_id = :destinatario_id AND leido = FALSE");
                        $db_count->bind(':destinatario_id', $_SESSION['user_id']);
                        $count = $db_count->single();
                        if ($count['total'] > 0) {
                            echo '<span class="badge bg-danger ms-1">' . $count['total'] . '</span>';
                        }
                        ?>
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>" href="perfil.php">
                    <i class="fas fa-user"></i>
                    <span class="nav-text">Mi Perfil</span>
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </div>
</div>