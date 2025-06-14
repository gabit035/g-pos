<?php
/**
 * Gestor de cachu00e9 para el plugin WP-POS
 *
 * Implementa un sistema de cachu00e9 multinivel para optimizar el rendimiento.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Cache Manager para WP-POS
 *
 * @since 1.0.0
 */
class WP_POS_Cache_Manager {

    /**
     * Instancia u00fanica de la clase
     *
     * @since 1.0.0
     * @access private
     * @var WP_POS_Cache_Manager
     */
    private static $instance = null;

    /**
     * Datos en cachu00e9 en memoria
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $memory_cache = array();

    /**
     * Grupos de cachu00e9 registrados
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $cache_groups = array();

    /**
     * Obtener instancia u00fanica de la clase
     *
     * @since 1.0.0
     * @return WP_POS_Cache_Manager
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
        // Registrar grupos de cachu00e9 predeterminados
        $this->register_default_groups();
        
        // Configurar hooks para limpieza de cachu00e9
        $this->setup_hooks();
    }

    /**
     * Registrar grupos de cachu00e9 predeterminados
     *
     * @since 1.0.0
     */
    private function register_default_groups() {
        // Grupo para configuraciones
        $this->register_cache_group('settings', array(
            'expiration' => 3600, // 1 hora
            'persistent' => true
        ));
        
        // Grupo para datos de ventas
        $this->register_cache_group('sales', array(
            'expiration' => 300, // 5 minutos
            'persistent' => true
        ));
        
        // Grupo para datos de productos
        $this->register_cache_group('products', array(
            'expiration' => 1800, // 30 minutos
            'persistent' => true
        ));
        
        // Grupo para datos de clientes
        $this->register_cache_group('customers', array(
            'expiration' => 1800, // 30 minutos
            'persistent' => true
        ));
        
        // Grupo para datos de UI
        $this->register_cache_group('ui', array(
            'expiration' => 3600, // 1 hora
            'persistent' => false // Solo en memoria
        ));
        
        // Grupo para consultas transitorias
        $this->register_cache_group('queries', array(
            'expiration' => 60, // 1 minuto
            'persistent' => true
        ));
    }

    /**
     * Configurar hooks para limpieza de cachu00e9
     *
     * @since 1.0.0
     */
    private function setup_hooks() {
        // Limpiar cachu00e9 de productos cuando cambian productos de WooCommerce
        add_action('woocommerce_update_product', array($this, 'clear_product_cache'));
        add_action('woocommerce_delete_product', array($this, 'clear_product_cache'));
        
        // Limpiar cachu00e9 de clientes cuando cambian usuarios
        add_action('user_register', array($this, 'clear_customer_cache'));
        add_action('profile_update', array($this, 'clear_customer_cache'));
        add_action('delete_user', array($this, 'clear_customer_cache'));
        
        // Limpiar cachu00e9 de configuraciones cuando se actualizan opciones
        add_action('update_option_wp_pos_options', array($this, 'clear_settings_cache'));
        
        // Limpiar cachu00e9 al desactivar plugin
        add_action('wp_pos_deactivated', array($this, 'clear_all_cache'));
    }

    /**
     * Registrar un nuevo grupo de cachu00e9
     *
     * @since 1.0.0
     * @param string $group_name Nombre del grupo
     * @param array $settings Configuraciu00f3n del grupo
     * @return bool True si se registru00f3 correctamente, False si ya existu00eda
     */
    public function register_cache_group($group_name, $settings = array()) {
        // Verificar si ya existe
        if (isset($this->cache_groups[$group_name])) {
            return false;
        }

        // Configuraciu00f3n por defecto
        $defaults = array(
            'expiration' => 3600, // 1 hora por defecto
            'persistent' => true // Guardar en transients por defecto
        );

        // Fusionar configuraciu00f3n
        $config = wp_parse_args($settings, $defaults);

        // Registrar grupo
        $this->cache_groups[$group_name] = $config;

        return true;
    }

    /**
     * Establecer un valor en cachu00e9
     *
     * @since 1.0.0
     * @param string $key Clave de cachu00e9
     * @param mixed $value Valor a cachear
     * @param string $group Grupo de cachu00e9 (default: 'default')
     * @param int $expiration Tiempo de expiraciu00f3n personalizado en segundos
     * @return bool True si se guardu00f3 correctamente
     */
    public function set($key, $value, $group = 'default', $expiration = null) {
        // Verificar grupo
        if (!isset($this->cache_groups[$group])) {
            // Si el grupo no existe, usar configuraciu00f3n por defecto
            $this->register_cache_group($group);
        }

        // Preparar clave u00fanica
        $cache_key = $this->get_cache_key($key, $group);

        // Guardar en cachu00e9 de memoria
        $this->memory_cache[$group][$key] = array(
            'value' => $value,
            'expires' => time() + ($expiration !== null ? $expiration : $this->cache_groups[$group]['expiration'])
        );

        // Si es persistente, guardar en transients
        if ($this->cache_groups[$group]['persistent']) {
            $transient_key = 'wp_pos_' . $group . '_' . $cache_key;
            return set_transient(
                $transient_key,
                $value,
                $expiration !== null ? $expiration : $this->cache_groups[$group]['expiration']
            );
        }

        return true;
    }

