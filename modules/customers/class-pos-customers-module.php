<?php
/**
 * Mu00f3dulo de Clientes para WP-POS
 *
 * Gestiona todas las funcionalidades relacionadas con clientes,
 * incluyendo bu00basqueda, listado y gestiu00f3n de datos de clientes.
 *
 * @package WP-POS
 * @subpackage Customers
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del mu00f3dulo de clientes
 *
 * @since 1.0.0
 */
class WP_POS_Customers_Module {

    /**
     * ID u00fanico del mu00f3dulo
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $module_id = 'customers';

    /**
     * Instancia singleton
     *
     * @since 1.0.0
     * @access private
     * @static
     * @var WP_POS_Customers_Module
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     *
     * @since 1.0.0
     * @static
     * @return WP_POS_Customers_Module Instancia
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
        add_action('wp_ajax_wp_pos_search_customers', array($this, 'ajax_search_customers'));
        add_action('wp_ajax_wp_pos_get_customer', array($this, 'ajax_get_customer'));
        add_action('wp_ajax_wp_pos_create_customer', array($this, 'ajax_create_customer'));
        add_action('wp_ajax_wp_pos_update_customer', array($this, 'ajax_update_customer'));

        // Frontend
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_scripts'));
        add_action('wp_pos_shortcode_customers', array($this, 'render_customers_interface'));

        // Hooks para ventas
        add_action('wp_pos_before_sale_create', array($this, 'validate_customer_for_sale'), 10, 2);
        add_action('wp_pos_after_sale_processed', array($this, 'update_customer_stats'), 20, 2);

        // Filtros
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
        
        // Cargar controladores
        require_once dirname(__FILE__) . '/controllers/class-pos-customers-controller.php';
        
        // Cargar clase base para REST API
        require_once WP_POS_PLUGIN_DIR . 'includes/api/class-pos-rest-controller.php';
        
        // Cargar API REST
        require_once dirname(__FILE__) . '/api/class-pos-customers-rest-controller.php';
        
        // Cargar funciones auxiliares
        require_once dirname(__FILE__) . '/includes/customers-functions.php';
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
            'name' => __('Clientes', 'wp-pos'),
            'description' => __('Gestiona clientes y sus datos en el punto de venta.', 'wp-pos'),
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
        $controller = new WP_POS_Customers_REST_Controller();
        $controller->register_routes();
    }

    /**
     * Registrar menu00fas administrativos adicionales
     *
     * @since 1.0.0
     */
    public function register_admin_menu() {
        // Estos menu00fas ya estu00e1n registrados en bootstrap.php pero podru00edamos au00f1adir mu00e1s
    }

