<?php
/**
 * Funciones bu00e1sicas del core para el plugin WP-POS
 *
 * Proporciona funciones de utilidad usadas en todo el sistema.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener opciones de configuraciu00f3n del plugin
 *
 * @since 1.0.0
 * @param string $option Opciu00f3n especu00edfica a obtener
 * @param mixed $default Valor por defecto si la opciu00f3n no existe
 * @return mixed Valor de la opciu00f3n o array completo de opciones
 */
function wp_pos_get_option($option = null, $default = null) {
    static $cached_options = null;
    
    // Si ya tenemos las opciones en cachu00e9 para esta ejecuciu00f3n de PHP, usarlas
    if ($cached_options !== null) {
        if (is_null($option)) {
            return $cached_options;
        }
        
        return isset($cached_options[$option]) ? $cached_options[$option] : $default;
    }
    
    // Intentar usar el Cache Manager si estu00e1 disponible
    $options = null;
    if (class_exists('WP_POS_Cache_Manager')) {
        $cache = WP_POS_Cache_Manager::get_instance();
        $options = $cache->get('all_options', 'settings');
    }
    
    // Si no hay opciones en cachu00e9 o el cache manager no estu00e1 disponible, obtener de la base de datos
    if (false === $options || null === $options) {
        $options = get_option('wp_pos_options', array());
        
        // Guardar en cachu00e9 si el Cache Manager estu00e1 disponible
        if (class_exists('WP_POS_Cache_Manager')) {
            $cache->set('all_options', $options, 'settings');
        }
    }
    
    // Si las opciones estu00e1n vaci00edas, usaremos las opciones predeterminadas del instalador
    if (empty($options)) {
        // Opciones predeterminadas
        $options = array(
            // Informaciu00f3n del negocio
            'business_name' => get_bloginfo('name'),
            'business_address' => '',
            'business_phone' => '',
            'business_email' => get_bloginfo('admin_email'),
            'business_logo' => '',
            
            // Configuraciu00f3n general
            'pos_page_id' => 0,
            'restrict_access' => 'yes',
            'enable_keyboard_shortcuts' => 'yes',
            'enable_barcode_scanner' => 'yes',
            
            // Opciones de venta
            'add_customer_to_sale' => 'optional',
            'default_tax_rate' => '0',
            'enable_discount' => 'yes',
            'default_payment_method' => 'cash',
            
            // Opciones de impresi00f3n
            'receipt_template' => 'default',
            'receipt_logo' => '',
            'receipt_store_name' => get_bloginfo('name'),
            'receipt_store_address' => '',
            'receipt_store_phone' => '',
            'receipt_footer' => __('Gracias por su compra', 'wp-pos'),
            'print_automatically' => 'no',
            
            // Opciones de moneda y formato
            'currency' => 'USD',
            'currency_position' => 'left',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'decimals' => 2,
            
            // Opciones de interfaz
            'products_per_page' => 20,
            'default_product_orderby' => 'title',
            'default_product_order' => 'ASC',
            'show_product_images' => 'yes',
            'show_categories_filter' => 'yes',
            
            // Opciones de stock
            'update_stock' => 'yes',
            'low_stock_threshold' => 2,
            'show_out_of_stock' => 'yes',
        );
    }
    
    // Guardar las opciones en cachu00e9 para esta ejecuciu00f3n
    $cached_options = $options;
    
    if (is_null($option)) {
        return $options;
    }
    
    return isset($options[$option]) ? $options[$option] : $default;
}

/**
 * Actualizar opciones de configuraciu00f3n del plugin
 *
 * @since 1.0.0
 * @param string $option Opciu00f3n a actualizar
 * @param mixed $value Nuevo valor
 * @return bool u00c9xito de la operaciu00f3n
 */
function wp_pos_update_option($option, $value) {
    $options = wp_pos_get_option();
    $options[$option] = $value;
    
    $updated = update_option('wp_pos_options', $options);
    
    if ($updated) {
        // Limpiar cachu00e9
        WP_POS_Cache_Manager::get_instance()->clear_settings_cache();
    }
    
    return $updated;
}

