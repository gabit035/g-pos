<?php
/**
 * Funciones auxiliares para el módulo de configuraciones
 *
 * @package WP-POS
 * @subpackage Settings
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener una opción de configuración
 *
 * @since 1.0.0
 * @param string $option Nombre de la opción
 * @param mixed $default Valor por defecto si no existe
 * @param bool $use_cache Usar cache o forzar lectura
 * @return mixed Valor de la opción
 */
if (!function_exists('wp_pos_get_option')) {
    function wp_pos_get_option($option, $default = null, $use_cache = true) {
        static $options_cache = null;
        
        // Inicializar cache si es necesario
        if ($options_cache === null || !$use_cache) {
            $options_cache = get_option('wp_pos_options', array());
        }
        
        // Comprobar si la opción existe
        if (isset($options_cache[$option])) {
            $value = $options_cache[$option];
        } else {
            $value = $default;
        }
        
        // Permitir filtrar el valor
        return apply_filters('wp_pos_get_option', $value, $option, $default);
    }
}

/**
 * Actualizar una opción de configuración
 *
 * @since 1.0.0
 * @param string $option Nombre de la opción
 * @param mixed $value Nuevo valor
 * @return bool Éxito o fallo al actualizar
 */
if (!function_exists('wp_pos_update_option')) {
    function wp_pos_update_option($option, $value) {
        $options = get_option('wp_pos_options', array());
        
        // Actualizar valor
        $options[$option] = $value;
        
        // Guardar opciones
        $updated = update_option('wp_pos_options', $options);
        
        // Limpiar cache si se actualizó
        if ($updated) {
            wp_cache_delete('alloptions', 'options');
        }
        
        return $updated;
    }
}

/**
 * Eliminar una opción de configuración
 *
 * @since 1.0.0
 * @param string $option Nombre de la opción
 * @return bool Éxito o fallo al eliminar
 */
if (!function_exists('wp_pos_delete_option')) {
    function wp_pos_delete_option($option) {
        $options = get_option('wp_pos_options', array());
        
        // Comprobar si la opción existe
        if (!isset($options[$option])) {
            return false;
        }
        
        // Eliminar opción
        unset($options[$option]);
        
        // Guardar opciones
        return update_option('wp_pos_options', $options);
    }
}

/**
 * Obtener una URL para la pantalla de configuración
 *
 * @since 1.0.0
 * @param string $group Grupo de configuración (opcional)
 * @param array $args Argumentos adicionales (opcional)
 * @return string URL de configuración
 */
if (!function_exists('wp_pos_get_settings_url')) {
    function wp_pos_get_settings_url($group = 'general', $args = array()) {
        $base_url = admin_url('admin.php?page=wp-pos-settings');
        
        // Añadir grupo si está especificado
        if (!empty($group)) {
            $base_url = wp_pos_safe_add_query_arg(array('group' => $group), $base_url);
        }
        
        // Añadir argumentos adicionales
        if (!empty($args) && is_array($args)) {
            $base_url = wp_pos_safe_add_query_arg($args, $base_url);
        }
        
        return $base_url;
    }
}

/**
 * Verificar si una funcionalidad está habilitada
 *
 * @since 1.0.0
 * @param string $feature Nombre de la funcionalidad
 * @return bool True si está habilitada
 */
function wp_pos_is_feature_enabled($feature) {
    return 'yes' === wp_pos_get_option($feature . '_enabled', 'no');
}

/**
 * Obtener opción de configuración como array
 *
 * Para opciones que se almacenan como cadenas separadas por comas o por saltos de línea
 *
 * @since 1.0.0
 * @param string $option Nombre de la opción
 * @param string $separator Separador (coma, barra vertical, etc.)
 * @param mixed $default Valor por defecto
 * @return array Valores separados en un array
 */
