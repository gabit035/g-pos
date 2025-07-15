<?php
/**
 * Vista de historial de Cierres de Caja
 *
 * @package WP-POS
 * @subpackage Closures
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;

// Obtener fechas para filtros predeterminados
$current_date = current_time('Y-m-d');
$month_start = date('Y-m-01', strtotime($current_date));

// Variables para AJAX
$ajax_url = admin_url('admin-ajax.php');
$ajax_nonce = wp_create_nonce('wp_pos_closures_nonce');

// Cargar estilos necesarios
wp_enqueue_style('wp-pos-closures-status-css');

// Get current view for active tab highlighting
$current_view = 'history';
?>

<div class="wrap">
    <?php include_once 'closures-header.php'; ?>
    
<script>
// Asegurar que el sistema de notificaciones exista
if (typeof WP_POS_Notifications === 'undefined') {
    // Fallback simple para notificaciones si el sistema principal no estu00e1 disponible
    window.WP_POS_Notifications = {
        success: function(message) { alert('✔️ ' + message); },
        error: function(message) { alert('❌ ' + message); },
        warning: function(message) { alert('⚠️ ' + message); },
        info: function(message) { alert('ℹ️ ' + message); }
    };
}

// Script para manejar el menú desplegable de exportación
jQuery(document).ready(function($) {
    $('.wp-pos-dropdown button').on('click', function(e) {
        e.preventDefault();
        $(this).siblings('.wp-pos-dropdown-content').toggle();
    });
    
    // Cerrar el menú cuando se hace clic fuera de él
    $(document).on('click', function(e) {
        if(!$(e.target).closest('.wp-pos-dropdown').length) {
            $('.wp-pos-dropdown-content').hide();
        }
    });
});
</script>

<div class="wrap wp-pos-closures-history-container">
    
    <hr class="wp-header-end">
    
    <div class="wp-pos-filter-bar" style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div class="filter-section" style="margin-right: 15px;">
            <label for="filter-date-from"><?php _e('Desde:', 'wp-pos'); ?></label>
            <input type="date" id="filter-date-from" value="<?php echo $month_start; ?>">
        </div>
        
        <div class="filter-section">
            <label for="filter-date-to"><?php _e('Hasta:', 'wp-pos'); ?></label>
            <input type="date" id="filter-date-to" value="<?php echo $current_date; ?>">
        </div>
        
        <div class="filter-section">
            <label for="filter-status"><?php _e('Estado:', 'wp-pos'); ?></label>
            <select id="filter-status">
                <option value=""><?php _e('Todos', 'wp-pos'); ?></option>
                <option value="pending"><?php _e('Pendiente', 'wp-pos'); ?></option>
                <option value="approved"><?php _e('Aprobado', 'wp-pos'); ?></option>
                <option value="rejected"><?php _e('Rechazado', 'wp-pos'); ?></option>
            </select>
        </div>
        
        <div class="filter-actions">
            <button id="filter-button" class="button button-primary">
                <span class="dashicons dashicons-filter"></span> <?php _e('Filtrar', 'wp-pos'); ?>
            </button>
            
            <button id="reset-filters" class="button">
                <span class="dashicons dashicons-image-rotate"></span> <?php _e('Reiniciar', 'wp-pos'); ?>
            </button>
        </div>
    </div>
    
    <div class="wp-pos-table-container">
        <table id="closures-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-id"><?php _e('ID', 'wp-pos'); ?></th>
                    <th class="column-date"><?php _e('Fecha', 'wp-pos'); ?></th>
                    <th class="column-cashier"><?php _e('Cajero', 'wp-pos'); ?></th>
                    <th class="column-initial"><?php _e('Monto Inicial', 'wp-pos'); ?></th>
                    <th class="column-expected"><?php _e('Esperado', 'wp-pos'); ?></th>
                    <th class="column-actual"><?php _e('Final', 'wp-pos'); ?></th>
<th class="column-payment-breakdown"><?php _e('Métodos de Pago', 'wp-pos'); ?></th>
                    <th class="column-difference"><?php _e('Diferencia', 'wp-pos'); ?></th>
                    <th class="column-status"><?php _e('Estado', 'wp-pos'); ?></th>
                    <th class="column-actions"><?php _e('Acciones', 'wp-pos'); ?></th>
                </tr>
            </thead>
            <tbody id="closures-list">
                <tr class="no-items">
                    <td colspan="10"><?php _e('Cargando datos...', 'wp-pos'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num" id="items-count">0 <?php _e('elementos', 'wp-pos'); ?></span>
                <span class="pagination-links" id="pagination-links">
                    <a class="first-page button" id="first-page"><span class="screen-reader-text"><?php _e('Primera pu00e1gina', 'wp-pos'); ?></span><span aria-hidden="true">&laquo;</span></a>
                    <a class="prev-page button" id="prev-page"><span class="screen-reader-text"><?php _e('Pu00e1gina anterior', 'wp-pos'); ?></span><span aria-hidden="true">&lsaquo;</span></a>
                    <span class="paging-input">
                        <label for="current-page-selector" class="screen-reader-text"><?php _e('Pu00e1gina actual', 'wp-pos'); ?></label>
                        <input class="current-page" id="current-page" type="number" min="1" value="1" size="1">
                        <span class="tablenav-paging-text"> <?php _e('de', 'wp-pos'); ?> <span class="total-pages" id="total-pages">1</span></span>
                    </span>
                    <a class="next-page button" id="next-page"><span class="screen-reader-text"><?php _e('Pu00e1gina siguiente', 'wp-pos'); ?></span><span aria-hidden="true">&rsaquo;</span></a>
                    <a class="last-page button" id="last-page"><span class="screen-reader-text"><?php _e('u00daltima pu00e1gina', 'wp-pos'); ?></span><span aria-hidden="true">&raquo;</span></a>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de cierre -->
<div id="closure-details-modal" class="wp-pos-modal" style="display:none;">
    <div class="wp-pos-modal-content">
        <div class="wp-pos-modal-header">
            <h2><?php _e('Detalles del Cierre', 'wp-pos'); ?> #<span id="modal-closure-id"></span></h2>
            <span class="wp-pos-modal-close">&times;</span>
        </div>
        <div class="wp-pos-modal-body">
            <div class="wp-pos-details-grid">
                <div class="wp-pos-detail-column">
                    <div class="wp-pos-detail-item">
                        <div class="wp-pos-detail-label"><?php _e('Fecha de Cierre', 'wp-pos'); ?></div>
                        <div class="wp-pos-detail-value" id="detail-date"></div>
                    </div>
                    <div class="wp-pos-detail-item">
                        <div class="wp-pos-detail-label"><?php _e('Cajero', 'wp-pos'); ?></div>
                        <div class="wp-pos-detail-value" id="detail-cashier"></div>
                    </div>
                    <div class="wp-pos-detail-item">
                        <div class="wp-pos-detail-label"><?php _e('Caja Registradora', 'wp-pos'); ?></div>
                        <div class="wp-pos-detail-value" id="detail-register"></div>
                    </div>
                </div>
                
                <div class="wp-pos-detail-column">
                    <div class="wp-pos-detail-item">
                        <div class="wp-pos-detail-label"><?php _e('Monto Inicial', 'wp-pos'); ?></div>
                        <div class="wp-pos-detail-value" id="detail-initial"></div>
                    </div>
                    <div class="wp-pos-detail-item">
                        <div class="wp-pos-detail-label"><?php _e('Monto Esperado', 'wp-pos'); ?></div>
                        <div class="wp-pos-detail-value" id="detail-expected"></div>
                    </div>
                    <div class="wp-pos-detail-item">
                        <div class="wp-pos-detail-label"><?php _e('Monto Final', 'wp-pos'); ?></div>
                        <div class="wp-pos-detail-value" id="detail-actual"></div>
                    </div>
                    <div class="wp-pos-detail-item">
                        <div class="wp-pos-detail-label"><?php _e('Diferencia', 'wp-pos'); ?></div>
                        <div class="wp-pos-detail-value" id="detail-difference"></div>
                    </div>
                </div>
            </div>
            
            <div class="wp-pos-detail-notes">
                <div class="wp-pos-detail-label"><?php _e('Observaciones', 'wp-pos'); ?></div>
                <div class="wp-pos-detail-value" id="detail-notes"></div>
            </div>
            
            <div class="wp-pos-detail-item status-item">
                <div class="wp-pos-detail-label"><?php _e('Estado', 'wp-pos'); ?></div>
                <div class="wp-pos-detail-value" id="detail-status"></div>
            </div>
        </div>
        <div class="wp-pos-modal-footer">
            <?php if (current_user_can('administrator')): ?>
            <div class="wp-pos-admin-actions">
                <button id="approve-closure" class="button button-primary">
                    <span class="dashicons dashicons-yes"></span> <?php _e('Aprobar', 'wp-pos'); ?>
                </button>
                <button id="reject-closure" class="button button-secondary">
                    <span class="dashicons dashicons-no"></span> <?php _e('Rechazar', 'wp-pos'); ?>
                </button>
                <button id="view-status-history" class="button button-secondary">
                    <span class="dashicons dashicons-list-view"></span> <?php _e('Historial', 'wp-pos'); ?>
                </button>
                <button id="delete-closure" class="button button-link-delete">
                    <span class="dashicons dashicons-trash"></span> <?php _e('Eliminar', 'wp-pos'); ?>
                </button>
            </div>
            <?php endif; ?>
            <button class="button wp-pos-modal-close-btn"><?php _e('Cerrar', 'wp-pos'); ?></button>
        </div>
    </div>
</div>

<style>
    /* Estilos para estados de cierre */
    .status-label {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-weight: bold;
        text-align: center;
    }
    
    .status-pending {
        background-color: #FFF3CD;
        color: #856404;
        border: 1px solid #FFEEBA;
    }
    
    .status-approved {
        background-color: #D4EDDA;
        color: #155724;
        border: 1px solid #C3E6CB;
    }
    
    .status-rejected {
        background-color: #F8D7DA;
        color: #721C24;
        border: 1px solid #F5C6CB;
    }
