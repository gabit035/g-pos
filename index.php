<?php
/**
 * Plugin Name: WP-POS (Point of Sale)
 * Plugin URI: https://wppossystem.com
 * Description: Sistema completo de punto de venta para WordPress con compatibilidad opcional con WooCommerce
 * Version: 1.2.1
 * Author: WP-POS Team
 * Author URI: https://wppossystem.com
 * Text Domain: wp-pos
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 *
 * @package WP-POS
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Carga de traducciones de manera segura para WordPress 6.7+
 * 
 * @since 1.0.0
 * @return bool Si las traducciones se cargaron correctamente
 */
function wp_pos_load_textdomain() {
    static $loaded = false;
    
    // Si ya se cargaron las traducciones, no hacer nada
    if ($loaded || did_action('wp_pos_textdomain_loaded')) {
        return true;
    }
    
    // No cargar demasiado temprano
    if (!did_action('plugins_loaded') && !doing_action('plugins_loaded')) {
        add_action('plugins_loaded', 'wp_pos_load_textdomain', 5);
        return false;
    }
    
    // Marcar como cargando para evitar bucles
    static $loading = false;
    if ($loading) {
        return false;
    }
    $loading = true;
    
    // Cargar las traducciones del plugin
    $domain = 'wp-pos';
    $locale = apply_filters('plugin_locale', is_admin() ? get_user_locale() : get_locale(), $domain);
    
    // Solo cargar traducciones si estamos después de plugins_loaded
    if (did_action('plugins_loaded')) {
        // 1. Primero intentar con load_plugin_textdomain
        $loaded = load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // 2. Si falla, intentar cargar manualmente
        if (!$loaded) {
            $mofile = WP_POS_PLUGIN_DIR . 'languages/' . $domain . '-' . $locale . '.mo';
            if (file_exists($mofile)) {
                $loaded = load_textdomain($domain, $mofile);
            }
        }
        
        // 3. Si aún falla, intentar con la ruta completa
        if (!$loaded) {
            $mofile = WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo';
            if (file_exists($mofile)) {
                $loaded = load_textdomain($domain, $mofile);
            }
        }
        
        // Marcar como cargado
        do_action('wp_pos_textdomain_loaded');
    }
    
    $loading = false;
    
    // Registrar error en el log de depuración si no se cargaron las traducciones
    if (!$loaded) {
        error_log('WP-POS: No se pudieron cargar las traducciones para el locale: ' . $locale);
    }
    
    return $loaded;
}

// Cargar traducciones después de plugins_loaded para evitar advertencias en WordPress 6.7+
add_action('plugins_loaded', 'wp_pos_load_textdomain', 5);

// Cargar también en init como respaldo
add_action('init', 'wp_pos_load_textdomain', 5);

// Función segura para obtener texto traducido
function wp_pos__($text, $domain = 'wp-pos') {
    // Si el dominio no es wp-pos, usar la función de traducción estándar
    if ($domain !== 'wp-pos') {
        return __($text, $domain);
    }
    
    // Si ya se cargaron las traducciones
    if (did_action('wp_pos_textdomain_loaded')) {
        return __($text, 'wp-pos');
    }
    
    // Si estamos en el proceso de inicialización, cargar las traducciones ahora
    if (doing_action('init')) {
        wp_pos_load_textdomain();
        return __($text, 'wp-pos');
    }
    
    // Si no se han cargado las traducciones y no estamos en init, 
    // programar la carga y devolver el texto sin traducir
    if (!did_action('init')) {
        add_action('init', 'wp_pos_load_textdomain', 10);
    }
    
    return $text;
}

// Función segura para hacer eco de texto traducido
function wp_pos__e($text, $domain = 'wp-pos') {
    echo esc_html(wp_pos__($text, $domain));
}

