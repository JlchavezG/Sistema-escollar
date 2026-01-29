<!-- Sidebar - Administrativos -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo mb-3">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h3><?php echo APP_NAME; ?></h3>
        <p class="text-white-50 small">
            <?php 
            $role_labels = [
                'profesor' => 'Profesor Panel',
                'administrativo' => 'Admin Panel',
                'sistemas' => 'Sistemas Panel'
            ];
            // Usar user_role de sesión (config.php ya inició sesión)
            echo $role_labels[$_SESSION['user_role']] ?? 'Panel';
            ?>
        </p>
    </div>
    
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            
            <?php if ($_SESSION['user_role'] === 'sistemas'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_usuarios.php' ? 'active' : ''; ?>" href="gestion_usuarios.php">
                    <i class="fas fa-users-cog"></i>
                    <span class="nav-text">Gestión de Usuarios</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'configuracion_sistema.php' ? 'active' : ''; ?>" href="configuracion_sistema.php">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">Configuración del Sistema</span>
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_usuarios.php' ? 'active' : ''; ?>" href="gestion_usuarios.php">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span class="nav-text">Gestión de Profesores</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_alumnos.php' ? 'active' : ''; ?>" href="gestion_alumnos.php">
                    <i class="fas fa-user-graduate"></i>
                    <span class="nav-text">Gestión de Alumnos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_modulos.php' ? 'active' : ''; ?>" href="gestion_modulos.php">
                    <i class="fas fa-book-open"></i>
                    <span class="nav-text">Gestión de Módulos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'asignar_modulos.php' ? 'active' : ''; ?>" href="asignar_modulos.php">
                    <i class="fas fa-book"></i>
                    <span class="nav-text">Asignar Módulos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'generar_boletas.php' ? 'active' : ''; ?>" href="generar_boletas.php">
                    <i class="fas fa-file-alt"></i>
                    <span class="nav-text">Generar Boletas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'plantillas_boletas.php' ? 'active' : ''; ?>" href="plantillas_boletas.php">
                    <i class="fas fa-palette"></i>
                    <span class="nav-text">Plantillas de Boletas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>" href="reportes.php">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Reportes</span>
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