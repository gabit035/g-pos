<?php
/**
 * Plantilla para la administracion de productos
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

// Prevencion de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Preparar variables y tablas
global $wpdb;
$products_table = $wpdb->prefix . 'pos_products';
$meta_table = $wpdb->prefix . 'pos_product_meta';
$wp_pos_nonce = wp_create_nonce('wp_pos_ajax');

// Cargar estilos mejorados
wp_enqueue_style('wp-pos-products-enhanced', WP_POS_PLUGIN_URL . 'assets/css/wp-pos-products-enhanced.css', array(), WP_POS_VERSION);

// Verificar si las tablas existen
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$products_table'") == $products_table;

// Procesar solicitud de eliminaciu00f3n
$deleted_message = '';

// Verificar si estamos eliminando un producto y procesar antes de cualquier salida HTML
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['product_id']) && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_product_' . $_GET['product_id'])) {
        // Eliminar producto directamente
        $product_id = absint($_GET['product_id']);
        
        // Primero eliminar metadatos si existen
        $wpdb->delete($meta_table, array('product_id' => $product_id));
        
        // Luego eliminar el producto
        $result = $wpdb->delete($products_table, array('id' => $product_id));
        
        // Establecer mensaje de confirmaciu00f3n para mostrarlo despuu00e9s
        $deleted_message = 'Producto eliminado correctamente.';
    } else {
        $deleted_message = 'Error: Token de seguridad no vu00e1lido.';
    }
}

// Obtener productos
$products = array();
if ($table_exists) {
    // Consulta para obtener todos los productos
    $sql = "SELECT * FROM $products_table ORDER BY name ASC";
    $results = $wpdb->get_results($sql, ARRAY_A);
    
    if ($results) {
        foreach ($results as $product) {
            // Obtener imagen si existe
            $thumbnail_id = 0;
            $thumbnail_url = '';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") == $meta_table) {
                $thumbnail_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT meta_value FROM $meta_table WHERE product_id = %d AND meta_key = 'thumbnail_id'",
                        $product['id']
                    )
                );
                
                if ($thumbnail_id) {
                    $thumbnail_url = wp_get_attachment_url($thumbnail_id);
                }
            }
            
            $products[] = array(
                'id'          => $product['id'],
                'name'        => $product['name'],
                'sku'         => $product['sku'],
                'price'       => $product['sale_price'] > 0 ? $product['sale_price'] : $product['regular_price'],
                'regular_price' => $product['regular_price'],
                'sale_price'  => $product['sale_price'],
                'stock'       => $product['stock_quantity'],
                'manage_stock' => $product['manage_stock'],
                'stock_status' => $product['stock_status'],
                'type'        => 'simple',
                'permalink'   => '',
                'thumbnail'   => $thumbnail_url,
                'thumbnail_id' => $thumbnail_id,
            );
        }
    }
}

?>

<div class="wrap wp-pos-products-wrapper">
    <!-- Encabezado con gradiente -->
    <div class="wp-pos-products-header">
        <div class="wp-pos-products-header-primary">
            <h1><?php esc_html_e('Productos', 'wp-pos'); ?></h1>
            <p><?php esc_html_e('Gestiona el inventario de productos para tu punto de venta.', 'wp-pos'); ?></p>
        </div>
        <a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg('action', 'add')); ?>" class="wp-pos-add-product">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Agregar producto', 'wp-pos'); ?>
        </a>
    </div>
    
    <?php if (!empty($deleted_message)) : ?>
    <div class="wp-pos-message wp-pos-message-success">
        <span class="dashicons dashicons-yes-alt"></span>
        <div><?php echo esc_html($deleted_message); ?></div>
    </div>
    <?php endif; ?>
    
    <!-- Filtros mejorados -->
    <div class="wp-pos-filters-card">
        <div class="wp-pos-filters-card-header">
            <h3><?php esc_html_e('Filtrar productos', 'wp-pos'); ?></h3>
        </div>
        <div class="wp-pos-filters-card-body">
            <div class="wp-pos-filters-form">
                <input type="text" id="product-search" class="wp-pos-search-input" placeholder="<?php esc_attr_e('Buscar productos...', 'wp-pos'); ?>">
                <select id="product-stock-filter" class="wp-pos-select-filter">
                    <option value="all"><?php esc_html_e('Todos los productos', 'wp-pos'); ?></option>
                    <option value="instock"><?php esc_html_e('En stock', 'wp-pos'); ?></option>
                    <option value="outofstock"><?php esc_html_e('Sin stock', 'wp-pos'); ?></option>
                </select>
                <button type="button" id="doaction" class="wp-pos-filter-button">
                    <span class="dashicons dashicons-filter"></span>
                    <?php esc_attr_e('Filtrar', 'wp-pos'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tabla de productos -->
    <div class="wp-pos-products-card">
        <div class="wp-pos-products-card-header">
            <h3><?php esc_html_e('Lista de productos', 'wp-pos'); ?></h3>
        </div>
        <div class="wp-pos-products-card-body">
            <?php if (empty($products)) : ?>
                <div class="wp-pos-message wp-pos-message-info">
                    <span class="dashicons dashicons-info-outline"></span>
                    <div><?php esc_html_e('No se encontraron productos. Añade algunos usando el botón "Agregar producto".', 'wp-pos'); ?></div>
                </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped posts" id="wp-pos-products-table">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox">
                        </th>
                        <th scope="col" class="manage-column column-thumbnail"><?php esc_html_e('Imagen', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-name column-primary"><?php esc_html_e('Nombre', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-sku"><?php esc_html_e('SKU', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-price"><?php esc_html_e('Precio', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-stock"><?php esc_html_e('Stock', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php esc_html_e('Acciones', 'wp-pos'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) : ?>
                        <tr id="product-<?php echo esc_attr($product['id']); ?>">
                            <th scope="row" class="check-column">
                                <input id="cb-select-<?php echo esc_attr($product['id']); ?>" type="checkbox" name="product[]" value="<?php echo esc_attr($product['id']); ?>">
                            </th>
                            <td class="column-thumbnail">
                                <div class="product-thumbnail">
                                    <?php if (!empty($product['thumbnail'])) : ?>
                                        <img src="<?php echo wp_pos_safe_esc_url($product['thumbnail']); ?>" alt="<?php echo esc_attr($product['name']); ?>" width="40" height="40">
                                    <?php else : ?>
                                        <span class="dashicons dashicons-format-image"></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-name column-primary">
                                <strong><a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg(array('action' => 'edit', 'product_id' => $product['id']))); ?>">
                                    <?php echo esc_html($product['name']); ?>
                                </a></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg(array('action' => 'edit', 'product_id' => $product['id']))); ?>"><?php esc_html_e('Editar', 'wp-pos'); ?></a> | </span>
                                    <span class="trash"><a href="<?php echo wp_pos_safe_esc_url(wp_nonce_url(wp_pos_safe_add_query_arg(array('action' => 'delete', 'product_id' => $product['id'])), 'delete_product_' . $product['id'])); ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar \'' . esc_js($product['name']) . '\'? Esta acción no se puede deshacer.')"><?php esc_html_e('Eliminar', 'wp-pos'); ?></a></span>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">Mostrar más detalles</span></button>
                            </td>
                            <td class="column-sku">
                                <?php echo !empty($product['sku']) ? esc_html($product['sku']) : '<span class="na">—</span>'; ?>
                            </td>
                            <td class="column-price">
                                <?php 
                                    if ($product['sale_price'] > 0 && $product['sale_price'] < $product['regular_price']) {
                                        echo '<del>' . wp_pos_format_price($product['regular_price']) . '</del> <span class="sale-price">' . wp_pos_format_price($product['sale_price']) . '</span>';
                                    } else {
                                        echo wp_pos_format_price($product['regular_price']);
                                    }
                                ?>
                            </td>
                            <td class="column-stock">
                                <?php if ($product['manage_stock']) : ?>
                                    <span class="stock-qty"><?php echo esc_html($product['stock']); ?></span>
                                <?php else : ?>
                                    <span class="stock-status <?php echo esc_attr($product['stock_status']); ?>" data-status="<?php echo esc_attr($product['stock_status']); ?>">
                                        <?php 
                                            if ($product['stock_status'] === 'instock') {
                                                echo '<span class="in-stock">' . esc_html__('En stock', 'wp-pos') . '</span>';
                                            } else {
                                                echo '<span class="out-of-stock">' . esc_html__('Agotado', 'wp-pos') . '</span>';
                                            }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <div class="action-buttons">
                                    <a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg(array('action' => 'edit', 'product_id' => $product['id']))); ?>" class="button button-small">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php esc_html_e('Editar', 'wp-pos'); ?>
                                    </a>
                                    <a href="<?php echo wp_pos_safe_esc_url(wp_nonce_url(wp_pos_safe_add_query_arg(array('action' => 'delete', 'product_id' => $product['id'])), 'delete_product_' . $product['id'])); ?>" class="button button-small button-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar \'' . esc_js($product['name']) . '\'? Esta acción no se puede deshacer.')">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php esc_html_e('Eliminar', 'wp-pos'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="col" class="manage-column column-cb check-column">
                            <input id="cb-select-all-2" type="checkbox">
                        </th>
                        <th scope="col" class="manage-column column-thumbnail"><?php esc_html_e('Imagen', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-name column-primary"><?php esc_html_e('Nombre', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-sku"><?php esc_html_e('SKU', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-price"><?php esc_html_e('Precio', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-stock"><?php esc_html_e('Stock', 'wp-pos'); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php esc_html_e('Acciones', 'wp-pos'); ?></th>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
        </div>
    </div>



    <style>
/* Estilos mejorados para la tabla de productos */
.wp-list-table .column-thumbnail {
    width: 60px;
    text-align: center;
}

