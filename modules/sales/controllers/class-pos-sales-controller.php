<?php
/**
 * Controlador de Ventas para WP-POS
 *
 * Maneja la lógica principal para la gestión de ventas
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
 * Clase del controlador de ventas
 *
 * @since 1.0.0
 */
class WP_POS_Sales_Controller {

    /**
     * Instancia singleton
     *
     * @since 1.0.0
     * @access private
     * @static
     * @var WP_POS_Sales_Controller
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     *
     * @since 1.0.0
     * @static
     * @return WP_POS_Sales_Controller Instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Inicializar controlador
        add_action('init', array($this, 'init'));
    }

    /**
     * Inicializar controlador
     *
     * @since 1.0.0
     */
    public function init() {
        // Añadir acciones o inicialización adicional si es necesario
    }

    /**
     * Obtener ventas con filtros opcionales
     *
     * @since 1.0.0
     * @param array $args Argumentos de búsqueda
     * @return array Ventas encontradas
     */
    public function get_sales($args = array()) {
        global $wpdb;
        
        // Tabla donde se guardan las ventas
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array(); // Si no existe la tabla, devolver array vacío
        }
        
        // Construir consulta SQL básica
        $sql = "SELECT * FROM $table_name WHERE 1=1";
        $sql_count = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
        $sql_args = array();
        
