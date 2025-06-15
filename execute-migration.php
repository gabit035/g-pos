<?php
/**
 * Ejecutar migración directa USD → $
 * EJECUTAR SOLO UNA VEZ
 */

// Cargar WordPress
require_once('../../../wp-config.php');

echo "🚀 EJECUTANDO MIGRACIÓN DIRECTA USD → $\n";
echo str_repeat("=", 50) . "\n\n";

global $wpdb;

$table_name = $wpdb->prefix . 'pos_sales';

// Verificar que la tabla existe
$table_exists = $wpdb->get_var($wpdb->prepare(
    "SHOW TABLES LIKE %s",
    $table_name
));

if (!$table_exists) {
    echo "❌ Error: La tabla $table_name no existe\n";
    exit(1);
}

echo "✅ Tabla encontrada: $table_name\n";

// Contar registros antes
$usd_before = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE currency = %s",
    'USD'
));

$peso_before = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE currency = %s",
    '$'
));

echo "📊 ANTES DE LA MIGRACIÓN:\n";
echo "   - Registros USD: $usd_before\n";
echo "   - Registros $: $peso_before\n\n";

if ($usd_before == 0) {
    echo "ℹ️  No hay registros USD para migrar\n";
    exit(0);
}

// Ejecutar migración
echo "🔄 Ejecutando migración...\n";

$result = $wpdb->query($wpdb->prepare(
    "UPDATE {$table_name} SET currency = %s WHERE currency = %s",
    '$',
    'USD'
));

if ($result === false) {
    echo "❌ Error al ejecutar la migración\n";
    echo "Error MySQL: " . $wpdb->last_error . "\n";
    exit(1);
}

echo "✅ Migración ejecutada. Registros actualizados: $result\n\n";

// Contar registros después
$usd_after = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE currency = %s",
    'USD'
));

$peso_after = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE currency = %s",
    '$'
));

echo "📊 DESPUÉS DE LA MIGRACIÓN:\n";
echo "   - Registros USD: $usd_after\n";
echo "   - Registros $: $peso_after\n";

// Actualizar versión de base de datos
update_option('wp_pos_reports_db_version', '1.2.0');
echo "✅ Versión de base de datos actualizada a 1.2.0\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
echo "   - Registros migrados: $result\n";
echo "   - USD restantes: $usd_after\n";
echo "   - $ totales: $peso_after\n";
echo "\n✅ Ahora refresca la página de Reports para ver los cambios\n";
?>
