<?php
/**
 * Módulo de Rendimiento para WP-POS
 *
 * Proporciona herramientas para optimizar y monitorear el rendimiento del sistema.
 *
 * @package WP-POS
 * @subpackage Performance
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Cargar la clase base de módulos si no está cargada
if (!class_exists('WP_POS_Module_Abstract')) {
    require_once WP_POS_PLUGIN_DIR . 'includes/abstract-class-module.php';
}

/**
 * Clase principal del módulo de rendimiento
 *
 * @since 1.0.0
 */
class WP_POS_Performance_Module extends WP_POS_Module_Abstract {
    /**
     * @var string Versión del módulo
     */
    protected $version = '1.0.0';
    
    /**
     * @var string Categoría del módulo
     */
    protected $category = 'system';
    
    /**
     * @var string Descripción del módulo
     */
    protected $description = 'Herramientas para optimizar y monitorear el rendimiento del sistema.';

    /**
     * Constructor de la clase
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Propiedades del módulo
        $this->id = 'performance';
        $this->name = __('Rendimiento', 'wp-pos');
        $this->capability = 'manage_options';
        $this->icon = 'dashicons-performance';
        $this->position = 100;
        
        // Llamar al constructor del padre
        parent::__construct();
        
        // Registrar el módulo
        add_action('wp_pos_register_modules', array($this, 'register_module'));
        
        // Inicializar el módulo cuando esté listo
        add_action('wp_pos_modules_loaded', array($this, 'init_module'));
    }

    /**
     * Instancia singleton
     *
     * @since 1.0.0
     * @access private
     * @static
     * @var WP_POS_Performance_Module
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     *
     * @since 1.0.0
     * @static
     * @return WP_POS_Performance_Module Instancia
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }



    /**
     * Inicializar el módulo
     *
     * @since 1.0.0
     */
    public function init_module() {
        // Cargar archivos necesarios
        $this->includes();
        
        // Inicializar componentes
        $this->init_components();
        
        // Registrar hooks
        $this->register_hooks();
        
        // Notificar que el módulo está listo
        do_action('wp_pos_module_ready_performance');
    }

    /**
     * Incluir archivos necesarios
     *
     * @since 1.0.0
     */
    private function includes() {
        // Cargar la clase principal de rendimiento desde la ubicación estándar
        $loader_file = WP_POS_PLUGIN_DIR . 'includes/class-pos-performance-loader.php';
        
        if (!file_exists($loader_file)) {
            wp_die(
                sprintf(
                    __('Error: No se pudo cargar el módulo de rendimiento. Archivo no encontrado: %s', 'wp-pos'),
                    esc_html($loader_file)
                ),
                __('Error de carga de módulo', 'wp-pos'),
                ['back_link' => true]
            );
            return;
        }
        
        require_once $loader_file;
    }

    /**
     * Inicializar componentes del módulo
     *
     * @since 1.0.0
     */
    private function init_components() {
        // El cargador de rendimiento se inicializa a través de sus propios hooks
        // No es necesario hacer nada más aquí
    }

    /**
     * Registrar hooks de WordPress
     *
     * @since 1.0.0
     */
    private function register_hooks() {
        // Registrar estilos y scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Registrar menús adicionales
        add_action('admin_menu', array($this, 'register_admin_menus'), 20);
    }

    /**
     * Registrar menús de administración
     *
     * @since 1.0.0
     */
    public function register_admin_menus() {
        // El menú principal ya está registrado por el core
        // Aquí podemos agregar submenús adicionales si es necesario
    }

    /**
     * Cargar estilos y scripts
     *
     * @since 1.0.0
     */
    public function enqueue_assets() {
        // Solo cargar en la página de rendimiento
        if (!isset($_GET['page']) || $_GET['page'] !== 'wp-pos-performance') {
            return;
        }

        // Estilos
        wp_enqueue_style(
            'wp-pos-performance-admin',
            WP_POS_PLUGIN_URL . 'modules/performance/assets/css/performance-admin.css',
            array(),
            WP_POS_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'wp-pos-performance-admin',
            WP_POS_PLUGIN_URL . 'modules/performance/assets/js/performance-admin.js',
            array('jquery'),
            WP_POS_VERSION,
            true
        );
    }

    /**
     * Renderizar página de configuración
     *
     * @since 1.0.0
     */
    public function render_settings_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return;
        }

        // La página de rendimiento será renderizada por WP_POS_Performance_Loader
        // Este método está aquí para cumplir con la interfaz del módulo
    }
    
    /**
     * Renderizar el contenido principal del módulo
     *
     * @since 1.0.0
     */
    public function render_content() {
        // Verificar permisos
        if (!current_user_can($this->capability)) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'wp-pos'));
        }
        
        // Crear directorio de vistas si no existe
        $views_dir = dirname(__FILE__) . '/views';
        if (!file_exists($views_dir)) {
            wp_mkdir_p($views_dir);
        }
        
        // Ruta al archivo de vista
        $view_file = $views_dir . '/performance-page.php';
        
        // Crear vista por defecto si no existe
        if (!file_exists($view_file)) {
            file_put_contents($view_file, '<?php
// Vista del módulo de rendimiento
?>
<div class="wrap wp-pos-performance">
    <h1><?php echo esc_html($this->name); ?></h1>
    <div class="wp-pos-performance-content">
        <div class="card">
            <h2><?php _e("Herramientas de Rendimiento", "wp-pos"); ?></h2>
            <p><?php _e("Bienvenido al módulo de rendimiento de WP-POS. Aquí puedes optimizar y monitorear el rendimiento del sistema.", "wp-pos"); ?></p>
            
            <div class="performance-tools">
                <h3><?php _e("Herramientas disponibles", "wp-pos"); ?></h3>
                <ul>
                    <li><?php _e("Optimización de base de datos", "wp-pos"); ?></li>
                    <li><?php _e("Limpieza de caché", "wp-pos"); ?></li>
                    <li><?php _e("Monitoreo de rendimiento", "wp-pos"); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.wp-pos-performance .card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-top: 20px;
    padding: 20px;
}

.wp-pos-performance h2 {
    margin-top: 0;
}

.performance-tools {
    margin-top: 20px;
}

.performance-tools h3 {
    margin-bottom: 10px;
}

.performance-tools ul {
    list-style-type: disc;
    margin-left: 20px;
}
</style>');
        }
        
        // Incluir la vista
        include $view_file;
    }
    
    /**
     * Registrar el módulo en el sistema
     *
     * @since 1.0.0
     */
    public function register_module() {
        // Registrar el módulo en el sistema
        if (function_exists('wp_pos_register_module')) {
            wp_pos_register_module($this);
        }
        
        // Marcar el módulo como activo
        if (!defined('WP_POS_PERFORMANCE_MODULE_ACTIVE')) {
            define('WP_POS_PERFORMANCE_MODULE_ACTIVE', true);
        }
    }
}

/**
 * Registrar el módulo de rendimiento en el sistema
 *
 * @since 1.0.0
 * @return WP_POS_Performance_Module Instancia del módulo
 */
function wp_pos_register_performance_module() {
    // Verificar si la clase existe
    if (!class_exists('WP_POS_Performance_Module')) {
        return null;
    }
    
    // Obtener la instancia del módulo
    $instance = WP_POS_Performance_Module::get_instance();
    
    // Registrar el módulo en el sistema
    $instance->register_module();
    
    return $instance;
}

// Registrar el módulo cuando el sistema esté listo
add_action('wp_pos_register_modules', 'wp_pos_register_performance_module', 10);
