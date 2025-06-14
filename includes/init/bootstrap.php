<?php
/**
 * Bootstrap para el plugin WP-POS
 *
 * Archivo de inicializacin que configura y arranca el sistema.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevencin de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Garantizar que los administradores tengan acceso a todo el sistema POS
 * sin importar qué capacidad se esté verificando
 */
add_filter('user_has_cap', 'wp_pos_admin_has_all_caps', 10, 4);
function wp_pos_admin_has_all_caps($allcaps, $caps, $args, $user) {
    // Si es un administrador y la capacidad empieza con 'view_' o 'manage_pos_' o contiene '_pos' o 'pos_'
    // directamente garantiza el acceso
    if (!empty($user->roles) && in_array('administrator', $user->roles)) {
        foreach ($caps as $cap) {
            // Dar acceso directo a todas las capacidades del sistema POS para administradores
            if (strpos($cap, 'view_') === 0 || 
                strpos($cap, 'manage_pos_') === 0 || 
                strpos($cap, '_pos') !== false || 
                strpos($cap, 'pos_') !== false) {
                $allcaps[$cap] = true;
            }
        }
    }
    return $allcaps;
}

/**
 * Funcin de inicializacin del sistema
 *
 * @since 1.0.0
 */
function wp_pos_bootstrap() {
    // Las constantes ya estn definidas en index.php
    // No hay necesidad de llamar a wp_pos_define_constants()
    
    // Primero incluir archivos de funciones bsicas
    // para garantizar que las funciones estn disponibles
    // Comentamos estas lneas porque ya se cargan en index.php
    // require_once WP_POS_INCLUDES_DIR . 'functions/core-functions.php';
    // require_once WP_POS_INCLUDES_DIR . 'functions/template-functions.php';
    
    // Luego incluir el resto de archivos
    wp_pos_include_core_files();
    
    // Verificar WooCommerce
    wp_pos_check_woocommerce();
    
    // Inicializar interfaces
    add_action('plugins_loaded', 'wp_pos_init_plugin');
    
    // Activacin/desactivacin
    register_activation_hook(WP_POS_PLUGIN_FILE, 'wp_pos_activate');
    register_deactivation_hook(WP_POS_PLUGIN_FILE, 'wp_pos_deactivate');
    
    // Crear tablas si es necesario
    add_action('plugins_loaded', 'wp_pos_create_tables_if_needed');
    
    // La verificacin de requisitos ya se realiza en index.php
    // antes de cargar este archivo
    
    // Registrar capacidades
    wp_pos_register_capabilities();
    
    // Registrar hooks de menu de administracin
    add_action('admin_menu', 'wp_pos_register_admin_menu');
    
    // Registrar shortcodes
    add_shortcode('wp_pos', 'wp_pos_shortcode');
    
    // Registrar endpoints de API REST
    add_action('rest_api_init', 'wp_pos_register_rest_routes');
    
    // Inicializar componentes adicionales segn el contexto
    if (is_admin()) {
        wp_pos_admin_init();
    } else {
        wp_pos_frontend_init();
    }
    
    // Ejecutar inicializacion personalizada
    do_action('wp_pos_bootstrapped');
}

/**
 * Notificacin de versin de PHP insuficiente
 * 
 * Esta funcin se mantiene para compatibilidad, pero es reemplazada por
 * la verificacin en index.php
 *
 * @since 1.0.0
 */
function wp_pos_php_version_notice() {
    echo '<div class="notice notice-error"><p>';
    _e('WP-POS requiere PHP 7.0 o superior. Por favor, actualiza tu versin de PHP para utilizar este plugin.', 'wp-pos');
    echo '</p></div>';
}

/**
 * Notificacin de WooCommerce no activo
 * 
 * Esta funcin se mantiene para compatibilidad, pero es reemplazada por
 * la verificacin en index.php
 *
 * @since 1.0.0
 */
function wp_pos_woocommerce_notice() {
    // Funcin desactivada a peticiu00f3n del usuario
    return;
    /* Notificaciu00f3n original desactivada
    echo '<div class="notice notice-warning is-dismissible"><p>';
    _e('WP-POS se est ejecutando en modo independiente. Para aprovechar todas las funcionalidades de integracin con WooCommerce, activa el plugin WooCommerce.', 'wp-pos');
    echo '</p></div>';
    */
}

/**
 * Notificacin de versin de WooCommerce insuficiente
 * 
 * Esta funcin se mantiene para compatibilidad, pero es reemplazada por
 * la verificacin en index.php
 *
 * @since 1.1.0
 */
function wp_pos_woocommerce_version_notice() {
    $wc_version = defined('WC_VERSION') ? WC_VERSION : '0';
    echo '<div class="notice notice-warning is-dismissible"><p>';
    printf(
        __('WP-POS detect WooCommerce %s pero recomienda la versin 5.0 o superior para una compatibilidad ptima. Algunas funcionalidades de integracin podrn no funcionar correctamente.', 'wp-pos'),
        $wc_version
    );
    echo '</p></div>';
}

