<?php
/**
 * Mu00f3dulo de Ventas para WP-POS
 *
 * Gestiona todas las funcionalidades relacionadas con ventas,
 * incluyendo creaciu00f3n, ediciu00f3n, listado y procesamiento de ventas.
 *
 * @package WP-POS
 * @subpackage Sales
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del mu00f3dulo de ventas
 *
 * @since 1.0.0
 */
class WP_POS_Sales_Module {

    /**
     * ID u00fanico del mu00f3dulo
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $module_id = 'sales';

    /**
     * Instancia singleton
     *
     * @since 1.0.0
     * @access private
     * @static
     * @var WP_POS_Sales_Module
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     *
     * @since 1.0.0
     * @static
     * @return WP_POS_Sales_Module Instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->setup_actions();
        $this->load_dependencies();
    }

    /**
     * Configurar acciones y filtros
     *
     * @since 1.0.0
     * @access private
     */
    private function setup_actions() {
        // Registrar el mu00f3dulo
        add_action('wp_pos_init_modules', array($this, 'register_module'));

        // Rutas REST
        add_action('wp_pos_register_rest_routes', array($this, 'register_rest_routes'));

        // Admin
        add_action('wp_pos_admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));

        // AJAX handlers
        add_action('wp_ajax_wp_pos_create_sale', array($this, 'ajax_create_sale'));
        add_action('wp_ajax_wp_pos_update_sale', array($this, 'ajax_update_sale'));
        add_action('wp_ajax_wp_pos_delete_sale', array($this, 'ajax_delete_sale'));
        add_action('wp_ajax_wp_pos_get_sale', array($this, 'ajax_get_sale'));
        // AJAX search for products and services
        add_action('wp_ajax_wp_pos_search_products_direct', 'wp_pos_ajax_search_products_direct');
        add_action('wp_ajax_wp_pos_search_services', array($this, 'ajax_search_services'));

        // Frontend
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_scripts'));
        add_action('wp_pos_shortcode_sales', array($this, 'render_sales_interface'));

        // Procesamiento de ventas
        add_action('wp_pos_process_sale', array($this, 'process_sale'), 10, 2);
        add_action('wp_pos_after_sale_processed', array($this, 'after_sale_processed'), 10, 2);

        // Filtros
        add_filter('wp_pos_sale_number', array($this, 'filter_sale_number'), 10, 3);
        add_filter('wp_pos_admin_menu_items', array($this, 'filter_admin_menu'));
    }

    /**
     * Cargar dependencias del mu00f3dulo
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies() {
        // Cargar clase Registry
        require_once WP_POS_PLUGIN_DIR . 'includes/class/class-pos-registry.php';
        
        // Incluir funciones
        require_once plugin_dir_path(__FILE__) . 'includes/sales-functions.php';
        
        // Incluir controlador
        require_once plugin_dir_path(__FILE__) . 'controllers/class-pos-sales-controller.php';
        
        // Incluir actualizador forzado de stock
        require_once plugin_dir_path(__FILE__) . 'includes/force-stock-update.php';
        
        // Cargar modelos
        require_once dirname(__FILE__) . '/models/class-pos-sale.php';
        require_once dirname(__FILE__) . '/models/class-pos-sale-item.php';
        require_once dirname(__FILE__) . '/models/class-pos-payment.php';
        
        // Cargar clase base para REST API
        require_once WP_POS_PLUGIN_DIR . 'includes/api/class-pos-rest-controller.php';
        
        // Cargar API REST
        require_once dirname(__FILE__) . '/api/class-pos-sales-rest-controller.php';
        
        // Cargar funciones auxiliares
        require_once dirname(__FILE__) . '/includes/sales-functions.php';
        
        // Cargar funciones de configuraciones para acceder a wp_pos_get_currency_symbol
        require_once WP_POS_PLUGIN_DIR . 'modules/settings/includes/settings-functions.php';
        
        // Incluir controlador de servicios para buscar servicios via AJAX
        require_once WP_POS_PLUGIN_DIR . 'modules/services/controllers/class-pos-services-controller.php';
    }

    /**
     * Registrar el mu00f3dulo en el sistema
     *
     * @since 1.0.0
     */
    public function register_module() {
        $registry = WP_POS_Registry::get_instance();
        
        $module = array(
            'id' => $this->module_id,
            'name' => __('Ventas', 'wp-pos'),
            'description' => __('Gestiona ventas, pagos y recibos.', 'wp-pos'),
            'version' => '1.0.0',
            'author' => 'WP-POS Team',
            'dependencies' => array('core'),
            'instance' => $this,
        );
        
        $registry->register_module($this->module_id, $module);
    }

    /**
     * Registrar rutas REST API
     *
     * @since 1.0.0
     */
    public function register_rest_routes() {
        $controller = new WP_POS_Sales_REST_Controller();
        $controller->register_routes();
    }

    /**
     * Registrar menu00fas administrativos adicionales
     *
     * @since 1.0.0
     */
    public function register_admin_menu() {
        // Submenu00fas adicionales para ventas
        // Estas pu00e1ginas ya estu00e1n registradas en bootstrap.php pero podru00edamos au00f1adir mu00e1s
    }

    /**
     * Filtrar elementos del menu00fa administrativo
     *
     * @since 1.0.0
     * @param array $menu_items Items actuales del menu00fa
     * @return array Items modificados
     */
    public function filter_admin_menu($menu_items) {
        // Personalizar items del menu00fa relacionados con ventas
        if (isset($menu_items['sales'])) {
            $menu_items['sales']['submenu'] = array(
                'sales_list' => array(
                    'title' => __('Todas las Ventas', 'wp-pos'),
                    'url' => wp_pos_get_admin_url('sales'),
                ),
                'new_sale' => array(
                    'title' => __('Nueva Venta', 'wp-pos'),
                    'url' => wp_pos_get_admin_url('new-sale'),
                ),
                'registers' => array(
                    'title' => __('Cajas Registradoras', 'wp-pos'),
                    'url' => wp_pos_get_admin_url('registers'),
                ),
                'receipts' => array(
                    'title' => __('Recibos', 'wp-pos'),
                    'url' => wp_pos_get_admin_url('receipts'),
                ),
            );
        }
        
        return $menu_items;
    }

    /**
     * Registrar scripts y estilos para admin
     *
     * @since 1.0.0
     * @param string $hook_suffix Sufijo de pu00e1gina actual
     */
    public function register_admin_scripts($hook_suffix) {
        // Verificar si estamos en una pu00e1gina de ventas
        if (!is_string($hook_suffix)) {
            return;
        }
        
        $is_sales_page = wp_pos_safe_strpos($hook_suffix, 'wp-pos-sales') !== false;
        $is_new_sale_page = wp_pos_safe_strpos($hook_suffix, 'wp-pos-new-sale') !== false;
        $is_sale_details_page = wp_pos_safe_strpos($hook_suffix, 'wp-pos-sale-details') !== false;
        $is_print_receipt_page = wp_pos_safe_strpos($hook_suffix, 'wp-pos-print-receipt') !== false;
        
        // Si no estamos en ninguna pu00e1gina relacionada con ventas, no cargar nada
        if (!$is_sales_page && !$is_new_sale_page && !$is_sale_details_page && !$is_print_receipt_page) {
            return;
        }
        
        // Script principal para todas las pu00e1ginas de ventas
        wp_enqueue_script(
            'wp-pos-sales-admin',
            plugin_dir_url(__FILE__) . 'assets/js/admin-sales.js',
            array('jquery', 'wp-pos-admin'),
            WP_POS_VERSION,
            true
        );
        
        // Estilos especu00edficos
        wp_enqueue_style(
            'wp-pos-sales-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin-sales.css',
            array('wp-pos-admin'),
            WP_POS_VERSION
        );
        
        // Estilos especu00edficos para la pu00e1gina de detalles de venta
        if ($is_sale_details_page) {
            wp_enqueue_style(
                'wp-pos-sale-details',
                plugin_dir_url(__FILE__) . 'assets/css/admin-sale-details.css',
                array('wp-pos-sales-admin'),
                WP_POS_VERSION
            );
        }
        
        // Localizar variables para el script
        wp_localize_script(
            'wp-pos-sales-admin',
            'wp_pos_sales',
            array(
                'nonce' => wp_create_nonce('wp_pos_sales_nonce'),
                'payment_methods' => wp_pos_get_payment_methods(),
                'sale_statuses' => wp_pos_get_sale_statuses(),
                'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : wp_pos_get_currency_symbol(),
                'i18n' => array(
                    'confirm_delete' => __('u00bfEstu00e1s seguro de que deseas eliminar esta venta?', 'wp-pos'),
                    'sale_created' => __('Venta creada correctamente.', 'wp-pos'),
                    'sale_updated' => __('Venta actualizada correctamente.', 'wp-pos'),
                    'sale_deleted' => __('Venta eliminada correctamente.', 'wp-pos'),
                    'error' => __('Ha ocurrido un error. Por favor, intu00e9ntalo de nuevo.', 'wp-pos'),
                ),
            )
        );
        
        // Cargar script de nueva venta en admin
        if ($is_new_sale_page) {
            // Load new-sale.js from plugin root assets/js
            wp_enqueue_script(
                'wp-pos-new-sale',
                WP_POS_PLUGIN_URL . 'assets/js/new-sale.js',
                array('jquery', 'wp-api'),
                WP_POS_VERSION,
                true
            );
        }
    }

    /**
     * Registrar scripts y estilos para frontend
     *
     * @since 1.0.0
     */
    public function register_frontend_scripts() {
        // Solo cargar en pu00e1gina de POS
        if (!wp_pos_is_pos_page()) {
            return;
        }
        
        // Script especu00edfico de ventas para frontend
        wp_enqueue_script(
            'wp-pos-sales-frontend',
            plugin_dir_url(__FILE__) . 'assets/js/frontend-sales.js',
            array('jquery', 'wp-pos-frontend'),
            WP_POS_VERSION,
            true
        );
        
        // Estilos especu00edficos
        wp_enqueue_style(
            'wp-pos-sales-frontend',
            plugin_dir_url(__FILE__) . 'assets/css/frontend-sales.css',
            array('wp-pos-frontend'),
            WP_POS_VERSION
        );
        
        // Localizar variables para el script
        wp_localize_script(
            'wp-pos-sales-frontend',
            'wp_pos_sales',
            array(
                'nonce' => wp_create_nonce('wp_pos_sales_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'payment_methods' => wp_pos_get_payment_methods(),
                'sale_statuses' => wp_pos_get_sale_statuses(),
                'require_customer' => wp_pos_get_option('require_customer', 'no'),
                'default_payment_method' => wp_pos_get_option('default_payment_method', 'cash'),
                'i18n' => array(
                    'empty_cart' => __('No hay productos en el carrito.', 'wp-pos'),
                    'add_payment' => __('Au00f1adir Pago', 'wp-pos'),
                    'payment_required' => __('Se requiere al menos un mu00e9todo de pago.', 'wp-pos'),
                    'payment_exceeds' => __('El monto total de pagos excede el total de la venta.', 'wp-pos'),
                    'payment_insufficient' => __('El monto total de pagos es insuficiente.', 'wp-pos'),
                    'customer_required' => __('Se requiere seleccionar un cliente.', 'wp-pos'),
                    'sale_completed' => __('Venta completada correctamente.', 'wp-pos'),
                    'print_receipt' => __('Imprimir Recibo', 'wp-pos'),
                    'new_sale' => __('Nueva Venta', 'wp-pos'),
                    'error' => __('Ha ocurrido un error. Por favor, intu00e9ntalo de nuevo.', 'wp-pos'),
                ),
            )
        );
    }

    /**
     * Renderizar interfaz de ventas en frontend (shortcode handler)
     *
     * @since 1.0.0
     * @param array $atts Atributos del shortcode
     */
    public function render_sales_interface($atts) {
        // Verificar permisos
        if (!current_user_can('process_sales')) {
            wp_pos_template_notice(
                __('No tienes permisos para procesar ventas.', 'wp-pos'),
                'error'
            );
            return;
        }
        
        // Cargar productos y clientes
        $controller = new WP_POS_Sales_Controller();
        $products = $controller->get_products();
        $customers = $controller->get_customers();
        
        // Cargar interfaz de ventas
        wp_pos_load_template('sales-interface', array(
            'products' => $products,
            'customers' => $customers,
            'payment_methods' => wp_pos_get_payment_methods(),
            'registers' => $controller->get_registers(),
            'user' => wp_get_current_user(),
        ));
    }

    /**
     * Handler para AJAX create_sale
     *
     * @since 1.0.0
     */
    public function ajax_create_sale() {
        // Verificar nonce
        check_ajax_referer('wp_pos_sales_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('process_sales')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para procesar ventas.', 'wp-pos'),
            ));
        }
        
