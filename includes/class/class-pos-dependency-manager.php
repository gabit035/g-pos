<?php
/**
 * Gestor de dependencias para el plugin WP-POS
 *
 * Administra y valida las dependencias entre módulos y con componentes externos.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Dependency Manager para WP-POS
 *
 * @since 1.0.0
 */
class WP_POS_Dependency_Manager {

    /**
     * Instancia única de la clase
     *
     * @since 1.0.0
     * @access private
     * @var WP_POS_Dependency_Manager
     */
    private static $instance = null;

    /**
     * Dependencias requeridas del sistema
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $system_requirements = array();

    /**
     * Errores de dependencias
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $dependency_errors = array();

    /**
     * Obtener instancia única de la clase
     *
     * @since 1.0.0
     * @return WP_POS_Dependency_Manager
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor de la clase
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Definir requisitos del sistema
        $this->define_system_requirements();
        
        // Validar requisitos del sistema
        $this->validate_system_requirements();
    }

    /**
     * Definir requisitos del sistema
     *
     * @since 1.0.0
     */
    private function define_system_requirements() {
        $this->system_requirements = array(
            'php' => array(
                'version' => '7.0',
                'extensions' => array(
                    'mysqli',
                    'json',
                    'mbstring',
                    'curl'
                )
            ),
            'wordpress' => array(
                'version' => '5.0'
            ),
            'plugins' => array(
                'woocommerce' => array(
                    'name' => 'WooCommerce',
                    'file' => 'woocommerce/woocommerce.php',
                    'version' => '3.0'
                )
            )
        );
        
        // Permitir modificar requisitos
        $this->system_requirements = apply_filters('wp_pos_system_requirements', $this->system_requirements);
    }

    /**
     * Validar requisitos del sistema
     *
     * @since 1.0.0
     * @return bool Resultado de la validación
     */
    public function validate_system_requirements() {
        $valid = true;
        
        // Validar versión de PHP
        if (isset($this->system_requirements['php']['version'])) {
            $required_php = $this->system_requirements['php']['version'];
            $current_php = phpversion();
            
            if (version_compare($current_php, $required_php, '<')) {
                $this->dependency_errors[] = sprintf(
                    __('WP-POS requiere PHP versión %s o superior. Tu servidor utiliza %s.', 'wp-pos'),
                    $required_php,
                    $current_php
                );
                $valid = false;
            }
        }
        
        // Validar extensiones de PHP
        if (isset($this->system_requirements['php']['extensions']) && is_array($this->system_requirements['php']['extensions'])) {
            foreach ($this->system_requirements['php']['extensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    $this->dependency_errors[] = sprintf(
                        __('WP-POS requiere la extensión de PHP "%s".', 'wp-pos'),
                        $extension
                    );
                    $valid = false;
                }
            }
        }
        
        // Validar versión de WordPress
        if (isset($this->system_requirements['wordpress']['version'])) {
            $required_wp = $this->system_requirements['wordpress']['version'];
            global $wp_version;
            
            if (version_compare($wp_version, $required_wp, '<')) {
                $this->dependency_errors[] = sprintf(
                    __('WP-POS requiere WordPress versión %s o superior. Tu sitio utiliza %s.', 'wp-pos'),
                    $required_wp,
                    $wp_version
                );
                $valid = false;
            }
        }
        
        // Validar plugins requeridos
        if (isset($this->system_requirements['plugins']) && is_array($this->system_requirements['plugins'])) {
            foreach ($this->system_requirements['plugins'] as $plugin_slug => $plugin_data) {
                if (!$this->is_plugin_active($plugin_data['file'])) {
                    $this->dependency_errors[] = sprintf(
                        __('WP-POS requiere el plugin %s.', 'wp-pos'),
                        $plugin_data['name']
                    );
                    $valid = false;
                } else if (isset($plugin_data['version'])) {
                    $plugin_version = $this->get_plugin_version($plugin_data['file']);
                    if (!empty($plugin_version) && version_compare($plugin_version, $plugin_data['version'], '<')) {
                        $this->dependency_errors[] = sprintf(
                            __('WP-POS requiere %s versión %s o superior. Tu sitio utiliza %s.', 'wp-pos'),
                            $plugin_data['name'],
                            $plugin_data['version'],
                            $plugin_version
                        );
                        $valid = false;
                    }
                }
            }
        }
        
        return $valid;
    }

    /**
     * Verificar si un plugin está activo
     *
     * @since 1.0.0
     * @param string $plugin_file Ruta relativa al archivo principal del plugin
     * @return bool True si está activo, False si no
     */
    public function is_plugin_active($plugin_file) {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        return is_plugin_active($plugin_file);
    }

    /**
     * Obtener la versión de un plugin
     *
     * @since 1.0.0
     * @param string $plugin_file Ruta relativa al archivo principal del plugin
     * @return string Versión del plugin o cadena vacía si no se encuentra
     */
    public function get_plugin_version($plugin_file) {
        if (!function_exists('get_plugins')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
        
        return isset($plugin_data['Version']) ? $plugin_data['Version'] : '';
    }

    /**
     * Verificar dependencias para un módulo específico
     *
     * @since 1.0.0
     * @param string $module_id ID del módulo
     * @param array $dependencies Lista de dependencias
     * @return bool True si todas las dependencias están satisfechas, False si no
     */
    public function validate_module_dependencies($module_id, $dependencies) {
        if (empty($dependencies) || !is_array($dependencies)) {
            return true;
        }
        
        $valid = true;
        $registry = WP_POS_Registry::get_instance();
        $loader = WP_POS_Loader::get_instance();
        
        foreach ($dependencies as $dependency) {
            // Verificar si el módulo existe
            $module = $registry->get_module($dependency);
            
            if (!$module) {
                $this->dependency_errors[] = sprintf(
                    __('El módulo "%s" depende de "%s" que no está registrado.', 'wp-pos'),
                    $module_id,
                    $dependency
                );
                $valid = false;
                continue;
            }
            
            // Verificar si el módulo está cargado
            if (!$loader->is_module_loaded($dependency)) {
                $this->dependency_errors[] = sprintf(
                    __('El módulo "%s" depende de "%s" que no está cargado.', 'wp-pos'),
                    $module_id,
                    $dependency
                );
                $valid = false;
            }
        }
        
        return $valid;
    }

    /**
     * Obtener errores de dependencias
     *
     * @since 1.0.0
     * @return array Lista de errores
     */
    public function get_dependency_errors() {
        return $this->dependency_errors;
    }

    /**
     * Verificar si hay errores de dependencias
     *
     * @since 1.0.0
     * @return bool True si hay errores, False si no hay
     */
    public function has_dependency_errors() {
        return !empty($this->dependency_errors);
    }

    /**
     * Mostrar notificaciones de dependencias
     *
     * @since 1.0.0
     */
    public function display_dependency_notices() {
        if (!$this->has_dependency_errors()) {
            return;
        }
        
        $message = '<p>' . __('WP-POS ha detectado problemas con las dependencias requeridas:', 'wp-pos') . '</p>';
        $message .= '<ul>';
        
        foreach ($this->dependency_errors as $error) {
            $message .= '<li>' . $error . '</li>';
        }
        
        $message .= '</ul>';
        $message .= '<p>' . __('Por favor, resuelve estos problemas para utilizar todas las funcionalidades del plugin.', 'wp-pos') . '</p>';
        
        echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
    }
}