/**
 * Registrar capacidades del plugin
 *
 * @since 1.0.0
 */
function wp_pos_register_capabilities() {
    global $wp_roles;

    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }

    // Aadir capacidades al rol de administrador
    $wp_roles->add_cap('administrator', 'manage_pos');
    $wp_roles->add_cap('administrator', 'view_pos');
    $wp_roles->add_cap('administrator', 'process_sales');
    $wp_roles->add_cap('administrator', 'manage_pos_settings');
    $wp_roles->add_cap('administrator', 'view_reports');
    $wp_roles->add_cap('administrator', 'view_sales');
    $wp_roles->add_cap('administrator', 'create_sales');
    $wp_roles->add_cap('administrator', 'view_customers');
    
    // Si existe el rol shop_manager, aadir capacidades
    if ($wp_roles->is_role('shop_manager')) {
        $wp_roles->add_cap('shop_manager', 'view_pos');
        $wp_roles->add_cap('shop_manager', 'process_sales');
        $wp_roles->add_cap('shop_manager', 'view_reports');
        $wp_roles->add_cap('shop_manager', 'view_sales');
        $wp_roles->add_cap('shop_manager', 'create_sales');
    }
}

/**
 * Registrar menús de administración
 *
 * @since 1.0.0
 */
function wp_pos_register_admin_menu() {
    // Si es administrador, siempre permitir acceso sin verificar nada más
    if (current_user_can('manage_options')) {
        // Los administradores siempre tienen acceso - continuar con el registro del menú
    }
    // Para roles no-administrador, verificar capacidades requeridas
    else if (!current_user_can('view_pos') && !current_user_can('access_pos')) {
        return; // No tiene permisos para acceder al sistema POS
    }
    
    // Página principal - Permitir a todos los roles con 'access_pos' o 'view_pos'
    add_menu_page(
        __('WP-POS', 'wp-pos'),
        __('WP-POS', 'wp-pos'),
        'read', // Cualquier usuario registrado puede ver el menú (después filtraremos el acceso)
        'wp-pos',
        'wp_pos_admin_page',
        'dashicons-cart',
        25
    );
    
    // Página principal (enlace en submenu)
    add_submenu_page(
        'wp-pos',
        __('Dashboard', 'wp-pos'),
        __('Dashboard', 'wp-pos'),
        'access_pos',
        'wp-pos',
        'wp_pos_admin_page'
    );
    
    // Ventas
    add_submenu_page(
        'wp-pos',
        __('Ventas', 'wp-pos'),
        __('Ventas', 'wp-pos'),
        apply_filters('wp_pos_menu_capability', 'view_sales', 'sales'),
        'wp-pos-sales',
        'wp_pos_sales_page'
    );
    
    // Nueva venta (usando versión V2)
    add_submenu_page(
        'wp-pos',
        __('Nueva Venta', 'wp-pos'),
        __('Nueva Venta', 'wp-pos'),
        apply_filters('wp_pos_menu_capability', 'process_sales', 'sales'),
        'wp-pos-new-sale-v2',
        'wp_pos_render_new_sale_v2'
    );
    
    // Detalles de venta (oculto en el menu)
    add_submenu_page(
        null, // Oculto del menu
        __('Detalles de Venta', 'wp-pos'),
        __('Detalles de Venta', 'wp-pos'),
        'view_sales',
        'wp-pos-sale-details',
        'wp_pos_sale_details_page'
    );
    
    // Imprimir recibo (oculto en el menu)
    add_submenu_page(
        null, // Oculto del menu
        __('Imprimir Recibo', 'wp-pos'),
        __('Imprimir Recibo', 'wp-pos'),
        'view_sales',
        'wp-pos-print-receipt',
        'wp_pos_print_receipt_page'
    );
    
    // Reportes
    add_submenu_page(
        'wp-pos',
        __('Reportes', 'wp-pos'),
        __('Reportes', 'wp-pos'),
        'view_reports',
        'wp-pos-reports',
        'wp_pos_reports_page'
    );
    
    // Clientes
    add_submenu_page(
        'wp-pos',
        __('Clientes', 'wp-pos'),
        __('Clientes', 'wp-pos'),
        'view_customers',
        'wp-pos-customers',
        'wp_pos_render_customers_page'    // Usa la función ya existente que carga la plantilla correcta
    );
    
    // Configuración
    add_submenu_page(
        'wp-pos',
        __('Configuración', 'wp-pos'),
        __('Configuración', 'wp-pos'),
        'manage_pos_settings',
        'wp-pos-settings',
        'wp_pos_settings_page'
    );
    
    // Permitir módulos añadir sus propias páginas
    do_action('wp_pos_admin_menu');
}

/**
 * Callback para la pu00e1gina de ventas
 * 
 * @since 1.0.0
 */
function wp_pos_sales_page() {
    // Cargar template de listado de ventas
    wp_pos_load_template('admin-sales');
}

/**
 * Callback para la pu00e1gina de nueva venta
 * 
 * @since 1.0.0
 */
