<?php
/**
 * Formulario de emergencia para au00f1adir productos
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

// Prevenciou00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Informaciu00f3n de depuraciu00f3n
echo '<div style="margin: 20px; padding: 20px; border: 3px solid red; background-color: #fff;">';
echo '<h2 style="color: red;">FORMULARIO DE EMERGENCIA - Au00d1ADIR PRODUCTO</h2>';

// Verificar si las tablas existen
global $wpdb;
$products_table = $wpdb->prefix . 'pos_products';
$meta_table = $wpdb->prefix . 'pos_product_meta';

// Mostrar estado de las tablas
echo '<h3>Estado de las tablas:</h3>';
echo '<pre>';
echo 'Tabla productos: ' . ($wpdb->get_var("SHOW TABLES LIKE '$products_table'") == $products_table ? 'EXISTE' : 'NO EXISTE') . "\n";
echo 'Tabla metadatos: ' . ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") == $meta_table ? 'EXISTE' : 'NO EXISTE') . "\n";
echo '</pre>';

// Procesar formulario de envu00edo
if (!empty($_POST['emergency_product_submit'])) {
    echo '<h3>Procesando envu00edo de producto:</h3>';
    
    // Datos del producto
    $product_name = sanitize_text_field($_POST['product_name']);
    $product_desc = wp_kses_post($_POST['product_description']);
    $product_price = floatval($_POST['product_price']);
    
    if (empty($product_name)) {
        echo '<p style="color:red;">Error: El nombre del producto es obligatorio</p>';
    } else {
        // Insertar producto directamente
        $result = $wpdb->insert(
            $products_table,
            array(
                'name' => $product_name,
                'description' => $product_desc,
                'regular_price' => $product_price,
                'sale_price' => 0,
                'manage_stock' => 0,
                'stock_quantity' => 0,
                'stock_status' => 'instock',
            )
        );
        
        if ($result) {
            $product_id = $wpdb->insert_id;
            echo '<p style="color:green;">Producto creado exitosamente. ID: ' . $product_id . '</p>';
            echo '<pre>';
            echo 'Query: ' . $wpdb->last_query . "\n";
            echo '</pre>';
        } else {
            echo '<p style="color:red;">Error al crear el producto</p>';
            echo '<pre>';
            echo 'Error: ' . $wpdb->last_error . "\n";
            echo 'Query: ' . $wpdb->last_query . "\n";
            echo '</pre>';
        }
    }
}

// Formulario simplificado
echo '<h3>Formulario de au00f1adir producto:</h3>';
echo '<form method="post" action="">';
echo '<p><label><strong>Nombre del producto:</strong></label><br>';
echo '<input type="text" name="product_name" value="" style="width: 100%; max-width: 400px;" required></p>';

echo '<p><label><strong>Descripciu00f3n:</strong></label><br>';
echo '<textarea name="product_description" style="width: 100%; max-width: 400px; height: 100px;"></textarea></p>';

echo '<p><label><strong>Precio:</strong></label><br>';
echo '<input type="number" name="product_price" value="0" step="0.01" min="0" style="width: 150px;"></p>';

echo '<p><input type="submit" name="emergency_product_submit" value="Guardar Producto" class="button button-primary"></p>';
echo '</form>';

echo '</div>'; // Cierre del div principal

// Cargar el formulario normal como respaldo
include dirname(__FILE__) . '/admin-product-form.php';
?>