</style>

<!-- Modal para justificaciu00f3n de rechazo -->
<div id="rejection-dialog" class="wp-pos-modal" style="display:none;">
    <div class="wp-pos-modal-content">
        <div class="wp-pos-modal-header">
            <h2><?php _e('Justificaciu00f3n de Rechazo', 'wp-pos'); ?></h2>
            <span class="wp-pos-modal-close">&times;</span>
        </div>
        <div class="wp-pos-modal-body">
            <textarea id="rejection-justification" placeholder="<?php _e('Ingrese la justificaciu00f3n para rechazar este cierre', 'wp-pos'); ?>" style="width: 100%; min-height: 100px;"></textarea>
        </div>
        <div class="wp-pos-modal-footer">
            <button id="submit-rejection" class="button button-primary"><?php _e('Enviar', 'wp-pos'); ?></button>
            <button class="button wp-pos-modal-close-btn"><?php _e('Cancelar', 'wp-pos'); ?></button>
        </div>
    </div>
</div>

<!-- Modal para ver historial de cambios de estado -->
<div id="status-history-modal" class="wp-pos-modal" style="display:none;">
    <div class="wp-pos-modal-content">
        <div class="wp-pos-modal-header">
            <h2><?php _e('Historial de Cambios de Estado', 'wp-pos'); ?> - <?php _e('Cierre', 'wp-pos'); ?> #<span id="history-closure-id"></span></h2>
            <span class="wp-pos-modal-close">&times;</span>
        </div>
        <div class="wp-pos-modal-body">
            <div class="wp-pos-status-history-list">
                <table class="wp-list-table widefat fixed striped" id="status-history-table">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'wp-pos'); ?></th>
                            <th><?php _e('Estado', 'wp-pos'); ?></th>
                            <th><?php _e('Usuario', 'wp-pos'); ?></th>
                            <th><?php _e('Justificación', 'wp-pos'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="status-history-list">
                        <tr><td colspan="4" class="loading-data"><?php _e("Cargando datos...", "wp-pos"); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="wp-pos-modal-footer">
            <button class="button wp-pos-modal-close-btn"><?php _e('Cerrar', 'wp-pos'); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Variables globales
    var currentPage = 1;
    var totalPages = 1;
    var itemsPerPage = 50; // Aumentamos a 50 para ver mu00e1s registros
    
    // Establecer un rango de fechas muy amplio para mostrar todos los cierres
    $('#filter-date-from').val('2000-01-01'); // Fecha muy antigua
    $('#filter-date-to').val('2030-12-31'); // Fecha muy futura
    $('#filter-status').val(''); // Sin filtro de estado
    
    var currentFilters = {
        dateFrom: '2000-01-01',
        dateTo: '2030-12-31',
        status: ''
    };
    
    // Cargar datos iniciales
    loadClosuresList();
    
    // Manejar filtros
    $('#filter-button').on('click', function() {
        currentFilters = {
            dateFrom: $('#filter-date-from').val(),
            dateTo: $('#filter-date-to').val(),
            status: $('#filter-status').val()
        };
        currentPage = 1;
        loadClosuresList();
    });
    
    // Reiniciar filtros
    $('#reset-filters').on('click', function() {
        $('#filter-date-from').val('<?php echo $month_start; ?>');
        $('#filter-date-to').val('<?php echo $current_date; ?>');
        $('#filter-status').val('');
        
        currentFilters = {
            dateFrom: '<?php echo $month_start; ?>',
            dateTo: '<?php echo $current_date; ?>',
            status: ''
        };
        currentPage = 1;
        loadClosuresList();
    });
    
    // Paginaciu00f3n
    $('#first-page').on('click', function() {
        if (currentPage > 1) {
            currentPage = 1;
            loadClosuresList();
        }
    });
    
    $('#prev-page').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadClosuresList();
        }
    });
    
    $('#next-page').on('click', function() {
        if (currentPage < totalPages) {
            currentPage++;
            loadClosuresList();
        }
    });
    
    $('#last-page').on('click', function() {
        if (currentPage < totalPages) {
            currentPage = totalPages;
            loadClosuresList();
        }
    });
    
    $('#current-page').on('change', function() {
        var newPage = parseInt($(this).val());
        if (newPage > 0 && newPage <= totalPages && newPage !== currentPage) {
            currentPage = newPage;
            loadClosuresList();
        } else {
            $(this).val(currentPage);
        }
    });
    
    // Abrir modal de detalles
    $(document).on('click', '.view-closure', function() {
        var closureId = $(this).data('id');
        loadClosureDetails(closureId);
    });
    
    // Cerrar modales
    $('.wp-pos-modal-close, .wp-pos-modal-close-btn').on('click', function() {
        $('.wp-pos-modal').hide();
    });
    
    // Botones de aprobación/rechazo
    $('#approve-closure').on('click', function() {
        var closureId = $('#closure-details').data('id');
        if (confirm('<?php _e("¿Está seguro que desea APROBAR este cierre?", "wp-pos"); ?>')) {
            updateClosureStatus(closureId, 'approved');
        }
    });
    
    $('#reject-closure').on('click', function() {
        var closureId = $('#closure-details').data('id');
        showRejectionDialog(closureId);
    });
    
    // Botón para ver historial de cambios de estado
    $('#view-status-history').on('click', function() {
        var closureId = $('#closure-details').data('id');
        loadStatusHistory(closureId);
    });
    
    // Botón para enviar rechazo con justificación
    $('#submit-rejection').on('click', function() {
        var closureId = $('#reject-closure').data('id');
        var justification = $('#rejection-justification').val().trim();
        
        if (!justification) {
            alert('<?php _e("Debe proporcionar una justificación para rechazar el cierre.", "wp-pos"); ?>');
            return;
        }
        
        updateClosureStatus(closureId, 'rejected', justification);
        $('#rejection-dialog').hide();
    });
    
    // Acciones administrativas
    $('#approve-closure').on('click', function() {
        updateClosureStatus($(this).data('id'), 'approved');
    });
    
    $('#reject-closure').on('click', function() {
        var closureId = $(this).data('id');
        // Pedir justificación para rechazo
        showRejectionDialog(closureId);
    });
    
    $('#view-status-history').on('click', function() {
        var closureId = $(this).data('id');
        loadStatusHistory(closureId);
    });
    
    $('#delete-closure').on('click', function() {
        deleteClosure($(this).data('id'));
    });
    
    // Enviar justificación de rechazo
    $('#submit-rejection').on('click', function() {
        var closureId = $('#reject-closure').data('id');
        var justification = $('#rejection-justification').val();
        if (!justification.trim()) {
            alert('<?php _e("Debe ingresar una justificación para rechazar el cierre", "wp-pos"); ?>');
            return;
        }
        updateClosureStatus(closureId, 'rejected', justification);
        $('#rejection-dialog').hide();
    });
    
    // Funciu00f3n para cargar la lista de cierres
    function loadClosuresList() {
        $('#closures-list').html('<tr><td colspan="9" class="loading-data"><?php _e("Cargando datos...", "wp-pos"); ?></td></tr>');
        
        $.ajax({
            url: '<?php echo $ajax_url; ?>',
            type: 'POST',
            data: {
                action: 'wp_pos_closures_get_closures',
                nonce: '<?php echo $ajax_nonce; ?>',
                page: currentPage,
                per_page: itemsPerPage,
                date_from: currentFilters.dateFrom,
                date_to: currentFilters.dateTo,
                status: currentFilters.status
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    renderClosuresList(data.closures);
                    updatePagination(data.total_items, data.total_pages);
                } else {
                    $('#closures-list').html('<tr><td colspan="9" class="no-items"><?php _e("Error al cargar los datos", "wp-pos"); ?>: ' + response.data.message + '</td></tr>');
                }
            },
            error: function() {
                $('#closures-list').html('<tr><td colspan="9" class="no-items"><?php _e("Error de conexi\u00f3n al cargar los datos", "wp-pos"); ?></td></tr>');
            }
        });
    }
    
    // Funciu00f3n para renderizar la lista de cierres
    function renderClosuresList(closures) {
        if (!closures || closures.length === 0) {
            $('#closures-list').html('<tr><td colspan="9" class="no-items"><?php _e("No se encontraron cierres de caja", "wp-pos"); ?></td></tr>');
            return;
        }
        
        var html = '';
        $.each(closures, function(index, closure) {
            var differenceClass = parseFloat(closure.difference) < 0 ? 'negative-amount' : 
                                 (parseFloat(closure.difference) > 0 ? 'positive-amount' : '');
            
            var statusClass = 'status-' + closure.status;
            var statusText = getStatusText(closure.status);
            
            html += '<tr>';
            html += '<td>' + closure.id + '</td>';
            html += '<td>' + formatDate(closure.created_at) + '</td>';
            html += '<td>' + (closure.user_name || '-') + '</td>';
            html += '<td>' + formatCurrency(closure.initial_amount) + '</td>';
            html += '<td>' + formatCurrency(closure.expected_amount) + '</td>';
            html += '<td>' + formatCurrency(closure.actual_amount) + '</td>';
            html += '<td class="' + differenceClass + '">' + formatCurrency(closure.difference) + '</td>';
            html += '<td><span class="closure-status ' + statusClass + '">' + statusText + '</span></td>';
            html += '<td class="actions-column">';
            html += '<button class="button button-small view-closure" data-id="' + closure.id + '">';
            html += '<span class="dashicons dashicons-visibility"></span></button>';
            html += '</td>';
            html += '</tr>';
        });
        
        $('#closures-list').html(html);
    }
    
    // Funciu00f3n para actualizar la paginaciu00f3n
    function updatePagination(totalItems, pages) {
        totalPages = pages;
        $('#items-count').text(totalItems + ' <?php _e("elementos", "wp-pos"); ?>');
        $('#total-pages').text(totalPages);
        $('#current-page').val(currentPage);
        
        // Deshabilitar/habilitar botones de paginaciu00f3n
        $('#first-page, #prev-page').toggleClass('disabled', currentPage <= 1);
        $('#next-page, #last-page').toggleClass('disabled', currentPage >= totalPages);
    }
    
    // Funciu00f3n para cargar detalles de un cierre
    function loadClosureDetails(closureId) {
        $.ajax({
            url: '<?php echo $ajax_url; ?>',
            type: 'POST',
            data: {
                action: 'wp_pos_closures_get_closure_details',
                nonce: '<?php echo $ajax_nonce; ?>',
                closure_id: closureId
            },
            success: function(response) {
                if (response.success) {
                    renderClosureDetails(response.data.closure);
                    $('#closure-details-modal').show();
                } else {
                    alert(response.data.message || '<?php _e("Error al cargar los detalles", "wp-pos"); ?>');
                }
            },
            error: function() {
                alert('<?php _e("Error de conexi\u00f3n al cargar los detalles", "wp-pos"); ?>');
            }
        });
    }
    
    // Funciu00f3n para renderizar detalles de un cierre
    function renderClosureDetails(closure) {
        $('#modal-closure-id').text(closure.id);
        $('#detail-date').text(formatDate(closure.created_at));
        $('#detail-cashier').text(closure.user_name || '-');
        $('#detail-register').text(closure.register_name || '-');
        $('#detail-initial').text(formatCurrency(closure.initial_amount));
        $('#detail-expected').text(formatCurrency(closure.expected_amount));
        $('#detail-actual').text(formatCurrency(closure.actual_amount));
        
        var differenceClass = parseFloat(closure.difference) < 0 ? 'negative-amount' : 
                             (parseFloat(closure.difference) > 0 ? 'positive-amount' : '');
        $('#detail-difference').removeClass('negative-amount positive-amount').addClass(differenceClass).text(formatCurrency(closure.difference));
        
        $('#detail-notes').text(closure.justification || '-');
        
        var statusClass = 'status-' + closure.status;
        var statusText = getStatusText(closure.status);
        $('#detail-status').removeClass().addClass('status-label ' + statusClass).text(statusText);
        
        // Configurar botones de acciones con el ID del cierre
        $('#approve-closure, #reject-closure, #delete-closure').data('id', closure.id);
        
        // Mostrar/ocultar botones segu00fan el estado actual
        if (closure.status === 'approved') {
            $('#approve-closure').hide();
            $('#reject-closure').show();
        } else if (closure.status === 'rejected') {
            $('#approve-closure').show();
            $('#reject-closure').hide();
        } else {
            $('#approve-closure').show();
            $('#reject-closure').show();
        }
    }
    
    // La funciu00f3n updateClosureStatus se ha movido y mejorado abajo para evitar duplicados
    
    // Funciu00f3n para eliminar un cierre
    function deleteClosure(closureId) {
        if (!confirm('<?php _e("\u00bfEst\u00e1s seguro de que deseas eliminar este cierre? Esta acci\u00f3n no se puede deshacer.", "wp-pos"); ?>')) {
            return;
        }
        
        $.ajax({
            url: '<?php echo $ajax_url; ?>',
            type: 'POST',
            data: {
                action: 'wp_pos_closures_delete_closure',
                nonce: '<?php echo $ajax_nonce; ?>',
                closure_id: closureId
            },
            success: function(response) {
                if (response.success) {
                    WP_POS_Notifications.success(response.data.message || '<?php _e("Cierre eliminado correctamente", "wp-pos"); ?>');
                    $('#closure-details-modal').hide();
                    loadClosuresList();
                } else {
                    WP_POS_Notifications.error(response.data.message || '<?php _e("Error al eliminar el cierre", "wp-pos"); ?>');
                }
            },
            error: function() {
                WP_POS_Notifications.error('<?php _e("Error de conexi\u00f3n al eliminar el cierre", "wp-pos"); ?>');
            }
        });
    }
    
    // Funciu00f3n para mostrar el diálogo de justificación de rechazo
    function showRejectionDialog(closureId) {
        $('#reject-closure').data('id', closureId);
        $('#rejection-justification').val('');
        $('#rejection-dialog').show();
    }
    
    // Funciu00f3n para actualizar el estado de un cierre (aprobar/rechazar)
    function updateClosureStatus(closureId, status, justification = '') {
        // Mostrar indicador de carga
        var loadingHtml = '<div class="wp-pos-loading-overlay"><div class="wp-pos-loading-spinner"></div></div>';
        $('#closure-details-modal').append(loadingHtml);
        
        $.ajax({
            url: '<?php echo $ajax_url; ?>',
            type: 'POST',
            data: {
                action: 'wp_pos_closures_update_status',
                nonce: '<?php echo $ajax_nonce; ?>',
                closure_id: closureId,
                status: status,
                justification: justification || ''
            },
            success: function(response) {
                // Quitar indicador de carga
                $('.wp-pos-loading-overlay').remove();
                
                if (response.success) {
                    // Mostrar mensaje de éxito
                    WP_POS_Notifications.success(response.data.message);
                    
                    // Actualizar UI con el nuevo estado
                    $('#closure-details .closure-status').removeClass('status-pending status-approved status-rejected').addClass('status-' + status);
                    $('#closure-details .closure-status').text(getStatusText(status));
                    
                    // Recargar la lista de cierres para reflejar el cambio
                    loadClosuresList();
                    
                    // Cerrar modal después de un breve retraso
                    setTimeout(function() {
                        $('#closure-details-modal').hide();
                    }, 1500);
                } else {
                    // Mostrar mensaje de error
                    WP_POS_Notifications.error(response.data.message || '<?php _e("Error al actualizar el estado del cierre", "wp-pos"); ?>');
                }
            },
            error: function() {
                // Quitar indicador de carga
                $('.wp-pos-loading-overlay').remove();
                
                // Mostrar mensaje de error
                WP_POS_Notifications.error('<?php _e("Error de conexiu00f3n al actualizar el estado", "wp-pos"); ?>');
            }
        });
    }
    
    // Funciu00f3n para cargar historial de cambios de estado
    function loadStatusHistory(closureId) {
        $('#history-closure-id').text(closureId);
        $('#status-history-list').html('<tr><td colspan="4" class="loading-data"><?php _e("Cargando datos...", "wp-pos"); ?></td></tr>');
        
        $.ajax({
            url: '<?php echo $ajax_url; ?>',
            type: 'POST',
            data: {
                action: 'wp_pos_closures_get_status_history',
                nonce: '<?php echo $ajax_nonce; ?>',
                closure_id: closureId
            },
            success: function(response) {
                if (response.success) {
                    renderStatusHistory(response.data.status_history);
                    $('#status-history-modal').show();
                } else {
                    WP_POS_Notifications.error(response.data.message || '<?php _e("Error al cargar el historial de cambios de estado", "wp-pos"); ?>');
                }
            },
            error: function() {
                WP_POS_Notifications.error('<?php _e("Error de conexi\u00f3n al cargar el historial de cambios de estado", "wp-pos"); ?>');
            }
        });
    }
    
    // Funciu00f3n para renderizar historial de cambios de estado
    function renderStatusHistory(statusHistory) {
        if (!statusHistory || statusHistory.length === 0) {
            $('#status-history-list').html('<tr><td colspan="4"><?php _e("No hay historial disponible", "wp-pos"); ?></td></tr>');
            return;
        }
        
        var html = '';
        $.each(statusHistory, function(index, item) {
            html += '<tr>';
            html += '<td>' + formatDate(item.date) + '</td>';
            html += '<td><span class="status-label status-' + item.status + '">' + getStatusText(item.status) + '</span></td>';
            html += '<td>' + (item.user_name || '-') + '</td>';
            html += '<td>' + (item.justification ? '<div class="status-justification">' + item.justification + '</div>' : '-') + '</td>';
            html += '</tr>';
        });
        
        $('#status-history-list').html(html);
    }
    
    // Funciones auxiliares
    function formatDate(dateString) {
        if (!dateString) return '-';
        var date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function formatCurrency(amount) {
        return '$' + parseFloat(amount || 0).toFixed(2);
    }
    
    function getStatusText(status) {
        switch (status) {
            case 'pending': return '<?php _e("Pendiente", "wp-pos"); ?>';
            case 'approved': return '<?php _e("Aprobado", "wp-pos"); ?>';
            case 'rejected': return '<?php _e("Rechazado", "wp-pos"); ?>';
            default: return status;
        }
    }
});
</script>

