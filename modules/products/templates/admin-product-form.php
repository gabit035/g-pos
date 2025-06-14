<?php
/**
 * Plantilla para el formulario de productos
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

// Prevencion de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$products_table = $wpdb->prefix . 'pos_products';
$meta_table = $wpdb->prefix . 'pos_product_meta';

// Verificar si es edición o nuevo producto
$product_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
$edit_mode = $product_id > 0;
$title = $edit_mode ? __('Editar Producto', 'wp-pos') : __('Añadir Nuevo Producto', 'wp-pos');

// Definir el símbolo de moneda (simplificado sin depender de WooCommerce)
$currency_symbol = '$';

// Si es edición, obtener datos del producto
$product = array(
    'id' => 0,
    'name' => '',
    'sku' => '',
    'description' => '',
    'regular_price' => '',
    'sale_price' => '',
    'manage_stock' => 1,
    'stock_quantity' => 0,
    'stock_status' => 'instock',
    'thumbnail_id' => 0,
    'thumbnail_url' => ''
);

if ($edit_mode) {
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $products_table WHERE id = %d", $product_id), ARRAY_A);
    if ($result) {
        $product = array_merge($product, $result);
        
        // Obtener imagen si existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") == $meta_table) {
            $thumbnail_id = $wpdb->get_var(
                $wpdb->prepare("SELECT meta_value FROM $meta_table WHERE product_id = %d AND meta_key = 'thumbnail_id'", $product_id)
            );
            
            if ($thumbnail_id) {
                $product['thumbnail_id'] = $thumbnail_id;
                $product['thumbnail_url'] = wp_get_attachment_url($thumbnail_id);
            }
        }
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si se ha enviado el formulario principal
    if (isset($_POST['submit_product']) || isset($_POST['product_name'])) {
        // Verificar nonce de seguridad
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wp_pos_save_product')) {
            echo '<div class="notice notice-error"><p>' . __('Error de seguridad: el token de verificación no es válido.', 'wp-pos') . '</p></div>';
        } else {
            // Preparar datos del producto
            $product_data = array(
                'name' => isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '',
                'sku' => isset($_POST['product_sku']) ? sanitize_text_field($_POST['product_sku']) : '',
                'description' => isset($_POST['product_description']) ? wp_kses_post($_POST['product_description']) : '',
                'purchase_price' => isset($_POST['product_purchase_price']) ? floatval($_POST['product_purchase_price']) : 0,
                'regular_price' => isset($_POST['product_regular_price']) ? floatval($_POST['product_regular_price']) : 0,
                'sale_price' => isset($_POST['product_sale_price']) ? floatval($_POST['product_sale_price']) : 0,
                'manage_stock' => isset($_POST['product_manage_stock']) ? 1 : 0,
                'stock_quantity' => isset($_POST['product_stock_quantity']) ? absint($_POST['product_stock_quantity']) : 0,
                'stock_status' => isset($_POST['product_stock_status']) ? sanitize_text_field($_POST['product_stock_status']) : 'instock',
            );
            
            // Imagen del producto
            $thumbnail_id = isset($_POST['product_image_id']) ? absint($_POST['product_image_id']) : 0;
            
            // Verificar datos obligatorios
            if (empty($product_data['name'])) {
                echo '<div class="notice notice-error"><p>' . __('Error: El nombre del producto es obligatorio.', 'wp-pos') . '</p></div>';
            } else {
                $success = false;
                
                if ($edit_mode) {
                    // Guardar el stock actual antes de actualizar
                    $current_stock = $wpdb->get_var($wpdb->prepare(
                        "SELECT stock_quantity FROM $products_table WHERE id = %d",
                        $product_id
                    ));
                    
                    // Si el stock no ha cambiado, no lo incluimos en la actualización para evitar duplicación
                    if (isset($current_stock) && $current_stock == $product_data['stock_quantity']) {
                        // Eliminar el campo stock_quantity para evitar actualizaciones innecesarias
                        unset($product_data['stock_quantity']);
                    }
                    
                    // Actualizar producto existente
                    $result = $wpdb->update($products_table, $product_data, array('id' => $product_id));
                    $success = ($result !== false);
                } else {
                    // Crear nuevo producto
                    $result = $wpdb->insert($products_table, $product_data);
                    if ($result) {
                        $product_id = $wpdb->insert_id;
                        $success = true;
                    }
                }
                
                // Guardar metadatos de imagen si existe
                if ($success && $product_id > 0 && $thumbnail_id > 0) {
                    // Verificar si ya existe el metadato
                    $meta_exists = $wpdb->get_var(
                        $wpdb->prepare("SELECT meta_id FROM $meta_table WHERE product_id = %d AND meta_key = 'thumbnail_id'", $product_id)
                    );
                    
                    if ($meta_exists) {
                        $wpdb->update(
                            $meta_table,
                            array('meta_value' => $thumbnail_id),
                            array('product_id' => $product_id, 'meta_key' => 'thumbnail_id')
                        );
                    } else {
                        $wpdb->insert(
                            $meta_table,
                            array(
                                'product_id' => $product_id,
                                'meta_key' => 'thumbnail_id',
                                'meta_value' => $thumbnail_id
                            )
                        );
                    }
                }
                
                if ($success) {
                    // Redirigir a la lista de productos
                    $redirect_url = wp_pos_safe_add_query_arg(array(
                        'page' => 'wp-pos-products',
                        'product_saved' => '1'
                    ), admin_url('admin.php'));
                    
                    // Forzar la redirección
                    echo "<script>window.location.href = '" . esc_url_raw($redirect_url) . "';</script>";
                    exit;
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Error al guardar el producto.', 'wp-pos') . '</p></div>';
                }
            }
        }
    }
}

?>
<div class="wrap">
    <!-- Header con degradado igual que en el Dashboard -->
    <div class="wp-pos-header">
        <h1><span class="dashicons dashicons-tag"></span> <?php echo esc_html($title); ?></h1>
        <p><?php _e('Completa la información del producto para agregarlo al inventario.', 'wp-pos'); ?></p>
    </div>
    
    <div class="wp-pos-container">
        <!-- Botón de volver integrado en el diseño -->
        <div class="wp-pos-return-link">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-products')); ?>" class="wp-pos-view-all-button">
                <span class="wp-pos-view-all-icon">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                </span>
                <span class="wp-pos-view-all-text"><?php esc_html_e('Volver a la lista de productos', 'wp-pos'); ?></span>
            </a>
        </div>
    
    <form method="post" action="<?php echo admin_url('admin.php?page=wp-pos-products&action=' . ($edit_mode ? 'edit&product_id=' . $product_id : 'add')); ?>" id="wp-pos-product-form" enctype="multipart/form-data">
        <?php wp_nonce_field('wp_pos_save_product'); ?>
        <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
        
        <div id="poststuff" class="wp-pos-metabox-holder">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox wp-pos-dashboard-card">
                        <div class="wp-pos-dashboard-card-header">
                            <h2 class="wp-pos-dashboard-card-title"><span class="dashicons dashicons-info"></span> <?php esc_html_e('Información Básica', 'wp-pos'); ?></h2>
                        </div>
                        <div class="inside wp-pos-dashboard-card-body">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="product_name"><?php esc_html_e('Nombre del producto', 'wp-pos'); ?><span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="text" name="product_name" id="product_name" value="<?php echo esc_attr($product['name']); ?>" class="regular-text" required>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="product_description"><?php esc_html_e('Descripción', 'wp-pos'); ?></label>
                                    </th>
                                    <td>
                                        <textarea name="product_description" id="product_description" rows="5" class="large-text"><?php echo esc_textarea($product['description']); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="postbox wp-pos-dashboard-card products">
                        <div class="wp-pos-dashboard-card-header">
                            <h2 class="wp-pos-dashboard-card-title"><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e('Precios', 'wp-pos'); ?></h2>
                        </div>
                        <div class="inside wp-pos-dashboard-card-body">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="product_purchase_price"><?php esc_html_e('Precio de compra', 'wp-pos'); ?><span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <div class="price-field">
                                            <span class="price-symbol"><?php echo esc_html($currency_symbol); ?></span>
                                            <input type="number" name="product_purchase_price" id="product_purchase_price" step="0.01" min="0" value="<?php echo esc_attr(isset($product['purchase_price']) ? $product['purchase_price'] : 0); ?>" required>
                                        </div>
                                        <p class="description"><?php esc_html_e('El costo al que compraste este producto. Necesario para calcular la ganancia.', 'wp-pos'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="product_regular_price"><?php esc_html_e('Precio regular', 'wp-pos'); ?><span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <div class="price-field">
                                            <span class="price-symbol"><?php echo esc_html($currency_symbol); ?></span>
                                            <input type="number" name="product_regular_price" id="product_regular_price" step="0.01" min="0" value="<?php echo esc_attr($product['regular_price']); ?>" required>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="product_sale_price"><?php esc_html_e('Precio rebajado', 'wp-pos'); ?></label>
                                    </th>
                                    <td>
                                        <div class="price-field">
                                            <span class="price-symbol"><?php echo esc_html($currency_symbol); ?></span>
                                            <input type="number" name="product_sale_price" id="product_sale_price" step="0.01" min="0" value="<?php echo esc_attr($product['sale_price']); ?>" class="regular-text">
                                        </div>
                                        <p class="description"><?php esc_html_e('Dejar en cero para usar solo el precio regular', 'wp-pos'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="postbox wp-pos-dashboard-card products">
                        <div class="wp-pos-dashboard-card-header">
                            <h2 class="wp-pos-dashboard-card-title"><span class="dashicons dashicons-archive"></span> <?php esc_html_e('Inventario', 'wp-pos'); ?></h2>
                        </div>
                        <div class="inside wp-pos-dashboard-card-body">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="product_sku"><?php esc_html_e('SKU', 'wp-pos'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="product_sku" id="product_sku" value="<?php echo esc_attr($product['sku']); ?>" class="regular-text">
                                        <p class="description"><?php esc_html_e('Código único de identificación del producto', 'wp-pos'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="product_manage_stock"><?php esc_html_e('Gestionar stock', 'wp-pos'); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="product_manage_stock" id="product_manage_stock" value="yes" <?php checked($product['manage_stock'], 1); ?>>
                                            <span><?php esc_html_e('Activar gestión de inventario', 'wp-pos'); ?></span>
                                        </label>
                                        <p class="description"><?php esc_html_e('Activa esta opción para llevar un control del inventario', 'wp-pos'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr class="stock-options" style="<?php echo $product['manage_stock'] ? '' : 'display:none;'; ?>">
                                    <th scope="row">
                                        <label for="product_stock_quantity"><?php esc_html_e('Cantidad en stock', 'wp-pos'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" name="product_stock_quantity" id="product_stock_quantity" step="1" min="0" value="<?php echo esc_attr($product['stock_quantity']); ?>" class="small-text">
                                    </td>
                                </tr>
                                
                                <tr class="stock-status-field" style="<?php echo !$product['manage_stock'] ? '' : 'display:none;'; ?>">
                                    <th scope="row">
                                        <label for="product_stock_status"><?php esc_html_e('Estado del stock', 'wp-pos'); ?></label>
                                    </th>
                                    <td>
                                        <select name="product_stock_status" id="product_stock_status" class="regular-text">
                                            <option value="instock" <?php selected($product['stock_status'], 'instock'); ?>><?php esc_html_e('En stock', 'wp-pos'); ?></option>
                                            <option value="outofstock" <?php selected($product['stock_status'], 'outofstock'); ?>><?php esc_html_e('Agotado', 'wp-pos'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div><!-- /#post-body-content -->
                
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox wp-pos-dashboard-card products">
                        <div class="wp-pos-dashboard-card-header">
                            <h2 class="wp-pos-dashboard-card-title"><span class="dashicons dashicons-saved"></span> <?php esc_html_e('Publicar', 'wp-pos'); ?></h2>
                        </div>
                        <div class="inside wp-pos-dashboard-card-body">
                            <div id="submitpost" class="submitbox">
                                <div class="misc-publishing-actions">
                                    <div class="misc-pub-section">
                                        <span class="dashicons dashicons-yes"></span> <?php esc_html_e('Publicar', 'wp-pos'); ?>
                                    </div>
                                </div>
                                <div id="major-publishing-actions">
                                    <div id="publishing-action">
                                        <div class="submit-box">
                                            <button type="submit" name="submit_product" id="submit-product" class="wp-pos-action-button primary">
                                                <span class="dashicons dashicons-saved"></span>
                                                <?php echo esc_attr($edit_mode ? __('Actualizar Producto', 'wp-pos') : __('Publicar Producto', 'wp-pos')); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="postbox wp-pos-dashboard-card products">
                        <div class="wp-pos-dashboard-card-header">
                            <h2 class="wp-pos-dashboard-card-title"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e('Imagen del Producto', 'wp-pos'); ?></h2>
                        </div>
                        <div class="inside wp-pos-dashboard-card-body">
                            <div class="product-image-container">
                                <div class="product-image-preview">
                                    <?php if (!empty($product['thumbnail_url'])) : ?>
                                        <img src="<?php echo esc_url($product['thumbnail_url']); ?>" alt="" style="max-width:100%;height:auto;">
                                    <?php else : ?>
                                        <div class="product-no-image"><?php esc_html_e('Sin imagen', 'wp-pos'); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <input type="hidden" name="product_image_id" id="product_image_id" value="<?php echo esc_attr($product['thumbnail_id']); ?>">
                                <input type="hidden" name="product_image_url" id="product_image_url" value="<?php echo esc_attr($product['thumbnail_url']); ?>">
                                
                                <div class="product-image-actions">
                                    <button type="button" class="wp-pos-action-button secondary" id="product-image-upload">
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php esc_html_e('Seleccionar imagen', 'wp-pos'); ?>
                                    </button>
                                    
                                    <button type="button" class="wp-pos-action-button danger" id="product-image-remove" <?php echo empty($product['thumbnail_url']) ? 'style="display:none;"' : ''; ?>>
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php esc_html_e('Eliminar imagen', 'wp-pos'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /#postbox-container-1 -->
            </div><!-- /#post-body -->
            <br class="clear">
        </div><!-- /#poststuff -->
    </form>
</div>

<style>
/* Estilos para integrar el diseño del dashboard al formulario de productos */
/* Corregir estilos de WP Admin */
#wpcontent, #wpbody-content {
    padding: 0 !important;
}

