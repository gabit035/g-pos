<?php
/**
 * Vista de formulario para Cierre de Caja
 *
 * @package WP-POS
 * @subpackage Closures
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) exit;

// Cargar estilos específicos para el formulario de cierres
wp_enqueue_style('wp-pos-closures-form', plugin_dir_url(dirname(__FILE__)) . 'assets/css/closures-form.css', [], '1.0.0');

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
                        <button type="button" id="refresh-total-amount" class="button button-small" title="<?php _e('Actualizar Total Efectivo', 'wp-pos'); ?>">
                            <span class="dashicons dashicons-update"></span>
                        </button>
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
                <div class="wp-pos-input-with-icon">
                    <input type="number" id="difference-amount" name="difference" readonly>
                    <span class="wp-pos-input-icon dashicons dashicons-chart-line"></span>
                </div>
            </div>
        </div>
        
        <!-- Desglose por método de pago -->
        <div class="wp-pos-form-section-title">
            <h3><?php _e('Desglose por método de pago', 'wp-pos'); ?></h3>
        </div>
        
        <div class="wp-pos-payment-methods-breakdown">
            <div class="wp-pos-form-row">
                <div class="wp-pos-form-column">
                    <div class="wp-pos-form-field">
                        <label for="payment-method-cash"><?php _e('Efectivo', 'wp-pos'); ?></label>
                        <div class="wp-pos-input-with-icon">
                            <input type="number" id="payment-method-cash" name="payment_methods[cash]" value="0" min="0" step="0.01">
                            <span class="wp-pos-input-icon dashicons dashicons-money-alt"></span>
                        </div>
                    </div>
                </div>
                <div class="wp-pos-form-column">
                    <div class="wp-pos-form-field">
                        <label for="payment-method-credit"><?php _e('Crédito', 'wp-pos'); ?></label>
                        <div class="wp-pos-input-with-icon">
                            <input type="number" id="payment-method-credit" name="payment_methods[credit]" value="0" min="0" step="0.01">
                            <span class="wp-pos-input-icon dashicons dashicons-credit-card"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="wp-pos-form-row">
                <div class="wp-pos-form-column">
                    <div class="wp-pos-form-field">
                        <label for="payment-method-debit"><?php _e('Débito', 'wp-pos'); ?></label>
                        <div class="wp-pos-input-with-icon">
                            <input type="number" id="payment-method-debit" name="payment_methods[debit]" value="0" min="0" step="0.01">
                            <span class="wp-pos-input-icon dashicons dashicons-card"></span>
                        </div>
                    </div>
                </div>
                <div class="wp-pos-form-column">
                    <div class="wp-pos-form-field">
                        <label for="payment-method-transfer"><?php _e('Transferencia', 'wp-pos'); ?></label>
                        <div class="wp-pos-input-with-icon">
                            <input type="number" id="payment-method-transfer" name="payment_methods[transfer]" value="0" min="0" step="0.01">
                            <span class="wp-pos-input-icon dashicons dashicons-bank"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección de Egresos (Repeater) -->
        <div class="wp-pos-form-section-title">
            <h3><?php _e('Egresos', 'wp-pos'); ?></h3>
            <small><?php _e('Registre los gastos realizados durante el día.', 'wp-pos'); ?></small>
        </div>
        
        <div id="expenses-container" class="wp-pos-repeater-container">
            <!-- Los elementos se agregarán dinámicamente -->
        </div>
        
        <div class="wp-pos-repeater-actions">
            <button type="button" id="add-expense" class="wp-pos-button wp-pos-button-secondary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Añadir Egreso', 'wp-pos'); ?>
            </button>
            <div class="wp-pos-repeater-total">
                <span><?php _e('Total Egresos:', 'wp-pos'); ?></span>
                <span id="expenses-total">$0.00</span>
                <input type="hidden" name="expenses_total" id="expenses-total-input" value="0">
            </div>
        </div>
        
        <!-- Sección de Ingresos (Repeater) -->
        <div class="wp-pos-form-section-title">
            <h3><?php _e('Ingresos', 'wp-pos'); ?></h3>
            <small><?php _e('Registre los ingresos adicionales durante el día.', 'wp-pos'); ?></small>
        </div>
        
        <div id="income-container" class="wp-pos-repeater-container">
            <!-- Los elementos se agregarán dinámicamente -->
        </div>
        
        <div class="wp-pos-repeater-actions">
            <button type="button" id="add-income" class="wp-pos-button wp-pos-button-secondary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Añadir Ingreso', 'wp-pos'); ?>
            </button>
            <div class="wp-pos-repeater-total">
                <span><?php _e('Total Ingresos:', 'wp-pos'); ?></span>
                <span id="income-total">$0.00</span>
                <input type="hidden" name="income_total" id="income-total-input" value="0">
            </div>
        </div>
        
        <!-- Total del día -->
        <div class="wp-pos-form-section-title">
            <h3><?php _e('Total del Día', 'wp-pos'); ?></h3>
        </div>
        
        <div class="wp-pos-form-row">
            <div class="wp-pos-form-field wp-pos-full-width">
                <label for="day-total"><?php _e('Total del Día', 'wp-pos'); ?></label>
                <div class="wp-pos-input-with-icon">
                    <input type="number" id="day-total" name="day_total" readonly>
                    <span class="wp-pos-input-icon dashicons dashicons-money-alt"></span>
                </div>
            </div>
        </div>
        
        <div class="wp-pos-form-actions">
            <button type="button" id="diagnose-sales" class="wp-pos-button wp-pos-button-secondary">
                <span class="dashicons dashicons-search" style="margin-top: 3px; margin-right: 5px;"></span>
                <?php _e('Diagnóstico de ventas', 'wp-pos'); ?>
            </button>
            <button type="button" id="calculate-amounts" class="wp-pos-button wp-pos-button-secondary">
                <span class="dashicons dashicons-calculator" style="margin-top: 3px; margin-right: 5px;"></span>
                <?php _e('Calcular montos', 'wp-pos'); ?>
            </button>
            <button type="submit" class="wp-pos-button wp-pos-button-primary">
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
    
    // Contadores para los campos repeater
    var expenseCounter = 0;
    var incomeCounter = 0;
    
    // Función auxiliar para ocultar todos los indicadores de carga activos
    function hideAllLoaders() {
        for (var key in loaders) {
            if (loaders[key]) {
                try {
                    WP_POS_LoaderManager.remove(loaders[key]);
                    loaders[key] = null;
                } catch (e) {
                    console.error('Error al ocultar el loader:', e);
                }
            }
        }
        
        // A veces puede haber restos de loaders, los limpiamos manualmente
        try {
            // Limpieza de emergencia
            $('.wp-pos-loading, .wp-pos-loading-container, .blockUI, .blockOverlay').remove();
        } catch (e) {}
    }
    
    // Función para añadir un nuevo campo de egreso
    function addExpenseField(description = '', amount = '') {
        var template = `
            <div class="wp-pos-repeater-item" data-id="${expenseCounter}">
                <div class="wp-pos-repeater-content">
                    <div class="wp-pos-repeater-field-group">
                        <div class="wp-pos-repeater-field">
                            <input type="text" class="expense-description" name="expenses[${expenseCounter}][description]" 
                                   placeholder="Descripción" value="${description}" required>
                        </div>
                        <div class="wp-pos-repeater-field">
                            <div class="wp-pos-input-with-icon">
                                <input type="number" class="expense-amount" name="expenses[${expenseCounter}][amount]" 
                                       placeholder="0.00" value="${amount}" min="0" step="0.01" required>
                                <span class="wp-pos-input-icon dashicons dashicons-money-alt"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wp-pos-repeater-actions">
                    <button type="button" class="remove-expense wp-pos-button wp-pos-button-icon">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        `;
        
        $('#expenses-container').append(template);
        expenseCounter++;
        recalculateTotals();
    }
    
    // Función para añadir un nuevo campo de ingreso
    function addIncomeField(description = '', amount = '') {
        var template = `
            <div class="wp-pos-repeater-item" data-id="${incomeCounter}">
                <div class="wp-pos-repeater-content">
                    <div class="wp-pos-repeater-field-group">
                        <div class="wp-pos-repeater-field">
                            <input type="text" class="income-description" name="incomes[${incomeCounter}][description]" 
                                   placeholder="Descripción" value="${description}" required>
                        </div>
                        <div class="wp-pos-repeater-field">
                            <div class="wp-pos-input-with-icon">
                                <input type="number" class="income-amount" name="incomes[${incomeCounter}][amount]" 
                                       placeholder="0.00" value="${amount}" min="0" step="0.01" required>
                                <span class="wp-pos-input-icon dashicons dashicons-money-alt"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wp-pos-repeater-actions">
                    <button type="button" class="remove-income wp-pos-button wp-pos-button-icon">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        `;
        
        $('#income-container').append(template);
        incomeCounter++;
        recalculateTotals();
    }
    
    // Función para recalcular todos los totales
    function recalculateTotals() {
        // Calcular total de egresos con validación numérica
        var expensesTotal = 0;
        $('.expense-amount').each(function() {
            var value = parseFloat($(this).val());
            if (!isNaN(value) && value >= 0) {
                expensesTotal += value;
            }
        });
        
        // Calcular total de ingresos con validación numérica
        var incomeTotal = 0;
        $('.income-amount').each(function() {
            var value = parseFloat($(this).val());
            if (!isNaN(value) && value >= 0) {
                incomeTotal += value;
            }
        });
        
        // Actualizar totales mostrados con 2 decimales fijos
        expensesTotal = parseFloat(expensesTotal.toFixed(2));
        incomeTotal = parseFloat(incomeTotal.toFixed(2));
        
        $('#expenses-total').text(formatMoney(expensesTotal));
        $('#expenses-total-input').val(expensesTotal);
        
        $('#income-total').text(formatMoney(incomeTotal));
        $('#income-total-input').val(incomeTotal);
        
        // Si no hay un monto total o es cero, llamamos primero al servidor para obtenerlo
        var totalAmount = parseFloat($('#total-amount').val());
        if (isNaN(totalAmount) || totalAmount === 0) {
            console.log('El campo total-amount no tiene valor válido. Solicitando cálculo al servidor.');
            calculateAmounts(function() {
                updateTotals(incomeTotal, expensesTotal);
            });
        } else {
            updateTotals(incomeTotal, expensesTotal);
        }
    }
    
    // Función para actualizar los totales una vez que tenemos el monto base
    function updateTotals(incomeTotal, expensesTotal) {
        // Obtener los valores de métodos de pago
        var cashAmount = parseFloat($('#payment-method-cash').val()) || 0;
        var creditAmount = parseFloat($('#payment-method-credit').val()) || 0;
        var debitAmount = parseFloat($('#payment-method-debit').val()) || 0;
        var transferAmount = parseFloat($('#payment-method-transfer').val()) || 0;
        
        // Obtener el monto total de ventas (validando que exista)
        var totalAmount = parseFloat($('#total-amount').val()) || 0;
        if (totalAmount === 0) {
            console.log('ADVERTENCIA: El total de ventas es cero, posible error en cálculo.');
        }
        
        // Calcular total del día considerando ingresos y egresos
        var dayTotal = totalAmount + incomeTotal - expensesTotal;
        dayTotal = parseFloat(dayTotal.toFixed(2)); // Formato con 2 decimales exactos
        
        // Actualizar el total del día
        $('#day-total').val(dayTotal);
        console.log('Total del día calculado: ' + dayTotal + ' (Ventas: ' + totalAmount + ', Ingresos: ' + incomeTotal + ', Egresos: ' + expensesTotal + ')');
        
        // Verificar si el total coincide con la suma de métodos de pago
        var paymentMethodsTotal = cashAmount + creditAmount + debitAmount + transferAmount;
        paymentMethodsTotal = parseFloat(paymentMethodsTotal.toFixed(2));
        
        // Mostrar advertencia si los métodos de pago no suman el total esperado
        if (Math.abs(paymentMethodsTotal - dayTotal) > 0.01) {
            console.log('Advertencia: Los métodos de pago (' + paymentMethodsTotal + ') no coinciden con el total del día (' + dayTotal + ')');
        }
        
        // Recalcular diferencia entre contado y calculado
        calculateDifference();
        
        // Dar feedback visual al usuario según los valores
        updateVisualFeedback();
    }
    
    // Eventos para los botones de añadir
    $('#add-expense').on('click', function() {
        addExpenseField();
    });
    
    $('#add-income').on('click', function() {
        addIncomeField();
    });
    
    // Delegación de eventos para eliminar ítems
    $(document).on('click', '.remove-expense', function() {
        $(this).closest('.wp-pos-repeater-item').remove();
        recalculateTotals();
    });
    
    $(document).on('click', '.remove-income', function() {
        $(this).closest('.wp-pos-repeater-item').remove();
        recalculateTotals();
    });
    
    // Delegación de eventos para actualizar totales cuando cambian los montos
    $(document).on('input', '.expense-amount, .income-amount', function() {
        recalculateTotals();
    });
    
    // Inicializar con al menos un campo de egreso e ingreso vacío
    addExpenseField();
    addIncomeField();
    
    // Función para calcular la diferencia entre el monto real contado y el esperado
    function calculateDifference() {
        // Obtener valores con validación numérica
        var expectedAmount = parseFloat($('#expected-amount').val()) || 0;
        var countedAmount = parseFloat($('#counted-amount').val()) || 0;
        
        // Calcular la diferencia con precisión de 2 decimales
        var difference = parseFloat((countedAmount - expectedAmount).toFixed(2));
        
        // Actualizar campo de diferencia
        $('#difference-amount').val(difference);
        
        // Aplicar clases según el valor (positivo, negativo o cero)
        if (difference > 0) {
            $('#difference-amount').removeClass('wp-pos-negative').addClass('wp-pos-positive');
        } else if (difference < 0) {
            $('#difference-amount').removeClass('wp-pos-positive').addClass('wp-pos-negative');
        } else {
            $('#difference-amount').removeClass('wp-pos-positive wp-pos-negative');
        }
        
        console.log('Diferencia calculada: ' + difference + ' (Contado: ' + countedAmount + ', Esperado: ' + expectedAmount + ')');
        return difference;
    }
    
    // Función para actualizar feedback visual en los campos
    function updateVisualFeedback() {
        // Actualizar apariencia del total del día según su valor
        var dayTotal = parseFloat($('#day-total').val()) || 0;
        if (dayTotal > 0) {
            $('#day-total').removeClass('wp-pos-negative').addClass('wp-pos-positive');
        } else if (dayTotal < 0) {
            $('#day-total').removeClass('wp-pos-positive').addClass('wp-pos-negative');
        } else {
            $('#day-total').removeClass('wp-pos-positive wp-pos-negative');
        }
        
        // Conectar eventos para que se recalculen valores al cambiar los campos principales
        $('#counted-amount, #expected-amount').off('input.difference').on('input.difference', function() {
            calculateDifference();
        });
        
        // Conectar eventos para métodos de pago
        $('#payment-method-cash, #payment-method-credit, #payment-method-debit, #payment-method-transfer').off('input.payments')
            .on('input.payments', function() {
                validatePaymentMethods();
            });
    }
    
    // Función para validar que los métodos de pago sumen el total esperado
    function validatePaymentMethods() {
        var cashAmount = parseFloat($('#payment-method-cash').val()) || 0;
        var creditAmount = parseFloat($('#payment-method-credit').val()) || 0;
        var debitAmount = parseFloat($('#payment-method-debit').val()) || 0;
        var transferAmount = parseFloat($('#payment-method-transfer').val()) || 0;
        
        var methodsTotal = parseFloat((cashAmount + creditAmount + debitAmount + transferAmount).toFixed(2));
        var dayTotal = parseFloat($('#day-total').val()) || 0;
        
        // Si hay diferencias mayores a 1 cent, mostrar advertencia
        if (Math.abs(methodsTotal - dayTotal) > 0.01) {
            console.log('Alerta: Los métodos de pago suman ' + methodsTotal + ' pero el total del día es ' + dayTotal);
            // Se podría mostrar una advertencia visual aquí
        }
    }

    // Asignar la fecha actual como predeterminada si no hay valor
    if (!$('#closure-date').val()) {
        var today = new Date();
        var formattedDate = today.toISOString().substring(0, 10);
        $('#closure-date').val(formattedDate);
    }
    
    // Inicializar cálculos y feedback visual
    updateVisualFeedback();
    calculateDifference();
    
    // Actualizar cálculos cuando cambie la fecha, registro o usuario
    $('#closure-date, #closure-register').on('change', function() {
        calculateAmounts();
    });
    
    // Manejador específico para cambios en el selector de usuario
    $('#closure-user').on('change', function() {
        var selectedUserId = $(this).val();
        console.log('Usuario cambiado a:', selectedUserId);
        
        // Mostrar indicador visual de carga
        $('#total-amount').val('Calculando...');
        $('#total-amount').addClass('wp-pos-loading-field');
        
        // Forzar recálculo con usuario específico
        forceRecalculateWithUser(selectedUserId);
    });
    
    // Función especializada para recalcular con usuario específico
    function forceRecalculateWithUser(userId) {
        var date = $('#closure-date').val();
        var register_id = $('#closure-register').val();
        
        if (!date || !register_id) {
            // Mostrar un mensaje de error si faltan datos necesarios
            WP_POS_Notifications.warning('Se requieren fecha y caja para calcular los totales');
            $('#total-amount').val(0);
            $('#total-amount').removeClass('wp-pos-loading-field');
            return;
        }
        
        console.log('Forzando recálculo con usuario:', userId);
        
        // Desactivar temporalmente los campos relacionados
        $('#closure-user, #closure-date, #closure-register').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo $ajax_url; ?>',
            type: 'POST',
            data: {
                action: 'wp_pos_closures_calculate_amounts',
                nonce: '<?php echo $ajax_nonce; ?>',
                date: date,
                register_id: register_id,
                user_id: userId
            },
            success: function(response) {
                console.log('Respuesta de recálculo por usuario:', response);
                $('#total-amount').removeClass('wp-pos-loading-field');
                
                if (response.success) {
                    // Almacenar valor anterior para comparar si hubo cambio
                    var previousTotal = parseFloat($('#total-amount').val()) || 0;
                    var newTotal = parseFloat(response.data.total_transactions) || 0;
                    
                    // Actualizar los valores en los campos
                    $('#initial-amount').val(response.data.initial_amount);
                    $('#total-amount').val(response.data.total_transactions);
                    $('#expected-amount').val(response.data.expected_amount);
                    
                    // Destacar visualmente el cambio si hay diferencia
                    if (previousTotal !== newTotal) {
                        $('#total-amount').addClass('wp-pos-highlight-change');
                        setTimeout(function() {
                            $('#total-amount').removeClass('wp-pos-highlight-change');
                        }, 2000);
                    }
                    
                    // Mostrar mensaje informativo sobre la actualización
                    WP_POS_Notifications.success('Total en efectivo actualizado correctamente');
                    
                    // Actualizar cálculos derivados
                    calculateDifference();
                    updateVisualFeedback();
                    recalculateTotals();
                } else {
                    // Mensaje de error con información detallada si está disponible
                    var errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : 'Error desconocido al recalcular totales';
                    
                    WP_POS_Notifications.error(errorMsg);
                    console.error('Error en recálculo:', errorMsg);
                    
                    // Mantener el valor anterior si existía
                    if (!$('#total-amount').val() || $('#total-amount').val() === 'Calculando...') {
                        $('#total-amount').val('0');
                    }
                }
            },
            error: function(xhr) {
                $('#total-amount').removeClass('wp-pos-loading-field');
                console.error('Error en recálculo por usuario:', xhr.responseText);
                WP_POS_Notifications.error('Error de conexión al procesar la solicitud');
                
                // Mantener el valor anterior si existía
                if (!$('#total-amount').val() || $('#total-amount').val() === 'Calculando...') {
                    $('#total-amount').val('0');
                }
            },
            complete: function() {
                // Reactivar los campos
                $('#closure-user, #closure-date, #closure-register').prop('disabled', false);
            }
        });
    }
    
    // Botón de actualización manual del total efectivo
    $('#refresh-total-amount').on('click', function() {
        // Mostrar mensaje de actualización
        WP_POS_Notifications.info('Actualizando total efectivo...');
        
        // Destacar visualmente el campo que se está actualizando
        $('#total-amount').addClass('wp-pos-loading-field');
        
        // Obtener el usuario seleccionado actualmente
        var selectedUserId = $('#closure-user').val();
        
        // Si hay un usuario seleccionado específico, usar la función especializada
        if (selectedUserId && selectedUserId > 0) {
            forceRecalculateWithUser(selectedUserId);
        } else {
            // Si no hay usuario específico, usar la función normal
            calculateAmounts(function() {
                // Mensaje de éxito después de actualizar
                $('#total-amount').removeClass('wp-pos-loading-field');
                WP_POS_Notifications.success('Total efectivo actualizado correctamente');
                // Recalcular todos los totales para asegurarnos de que estén sincronizados
                recalculateTotals();
            });
        }
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
    function calculateAmounts(callback) {
        var date = $('#closure-date').val();
        var register_id = $('#closure-register').val();
        var user_id = $('#closure-user').val();
        
        // Depurar valores enviados
        console.log('Enviando parámetros a calculateAmounts:', {
            date: date,
            register_id: register_id,
            user_id: user_id
        });
        
        if (!date || !register_id) {
            if (typeof callback === 'function') callback();
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
                    console.log('Datos recibidos del servidor:', data);
                    
                    // Recalcular diferencia
                    calculateDifference();
                    
                    // Ejecutar callback si existe
                    if (typeof callback === 'function') {
                        callback();
                    }
                } else {
                    WP_POS_Notifications.error(response.data.message || 'Error al calcular los montos');
                    if (typeof callback === 'function') callback();
                }
            },
            error: function() {
                WP_POS_Notifications.error('Error de conexión al calcular los montos');
                if (typeof callback === 'function') callback();
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
    
    // Añadir estilos CSS para los efectos visuales de feedback
    $('<style>' +
        '.wp-pos-loading-field {' +
        '    background-color: #f0f0f0 !important;' +
        '    color: #999 !important;' +
        '    transition: all 0.3s ease;' +
        '}' +
        '.wp-pos-highlight-change {' +
        '    background-color: #fffacd !important;' +
        '    transition: background-color 2s ease;' +
        '}' +
    '</style>').appendTo('head');

    // Ejecutar primer cálculo después de cargar la página
    // Esto asegura que los totales iniciales sean correctos
    setTimeout(function() {
        calculateAmounts();
    }, 500);
});
</script>
