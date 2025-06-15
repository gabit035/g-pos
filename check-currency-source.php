<?php
/**
 * Detectar el origen del símbolo USD en Reports
 */

// Cargar WordPress
require_once('../../../wp-config.php');

echo "🔍 INVESTIGACIÓN: ORIGEN DEL SÍMBOLO USD\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Verificar configuración de WooCommerce
echo "💰 CONFIGURACIÓN DE WOOCOMMERCE:\n";
if (function_exists('get_woocommerce_currency')) {
    $wc_currency = get_woocommerce_currency();
    $wc_symbol = get_woocommerce_currency_symbol();
    echo "   - Moneda WooCommerce: $wc_currency\n";
    echo "   - Símbolo WooCommerce: $wc_symbol\n";
} else {
    echo "   - WooCommerce NO está activo\n";
}

// 2. Verificar opciones de WordPress relacionadas con moneda
echo "\n🏛️  OPCIONES DE WORDPRESS:\n";
$wp_currency_options = [
    'woocommerce_currency',
    'woocommerce_currency_symbol',
    'pos_currency',
    'wp_pos_currency',
    'currency',
    'default_currency'
];

foreach ($wp_currency_options as $option) {
    $value = get_option($option, 'NO_EXISTE');
    echo "   - $option: $value\n";
}

// 3. Verificar qué función de formato se está usando
echo "\n🛠️  PRUEBA DE FUNCIONES DE FORMATO:\n";

// Test wp_pos_format_price
if (function_exists('wp_pos_format_price')) {
    $test_price = wp_pos_format_price(1234.56);
    echo "   - wp_pos_format_price(1234.56): '$test_price'\n";
} else {
    echo "   - wp_pos_format_price: NO EXISTE\n";
}

// Test wc_price si existe
if (function_exists('wc_price')) {
    $wc_test = wc_price(1234.56);
    echo "   - wc_price(1234.56): $wc_test\n";
} else {
    echo "   - wc_price: NO EXISTE\n";
}

// 4. Simular la obtención de datos de ventas recientes
echo "\n📊 SIMULACIÓN DE DATOS DE VENTAS:\n";

// Incluir las clases necesarias
$reports_module_file = __DIR__ . '/modules/reports/class-pos-reports-module.php';
$reports_data_file = __DIR__ . '/modules/reports/class-pos-reports-data.php';

if (file_exists($reports_data_file)) {
    include_once $reports_data_file;
    
    if (class_exists('WP_POS_Reports_Data')) {
        echo "   - Clase WP_POS_Reports_Data: DISPONIBLE\n";
        
        // Obtener datos reales
        $recent_sales = WP_POS_Reports_Data::get_recent_sales(['limit' => 1]);
        echo "   - Resultado get_recent_sales: " . print_r($recent_sales, true) . "\n";
        
        if (isset($recent_sales['recent_sales']) && !empty($recent_sales['recent_sales'])) {
            $first_sale = $recent_sales['recent_sales'][0];
            echo "   - Primera venta raw: " . print_r($first_sale, true) . "\n";
            
            if (isset($first_sale['total'])) {
                echo "   - Total raw: " . $first_sale['total'] . "\n";
                echo "   - Total formateado: " . wp_pos_format_price($first_sale['total']) . "\n";
            }
        }
    } else {
        echo "   - Clase WP_POS_Reports_Data: NO DISPONIBLE\n";
    }
} else {
    echo "   - Archivo class-pos-reports-data.php: NO ENCONTRADO\n";
}

// 5. Verificar template de ventas recientes
echo "\n📄 CONTENIDO DEL TEMPLATE:\n";
$template_file = __DIR__ . '/modules/reports/templates/recent-sales-table.php';
if (file_exists($template_file)) {
    echo "   - Template existe: SÍ\n";
    
    // Buscar USD en el contenido del template
    $template_content = file_get_contents($template_file);
    if (strpos($template_content, 'USD') !== false) {
        echo "   - Template contiene 'USD': SÍ ⚠️\n";
        $lines = explode("\n", $template_content);
        foreach ($lines as $num => $line) {
            if (strpos($line, 'USD') !== false) {
                echo "     Línea " . ($num + 1) . ": " . trim($line) . "\n";
            }
        }
    } else {
        echo "   - Template contiene 'USD': NO\n";
    }
} else {
    echo "   - Template NO existe\n";
}

// 6. Verificar variables globales y sesiones
echo "\n🌐 VARIABLES GLOBALES:\n";
if (isset($_SESSION)) {
    echo "   - SESSION currency: " . ($_SESSION['currency'] ?? 'NO_EXISTE') . "\n";
}

if (isset($GLOBALS['wp_pos_currency'])) {
    echo "   - GLOBALS wp_pos_currency: " . $GLOBALS['wp_pos_currency'] . "\n";
} else {
    echo "   - GLOBALS wp_pos_currency: NO_EXISTE\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 INVESTIGACIÓN COMPLETA\n";
echo "Si aún ves USD, verifica:\n";
echo "1. Configuración de WooCommerce\n";
echo "2. JavaScript en el frontend que pueda estar modificando valores\n";
echo "3. Otros plugins que interfieran con el formato de moneda\n";
echo "4. Caché del navegador\n";
?>
