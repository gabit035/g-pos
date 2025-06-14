/**
 * Coordinador WP-POS Reports - Prevención de Conflictos
 * SOLUCION para error 404: wp-pos-reports-coordinator.js
 * 
 * Colocar en: modules/reports/assets/js/wp-pos-reports-coordinator.js
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
            filtersReady: false,
            loadAttempts: 0,
            maxAttempts: 10
        },
        
        /**
         * Inicializar coordinador
         */
        init: function() {
            console.log('=== INICIALIZANDO WP-POS Coordinator (CORREGIDO) ===');
            
            this.setupGlobalMethods();
            this.setupEventListeners();
            this.checkComponentsReady();
            this.setupHealthCheck();
            
            this.state.initialized = true;
            console.log('=== WP-POS Coordinator INICIALIZADO (CORREGIDO) ===');
        },
        
        /**
         * Configurar métodos globales de respaldo mejorados
         */
        setupGlobalMethods: function() {
            var self = this;
            
            // Bind all methods to maintain proper context
            this.getFilterValues = this.getFilterValues.bind(this);
            this.executeFilterApplication = this.executeFilterApplication.bind(this);
            this.diagnose = this.diagnose.bind(this);
            this.forceInitialization = this.forceInitialization.bind(this);
            
            // Método global robusto para aplicar filtros con múltiples intentos
            if (!window.wpPosApplyFiltersAjax) {
                window.wpPosApplyFiltersAjax = function() {
                    console.log('=== wpPosApplyFiltersAjax (Coordinador CORREGIDO) ===');
                    return self.executeFilterApplication();
                };
            }
            
            // Método de respaldo adicional
            if (!window.WPPosApplyFilters) {
                window.WPPosApplyFilters = function() {
                    console.log('=== WPPosApplyFilters (Coordinador CORREGIDO) ===');
                    return self.executeFilterApplication();
                };
            }
            
            // Método para obtener filtros con fallbacks
            if (!window.WPPosGetFilterValues) {
                window.WPPosGetFilterValues = function() {
                    return self.getFilterValues();
                };
            }
            
            // Método de diagnóstico global
            window.wpPosDiagnose = function() {
                return self.diagnose();
            };
            
            // Método para forzar inicialización
            window.wpPosForceInit = function() {
                self.forceInitialization();
            };
            
            console.log('Métodos globales de respaldo configurados (CORREGIDO)');
        },
        
        /**
         * Configurar listeners de eventos
         */
        setupEventListeners: function() {
            var self = this;
            
            // Escuchar cuando los componentes estén listos
            $(document).on('wp-pos:reports-ready', function() {
                console.log('Evento wp-pos:reports-ready recibido');
                self.state.reportsReady = true;
                self.checkComponentsReady();
            });
            
            $(document).on('wp-pos:filters-ready', function() {
                console.log('Evento wp-pos:filters-ready recibido');
                self.state.filtersReady = true;
                self.checkComponentsReady();
            });
            
            // Escuchar eventos de aplicación de filtros
            $(document).on('wp-pos:apply-filters', function(event, filterData) {
                console.log('Evento wp-pos:apply-filters interceptado por coordinador:', filterData);
                self.executeFilterApplication(filterData);
            });
            
            // Evento personalizado para forzar verificación
            $(document).on('wp-pos:force-check', function() {
                self.forceComponentCheck();
            });
        },
        
        /**
         * Configurar health check periódico
         */
        setupHealthCheck: function() {
            var self = this;
            
            // Verificar estado cada 5 segundos durante el primer minuto
            var healthCheckInterval = setInterval(function() {
                self.state.loadAttempts++;
                
                if (self.state.loadAttempts > self.state.maxAttempts) {
                    clearInterval(healthCheckInterval);
                    console.warn('WP-POS Coordinator: Se alcanzó el máximo de intentos de verificación');
                    return;
                }
                
                if (!self.state.reportsReady || !self.state.filtersReady) {
                    console.log('Health check - Verificando componentes...');
                    self.recheckComponents();
                } else {
                    clearInterval(healthCheckInterval);
                    console.log('Health check - Todos los componentes listos, deteniendo verificación');
                }
            }, 5000);
        },
        
        /**
         * Verificar si todos los componentes están listos
         */
        checkComponentsReady: function() {
            this.recheckComponents();
            
            if (this.state.reportsReady && this.state.filtersReady) {
                console.log('✓ Todos los componentes WP-POS están listos');
                $(document).trigger('wp-pos:all-ready');
                this.displayReadyStatus();
            } else {
                console.log('Esperando componentes...', {
                    reports: this.state.reportsReady,
                    filters: this.state.filtersReady,
                    attempt: this.state.loadAttempts
                });
            }
        },
        
        /**
         * Re-verificar componentes de forma más robusta
         */
        recheckComponents: function() {
            // Verificar WPPosReports
            this.state.reportsReady = this.isWPPosReportsReady();
            
            // Verificar WPPosFilters
            this.state.filtersReady = this.isWPPosFiltersReady();
            
            console.log('Recheck components:', {
                reports: this.state.reportsReady,
                filters: this.state.filtersReady
            });
        },
        
        /**
         * Verificar si WPPosReports está listo
         */
        isWPPosReportsReady: function() {
            return typeof window.WPPosReports !== 'undefined' && 
                   window.WPPosReports && 
                   typeof window.WPPosReports.applyFilters === 'function' &&
                   window.WPPosReports.state && 
                   window.WPPosReports.state.initialized === true;
        },
        
        /**
         * Verificar si WPPosFilters está listo
         */
        isWPPosFiltersReady: function() {
            return typeof window.WPPosFilters !== 'undefined' && 
                   window.WPPosFilters && 
                   typeof window.WPPosFilters.getCurrentFilterValues === 'function' &&
                   window.WPPosFilters.state && 
                   window.WPPosFilters.state.initialized === true;
        },
        
        /**
         * Forzar verificación de componentes
         */
        forceComponentCheck: function() {
            console.log('Forzando verificación de componentes...');
            this.state.loadAttempts = 0; // Reset attempts
            this.recheckComponents();
            this.checkComponentsReady();
        },
        
        /**
         * Forzar inicialización de componentes
         */
        forceInitialization: function() {
            console.log('Forzando inicialización de componentes...');
            
            // Intentar inicializar WPPosReports si no está listo
            if (!this.isWPPosReportsReady() && typeof window.WPPosReports !== 'undefined') {
                try {
                    if (typeof window.WPPosReports.init === 'function') {
                        window.WPPosReports.init();
                    }
                } catch (error) {
                    console.error('Error forzando inicialización de WPPosReports:', error);
                }
            }
            
            // Intentar inicializar WPPosFilters si no está listo
            if (!this.isWPPosFiltersReady() && typeof window.WPPosFilters !== 'undefined') {
                try {
                    if (typeof window.WPPosFilters.init === 'function') {
                        window.WPPosFilters.init();
                    }
                } catch (error) {
                    console.error('Error forzando inicialización de WPPosFilters:', error);
                }
            }
            
            // Verificar después de los intentos de inicialización
            setTimeout(() => {
                this.forceComponentCheck();
            }, 1000);
        },
        
        /**
         * Mostrar estado de listo en consola
         */
        displayReadyStatus: function() {
            console.log('%c✓ WP-POS Reports Sistema Completamente Listo', 
                'background: #4CAF50; color: white; padding: 5px 10px; border-radius: 3px; font-weight: bold');
            console.log('Todos los módulos están funcionando correctamente');
        },
        
        /**
         * Ejecutar aplicación de filtros con múltiples estrategias MEJORADAS
         */
        executeFilterApplication: function(filterData) {
            console.log('=== executeFilterApplication (Coordinador CORREGIDO) ===');
            
            // ESTRATEGIA 1: Usar WPPosReports si está completamente listo
            if (this.isWPPosReportsReady()) {
                console.log('✓ Estrategia 1: Usando WPPosReports.applyFilters');
                try {
                    window.WPPosReports.applyFilters.call(window.WPPosReports);
                    return true;
                } catch (error) {
                    console.error('Error en estrategia 1:', error);
                }
            }
            
            // ESTRATEGIA 2: Intentar llamada directa más robusta
            if (typeof window.WPPosReports !== 'undefined' && 
                typeof window.WPPosReports.applyFilters === 'function') {
                console.log('✓ Estrategia 2: Llamada directa a WPPosReports.applyFilters');
                try {
                    var result = window.WPPosReports.applyFilters();
                    return result !== false;
                } catch (error) {
                    console.error('Error en estrategia 2:', error);
                }
            }
            
            // ESTRATEGIA 3: Simular evento de click en botón AJAX
            var $ajaxButton = $('#wp-pos-apply-filters-ajax');
            if ($ajaxButton.length > 0 && $ajaxButton.is(':visible')) {
                console.log('✓ Estrategia 3: Simulando click en botón AJAX');
                try {
                    $ajaxButton.trigger('click');
                    return true;
                } catch (error) {
                    console.error('Error en estrategia 3:', error);
                }
            }
            
            // ESTRATEGIA 4: Petición AJAX manual con datos mejorados
            console.log('✓ Estrategia 4: Petición AJAX manual');
            try {
                var filters = filterData || this.getFilterValues();
                if (filters) {
                    this.makeManualAjaxRequest(filters);
                    return true;
                }
            } catch (error) {
                console.error('Error en estrategia 4:', error);
            }
            
            // ESTRATEGIA 5: Envío de formulario tradicional
            console.log('✓ Estrategia 5: Envío de formulario tradicional');
            try {
                var $form = $('#wp-pos-filter-form');
                if ($form.length > 0) {
                    // Asegurar que los valores estén en el formulario
                    this.ensureFormValues($form, filterData);
                    $form.submit();
                    return true;
                }
            } catch (error) {
                console.error('Error en estrategia 5:', error);
            }
            
            // ESTRATEGIA 6: Recargar página como último recurso
            console.warn('⚠ Estrategia 6: Recargando página como último recurso');
            setTimeout(function() {
                window.location.reload();
            }, 2000);
            
            return false;
        },
        
        /**
         * Asegurar valores en formulario
         */
        ensureFormValues: function($form, filterData) {
            if (!filterData) {
                filterData = this.getFilterValues();
            }
            
            if (filterData) {
                // Actualizar campos del formulario
                $.each(filterData, function(key, value) {
                    var $field = $form.find('[name="' + key + '"]');
                    if ($field.length > 0) {
                        $field.val(value);
                    } else if (value) {
                        // Crear campo oculto si no existe
                        $form.append('<input type="hidden" name="' + key + '" value="' + value + '" data-coordinator-added="true">');
                    }
                });
            }
        },
        
        /**
         * Obtener valores de filtros con múltiples fallbacks
         */
        getFilterValues: function() {
            console.log('=== getFilterValues (Coordinador CORREGIDO) ===');
            
            // OPCIÓN 1: Usar WPPosReports si está disponible
            if (this.isWPPosReportsReady()) {
                try {
                    var reportsFilters = window.WPPosReports.getFilterValues();
                    if (reportsFilters && typeof reportsFilters === 'object') {
                        console.log('Filtros desde WPPosReports:', reportsFilters);
                        return reportsFilters;
                    }
                } catch (error) {
                    console.warn('Error al obtener filtros desde WPPosReports:', error);
                }
            }
            
            // OPCIÓN 2: Usar WPPosFilters si está disponible
            if (this.isWPPosFiltersReady()) {
                try {
                    var filtersFilters = window.WPPosFilters.getCurrentFilterValues();
                    if (filtersFilters && typeof filtersFilters === 'object') {
                        console.log('Filtros desde WPPosFilters:', filtersFilters);
                        return filtersFilters;
                    }
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
         * Hacer petición AJAX manual mejorada
         */
        makeManualAjaxRequest: function(filterData) {
            console.log('=== makeManualAjaxRequest (MEJORADA) ===');
            
            var ajaxData = {
                action: 'get_pos_report_data',
                security: this.getNonce()
            };
            
            $.extend(ajaxData, filterData);
            
            // Mostrar loading
            this.showLoading(true);
            
            $.ajax({
                url: this.getAjaxUrl(),
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    console.log('Respuesta AJAX manual exitosa:', response);
                    
                    if (response.success && response.data) {
                        // Actualizar contenido
                        if (response.data.html_summary_cards) {
                            $('#wp-pos-summary-cards-placeholder').html(response.data.html_summary_cards);
                        }
                        if (response.data.html_recent_sales_table) {
                            $('#wp-pos-recent-sales-table-placeholder').html(response.data.html_recent_sales_table);
                        }
                        if (response.data.html_charts_section) {
                            $('#wp-pos-charts-section-placeholder').html(response.data.html_charts_section);
                        }
                        
                        // Actualizar gráficos si es posible
                        if (response.data.chart_data && typeof window.updateWPPosCharts === 'function') {
                            window.updateWPPosCharts(response.data.chart_data);
                        }
                        
                        // Mostrar éxito
                        WPPosReportsCoordinator.showNotification('Datos actualizados correctamente', 'success');
                    } else {
                        console.error('Error en respuesta AJAX manual:', response);
                        WPPosReportsCoordinator.showNotification(
                            response.data?.message || 'Error al actualizar datos', 
                            'error'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en petición AJAX manual:', {xhr, status, error});
                    WPPosReportsCoordinator.showNotification('Error de conexión: ' + error, 'error');
                },
                complete: function() {
                    WPPosReportsCoordinator.showLoading(false);
                }
            });
        },
        
        /**
         * Obtener URL de AJAX
         */
        getAjaxUrl: function() {
            if (typeof window.ajaxurl !== 'undefined') {
                return window.ajaxurl;
            }
            
            if (typeof wp_pos_reports_config !== 'undefined' && wp_pos_reports_config.ajaxUrl) {
                return wp_pos_reports_config.ajaxUrl;
            }
            
            return '/wp-admin/admin-ajax.php';
        },
        
        /**
         * Obtener nonce de seguridad mejorado
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
            var $nonceField = $('input[name="_wpnonce"], input[name="security"], input[id*="nonce"]').first();
            if ($nonceField.length > 0) {
                return $nonceField.val();
            }
            
            // Buscar en meta tags
            var $nonceMeta = $('meta[name="wp-pos-nonce"]');
            if ($nonceMeta.length > 0) {
                return $nonceMeta.attr('content');
            }
            
            console.warn('No se pudo obtener nonce de seguridad');
            return '';
        },
        
        /**
         * Mostrar/ocultar loading
         */
        showLoading: function(show) {
            var $loading = $('.wp-pos-loading-overlay');
            if (show) {
                $loading.fadeIn(200);
            } else {
                $loading.fadeOut(200);
            }
        },
        
        /**
         * Mostrar notificación
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            // Intentar usar el sistema de notificaciones existente
            if (window.WPPosReports && typeof window.WPPosReports.showNotification === 'function') {
                window.WPPosReports.showNotification(message, type);
                return;
            }
            
            // Fallback: crear notificación simple
            var $notification = $('.wp-pos-notification');
            if ($notification.length === 0) {
                $('body').append('<div class="wp-pos-notification" style="position:fixed;top:60px;right:30px;z-index:10000;display:none;"></div>');
                $notification = $('.wp-pos-notification');
            }
            
            $notification
                .removeClass('success error info warning')
                .addClass(type)
                .text(message)
                .fadeIn(300);
            
            setTimeout(function() {
                $notification.fadeOut(300);
            }, 3000);
        },
        
        /**
         * Diagnóstico completo del sistema
         */
        diagnose: function() {
            var diagnosis = {
                coordinator: {
                    initialized: this.state.initialized,
                    reportsReady: this.state.reportsReady,
                    filtersReady: this.state.filtersReady,
                    loadAttempts: this.state.loadAttempts
                },
                wpPosReports: {
                    exists: typeof window.WPPosReports !== 'undefined',
                    initialized: this.isWPPosReportsReady(),
                    hasApplyFilters: window.WPPosReports && typeof window.WPPosReports.applyFilters === 'function',
                    hasGetFilterValues: window.WPPosReports && typeof window.WPPosReports.getFilterValues === 'function',
                    state: window.WPPosReports ? window.WPPosReports.state : null
                },
                wpPosFilters: {
                    exists: typeof window.WPPosFilters !== 'undefined',
                    initialized: this.isWPPosFiltersReady(),
                    hasGetFilterValues: window.WPPosFilters && typeof window.WPPosFilters.getCurrentFilterValues === 'function',
                    state: window.WPPosFilters ? window.WPPosFilters.state : null
                },
                dom: {
                    formExists: $('#wp-pos-filter-form').length > 0,
                    ajaxButtonExists: $('#wp-pos-apply-filters-ajax').length > 0,
                    dashboardExists: $('.wp-pos-reports-dashboard').length > 0,
                    placeholdersExist: {
                        summaryCards: $('#wp-pos-summary-cards-placeholder').length > 0,
                        recentSales: $('#wp-pos-recent-sales-table-placeholder').length > 0,
                        charts: $('#wp-pos-charts-section-placeholder').length > 0
                    }
                },
                globalMethods: {
                    wpPosApplyFiltersAjax: typeof window.wpPosApplyFiltersAjax === 'function',
                    WPPosApplyFilters: typeof window.WPPosApplyFilters === 'function',
                    WPPosGetFilterValues: typeof window.WPPosGetFilterValues === 'function',
                    ajaxurl: typeof window.ajaxurl !== 'undefined'
                },
                config: {
                    hasConfig: typeof wp_pos_reports_config !== 'undefined',
                    nonce: this.getNonce() ? 'Disponible' : 'Faltante',
                    ajaxUrl: this.getAjaxUrl()
                }
            };
            
            console.group('🔍 Diagnóstico WP-POS Reports');
            console.table(diagnosis.coordinator);
            console.table(diagnosis.wpPosReports);
            console.table(diagnosis.wpPosFilters);
            console.table(diagnosis.dom);
            console.table(diagnosis.globalMethods);
            console.table(diagnosis.config);
            console.groupEnd();
            
            return diagnosis;
        }
    };
    
    // Inicialización robusta
    $(document).ready(function() {
        // Verificar si estamos en la página correcta
        if ($('.wp-pos-reports-dashboard').length > 0 || 
            $('[id*="wp-pos-reports"]').length > 0 ||
            window.location.href.indexOf('wp-pos-reports') !== -1) {
            
            console.log('Página de reportes detectada, inicializando coordinador...');
            
            // Esperar un poco para que otros scripts se carguen
            setTimeout(function() {
                try {
                    WPPosReportsCoordinator.init();
                } catch (error) {
                    console.error('Error inicializando coordinador:', error);
                }
            }, 250);
        }
    });
    
    // Exponer globalmente
    window.WPPosReportsCoordinator = WPPosReportsCoordinator;
    
})(jQuery);