.wp-list-table .column-name {
    width: 25%;
}

.wp-list-table .column-sku {
    width: 15%;
}

.wp-list-table .column-price {
    width: 15%;
}

.wp-list-table .column-stock {
    width: 10%;
    text-align: center;
}

.wp-list-table .column-actions {
    width: 120px;
    text-align: center;
}

/* Estilos de thumbnail */
.product-thumbnail {
    position: relative;
    width: 40px;
    height: 40px;
    margin: 0 auto;
    background-color: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.product-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.product-thumbnail .dashicons {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #aaa;
    font-size: 20px;
}

/* Estilos de precios */
.column-price del {
    color: #888;
    font-size: 0.9em;
    display: block;
}

.column-price .sale-price {
    color: #c9302c;
    font-weight: bold;
}

/* Estilos de stock */
.stock-qty {
    font-weight: bold;
    padding: 3px 8px;
    border-radius: 3px;
    background-color: #f8f8f8;
    display: inline-block;
    min-width: 30px;
    text-align: center;
}

.stock-status-instock .stock-qty {
    color: #5cb85c;
    background-color: rgba(92, 184, 92, 0.1);
    border: 1px solid rgba(92, 184, 92, 0.2);
}

.stock-status-outofstock .stock-qty {
    color: #d9534f;
    background-color: rgba(217, 83, 79, 0.1);
    border: 1px solid rgba(217, 83, 79, 0.2);
}

.stock-edit-button {
    background: none;
    border: none;
    color: #0073aa;
    cursor: pointer;
    padding: 0;
    margin-left: 5px;
    vertical-align: middle;
}

.stock-edit-button:hover {
    color: #00a0d2;
}

/* Modal mejorado */
.wp-pos-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.wp-pos-modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 10% auto;
    padding: 20px 25px;
    border-radius: 6px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
    width: 400px;
    animation: modalFadeIn 0.3s;
}