/**
 * Verificar si un usuario tiene permisos para una capacidad especu00edfica
 *
 * @since 1.0.0
 * @param string $capability Capacidad a verificar
 * @param int $user_id ID del usuario (opcional, usa usuario actual por defecto)
 * @return bool True si tiene permiso, False si no
 */
/**
 * Verificar si un usuario tiene permiso para realizar una acciu00f3n especu00edfica.
 * Los administradores SIEMPRE tenu00edan acceso completo sin restricciones.
 *
 * @param string $capability Capacidad a verificar
 * @param int|null $user_id ID del usuario, si es null se usa el usuario actual
 * @return bool True si tiene permiso, False si no
 */
function wp_pos_current_user_can($capability, $user_id = null) {
    // Si no se especificu00f3 un usuario, usar el usuario actual
    if (null === $user_id) {
        $user_id = get_current_user_id();
    }
    
    // Obtener datos del usuario
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    // ADMIN PRIORITY: Los administradores SIEMPRE tienen acceso total a TODO el sistema
    if (in_array('administrator', $user->roles)) {
        return true;
    }
    
    // Para otros usuarios, verificar la capacidad especu00edfica
    return $user->has_cap($capability);
}

/**
 * Generar un nu00famero u00fanico para una venta
 *
 * @since 1.0.0
 * @return string Nu00famero de venta formateado
 */
function wp_pos_generate_sale_number() {
    $prefix = apply_filters('wp_pos_sale_number_prefix', 'POS-');
    $date_part = date('Ymd');
    $count_key = 'sale_count_' . $date_part;
    
    // Obtener contador actual
    $count = get_option($count_key, 0);
    $count++;
    
    // Actualizar contador
    update_option($count_key, $count);
    
    // Generar nu00famero
    $number = $prefix . $date_part . '-' . sprintf('%04d', $count);
    
    return apply_filters('wp_pos_sale_number', $number, $count, $date_part);
}

/**
 * Formatear un precio segu00fan la configuraciu00f3n
 *
 * @since 1.0.0
 * @param float $price Precio a formatear
 * @param array $args Argumentos de formateo (opcional)
 * @return string Precio formateado
 */
