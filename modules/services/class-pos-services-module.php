<?php
/**
 * Módulo de Servicios para WP-POS
 *
 * Gestiona servicios sin stock e integración en ventas.
 *
 * @package WP-POS
 * @subpackage Services
 * @since 1.0.0
 */
if (!defined('ABSPATH')) exit;

class WP_POS_Services_Module {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Registrar módulo y rutas
        add_action('wp_pos_register_modules', array($this, 'register_module'));
        add_action('wp_pos_register_rest_routes', array($this, 'register_rest_routes'));

        // Menú admin y assets
        add_action('wp_pos_admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'load_admin_assets'));

        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'load_public_assets'));
    }

    public function register_module() {
        wp_pos_register_module('services', __('Servicios', 'wp-pos'), 'dashicons-admin-tools');
    }

    public function register_rest_routes() {
        require_once __DIR__ . '/api/class-pos-services-rest-controller.php';
        $controller = new WP_POS_Services_REST_Controller();
        $controller->register_routes();
    }

    public function register_admin_menu() {
        // List Services
        add_submenu_page(
            'wp-pos',
            __('Servicios', 'wp-pos'),
            __('Servicios', 'wp-pos'),
            apply_filters('wp_pos_menu_capability', 'view_services', 'services'),
            'wp-pos-services',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render admin page based on action
     */
    public function render_admin_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        if ($action === 'add' || $action === 'edit') {
            require_once WP_POS_MODULES_DIR . 'services/templates/admin-service-form.php';
        } else {
            require_once WP_POS_MODULES_DIR . 'services/templates/admin-services.php';
        }
    }

    public function load_admin_assets($hook) {
        if (false === strpos($hook, 'wp-pos-services')) return;
        // Load core admin and products styles to match UI
        wp_enqueue_style('wp-pos-admin', WP_POS_PLUGIN_URL . 'assets/css/admin.css', array(), WP_POS_VERSION);
        wp_enqueue_style('wp-pos-products-enhanced', WP_POS_PLUGIN_URL . 'assets/css/wp-pos-products-enhanced.css', array(), WP_POS_VERSION);
        wp_enqueue_style('wp-pos-services-admin', WP_POS_PLUGIN_URL . 'modules/services/assets/css/services-admin.css', array(), WP_POS_VERSION);
        wp_enqueue_script('wp-pos-services-admin', WP_POS_PLUGIN_URL . 'modules/services/assets/js/services-admin.js', array('jquery'), WP_POS_VERSION, true);
        wp_localize_script('wp-pos-services-admin', 'wp_pos_services_admin', array(
            'rest_url' => get_rest_url(null, 'wp-pos/v1/services'),
            'nonce'    => wp_create_nonce('wp_rest'),
            'add_url'  => admin_url('admin.php?page=wp-pos-services&action=add'),
            'i18n'     => array(
                'name'        => __('Nombre', 'wp-pos'),
                'sale_price'  => __('Precio de venta', 'wp-pos'),
                'loading'     => __('Cargando...', 'wp-pos'),
                'no_services' => __('No hay servicios.', 'wp-pos'),
                'description'      => __('Descripción', 'wp-pos'),
                'purchase_price'   => __('Precio de compra', 'wp-pos'),
                'add_service'      => __('Agregar servicio', 'wp-pos'),
                'name_label'       => __('Nombre', 'wp-pos'),
                'desc_label'       => __('Descripción', 'wp-pos'),
                'purchase_label'   => __('Precio compra', 'wp-pos'),
                'sale_label'       => __('Precio venta', 'wp-pos'),
                'error_creating' => __('Error al crear servicio', 'wp-pos'),
            ),
        ));
    }

    public function load_public_assets() {
        wp_enqueue_script('wp-pos-services', WP_POS_PLUGIN_URL . 'modules/services/assets/js/services.js', array('jquery'), WP_POS_VERSION, true);
        wp_localize_script('wp-pos-services', 'wp_pos_services', array(
            'rest_url' => get_rest_url(null, 'wp-pos/v1/services'),
            'nonce'    => wp_create_nonce('wp_pos_nonce'),
        ));
    }
}

// Inicializar módulo
WP_POS_Services_Module::get_instance();
