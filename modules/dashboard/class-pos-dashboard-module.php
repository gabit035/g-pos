<?php
/**
 * WP-POS Module: Dashboard
 *
 * @package WP-POS
 * @subpackage Dashboard
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para el módulo de Dashboard
 */
class WP_POS_Dashboard_Module {
    
    /**
     * Instancia única de la clase
     *
     * @var WP_POS_Dashboard_Module
     */
    private static $instance = null;
    
    /**
     * Obtener instancia única
     *
     * @return WP_POS_Dashboard_Module
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor privado para prevenir la creación de instancias
     */
    private function __construct() {
        // Registrar menú
        add_action('admin_menu', array($this, 'register_admin_menu'), 5);
        
        // Scripts y estilos
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
        
        // Redirección al activar
        add_action('activated_plugin', array($this, 'redirect_after_activation'), 10, 2);
    }
    
    /**
     * Registrar menú de administración
     */
    public function register_admin_menu() {
        // La página principal ahora será el dashboard
        add_menu_page(
            __('WP-POS', 'wp-pos'),
            __('WP-POS', 'wp-pos'),
            'access_pos', // Permitir a todos los roles con capacidades POS
            'wp-pos',
            array($this, 'display_dashboard_page'),
            'dashicons-cart',
            25
        );
        
        // Ya no registramos el Dashboard como submenu para evitar duplicación
        // El primer elemento del menú principal ya funciona como Dashboard
    }
    
    /**
     * Registrar scripts y estilos
     */
    public function register_admin_scripts($hook) {
        // Solo cargar en nuestra página
        if (strpos($hook, 'wp-pos') === false) {
            return;
        }
        
        // Estilos
        wp_enqueue_style(
            'wp-pos-dashboard-styles',
            plugin_dir_url(__FILE__) . 'assets/css/dashboard-styles.css',
            array(),
            WP_POS_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'wp-pos-dashboard-scripts',
            plugin_dir_url(__FILE__) . 'assets/js/dashboard-scripts.js',
            array('jquery'),
            WP_POS_VERSION,
            true
        );
        
        // Añadir Dashicons
        wp_enqueue_style('dashicons');
        
        // Cargar estilos de notificaciones de stock
        wp_enqueue_style(
            'wp-pos-stock-notifications',
            WP_POS_PLUGIN_URL . 'assets/css/stock-notifications.css',
            array(),
            WP_POS_VERSION
        );
    }
    
    /**
     * Mostrar página de dashboard
     */
    public function display_dashboard_page() {
        include_once(plugin_dir_path(__FILE__) . 'dashboard-page.php');
    }
    
    /**
     * Redireccionar después de activar el plugin
     */
    public function redirect_after_activation($plugin, $network_activation) {
        if ($plugin == 'g-pos/wp-pos.php' || $plugin == 'g-pos/index.php') {
            wp_redirect(admin_url('admin.php?page=wp-pos&welcome=1'));
            exit();
        }
    }
}

// La instancia se crea a través de get_instance() cuando se necesita
