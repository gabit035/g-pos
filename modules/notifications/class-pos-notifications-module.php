<?php
/**
 * Clase del mu00f3dulo de notificaciones
 *
 * @package WP-POS
 * @subpackage Notifications
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del mu00f3dulo de notificaciones
 * 
 * @since 1.0.0
 */
class WP_POS_Notifications_Module {
    
    /**
     * Instancia u00fanica de la clase (patru00f3n Singleton)
     */
    private static $instance = null;
    
    /**
     * URL del mu00f3dulo
     */
    private $module_url;
    
    /**
     * Directorio del mu00f3dulo
     */
    private $module_dir;
    
    /**
     * Constructor
     */
    private function __construct() {
        // Definir URL y directorio del mu00f3dulo
        $this->module_url = WP_POS_PLUGIN_URL . 'modules/notifications/';
        $this->module_dir = WP_POS_PLUGIN_DIR . 'modules/notifications/';
        
        // Incluir funciones auxiliares
        require_once $this->module_dir . 'includes/notifications-functions.php';
        
        // Inicializar acciones y filtros
        $this->init_hooks();
    }
    
    /**
     * Obtener la instancia u00fanica de la clase
     * 
     * @return WP_POS_Notifications_Module Instancia de la clase
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inicializar acciones y filtros
     */
    private function init_hooks() {
        // Agregar pu00e1gina en el menu00fa de administraciu00f3n
        add_action('admin_menu', array($this, 'register_admin_menu'), 30);
        
        // Registrar manejadores AJAX
        add_action('wp_ajax_wp_pos_dismiss_notification', array($this, 'ajax_dismiss_notification'));
        add_action('wp_ajax_wp_pos_create_stock_notification', array($this, 'ajax_create_stock_notification'));
        
        // Registrar scripts y estilos
        add_action('admin_enqueue_scripts', array($this, 'register_scripts_and_styles'));
        
        // Notificaciones en el menu00fa principal
        add_action('admin_footer', array($this, 'display_menu_notifications'));
        
        // Registrar widget en el dashboard
        add_action('wp_dashboard_setup', array($this, 'register_dashboard_widget'));
        
        // Acciones tras actualizaciu00f3n de stock
        add_action('wp_pos_after_update_product_stock', array($this, 'check_stock_after_update'), 10, 3);
        
        // Verificaciones diarias (cumpleaños y stock)
        add_action('wp', array($this, 'setup_daily_checks'));
        add_action('wp_pos_daily_check', array($this, 'do_daily_checks'));
    }
    
    /**
     * Registrar pu00e1gina en el menu00fa de administraciu00f3n
     */
    public function register_admin_menu() {
        add_submenu_page(
            'wp-pos', // slug del menu00fa padre
            __('Notificaciones', 'wp-pos'),
            __('Notificaciones', 'wp-pos') . $this->get_menu_notification_badge(),
            'manage_options',
            'wp-pos-notifications',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Generar indicador de notificaciones para el menu00fa
     */
    private function get_menu_notification_badge() {
        $count = wp_pos_get_notifications_count();
        
        if ($count > 0) {
            return ' <span class="wp-pos-notification-count">' . $count . '</span>';
        }
        
        return '';
    }
    
    /**
     * Renderizar pu00e1gina de administraciu00f3n
     */
    public function render_admin_page() {
        // Cargar template
        include $this->module_dir . 'templates/admin-notifications.php';
    }
    
    /**
     * Registrar scripts y estilos
     */
    public function register_scripts_and_styles() {
        // Registrar y encolar estilos
        wp_register_style(
            'wp-pos-notifications', 
            $this->module_url . 'assets/css/notifications.css', 
            array(), 
            WP_POS_VERSION
        );
        
        // Registrar y encolar scripts
        wp_register_script(
            'wp-pos-notifications', 
            $this->module_url . 'assets/js/notifications.js', 
            array('jquery'), 
            WP_POS_VERSION, 
            true
        );
        
        // Localizar script
        wp_localize_script('wp-pos-notifications', 'wp_pos_notifications', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_pos_nonce'),
            'dismiss_text' => __('Cerrar', 'wp-pos'),
            'timeout' => 5000 // Tiempo predeterminado para notificaciones temporales (5 segundos)
        ));
        
        // Encolar en todas las pu00e1ginas del plugin, independientemente del screen ID
        // para evitar problemas de compatibilidad
        if (isset($_GET['page']) && strpos($_GET['page'], 'wp-pos') !== false) {
            wp_enqueue_style('wp-pos-notifications');
            wp_enqueue_script('wp-pos-notifications');
        }
        
        // Tambie00e9n cargar en el dashboard
        $screen = get_current_screen();
        if ($screen && $screen->id === 'dashboard') {
            wp_enqueue_style('wp-pos-notifications');
            wp_enqueue_script('wp-pos-notifications');
        }
        
        // Debug para ver si se estu00e1n cargando los scripts correctamente
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP_POS Notificaciones: Pantalla: ' . (is_object($screen) ? $screen->id : 'no screen') . ' / Página: ' . (isset($_GET['page']) ? $_GET['page'] : 'no page'));
        }
    }
    