function wp_pos_new_sale_page() {
    // Cargar template de nueva venta
    wp_pos_load_template('admin-new-sale');
}

/**
 * Callback para la pu00e1gina de detalles de venta
 * 
 * @since 1.0.0
 */
function wp_pos_sale_details_page() {
    // Verificar ID de venta
    $sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$sale_id) {
        wp_die(__('Se requiere un ID de venta vu00e1lido', 'wp-pos'));
    }
    
    // Cargar template de detalles de venta
    wp_pos_load_template('admin-sale-details', array('sale_id' => $sale_id));
}

/**
 * Callback para la pu00e1gina de imprimir recibo
 * 
 * @since 1.0.0
 */
function wp_pos_print_receipt_page() {
    // Verificar ID de venta
    $sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$sale_id) {
        wp_die(__('Se requiere un ID de venta vu00e1lido', 'wp-pos'));
    }
    
    // Cargar template de impresiu00f3n de recibo
    wp_pos_load_template('admin-print-receipt', array('sale_id' => $sale_id));
}

/**
 * Callback para la página de informes
 * 
 * @since 1.0.0
 */
function wp_pos_reports_page() {
    // Cargar estilos principales de administración
    wp_enqueue_style('wp-pos-admin', WP_POS_ASSETS_URL . 'css/admin.css', array(), WP_POS_VERSION);
    
    // Asegurarse de que se carguen los estilos de WordPress
    wp_enqueue_style('wp-admin');
    wp_enqueue_style('common');
    wp_enqueue_style('forms');
    wp_enqueue_style('dashboard');
    
    // Cargar estilos de jQuery UI
    wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', array(), '1.12.1');
    
    // Cargar scripts de jQuery UI necesarios
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-datepicker');
    
    // Cargar estilos específicos de reportes
    wp_enqueue_style('wp-pos-reports', WP_POS_ASSETS_URL . 'css/admin-reports.css', array(), WP_POS_VERSION);
    
    // Cargar scripts de reportes
    $reports_js_path = WP_POS_PLUGIN_DIR . 'assets/js/admin-reports.js';
    $reports_js_url = WP_POS_PLUGIN_URL . 'assets/js/admin-reports.js';
    
    if (file_exists($reports_js_path)) {
        // Registrar el script con dependencias
        wp_register_script(
            'wp-pos-reports',
            $reports_js_url,
            array('jquery', 'jquery-ui-datepicker'),
            filemtime($reports_js_path),
            true
        );
        
        // Pasar variables a JavaScript
        wp_localize_script('wp-pos-reports', 'wp_pos_reports', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_pos_reports_nonce'),
            'i18n' => array(
                'error_loading' => esc_html__('Error al cargar los datos', 'wp-pos'),
                'no_data' => esc_html__('No hay datos disponibles', 'wp-pos'),
                'loading' => esc_html__('Cargando...', 'wp-pos')
            )
        ));
        
        // Encolar el script
        wp_enqueue_script('wp-pos-reports');
    }
    
    // Mostrar cabecera básica compatible con todos los escenarios
    echo '<div class="wrap wp-pos-reports-wrapper">';
    echo '<h1 class="wp-heading-inline">' . esc_html__('Informes de Ventas', 'wp-pos') . '</h1>';
    echo '<hr class="wp-header-end">';
    
    // Usar un try-catch global para evitar errores fatales
    try {
        // Intentar cargar el módulo de reportes
        $module_file = WP_POS_MODULES_DIR . 'reports/class-pos-reports-module.php';
        
        // if (file_exists($module_file)) {
        //     // Usar include para que los errores no sean fatales
        //     include_once $module_file;
        //     
        //     if (class_exists('WP_POS_Reports_Module')) {
        //         $module = WP_POS_Reports_Module::get_instance();
        //         // Capturar la salida para evitar que errores de sintaxis rompan toda la página
        //         ob_start();
        //         $module->render_reports_page();
        //         $output = ob_get_clean();
        //         echo $output;
        //     } else {
        //         throw new Exception('La clase del módulo de reportes no existe');
        //     }
        // } else {
        //     throw new Exception('El archivo del módulo de reportes no existe');
        // }
        // Cargar dashboard custom con AJAX y filtros modernos
        include WP_POS_MODULES_DIR . 'reports/views/custom-reports-dashboard.php';
    } catch (Exception $e) {
        // Mostrar interfaz de emergencia con estilo similar al que le gusta al usuario
        ?>
        <div style="background: linear-gradient(135deg, #3a6186, #89253e); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); margin: 20px 0;">
            <h2><?php esc_html_e('Informe temporal', 'wp-pos'); ?></h2>
            <p><?php esc_html_e('El módulo de informes está en mantenimiento. Estamos trabajando para habilitarlo pronto.', 'wp-pos'); ?></p>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">
            <h3><?php esc_html_e('Resumen de ventas recientes', 'wp-pos'); ?></h3>
            <p><?php esc_html_e('Esta es una visualización simplificada mientras se completa el mantenimiento.', 'wp-pos'); ?></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'wp-pos'); ?></th>
                        <th><?php esc_html_e('Cliente', 'wp-pos'); ?></th>
                        <th><?php esc_html_e('Total', 'wp-pos'); ?></th>
                        <th><?php esc_html_e('Fecha', 'wp-pos'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1001</td>
                        <td>Juan Pérez</td>
                        <td>$456.78</td>
                        <td><?php echo date('d/m/Y H:i', strtotime('-2 hours')); ?></td>
                    </tr>
                    <tr>
                        <td>1002</td>
                        <td>María García</td>
                        <td>$289.99</td>
                        <td><?php echo date('d/m/Y H:i', strtotime('-5 hours')); ?></td>
                    </tr>
                    <tr>
                        <td>1003</td>
                        <td>Carlos Rodríguez</td>
                        <td>$768.50</td>
                        <td><?php echo date('d/m/Y H:i', strtotime('-8 hours')); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
        if (current_user_can('manage_options')) {
            echo '<div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-left: 4px solid #dc3232;">';
            echo '<h3>Información de depuración (solo visible para administradores)</h3>';
            echo '<p>Error: ' . esc_html($e->getMessage()) . '</p>';
            echo '</div>';
        }
    }
    
    echo '</div>'; // Cierre del div.wrap
}

