/**
 * JavaScript para la página de configuraciones
 *
 * @package WP-POS
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Objeto principal de configuraciones
    var POS_Settings = {
        /**
         * Inicialización
         */
        init: function() {
            this.initTabs();
            this.initTooltips();
            this.initToggleFields();
            this.initColorPickers();
            this.initNotifications();
            this.initConfirmChanges();
        },
        
        /**
         * Inicializar navegación por pestañas
         */
        initTabs: function() {
            // Cambio de pestaña al hacer clic
            $('.wp-pos-tabs-nav a').on('click', function(e) {
                e.preventDefault();
                
                var tabId = $(this).attr('href');
                
                // Desactivar pestañas actuales
                $('.wp-pos-tab-pane').removeClass('active');
                $('.wp-pos-tabs-nav li').removeClass('active');
                
                // Activar pestaña seleccionada
                $(tabId).addClass('active');
                $(this).parent().addClass('active');
                
                // Guardar estado en sessionStorage
                if (typeof(Storage) !== 'undefined') {
                    sessionStorage.setItem('wp_pos_active_settings_tab', tabId);
                }
            });
            
            // Restaurar pestaña activa
            if (typeof(Storage) !== 'undefined') {
                var activeTab = sessionStorage.getItem('wp_pos_active_settings_tab');
                if (activeTab && $(activeTab).length) {
                    $('.wp-pos-tab-pane').removeClass('active');
                    $('.wp-pos-tabs-nav li').removeClass('active');
                    
                    $(activeTab).addClass('active');
                    $('.wp-pos-tabs-nav a[href="' + activeTab + '"]').parent().addClass('active');
                }
            }
        },
        
        /**
         * Inicializar tooltips
         */
        initTooltips: function() {
            // Añadir tooltips a elementos con descripción
            $('.form-table th label').each(function() {
                var $this = $(this);
                var $row = $this.closest('tr');
                var $description = $row.find('.description');
                
                if ($description.length) {
                    var tooltipText = $description.text();
                    
                    // Añadir icono de ayuda
                    $this.append(' <span class="wp-pos-tooltip-icon dashicons dashicons-editor-help"></span>');
                    
                    // Crear tooltip
                    $this.find('.wp-pos-tooltip-icon').attr('title', tooltipText);
                    
                    // Ocultar descripción original
                    $description.addClass('screen-reader-text');
                }
            });
            
            // Inicializar tooltips si existe jQuery UI
            if ($.fn.tooltip) {
                $('.wp-pos-tooltip-icon').tooltip({
                    position: { my: 'left center', at: 'right+10 center' },
                    show: { duration: 200 },
                    hide: { duration: 200 }
                });
            }
        },
        
        /**
         * Inicializar campos dependientes
         */
        initToggleFields: function() {
            // Función para actualizar campos dependientes
            function updateDependentFields(controlField, dependentSelector, isInverse) {
                var isChecked = $(controlField).is(':checked');
                var shouldShow = isInverse ? !isChecked : isChecked;
                
                if (shouldShow) {
                    $(dependentSelector).closest('tr').fadeIn(200);
                } else {
                    $(dependentSelector).closest('tr').fadeOut(200);
                }
            }
            
            // Campos que controlan la visibilidad de otros campos
            var toggleFields = [
                { control: '#enable_discount', dependent: '#discount_type', inverse: false },
                { control: '#update_stock', dependent: '#low_stock_threshold', inverse: false },
                { control: '#show_categories_filter', dependent: '#default_category', inverse: false }
            ];
            
            // Inicializar estado de campos dependientes
            $.each(toggleFields, function(index, field) {
                updateDependentFields(field.control, field.dependent, field.inverse);
                
                // Actualizar al cambiar
                $(field.control).on('change', function() {
                    updateDependentFields(this, field.dependent, field.inverse);
                });
            });
        },
        
        /**
         * Inicializar selectores de color
         */
        initColorPickers: function() {
            // Inicializar color picker si existe la función
            if ($.fn.wpColorPicker) {
                $('.wp-pos-color-picker').wpColorPicker();
            }
        },
        
        /**
         * Inicializar notificaciones
         */
        initNotifications: function() {
            // Desvanecimiento automático de notificaciones
            setTimeout(function() {
                $('.settings-error').slideUp(300);
            }, 5000);
            
            // Cerrar notificación al hacer clic en botón de cierre
            $('.notice-dismiss').on('click', function() {
                $(this).closest('.notice').slideUp(200);
            });
        },
        
        /**
         * Confirmar cambios antes de abandonar la página
         */
        initConfirmChanges: function() {
            var formChanged = false;
            
            // Detectar cambios en el formulario
            $('.wp-pos-settings-form :input').on('change input', function() {
                formChanged = true;
            });
            
            // Reiniciar indicador de cambios al enviar el formulario
            $('.wp-pos-settings-form').on('submit', function() {
                formChanged = false;
            });
            
            // Mostrar alerta al intentar abandonar la página con cambios sin guardar
            $(window).on('beforeunload', function() {
                if (formChanged) {
                    return '¿Abandonar la página? Los cambios que has hecho no se guardarán.';
                }
            });
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        POS_Settings.init();
    });
    
})(jQuery);
