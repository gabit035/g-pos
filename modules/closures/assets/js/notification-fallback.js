/**
 * Sistema de notificaciones alternativo para G-POS
 * Proporciona una capa de seguridad para asegurar que las notificaciones siempre estén disponibles
 */

jQuery(document).ready(function($) {
    // Verificar si el sistema principal de notificaciones está disponible
    if (typeof WP_POS_Notifications !== 'object' || 
        typeof WP_POS_Notifications.success !== 'function' ||
        typeof WP_POS_Notifications.error !== 'function') {
        
        console.warn('Sistema principal de notificaciones no disponible - usando alternativa');
        
        // Implementar sistema alternativo
        window.WP_POS_Notifications = {
            success: function(message) {
                if (typeof message === 'string') {
                    console.log('✅ ' + message);
                    // Crear alerta de éxito
                    createAlert(message, 'success');
                }
            },
            error: function(message) {
                if (typeof message === 'string') {
                    console.error('❌ ' + message);
                    // Crear alerta de error
                    createAlert(message, 'error');
                }
            },
            warning: function(message) {
                if (typeof message === 'string') {
                    console.warn('⚠️ ' + message);
                    // Crear alerta de advertencia
                    createAlert(message, 'warning');
                }
            },
            info: function(message) {
                if (typeof message === 'string') {
                    console.info('ℹ️ ' + message);
                    // Crear alerta de información
                    createAlert(message, 'info');
                }
            }
        };
    }
    
    // Función para crear alertas visuales de emergencia
    function createAlert(message, type) {
        type = type || 'info';
        
        // Crear estilos si no existen
        if (!$('#wp-pos-alerts-styles').length) {
            $('<style id="wp-pos-alerts-styles">' +
              '.wp-pos-alert { padding: 12px 20px; margin: 10px 0; border-radius: 4px; position: fixed; top: 50px; right: 20px; z-index: 9999; box-shadow: 0 2px 5px rgba(0,0,0,0.2); animation: wp-pos-alert-in 0.3s ease-out; width: auto; max-width: 400px; }' +
              '.wp-pos-alert-success { background-color: #e7f7e7; color: #1e7b1e; border-left: 4px solid #1e7b1e; }' +
              '.wp-pos-alert-error { background-color: #ffecec; color: #d63638; border-left: 4px solid #d63638; }' +
              '.wp-pos-alert-warning { background-color: #fff8e5; color: #996e00; border-left: 4px solid #f0b849; }' +
              '.wp-pos-alert-info { background-color: #e7f5fa; color: #0071a1; border-left: 4px solid #0071a1; }' +
              '@keyframes wp-pos-alert-in { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }' +
              '</style>').appendTo('head');
        }
        
        // Crear alerta
        var $alert = $('<div class="wp-pos-alert wp-pos-alert-' + type + '">' + message + '</div>');
        $('body').append($alert);
        
        // Eliminar automáticamente después de 5 segundos
        setTimeout(function() {
            $alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
});