        // Obtener datos
        $sale_data = isset($_POST['sale']) ? $_POST['sale'] : array();
        if (empty($sale_data)) {
            wp_send_json_error(array(
                'message' => __('No se recibieron datos de venta.', 'wp-pos'),
            ));
        }
        
        // Sanitizar datos
        $sale_data = wp_pos_sanitize_array($sale_data);
        
        // Procesar venta
        $controller = new WP_POS_Sales_Controller();
        $result = $controller->create_sale($sale_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
            ));
        }
        
        // u00c9xito
        wp_send_json_success(array(
            'message' => __('Venta creada correctamente.', 'wp-pos'),
            'sale_id' => $result,
            'receipt_url' => wp_pos_safe_add_query_arg(array(
                'sale_id' => $result,
                'action' => 'view_receipt',
            ), wp_pos_get_pos_url()),
        ));
    }

    /**
     * Handler para AJAX update_sale
     *
     * @since 1.0.0
     */
    public function ajax_update_sale() {
        // Verificar nonce
        check_ajax_referer('wp_pos_sales_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('process_sales')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para procesar ventas.', 'wp-pos'),
            ));
        }
        
        // Obtener datos
        $sale_id = isset($_POST['sale_id']) ? absint($_POST['sale_id']) : 0;
        $sale_data = isset($_POST['sale']) ? $_POST['sale'] : array();
        
        if (empty($sale_id) || empty($sale_data)) {
            wp_send_json_error(array(
                'message' => __('Datos de venta incompletos.', 'wp-pos'),
            ));
        }
        
        // Sanitizar datos
        $sale_data = wp_pos_sanitize_array($sale_data);
        
        // Actualizar venta
        $controller = new WP_POS_Sales_Controller();
        $result = $controller->update_sale($sale_id, $sale_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
            ));
        }
        
        // u00c9xito
        wp_send_json_success(array(
            'message' => __('Venta actualizada correctamente.', 'wp-pos'),
            'sale_id' => $sale_id,
        ));
    }

    /**
     * Handler para AJAX delete_sale
     *
     * @since 1.0.0
     */
    public function ajax_delete_sale() {
        // Verificar nonce
        check_ajax_referer('wp_pos_sales_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_pos')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para eliminar ventas.', 'wp-pos'),
            ));
        }
        
        // Obtener ID de venta
        $sale_id = isset($_POST['sale_id']) ? absint($_POST['sale_id']) : 0;
        
        if (empty($sale_id)) {
            wp_send_json_error(array(
                'message' => __('ID de venta no vu00e1lido.', 'wp-pos'),
            ));
        }
        
        // Eliminar venta
        $controller = new WP_POS_Sales_Controller();
        $result = $controller->delete_sale($sale_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
            ));
        }
        
        // u00c9xito
        wp_send_json_success(array(
            'message' => __('Venta eliminada correctamente.', 'wp-pos'),
        ));
    }

    /**
     * Handler para AJAX get_sale
     *
     * @since 1.0.0
     */
    public function ajax_get_sale() {
        // Verificar nonce
        check_ajax_referer('wp_pos_sales_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('view_pos')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para ver ventas.', 'wp-pos'),
            ));
        }
        
        // Obtener ID de venta
        $sale_id = isset($_GET['sale_id']) ? absint($_GET['sale_id']) : 0;
        
        if (empty($sale_id)) {
            wp_send_json_error(array(
                'message' => __('ID de venta no vu00e1lido.', 'wp-pos'),
            ));
        }
        
        // Obtener venta
        $controller = new WP_POS_Sales_Controller();
        $sale = $controller->get_sale($sale_id);
        
        if (is_wp_error($sale)) {
            wp_send_json_error(array(
                'message' => $sale->get_error_message(),
            ));
        }
        
        if (!$sale) {
            wp_send_json_error(array(
                'message' => __('Venta no encontrada.', 'wp-pos'),
            ));
        }
        
        // u00c9xito
        wp_send_json_success($sale);
    }

    /**
     * Procesar una venta
     *
     * @since 1.0.0
     * @param array $sale_data Datos de la venta
     * @param array $context Contexto adicional
     * @return int|WP_Error ID de la venta o error
     */
    public function process_sale($sale_data, $context = array()) {
        // Procesar usando el controlador
        $controller = new WP_POS_Sales_Controller();
        $result = $controller->create_sale($sale_data);
        
        // Disparar acciu00f3n despuu00e9s de procesar venta
        if (!is_wp_error($result)) {
            do_action('wp_pos_after_sale_processed', $result, $context);
        }
        
        return $result;
    }

    /**
     * Acciones posteriores al procesamiento de una venta
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @param array $context Contexto adicional
     */
    public function after_sale_processed($sale_id, $context = array()) {
        // Au00f1adir al log
        wp_pos_log(
            sprintf(__('Venta #%s procesada correctamente.', 'wp-pos'), $sale_id),
            'info',
            array('sale_id' => $sale_id, 'context' => $context)
        );
        
        // Si se solicita impresiu00f3n automu00e1tica
        if ('yes' === wp_pos_get_option('print_automatically', 'no')) {
            // Aqunu00ed iru00eda lu00f3gica para impresiu00f3n automu00e1tica
        }
    }

    /**
     * Filtrar formato de nu00famero de venta
     *
     * @since 1.0.0
     * @param string $number Nu00famero generado
     * @param int $count Contador
     * @param string $date_part Parte de fecha
     * @return string Nu00famero modificado
     */
    public function filter_sale_number($number, $count, $date_part) {
        // Posibilidad de personalizar formato del nu00famero de venta
        return $number;
    }

    /**
     * Generar nu00famero de venta
     *
     * @since 1.0.0
     * @return string Nu00famero u00fanico de venta
     */
    public function generate_sale_number() {
        // Obtener prefijo de opciones
        $options = wp_pos_get_option();
        $prefix = isset($options['sales_number_prefix']) ? $options['sales_number_prefix'] : 'POS';
        
        // Generar fecha YYYYMMDD
        $date = date('Ymd');
        
        // Buscar u00faltimo nu00famero de secuencia para hoy
        $controller = WP_POS_Sales_Controller::get_instance();
        $last_number = $controller->get_last_sale_number();
        
        // Determinar secuencia
        $sequence = 1;
        if (!empty($last_number)) {
            // Intentar extraer secuencia del u00faltimo nu00famero
            $parts = explode('-', $last_number);
            if (count($parts) >= 3) {
                // Si la fecha coincide con hoy, incrementar secuencia
                if ($parts[1] === $date) {
                    $sequence = intval($parts[2]) + 1;
                }
            }
        }
        
        // Formatear secuencia a 4 du00edgitos
        $sequence_str = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        // Formato final: PREFIX-YYYYMMDD-SEQUENCE
        $number = $prefix . '-' . $date . '-' . $sequence_str;
        
        // Posibilidad de personalizar formato del nu00famero de venta
        return $number;
    }

    /**
     * AJAX handler for service search
     *
     * @since 1.0.0
     */
    public function ajax_search_services() {
        // Security check
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wp_pos_nonce')) {
            wp_send_json_error('Nonce inválido');
        }
        $term = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $services = WP_POS_Services_Controller::get_instance()->get_services(['search' => $term]);
        wp_send_json_success($services);
    }
}

// Inicializar mu00f3dulo
WP_POS_Sales_Module::get_instance();