// Definir constantes básicas del plugin
define('WP_POS_VERSION', '1.2.1');
define('WP_POS_PLUGIN_FILE', __FILE__);
define('WP_POS_PLUGIN_DIR', trailingslashit(plugin_dir_path(__FILE__)));
define('WP_POS_PLUGIN_URL', trailingslashit(plugin_dir_url(__FILE__)));
define('WP_POS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Backward compatibility
define('WP_POS_URL', WP_POS_PLUGIN_URL);

// Definir rutas de directorios importantes
define('WP_POS_INCLUDES_DIR', WP_POS_PLUGIN_DIR . 'includes/');
define('WP_POS_TEMPLATES_DIR', WP_POS_PLUGIN_DIR . 'templates/');
define('WP_POS_MODULES_DIR', WP_POS_PLUGIN_DIR . 'modules/');
define('WP_POS_ASSETS_URL', WP_POS_PLUGIN_URL . 'assets/');

// Cargar funciones principales
if (file_exists(WP_POS_INCLUDES_DIR . 'functions/core-functions.php')) {
    require_once WP_POS_INCLUDES_DIR . 'functions/core-functions.php';
}

// Verificar si WooCommerce está activo
define('WP_POS_WOOCOMMERCE_ACTIVE', function_exists('wp_pos_is_woocommerce_active') ? wp_pos_is_woocommerce_active() : false);

// Asegurar que el directorio de idiomas exista
$languages_dir = WP_POS_PLUGIN_DIR . 'languages';
if (!file_exists($languages_dir)) {
    wp_mkdir_p($languages_dir);
}

// Cargar las funciones de traducciu00f3n seguras
require_once WP_POS_INCLUDES_DIR . 'functions/translation-functions.php';
define('WP_POS_ASSETS_DIR', WP_POS_PLUGIN_DIR . 'assets/');

// Cargar instaladores de tablas
require_once WP_POS_PLUGIN_DIR . 'includes/install-tables.php';
require_once WP_POS_PLUGIN_DIR . 'includes/install-products-table.php';
require_once WP_POS_PLUGIN_DIR . 'includes/install-services-table.php';

// Cargar archivos principales
require_once WP_POS_PLUGIN_DIR . 'includes/init/module-system.php';
require_once WP_POS_PLUGIN_DIR . 'includes/init/bootstrap.php';

// La función wp_pos_load_modules() ahora está definida en includes/init/bootstrap.php
// para mantener la lógica de carga de módulos en un solo lugar y evitar duplicaciones

// Incluir la nueva versión de la página de nueva venta
require_once WP_POS_PLUGIN_DIR . 'templates/register-new-sale-v2.php';

// Ya no necesitamos estos archivos ya que la Nueva Venta V2 ahora está integrada en el menú principal
// require_once WP_POS_PLUGIN_DIR . 'templates/debug-menu.php';
// require_once WP_POS_PLUGIN_DIR . 'force-register-menu.php';

// Incluir controladores AJAX para la versión 2
require_once WP_POS_PLUGIN_DIR . 'templates/ajax-handlers-v2.php';

// Incluir la funcionalidad para verificar y crear archivos de Nueva Venta V2
require_once WP_POS_PLUGIN_DIR . 'includes/init/ensure-v2-files.php';

// Incluir la herramienta de depuración
require_once WP_POS_PLUGIN_DIR . 'includes/admin/class-pos-debug-tool.php';

// Cargar el sistema modular de menús
require_once WP_POS_INCLUDES_DIR . 'init/module-system.php';

// La carga de módulos ahora se maneja desde includes/init/bootstrap.php

// Establecer la codificación UTF-8 para asegurar que los acentos se muestren correctamente
add_action('init', 'wp_pos_set_charset', 1);
function wp_pos_set_charset() {
    // Asegurar que WordPress use UTF-8
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
    
    // Asegurarse de que la codificación del contenido sea UTF-8
    add_filter('wp_headers', function($headers) {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        }
        return $headers;
    });
    
    // Asegurarse de que la codificación de la base de datos sea UTF-8
    add_filter('wp_db_charset', function() {
        return 'utf8mb4';
    });
    
    add_filter('wp_db_collate', function() {
        return 'utf8mb4_unicode_ci';
    });
}