@keyframes modalFadeIn {
    from {opacity: 0; transform: translateY(-30px);}
    to {opacity: 1; transform: translateY(0);}
}

.wp-pos-modal-header {
    border-bottom: 1px solid #eeeeee;
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.wp-pos-modal-title {
    margin: 0;
    font-size: 1.4em;
    color: #333;
    font-weight: 600;
}

.wp-pos-modal-close {
    position: absolute;
    right: 15px;
    top: 15px;
    color: #777;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s;
}

.wp-pos-modal-close:hover {
    color: #333;
}

.wp-pos-modal-body {
    margin-bottom: 20px;
}

.wp-pos-modal-footer {
    border-top: 1px solid #eeeeee;
    padding-top: 15px;
    text-align: right;
}

.wp-pos-modal-footer .button {
    margin-left: 10px;
}

/* Botones de acción */
.action-buttons {
    display: flex;
    justify-content: center;
    gap: 5px;
}

.action-buttons .button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 3px;
    transition: all 0.2s;
}

.action-buttons .button-edit {
    background-color: #f0f0f0;
    border-color: #ccc;
    color: #333;
}

.action-buttons .button-edit:hover {
    background-color: #e8e8e8;
}

.action-buttons .button-delete {
    background-color: #fcf0f0;
    border-color: #f7d6d6;
    color: #d63638;
}

.action-buttons .button-delete:hover {
    background-color: #facaca;
    border-color: #f5bebe;
}

.action-buttons .dashicons {
    font-size: 16px;
    height: 16px;
    width: 16px;
    margin-right: 3px;
}