.wrap h1.wp-heading-inline,
.wrap > h2,
.wrap > .notice,
.wrap > .updated,
.wrap > .error,
.wrap > h1:not(.wp-pos-header-title) {
    display: none !important;
}

.wrap {
    margin: 0;
    padding: 0;
}

/* Contenedor principal */
.wp-pos-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 40px;
}

/* Header con degradado */
.wp-pos-header {
    background: linear-gradient(135deg, #3a6186, #89253e);
    color: #fff;
    padding: 30px 20px;
    margin-bottom: 25px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}

.wp-pos-header h1 {
    margin: 0;
    font-size: 28px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}

.wp-pos-header p {
    margin: 10px 0 0;
    opacity: 0.9;
    font-size: 15px;
    max-width: 700px;
}

/* Estilos para los paneles de producto */
.wp-pos-dashboard-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    overflow: hidden;
    transition: transform 0.2s;
    margin-bottom: 20px;
    border: none;
}

.wp-pos-dashboard-card.products {
    border-top: 3px solid #6c5ce7;
}

.wp-pos-dashboard-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
    color: #333;
}

.wp-pos-dashboard-card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.wp-pos-dashboard-card-title .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.wp-pos-dashboard-card-body {
    padding: 20px;
}

/* Botón personalizado */
.wp-pos-action-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 15px;
    background: #fff;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    font-weight: 500;
    position: relative;
    border: 1px solid #eee;
    transition: all 0.2s ease;
    cursor: pointer;
    width: 100%;
    margin-bottom: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