function wp_pos_format_price($price, $args = array()) {
    // Verificar si WooCommerce estu00e1 activo
    $woocommerce_active = function_exists('WC');
    
    // Valores por defecto si WooCommerce no estu00e1 activo
    $default_currency = 'USD';
    $default_decimals = 2;
    $default_decimal_separator = '.';
    $default_thousand_separator = ',';
    $default_format = 'left';
    
    // Establecer argumentos por defecto
    $args = wp_parse_args($args, array(
        'currency' => wp_pos_get_option('currency', $woocommerce_active ? get_woocommerce_currency() : $default_currency),
        'decimals' => wp_pos_get_option('decimals', $woocommerce_active ? wc_get_price_decimals() : $default_decimals),
        'decimal_separator' => wp_pos_get_option('decimal_separator', $woocommerce_active ? wc_get_price_decimal_separator() : $default_decimal_separator),
        'thousand_separator' => wp_pos_get_option('thousand_separator', $woocommerce_active ? wc_get_price_thousand_separator() : $default_thousand_separator),
        'format' => wp_pos_get_option('currency_position', $woocommerce_active ? get_option('woocommerce_currency_pos', 'left') : $default_format),
    ));
    
    // Si WooCommerce estu00e1 activo, usar su formateador
    if ($woocommerce_active) {
        $formatted_price = wc_price($price, $args);
    } else {
        // Implementaciu00f3n propia de formato de precio
        $price = number_format($price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator']);
        
        // Aplicar formato segu00fan posiciu00f3n de moneda
        switch ($args['format']) {
            case 'left':
                $formatted_price = $args['currency'] . $price;
                break;
            case 'right':
                $formatted_price = $price . $args['currency'];
                break;
            case 'left_space':
                $formatted_price = $args['currency'] . ' ' . $price;
                break;
            case 'right_space':
                $formatted_price = $price . ' ' . $args['currency'];
                break;
            default:
                $formatted_price = $args['currency'] . $price;
        }
    }
    
    return apply_filters('wp_pos_formatted_price', $formatted_price, $price, $args);
}

/**
 * Registrar un error en el log
 *
 * @since 1.0.0
 * @param string $message Mensaje de error
 * @param string $level Nivel de error (error, warning, info, debug)
 * @param array $context Datos adicionales de contexto
 */
function wp_pos_log($message, $level = 'info', $context = array()) {
    // Verificar si el logging estu00e1 activado
    if ('yes' !== wp_pos_get_option('enable_logging', 'yes')) {
        return;
    }
    
    // Preparar datos
    $data = array(
        'time' => current_time('mysql'),
        'level' => $level,
        'message' => $message,
        'context' => $context,
    );
    
    // Filtrar datos antes de guardar
    $data = apply_filters('wp_pos_log_data', $data, $level, $context);
    
    // Au00f1adir a la tabla de log si existe
    global $wpdb;
    $table_name = $wpdb->prefix . 'wp_pos_logs';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        $wpdb->insert(
            $table_name,
            array(
                'log_time' => $data['time'],
                'log_level' => $data['level'],
                'log_message' => $data['message'],
                'log_context' => maybe_serialize($data['context']),
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    // Acciu00f3n para integrar con otros sistemas de log
    do_action('wp_pos_logged_message', $data);
    
    // Si es error, tambiu00e9n usar error_log de PHP
    if ('error' === $level) {
        error_log(sprintf('[WP-POS] %s: %s', $level, $message));
    }
}

/**
 * Verificar si WooCommerce estu00e1 activo
 *
 * @since 1.0.0
 * @return bool True si WooCommerce estu00e1 activo, False si no
 */
function wp_pos_is_woocommerce_active() {
    return class_exists('WooCommerce');
}

/**
 * Obtener URL a asset del plugin
 *
 * @since 1.0.0
 * @param string $path Ruta relativa del asset
 * @return string URL completa al asset
 */
function wp_pos_asset_url($path) {
    return WP_POS_PLUGIN_URL . 'assets/' . ltrim($path, '/');
}

/**
 * Obtener ruta a archivo de template
 *
 * @since 1.0.0
 * @param string $template Nombre del template
 * @param string $extension Extensiu00f3n del archivo (default: php)
 * @return string Ruta al archivo de template o false si no existe
 */
function wp_pos_get_template_path($template, $extension = 'php') {
    $template = sanitize_file_name($template . '.' . $extension);
    
    // Primero buscar en directorio del tema
    $theme_template = get_stylesheet_directory() . '/wp-pos/' . $template;
    
    if (file_exists($theme_template)) {
        return $theme_template;
    }
    
    // Luego en el directorio del plugin
    $plugin_template = WP_POS_PLUGIN_DIR . 'templates/' . $template;
    
    if (file_exists($plugin_template)) {
        return $plugin_template;
    }
    
    // No se encontru00f3 el template
    wp_pos_log(
        sprintf(__('Template %s no encontrado.', 'wp-pos'), $template),
        'warning'
    );
    
    return false;
}

/**
 * Cargar un template con datos
 *
 * @since 1.0.0
 * @param string $template Nombre del template
 * @param array $args Variables a pasar al template
 * @param bool $return Si es true, devuelve el contenido en lugar de imprimirlo
 * @return string|void Contenido del template si $return es true
 */
function wp_pos_load_template($template, $args = array(), $return = false) {
    $template_path = wp_pos_get_template_path($template);
    
    if (!$template_path) {
        return '';
    }
    
    if ($return) {
        ob_start();
    }
    
    // Extraer variables para que estu00e9n disponibles en el template
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    include $template_path;
    
    if ($return) {
        return ob_get_clean();
    }
}

/**
 * Validar un nu00famero de venta (ej: POS-20230101-0001)
 *
 * @since 1.0.0
 * @param string $sale_number Nu00famero a validar
 * @return bool True si es vu00e1lido, False si no
 */
function wp_pos_validate_sale_number($sale_number) {
    $prefix = apply_filters('wp_pos_sale_number_prefix', 'POS-');
    $pattern = '/^' . preg_quote($prefix, '/') . '\d{8}-\d{4}$/';
    
    return (bool) preg_match($pattern, $sale_number);
}

/**
 * Obtener lista de estados de venta disponibles
 *
 * @since 1.0.0
 * @return array Estados de venta
 */
function wp_pos_get_sale_statuses() {
    $statuses = array(
        'pending'   => __('Pendiente', 'wp-pos'),
        'completed' => __('Completada', 'wp-pos'),
        'refunded'  => __('Reembolsada', 'wp-pos'),
        'cancelled' => __('Cancelada', 'wp-pos'),
        'on-hold'   => __('En espera', 'wp-pos'),
    );
    
    return apply_filters('wp_pos_sale_statuses', $statuses);
}

/**
 * Obtener lista de mu00e9todos de pago disponibles
 *
 * @since 1.0.0
 * @return array Mu00e9todos de pago
 */
function wp_pos_get_payment_methods() {
    $methods = array(
        'cash'      => __('Efectivo', 'wp-pos'),
        'card'      => __('Tarjeta de cru00e9dito/du00e9bito', 'wp-pos'),
        'transfer'  => __('Transferencia bancaria', 'wp-pos'),
        'check'     => __('Cheque', 'wp-pos'),
        'other'     => __('Otro', 'wp-pos'),
    );
    
    return apply_filters('wp_pos_payment_methods', $methods);
}

/**
 * Sanitizar un array de forma recursiva
 *
 * @since 1.0.0
 * @param array $array Array a sanitizar
 * @return array Array sanitizado
 */
function wp_pos_sanitize_array($array) {
    if (!is_array($array)) {
        return sanitize_text_field($array);
    }
    
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = wp_pos_sanitize_array($value);
        } else {
            $array[$key] = sanitize_text_field($value);
        }
    }
    
    return $array;
}

/**
 * Obtener usuario actual del POS
 *
 * @since 1.0.0
 * @return WP_User|false Objeto usuario o false si no hay usuario autenticado
 */
function wp_pos_get_current_user() {
    return wp_get_current_user();
}

/**
 * Obtener URL del panel de administraciu00f3n del POS
 *
 * @since 1.0.0
 * @param string $tab Pestau00f1a especu00edfica
 * @param array $args Paru00e1metros adicionales
 * @return string URL al panel
 */
function wp_pos_get_admin_url($tab = '', $args = array()) {
    // Verificar que el tab sea un string
    if (!is_string($tab)) {
        $tab = '';
    }
    
    // Verificar que args sea un array
    if (!is_array($args)) {
        $args = array();
    }
    
    // Asignar la pu00e1gina correcta basada en el tab
    switch ($tab) {
        case 'sales':
            $page = 'wp-pos-sales';
            break;
            
        case 'new-sale':
            $page = 'wp-pos-new-sale-v2';
            break;
            
        case 'sale-details':
            $page = 'wp-pos-sale-details';
            break;
            
        case 'print-receipt':
            $page = 'wp-pos-print-receipt';
            break;
            
        case 'reports':
            $page = 'wp-pos-reports';
            break;
            
        case 'settings':
            $page = 'wp-pos-settings';
            break;
            
        default:
            $page = 'wp-pos'; // Dashboard por defecto
            break;
    }
    
    $base_args = array('page' => $page);
    
    // Ya no necesitamos el paru00e1metro 'tab' pues ahora es parte del 'page'
    return wp_pos_safe_add_query_arg(array_merge($base_args, $args), admin_url('admin.php'));
}

/**
 * Obtener URL del punto de venta frontend
 *
 * @since 1.0.0
 * @param array $args Paru00e1metros adicionales
 * @return string URL al punto de venta
 */
function wp_pos_get_pos_url($args = array()) {
    // Intentar obtener pu00e1gina configurada
    $pos_page_id = wp_pos_get_option('pos_page_id', 0);
    
    if ($pos_page_id > 0) {
        $url = get_permalink($pos_page_id);
    } else {
        // Usar URL generada por shortcode
        $url = home_url('?wp_pos=1');
    }
    
    if (!empty($args)) {
        $url = wp_pos_safe_add_query_arg($args, $url);
    }
    
    return apply_filters('wp_pos_frontend_url', $url, $args);
}

/**
 * Obtener el formato de precio para usar en JavaScript
 *
 * @since 1.0.0
 * @return string Formato de precio para JavaScript
 */
function wp_pos_get_price_format() {
    $options = wp_pos_get_option();
    
    $currency_pos = isset($options['currency_position']) ? $options['currency_position'] : 'left';
    $format = '%s';
    $currency = wp_pos_get_currency_symbol();
    
    switch ($currency_pos) {
        case 'left':
            $format = $currency . '%s';
            break;
        case 'right':
            $format = '%s' . $currency;
            break;
        case 'left_space':
            $format = $currency . ' %s';
            break;
        case 'right_space':
            $format = '%s ' . $currency;
            break;
    }
    
    return $format;
}

/**
 * Obtener el símbolo de la moneda configurada
 *
 * @since 1.0.0
 * @return string Símbolo de moneda
 */
function wp_pos_get_currency_symbol() {
    $options = wp_pos_get_option();
    $currency = isset($options['currency']) ? $options['currency'] : 'USD';
    
    $symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'BRL' => 'R$',
        'MXN' => '$',
        'CLP' => '$',
        'ARS' => '$',
        'COP' => '$',
        'PEN' => 'S/',
        'BOB' => 'Bs',
        'VES' => 'Bs',
        'UYU' => '$',
        'PYG' => '₲',
        'CAD' => '$',
        'AUD' => '$',
        'CNY' => '¥',
        'INR' => '₹',
        'RUB' => '₽',
    );
    
    return isset($symbols[$currency]) ? $symbols[$currency] : $currency;
}

