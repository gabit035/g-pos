/**
 * Correcciju00f3n para evitar la duplicaciu00f3n de cierres en el historial
 * Este script modifica el comportamiento del historial para evitar
 * que los cierres aparezcan duplicados cuando se guarda un nuevo cierre
 */

jQuery(document).ready(function($) {
    // Cache para almacenar IDs de cierres ya mostrados
    var displayedClosureIds = {};
    
    // Sobrescribir la funciu00f3n de renderizado de cierres si existe
    if (typeof window.renderClosuresList === 'function') {
        // Guardar la funciu00f3n original
        var originalRenderFunction = window.renderClosuresList;
        
        // Reemplazar con versiuu00f3n mejorada
        window.renderClosuresList = function(closures) {
            if (!closures || closures.length === 0) {
                $('#closures-list').html('<tr><td colspan="9" class="no-items">No se encontraron cierres de caja</td></tr>');
                return;
            }
            
            // Filtrar cierres duplicados
            var uniqueClosures = [];
            var currentIds = {};
            
            // Primera pasada: recolectar IDs u00fanicos
            closures.forEach(function(closure) {
                if (!currentIds[closure.id] && !displayedClosureIds[closure.id]) {
                    currentIds[closure.id] = true;
                    uniqueClosures.push(closure);
                }
            });
            
            // Actualizar cache global
            Object.assign(displayedClosureIds, currentIds);
            
            // Renderizar con cierres u00fanicos
            originalRenderFunction(uniqueClosures);
            
            console.log('📊 Historial renderizado con ' + uniqueClosures.length + ' cierres u00fanicos');
        };
        
        console.log('✅ Mejora anti-duplicaciu00f3n de cierres aplicada');
    }
    
    // Funciu00f3n para limpiar completamente el cache (u00fatil al cambiar filtros)
    window.resetClosureCache = function() {
        displayedClosureIds = {};
        console.log('🔄 Cache de cierres reiniciado');
    };
    
    // Sobrescribir comportamientos relacionados con filtros
    if (typeof window.loadClosuresList === 'function') {
        var originalLoadFunction = window.loadClosuresList;
        
        window.loadClosuresList = function() {
            // Reiniciar cache al cambiar pu00e1gina o filtros
            window.resetClosureCache();
            // Llamar a la funciu00f3n original
            originalLoadFunction.apply(this, arguments);
        };
    }
});
