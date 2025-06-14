<?php
/**
 * Módulo de Impresión de Recibos
 * 
 * @package WP-POS
 * @subpackage Receipts
 * @since 2.3.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo de impresión de recibos
 */
class WP_POS_Receipts_Module {
    
    /**
     * Inicializar el módulo
     */
    public function __construct() {
        // Registrar estilos y scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Registrar la página de impresión en el menú - importante usar admin_menu en lugar de wp_pos_admin_menu
        add_action('admin_menu', array($this, 'register_receipt_page'));
        
        // Registrar la acción AJAX para impresión de recibos
        add_action('wp_ajax_wp_pos_print_receipt', array($this, 'print_receipt_ajax'));
        
        // Agregar un log para depuración
        error_log('[WP-POS] Módulo de recibos inicializado');
    }
    
    /**
     * Registra la página de impresión de recibos
     */
    public function register_receipt_page() {
        // Registrar página pero no mostrarla en el menú (parámetro null)
        add_submenu_page(
            null, // No mostrar en el menú
            __('Imprimir Recibo', 'wp-pos'),
            __('Imprimir Recibo', 'wp-pos'),
            'access_pos',
            'wp-pos-print-receipt',
            array($this, 'render_receipt_page')
        );
    }
    
    /**
     * Renderizar la página de impresión de recibos
     * Redirecciona a la página independiente para evitar problemas con encabezados
     */
    public function render_receipt_page() {
        // Verificar ID de venta
        $sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$sale_id) {
            wp_die(__('Se requiere un ID de venta válido', 'wp-pos'));
        }
        
        // Añadir log para depuración
        error_log("[WP-POS] Redireccionando a la página independiente para venta ID: {$sale_id}");
        
        // Redireccionar a la página independiente
        $standalone_url = plugins_url('/receipt-standalone.php?id=' . $sale_id, __FILE__);
        
        // Si se solicita auto-impresión, agregar el parámetro
        if (isset($_GET['autoprint']) && $_GET['autoprint'] == 1) {
            $standalone_url .= '&autoprint=1';
        }
        
        // Evitar problemas de encabezados realizando una redirección por JavaScript
        echo '<script>window.location.href = "' . esc_url($standalone_url) . '";</script>';
        echo '<p>' . __('Redirigiendo a la página de impresión...', 'wp-pos') . '</p>';
        echo '<p><a href="' . esc_url($standalone_url) . '">' . __('Haga clic aquí si no es redirigido automáticamente', 'wp-pos') . '</a></p>';
        exit;
    }
    
    /**
     * Cargar la plantilla de impresión
     * 
     * @param int $sale_id ID de la venta
     */
    public function load_receipt_template($sale_id) {
        // Obtener datos de la venta
        $sale_data = $this->get_sale_data($sale_id);
        
        if (!$sale_data) {
            wp_die(__('No se pudo obtener la información de la venta', 'wp-pos'));
        }
        
        // Incluir la plantilla
        include_once(dirname(__FILE__) . '/templates/receipt-template.php');
    }
    
    /**
     * Obtener datos de la venta
     * 
     * @param int $sale_id ID de la venta
     * @return object|false Datos de la venta o false si no existe
     */
    public function get_sale_data($sale_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Log para depuración
        error_log("[WP-POS] Buscando venta ID {$sale_id} en la tabla {$table_name}");
        
        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log("[WP-POS] ERROR: La tabla {$table_name} no existe");
            return false;
        }
        
        // Obtener venta directamente de la base de datos
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $sale_id);
        $sale = $wpdb->get_row($query);
        
        // Log del resultado de la consulta
        error_log("[WP-POS] Consulta SQL: " . $query);
        error_log("[WP-POS] Resultado de la consulta: " . ($sale ? 'Venta encontrada' : 'Venta NO encontrada'));
        
        if ($sale) {
            // Deserializar items y pagos
            $sale->items = maybe_unserialize($sale->items);
            $sale->payments = maybe_unserialize($sale->payments);
            
            // Asegurarse de que sean arrays
            if (!is_array($sale->items)) {
                $sale->items = array();
            }
            
            if (!is_array($sale->payments)) {
                $sale->payments = array();
            }
            
            // Obtener datos del cliente si existe
            if (!empty($sale->customer_id)) {
                $customer = get_post($sale->customer_id);
                if ($customer) {
                    $sale->customer_name = $customer->post_title;
                }
            }
            
            // Si no hay nombre de cliente, usar el valor por defecto
            if (empty($sale->customer_name)) {
                $sale->customer_name = __('Cliente anónimo', 'wp-pos');
            }
        }
        
        return $sale;
    }
    
    /**
     * Endpoint AJAX para imprimir recibos
     */
    public function print_receipt_ajax() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_pos_print_receipt')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'wp-pos'));
        }
        
        $sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
        
        if (!$sale_id) {
            wp_send_json_error(__('Se requiere un ID de venta válido', 'wp-pos'));
        }
        
        // Obtener datos de la venta
        $sale_data = $this->get_sale_data($sale_id);
        
        if (!$sale_data) {
            wp_send_json_error(__('No se pudo obtener la información de la venta', 'wp-pos'));
        }
        
        // Obtener HTML del recibo
        ob_start();
        include_once(dirname(__FILE__) . '/templates/receipt-template.php');
        $receipt_html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $receipt_html,
            'sale_number' => $sale_data->sale_number
        ));
    }
    
    /**
     * Registra los estilos y scripts necesarios
     * 
     * @param string $hook Página actual
     */
    public function enqueue_assets($hook) {
        // Solo cargar en la página de impresión
        if (strpos($hook, 'wp-pos-print-receipt') === false) {
            return;
        }
        
        // Registrar estilos
        wp_enqueue_style(
            'wp-pos-receipt-styles',
            plugins_url('assets/css/receipt-styles.css', __FILE__),
            array(),
            WP_POS_VERSION
        );
        
        // Registrar scripts
        wp_enqueue_script(
            'wp-pos-receipt-scripts',
            plugins_url('assets/js/receipt-scripts.js', __FILE__),
            array('jquery'),
            WP_POS_VERSION,
            true
        );
        
        // Localizar script
        wp_localize_script('wp-pos-receipt-scripts', 'wpPosReceipts', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_pos_print_receipt'),
            'printText' => __('Imprimir Recibo', 'wp-pos'),
            'closeText' => __('Cerrar', 'wp-pos')
        ));
    }
}

// Inicializar el módulo
function wp_pos_init_receipts_module() {
    new WP_POS_Receipts_Module();
}

// Iniciar el módulo
wp_pos_init_receipts_module();