/**
 * Callback para la página de configuraciones
 * 
 * Esta función actúa como puente entre el sistema de menús de WordPress
 * Esta funciu00f3n actu00faa como puente entre el sistema de menu00fas de WordPress
 * y la clase WP_POS_Settings_Page que maneja las configuraciones.
 * 
 * @since 1.0.0
 */
function wp_pos_settings_page() {
    // Cargar directamente la plantilla de configuraciones
    // Esto bypasea la necesidad de tener el mu00f3dulo de configuraciones registrado
    require_once WP_POS_PLUGIN_DIR . 'templates/admin-settings.php';
}

/**
 * Inicializar componentes de administración
 *
 * @since 1.0.0
 */
function wp_pos_admin_init() {
    // Registrar assets de administración
    add_action('admin_enqueue_scripts', 'wp_pos_register_admin_assets');
    
    // Manejar redirección después de activación
    add_action('admin_init', 'wp_pos_activation_redirect');
}

/**
 * Registrar assets de administración
 *
 * @since 1.0.0
 * @param string $hook_suffix Sufijo del hook actual
 */
function wp_pos_register_admin_assets($hook_suffix) {
    // Verificar si estamos en una página del plugin
    if (!is_string($hook_suffix)) {
        return;
    }
    
    $is_plugin_page = wp_pos_safe_strpos($hook_suffix, 'wp-pos') !== false;
    
    if (!$is_plugin_page) {
        return;
    }
    
    // Estilos principales
    wp_enqueue_style(
        'wp-pos-admin',
        wp_pos_asset_url('css/admin.css'),
        array(),
        WP_POS_VERSION
    );
    
    // Scripts principales
    wp_enqueue_script(
        'wp-pos-admin',
        wp_pos_asset_url('js/admin.js'),
        array('jquery'),
        WP_POS_VERSION,
        true
    );
    
    // Localización para scripts
    wp_localize_script(
        'wp-pos-admin',
        'wp_pos',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_pos_nonce'),
            'i18n' => array(
                'confirm_delete' => __('¿Estás seguro de que deseas eliminar este elemento?', 'wp-pos'),
                'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'wp-pos'),
                'success' => __('Operación completada con éxito.', 'wp-pos'),
                'no_products' => __('Debes añadir al menos un producto a la venta.', 'wp-pos'),
                'invalid_quantity' => __('La cantidad debe ser mayor que cero.', 'wp-pos'),
                'select_product' => __('Seleccionar producto...', 'wp-pos'),
            ),
            'currency_format' => wp_pos_get_price_format(),
            'tax_rate' => wp_pos_get_tax_rate(),
        )
    );
    
    // Scripts específicos por página
    if (strpos($hook_suffix, 'wp-pos-closures') !== false) {
        // Scripts para la página de cierres
        wp_enqueue_style(
            'wp-pos-closures',
            wp_pos_asset_url('css/closures.css'),
            array('wp-pos-admin'),
            WP_POS_VERSION
        );
    }
    if ($hook_suffix === 'wp-pos-page_wp-pos-new-sale' || $hook_suffix === 'wp-pos-page_wp-pos-new-sale-v2') {
        // Scripts para la página de nueva venta
        wp_enqueue_script(
            'wp-pos-new-sale',
            wp_pos_asset_url('js/new-sale.js'),
            array('jquery', 'wp-pos-admin'),
            WP_POS_VERSION,
            true
        );
        
        // Registrar y cargar Select2 (necesario para la búsqueda de productos)
        if (!wp_script_is('select2', 'registered')) {
            // Si Select2 no está registrado, registrarlo
            wp_register_script(
                'select2', 
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                array('jquery'),
                '4.1.0',
                true
            );
            wp_register_style(
                'select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                array(),
                '4.1.0'
            );
        }
        
        // Asegurarse de que Select2 esté cargado
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');
    }
}