    /**
     * Filtrar elementos del menu00fa administrativo
     *
     * @since 1.0.0
     * @param array $menu_items Items actuales del menu
     * @return array Items modificados
     */
    public function filter_admin_menu($menu_items) {
        // Personalizar items del menu relacionados con clientes
        if (isset($menu_items['customers'])) {
            $menu_items['customers']['submenu'] = array(
                'customers_list' => array(
                    'title' => __('Todos los Clientes', 'wp-pos'),
                    'url' => wp_pos_get_admin_url('customers'),
                ),
                'add_customer' => array(
                    'title' => __('Agregar Cliente', 'wp-pos'),
                    'url' => wp_pos_get_admin_url('add-customer'),
                ),
                'customer_groups' => array(
                    'title' => __('Grupos de Clientes', 'wp-pos'),
                    'url' => wp_pos_get_admin_url('customer-groups'),
                ),
                'import_export' => array(
                    'title' => __('Importar/Exportar', 'wp-pos'),
                    'url' => wp_pos_get_admin_url('import-export-customers'),
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
        // Verificar si estamos en una pu00e1gina de clientes
        if (!is_string($hook_suffix)) {
            return;
        }
        
        $is_customers_page = wp_pos_safe_strpos($hook_suffix, 'wp-pos') !== false;
        
        // Si no estamos en ninguna pu00e1gina relacionada con clientes, no cargar nada
        if (!$is_customers_page) {
            return;
        }
        
        // Script especu00edfico de clientes para admin
        wp_enqueue_script(
            'wp-pos-customers-admin',
            plugin_dir_url(__FILE__) . 'assets/js/admin-customers.js',
            array('jquery', 'wp-pos-admin'),
            WP_POS_VERSION,
            true
        );
        
        // Estilos especu00edficos
        wp_enqueue_style(
            'wp-pos-customers-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin-customers.css',
            array('wp-pos-admin'),
            WP_POS_VERSION
        );
        
        // Localizar variables para el script
        wp_localize_script(
            'wp-pos-customers-admin',
            'wp_pos_customers',
            array(
                'nonce' => wp_create_nonce('wp_pos_customers_nonce'),
                'i18n' => array(
                    'confirm_delete' => __('Estás seguro de que deseas eliminar este cliente?', 'wp-pos'),
                    'customer_created' => __('Cliente creado correctamente.', 'wp-pos'),
                    'customer_updated' => __('Cliente actualizado correctamente.', 'wp-pos'),
                    'customer_deleted' => __('Cliente eliminado correctamente.', 'wp-pos'),
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'wp-pos'),
                ),
            )
        );
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
        
        // Script especu00edfico de clientes para frontend
        wp_enqueue_script(
            'wp-pos-customers-frontend',
            plugin_dir_url(__FILE__) . 'assets/js/frontend-customers.js',
            array('jquery', 'wp-pos-frontend'),
            WP_POS_VERSION,
            true
        );
        
        // Estilos especu00edficos
        wp_enqueue_style(
            'wp-pos-customers-frontend',
            plugin_dir_url(__FILE__) . 'assets/css/frontend-customers.css',
            array('wp-pos-frontend'),
            WP_POS_VERSION
        );
        
        // Localizar variables para el script
        wp_localize_script(
            'wp-pos-customers-frontend',
            'wp_pos_customers',
            array(
                'nonce' => wp_create_nonce('wp_pos_customers_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'require_customer' => wp_pos_get_option('require_customer', 'no'),
                'default_customer' => wp_pos_get_option('default_customer', 0),
                'i18n' => array(
                    'no_customers' => __('No se encontraron clientes.', 'wp-pos'),
                    'searching' => __('Buscando...', 'wp-pos'),
                    'select_customer' => __('Seleccionar cliente', 'wp-pos'),
                    'new_customer' => __('Nuevo cliente', 'wp-pos'),
                    'create_customer' => __('Crear cliente', 'wp-pos'),
                    'cancel' => __('Cancelar', 'wp-pos'),
                    'customer_created' => __('Cliente creado correctamente.', 'wp-pos'),
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'wp-pos'),
                ),
            )
        );
    }

    /**
     * Renderizar interfaz de clientes en frontend (shortcode handler)
     *
     * @since 1.0.0
     * @param array $atts Atributos del shortcode
     */
    public function render_customers_interface($atts) {
        // Verificar permisos
        if (!current_user_can('view_pos')) {
            wp_pos_template_notice(
                __('No tienes permisos para acceder a los clientes.', 'wp-pos'),
                'error'
            );
            return;
        }
        
        // Cargar controlador
        $controller = new WP_POS_Customers_Controller();
        
        // Obtener grupos de clientes
        $groups = $controller->get_customer_groups();
        
        // Cargar interfaz de clientes
        wp_pos_load_template('customers-interface', array(
            'groups' => $groups,
        ));
    }

    /**
     * Handler para AJAX search_customers
     *
     * @since 1.0.0
     */
    public function ajax_search_customers() {
        // Verificar nonce
        check_ajax_referer('wp_pos_customers_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('view_pos')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para buscar clientes.', 'wp-pos'),
            ));
        }
        
        // Obtener paru00e1metros de bu00basqueda
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $group = isset($_GET['group']) ? absint($_GET['group']) : 0;
        $page = isset($_GET['page']) ? absint($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 20;
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'name';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC';
        
        // Buscar clientes
        $controller = new WP_POS_Customers_Controller();
        $customers = $controller->search_customers(array(
            'search' => $search,
            'group' => $group,
            'page' => $page,
            'per_page' => $per_page,
            'orderby' => $orderby,
            'order' => $order,
        ));
        
        // u00c9xito
        wp_send_json_success($customers);
    }

