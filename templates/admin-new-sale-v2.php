<?php
/**
 * Plantilla de nueva venta - Versiu00f3n 2
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluir recursos necesarios
wp_enqueue_style('wp-pos-enhanced-ui', WP_POS_PLUGIN_URL . 'assets/css/wp-pos-enhanced-ui.css', array(), WP_POS_VERSION);

// Crear una carpeta para los nuevos archivos si no existe
if (!file_exists(WP_POS_PLUGIN_DIR . 'templates/js')) {
    mkdir(WP_POS_PLUGIN_DIR . 'templates/js', 0755, true);
}
if (!file_exists(WP_POS_PLUGIN_DIR . 'templates/css')) {
    mkdir(WP_POS_PLUGIN_DIR . 'templates/css', 0755, true);
}

// Registrar y cargar los nuevos scripts y estilos
wp_enqueue_style('wp-pos-new-sale-v2', WP_POS_PLUGIN_URL . 'templates/css/new-sale-v2.css', array('wp-pos-enhanced-ui'), WP_POS_VERSION);
wp_enqueue_script('wp-pos-new-sale-v2', WP_POS_PLUGIN_URL . 'templates/js/new-sale-v2.js', array('jquery'), WP_POS_VERSION, true);

// Crear nonce para AJAX
wp_localize_script('wp-pos-new-sale-v2', 'wp_pos_data', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('wp_pos_nonce'),
    'customer_nonce' => wp_create_nonce('wp_pos_customers_nonce'),
    'product_nonce' => wp_create_nonce('wp_pos_products_nonce'),
    'service_nonce' => wp_create_nonce('wp_pos_services_nonce'),
    'texts' => array(
        'anonymous_customer' => __('Cliente anónimo', 'wp-pos'),
        'search_customers' => __('Buscar clientes...', 'wp-pos'),
        'search_products' => __('Buscar productos...', 'wp-pos'),
        'no_results' => __('No se encontraron resultados', 'wp-pos'),
        'searching' => __('Buscando...', 'wp-pos'),
        'error' => __('Error al realizar la búsqueda', 'wp-pos'),
        'add_product' => __('Añadir producto', 'wp-pos'),
        'product_added' => __('Producto añadido', 'wp-pos'),
        'service_added' => __('Servicio añadido', 'wp-pos'),
        'remove_product' => __('Eliminar', 'wp-pos'),
        'empty_cart' => __('No hay productos en la venta', 'wp-pos'),
        'save_sale' => __('Guardar venta', 'wp-pos'),
        'processing' => __('Procesando...', 'wp-pos'),
        'sale_saved' => __('Venta guardada correctamente', 'wp-pos'),
        'error_saving' => __('Error al guardar la venta', 'wp-pos'),
        'products_section' => __('Productos', 'wp-pos'),
        'services_section' => __('Servicios', 'wp-pos'),
    )
));

// Obtener configuración
$options = wp_pos_get_option();

// Cargar header
wp_pos_template_header(array(
    'title' => __('Nueva Venta (V2)', 'wp-pos'),
    'active_menu' => 'new-sale'
));

// Productos
$controller = WP_POS_Products_Controller::get_instance();
?>

<div class="wp-pos-container">
    <!-- Encabezado administrativo mejorado -->
    <div class="wp-pos-admin-header">
        <h1><?php _e('Nueva Venta', 'wp-pos'); ?></h1>
        <p><?php _e('Gestiona tus ventas de productos y servicios de forma sencilla y rápida.', 'wp-pos'); ?></p>
    </div>
    
    <!-- Mensajes de error/u00e9xito -->
    <div id="wp-pos-messages" class="wp-pos-messages"></div>
    
    <form id="wp-pos-new-sale-form" class="wp-pos-form" method="post">
        <!-- Campo oculto para identificar el formulario -->
        <input type="hidden" name="wp_pos_process_sale_direct" value="1">
        <input type="hidden" name="wp_pos_sale_data" id="wp_pos_sale_data" value="">
        <?php wp_nonce_field('wp_pos_process_sale_direct', 'wp_pos_sale_nonce'); ?>
        
        <div class="wp-pos-layout">
            <!-- Panel izquierdo: Datos de venta y carrito -->
            <div class="wp-pos-panel wp-pos-panel-left">
                <!-- Sección de Información de venta -->
                <div class="wp-pos-card">
                    <div class="wp-pos-card-header">
                        <h3><?php _e('Información de la venta', 'wp-pos'); ?></h3>
                    </div>
                    <div class="wp-pos-card-body">
                        <div class="wp-pos-form-grid">
                            <!-- Cliente -->
                            <div class="wp-pos-form-group">
                                <label for="wp-pos-customer-display"><?php _e('Cliente', 'wp-pos'); ?></label>
                                <div class="wp-pos-input-group">
                                    <input type="hidden" id="wp-pos-customer" name="customer_id" value="0">
                                    <input type="text" id="wp-pos-customer-display" class="wp-pos-input" value="<?php _e('Cliente anónimo', 'wp-pos'); ?>" readonly>
                                    <button type="button" id="wp-pos-customer-select" class="wp-pos-button wp-pos-button-icon">
                                        <span class="dashicons dashicons-search"></span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Fecha -->
                            <div class="wp-pos-form-group">
                                <label for="wp-pos-sale-date"><?php _e('Fecha', 'wp-pos'); ?></label>
                                <input type="date" id="wp-pos-sale-date" name="sale_date" value="<?php echo esc_attr(date_i18n('Y-m-d', current_time('timestamp'))); ?>" class="wp-pos-input">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de productos -->
                <div class="wp-pos-card wp-pos-cart-card">
                    <div class="wp-pos-card-header">
                        <h3><?php _e('Items de la venta', 'wp-pos'); ?></h3>
                    </div>
                    <div class="wp-pos-card-body">
                        <table class="wp-pos-cart-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Producto', 'wp-pos'); ?></th>
                                    <th><?php _e('Cantidad', 'wp-pos'); ?></th>
                                    <th><?php _e('Precio', 'wp-pos'); ?></th>
                                    <th><?php _e('Total', 'wp-pos'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="wp-pos-cart-items">
                                <!-- Los productos se añaden dinámicamente aquí -->
                                <tr class="wp-pos-empty-cart">
                                    <td colspan="5"><?php _e('No hay productos en la venta', 'wp-pos'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="wp-pos-card-footer">
                        <div class="wp-pos-totals">
                            <div class="wp-pos-totals-row">
                                <span><?php _e('Subtotal', 'wp-pos'); ?></span>
                                <span id="wp-pos-subtotal">0.00</span>
                            </div>
                            <div class="wp-pos-totals-row">
                                <span><?php _e('Impuestos', 'wp-pos'); ?></span>
                                <span id="wp-pos-tax">0.00</span>
                            </div>
                            <div class="wp-pos-totals-row wp-pos-totals-total">
                                <span><?php _e('Total', 'wp-pos'); ?></span>
                                <span id="wp-pos-total">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informacion de pago -->
                <div class="wp-pos-card">
                    <div class="wp-pos-card-header">
                        <h3><?php _e('Información de pago', 'wp-pos'); ?></h3>
                    </div>
                    <div class="wp-pos-card-body">
                        <div class="wp-pos-form-group">
                            <label for="wp-pos-payment-method"><?php _e('Metodo de pago', 'wp-pos'); ?></label>
                            <select id="wp-pos-payment-method" name="payment_method" class="wp-pos-input">
                                <option value="cash"><?php _e('Efectivo', 'wp-pos'); ?></option>
                                <option value="card"><?php _e('Tarjeta', 'wp-pos'); ?></option>
                                <option value="transfer"><?php _e('Transferencia', 'wp-pos'); ?></option>
                                <option value="other"><?php _e('Otro', 'wp-pos'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Detalles de pago en efectivo -->
                        <div id="wp-pos-cash-details" class="wp-pos-form-grid">
                            <div class="wp-pos-form-group">
                                <label for="wp-pos-amount-received"><?php _e('Importe recibido', 'wp-pos'); ?></label>
                                <input type="number" id="wp-pos-amount-received" name="amount_received" value="0" step="0.01" min="0" class="wp-pos-input">
                            </div>
                            <div class="wp-pos-form-group">
                                <label for="wp-pos-change"><?php _e('Cambio', 'wp-pos'); ?></label>
                                <input type="text" id="wp-pos-change" value="0.00" readonly class="wp-pos-input">
                            </div>
                        </div>
                    </div>
                    <div class="wp-pos-card-footer">
                        <button type="submit" id="wp-pos-save-sale" class="wp-pos-button wp-pos-button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php _e('Procesar venta', 'wp-pos'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Panel derecho: Búsqueda y agregado de productos/servicios -->
            <div class="wp-pos-panel wp-pos-panel-right">
                <!-- Pestaña de navegación -->
                <div class="wp-pos-tabs">
                    <div class="wp-pos-tab wp-pos-tab-active" data-tab="products">
                        <span class="dashicons dashicons-products"></span>
                        <?php _e('Productos', 'wp-pos'); ?>
                    </div>
                    <div class="wp-pos-tab" data-tab="services">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Servicios', 'wp-pos'); ?>
                    </div>
                </div>
                
                <!-- Tabs de productos -->
                <div class="wp-pos-card wp-pos-tab-content wp-pos-tab-content-active" id="wp-pos-tab-products">
                    <div class="wp-pos-card-header">
                        <h3><?php _e('Añadir productos', 'wp-pos'); ?></h3>
                    </div>
                    <div class="wp-pos-card-body">
                        <div class="wp-pos-search-box">
                            <input type="text" id="wp-pos-product-search" class="wp-pos-input" placeholder="<?php esc_attr_e('Buscar productos por nombre o SKU...', 'wp-pos'); ?>">
                            <div id="wp-pos-product-results" class="wp-pos-search-results"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs de servicios -->
                <div class="wp-pos-card wp-pos-tab-content" id="wp-pos-tab-services">
                    <div class="wp-pos-card-header">
                        <h3><?php _e('Añadir servicios', 'wp-pos'); ?></h3>
                    </div>
                    <div class="wp-pos-card-body">
                        <div class="wp-pos-search-box">
                            <input type="text" id="wp-pos-service-search" class="wp-pos-input" placeholder="<?php esc_attr_e('Buscar servicios...', 'wp-pos'); ?>">
                            <div id="wp-pos-service-results" class="wp-pos-search-results"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal de búsqueda de clientes -->
<div id="wp-pos-customer-modal" class="wp-pos-modal">
    <div class="wp-pos-modal-content">
        <div class="wp-pos-modal-header">
            <h3><?php _e('Seleccionar cliente', 'wp-pos'); ?></h3>
            <button type="button" class="wp-pos-modal-close">&times;</button>
        </div>
        <div class="wp-pos-modal-body">
            <div class="wp-pos-search-box">
                <input type="text" id="wp-pos-customer-search" class="wp-pos-input" placeholder="<?php esc_attr_e('Buscar por nombre, email o teléfono...', 'wp-pos'); ?>">
                <div id="wp-pos-customer-results" class="wp-pos-search-results"></div>
            </div>
        </div>
    </div>
</div>

<?php
// Cargar footer
wp_pos_template_footer();