if (!function_exists('wp_pos_get_option_array')) {
    function wp_pos_get_option_array($option, $separator = ',', $default = array()) {
        $value = wp_pos_get_option($option, '');
        
        if (empty($value)) {
            return $default;
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        $array_values = explode($separator, $value);
        $array_values = array_map('trim', $array_values);
        $array_values = array_filter($array_values);
        
        return !empty($array_values) ? $array_values : $default;
    }
}

/**
 * Comprobar si una opción está habilitada
 *
 * @since 1.0.0
 * @param string $option Nombre de la opción
 * @param bool $default Valor por defecto
 * @return bool Si está habilitada o no
 */
if (!function_exists('wp_pos_is_option_enabled')) {
    function wp_pos_is_option_enabled($option, $default = false) {
        $value = wp_pos_get_option($option, $default);
        
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

/**
 * Comprobar si el modo de depuración está habilitado
 *
 * @since 1.0.0
 * @return bool Si está habilitado o no
 */
if (!function_exists('wp_pos_is_debug_mode')) {
    function wp_pos_is_debug_mode() {
        return wp_pos_is_option_enabled('debug_mode', false);
    }
}

/**
 * Comprobar si los impuestos están habilitados
 *
 * @since 1.0.0
 * @return bool True si los impuestos están habilitados
 */
if (!function_exists('wp_pos_tax_enabled')) {
    function wp_pos_tax_enabled() {
        return 'yes' === wp_pos_get_option('tax_enabled', 'no');
    }
}

/**
 * Comprobar si hay impuestos incluidos en los precios
 *
 * @since 1.0.0
 * @return bool True si los precios incluyen impuestos
 */
if (!function_exists('wp_pos_prices_include_tax')) {
    function wp_pos_prices_include_tax() {
        return 'yes' === wp_pos_get_option('prices_include_tax', 'no');
    }
}

/**
 * Obtener símbolo de moneda
 *
 * @since 1.0.0
 * @return string Símbolo de moneda (siempre devuelve el símbolo de peso argentino)
 */
if (!function_exists('wp_pos_get_currency_symbol')) {
    function wp_pos_get_currency_symbol() {
        // Forzar el símbolo de peso argentino
        return '$';
    }
}

/**
 * Obtener lista de símbolos de moneda
 *
 * @since 1.0.0
 * @return array Símbolos de moneda
 */
if (!function_exists('wp_pos_get_currency_symbols')) {
    function wp_pos_get_currency_symbols() {
        return array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'ARS' => '$',
            'BRL' => 'R$',
            'CAD' => '$',
            'CLP' => '$',
            'CNY' => '¥',
            'COP' => '$',
            'MXN' => '$',
            'PEN' => 'S/',
            'UYU' => '$U',
            'VEF' => 'Bs',
        );
    }
}

/**
 * Dar formato a un precio con símbolo de moneda
 *
 * @since 1.0.0
 * @param float $price Precio a formatear
 * @param array $args Argumentos adicionales
 * @return string Precio formateado
 */
if (!function_exists('wp_pos_format_price')) {
    function wp_pos_format_price($price, $args = array()) {
        $price = floatval($price);
        
        // Valores por defecto
        $defaults = array(
            'currency'           => wp_pos_get_option('currency', 'USD'),
            'decimal_separator'  => wp_pos_get_option('decimal_separator', '.'),
            'thousand_separator' => wp_pos_get_option('thousand_separator', ','),
            'decimals'           => (int) wp_pos_get_option('price_decimals', 2),
            'price_format'       => wp_pos_get_option('price_format', '%s%v'),
        );
        
        // Fusionar con valores por defecto
        $args = wp_parse_args($args, $defaults);
        
        // Obtener símbolo de moneda
        $symbols = wp_pos_get_currency_symbols();
        $currency_symbol = isset($symbols[$args['currency']]) ? $symbols[$args['currency']] : '$';
        
        // Formatear el precio
        $formatted_price = number_format(
            $price,
            $args['decimals'],
            $args['decimal_separator'],
            $args['thousand_separator']
        );
        
        // Reemplazar marcadores de posición con valores
        // %s = símbolo, %v = valor
        $price_format = str_replace('%v', $formatted_price, $args['price_format']);
        $formatted = str_replace('%s', $currency_symbol, $price_format);
        
        return $formatted;
    }
}

/**
 * Registrar mensaje de log
 *
 * @since 1.0.0
 * @param string $message Mensaje a registrar
 * @param string $level Nivel del mensaje (debug, info, warning, error)
 * @param array $context Datos adicionales
 */
if (!function_exists('wp_pos_log')) {
    function wp_pos_log($message, $level = 'info', $context = array()) {
        // Solo registrar si el debug está activado
        if (!wp_pos_is_debug_mode() && $level !== 'error') {
            return;
        }
        
        // Formatear contexto
        $context_string = '';
        if (!empty($context)) {
            $context_string = ' ' . json_encode($context);
        }
        
        // Preparar mensaje
        $log_message = date('[Y-m-d H:i:s]') . ' ' . strtoupper($level) . ': ' . $message . $context_string . PHP_EOL;
        
        // Ruta del archivo de log
        $log_dir = WP_POS_PLUGIN_DIR . 'logs';
        $log_file = $log_dir . '/wp-pos-' . date('Y-m-d') . '.log';
        
        // Crear directorio si no existe
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        // Registrar mensaje
        @file_put_contents($log_file, $log_message, FILE_APPEND);
        
        // Notificar para integraciones
        do_action('wp_pos_log_message', $message, $level, $context);
    }
}

/**
 * Obtener todas las opciones de configuración del plugin
 *
 * @since 1.0.0
 * @return array Todas las opciones de configuración
 */
if (!function_exists('wp_pos_get_all_options')) {
    function wp_pos_get_all_options() {
        // Obtener todas las opciones de WP-POS
        $options = get_option('wp_pos_options', array());
        
        // Si no hay opciones, devolver un array vacío
        if (!is_array($options)) {
            $options = array();
        }
        
        // Permitir que otros plugins filtren las opciones
        return apply_filters('wp_pos_all_options', $options);
    }
}
