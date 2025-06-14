<?php
/**
 * Script de diagnóstico para encontrar ventas en G-POS
 */

// Cargar WordPress
if (file_exists('../../../../wp-load.php')) {
    require_once('../../../../wp-load.php');
} elseif (file_exists('../../../../../wp-load.php')) {
    require_once('../../../../../wp-load.php');
} else {
    // Intentar una búsqueda automática del archivo wp-load.php
    $root_dir = dirname(__FILE__);
    $wp_load_path = '';
    
    // Subir hasta 10 niveles para encontrar wp-load.php
    for ($i = 0; $i < 10; $i++) {
        $root_dir = dirname($root_dir);
        if (file_exists($root_dir . '/wp-load.php')) {
            $wp_load_path = $root_dir . '/wp-load.php';
            break;
        }
    }
    
    if (!empty($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('No se pudo localizar wp-load.php. Por favor ejecuta este script desde el panel de administración de WordPress.');
    }
}

// Verificar acceso
if (!current_user_can('administrator')) {
    wp_die('Acceso denegado. Debes ser administrador para ejecutar este diagnóstico.');
}

// Obtener fecha actual o la proporcionada
$date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

echo '<h1>Diagnóstico de ventas para G-POS</h1>';
echo '<p>Fecha de búsqueda: ' . $date . '</p>';

global $wpdb;

// Función para ejecutar consultas seguras
function run_safe_query($query, $args = []) {
    global $wpdb;
    
    try {
        if (!empty($args)) {
            $prepared_query = $wpdb->prepare($query, $args);
        } else {
            $prepared_query = $query;
        }
        
        return $wpdb->get_results($prepared_query);
    } catch (Exception $e) {
        return null;
    }
}

// Mostrar todas las tablas del prefijo pos_
echo '<h2>Tablas disponibles relacionadas con POS</h2>';
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}pos_%'");

echo '<ul>';
foreach ($tables as $table) {
    foreach ($table as $table_name) {
        echo "<li>$table_name</li>";
    }
}
echo '</ul>';

// Comprobar tabla wp_posts por ventas (si G-POS usa CPT)
echo '<h2>Verificando ventas en wp_posts (Custom Post Types)</h2>';
$posts_query = "SELECT ID, post_title, post_date FROM {$wpdb->posts} 
               WHERE post_type LIKE '%order%' OR post_type LIKE '%sale%' OR post_type LIKE '%pos%'
               AND DATE(post_date) = %s";

$sales_posts = run_safe_query($posts_query, [$date]);

echo '<h3>Resultados encontrados: ' . count($sales_posts) . '</h3>';
if (!empty($sales_posts)) {
    echo '<table border="1" cellpadding="10">';
    echo '<tr><th>ID</th><th>Título</th><th>Fecha</th></tr>';
    foreach ($sales_posts as $post) {
        echo "<tr><td>{$post->ID}</td><td>{$post->post_title}</td><td>{$post->post_date}</td></tr>";
    }
    echo '</table>';
}

// Buscar en tablas específicas de G-POS
$pos_tables_to_check = [
    'pos_sales',
    'pos_sale_items',
    'pos_transactions',
    'pos_orders',
    'pos_order_items',
    'pos_receipts'
];

