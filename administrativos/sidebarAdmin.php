<!-- Sidebar - Administrativos -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo mb-3">
            <i class="fas fa-graduation-cap fa-2x"></i>
        </div>
        <h3><?php echo APP_NAME; ?></h3>
        <p class="text-white-50 small">Admin Panel</p>
    </div>
    
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_usuarios.php' ? 'active' : ''; ?>" href="gestion_usuarios.php">
                    <i class="fas fa-users"></i> Gestión de Usuarios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_alumnos.php' ? 'active' : ''; ?>" href="gestion_alumnos.php">
                    <i class="fas fa-user-graduate"></i> Gestión de Alumnos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_modulos.php' ? 'active' : ''; ?>" href="gestion_modulos.php">
                    <i class="fas fa-book-open"></i> Gestión de Módulos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'asignar_modulos.php' ? 'active' : ''; ?>" href="asignar_modulos.php">
                    <i class="fas fa-book"></i> Asignar Módulos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'generar_boletas.php' ? 'active' : ''; ?>" href="generar_boletas.php">
                    <i class="fas fa-file-alt"></i> Generar Boletas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'plantillas_boletas.php' ? 'active' : ''; ?>" href="plantillas_boletas.php">
                    <i class="fas fa-palette"></i> Plantillas de Boletas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>" href="reportes.php">
                    <i class="fas fa-chart-bar"></i> Reportes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>" href="perfil.php">
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