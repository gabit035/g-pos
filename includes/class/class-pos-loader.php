<?php
/**
 * Clase de carga de modulos para el plugin WP-POS
 *
 * Gestiona la carga de modulos y sus dependencias en el orden correcto.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Loader para WP-POS
 *
 * @since 1.0.0
 */
class WP_POS_Loader {

    /**
     * Instancia u00fanica de la clase
     *
     * @since 1.0.0
     * @access private
     * @var WP_POS_Loader
     */
    private static $instance = null;

    /**
     * modulos cargados
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $loaded_modules = array();

    /**
     * Registro de errores de carga
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $loading_errors = array();

    /**
     * Obtener instancia u00fanica de la clase
     *
     * @since 1.0.0
     * @return WP_POS_Loader
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
        // Inicializar el cargador
    }

    /**
     * Cargar todos los modulos activos
     *
     * @since 1.0.0
     * @return bool u00c9xito de la operaciu00f3n
     */
    public function load_modules() {
        // Obtener el Registry
        $registry = WP_POS_Registry::get_instance();
        
        // Obtener todos los modulos activos y requeridos
        $active_modules = $registry->get_modules('active');
        $required_modules = $registry->get_modules('required');
        
        // Combinar modulos
        $modules_to_load = array_merge($required_modules, $active_modules);
        
        // Si no hay modulos, salir
        if (empty($modules_to_load)) {
            return true;
        }
        
        // Crear grafo de dependencias
        $dependency_graph = $this->build_dependency_graph($modules_to_load);
        
        // Ordenar modulos por dependencias
        $loading_order = $this->resolve_dependencies($dependency_graph);
        
        // Si hay error en dependencias, retornar falso
        if (empty($loading_order)) {
            $this->loading_errors[] = __('Error al resolver dependencias de modulos.', 'wp-pos');
            return false;
        }
        
        // Cargar modulos en orden
        foreach ($loading_order as $module_id) {
            // Verificar si el modulo existe en la lista a cargar
            if (isset($modules_to_load[$module_id])) {
                $this->load_module($module_id, $modules_to_load[$module_id]);
            }
        }
        
        // Notificar que todos los modulos han sido cargados
        do_action('wp_pos_modules_loaded', $this->loaded_modules);
        
        return true;
    }

    /**
     * Cargar un modulo especu00edfico
     *
     * @since 1.0.0
     * @param string $module_id ID del modulo
     * @param array $module Datos del modulo
     * @return bool u00c9xito de la operaciu00f3n
     */
    public function load_module($module_id, $module) {
        // Verificar si ya estu00e1 cargado
        if (isset($this->loaded_modules[$module_id])) {
            return true;
        }
        
        // Verificar si tiene archivo principal
        if (empty($module['file'])) {
            // Intentar determinar archivo principal por convenciu00f3n
            $module_dir = WP_POS_PLUGIN_DIR . $module_id;
            $module_file = $module_dir . '/' . $module_id . '.php';
            
            if (file_exists($module_file)) {
                $module['file'] = $module_file;
            } else {
                $this->loading_errors[] = sprintf(
                    __('No se pudo cargar el modulo "%s": archivo principal no encontrado.', 'wp-pos'),
                    $module_id
                );
                return false;
            }
        }
        
        // Cargar archivo principal del modulo
        if (file_exists($module['file'])) {
            // Notificar antes de cargar
            do_action('wp_pos_before_load_module', $module_id, $module);
            
            // Incluir archivo
            include_once $module['file'];
            
            // Inicializar clase principal si estu00e1 definida
            if (!empty($module['main_class']) && class_exists($module['main_class'])) {
                $class_name = $module['main_class'];
                
                // Verificar si tiene mu00e9todo de instancia
                if (method_exists($class_name, 'get_instance')) {
                    $instance = call_user_func(array($class_name, 'get_instance'));
                    
                    // Registrar instancia en el Registry
                    WP_POS_Registry::get_instance()->register_component($module_id, $instance);
                } elseif (method_exists($class_name, 'instance')) {
                    $instance = call_user_func(array($class_name, 'instance'));
                    
                    // Registrar instancia en el Registry
                    WP_POS_Registry::get_instance()->register_component($module_id, $instance);
                } else {
                    // Crear instancia normalmente
                    $instance = new $class_name();
                    
                    // Registrar instancia en el Registry
                    WP_POS_Registry::get_instance()->register_component($module_id, $instance);
                }
            }
            
            // Marcar como cargado
            $this->loaded_modules[$module_id] = $module;
            
            // Notificar despuu00e9s de cargar
            do_action('wp_pos_after_load_module', $module_id, $module);
            
            return true;
        } else {
            $this->loading_errors[] = sprintf(
                __('No se pudo cargar el modulo "%s": archivo %s no encontrado.', 'wp-pos'),
                $module_id,
                $module['file']
            );
            return false;
        }
    }

