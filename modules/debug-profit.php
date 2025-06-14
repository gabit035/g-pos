<?php
/**
 * Script de diagnóstico para calcular la ganancia
 *
 * @package WP-POS
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
    require_once(ABSPATH . 'wp-config.php');
    require_once(ABSPATH . 'wp-includes/wp-db.php');
}

global $wpdb;

// 1. Verificar estructura de tablas
echo "<h2>Verificando estructura de tablas</h2>";

$products_table = $wpdb->prefix . 'pos_products';
$sales_table = $wpdb->prefix . 'pos_sales';
$sales_items_table = $wpdb->prefix . 'pos_sales_items';

echo "<h3>Tabla de productos: {$products_table}</h3>";
$product_columns = $wpdb->get_results("SHOW COLUMNS FROM {$products_table}");
echo "<pre>";
print_r($product_columns);
echo "</pre>";

echo "<h3>Tabla de ventas: {$sales_table}</h3>";
$sales_exists = $wpdb->get_var("SHOW TABLES LIKE '{$sales_table}'") === $sales_table;
if ($sales_exists) {
    $sales_columns = $wpdb->get_results("SHOW COLUMNS FROM {$sales_table}");
    echo "<pre>";
    print_r($sales_columns);
    echo "</pre>";
} else {
    echo "<p>La tabla de ventas no existe.</p>";
}

echo "<h3>Tabla de items de ventas: {$sales_items_table}</h3>";
$items_exists = $wpdb->get_var("SHOW TABLES LIKE '{$sales_items_table}'") === $sales_items_table;
if ($items_exists) {
    $items_columns = $wpdb->get_results("SHOW COLUMNS FROM {$sales_items_table}");
    echo "<pre>";
    print_r($items_columns);
    echo "</pre>";
} else {
    echo "<p>La tabla de items de ventas no existe.</p>";
}

// 2. Examinar algunos productos
echo "<h2>Productos con precio de compra</h2>";
$products = $wpdb->get_results("SELECT * FROM {$products_table} WHERE purchase_price > 0 LIMIT 5");
echo "<pre>";
print_r($products);
echo "</pre>";

echo "<h2>Productos sin precio de compra</h2>";
$products_no_purchase = $wpdb->get_results("SELECT * FROM {$products_table} WHERE purchase_price = 0 OR purchase_price IS NULL LIMIT 5");
echo "<pre>";
print_r($products_no_purchase);
echo "</pre>";

// 3. Examinar algunas ventas y sus ítems
if ($sales_exists && $items_exists) {
    echo "<h2>Ventas recientes</h2>";
    $sales = $wpdb->get_results("SELECT * FROM {$sales_table} ORDER BY id DESC LIMIT 5");
    echo "<pre>";
    print_r($sales);
    echo "</pre>";
    
    if (!empty($sales)) {
        foreach ($sales as $sale) {
            echo "<h3>Items de la venta #{$sale->id}</h3>";
            $items = $wpdb->get_results("SELECT si.*, p.purchase_price, p.name 
                                          FROM {$sales_items_table} si 
                                          LEFT JOIN {$products_table} p ON si.product_id = p.id 
                                          WHERE si.sale_id = {$sale->id}");
            echo "<pre>";
            print_r($items);
            echo "</pre>";
            
            // Calcular ganancia
            $total_profit = 0;
            foreach ($items as $item) {
                $unit_price = isset($item->price) ? (float)$item->price : 0;
                $quantity = isset($item->quantity) ? (int)$item->quantity : 0;
                
                if (!isset($item->purchase_price) || empty($item->purchase_price)) {
                    $purchase_price = $unit_price * 0.4; // Asumir 40% costo
                    echo "<p>Producto {$item->name}: Precio de compra estimado: $purchase_price</p>";
                } else {
                    $purchase_price = (float)$item->purchase_price;
                    echo "<p>Producto {$item->name}: Precio de compra real: $purchase_price</p>";
                }
                
                $profit = ($unit_price - $purchase_price) * $quantity;
                $total_profit += $profit;
                
                echo "<p>Ganancia del ítem: $profit = ($unit_price - $purchase_price) * $quantity</p>";
            }
            
            echo "<h4>Ganancia total de la venta: $total_profit</h4>";
        }
    }
}

// 4. Verificar la consulta específica de reportes
echo "<h2>Prueba de la consulta de reportes</h2>";
$period_start = date('Y-m-d', strtotime('-30 days'));
$period_end = date('Y-m-d');

$query = "SELECT p.id, p.name, p.purchase_price, SUM(si.quantity) as quantity, 
                 SUM(si.price * si.quantity) as total,
                 SUM((si.price - COALESCE(p.purchase_price, si.price * 0.4)) * si.quantity) as profit 
          FROM {$sales_items_table} si 
          JOIN {$sales_table} s ON si.sale_id = s.id 
          JOIN {$products_table} p ON si.product_id = p.id 
          WHERE DATE(s.created_at) BETWEEN %s AND %s 
          GROUP BY p.id 
          ORDER BY profit DESC 
          LIMIT 5";

$product_report = $wpdb->get_results($wpdb->prepare($query, $period_start, $period_end));
echo "<pre>";
print_r($product_report);
echo "</pre>";
?>
