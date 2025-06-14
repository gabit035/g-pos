/**
 * Coordinador WP-POS Reports - Prevención de Conflictos
 * 
 * Este archivo coordina la comunicación entre reports-scripts.js y reports-filters.js
 * para evitar conflictos de contexto y asegurar que los filtros funcionen correctamente.
 *
 * @package WP-POS
 * @subpackage Reports
 */

;(function($) {
    'use strict';
    
    var WPPosReportsCoordinator = {
        
        // Estado del coordinador
        state: {
            initialized: false,
            reportsReady: false,
            filtersReady: false
        },
        
        /**
         * Inicializar coordinador
         */
        init: function() {
            console.log('=== INICIALIZANDO WP-POS Coordinator ===');
            
            this.setupGlobalMethods();
            this.setupEventListeners();
            this.checkComponentsReady();
            
            this.state.initialized = true;
            console.log('=== WP-POS Coordinator INICIALIZADO ===');
        },
        
        /**
         * Configurar métodos globales de respaldo
         */
        setupGlobalMethods: function() {
            var self = this;
            
            // Método global robusto para aplicar filtros
            if (!window.wpPosApplyFiltersAjax) {
                window.wpPosApplyFiltersAjax = function() {
                    console.log('=== wpPosApplyFiltersAjax (Coordinador) ===');
                    self.executeFilterApplication();
                };
            }
            
            // Método de respaldo adicional
            if (!window.WPPosApplyFilters) {
                window.WPPosApplyFilters = function() {
                    console.log('=== WPPosApplyFilters (Coordinador) ===');
                    self.executeFilterApplication();
                };
            }
            
            // Método para obtener filtros
            if (!window.WPPosGetFilterValues) {
                window.WPPosGetFilterValues = function() {
                    return self.getFilterValues();
                };
            }
            
            console.log('Métodos globales de respaldo configurados');
        },
        
        /**
         * Configurar listeners de eventos
         */
        setupEventListeners: function() {
            var self = this;
            
            // Escuchar cuando los componentes estén listos
            $(document).on('wp-pos:reports-ready', function() {
                self.state.reportsReady = true;
                self.checkComponentsReady();
            });
            
            $(document).on('wp-pos:filters-ready', function() {
                self.state.filtersReady = true;
                self.checkComponentsReady();
            });
            
            // Escuchar eventos de aplicación de filtros
            $(document).on('wp-pos:apply-filters', function(event, filterData) {
                console.log('Evento wp-pos:apply-filters interceptado por coordinador:', filterData);
                self.executeFilterApplication(filterData);
            });
            
            // Escuchar cambios en el DOM para reconfigurar si es necesario
            $(document).on('DOMNodeInserted', function(e) {
                if ($(e.target).hasClass('wp-pos-reports-dashboard') || 
                    $(e.target).find('.wp-pos-reports-dashboard').length > 0) {
                    setTimeout(function() {
                        self.recheckComponents();
                    }, 500);
                }
            });
        },
        
        /**
         * Verificar si todos los componentes están listos
         */
        checkComponentsReady: function() {
            if (this.state.reportsReady && this.state.filtersReady) {
                console.log('✓ Todos los componentes WP-POS están listos');
                $(document).trigger('wp-pos:all-ready');
            } else {
                console.log('Esperando componentes...', {
                    reports: this.state.reportsReady,
                    filters: this.state.filtersReady
                });
            }
        },
        
        /**
         * Re-verificar componentes (útil para contenido dinámico)
         */
        recheckComponents: function() {
            this.state.reportsReady = typeof window.WPPosReports !== 'undefined' && 
                                     window.WPPosReports && 
                                     window.WPPosReports.state && 
                                     window.WPPosReports.state.initialized;
            
            this.state.filtersReady = typeof window.WPPosFilters !== 'undefined' && 
                                     window.WPPosFilters && 
                                     window.WPPosFilters.state && 
                                     window.WPPosFilters.state.initialized;
            
            this.checkComponentsReady();
        },
        
        /**
         * Ejecutar aplicación de filtros de forma robusta
         */
        executeFilterApplication: function(filterData) {
            console.log('=== executeFilterApplication (Coordinador) ===');
            
            // ESTRATEGIA 1: Usar WPPosReports directamente
            if (window.WPPosReports && 
                typeof window.WPPosReports.applyFilters === 'function' &&
                window.WPPosReports.state &&
                window.WPPosReports.state.initialized) {
                
                console.log('✓ Usando WPPosReports.applyFilters');
                try {
                    window.WPPosReports.applyFilters.call(window.WPPosReports);
                    return true;
                } catch (error) {
                    console.error('Error en WPPosReports.applyFilters:', error);
                }
            }
            
            // ESTRATEGIA 2: Simular click en botón AJAX
            var $ajaxButton = $('#wp-pos-apply-filters-ajax');
            if ($ajaxButton.length > 0) {
                console.log('✓ Simulando click en botón AJAX');
                try {
                    $ajaxButton.trigger('click');
                    return true;
                } catch (error) {
                    console.error('Error al simular click:', error);
                }
            }
            
            // ESTRATEGIA 3: Usar datos de filtros si se proporcionaron
            if (filterData) {
                console.log('✓ Usando datos de filtros proporcionados');
                this.applyFiltersWithData(filterData);
                return true;
            }
            
            // ESTRATEGIA 4: Intentar obtener filtros y aplicar
            try {
                var filters = this.getFilterValues();
                if (filters) {
                    console.log('✓ Obteniendo filtros y aplicando');
                    this.applyFiltersWithData(filters);
                    return true;
                }
            } catch (error) {
                console.error('Error al obtener filtros:', error);
            }
            
            // ESTRATEGIA 5: Fallback - recarga de página
            console.warn('⚠ Fallback: recargando página');
            if (window.WPPosFilters && typeof window.WPPosFilters.submitForm === 'function') {
                window.WPPosFilters.submitForm();
            } else {
                var $form = $('#wp-pos-filter-form');
                if ($form.length > 0) {
                    $form.submit();
                }
            }
            
            return false;
        },
        
        /**
         * Obtener valores de filtros de forma robusta
         */
        getFilterValues: function() {
            console.log('=== getFilterValues (Coordinador) ===');
            
            // OPCIÓN 1: Usar WPPosReports
            if (window.WPPosReports && typeof window.WPPosReports.getFilterValues === 'function') {
                try {
                    var reportsFilters = window.WPPosReports.getFilterValues();
                    console.log('Filtros desde WPPosReports:', reportsFilters);
                    return reportsFilters;
                } catch (error) {
                    console.warn('Error al obtener filtros desde WPPosReports:', error);
                }
            }
            
            // OPCIÓN 2: Usar WPPosFilters
            if (window.WPPosFilters && typeof window.WPPosFilters.getCurrentFilterValues === 'function') {
                try {
                    var filtersFilters = window.WPPosFilters.getCurrentFilterValues();
                    console.log('Filtros desde WPPosFilters:', filtersFilters);
                    return filtersFilters;
                } catch (error) {
                    console.warn('Error al obtener filtros desde WPPosFilters:', error);
                }
            }
            
            // OPCIÓN 3: Leer directamente del DOM
            var domFilters = {
                period: $('#wp-pos-periodo').val() || 'today',
                seller_id: $('#wp-pos-vendedor').val() || 'all',
                payment_method: $('#wp-pos-payment-method').val() || 'all',
                date_from: $('#wp-pos-date-from').val() || '',
                date_to: $('#wp-pos-date-to').val() || ''
            };
            
            console.log('Filtros desde DOM:', domFilters);
            return domFilters;
        },
        
        /**
         * Aplicar filtros usando datos específicos
         */
        applyFiltersWithData: function(filterData) {
            console.log('=== applyFiltersWithData ===', filterData);
            
            // Si WPPosReports está disponible, intentar usarlo
            if (window.WPPosReports && typeof window.WPPosReports.applyFilters === 'function') {
                try {
                    // Actualizar los campos del formulario con los datos
                    $.each(filterData, function(key, value) {
                        var fieldId = '#wp-pos-' + key.replace('_', '-');
                        if ($(fieldId).length > 0) {
                            $(fieldId).val(value);
                        }
                    });
                    
                    // Aplicar filtros
                    window.WPPosReports.applyFilters.call(window.WPPosReports);
                    return;
                } catch (error) {
                    console.error('Error aplicando filtros con datos:', error);
                }
            }
            
            // Fallback: hacer petición AJAX manual
            this.makeManualAjaxRequest(filterData);
        },
        
        /**
         * Hacer petición AJAX manual como último recurso
         */
        makeManualAjaxRequest: function(filterData) {
            console.log('=== makeManualAjaxRequest ===');
            
            var ajaxData = {
                action: 'get_pos_report_data',
                security: this.getNonce()
            };
            
            $.extend(ajaxData, filterData);
            
            // Mostrar loading si es posible
            $('.wp-pos-loading-overlay').fadeIn(200);
            
            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    console.log('Respuesta AJAX manual:', response);
                    
                    if (response.success && response.data) {
                        // Actualizar contenido básico
                        if (response.data.html_summary_cards) {
                            $('#wp-pos-summary-cards-placeholder').html(response.data.html_summary_cards);
                        }
                        if (response.data.html_recent_sales_table) {
                            $('#wp-pos-recent-sales-table-placeholder').html(response.data.html_recent_sales_table);
                        }
                        
                        // Mostrar éxito
                        if (typeof window.WPPosReports !== 'undefined' && window.WPPosReports.showNotification) {
                            window.WPPosReports.showNotification('Datos actualizados', 'success');
                        }
                    } else {
                        console.error('Error en respuesta AJAX manual:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en petición AJAX manual:', error);
                },
                complete: function() {
                    $('.wp-pos-loading-overlay').fadeOut(200);
                }
            });
        },
        
        /**
         * Obtener nonce de seguridad
         */
        getNonce: function() {
            // Intentar desde configuración global
            if (typeof wp_pos_reports_config !== 'undefined' && wp_pos_reports_config.nonce) {
                return wp_pos_reports_config.nonce;
            }
            
            // Intentar desde WPPosReports
            if (window.WPPosReports && window.WPPosReports.config && window.WPPosReports.config.nonce) {
                return window.WPPosReports.config.nonce;
            }
            
            // Buscar en el DOM
            var $nonceField = $('input[name="_wpnonce"], input[name="security"]').first();
            if ($nonceField.length > 0) {
                return $nonceField.val();
            }
            
            console.warn('No se pudo obtener nonce de seguridad');
            return '';
        },
        
        /**
         * Diagnóstico del sistema
         */
        diagnose: function() {
            var diagnosis = {
                coordinator: this.state,
                wpPosReports: {
                    exists: typeof window.WPPosReports !== 'undefined',
                    initialized: window.WPPosReports && window.WPPosReports.state && window.WPPosReports.state.initialized,
                    hasApplyFilters: window.WPPosReports && typeof window.WPPosReports.applyFilters === 'function'
                },
                wpPosFilters: {
                    exists: typeof window.WPPosFilters !== 'undefined',
                    initialized: window.WPPosFilters && window.WPPosFilters.state && window.WPPosFilters.state.initialized,
                    hasGetFilterValues: window.WPPosFilters && typeof window.WPPosFilters.getCurrentFilterValues === 'function'
                },
                dom: {
                    formExists: $('#wp-pos-filter-form').length > 0,
                    ajaxButtonExists: $('#wp-pos-apply-filters-ajax').length > 0,
                    dashboardExists: $('.wp-pos-reports-dashboard').length > 0
                },
                globalMethods: {
                    wpPosApplyFiltersAjax: typeof window.wpPosApplyFiltersAjax === 'function',
                    WPPosApplyFilters: typeof window.WPPosApplyFilters === 'function',
                    WPPosGetFilterValues: typeof window.WPPosGetFilterValues === 'function'
                }
            };
            
            console.table(diagnosis);
            return diagnosis;
        }
    };
    
    // Inicialización
    $(document).ready(function() {
        // Esperar un poco para que otros scripts se carguen
        setTimeout(function() {
            if ($('.wp-pos-reports-dashboard').length > 0 || $('[id*="wp-pos-reports"]').length > 0) {
                WPPosReportsCoordinator.init();
            }
        }, 100);
    });
    
    // Exponer globalmente para debugging
    window.WPPosReportsCoordinator = WPPosReportsCoordinator;
    
    // Método de diagnóstico global
    window.wpPosDiagnose = function() {
        return WPPosReportsCoordinator.diagnose();
    };
    
})(jQuery);