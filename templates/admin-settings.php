<?php
/**
 * Plantilla de configuraciones
 *
 * @package WP-POS
 * @since 1.0.0
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Cargar header
wp_pos_template_header(array(
    'title' => __('Configuraciones', 'wp-pos'),
    'active_menu' => 'settings'
));

// Cargar estilos específicos para configuraciones
wp_enqueue_style('wp-pos-admin-settings', WP_POS_PLUGIN_URL . 'assets/css/admin-settings.css', array(), WP_POS_VERSION);
wp_enqueue_style('wp-pos-settings-enhanced', WP_POS_PLUGIN_URL . 'assets/css/wp-pos-settings-enhanced.css', array(), WP_POS_VERSION);

// Cargar script corrector de opciones
wp_enqueue_script('wp-pos-options-fix', WP_POS_PLUGIN_URL . 'modules/settings/assets/js/options-fix.js', array('jquery'), WP_POS_VERSION, true);

// Cargar script específico para configuraciones
wp_enqueue_script('wp-pos-admin-settings', WP_POS_PLUGIN_URL . 'assets/js/admin-settings.js', array('jquery', 'jquery-ui-tooltip'), WP_POS_VERSION, true);

// Obtener opciones actuales
$options = wp_pos_get_option();

// Asegurarnos de que $options sea un array
if (!is_array($options)) {
    $options = array();
}

// Definir valores predeterminados si no existen
$default_options = array(
    'business_name' => get_bloginfo('name'),
    'business_address' => '',
    'business_phone' => '',
    'business_email' => get_bloginfo('admin_email'),
    'business_logo' => '',
    'restrict_access' => 'yes',
    'enable_keyboard_shortcuts' => 'yes',
    'enable_barcode_scanner' => 'yes',
    'add_customer_to_sale' => 'optional',
    'default_tax_rate' => '0',
    'enable_discount' => 'yes',
    'default_payment_method' => 'cash',
    'receipt_template' => 'default',
    'receipt_logo' => '',
    'receipt_store_name' => get_bloginfo('name'),
    'receipt_store_address' => '',
    'receipt_store_phone' => '',
    'receipt_footer' => __('Gracias por su compra', 'wp-pos'),
    'print_automatically' => 'no',
    'currency' => 'USD',
    'currency_position' => 'left',
    'thousand_separator' => ',',
    'decimal_separator' => '.',
    'decimals' => 2,
    'products_per_page' => 20,
    'default_product_orderby' => 'title',
    'default_product_order' => 'ASC',
    'show_product_images' => 'yes',
    'show_categories_filter' => 'yes',
    'update_stock' => 'yes',
    'low_stock_threshold' => 2,
    'show_out_of_stock' => 'yes',
);

// Combinar los valores predeterminados con las opciones existentes
$options = array_merge($default_options, $options);

// Mostrar mensajes de notificacion si existen
settings_errors('wp_pos_settings');
?>

<div class="wrap wp-pos-settings-wrapper">
    <div class="wp-pos-settings-header">
        <div class="wp-pos-settings-header-primary">
            <h1><?php _e('Configuraciones del Punto de Venta', 'wp-pos'); ?></h1>
            <p><?php _e('Personaliza la configuración de tu Punto de Venta para adaptarlo a las necesidades de tu negocio.', 'wp-pos'); ?></p>
        </div>
        <div class="wp-pos-control-panel-secondary">
            <a href="<?php echo admin_url('admin.php?page=wp-pos'); ?>" class="wp-pos-back-button">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Volver al Dashboard', 'wp-pos'); ?>
            </a>
        </div>
    </div>
    
    <div class="wp-pos-settings-content">
        <form method="post" action="options.php" class="wp-pos-settings-form" id="wp-pos-settings-form">
            <?php
            // Usar el sistema estándar de opciones de WordPress
            settings_fields('wp_pos_options');
            ?>
            
            <div class="wp-pos-settings-tabs">
                <ul class="wp-pos-tabs-nav">
                    <li class="active"><a href="#general"><span class="dashicons dashicons-admin-generic"></span> <?php _e('General', 'wp-pos'); ?></a></li>
                    <li><a href="#sales"><span class="dashicons dashicons-cart"></span> <?php _e('Ventas', 'wp-pos'); ?></a></li>
                    <li><a href="#receipts"><span class="dashicons dashicons-media-text"></span> <?php _e('Recibos', 'wp-pos'); ?></a></li>
                    <li><a href="#interface"><span class="dashicons dashicons-admin-appearance"></span> <?php _e('Interfaz', 'wp-pos'); ?></a></li>
                </ul>
                
                <div class="wp-pos-tabs-content">
                    <!-- Pestaña General -->
                    <div id="general" class="wp-pos-tab-pane active">
                        <h3><?php _e('Información del Negocio', 'wp-pos'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="business_name"><?php _e('Nombre del negocio', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="wp_pos_options[business_name]" id="business_name" 
                                           value="<?php echo esc_attr($options['business_name']); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="business_address"><?php _e('Dirección', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <textarea name="business_address" id="business_address" rows="3" 
                                              class="regular-text"><?php echo esc_textarea($options['business_address']); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="business_phone"><?php _e('Teléfono', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="business_phone" id="business_phone" 
                                           value="<?php echo esc_attr($options['business_phone']); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="business_email"><?php _e('Email', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <input type="email" name="business_email" id="business_email" 
                                           value="<?php echo esc_attr($options['business_email']); ?>" class="regular-text">
                                </td>
                            </tr>
                        </table>
                        
                        <h3><?php _e('Configuración General', 'wp-pos'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="restrict_access"><?php _e('Restringir acceso', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="restrict_access" id="restrict_access" 
                                               value="yes" <?php checked($options['restrict_access'], 'yes'); ?>>
                                        <?php _e('Solo permitir acceso a usuarios con permisos específicos', 'wp-pos'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="enable_keyboard_shortcuts"><?php _e('Atajos de teclado', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_keyboard_shortcuts" id="enable_keyboard_shortcuts" 
                                               value="yes" <?php checked($options['enable_keyboard_shortcuts'], 'yes'); ?>>
                                        <?php _e('Habilitar atajos de teclado para acciones comunes', 'wp-pos'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="enable_barcode_scanner"><?php _e('Escáner de códigos', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_barcode_scanner" id="enable_barcode_scanner" 
                                               value="yes" <?php checked($options['enable_barcode_scanner'], 'yes'); ?>>
                                        <?php _e('Habilitar soporte para escáner de códigos de barras', 'wp-pos'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Pestaña Ventas -->
                    <div id="sales" class="wp-pos-tab-pane">
                        <h3><?php _e('Opciones de Venta', 'wp-pos'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="add_customer_to_sale"><?php _e('Cliente en venta', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <select name="add_customer_to_sale" id="add_customer_to_sale">
                                        <option value="required" <?php selected($options['add_customer_to_sale'], 'required'); ?>>
                                            <?php _e('Requerido', 'wp-pos'); ?>
                                        </option>
                                        <option value="optional" <?php selected($options['add_customer_to_sale'], 'optional'); ?>>
                                            <?php _e('Opcional', 'wp-pos'); ?>
                                        </option>
                                        <option value="hidden" <?php selected($options['add_customer_to_sale'], 'hidden'); ?>>
                                            <?php _e('Oculto', 'wp-pos'); ?>
                                        </option>
                                    </select>
                                    <p class="description"><?php _e('Define cómo se manejará la asignación de clientes durante la venta', 'wp-pos'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="default_tax_rate"><?php _e('Tasa de impuestos predeterminada', 'wp-pos'); ?> (%)</label>
                                </th>
                                <td>
                                    <input type="text" name="default_tax_rate" id="default_tax_rate" 
                                           value="<?php echo esc_attr($options['default_tax_rate']); ?>" class="small-text">
                                    <p class="description"><?php _e('Tasa de impuestos que se aplicará por defecto a todas las ventas', 'wp-pos'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="enable_discount"><?php _e('Habilitar descuentos', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_discount" id="enable_discount" 
                                               value="yes" <?php checked($options['enable_discount'], 'yes'); ?>>
                                        <?php _e('Permitir descuentos en ventas', 'wp-pos'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="default_payment_method"><?php _e('Método de pago predeterminado', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <select name="default_payment_method" id="default_payment_method">
                                        <option value="cash" <?php selected($options['default_payment_method'], 'cash'); ?>>
                                            <?php _e('Efectivo', 'wp-pos'); ?>
                                        </option>
                                        <option value="card" <?php selected($options['default_payment_method'], 'card'); ?>>
                                            <?php _e('Tarjeta', 'wp-pos'); ?>
                                        </option>
                                        <option value="mixed" <?php selected($options['default_payment_method'], 'mixed'); ?>>
                                            <?php _e('Mixto', 'wp-pos'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Pestaña Recibos -->
                    <div id="receipts" class="wp-pos-tab-pane">
                        <h3><?php _e('Opciones de Recibo', 'wp-pos'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="receipt_template"><?php _e('Plantilla de recibo', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <select name="receipt_template" id="receipt_template">
                                        <option value="default" <?php selected($options['receipt_template'], 'default'); ?>>
                                            <?php _e('Predeterminada', 'wp-pos'); ?>
                                        </option>
                                        <option value="compact" <?php selected($options['receipt_template'], 'compact'); ?>>
                                            <?php _e('Compacta', 'wp-pos'); ?>
                                        </option>
                                        <option value="detailed" <?php selected($options['receipt_template'], 'detailed'); ?>>
                                            <?php _e('Detallada', 'wp-pos'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="receipt_store_name"><?php _e('Nombre en recibo', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="receipt_store_name" id="receipt_store_name" 
                                           value="<?php echo esc_attr($options['receipt_store_name']); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="receipt_store_address"><?php _e('Dirección en recibo', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <textarea name="receipt_store_address" id="receipt_store_address" rows="3" 
                                              class="regular-text"><?php echo esc_textarea($options['receipt_store_address']); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="receipt_store_phone"><?php _e('Teléfono en recibo', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="receipt_store_phone" id="receipt_store_phone" 
                                           value="<?php echo esc_attr($options['receipt_store_phone']); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="receipt_footer"><?php _e('Pie de recibo', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <textarea name="receipt_footer" id="receipt_footer" rows="3" 
                                              class="regular-text"><?php echo esc_textarea($options['receipt_footer']); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="print_automatically"><?php _e('Impresión automática', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="print_automatically" id="print_automatically" 
                                               value="yes" <?php checked($options['print_automatically'], 'yes'); ?>>
                                        <?php _e('Imprimir recibo automáticamente al finalizar venta', 'wp-pos'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Pestaña Interfaz -->
                    <div id="interface" class="wp-pos-tab-pane">
                        <h3><?php _e('Opciones de Interfaz', 'wp-pos'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="products_per_page"><?php _e('Productos por página', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <input type="number" name="products_per_page" id="products_per_page" 
                                           value="<?php echo esc_attr($options['products_per_page']); ?>" min="10" max="100" step="5" class="small-text">
                                    <p class="description"><?php _e('Cantidad de productos a mostrar por página en el catálogo', 'wp-pos'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="default_product_orderby"><?php _e('Ordenar productos por', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <select name="default_product_orderby" id="default_product_orderby">
                                        <option value="title" <?php selected($options['default_product_orderby'], 'title'); ?>>
                                            <?php _e('Título', 'wp-pos'); ?>
                                        </option>
                                        <option value="date" <?php selected($options['default_product_orderby'], 'date'); ?>>
                                            <?php _e('Fecha', 'wp-pos'); ?>
                                        </option>
                                        <option value="price" <?php selected($options['default_product_orderby'], 'price'); ?>>
                                            <?php _e('Precio', 'wp-pos'); ?>
                                        </option>
                                        <option value="popularity" <?php selected($options['default_product_orderby'], 'popularity'); ?>>
                                            <?php _e('Popularidad', 'wp-pos'); ?>
                                        </option>
                                    </select>
                                    
                                    <select name="default_product_order" id="default_product_order" style="margin-left: 10px;">
                                        <option value="ASC" <?php selected($options['default_product_order'], 'ASC'); ?>>
                                            <?php _e('Ascendente', 'wp-pos'); ?>
                                        </option>
                                        <option value="DESC" <?php selected($options['default_product_order'], 'DESC'); ?>>
                                            <?php _e('Descendente', 'wp-pos'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="show_product_images"><?php _e('Imágenes de producto', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="show_product_images" id="show_product_images" 
                                               value="yes" <?php checked($options['show_product_images'], 'yes'); ?>>
                                        <?php _e('Mostrar imágenes de productos en la lista', 'wp-pos'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="show_categories_filter"><?php _e('Filtro de categorías', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="show_categories_filter" id="show_categories_filter" 
                                               value="yes" <?php checked($options['show_categories_filter'], 'yes'); ?>>
                                        <?php _e('Mostrar filtro de categorías de productos', 'wp-pos'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="update_stock"><?php _e('Actualización de stock', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="update_stock" id="update_stock" 
                                               value="yes" <?php checked($options['update_stock'], 'yes'); ?>>
                                        <?php _e('Actualizar stock automáticamente al completar venta', 'wp-pos'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="low_stock_threshold"><?php _e('Umbral de stock bajo', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <input type="number" name="low_stock_threshold" id="low_stock_threshold" 
                                           value="<?php echo esc_attr($options['low_stock_threshold']); ?>" min="0" step="1" class="small-text">
                                    <p class="description"><?php _e('Cantidad a partir de la cual se considerará que un producto tiene stock bajo', 'wp-pos'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="show_out_of_stock"><?php _e('Productos sin stock', 'wp-pos'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="show_out_of_stock" id="show_out_of_stock" 
                                               value="yes" <?php checked($options['show_out_of_stock'], 'yes'); ?>>
                                        <?php _e('Mostrar productos sin stock en la lista', 'wp-pos'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="wp-pos-submit-panel">
                <?php 
                // Usando el sistema estándar de botones de WordPress pero con nuestra clase de estilo
                echo '<input type="submit" name="submit" id="submit" class="wp-pos-save-button" value="' . esc_attr__('Guardar cambios', 'wp-pos') . '" />';
                ?>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Manejo de las pestañas
    $('.wp-pos-tabs-nav a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Activar pestaña seleccionada
        $('.wp-pos-tabs-nav li').removeClass('active');
        $(this).parent().addClass('active');
        
        // Mostrar contenido de la pestaña seleccionada
        $('.wp-pos-tab-pane').removeClass('active').hide();
        $(target).addClass('active').fadeIn(300);
        
        // Guardar pestaña activa en localStorage
        localStorage.setItem('wp_pos_active_settings_tab', target);
    });
    
    // Cargar pestaña guardada o usar la primera por defecto
    var activeTab = localStorage.getItem('wp_pos_active_settings_tab');
    if (activeTab && $(activeTab).length) {
        $('.wp-pos-tabs-nav a[href="' + activeTab + '"]').trigger('click');
    } else {
        $('.wp-pos-tabs-nav li:first-child a').trigger('click');
    }
});
</script>

<?php
// Cargar footer
wp_pos_template_footer();