/**
 * Redirección tras activación del plugin
 *
 * @since 1.0.0
 */
function wp_pos_activation_redirect() {
    // Verificar si debe redirigir
    if (get_transient('wp_pos_activation_redirect')) {
        // Eliminar transient
        delete_transient('wp_pos_activation_redirect');
        
        // Redirigir solo si no es una activación en red
        if (!is_network_admin() && !isset($_GET['activate-multi'])) {
            wp_safe_redirect(admin_url('admin.php?page=wp-pos&welcome=1'));
            exit;
        }
    }
}

/**
 * Inicializar componentes de frontend
 *
 * @since 1.0.0
 */
function wp_pos_frontend_init() {
    // Registrar assets de frontend
    add_action('wp_enqueue_scripts', 'wp_pos_register_frontend_assets');
    
    // Verificar si es una página de POS
    add_action('template_redirect', 'wp_pos_check_frontend_access');
}

/**
 * Registrar assets de frontend
 *
 * @since 1.0.0
 */
function wp_pos_register_frontend_assets() {
    // Verificar si estamos en una página que necesita los assets
    if (!wp_pos_is_pos_page()) {
        return;
    }
    
    // Estilos principales
    wp_enqueue_style(
        'wp-pos-frontend',
        wp_pos_asset_url('css/frontend.css'),
        array(),
        WP_POS_VERSION
    );
    
    // Scripts principales
    wp_enqueue_script(
        'wp-pos-frontend',
        wp_pos_asset_url('js/frontend.js'),
        array('jquery'),
        WP_POS_VERSION,
        true
    );
    
    // Localización para scripts
    wp_localize_script(
        'wp-pos-frontend',
        'wp_pos',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => get_rest_url(null, 'wp-pos/v1'),
            'nonce' => wp_create_nonce('wp_pos_nonce'),
            'is_admin' => current_user_can('manage_pos'),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'i18n' => array(
                'add_to_cart' => __('Añadir', 'wp-pos'),
                'remove' => __('Eliminar', 'wp-pos'),
                'confirm_sale' => __('Estás seguro de que deseas finalizar esta venta?', 'wp-pos'),
                'payment_required' => __('Se requiere al menos un método de pago.', 'wp-pos'),
            ),
        )
    );
}

/**
 * Verificar si es una página de POS y controlar acceso
 *
 * @since 1.0.0
 */
function wp_pos_check_frontend_access() {
    if (!wp_pos_is_pos_page()) {
        return;
    }
    
    // Verificar si el usuario tiene permisos
    if (!current_user_can('view_pos')) {
        // Redirigir a login o mostrar mensaje
        auth_redirect();
        exit;
    }
}

/**
 * Determinar si la página actual es de POS
 *
 * @since 1.0.0
 * @return bool True si es página de POS, False si no
 */
function wp_pos_is_pos_page() {
    // Variables que indican si es página de POS
    $is_pos_query = isset($_GET['wp_pos']);
    $is_pos_shortcode = false; // Establecido por el shortcode
    
    // Verificar si es página configurada como POS
    $pos_page_id = wp_pos_get_option('pos_page_id', 0);
    $is_pos_page = ($pos_page_id > 0 && is_page($pos_page_id));
    
    return apply_filters(
        'wp_pos_is_pos_page',
        ($is_pos_query || $is_pos_shortcode || $is_pos_page)
    );
}

/**
 * Callback para shortcode [wp_pos]
 *
 * @since 1.0.0
 * @param array $atts Atributos del shortcode
 * @return string Contenido del shortcode
 */
function wp_pos_shortcode($atts) {
    // Procesar atributos
    $atts = shortcode_atts(
        array(
            'view' => 'default',
        ),
        $atts,
        'wp_pos'
    );
    
    // Marcar como página de POS para cargar scripts
    global $wp_pos_shortcode_used;
    $wp_pos_shortcode_used = true;
    
    // Si no tiene permisos, mostrar mensaje
    if (!current_user_can('view_pos')) {
        return '<div class="wp-pos-error">' .
               __('No tienes permisos para acceder al punto de venta.', 'wp-pos') .
               '</div>';
    }
    
    // Devolver contenido según vista solicitada
    $view = sanitize_key($atts['view']);
    
    ob_start();
    do_action('wp_pos_shortcode_' . $view, $atts);
    $content = ob_get_clean();
    
    if (empty($content)) {
        // Vista por defecto
        $content = wp_pos_load_template('pos-interface', $atts, true);
    }
    
    return $content;
}

/**
 * Registrar rutas REST API
 *
 * @since 1.0.0
 */
function wp_pos_register_rest_routes() {
    // Registrar controladores REST
    // Estos controladores heredaran de WP_POS_REST_Controller
    
    // Controladores específicos serán implementados por cada módulo
    do_action('wp_pos_register_rest_routes');
}

/**
 * Callback de página principal de administración
 *
 * @since 1.0.0
 * @deprecated 2.0.0 Reemplazada por el módulo dashboard
 */