.wp-pos-action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.wp-pos-action-button.primary {
    background: linear-gradient(135deg, #3a6186, #89253e);
    color: #fff;
    border: none;
}

.wp-pos-action-button.secondary {
    background: #f8f9fa;
    border: 1px solid #ddd;
}

.wp-pos-action-button.danger {
    background: #f8f9fa;
    border: 1px solid #ddd;
    color: #e74c3c;
}

.wp-pos-action-button .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    margin-right: 8px;
}

/* Estilo enlace volver */
.wp-pos-return-link {
    margin-bottom: 20px;
}

.wp-pos-view-all-button {
    display: inline-flex;
    align-items: center;
    background: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 20px;
    padding: 8px 15px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: all 0.2s;
}

.wp-pos-view-all-button:hover {
    background: #f0f0f0;
    transform: translateY(-2px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.06);
}

.wp-pos-view-all-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
}

.wp-pos-view-all-icon .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.wp-pos-view-all-text {
    margin: 0 8px;
}

/* Ajustes para campos de formulario */
.form-table th {
    font-weight: 500;
    color: #333;
}

.form-table input[type="text"],
.form-table input[type="number"],
.form-table textarea {
    border-radius: 4px;
    border: 1px solid #ddd;
    padding: 8px 12px;
    width: 100%;
    max-width: 400px;
}

