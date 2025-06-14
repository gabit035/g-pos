<?php
/**
 * Funciones auxiliares para el módulo de productos
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener listado de productos
 *
 * @since 1.0.0
 * @param array $args Argumentos para la consulta
 * @return array Array de productos
 */
function wp_pos_get_products($args = array()) {
    $controller = WP_POS_Products_Controller::get_instance();
    return $controller->get_products($args);
}

/**
 * Buscar productos
 *
 * @since 1.0.0
 * @param string $search_term Término de búsqueda
 * @param array $args Argumentos adicionales
 * @return array Array de productos encontrados
 */
function wp_pos_search_products($search_term, $args = array()) {
    $controller = WP_POS_Products_Controller::get_instance();
    return $controller->search_products($search_term, $args);
}

/**
 * Obtener un producto por su ID
 *
 * @since 1.0.0
 * @param int $product_id ID del producto
 * @return array|false Datos del producto o false si no existe
 */
function wp_pos_get_product($product_id) {
    $controller = WP_POS_Products_Controller::get_instance();
    return $controller->get_product($product_id);
}

/**
 * Buscar productos directamente en la base de datos (solución directa)
 *
 * Esta función bypasea el controlador y busca productos directamente en la base de datos.
 * Se implementa por problemas con el sistema AJAX original que solo mostraba el último producto.
 *
 * @since 1.0.0
 * @param string $search_term Término de búsqueda
 * @return array Array de productos encontrados
 */
function wp_pos_search_products_direct($search_term) {
    global $wpdb;
    
    // Tabla de productos
    $table = $wpdb->prefix . 'pos_products';
    
    // Escapar término de búsqueda para consulta SQL
    $search_term = '%' . $wpdb->esc_like($search_term) . '%';
    
    // Realizar consulta SQL directa
    $products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE name LIKE %s OR sku LIKE %s ORDER BY name ASC LIMIT 30",
            $search_term,
            $search_term
        ),
        ARRAY_A
    );
    
    // Asegurarse de que el precio y stock tenga valores válidos
    foreach ($products as &$product) {
        // Calcular el precio final correctamente basado en regular_price y sale_price
        $regular_price = isset($product['regular_price']) ? floatval($product['regular_price']) : 0;
        $sale_price = isset($product['sale_price']) ? floatval($product['sale_price']) : 0;
        
        // Usar sale_price si es mayor que 0, sino usar regular_price
        $final_price = ($sale_price > 0) ? $sale_price : $regular_price;
        
        // Asignar el precio final calculado
        $product['price'] = $final_price;
        
        // Usar stock_quantity en lugar de stock, ya que ese es el campo correcto en la DB
        $stock_quantity = isset($product['stock_quantity']) ? intval($product['stock_quantity']) : 0;
        $product['stock'] = $stock_quantity;
        
        // Para depuración
        error_log('Producto: ' . $product['name'] . ' - Stock: ' . $product['stock'] . ' - Stock Quantity: ' . $stock_quantity);
    }
    
    return $products;
}

/**
 * Manejador AJAX para búsqueda directa de productos
 * 
 * @since 1.0.0
 */
function wp_pos_ajax_search_products_direct() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_pos_nonce')) {
        wp_send_json_error(['message' => 'Error de seguridad: Nonce inválido.']);
    }
    
    // Obtener término de búsqueda
    $search_term = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    
    if (empty($search_term)) {
        wp_send_json_error(['message' => 'Término de búsqueda vacío.']);
    }
    
    // Buscar productos directamente
    $products = wp_pos_search_products_direct($search_term);
    
    // Formatear resultados para la respuesta
    $formatted_products = [];
    foreach ($products as $product) {
        $formatted_products[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'sku' => $product['sku'] ?: '',
            'price' => $product['price'],
            'stock' => $product['stock'] ?: '0',
        ];
    }
    
    wp_send_json_success([
        'message' => 'Productos encontrados',
        'products' => $formatted_products
    ]);
}

