<?php
/**
 * Debug completo de la tabla pos_sales
 */

// Cargar WordPress
require_once('../../../wp-config.php');

echo "🔍 DEBUG COMPLETO DE LA TABLA POS_SALES\n";
echo str_repeat("=", 60) . "\n\n";

global $wpdb;

$table_name = $wpdb->prefix . 'pos_sales';

// 1. Verificar estructura de la tabla
echo "📋 ESTRUCTURA DE LA TABLA:\n";
$columns = $wpdb->get_results("DESCRIBE {$table_name}");
foreach ($columns as $column) {
    echo "   - {$column->Field}: {$column->Type}";
    if ($column->Default !== null) {
        echo " (DEFAULT: {$column->Default})";
    }
    echo "\n";
}

// 2. Contar total de registros
echo "\n📊 REGISTROS EN LA TABLA:\n";
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
echo "   - Total de registros: $total\n";

if ($total > 0) {
    // 3. Ver algunos registros de ejemplo
    echo "\n🔍 PRIMEROS 5 REGISTROS:\n";
    $samples = $wpdb->get_results("SELECT id, total, currency, created_at FROM {$table_name} LIMIT 5");
    foreach ($samples as $row) {
        echo "   - ID: {$row->id} | Total: {$row->total} | Currency: '{$row->currency}' | Fecha: {$row->created_at}\n";
    }
    
    // 4. Contar por tipo de moneda
    echo "\n💰 DISTRIBUCIÓN POR MONEDA:\n";
    $currencies = $wpdb->get_results("SELECT currency, COUNT(*) as count FROM {$table_name} GROUP BY currency");
    foreach ($currencies as $curr) {
        echo "   - '{$curr->currency}': {$curr->count} registros\n";
    }
    
    // 5. Verificar si hay valores NULL o vacíos
    $null_currency = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE currency IS NULL OR currency = ''");
    echo "   - Registros con currency NULL/vacío: $null_currency\n";
    
    // 6. Ver todos los valores únicos de currency
    echo "\n🎯 VALORES ÚNICOS DE CURRENCY:\n";
    $unique_currencies = $wpdb->get_results("SELECT DISTINCT currency FROM {$table_name}");
    foreach ($unique_currencies as $curr) {
        $clean_value = $curr->currency;
        $length = strlen($clean_value);
        echo "   - Valor: '{$clean_value}' (longitud: {$length})\n";
        
        // Mostrar caracteres ocultos
        for ($i = 0; $i < $length; $i++) {
            $char = $clean_value[$i];
            $ascii = ord($char);
            echo "     Char $i: '$char' (ASCII: $ascii)\n";
        }
    }
} else {
    echo "   ⚠️  La tabla está vacía\n";
    
    // Verificar si existe el esquema por defecto
    echo "\n🔍 VERIFICANDO ESQUEMA DEFAULT:\n";
    $default_currency = $wpdb->get_var("SELECT column_default FROM information_schema.columns WHERE table_name = 'pos_sales' AND column_name = 'currency' AND table_schema = DATABASE()");
    echo "   - Default de columna currency: '$default_currency'\n";
}

// 7. Verificar versión de base de datos del plugin
echo "\n🔧 VERSIÓN DE BASE DE DATOS:\n";
$db_version = get_option('wp_pos_reports_db_version', 'no_existe');
echo "   - wp_pos_reports_db_version: $db_version\n";

// 8. Verificar si la tabla tiene el esquema correcto
echo "\n🏗️  VERIFICACIÓN DE ESQUEMA:\n";
$currency_column = $wpdb->get_row("SELECT * FROM information_schema.columns WHERE table_name = 'pos_sales' AND column_name = 'currency' AND table_schema = DATABASE()");
if ($currency_column) {
    echo "   - Columna currency existe: SÍ\n";
    echo "   - Tipo: {$currency_column->COLUMN_TYPE}\n";
    echo "   - Default: {$currency_column->COLUMN_DEFAULT}\n";
    echo "   - Es NULL: {$currency_column->IS_NULLABLE}\n";
} else {
    echo "   - ❌ Columna currency NO existe\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 DEBUG COMPLETO\n";
?>