.price-field {
    position: relative;
}

.price-symbol {
    background-color: #f5f5f5;
    border-radius: 4px 0 0 4px;
    border: 1px solid #ddd;
    border-right: none;
    padding: 8px 12px;
}

.product-image-preview {
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #eee;
    min-height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    overflow: hidden;
}

.product-image-preview img {
    max-width: 100%;
    max-height: 150px;
    display: block;
}

.product-no-image {
    color: #666;
    font-style: italic;
}

.product-image-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.product-image-actions button {
    flex: 1;
}

@media (max-width: 782px) {
    .columns-2 #postbox-container-1 {
        margin-left: 0;
    }
    #poststuff #post-body.columns-2 {
        margin-right: 0;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Gestionar opción de stock
    $('#product_manage_stock').on('change', function() {
        if ($(this).is(':checked')) {
            $('.stock-options').show();
            $('.stock-status-field').hide();
        } else {
            $('.stock-options').hide();
            $('.stock-status-field').show();
        }
    });
    
    // Gestionar imagen del producto
    var mediaUploader;
    
    $('#product-image-upload').on('click', function(e) {
        e.preventDefault();
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: '<?php esc_html_e('Seleccionar imagen del producto', 'wp-pos'); ?>',
            button: {
                text: '<?php esc_html_e('Usar esta imagen', 'wp-pos'); ?>'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            
            $('#product_image_id').val(attachment.id);
            $('#product_image_url').val(attachment.url);
            
            $('.product-image-preview').html('<img src="' + attachment.url + '" alt="" style="max-width:100%;height:auto;">');
            $('#product-image-remove').show();
        });
        
        mediaUploader.open();
    });
    
    $('#product-image-remove').on('click', function(e) {
        e.preventDefault();
        
        $('#product_image_id').val('0');
        $('#product_image_url').val('');
        
        $('.product-image-preview').html('<div class="product-no-image"><?php esc_html_e('Sin imagen', 'wp-pos'); ?></div>');
        $(this).hide();
    });
    
    // Forzar el envío manual del formulario
    $('#submit-product').on('click', function(e) {
        e.preventDefault();
        document.getElementById('wp-pos-product-form').submit();
    });
});
</script>


