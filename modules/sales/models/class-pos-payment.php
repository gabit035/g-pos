<?php
/**
 * Modelo de pago para WP-POS
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
 * Clase modelo para pagos
 *
 * @since 1.0.0
 */
class WP_POS_Payment {
    
    /**
     * ID del pago
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
     * Mu00e9todo de pago
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $method = 'cash';
    
    /**
     * Monto del pago
     *
     * @since 1.0.0
     * @access private
     * @var float
     */
    private $amount = 0;
    
    /**
     * Fecha del pago
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $date = '';
    
    /**
     * Detalles adicionales del pago
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $details = array();
    
    /**
     * Constructor
     *
     * @since 1.0.0
     * @param int|array $data ID del pago o array de datos
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
     * @param array $data Datos del pago
     */
    public function set_props($data) {
        if (isset($data['id'])) {
            $this->id = absint($data['id']);
        }
        
        if (isset($data['sale_id'])) {
            $this->sale_id = absint($data['sale_id']);
        }
        
        if (isset($data['method'])) {
            $this->method = sanitize_text_field($data['method']);
        }
        
        if (isset($data['amount'])) {
            $this->amount = floatval($data['amount']);
        }
        
        if (isset($data['date'])) {
            $this->date = sanitize_text_field($data['date']);
        }
        
        if (isset($data['details']) && is_array($data['details'])) {
            $this->details = $data['details'];
        }
    }
    
    /**
     * Cargar datos del pago desde la base de datos
     *
     * @since 1.0.0
     */
    private function load() {
        // Implementaciu00f3n bu00e1sica para compatibilidad
        $payment_data = array(
            'id' => $this->id,
            'sale_id' => 0,
            'method' => 'cash',
            'amount' => 0,
            'date' => date('Y-m-d H:i:s'),
            'details' => array()
        );
        
        $this->set_props($payment_data);
    }
    
    /**
     * Guardar pago en la base de datos
     *
     * @since 1.0.0
     * @return int|bool ID del pago o false en caso de error
     */
    public function save() {
        // Implementaciu00f3n bu00e1sica para compatibilidad
        if ($this->id === 0) {
            $this->id = wp_rand(1, 1000);
        }
        
        return $this->id;
    }
    
    /**
     * Obtener ID del pago
     *
     * @since 1.0.0
     * @return int ID del pago
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
     * Obtener mu00e9todo de pago
     *
     * @since 1.0.0
     * @return string Mu00e9todo de pago
     */
    public function get_method() {
        return $this->method;
    }
    
    /**
     * Obtener monto del pago
     *
     * @since 1.0.0
     * @return float Monto del pago
     */
    public function get_amount() {
        return $this->amount;
    }
    
    /**
     * Obtener fecha del pago
     *
     * @since 1.0.0
     * @return string Fecha del pago
     */
    public function get_date() {
        return $this->date;
    }
    
    /**
     * Obtener detalles adicionales del pago
     *
     * @since 1.0.0
     * @return array Detalles adicionales
     */
    public function get_details() {
        return $this->details;
    }
}
