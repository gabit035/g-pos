<?php
/**
 * Módulo de Configuración para WP-POS
 *
 * Gestiona todas las opciones y ajustes del sistema de punto de venta.
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
 * Clase principal del módulo de configuración
 *
 * @since 1.0.0
 */
class WP_POS_Settings_Module {

    /**
     * ID único del módulo
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $module_id = 'settings';

    /**
     * Grupos de configuración registrados
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $settings_groups = array();
    
    /**
     * Instancia del controlador de configuraciones
     *
     * @since 1.0.0
     * @access private
     * @var WP_POS_Settings_Controller
     */
    private $settings_controller = null;
    
    /**
     * Instancia de la página de configuraciones
     *
     * @since 1.0.0
     * @access private
     * @var WP_POS_Settings_Page
     */
    private $settings_page = null;

    /**
     * Instancia singleton
     *
     * @since 1.0.0
     * @access private
     * @static
     * @var WP_POS_Settings_Module
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     *
     * @since 1.0.0
     * @static
     * @return WP_POS_Settings_Module Instancia
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
        $this->register_settings_groups();
        $this->initialize_components();
    }

    /**
     * Configurar acciones y filtros
     *
     * @since 1.0.0
     * @access private
     */
    private function setup_actions() {
        // Registrar el módulo
        add_action('wp_pos_init_modules', array($this, 'register_module'));

        // Admin
        add_action('wp_pos_admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));

        // AJAX handlers
        add_action('wp_ajax_wp_pos_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_wp_pos_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_wp_pos_test_printing', array($this, 'ajax_test_printing'));

        // Filtros
        add_filter('wp_pos_admin_menu_items', array($this, 'filter_admin_menu'));
        add_filter('wp_pos_get_option', array($this, 'filter_option_value'), 10, 3);
    }

    /**
     * Cargar dependencias del módulo
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies() {
        // Cargar controladores
        require_once dirname(__FILE__) . '/controllers/class-pos-settings-controller.php';
        require_once dirname(__FILE__) . '/controllers/class-pos-settings-fields-renderer.php';
        require_once dirname(__FILE__) . '/controllers/class-pos-settings-sanitize.php';
        require_once dirname(__FILE__) . '/controllers/class-pos-settings-page.php';
        
        // Cargar clases de opciones
        require_once dirname(__FILE__) . '/options/class-pos-settings-group.php';
        require_once dirname(__FILE__) . '/options/class-pos-settings-field.php';
        
        // Cargar funciones auxiliares
        require_once dirname(__FILE__) . '/includes/settings-functions.php';
        require_once dirname(__FILE__) . '/includes/default-settings-groups.php';
    }
    
    /**
     * Inicializar componentes del módulo
     *
     * @since 1.0.0
     * @access private
     */
    private function initialize_components() {
        // Inicializar controlador de configuraciones
        $this->settings_controller = new WP_POS_Settings_Controller();
        
        // Inicializar página de configuraciones
        $this->settings_page = new WP_POS_Settings_Page($this->settings_groups);
        
        // Inicializar renderizador de campos y sanitizador (funcionan como instancias independientes)
        new WP_POS_Settings_Fields_Renderer();
        new WP_POS_Settings_Sanitize();
    }

    /**
     * Registrar el módulo en el sistema
     *
     * @since 1.0.0
     */
    public function register_module() {
        $registry = WP_POS_Registry::get_instance();
        
        $module = array(
            'id' => $this->module_id,
            'name' => __('Configuración', 'wp-pos'),
            'description' => __('Gestiona las opciones y ajustes del punto de venta.', 'wp-pos'),
            'version' => '1.0.0',
            'author' => 'WP-POS Team',
            'dependencies' => array('core'),
            'instance' => $this,
        );
        
        $registry->register_module($this->module_id, $module);
    }

    /**
     * Registrar grupos de configuraciones
     *
     * @since 1.0.0
     * @access private
     */
    private function register_settings_groups() {
        // Obtener grupos predeterminados desde la función auxiliar
        $this->settings_groups = wp_pos_get_default_settings_groups();
        
        // Aplicar filtro para permitir que otros complementos añadan sus propios grupos
        $this->settings_groups = apply_filters('wp_pos_register_settings_groups', $this->settings_groups);
    }

    /**
     * Registrar menús administrativos
     *
     * @since 1.0.0
     */
    public function register_admin_menu() {
        // Los menús principales se registran en la clase WP_POS_Settings_Page
    }

    /**
     * Registrar configuraciones
     *
     * @since 1.0.0
     */
    public function register_settings() {
        // Las configuraciones se registran en la clase WP_POS_Settings_Page durante su inicialización
    }

    /**
     * Filtrar elementos del menú administrativo
     *
     * @since 1.0.0
     * @param array $menu_items Items actuales del menú
     * @return array Items modificados
     */
    public function filter_admin_menu($menu_items) {
        // Personalizar items del menú relacionados con configuración
        if (isset($menu_items['settings'])) {
            $menu_items['settings']['submenu'] = array();
            
            // Añadir cada grupo de configuración como submenú
            foreach ($this->settings_groups as $group_id => $group) {
                $menu_items['settings']['submenu'][$group_id] = array(
                    'title' => $group->get_title(),
                    'url' => admin_url('admin.php?page=wp-pos-settings&tab=' . $group_id),
                );
            }
        }
        
        return $menu_items;
    }

    /**
     * Registrar scripts y estilos para admin
     *
     * @since 1.0.0
     * @param string $hook_suffix Sufijo de página actual
     */
    public function register_admin_scripts($hook_suffix) {
        // Verificar si estamos en una página de configuraciones
        if (!is_string($hook_suffix)) {
            return;
        }
        
        $is_settings_page = wp_pos_safe_strpos($hook_suffix, 'wp-pos-settings') !== false;
        
        // Si no estamos en la página de configuraciones, no cargar nada
        if (!$is_settings_page) {
            return;
        }
        
        // Enqueue scripts y estilos comunes para configuraciones
        wp_enqueue_style(
            'wp-pos-settings-admin',
            plugin_dir_url(__FILE__) . 'assets/css/settings-admin.css',
            array('wp-pos-admin'),
            WP_POS_VERSION
        );
        
        // Script específico de configuraciones para admin
        wp_enqueue_script(
            'wp-pos-settings-admin',
            plugin_dir_url(__FILE__) . 'assets/js/admin-settings.js',
            array('jquery', 'wp-pos-admin'),
            WP_POS_VERSION,
            true
        );
        
        // Estilos específicos
        wp_enqueue_style(
            'wp-pos-settings-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin-settings.css',
            array(),
            WP_POS_VERSION
        );
        
        // Color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Media upload
        wp_enqueue_media();
        
        // CodeMirror para campos de código
        if (function_exists('wp_enqueue_code_editor')) {
            wp_enqueue_code_editor(array('type' => 'text/html'));
            wp_enqueue_script('wp-theme-plugin-editor');
        }
        
        // Localizar variables para el script
        wp_localize_script(
            'wp-pos-settings-admin',
            'wp_pos_settings',
            array(
                'nonce' => wp_create_nonce('wp_pos_settings_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'i18n' => array(
                    'saved' => __('Configuración guardada correctamente.', 'wp-pos'),
                    'reset' => __('Configuración restablecida a valores predeterminados.', 'wp-pos'),
                    'confirm_reset' => __('¿Estás seguro de que deseas restablecer todas las opciones a sus valores predeterminados? Esta acción no se puede deshacer.', 'wp-pos'),
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'wp-pos'),
                    'test_success' => __('Prueba exitosa.', 'wp-pos'),
                    'test_failed' => __('Prueba fallida. Verifica tu configuración.', 'wp-pos'),
                    'select_file' => __('Seleccionar archivo', 'wp-pos'),
                    'use_file' => __('Usar archivo', 'wp-pos'),
                    'select_image' => __('Seleccionar imagen', 'wp-pos'),
                    'use_image' => __('Usar imagen', 'wp-pos'),
                    'testing' => __('Probando...', 'wp-pos'),
                    'test_print' => __('Imprimir Recibo de Prueba', 'wp-pos'),
                ),
            )
        );
    }

