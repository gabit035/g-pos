<?php
/**
 * Clase abstracta base para mu00f3dulos del sistema G-POS
 *
 * Implementa la funcionalidad comu00fan que todos los mu00f3dulos pueden heredar
 * para reducir la duplicaciu00f3n de cu00f3digo y proporcionar una estructura consistente.
 *
 * @package WP-POS
 * @subpackage Core
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;

// Cargar la interfaz si no se ha cargado
if (!interface_exists('WP_POS_Module_Interface')) {
    require_once WP_POS_INCLUDES_DIR . 'interfaces/interface-module.php';
}

abstract class WP_POS_Module_Abstract implements WP_POS_Module_Interface {
    /**
     * ID u00fanico del mu00f3dulo
     *
     * @var string
     */
    protected $id = '';
    
    /**
     * Nombre para mostrar del mu00f3dulo
     *
     * @var string
     */
    protected $name = '';
    
    /**
     * Capacidad (permiso) requerida para acceder al mu00f3dulo
     *
     * @var string
     */
    protected $capability = 'view_pos';
    
    /**
     * Posiciu00f3n del mu00f3dulo en el menu00fa
     *
     * @var int|null
     */
    protected $position = null;
    
    /**
     * Icono del mu00f3dulo
     *
     * @var string|null
     */
    protected $icon = null;
    
    /**
     * Estado activo del mu00f3dulo
     *
     * @var bool
     */
    protected $active = true;
    
    /**
     * Mostrar en menu00fa
     *
     * @var bool
     */
    protected $show_in_menu = true;
    
    /**
     * Directorio del mu00f3dulo
     *
     * @var string
     */
    protected $module_dir = '';
    
    /**
     * URL del mu00f3dulo
     *
     * @var string
     */
    protected $module_url = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Los mu00f3dulos hijos deben establecer sus propiedades en el constructor
    }
    
    /**
     * {@inheritDoc}
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * {@inheritDoc}
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * {@inheritDoc}
     */
    public function get_capability() {
        return $this->capability;
    }
    
    /**
     * {@inheritDoc}
     */
    public function get_position() {
        return $this->position;
    }
    
    /**
     * {@inheritDoc}
     */
    public function get_icon() {
        return $this->icon;
    }
    
    /**
     * {@inheritDoc}
     */
    public function is_active() {
        return $this->active;
    }
    
    /**
     * {@inheritDoc}
     */
    public function show_in_menu() {
        return $this->show_in_menu;
    }
    
    /**
     * {@inheritDoc}
     */
    public function initialize() {
        // Implementaciu00f3n por defecto vacu00eda - los mu00f3dulos hijos pueden sobrescribir
        // Se llama cuando el mu00f3dulo se registra
    }
    
    /**
     * {@inheritDoc}
     */
    public function register_assets() {
        // Implementaciu00f3n por defecto vacu00eda - los mu00f3dulos hijos pueden sobrescribir
    }
    
    /**
     * Cargar assets (CSS, JS) del mu00f3dulo cuando se visita su pu00e1gina
     * 
     * Esta funciu00f3n se llama automu00e1ticamente por el sistema cuando
     * se visita la pu00e1gina del mu00f3dulo.
     *
     * @return void
     */
    public function enqueue_assets() {
        // Los mu00f3dulos hijos deben sobrescribir esta funciu00f3n si necesitan cargar assets
    }
    
    /**
     * Establecer el directorio del mu00f3dulo
     *
     * @param string $dir Directorio del mu00f3dulo
     * @return void
     */
    public function set_module_dir($dir) {
        $this->module_dir = trailingslashit($dir);
    }
    
    /**
     * Establecer la URL del mu00f3dulo
     *
     * @param string $url URL del mu00f3dulo
     * @return void
     */
    public function set_module_url($url) {
        $this->module_url = trailingslashit($url);
    }
    
    /**
     * Obtener el directorio del mu00f3dulo
     *
     * @return string Directorio del mu00f3dulo
     */
    public function get_module_dir() {
        return $this->module_dir;
    }
    
    /**
     * Obtener la URL del mu00f3dulo
     *
     * @return string URL del mu00f3dulo
     */
    public function get_module_url() {
        return $this->module_url;
    }
    
    /**
     * Obtener la URL de assets del mu00f3dulo
     *
     * @return string URL de assets del mu00f3dulo
     */
    public function get_assets_url() {
        return $this->get_module_url() . 'assets/';
    }
    
    /**
     * Obtener el directorio de assets del mu00f3dulo
     *
     * @return string Directorio de assets del mu00f3dulo
     */
    public function get_assets_dir() {
        return $this->get_module_dir() . 'assets/';
    }
    
    /**
     * Verificar si estamos en la pu00e1gina de este mu00f3dulo
     *
     * @return bool True si estamos en la pu00e1gina de este mu00f3dulo, false en caso contrario
     */
    public function is_module_page() {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return false;
        }
        
        $screen = get_current_screen();
        return $screen && isset($screen->id) && strpos($screen->id, 'wp-pos-' . $this->get_id()) !== false;
    }
}