        // Aplicar filtros si hay
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $sql .= " AND (sale_number LIKE %s OR id = %s)";
            $sql_count .= " AND (sale_number LIKE %s OR id = %s)";
            $sql_args[] = $search;
            $sql_args[] = $args['search']; // Búsqueda exacta por ID
        }
        
        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $sql_count .= " AND status = %s";
            $sql_args[] = $args['status'];
        }
        
        if (!empty($args['date_from'])) {
            $sql .= " AND date_created >= %s";
            $sql_count .= " AND date_created >= %s";
            $sql_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $sql .= " AND date_created <= %s";
            $sql_count .= " AND date_created <= %s";
            $sql_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        // Ordenar resultados
        $order_by = !empty($args['order_by']) ? $args['order_by'] : 'date_desc';
        $order_sql = 'date_created DESC';
        
        switch ($order_by) {
            case 'date_asc':
                $order_sql = 'date_created ASC';
                break;
            case 'date_desc':
                $order_sql = 'date_created DESC';
                break;
            case 'total_asc':
                $order_sql = 'total ASC';
                break;
            case 'total_desc':
                $order_sql = 'total DESC';
                break;
            case 'id_asc':
                $order_sql = 'id ASC';
                break;
            case 'id_desc':
                $order_sql = 'id DESC';
                break;
        }
        
        $sql .= " ORDER BY $order_sql";
        
        // Aplicar paginación si se especifica
        if (isset($args['limit']) && intval($args['limit']) > 0) {
            $limit = intval($args['limit']);
            $offset = !empty($args['offset']) ? intval($args['offset']) : 0;
            
            $sql .= " LIMIT %d OFFSET %d";
            $sql_args[] = $limit;
            $sql_args[] = $offset;
        }
        
        // Preparar consulta con argumentos
        if (!empty($sql_args)) {
            $sql = $wpdb->prepare($sql, $sql_args);
        }
        
        // Ejecutar consulta
        $results = $wpdb->get_results($sql);
        
        if (!$results) {
            return array();
        }
        
        // Obtener el total de registros para la paginación
        $total_items = $wpdb->get_var($wpdb->prepare($sql_count, $sql_args));
        
        // Convertir resultados a objetos de venta
        $sales = array();
        foreach ($results as $result) {
            // Simplemente devolver los datos como array para la lista
            $sale = array(
                'id' => $result->id,
                'sale_number' => $result->sale_number,
                'customer_id' => $result->customer_id,
                'customer_name' => $this->get_customer_name($result->customer_id),
                'date' => $result->date_created, // Usar date_created en lugar de date para consistencia
                'date_created' => $result->date_created,
                'status' => $result->status,
                'total' => $result->total,
                'items_count' => $this->count_items($result->id), // Pasar el ID de la venta
            );
            
            $sales[] = (object)$sale; // Convertir a objeto para mantener compatibilidad con el código existente
        }
        
        // Añadir información de paginación al resultado si es necesario
        if (isset($args['limit'])) {
            return array(
                'items' => $sales,
                'total_items' => $total_items,
                'total_pages' => ceil($total_items / $args['limit'])
            );
        }
        
        return $sales;
    }
    
    /**
     * Obtener nombre de cliente por ID
     *
     * @since 1.0.0
     * @param int $customer_id ID del cliente
     * @return string Nombre del cliente
     */
    private function get_customer_name($customer_id) {
        if (empty($customer_id) || $customer_id == 0) {
            return __('Cliente anónimo', 'wp-pos');
        }
        
        // Si es un usuario de WordPress
        $user = get_user_by('id', $customer_id);
        if ($user) {
            return $user->display_name;
        }
        
        return __('Cliente #', 'wp-pos') . $customer_id;
    }
    
    /**
     * Contar items de una venta
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @return int Número de items
     */
    private function count_items($sale_id) {
        global $wpdb;
        
        // Verificar si el parámetro es un ID de venta o datos serializados (para compatibilidad)
        if (is_numeric($sale_id) && $sale_id > 0) {
            $items_table = $wpdb->prefix . 'pos_sale_items';
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $items_table WHERE sale_id = %d",
                $sale_id
            ));
            
            return intval($count);
        }
        
        // Compatibilidad con versiones anteriores (si se pasa un string serializado)
        if (is_string($sale_id) && !empty($sale_id)) {
            $items = maybe_unserialize($sale_id);
            if (is_array($items)) {
                return count($items);
            }
        }
        
        return 0;
    }

    /**
     * Obtener una venta por su ID
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @return WP_POS_Sale|false Objeto de venta o false en caso de no encontrarla
     */
    public function get_sale($sale_id) {
        $sale = new WP_POS_Sale($sale_id);
        if ($sale->get_id() > 0) {
            return $sale;
        }
        return false;
    }

    /**
     * Crear una nueva venta
     *
     * @since 1.0.0
     * @param array $data Datos de la venta
     * @return WP_POS_Sale|false Objeto de venta o false en caso de error
     */
    public function create_sale($data) {
        $sale = new WP_POS_Sale($data);
        $sale_id = $sale->save();
        if ($sale_id) {
            return $sale;
        }
        return false;
    }

    /**
     * Actualizar una venta existente
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @param array $data Datos a actualizar
     * @return WP_POS_Sale|false Objeto de venta o false en caso de error
     */
    public function update_sale($sale_id, $data) {
        // Verificar permisos para editar ventas
        if (!function_exists('wp_pos_can_edit_sales')) {
            require_once(WP_POS_PLUGIN_DIR . 'includes/helpers/permissions-helper.php');
        }
        
        if (!wp_pos_can_edit_sales()) {
            error_log('Error al actualizar venta: El usuario no tiene permisos');
            return false;
        }

        $sale = $this->get_sale($sale_id);
        if (!$sale) {
            return false;
        }
        
        $sale->set_props($data);
        $sale_id = $sale->save();
        
        if ($sale_id) {
            return $sale;
        }
        return false;
    }

    /**
     * Añadir un producto a una venta
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @param array $item_data Datos del item (producto)
     * @return bool Éxito de la operación
     */
    public function add_item_to_sale($sale_id, $item_data) {
        $sale = $this->get_sale($sale_id);
        if (!$sale) {
            return false;
        }
        
        $item = new WP_POS_Sale_Item($item_data);
        $sale->add_item($item);
        
        return $sale->save() ? true : false;
    }

    /**
     * Añadir un pago a una venta
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @param array $payment_data Datos del pago
     * @return bool Éxito de la operación
     */
    public function add_payment_to_sale($sale_id, $payment_data) {
        $sale = $this->get_sale($sale_id);
        if (!$sale) {
            return false;
        }
        
        $payment = new WP_POS_Payment($payment_data);
        $sale->add_payment($payment);
        
        return $sale->save() ? true : false;
    }

    /**
     * Procesar una venta completa
     *
     * @since 1.0.0
     * @param array $sale_data Datos de la venta
     * @return WP_POS_Sale|WP_Error|false Objeto de venta o error
     */
    public function process_sale($sale_data) {
        global $wpdb;
        
        // Verificar datos requeridos
        if (empty($sale_data)) {
            return new WP_Error('invalid_data', __('Datos de venta inválidos.', 'wp-pos'));
        }
        
        // Verificar si la tabla existe
        $table_name = $wpdb->prefix . 'pos_sales';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            // Intentar crear la tabla nuevamente
            require_once WP_POS_INCLUDES_DIR . 'class/class-pos-installer.php';
            $installer = new WP_POS_Installer();
            $installer->create_tables();
            
            // Verificar nuevamente
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if (!$table_exists) {
                wp_pos_log('Error: Tabla de ventas no encontrada al procesar venta', 'error');
                return new WP_Error('table_missing', __('No se puede procesar la venta: tablas no disponibles.', 'wp-pos'));
            }
        }
        
        // Crear la venta
        $sale = new WP_POS_Sale();
        
        // Calcular ganancia (suma de (precio - costo) * cantidad para cada item)
        $profit = 0;
        if (isset($sale_data['items']) && is_array($sale_data['items'])) {
            foreach ($sale_data['items'] as $item_data) {
                if (isset($item_data['cost']) && isset($item_data['price']) && isset($item_data['quantity'])) {
                    $profit += ($item_data['price'] - $item_data['cost']) * $item_data['quantity'];
                }
            }
        }
        
        // Establecer propiedades básicas
        $sale->set_props(array(
            'sale_number' => 'POS-' . date('YmdHis'),
            'customer_id' => isset($sale_data['customer_id']) ? $sale_data['customer_id'] : 0,
            'date_created' => current_time('mysql'),
            'date_completed' => current_time('mysql'),
            'status' => 'completed',
            'total' => $sale_data['total'] ?? 0,
            'tax_total' => $sale_data['tax_total'] ?? 0,
            'discount_total' => $sale_data['discount_total'] ?? 0,
            'user_id' => get_current_user_id(),
            'register_id' => $sale_data['register_id'] ?? 1, // Asumir registro 1 si no se especifica
            'profit' => $profit
        ));
        
        // Agregar items
        if (isset($sale_data['items']) && is_array($sale_data['items'])) {
            foreach ($sale_data['items'] as $item_data) {
                $item = new WP_POS_Sale_Item($item_data);
                $sale->add_item($item);
            }
        }
        
        // Agregar pagos
        if (isset($sale_data['payments']) && is_array($sale_data['payments'])) {
            foreach ($sale_data['payments'] as $payment_data) {
                $payment = new WP_POS_Payment($payment_data);
                $sale->add_payment($payment);
            }
        }
        
        // Guardar la venta
        $sale_id = $sale->save();
        
        if ($sale_id) {
            // Actualizar inventario
            $this->update_inventory_after_sale($sale);
            
            return $sale;
        }
        
        return false;
    }
    
    /**
     * Actualizar inventario después de una venta
     *
     * @since 1.0.0
     * @param WP_POS_Sale $sale Objeto de venta
     */
    private function update_inventory_after_sale($sale) {
        $items = $sale->get_items();
        
        foreach ($items as $item) {
            if (function_exists('wp_pos_update_product_stock')) {
                wp_pos_update_product_stock(
                    $item->get_product_id(),
                    $item->get_quantity(),
                    'subtract'
                );
            }
        }
    }
    
    /**
     * Obtener número de ventas con filtros opcionales
     *
     * @since 1.0.0
     * @param array $args Argumentos de filtrado (fecha, estado, etc.)
     * @return int Número de ventas
     */
    public function get_sales_count($args = array()) {
        global $wpdb;
        
        // Tabla donde se guardan las ventas
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0; // Si no existe la tabla, devolver 0
        }
        
        // Construir consulta SQL básica
        $sql = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
        $sql_args = array();
        
        // Aplicar filtros si hay
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $sql .= " AND (sale_number LIKE %s OR id = %s)";
            $sql_args[] = $search;
            $sql_args[] = $args['search']; // Búsqueda exacta por ID
        }
        
        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $sql_args[] = $args['status'];
        }
        
        if (!empty($args['date_from'])) {
            $sql .= " AND date >= %s";
            $sql_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $sql .= " AND date <= %s";
            $sql_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        // Preparar consulta con argumentos
        if (!empty($sql_args)) {
            $sql = $wpdb->prepare($sql, $sql_args);
        }
        
        // Ejecutar consulta
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Obtener ingresos totales de ventas con filtros opcionales
     *
     * @since 1.0.0
     * @param array $args Argumentos de filtrado (fecha, estado, etc.)
     * @return float Total de ingresos
     */
    public function get_sales_revenue($args = array()) {
        // Implementación básica para compatibilidad
        // En una implementación real, consultaríamos la base de datos con los filtros
        return 0.00;
    }
    
    /**
     * Obtener número de productos vendidos con filtros opcionales
     *
     * @since 1.0.0
     * @param array $args Argumentos de filtrado (fecha, estado, etc.)
     * @return int Número de productos vendidos
     */
    public function get_products_sold_count($args = array()) {
        // Implementación básica para compatibilidad
        // En una implementación real, consultaríamos la base de datos con los filtros
        return 0;
    }
    
    /**
     * Contar ventas con los mismos filtros
     *
     * @since 1.0.0
     * @param array $args Argumentos de filtro
     * @return int Cantidad de ventas
     */
    public function count_sales($args = array()) {
        global $wpdb;
        
        // Tabla donde se guardan las ventas
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0; // Si no existe la tabla, devolver 0
        }
        
        // Construir consulta SQL básica
        $sql = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
        $sql_args = array();
        
        // Aplicar filtros si hay
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $sql .= " AND (sale_number LIKE %s OR id = %s)";
            $sql_args[] = $search;
            $sql_args[] = $args['search']; // Búsqueda exacta por ID
        }
        
        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $sql_args[] = $args['status'];
        }
        
        if (!empty($args['date_from'])) {
            $sql .= " AND date >= %s";
            $sql_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $sql .= " AND date <= %s";
            $sql_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        // Preparar consulta con argumentos
        if (!empty($sql_args)) {
            $sql = $wpdb->prepare($sql, $sql_args);
        }
        
        // Ejecutar consulta
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Actualizar el estado de una venta
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @param string $status Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function update_sale_status($sale_id, $status) {
        global $wpdb;
        
        // Validar datos
        $sale_id = intval($sale_id);
        if ($sale_id <= 0) {
            return false;
        }
        
        // Asegurar que el estado es válido
        $valid_statuses = array_keys(wp_pos_get_sale_statuses());
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        // Tabla donde se guardan las ventas
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Actualizar estado en la base de datos
        $result = $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $sale_id),
            array('%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Eliminar una venta
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @return bool True si se eliminó correctamente
     */
    public function delete_sale($sale_id) {
        // Verificar permisos para eliminar ventas
        if (!function_exists('wp_pos_can_delete_sales')) {
            require_once(WP_POS_PLUGIN_DIR . 'includes/helpers/permissions-helper.php');
        }
        
        if (!wp_pos_can_delete_sales()) {
            error_log('Error al eliminar venta: El usuario no tiene permisos');
            return false;
        }

        global $wpdb;
        
        // Validar datos
        $sale_id = intval($sale_id);
        if ($sale_id <= 0) {
            return false;
        }
        
        // Tabla donde se guardan las ventas
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Eliminar venta de la base de datos
        $result = $wpdb->delete(
            $table_name,
            array('id' => $sale_id),
            array('%d')
        );
        
        return $result !== false;
    }
}
