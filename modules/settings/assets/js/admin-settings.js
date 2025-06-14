/**
 * Script para la pu00e1gina de configuraciones de WP-POS
 *
 * @package WP-POS
 * @subpackage Settings
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Objeto principal
    var WP_POS_Settings = {
        /**
         * Inicializar
         */
        init: function() {
            // Inicializar tabs si existen
            if ($('.nav-tab-wrapper').length > 0) {
                this.initTabs();
            }
            
            // Inicializar campos condicionales
            this.initConditionalFields();
            
            // Inicializar selectores de color
            this.initColorPickers();
            
            // Inicializar carga de archivo/imagen
            this.initMediaUploads();
            
            // Inicializar editor de cu00f3digo
            this.initCodeEditors();
            
            // Inicializar prueba de impresiu00f3n
            this.initPrintTest();
            
            // Inicializar reseteo de opciones
            this.initResetOptions();
            
            // Inicializar ajustes dinu00e1micos
            this.initDynamicSettings();
        },
        
        /**
         * Inicializar pestau00f1as
         */
        initTabs: function() {
            var hash = window.location.hash;
            
            // Si hay un hash, activar la pestau00f1a correspondiente
            if (hash && $('.nav-tab-wrapper a[href="' + hash + '"]').length) {
                $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                $('.nav-tab-wrapper a[href="' + hash + '"]').addClass('nav-tab-active');
                
                // Mostrar contenido
                $('.wp-pos-settings-tab').hide();
                $(hash).show();
            }
            
            // Click en tab
            $('.nav-tab-wrapper a').on('click', function(e) {
                var target = $(this).attr('href');
                
                // Si es un ID, prevenir navegaciu00f3n
                if (target.startsWith('#')) {
                    e.preventDefault();
                    
                    // Activar tab
                    $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    
                    // Mostrar contenido
                    $('.wp-pos-settings-tab').hide();
                    $(target).show();
                    
                    // Actualizar hash
                    window.location.hash = target;
                }
            });
        },
        
        /**
         * Inicializar campos condicionales
         */
        initConditionalFields: function() {
            // Funciu00f3n para actualizar campos condicionales
            function updateConditionalFields() {
                $('.wp-pos-field-conditional').each(function() {
                    var $field = $(this);
                    var conditions = $field.data('conditions');
                    
                    if (!conditions) {
                        return;
                    }
                    
                    var show = true;
                    
                    // Verificar todas las condiciones
                    $.each(conditions, function(field_id, condition) {
                        var $control = $('#' + field_id);
                        var value = '';
                        
                        // Obtener valor segu00fan tipo
                        if ($control.is(':checkbox')) {
                            value = $control.is(':checked') ? 'yes' : 'no';
                        } else {
                            value = $control.val();
                        }
                        
                        // Verificar condiciu00f3n
                        if (condition.operator === '==' && value != condition.value) {
                            show = false;
                        } else if (condition.operator === '!=' && value == condition.value) {
                            show = false;
                        }
                    });
                    
                    // Mostrar u ocultar
                    if (show) {
                        $field.show();
                    } else {
                        $field.hide();
                    }
                });
            }
            
            // Ejecutar al cargar
            updateConditionalFields();
            
            // Escuchar cambios en los campos
            $('#wp-pos-settings-form input, #wp-pos-settings-form select').on('change', function() {
                updateConditionalFields();
            });
        },
        
        /**
         * Inicializar selectores de color
         */
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.wp-pos-color-picker').wpColorPicker();
            }
        },
        
        /**
         * Inicializar carga de medios
         */
        initMediaUploads: function() {
            // Carga de archivos
            $('.wp-pos-upload-button').on('click', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var target_id = button.data('target');
                var file_types = button.data('file-types') || '';
                var $target = $('#' + target_id);
                
                // Crear frame de medios
                var frame = wp.media({
                    title: wp_pos_settings.i18n.select_file || 'Seleccionar archivo',
                    button: {
                        text: wp_pos_settings.i18n.use_file || 'Usar archivo'
                    },
                    multiple: false
                });
                
                // Callback al seleccionar
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $target.val(attachment.url);
                });
                
                // Abrir selector
                frame.open();
            });
            
            // Carga de imu00e1genes
            $('.wp-pos-upload-image').on('click', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var target_id = button.data('target');
                var preview_size = button.data('preview-size') || 'thumbnail';
                var $target = $('#' + target_id);
                var $preview = $target.siblings('.wp-pos-image-preview');
                
                // Crear frame de medios
                var frame = wp.media({
                    title: wp_pos_settings.i18n.select_image || 'Seleccionar imagen',
                    button: {
                        text: wp_pos_settings.i18n.use_image || 'Usar imagen'
                    },
                    library: { type: 'image' },
                    multiple: false
                });
                
                // Callback al seleccionar
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    
                    // Actualizar campo con ID
                    $target.val(attachment.id);
                    
                    // Actualizar vista previa
                    if ($preview.length) {
                        // Usar tamau00f1o solicitado o url por defecto
                        var img_url = attachment.sizes && attachment.sizes[preview_size] 
                            ? attachment.sizes[preview_size].url 
                            : attachment.url;
                        
                        $preview.html('<img src="' + img_url + '" alt="' + attachment.title + '" />');
                    }
                    
                    // Mostrar botu00f3n de eliminar
                    button.siblings('.wp-pos-remove-image').show();
                });
                
                // Abrir selector
                frame.open();
            });
            
            // Botu00f3n eliminar imagen
            $('.wp-pos-remove-image').on('click', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var target_id = button.data('target');
                var $target = $('#' + target_id);
                var $preview = $target.siblings('.wp-pos-image-preview');
                
                // Limpiar campo
                $target.val('');
                
                // Limpiar vista previa
                if ($preview.length) {
                    $preview.empty();
                }
                
                // Ocultar botu00f3n
                button.hide();
            });
        },
        
        /**
         * Inicializar editores de cu00f3digo
         */
        initCodeEditors: function() {
            $('.wp-pos-code-editor').each(function() {
                var $editor = $(this);
                var language = $editor.data('language') || 'php';
                
                // Si wp.codeEditor estu00e1 disponible, inicializar
                if (wp.codeEditor) {
                    var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                    editorSettings.codemirror = _.extend(
                        {},
                        editorSettings.codemirror,
                        {
                            indentUnit: 4,
                            tabSize: 4,
                            mode: language
                        }
                    );
                    
                    var editor = wp.codeEditor.initialize($editor, editorSettings);
                }
            });
        },
        
        /**
         * Inicializar prueba de impresiu00f3n
         */
        initPrintTest: function() {
            $('.wp-pos-test-print').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                $button.prop('disabled', true).text(wp_pos_settings.i18n.testing || 'Probando...');
                
                // Solicitud AJAX
                $.ajax({
                    url: wp_pos_settings.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_pos_test_printing',
                        nonce: wp_pos_settings.nonce,
                        printer_type: $('#printing_receipt_printer').val(),
                        printer_ip: $('#printing_printer_ip').val(),
                        printer_port: $('#printing_printer_port').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(wp_pos_settings.i18n.test_success || 'Prueba exitosa');
                        } else {
                            alert(wp_pos_settings.i18n.test_failed || 'Prueba fallida. Verifica tu configuraciu00f3n.');
                        }
                    },
                    error: function() {
                        alert(wp_pos_settings.i18n.error || 'Ha ocurrido un error. Por favor, intu00e9ntalo de nuevo.');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text(wp_pos_settings.i18n.test_print || 'Imprimir Recibo de Prueba');
                    }
                });
            });
        },
        
        /**
         * Inicializar reseteo de opciones
         */
        initResetOptions: function() {
            $('#wp-pos-reset-options').on('click', function(e) {
                e.preventDefault();
                
                if (confirm(wp_pos_settings.i18n.confirm_reset || 'u00bfEstu00e1s seguro de que deseas restablecer todas las opciones a sus valores predeterminados? Esta acciu00f3n no se puede deshacer.')) {
                    var $button = $(this);
                    $button.prop('disabled', true);
                    
                    // Solicitud AJAX
                    $.ajax({
                        url: wp_pos_settings.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wp_pos_reset_settings',
                            nonce: wp_pos_settings.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(wp_pos_settings.i18n.reset || 'Configuraciu00f3n restablecida a valores predeterminados.');
                                window.location.reload();
                            } else {
                                alert(wp_pos_settings.i18n.error || 'Ha ocurrido un error. Por favor, intu00e9ntalo de nuevo.');
                                $button.prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert(wp_pos_settings.i18n.error || 'Ha ocurrido un error. Por favor, intu00e9ntalo de nuevo.');
                            $button.prop('disabled', false);
                        }
                    });
                }
            });
        },
        
        /**
         * Inicializar ajustes dinu00e1micos
         */
        initDynamicSettings: function() {
            // Para futuras mejoras, como campos dinu00e1micos adicionales
        }
    };
    
    // Inicializar al cargar el documento
    $(document).ready(function() {
        WP_POS_Settings.init();
    });
    
})(jQuery);