/**
 * Carga de traducciones de forma segura
 * 
 * Implementamos una solución más robusta para WordPress 6.7+ que:
 * 1. Usa el hook 'init' como recomienda la documentación oficial
 * 2. Proporciona funciones de traducción seguras para uso temprano
 * 3. Desactiva temporalmente la función _load_textdomain_just_in_time para evitar advertencias
 */

// Definir funciones de traducción seguras que no intentan cargar traducciones prematuramente
if (!function_exists('wp_pos_translate')) {
    function wp_pos_translate($text, $domain = 'wp-pos') {
        // Simplemente devuelve el texto sin intentar traducir en esta etapa temprana
        return $text;
    }
}

// Interceptar y corregir valores nulos en funciones de WordPress
// Esta es una solución global para prevenir advertencias de depreciación
add_filter('esc_url', function($url) {
    if (is_null($url)) {
        return '';
    }
    return $url;
}, 1, 1);

add_filter('esc_url_raw', function($url) {
    if (is_null($url)) {
        return '';
    }
    return $url;
}, 1, 1);

// Cargar parches para corregir advertencias de depreciación
require_once WP_POS_PLUGIN_DIR . 'includes/fixes/deprecation-fixes.php';

/**
 * Verificar requisitos básicos antes de incluir bootstrap
 */
function wp_pos_check_basic_requirements() {
    $can_load = true;
    
    // Verificar versión de PHP
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $can_load = false;
        
        // Mostrar notificación de error de PHP
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                __('WP-POS requiere PHP 7.4 o superior. Tu servidor está ejecutando la versión %s.', 'wp-pos'),
                PHP_VERSION
            );
            echo '</p></div>';
        });
        
        // Desactivar plugin automáticamente
        add_action('admin_init', function() {
            deactivate_plugins(plugin_basename(__FILE__));
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        });
    }
    
    // Mostrar información sobre WooCommerce
    if (WP_POS_WOOCOMMERCE_ACTIVE) {
        // Verificar versión de WooCommerce
        $wc_version = defined('WC_VERSION') ? WC_VERSION : '0';
        if (version_compare($wc_version, '5.0', '<')) {
            add_action('admin_notices', function() use ($wc_version) {
                echo '<div class="notice notice-warning is-dismissible"><p>';
                echo sprintf(
                    __('WP-POS es compatible con WooCommerce 5.0 o superior. Tu tienda está ejecutando la versión %s.', 'wp-pos'),
                    $wc_version
                );
                echo '</p></div>';
            });
        } else {
            // Informar que se ha detectado WooCommerce
            add_action('admin_notices', function() {
                // Solo mostrar en páginas que no sean del plugin WP-POS
                if (!isset($_GET['page']) || !is_string($_GET['page']) || strpos($_GET['page'], 'wp-pos') === false) {
                    return;
                }
                echo '<div class="notice notice-info is-dismissible"><p>';
                echo __('WP-POS ha detectado WooCommerce y se ha activado la integración con la tienda.', 'wp-pos');
                echo '</p></div>';
            });
        }
    } else {
        // Informar que WooCommerce no está activo pero no es obligatorio
        /* Notificación desactivada a petición del usuario
        add_action('admin_notices', function() {
            // Solo mostrar en páginas que no sean del plugin WP-POS
            if (!isset($_GET['page']) || !is_string($_GET['page']) || strpos($_GET['page'], 'wp-pos') === false) {
                return;
            }
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo __('WP-POS se está ejecutando en modo independiente. Para utilizar la integración con WooCommerce, activa el plugin.', 'wp-pos');
            echo '</p></div>';
        });
        */
    }
    
    return $can_load;
}

/**
 * Inicializar el plugin
 */