    /**
     * Mostrar indicador de notificaciones en el menu00fa
     */
    public function display_menu_notifications() {
        $count = wp_pos_get_notifications_count();
        
        if ($count > 0) {
            // Agregar estilos inline
            echo '<style>
                .wp-pos-notification-count {
                    display: inline-block;
                    background-color: #6c5ce7;
                    color: white;
                    border-radius: 50%;
                    min-width: 18px;
                    height: 18px;
                    text-align: center;
                    line-height: 18px;
                    font-size: 11px;
                    font-weight: bold;
                    margin-left: 5px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                }
            </style>';
        }
    }
    
    /**
     * Registrar widget en el dashboard de WordPress
     */
    public function register_dashboard_widget() {
        wp_add_dashboard_widget(
            'wp_pos_notifications_widget',
            __('Notificaciones WP-POS', 'wp-pos'),
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Renderizar widget en el dashboard
     */
    public function render_dashboard_widget() {
        include $this->module_dir . 'templates/dashboard-widget.php';
    }
    
    /**
     * Manejador AJAX para descartar notificaciones
     */
    public function ajax_dismiss_notification() {
        // Verificar nonce
        check_ajax_referer('wp_pos_nonce', 'security');
        
        // Obtener ID de notificaciu00f3n
        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if ($notification_id) {
            // Descartar notificaciu00f3n
            $result = wp_pos_dismiss_notification($notification_id);
            
            if ($result) {
                wp_send_json_success();
            } else {
                wp_send_json_error(__('No se pudo descartar la notificaciu00f3n', 'wp-pos'));
            }
        } else {
            wp_send_json_error(__('ID de notificaciu00f3n invu00e1lido', 'wp-pos'));
        }
    }
    
    /**
     * Manejador AJAX para crear notificaciones de stock
     */
    public function ajax_create_stock_notification() {
        // Verificar nonce
        check_ajax_referer('wp_pos_nonce', 'security');
        
        // Obtener datos del producto
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $product_name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        $current_stock = isset($_POST['current_stock']) ? intval($_POST['current_stock']) : 0;
        
        if ($product_id && $product_name && $quantity > $current_stock) {
            // Crear notificaciu00f3n de stock insuficiente
            $notification_id = wp_pos_add_insufficient_stock_notification($product_id, $product_name, $quantity, $current_stock);
            
            if ($notification_id) {
                // Obtener HTML actualizado
                ob_start();
                wp_pos_display_notifications('sales');
                $html = ob_get_clean();
                
                wp_send_json_success(array(
                    'notification_id' => $notification_id,
                    'html' => $html
                ));
            } else {
                wp_send_json_error(__('No se pudo crear la notificaciu00f3n', 'wp-pos'));
            }
        } else {
            wp_send_json_error(__('Datos insuficientes para crear la notificaciu00f3n', 'wp-pos'));
        }
    }
    
    /**
     * Verificar stock despuu00e9s de una actualizaciu00f3n
     * 
     * @param int $product_id ID del producto
     * @param int $quantity Cantidad nueva
     * @param string $operation Tipo de operaciu00f3n realizada
     */
    public function check_stock_after_update($product_id, $quantity, $operation) {
        // Obtener producto actualizado
        $product = wp_pos_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        // Obtener umbral configurado
        $threshold = intval(get_option('wp_pos_stock_threshold', 5));
        
        // Si el stock estu00e1 por debajo del umbral, generar notificaciu00f3n para administradores
        if ($product['stock_quantity'] <= $threshold && $product['stock_quantity'] > 0) {
            wp_pos_add_low_stock_notification($product_id, $product['name'], $product['stock_quantity'], $threshold);
        }
        
        // Si el stock es cero o negativo, generar notificaciu00f3n de stock agotado
        if ($product['stock_quantity'] <= 0) {
            wp_pos_add_out_of_stock_notification($product_id, $product['name'], $product['stock_quantity']);
        }
    }
    
    /**
     * Configurar verificaciones diarias
     */
    public function setup_daily_checks() {
        // Verificar si ya está programado el evento
        if (!wp_next_scheduled('wp_pos_daily_check')) {
            // Programar para que se ejecute todos los días a las 00:05
            wp_schedule_event(strtotime('tomorrow 00:05'), 'daily', 'wp_pos_daily_check');
        }
    }
    
    /**
     * Realizar verificaciones diarias
     * - Verifica cumpleaños de clientes
     * - Verifica productos con stock bajo
     */
    public function do_daily_checks() {
        // Registrar la ejecución
        error_log('[WP-POS] Ejecutando verificaciones diarias: ' . date('Y-m-d H:i:s'));
        
        // Verificar cumpleaños de clientes
        $this->check_customer_birthdays();
        
        // Verificar productos con stock bajo (verificación completa)
        $this->check_all_products_stock();
    }
    
    /**
     * Verificar cumpleaños de clientes
     */
    private function check_customer_birthdays() {
        global $wpdb;
        $today = date('m-d'); // Formato: MM-DD
        
        // Obtener clientes cuyo cumpleaños es hoy
        $customers_query = $wpdb->prepare(
            "SELECT ID, post_title, meta_value AS birthdate 
            FROM {$wpdb->posts} p 
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'pos_customer' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = 'birthdate' 
            AND DATE_FORMAT(pm.meta_value, '%%m-%%d') = %s",
            $today
        );
        
        $birthday_customers = $wpdb->get_results($customers_query);
        
        // Si hay clientes con cumpleaños hoy, crear notificaciones
        if ($birthday_customers && !is_wp_error($birthday_customers)) {
            foreach ($birthday_customers as $customer) {
                wp_pos_add_birthday_notification(
                    $customer->ID,
                    $customer->post_title,
                    $customer->birthdate
                );
            }
        }
        
        // Registrar resultado
        $count = is_array($birthday_customers) ? count($birthday_customers) : 0;
        error_log("[WP-POS] Verificación de cumpleaños: {$count} clientes cumplen años hoy");
    }
    
    /**
     * Verificar stock de todos los productos
     */
    private function check_all_products_stock() {
        // Obtener umbral configurado
        $threshold = intval(get_option('wp_pos_stock_threshold', 5));
        
        // Obtener todos los productos
        $args = array(
            'post_type' => 'pos_product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'stock_quantity',
                    'value' => $threshold,
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                ),
                array(
                    'key' => 'stock_quantity',
                    'value' => '0',
                    'compare' => '>',
                    'type' => 'NUMERIC'
                )
            )
        );
        
        $products_query = new WP_Query($args);
        
        // Para cada producto con stock bajo, crear notificación
        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product_id = get_the_ID();
                $product_name = get_the_title();
                $stock = get_post_meta($product_id, 'stock_quantity', true);
                
                wp_pos_add_low_stock_notification($product_id, $product_name, $stock, $threshold);
            }
        }
        
        wp_reset_postdata();
        
        // Productos agotados (stock = 0)
        $args_out = array(
            'post_type' => 'pos_product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'stock_quantity',
                    'value' => '0',
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                )
            )
        );
        
        $out_query = new WP_Query($args_out);
        
        // Para cada producto agotado, crear notificación
        if ($out_query->have_posts()) {
            while ($out_query->have_posts()) {
                $out_query->the_post();
                $product_id = get_the_ID();
                $product_name = get_the_title();
                $stock = get_post_meta($product_id, 'stock_quantity', true);
                
                wp_pos_add_out_of_stock_notification($product_id, $product_name, $stock);
            }
        }
        
        wp_reset_postdata();
        
        // Registrar resultado
        $low_count = $products_query->post_count;
        $out_count = $out_query->post_count;
        error_log("[WP-POS] Verificación de stock: {$low_count} productos con stock bajo, {$out_count} productos agotados");
    }
}

// Inicializar el mu00f3dulo
WP_POS_Notifications_Module::get_instance();