/**
 * Obtener la tasa de impuesto configurada
 *
 * @since 1.0.0
 * @return float Tasa de impuesto (porcentaje)
 */
function wp_pos_get_tax_rate() {
    $options = wp_pos_get_option();
    $tax_rate = isset($options['default_tax_rate']) ? (float) $options['default_tax_rate'] : 0;
    
    return $tax_rate;
}

/**
 * Funciu00f3n de depuraciu00f3n para el plugin POS
 *
 * @param mixed $data Los datos a depurar
 * @param string $label Etiqueta opcional
 * @param bool $write_to_file Si se debe escribir en archivo de log
 */
function wp_pos_debug($data, $label = '', $write_to_file = true) {
    // Formatear los datos para la depuración
    $output = "";
    
    if (!empty($label)) {
        $output .= "[" . $label . "] ";
    }
    
    if (is_array($data) || is_object($data)) {
        $output .= print_r($data, true);
    } else {
        $output .= $data;
    }
    
    // Ruta al archivo de log
    $log_file = WP_CONTENT_DIR . '/debug-pos.log';
    
    // Escribir al archivo de log
    if ($write_to_file) {
        // Obtener timestamp actual
        $timestamp = date('[Y-m-d H:i:s]');
        file_put_contents($log_file, $timestamp . " " . $output . "\n", FILE_APPEND);
    }
    
    // Mostrar en pantalla solo si estamos en modo depuración
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
        echo '<div class="notice notice-info is-dismissible"><pre>';
        echo esc_html($output);
        echo '</pre></div>';
    }
    
    return $data; // Devolver los datos para poder encadenar
}

