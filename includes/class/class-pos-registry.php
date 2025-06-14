<?php
/**
 * Clase de registro central para el plugin WP-POS
 *
 * Implementa el patrón Registry para gestionar todos los módulos
 * y componentes del sistema.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Registry para WP-POS
 *
 * @since 1.0.0
 */
class WP_POS_Registry {

    /**
     * Instancia única de la clase
     *
     * @since 1.0.0
     * @access private
     * @var WP_POS_Registry
     */
    private static $instance = null;

    /**
     * Módulos registrados
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $modules = array();

    /**
     * Componentes registrados
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $components = array();

    /**
     * Obtener instancia única de la clase
     *
     * @since 1.0.0
     * @return WP_POS_Registry
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
        // Inicializar el registro
        $this->init();
    }

    /**
     * Inicializar el registro
     *
     * @since 1.0.0
     */
    private function init() {
        // Hook para inicializar el registro
        add_action('wp_pos_loaded', array($this, 'load_from_cache'));
        
        // Hook para guardar en caché al desactivar
        add_action('wp_pos_deactivated', array($this, 'save_to_cache'));
    }

    /**
     * Registrar un módulo
     *
     * @since 1.0.0
     * @param string $module_id ID único del módulo
     * @param array $args Argumentos del módulo
     * @return bool True si se registró correctamente, False si ya existía
     */
    public function register_module($module_id, $args = array()) {
        // Verificar si ya existe
        if (isset($this->modules[$module_id])) {
            return false;
        }

        // Argumentos por defecto
        $defaults = array(
            'name'        => '',
            'description' => '',
            'version'     => '1.0.0',
            'author'      => '',
            'url'         => '',
            'main_class'  => '',
            'file'        => '',
            'depends'     => array(),
            'status'      => 'active' // active, inactive, required
        );

        // Fusionar argumentos
        $module = wp_parse_args($args, $defaults);
        $module['id'] = $module_id;

        // Añadir al registro
        $this->modules[$module_id] = $module;

        // Disparar acción
        do_action('wp_pos_module_registered', $module_id, $module);

        return true;
    }

    /**
     * Desregistrar un módulo
     *
     * @since 1.0.0
     * @param string $module_id ID del módulo a desregistrar
     * @return bool True si se desregistró correctamente, False si no existía
     */
    public function unregister_module($module_id) {
        // Verificar si existe
        if (!isset($this->modules[$module_id])) {
            return false;
        }

        // No se pueden desregistrar módulos requeridos
        if ($this->modules[$module_id]['status'] === 'required') {
            return false;
        }

        // Guardar una copia para el hook
        $module = $this->modules[$module_id];

        // Eliminar del registro
        unset($this->modules[$module_id]);

        // Disparar acción
        do_action('wp_pos_module_unregistered', $module_id, $module);

        return true;
    }

    /**
     * Obtener un módulo específico
     *
     * @since 1.0.0
     * @param string $module_id ID del módulo
     * @return array|null Datos del módulo o null si no existe
     */
    public function get_module($module_id) {
        if (isset($this->modules[$module_id])) {
            return $this->modules[$module_id];
        }
        return null;
    }

    /**
     * Obtener todos los módulos registrados
     *
     * @since 1.0.0
     * @param string $status Filtrar por estado (active, inactive, required, all)
     * @return array Lista de módulos
     */
    public function get_modules($status = 'all') {
        if ($status === 'all') {
            return $this->modules;
        }

        $filtered = array();
        foreach ($this->modules as $id => $module) {
            if ($module['status'] === $status) {
                $filtered[$id] = $module;
            }
        }

        return $filtered;
    }

    /**
     * Actualizar el estado de un módulo
     *
     * @since 1.0.0
     * @param string $module_id ID del módulo
     * @param string $status Nuevo estado (active, inactive)
     * @return bool True si se actualizó correctamente, False si hubo error
     */
    public function update_module_status($module_id, $status) {
        // Verificar si existe
        if (!isset($this->modules[$module_id])) {
            return false;
        }

        // No se puede cambiar el estado de módulos requeridos
        if ($this->modules[$module_id]['status'] === 'required') {
            return false;
        }

        // Validar estado
        $valid_statuses = array('active', 'inactive');
        if (!in_array($status, $valid_statuses)) {
            return false;
        }

        // Guardar estado anterior
        $old_status = $this->modules[$module_id]['status'];

        // Actualizar estado
        $this->modules[$module_id]['status'] = $status;

        // Disparar acción
        do_action('wp_pos_module_status_changed', $module_id, $status, $old_status);

        return true;
    }

    /**
     * Registrar un componente
     *
     * @since 1.0.0
     * @param string $component_id ID único del componente
     * @param mixed $instance Instancia del componente
     * @return bool True si se registró correctamente, False si ya existía
     */
    public function register_component($component_id, $instance) {
        // Verificar si ya existe
        if (isset($this->components[$component_id])) {
            return false;
        }

        // Añadir al registro
        $this->components[$component_id] = $instance;

        // Disparar acción
        do_action('wp_pos_component_registered', $component_id, $instance);

        return true;
    }

    /**
     * Obtener un componente específico
     *
     * @since 1.0.0
     * @param string $component_id ID del componente
     * @return mixed|null Instancia del componente o null si no existe
     */
    public function get_component($component_id) {
        if (isset($this->components[$component_id])) {
            return $this->components[$component_id];
        }
        return null;
    }

    /**
     * Cargar registro desde caché
     *
     * @since 1.0.0
     */
    public function load_from_cache() {
        // Cargar desde la opción de WordPress
        $cached = get_option('wp_pos_registry_cache', array());
        
        if (!empty($cached) && is_array($cached)) {
            if (isset($cached['modules']) && is_array($cached['modules'])) {
                // Fusionar con los módulos actuales (prioridad a los ya registrados)
                $this->modules = array_merge($cached['modules'], $this->modules);
            }
        }
    }

    /**
     * Guardar registro en caché
     *
     * @since 1.0.0
     */
    public function save_to_cache() {
        // Preparar datos para caché
        $cache = array(
            'modules' => $this->modules,
            'timestamp' => time()
        );
        
        // Guardar en la opción de WordPress
        update_option('wp_pos_registry_cache', $cache);
    }
}
