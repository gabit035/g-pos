/**
 * Sistema de notificaciones modernas para G-POS
 * Reemplaza los alerts tradicionales por notificaciones visuales no intrusivas
 */

var WP_POS_Notifications = (function() {
    // Contador para IDs únicos de notificaciones
    var notificationCounter = 0;
    
    // Tiempo predeterminado de duración de las notificaciones en ms
    var defaultDuration = 5000;
    
    // Crear contenedor de notificaciones si no existe
    function ensureContainer() {
        if (!document.getElementById('wp-pos-notifications-container')) {
            var container = document.createElement('div');
            container.id = 'wp-pos-notifications-container';
            container.style.position = 'fixed';
            container.style.top = '32px'; // Ajuste para la barra de admin de WordPress
            container.style.right = '20px';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return document.getElementById('wp-pos-notifications-container');
    }
    
    // Función para mostrar una notificación
    function show(message, type, duration) {
        var container = ensureContainer();
        var notificationId = 'wp-pos-notification-' + (++notificationCounter);
        var notificationType = type || 'info'; // Valores: success, error, warning, info
        var notificationDuration = duration || defaultDuration;
        
        // Crear elemento de notificación
        var notification = document.createElement('div');
        notification.id = notificationId;
        notification.className = 'wp-pos-notification wp-pos-notification-' + notificationType;
        
        // Contenido de la notificación
        notification.innerHTML = `
            <div class="wp-pos-notification-content">
                ${message}
            </div>
            <div class="wp-pos-notification-close">&times;</div>
        `;
        
        // Agregar al contenedor
        container.appendChild(notification);
        
        // Mostrar con animación
        setTimeout(function() {
            notification.classList.add('show');
        }, 10);
        
        // Configurar cierre
        var closeButton = notification.querySelector('.wp-pos-notification-close');
        closeButton.addEventListener('click', function() {
            closeNotification(notificationId);
        });
        
        // Auto-cierre después del tiempo especificado
        if (notificationDuration > 0) {
            setTimeout(function() {
                closeNotification(notificationId);
            }, notificationDuration);
        }
        
        return notificationId;
    }
    
    // Función para cerrar una notificación específica
    function closeNotification(id) {
        var notification = document.getElementById(id);
        if (notification) {
            notification.classList.remove('show');
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300); // Tiempo para completar la animación de salida
        }
    }
    
    // Función para cerrar todas las notificaciones
    function closeAll() {
        var container = document.getElementById('wp-pos-notifications-container');
        if (container) {
            var notifications = container.querySelectorAll('.wp-pos-notification');
            notifications.forEach(function(notification) {
                closeNotification(notification.id);
            });
        }
    }
    
    // API pública
    return {
        success: function(message, duration) {
            return show(message, 'success', duration);
        },
        error: function(message, duration) {
            return show(message, 'error', duration);
        },
        warning: function(message, duration) {
            return show(message, 'warning', duration);
        },
        info: function(message, duration) {
            return show(message, 'info', duration);
        },
        show: show,
        close: closeNotification,
        closeAll: closeAll
    };
})();