/**
 * Versiu00f3n ultra-segura de add_query_arg que maneja valores nulos
 * y garantiza que no se pasen valores nulos a las funciones nativas de WordPress
 *
 * @since 1.0.0
 * @param mixed $args Array o string de argumentos a au00f1adir
 * @param mixed $url URL a la que au00f1adir los argumentos
 * @return string URL con argumentos au00f1adidos
 */
function wp_pos_safe_add_query_arg($args, $url = '') {
    // Sanitizar argumentos (garantiza que nunca sea null)
    if (is_null($args)) {
        $args = array();
    }
    
    // Si es array, verificar cada elemento para asegurar que no hay valores nulos
    if (is_array($args)) {
        foreach ($args as $key => $value) {
            if (is_null($value)) {
                $args[$key] = ''; // Reemplazar null con string vacu00edo
            }
        }
    }
    
    // Sanitizar URL (garantiza que nunca sea null)
    if (is_null($url)) {
        $url = '';
    } elseif (!is_string($url)) {
        $url = strval($url); // Convertir a string
    }
    
    // Si la URL estu00e1 vacu00eda, usar comportamiento nativo (pero asegurando que args no tenga nulls)
    if ($url === '') {
        return add_query_arg($args);
    }
    
    // Llamar a add_query_arg con paru00e1metros sanitizados
    return add_query_arg($args, $url);
}

