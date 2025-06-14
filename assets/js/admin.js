/**
 * Scripts principales para la administración del plugin WP-POS
 *
 * @package WP-POS
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Objeto principal
    var WPPOS = {
        
        /**
         * Inicializar
         */
        init: function() {
            // Inicializar eventos
            this.bindEvents();
            
            // Inicializar componentes
            this.initComponents();
            
            // Manejar enlaces de confirmación
            this.handleConfirmLinks();
            
            console.log('WP-POS Admin: Inicializado');
        },
        
        /**
         * Manejar enlaces de confirmación
         */
        handleConfirmLinks: function() {
            $(document).on('click', '.wp-pos-confirm-action', function(e) {
                e.preventDefault();
                var $link = $(this);
                var message = $link.data('message') || wp_pos_admin_params.confirm_delete;
                
                if (confirm(message)) {
                    window.location.href = $link.attr('href');
                }
                
                return false;
            });
        },
        
        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Manejar acciones de eliminación
            $('.wp-pos-delete-action').on('click', this.handleDelete);
            
            // Manejar envío de formularios
            $('.wp-pos-form').on('submit', this.handleFormSubmit);
            
            // Manejar navegación por pestañas
            $('.wp-pos-tabs-nav a').on('click', this.handleTabClick);
            
            // Filtros de tablas
            $('.wp-pos-filter-input').on('keyup', this.handleTableFilter);
            
            // Cambios de periodo en reportes
            $('.wp-pos-period-selector button').on('click', this.handlePeriodChange);
        },
        
        /**
         * Inicializar componentes 
         */
        initComponents: function() {
            // Inicializar datepickers si existen
            if ($.fn.datepicker) {
                $('.wp-pos-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
            
            // Inicializar selectores mejorados si existen
            if ($.fn.select2) {
                $('.wp-pos-select2').select2({
                    width: '100%',
                    placeholder: 'Seleccione una opción'
                });
            }
            
            // Inicializar tooltips
            this.initTooltips();
        },
        
        /**
         * Inicializar tooltips
         */
        initTooltips: function() {
            $('.wp-pos-tooltip').each(function() {
                var $this = $(this);
                $this.append('<span class="wp-pos-tooltip-content">' + $this.data('tooltip') + '</span>');
            });
        },
        
        /**
         * Manejar eventos de eliminación
         */
        handleDelete: function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var message = $link.data('message') || $link.data('confirm') || '¿Estás seguro de que deseas continuar?';
            
            if (confirm(message)) {
                window.location.href = $link.attr('href');
            }
            
            return false;
        },
        
        /**
         * Manejar envío de formularios
         */
        handleFormSubmit: function(e) {
            var $form = $(this);
            var confirmMessage = $form.data('confirm');
            
            // Verificar si es el formulario de acciones masivas
            if ($form.attr('id') === 'wp-pos-bulk-actions-form') {
                var action = $form.find('select[name="bulk_action"]').val();
                var checkedItems = $form.find('input[name^="sale_ids["]:checked').length;
                
                if (!action) {
                    e.preventDefault();
                    alert('Por favor selecciona una acción.');
                    return false;
                }
                
                if (checkedItems === 0) {
                    e.preventDefault();
                    alert('Por favor selecciona al menos una venta.');
                    return false;
                }
                
                var message = '¿Estás seguro de que deseas ';
                
                if (action === 'delete') {
                    message += 'eliminar ' + checkedItems + ' venta' + (checkedItems > 1 ? 's' : '') + '? Esta acción no se puede deshacer.';
                } else if (action === 'cancel') {
                    message += 'cancelar ' + checkedItems + ' venta' + (checkedItems > 1 ? 's' : '') + '?';
                } else {
                    return true; // Otra acción, continuar con el envío
                }
                
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            } else if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Deshabilitar botón de envío para prevenir múltiples envíos
            $form.find('[type="submit"]').prop('disabled', true).addClass('disabled');
            
            // Mostrar loader
            WPPOS.showLoader();
        },
        
        /**
         * Manejar navegación por pestañas
         */
        handleTabClick: function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var target = $this.attr('href');
            
            // Cambiar pestañas activas
            $('.wp-pos-tabs-nav li').removeClass('active');
            $this.parent().addClass('active');
            
            // Cambiar contenido activo
            $('.wp-pos-tab-pane').removeClass('active');
            $(target).addClass('active');
            
            // Guardar pestaña activa en sesión si está habilitado
            if (typeof(Storage) !== 'undefined') {
                sessionStorage.setItem('wp_pos_active_tab', target);
            }
        },
        
        /**
         * Manejar filtrado de tablas
         */
        handleTableFilter: function() {
            var $input = $(this);
            var filterValue = $input.val().toLowerCase();
            var targetTable = $input.data('target');
            
            $('#' + targetTable + ' tbody tr').each(function() {
                var $row = $(this);
                var text = $row.text().toLowerCase();
                $row.toggle(text.indexOf(filterValue) > -1);
            });
        },
        
        /**
         * Manejar cambio de periodo en reportes
         */
        handlePeriodChange: function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var period = $this.data('period');
            
            // Actualizar botón activo
            $('.wp-pos-period-selector button').removeClass('active');
            $this.addClass('active');
            
            // Mostrar indicador de carga si existe
            if (WPPOS.showLoader) {
                WPPOS.showLoader();
            }
            
            // Si existe la función para cargar datos de periodo, llamarla
            if (typeof POS_Reports !== 'undefined' && 
                POS_Reports.Dashboard && 
                typeof POS_Reports.Dashboard.loadData === 'function') {
                POS_Reports.Dashboard.loadData(period);
            }
        },
        
        /**
         * Mostrar loader
         */
        showLoader: function() {
            $('.wp-pos-loader-container').show();
            $('.wp-pos-content').css('opacity', '0.5');
        },
        
        /**
         * Ocultar loader
         */
        hideLoader: function() {
            $('.wp-pos-loader-container').hide();
            $('.wp-pos-content').css('opacity', '1');
        },
        
        /**
         * Mostrar mensaje de notificación
         */
        showNotice: function(message, type) {
            var noticeClass = 'wp-pos-notice';
            if (type) {
                noticeClass += ' ' + type;
            }
            
            var $notice = $('<div class="' + noticeClass + '">' + message + '</div>');
            $('.wp-pos-notices').html($notice);
            
            // Auto-ocultar después de 5 segundos si no es error
            if (type !== 'error') {
                setTimeout(function() {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        WPPOS.init();
        
        // Restaurar pestaña activa de sesión si existe
        if (typeof(Storage) !== 'undefined') {
            var activeTab = sessionStorage.getItem('wp_pos_active_tab');
            if (activeTab) {
                $('.wp-pos-tabs-nav a[href="' + activeTab + '"]').trigger('click');
            }
        }
    });
    
    // Exportar objeto para uso global
    window.WPPOS = WPPOS;
    
})(jQuery);