/**
 * Actualizar stock de un producto
 *
 * @since 1.0.0
 * @param int $product_id ID del producto
 * @param int $quantity Nueva cantidad o cantidad a añadir/restar
 * @param string $operation Tipo de operación (set, add, subtract)
 * @return bool Éxito de la operación
 */
function wp_pos_update_product_stock($product_id, $quantity, $operation = 'set') {
    global $wpdb;
    
    // DIAGNÓSTICO: Registrar llamada a la función
    error_log(sprintf('DIAGNÓSTICO FUNC PRINCIPAL - INICIO: Función wp_pos_update_product_stock llamada con: ID=%d, Cantidad=%d, Operación=%s',
        $product_id, $quantity, $operation));
    
    // Obtener stack trace para ver desde dónde se llamó
    $e = new Exception();
    error_log('DIAGNÓSTICO STACK TRACE: ' . $e->getTraceAsString());
    
    $product_id = absint($product_id);
    $quantity = intval($quantity);
    
    if (!$product_id) {
        return false;
    }
    
    // Tabla de productos
    $table = $wpdb->prefix . 'pos_products';
    
    // Obtener stock actual
    $current_stock = $wpdb->get_var(
        $wpdb->prepare("SELECT stock_quantity FROM {$table} WHERE id = %d", $product_id)
    );
    
    // Si el producto no existe, retornar error
    if ($current_stock === null) {
        error_log('Error al actualizar stock: El producto con ID ' . $product_id . ' no existe.');
        return false;
    }
    
    $current_stock = intval($current_stock);
    $new_stock = $current_stock;
    
    // Calcular nuevo stock según operación
    switch ($operation) {
        case 'add':
            $new_stock = $current_stock + $quantity;
            break;
        case 'subtract':
            $new_stock = max(0, $current_stock - $quantity); // Prevenir stock negativo
            break;
        case 'set':
        default:
            $new_stock = $quantity; // Permitir stock negativo
            break;
    }
    
    // DIAGNÓSTICO: Registrar valores antes de actualizar
    error_log(sprintf('DIAGNÓSTICO FUNC PRINCIPAL - ANTES DE UPDATE: Tabla=%s, ID=%d, Stock Nuevo=%d',
        $table, $product_id, $new_stock));
    
    // Verificar que la tabla existe
    $table_exists = $wpdb->get_var($wpdb->prepare("\\nSHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
        error_log(sprintf('DIAGNÓSTICO ERROR: La tabla %s NO EXISTE', $table));
        return false;
    }
    
    // Actualizar stock en la base de datos
    $result = $wpdb->update(
        $table,
        array('stock_quantity' => $new_stock),
        array('id' => $product_id)
    );
    
    // DIAGNÓSTICO: Verificar campo stock_quantity existe en la tabla
    $columns = $wpdb->get_results("DESCRIBE {$table}");
    $has_stock_column = false;
    foreach ($columns as $column) {
        if ($column->Field === 'stock_quantity') {
            $has_stock_column = true;
            error_log('DIAGNÓSTICO: Columna stock_quantity EXISTE en tabla ' . $table);
            break;
        }
    }
    if (!$has_stock_column) {
        error_log('DIAGNÓSTICO ERROR CRITICO: Columna stock_quantity NO EXISTE en tabla ' . $table);
        // Mostrar todas las columnas disponibles
        $column_names = array();
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        error_log('DIAGNÓSTICO: Columnas disponibles: ' . implode(', ', $column_names));
    }
    
    // Registrar la actualización en el log para depuración
    if ($result === false) {
        error_log(sprintf('ERROR al actualizar stock para producto ID: %d, Operación: %s. Error: %s', 
            $product_id, $operation, $wpdb->last_error));
    } else {
        error_log(sprintf('Actualizado stock del producto ID: %d. Stock anterior: %d, Nuevo stock: %d, Operación: %s', 
            $product_id, $current_stock, $new_stock, $operation));
        
        // Si la actualización fue exitosa, lanzar hook para notificaciones
        /**
         * Hook que se ejecuta después de actualizar el stock de un producto
         * 
         * @param int $product_id ID del producto actualizado
         * @param int $new_stock Nueva cantidad de stock
         * @param string $operation Tipo de operación realizada
         */
        do_action('wp_pos_after_update_product_stock', $product_id, $new_stock, $operation);
    }
    
    return $result !== false;
}

/**
 * Renderizar interfaz de productos
 *
 * @since 1.0.0
 * @param array $atts Atributos del shortcode
 * @return string HTML de la interfaz
 */
function wp_pos_render_products_interface($atts = array()) {
    $atts = shortcode_atts(array(
        'category' => '',
        'limit'    => 20,
        'columns'  => 4,
    ), $atts, 'wp_pos_products');
    
    $args = array(
        'posts_per_page' => absint($atts['limit']),
    );
    
    if (!empty($atts['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($atts['category']),
            ),
        );
    }
    
    $products = wp_pos_get_products($args);
    
    ob_start();
    
    // Incluir plantilla si existe
    $template_path = apply_filters(
        'wp_pos_products_template',
        WP_POS_PLUGIN_DIR . 'modules/products/templates/products-grid.php'
    );
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        // Plantilla alternativa integrada
        ?>
        <div class="wp-pos-products-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <?php if (!empty($products)) : ?>
                <?php foreach ($products as $product) : ?>
                    <div class="wp-pos-product-item" data-product-id="<?php echo esc_attr($product['id']); ?>">
                        <?php if (!empty($product['thumbnail'])) : ?>
                            <div class="wp-pos-product-thumbnail">
                                <img src="<?php echo esc_url($product['thumbnail']); ?>" alt="<?php echo esc_attr($product['name']); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="wp-pos-product-details">
                            <h3 class="wp-pos-product-title"><?php echo esc_html($product['name']); ?></h3>
                            
                            <div class="wp-pos-product-price">
                                <?php echo wp_kses_post(wc_price($product['price'])); ?>
                            </div>
                            
                            <?php if (isset($product['stock']) && $product['stock'] !== '') : ?>
                                <div class="wp-pos-product-stock">
                                    <?php esc_html_e('Stock:', 'wp-pos'); ?> <?php echo esc_html($product['stock']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="wp-pos-product-actions">
                                <button class="wp-pos-add-to-cart button" data-product-id="<?php echo esc_attr($product['id']); ?>">
                                    <?php esc_html_e('Añadir', 'wp-pos'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="wp-pos-no-products"><?php esc_html_e('No se encontraron productos.', 'wp-pos'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    return ob_get_clean();
}

/**
 * Generar selector de productos
 *
 * @since 1.0.0
 * @param array $args Argumentos para el selector
 * @return string HTML del selector
 */
function wp_pos_product_selector($args = array()) {
    $defaults = array(
        'id'          => 'wp-pos-product-selector',
        'name'        => 'product_id',
        'class'       => 'wp-pos-select2',
        'selected'    => 0,
        'placeholder' => __('Buscar productos...', 'wp-pos'),
    );
    
    $args = wp_parse_args($args, $defaults);
    
    ob_start();
    ?>
    <select id="<?php echo esc_attr($args['id']); ?>" 
            name="<?php echo esc_attr($args['name']); ?>" 
            class="<?php echo esc_attr($args['class']); ?>" 
            data-placeholder="<?php echo esc_attr($args['placeholder']); ?>">
        <option value=""></option>
        <?php if ($args['selected']) : 
            $product = wp_pos_get_product($args['selected']);
            if ($product) : ?>
                <option value="<?php echo esc_attr($product['id']); ?>" selected="selected">
                    <?php echo esc_html($product['name']); ?> 
                    <?php if (!empty($product['sku'])) : ?>
                        (<?php echo esc_html($product['sku']); ?>)
                    <?php endif; ?>
                </option>
            <?php endif; 
        endif; ?>
    </select>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        if ($.fn.select2) {
            $("#<?php echo esc_js($args['id']); ?>").select2({
                width: '100%',
                ajax: {
                    url: wp_pos.ajax_url,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'wp_pos_search_products',
                            term: params.term,
                            security: wp_pos.nonce
                        };
                    },
                    processResults: function(data) {
                        var options = [];
                        if (data.success && data.data) {
                            $.each(data.data, function(index, product) {
                                options.push({
                                    id: product.id,
                                    text: product.name + (product.sku ? ' (' + product.sku + ')' : '')
                                });
                            });
                        }
                        return {
                            results: options
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Registrar los manejadores de AJAX
 * Esta función es llamada desde el módulo principal
 *
 * @since 1.0.0
 */
function wp_pos_register_products_ajax_handlers() {
    // Ajax para buscar productos
    add_action('wp_ajax_wp_pos_search_products', 'wp_pos_ajax_search_products');
    
    // Ajax para obtener un producto
    add_action('wp_ajax_wp_pos_get_product', 'wp_pos_ajax_get_product');
    
    // Ajax para actualizar stock
    add_action('wp_ajax_wp_pos_update_product_stock', 'wp_pos_ajax_update_product_stock');
    
    // Ajax para buscar productos directamente
    add_action('wp_ajax_wp_pos_search_products_direct', 'wp_pos_ajax_search_products_direct');
}

/**
 * Manejador AJAX para buscar productos
 *
 * @since 1.0.0
 */
function wp_pos_ajax_search_products() {
    // Verificar nonce
    if (!check_ajax_referer('wp_pos_nonce', 'security', false)) {
        wp_send_json_error(array('message' => __('Error de seguridad. Por favor, recarga la página.', 'wp-pos')));
        return;
    }
    
    // Verificar permisos
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => __('Permisos insuficientes.', 'wp-pos')));
        return;
    }
    
    $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
    
    if (empty($term)) {
        // Enviar lista vacía en lugar de error para mejor experiencia de usuario
        wp_send_json_success(array());
        return;
    }
    
    $products = wp_pos_search_products($term);
    
    wp_send_json_success($products);
}

/**
 * Manejador AJAX para obtener un producto
 *
 * @since 1.0.0
 */
function wp_pos_ajax_get_product() {
    // Verificar nonce
    check_ajax_referer('wp_pos_ajax', 'security');
    
    // Verificar permisos
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => __('Permisos insuficientes.', 'wp-pos')));
    }
    
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    
    if (!$product_id) {
        wp_send_json_error(array('message' => __('ID de producto inválido.', 'wp-pos')));
    }
    
    $product = wp_pos_get_product($product_id);
    
    if (!$product) {
        wp_send_json_error(array('message' => __('Producto no encontrado.', 'wp-pos')));
    }
    
    wp_send_json_success($product);
}

/**
 * Manejador AJAX para actualizar stock
 *
 * @since 1.0.0
 */
function wp_pos_ajax_update_product_stock() {
    // Verificar nonce
    check_ajax_referer('wp_pos_ajax', 'security');
    
    // Verificar permisos
    if (!current_user_can('manage_options') && !current_user_can('edit_products')) {
        wp_send_json_error(array('message' => __('Permisos insuficientes.', 'wp-pos')));
    }
    
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $operation = isset($_POST['operation']) ? sanitize_text_field($_POST['operation']) : 'set';
    
    if (!$product_id) {
        wp_send_json_error(array('message' => __('ID de producto inválido.', 'wp-pos')));
    }
    
    $result = wp_pos_update_product_stock($product_id, $quantity, $operation);
    
    if (!$result) {
        wp_send_json_error(array('message' => __('No se pudo actualizar el stock.', 'wp-pos')));
    }
    
    $product = wp_pos_get_product($product_id);
    
    wp_send_json_success(array(
        'message' => __('Stock actualizado correctamente.', 'wp-pos'),
        'product' => $product
    ));
}

// Registrar los manejadores AJAX
wp_pos_register_products_ajax_handlers();
