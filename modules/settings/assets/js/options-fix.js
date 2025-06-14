/**
 * Script para corregir el guardado de configuraciones
 * 
 * Este script modifica todos los campos del formulario para asegurar
 * que usen el formato adecuado para el sistema de opciones de WordPress
 */

jQuery(document).ready(function($) {
    // Elementos que necesitan ser modificados
    var elementsToFix = $('input[type="text"], input[type="email"], input[type="number"], input[type="checkbox"], textarea, select').not('[name^="wp_pos_options"]');
    
    // Modificar cada elemento para que use el formato correcto
    elementsToFix.each(function() {
        var $element = $(this);
        var originalName = $element.attr('name');
        
        // Solo modificar si tiene un nombre
        if (originalName) {
            // Establecer el nuevo nombre con el prefijo correcto
            $element.attr('name', 'wp_pos_options[' + originalName + ']');
            
            // Manejo especial para checkboxes
            if ($element.is(':checkbox')) {
                // Si es un checkbox, asegurarse que tenga el valor correcto
                if (!$element.attr('value')) {
                    $element.attr('value', 'yes');
                }
                
                // Crear un campo oculto para manejar el caso de un checkbox desmarcado
                var $hidden = $('<input>', {
                    type: 'hidden',
                    name: 'wp_pos_options[' + originalName + '_exists]',
                    value: '1'
                });
                
                // Insertar después del checkbox
                $hidden.insertAfter($element);
            }
        }
    });
    
    // Registrar evento para depuración
    $('#wp-pos-settings-form').on('submit', function() {
        console.log('Formulario enviado con:', $(this).serialize());
    });
});
