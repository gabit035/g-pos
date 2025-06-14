<?php
/**
 * Modelo de venta para WP-POS
 *
 * @package WP-POS
 * @subpackage Sales
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase modelo para ventas
 *
 * @since 1.0.0
 */
class WP_POS_Sale {
    
    /**
     * ID de la venta
     *
     * @since 1.0.0
     * @access private
     * @var int
     */
    private $id = 0;
    
    /**
     * Número de venta
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $sale_number = '';
    
    /**
     * ID del cliente
     *
     * @since 1.0.0
     * @access private
     * @var int
     */
    private $customer_id = 0;
    
    /**
     * Fecha de la venta
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $date = '';
    
    /**
     * Estado de la venta
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $status = 'pending';
    
    /**
     * Items de la venta
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $items = array();
    
    /**
     * Pagos de la venta
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $payments = array();
    
    /**
     * Constructor
     *
     * @since 1.0.0
     * @param int|array $data ID de la venta o array de datos
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
     * @param array $data Datos de la venta
     */
    public function set_props($data) {
        if (isset($data['id'])) {
            $this->id = absint($data['id']);
        }
        
        if (isset($data['sale_number'])) {
            $this->sale_number = sanitize_text_field($data['sale_number']);
        }
        
        if (isset($data['customer_id'])) {
            $this->customer_id = absint($data['customer_id']);
        }
        
        if (isset($data['date'])) {
            $this->date = sanitize_text_field($data['date']);
        }
        
        if (isset($data['status'])) {
            $this->status = sanitize_text_field($data['status']);
        }
        
        if (isset($data['items']) && is_array($data['items'])) {
            $this->items = $data['items'];
        }
        
        if (isset($data['payments']) && is_array($data['payments'])) {
            $this->payments = $data['payments'];
        }
    }
    
    /**
     * Cargar datos de la venta desde la base de datos
     *
     * @since 1.0.0
     */
    private function load() {
        global $wpdb;
        
        if ($this->id <= 0) {
            return;
        }
        
        // Tabla donde se guardan las ventas
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return;
        }
        
        // Obtener venta de la base de datos
        $sale = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $this->id)
        );
        
        if ($sale) {
            $this->sale_number = $sale->sale_number;
            $this->customer_id = intval($sale->customer_id);
            $this->date = $sale->date;
            $this->status = $sale->status;
            $this->items = maybe_unserialize($sale->items);
            $this->payments = maybe_unserialize($sale->payments);
        }
    }
    
    /**
     * Guardar venta en la base de datos
     *
     * @since 1.0.0
     * @return int|bool ID de la venta o false en caso de error
     */
    public function save() {
        global $wpdb;
        
        // Preparar datos para guardar
        $sale_data = array(
            'sale_number' => $this->sale_number,
            'customer_id' => $this->customer_id,
            'date' => $this->date,
            'status' => $this->status,
            'items' => maybe_serialize($this->items),
            'payments' => maybe_serialize($this->payments),
            'total' => $this->calculate_total()
        );
        
        // Tabla donde se guardan las ventas
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Verificar si la tabla existe, si no, crearla
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                sale_number varchar(50) NOT NULL,
                customer_id mediumint(9) NOT NULL,
                date datetime NOT NULL,
                status varchar(50) NOT NULL,
                items longtext NOT NULL,
                payments longtext,
                total decimal(10,2) NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Verificar si la tabla se creó correctamente después de intentar crearla
            if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                // La tabla no se pudo crear
                error_log('WP-POS: No se pudo crear la tabla de ventas: ' . $wpdb->last_error);
                return false;
            }
        }
        
        // Verificar que tenemos al menos un ítem en la venta
        if (empty($this->items)) {
            error_log('WP-POS: Intento de guardar venta sin ítems');
            return false;
        }
        
        // Si es una venta nueva, insertar
        if($this->id === 0) {
            $result = $wpdb->insert(
                $table_name,
                $sale_data
            );
            
            if ($result) {
                $this->id = $wpdb->insert_id;
            } else {
                // Registrar el error para depuración
                error_log('WP-POS: Error al insertar venta: ' . $wpdb->last_error);
                return false;
            }
        } 
        // Si es una venta existente, actualizar
        else {
            $result = $wpdb->update(
                $table_name,
                $sale_data,
                array('id' => $this->id)
            );
            
            if ($result === false) {
                // Registrar el error para depuración
                error_log('WP-POS: Error al actualizar venta: ' . $wpdb->last_error);
                return false;
            }
        }
        
        return $this->id;
    }
    
    /**
     * Calcular total de la venta
     *
     * @since 1.0.0
     * @access private
     * @return float Total de la venta
     */
    private function calculate_total() {
        $total = 0;
        
        // Sumar el total de cada item
        foreach ($this->items as $item) {
            if (is_object($item) && method_exists($item, 'get_total')) {
                $total += $item->get_total();
            }
        }
        
        return $total;
    }
    
    /**
     * Obtener ID de la venta
     *
     * @since 1.0.0
     * @return int ID de la venta
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Obtener número de venta
     *
     * @since 1.0.0
     * @return string Número de venta
     */
    public function get_sale_number() {
        return $this->sale_number;
    }
    
    /**
     * Obtener ID del cliente
     *
     * @since 1.0.0
     * @return int ID del cliente
     */
    public function get_customer_id() {
        return $this->customer_id;
    }
    
    /**
     * Obtener fecha de la venta
     *
     * @since 1.0.0
     * @return string Fecha de la venta
     */
    public function get_date() {
        return $this->date;
    }
    
    /**
     * Obtener estado de la venta
     *
     * @since 1.0.0
     * @return string Estado de la venta
     */
    public function get_status() {
        return $this->status;
    }
    
    /**
     * Obtener items de la venta
     *
     * @since 1.0.0
     * @return array Items de la venta
     */
    public function get_items() {
        return $this->items;
    }
    
    /**
     * Obtener pagos de la venta
     *
     * @since 1.0.0
     * @return array Pagos de la venta
     */
    public function get_payments() {
        return $this->payments;
    }
    
    /**
     * Agregar item a la venta
     *
     * @since 1.0.0
     * @param WP_POS_Sale_Item $item Item a agregar
     */
    public function add_item($item) {
        $this->items[] = $item;
    }
    
    /**
     * Agregar pago a la venta
     *
     * @since 1.0.0
     * @param WP_POS_Payment $payment Pago a agregar
     */
    public function add_payment($payment) {
        $this->payments[] = $payment;
    }
}
