/**
 * Gestión de aprobación/rechazo de cierres de caja
 * Versión: 1.0.0
 * 
 * Este archivo contiene la lógica JS para gestionar el flujo de aprobación
 * y rechazo de cierres de caja, asegurando que funcione correctamente
 * incluso en nuevas instalaciones de WordPress.
 */

jQuery(document).ready(function($) {
    // Constantes para los tipos de estados
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PENDING = 'pending';
    
    // Botones de aprobación/rechazo en el modal de detalles
    $('.wp-pos-approve-closure').on('click', function(e) {
        e.preventDefault();
        const closureId = $(this).data('closure-id');
        updateClosureStatus(closureId, STATUS_APPROVED);
    });
    
    $('.wp-pos-reject-closure').on('click', function(e) {
        e.preventDefault();
        const closureId = $(this).data('closure-id');
        showRejectionModal(closureId);
    });
    
    // Función para mostrar el modal de rechazo con justificación
    function showRejectionModal(closureId) {
        $('#rejection-justification').val('');
        $('#rejection-closure-id').val(closureId);
        $('#wp-pos-rejection-modal').show();
    }
    
    // Confirmar rechazo con justificación
    $('#confirm-rejection').on('click', function() {
        const closureId = $('#rejection-closure-id').val();
        const justification = $('#rejection-justification').val();
        
        if (!justification.trim()) {
            alert('Debe proporcionar una justificación para rechazar el cierre.');
            return;
        }
        
        $('#wp-pos-rejection-modal').hide();
        updateClosureStatus(closureId, STATUS_REJECTED, justification);
    });
    
    // Cancelar rechazo
    $('#cancel-rejection').on('click', function() {
        $('#wp-pos-rejection-modal').hide();
    });
    
    // Función para actualizar el estado del cierre
    function updateClosureStatus(closureId, status, justification = '') {
        // Mostrar indicador de carga
        showLoading();
        
        // Datos para la petición AJAX
        const data = {
            action: 'wp_pos_closures_update_status',
            nonce: wp_pos_closures_vars.nonce,
            closure_id: closureId,
            status: status,
            justification: justification
        };
        
        // Realizar la petición AJAX
        $.post(ajaxurl, data, function(response) {
            hideLoading();
            
            if (response.success) {
                // Actualizar la interfaz con el nuevo estado
                updateUIAfterStatusChange(closureId, status);
                
                // Mostrar mensaje de éxito
                showNotification('success', response.data.message);
                
                // Cerrar el modal de detalles
                $('#wp-pos-closure-details-modal').hide();
                
                // Recargar la tabla de cierres para reflejar el cambio
                if (typeof loadClosures === 'function') {
                    loadClosures();
                }
            } else {
                // Mostrar mensaje de error
                showNotification('error', response.data.message);
            }
        }).fail(function() {
            hideLoading();
            showNotification('error', 'Error de conexión al procesar la solicitud.');
        });
    }
    
    // Actualizar la UI después de un cambio de estado
    function updateUIAfterStatusChange(closureId, status) {
        // Actualizar la fila en la tabla de cierres
        const row = $(`tr[data-closure-id="${closureId}"]`);
        
        if (row.length) {
            // Eliminar clases de estado anteriores
            row.removeClass('status-pending status-approved status-rejected');
            
            // Añadir la clase para el nuevo estado
            row.addClass(`status-${status}`);
            
            // Actualizar el texto del estado
            const statusCell = row.find('.closure-status');
            if (statusCell.length) {
                let statusText = 'Pendiente';
                if (status === STATUS_APPROVED) {
                    statusText = 'Aprobado';
                } else if (status === STATUS_REJECTED) {
                    statusText = 'Rechazado';
                }
                statusCell.text(statusText);
            }
        }
    }
    
    // Funciones auxiliares para la UI
    function showLoading() {
        $('.wp-pos-loading-overlay').show();
    }
    
    function hideLoading() {
        $('.wp-pos-loading-overlay').hide();
    }
    
    function showNotification(type, message) {
        // Si existe una función de notificaciones, usarla
        if (typeof WPPOSNotifications !== 'undefined' && typeof WPPOSNotifications.show === 'function') {
            WPPOSNotifications.show(type, message);
            return;
        }
        
        // Fallback simple si no existe el sistema de notificaciones
        const notificationClass = type === 'success' ? 'wp-pos-notification-success' : 'wp-pos-notification-error';
        
        // Crear elemento de notificación
        const notification = $(`<div class="wp-pos-notification ${notificationClass}">${message}</div>`);
        
        // Añadir al DOM
        $('body').append(notification);
        
        // Animar entrada
        notification.fadeIn();
        
        // Eliminar después de 5 segundos
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Ver historial de cambios de estado
    $('.wp-pos-view-status-history').on('click', function(e) {
        e.preventDefault();
        const closureId = $(this).data('closure-id');
        loadStatusHistory(closureId);
    });
    
    // Cargar historial de cambios de estado
    function loadStatusHistory(closureId) {
        showLoading();
        
        const data = {
            action: 'wp_pos_closures_get_status_history',
            nonce: wp_pos_closures_vars.nonce,
            closure_id: closureId
        };
        
        $.post(ajaxurl, data, function(response) {
            hideLoading();
            
            if (response.success && response.data.history) {
                // Limpiar contenedor de historial
                const historyContainer = $('#status-history-content');
                historyContainer.empty();
                
                // Llenar con los datos recibidos
                if (response.data.history.length > 0) {
                    const historyItems = response.data.history.map(function(item) {
                        return `
                            <div class="history-item">
                                <div class="history-header">
                                    <span class="history-date">${item.changed_at}</span>
                                    <span class="history-user">${item.user_name}</span>
                                </div>
                                <div class="history-status">
                                    <span class="status-from">De: ${formatStatus(item.old_status)}</span>
                                    <span class="status-arrow">→</span>
                                    <span class="status-to">A: ${formatStatus(item.new_status)}</span>
                                </div>
                                ${item.justification ? `<div class="history-justification">${item.justification}</div>` : ''}
                            </div>
                        `;
                    }).join('');
                    
                    historyContainer.html(historyItems);
                } else {
                    historyContainer.html('<p>No hay cambios de estado registrados.</p>');
                }
                
                // Mostrar el modal
                $('#wp-pos-status-history-modal').show();
            } else {
                showNotification('error', 'No se pudo cargar el historial de estados.');
            }
        }).fail(function() {
            hideLoading();
            showNotification('error', 'Error de conexión al cargar el historial.');
        });
    }
    
    // Cerrar modal de historial
    $('#close-history-modal').on('click', function() {
        $('#wp-pos-status-history-modal').hide();
    });
    
    // Formatear estado para mostrar
    function formatStatus(status) {
        switch(status) {
            case STATUS_APPROVED:
                return 'Aprobado';
            case STATUS_REJECTED:
                return 'Rechazado';
            case STATUS_PENDING:
                return 'Pendiente';
            default:
                return status;
        }
    }
});
