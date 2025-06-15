<?php
/**
 * Test directo para verificar qué está devolviendo calculate_payment_method_amount
 * para el método 'cash' con filtro por usuario
 * 
 * Acceder a: /wp-content/plugins/G-POS/modules/closures/test-cash-filter.php
 */

// Cargar WordPress
require_once('../../../../../wp-config.php');

// Desactivado temporalmente verificación de permisos para diagnóstico
// if (!current_user_can('manage_options')) {
//     die('❌ No tienes permisos para ejecutar este script');
// }

echo "<div class='result warning'>
    <h3>⚠️ Modo Diagnóstico</h3>
    <p>Verificación de permisos desactivada temporalmente para diagnóstico del 'Total Efectivo' de Ileana.</p>
</div>";

echo "<h1>🧪 Test: Diagnóstico de Total Efectivo</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .result { background: #f0f0f0; padding: 15px; margin: 10px 0; border-left: 4px solid #007cba; }
    .error { border-left-color: #dc3232; background: #ffebee; }
    .success { border-left-color: #46b450; background: #e8f5e9; }
    .warning { border-left-color: #ffb900; background: #fff8e1; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    .highlight { background-color: #fffbcc; }
</style>";

global $wpdb;

// Parámetros de test
$test_register_id = isset($_GET['register_id']) ? intval($_GET['register_id']) : 1;
$test_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 4; // Default a 4 si es Ileana
$test_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Mostrar información de usuario seleccionado
$user_info = get_userdata($test_user_id);
$user_name = $user_info ? $user_info->display_name : "Usuario #{$test_user_id}";

echo "<div class='result'>
<h2>📋 Parámetros de Diagnóstico</h2>
<p><strong>Caja:</strong> #{$test_register_id}</p>
<p><strong>Usuario:</strong> {$user_name} (ID: {$test_user_id})</p>
<p><strong>Fecha:</strong> {$test_date}</p>
<p><strong>URL personalizada:</strong> <a href='?register_id={$test_register_id}&user_id={$test_user_id}&date={$test_date}'>Usar estos parámetros</a></p>
</div>";

// Tablas que usaremos
$pos_sales_table = $wpdb->prefix . 'pos_sales';
$pos_transactions_table = $wpdb->prefix . 'pos_transactions';
$pos_payments_table = $wpdb->prefix . 'pos_payments';

// =================================================================
// DIAGNÓSTICO 1: VERIFICAR EXISTENCIA DE TABLAS
// =================================================================
echo "<h2>🗄️ Verificación de Tablas</h2>";
echo "<div class='result'>";

$tables_to_check = [
    'pos_sales' => $pos_sales_table,
    'pos_transactions' => $pos_transactions_table, 
    'pos_payments' => $pos_payments_table
];

foreach ($tables_to_check as $table_name => $full_table_name) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
    if ($table_exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
        echo "<p>✅ <strong>{$table_name}:</strong> {$count} registros</p>";
    } else {
        echo "<p>❌ <strong>{$table_name}:</strong> No existe</p>";
    }
}
echo "</div>";

// =================================================================
// DIAGNÓSTICO 2: ANÁLISIS DE ESTRUCTURA DE TABLAS
// =================================================================
echo "<h2>🔍 Estructura de Tablas - Columnas de Usuario</h2>";

foreach ($tables_to_check as $table_name => $full_table_name) {
    if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'")) {
        echo "<div class='result'>";
        echo "<h3>📋 {$table_name}</h3>";
        
        $columns = $wpdb->get_results("DESCRIBE {$full_table_name}");
        $user_columns = [];
        $payment_columns = [];
        
        echo "<table>";
        echo "<tr><th>Columna</th><th>Tipo</th><th>Relevante para</th></tr>";
        
        foreach ($columns as $column) {
            $relevance = "";
            if (strpos(strtolower($column->Field), 'user') !== false || 
                strpos(strtolower($column->Field), 'cashier') !== false ||
                strpos(strtolower($column->Field), 'seller') !== false ||
                strpos(strtolower($column->Field), 'employee') !== false ||
                strpos(strtolower($column->Field), 'created_by') !== false) {
                $user_columns[] = $column->Field;
                $relevance = "👤 Usuario";
            }
            
            if (strpos(strtolower($column->Field), 'payment') !== false ||
                strpos(strtolower($column->Field), 'method') !== false ||
                strpos(strtolower($column->Field), 'cash') !== false ||
                strpos(strtolower($column->Field), 'efectivo') !== false) {
                $payment_columns[] = $column->Field;
                $relevance .= ($relevance ? " + " : "") . "💰 Pago";
            }
            
            $highlight_class = $relevance ? "highlight" : "";
            echo "<tr class='{$highlight_class}'><td>{$column->Field}</td><td>{$column->Type}</td><td>{$relevance}</td></tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Columnas de usuario detectadas:</strong> " . (empty($user_columns) ? "❌ Ninguna" : "✅ " . implode(', ', $user_columns)) . "</p>";
        echo "<p><strong>Columnas de pago detectadas:</strong> " . (empty($payment_columns) ? "❌ Ninguna" : "✅ " . implode(', ', $payment_columns)) . "</p>";
        echo "</div>";
    }
}

// =================================================================
// DIAGNÓSTICO 3: CONSULTAS ESPECÍFICAS POR TABLA
// =================================================================
echo "<h2>🎯 Análisis de Datos - Fecha: {$test_date}</h2>";

// Mapeo de métodos de pago (igual que en calculate_payment_method_amount)
$cash_variants = ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'];

// ANÁLISIS TABLA POS_SALES
if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_sales_table}'")) {
    echo "<div class='result'>";
    echo "<h3>📊 pos_sales - Análisis de Ventas en Efectivo</h3>";
    
    // Consulta general para la fecha
    $sales_query = "SELECT * FROM {$pos_sales_table} WHERE DATE(created_at) = %s OR DATE(sale_date) = %s LIMIT 20";
    $sales_results = $wpdb->get_results($wpdb->prepare($sales_query, $test_date, $test_date));
    
    echo "<p><strong>Total registros para {$test_date}:</strong> " . count($sales_results) . "</p>";
    
    if (!empty($sales_results)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Método Pago</th><th>Total</th><th>Fecha</th><th>Columnas Usuario</th></tr>";
        
        foreach ($sales_results as $sale) {
            // Detectar columnas de usuario
            $user_info = [];
            $possible_user_cols = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
            foreach ($possible_user_cols as $col) {
                if (property_exists($sale, $col) && !empty($sale->$col)) {
                    $user_info[] = "{$col}: {$sale->$col}";
                }
            }
            
            // Detectar métodos de pago
            $payment_info = [];
            if (property_exists($sale, 'payment_method')) {
                $payment_info[] = "payment_method: {$sale->payment_method}";
            }
            
            // Resaltar si es del usuario que estamos analizando
            $is_target_user = false;
            foreach ($possible_user_cols as $col) {
                if (property_exists($sale, $col) && $sale->$col == $test_user_id) {
                    $is_target_user = true;
                    break;
                }
            }
            
            $row_class = $is_target_user ? "highlight" : "";
            
            echo "<tr class='{$row_class}'>";
            echo "<td>{$sale->id}</td>";
            echo "<td>" . ($is_target_user ? "⭐ {$user_name}" : "Otro") . "</td>";
            echo "<td>" . (property_exists($sale, 'payment_method') ? $sale->payment_method : 'N/A') . "</td>";
            echo "<td>" . (property_exists($sale, 'total') ? $sale->total : 'N/A') . "</td>";
            echo "<td>" . (property_exists($sale, 'created_at') ? $sale->created_at : (property_exists($sale, 'sale_date') ? $sale->sale_date : 'N/A')) . "</td>";
            echo "<td>" . implode('<br>', $user_info) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No se encontraron ventas para la fecha {$test_date}</p>";
    }
    echo "</div>";
}

// ANÁLISIS TABLA POS_TRANSACTIONS  
if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_transactions_table}'")) {
    echo "<div class='result'>";
    echo "<h3>💳 pos_transactions - Análisis de Transacciones</h3>";
    
    $trans_query = "SELECT * FROM {$pos_transactions_table} WHERE DATE(created_at) = %s OR DATE(transaction_date) = %s LIMIT 20";
    $trans_results = $wpdb->get_results($wpdb->prepare($trans_query, $test_date, $test_date));
    
    echo "<p><strong>Total transacciones para {$test_date}:</strong> " . count($trans_results) . "</p>";
    
    if (!empty($trans_results)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Método</th><th>Monto</th><th>Fecha</th><th>Estado</th></tr>";
        
        foreach ($trans_results as $trans) {
            $is_target_user = false;
            $user_display = "N/A";
            
            $possible_user_cols = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
            foreach ($possible_user_cols as $col) {
                if (property_exists($trans, $col) && $trans->$col == $test_user_id) {
                    $is_target_user = true;
                    $user_display = "⭐ {$user_name}";
                    break;
                } elseif (property_exists($trans, $col) && !empty($trans->$col)) {
                    $user_display = "Usuario #{$trans->$col}";
                }
            }
            
            $row_class = $is_target_user ? "highlight" : "";
            
            echo "<tr class='{$row_class}'>";
            echo "<td>{$trans->id}</td>";
            echo "<td>{$user_display}</td>";
            echo "<td>" . (property_exists($trans, 'payment_method') ? $trans->payment_method : 'N/A') . "</td>";
            echo "<td>" . (property_exists($trans, 'amount') ? $trans->amount : 'N/A') . "</td>";
            echo "<td>" . (property_exists($trans, 'created_at') ? $trans->created_at : 'N/A') . "</td>";
            echo "<td>" . (property_exists($trans, 'status') ? $trans->status : 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
}

// ANÁLISIS TABLA POS_PAYMENTS
if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_payments_table}'")) {
    echo "<div class='result'>";
    echo "<h3>💰 pos_payments - Análisis de Pagos</h3>";
    
    $payments_query = "SELECT * FROM {$pos_payments_table} WHERE DATE(created_at) = %s OR DATE(payment_date) = %s LIMIT 20";
    $payments_results = $wpdb->get_results($wpdb->prepare($payments_query, $test_date, $test_date));
    
    echo "<p><strong>Total pagos para {$test_date}:</strong> " . count($payments_results) . "</p>";
    
    if (!empty($payments_results)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Método</th><th>Monto</th><th>Fecha</th><th>Sale ID</th></tr>";
        
        foreach ($payments_results as $payment) {
            $is_target_user = false;
            $user_display = "N/A";
            
            $possible_user_cols = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
            foreach ($possible_user_cols as $col) {
                if (property_exists($payment, $col) && $payment->$col == $test_user_id) {
                    $is_target_user = true;
                    $user_display = "⭐ {$user_name}";
                    break;
                } elseif (property_exists($payment, $col) && !empty($payment->$col)) {
                    $user_display = "Usuario #{$payment->$col}";
                }
            }
            
            $row_class = $is_target_user ? "highlight" : "";
            
            echo "<tr class='{$row_class}'>";
            echo "<td>{$payment->id}</td>";
            echo "<td>{$user_display}</td>";
            echo "<td>" . (property_exists($payment, 'payment_method') ? $payment->payment_method : 'N/A') . "</td>";
            echo "<td>" . (property_exists($payment, 'amount') ? $payment->amount : 'N/A') . "</td>";
            echo "<td>" . (property_exists($payment, 'created_at') ? $payment->created_at : 'N/A') . "</td>";
            echo "<td>" . (property_exists($payment, 'sale_id') ? $payment->sale_id : 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
}

// =================================================================
// DIAGNÓSTICO 4: SIMULACIÓN DE calculate_payment_method_amount
// =================================================================
echo "<h2>🧮 Simulación de Cálculo de Total Efectivo</h2>";

echo "<div class='result'>";
echo "<h3>🎯 Reproduciendo lógica de calculate_payment_method_amount()</h3>";

$total_cash = 0;
$queries_executed = [];

// Simular las mismas consultas que hace calculate_payment_method_amount
foreach ($tables_to_check as $table_name => $full_table_name) {
    if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'")) {
        echo "<h4>Tabla: {$table_name}</h4>";
        
        // Detectar columnas disponibles
        $columns = $wpdb->get_col("DESCRIBE {$full_table_name}");
        
        // Buscar columna de usuario
        $user_column = null;
        $user_columns_priority = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
        foreach ($user_columns_priority as $user_col) {
            if (in_array($user_col, $columns)) {
                $user_column = $user_col;
                break;
            }
        }
        
        // Buscar columnas de método de pago y monto
        $payment_method_column = null;
        $amount_column = null;
        
        foreach ($columns as $col) {
            if (strpos(strtolower($col), 'payment') !== false && strpos(strtolower($col), 'method') !== false) {
                $payment_method_column = $col;
            }
            if (in_array(strtolower($col), ['amount', 'total', 'monto'])) {
                $amount_column = $col;
            }
        }
        
        echo "<p><strong>Columna de usuario:</strong> " . ($user_column ? "✅ {$user_column}" : "❌ No encontrada") . "</p>";
        echo "<p><strong>Columna método pago:</strong> " . ($payment_method_column ? "✅ {$payment_method_column}" : "❌ No encontrada") . "</p>";
        echo "<p><strong>Columna monto:</strong> " . ($amount_column ? "✅ {$amount_column}" : "❌ No encontrada") . "</p>";
        
        if ($payment_method_column && $amount_column) {
            // Construir consulta similar a calculate_payment_method_amount
            $date_columns = ['created_at', 'sale_date', 'transaction_date', 'payment_date'];
            $date_column = null;
            foreach ($date_columns as $date_col) {
                if (in_array($date_col, $columns)) {
                    $date_column = $date_col;
                    break;
                }
            }
            
            if ($date_column) {
                // Consulta SIN filtro de usuario
                $query_no_user = "SELECT SUM({$amount_column}) as total 
                                 FROM {$full_table_name} 
                                 WHERE DATE({$date_column}) = %s 
                                 AND {$payment_method_column} IN ('" . implode("','", $cash_variants) . "')";
                
                $total_no_user = $wpdb->get_var($wpdb->prepare($query_no_user, $test_date)) ?: 0;
                
                // Consulta CON filtro de usuario
                $total_with_user = 0;
                if ($user_column) {
                    $query_with_user = "SELECT SUM({$amount_column}) as total 
                                       FROM {$full_table_name} 
                                       WHERE DATE({$date_column}) = %s 
                                       AND {$payment_method_column} IN ('" . implode("','", $cash_variants) . "')
                                       AND {$user_column} = %d";
                    
                    $total_with_user = $wpdb->get_var($wpdb->prepare($query_with_user, $test_date, $test_user_id)) ?: 0;
                }
                
                echo "<p><strong>Total sin filtro usuario:</strong> ".number_format($total_no_user, 2)."</p>";
                echo "<p><strong>Total con filtro usuario ({$user_name}):</strong> ".number_format($total_with_user, 2)."</p>";
                
                if ($total_with_user > 0) {
                    echo "<div class='warning'>";
                    echo "<p>⚠️ <strong>ENCONTRADO:</strong> Esta tabla contribuye ".number_format($total_with_user, 2)." al total de efectivo para {$user_name}</p>";
                    
                    // Mostrar registros específicos
                    $detail_query = "SELECT * FROM {$full_table_name} 
                                    WHERE DATE({$date_column}) = %s 
                                    AND {$payment_method_column} IN ('" . implode("','", $cash_variants) . "')
                                    AND {$user_column} = %d";
                    
                    $detail_results = $wpdb->get_results($wpdb->prepare($detail_query, $test_date, $test_user_id));
                    
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Método</th><th>Monto</th><th>Usuario</th><th>Fecha</th></tr>";
                    foreach ($detail_results as $detail) {
                        echo "<tr>";
                        echo "<td>{$detail->id}</td>";
                        echo "<td>{$detail->{$payment_method_column}}</td>";
                        echo "<td>{$detail->{$amount_column}}</td>";
                        echo "<td>{$detail->{$user_column}}</td>";
                        echo "<td>{$detail->{$date_column}}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "</div>";
                }
                
                $total_cash += $total_with_user;
                $queries_executed[] = [
                    'table' => $table_name,
                    'amount' => $total_with_user,
                    'query' => $query_with_user ?? 'N/A'
                ];
            } else {
                echo "<p>❌ No se pudo encontrar columna de fecha</p>";
            }
        }
        echo "<hr>";
    }
}

echo "<div class='success'>";
echo "<h3>🎯 RESULTADO FINAL</h3>";
echo "<p><strong>Total Efectivo calculado para {$user_name}:</strong> ".number_format($total_cash, 2)."</p>";
echo "<p><strong>Fecha analizada:</strong> {$test_date}</p>";
echo "<p><strong>Registro de caja:</strong> #{$test_register_id}</p>";

if ($total_cash > 0) {
    echo "<div class='warning'>";
    echo "<h4>⚠️ DIAGNÓSTICO: Se encontró dinero en efectivo</h4>";
    echo "<p>Las siguientes tablas contribuyen al total:</p>";
    foreach ($queries_executed as $query_info) {
        if ($query_info['amount'] > 0) {
            echo "<p>• <strong>{$query_info['table']}:</strong> ".number_format($query_info['amount'], 2)."</p>";
        }
    }
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<p>✅ No se encontró dinero en efectivo para este usuario en esta fecha</p>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

echo "<div class='result'>";
echo "<h3>🔧 Recomendaciones</h3>";
echo "<ol>";
echo "<li>Si el total debería ser \$0.00 pero aparece un valor, revisar los registros resaltados arriba</li>";
echo "<li>Verificar si hay ventas mal asignadas al usuario {$user_name}</li>";
echo "<li>Comprobar si existen registros duplicados o con estados incorrectos</li>";
echo "<li>Revisar los logs de WordPress para mensajes de debug del módulo Closures</li>";
echo "</ol>";
echo "</div>";

echo "<p><small>Diagnóstico completado: " . date('Y-m-d H:i:s') . "</small></p>";

// Tablas que usaremos
$pos_sales_table = $wpdb->prefix . 'pos_sales';
$pos_transactions_table = $wpdb->prefix . 'pos_transactions';
$pos_payments_table = $wpdb->prefix . 'pos_payments';

echo "<h2>🔍 Test con Diferentes Escenarios</h2>";

$test_scenarios = [
    ['user_id' => 0, 'description' => 'Todos los usuarios (sin filtro)'],
    ['user_id' => $test_user_id, 'description' => "Usuario específico ({$test_user_id})"],
    ['user_id' => 9999, 'description' => 'Usuario inexistente (9999)'],
];

echo "<table>";
echo "<tr><th>Escenario</th><th>User ID</th><th>Resultado Cash</th><th>Resultado Card</th><th>Resultado Transfer</th></tr>";

foreach ($test_scenarios as $scenario) {
    $user_id = $scenario['user_id'];
    $description = $scenario['description'];
    
    // Probar diferentes métodos de pago
    $cash_result = $closures->calculate_payment_method_amount($test_register_id, $user_id, $test_date, 'cash');
    $card_result = $closures->calculate_payment_method_amount($test_register_id, $user_id, $test_date, 'card');
    $transfer_result = $closures->calculate_payment_method_amount($test_register_id, $user_id, $test_date, 'transfer');
    
    echo "<tr>";
    echo "<td>{$description}</td>";
    echo "<td>{$user_id}</td>";
    echo "<td style='font-weight:bold; color:" . ($cash_result > 0 ? '#dc3232' : '#46b450') . "'>{$cash_result}</td>";
    echo "<td>{$card_result}</td>";
    echo "<td>{$transfer_result}</td>";
    echo "</tr>";
}

echo "</table>";

// Análisis detallado
echo "<h2>🔍 Análisis de Base de Datos</h2>";

global $wpdb;

$tables_to_check = [
    'pos_sales' => $wpdb->prefix . 'pos_sales',
    'pos_transactions' => $wpdb->prefix . 'pos_transactions',
    'pos_payments' => $wpdb->prefix . 'pos_payments'
];

foreach ($tables_to_check as $table_name => $table_full) {
    echo "<div class='result'>";
    echo "<h3>📊 Tabla: {$table_name}</h3>";
    
    // Verificar si existe
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_full}'") === $table_full;
    if (!$exists) {
        echo "<p style='color:red'>❌ Tabla NO existe</p>";
        echo "</div>";
        continue;
    }
    
    echo "<p style='color:green'>✅ Tabla existe</p>";
    
    // Contar registros totales de hoy
    $count_today = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_full} WHERE DATE(created_at) = %s",
        $test_date
    ));
    
    echo "<p><strong>Registros hoy:</strong> {$count_today}</p>";
    
    if ($count_today > 0) {
        // Obtener estructura
        $columns = $wpdb->get_results("DESCRIBE {$table_full}");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        echo "<p><strong>Columnas:</strong> " . implode(', ', $column_names) . "</p>";
        
        // Verificar si hay columna amount/total
        $amount_col = in_array('amount', $column_names) ? 'amount' : 
                     (in_array('total', $column_names) ? 'total' : null);
        
        if ($amount_col) {
            // Total general
            $total_general = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM({$amount_col}) FROM {$table_full} WHERE DATE(created_at) = %s",
                $test_date
            ));
            
            echo "<p><strong>Total general hoy:</strong> {$total_general}</p>";
            
            // Total por usuario específico (si hay columna de usuario)
            $user_columns = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
            $found_user_col = null;
            
            foreach ($user_columns as $ucol) {
                if (in_array($ucol, $column_names)) {
                    $found_user_col = $ucol;
                    break;
                }
            }
            
            if ($found_user_col) {
                $total_user = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM({$amount_col}) FROM {$table_full} WHERE DATE(created_at) = %s AND {$found_user_col} = %d",
                    $test_date, $test_user_id
                ));
                
                echo "<p><strong>Total usuario {$test_user_id} ({$found_user_col}):</strong> {$total_user}</p>";
            }
            
            // Verificar métodos de pago
            $payment_columns = [];
            foreach ($column_names as $col) {
                if (strpos(strtolower($col), 'payment') !== false || 
                    strpos(strtolower($col), 'method') !== false) {
                    $payment_columns[] = $col;
                }
            }
            
            if (!empty($payment_columns)) {
                echo "<p><strong>Columnas de método de pago:</strong> " . implode(', ', $payment_columns) . "</p>";
                
                foreach ($payment_columns as $pcol) {
                    $payment_values = $wpdb->get_results($wpdb->prepare(
                        "SELECT {$pcol}, COUNT(*) as count, SUM({$amount_col}) as total 
                         FROM {$table_full} 
                         WHERE DATE(created_at) = %s 
                         GROUP BY {$pcol} 
                         ORDER BY count DESC 
                         LIMIT 5",
                        $test_date
                    ));
                    
                    if ($payment_values) {
                        echo "<p><strong>Valores en {$pcol}:</strong></p>";
                        echo "<ul>";
                        foreach ($payment_values as $pv) {
                            $method = $pv->$pcol;
                            $count = $pv->count;
                            $total = $pv->total;
                            echo "<li>{$method}: {$count} registros, total: {$total}</li>";
                        }
                        echo "</ul>";
                    }
                }
            } else {
                echo "<p style='color:orange'>⚠️ NO se encontraron columnas de método de pago</p>";
            }
        }
    }
    
    echo "</div>";
}

// Instrucciones
echo "<h2>📝 Diagnóstico</h2>";
echo "<div class='result warning'>";
echo "<p><strong>Si el valor de 'cash' es igual al total general:</strong></p>";
echo "<ul>";
echo "<li>Problema: No se está filtrando por método de pago correctamente</li>";
echo "<li>Posible causa: No se encuentra columna de método de pago o los valores no coinciden</li>";
echo "<li>Solución: Verificar los valores de métodos de pago en la base de datos</li>";
echo "</ul>";
echo "</div>";

echo "<div class='result'>";
echo "<p><strong>Para debug más detallado:</strong></p>";
echo "<p>1. Revisa los logs de WordPress (wp-content/debug.log)</p>";
echo "<p>2. Busca mensajes que contengan '🔍 ANÁLISIS' para ver qué columnas se detectan</p>";
echo "<p>3. Verifica si los valores 'cash', 'efectivo', etc. coinciden con los de la BD</p>";
echo "</div>";
?>