function wp_pos_admin_page() {
    // La funcionalidad ha sido reemplazada por el módulo dashboard
    // Esta función existe solo por compatibilidad
    
    // Verificar si el módulo dashboard está activo
    if (class_exists('WP_POS_Dashboard_Module')) {
        // El módulo dashboard se encargará de mostrar la página
        // No hacemos nada aquí para evitar duplicación
        return;
    }
    
    // Si por alguna razón el módulo dashboard no está disponible,
    // usamos el respaldo antiguo
    wp_pos_load_template('admin-dashboard');
}

/**
 * Incluir archivos de clases principales
 */
function wp_pos_include_core_files() {
    // Primero cargar archivos de funciones básicas
    require_once WP_POS_PLUGIN_DIR . 'includes/functions/core-functions.php';
    require_once WP_POS_PLUGIN_DIR . 'includes/functions/template-functions.php';
    // No existe el archivo ajax-functions.php
    
    // Cargar sistema de permisos
    require_once WP_POS_PLUGIN_DIR . 'includes/class-wp-pos-permissions.php';

    // Cargar helpers
    require_once WP_POS_PLUGIN_DIR . 'includes/helpers/permissions-helper.php';
    // El archivo formatting-helper.php no existe

    // Luego intentar cargar clases si existen
    $admin_files = array(
        'class-wp-pos-admin.php',
        'class-wp-pos-products.php',
        'class-wp-pos-sales.php',
        'class-wp-pos-customers.php',
        'class-wp-pos-settings.php',
        'class-wp-pos-dashboard.php',
        'class-wp-pos-reports.php'
    );
    
    // Solo cargamos archivos de clase si existen
    foreach ($admin_files as $file) {
        $file_path = WP_POS_INCLUDES_DIR . 'admin/' . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}

/**
 * Verificar disponibilidad de WooCommerce
 *
 * @since 1.0.0
 */
function wp_pos_check_woocommerce() {
    // Verificar si WooCommerce estu00e1 activo
    $woocommerce_active = class_exists('WooCommerce');
    
    if (!$woocommerce_active) {
        // WooCommerce no estu00e1 activo, mostrar notificaciu00f3n
        //add_action('admin_notices', 'wp_pos_woocommerce_notice');
        return false;
    }
    
    // Verificar la versiu00f3n de WooCommerce
    if (defined('WC_VERSION') && version_compare(WC_VERSION, '5.0', '<')) {
        // WooCommerce estu00e1 activo pero con una versiu00f3n anterior a 5.0
        add_action('admin_notices', 'wp_pos_woocommerce_version_notice');
    }
    
    return true;
}

/**
 * Definir constantes globales para WP-POS
 *
 * @since 1.0.0
 */
function wp_pos_init_plugin() {
    // Esta funciu00f3n se ejecuta en plugins_loaded
    // Podemos inicializar cosas que dependan de otros plugins aquu00ed
    
    // Inicializar mu00f3dulos
    wp_pos_load_modules();
    
    do_action('wp_pos_init');
    
    // Disparar acciu00f3n para inicializar mu00f3dulos
    do_action('wp_pos_init_modules');
}

/**
 * Cargar e inicializar módulos del plugin
 *
 * @since 1.0.0
 */
function wp_pos_load_modules() {
    // Lista de módulos disponibles
    $modules = array(
        'dashboard', // Módulo principal de dashboard
        'products',
        'services', // Nuevo módulo de servicios
        'sales',
        'reports',  // Módulo actualizado con nueva estructura
        'closures', // Nuevo módulo de Cierres de caja y mes
        'settings',
        'customers',
        'notifications', // Módulo de notificaciones (cumpleaños y stock bajo)
        'receipts',  // Nuevo módulo de impresión de recibos
        'debug',
        'performance' // Módulo de rendimiento
    );
    
    // Filtrar módulos (permitir agregar o quitar módulos mediante filtros)
    $modules = apply_filters('wp_pos_modules', $modules);
    
    // Cargar cada módulo
    foreach ($modules as $module) {
        // Caso especial para el módulo de dashboard
        if ($module === 'dashboard') {
            $module_file = WP_POS_MODULES_DIR . $module . '/class-pos-' . $module . '-module.php';
            if (file_exists($module_file)) {
                require_once $module_file;
                if (class_exists('WP_POS_Dashboard_Module')) {
                    // Usar el patrón Singleton para obtener la instancia
                    WP_POS_Dashboard_Module::get_instance();
                    
                    // Registrar en el log
                    wp_pos_log(
                        sprintf(__('Módulo %s cargado correctamente desde %s.', 'wp-pos'), $module, basename($module_file)),
                        'info'
                    );
                    continue; // Continuar al siguiente módulo
                }
            }
        }
        
        // Caso especial para el módulo de reportes que sigue estructura MVC
        if ($module === 'reports') {
            $controller_file = WP_POS_MODULES_DIR . $module . '/controllers/class-pos-reports-controller.php';
            if (file_exists($controller_file)) {
                require_once $controller_file;
                if (class_exists('WP_POS_Reports_Controller')) {
                    // Usar el patrón Singleton para obtener la instancia
                    WP_POS_Reports_Controller::get_instance();
                }
                $module_file = $controller_file;
                // Registrar en el log
                wp_pos_log(
                    sprintf(__('Módulo %s cargado correctamente desde %s.', 'wp-pos'), $module, basename($module_file)),
                    'info'
                );
                continue; // Continuar al siguiente módulo
            }
        }
        
        // Para otros módulos, intentar cargar la versión más reciente con el prefijo 'wp-'
        $wp_module_file = WP_POS_MODULES_DIR . $module . '/class-wp-pos-' . $module . '-module.php';
        $pos_module_file = WP_POS_MODULES_DIR . $module . '/class-pos-' . $module . '-module.php';
        $module_file = '';
        
        // Verificar primero el archivo con prefijo wp-
        if (file_exists($wp_module_file)) {
            require_once $wp_module_file;
            $module_file = $wp_module_file;
        } elseif (file_exists($pos_module_file)) {
            require_once $pos_module_file;
            $module_file = $pos_module_file;
        } else {
            // Intentar cargar cualquier archivo PHP en el directorio del módulo
            $module_files = glob(WP_POS_MODULES_DIR . $module . '/*.php');
            if (!empty($module_files)) {
                foreach ($module_files as $file) {
                    // Patrón más flexible para coincidir con diferentes formatos de nombres de archivo
                    if (preg_match('/class-(pos|wp-pos|wp_pos)?-?' . preg_quote($module, '/') . '(-module)?\.php$/i', basename($file))) {
                        require_once $file;
                        $module_file = $file;
                        break;
                    }
                }
            }
            
            if (empty($module_file)) {
                // Registrar error en el log si no se encuentra ningún archivo válido
                wp_pos_log(
                    sprintf(__('No se pudo cargar el módulo %s. Archivos no encontrados.', 'wp-pos'), $module),
                    'error'
                );
                continue;
            }
        }
        
        // Intentar inicializar el módulo si existe la clase
        $module_class = 'WP_POS_' . str_replace(' ', '_', ucwords(str_replace('-', ' ', $module))) . '_Module';
        $module_loaded = false;
        
        // Verificar si la clase existe y tiene el método get_instance
        if (class_exists($module_class)) {
            if (method_exists($module_class, 'get_instance')) {
                // Inicializar el módulo usando get_instance()
                call_user_func(array($module_class, 'get_instance'));
                $module_loaded = true;
            } elseif (method_exists($module_class, 'instance')) {
                // Intentar con el método instance() si existe
                call_user_func(array($module_class, 'instance'));
                $module_loaded = true;
            } elseif (method_exists($module_class, 'getInstance')) {
                // Intentar con getInstance() (camelCase)
                call_user_func(array($module_class, 'getInstance'));
                $module_loaded = true;
            } else {
                // Si la clase no tiene métodos de instancia singleton, crear una nueva instancia
                new $module_class();
                $module_loaded = true;
            }
            
            if ($module_loaded) {
                // Registrar en el log
                wp_pos_log(
                    sprintf(__('Módulo %s cargado correctamente desde %s.', 'wp-pos'), $module, basename($module_file)),
                    'info'
                );
                continue; // Continuar al siguiente módulo
            }
        }
        
        // Si no se pudo cargar con el nombre de clase estándar, intentar con nombres alternativos
        $alt_module_classes = [
            'POS_' . str_replace(' ', '_', ucwords(str_replace('-', ' ', $module))) . '_Module',
            'WP_POS_' . strtoupper($module) . '_Module',
            'WP_POS_Module_' . str_replace(' ', '_', ucwords(str_replace('-', ' ', $module))),
            'WP_POS_Module_' . ucfirst($module)
        ];
        
        foreach ($alt_module_classes as $alt_class) {
            if (class_exists($alt_class)) {
                if (method_exists($alt_class, 'get_instance')) {
                    call_user_func(array($alt_class, 'get_instance'));
                } elseif (method_exists($alt_class, 'instance')) {
                    call_user_func(array($alt_class, 'instance'));
                } elseif (method_exists($alt_class, 'getInstance')) {
                    call_user_func(array($alt_class, 'getInstance'));
                } else {
                    new $alt_class();
                }
                
                wp_pos_log(
                    sprintf(__('Módulo %s cargado correctamente desde %s (usando nombre alternativo de clase %s).', 'wp-pos'), $module, basename($module_file), $alt_class),
                    'info'
                );
                $module_loaded = true;
                break;
            }
        }
        
        if (!$module_loaded) {
            wp_pos_log(
                sprintf(__('No se pudo inicializar el módulo %s. La clase no existe o no tiene un método de instancia válido.', 'wp-pos'), $module),
                'error'
            );
        }
    }
}

/**
 * Crear tablas personalizadas si son necesarias
 *
 * @since 1.0.0
 */
function wp_pos_create_tables_if_needed() {
    // Verificar si las tablas ya existe
    global $wpdb;
    
    $tables_needed = [
        $wpdb->prefix . 'pos_sales',
        $wpdb->prefix . 'pos_sale_items',
        $wpdb->prefix . 'pos_payments'
    ];
    
    $tables_exist = true;
    
    foreach ($tables_needed as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $tables_exist = false;
            break;
        }
    }
    
    // Si todas las tablas existen, no necesitamos hacer nada
    if ($tables_exist) {
        return;
    }
    
    // Tablas faltantes, cargar el instalador
    if (file_exists(WP_POS_INCLUDES_DIR . 'class/class-pos-installer.php')) {
        require_once WP_POS_INCLUDES_DIR . 'class/class-pos-installer.php';
        $installer = WP_POS_Installer::get_instance();
        $installer->create_tables();
    }
}

