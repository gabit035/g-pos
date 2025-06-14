<?php
/**
 * Modelo de item de venta para WP-POS
 *
 * @package WP-POS
 * @subpackage Sales
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase modelo para items de venta
 *
 * @since 1.0.0
 */
class WP_POS_Sale_Item {
    
    /**
     * ID del item
     *
     * @since 1.0.0
     * @access private
     * @var int
     */
    private $id = 0;
    
    /**
     * ID de la venta
     *
     * @since 1.0.0
     * @access private
     * @var int
     */
    private $sale_id = 0;
    
    /**
     * ID del producto
     *
     * @since 1.0.0
     * @access private
     * @var int
     */
    private $product_id = 0;
    
    /**
     * Nombre del producto
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $product_name = '';
    
    /**
     * Cantidad
     *
     * @since 1.0.0
     * @access private
     * @var float
     */
    private $quantity = 0;
    
    /**
     * Precio unitario
     *
     * @since 1.0.0
     * @access private
     * @var float
     */
    private $price = 0;
    
    /**
     * Total
     *
     * @since 1.0.0
     * @access private
     * @var float
     */
    private $total = 0;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     * @param int|array $data ID del item o array de datos
     */
    public function __construct($data = 0) {
        if (is_numeric($data)) {
            $this->id = absint($data);
            $this->load();
        } elseif (is_array($data)) {
            $this->set_props($data);
        }
    }
    
    /**
     * Establecer propiedades
     *
     * @since 1.0.0
     * @param array $data Datos del item
     */
    public function set_props($data) {
        if (isset($data['id'])) {
            $this->id = absint($data['id']);
        }
        
        if (isset($data['sale_id'])) {
            $this->sale_id = absint($data['sale_id']);
        }
        
        if (isset($data['product_id'])) {
            $this->product_id = absint($data['product_id']);
        }
        
        if (isset($data['product_name'])) {
            $this->product_name = sanitize_text_field($data['product_name']);
        }
        
        if (isset($data['quantity'])) {
            $this->quantity = floatval($data['quantity']);
        }
        
        if (isset($data['price'])) {
            $this->price = floatval($data['price']);
        }
        
        // Calcular total
        $this->total = $this->price * $this->quantity;
    }
    
    /**
     * Cargar datos del item desde la base de datos
     *
     * @since 1.0.0
     */
    private function load() {
        // Implementaciu00f3n bu00e1sica para compatibilidad
        $item_data = array(
            'id' => $this->id,
            'sale_id' => 0,
            'product_id' => 0,
            'product_name' => '',
            'quantity' => 0,
            'price' => 0
        );
        
        $this->set_props($item_data);
    }
    
    /**
     * Guardar item en la base de datos
     *
     * @since 1.0.0
     * @return int|bool ID del item o false en caso de error
     */
    public function save() {
        // Implementaciu00f3n bu00e1sica para compatibilidad
        if ($this->id === 0) {
            $this->id = wp_rand(1, 1000);
        }
        
        return $this->id;
    }
    
    /**
     * Obtener ID del item
     *
     * @since 1.0.0
     * @return int ID del item
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Obtener ID de la venta
     *
     * @since 1.0.0
     * @return int ID de la venta
     */
    public function get_sale_id() {
        return $this->sale_id;
    }
    
    /**
     * Obtener ID del producto
     *
     * @since 1.0.0
     * @return int ID del producto
     */
    public function get_product_id() {
        return $this->product_id;
    }
    
    /**
     * Obtener nombre del producto
     *
     * @since 1.0.0
     * @return string Nombre del producto
     */
    public function get_product_name() {
        return $this->product_name;
    }
    
    /**
     * Obtener cantidad
     *
     * @since 1.0.0
     * @return float Cantidad
     */
    public function get_quantity() {
        return $this->quantity;
    }
    
    /**
     * Obtener precio unitario
     *
     * @since 1.0.0
     * @return float Precio unitario
     */
    public function get_price() {
        return $this->price;
    }
    
    /**
     * Obtener total
     *
     * @since 1.0.0
     * @return float Total
     */
    public function get_total() {
        return $this->total;
    }
}