function wp_pos_init() {
    // Revisar requisitos básicos
    if (!wp_pos_check_basic_requirements()) {
        return;
    }
    
    // Definir constantes globales para incluir archivos
    define('WP_POS_FUNCTIONS_DIR', WP_POS_INCLUDES_DIR . 'functions/');
    
    // Cargar funciones core primero (necesarias para el instalador)
    require_once WP_POS_FUNCTIONS_DIR . 'core-functions.php';
    
    // Cargar las funciones de plantilla (necesarias para los templates)
    require_once WP_POS_FUNCTIONS_DIR . 'template-functions.php';
    
    // Cargar clases principales
    require_once WP_POS_INCLUDES_DIR . 'class/class-pos-cache-manager.php';
    
    // Cargar gestor de roles
    require_once WP_POS_INCLUDES_DIR . 'class-wp-pos-roles.php';
    
    // Cargar migración de roles de cliente
    require_once WP_POS_INCLUDES_DIR . 'migrations/migrate-customer-roles.php';
    
    // Cargar archivo de inicialización principal
    require_once WP_POS_INCLUDES_DIR . 'init/bootstrap.php';
    
    // Cargar sistema de optimización de rendimiento
    require_once WP_POS_INCLUDES_DIR . 'class-pos-performance-loader.php';
    require_once WP_POS_INCLUDES_DIR . 'init/performance-menu-link.php';
    
    // Cargar sincronizador de umbrales de stock bajo
    require_once WP_POS_INCLUDES_DIR . 'init/stock-threshold-sync.php';
    require_once WP_POS_INCLUDES_DIR . 'init/stock-notifications-init.php';
    
    // Cargar sistema de alertas directas en dashboard
    require_once WP_POS_INCLUDES_DIR . 'direct-stock-alerts.php';
    
    // Herramientas de diagnóstico movidas a la carpeta temp
    
    // Registrar hooks de activación/desactivación
    register_activation_hook(__FILE__, 'wp_pos_activate');
    register_deactivation_hook(__FILE__, 'wp_pos_deactivate');
}

// Funciones para activar y desactivar el plugin
if (!function_exists('wp_pos_activate')) {
    /**
     * Callback de activación: crear tablas necesarias
     */
    function wp_pos_activate() {
        // Crear tablas personalizadas
        if (function_exists('wp_pos_create_missing_tables')) {
            wp_pos_create_missing_tables();
        }
        if (function_exists('wp_pos_create_products_table')) {
            wp_pos_create_products_table();
        }
    }
}
if (!function_exists('wp_pos_deactivate')) {
    /**
     * Callback de desactivación
     */
    function wp_pos_deactivate() {
        // No se requieren acciones al desactivar
    }
}

/**
 * Código de activación del plugin
 */
function wp_pos_activate() {
    // Cargar el instalador
    require_once WP_POS_INCLUDES_DIR . 'class/class-pos-installer.php';
    $installer = WP_POS_Installer::get_instance();
    
    // Llamar al método activate() que se encarga de la instalación
    $installer->activate();
    
    // Asegurar que existan todos los archivos necesarios para la versión V2
    if (function_exists('wp_pos_ensure_v2_files')) {
        wp_pos_ensure_v2_files();
    }
    
    // Actualizar versión en opciones
    update_option('wp_pos_version', WP_POS_VERSION);
    
    // Limpiar cache de permisos (usando el método recomendado)
    // WP_Roles::for_site() no puede ser llamado estaticamente
    // La mejor solución es usar directamente wp_roles() que internamente hace el reinit necesario
    wp_roles();
    
    // Limpiar reglas de rewrite
    flush_rewrite_rules();
}

/**
 * Código de desactivación del plugin
 */
function wp_pos_deactivate() {
    // Limpiar reglas de rewrite
    flush_rewrite_rules();
    
    // Registrar que el plugin fue desactivado
    update_option('wp_pos_deactivated', current_time('mysql'));
}

// Inicializar plugin
add_action('plugins_loaded', 'wp_pos_init', 10);
