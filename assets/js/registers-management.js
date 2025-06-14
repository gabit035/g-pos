/**
 * Archivo JavaScript para la gestión de cajas registradoras
 * 
 * Maneja todas las interacciones AJAX para la apertura, cierre y gestión de transacciones
 * en el módulo de Cierres de Caja.
 *
 * @package WP-POS
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Referencias a los elementos DOM para modales
    const $openRegisterModal = $('#wp-pos-open-register-modal');
    const $addTransactionModal = $('#wp-pos-add-transaction-modal');
    const $closeRegisterModal = $('#wp-pos-close-register-modal');
    const $approveClosureModal = $('#wp-pos-approve-closure-modal');
    const $rejectClosureModal = $('#wp-pos-reject-closure-modal');

    // Contenedores de notificaciones
    const $notificationsContainer = $('.wp-pos-notifications');
    
    // Manejo de tabs
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        
        // Actualizar estados activos
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.wp-pos-tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Funciones para modales
    function openModal($modal) {
        $modal.fadeIn(300);
    }
    
    function closeModal($modal) {
        $modal.fadeOut(300);
    }
    
    // Asignar eventos para cerrar modales
    $('.wp-pos-modal-close, .wp-pos-modal-cancel').on('click', function() {
        closeModal($(this).closest('.wp-pos-modal'));
    });
    
    // Mostrar notificación
    function showNotification(message, type = 'success') {
        const $notification = $('<div class="wp-pos-notification ' + type + '">' + message + '</div>');
        $notificationsContainer.append($notification);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // --------------------------------
    // Manejo de Apertura de Caja
    // --------------------------------
    $('.open-register-button').on('click', function() {
        const registerId = $(this).data('register-id');
        $('#open-register-form input[name="register_id"]').val(registerId);
        openModal($openRegisterModal);
    });
    
    $('#open-register-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wp_pos_open_register');
        formData.append('nonce', wpPosRegistersData.nonce);
        
        $.ajax({
            url: wpPosRegistersData.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message);
                    // Recargar página después de 1 segundo
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message, 'error');
                }
                closeModal($openRegisterModal);
            },
            error: function() {
                showNotification(wpPosRegistersData.texts.error, 'error');
                closeModal($openRegisterModal);
            }
        });
    });
    
    // --------------------------------
    // Manejo de Transacciones
    // --------------------------------
    $('.add-transaction-button').on('click', function() {
        const registerId = $(this).data('register-id');
        $('#add-transaction-form input[name="register_id"]').val(registerId);
        openModal($addTransactionModal);
    });
    
    $('#add-transaction-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wp_pos_register_transaction');
        formData.append('nonce', wpPosRegistersData.closureNonce);
        
        $.ajax({
            url: wpPosRegistersData.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message);
                    // Recargar página después de 1 segundo
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message, 'error');
                }
                closeModal($addTransactionModal);
            },
            error: function() {
                showNotification(wpPosRegistersData.texts.error, 'error');
                closeModal($addTransactionModal);
            }
        });
    });
    
    // --------------------------------
    // Manejo de Cierre de Caja
    // --------------------------------
    $('.close-register-button').on('click', function() {
        const registerId = $(this).data('register-id');
        $('#close-register-form input[name="register_id"]').val(registerId);
        openModal($closeRegisterModal);
    });
    
    $('#close-register-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wp_pos_start_closure');
        formData.append('nonce', wpPosRegistersData.nonce);
        
        $.ajax({
            url: wpPosRegistersData.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message);
                    // Recargar página después de 1 segundo
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message, 'error');
                }
                closeModal($closeRegisterModal);
            },
            error: function() {
                showNotification(wpPosRegistersData.texts.error, 'error');
                closeModal($closeRegisterModal);
            }
        });
    });
    
    // --------------------------------
    // Manejo de Aprobación de Cierres
    // --------------------------------
    $('.approve-closure-button').on('click', function() {
        const closureId = $(this).data('closure-id');
        $('#approve_closure_id').val(closureId);
        openModal($approveClosureModal);
    });
    
    $('#approve-closure-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wp_pos_approve_closure');
        formData.append('nonce', wpPosRegistersData.nonce);
        
        $.ajax({
            url: wpPosRegistersData.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message);
                    // Recargar página después de 1 segundo
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message, 'error');
                }
                closeModal($approveClosureModal);
            },
            error: function() {
                showNotification(wpPosRegistersData.texts.error, 'error');
                closeModal($approveClosureModal);
            }
        });
    });
    
    // --------------------------------
    // Manejo de Rechazo de Cierres
    // --------------------------------
    $('.reject-closure-button').on('click', function() {
        const closureId = $(this).data('closure-id');
        $('#reject_closure_id').val(closureId);
        openModal($rejectClosureModal);
    });
    
    $('#reject-closure-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wp_pos_reject_closure');
        formData.append('nonce', wpPosRegistersData.nonce);
        
        $.ajax({
            url: wpPosRegistersData.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message);
                    // Recargar página después de 1 segundo
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message, 'error');
                }
                closeModal($rejectClosureModal);
            },
            error: function() {
                showNotification(wpPosRegistersData.texts.error, 'error');
                closeModal($rejectClosureModal);
            }
        });
    });
    
    // --------------------------------
    // Exportación de Cierres
    // --------------------------------
    $('.export-closure-button').on('click', function() {
        const closureId = $(this).data('closure-id');
        const exportUrl = wpPosRegistersData.ajax_url + '?action=wp_pos_export_closure_data&nonce=' + 
                           wpPosRegistersData.nonce + '&closure_id=' + closureId + '&type=csv';
                           
        // Abrir en nueva ventana para descarga
        window.open(exportUrl, '_blank');
    });

})(jQuery);
