<?php
/**
 * Controlador principal de configuraciones
 *
 * @package WP-POS
 * @subpackage Settings
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora de configuraciones
 *
 * @since 1.0.0
 */
class WP_POS_Settings_Controller {

    /**
     * Instancia del renderizador de campos
     *
     * @since 1.0.0
     * @var WP_POS_Settings_Fields_Renderer
     */
    private $renderer;

    /**
     * Instancia del sanitizador
     *
     * @since 1.0.0
     * @var WP_POS_Settings_Sanitize
     */
    private $sanitizer;

    /**
     * Campos de configuracioFn registrados
     *
     * @since 1.0.0
     * @var array
     */
    private $fields = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Cargar dependencias
        $this->load_dependencies();
        
        // Registrar campos predeterminados
        $this->register_default_fields();
    }

    /**
     * Cargar dependencias
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        // Cargar clases adicionales
        require_once dirname(__FILE__) . '/class-pos-settings-fields-renderer.php';
        require_once dirname(__FILE__) . '/class-pos-settings-sanitize.php';
        
        // Instanciar clases
        $this->renderer = new WP_POS_Settings_Fields_Renderer();
        $this->sanitizer = new WP_POS_Settings_Sanitize();
    }

    /**
     * Registrar campos predeterminados
     *
     * @since 1.0.0
     */
    private function register_default_fields() {
        // Registrar campos para cada grupo
        $this->register_general_fields();
        $this->register_currency_fields();
        $this->register_receipts_fields();
        $this->register_customers_fields();
        $this->register_integrations_fields();
        $this->register_advanced_fields();
        
        // Permitir au00f1adir mu00e1s campos
        do_action('wp_pos_register_settings_fields', $this);
    }

    /**
     * Registrar campos para el grupo 'general'
     *
     * @since 1.0.0
     */
    private function register_general_fields() {
        $this->add_field('general', 'store_name', array(
            'title'       => __('Nombre de la tienda', 'wp-pos'),
            'description' => __('Nombre de la tienda que apareceru00e1 en recibos e informes.', 'wp-pos'),
            'type'        => 'text',
            'default'     => get_bloginfo('name'),
        ));
        
        $this->add_field('general', 'store_address', array(
            'title'       => __('Direcciu00f3n de la tienda', 'wp-pos'),
            'description' => __('Direcciu00f3n fu00edsica de la tienda (opcional).', 'wp-pos'),
            'type'        => 'textarea',
            'default'     => '',
        ));
        
        $this->add_field('general', 'store_phone', array(
            'title'       => __('Telu00e9fono de contacto', 'wp-pos'),
            'description' => __('Telu00e9fono de contacto que apareceru00e1 en recibos.', 'wp-pos'),
            'type'        => 'text',
            'default'     => '',
        ));
        
        $this->add_field('general', 'store_email', array(
            'title'       => __('Email de contacto', 'wp-pos'),
            'description' => __('Email de contacto para clientes.', 'wp-pos'),
            'type'        => 'email',
            'default'     => get_bloginfo('admin_email'),
        ));
        
        $this->add_field('general', 'store_logo', array(
            'title'       => __('Logo de la tienda', 'wp-pos'),
            'description' => __('Logo que apareceru00e1 en recibos e informes.', 'wp-pos'),
            'type'        => 'image',
            'default'     => '',
        ));
        
        $this->add_field('general', 'default_customer', array(
            'title'       => __('Cliente por defecto', 'wp-pos'),
            'description' => __('Cliente que se seleccionaru00e1 por defecto en ventas nuevas.', 'wp-pos'),
            'type'        => 'select',
            'default'     => '0',
            'options'     => array(
                'choices' => $this->get_customers_list(),
            ),
        ));
        
        $this->add_field('general', 'require_customer', array(
            'title'       => __('Requerir cliente', 'wp-pos'),
            'description' => __('Obligar a seleccionar un cliente para cada venta.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'no',
            'options'     => array(
                'checkbox_label' => __('Su00ed, requerir cliente para todas las ventas', 'wp-pos'),
            ),
        ));
    }

    /**
     * Registrar campos para el grupo 'currency'
     *
     * @since 1.0.0
     */
    private function register_currency_fields() {
        $this->add_field('currency', 'currency', array(
            'title'       => __('Moneda', 'wp-pos'),
            'description' => __('Moneda principal para ventas.', 'wp-pos'),
            'type'        => 'select',
            'default'     => 'USD',
            'options'     => array(
                'choices' => $this->get_currencies_list(),
            ),
        ));
        
        $this->add_field('currency', 'price_format', array(
            'title'       => __('Formato de precio', 'wp-pos'),
            'description' => __('Formato para mostrar precios. %s = su00edmbolo, %v = valor', 'wp-pos'),
            'type'        => 'select',
            'default'     => '%s%v',
            'options'     => array(
                'choices' => array(
                    '%s%v' => __('Su00edmbolo antes del precio (ej. $10.99)', 'wp-pos'),
                    '%v%s' => __('Su00edmbolo despuu00e9s del precio (ej. 10.99$)', 'wp-pos'),
                    '%s %v' => __('Su00edmbolo con espacio antes del precio (ej. $ 10.99)', 'wp-pos'),
                    '%v %s' => __('Su00edmbolo con espacio despuu00e9s del precio (ej. 10.99 $)', 'wp-pos'),
                ),
            ),
        ));
        
        $this->add_field('currency', 'thousand_separator', array(
            'title'       => __('Separador de miles', 'wp-pos'),
            'description' => __('Su00edmbolo para separar miles.', 'wp-pos'),
            'type'        => 'text',
            'default'     => ',',
        ));
        
        $this->add_field('currency', 'decimal_separator', array(
            'title'       => __('Separador decimal', 'wp-pos'),
            'description' => __('Su00edmbolo para separar decimales.', 'wp-pos'),
            'type'        => 'text',
            'default'     => '.',
        ));
        
        $this->add_field('currency', 'price_decimals', array(
            'title'       => __('Nu00famero de decimales', 'wp-pos'),
            'description' => __('Nu00famero de decimales a mostrar en los precios.', 'wp-pos'),
            'type'        => 'number',
            'default'     => '2',
            'options'     => array(
                'min' => 0,
                'max' => 4,
                'step' => 1,
            ),
        ));
        
        $this->add_field('currency', 'tax_enabled', array(
            'title'       => __('Habilitar impuestos', 'wp-pos'),
            'description' => __('Activar cu00e1lculo de impuestos en las ventas.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'no',
            'options'     => array(
                'checkbox_label' => __('Su00ed, activar impuestos', 'wp-pos'),
            ),
        ));
        
        $this->add_field('currency', 'prices_include_tax', array(
            'title'       => __('Precios con impuestos incluidos', 'wp-pos'),
            'description' => __('Indicar si los precios de productos ya incluyen impuestos.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'no',
            'options'     => array(
                'checkbox_label' => __('Su00ed, los precios ya incluyen impuestos', 'wp-pos'),
            ),
            'conditions'  => array(
                array('field' => 'currency_tax_enabled', 'value' => 'yes'),
            ),
        ));
        
        $this->add_field('currency', 'tax_rate', array(
            'title'       => __('Tasa de impuesto (%)', 'wp-pos'),
            'description' => __('Porcentaje de impuesto por defecto.', 'wp-pos'),
            'type'        => 'number',
            'default'     => '0',
            'options'     => array(
                'min' => 0,
                'max' => 100,
                'step' => 0.01,
            ),
            'conditions'  => array(
                array('field' => 'currency_tax_enabled', 'value' => 'yes'),
            ),
        ));
    }

    /**
     * Registrar campos para el grupo 'receipts'
     *
     * @since 1.0.0
     */
    private function register_receipts_fields() {
        $this->add_field('receipts', 'receipt_header', array(
            'title'       => __('Encabezado del recibo', 'wp-pos'),
            'description' => __('Texto a mostrar en la parte superior del recibo.', 'wp-pos'),
            'type'        => 'textarea',
            'default'     => '',
        ));
        
        $this->add_field('receipts', 'receipt_footer', array(
            'title'       => __('Pie del recibo', 'wp-pos'),
            'description' => __('Texto a mostrar en la parte inferior del recibo.', 'wp-pos'),
            'type'        => 'textarea',
            'default'     => __('Gracias por su compra', 'wp-pos'),
        ));
        
        $this->add_field('receipts', 'show_tax_summary', array(
            'title'       => __('Mostrar resumen de impuestos', 'wp-pos'),
            'description' => __('Mostrar desglose de impuestos en el recibo.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'yes',
            'options'     => array(
                'checkbox_label' => __('Su00ed, mostrar resumen de impuestos', 'wp-pos'),
            ),
        ));
        
        $this->add_field('receipts', 'show_cashier_name', array(
            'title'       => __('Mostrar nombre del cajero', 'wp-pos'),
            'description' => __('Mostrar quiu00e9n procesa2 la venta.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'yes',
            'options'     => array(
                'checkbox_label' => __('Su00ed, mostrar nombre del cajero', 'wp-pos'),
            ),
        ));
        
        $this->add_field('receipts', 'receipt_printer_enabled', array(
            'title'       => __('Habilitar impresora de recibos', 'wp-pos'),
            'description' => __('Activar impresiu00f3n de recibos.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'no',
            'options'     => array(
                'checkbox_label' => __('Su00ed, activar impresora', 'wp-pos'),
            ),
        ));
        
        $this->add_field('receipts', 'printer_type', array(
            'title'       => __('Tipo de impresora', 'wp-pos'),
            'description' => __('Seleccionar el tipo de impresora.', 'wp-pos'),
            'type'        => 'select',
            'default'     => 'browser',
            'options'     => array(
                'choices' => array(
                    'browser' => __('Impresiu00f3n desde navegador', 'wp-pos'),
                    'thermal' => __('Impresora tu00e9rmica', 'wp-pos'),
                    'network' => __('Impresora de red', 'wp-pos'),
                ),
            ),
            'conditions'  => array(
                array('field' => 'receipts_receipt_printer_enabled', 'value' => 'yes'),
            ),
        ));
    }

    /**
     * Registrar campos para el grupo 'customers'
     *
     * @since 1.0.0
     */
    private function register_customers_fields() {
        $this->add_field('customers', 'enable_customer_management', array(
            'title'       => __('Habilitar gestiu00f3n de clientes', 'wp-pos'),
            'description' => __('Permitir crear y gestionar clientes desde el POS.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'yes',
            'options'     => array(
                'checkbox_label' => __('Su00ed, habilitar gestiu00f3n de clientes', 'wp-pos'),
            ),
        ));
        
        $this->add_field('customers', 'show_customer_details', array(
            'title'       => __('Mostrar detalles de cliente', 'wp-pos'),
            'description' => __('Mostrar informacid=F3n del cliente en el POS.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'yes',
            'options'     => array(
                'checkbox_label' => __('Su00ed, mostrar detalles de cliente', 'wp-pos'),
            ),
        ));
        
        $this->add_field('customers', 'enable_customer_groups', array(
            'title'       => __('Habilitar grupos de clientes', 'wp-pos'),
            'description' => __('Permitir organizar clientes en grupos.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'no',
            'options'     => array(
                'checkbox_label' => __('Su00ed, habilitar grupos de clientes', 'wp-pos'),
            ),
        ));
        
        $this->add_field('customers', 'required_customer_fields', array(
            'title'       => __('Campos obligatorios', 'wp-pos'),
            'description' => __('Campos que deben ser completados al crear un cliente.', 'wp-pos'),
            'type'        => 'select',
            'default'     => array('name', 'email'),
            'options'     => array(
                'multiple' => true,
                'choices'  => array(
                    'name'    => __('Nombre', 'wp-pos'),
                    'email'   => __('Email', 'wp-pos'),
                    'phone'   => __('Telu00e9fono', 'wp-pos'),
                    'address' => __('Direcciu00f3n', 'wp-pos'),
                    'company' => __('Empresa', 'wp-pos'),
                    'tax_id'  => __('ID Fiscal', 'wp-pos'),
                ),
            ),
        ));
    }

    /**
     * Registrar campos para el grupo 'integrations'
     *
     * @since 1.0.0
     */
    private function register_integrations_fields() {
        // Sección de Integraciones
        $this->add_field('integrations', 'section_integrations', array(
            'title'       => __('Integraciones con otros sistemas', 'wp-pos'),
            'description' => __('Configuración para integrar WP-POS con otros sistemas.', 'wp-pos'),
            'type'        => 'section',
        ));
        
        // Integración con pasarelas de pago
        $this->add_field('integrations', 'payment_gateway_integration', array(
            'title'       => __('Pasarelas de Pago', 'wp-pos'),
            'description' => __('Selecciona qué pasarelas de pago deseas habilitar.', 'wp-pos'),
            'type'        => 'checkboxes',
            'default'     => array('cash' => 'yes', 'card' => 'yes'),
            'options'     => array(
                'choices' => array(
                    'cash'       => __('Efectivo', 'wp-pos'),
                    'card'       => __('Tarjeta', 'wp-pos'),
                    'transfer'   => __('Transferencia bancaria', 'wp-pos'),
                    'mobile_pay' => __('Pago móvil', 'wp-pos'),
                ),
            ),
        ));
        
        // Solo mostrar configuración de WooCommerce si está activo
        if (defined('WP_POS_WOOCOMMERCE_ACTIVE') && WP_POS_WOOCOMMERCE_ACTIVE) {
            $this->add_field('integrations', 'section_woocommerce', array(
                'title'       => __('Integración con WooCommerce', 'wp-pos'),
                'description' => __('Configuración para la integración con WooCommerce.', 'wp-pos'),
                'type'        => 'section',
            ));
            
            $this->add_field('integrations', 'woocommerce_sync', array(
                'title'       => __('Sincronizar con WooCommerce', 'wp-pos'),
                'description' => __('Sincronizar productos, clientes y pedidos con WooCommerce.', 'wp-pos'),
                'type'        => 'checkbox',
                'default'     => 'yes',
                'options'     => array(
                    'checkbox_label' => __('Sí, sincronizar con WooCommerce', 'wp-pos'),
                ),
            ));
            
            $this->add_field('integrations', 'woocommerce_order_status', array(
                'title'       => __('Estado de pedido en WooCommerce', 'wp-pos'),
                'description' => __('Estado que tendrán los pedidos creados desde el POS.', 'wp-pos'),
                'type'        => 'select',
                'default'     => 'wc-completed',
                'options'     => array(
                    'choices' => $this->get_wc_order_statuses(),
                ),
            ));
        } else {
            // Mensaje informativo cuando WooCommerce no está activo
            $this->add_field('integrations', 'woocommerce_not_active_notice', array(
                'title'       => __('Integración con WooCommerce', 'wp-pos'),
                'description' => __('WooCommerce no está activo. Actívalo para habilitar las opciones de integración.', 'wp-pos'),
                'type'        => 'html',
                'html'        => '',
            ));
        }
        
        // Integración con sistemas externos
        $this->add_field('integrations', 'section_external', array(
            'title'       => __('Sistemas Externos', 'wp-pos'),
            'description' => __('Configuración para integrar con APIs externas.', 'wp-pos'),
            'type'        => 'section',
        ));
        
        $this->add_field('integrations', 'external_api_key', array(
            'title'       => __('Clave API', 'wp-pos'),
            'description' => __('Clave para acceso a APIs externas (opcional).', 'wp-pos'),
            'type'        => 'text',
            'default'     => '',
            'placeholder' => __('Ingresa tu clave API', 'wp-pos'),
            'class'       => 'regular-text',
        ));
    }

    /**
     * Registrar campos para el grupo 'advanced'
     *
     * @since 1.0.0
     */
    private function register_advanced_fields() {
        $this->add_field('advanced', 'debug_mode', array(
            'title'       => __('Modo debug', 'wp-pos'),
            'description' => __('Activar registro de depuraciu00f3n. Solo para desarrollo.', 'wp-pos'),
            'type'        => 'checkbox',
            'default'     => 'no',
            'options'     => array(
                'checkbox_label' => __('Su00ed, activar modo debug', 'wp-pos'),
            ),
        ));
        
        $this->add_field('advanced', 'cache_timeout', array(
            'title'       => __('Tiempo de cachu00e9 (segundos)', 'wp-pos'),
            'description' => __('Tiempo en segundos para mantener datos en cachu00e9.', 'wp-pos'),
            'type'        => 'number',
            'default'     => '3600',
            'options'     => array(
                'min' => 0,
                'max' => 86400, // 24 horas
                'step' => 60,
            ),
        ));
    }

    /**
     * Au00f1adir un campo de configuracib3n
     *
     * @since 1.0.0
     * @param string $group_id ID del grupo
     * @param string $field_id ID del campo
     * @param array $args Argumentos del campo
     */
    public function add_field($group_id, $field_id, $args = array()) {
        $field_id = sanitize_key($field_id);
        $group_id = sanitize_key($group_id);
        
        // Generar ID completo
        $full_id = $group_id . '_' . $field_id;
        
        // Au00f1adir campo
        $this->fields[$full_id] = array(
            'id'      => $field_id,
            'group'   => $group_id,
            'full_id' => $full_id,
        ) + $args;
    }

    /**
     * Obtener campos para un grupo
     *
     * @since 1.0.0
     * @param string $group_id ID del grupo
     * @return array Campos del grupo
     */
    public function get_settings_fields($group_id) {
        $group_fields = array();
        
        foreach ($this->fields as $full_id => $field) {
            if ($field['group'] === $group_id) {
                $group_fields[$full_id] = $field;
            }
        }
        
        return $group_fields;
    }

    /**
     * Obtener todos los campos
     *
     * @since 1.0.0
     * @return array Todos los campos
     */
    public function get_all_fields() {
        return $this->fields;
    }

    /**
     * Obtener valores por defecto para todos los campos
     *
     * @since 1.0.0
     * @param string $group_id ID del grupo (opcional)
     * @return array Valores por defecto
     */
    public function get_default_settings($group_id = '') {
        $defaults = array();
        
        foreach ($this->fields as $full_id => $field) {
            // Si se especifica grupo, filtrar solo ese grupo
            if (!empty($group_id) && $field['group'] !== $group_id) {
                continue;
            }
            
            $defaults[$full_id] = isset($field['default']) ? $field['default'] : '';
        }
        
        return $defaults;
    }

    /**
     * Renderizar un campo
     *
     * @since 1.0.0
     * @param array $args Argumentos del campo
     */
    public function render_field($args) {
        $this->renderer->render_field($args);
    }

    /**
     * Sanitizar configuraciones
     *
     * @since 1.0.0
     * @param array $input Datos a sanitizar
     * @return array Datos sanitizados
     */
    public function sanitize_settings($input) {
        return $this->sanitizer->sanitize($input, $this->fields);
    }

    /**
     * Probar una impresora
     *
     * @since 1.0.0
     * @param string $printer_type Tipo de impresora
     * @return true|WP_Error u00c9xito o error
     */
    public function test_printer($printer_type) {
        // Implementacid=F3n bu00e1sica, expandir segoFn necesidades
        if (empty($printer_type)) {
            return new WP_Error('printer_type_missing', __('Tipo de impresora no especificado.', 'wp-pos'));
        }
        
        // Comprobar si el tipo es vu00e1lido
        $valid_types = array('browser', 'thermal', 'network');
        if (!in_array($printer_type, $valid_types)) {
            return new WP_Error('invalid_printer_type', __('Tipo de impresora no vu00e1lido.', 'wp-pos'));
        }
        
        // Aquu00ed iru00eda la lu00f3gica de prueba
        
        return true;
    }

    /**
     * Obtener lista de clientes para selector
     *
     * @since 1.0.0
     * @return array Lista de clientes
     */
    private function get_customers_list() {
        $customers = array(
            '0' => __('Ninguno', 'wp-pos'),
        );
        
        // Obtener clientes si la funciu00f3n estu00e1 disponible
        if (function_exists('wp_pos_get_customers')) {
            $results = wp_pos_get_customers(array(
                'per_page' => 100,
                'orderby'  => 'name',
                'order'    => 'ASC',
            ));
            
            if (!empty($results['customers'])) {
                foreach ($results['customers'] as $customer) {
                    $customers[$customer['id']] = $customer['full_name'] . ' (' . $customer['email'] . ')';
                }
            }
        }
        
        return $customers;
    }

    /**
     * Obtener lista de monedas
     *
     * @since 1.0.0
     * @return array Lista de monedas
     */
    private function get_currencies_list() {
        return array(
            'USD' => __('Du00f3lar estadounidense ($)', 'wp-pos'),
            'EUR' => __('Euro (u20ac)', 'wp-pos'),
            'GBP' => __('Libra esterlina (u00a3)', 'wp-pos'),
            'ARS' => __('Peso argentino ($)', 'wp-pos'),
            'BRL' => __('Real brasileu00f1o (R$)', 'wp-pos'),
            'CAD' => __('Du00f3lar canadiense ($)', 'wp-pos'),
            'CLP' => __('Peso chileno ($)', 'wp-pos'),
            'CNY' => __('Yuan chino (u00a5)', 'wp-pos'),
            'COP' => __('Peso colombiano ($)', 'wp-pos'),
            'MXN' => __('Peso mexicano ($)', 'wp-pos'),
            'PEN' => __('Sol peruano (S/)', 'wp-pos'),
            'UYU' => __('Peso uruguayo ($U)', 'wp-pos'),
        );
    }

    /**
     * Obtener estados de pedido de WooCommerce
     *
     * @since 1.0.0
     * @return array Estados de pedido
     */
    private function get_wc_order_statuses() {
        $statuses = array(
            'wc-pending'    => __('Pendiente de pago', 'wp-pos'),
            'wc-processing' => __('Procesando', 'wp-pos'),
            'wc-on-hold'    => __('En espera', 'wp-pos'),
            'wc-completed'  => __('Completado', 'wp-pos'),
        );
        
        // Si WooCommerce estu00e1 activo, obtener estados reales
        if (function_exists('wc_get_order_statuses')) {
            return wc_get_order_statuses();
        }
        
        return $statuses;
    }
}