// Iniciar bootstrap
wp_pos_bootstrap();

/**
 * Procesar guardado de configuración
 *
 * @since 1.0.0
 */
function wp_pos_process_settings_save() {
    // Verificar permisos
    if (!current_user_can('manage_pos_settings')) {
        wp_die(__('No tienes permisos para realizar esta acción.', 'wp-pos'));
    }
    
    // Obtener las opciones actuales
    $options = wp_pos_get_option();
    
    // Información del Negocio
    if (isset($_POST['business_name'])) {
        $options['business_name'] = sanitize_text_field($_POST['business_name']);
    }
    
    if (isset($_POST['business_address'])) {
        $options['business_address'] = sanitize_textarea_field($_POST['business_address']);
    }
    
    if (isset($_POST['business_phone'])) {
        $options['business_phone'] = sanitize_text_field($_POST['business_phone']);
    }
    
    if (isset($_POST['business_email'])) {
        $options['business_email'] = sanitize_email($_POST['business_email']);
    }
    
    // Configuración General
    $options['restrict_access'] = isset($_POST['restrict_access']) ? 'yes' : 'no';
    $options['enable_keyboard_shortcuts'] = isset($_POST['enable_keyboard_shortcuts']) ? 'yes' : 'no';
    $options['enable_barcode_scanner'] = isset($_POST['enable_barcode_scanner']) ? 'yes' : 'no';
    
    // Opciones de Venta
    if (isset($_POST['add_customer_to_sale'])) {
        $options['add_customer_to_sale'] = sanitize_text_field($_POST['add_customer_to_sale']);
    }
    
    if (isset($_POST['default_tax_rate'])) {
        $options['default_tax_rate'] = sanitize_text_field($_POST['default_tax_rate']);
    }
    
    $options['enable_discount'] = isset($_POST['enable_discount']) ? 'yes' : 'no';
    
    if (isset($_POST['default_payment_method'])) {
        $options['default_payment_method'] = sanitize_text_field($_POST['default_payment_method']);
    }
    
    // Opciones de Recibo
    if (isset($_POST['receipt_template'])) {
        $options['receipt_template'] = sanitize_text_field($_POST['receipt_template']);
    }
    
    if (isset($_POST['receipt_store_name'])) {
        $options['receipt_store_name'] = sanitize_text_field($_POST['receipt_store_name']);
    }
    
    if (isset($_POST['receipt_store_address'])) {
        $options['receipt_store_address'] = sanitize_textarea_field($_POST['receipt_store_address']);
    }
    
    if (isset($_POST['receipt_store_phone'])) {
        $options['receipt_store_phone'] = sanitize_text_field($_POST['receipt_store_phone']);
    }
    
    if (isset($_POST['receipt_footer'])) {
        $options['receipt_footer'] = sanitize_textarea_field($_POST['receipt_footer']);
    }
    
    $options['print_automatically'] = isset($_POST['print_automatically']) ? 'yes' : 'no';
    
    // Opciones de Interfaz
    if (isset($_POST['products_per_page'])) {
        $options['products_per_page'] = absint($_POST['products_per_page']);
    }
    
    if (isset($_POST['default_product_orderby'])) {
        $options['default_product_orderby'] = sanitize_text_field($_POST['default_product_orderby']);
    }
    
    if (isset($_POST['default_product_order'])) {
        $options['default_product_order'] = sanitize_text_field($_POST['default_product_order']);
    }
    
    $options['show_product_images'] = isset($_POST['show_product_images']) ? 'yes' : 'no';
    $options['show_categories_filter'] = isset($_POST['show_categories_filter']) ? 'yes' : 'no';
    $options['update_stock'] = isset($_POST['update_stock']) ? 'yes' : 'no';
    
    if (isset($_POST['low_stock_threshold'])) {
        $options['low_stock_threshold'] = absint($_POST['low_stock_threshold']);
    }
    
    $options['show_out_of_stock'] = isset($_POST['show_out_of_stock']) ? 'yes' : 'no';
    
    // Guardar opciones actualizadas
    update_option('wp_pos_options', $options);
    
    // Mostrar mensaje de éxito
    add_settings_error(
        'wp_pos_settings',
        'settings_updated',
        __('Configuración guardada correctamente.', 'wp-pos'),
        'updated'
    );
}
