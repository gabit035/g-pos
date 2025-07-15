/**
 * JavaScript para el módulo de Cierres
 */

// Definir el objeto global WP_POS_Closures si no existe
window.WP_POS_Closures = window.WP_POS_Closures || {};

// Extender el objeto global con los métodos necesarios
(function($) {
    'use strict';
    
    // Funciones auxiliares globales para formatear datos
    function formatCurrency(amount) {
        var numericAmount = parseFloat(amount);
        if (isNaN(numericAmount)) return '$0.00';
        return '$' + numericAmount.toFixed(2);
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        try {
            var date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            return ('0' + date.getDate()).slice(-2) + '/' + 
                   ('0' + (date.getMonth() + 1)).slice(-2) + '/' + 
                   date.getFullYear() + ' ' + 
                   ('0' + date.getHours()).slice(-2) + ':' + 
                   ('0' + date.getMinutes()).slice(-2);
        } catch(e) {
            console.error('Error al formatear fecha:', e);
            return dateString;
        }
    }

    function getStatusText(status) {
        var statusMap = {
            'pending': 'Pendiente',
            'approved': 'Aprobado',
            'rejected': 'Rechazado',
            'closed': 'Cerrado'
        };
        return statusMap[status] || status;
    }

    // Función global para renderizar la lista de cierres
    function renderClosuresList(closures) {
        console.log('Renderizando lista de cierres (global):', closures);
        if (!closures || closures.length === 0) {
            jQuery('#closures-list').html('<tr><td colspan="9" class="no-items">No se encontraron cierres de caja</td></tr>');
            return;
        }
        
        var html = '';
        jQuery.each(closures, function(index, closure) {
            var differenceClass = parseFloat(closure.difference) < 0 ? 'negative-amount' : 
                               (parseFloat(closure.difference) > 0 ? 'positive-amount' : '');
            
            var statusClass = 'status-' + closure.status;
            var statusText = getStatusText(closure.status);
            
            html += '<tr>';
            html += '<td>' + (closure.id || '-') + '</td>';
            html += '<td>' + formatDate(closure.created_at || '') + '</td>';
            html += '<td>' + (closure.user_name || '-') + '</td>';
            html += '<td>' + formatCurrency(closure.initial_amount) + '</td>';
            html += '<td>' + formatCurrency(closure.expected_amount) + '</td>';
            html += '<td>' + formatCurrency(closure.final_amount || closure.actual_amount) + '</td>';
            html += '<td class="' + differenceClass + '">' + formatCurrency(closure.difference) + '</td>';
            html += '<td><span class="closure-status ' + statusClass + '">' + statusText + '</span></td>';
            html += '<td class="actions-column">';
            html += '<button class="button button-small view-closure" data-id="' + closure.id + '">';
            html += '<span class="dashicons dashicons-visibility"></span></button>';
            html += '</td>';
            html += '</tr>';
        });
        
        jQuery('#closures-list').html(html);
    }

    // Extender el objeto principal del módulo
    $.extend(window.WP_POS_Closures, {
        /**
         * Inicializar el módulo
         */
        init: function() {
            this.setupTabs();
            this.setupRegisters();
            this.setupClosureForm();
            this.setupHistory();
            this.setupReports();
        },
        
        /**
         * Configurar navegación por pestañas
         */
        setupTabs: function() {
            $('.wp-pos-tab-content').hide();
            $('.wp-pos-tab-content:first').show();
            
            $('.wp-pos-tab').on('click', function() {
                var tab_id = $(this).attr('data-tab');
                
                $('.wp-pos-tab').removeClass('active');
                $('.wp-pos-tab-content').hide();
                
                $(this).addClass('active');
                $('#' + tab_id).show();
                
                return false;
            });
        },
        
        /**
         * Configurar funcionalidad de cajas registradoras
         */
        setupRegisters: function() {
            var self = this;
            
            // Mostrar formulario para agregar caja
            $('#add-register-button').on('click', function() {
                self.showRegisterForm();
            });
            
            // Mostrar formulario para editar caja
            $(document).on('click', '.edit-register', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var location = $(this).data('location');
                self.showRegisterForm(id, name, location);
            });
            
            // Eliminar caja
            $(document).on('click', '.delete-register', function() {
                if (confirm('¿Estás seguro de eliminar esta caja registradora?')) {
                    self.deleteRegister($(this).data('id'));
                }
            });
            
            // Cargar cajas registradoras iniciales
            this.loadRegisters();
        },
        
        /**
         * Cargar cajas registradoras
         */
        loadRegisters: function() {
            var self = this;
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_pos_closures_get_registers',
                    nonce: wp_pos_closures.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var registers = response.data.registers;
                        var html = '';
                        
                        if (registers.length === 0) {
                            html = '<tr><td colspan="3">No hay cajas registradoras.</td></tr>';
                        } else {
                            $.each(registers, function(index, register) {
                                html += '<tr>' +
                                    '<td>' + register.name + '</td>' +
                                    '<td>' + register.location + '</td>' +
                                    '<td>' +
                                    '<a href="#" class="edit-register" data-id="' + register.id + '" data-name="' + register.name + '" data-location="' + register.location + '">Editar</a> | ' +
                                    '<a href="#" class="delete-register" data-id="' + register.id + '">Eliminar</a>' +
                                    '</td>' +
                                    '</tr>';
                            });
                        }
                        
                        $('#registers-table tbody').html(html);
                        self.populateRegisterSelects(registers);
                    }
                },
                error: function() {
                    alert('Error al cargar cajas registradoras.');
                }
            });
        },
        
        /**
         * Mostrar formulario de caja registradora
         */
        showRegisterForm: function(id, name, location) {
            var self = this;
            var title = id ? 'Editar caja registradora' : 'Agregar caja registradora';
            var html = '<div class="wp-pos-dialog-form">' +
                '<form id="register-form">' +
                '<div class="wp-pos-form-field">' +
                '<label for="register-name">Nombre:</label>' +
                '<input type="text" id="register-name" name="register-name" value="' + (name || '') + '" required>' +
                '</div>' +
                '<div class="wp-pos-form-field">' +
                '<label for="register-location">Ubicación:</label>' +
                '<input type="text" id="register-location" name="register-location" value="' + (location || '') + '">' +
                '</div>' +
                '</form>' +
                '</div>';
            
            var dialog = $(html).dialog({
                autoOpen: true,
                modal: true,
                title: title,
                width: 400,
                buttons: {
                    "Guardar": function() {
                        var name = $('#register-name').val();
                        var location = $('#register-location').val();
                        
                        if (id) {
                            self.updateRegister(id, name, location, dialog);
                        } else {
                            self.addRegister(name, location, dialog);
                        }
                    },
                    "Cancelar": function() {
                        $(this).dialog("close");
                    }
                },
                close: function() {
                    $(this).dialog('destroy').remove();
                }
            });
        },
        
        /**
         * Agregar caja registradora
         */
        addRegister: function(name, location, dialog) {
            var self = this;
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_pos_closures_add_register',
                    nonce: wp_pos_closures.nonce,
                    name: name,
                    location: location
                },
                success: function(response) {
                    if (response.success) {
                        dialog.dialog("close");
                        self.loadRegisters();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Error al agregar caja registradora.');
                }
            });
        },
        
        /**
         * Actualizar caja registradora
         */
        updateRegister: function(id, name, location, dialog) {
            var self = this;
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_pos_closures_update_register',
                    nonce: wp_pos_closures.nonce,
                    id: id,
                    name: name,
                    location: location
                },
                success: function(response) {
                    if (response.success) {
                        dialog.dialog("close");
                        self.loadRegisters();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Error al actualizar caja registradora.');
                }
            });
        },
        
        /**
         * Eliminar caja registradora
         */
        deleteRegister: function(id) {
            var self = this;
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_pos_closures_delete_register',
                    nonce: wp_pos_closures.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        self.loadRegisters();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Error al eliminar caja registradora.');
                }
            });
        },
        
        /**
         * Poblar selectores de cajas registradoras
         */
        populateRegisterSelects: function(registers) {
            var options = '<option value="">Seleccionar caja</option>';
            
            $.each(registers, function(index, register) {
                options += '<option value="' + register.id + '">' + register.name + '</option>';
            });
            
            $('#register').html(options);
            $('#history-filter-register').html('<option value="">Todas las cajas</option>' + options);
            $('#report-register').html('<option value="">Todas las cajas</option>' + options);
        },
        
        /**
         * Configurar los eventos para el formulario de cierre
         */
        setupClosureForm: function() {
            var self = this;
            
            // Formato de fecha
            $('.datepicker').datepicker({
                dateFormat: 'yy-mm-dd'
            });
            
            // Calcular los montos
            $('#calculate-amounts').on('click', function(e) {
                e.preventDefault();
                self.calculateAmounts();
            });
            
            // Refrescar desglose de pagos al cambiar fecha o usuario
            $('#closure-date, #closure-user').on('change', function() {
                console.log('Fecha o usuario cambiado, actualizando desglose');
                self.loadPaymentBreakdown();
            });
            
            // Botón de refrescar desglose de pagos
            $('#refresh-breakdown').on('click', function(e) {
                e.preventDefault();
                self.loadPaymentBreakdown();
            });
            
            // Cargar desglose inicial si está en la página de formulario
            if ($('#closure-form').length > 0) {
                self.loadPaymentBreakdown();
            }
        },
        
        /**
         * Cargar el desglose de pagos basado en la fecha y usuario actual
         */
        loadPaymentBreakdown: function() {
            var self = this;
            console.log('loadPaymentBreakdown llamado');
            
            var date = $('#closure-date').val();
            var register_id = $('#closure-register').val();
            var user_id = $('#closure-user').val();
            
            if (!date) {
                console.log('Fecha no seleccionada, cancelando carga de desglose');
                return;
            }
            
            console.log('Cargando desglose para fecha:', date, 'register_id:', register_id, 'user_id:', user_id);
            
            $('#payment-method-breakdown').html('<p class="loading">Cargando desglose...</p>');
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_pos_closures_get_breakdown',
                    nonce: wp_pos_closures.nonce,
                    date: date,
                    register_id: register_id,
                    user_id: user_id
                },
                success: function(response) {
                    console.log('Respuesta AJAX recibida:', response);
                    if (response.success) {
                        self.renderPaymentBreakdown(response.data.breakdown);
                    } else {
                        console.error('Error al cargar desglose:', response.data.message);
                        $('#payment-method-breakdown').html('<p class="error">' + response.data.message + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    $('#payment-method-breakdown').html('<p class="error">Error de conexión al cargar desglose</p>');
                }
            });
        },
        
        /**
         * Renderiza el desglose de pagos en la tabla con una mejor visualización
         * @param {Object} breakdown - Objeto con los montos por método de pago
         * @param {jQuery|null} $container - Contenedor opcional donde insertar el desglose (jQuery object)
         */
        renderPaymentBreakdown: function(breakdown, $container) {
            console.log('Renderizando desglose de pagos:', breakdown);
            var $ = jQuery;
            
            // Si no se proporciona un contenedor específico, usar el predeterminado
            $container = $container || $('#view-payment-breakdown');
            
            // Asegurar que breakdown sea un objeto
            if (!breakdown || typeof breakdown !== 'object') {
                console.warn('El desglose de pagos no es un objeto válido:', breakdown);
                breakdown = {
                    cash: 0,
                    card: 0,
                    transfer: 0,
                    check: 0,
                    other: 0
                };
            }
            
            // Estructura estándar de métodos de pago basados en la memoria
            var standardMethods = ['cash', 'card', 'transfer', 'check', 'other'];
            
            // Asegurar que todos los métodos estén presentes
            standardMethods.forEach(function(method) {
                if (typeof breakdown[method] === 'undefined') {
                    breakdown[method] = 0;
                    console.log('Añadiendo método faltante:', method);
                }
            });
            
            // Estilos CSS para la tabla
            var html = '<table style="width:100%; border-collapse:collapse; margin:10px 0; border-radius:5px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.1);" class="payment-breakdown-table">'
                    + '<thead>'
                    + '<tr style="background-color:#f4f6f9;">'
                    + '<th style="padding:12px 15px; text-align:left; border-bottom:2px solid #eee;">Método de Pago</th>'
                    + '<th style="padding:12px 15px; text-align:right; border-bottom:2px solid #eee;">Monto</th>'
                    + '</tr>'
                    + '</thead>'
                    + '<tbody>';
            var total = 0;
            
            // Definiciones de métodos de pago consistentes con el módulo de ventas
            var methodLabels = {
                cash: 'Efectivo',
                card: 'Tarjeta',
                transfer: 'Transferencia',
                check: 'Cheque',
                other: 'Otro'
            };
            
            // Iconos mejorados para cada método de pago
            var methodIcons = {
                cash: '<span style="display:inline-block; width:24px; text-align:center; margin-right:5px;">💵</span>',
                card: '<span style="display:inline-block; width:24px; text-align:center; margin-right:5px;">💳</span>',
                transfer: '<span style="display:inline-block; width:24px; text-align:center; margin-right:5px;">🏦</span>',
                check: '<span style="display:inline-block; width:24px; text-align:center; margin-right:5px;">📝</span>',
                other: '<span style="display:inline-block; width:24px; text-align:center; margin-right:5px;">📎</span>'
            };
            
            // Añadir filas con datos para métodos estándar (en orden definido)
            standardMethods.forEach(function(method) {
                if (!breakdown.hasOwnProperty(method)) return;
                
                var label = methodLabels[method] || method;
                var icon = methodIcons[method] || '';
                var amount = parseFloat(breakdown[method]) || 0;
                var amountFormatted = !isNaN(amount) ? amount.toFixed(2) : '0.00';
                
                // Estilo condicional según el valor
                var rowStyle = '';
                if (amount > 0) {
                    rowStyle = 'background-color:#f9fffa;';
                } else {
                    rowStyle = 'color:#999;';
                }
                
                html += '<tr style="border-bottom:1px solid #eee; ' + rowStyle + '">'
                      + '<td style="padding:10px 15px; text-align:left">' + icon + label + '</td>'
                      + '<td style="padding:10px 15px; text-align:right; font-family:monospace; font-size:1.1em;">$' + amountFormatted + '</td>'
                      + '</tr>';
                      
                console.log('Agregando fila:', label, amountFormatted);
                total += amount;
            });
            
            // Añadir cualquier método adicional que pueda existir pero no esté en la lista estándar
            for (var method in breakdown) {
                if (!breakdown.hasOwnProperty(method) || standardMethods.includes(method)) continue;
                
                var label = methodLabels[method] || method;
                var amount = parseFloat(breakdown[method]) || 0;
                var amountFormatted = !isNaN(amount) ? amount.toFixed(2) : '0.00';
                
                html += '<tr style="border-bottom:1px solid #eee;">'
                      + '<td style="padding:10px 15px; text-align:left">' + label + '</td>'
                      + '<td style="padding:10px 15px; text-align:right; font-family:monospace; font-size:1.1em;">$' + amountFormatted + '</td>'
                      + '</tr>';
                      
                total += amount;
            }
            
            // Formatear total
            var totalFormatted = !isNaN(total) ? total.toFixed(2) : '0.00';
            
            // Añadir fila de total con estilo destacado
            html += '<tr style="border-top:2px solid #ddd; font-weight:bold; background-color:#e6f0fa">'
                  + '<td style="padding:12px 15px; text-align:left">TOTAL</td>'
                  + '<td style="padding:12px 15px; text-align:right; font-family:monospace; font-size:1.2em;">$' + totalFormatted + '</td>'
                  + '</tr>';
            
            html += '</tbody></table>';
            
            // Añadir metadatos ocultos para facilitar la captura en el histórico
            html += '<input type="hidden" id="payment_breakdown_total" name="payment_breakdown_total" value="' + total + '">';
            
            // Guardar el JSON del desglose en los campos ocultos para el envío del formulario
            var breakdownJson = JSON.stringify(breakdown);
            $('#payment_breakdown_stored').val(breakdownJson);
            $('#payment_breakdown').val(breakdownJson);
            console.log('JSON del desglose guardado en campos ocultos:', breakdownJson);
            html += '<input type="hidden" id="payment_breakdown_json" name="payment_breakdown_json" value=\'' + JSON.stringify(breakdown) + '\'>';
            
            console.log('HTML generado con total:', html);
            
            // Usar el contenedor proporcionado para insertar el HTML
            if ($container && $container.length) {
                $container.html(html);
            } else {
                // Fallback a los selectores anteriores por compatibilidad
                $('#payment-method-breakdown, #view-payment-breakdown').html(html);
            }
            
            // Disparar evento para notificar que el desglose ha sido actualizado
            $(document).trigger('wp-pos-payment-breakdown-updated', [breakdown, total]);
        },
        
        /**
         * Calcular montos del cierre
         */
        calculateAmounts: function() {
            var self = this;
            var register_id = $('#closure-register').val();
            var date = $('#closure-date').val();
            var user_id = $('#closure-user').val();
            
            if (!register_id || !date) {
                alert('Debes seleccionar una caja registradora y una fecha.');
                return;
            }
            
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_pos_closures_calculate_amounts',
                    nonce: wp_pos_closures.nonce,
                    register_id: register_id,
                    date: date,
                    user_id: user_id
                },
                success: function(response) {
                    if (response.success) {
                        $('#initial_amount').val(response.data.initial_amount);
                        $('#cash_sales').val(response.data.cash);
                        $('#card_sales').val(response.data.card);
                        $('#transfer_sales').val(response.data.transfer);
                        $('#check_sales').val(response.data.check);
                        $('#other_sales').val(response.data.other);
                        $('#total_sales').val(response.data.total_sales);
                        $('#final_amount').val(response.data.final_amount);
                        
                        // También cargar el desglose
                        self.loadPaymentBreakdown();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Error al calcular montos.');
                }
            });
        },
        
        /**
         * Obtener detalles de un cierre específico
         */
        getClosureDetails: function(closure_id, callback) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_pos_closures_get_closure_details',
                    nonce: wp_pos_closures.nonce,
                    closure_id: closure_id
                },
                success: function(response) {
                    console.log('Respuesta de detalles de cierre:', response);
                    if (response.success) {
                        var closure = response.data.closure;
                        
                        // Obtener payment_breakdown desde la respuesta con manejo mejorado
                        var payment_breakdown = null;
                        
                        try {
                            // 1. Primero intentar usar payment_breakdown_decoded (si fue decodificado por el backend)
                            if (closure.payment_breakdown_decoded) {
                                console.log('Usando payment_breakdown_decoded predecodificado');
                                payment_breakdown = closure.payment_breakdown_decoded;
                            } 
                            // 2. Si no, intentar decodificar payment_breakdown si es string
                            else if (closure.payment_breakdown) {
                                if (typeof closure.payment_breakdown === 'string') {
                                    console.log('Decodificando payment_breakdown desde string');
                                    payment_breakdown = JSON.parse(closure.payment_breakdown);
                                } else {
                                    console.log('Usando payment_breakdown como objeto');
                                    payment_breakdown = closure.payment_breakdown;
                                }
                            }
                            
                            // Si no hay desglose de pagos, crear uno vacío con todos los métodos
                            if (!payment_breakdown) {
                                console.log('No se encontró desglose de pagos, creando estructura vacía');
                                payment_breakdown = {
                                    cash: 0,
                                    card: 0,
                                    transfer: 0,
                                    check: 0,
                                    other: 0
                                };
                            }
                            
                            // Asegurar que todos los métodos estén presentes
                            var methods = ['cash', 'card', 'transfer', 'check', 'other'];
                            methods.forEach(function(method) {
                                if (typeof payment_breakdown[method] === 'undefined') {
                                    payment_breakdown[method] = 0;
                                }
                            });
                            
                            console.log('Desglose final:', payment_breakdown);
                        } catch(e) {
                            console.error('Error al procesar desglose de pagos:', e);
                            payment_breakdown = {
                                cash: 0,
                                card: 0,
                                transfer: 0,
                                check: 0,
                                other: 0
                            };
                        }
                        
                        // Crear un ID único para el modal
                        var modalId = 'closure-details-' + closure.id;
                        
                        // Generar HTML para el diálogo
                        var html = '<div id="' + modalId + '" class="wp-pos-closure-details">';
                        html += '<div class="wp-pos-closure-info">';
                        html += '<div class="closure-header">';
                        html += '<h3>Cierre #' + closure.id + '</h3>';
                        html += '<span class="closure-date">' + formatDate(closure.created_at) + '</span>';
                        html += '</div>';
                        
                        html += '<div class="closure-metadata">';
                        html += '<div><strong>Caja:</strong> ' + (closure.register_name || 'No disponible') + '</div>';
                        html += '<div><strong>Usuario:</strong> ' + (closure.user_name || 'No disponible') + '</div>';
                        html += '<div><strong>Estado:</strong> <span class="status-' + closure.status + '">' + getStatusText(closure.status) + '</span></div>';
                        html += '</div>';
                        
                        html += '<div class="closure-amounts">';
                        html += '<div><strong>Monto Inicial:</strong> ' + formatCurrency(closure.initial_amount) + '</div>';
                        html += '<div><strong>Ventas:</strong> ' + formatCurrency(closure.sales_amount) + '</div>';
                        html += '<div><strong>Monto Esperado:</strong> ' + formatCurrency(closure.expected_amount) + '</div>';
                        html += '<div><strong>Monto Final:</strong> ' + formatCurrency(closure.final_amount || closure.actual_amount) + '</div>';
                        
                        var differenceClass = parseFloat(closure.difference) < 0 ? 'negative-amount' : 
                                    (parseFloat(closure.difference) > 0 ? 'positive-amount' : '');
                        html += '<div><strong>Diferencia:</strong> <span class="' + differenceClass + '">' + formatCurrency(closure.difference) + '</span></div>';
                        html += '</div>';
                        
                        html += '<div class="payment-breakdown">';
                        html += '<h4>Desglose por método de pago</h4>';
                        html += '<div class="payment-breakdown-container"></div>';
                        html += '</div>';
                        
                        // Si hay observaciones, mostrarlas
                        if (closure.observations) {
                            html += '<div class="closure-observations">';
                            html += '<h4>Observaciones</h4>';
                            html += '<p>' + closure.observations + '</p>';
                            html += '</div>';
                        }
                        
                        html += '</div>';
                        html += '</div>';
                            
                        // Eliminar cualquier diálogo existente con el mismo ID para evitar duplicados
                        $('#' + modalId).remove();
                        
                        // Crear el diálogo
                        var $dialog = $(html).dialog({
                            autoOpen: true,
                            modal: true,
                            title: 'Cierre #' + closure.id,
                            width: 500,
                            buttons: {
                                "Cerrar": function() {
                                    $(this).dialog("close");
                                    // Destruir completamente el diálogo para evitar elementos huérfanos
                                    $(this).dialog("destroy").remove();
                                }
                            },
                            // Destruir el diálogo al cerrarlo para eliminar elementos huérfanos
                            close: function() {
                                $(this).dialog("destroy").remove();
                            }
                        });
                        
                        // Si hay desglose de pagos, renderizarlo
                        if (payment_breakdown) {
                            // Usar el contenedor específico de este diálogo
                            window.WP_POS_Closures.renderPaymentBreakdown(payment_breakdown, $dialog.find('.payment-breakdown-container'));
                        }
                        
                        // Si hay callback, ejecutarlo
                        if (typeof callback === 'function') {
                            callback(closure, $dialog);
                        }
                    } else {
                        alert(response.data.message || 'Error al cargar detalles del cierre');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error al cargar detalles del cierre.');
                }
            });
        },
        
        /**
         * Configurar funcionalidad de historial
         */
        setupHistory: function() {
            var self = this;
            
            // Filtrar historial
            $('#filter-history').on('click', function(e) {
                e.preventDefault();
                self.loadHistory();
            });
            
            // Cargar historial inicial
            this.loadHistory();
        },
        
        /**
         * Cargar historial de cierres
         */
        loadHistory: function() {
            var self = this;
            var register_id = $('#history-register').val();
            var status = $('#history-status').val();
            var date_from = $('#history-date-from').val();
            var date_to = $('#history-date-to').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_pos_closures_get_history',
                    nonce: wp_pos_closures.nonce,
                    register_id: register_id,
                    status: status,
                    date_from: date_from,
                    date_to: date_to
                },
                success: function(response) {
                    console.log('Respuesta de historial:', response);
                    var html = '';
                    
                    if (!response.success || !response.data.closures || response.data.closures.length === 0) {
                        html = '<tr><td colspan="8">No se encontraron cierres</td></tr>';
                    } else {
                        var closures = response.data.closures;
                        $.each(closures, function(index, closure) {
                            var status = '';
                            
                            if (closure.status === 'pending') {
                                status = '<span class="status pending">Pendiente</span>';
                            } else if (closure.status === 'approved') {
                                status = '<span class="status approved">Aprobado</span>';
                            } else if (closure.status === 'rejected') {
                                status = '<span class="status rejected">Rechazado</span>';
                            }
                            
                            html += '<tr>' +
                                '<td>' + closure.id + '</td>' +
                                '<td>' + closure.date + '</td>' +
                                '<td>' + closure.register_name + '</td>' +
                                '<td>' + closure.user_name + '</td>' +
                                '<td>$' + parseFloat(closure.total_sales).toFixed(2) + '</td>' +
                                '<td>' + status + '</td>' +
                                '<td>' + (closure.observations || '-') + '</td>' +
                                '<td><a href="#" class="view-closure" data-id="' + closure.id + '">Ver</a></td>' +
                                '</tr>';
                        });
                    }
                    
                    $('#closures-table tbody').html(html);
                },
                error: function() {
                    alert('Error al cargar historial de cierres.');
                }
            });
        },
        
        /**
         * Configurar funcionalidad para ver detalles de cierre
         */
        setupClosureView: function() {
            // Manejar click en botones "Ver" para mostrar detalles de cierre
            $(document).on('click', '.view-closure', function(e) {
                e.preventDefault();
                var closureId = $(this).data('id');
                if (closureId) {
                    // Usar la funcionalidad existente en closures-history.js
                    if (window.WP_POS_Closures_History && typeof window.WP_POS_Closures_History.viewClosure === 'function') {
                        window.WP_POS_Closures_History.viewClosure(closureId);
                    } else {
                        // Si no está disponible la función en history, usar la local
                        window.WP_POS_Closures.getClosureDetails(closureId);
                    }
                }
                return false;
            });
        },
        
        /**
         * Configurar funcionalidad de reportes
         */
        setupReports: function() {
            var self = this;
            
            // Cambiar tipo de reporte
            $('.wp-pos-report-types li').on('click', function() {
                $('.wp-pos-report-types li').removeClass('active');
                $(this).addClass('active');
            });
            
            // Generar reporte
            $('#generate-report').on('click', function() {
                var report_type = $('.wp-pos-report-types li.active').data('report');
                var register_id = $('#report-register').val();
                var date_from = $('#report-date-from').val();
                var date_to = $('#report-date-to').val();
                var format = $('#report-format').val();
                
                if (!report_type) {
                    alert('Debes seleccionar un tipo de reporte.');
                    return;
                }
                
                if (!date_from || !date_to) {
                    alert('Debes seleccionar un rango de fechas.');
                    return;
                }
                
                var url = wp_pos_closures.reports_url +
                    '?action=wp_pos_closures_generate_report' +
                    '&report_type=' + report_type +
                    '&register_id=' + (register_id || '') +
                    '&date_from=' + date_from +
                    '&date_to=' + date_to +
                    '&format=' + format;
                
                window.open(url, '_blank');
            });
        }
    });

    // Cuando el documento esté listo
    $(document).ready(function() {
        // Inicializar el módulo
        window.WP_POS_Closures.init();
        
        // Configurar el handler para ver detalles del cierre
        window.WP_POS_Closures.setupClosureView();
    });

    // Hook para cargar el desglose después de un cálculo o carga de cierre
    $(document).on('wp-pos-closure-breakdown-update', function(e, breakdown) {
        window.WP_POS_Closures.renderPaymentBreakdown(breakdown);
    });

})(jQuery);
