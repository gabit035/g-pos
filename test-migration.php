<?php
/**
 * Script de prueba para cargar el módulo versión 1.2.0 y ejecutar la migración
 * 
 * Ejecutar este script desde el directorio del plugin para simular la carga del módulo
 */

// Simular entorno WordPress mínimo
if (!defined('ABSPATH')) {
    define('ABSPATH', 'C:/Laragon/www/pep/');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

// Funciones WordPress simuladas
function get_option($option, $default = false) {
    $options = [
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'wp_pos_currency_symbol' => '$',
        'wp_pos_currency_position' => 'left',
        'wp_pos_decimal_places' => 2,
        'wp_pos_reports_auto_refresh' => false,
        'wp_pos_reports_refresh_interval' => 30,
        'wp_pos_reports_db_version' => '1.0.0'  // Simular versión anterior
    ];
    
    return isset($options[$option]) ? $options[$option] : $default;
}

function update_option($option, $value) {
    echo "✅ Actualizando opción: $option = $value\n";
    return true;
}

function apply_filters($filter, $value) {
    return $value;
}

function add_action($hook, $callback) {
    // Simular registro de actions
    return true;
}

function add_filter($hook, $callback) {
    // Simular registro de filtros
    return true;
}

function wp_schedule_event($timestamp, $recurrence, $hook) {
    return true;
}

function wp_next_scheduled($hook) {
    return false;
}

function error_log($message) {
    echo "🔍 LOG: $message\n";
}

// Simular $wpdb
class MockWPDB {
    public $prefix = 'wp_';
    
    public function prepare($query, ...$args) {
        // Simular prepared statement
        return vsprintf(str_replace('%s', "'%s'", $query), $args);
    }
    
    public function query($query) {
        echo "🗄️  EJECUTANDO SQL: $query\n";
        
        // Simular actualización exitosa
        if (strpos($query, 'UPDATE') !== false && strpos($query, 'currency') !== false) {
            echo "   📊 Registros actualizados: 5 (simulado)\n";
            return 5;
        }
        
        return true;
    }
}

$wpdb = new MockWPDB();

// Incluir el módulo
echo "🚀 INICIANDO PRUEBA DE MIGRACIÓN - MÓDULO VERSIÓN 1.2.0\n";
echo "=" . str_repeat("=", 60) . "\n\n";

echo "📁 Cargando módulo Reports...\n";
require_once __DIR__ . '/modules/reports/class-pos-reports-module.php';

echo "\n🔧 Instanciando módulo...\n";
$reports_module = WP_POS_Reports_Module::get_instance();

echo "\n📋 ESTADO INICIAL:\n";
echo "   - Versión del módulo: " . $reports_module->get_version() . "\n";
echo "   - Versión DB simulada: 1.0.0\n";

echo "\n🔄 EJECUTANDO MIGRACIÓN MANUAL...\n";
$migration_result = $reports_module->force_database_migration();

echo "\n📊 RESULTADO DE MIGRACIÓN:\n";
foreach ($migration_result as $key => $value) {
    echo "   - $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}

echo "\n🧪 VERIFICANDO ESTADO POST-MIGRACIÓN...\n";
$status = $reports_module->check_migration_status();

echo "\n📈 ESTADO FINAL:\n";
foreach ($status as $key => $value) {
    if ($key === 'message') continue;
    echo "   - $key: " . (is_bool($value) ? ($value ? 'SÍ' : 'NO') : $value) . "\n";
}
echo "   - " . $status['message'] . "\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ PRUEBA COMPLETADA - MÓDULO VERSIÓN 1.2.0 CARGADO\n";
?>
