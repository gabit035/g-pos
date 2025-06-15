<?php
/**
 * Script para probar que la corrección del símbolo de moneda funciona correctamente
 */

// Cargar WordPress
require_once('C:/Laragon/www/pep/wp-load.php');

// Verificar que estemos en modo debug
if (!WP_DEBUG) {
    echo "Este script solo funciona con WP_DEBUG = true\n";
    exit;
}

echo "=== TESTING CURRENCY SYMBOL FIX ===\n\n";

// Test 1: Verificar función wp_pos_format_price del core
echo "1. Testing wp_pos_format_price (core function):\n";
if (function_exists('wp_pos_format_price')) {
    $price = 1234.56;
    $formatted = wp_pos_format_price($price);
    echo "   Precio: $price\n";
    echo "   Formateado: $formatted\n";
    
    if (strpos($formatted, '$') !== false) {
        echo "   CORRECTO: Contiene símbolo '$'\n";
    } else {
        echo "   ERROR: No contiene símbolo '$'\n";
    }
    
    if (strpos($formatted, 'USD') !== false) {
        echo "   ERROR: Todavía contiene 'USD'\n";
    } else {
        echo "   CORRECTO: No contiene 'USD'\n";
    }
} else {
    echo "   ERROR: Función wp_pos_format_price no existe\n";
}

echo "\n";

// Test 2: Verificar función del módulo Settings si existe
echo "2. Testing Settings module function (if exists):\n";
$settings_file = dirname(__FILE__) . '/modules/settings/includes/settings-functions.php';
if (file_exists($settings_file)) {
    // Incluir el archivo del módulo Settings
    include_once($settings_file);
    
    // Verificar contenido del archivo
    $content = file_get_contents($settings_file);
    if (strpos($content, "'USD'") !== false) {
        echo "   ADVERTENCIA: El archivo todavía contiene 'USD'\n";
    } else {
        echo "   CORRECTO: El archivo ya no contiene 'USD'\n";
    }
    
    if (strpos($content, "'currency' => '\$'") !== false) {
        echo "   CORRECTO: Contiene el nuevo valor por defecto '\$'\n";
    } else {
        echo "   INFORMACIÓN: No se encuentra el patrón exacto, pero puede estar correcto\n";
    }
} else {
    echo "   ERROR: Archivo settings-functions.php no encontrado\n";
}

echo "\n";

// Test 3: Verificar función del core-functions
echo "3. Testing core-functions.php:\n";
$core_file = dirname(__FILE__) . '/includes/functions/core-functions.php';
if (file_exists($core_file)) {
    $content = file_get_contents($core_file);
    if (strpos($content, "\$default_currency = '\$';") !== false) {
        echo "   CORRECTO: core-functions.php usa '\$' como moneda por defecto\n";
    } else {
        echo "   ERROR: core-functions.php no usa '\$' como moneda por defecto\n";
    }
    
    if (strpos($content, "\$default_currency = 'USD';") !== false) {
        echo "   ERROR: core-functions.php todavía contiene 'USD'\n";
    } else {
        echo "   CORRECTO: core-functions.php ya no contiene 'USD' como default\n";
    }
} else {
    echo "   ERROR: Archivo core-functions.php no encontrado\n";
}

echo "\n";

// Test 4: Simular datos de venta reciente
echo "4. Testing recent sales data simulation:\n";
$sample_data = array(
    'id' => 123,
    'sale_number' => 'POS-20240615-0001',
    'total' => 1234.56,
    'currency' => '$',
    'date' => date('Y-m-d H:i:s')
);

if (function_exists('wp_pos_format_price')) {
    $formatted_total = wp_pos_format_price($sample_data['total']);
    echo "   Datos de venta simulada:\n";
    echo "   - ID: {$sample_data['id']}\n";
    echo "   - Número: {$sample_data['sale_number']}\n";
    echo "   - Total raw: {$sample_data['total']}\n";
    echo "   - Total formateado: $formatted_total\n";
    echo "   - Currency en DB: {$sample_data['currency']}\n";
    echo "   - Fecha: {$sample_data['date']}\n";
    
    if (strpos($formatted_total, '$') !== false && strpos($formatted_total, 'USD') === false) {
        echo "   PERFECTO: El formato es correcto\n";
    } else {
        echo "   ERROR: El formato no es correcto\n";
    }
}

echo "\n=== RESUMEN ===\n";
echo "Si todos los tests muestran , la corrección está funcionando correctamente.\n";
echo "Si hay , revisa los archivos mencionados.\n";
echo "\nPróximo paso: Probar en el navegador visitando el módulo Reports.\n";
