/**
 * Scripts CORREGIDOS FINAL para el Panel de Reportes WP-POS
 * SOLUCION DEFINITIVA: Problemas de contexto (this) completamente resueltos
 *
 * @package WP-POS
 * @subpackage Reports
 */

;(function($){
    'use strict';
    
    // Objeto principal para manejar reportes
    var WPPosReports = {
        
        // Configuración
        config: {
            ajaxUrl: window.ajaxurl || '',
            nonce: '',
            debug: false,
            autoRefresh: false,
            refreshInterval: 30000,
            recentSalesLimit: 5,
            strings: {
                loading: 'Cargando...',
                noRecentSales: 'No hay ventas recientes para mostrar.',
                errorLoading: 'Error al cargar las ventas recientes. Intenta de nuevo.',
                success_update: 'Datos actualizados correctamente',
                error_connection: 'Error de conexión',
                error_data: 'Error al cargar los datos'
            }
        },
        
        // Cache de datos
        cache: {
            lastFilters: {},
            lastData: null
        },
        
        // Estado de la aplicación
        state: {
            loading: false,
            initialized: false,
            autoRefreshTimer: null
        },
        
        /**
         * Inicialización mejorada
         */
        init: function() {
            console.log('=== INICIALIZANDO WP-POS Reports (DEFINITIVO) ===');
            
            // Configurar desde datos localizados
            if (typeof wp_pos_reports_config !== 'undefined') {
                $.extend(this.config, wp_pos_reports_config);
                console.log('Configuración cargada:', this.config);
            }
            
            // Configurar elementos DOM
            this.setupDOM();
            
            // Configurar eventos
            this.setupEvents();
            
            // Configurar filtros dinámicos
            this.setupFilters();
            
            // Configurar acciones de tabla
            this.setupTableActions();
            
            // Configurar auto-refresh si está habilitado
            if (this.config.autoRefresh) {
                this.setupAutoRefresh();
            }
            
            // Cargar ventas recientes solo si existe el contenedor
            if ($('.wp-pos-recent-activity-container').length > 0) {
                this.loadRecentSales();
            }
            
            // Marcar como inicializado
            this.state.initialized = true;
            
            this.log('WP-POS Reports initialized successfully');
            console.log('=== WP-POS Reports INICIALIZADO DEFINITIVO ===');
            
            // SOLUCION: Registrar métodos globalmente con contexto preservado
            this.registerGlobalMethods();
        },
        
        /**
         * NUEVO: Registrar métodos globalmente con contexto preservado
         */
        registerGlobalMethods: function() {
            var self = this; // Capturar referencia para closures
            
            // Función global principal para aplicar filtros
            window.wpPosApplyFiltersAjax = function() {
                console.log('=== wpPosApplyFiltersAjax (DEFINITIVO) ===');
                console.log('Contexto self:', self);
                
                if (self && typeof self.applyFilters === 'function') {
                    self.applyFilters();
                } else {
                    console.error('WPPosReports no disponible o applyFilters falta');
                }
            };
            
            // Función alternativa
            window.WPPosApplyFilters = function() {
                if (self && typeof self.applyFilters === 'function') {
                    self.applyFilters();
                }
            };
            
            // Función para refrescar datos
            window.WPPosRefreshData = function() {
                if (self && typeof self.refreshData === 'function') {
                    self.refreshData();
                }
            };
            
            // Función para obtener filtros (para reports-filters.js)
            window.WPPosGetFilterValues = function() {
                if (self && typeof self.getFilterValues === 'function') {
                    return self.getFilterValues();
                }
                return null;
            };
            
            console.log('Métodos globales registrados con contexto preservado');
        },
        
        /**
         * Configurar elementos DOM
         */
        setupDOM: function() {
            // Crear elementos necesarios si no existen
            if (!$('.wp-pos-notification').length) {
                $('body').append('<div class="wp-pos-notification" style="display:none;"></div>');
            }
            
            if (!$('.wp-pos-loading-overlay').length && $('.wp-pos-reports-dashboard').length > 0) {
                $('.wp-pos-reports-dashboard').append(
                    '<div class="wp-pos-loading-overlay" style="display:none;">' +
                    '<div class="wp-pos-spinner-container">' +
                    '<div class="wp-pos-spinner"></div>' +
                    '<p>' + (this.config.strings?.loading || 'Cargando...') + '</p>' +
                    '</div></div>'
                );
            }
        },
        
        /**
         * CORREGIDO: Configurar eventos principales
         */
        setupEvents: function() {
            var self = this; // Capturar contexto para eventos
            
            // Limpiar eventos previos con namespace específico
            $(document).off('.wp-pos-reports-main');
            
            // Botones de refrescar
            $(document).on('click.wp-pos-reports-main', '.wp-pos-refresh-button, #wp-pos-refresh-data', function(e) {
                e.preventDefault();
                self.refreshData();
            });
            
            // Botón AJAX principal (con namespace específico para evitar conflictos)
            $(document).on('click.wp-pos-reports-main', '#wp-pos-apply-filters-ajax, .wp-pos-apply-filters-ajax:not(.wp-pos-apply-filters-get)', function(e) {
                e.preventDefault();
                console.log('Click en botón AJAX principal, aplicando filtros...');
                self.applyFilters();
            });
            
            // Acciones de tabla
            $(document).on('click.wp-pos-reports-main', '.action-view', function(e) {
                e.preventDefault();
                self.viewSaleDetails($(this));
            });
            
            $(document).on('click.wp-pos-reports-main', '.action-ticket', function(e) {
                e.preventDefault();
                self.printTicket($(this));
            });
            
            console.log('Eventos configurados con contexto preservado');
        },
        
        /**
         * CORREGIDO: Configurar filtros sin conflictos
         */
        setupFilters: function() {
            var self = this;
            
            // Solo manejar el toggle de fechas personalizadas
            $(document).off('.wp-pos-date-main').on('change.wp-pos-date-main', '#wp-pos-periodo', function() {
                self.toggleCustomDates();
            });
            
            // Inicializar estado de fechas personalizadas
            this.toggleCustomDates();
        },
        
        /**
         * Configurar acciones de tabla
         */
        setupTableActions: function() {
            // Ya incluido en setupEvents
        },
        
        /**
         * Mostrar/ocultar campos de fecha personalizada
         */
        toggleCustomDates: function() {
            var period = $('#wp-pos-periodo').val();
            var $customDates = $('.wp-pos-custom-dates');
            
            if (period === 'custom') {
                $customDates.slideDown(200);
                // Asignar fechas por defecto si están vacías
                if (!$('#wp-pos-date-from').val()) {
                    $('#wp-pos-date-from').val(new Date().toISOString().split('T')[0]);
                }
                if (!$('#wp-pos-date-to').val()) {
                    $('#wp-pos-date-to').val(new Date().toISOString().split('T')[0]);
                }
            } else {
                $customDates.slideUp(200);
            }
        },
        
        /**
         * SOLUCIONADO DEFINITIVO: Aplicar filtros con AJAX
         */
        applyFilters: function() {
            console.log('=== INICIO applyFilters (DEFINITIVO) ===');
            console.log('Contexto this:', this);
            console.log('¿Es WPPosReports?', this === WPPosReports);
            console.log('¿Tiene getFilterValues?', typeof this.getFilterValues === 'function');
            
            if (this.state.loading) {
                this.log('Ya hay una petición en curso, ignorando...');
                return;
            }
            
            // Obtener valores de filtros con manejo de errores robusto
            var filters;
            try {
                filters = this.getFilterValues();
                console.log('Filtros obtenidos:', filters);
            } catch (error) {
                console.error('Error al obtener filtros:', error);
                this.showNotification('Error al obtener valores de filtros', 'error');
                return;
            }
            
            // Verificar si los filtros han cambiado
            if (JSON.stringify(filters) === JSON.stringify(this.cache.lastFilters)) {
                this.log('Los filtros no han cambiado, usando cache');
                return;
            }
            
            // Validar filtros
            if (!this.validateFilters(filters)) {
                return;
            }
            
            // Mostrar loading
            this.showLoading();
            
            // Preparar datos para AJAX
            var ajaxData = {
                action: 'get_pos_report_data',
                security: this.config.nonce
            };
            
            // Agregar filtros a los datos
            $.extend(ajaxData, filters);
            
            this.log('Enviando petición AJAX:', ajaxData);
            
            var self = this; // Capturar contexto para callbacks
            
            // Realizar petición AJAX
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                timeout: 30000,
                dataType: 'json',
                success: function(response) {
                    self.handleAjaxSuccess(response, filters);
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, status, error);
                },
                complete: function() {
                    self.hideLoading();
                }
            });
        },
        
        /**
         * CORREGIDO: Obtener valores de filtros de forma robusta
         */
        getFilterValues: function() {
            console.log('=== getFilterValues EJECUTADO ===');
            
            var filters = {
                period: $('#wp-pos-periodo').val() || 'today',
                seller_id: $('#wp-pos-vendedor').val() || 'all',
                payment_method: $('#wp-pos-payment-method').val() || 'all',
                date_from: $('#wp-pos-date-from').val() || '',
                date_to: $('#wp-pos-date-to').val() || ''
            };
            
            console.log('getFilterValues resultado:', filters);
            return filters;
        },
        
        /**
         * Validar filtros
         */
        validateFilters: function(filters) {
            // Validar fechas personalizadas
            if (filters.period === 'custom') {
                if (!filters.date_from || !filters.date_to) {
                    this.showNotification('Por favor, selecciona ambas fechas para el período personalizado.', 'error');
                    return false;
                }
                
                if (new Date(filters.date_from) > new Date(filters.date_to)) {
                    this.showNotification('La fecha de inicio no puede ser mayor que la fecha de fin.', 'error');
                    return false;
                }
            }
            
            return true;
        },
        
        /**
         * Manejar éxito de AJAX
         */
        handleAjaxSuccess: function(response, filters) {
            this.log('Respuesta AJAX recibida:', response);
            
            if (response.success && response.data) {
                // Actualizar contenido si existen los contenedores
                if (response.data.html_summary_cards && $('#wp-pos-summary-cards-placeholder').length) {
                    $('#wp-pos-summary-cards-placeholder').html(response.data.html_summary_cards);
                }
                
                if (response.data.html_recent_sales_table && $('#wp-pos-recent-sales-table-placeholder').length) {
                    $('#wp-pos-recent-sales-table-placeholder').html(response.data.html_recent_sales_table);
                }
                
                if (response.data.html_charts_section && $('#wp-pos-charts-section-placeholder').length) {
                    $('#wp-pos-charts-section-placeholder').html(response.data.html_charts_section);
                }
                
                // Actualizar gráficos si hay datos
                if (response.data.chart_data && typeof window.updateWPPosCharts === 'function') {
                    try {
                        window.updateWPPosCharts(response.data.chart_data);
                    } catch (chartError) {
                        console.warn('Error al actualizar gráficos:', chartError);
                    }
                }
                
                // Actualizar cache
                this.cache.lastFilters = filters;
                this.cache.lastData = response.data;
                
                // Mostrar notificación de éxito
                this.showNotification(
                    this.config.strings?.success_update || 'Datos actualizados correctamente',
                    'success'
                );
                
                // Disparar evento personalizado
                $(document).trigger('wp-pos:reports-updated', [response.data]);
                
            } else {
                var errorMsg = response.data?.message || 
                              this.config.strings?.error_data || 
                              'Error al cargar los datos';
                this.showNotification(errorMsg, 'error');
            }
        },
        
        /**
         * Manejar error de AJAX
         */
        handleAjaxError: function(xhr, status, error) {
            this.log('Error AJAX:', {xhr: xhr, status: status, error: error});
            
            var errorMsg = this.config.strings?.error_connection || 'Error de conexión';
            
            if (status === 'timeout') {
                errorMsg = 'La petición ha excedido el tiempo límite. Inténtalo de nuevo.';
            } else if (xhr.status === 403) {
                errorMsg = 'Acceso denegado. Por favor, recarga la página.';
            } else if (xhr.status === 500) {
                errorMsg = 'Error interno del servidor. Contacta al administrador.';
            } else if (xhr.status === 400) {
                errorMsg = 'Petición incorrecta. Verifica los datos enviados.';
            }
            
            // Intentar obtener mensaje específico del servidor
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                errorMsg = xhr.responseJSON.data.message;
            }
            
            this.showNotification(errorMsg, 'error');
        },
        
        /**
         * Refrescar datos
         */
        refreshData: function() {
            // Limpiar cache
            this.cache.lastFilters = {};
            this.cache.lastData = null;
            
            // Aplicar filtros actuales
            this.applyFilters();
        },
        
        /**
         * Ver detalles de venta
         */
        viewSaleDetails: function($button) {
            var saleId = $button.data('sale-id');
            var nonce = $button.data('nonce');
            
            if (!saleId) {
                this.log('ID de venta no válido');
                return;
            }
            
            var url = this.config.ajaxUrl + 
                      '?action=wp_pos_view_sale&sale_id=' + saleId + 
                      '&_wpnonce=' + nonce;
            
            var popup = window.open(
                url, 
                'wp-pos-sale-details-' + saleId, 
                'width=900,height=700,resizable=yes,scrollbars=yes,location=no,menubar=no,toolbar=no'
            );
            
            if (popup) {
                popup.focus();
            } else {
                this.showNotification('Por favor, permite las ventanas emergentes para este sitio.', 'error');
            }
        },
        
        /**
         * Imprimir ticket
         */
        printTicket: function($button) {
            var saleId = $button.data('sale-id');
            var nonce = $button.data('nonce');
            
            if (!saleId) {
                this.log('ID de venta no válido');
                return;
            }
            
            var url = this.config.ajaxUrl + 
                      '?action=wp_pos_print_ticket&sale_id=' + saleId + 
                      '&_wpnonce=' + nonce;
            
            var popup = window.open(
                url, 
                'wp-pos-ticket-' + saleId, 
                'width=350,height=600,resizable=yes,scrollbars=yes,location=no,menubar=no,toolbar=no'
            );
            
            if (popup) {
                popup.focus();
            } else {
                this.showNotification('Por favor, permite las ventanas emergentes para este sitio.', 'error');
            }
        },
        
        /**
         * Mostrar/ocultar loading
         */
        showLoading: function() {
            this.state.loading = true;
            $('.wp-pos-loading-overlay').fadeIn(200);
        },
        
        hideLoading: function() {
            this.state.loading = false;
            $('.wp-pos-loading-overlay').fadeOut(200);
        },
        
        /**
         * Mostrar notificaciones mejoradas
         */
        showNotification: function(message, type, duration) {
            var $notification = $('.wp-pos-notification');
            
            type = type || 'info';
            duration = duration || 3000;
            
            $notification
                .removeClass('success error info warning')
                .addClass(type)
                .text(message)
                .fadeIn(300);
            
            // Auto-ocultar
            setTimeout(function() {
                $notification.fadeOut(300);
            }, duration);
        },
        
        /**
         * Configurar auto-refresh
         */
        setupAutoRefresh: function() {
            if (this.state.autoRefreshTimer) {
                clearInterval(this.state.autoRefreshTimer);
            }
            
            var self = this; // Capturar contexto
            
            this.state.autoRefreshTimer = setInterval(function() {
                if (!self.state.loading) {
                    self.log('Auto-refresh ejecutado');
                    self.applyFilters();
                }
            }, this.config.refreshInterval);
        },
        
        /**
         * Logging para debug
         */
        log: function() {
            if (this.config.debug && console && console.log) {
                console.log.apply(console, ['[WP-POS Reports]'].concat(Array.prototype.slice.call(arguments)));
            }
        },
        
        /**
         * Cargar ventas recientes vía AJAX
         */
        loadRecentSales: function() {
            var $container = $('.wp-pos-recent-activity-container');
            var $loading = $container.find('.wp-pos-activity-loading');
            var $content = $container.find('.wp-pos-activity-content');
            
            // Si no existe el contenedor, no hacer nada
            if ($container.length === 0) {
                this.log('Contenedor de actividad reciente no encontrado, saltando loadRecentSales');
                return;
            }
            
            console.log('=== INICIO loadRecentSales ===');
            
            // Mostrar loading
            $loading.show();
            $content.hide();
            
            // Verificar si hay un nonce disponible
            if (!this.config.nonce) {
                console.error('Error: No se encontró el nonce de seguridad');
                this.showEmptyState(true, 'Error de seguridad: Falta el nonce');
                return;
            }
            
            var self = this; // Capturar contexto para callbacks
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'wp_pos_get_recent_sales',
                    nonce: this.config.nonce,
                    limit: this.config.recentSalesLimit || 5,
                    _t: new Date().getTime()
                },
                dataType: 'json',
                success: function(response, status, xhr) {
                    console.log('Respuesta loadRecentSales:', response);
                    
                    if (response && response.success) {
                        var salesData = response.data?.sales || response.data?.data || response.data || [];
                        
                        if (salesData.length > 0) {
                            self.renderRecentSales(salesData);
                            $content.slideDown();
                        } else {
                            self.showEmptyState(false, 'No hay ventas recientes para mostrar');
                        }
                    } else {
                        var errorMsg = response?.data?.message || 'Error desconocido al cargar las ventas';
                        self.showEmptyState(true, errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en loadRecentSales:', status, error);
                    var errorMsg = 'Error al conectar con el servidor';
                    if (xhr.responseJSON?.data?.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    self.showEmptyState(true, errorMsg);
                },
                complete: function() {
                    $loading.hide();
                    if ($content.find('.wp-pos-activity-empty').length === 0) {
                        $content.show();
                    }
                }
            });
        },
        
        /**
         * Renderizar la lista de ventas recientes
         */
        renderRecentSales: function(sales) {
            var $container = $('.wp-pos-activity-content');
            var html = '<ul class="wp-pos-activity-list">';
            
            // Mapear métodos de pago a iconos
            var paymentIcons = {
                'efectivo': 'money',
                'cash': 'money',
                'tarjeta': 'card',
                'card': 'card',
                'transferencia': 'bank',
                'transfer': 'bank',
                'cheque': 'media-document',
                'check': 'media-document',
                'pago_movil': 'smartphone',
                'zelle': 'bank',
                'paypal': 'admin-site-alt3'
            };
            
            var self = this; // Para usar en forEach
            
            // Generar HTML para cada venta
            sales.forEach(function(sale) {
                var paymentMethod = (sale.payment_method || 'efectivo').toLowerCase();
                var icon = paymentIcons[paymentMethod] || 'cart';
                var amount = parseFloat(sale.total || 0).toFixed(2);
                var statusClass = sale.status === 'completed' ? 'completed' : 'pending';
                var customerName = sale.customer_name || sale.display_name || 'Cliente no registrado';
                var formattedDate = self.formatDate(sale.created_at || sale.date);
                
                html += `
                    <li class="wp-pos-activity-item">
                        <div class="wp-pos-activity-icon">
                            <span class="dashicons dashicons-${icon}"></span>
                        </div>
                        <div class="wp-pos-activity-details">
                            <h4 class="wp-pos-activity-title">
                                ${customerName}
                            </h4>
                            <div class="wp-pos-activity-meta">
                                <span class="wp-pos-activity-date">
                                    <i class="dashicons dashicons-calendar-alt"></i> ${formattedDate}
                                </span>
                                <span class="wp-pos-activity-status ${statusClass}">
                                    <i class="dashicons dashicons-${sale.status === 'completed' ? 'yes' : 'clock'}"></i> 
                                    ${sale.status === 'completed' ? 'Completada' : 'Pendiente'}
                                </span>
                                <span class="wp-pos-activity-method">
                                    <i class="dashicons dashicons-${icon}"></i> ${sale.payment_method || 'Efectivo'}
                                </span>
                            </div>
                        </div>
                        <div class="wp-pos-activity-amount">
                            ${wp_pos_reports_config.currency_symbol || '$'} ${parseFloat(amount).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </div>
                    </li>`;
            });
            
            html += '</ul>';
            
            // Botón para ver todas las ventas
            html += `
                <div class="wp-pos-activity-actions">
                    <a href="#" class="button button-secondary">
                        <i class="dashicons dashicons-list-view"></i> Ver todas las ventas
                    </a>
                </div>`;
            
            $container.html(html);
        },
        
        /**
         * Formatear fecha
         */
        formatDate: function(dateString) {
            if (!dateString) return 'Fecha no disponible';
            
            try {
                var date = new Date(dateString);
                var now = new Date();
                var diff = now - date;
                
                // Menos de 1 hora
                if (diff < 3600000) {
                    var minutes = Math.floor(diff / 60000);
                    return `Hace ${minutes} min`;
                }
                
                // Menos de 24 horas
                if (diff < 86400000) {
                    var hours = Math.floor(diff / 3600000);
                    return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
                }
                
                // Más de 24 horas
                return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            } catch (e) {
                return dateString;
            }
        },
        
        /**
         * Mostrar estado vacío o error
         */
        showEmptyState: function(isError, customMessage) {
            var $container = $('.wp-pos-activity-content');
            var message = customMessage || (isError ? 
                this.config.strings.errorLoading : 
                this.config.strings.noRecentSales);
            var icon = isError ? 'dashicons-warning' : 'dashicons-info';
            
            var html = `
                <div class="wp-pos-activity-empty">
                    <span class="dashicons ${icon}"></span>
                    <p>${message}</p>`;
            
            if (isError) {
                html += `
                    <button class="button button-primary wp-pos-retry-loading">
                        <i class="dashicons dashicons-update"></i> Reintentar
                    </button>`;
            }
            
            html += '</div>';
            
            $container.html(html);
            
            var self = this; // Capturar contexto para evento
            
            // Configurar evento de reintento
            var $retryButton = $container.find('.wp-pos-retry-loading');
            if ($retryButton.length) {
                $retryButton.off('click').on('click', function(e) {
                    e.preventDefault();
                    self.loadRecentSales();
                });
            }
            
            $container.show();
        },
        
        /**
         * Destructor
         */
        destroy: function() {
            if (this.state.autoRefreshTimer) {
                clearInterval(this.state.autoRefreshTimer);
            }
            
            // Limpiar eventos con namespace específico
            $(document).off('.wp-pos-reports-main .wp-pos-date-main');
            
            this.state.initialized = false;
            this.log('WP-POS Reports destroyed');
        }
    };
    
    // Inicialización cuando el DOM esté listo
    $(document).ready(function() {
        // Verificar si estamos en la página de reportes
        if ($('.wp-pos-reports-dashboard').length > 0 || $('[id*="wp-pos-reports"]').length > 0) {
            try {
                // Bind all methods to maintain proper context
                var methods = ['init', 'registerGlobalMethods', 'setupDOM', 'setupEvents', 'setupFilters', 
                             'setupTableActions', 'toggleCustomDates', 'applyFilters', 'getFilterValues',
                             'validateFilters', 'handleAjaxSuccess', 'handleAjaxError', 'refreshData',
                             'viewSaleDetails', 'printTicket', 'showLoading', 'hideLoading',
                             'showNotification', 'setupAutoRefresh', 'log', 'loadRecentSales',
                             'renderRecentSales', 'formatDate', 'showEmptyState', 'destroy'];
                
                methods.forEach(function(method) {
                    if (typeof WPPosReports[method] === 'function') {
                        WPPosReports[method] = WPPosReports[method].bind(WPPosReports);
                    }
                });
                
                // Initialize
                WPPosReports.init();
                console.log('WP-POS Reports initialized successfully (DEFINITIVO)');
            } catch (error) {
                console.error('Error initializing WP-POS Reports:', error);
            }
        }
    });
    
    // Exponer globalmente para depuración y uso externo
    window.WPPosReports = WPPosReports;
    
})(jQuery);