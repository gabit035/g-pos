/**
 * Implementación de los indicadores de carga mejorados para G-POS
 * Reemplaza los indicadores de carga simples por componentes visuales avanzados
 */

// Sistema centralizado de gestión de indicadores de carga
var WP_POS_LoaderManager = (function() {
    // Registro de todos los indicadores activos
    var activeLoaders = {};
    
    // Contador para IDs únicos
    var loaderCounter = 0;
    
    return {
        // Crear un nuevo indicador y registrarlo
        create: function(target, options) {
            // Limpiar indicadores existentes en el mismo target
            this.clearTarget(target);
            
            // Generar ID único para este indicador
            var id = 'loader-' + (++loaderCounter);
            
            // Crear el indicador
            var loader = WP_POS_LoadingIndicator.show(target, options);
            
            // Registrarlo
            activeLoaders[id] = {
                target: target,
                loader: loader
            };
            
            return id;
        },
        
        // Eliminar un indicador específico
        remove: function(id) {
            if (activeLoaders[id] && activeLoaders[id].loader) {
                if (activeLoaders[id].loader.hideAll) {
                    activeLoaders[id].loader.hideAll();
                } else if (activeLoaders[id].loader.hide) {
                    activeLoaders[id].loader.hide();
                }
                delete activeLoaders[id];
            }
        },
        
        // Eliminar todos los indicadores activos
        clearAll: function() {
            for (var id in activeLoaders) {
                this.remove(id);
            }
            
            // Eliminar cualquier indicador residual usando la clase común
            jQuery('.wp-pos-loading').remove();
            jQuery('.wp-pos-loading-container').remove();
        },
        
        // Eliminar indicadores en un target específico
        clearTarget: function(target) {
            var targetSelector = (typeof target === 'string') ? target : null;
            
            for (var id in activeLoaders) {
                var loaderTarget = activeLoaders[id].target;
                if ((targetSelector && loaderTarget === targetSelector) || 
                    (!targetSelector && loaderTarget === target)) {
                    this.remove(id);
                }
            }
        }
    };
})();

jQuery(document).ready(function($) {
    // ID del indicador global para AJAX
    var globalLoaderId = null;
    
    // Sobrescribir las funciones jQuery AJAX globales
    $(document).ajaxStart(function() {
        // Solo crear un indicador global si no hay uno activo
        if (!globalLoaderId) {
            globalLoaderId = WP_POS_LoaderManager.create('body', {
                fullscreen: true,
                overlay: true,
                text: 'Procesando...',
                size: 'large'
            });
        }
    });
    
    $(document).ajaxStop(function() {
        // Eliminar el indicador global
        if (globalLoaderId) {
            WP_POS_LoaderManager.remove(globalLoaderId);
            globalLoaderId = null;
        }
        
        // Habilitar todos los botones por seguridad
        $('.wp-pos-form-actions button, .wp-pos-form button').prop('disabled', false);
    });
    
    // Método global para limpiar todos los indicadores (útil en caso de error)
    window.clearAllLoaders = function() {
        WP_POS_LoaderManager.clearAll();
        globalLoaderId = null;
    };
});
