<?php
/**
 * Registro de mu00f3dulos para el sistema G-POS
 *
 * Esta clase gestiona el descubrimiento, registro y acceso a todos los mu00f3dulos
 * del sistema de forma centralizada, siguiendo el patru00f3n Singleton.
 *
 * @package WP-POS
 * @subpackage Core
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;

// Cargar dependencias
if (!class_exists('WP_POS_Module_Abstract')) {
    require_once WP_POS_INCLUDES_DIR . 'abstract-class-module.php';
}

class WP_POS_Module_Registry {
    /**
     * Instancia u00fanica de la clase (patru00f3n Singleton)
     *
     * @var WP_POS_Module_Registry
     */
    private static $instance = null;
    
    /**
     * Array de mu00f3dulos registrados
     *
     * @var array
     */
    private $modules = array();
    
    /**
     * Mu00f3dulos ordenados por posiciu00f3n en el menu00fa
     *
     * @var array
     */
    private $sorted_modules = array();
    
    /**
     * Flag para verificar si los menu00fas ya se han registrado
     *
     * @var bool
     */
    private $menus_registered = false;
    
    /**
     * Constructor privado (patru00f3n Singleton)
     */
    private function __construct() {
        // Registrar hooks para el sistema de menu00fas
        add_action('admin_menu', array($this, 'register_module_menus'), 20);
        
        // Filtro para permitir a los mu00f3dulos registrarse a su00ed mismos
        add_action('wp_pos_modules_loaded', array($this, 'autoload_modules'));
        
        // Hook para cargar assets cuando se visita la pu00e1gina de un mu00f3dulo
        add_action('admin_enqueue_scripts', array($this, 'maybe_load_module_assets'));
    }
    
    /**
     * Obtener la instancia u00fanica (patru00f3n Singleton)
     *
     * @return WP_POS_Module_Registry
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Registrar un mu00f3dulo en el sistema
     *
     * @param WP_POS_Module_Interface $module Instancia del mu00f3dulo
     * @return bool True si se registru00f3 correctamente, false en caso contrario
     */
    public function register_module($module) {
        // Verificar que sea una instancia vu00e1lida
        if (!$module instanceof WP_POS_Module_Interface) {
            error_log('G-POS: Intento de registro de mu00f3dulo invu00e1lido');
            return false;
        }
        
        $module_id = $module->get_id();
        
        // Verificar que el ID sea vu00e1lido y u00fanico
        if (empty($module_id) || isset($this->modules[$module_id])) {
            error_log('G-POS: ID de mu00f3dulo invu00e1lido o duplicado: ' . $module_id);
            return false;
        }
        
        // Registrar el mu00f3dulo
        $this->modules[$module_id] = $module;
        
        // Permitir que el mu00f3dulo se inicialice
        $module->initialize();
        
        // Ordenar los mu00f3dulos por posiciu00f3n
        $this->sort_modules();
        
        return true;
    }
    
    /**
     * Autodetectar y cargar los mu00f3dulos disponibles
     */
    public function autoload_modules() {
        $modules_dir = WP_POS_MODULES_DIR;
        
        // Verificar que el directorio exista
        if (!is_dir($modules_dir)) {
            error_log('[WP-POS] error: El directorio de mu00f3dulos no existe: ' . $modules_dir);
            return;
        }
        
        // Lista de mu00f3dulos que sabemos que existen
        $active_modules = array('closures'); // Actualmente solo tenemos el mu00f3dulo de cierres implementado
        
        foreach ($active_modules as $module_id) {
            $module_folder = $modules_dir . $module_id;
            
            // Verificar que la carpeta del mu00f3dulo exista
            if (!is_dir($module_folder)) {
                continue; // Saltamos si no existe la carpeta
            }
            
            // Intentamos con el formato class-{id}-module.php
            $main_file = $module_folder . '/class-' . $module_id . '-module.php';
            
            // Si no existe, intentamos con class-closures-module.php (para compatibilidad)
            if (!file_exists($main_file)) {
                $main_file = $module_folder . '/class-closures-module.php';
            }
            
            // Verificar si existe el archivo principal del mu00f3dulo
            if (file_exists($main_file)) {
                try {
                    // Cargar el archivo principal
                    require_once $main_file;
                    
                    // Construir el nombre de la clase esperada
                    $class_name = 'WP_POS_' . str_replace('-', '_', ucwords($module_id, '-')) . '_Module';
                    
                    // Verificar si la clase existe
                    if (class_exists($class_name)) {
                        try {
                            // Instanciar el mu00f3dulo
                            $module = new $class_name();
                            
                            // Establecer directorios y URLs
                            if (method_exists($module, 'set_module_dir')) {
                                $module->set_module_dir($module_folder . '/');
                            }
                            
                            if (method_exists($module, 'set_module_url')) {
                                $module->set_module_url(WP_POS_PLUGIN_URL . 'modules/' . $module_id . '/');
                            }
                            
                            // Registrar el mu00f3dulo
                            $this->register_module($module);
                            
                            // Registrar que el mu00f3dulo fue cargado exitosamente
                            error_log('[WP-POS] Mu00f3dulo de ' . $module_id . ' inicializado');
                        } catch (Exception $e) {
                            error_log('[WP-POS] error: Error al instanciar el mu00f3dulo ' . $class_name . ': ' . $e->getMessage());
                        }
                    } else {
                        error_log('[WP-POS] error: La clase de mu00f3dulo ' . $class_name . ' no existe en ' . $main_file);
                    }
                } catch (Error $e) {
                    error_log('[WP-POS] error: Error al cargar el mu00f3dulo ' . $module_id . ': ' . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Obtener un mu00f3dulo por su ID
     *
     * @param string $module_id ID del mu00f3dulo
     * @return WP_POS_Module_Interface|null Instancia del mu00f3dulo o null si no existe
     */
    public function get_module($module_id) {
        return isset($this->modules[$module_id]) ? $this->modules[$module_id] : null;
    }
    
    /**
     * Obtener todos los mu00f3dulos registrados
     *
     * @param bool $active_only True para obtener solo los mu00f3dulos activos
     * @return array Array de mu00f3dulos
     */
    public function get_modules($active_only = true) {
        if ($active_only) {
            return array_filter($this->modules, function($module) {
                return $module->is_active();
            });
        }
        
        return $this->modules;
    }
    
    /**
     * Registrar los menu00fas de todos los mu00f3dulos
     */
    public function register_module_menus() {
        // Evitar registrar los menu00fas mu00faltiples veces
        if ($this->menus_registered) {
            return;
        }
        
        // Registrar los menu00fas de los mu00f3dulos activos
        foreach ($this->sorted_modules as $module) {
            if ($module->is_active() && $module->show_in_menu()) {
                $this->register_module_menu($module);
            }
        }
        
        $this->menus_registered = true;
    }
    
    /**
     * Registrar el menu00fa de un mu00f3dulo especu00edfico
     *
     * @param WP_POS_Module_Interface $module Instancia del mu00f3dulo
     */
    private function register_module_menu($module) {
        // Obtener los datos del mu00f3dulo
        $id = $module->get_id();
        $name = $module->get_name();
        $capability = $module->get_capability();
        
        // Crear el slug del menu00fa
        $menu_slug = 'wp-pos-' . $id;
        
        // Registrar el menu00fa
        add_submenu_page(
            'wp-pos',           // Padre (menu00fa principal)
            $name,              // Tu00edtulo de la pu00e1gina
            $name,              // Tu00edtulo del menu00fa
            $capability,        // Capacidad requerida
            $menu_slug,         // Slug del menu00fa
            function() use ($module) {  // Callback
                // Verificar permisos nuevamente
                if (!current_user_can($module->get_capability())) {
                    wp_die(__('No tienes permisos suficientes para acceder a esta pu00e1gina.', 'wp-pos'));
                }
                
                // Contenedor
                echo '<div class="wrap wp-pos-module-' . esc_attr($module->get_id()) . '-page">';
                
                // Tu00edtulo
                echo '<h1>' . esc_html($module->get_name()) . '</h1>';
                
                // Renderizar el contenido del mu00f3dulo
                $module->render_content();
                
                echo '</div>';
            }
        );
    }
    
    /**
     * Cargar los assets de un mu00f3dulo si estamos en su pu00e1gina
     */
    public function maybe_load_module_assets() {
        foreach ($this->modules as $module) {
            if ($module->is_active() && $module->is_module_page()) {
                // Registrar assets generales
                $module->register_assets();
                
                // Cargar assets especu00edficos para la pu00e1gina
                $module->enqueue_assets();
                
                // Solo procesamos un mu00f3dulo (el activo)
                break;
            }
        }
    }
    
    /**
     * Ordenar los mu00f3dulos por posiciu00f3n
     */
    private function sort_modules() {
        $this->sorted_modules = $this->modules;
        
        uasort($this->sorted_modules, function($a, $b) {
            $pos_a = $a->get_position();
            $pos_b = $b->get_position();
            
            // Si ambos tienen posiciu00f3n null, ordenar alfafu00e9ticamente
            if (null === $pos_a && null === $pos_b) {
                return strcmp($a->get_name(), $b->get_name());
            }
            
            // Si solo uno tiene posiciu00f3n null, ponerlo al final
            if (null === $pos_a) {
                return 1;
            }
            
            if (null === $pos_b) {
                return -1;
            }
            
            // Ordenar por posiciu00f3n
            return $pos_a - $pos_b;
        });
    }
}
