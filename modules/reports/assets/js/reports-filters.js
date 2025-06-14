/**
 * Módulo de filtros CORREGIDO FINAL para el Panel de Reportes WP-POS
 * SOLUCION DEFINITIVA: Compatible con reports-scripts.js - Sin conflictos de contexto
 * 
 * @package WP-POS
 * @subpackage Reports
 */

;(function($){
    'use strict';
    
    var WPPosFilters = {
        
        // Configuración
        config: {
            baseUrl: '',
            queryParams: {},
            autoSubmit: true,
            debounceDelay: 800
        },
        
        // Estado
        state: {
            initialized: false,
            submitTimeout: null,
            lastValues: {}
        },
        
        /**
         * Inicialización mejorada y corregida
         */
        init: function() {
            console.log('=== INICIALIZANDO WP-POS Filters (DEFINITIVO) ===');
            
            // Configurar desde datos localizados si existen
            if (typeof WP_POS_Reports_Filters_Config !== 'undefined') {
                $.extend(this.config, WP_POS_Reports_Filters_Config);
            }
            
            // Configurar eventos
            this.setupEvents();
            
            // Configurar auto-submit
            if (this.config.autoSubmit) {
                this.setupAutoSubmit();
            }
            
            // Configurar validaciones
            this.setupValidations();
            
            // Inicializar estado de filtros
            this.initializeFilters();
            
            this.state.initialized = true;
            this.log('WP-POS Filters initialized (DEFINITIVO)');
            console.log('=== WP-POS Filters INICIALIZADO DEFINITIVO ===');
        },
        
        /**
         * CORREGIDO DEFINITIVO: Configurar eventos principales con contexto preservado
         */
        setupEvents: function() {
            var self = this; // Capturar contexto para todos los eventos
            
            // Usar namespace específico para evitar conflictos
            $(document).off('.wp-pos-filters-main');
            
            // Manejar cambio en período para mostrar/ocultar fechas
            $(document).on('change.wp-pos-filters-main', '#wp-pos-periodo', function() {
                self.handlePeriodChange();
            });
            
            // Resetear filtros
            $(document).on('click.wp-pos-filters-main', '.wp-pos-reset-filters', function(e) {
                e.preventDefault();
                self.resetFilters();
            });
            
            // Guardar filtros favoritos
            $(document).on('click.wp-pos-filters-main', '.wp-pos-save-filters', function(e) {
                e.preventDefault();
                self.saveFilters();
            });
            
            // Cargar filtros favoritos
            $(document).on('change.wp-pos-filters-main', '.wp-pos-load-filters', function(e) {
                self.loadFilters($(e.currentTarget).val());
            });
            
            // IMPORTANTE: Solo manejar el botón de recarga de página (GET)
            $(document).on('click.wp-pos-filters-main', '.wp-pos-apply-filters-get', function(e) {
                e.preventDefault();
                self.submitForm();
            });
            
            // NO manejar botones AJAX aquí - los maneja reports-scripts.js
            
            console.log('Eventos de filtros configurados con contexto preservado');
        },
        
        /**
         * CORREGIDO: Configurar auto-submit con contexto preservado
         */
        setupAutoSubmit: function() {
            var self = this; // Capturar contexto
            
            // Auto-submit para cambios que no requieren validación inmediata
            $(document).on('change.wp-pos-filters-main', '#wp-pos-vendedor, #wp-pos-payment-method', function() {
                self.scheduleAutoSubmit();
            });
            
            // Para fechas personalizadas
            $(document).on('change.wp-pos-filters-main', '#wp-pos-date-from, #wp-pos-date-to', function() {
                if ($('#wp-pos-periodo').val() === 'custom') {
                    self.scheduleAutoSubmit();
                }
            });
            
            // Enter en campos de input
            $(document).on('keypress.wp-pos-filters-main', 'input[type="date"], input[type="text"]', function(e) {
                if (e.which === 13) { // Enter
                    e.preventDefault();
                    self.triggerSubmit();
                }
            });
        },
        
        /**
         * CORREGIDO: Configurar validaciones con contexto preservado
         */
        setupValidations: function() {
            var self = this; // Capturar contexto
            
            // Validación de fechas
            $(document).on('change.wp-pos-filters-main', '#wp-pos-date-from, #wp-pos-date-to', function() {
                self.validateDateRange();
            });
            
            // Validación antes de envío de formulario
            $(document).on('submit.wp-pos-filters-main', '#wp-pos-filter-form', function(e) {
                e.preventDefault(); // Siempre prevenir envío normal
                
                if (self.validateForm()) {
                    self.triggerSubmit();
                }
                return false;
            });
        },
        
        /**
         * Inicializar estado de filtros
         */
        initializeFilters: function() {
            // Guardar valores iniciales
            this.saveCurrentValues();
            
            // Configurar estado inicial de fechas personalizadas
            this.handlePeriodChange();
            
            // Aplicar estilos de filtros activos
            this.updateActiveFilters();
        },
        
        /**
         * Manejar cambio de período
         */
        handlePeriodChange: function() {
            var period = $('#wp-pos-periodo').val();
            var $customDates = $('.wp-pos-custom-dates');
            var $dateFields = $('#wp-pos-date-from, #wp-pos-date-to');
            
            if (period === 'custom') {
                $customDates.slideDown(300);
                $dateFields.prop('required', true);
                
                // Asignar fechas por defecto si están vacías
                if (!$('#wp-pos-date-from').val()) {
                    $('#wp-pos-date-from').val(this.getCurrentDate());
                }
                if (!$('#wp-pos-date-to').val()) {
                    $('#wp-pos-date-to').val(this.getCurrentDate());
                }
            } else {
                $customDates.slideUp(300);
                $dateFields.prop('required', false);
            }
            
            // Programar auto-submit si está habilitado
            if (this.config.autoSubmit) {
                this.scheduleAutoSubmit();
            }
        },
        
        /**
         * CORREGIDO DEFINITIVO: Programar auto-submit con contexto preservado
         */
        scheduleAutoSubmit: function() {
            // Limpiar timeout anterior
            if (this.state.submitTimeout) {
                clearTimeout(this.state.submitTimeout);
            }
            
            var self = this; // Capturar contexto para setTimeout
            
            this.state.submitTimeout = setTimeout(function() {
                if (self.hasFiltersChanged()) {
                    self.log('Auto-submit triggered');
                    self.triggerSubmit();
                }
            }, this.config.debounceDelay);
        },
        
        /**
         * SOLUCIONADO DEFINITIVO: Disparar submit con múltiples estrategias
         */
        triggerSubmit: function() {
            console.log('=== triggerSubmit (DEFINITIVO) ===');
            console.log('Intentando usar métodos en orden de prioridad...');
            
            // ESTRATEGIA 1: Usar la función global específica con verificación robusta
            if (typeof window.wpPosApplyFiltersAjax === 'function') {
                this.log('Usando función global wpPosApplyFiltersAjax');
                try {
                    window.wpPosApplyFiltersAjax();
                    console.log('✓ wpPosApplyFiltersAjax ejecutado exitosamente');
                    return;
                } catch (error) {
                    console.error('Error en wpPosApplyFiltersAjax:', error);
                }
            }
            
            // ESTRATEGIA 2: Usar WPPosApplyFilters
            if (typeof window.WPPosApplyFilters === 'function') {
                this.log('Usando función global WPPosApplyFilters');
                try {
                    window.WPPosApplyFilters();
                    console.log('✓ WPPosApplyFilters ejecutado exitosamente');
                    return;
                } catch (error) {
                    console.error('Error en WPPosApplyFilters:', error);
                }
            }
            
            // ESTRATEGIA 3: Acceder directamente al objeto WPPosReports
            if (typeof window.WPPosReports !== 'undefined' && 
                window.WPPosReports && 
                typeof window.WPPosReports.applyFilters === 'function') {
                this.log('Usando WPPosReports.applyFilters directamente');
                try {
                    window.WPPosReports.applyFilters.call(window.WPPosReports);
                    console.log('✓ WPPosReports.applyFilters ejecutado exitosamente');
                    return;
                } catch (error) {
                    console.error('Error en WPPosReports.applyFilters:', error);
                }
            }
            
            // ESTRATEGIA 4: Simular click en el botón AJAX
            if ($('#wp-pos-apply-filters-ajax').length > 0) {
                this.log('Simulando click en botón AJAX');
                try {
                    $('#wp-pos-apply-filters-ajax').trigger('click');
                    console.log('✓ Click simulado en botón AJAX');
                    return;
                } catch (error) {
                    console.error('Error al simular click:', error);
                }
            }
            
            // ESTRATEGIA 5: Disparar evento personalizado
            this.log('Disparando evento personalizado wp-pos:apply-filters');
            $(document).trigger('wp-pos:apply-filters', [this.getCurrentFilterValues()]);
            
            // ESTRATEGIA 6: Fallback con timeout
            var self = this;
            setTimeout(function() {
                self.log('Fallback: usando envío de formulario tradicional');
                self.submitForm();
            }, 500);
        },
        
        /**
         * Verificar si los filtros han cambiado
         */
        hasFiltersChanged: function() {
            var currentValues = this.getCurrentFilterValues();
            return JSON.stringify(currentValues) !== JSON.stringify(this.state.lastValues);
        },
        
        /**
         * CORREGIDO: Obtener valores actuales de filtros (método robusto)
         */
        getCurrentFilterValues: function() {
            // SOLUCION: Usar función global si está disponible, sino usar método local
            if (typeof window.WPPosGetFilterValues === 'function') {
                try {
                    var globalFilters = window.WPPosGetFilterValues();
                    if (globalFilters) {
                        console.log('Usando filtros desde función global:', globalFilters);
                        return globalFilters;
                    }
                } catch (error) {
                    console.warn('Error al obtener filtros desde función global:', error);
                }
            }
            
            // Fallback: método local
            var localFilters = {
                period: $('#wp-pos-periodo').val() || '',
                seller_id: $('#wp-pos-vendedor').val() || '',
                payment_method: $('#wp-pos-payment-method').val() || '',
                date_from: $('#wp-pos-date-from').val() || '',
                date_to: $('#wp-pos-date-to').val() || ''
            };
            
            console.log('Usando filtros locales:', localFilters);
            return localFilters;
        },
        
        /**
         * Guardar valores actuales
         */
        saveCurrentValues: function() {
            this.state.lastValues = this.getCurrentFilterValues();
        },
        
        /**
         * Validar rango de fechas
         */
        validateDateRange: function() {
            var dateFrom = $('#wp-pos-date-from').val();
            var dateTo = $('#wp-pos-date-to').val();
            var $errorMsg = $('.wp-pos-date-error');
            
            // Limpiar errores previos
            $errorMsg.remove();
            $('#wp-pos-date-from, #wp-pos-date-to').removeClass('error');
            
            if (dateFrom && dateTo) {
                if (new Date(dateFrom) > new Date(dateTo)) {
                    this.showDateError('La fecha de inicio no puede ser mayor que la fecha de fin.');
                    return false;
                }
                
                // Verificar que no sean fechas futuras (opcional)
                var today = new Date().toISOString().split('T')[0];
                if (dateFrom > today || dateTo > today) {
                    this.showDateWarning('Has seleccionado fechas futuras.');
                }
            }
            
            return true;
        },
        
        /**
         * Mostrar error de fecha
         */
        showDateError: function(message) {
            var $dateGroup = $('.wp-pos-custom-dates').first();
            $dateGroup.append('<div class="wp-pos-date-error" style="color: red; font-size: 12px; margin-top: 5px;">' + message + '</div>');
            $('#wp-pos-date-from, #wp-pos-date-to').addClass('error');
        },
        
        /**
         * Mostrar advertencia de fecha
         */
        showDateWarning: function(message) {
            var $dateGroup = $('.wp-pos-custom-dates').first();
            $dateGroup.append('<div class="wp-pos-date-warning" style="color: orange; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        },
        
        /**
         * Validar formulario completo
         */
        validateForm: function() {
            var isValid = true;
            
            // Validar fechas
            if (!this.validateDateRange()) {
                isValid = false;
            }
            
            // Validar período personalizado
            var period = $('#wp-pos-periodo').val();
            if (period === 'custom') {
                var dateFrom = $('#wp-pos-date-from').val();
                var dateTo = $('#wp-pos-date-to').val();
                
                if (!dateFrom || !dateTo) {
                    this.showDateError('Ambas fechas son requeridas para el período personalizado.');
                    isValid = false;
                }
            }
            
            return isValid;
        },
        
        /**
         * CORREGIDO: Enviar formulario GET tradicional
         */
        submitForm: function() {
            // Validar antes de enviar
            if (!this.validateForm()) {
                this.log('Validation failed, form not submitted');
                return false;
            }
            
            var $form = $('#wp-pos-filter-form');
            
            // Asegurar que todos los campos estén incluidos
            this.ensureFormFields($form);
            
            // Guardar valores actuales
            this.saveCurrentValues();
            
            // Mostrar indicador de carga
            this.showSubmitIndicator();
            
            // Quitar temporalmente el manejador de submit para evitar bucles
            $(document).off('submit.wp-pos-filters-main', '#wp-pos-filter-form');
            
            try {
                // Enviar formulario de manera tradicional
                $form.submit();
            } catch (error) {
                console.error('Error al enviar formulario:', error);
                // Restaurar el manejador después de un momento
                var self = this;
                setTimeout(function() {
                    self.setupValidations();
                }, 100);
            }
        },
        
        /**
         * Asegurar que todos los campos estén en el formulario
         */
        ensureFormFields: function($form) {
            var filters = this.getCurrentFilterValues();
            
            // Limpiar campos ocultos anteriores
            $form.find('input[type="hidden"][data-auto-added]').remove();
            
            // Agregar campos faltantes como campos ocultos
            $.each(filters, function(key, value) {
                if (value && !$form.find('[name="' + key + '"]').length) {
                    $form.append('<input type="hidden" name="' + key + '" value="' + value + '" data-auto-added="true">');
                }
            });
        },
        
        /**
         * Mostrar indicador de envío
         */
        showSubmitIndicator: function() {
            var $button = $('.wp-pos-apply-filters-get').first();
            if ($button.length === 0) {
                $button = $('.wp-pos-apply-filters').first();
            }
            
            if ($button.length > 0) {
                var originalText = $button.text();
                
                $button.prop('disabled', true)
                       .html('<i class="dashicons dashicons-update"></i> Cargando...')
                       .data('original-text', originalText);
            }
        },
        
        /**
         * Refrescar página limpiando parámetros
         */
        refreshPage: function() {
            var baseUrl = this.config.baseUrl || window.location.pathname;
            var params = new URLSearchParams();
            params.set('page', 'wp-pos-reports');
            
            window.location.href = baseUrl + '?' + params.toString();
        },
        
        /**
         * Resetear filtros a valores por defecto
         */
        resetFilters: function() {
            $('#wp-pos-periodo').val('today');
            $('#wp-pos-vendedor').val('all');
            $('#wp-pos-payment-method').val('all');
            $('#wp-pos-date-from, #wp-pos-date-to').val('');
            
            this.handlePeriodChange();
            this.updateActiveFilters();
            
            if (this.config.autoSubmit) {
                this.scheduleAutoSubmit();
            }
        },
        
        /**
         * Actualizar indicadores de filtros activos
         */
        updateActiveFilters: function() {
            var filters = this.getCurrentFilterValues();
            var activeCount = 0;
            
            // Contar filtros activos
            $.each(filters, function(key, value) {
                if (value && value !== 'all' && value !== 'today') {
                    activeCount++;
                }
            });
            
            // Actualizar indicador
            var $indicator = $('.wp-pos-active-filters-count');
            if (activeCount > 0) {
                if (!$indicator.length) {
                    $('.wp-pos-filter-section').prepend('<div class="wp-pos-active-filters-count"></div>');
                    $indicator = $('.wp-pos-active-filters-count');
                }
                $indicator.text(activeCount + ' filtro(s) activo(s)').show();
            } else {
                $indicator.hide();
            }
        },
        
        /**
         * Guardar filtros como favoritos
         */
        saveFilters: function() {
            var filters = this.getCurrentFilterValues();
            var name = prompt('Nombre para estos filtros:');
            
            if (name) {
                var savedFilters = this.getSavedFilters();
                savedFilters[name] = filters;
                
                try {
                    localStorage.setItem('wp_pos_saved_filters', JSON.stringify(savedFilters));
                    this.updateSavedFiltersDropdown();
                    this.log('Filters saved as:', name);
                } catch (e) {
                    console.warn('No se pudieron guardar los filtros en localStorage:', e);
                }
            }
        },
        
        /**
         * Cargar filtros favoritos
         */
        loadFilters: function(name) {
            if (!name) return;
            
            var savedFilters = this.getSavedFilters();
            if (savedFilters[name]) {
                var filters = savedFilters[name];
                
                $.each(filters, function(key, value) {
                    $('#wp-pos-' + key.replace('_', '-')).val(value);
                });
                
                this.handlePeriodChange();
                this.updateActiveFilters();
                
                if (this.config.autoSubmit) {
                    this.scheduleAutoSubmit();
                }
                
                this.log('Filters loaded:', name);
            }
        },
        
        /**
         * Obtener filtros guardados
         */
        getSavedFilters: function() {
            try {
                return JSON.parse(localStorage.getItem('wp_pos_saved_filters') || '{}');
            } catch (e) {
                return {};
            }
        },
        
        /**
         * Actualizar dropdown de filtros guardados
         */
        updateSavedFiltersDropdown: function() {
            var savedFilters = this.getSavedFilters();
            var $dropdown = $('.wp-pos-load-filters');
            
            if ($dropdown.length) {
                $dropdown.empty().append('<option value="">Cargar filtros guardados...</option>');
                
                $.each(savedFilters, function(name, filters) {
                    $dropdown.append('<option value="' + name + '">' + name + '</option>');
                });
            }
        },
        
        /**
         * Obtener fecha actual en formato YYYY-MM-DD
         */
        getCurrentDate: function() {
            return new Date().toISOString().split('T')[0];
        },
        
        /**
         * Logging para debug
         */
        log: function() {
            if (window.console && console.log) {
                console.log.apply(console, ['[WP-POS Filters]'].concat(Array.prototype.slice.call(arguments)));
            }
        },
        
        /**
         * Destructor
         */
        destroy: function() {
            if (this.state.submitTimeout) {
                clearTimeout(this.state.submitTimeout);
            }
            
            // Limpiar eventos con namespace específico
            $(document).off('.wp-pos-filters-main');
            
            this.state.initialized = false;
            this.log('WP-POS Filters destroyed');
        }
    };
    
    // NUEVO: Escuchar evento personalizado para coordinación
    $(document).on('wp-pos:apply-filters', function(event, filterData) {
        console.log('Evento wp-pos:apply-filters recibido:', filterData);
        // Este evento puede ser procesado aquí si es necesario
    });
    
    // Inicialización cuando el DOM esté listo
    $(document).ready(function() {
        if ($('.wp-pos-filter-section').length > 0) {
            try {
                WPPosFilters.init();
                console.log('WP-POS Filters initialized successfully (DEFINITIVO)');
            } catch (error) {
                console.error('Error initializing WP-POS Filters:', error);
            }
        }
    });
    
    // Exponer para uso global
    window.WPPosFilters = WPPosFilters;
    
})(jQuery);