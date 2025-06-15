<?php
/**
 * Script simple para verificar los cambios en las funciones de formato de moneda
 */

echo "=== VERIFICACIÓN DE CORRECCIÓN DEL SÍMBOLO DE MONEDA ===\n\n";

// Test 1: Verificar core-functions.php
echo "1. Verificando core-functions.php:\n";
$core_file = __DIR__ . '/includes/functions/core-functions.php';
if (file_exists($core_file)) {
    $content = file_get_contents($core_file);
    
    if (strpos($content, "\$default_currency = '\$';") !== false) {
        echo "   ✅ CORRECTO: Encontrado \$default_currency = '\$';\n";
    } else {
        echo "   ❌ ERROR: No se encontró \$default_currency = '\$';\n";
    }
    
    if (strpos($content, "\$default_currency = 'USD';") !== false) {
        echo "   ❌ ERROR: Todavía contiene \$default_currency = 'USD';\n";
    } else {
        echo "   ✅ CORRECTO: Ya no contiene 'USD' como default\n";
    }
} else {
    echo "   ❌ ERROR: Archivo no encontrado\n";
}

echo "\n";

// Test 2: Verificar settings-functions.php
echo "2. Verificando settings-functions.php:\n";
$settings_file = __DIR__ . '/modules/settings/includes/settings-functions.php';
if (file_exists($settings_file)) {
    $content = file_get_contents($settings_file);
    
    if (strpos($content, "'currency' => '\$'") !== false) {
        echo "   ✅ CORRECTO: Encontrado 'currency' => '\$'\n";
    } else {
        echo "   ❌ ERROR: No se encontró 'currency' => '\$'\n";
    }
    
    // Buscar cualquier referencia a USD
    $usd_count = substr_count($content, 'USD');
    echo "   Número de referencias a 'USD': $usd_count\n";
    
    if ($usd_count > 0) {
        echo "   ⚠️  ADVERTENCIA: Todavía hay referencias a 'USD'\n";
    } else {
        echo "   ✅ CORRECTO: No hay referencias a 'USD'\n";
    }
} else {
    echo "   ❌ ERROR: Archivo no encontrado\n";
}

echo "\n";

// Test 3: Verificar archivos del template
echo "3. Verificando template recent-sales-table.php:\n";
$template_file = __DIR__ . '/modules/reports/templates/recent-sales-table.php';
if (file_exists($template_file)) {
    $content = file_get_contents($template_file);
    
    if (strpos($content, "wp_pos_format_price") !== false) {
        echo "   ✅ CORRECTO: El template usa wp_pos_format_price\n";
    } else {
        echo "   ❌ ERROR: El template no usa wp_pos_format_price\n";
    }
    
    // Buscar referencias hardcodeadas a USD
    if (strpos($content, 'USD') !== false) {
        echo "   ❌ ERROR: El template contiene 'USD' hardcodeado\n";
    } else {
        echo "   ✅ CORRECTO: El template no contiene 'USD' hardcodeado\n";
    }
} else {
    echo "   ❌ ERROR: Archivo de template no encontrado\n";
}

echo "\n=== RESUMEN ===\n";
echo "Si todos los tests muestran ✅, la corrección está lista.\n";
echo "Ahora puedes probar en el navegador accediendo al módulo Reports.\n";
echo "El símbolo debería mostrar '\$' en lugar de 'USD'.\n";
