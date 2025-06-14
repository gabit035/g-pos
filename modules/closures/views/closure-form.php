<?php
/**
 * Vista de formulario para Cierre de Caja
 *
 * @package WP-POS
 * @subpackage Closures
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) exit;

// Obtener la fecha actual en formato Y-m-d para el valor predeterminado
$current_date = current_time('Y-m-d'); // Usa current_time para obtener la fecha con la zona horaria de WordPress

// Obtener usuarios con rol vendedor/cajero
global $wpdb;
$users_query = "SELECT ID, display_name FROM {$wpdb->users} ";
$users_query .= "JOIN {$wpdb->usermeta} ON {$wpdb->users}.ID = {$wpdb->usermeta}.user_id ";
$users_query .= "WHERE {$wpdb->usermeta}.meta_key = '{$wpdb->prefix}capabilities' ";
$users_query .= "AND ({$wpdb->usermeta}.meta_value LIKE '%cashier%' OR {$wpdb->usermeta}.meta_value LIKE '%seller%')";
$users = $wpdb->get_results($users_query);

// Obtener cajas registradoras
$registers = $wpdb->get_results("SELECT id, name, location FROM {$wpdb->prefix}pos_registers ORDER BY name ASC");

// Variables para AJAX
$ajax_url = admin_url('admin-ajax.php');
$ajax_nonce = wp_create_nonce('wp_pos_closures_nonce');
// Get current view for active tab highlighting
$current_view = 'form';
?>

<div class="wrap">
    <?php include_once 'closures-header.php'; ?>
    
    <div class="wp-pos-closure-form-container">
    <form id="wp-pos-closure-form" class="wp-pos-form">
        <div class="wp-pos-form-row">
            <div class="wp-pos-form-column">
                <div class="wp-pos-form-field">
                    <label for="closure-date"><?php _e('Fecha de Cierre', 'wp-pos'); ?></label>
                    <div class="wp-pos-input-with-icon">
                        <input type="date" id="closure-date" name="closure_date" value="<?php echo $current_date; ?>" required>
                        <span class="wp-pos-input-icon dashicons dashicons-calendar-alt"></span>
                    </div>
                </div>
                
                <!-- Campo oculto para la caja registradora con valor predeterminado -->
                <input type="hidden" id="closure-register" name="register_id" value="<?php echo !empty($registers) ? $registers[0]->id : '1'; ?>">
                
                <div class="wp-pos-form-field">
                    <label for="closure-user"><?php _e('Usuario', 'wp-pos'); ?></label>
                    <select id="closure-user" name="user_id">
                        <option value=""><?php _e('Todos', 'wp-pos'); ?></option>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled><?php _e('No hay usuarios Vendedor/Cajero', 'wp-pos'); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="wp-pos-form-field">
                    <label for="initial-amount"><?php _e('Caja Inicial', 'wp-pos'); ?> <span class="dashicons dashicons-info" title="Este valor se obtiene del último cierre, pero puedes modificarlo según sea necesario"></span></label>
                    <div class="wp-pos-input-with-icon">
                        <input type="number" id="initial-amount" name="initial_amount" min="0" step="0.01" required style="background-color: #f9f9f9;">
                        <span class="wp-pos-input-icon dashicons dashicons-money-alt"></span>
                    </div>
                    <small class="wp-pos-field-description" style="display:block; margin-top:5px; color:#666;">El monto inicial se calcula automáticamente basado en el último cierre.</small>
                </div>
            </div>
            
            <div class="wp-pos-form-column">
                <div class="wp-pos-form-field">
                    <label for="total-amount"><?php _e('Total Efectivo', 'wp-pos'); ?></label>
                    <div class="wp-pos-input-with-icon wp-pos-readonly">
                        <input type="text" id="total-amount" name="total_amount" readonly>
                        <span class="wp-pos-input-icon dashicons dashicons-chart-bar"></span>
                    </div>
                </div>
                
                <div class="wp-pos-form-field">
                    <label for="expected-amount"><?php _e('Monto Esperado', 'wp-pos'); ?></label>
                    <div class="wp-pos-input-with-icon wp-pos-readonly">
                        <input type="text" id="expected-amount" name="expected_amount" readonly>
                        <span class="wp-pos-input-icon dashicons dashicons-money-alt"></span>
                    </div>
                </div>
                
                <div class="wp-pos-form-field">
                    <label for="counted-amount"><?php _e('Monto Contado', 'wp-pos'); ?></label>
                    <div class="wp-pos-input-with-icon">
                        <input type="number" id="counted-amount" name="counted_amount" min="0" step="0.01" required>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="wp-pos-form-row">
            <div class="wp-pos-form-field wp-pos-full-width">
                <label for="difference-amount"><?php _e('Diferencia', 'wp-pos'); ?></label>
                <div class="wp-pos-input-with-icon wp-pos-readonly">
                    <input type="text" id="difference-amount" name="difference" readonly>
                    <span class="wp-pos-input-icon dashicons dashicons-calculator"></span>
                </div>
            </div>
        </div>
        
        <div class="wp-pos-form-row">
            <div class="wp-pos-form-field wp-pos-full-width">
                <label for="notes"><?php _e('Notas / Justificación', 'wp-pos'); ?></label>
                <textarea id="notes" name="notes" rows="3" placeholder="<?php _e('Ingrese notas o justificación para la diferencia, si existe', 'wp-pos'); ?>" style="width: 100%;"></textarea>
            </div>
        </div>
        
        <div class="wp-pos-form-actions">
            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-saved" style="margin-top: 3px; margin-right: 5px;"></span>
                <?php _e('Guardar Cierre', 'wp-pos'); ?>
            </button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Control de envíos duplicados
    var isSubmitting = false;
    
    // Variables globales para controlar los indicadores de carga
    var loaders = {
        calculate: null,
        save: null,
        diagnostic: null
    };
    
    // Función auxiliar para ocultar todos los indicadores de carga activos
    function hideAllLoaders() {
        try {
            // Limpiar cargadores registrados
            for (var key in loaders) {
                if (loaders[key]) {
                    WP_POS_LoaderManager.remove(loaders[key]);
                    loaders[key] = null;
                }
            }
            
            // Eliminar cualquier cargador por clase
            if (typeof WP_POS_LoadingIndicator !== 'undefined' && WP_POS_LoadingIndicator.hideAll) {
                WP_POS_LoadingIndicator.hideAll();
            }
            
            // Habilitar botones
            $('.wp-pos-form-actions button').prop('disabled', false);
            
            // Limpieza de emergencia
            $('.wp-pos-loading, .wp-pos-loading-container, .blockUI, .blockOverlay').remove();
        } catch (e) {
            console.error('Error al ocultar cargadores:', e);
            // Limpieza de emergencia
            $('.wp-pos-loading, .wp-pos-loading-container, .blockUI, .blockOverlay').remove();
        }
    }

    // Asignar la fecha actual como predeterminada si no hay valor
    if (!$('#closure-date').val()) {
        var today = new Date();
        var formattedDate = today.toISOString().substring(0, 10);
        $('#closure-date').val(formattedDate);
    }
    
    // Actualizar cálculos cuando cambie la fecha
    $('#closure-date, #closure-register, #closure-user').on('change', function() {
        calculateAmounts();
    });
    
    // Calcular la diferencia cuando cambie el monto contado
    $('#counted-amount').on('input', function() {
        calculateDifference();
    });
    
    // Inicializar con un cálculo
    calculateAmounts();
    
    // Vincular el envío del formulario
    $('#wp-pos-closure-form').on('submit', function(e) {
        e.preventDefault();
        saveClosure();
    });
    
    // Botón de diagnóstico
    $('#diagnose-sales').on('click', function() {
        var date = $('#closure-date').val();
        diagnoseCalculations(date);
    });
    
    // Función para diagnóstico de ventas
    function diagnoseCalculations(date) {
        if (!date) {
            WP_POS_Notifications.warning('Seleccione una fecha válida');
            return;
        }
        
        // Cargar el modal si no está ya en el DOM
        if ($('#diagnostics-modal').length === 0) {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'wp_pos_closures_load_diagnostics_modal',
                    nonce: '<?php echo $ajax_nonce; ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('body').append(response.data.html);
                        // Mostrar el modal y realizar la consulta
                        showDiagnosticsModal(date);
                    }
                }
            });
        } else {
            showDiagnosticsModal(date);
        }
    }
    
    // Función para mostrar el modal y cargar los datos
    function showDiagnosticsModal(date) {
        $('#diagnostics-modal').show();
        $('#diagnostics-content').html(`
            <div class="wp-pos-loading" style="text-align:center; padding:20px;">
                <span class="spinner is-active" style="float:none; width:20px; height:20px; margin:0 auto;"></span>
                <p>Analizando datos de ventas...</p>
            </div>
        `);
        
        // Cargar los datos para la fecha seleccionada
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'wp_pos_closures_diagnostics',
                nonce: '<?php echo $ajax_nonce; ?>',
                date: date,
                register_id: $('#closure-register').val(),
                user_id: $('#closure-user').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#diagnostics-content').html(response.data.html);
                } else {
                    $('#diagnostics-content').html(`
                        <div class="wp-pos-error" style="padding:20px;">
                            <p>${response.data.message || 'Error al cargar los datos de diagnóstico'}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#diagnostics-content').html(`
                    <div class="wp-pos-error" style="padding:20px;">
                        <p>Error de conexión al cargar los datos de diagnóstico</p>
                    </div>
                `);
            }
        });
    }
    
    // Función para calcular la diferencia entre monto esperado y contado
    function calculateDifference() {
        var expected = parseFloat($('#expected-amount').val()) || 0;
        var counted = parseFloat($('#counted-amount').val()) || 0;
        var difference = counted - expected;
        
        // Actualizar el campo de diferencia
        $('#difference-amount').val(difference.toFixed(2));
        
        // Aplicar clases de estilo según el valor
        if (difference < 0) {
            $('#difference-amount').addClass('wp-pos-negative').removeClass('wp-pos-positive');
        } else if (difference > 0) {
            $('#difference-amount').addClass('wp-pos-positive').removeClass('wp-pos-negative');
        } else {
            $('#difference-amount').removeClass('wp-pos-positive wp-pos-negative');
        }
    }
    
    // Función para calcular los montos
    function calculateAmounts() {
        var date = $('#closure-date').val();
        var register_id = $('#closure-register').val();
        var user_id = $('#closure-user').val();
        
        if (!date || !register_id) {
            return;
        }
        
        $.ajax({
            url: '<?php echo $ajax_url; ?>',
            type: 'POST',
            data: {
                action: 'wp_pos_closures_calculate_amounts',
                nonce: '<?php echo $ajax_nonce; ?>',
                date: date,
                register_id: register_id,
                user_id: user_id
            },
            beforeSend: function() {
                // Ocultar todos los indicadores de carga activos
                hideAllLoaders();
                
                // Mostrar indicador de carga para este proceso
                $('.wp-pos-form-actions button').prop('disabled', true);
                
                // Usar el sistema centralizado de gestión de cargadores
                if (loaders.calculate) {
                    // Limpiar cargador existente primero
                    WP_POS_LoaderManager.remove(loaders.calculate);
                }
                
                // Crear un nuevo cargador usando el sistema centralizado
                loaders.calculate = WP_POS_LoaderManager.create('.wp-pos-form-actions', {
                    text: 'Calculando montos...',
                    size: 'medium',
                    overlay: false
                });
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Actualizar los campos
                    $('#initial-amount').val(data.initial_amount);
                    $('#total-amount').val(data.total_transactions);
                    $('#expected-amount').val(data.expected_amount);
                    
                    // Mostrar en consola para depuración
                    console.log('Datos recibidos:', data);
                    
                    // Recalcular diferencia
                    calculateDifference();
                } else {
                    WP_POS_Notifications.error(response.data.message || 'Error al calcular los montos');
                }
            },
            error: function() {
                WP_POS_Notifications.error('Error de conexión al calcular los montos');
            },
            complete: function() {
                // Quitar indicador de carga
                $('.wp-pos-form-actions button').prop('disabled', false);
                hideAllLoaders();
            }
        });
    }
    
    // Función para guardar el cierre
    function saveClosure() {
        // Prevenir envíos duplicados
        if (isSubmitting) {
            console.log('Ya hay un envío en progreso, no se puede enviar nuevamente');
            WP_POS_Notifications.warning('Ya se está procesando la solicitud, espere un momento');
            return false;
        }
        
        // Activar bandera de envío
        isSubmitting = true;
        
        var formData = $('#wp-pos-closure-form').serialize();
        
        $.ajax({
            url: '<?php echo $ajax_url; ?>',
            type: 'POST',
            data: formData + '&action=wp_pos_closures_save_closure&nonce=<?php echo $ajax_nonce; ?>',
            beforeSend: function() {
                // Mostrar indicador de carga mejorado
                $('.wp-pos-form-actions button').prop('disabled', true);
                hideAllLoaders();
                loaders.save = WP_POS_LoaderManager.create('.wp-pos-form-actions', {
                    text: 'Guardando cierre...',
                    size: 'medium',
                    overlay: false
                });
            },
            success: function(response) {
                // Desactivar bandera de envío
                isSubmitting = false;
                
                if (response.success) {
                    WP_POS_Notifications.success(response.data.message || 'Cierre guardado correctamente');
                    // Opcional: redirigir a otra página o restablecer el formulario
                    $('#wp-pos-closure-form')[0].reset();
                    // Establecer la fecha actual nuevamente
                    var today = new Date();
                    var formattedDate = today.toISOString().substring(0, 10);
                    $('#closure-date').val(formattedDate);
                    // Calcular nuevamente
                    calculateAmounts();
                } else {
                    WP_POS_Notifications.error(response.data.message || 'Error al guardar el cierre');
                }
            },
            error: function() {
                // Desactivar bandera de envío en caso de error
                isSubmitting = false;
                WP_POS_Notifications.error('Error de conexión al guardar el cierre');
            },
            complete: function() {
                // Quitar indicador de carga
                $('.wp-pos-form-actions button').prop('disabled', false);
                hideAllLoaders();
            }
        });
    }
});

// Función para formatear valores monetarios para mostrar en la interfaz
function formatMoney(amount) {
    return '$' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Función para formatear fechas para mostrar en la interfaz
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