/* Animación al actualizar */
@keyframes highlightRow {
    0% { background-color: #fff7e9; }
    100% { background-color: transparent; }
}

tr.updating {
    opacity: 0.7;
    background-color: #f8f8f8;
}

tr.deleting {
    opacity: 0.7;
    background-color: #fff0f0;
}

.stock-qty.updated {
    animation: highlight 1s;
    font-weight: bold;
}

@keyframes highlight {
    0% { background-color: #ffff9c; }
    100% { background-color: rgba(92, 184, 92, 0.1); }
}

/* Mejoras visuales generales */
.wp-pos-table-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin-top: 20px;
    padding: 2px;
}

.widefat td, .widefat th {
    padding: 12px 10px;
    vertical-align: middle;
}

/* Mejoras de contraste y legibilidad */
.widefat th {
    background-color: #f9f9f9;
    border-bottom: 1px solid #e1e1e1;
    font-weight: 600;
    color: #32373c;
}

.widefat td {
    border-bottom: 1px solid #f0f0f0;
}

.widefat tr:last-child td {
    border-bottom: none;
}

/* Mejoras en filtros */
.tablenav.top {
    display: flex;
    align-items: center;
    margin: 15px 0;
    background: #fff;
    padding: 10px 15px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #ccd0d4;
}

#product-search {
    min-width: 250px;
    padding: 6px 10px;
    border-radius: 3px;
    margin-right: 10px;
    border: 1px solid #ddd;
}

#product-stock-filter {
    min-width: 150px;
    padding: 5px 24px 5px 10px;
    border-radius: 3px;
    margin-right: 10px;
    border: 1px solid #ddd;
}

.button.action {
    background-color: #f7f7f7;
    border-color: #ccc;
    color: #555;
    padding: 5px 14px;
    border-radius: 3px;
}

.button.action:hover {
    background-color: #f0f0f0;
    border-color: #999;
    color: #23282d;
}

/* Mejora el contraste de la cabecera */
.wp-heading-inline {
    font-size: 1.5em;
    font-weight: 600;
    color: #23282d;
    margin-right: 10px;
}

/* Responsividad mejorada */
@media screen and (max-width: 782px) {
    .wp-list-table .column-thumbnail,
    .wp-list-table .column-sku,
    .wp-list-table .column-price,
    .wp-list-table .column-actions {
        display: none;
    }
    
    .wp-list-table tr:not(.inline-edit-row):not(.no-items) td:not(.column-primary)::before {
        font-weight: 600;
        color: #32373c;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Pasar nonce a JavaScript
    var wp_pos_nonce = '<?php echo esc_js($wp_pos_nonce); ?>';
    var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    
    console.log('Nonce:', wp_pos_nonce);
    console.log('AJAX URL:', ajaxurl);
    
    // Mejora del filtrado y búsqueda
    var productsTable = $('#wp-pos-products-table');
    var filterButton = $('#doaction');
    var searchInput = $('#product-search');
    var stockFilter = $('#product-stock-filter');
    
    // Actualizar clase de la fila según status de stock
    productsTable.find('tbody tr').each(function() {
        var stockCell = $(this).find('.column-stock');
        var stockQty = parseInt(stockCell.find('.stock-qty').text(), 10);
        
        if (stockQty <= 0) {
            $(this).addClass('stock-status-outofstock');
        } else {
            $(this).addClass('stock-status-instock');
        }
    });
    
    // Función para filtrar productos
    function filterProducts() {
        var searchTerm = searchInput.val().toLowerCase();
        var stockStatus = stockFilter.val();
        
        productsTable.find('tbody tr').each(function() {
            var row = $(this);
            var productName = row.find('.column-name a').text().toLowerCase();
            var productSku = row.find('.column-sku').text().toLowerCase();
            var inStock = row.hasClass('stock-status-instock');
            
            var matchesSearch = searchTerm === '' || productName.indexOf(searchTerm) > -1 || productSku.indexOf(searchTerm) > -1;
            var matchesStock = stockStatus === '' || 
                              (stockStatus === 'instock' && inStock) || 
                              (stockStatus === 'outofstock' && !inStock);
            
            if (matchesSearch && matchesStock) {
                row.show();
            } else {
                row.hide();
            }
        });
        
        // Mostrar mensaje si no hay resultados
        if (productsTable.find('tbody tr:visible').length === 0) {
            if (productsTable.find('.no-items').length === 0) {
                productsTable.find('tbody').append(
                    '<tr class="no-items"><td colspan="7" class="colspanchange">No se encontraron productos que coincidan con los filtros.</td></tr>'
                );
            } else {
                productsTable.find('.no-items').show();
            }
        } else {
            productsTable.find('.no-items').hide();
        }
    }
    
    // Ejecutar filtrado al hacer clic en el botón o cambiar el select
    filterButton.on('click', filterProducts);
    stockFilter.on('change', filterProducts);
    
    // Filtrado en tiempo real al escribir (con debounce)
    var searchTimeout;
    searchInput.on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterProducts, 300);
    });
    
    // Select all functionality
    $('#cb-select-all-1, #cb-select-all-2').change(function() {
        var isChecked = $(this).prop('checked');
        $('#wp-pos-products-table tbody input[type="checkbox"]').prop('checked', isChecked);
    });
});
</script>