<script>
// Cargar el script de correcciju00f3n para el historial de cierres
jQuery(document).ready(function($) {
    // Agregar control de cierres duplicados
    var displayedClosureIds = {};
    
    // Sobrescribir la funciu00f3n renderClosuresList para evitar duplicados
    var originalRenderFunction = renderClosuresList;
    renderClosuresList = function(closures) {
        if (!closures || closures.length === 0) {
            $('#closures-list').html('<tr><td colspan="9" class="no-items">No se encontraron cierres de caja</td></tr>');
            return;
        }
        
        // Filtrar cierres duplicados
        var uniqueClosures = [];
        var seenIds = {};
        
        closures.forEach(function(closure) {
            if (!seenIds[closure.id]) {
                seenIds[closure.id] = true;
                uniqueClosures.push(closure);
            }
        });
        
        // Llamar a la funciu00f3n original con cierres u00fanicos
        originalRenderFunction(uniqueClosures);
        console.log('Historial renderizado con ' + uniqueClosures.length + ' cierres u00fanicos');
    };
    
    // Sobrescribir el comportamiento de cargar la lista para reiniciar cache
    var originalLoadFunction = loadClosuresList;
    loadClosuresList = function() {
        // Reiniciar cache al cambiar pu00e1gina o filtros
        displayedClosureIds = {};
        // Llamar a la funciu00f3n original
        originalLoadFunction.apply(this, arguments);
    };
});
</script>
