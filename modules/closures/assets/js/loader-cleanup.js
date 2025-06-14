/**
 * Utilidad para limpiar indicadores de carga persistentes
 * Asegura que los cargadores se eliminen correctamente incluso en situaciones excepcionales
 */

jQuery(document).ready(function($) {
    // Referencia global para los indicadores activos
    window.WP_POS_ActiveLoaders = window.WP_POS_ActiveLoaders || {};
    
    // Función para limpiar todos los indicadores de carga
    window.cleanupAllLoaders = function() {
        // Usar el sistema centralizado si está disponible
        if (typeof WP_POS_LoaderManager !== 'undefined') {
            WP_POS_LoaderManager.clearAll();
        }
        
        // Limpieza manual como respaldo
        $('.wp-pos-loading').remove();
        $('.wp-pos-loading-container').remove();
        
        // Habilitar todos los botones
        $('.wp-pos-form-actions button, .wp-pos-form button').prop('disabled', false);
        
        // Limpiar referencia global
        window.WP_POS_ActiveLoaders = {};
        
        console.log('✅ Todos los indicadores de carga han sido limpiados');
    };
    
    // Limpiar automáticamente después de completar cualquier AJAX
    $(document).ajaxComplete(function(event, xhr, settings) {
        // Para todas las solicitudes relacionadas con cierres
        if (settings.data && (
            settings.data.indexOf('wp_pos_closures_calculate_amounts') > -1 ||
            settings.data.indexOf('wp_pos_closures_save_closure') > -1 ||
            settings.data.indexOf('wp_pos_closures_diagnostic') > -1
        )) {
            console.log('🔄 Completada solicitud AJAX de cierres');
            
            // Limpiar indicadores inmediatamente
            window.cleanupAllLoaders();
            
            // Limpiar específicamente el indicador global
            setTimeout(function() {
                // Eliminar todo rastro del indicador global
                $('.blockUI').remove();
                $('#wp-pos-global-loader').remove();
                $('.wp-pos-loading-global').remove();
                $('.blockOverlay').remove();
                
                // Restaurar visibilidad del fondo
                $('body').css('overflow', 'auto');
                console.log('✅ Limpieza adicional de indicadores globales');
            }, 500);
        }
    });
    
    // Forzar limpieza periódica si hay indicadores visibles
    setInterval(function() {
        if ($('.wp-pos-loading, .blockUI, .wp-pos-loading-global').length > 0) {
            console.log('⚠ufe0f Detectados indicadores persistentes - limpieza automática');
            window.cleanupAllLoaders();
        }
    }, 3000); // Verificar cada 3 segundos
    
    // Agregar botón de limpieza manual para casos excepcionales (solo visible para administradores)
    if ($('body').hasClass('wp-pos-admin') || $('body').hasClass('wp-admin')) {
        $('<button>', {
            text: 'Limpiar indicadores',
            class: 'button wp-pos-cleanup-loaders',
            style: 'position: fixed; bottom: 10px; right: 10px; z-index: 999999; display: none;',
            click: function(e) {
                e.preventDefault();
                window.cleanupAllLoaders();
                $(this).fadeOut();
            }
        }).appendTo('body');
        
        // Mostrar el botón solo si hay indicadores visibles por más de 5 segundos
        setTimeout(function() {
            if ($('.wp-pos-loading').length > 0) {
                $('.wp-pos-cleanup-loaders').fadeIn();
            }
        }, 5000);
    }
});
