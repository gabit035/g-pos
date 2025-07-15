/**
 * Script específico para la vista de historial de cierres
 * 
 * Este archivo maneja la funcionalidad de la vista "Historial de Cierres"
 * incluyendo el filtrado, paginación y visualización de los cierres.
 */
(function($) {
    'use strict';

    // Variables globales
    var currentPage = 1;
    var totalPages = 1;
    var itemsPerPage = 20;

    // Formatear moneda
    function formatCurrency(amount) {
        var numericAmount = parseFloat(amount);
        if (isNaN(numericAmount)) return '$0.00';
        return '$' + numericAmount.toFixed(2);
    }

    // Formatear fecha
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

    // Obtener el texto del estado según su código
    function getStatusText(status) {
        var statusMap = {
            'pending': 'Pendiente',
            'approved': 'Aprobado',
            'rejected': 'Rechazado',
            'closed': 'Cerrado'
        };
        
        return statusMap[status] || status;
    }

    // Renderizar la lista de cierres
    function renderClosuresList(closures) {
        console.log('Renderizando lista de cierres (historial):', closures);
        
        if (!closures || closures.length === 0) {
            $('#closures-list').html('<tr><td colspan="8" class="no-items"><div class="empty-state"><span class="dashicons dashicons-info"></span> No se encontraron cierres de caja</div></td></tr>');
            return;
        }
        
        var html = '';
        $.each(closures, function(index, closure) {
            var differenceClass = parseFloat(closure.difference) < 0 ? 'negative-amount' : 
                                (parseFloat(closure.difference) > 0 ? 'positive-amount' : '');
            
            // Definir iconos y clases según el estado
            var statusClass = 'status-' + closure.status;
            var statusText = getStatusText(closure.status);
            var statusIcon = '';
            
            // Añadir iconos apropiados según el estado
            switch (closure.status) {
                case 'approved':
                    statusIcon = '<span class="dashicons dashicons-yes-alt"></span> ';
                    break;
                case 'rejected':
                    statusIcon = '<span class="dashicons dashicons-dismiss"></span> ';
                    break;
                case 'pending':
                    statusIcon = '<span class="dashicons dashicons-clock"></span> ';
                    break;
                default:
                    statusIcon = '';
            }
            
            // Construir la fila con mejores elementos visuales
            html += '<tr>';
            html += '<td><strong>#' + (closure.id || '-') + '</strong></td>';
            html += '<td><div class="date-cell">' + formatDate(closure.created_at || '') + '</div></td>';
            html += '<td><div class="user-cell"><span class="dashicons dashicons-admin-users"></span> ' + (closure.user_name || '-') + '</div></td>';
            html += '<td>' + formatCurrency(closure.initial_amount) + '</td>';
            html += '<td>' + formatCurrency(closure.expected_amount) + '</td>';
            html += '<td>' + formatCurrency(closure.final_amount || closure.actual_amount) + '</td>';
            html += '<td class="' + differenceClass + '">' + formatCurrency(closure.difference) + '</td>';
            html += '<td><span class="closure-status ' + statusClass + '">' + statusIcon + statusText + '</span></td>';
            html += '<td class="actions-column">';
            html += '<button class="button button-small view-closure" title="Ver detalles" data-id="' + closure.id + '"><span class="dashicons dashicons-visibility"></span></button>';
            html += '</td>';
            html += '</tr>';
        });
        
        $('#closures-list').html(html);
        
        // Agregar efecto de hover suave a las filas
        $('#closures-list tr').hover(
            function() { $(this).addClass('hover-highlight'); },
            function() { $(this).removeClass('hover-highlight'); }
        );
    }

    // Actualizar la paginación
    function updatePagination(totalItems, pages) {
        totalPages = pages;
        $('#items-count').text(totalItems + ' elementos');
        $('#total-pages').text(totalPages);
        $('#current-page').val(currentPage);
        
        // Habilitar/deshabilitar botones de paginación
        if (currentPage <= 1) {
            $('#prev-page, #first-page').addClass('disabled');
        } else {
            $('#prev-page, #first-page').removeClass('disabled');
        }
        
        if (currentPage >= totalPages) {
            $('#next-page, #last-page').addClass('disabled');
        } else {
            $('#next-page, #last-page').removeClass('disabled');
        }
    }

    // Cargar el listado de cierres
    function loadClosuresList(page) {
        var register_id = $('#history-filter-register').val();
        var date_from = $('#history-filter-from').val();
        var date_to = $('#history-filter-to').val();
        var status = $('#history-filter-status').val();
        
        // Mostrar indicador de carga
        $('#closures-table-container').addClass('loading');
        $('#closures-list').html('<tr><td colspan="8" class="loading-indicator">Cargando...</td></tr>');
        
        // Realizar llamada AJAX
        $.ajax({
            url: wp_pos_closures.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wp_pos_closures_get_closures',
                nonce: wp_pos_closures.nonce,
                register_id: register_id,
                date_from: date_from,
                date_to: date_to,
                status: status,
                page: page || currentPage,
                per_page: itemsPerPage
            },
            success: function(response) {
                console.log('Respuesta de cierres recibida:', response);
                
                // Quitar indicador de carga
                $('#closures-table-container').removeClass('loading');
                
                if (response.success) {
                    // Actualizar la página actual
                    currentPage = response.data.page || 1;
                    
                    // Renderizar la lista de cierres
                    renderClosuresList(response.data.closures);
                    
                    // Actualizar información de paginación
                    updatePagination(response.data.total_items, response.data.total_pages);
                } else {
                    $('#closures-list').html('<tr><td colspan="8" class="error">Error al cargar cierres: ' + 
                        (response.data && response.data.message ? response.data.message : 'Error desconocido') + 
                        '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', error, xhr);
                $('#closures-table-container').removeClass('loading');
                $('#closures-list').html('<tr><td colspan="8" class="error">Error al cargar cierres. Por favor, intente nuevamente.</td></tr>');
            }
        });
    }

    // Inicializar la vista de historial
    function initHistoryView() {
        // Cargar listado inicial
        loadClosuresList(1);
        
        // Configurar eventos de paginación
        $('#first-page').on('click', function(e) {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage = 1;
                loadClosuresList();
            }
        });
        
        $('#prev-page').on('click', function(e) {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                loadClosuresList();
            }
        });
        
        $('#next-page').on('click', function(e) {
            e.preventDefault();
            if (currentPage < totalPages) {
                currentPage++;
                loadClosuresList();
            }
        });
        
        $('#last-page').on('click', function(e) {
            e.preventDefault();
            if (currentPage < totalPages) {
                currentPage = totalPages;
                loadClosuresList();
            }
        });
        
        $('#goto-page').on('click', function(e) {
            e.preventDefault();
            var page = parseInt($('#current-page').val());
            if (page >= 1 && page <= totalPages && page !== currentPage) {
                currentPage = page;
                loadClosuresList();
            }
        });
        
        // Configurar evento de filtrado
        $('#filter-history').on('click', function(e) {
            e.preventDefault();
            currentPage = 1;
            loadClosuresList();
        });
        
        // Configurar evento para resetear filtros
        $('#reset-filters').on('click', function(e) {
            e.preventDefault();
            $('#history-filter-register').val('');
            $('#history-filter-from').val('');
            $('#history-filter-to').val('');
            $('#history-filter-status').val('');
            currentPage = 1;
            loadClosuresList();
        });
        
        // Variable global para rastrear si hay un modal abierto
        window.closureModalOpen = false;
        
        // Delegación de eventos para el botón de ver detalles - EXCLUSIVO para historial
        $(document).off('click', '.view-closure').on('click', '.view-closure', function(e) {
            e.preventDefault();
            // IMPORTANTE: Detener cualquier otro handler de click de ejecutarse
            e.stopImmediatePropagation();
            
            // Si ya hay un modal abierto, evitar abrir otro
            if (window.closureModalOpen) {
                console.log('Ya hay un modal abierto, ignorando este clic');
                return;
            }
            
            // Marcar que se está abriendo un modal
            window.closureModalOpen = true;
            
            // Cerrar TODOS los modales jQuery UI y eliminar residuos
            $('.ui-dialog-content').each(function() {
                try {
                    // Verificar si es un diálogo jQuery UI inicializado
                    if ($(this).hasClass('ui-dialog-content') && $(this).dialog('instance')) {
                        $(this).dialog('destroy');
                    }
                    $(this).remove();
                } catch(e) {
                    console.log('Error al intentar destruir diálogo:', e);
                    // Si hay error, solo eliminarlo del DOM
                    $(this).remove();
                }
            });
            
            // Eliminar los contenedores de detalles
            $('.wp-pos-closure-details').remove();
            
            // Manejar con cuidado cualquier diálogo con ID específico
            $('div[id^="closure-details-"]').each(function() {
                try {
                    // Verificar si es un diálogo jQuery UI inicializado
                    if ($(this).hasClass('ui-dialog-content') && $(this).dialog('instance')) {
                        $(this).dialog('destroy');
                    }
                    $(this).remove();
                } catch(e) {
                    console.log('Error al intentar destruir diálogo con ID específico:', e);
                    // Si hay error, solo eliminarlo del DOM
                    $(this).remove();
                }
            });

            var closure_id = $(this).data('id');
            console.log('Ver detalles del cierre ID:', closure_id);

            // Siempre usar la lógica moderna de modal estilizado
            $.ajax({
                url: wp_pos_closures.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wp_pos_closures_get_closure_details',
                    nonce: wp_pos_closures.nonce,
                    closure_id: closure_id
                },
                success: function(response) {
                    // Restablecer la bandera de acción en proceso
                    window.actionInProgress = false;
                    if (response.success) {
                        var closure = response.data.closure;

                        // Obtener payment_breakdown desde la respuesta con manejo mejorado y completo
                        var payment_breakdown = { cash: 0, card: 0, transfer: 0, check: 0, other: 0 };
                        try {
                            var sourceData = null;
                            
                            // Intentar múltiples fuentes de datos de forma priorizada
                            if (response.data.payment_breakdown) {
                                sourceData = response.data.payment_breakdown;
                                console.log('Usando payment_breakdown de response.data');
                            } else if (closure.payment_breakdown_decoded) {
                                sourceData = closure.payment_breakdown_decoded;
                                console.log('Usando payment_breakdown_decoded');
                            } else if (closure.payment_breakdown) {
                                if (typeof closure.payment_breakdown === 'string') {
                                    sourceData = JSON.parse(closure.payment_breakdown);
                                    console.log('Usando payment_breakdown string parseado');
                                } else {
                                    sourceData = closure.payment_breakdown;
                                    console.log('Usando payment_breakdown objeto');
                                }
                            }
                            
                            // Si encontramos datos, copiarlos a nuestro objeto base
                            if (sourceData) {
                                // Mapeamos cualquier variante de nombre de método de pago
                                var methodMappings = {
                                    // Efectivo
                                    'cash': 'cash', 'efectivo': 'cash', 'CASH': 'cash', 'EFECTIVO': 'cash', 'Cash': 'cash',
                                    // Tarjeta
                                    'card': 'card', 'tarjeta': 'card', 'CARD': 'card', 'TARJETA': 'card', 'credit_card': 'card',
                                    'credit': 'card', 'debit_card': 'card', 'tarjeta_credito': 'card', 'tarjeta_debito': 'card',
                                    // Transferencia
                                    'transfer': 'transfer', 'transferencia': 'transfer', 'TRANSFER': 'transfer', 'bank_transfer': 'transfer',
                                    'wire': 'transfer', 'TRANSFERENCIA': 'transfer',
                                    // Cheque
                                    'check': 'check', 'cheque': 'check', 'CHECK': 'check', 'CHEQUE': 'check',
                                    // Otro
                                    'other': 'other', 'otro': 'other', 'OTHER': 'other', 'OTRO': 'other', 'misc': 'other'
                                };
                                
                                // Procesar cada propiedad encontrada
                                for (var key in sourceData) {
                                    var standardKey = methodMappings[key.toLowerCase()] || key;
                                    var value = parseFloat(sourceData[key]) || 0;
                                    
                                    // Asignar al método de pago estándar correspondiente
                                    if (payment_breakdown.hasOwnProperty(standardKey)) {
                                        payment_breakdown[standardKey] += value;
                                    } else if (standardKey === 'card' || key.toLowerCase().includes('tarjeta')) {
                                        payment_breakdown.card += value;
                                    } else if (standardKey === 'transfer' || key.toLowerCase().includes('transfer')) {
                                        payment_breakdown.transfer += value;
                                    } else if (standardKey === 'check' || key.toLowerCase().includes('cheque')) {
                                        payment_breakdown.check += value;
                                    } else if (standardKey === 'cash' || key.toLowerCase().includes('efectivo')) {
                                        payment_breakdown.cash += value;
                                    } else {
                                        payment_breakdown.other += value;
                                    }
                                }
                            }
                            
                            console.log('Payment breakdown procesado:', payment_breakdown);
                        } catch (e) {
                            console.error('Error al procesar payment_breakdown:', e);
                            // Mantenemos los valores predeterminados en ceros
                        }

                        // Formatear las cifras para mostrar
                        var initialAmount = parseFloat(closure.initial_amount) || 0;
                        var finalAmount = parseFloat(closure.final_amount || closure.actual_amount) || 0;
                        var expectedAmount = parseFloat(closure.expected_amount) || 0;
                        var difference = finalAmount - expectedAmount;
                        var differenceClass = difference < 0 ? 'negative-amount' : (difference > 0 ? 'positive-amount' : '');
                        
                        // Generar tabla de métodos de pago directamente
                        var paymentMethodsTable = renderPaymentBreakdownHTML(payment_breakdown);
                        
                        // Determinar estado y clase CSS
                        var statusClass = '';
                        var statusText = '';
                        var statusIcon = '';
                        
                        switch(closure.status) {
                            case 'approved':
                            case 'aprobado':
                                statusClass = 'status-approved';
                                statusText = 'Aprobado';
                                statusIcon = '<span class="dashicons dashicons-yes-alt" style="margin-right:5px;"></span>';
                                break;
                            case 'rejected':
                            case 'rechazado':
                                statusClass = 'status-rejected';
                                statusText = 'Rechazado';
                                statusIcon = '<span class="dashicons dashicons-no-alt" style="margin-right:5px;"></span>';
                                break;
                            case 'pending':
                            case 'pendiente':
                            default:
                                statusClass = 'status-pending';
                                statusText = 'Pendiente';
                                statusIcon = '<span class="dashicons dashicons-clock" style="margin-right:5px;"></span>';
                                break;
                        }
                        
                        // Crear ID único para el modal
                        var modalId = 'modal-closure-' + closure.id;
                        
                        // Construir el HTML del modal con diseño mejorado
                        var html = '<div class="wp-pos-closure-details" id="' + modalId + '" style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">';
                        
                        // Encabezado con gradiente, iconos y badge de estado
                        html += '<div style="background: linear-gradient(135deg, #1976d2, #0d47a1); color: white; padding: 16px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center;">';
                        html += '<h3 style="margin: 0; font-size: 1.4em; display: flex; align-items: center; color: #FFF;"><span class="dashicons dashicons-portfolio" style="margin-right: 10px; font-size: 24px;"></span>Cierre #' + closure.id + '</h3>';
                        html += '<span class="' + statusClass + '" style="padding: 6px 12px; border-radius: 4px; font-weight: 600; font-size: 0.9em; display: flex; align-items: center;">' + statusIcon + statusText + '</span>';
                        html += '</div>';
                        
                        // Sección de información principal
                        html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">';
                        
                        // Columna izquierda: Información general
                        // Formatear la fecha si existe
                        var formattedDate = 'No disponible';
                        if (closure.date) {
                            try {
                                var dateObj = new Date(closure.date);
                                if (!isNaN(dateObj.getTime())) {
                                    formattedDate = dateObj.toLocaleDateString('es-AR', { 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                }
                            } catch(e) {
                                formattedDate = closure.date;
                            }
                        } else if (closure.created_at) {
                            try {
                                var dateObj = new Date(closure.created_at);
                                if (!isNaN(dateObj.getTime())) {
                                    formattedDate = dateObj.toLocaleDateString('es-AR', { 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                }
                            } catch(e) {
                                formattedDate = closure.created_at;
                            }
                        }
                        
                        // Determinar valores a mostrar con fallbacks
                        var registerName = closure.register_name || closure.register || closure.register_id || 'No disponible';
                        var userName = closure.user_name || closure.username || closure.user || closure.created_by_name || 'No disponible';
                        
                        html += '<div style="background: #f8f9fa; border-radius: 6px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
                        html += '<p style="margin: 10px 0; display: flex; justify-content: space-between;"><strong style="color: #444;">Fecha:</strong> <span style="color: #555;">' + formattedDate + '</span></p>';
                        html += '<p style="margin: 10px 0; display: flex; justify-content: space-between;"><strong style="color: #444;">Caja:</strong> <span style="color: #555;">' + registerName + '</span></p>';
                        html += '<p style="margin: 10px 0; display: flex; justify-content: space-between;"><strong style="color: #444;">Usuario:</strong> <span style="color: #555;">' + userName + '</span></p>';
                        html += '</div>';
                        
                        // Columna derecha: Montos
                        // Asegurar que tengamos todos los valores numéricos correctos
                        var initialAmount = parseFloat(closure.initial_amount || 0);
                        var finalAmount = parseFloat(closure.final_amount || closure.actual_amount || 0);
                        var totalSales = parseFloat(closure.total_sales || closure.expected_amount || 0);
                        
                        // Calcular diferencia si no está disponible
                        var difference = parseFloat(closure.difference || (finalAmount - (initialAmount + totalSales)));
                        var differenceColor = difference < 0 ? '#e53935' : (difference > 0 ? '#f57c00' : '#388e3c');
                        
                        // Función interna para formatear como moneda argentina
                        function formatArgCurrency(value) {
                            var numValue = parseFloat(value || 0);
                            return '$' + numValue.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                        }
                        
                        html += '<div style="background: #f8f9fa; border-radius: 6px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
                        html += '<p style="margin: 10px 0; display: flex; justify-content: space-between;"><strong style="color: #444;">Monto inicial:</strong> <span style="color: #555;">' + formatArgCurrency(initialAmount) + '</span></p>';
                        html += '<p style="margin: 10px 0; display: flex; justify-content: space-between;"><strong style="color: #444;">Monto final:</strong> <span style="color: #555;">' + formatArgCurrency(finalAmount) + '</span></p>';
                        html += '<p style="margin: 10px 0; display: flex; justify-content: space-between;"><strong style="color: #444;">Total ventas:</strong> <span style="color: #555;">' + formatArgCurrency(totalSales) + '</span></p>';
                        html += '<p style="margin: 10px 0; display: flex; justify-content: space-between; color: ' + differenceColor + ';"><strong>Diferencia:</strong> <span>' + formatArgCurrency(difference) + '</span></p>';
                        html += '</div>';
                        html += '</div>';
                        
                        // Observaciones - Diseño mejorado
                        html += '<div style="margin-bottom: 20px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; background-color: #f9f9f9; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
                        html += '<h4 style="margin-top: 0; margin-bottom: 10px; font-size: 16px; color: #4a5568; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 8px;">Observaciones</h4>';
                        html += '<p style="margin-bottom: 0; color: #555;">' + (closure.observations && closure.observations.length > 0 ? closure.observations : '<em>Sin observaciones</em>') + '</p>';
                        html += '</div>';
                        
                        // Desglose de pagos - Diseño mejorado
                        html += '<div style="margin-bottom: 0; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; background-color: #f9f9f9; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
                        html += '<h4 style="margin-top: 0; margin-bottom: 12px; font-size: 16px; color: #4a5568; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 8px;">Desglose de Métodos de Pago</h4>';
                        html += '<div id="view-payment-breakdown" style="background-color: #fff; border-radius: 5px; border: 1px solid #e9e9e9; overflow: hidden;">' + paymentMethodsTable + '</div>';
                        html += '</div>';
                        
                        // Contenedor para mensajes con diseño mejorado
                        html += '<div id="closure-action-message" style="margin-top: 20px; display: none; border-radius: 6px; padding: 12px 15px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: all 0.3s ease;" class="notice"></div>';
                        
                        html += '</div>';

                        // Preparar configuración de botones según estado actual
                        var dialogButtons = {};
                        
                        // Solo mostrar botones de acción si el estado es pendiente
                        if (closure.status === 'pending' || closure.status === 'pendiente' || !closure.status) {
                            // Usamos array de objetos en lugar de objeto con propiedades para que jQuery UI respete el orden
                            dialogButtons = [
                                {
                                    // En lugar de 'text' usamos el propio ID para identificar el botón
                                    id: "btn-approve-" + closure.id,
                                    text: "Aprobar", // Texto simple como respaldo
                                    class: "button button-primary wp-pos-btn-approve",
                                    click: function() {
                                        handleClosureAction('approve', closure.id, $(this));
                                    }
                                },
                                {
                                    id: "btn-reject-" + closure.id,
                                    text: "Rechazar",
                                    class: "button button-secondary wp-pos-btn-reject",
                                    click: function() {
                                        handleClosureAction('reject', closure.id, $(this));
                                    }
                                },
                                {
                                    id: "btn-delete-" + closure.id,
                                    text: "Eliminar",
                                    class: "button button-link-delete wp-pos-btn-delete",
                                    click: function() {
                                        handleClosureAction('delete', closure.id, $(this));
                                    }
                                },
                                {
                                    id: "btn-close-" + closure.id,
                                    text: "Cerrar",
                                    class: "button wp-pos-btn-close",
                                    click: function() {
                                        $(this).dialog("close");
                                    }
                                }
                            ];
                        } else {
                            // Si ya está aprobado o rechazado, solo permitir cierre
                            dialogButtons = [
                                {
                                    id: "btn-close-" + closure.id,
                                    text: "Cerrar",
                                    class: "button wp-pos-btn-close",
                                    click: function() {
                                        $(this).dialog("close");
                                    }
                                }
                            ];
                        }

                        // Mostrar diálogo
                        var $dialog = $(html).dialog({
                            autoOpen: true,
                            modal: true,
                            title: 'Cierre #' + closure.id,
                            width: 700, // Ampliamos el ancho para mejor visualización
                            buttons: dialogButtons,
                            create: function() {
                                // Estilar botones al crear el diálogo - usamos setTimeout para asegurar que los botones existan
                                setTimeout(function() {
                                    var $buttonPane = $('.ui-dialog:visible').find('.ui-dialog-buttonpane');
                                    
                                    // Agregar dashicons a los botones por sus IDs específicos
                                    $buttonPane.find('#btn-approve-' + closure.id).html('<span class="dashicons dashicons-yes-alt" style="margin-right:5px;"></span> Aprobar');
                                    $buttonPane.find('#btn-reject-' + closure.id).html('<span class="dashicons dashicons-no-alt" style="margin-right:5px;"></span> Rechazar');
                                    $buttonPane.find('#btn-delete-' + closure.id).html('<span class="dashicons dashicons-trash" style="margin-right:5px;"></span> Eliminar');
                                    $buttonPane.find('#btn-close-' + closure.id).html('<span class="dashicons dashicons-no-alt" style="margin-right:5px;"></span> Cerrar');
                                    
                                    // Estilos adicionales para los botones
                                    $buttonPane.find('button').css({
                                        'display': 'flex',
                                        'align-items': 'center',
                                        'justify-content': 'center',
                                        'padding': '6px 12px',
                                        'height': 'auto'
                                    });
                                    
                                    // Ajustes de color según tipo de botón
                                    $buttonPane.find('.wp-pos-btn-approve .dashicons').css('color', '#388e3c');
                                    $buttonPane.find('.wp-pos-btn-reject .dashicons').css('color', '#d32f2f');
                                    $buttonPane.find('.wp-pos-btn-delete .dashicons').css('color', '#f44336');
                                }, 10);
                            },
                            close: function() {
                                try {
                                    // Destruir el diálogo solo si está inicializado para evitar errores
                                    if ($(this).dialog("instance")) {
                                        $(this).dialog("destroy").remove();
                                    } else {
                                        $(this).remove();
                                    }
                                    
                                    // IMPORTANTE: Restablecer la bandera para permitir abrir nuevos modales
                                    window.closureModalOpen = false;
                                    console.log('Modal cerrado, se puede abrir uno nuevo');
                                } catch (e) {
                                    console.warn('Error al cerrar el diálogo:', e);
                                    // Intentar eliminar el elemento si falla destruir el diálogo
                                    $(this).remove();
                                    // Asegurar que la bandera se restablece incluso en caso de error
                                    window.closureModalOpen = false;
                                }
                            }
                        });
                        
                        // Aplicar estilos CSS inline a los elementos del diálogo
                        $dialog.closest('.ui-dialog').css({
                            'border-radius': '8px',
                            'box-shadow': '0 10px 25px rgba(0,0,0,0.15)',
                            'background': '#ffffff'
                        });
                        
                        $dialog.closest('.ui-dialog').find('.ui-dialog-titlebar').css({
                            'background': 'linear-gradient(135deg, #4a6baf, #6a4ca3)',
                            'color': 'white',
                            'border': 'none',
                            'border-radius': '8px 8px 0 0',
                            'padding': '12px 16px'
                        });
                        
                        $dialog.closest('.ui-dialog').find('.ui-dialog-buttonpane').css({
                            'background': '#f8fafc',
                            'border-top': '1px solid #eee',
                            'padding': '12px',
                            'margin-top': '0'
                        });
                        
                        // Función para manejar las acciones de cierre con experiencia de usuario mejorada
                        function handleClosureAction(action, closureId, dialogRef) {
                            // Configuración de acciones
                            var actionConfig = {
                                'approve': {
                                    text: 'aprobar',
                                    icon: 'dashicons-yes-alt',
                                    confirmColor: '#388e3c',
                                    successStatus: 'approved',
                                    successText: 'Aprobado',
                                    successClass: 'status-approved'
                                },
                                'reject': {
                                    text: 'rechazar',
                                    icon: 'dashicons-no-alt',
                                    confirmColor: '#e53935',
                                    successStatus: 'rejected',
                                    successText: 'Rechazado',
                                    successClass: 'status-rejected'
                                },
                                'delete': {
                                    text: 'eliminar',
                                    icon: 'dashicons-trash',
                                    confirmColor: '#e53935',
                                    successStatus: 'deleted',
                                    successText: 'Eliminado',
                                    successClass: ''
                                }
                            };
                            
                            // Prevenir múltiples clics en botones de acción
                            if (window.actionInProgress) {
                                console.log('Ya hay una acción en proceso, ignorando este clic');
                                return;
                            }
                            window.actionInProgress = true;
                            
                            // Crear un diálogo de confirmación moderno en lugar del confirm() nativo
                            var $confirmDialog = $('<div id="closure-confirm-dialog" title="Confirmar acción">' +
                                '<div style="padding: 20px; display: flex; align-items: center;">' +
                                '<span class="dashicons ' + actionConfig[action].icon + '" style="font-size: 28px; margin-right: 16px; color: ' + actionConfig[action].confirmColor + ';"></span>' +
                                '<p style="margin: 0; font-size: 15px;">¿Está seguro que desea <strong>' + actionConfig[action].text + '</strong> este cierre?</p>' +
                                '</div>' +
                                '</div>');

                            // Mostrar diálogo de confirmación estilizado
                            $confirmDialog.dialog({
                                resizable: false,
                                modal: true,
                                width: 400,
                                classes: {
                                    "ui-dialog": "wp-dialog wp-pos-confirm-dialog"
                                },
                                open: function() {
                                    // Aplicar estilos al diálogo de confirmación
                                    $(this).closest('.ui-dialog').css({
                                        'border-radius': '8px',
                                        'box-shadow': '0 8px 20px rgba(0,0,0,0.15)',
                                        'overflow': 'hidden',
                                        'background': '#ffffff'
                                    });
                                    
                                    $(this).closest('.ui-dialog').find('.ui-dialog-titlebar').css({
                                        'background': 'linear-gradient(135deg, #37474f, #263238)',
                                        'color': 'white',
                                        'border': 'none',
                                        'border-radius': '8px 8px 0 0',
                                        'padding': '12px 16px',
                                        'font-size': '16px',
                                        'font-weight': '500'
                                    });
                                    
                                    // Aplicar dashicons a los botones usando setTimeout
                                    setTimeout(function() {
                                        var $buttonPane = $('.wp-pos-confirm-dialog').closest('.ui-dialog').find('.ui-dialog-buttonpane');
                                        
                                        // Encontrar botones por texto
                                        var $cancelBtn = $buttonPane.find('button').filter(function() {
                                            return $(this).text().trim() === 'Cancelar';
                                        });
                                        
                                        var $confirmBtn = $buttonPane.find('button').filter(function() {
                                            return $(this).text().trim() === 'Confirmar';
                                        });
                                        
                                        // Agregar dashicons
                                        $cancelBtn.html('<span class="dashicons dashicons-no" style="margin-right:5px;"></span> Cancelar');
                                        $confirmBtn.html('<span class="dashicons ' + actionConfig[action].icon + '" style="margin-right:5px; color:' + actionConfig[action].confirmColor + ';"></span> Confirmar');
                                        
                                        // Estilos de botones
                                        $buttonPane.find('button').css({
                                            'display': 'flex',
                                            'align-items': 'center',
                                            'justify-content': 'center',
                                            'padding': '6px 12px',
                                            'height': 'auto'
                                        });
                                    }, 10);
                                },
                                buttons: [
                                    {
                                        text: "Cancelar",
                                        class: "button wp-pos-btn-cancel",
                                        click: function() {
                                            $(this).dialog("close").remove();
                                        }
                                    },
                                    {
                                        text: "Confirmar",
                                        class: "button button-primary wp-pos-btn-confirm",
                                        click: function() {
                                            // Cerrar diálogo de confirmación
                                            $(this).dialog("close").remove();
                                            
                                            // Proceder con la acción
                                            executeClosureAction(action, closureId, $dialogElement, actionConfig[action]);
                                        }
                                    }
                                ]
                            });
                        }
                        
                        // Función para ejecutar la acción una vez confirmada
                        function executeClosureAction(action, closureId, $dialogElement, actionConfig) {
                            // Contenedor de mensajes
                            var $message = $dialogElement.find('#closure-action-message');
                            
                            // Mostrar indicador de carga con animación y estilo mejorado
                            $message.removeClass('notice-success notice-error').addClass('notice-info').html(
                                '<div style="display: flex; align-items: center;">' + 
                                '<span class="dashicons dashicons-update-alt" style="animation: rotation 2s infinite linear; font-size: 20px; margin-right: 10px;"></span>' + 
                                '<div><strong>Procesando acción</strong><br><small>Por favor espere mientras se completa la solicitud...</small></div>' +
                                '</div>'
                            ).fadeIn(300);
                            
                            // Deshabilitar botones durante la acción
                            var $buttons = $dialogElement.closest('.ui-dialog').find('.ui-dialog-buttonpane button');
                            $buttons.prop('disabled', true).css('opacity', '0.6');
                            
                            // Determinar si se debe recargar automáticamente
                            var autoReloadEnabled = window.WP_POS_CONFIG?.autoReload !== false;
                            var reloadDelay = window.WP_POS_CONFIG?.reloadDelay || 1500;
                            
                            // Log para debugging
                            console.log('Enviando acción:', action, 'para cierre ID:', closureId);
                            console.log('AJAX URL:', wp_pos_closures.ajax_url);
                            console.log('Nonce:', wp_pos_closures.nonce);
                            
                            // Realizar llamada AJAX con parámetros corregidos para el endpoint correcto
                            $.ajax({
                                url: wp_pos_closures.ajax_url,
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    // El endpoint real es 'wp_pos_closures_update_status', no 'wp_pos_closures_[action]_closure'
                                    action: 'wp_pos_closures_update_status',
                                    nonce: wp_pos_closures.nonce,
                                    closure_id: closureId,
                                    // Agregar el parámetro de estado que espera el backend
                                    status: action === 'approve' ? 'approved' : (action === 'reject' ? 'rejected' : action),
                                    // Agregar justificación vacía (requerida para rechazos, pero no la pedimos en la UI)
                                    justification: ''
                                },
                                beforeSend: function(xhr) {
                                    // Agregar header de X-WP-Nonce para APIs REST
                                    xhr.setRequestHeader('X-WP-Nonce', wp_pos_closures.nonce);
                                },
                                success: function(response) {
                                    if (response.success) {
                                        // Mensaje de éxito mejorado
                                        $message.removeClass('notice-info').addClass('notice-success').html(
                                            '<div style="display: flex; align-items: center;">' +
                                            '<span class="dashicons dashicons-yes-alt" style="font-size: 20px; margin-right: 10px;"></span>' +
                                            '<div><strong>¡Acción completada!</strong><br><small>' + response.data.message + '</small></div>' +
                                            '</div>'
                                        );
                                        
                                        // Actualizar UI si corresponde
                                        if (autoReloadEnabled) {
                                            setTimeout(function() {
                                                if (action === 'delete') {
                                                    // Cerrar modal y recargar la lista con efecto de transición
                                                    $dialogElement.fadeOut(300, function() {
                                                        $dialogElement.dialog('close');
                                                        loadClosuresList(currentPage);
                                                    });
                                                } else {
                                                    // Actualizar estado en el modal con transición suave
                                                    var newStatusIcon = actionConfig.icon ? '<span class="dashicons ' + actionConfig.icon + '" style="margin-right:5px;"></span>' : '';
                                                    var $statusBadge = $dialogElement.find('.closure-status');
                                                    
                                                    $statusBadge.fadeOut(200, function() {
                                                        $statusBadge.attr('class', 'closure-status ' + actionConfig.successClass)
                                                            .html(newStatusIcon + actionConfig.successText)
                                                            .fadeIn(200);
                                                    });
                                                    
                                                    // Usar el mismo enfoque de botones con IDs
                                                    var closeButtonId = 'close-button-' + new Date().getTime();
                                                    
                                                    // Actualizar botones del diálogo usando array en lugar de objeto
                                                    $dialogElement.dialog('option', 'buttons', [
                                                        {
                                                            id: closeButtonId,
                                                            text: "Cerrar",
                                                            class: "button",
                                                            click: function() {
                                                                $(this).dialog("close");
                                                            }
                                                        }
                                                    ]);
                                                    
                                                    // Usar setTimeout para insertar el ícono después de que se cree el botón
                                                    setTimeout(function() {
                                                        var $closeBtn = $('#' + closeButtonId);
                                                        if ($closeBtn.length) {
                                                            $closeBtn.html('<span class="dashicons dashicons-no-alt" style="margin-right:5px;"></span> Cerrar');
                                                            $closeBtn.css({
                                                                'display': 'flex',
                                                                'align-items': 'center',
                                                                'justify-content': 'center',
                                                                'padding': '6px 12px'
                                                            });
                                                        }
                                                    }, 10);
                                                    
                                                    // Recargar la lista en segundo plano
                                                    loadClosuresList(currentPage);
                                                }
                                            }, reloadDelay);
                                        } else {
                                            // Si auto-reload está deshabilitado, mostrar botón manual
                                            $message.append('<div style="margin-top: 10px;">' +
                                                '<button class="button button-small reload-list"><span class="dashicons dashicons-update"></span> Actualizar lista</button>' +
                                                '</div>');
                                                
                                            // Manejar click en botón de actualizar
                                            $message.find('.reload-list').on('click', function() {
                                                loadClosuresList(currentPage);
                                                $(this).prop('disabled', true).text('Actualizando...');
                                            });
                                        }
                                    } else {
                                        // Mensaje de error mejorado
                                        $message.removeClass('notice-info').addClass('notice-error').html(
                                            '<div style="display: flex; align-items: center;">' +
                                            '<span class="dashicons dashicons-warning" style="font-size: 20px; margin-right: 10px;"></span>' +
                                            '<div><strong>Error</strong><br><small>' + (response.data.message || 'No se pudo completar la acción. Intente nuevamente.') + '</small></div>' +
                                            '</div>'
                                        );
                                        
                                        // Re-habilitar botones
                                        $buttons.prop('disabled', false).css('opacity', '1');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    // Obtener información detallada del error
                                    var errorMessage = 'No se pudo conectar con el servidor. Código: ' + xhr.status;
                                    var errorDetails = '';
                                    
                                    try {
                                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                                            errorMessage = xhr.responseJSON.data.message;
                                        } else if (xhr.responseText) {
                                            // Intentar parsear la respuesta como JSON
                                            var jsonResponse = JSON.parse(xhr.responseText);
                                            if (jsonResponse.message) errorMessage = jsonResponse.message;
                                            if (jsonResponse.data && jsonResponse.data.message) {
                                                errorMessage = jsonResponse.data.message;
                                            }
                                        }
                                        
                                        // Detalles específicos para errores comunes
                                        if (xhr.status === 400) {
                                            errorDetails = 'El servidor rechazó la solicitud. Posiblemente faltan parámetros o el formato es incorrecto.';
                                        } else if (xhr.status === 401 || xhr.status === 403) {
                                            errorDetails = 'No tiene permisos para realizar esta acción o su sesión ha expirado.';
                                        } else if (xhr.status === 404) {
                                            errorDetails = 'El recurso o punto de acceso no fue encontrado en el servidor.';
                                        } else if (xhr.status === 500) {
                                            errorDetails = 'Error interno del servidor. Por favor contacte al administrador.';
                                        }
                                    } catch(e) {
                                        console.warn('Error al parsear respuesta:', e);
                                    }
                                    
                                    // Mensaje de error técnico mejorado con detalles adicionales
                                    var errorHtml = '<div style="display: flex; align-items: flex-start;">' +
                                        '<span class="dashicons dashicons-dismiss" style="font-size: 20px; margin-right: 10px; color: #d32f2f;"></span>' +
                                        '<div><strong>Error de comunicación</strong><br><small>' + errorMessage + '</small>';
                                    
                                    if (errorDetails) {
                                        errorHtml += '<p style="margin: 5px 0 0; font-size: 12px; color: #777;">' + errorDetails + '</p>';
                                    }
                                    
                                    errorHtml += '<div style="margin-top: 8px;">' +
                                        '<button class="button button-small retry-action"><span class="dashicons dashicons-update" style="margin-right: 3px;"></span> Reintentar</button>' +
                                        '</div></div></div>';
                                    
                                    $message.removeClass('notice-info').addClass('notice-error').html(errorHtml);
                                    
                                    // Agregar handler para el botón de reintento
                                    $message.find('.retry-action').on('click', function() {
                                        // Reiniciar el proceso
                                        executeClosureAction(action, closureId, $dialogElement, actionConfig);
                                    });
                                    
                                    // Re-habilitar botones
                                    $buttons.prop('disabled', false).css('opacity', '1');
                                    
                                    // Log detallado del error para debugging
                                    console.error('Error al procesar acción de cierre:', {
                                        action: action,
                                        closureId: closureId,
                                        status: xhr.status,
                                        statusText: xhr.statusText,
                                        responseText: xhr.responseText,
                                        ajaxSettings: xhr.settings
                                    });
                                }
                            });
                        }

                        // SIEMPRE renderizar el desglose de pagos, incluso si está vacío
                        console.log('Intentando renderizar desglose de pagos:', payment_breakdown);
                        renderPaymentBreakdown(payment_breakdown);
                        
                        // Verificar que el contenedor existe y es visible
                        setTimeout(function() {
                            if ($('#view-payment-breakdown').length) {
                                console.log('Contenedor de desglose encontrado');
                                console.log('Contenido HTML:', $('#view-payment-breakdown').html());
                                
                                // Si está vacío, intentar renderizar de nuevo
                                if ($('#view-payment-breakdown').html().trim() === '') {
                                    console.log('Contenedor vacío, renderizando de nuevo');
                                    renderPaymentBreakdown(payment_breakdown);
                                }
                            } else {
                                console.error('Contenedor de desglose NO encontrado');
                            }
                        }, 500);
                    } else {
                        alert(response.data.message || 'Error al cargar los detalles del cierre');
                    }
                },
                error: function() {
                    alert('Error de comunicación al cargar los detalles del cierre');
                }
            });
        });
    }
    
    // Función para generar el HTML de la tabla de desglose de pagos
    function renderPaymentBreakdownHTML(breakdown) {
        console.log('Generando HTML para desglose de pagos:', breakdown);
        
        // Normalizar el objeto breakdown
        breakdown = normalizePaymentBreakdown(breakdown);
        
        // Definiciones de métodos de pago (consistentes con el módulo de Nueva Venta)
        var methodLabels = {
            cash: 'Efectivo',
            card: 'Tarjeta', // Reemplazó "Crédito" para mayor consistencia
            transfer: 'Transferencia',
            check: 'Cheque',
            other: 'Otro'
        };
        
        // Iconos mejorados para cada método de pago con dashicons
        var methodIcons = {
            cash: '<span class="dashicons dashicons-money-alt" style="color: #43A047; font-size: 20px; width: 24px; height: 24px; line-height: 24px; margin-right: 8px; vertical-align: text-bottom;"></span>',
            card: '<span class="dashicons dashicons-credit-card" style="color: #1E88E5; font-size: 20px; width: 24px; height: 24px; line-height: 24px; margin-right: 8px; vertical-align: text-bottom;"></span>',
            transfer: '<span class="dashicons dashicons-bank" style="color: #5E35B1; font-size: 20px; width: 24px; height: 24px; line-height: 24px; margin-right: 8px; vertical-align: text-bottom;"></span>',
            check: '<span class="dashicons dashicons-paperclip" style="color: #FB8C00; font-size: 20px; width: 24px; height: 24px; line-height: 24px; margin-right: 8px; vertical-align: text-bottom;"></span>',
            other: '<span class="dashicons dashicons-tag" style="color: #546E7A; font-size: 20px; width: 24px; height: 24px; line-height: 24px; margin-right: 8px; vertical-align: text-bottom;"></span>'
        };
        
        // Estilos CSS para la tabla moderna
        var html = '<div style="border-radius:6px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.1);">' + 
                  '<table style="width:100%; border-collapse:collapse; margin:0; background-color:#fff;" class="payment-breakdown-table">' +
                  '<thead>' +
                  '<tr style="background: linear-gradient(to right, #f0f7ff, #e6f1ff);">' +
                  '<th style="padding:14px 16px; text-align:left; border-bottom:2px solid #e3f0ff; font-weight:600; color:#2c5282; font-size: 15px;">Método de Pago</th>' +
                  '<th style="padding:14px 16px; text-align:right; border-bottom:2px solid #e3f0ff; font-weight:600; color:#2c5282; font-size: 15px;">Monto</th>' +
                  '</tr>' +
                  '</thead>' +
                  '<tbody>';
                
        var total = 0;
        var methods = ['cash', 'card', 'transfer', 'check', 'other'];
        var hasPositiveValues = false;
        
        // Añadir filas con datos para métodos estándar (en orden definido)
        methods.forEach(function(method) {
            var label = methodLabels[method] || method;
            var icon = methodIcons[method] || '';
            var amount = parseFloat(breakdown[method]) || 0;
            var amountFormatted = !isNaN(amount) ? amount.toFixed(2) : '0.00';
            
            // Verificar si hay valores positivos
            if (amount > 0) {
                hasPositiveValues = true;
            }
            
            // Estilo condicional según el valor
            var rowStyle = '';
            var amountStyle = '';
            
            if (amount > 0) {
                rowStyle = 'background-color:#f9fffa;';
                amountStyle = 'color: #2e7d32; font-weight:600;';
            } else {
                rowStyle = 'background-color:#f9f9f9;';
                amountStyle = 'color:#9e9e9e; font-weight:400;';
            }
            
            html += '<tr style="border-bottom:1px solid #f0f0f0; ' + rowStyle + '">'
                  + '<td style="padding:12px 16px; text-align:left; font-size:14px;">' + icon + '<span style="vertical-align:middle;">' + label + '</span></td>'
                  + '<td style="padding:12px 16px; text-align:right; font-family:monospace; font-size:15px; ' + amountStyle + '">$' + amountFormatted + '</td>'
                  + '</tr>';
                  
            total += amount;
        });
        
        // Formatear total
        var totalFormatted = !isNaN(total) ? total.toFixed(2) : '0.00';
        
        // Añadir fila de total con estilo mejorado
        html += '<tr style="border-top:2px solid #1976D2; background: linear-gradient(to right, #e3f2fd, #bbdefb);">'
              + '<td style="padding:14px 16px; text-align:left; font-size:15px; color:#0D47A1; font-weight:700;">'
              + '<span class="dashicons dashicons-chart-bar" style="font-size: 20px; width: 24px; height: 24px; line-height: 24px; margin-right: 8px; vertical-align: text-bottom; color:#1565C0;"></span>TOTAL</td>'
              + '<td style="padding:14px 16px; text-align:right; font-family:monospace; font-size:16px; color:#0D47A1; font-weight:700;">$' + totalFormatted + '</td>'
              + '</tr>';
        
        html += '</tbody></table></div>';
        
        // Mensaje si no hay valores
        if (!hasPositiveValues) {
            html += '<div style="text-align:center; margin-top:10px; font-style:italic; color:#757575; font-size:13px;">'
                + '<span class="dashicons dashicons-info-outline" style="vertical-align:middle; margin-right:5px;"></span>'
                + 'No se encontraron montos para este cierre</div>';
        }
        
        console.log('HTML de desglose generado correctamente');
        return html;
    }
    
    // Función auxiliar para normalizar datos de desglose de pagos
    function normalizePaymentBreakdown(breakdown) {
        // Si es string, intentar parsear como JSON
        if (typeof breakdown === 'string') {
            try {
                breakdown = JSON.parse(breakdown);
            } catch(e) {
                console.error('Error al parsear JSON de desglose:', e);
                breakdown = {};
            }
        }
        
        // Si es null o no es objeto
        if (!breakdown || typeof breakdown !== 'object') {
            breakdown = {};
        }
        
        // Mapeo de posibles nombres de métodos a las claves estándar
        var methodMappings = {
            cash: ['cash', 'efectivo', 'efectivo_total', 'cash_total', 'cashTotal'],
            card: ['card', 'tarjeta', 'credit', 'credit_card', 'credito', 'tarjeta_credito', 'tarjeta_de_credito', 'debito', 'tarjeta_de_debito', 'debit', 'debit_card', 'cardTotal'],
            transfer: ['transfer', 'transferencia', 'wire', 'bank_transfer', 'transferTotal'],
            check: ['check', 'cheque', 'checkTotal'],
            other: ['other', 'otro', 'otherTotal']
        };
        
        // Objeto normalizado con los 5 métodos estándar
        var normalizedBreakdown = {
            cash: 0,
            card: 0,
            transfer: 0,
            check: 0,
            other: 0
        };
        
        // Buscar valores en el objeto original usando todas las posibles variantes
        Object.keys(methodMappings).forEach(function(standardKey) {
            var possibleKeys = methodMappings[standardKey];
            
            // Buscar entre todas las posibles claves
            possibleKeys.forEach(function(possibleKey) {
                if (breakdown[possibleKey] !== undefined && !isNaN(parseFloat(breakdown[possibleKey]))) {
                    normalizedBreakdown[standardKey] += parseFloat(breakdown[possibleKey]);
                }
            });
        });
        
        return normalizedBreakdown;
    }
    
    // Función para renderizar el desglose de pagos en el modal
    function renderPaymentBreakdown(breakdown) {
        console.log('Renderizando desglose de pagos:', breakdown);
        
        // Asegurar que breakdown sea un objeto
        if (!breakdown || typeof breakdown !== 'object') {
            breakdown = {
                cash: 0,
                card: 0,
                transfer: 0,
                check: 0,
                other: 0
            };
        }
        
        // Utilizar la función mejorada para generar el HTML
        var html = renderPaymentBreakdownHTML(breakdown);
        $('#view-payment-breakdown').html(html);
    }
    
    // Exponer funciones necesarias globalmente
    window.renderClosuresList = renderClosuresList;
    window.loadClosuresList = loadClosuresList;
    window.initHistoryView = initHistoryView;
    
    // Agregar estilos globales para la vista de cierres
    function addGlobalStyles() {
        var styleElement = document.createElement('style');
        styleElement.id = 'wp-pos-closures-history-styles';
        styleElement.innerHTML = `
            /* Estilos para la tabla principal */
            #closures-table {
                border-collapse: separate !important;
                border-spacing: 0 !important;
                border-radius: 8px !important;
                overflow: hidden !important;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
                margin-top: 20px !important;
                border: 1px solid #e2e4e7 !important;
            }
            
            #closures-table th {
                background: linear-gradient(to bottom, #f8f9fa, #f1f3f5) !important;
                color: #444 !important;
                font-weight: 600 !important;
                text-transform: uppercase !important;
                font-size: 12px !important;
                letter-spacing: 0.5px !important;
                padding: 15px 12px !important;
                border-bottom: 2px solid #e2e4e7 !important;
                position: relative !important;
            }
            
            #closures-table td {
                padding: 14px 12px !important;
                border-bottom: 1px solid #f0f0f0 !important;
                vertical-align: middle !important;
                font-size: 14px !important;
                transition: background 0.2s !important;
            }
            
            #closures-table tr:last-child td {
                border-bottom: none !important;
            }
            
            #closures-table tr:hover td {
                background-color: #f9fbfd !important;
            }
            
            /* Estilos para estados de cierre */
            .closure-status {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 50px !important;
                padding: 4px 12px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                min-width: 80px !important;
            }
            
            .status-approved {
                background-color: #ebf9f0 !important;
                color: #27ae60 !important;
                border: 1px solid #d4f5e2 !important;
            }
            
            .status-rejected {
                background-color: #fdf1f0 !important;
                color: #e74c3c !important;
                border: 1px solid #fad9d7 !important;
            }
            
            .status-pending {
                background-color: #e9f3fe !important;
                color: #2980b9 !important;
                border: 1px solid #d0e6fb !important;
            }
            
            /* Estilos para valores monetarios */
            #closures-table td:nth-child(4),
            #closures-table td:nth-child(5),
            #closures-table td:nth-child(6) {
                font-family: monospace !important;
                font-size: 13px !important;
                letter-spacing: 0.5px !important;
            }
            
            /* Estilos para valores positivos/negativos */
            .negative-amount {
                color: #e53935 !important;
                font-weight: 600 !important;
                position: relative !important;
                background-color: rgba(229, 57, 53, 0.05) !important;
                border-radius: 4px !important;
                padding: 5px 8px !important;
                text-align: right !important;
            }
            
            .positive-amount {
                color: #388e3c !important;
                font-weight: 600 !important;
                position: relative !important;
                background-color: rgba(56, 142, 60, 0.05) !important;
                border-radius: 4px !important;
                padding: 5px 8px !important;
                text-align: right !important;
            }
            
            /* Animación de rotación para el indicador de carga */
            @keyframes rotation {
                from { transform: rotate(0deg); }
                to { transform: rotate(359deg); }
            }
            
            /* Animación de entrada suave */
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            /* Aplicar animación al modal */
            .wp-pos-closure-details {
                animation: fadeIn 0.3s ease-out;
            }
            
            /* Estilos para mensajes de notificación */
            .notice {
                padding: 12px 16px;
                border-radius: 6px;
                margin: 12px 0;
                display: flex;
                align-items: center;
                box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            }
            .notice-success {
                background-color: #e8f5e9;
                border-left: 4px solid #388e3c;
                color: #1b5e20;
            }
            .notice-error {
                background-color: #ffebee;
                border-left: 4px solid #e53935;
                color: #b71c1c;
            }
            .notice-info {
                background-color: #e3f2fd;
                border-left: 4px solid #1976d2;
                color: #0d47a1;
            }
            
            /* Estilos para botones de acción en la tabla */
            .actions-column {
                width: 70px !important;
                text-align: center !important;
                white-space: nowrap !important;
            }
            
            .actions-column button.view-closure {
                background-color: #f8f9fa !important;
                border: 1px solid #e2e4e7 !important;
                border-radius: 50% !important;
                color: #2271b1 !important;
                width: 32px !important;
                height: 32px !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                transition: all 0.2s !important;
            }
            
            .actions-column button.view-closure:hover {
                background-color: #2271b1 !important;
                color: white !important;
                border-color: #2271b1 !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 3px 8px rgba(0,0,0,0.1) !important;
            }
            
            .actions-column button.view-closure .dashicons {
                font-size: 16px !important;
                width: 16px !important;
                height: 16px !important;
            }
            
            /* Estilos para paginación */
            .pagination {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 25px 0 !important;
                gap: 5px !important;
            }
            
            .pagination button {
                background: white !important;
                border: 1px solid #e2e4e7 !important;
                border-radius: 4px !important;
                padding: 6px 12px !important;
                font-size: 13px !important;
                color: #50575e !important;
                min-width: 34px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                transition: all 0.2s !important;
            }
            
            .pagination button:hover:not(.current) {
                background-color: #f1f3f5 !important;
                border-color: #c3c4c7 !important;
            }
            
            .pagination button.current {
                background-color: #2271b1 !important;
                color: white !important;
                border-color: #2271b1 !important;
                font-weight: 600 !important;
            }
            
            /* Estilos para filtros */
            .closures-filters {
                padding: 16px !important;
                background-color: #f8f9fa !important;
                border: 1px solid #e2e4e7 !important;
                border-radius: 8px !important;
                margin-bottom: 20px !important;
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 15px !important;
                align-items: flex-end !important;
            }
            
            .closures-filters .filter-group {
                min-width: 200px !important;
            }
            
            .closures-filters label {
                display: block !important;
                margin-bottom: 5px !important;
                font-weight: 500 !important;
                color: #50575e !important;
                font-size: 13px !important;
            }
            
            /* Estilos para hover en filas de tabla */
            tr.hover-highlight td {
                background-color: #f0f7ff !important;
                transition: background-color 0.15s ease-in-out !important;
            }
            
            /* Estilos para estado vacío */
            .empty-state {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 25px !important;
                color: #787c82 !important;
                font-style: italic !important;
                background-color: #f9f9f9 !important;
                border-radius: 6px !important;
            }
            
            .empty-state .dashicons {
                margin-right: 8px !important;
                color: #b5bcc2 !important;
                font-size: 18px !important;
            }
            
            /* Estilos para celdas especiales */
            .date-cell {
                display: inline-flex !important;
                align-items: center !important;
                color: #50575e !important;
                font-family: monospace !important;
                letter-spacing: 0.5px !important;
            }
            
            .user-cell {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
            }
            
            .user-cell .dashicons {
                color: #787c82 !important;
                font-size: 16px !important;
            }
            
            .ui-dialog .ui-dialog-buttonpane button .dashicons {
                margin-right: 6px !important;
                font-size: 18px !important;
            }
            
            .ui-dialog .ui-dialog-buttonpane button.button-primary {
                background-color: #2271b1 !important;
                border-color: #2271b1 !important;
                color: white !important;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
            }
            
            .ui-dialog .ui-dialog-buttonpane button.button-primary:hover {
                background-color: #135e96 !important;
                border-color: #135e96 !important;
            }
            
            .ui-dialog .ui-dialog-buttonpane button.button-link-delete {
                color: #d63638 !important;
                border-color: #d63638 !important;
            }
            
            .ui-dialog .ui-dialog-buttonpane button.button-link-delete:hover {
                background-color: #f6e1e1 !important;
            }
            
            /* Estilos mejorados para el diálogo */
            .ui-dialog {
                border-radius: 8px !important;
                overflow: hidden !important;
                box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
            }
            
            .ui-dialog .ui-dialog-titlebar {
                background: linear-gradient(135deg, #1976d2, #0d47a1) !important;
                color: white !important;
                border: none !important;
                padding: 14px 18px !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                border-bottom: 1px solid rgba(0,0,0,0.1) !important;
            }
            
            .ui-dialog .ui-dialog-content {
                padding: 18px !important;
            }
            
            /* Estilos para iconos en línea */
            .dashicons {
                vertical-align: middle;
                margin-right: 6px;
            }
        `;
        document.head.appendChild(styleElement);
        console.log('Estilos de historial de cierres mejorados añadidos');
    }

    $(document).ready(function() {
        console.log('Inicializando vista de historial de cierres');
        
        // Añadir estilos globales primero
        addGlobalStyles();
        
        // Inicializar vista
        initHistoryView();
    });

})(jQuery);