/**
 * Versiu00f3n ultra-segura de esc_url que maneja valores nulos
 *
 * @since 1.0.0
 * @param mixed $url URL a escapar
 * @param array|string $protocols Protocolos permitidos
 * @return string URL escapada
 */
function wp_pos_safe_esc_url($url) {
    if (is_null($url)) {
        return '';
    }
    
    if (!is_string($url)) {
        $url = strval($url);
    }
    
    // Devolver la URL tal cual para no interferir con nonces o parámetros
    // Esto es crucial para que funcionen los links de acción como cancelar o eliminar
    return $url;
}

/**
 * Versiu00f3n ultra-segura de strpos que maneja valores nulos
 *
 * @since 1.0.0
 * @param mixed $haystack String en el que buscar
 * @param mixed $needle String a buscar
 * @param int $offset Posiciu00f3n desde la que empezar a buscar
 * @return int|false Posiciu00f3n donde se encontru00f3 la aguja o falso
 */
function wp_pos_safe_strpos($haystack, $needle, $offset = 0) {
    // Verificar y sanitizar haystack
    if (is_null($haystack)) {
        $haystack = '';
    }
    
    if (!is_string($haystack)) {
        $haystack = strval($haystack);
    }
    
    // Verificar y sanitizar needle
    if (is_null($needle)) {
        $needle = '';
    }
    
    if (!is_string($needle)) {
        $needle = strval($needle);
    }
    
    // Si needle estu00e1 vacu00edo, devolver 0 (comportamiento de PHP para needle vacu00edo)
    if ($needle === '') {
        return 0;
    }
    
    // Si haystack estu00e1 vacu00edo, no hay nada que buscar
    if ($haystack === '') {
        return false;
    }
    
    return strpos($haystack, $needle, $offset);
}

/**
 * Versiu00f3n ultra-segura de str_replace que maneja valores nulos
 *
 * @since 1.0.0
 * @param mixed $search String a buscar
 * @param mixed $replace String de reemplazo
 * @param mixed $subject String en el que realizar el reemplazo
 * @param int $count Nu00famero de reemplazos realizados
 * @return string|array String o array con los reemplazos realizados
 */
function wp_pos_safe_str_replace($search, $replace, $subject, &$count = null) {
    // Verificar y sanitizar search
    if (is_null($search)) {
        $search = '';
    }
    
    // Verificar y sanitizar replace
    if (is_null($replace)) {
        $replace = '';
    }
    
    // Verificar y sanitizar subject
    if (is_null($subject)) {
        // Si subject es null, no hay nada que reemplazar
        return '';
    }
    
    return str_replace($search, $replace, $subject, $count);
}
