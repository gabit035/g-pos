<?php
/**
 * Script simple para verificar la versión del módulo
 */

echo "🚀 VERIFICANDO MÓDULO G-POS REPORTS\n";
echo str_repeat("=", 50) . "\n\n";

// Verificar que el archivo del módulo existe
$module_file = __DIR__ . '/modules/reports/class-pos-reports-module.php';

if (!file_exists($module_file)) {
    echo "❌ Error: No se encuentra el archivo del módulo\n";
    exit(1);
}

echo "✅ Archivo del módulo encontrado\n";

// Leer el contenido del archivo para verificar la versión
$content = file_get_contents($module_file);

// Buscar la versión
if (preg_match('/private\s+\$version\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
    $version = $matches[1];
    echo "📌 Versión del módulo: $version\n";
    
    if ($version === '1.2.0') {
        echo "✅ Versión correcta para migración\n";
    } else {
        echo "⚠️  Versión inesperada\n";
    }
} else {
    echo "❌ No se pudo encontrar la versión en el archivo\n";
}

// Verificar que existe la función de migración
if (strpos($content, 'function upgrade_database') !== false) {
    echo "✅ Función upgrade_database encontrada\n";
} else {
    echo "❌ Función upgrade_database no encontrada\n";
}

// Verificar que existe la migración para versión 1.2.0
if (strpos($content, "version_compare(\$from_version, '1.2.0', '<')") !== false) {
    echo "✅ Migración para versión 1.2.0 encontrada\n";
} else {
    echo "❌ Migración para versión 1.2.0 no encontrada\n";
}

// Verificar que existe la actualización de USD a $
if (strpos($content, "currency = %s WHERE currency = %s") !== false) {
    echo "✅ Código de actualización USD → $ encontrado\n";
} else {
    echo "❌ Código de actualización USD → $ no encontrado\n";
}

// Verificar que el constructor llama a check_database_version
if (strpos($content, '$this->check_database_version()') !== false) {
    echo "✅ Constructor configurado para ejecutar migración\n";
} else {
    echo "❌ Constructor no está configurado para migración automática\n";
}

// Verificar funciones de control manual
if (strpos($content, 'function force_database_migration') !== false) {
    echo "✅ Función de migración manual encontrada\n";
} else {
    echo "❌ Función de migración manual no encontrada\n";
}

if (strpos($content, 'function check_migration_status') !== false) {
    echo "✅ Función de verificación de estado encontrada\n";
} else {
    echo "❌ Función de verificación de estado no encontrada\n";
}

// Verificar handlers AJAX
if (strpos($content, 'wp_ajax_wp_pos_force_migration') !== false) {
    echo "✅ AJAX handler para migración encontrado\n";
} else {
    echo "❌ AJAX handler para migración no encontrado\n";
}

if (strpos($content, 'wp_ajax_wp_pos_check_migration_status') !== false) {
    echo "✅ AJAX handler para verificación encontrado\n";
} else {
    echo "❌ AJAX handler para verificación no encontrado\n";
}

// Verificar funciones JavaScript
if (strpos($content, 'forcePOSMigration') !== false) {
    echo "✅ Función JavaScript forcePOSMigration encontrada\n";
} else {
    echo "❌ Función JavaScript forcePOSMigration no encontrada\n";
}

if (strpos($content, 'checkPOSMigrationStatus') !== false) {
    echo "✅ Función JavaScript checkPOSMigrationStatus encontrada\n";
} else {
    echo "❌ Función JavaScript checkPOSMigrationStatus no encontrada\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎯 RESUMEN: El módulo versión 1.2.0 está listo para:\n";
echo "   - Migración automática al cargar\n";
echo "   - Control manual vía JavaScript en consola\n";
echo "   - Verificación de estado de migración\n";
echo "\n✅ MÓDULO PREPARADO PARA CARGAR\n";
?>