    /**
     * Obtener un valor de cachu00e9
     *
     * @since 1.0.0
     * @param string $key Clave de cachu00e9
     * @param string $group Grupo de cachu00e9 (default: 'default')
     * @param mixed $default Valor por defecto si no se encuentra en cachu00e9
     * @return mixed Valor en cachu00e9 o valor por defecto
     */
    public function get($key, $group = 'default', $default = null) {
        // Verificar en cachu00e9 de memoria primero
        if (isset($this->memory_cache[$group][$key])) {
            $data = $this->memory_cache[$group][$key];
            
            // Verificar si ha expirado
            if ($data['expires'] > time()) {
                return $data['value'];
            } else {
                // Si expiru00f3, eliminar de memoria
                unset($this->memory_cache[$group][$key]);
            }
        }

        // Si el grupo existe y es persistente, buscar en transients
        if (isset($this->cache_groups[$group]) && $this->cache_groups[$group]['persistent']) {
            $cache_key = $this->get_cache_key($key, $group);
            $transient_key = 'wp_pos_' . $group . '_' . $cache_key;
            $value = get_transient($transient_key);
            
            if ($value !== false) {
                // Guardar en cachu00e9 de memoria para futuras solicitudes
                $this->memory_cache[$group][$key] = array(
                    'value' => $value,
                    'expires' => time() + $this->cache_groups[$group]['expiration']
                );
                
                return $value;
            }
        }

        return $default;
    }

    /**
     * Eliminar un valor de cachu00e9
     *
     * @since 1.0.0
     * @param string $key Clave de cachu00e9
     * @param string $group Grupo de cachu00e9 (default: 'default')
     * @return bool True si se eliminu00f3 correctamente o no existu00eda
     */
    public function delete($key, $group = 'default') {
        // Eliminar de cachu00e9 de memoria
        if (isset($this->memory_cache[$group][$key])) {
            unset($this->memory_cache[$group][$key]);
        }

        // Si el grupo es persistente, eliminar transient
        if (isset($this->cache_groups[$group]) && $this->cache_groups[$group]['persistent']) {
            $cache_key = $this->get_cache_key($key, $group);
            $transient_key = 'wp_pos_' . $group . '_' . $cache_key;
            return delete_transient($transient_key);
        }

        return true;
    }

    /**
     * Limpiar todo un grupo de cachu00e9
     *
     * @since 1.0.0
     * @param string $group Grupo de cachu00e9 a limpiar
     * @return bool True si se limpiu00f3 correctamente
     */
    public function clear_group($group) {
        // Limpiar de memoria
        if (isset($this->memory_cache[$group])) {
            unset($this->memory_cache[$group]);
        }

        // Si es persistente, necesitamos limpiar transients
        // Esto es mu00e1s difu00edcil ya que WP no tiene una funciu00f3n nativa para esto
        if (isset($this->cache_groups[$group]) && $this->cache_groups[$group]['persistent']) {
            global $wpdb;
            
            // Buscar todos los transients con el prefijo de este grupo
            // Nota: Esto funciona solo en WordPress 4.4+
            $prefix = $wpdb->esc_like('_transient_wp_pos_' . $group . '_');
            $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE '%s'";
            $wpdb->query($wpdb->prepare($sql, $prefix . '%'));
            
            // Tambiu00e9n eliminar los transients expirados (timeout)
            $timeout_prefix = $wpdb->esc_like('_transient_timeout_wp_pos_' . $group . '_');
            $wpdb->query($wpdb->prepare($sql, $timeout_prefix . '%'));
        }

        return true;
    }

    /**
     * Limpiar toda la cachu00e9
     *
     * @since 1.0.0
     * @return bool True si se limpiu00f3 correctamente
     */
    public function clear_all_cache() {
        // Limpiar memoria
        $this->memory_cache = array();

        // Limpiar cada grupo persistente
        foreach ($this->cache_groups as $group => $config) {
            if ($config['persistent']) {
                $this->clear_group($group);
            }
        }

        return true;
    }

    /**
     * Limpiar cachu00e9 de productos
     *
     * @since 1.0.0
     * @param int $product_id ID de producto
     */
    public function clear_product_cache($product_id = null) {
        if (!is_null($product_id)) {
            // Limpiar cachu00e9 especu00edfica de este producto
            $this->delete('product_' . $product_id, 'products');
        }
        
        // Limpiar cachu00e9 de listas de productos
        $this->delete('product_list', 'products');
        $this->delete('product_categories', 'products');
        
        do_action('wp_pos_clear_product_cache', $product_id);
    }

    /**
     * Limpiar cachu00e9 de clientes
     *
     * @since 1.0.0
     * @param int $customer_id ID de cliente/usuario
     */
    public function clear_customer_cache($customer_id = null) {
        if (!is_null($customer_id)) {
            // Limpiar cachu00e9 especu00edfica de este cliente
            $this->delete('customer_' . $customer_id, 'customers');
        }
        
        // Limpiar cachu00e9 de listas de clientes
        $this->delete('customer_list', 'customers');
        
        do_action('wp_pos_clear_customer_cache', $customer_id);
    }

    /**
     * Limpiar cachu00e9 de configuraciones
     */
    public function clear_settings_cache() {
        $this->clear_group('settings');
        do_action('wp_pos_clear_settings_cache');
    }

    /**
     * Generar clave u00fanica para cachu00e9
     *
     * @since 1.0.0
     * @param string $key Clave original
     * @param string $group Grupo de cachu00e9
     * @return string Clave u00fanica
     */
    private function get_cache_key($key, $group) {
        // Sanitizar clave
        $key = sanitize_key($key);
        
        // Si la clave ya incluye el grupo, no duplicar
        if (strpos($key, $group . '_') === 0) {
            return $key;
        }
        
        return md5($group . '_' . $key);
    }
}