    /**
     * Manejador AJAX para guardar configuraciones
     *
     * @since 1.0.0
     */
    public function ajax_save_settings() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_pos_settings_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad. Por favor, recarga la página.', 'wp-pos')));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-pos')));
        }
        
        // Obtener datos
        $data = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        // Sanitizar y guardar opciones
        $sanitizer = new WP_POS_Settings_Sanitize();
        $sanitized = $sanitizer->sanitize($data, $this->get_all_fields());
        
        // Actualizar opciones
        update_option('wp_pos_options', $sanitized);
        
        // Respuesta exitosa
        wp_send_json_success(array('message' => __('Configuración guardada correctamente.', 'wp-pos')));
    }

    /**
     * Manejador AJAX para restablecer configuraciones
     *
     * @since 1.0.0
     */
    public function ajax_reset_settings() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_pos_settings_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad. Por favor, recarga la página.', 'wp-pos')));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-pos')));
        }
        
        // Eliminar opciones guardadas
        delete_option('wp_pos_options');
        
        // Respuesta exitosa
        wp_send_json_success(array('message' => __('Configuración restablecida a valores predeterminados.', 'wp-pos')));
    }

    /**
     * Manejador AJAX para prueba de impresión
     *
     * @since 1.0.0
     */
    public function ajax_test_printing() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_pos_settings_nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad. Por favor, recarga la página.', 'wp-pos')));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción.', 'wp-pos')));
        }
        
        // Obtener configuración de impresora
        $printer_type = isset($_POST['printer_type']) ? sanitize_text_field($_POST['printer_type']) : 'browser';
        $printer_ip = isset($_POST['printer_ip']) ? sanitize_text_field($_POST['printer_ip']) : '';
        $printer_port = isset($_POST['printer_port']) ? absint($_POST['printer_port']) : 9100;
        
        // Simulación de prueba exitosa
        // En una implementación real, aquí probaríamos la conexión con la impresora
        $success = true;
        
        if ($printer_type === 'network' && empty($printer_ip)) {
            $success = false;
        }
        
        // Respuesta
        if ($success) {
            wp_send_json_success(array('message' => __('Prueba de impresión exitosa.', 'wp-pos')));
        } else {
            wp_send_json_error(array('message' => __('No se pudo conectar con la impresora. Verifica la configuración.', 'wp-pos')));
        }
    }
    
    /**
     * Filtrar valor de opción
     *
     * @since 1.0.0
     * @param mixed $value Valor actual
     * @param string $key Clave de la opción
     * @param mixed $default Valor predeterminado
     * @return mixed Valor filtrado
     */
    public function filter_option_value($value, $key, $default) {
        // Aquí podemos aplicar lógica específica para ciertas opciones
        return $value;
    }
    
    /**
     * Obtener todos los campos configurados
     *
     * @since 1.0.0
     * @return array Campos configurados
     */
    private function get_all_fields() {
        $all_fields = array();
        
        foreach ($this->settings_groups as $group_id => $group) {
            $fields = $group->get_fields();
            
            foreach ($fields as $field_id => $field) {
                $key = $group_id . '_' . $field_id;
                $all_fields[$key] = $field->get_args();
            }
        }
        
        return $all_fields;
    }
}

// Inicializar módulo
WP_POS_Settings_Module::get_instance();
