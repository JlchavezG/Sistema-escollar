<?php
// Verificar si el usuario ya está logueado
session_start();
if (isset($_SESSION['user_id'])) {
    // Redirigir según el rol del usuario
    if ($_SESSION['user_role'] == 'profesor') {
        header('Location: profesores/dashboard.php');
        exit();
    } else {
        header('Location: administrativos/dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema Escolar Integral - Gestión de alumnos, profesores, calificaciones y más">
    <title><?php echo defined('APP_NAME') ? APP_NAME : 'Sistema Escolar Integral'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sitio.css">
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="system-logo floating">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="system-name"><?php echo defined('APP_NAME') ? APP_NAME : 'Sistema Escolar Integral'; ?></h1>
            <p class="system-description">
                Plataforma completa para la gestión educativa. Control total de alumnos, profesores, calificaciones, boletas y credenciales en un solo sistema seguro y eficiente.
            </p>
            <div class="cta-container">
                <a href="login.php" class="btn-landing btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </a>
                <a href="#features" class="btn-landing btn-demo">
                    <i class="fas fa-info-circle"></i> Conoce el Sistema
                </a>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Funcionalidades Destacadas</h2>
                <p>Descubre cómo nuestro sistema transforma la gestión educativa con herramientas poderosas e intuitivas</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Gestión de Alumnos</h3>
                    <p>Registra, organiza y monitorea a todos los estudiantes. Importación masiva vía CSV y control completo de información académica y personal.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>Control de Profesores</h3>
                    <p>Administra el cuerpo docente, asigna módulos por grado y grupo, y gestiona sus credenciales y acceso al sistema.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h3>Carga de Calificaciones</h3>
                    <p>Profesores pueden registrar calificaciones mensualmente con validaciones automáticas y vista previa en tiempo real.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Boletas Personalizadas</h3>
                    <p>Creador visual drag & drop para diseñar boletas únicas. Vista previa y generación de PDF con un solo clic.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <h3>Credenciales Estudiantiles</h3>
                    <p>Diseña credenciales en orientación vertical u horizontal. Impresión masiva optimizada con 8 credenciales por hoja.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Notificaciones en Tiempo Real</h3>
                    <p>Comunicación directa entre administrativos y profesores con sistema de prioridades y confirmación de lectura.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Reportes y Estadísticas</h3>
                    <p>Gráficos interactivos y exportación de datos para análisis completo del rendimiento académico y operativo.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h3>Configuración Avanzada</h3>
                    <p>Panel exclusivo para sistemas con control total de parámetros académicos, seguridad y personalización institucional.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Seguridad de Datos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Acceso Continuo</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">+15</div>
                    <div class="stat-label">Módulos Integrados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Disponibilidad</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3><?php echo defined('APP_NAME') ? APP_NAME : 'Sistema Escolar'; ?></h3>
                    <p style="color: rgba(255, 255, 255, 0.75); line-height: 1.7; margin-top: 1.2rem;">
                        Plataforma integral para la gestión educativa moderna. Eficiencia, seguridad y simplicidad en un solo sistema.
                    </p>
                </div>
                
                <div class="footer-column">
                    <h3>Accesos Rápidos</h3>
                    <ul class="footer-links">
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a></li>
                        <li><a href="#features"><i class="fas fa-star"></i> Funcionalidades</a></li>
                        <li><a href="login.php"><i class="fas fa-tachometer-alt"></i> Dashboard Admin</a></li>
                        <li><a href="login.php"><i class="fas fa-chalkboard-teacher"></i> Dashboard Profesor</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Soporte</h3>
                    <ul class="footer-links">
                        <li><a href="mailto:soporte@escuela.com"><i class="fas fa-envelope"></i> soporte@escuela.com</a></li>
                        <li><a href="tel:+525512345678"><i class="fas fa-phone"></i> +52 (55) 1234 5678</a></li>
                        <li><a href="#"><i class="fas fa-life-ring"></i> Ayuda y Documentación</a></li>
                        <li><a href="#"><i class="fas fa-file-alt"></i> Términos y Condiciones</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> <?php echo defined('APP_NAME') ? APP_NAME : 'Sistema Escolar Integral'; ?>. Todos los derechos reservados. | Desarrollado con ❤️ para la educación</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scroll para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Efecto de fade-in para elementos al hacer scroll
        document.addEventListener('DOMContentLoaded', function() {
            const featureCards = document.querySelectorAll('.feature-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            featureCards.forEach((card, index) => {
                card.style.opacity = 0;
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                setTimeout(() => observer.observe(card), 200 * index);
            });
        });
    </script>
</body>
</html>