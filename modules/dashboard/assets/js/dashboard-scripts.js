/**
 * Scripts modernos para el Dashboard de WP-POS
 * Incluye soporte para atajos de teclado y animaciones mejoradas
 */
jQuery(document).ready(function($) {
    // Animaciones de entrada para los elementos principales
    function animateElements() {
        // Animar tarjetas de estadísticas
        $('.wp-pos-stat-card').each(function(index) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(15px)'
            });
            
            setTimeout(function() {
                $(this).css({
                    'opacity': '1',
                    'transform': 'translateY(0)',
                    'transition': 'opacity 0.3s ease-out, transform 0.3s ease-out'
                });
            }.bind(this), 100 * index);
        });
        
        // Animar botones de acciones
        $('.wp-pos-action-button').each(function(index) {
            $(this).css({
                'opacity': '0',
                'transform': 'scale(0.95)'
            });
            
            setTimeout(function() {
                $(this).css({
                    'opacity': '1',
                    'transform': 'scale(1)',
                    'transition': 'opacity 0.3s ease-out, transform 0.3s ease-out'
                });
            }.bind(this), 150 + (80 * index));
        });
        
        // Animar panel de actividad reciente
        $('.wp-pos-recent-activity').css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        });
        
        setTimeout(function() {
            $('.wp-pos-recent-activity').css({
                'opacity': '1',
                'transform': 'translateY(0)',
                'transition': 'opacity 0.4s ease-out, transform 0.4s ease-out'
            });
        }, 300);
    }
    
    // Ejecutar animaciones cuando todo esté cargado
    animateElements();
    
    // Manejar panel de bienvenida
    if ($('.wp-pos-welcome-panel').length > 0) {
        $('.wp-pos-welcome-panel').css({
            'opacity': '0',
            'transform': 'translateY(-20px)'
        });
        
        setTimeout(function() {
            $('.wp-pos-welcome-panel').css({
                'opacity': '1',
                'transform': 'translateY(0)',
                'transition': 'opacity 0.5s ease-out, transform 0.5s ease-out'
            });
        }, 400);
        
        // Cerrar notificación de bienvenida
        $('.wp-pos-welcome-close').on('click', function(e) {
            e.preventDefault();
            
            $('.wp-pos-welcome-panel').css({
                'opacity': '0',
                'transform': 'translateY(-20px)',
                'transition': 'opacity 0.5s ease-out, transform 0.5s ease-out'
            });
            
            setTimeout(function() {
                $('.wp-pos-welcome-panel').remove();
            }, 500);
            
            // Guardar preferencia
            $.post(ajaxurl, {
                action: 'wp_pos_dismiss_welcome'
            });
        });
    }
    
    // Implementar atajos de teclado
    // Mapeo de teclas numéricas 1-6 a botones de acciones rápidas
    $(document).on('keydown', function(e) {
        // Solo activar cuando no se está escribiendo en un input, textarea o select
        if ($(e.target).is('input, textarea, select')) {
            return;
        }
        
        // Teclas numéricas 1-6 (códigos 49-54)
        if (e.keyCode >= 49 && e.keyCode <= 54) {
            const shortcutNum = e.keyCode - 48; // Convertir código a número 1-6
            const targetButton = $('.wp-pos-action-button[data-shortcut="' + shortcutNum + '"]');
            
            if (targetButton.length) {
                // Efecto visual de pulsación
                targetButton.addClass('active');
                setTimeout(function() {
                    targetButton.removeClass('active');
                }, 200);
                
                // Redireccionar al hacer clic
                setTimeout(function() {
                    window.location.href = targetButton.attr('href');
                }, 300);
            }
        }
    });
    
    // Agregar tooltip a los botones con atajo de teclado
    $('.wp-pos-action-button').each(function() {
        const shortcut = $(this).data('shortcut');
        const label = $(this).find('.wp-pos-action-label').text();
        
        $(this).attr('title', label + ' (Tecla ' + shortcut + ')');
    });
});
