<?php
/**
 * Debug script para identificar por qué calculate_payment_method_amount 
 * devuelve el total de todas las ventas en lugar de solo efectivo
 * 
 * Ejecutar este script directamente accediendo a:
 * /wp-content/plugins/G-POS/modules/closures/debug-payment-method.php
 */

// Cargar WordPress
require_once('../../../../../wp-config.php');

// Verificar si el usuario tiene permisos
if (!current_user_can('manage_options')) {
    die('No tienes permisos para ejecutar este script');
}

echo "<h1>🔍 Debug: calculate_payment_method_amount</h1>";
echo "<style>body{font-family:Arial;} .debug{background:#f0f0f0;padding:10px;margin:10px 0;border-left:4px solid #007cba;} .error{border-left-color:#dc3232;}</style>";

// Obtener las tablas
global $wpdb;
$tables = [
    'pos_sales' => $wpdb->prefix . 'pos_sales',
    'pos_transactions' => $wpdb->prefix . 'pos_transactions', 
    'pos_payments' => $wpdb->prefix . 'pos_payments'
];

echo "<h2>📊 Análisis de Tablas</h2>";

foreach ($tables as $table_name => $table_full) {
    echo "<div class='debug'>";
    echo "<h3>🗄️ Tabla: {$table_name} ({$table_full})</h3>";
    
    // Verificar si existe
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_full}'") === $table_full;
    if (!$exists) {
        echo "<p style='color:red'>❌ Tabla NO existe</p></div>";
        continue;
    }
    
    echo "<p style='color:green'>✅ Tabla existe</p>";
    
    // Obtener estructura
    $columns = $wpdb->get_results("DESCRIBE {$table_full}");
    $column_names = array_map(function($col) { return $col->Field; }, $columns);
    
    echo "<p><strong>Columnas:</strong> " . implode(', ', $column_names) . "</p>";
    
    // Buscar columnas de método de pago
    $payment_columns = [];
    foreach ($column_names as $col) {
        if (strpos(strtolower($col), 'payment') !== false || 
            strpos(strtolower($col), 'method') !== false) {
            $payment_columns[] = $col;
        }
    }
    
    if (empty($payment_columns)) {
        echo "<p style='color:orange'>⚠️ NO se encontraron columnas de método de pago</p>";
    } else {
        echo "<p style='color:green'>✅ Columnas de método de pago: " . implode(', ', $payment_columns) . "</p>";
        
        // Verificar valores de método de pago
        foreach ($payment_columns as $payment_col) {
            $values = $wpdb->get_results("SELECT DISTINCT {$payment_col} FROM {$table_full} LIMIT 10");
            echo "<p><strong>Valores en {$payment_col}:</strong> ";
            foreach ($values as $value) {
                echo $value->$payment_col . " | ";
            }
            echo "</p>";
        }
    }
    
    // Buscar columnas de usuario
    $user_columns = [];
    $user_search = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
    foreach ($user_search as $user_col) {
        if (in_array($user_col, $column_names)) {
            $user_columns[] = $user_col;
        }
    }
    
    if (empty($user_columns)) {
        echo "<p style='color:orange'>⚠️ NO se encontraron columnas de usuario</p>";
    } else {
        echo "<p style='color:green'>✅ Columnas de usuario: " . implode(', ', $user_columns) . "</p>";
    }
    
    // Contar registros por fecha (hoy)
    $today = date('Y-m-d');
    $date_columns = ['created_at', 'date_created', 'date', 'timestamp'];
    $date_column = '';
    
    foreach ($date_columns as $date_col) {
        if (in_array($date_col, $column_names)) {
            $date_column = $date_col;
            break;
        }
    }
    
    if ($date_column) {
        $count_today = $wpdb->get_var("SELECT COUNT(*) FROM {$table_full} WHERE DATE({$date_column}) = '{$today}'");
        echo "<p><strong>Registros hoy ({$today}):</strong> {$count_today}</p>";
        
        // Si hay columna de amount/total, mostrar total
        $amount_column = in_array('amount', $column_names) ? 'amount' : 
                        (in_array('total', $column_names) ? 'total' : '');
        
        if ($amount_column) {
            $total_today = $wpdb->get_var("SELECT SUM({$amount_column}) FROM {$table_full} WHERE DATE({$date_column}) = '{$today}'");
            echo "<p><strong>Total hoy ({$amount_column}):</strong> {$total_today}</p>";
        }
    }
    
    echo "</div>";
}

// Simular la llamada a calculate_payment_method_amount
echo "<h2>🧪 Simulación de calculate_payment_method_amount</h2>";

if (class_exists('Closures_Module')) {
    $closures = new Closures_Module();
    
    // Obtener parámetros de test
    $test_register_id = 1;
    $test_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;
    $test_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    echo "<div class='debug'>";
    echo "<p><strong>Parámetros de prueba:</strong></p>";
    echo "<p>register_id: {$test_register_id}</p>";
    echo "<p>user_id: {$test_user_id}</p>";
    echo "<p>date: {$test_date}</p>";
    echo "</div>";
    
    // Llamar al método para diferentes métodos de pago
    $methods = ['cash', 'card', 'transfer', 'check', 'other'];
    
    foreach ($methods as $method) {
        $result = $closures->calculate_payment_method_amount($test_register_id, $test_user_id, $test_date, $method);
        echo "<div class='debug'>";
        echo "<p><strong>Método {$method}:</strong> {$result}</p>";
        echo "</div>";
    }
} else {
    echo "<div class='debug error'>";
    echo "<p>❌ Clase Closures_Module no encontrada</p>";
    echo "</div>";
}

echo "<h2>📝 Recomendaciones</h2>";
echo "<div class='debug'>";
echo "<p>1. Verifica en los logs de WordPress (WP_DEBUG = true) las consultas SQL ejecutadas</p>";
echo "<p>2. Si una tabla no tiene columna de método de pago, debería devolver 0 para métodos específicos</p>";
echo "<p>3. Revisa si hay consultas fallback que estén sumando todas las ventas</p>";
echo "</div>";

echo "<p><strong>URL de prueba:</strong> ?user_id=123&date=2025-06-15</p>";
?>