foreach ($pos_tables_to_check as $table_suffix) {
    $table_name = $wpdb->prefix . $table_suffix;
    echo "<h2>Verificando tabla: $table_name</h2>";
    
    // Verificar si la tabla existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        echo "<p>La tabla $table_name no existe en la base de datos.</p>";
        continue;
    }
    
    // Obtener estructura de tabla
    echo '<h3>Estructura de la tabla</h3>';
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>';
    foreach ($columns as $column) {
        echo "<tr><td>{$column->Field}</td><td>{$column->Type}</td><td>{$column->Null}</td><td>{$column->Key}</td></tr>";
    }
    echo '</table>';
    
    // Verificar si tiene una columna de fecha
    $date_column = null;
    foreach ($columns as $column) {
        if (strpos(strtolower($column->Field), 'date') !== false || 
            strpos(strtolower($column->Field), 'created') !== false || 
            strpos(strtolower($column->Field), 'time') !== false) {
            $date_column = $column->Field;
            break;
        }
    }
    
    // Buscar ventas para la fecha seleccionada
    echo '<h3>Datos para la fecha ' . $date . '</h3>';
    
    if ($date_column) {
        $data_query = "SELECT * FROM $table_name WHERE DATE($date_column) = %s LIMIT 10";
        $sales_data = run_safe_query($data_query, [$date]);
    } else {
        $data_query = "SELECT * FROM $table_name LIMIT 10";
        $sales_data = run_safe_query($data_query);
    }
    
    if (empty($sales_data)) {
        echo "<p>No se encontraron datos para esta fecha.</p>";
    } else {
        echo '<p>Encontrados ' . count($sales_data) . ' registros.</p>';
        echo '<table border="1" cellpadding="5">';
        
        // Encabezados de tabla
        echo '<tr>';
        foreach (get_object_vars($sales_data[0]) as $key => $value) {
            echo "<th>$key</th>";
        }
        echo '</tr>';
        
        // Datos
        foreach ($sales_data as $row) {
            echo '<tr>';
            foreach (get_object_vars($row) as $value) {
                echo "<td>" . (is_null($value) ? 'NULL' : htmlspecialchars($value)) . "</td>";
            }
            echo '</tr>';
        }
        
        echo '</table>';
        
        // Intentar calcular el total si es la tabla de ventas
        if (strpos($table_name, 'sales') !== false || strpos($table_name, 'orders') !== false) {
            $total_query = "SELECT SUM(total) as total_sum FROM $table_name WHERE DATE($date_column) = %s";
            $total_result = run_safe_query($total_query, [$date]);
            
            if ($total_result && isset($total_result[0]->total_sum)) {
                echo "<p><strong>Total de ventas para esta fecha:</strong> $" . number_format($total_result[0]->total_sum, 2) . "</p>";
            }
        }
    }
}

// Verificar tablas de WooCommerce
echo '<h2>Verificando tablas de WooCommerce</h2>';

$wc_tables = [
    'wc_order_stats',
    'woocommerce_order_items',
    'woocommerce_order_itemmeta'
];

foreach ($wc_tables as $wc_table) {
    $table_name = $wpdb->prefix . $wc_table;
    echo "<h3>Tabla: $table_name</h3>";
    
    // Verificar si la tabla existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        echo "<p>La tabla $table_name no existe en la base de datos.</p>";
        continue;
    }
    
    // Buscar ventas para la fecha seleccionada
    if ($wc_table === 'wc_order_stats') {
        $wc_query = "SELECT * FROM $table_name WHERE DATE(date_created) = %s LIMIT 10";
        $wc_data = run_safe_query($wc_query, [$date]);
        
        if (!empty($wc_data)) {
            echo '<p>Encontradas ' . count($wc_data) . ' órdenes de WooCommerce.</p>';
            echo '<table border="1" cellpadding="5">';
            
            // Encabezados
            echo '<tr>';
            foreach (get_object_vars($wc_data[0]) as $key => $value) {
                echo "<th>$key</th>";
            }
            echo '</tr>';
            
            // Datos
            foreach ($wc_data as $row) {
                echo '<tr>';
                foreach (get_object_vars($row) as $value) {
                    echo "<td>" . (is_null($value) ? 'NULL' : htmlspecialchars($value)) . "</td>";
                }
                echo '</tr>';
            }
            
            echo '</table>';
            
            // Calcular total
            $total_query = "SELECT SUM(total_sales) as total_sum FROM $table_name WHERE DATE(date_created) = %s";
            $total_result = run_safe_query($total_query, [$date]);
            
            if ($total_result && isset($total_result[0]->total_sum)) {
                echo "<p><strong>Total de ventas WooCommerce para esta fecha:</strong> $" . number_format($total_result[0]->total_sum, 2) . "</p>";
            }
        } else {
            echo "<p>No se encontraron órdenes de WooCommerce para esta fecha.</p>";
        }
    } else {
        echo "<p>No se realizó búsqueda en esta tabla (requiere join con otras tablas).</p>";
    }
}

echo '<h2>Sugerencias</h2>';
echo '<p>Para encontrar las ventas realizadas, intenta:';
echo '<ul>';
echo '<li>Verifica los datos en diferentes fechas (las fechas pueden estar guardadas en otro formato)</li>';
echo '<li>Consulta con el desarrollador del plugin G-POS sobre dónde se almacenan las ventas</li>';
echo '<li>Revisa si las ventas se están almacenando directamente en WooCommerce u otro sistema</li>';
echo '</ul>';
echo '</p>';

echo '<p><a href="' . admin_url('admin.php?page=wp_pos_closures') . '">Volver al módulo de cierres</a></p>';