    /**
     * Handler para AJAX get_customer
     *
     * @since 1.0.0
     */
    public function ajax_get_customer() {
        // Verificar nonce
        check_ajax_referer('wp_pos_customers_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('view_pos')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para ver clientes.', 'wp-pos'),
            ));
        }
        
        // Obtener ID de cliente
        $customer_id = isset($_GET['customer_id']) ? absint($_GET['customer_id']) : 0;
        
        if (empty($customer_id)) {
            wp_send_json_error(array(
                'message' => __('ID de cliente no válido.', 'wp-pos'),
            ));
        }
        
        // Obtener cliente
        $controller = new WP_POS_Customers_Controller();
        $customer = $controller->get_customer($customer_id);
        
        if (is_wp_error($customer)) {
            wp_send_json_error(array(
                'message' => $customer->get_error_message(),
            ));
        }
        
        if (!$customer) {
            wp_send_json_error(array(
                'message' => __('Cliente no encontrado.', 'wp-pos'),
            ));
        }
        
        // u00c9xito
        wp_send_json_success($customer);
    }

    /**
     * Handler para AJAX create_customer
     *
     * @since 1.0.0
     */
    public function ajax_create_customer() {
        // Verificar nonce
        check_ajax_referer('wp_pos_customers_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_pos')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para crear clientes.', 'wp-pos'),
            ));
        }
        
        // Obtener datos
        $customer_data = isset($_POST['customer']) ? $_POST['customer'] : array();
        
        if (empty($customer_data)) {
            wp_send_json_error(array(
                'message' => __('No se recibieron datos de cliente.', 'wp-pos'),
            ));
        }
        
        // Sanitizar datos
        $customer_data = wp_pos_sanitize_array($customer_data);
        
        // Crear cliente
        $controller = new WP_POS_Customers_Controller();
        $result = $controller->create_customer($customer_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
            ));
        }
        
        // u00c9xito
        wp_send_json_success(array(
            'message' => __('Cliente creado correctamente.', 'wp-pos'),
            'customer_id' => $result,
            'customer' => $controller->get_customer($result),
        ));
    }

    /**
     * Handler para AJAX update_customer
     *
     * @since 1.0.0
     */
    public function ajax_update_customer() {
        // Verificar nonce
        check_ajax_referer('wp_pos_customers_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_pos')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para actualizar clientes.', 'wp-pos'),
            ));
        }
        
        // Obtener datos
        $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
        $customer_data = isset($_POST['customer']) ? $_POST['customer'] : array();
        
        if (empty($customer_id) || empty($customer_data)) {
            wp_send_json_error(array(
                'message' => __('Datos de cliente incompletos.', 'wp-pos'),
            ));
        }
        
        // Sanitizar datos
        $customer_data = wp_pos_sanitize_array($customer_data);
        
        // Actualizar cliente
        $controller = new WP_POS_Customers_Controller();
        $result = $controller->update_customer($customer_id, $customer_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
            ));
        }
        
        // u00c9xito
        wp_send_json_success(array(
            'message' => __('Cliente actualizado correctamente.', 'wp-pos'),
            'customer_id' => $customer_id,
        ));
    }

    /**
     * Validar cliente antes de crear una venta
     *
     * @since 1.0.0
     * @param array $sale_data Datos de la venta
     * @param WP_POS_Sales_Controller $controller Controlador de ventas
     * @return bool|WP_Error True si la validaciu00f3n es exitosa, WP_Error si hay problemas
     */
    public function validate_customer_for_sale($sale_data, $controller) {
        // Verificar si se requiere cliente
        if ('yes' === wp_pos_get_option('require_customer', 'no')) {
            // Verificar si hay cliente en los datos de venta
            if (empty($sale_data['customer_id'])) {
                return new WP_Error(
                    'customer_required',
                    __('Se requiere seleccionar un cliente para esta venta.', 'wp-pos')
                );
            }
            
            // Verificar que el cliente exista
            $customer_controller = new WP_POS_Customers_Controller();
            $customer = $customer_controller->get_customer($sale_data['customer_id']);
            
            if (!$customer) {
                return new WP_Error(
                    'invalid_customer',
                    __('El cliente seleccionado no es válido.', 'wp-pos')
                );
            }
        }
        
        return true;
    }

    /**
     * Actualizar estadu00edsticas del cliente despuu00e9s de procesar una venta
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @param array $context Contexto adicional
     */
    public function update_customer_stats($sale_id, $context = array()) {
        // Obtener datos de la venta
        $sale = wp_pos_get_sale($sale_id);
        
        if (!$sale || empty($sale->customer_id)) {
            return;
        }
        
        // Actualizar estadu00edsticas del cliente
        $customer_id = $sale->customer_id;
        $controller = new WP_POS_Customers_Controller();
        $customer = $controller->get_customer($customer_id);
        
        if (!$customer) {
            return;
        }
        
        // Incrementar contador de ventas y total gastado
        $total_spent = get_user_meta($customer_id, '_wp_pos_total_spent', true);
        $total_spent = floatval($total_spent) + floatval($sale->total);
        update_user_meta($customer_id, '_wp_pos_total_spent', $total_spent);
        
        $total_orders = get_user_meta($customer_id, '_wp_pos_order_count', true);
        $total_orders = intval($total_orders) + 1;
        update_user_meta($customer_id, '_wp_pos_order_count', $total_orders);
        
        // Actualizar fecha de ultima compra
        update_user_meta($customer_id, '_wp_pos_last_order', current_time('mysql'));
        
        // Añadir nota de log
        wp_pos_log(
            sprintf(__('Estadísticas actualizadas para cliente #%s', 'wp-pos'), $customer_id),
            'info',
            array('customer_id' => $customer_id, 'sale_id' => $sale_id)
        );
    }
}

// Inicializar mu00f3dulo
WP_POS_Customers_Module::get_instance();
