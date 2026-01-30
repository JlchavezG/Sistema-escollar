/**
 * Sistema de Notificaciones Toast
 * Compatible con toda la estructura existente del sistema
 * Se carga automáticamente en el dashboard de profesores
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar si es panel de profesor
    if (!document.body.classList.contains('profesor-dashboard') && 
        !window.location.pathname.includes('profesores/dashboard.php')) {
        return;
    }
    
    // Crear contenedor para toasts (si no existe)
    let toastContainer = document.getElementById('notificationToastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'notificationToastContainer';
        toastContainer.style.position = 'fixed';
        toastContainer.style.top = '80px';
        toastContainer.style.right = '20px';
        toastContainer.style.zIndex = '9999';
        toastContainer.style.maxWidth = '350px';
        document.body.appendChild(toastContainer);
    }
    
    // Función para mostrar toast
    function showToast(notification) {
        // Crear toast
        const toastEl = document.createElement('div');
        toastEl.className = 'toast show mb-3';
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.style.minWidth = '300px';
        toastEl.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        toastEl.style.borderRadius = '10px';
        toastEl.style.overflow = 'hidden';
        toastEl.style.animation = 'slideInRight 0.3s ease-out';
        
        // Determinar color según tipo
        let bgColor, iconClass, iconColor;
        switch(notification.tipo) {
            case 'urgente':
                bgColor = '#ffebee';
                iconClass = 'fa-exclamation-triangle';
                iconColor = '#c62828';
                break;
            case 'importante':
                bgColor = '#e8f5e9';
                iconClass = 'fa-star';
                iconColor = '#2e7d32';
                break;
            case 'recordatorio':
                bgColor = '#fff8e1';
                iconClass = 'fa-clock';
                iconColor = '#5d4037';
                break;
            default: // informativa
                bgColor = '#e3f2fd';
                iconClass = 'fa-info-circle';
                iconColor = '#1976d2';
        }
        
        // Estilos del toast
        toastEl.style.backgroundColor = bgColor;
        toastEl.style.border = '1px solid rgba(0,0,0,0.1)';
        
        // Contenido del toast
        toastEl.innerHTML = `
            <div class="toast-header" style="background: ${bgColor}; border-bottom: 1px solid rgba(0,0,0,0.1); padding: 12px 15px;">
                <i class="fas ${iconClass} me-2" style="color: ${iconColor}; font-size: 1.2rem;"></i>
                <strong class="me-auto" style="color: #2c3e50; font-weight: 600;">${notification.asunto}</strong>
                <small class="text-muted">${new Date(notification.fecha_envio).toLocaleTimeString()}</small>
                <button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" style="padding: 15px; color: #2c3e50; line-height: 1.5;">
                ${notification.mensaje}
                <div class="mt-3 pt-2 border-top" style="border-color: rgba(0,0,0,0.1);">
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i>De: ${notification.remitente}
                    </small>
                </div>
            </div>
        `;
        
        // Agregar toast al contenedor
        toastContainer.appendChild(toastEl);
        
        // Remover toast después de 8 segundos
        setTimeout(() => {
            toastEl.style.animation = 'fadeOut 0.5s ease-out';
            setTimeout(() => {
                if (toastContainer.contains(toastEl)) {
                    toastContainer.removeChild(toastEl);
                }
            }, 500);
        }, 8000);
        
        // Marcar como leída al cerrar
        const closeBtn = toastEl.querySelector('.btn-close');
        closeBtn.addEventListener('click', function() {
            marcarComoLeida(notification.id);
        });
        
        // Marcar como leída al hacer clic en el toast
        toastEl.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-close')) {
                marcarComoLeida(notification.id);
                // Redirigir a ver notificaciones
                window.location.href = '../profesores/ver_notificaciones.php';
            }
        });
    }
    
    // Función para marcar como leída
    function marcarComoLeida(id) {
        fetch('../profesores/marcar_notificacion_leida.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        }).catch(error => console.error('Error al marcar notificación:', error));
    }
    
    // Cargar notificaciones no leídas al iniciar
    function cargarNotificacionesNoLeidas() {
        fetch('../profesores/obtener_notificaciones_no_leidas.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notificaciones.length > 0) {
                    // Mostrar cada notificación como toast
                    data.notificaciones.forEach(notificacion => {
                        showToast(notificacion);
                    });
                }
            })
            .catch(error => console.error('Error al cargar notificaciones:', error));
    }
    
    // Cargar notificaciones al iniciar
    cargarNotificacionesNoLeidas();
    
    // Verificar nuevas notificaciones cada 60 segundos
    setInterval(cargarNotificacionesNoLeidas, 60000);
});