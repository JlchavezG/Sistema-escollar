<!-- Sidebar - Profesores -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo mb-3">
            <i class="fas fa-chalkboard-teacher fa-2x"></i>
        </div>
        <h3><?php echo APP_NAME; ?></h3>
        <p class="text-white-50 small">Profesor Panel</p>
    </div>
    
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cargar_calificaciones.php' ? 'active' : ''; ?>" href="cargar_calificaciones.php">
                    <i class="fas fa-edit"></i> Cargar Calificaciones
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ver_modulos.php' ? 'active' : ''; ?>" href="ver_modulos.php">
                    <i class="fas fa-book"></i> Mis Módulos
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