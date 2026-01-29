/**
 * Sidebar Toggle Functionality - CORREGIDO
 * Sistema Escolar - Sidebar Retráctil con Iconos Centrados
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const body = document.body;
    
    if (!sidebarToggle || !sidebar || !mainContent) {
        console.warn('Sidebar elements not found');
        return;
    }
    
    // Verificar estado guardado en localStorage
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('collapsed');
        sidebarToggle.innerHTML = '<i class="fas fa-arrow-right"></i>';
        sidebarToggle.setAttribute('aria-expanded', 'false');
    } else {
        sidebarToggle.setAttribute('aria-expanded', 'true');
    }
    
    // Toggle sidebar
    sidebarToggle.addEventListener('click', function() {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed', isCollapsed);
        
        // Cambiar icono y aria
        if (isCollapsed) {
            sidebarToggle.innerHTML = '<i class="fas fa-arrow-right"></i>';
            sidebarToggle.setAttribute('aria-expanded', 'false');
            localStorage.setItem('sidebarCollapsed', 'true');
        } else {
            sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
            sidebarToggle.setAttribute('aria-expanded', 'true');
            localStorage.setItem('sidebarCollapsed', 'false');
        }
        
        // Forzar repaint para animaciones suaves
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 300);
    });
    
    // Manejo responsivo para mobile
    const mediaQuery = window.matchMedia('(max-width: 992px)');
    
    function handleMobileView(e) {
        if (e.matches) {
            // Mobile view - sidebar oculto por defecto
            if (!sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('collapsed');
                sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
                sidebarToggle.classList.add('sidebar-mobile-active');
            }
        } else {
            // Desktop view - restaurar estado guardado
            sidebarToggle.classList.remove('sidebar-mobile-active');
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('collapsed');
                sidebarToggle.innerHTML = '<i class="fas fa-arrow-right"></i>';
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('collapsed');
                sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
    }
    
    // Initial check and listener
    handleMobileView(mediaQuery);
    mediaQuery.addEventListener('change', handleMobileView);
    
    // Cerrar sidebar en mobile al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (mediaQuery.matches && 
            !sidebar.contains(e.target) && 
            !sidebarToggle.contains(e.target) && 
            !sidebar.classList.contains('collapsed')) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('collapsed');
            sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
            sidebarToggle.classList.add('sidebar-mobile-active');
        }
    });
    
    // Cerrar sidebar al hacer clic en un enlace (mobile)
    const navLinks = document.querySelectorAll('.sidebar-menu .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (mediaQuery.matches && !sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('collapsed');
                sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
                sidebarToggle.classList.add('sidebar-mobile-active');
            }
        });
    });
});