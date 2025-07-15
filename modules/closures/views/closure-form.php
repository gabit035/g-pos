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
                    <label for="initial-amount"><?php _e('Caja Inicial', 'wp-pos'); ?></label>
                    <div class="wp-pos-input-with-icon">
                        <input type="number" id="initial-amount" name="initial_amount" min="0" step="0.01" required placeholder="0.00" value="0.00">
                        <span class="wp-pos-input-icon dashicons dashicons-money-alt"></span>
                    </div>
                    <small class="wp-pos-field-description" style="display:block; margin-top:5px; color:#666;">Ingrese manualmente el monto inicial de caja.</small>
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

        <!-- Desglose de otros métodos de pago del día (solo lectura) -->
        <div class="wp-pos-form-field" id="payment-method-breakdown-container">
            <label>
                <?php _e('Desglose otros métodos de pago del día', 'wp-pos'); ?>
                <span class="dashicons dashicons-update" id="refresh-breakdown" title="Actualizar desglose" style="cursor:pointer; font-size:18px; vertical-align:text-bottom; color:#0073aa;"></span>
            </label>
            <div id="payment-method-breakdown" style="background:#f9f9f9; border:1px solid #e1e1e1; border-radius:4px; padding:15px; margin-bottom:15px; font-size:15px; box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                <em><?php _e('Cargando desglose...', 'wp-pos'); ?></em>
            </div>
            <!-- Campo oculto para guardar el desglose de pagos en formato JSON -->
            <input type="hidden" name="payment_breakdown" id="payment_breakdown" value="">
            
            <!-- Campos ocultos para guardar en el historial -->
            <input type="hidden" id="payment_breakdown_stored" name="payment_breakdown_stored" value="">
        </div>
        
        <div class="wp-pos-form-actions">

            <button type="button" id="calculate-amounts" class="button button-primary button-large">
                <span class="dashicons dashicons-calculator"></span>
                <?php _e('Calcular Montos', 'wp-pos'); ?>
            </button>
            <button type="submit" class="button button-primary button-large">
                <span class="dashicons dashicons-yes-alt"></span>
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
                                   placeholder="Descripción" value="${description}">
                        </div>
                        <div class="wp-pos-repeater-field">
                            <div class="wp-pos-input-with-icon">
                                <input type="number" class="expense-amount" name="expenses[${expenseCounter}][amount]" 
                                       placeholder="0.00" value="${amount}" min="0" step="0.01">
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
                                   placeholder="Descripción" value="${description}">
                        </div>
                        <div class="wp-pos-repeater-field">
                            <div class="wp-pos-input-with-icon">
                                <input type="number" class="income-amount" name="incomes[${incomeCounter}][amount]" 
                                       placeholder="0.00" value="${amount}" min="0" step="0.01">
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
        var cardAmount = parseFloat($('#payment-method-card').val()) || 0;
        var transferAmount = parseFloat($('#payment-method-transfer').val()) || 0;
        var checkAmount = parseFloat($('#payment-method-check').val()) || 0;
        var otherAmount = parseFloat($('#payment-method-other').val()) || 0;
        
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
        var paymentMethodsTotal = cashAmount + cardAmount + transferAmount + checkAmount + otherAmount;
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
    
    // Función para crear campos de método de pago dinámicamente si no existen
    function createPaymentMethodFieldIfNeeded(method, amount) {
        // Verificar si ya existe un campo para este método
        if ($('input[data-method="' + method + '"]').length === 0) {
            var methodLabel = method.charAt(0).toUpperCase() + method.slice(1);
            var methodIcon = 'admin-generic';
            
            // Mapeo de iconos conocidos
            var iconMap = {
                'cash': 'money-alt',
                'card': 'credit-card',
                'transfer': 'bank',
                'check': 'money',
                'other': 'admin-generic'
            };
            
            if (iconMap[method]) {
                methodIcon = iconMap[method];
            }
            
            // Obtener el contenedor de métodos de pago
            var $container = $('#dynamic-payment-methods');
            var $lastRow = $container.find('.wp-pos-form-row').last();
            
            // Si la última fila tiene 2 columnas o no hay filas, crear una nueva fila
            if ($lastRow.length === 0 || $lastRow.find('.wp-pos-form-column').length >= 2) {
                $lastRow = $('<div class="wp-pos-form-row"></div>');
                $container.append($lastRow);
            }
            
            // Crear la nueva columna con el campo de entrada
            var $column = $('<div class="wp-pos-form-column"></div>');
            var $field = $('<div class="wp-pos-form-field"></div>');
            var $label = $('<label for="payment-method-' + method + '">' + methodLabel + '</label>');
            var $inputContainer = $('<div class="wp-pos-input-with-icon"></div>');
            var $input = $('<input type="number" id="payment-method-' + method + '" name="payment_methods[' + method + ']" value="' + parseFloat(amount).toFixed(2) + '" min="0" step="0.01" class="payment-method-input" data-method="' + method + '">');
            var $icon = $('<span class="wp-pos-input-icon dashicons dashicons-' + methodIcon + '"></span>');
            
            // Construir la estructura del DOM
            $inputContainer.append($input).append($icon);
            $field.append($label).append($inputContainer);
            $column.append($field);
            $lastRow.append($column);
        } else {
            // Si el campo ya existe, solo actualizar el valor
            $('input[data-method="' + method + '"]').val(parseFloat(amount).toFixed(2));
        }
    }
    
    // Manejar cambios en los campos de métodos de pago (delegación de eventos para elementos dinámicos)
    $(document).on('input', '.payment-method-input', function() {
        recalculateTotals();
    });
    
    // Inicializar con al menos un campo de egreso e ingreso vacío
    addExpenseField();
    addIncomeField();
    
    // Función para recalcular el monto esperado cuando cambia el monto inicial manualmente
    function recalculateExpectedAmount() {
        // Obtener el monto inicial (ingresado por el usuario) - asegurar que sea un número válido o 0
        var initialAmountInput = $('#initial-amount').val().trim();
        var initialAmount = initialAmountInput === '' || isNaN(initialAmountInput) ? 0 : parseFloat(initialAmountInput);
        
        // Asegurarse de que el campo tenga un valor válido
        if (isNaN(initialAmount) || initialAmount < 0) {
            initialAmount = 0;
            $('#initial-amount').val('0.00');
        }
        
        // Obtener el total de transacciones - asegurar que sea un número válido o 0
        var totalAmount = parseFloat($('#total-amount').val()) || 0;
        
        // Calcular el monto esperado = monto inicial + total transacciones
        var expectedAmount = initialAmount + totalAmount;
        
        // Actualizar el campo del monto esperado
        $('#expected-amount').val(expectedAmount.toFixed(2));
        
        // Dar feedback visual breve para mostrar que cambió
        $('#expected-amount').addClass('wp-pos-highlight-change');
        setTimeout(function() {
            $('#expected-amount').removeClass('wp-pos-highlight-change');
        }, 1500);
        
        // Recalcular la diferencia con el nuevo monto esperado
        calculateDifference();
        
        console.log('Monto esperado recalculado: ' + expectedAmount.toFixed(2) + 
                   ' (Inicial: ' + initialAmount.toFixed(2) + 
                   ' + Total: ' + totalAmount.toFixed(2) + ')');
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
        
        // Recalcular monto esperado cuando cambia manualmente el monto inicial
        $('#initial-amount').off('input.expected').on('input.expected', function() {
            recalculateExpectedAmount();
        });
        
        // Conectar eventos para métodos de pago
        $('#payment-method-cash, #payment-method-card, #payment-method-transfer, #payment-method-check, #payment-method-other').off('input.payments')
            .on('input.payments', function() {
                validatePaymentMethods();
            });
    }
    
    // Función para validar que los métodos de pago sumen el total esperado
    function validatePaymentMethods() {
        var cashAmount = parseFloat($('#payment-method-cash').val()) || 0;
        var cardAmount = parseFloat($('#payment-method-card').val()) || 0;
        var transferAmount = parseFloat($('#payment-method-transfer').val()) || 0;
        var checkAmount = parseFloat($('#payment-method-check').val()) || 0;
        var otherAmount = parseFloat($('#payment-method-other').val()) || 0;
        
        var methodsTotal = parseFloat((cashAmount + cardAmount + transferAmount + checkAmount + otherAmount).toFixed(2));
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
    
    // Calcular montos automáticamente al cargar la página
    calculateAmounts(function() {
        // Una vez calculados los montos, actualizar el monto esperado
        recalculateExpectedAmount();
    });
    
    // Actualizar cálculos cuando cambie la fecha, registro o usuario
    $('#closure-register, #closure-date').on('change', function() {
        // Recalcular montos cuando cambia algún filtro
        calculateAmounts();
    });
    
    // Manejador para cambios en el selector de usuario
    $('#closure-user').on('change', function() {
        var userId = $(this).val();
        // Mostrar indicador de carga
        $('#expected-amount').addClass('loading');
        
        // Recalcular montos basados en el usuario seleccionado
        if (userId) {
            forceRecalculateWithUser(userId);
        } else {
            calculateAmounts();
        }
    });
    // Función especializada para recalcar con usuario específico
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
                    // Usar total_amount que ahora contiene solo el efectivo
                    var cashTotal = response.data.total_amount || '0.00';
                    $('#total-amount').val(cashTotal);
                    $('#expected-amount').val(response.data.expected_amount);
                    
                    // Actualizar campos por método de pago si están disponibles
                    if (response.data.payment_methods) {
                        $('#payment-method-cash').val(response.data.payment_methods.cash || '0.00');
                        $('#payment-method-card').val(response.data.payment_methods.card || '0.00');
                        $('#payment-method-transfer').val(response.data.payment_methods.transfer || '0.00');
                        $('#payment-method-check').val(response.data.payment_methods.check || '0.00');
                        $('#payment-method-other').val(response.data.payment_methods.other || '0.00');
                        
                        console.log('Métodos de pago actualizados:', response.data.payment_methods);
                    }
                    
                    // Destacar visualmente el cambio si hay diferencia
                    if (previousTotal !== newTotal) {
                        $('#total-amount').addClass('wp-pos-highlight-change');
                        setTimeout(function() {
                            $('#total-amount').removeClass('wp-pos-highlight-change');
                        }, 2000);
                    }
                    
                    // Mostrar mensaje informativo sobre la actualización
                    WP_POS_Notifications.success('Total en efectivo actualizado correctamente');
                    
                    // Actualizar solo la diferencia y feedback visual
                    // No llamar a recalculateTotals() aquí para evitar sumar todos los métodos
                    calculateDifference();
                    updateVisualFeedback();
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
    
    // CONFIGURACIÓN DE ACTUALIZACIÓN AUTOMÁTICA
    window.WP_POS_CONFIG = window.WP_POS_CONFIG || {};
    window.WP_POS_CONFIG.autoCalculate = window.WP_POS_CONFIG.autoCalculate !== false; // Por defecto habilitado
    window.WP_POS_CONFIG.autoCalculateDelay = window.WP_POS_CONFIG.autoCalculateDelay || 1000; // 1 segundo por defecto
    window.WP_POS_CONFIG.autoCleanup = window.WP_POS_CONFIG.autoCleanup !== false; // Por defecto habilitado
    window.WP_POS_CONFIG.cleanupInterval = window.WP_POS_CONFIG.cleanupInterval || 30000; // 30 segundos
    
    // Ejecutar primer cálculo después de cargar la página (OPCIONAL)
    // Esto asegura que los totales iniciales sean correctos
    if (window.WP_POS_CONFIG.autoCalculate) {
        console.log(' Cálculo automático inicial habilitado (delay: ' + window.WP_POS_CONFIG.autoCalculateDelay + 'ms)');
        setTimeout(function() {
            calculateAmounts();
            // Cargar desglose de pagos automáticamente al iniciar
            console.log('Cargando desglose de pagos al iniciar...');
            WP_POS_Closures.loadPaymentBreakdown();
        }, window.WP_POS_CONFIG.autoCalculateDelay);
    } else {
        console.log(' Cálculo automático inicial DESHABILITADO');
        console.log(' Usa el botón "Calcular Montos" para actualizar manualmente');
    }
    
    // Configurar eventos para actualizar automáticamente el desglose de pagos
    $('#closure-date, #closure-user').on('change', function() {
        console.log('Cambio detectado en fecha o usuario, actualizando desglose...');
        WP_POS_Closures.loadPaymentBreakdown();
    });
    
    // Escuchar el evento de cálculo de montos para actualizar el desglose
    $(document).on('wp-pos-amounts-calculated', function(e, data) {
        console.log('Evento de cálculo detectado, actualizando desglose automáticamente');
        WP_POS_Closures.loadPaymentBreakdown();
    });
    
    // Botón de actualización manual del desglose de pagos
    $('#refresh-breakdown').on('click', function() {
        console.log('Botón de actualización del desglose clickeado');
        $('#payment-method-breakdown').html('<em><?php _e("Actualizando desglose...", "wp-pos"); ?></em>');
        WP_POS_Closures.loadPaymentBreakdown();
    });
    
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
                    
                    // Actualizar los campos principales
                    // Mostrar solo el total en efectivo (cash_total) en lugar del total de transacciones
                    // $('#initial-amount').val(data.initial_amount); // DESHABILITADO: No calcular automáticamente el monto inicial
                    $('#total-amount').val(data.cash_total || '0.00');
                    $('#expected-amount').val(data.expected_amount);
                    
                    // Actualizar campos por método de pago si están disponibles
                    if (data.payment_methods) {
                        // Resetear todos los campos de métodos de pago a 0
                        $('.payment-method-input').val('0.00');
                        
                        // Actualizar con los valores devueltos
                        $.each(data.payment_methods, function(method, amount) {
                            // Buscar por name o data-method para mayor compatibilidad
                            var input = $('input[name="payment_methods[' + method + ']"], input[data-method="' + method + '"]');
                            if (input.length) {
                                input.val(parseFloat(amount).toFixed(2));
                            } else {
                                // Si no existe el campo, lo creamos dinámicamente
                                createPaymentMethodFieldIfNeeded(method, amount);
                            }
                        });
                    }
                    
                    // Mostrar en consola para depuración
                    console.log('Datos recibidos del servidor:', data);
                    
                    // Recalcular diferencia
                    calculateDifference();
                    
                    // Disparar evento para actualizar automáticamente el desglose de pagos
                    $(document).trigger('wp-pos-amounts-calculated', [{
                        date: date,
                        register_id: register_id,
                        user_id: user_id
                    }]);
                    console.log('Evento wp-pos-amounts-calculated disparado para actualizar desglose');
                    
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
                    // No calcular nuevamente
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
    
    // Vincular el envío del formulario
    $('#wp-pos-closure-form').on('submit', function(e) {
        e.preventDefault();
        saveClosure();
    });
});
</script>