    /**
     * Construir grafo de dependencias
     *
     * @since 1.0.0
     * @param array $modules Lista de modulos a procesar
     * @return array Grafo de dependencias
     */
    private function build_dependency_graph($modules) {
        $graph = array();
        
        foreach ($modules as $module_id => $module) {
            $graph[$module_id] = array();
            
            // Agregar dependencias
            if (!empty($module['depends']) && is_array($module['depends'])) {
                foreach ($module['depends'] as $dependency) {
                    // Verificar si la dependencia existe
                    if (isset($modules[$dependency])) {
                        $graph[$module_id][] = $dependency;
                    } else {
                        $this->loading_errors[] = sprintf(
                            __('modulo "%s" depende de "%s" que no estu00e1 disponible.', 'wp-pos'),
                            $module_id,
                            $dependency
                        );
                    }
                }
            }
        }
        
        return $graph;
    }

    /**
     * Resolver dependencias usando algoritmo de ordenamiento topolu00f3gico
     *
     * @since 1.0.0
     * @param array $graph Grafo de dependencias
     * @return array Orden de carga o array vacu00edo si hay ciclos
     */
    private function resolve_dependencies($graph) {
        $visited = array();
        $temp = array();
        $order = array();
        $modules = array_keys($graph);
        
        // Algoritmo de bu00fasqueda en profundidad para detectar ciclos
        $visit = function($node) use (&$visit, &$visited, &$temp, &$order, &$graph) {
            // Si ya fue visitado permanentemente, omitir
            if (isset($visited[$node])) {
                return true;
            }
            
            // Si estu00e1 en el array temporal, hay un ciclo
            if (isset($temp[$node])) {
                $this->loading_errors[] = sprintf(
                    __('Se detectu00f3 un ciclo de dependencias en el modulo "%s".', 'wp-pos'),
                    $node
                );
                return false;
            }
            
            // Marcar como visitado temporalmente
            $temp[$node] = true;
            
            // Visitar dependencias
            foreach ($graph[$node] as $dependency) {
                if (!$visit($dependency)) {
                    return false;
                }
            }
            
            // Quitar marca temporal
            unset($temp[$node]);
            
            // Marcar como visitado permanentemente
            $visited[$node] = true;
            
            // Agregar al orden de carga
            $order[] = $node;
            
            return true;
        };
        
        // Intentar visitar cada modulo
        foreach ($modules as $module) {
            if (!isset($visited[$module])) {
                if (!$visit($module)) {
                    // Si hay ciclo, retornar array vacu00edo
                    return array();
                }
            }
        }
        
        // Retornar orden inverso (primero dependencias, luego dependientes)
        return array_reverse($order);
    }

    /**
     * Obtener los errores de carga
     *
     * @since 1.0.0
     * @return array Lista de errores
     */
    public function get_loading_errors() {
        return $this->loading_errors;
    }

    /**
     * Obtener los modulos cargados
     *
     * @since 1.0.0
     * @return array Lista de modulos cargados
     */
    public function get_loaded_modules() {
        return $this->loaded_modules;
    }

    /**
     * Verificar si un modulo estu00e1 cargado
     *
     * @since 1.0.0
     * @param string $module_id ID del modulo
     * @return bool True si estu00e1 cargado, False si no
     */
    public function is_module_loaded($module_id) {
        return isset($this->loaded_modules[$module_id]);
    }
}
