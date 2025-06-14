<?php
/**
 * Plantilla de administración de ventas
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Mensaje de depuración inicial
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<div class="notice notice-info">';
    echo '<h3>Depuración: Inicio de admin-sales.php</h3>';
    echo '<p>Archivo cargado correctamente.</p>';
    
    // Verificar si estamos en la página correcta
    echo '<p>Página actual: ' . (isset($_GET['page']) ? esc_html($_GET['page']) : 'No definida') . '</p>';
    
    // Verificar si la tabla de ventas existe
    global $wpdb;
    $table_name = $wpdb->prefix . 'pos_sales';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    echo '<p>Tabla de ventas existe: ' . ($table_exists ? 'Sí' : 'No') . '</p>';
    
    if ($table_exists) {
        $total_sales = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo '<p>Total de ventas en la base de datos: ' . intval($total_sales) . '</p>';
    }
    
    echo '</div>';
}

// Incluir archivo auxiliar para manejo de clientes
require_once WP_POS_PLUGIN_DIR . 'includes/helpers/customer-helper.php';

// Incluir hoja de estilos mejorados
$version = WP_POS_VERSION . '.' . filemtime(WP_POS_PLUGIN_DIR . 'assets/css/wp-pos-sales-enhanced.css');
wp_enqueue_style('wp-pos-sales-enhanced', WP_POS_PLUGIN_URL . 'assets/css/wp-pos-sales-enhanced.css', array(), $version);

// Incluir script de administración
wp_enqueue_script('wp-pos-admin', WP_POS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WP_POS_VERSION, true);

// Pasar variables a JavaScript
wp_localize_script('wp-pos-admin', 'wp_pos_admin_params', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'confirm_delete' => __('¿Estás seguro de que deseas eliminar este elemento?', 'wp-pos'),
    'confirm_cancel' => __('¿Estás seguro de que deseas cancelar esta operación?', 'wp-pos'),
    'no_items_selected' => __('Por favor selecciona al menos un elemento.', 'wp-pos'),
    'no_action_selected' => __('Por favor selecciona una acción.', 'wp-pos')
));

// Cargar header
wp_pos_template_header(array(
    'title' => __('Ventas', 'wp-pos'),
    'active_menu' => 'sales'
));

// Obtener configuración
$options = wp_pos_get_option();

// Procesar acciones individuales y masivas
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$sale_id = isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0;
$action_message = '';
$action_type = '';

// Procesar acciones en masa si se envu00eda el formulario
if (isset($_POST['wp_pos_bulk_actions_nonce']) && wp_verify_nonce($_POST['wp_pos_bulk_actions_nonce'], 'wp_pos_bulk_actions_nonce')) {
    $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
    $sale_ids = isset($_POST['sale_ids']) ? array_map('intval', $_POST['sale_ids']) : array();
    
    // Verificar que tenemos idu2019s y una acciu00f3n vu00e1lida
    if (!empty($sale_ids) && !empty($bulk_action) && in_array($bulk_action, array('cancel', 'delete'))) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_sales';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'" );
        
        // Contar operaciones exitosas y fallidas
        $success_count = 0;
        $error_count = 0;
        
        // Procesar cada venta seleccionada
        foreach ($sale_ids as $sale_id) {
            // Verificar si la venta existe
            $sale = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $sale_id));
            
            if ($sale) {
                // Si es cancelaciu00f3n, actualizar estado
                if ($bulk_action === 'cancel' && $sale->status !== 'cancelled') {
                    $result = $wpdb->update(
                        $table_name,
                        array('status' => 'cancelled'),
                        array('id' => $sale_id),
                        array('%s'),
                        array('%d')
                    );
                    
                    if ($result !== false) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                // Si es eliminaciu00f3n, eliminar la venta
                elseif ($bulk_action === 'delete') {
                    $result = $wpdb->delete(
                        $table_name,
                        array('id' => $sale_id),
                        array('%d')
                    );
                    
                    if ($result !== false) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            }
        }
        
        // Mostrar mensaje de resultados
        if ($success_count > 0) {
            if ($bulk_action === 'cancel') {
                $action_message = sprintf(__('%d ventas canceladas correctamente.', 'wp-pos'), $success_count);
            } else {
                $action_message = sprintf(__('%d ventas eliminadas correctamente.', 'wp-pos'), $success_count);
            }
            
            if ($error_count > 0) {
                $action_message .= ' ' . sprintf(__('%d operaciones fallidas.', 'wp-pos'), $error_count);
            }
            
            $action_type = 'success';
        } elseif ($error_count > 0) {
            $action_message = sprintf(__('Error al procesar acciones en masa. %d operaciones fallidas.', 'wp-pos'), $error_count);
            $action_type = 'error';
        }
    }
}

// DEBUG: Registrar las variables de la acción
wp_pos_debug(array(
    'action' => $action,
    'sale_id' => $sale_id,
    'request' => $_GET,
    'post' => $_POST,
    'server' => $_SERVER['REQUEST_URI']
), 'ADMIN_SALES_ACTION_REQUEST');

// Acceso directo a la base de datos para operaciones críticas
if ($sale_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pos_sales';
    
    // DEBUG: Verificar que la tabla existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
    wp_pos_debug($table_exists, 'TABLE_EXISTS_CHECK');
    
    // DEBUG: Revisar los datos de la venta antes de procesar
    $sale_before = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $sale_id));
    wp_pos_debug($sale_before, 'SALE_BEFORE_ACTION');
    
    if ($action === 'cancel' && isset($_GET['_wpnonce'])) {
        // DEBUG: Verificar el nonce
        $nonce_is_valid = wp_verify_nonce($_GET['_wpnonce'], 'wp_pos_cancel_sale_' . $sale_id);
        wp_pos_debug(array(
            'nonce_provided' => $_GET['_wpnonce'],
            'nonce_expected' => 'wp_pos_cancel_sale_' . $sale_id,
            'is_valid' => $nonce_is_valid
        ), 'NONCE_VALIDATION_CANCEL');
        
        if ($nonce_is_valid) {
            // Verificar que la tabla existe y la venta existe antes de continuar
            if ($table_exists && $sale_before) {
                // Cancelar venta - acceso directo a BD
                $result = $wpdb->update(
                    $table_name,
                    array('status' => 'cancelled'),
                    array('id' => $sale_id),
                    array('%s'),
                    array('%d')
                );
                
                // DEBUG: Registrar el resultado de la operación
                wp_pos_debug(array(
                    'operation' => 'cancel_sale',
                    'sale_id' => $sale_id,
                    'result' => $result,
                    'wpdb_last_error' => $wpdb->last_error,
                    'wpdb_last_query' => $wpdb->last_query
                ), 'ADMIN_SALES_CANCEL_RESULT');
                
                if ($result !== false) {
                    $action_message = __('Venta cancelada correctamente.', 'wp-pos');
                    $action_type = 'success';
                } else {
                    $action_message = __('No se pudo cancelar la venta. Error: ' . $wpdb->last_error, 'wp-pos');
                    $action_type = 'error';
                }
            } else {
                $action_message = __('No se pudo procesar la acción porque la tabla o la venta no existe.', 'wp-pos');
                $action_type = 'error';
                wp_pos_debug(array(
                    'operation' => 'cancel_sale',
                    'sale_id' => $sale_id,
                    'table_exists' => $table_exists,
                    'sale_exists' => !empty($sale_before)
                ), 'ADMIN_SALES_CANCEL_ERROR');
            }
        } else {
            $action_message = __('Error de seguridad al cancelar la venta. Intente nuevamente.', 'wp-pos');
            $action_type = 'error';
        }
    }

    if ($action === 'delete' && isset($_GET['_wpnonce'])) {
        // DEBUG: Verificar el nonce
        $nonce_is_valid = wp_verify_nonce($_GET['_wpnonce'], 'wp_pos_delete_sale_' . $sale_id);
        wp_pos_debug(array(
            'nonce_provided' => $_GET['_wpnonce'],
            'nonce_expected' => 'wp_pos_delete_sale_' . $sale_id,
            'is_valid' => $nonce_is_valid
        ), 'NONCE_VALIDATION_DELETE');
        
        if ($nonce_is_valid) {
            // Verificar que la tabla existe y la venta existe antes de continuar
            if ($table_exists && $sale_before) {
                // Eliminar venta - acceso directo a BD
                $result = $wpdb->delete(
                    $table_name,
                    array('id' => $sale_id),
                    array('%d')
                );
                
                // DEBUG: Registrar el resultado de la operación
                wp_pos_debug(array(
                    'operation' => 'delete_sale',
                    'sale_id' => $sale_id,
                    'result' => $result,
                    'wpdb_last_error' => $wpdb->last_error,
                    'wpdb_last_query' => $wpdb->last_query
                ), 'ADMIN_SALES_DELETE_RESULT');
                
                if ($result !== false) {
                    $action_message = __('Venta eliminada correctamente.', 'wp-pos');
                    $action_type = 'success';
                } else {
                    $action_message = __('No se pudo eliminar la venta. Error: ' . $wpdb->last_error, 'wp-pos');
                    $action_type = 'error';
                }
            } else {
                $action_message = __('No se pudo procesar la acción porque la tabla o la venta no existe.', 'wp-pos');
                $action_type = 'error';
                wp_pos_debug(array(
                    'operation' => 'delete_sale',
                    'sale_id' => $sale_id,
                    'table_exists' => $table_exists,
                    'sale_exists' => !empty($sale_before)
                ), 'ADMIN_SALES_DELETE_ERROR');
            }
        } else {
            $action_message = __('Error de seguridad al eliminar la venta. Intente nuevamente.', 'wp-pos');
            $action_type = 'error';
        }
    }
    
    // DEBUG: Verificar el estado de la venta después de la acción (si aún existe)
    if ($action === 'cancel') {
        $sale_after = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $sale_id));
        wp_pos_debug($sale_after, 'SALE_AFTER_CANCEL');
    }
}

// Establecer parámetros de búsqueda y paginación
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20; // Cantidad de ventas por página
$offset = ($current_page - 1) * $per_page;

// Filtros
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$order_by = isset($_GET['order_by']) ? sanitize_text_field($_GET['order_by']) : 'date_desc';

// Definir opciones de ordenación
$order_options = array(
    'date_desc' => __('Fecha (más reciente primero)', 'wp-pos'),
    'date_asc' => __('Fecha (más antigua primero)', 'wp-pos'),
    'total_desc' => __('Total (mayor primero)', 'wp-pos'),
    'total_asc' => __('Total (menor primero)', 'wp-pos'),
    'id_desc' => __('ID (descendente)', 'wp-pos'),
    'id_asc' => __('ID (ascendente)', 'wp-pos')
);

// Consulta base
global $wpdb;
$table_name = $wpdb->prefix . 'pos_sales';

// Construir WHERE según filtros
$where = array();
$where_values = array();

if (!empty($search)) {
    // Buscar en múltiples campos para mayor flexibilidad
    $search_terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
    $search_conditions = array();
    $search_values = array();

    // Para búsquedas exactas de ID o números
    $exact_numeric_search = is_numeric($search) ? intval($search) : null;
    
    foreach ($search_terms as $term) {
        $like_term = '%' . $wpdb->esc_like($term) . '%';
        
        // Subcondiciones para este término (los diferentes campos donde buscar)
        $term_conditions = array();
        
        // 1. Búsqueda en campos numéricos (id, customer_id, total)
        if (is_numeric($term)) {
            $term_int = intval($term);
            // Búsqueda exacta por ID o customer_id
            $term_conditions[] = "id = %d";
            $search_values[] = $term_int;
            
            $term_conditions[] = "customer_id = %d";
            $search_values[] = $term_int;
            
            // Búsqueda parcial por total (para montos)
            $term_conditions[] = "total LIKE %s";
            $search_values[] = $like_term;
        }
        
        // 2. Búsqueda general por texto en todos los campos relevantes
        $term_conditions[] = "sale_number LIKE %s";
        $search_values[] = $like_term;
        
        // 3. Búsqueda en campo de items serializado (para encontrar productos por nombre o código)
        $term_conditions[] = "items LIKE %s";
        $search_values[] = $like_term;
        
        // 4. Búsqueda por estado
        if (in_array(strtolower($term), array('completada', 'cancelada', 'pendiente', 'completed', 'cancelled', 'pending'))) {
            // Mapear términos en español a valores en inglés del sistema
            $status_map = array(
                'completada' => 'completed',
                'cancelada' => 'cancelled',
                'pendiente' => 'pending'
            );
            
            $search_status = isset($status_map[strtolower($term)]) ? $status_map[strtolower($term)] : strtolower($term);
            $term_conditions[] = "status = %s";
            $search_values[] = $search_status;
        }
        
        // 5. Buscar por fecha si el término tiene formato de fecha (año-mes-día o día/mes/año)
        if (preg_match('/^(\d{4}-\d{2}-\d{2}|\d{2}\/\d{2}\/\d{4})$/', $term)) {
            // Convertir formato día/mes/año a año-mes-día si es necesario
            if (preg_match('/^(\d{2})\/\d{2}\/\d{4}$/', $term)) {
                $date_parts = explode('/', $term);
                $term = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
            }
            
            $term_conditions[] = "date LIKE %s";
            $search_values[] = $term . '%';  // Búsqueda por día específico
        }
        
        // Combinar las condiciones para este término con OR
        if (!empty($term_conditions)) {
            $search_conditions[] = '(' . implode(' OR ', $term_conditions) . ')';
        }
    }
    
    // Si hay condiciones, añadirlas a la cláusula WHERE
    if (!empty($search_conditions)) {
        $where[] = '(' . implode(' AND ', $search_conditions) . ')';
        $where_values = array_merge($where_values, $search_values);
    }
    
    // Registrar la búsqueda para debug
    wp_pos_debug([
        'search_term' => $search,
        'search_terms' => $search_terms,
        'search_conditions' => $search_conditions,
        'search_values' => $search_values
    ], 'SALES_SEARCH_FILTER');
}

if (!empty($status)) {
    $where[] = "status = %s";
    $where_values[] = $status;
}

if (!empty($date_from)) {
    $where[] = "date_created >= %s";
    $where_values[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $where[] = "date_created <= %s";
    $where_values[] = $date_to . ' 23:59:59';
}

// Armar cláusula WHERE
$where_clause = '';
if (!empty($where)) {
    $where_clause = "WHERE " . implode(" AND ", $where);
}

// Contar total de ventas con filtros
$count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
if (!empty($where_values)) {
    $count_query = $wpdb->prepare($count_query, $where_values);
}
$total_items = $wpdb->get_var($count_query);
$total_pages = ceil($total_items / $per_page);

// Obtener ventas paginadas
// Determinar orden según selección del usuario
$order_sql = 'date_created DESC'; // Predeterminado: fecha descendente
switch ($order_by) {
    case 'date_asc':
        $order_sql = 'date_created ASC';
        break;
    case 'date_desc':
        $order_sql = 'date_created DESC';
        break;
    case 'total_desc':
        $order_sql = 'total DESC';
        break;
    case 'total_asc':
        $order_sql = 'total ASC';
        break;
    case 'id_desc':
        $order_sql = 'id DESC';
        break;
    case 'id_asc':
        $order_sql = 'id ASC';
        break;
}

// Verificar si la tabla existe
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

if (!$table_exists) {
    // Si la tabla no existe, mostrar un mensaje de error
    echo '<div class="notice notice-error"><p>' . 
         __('Error: La tabla de ventas no existe en la base de datos. Por favor, desactiva y reactiva el plugin para crear las tablas necesarias.', 'wp-pos') . 
         '</p></div>';
    return;
}

// Verificar la estructura de la tabla
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
$required_columns = ['id', 'customer_id', 'total', 'status', 'date_created'];
$missing_columns = [];

foreach ($required_columns as $column) {
    $column_exists = false;
    foreach ($columns as $col) {
        if ($col->Field === $column) {
            $column_exists = true;
            break;
        }
    }
    if (!$column_exists) {
        $missing_columns[] = $column;
    }
}

if (!empty($missing_columns)) {
    echo '<div class="notice notice-error"><p>' . 
         sprintf(
             __('Error: Faltan columnas requeridas en la tabla de ventas: %s. Por favor, desactiva y reactiva el plugin para corregir la estructura de la base de datos.', 'wp-pos'),
             implode(', ', $missing_columns)
         ) . 
         '</p></div>';
    return;
}

// Verificar si hay datos en la tabla
$total_sales = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

if ($total_sales == 0) {
    // Si no hay ventas, mostrar un mensaje amigable
    echo '<div class="notice notice-info"><p>' . 
         __('No hay ventas registradas en el sistema. Crea tu primera venta para comenzar.', 'wp-pos') . 
         ' <a href="' . admin_url('admin.php?page=wp-pos-new-sale-v2') . '" class="button button-primary">' . 
         __('Crear nueva venta', 'wp-pos') . '</a></p></div>';
    return;
}

// Asegurarse de seleccionar date_created como date para mantener compatibilidad
$query = "SELECT *, date_created as date FROM $table_name $where_clause ORDER BY $order_sql LIMIT %d OFFSET %d";
$prepared_values = array_merge($where_values, array($per_page, $offset));

// DEBUG: Registrar la consulta final
wp_pos_debug(array(
    'query' => $query,
    'prepared_values' => $prepared_values,
    'where_clause' => $where_clause,
    'filters_applied' => array(
        'search' => $search,
        'status' => $status,
        'date_from' => $date_from,
        'date_to' => $date_to
    ),
    'table_name' => $table_name,
    'total_items' => $total_items,
    'total_pages' => $total_pages
), 'SALES_FINAL_QUERY');

// Obtener resultados usando el controlador
$controller = WP_POS_Sales_Controller::get_instance();

// Preparar argumentos para get_sales
$args = array(
    'search' => $search,
    'status' => $status,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'limit' => $per_page,
    'offset' => $offset,
    'order_by' => $order_by
);

// Obtener las ventas usando el controlador
$sales_response = $controller->get_sales($args);

// Verificar si la respuesta incluye información de paginación
if (is_array($sales_response) && isset($sales_response['items'])) {
    $sales = $sales_response['items'];
    $total_items = $sales_response['total_items'];
    $total_pages = $sales_response['total_pages'];
} else {
    // Compatibilidad con versiones anteriores
    $sales = $sales_response;
}

// DEBUG: Verificar resultados con más detalle
global $wpdb;
$debug_info = array(
    'sales_count' => is_array($sales) ? count($sales) : 0,
    'sales_sample' => is_array($sales) && !empty($sales) ? array_slice($sales, 0, 2) : 'No hay ventas o error en la consulta',
    'query' => $query,
    'prepared_values' => $prepared_values,
    'last_query' => $wpdb->last_query,
    'last_error' => $wpdb->last_error,
    'table_name' => $table_name,
    'table_columns' => $wpdb->get_col("SHOW COLUMNS FROM $table_name", 0)
);

// Verificar si hay algún error en la consulta
if ($wpdb->last_error) {
    $debug_info['error'] = 'Error en la consulta: ' . $wpdb->last_error;
}

// Verificar la estructura de la tabla
$table_structure = $wpdb->get_results("DESCRIBE $table_name", ARRAY_A);
$debug_info['table_structure'] = $table_structure;

// Verificar los primeros registros directamente
$sample_records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 2");
$debug_info['sample_records'] = $sample_records;

// Mostrar información de depuración
wp_pos_debug($debug_info, 'SALES_QUERY_DEBUG');

// Si no hay ventas pero hay mensaje de filtro, no mostrar mensaje
$show_no_results_message = empty($sales) && empty($action_message);

?>

<div class="wp-pos-admin-wrapper wp-pos-sales-wrapper">
    <!-- Aviso de WooCommerce (Desactivado a petición del usuario) -->
    <?php if (false && WP_POS_WOOCOMMERCE_ACTIVE) : ?>
    <div class="wp-pos-woo-notice">
        <span class="dashicons dashicons-info"></span>
        <div>
            <?php _e('WP-POS se está ejecutando en modo interoperable. Puedes utilizar la integración con WooCommerce, si lo deseas.', 'wp-pos'); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Panel de control de ventas -->
    <div class="wp-pos-control-panel">
        <div class="wp-pos-control-panel-primary">
            <h3><?php esc_html_e('Ventas', 'wp-pos'); ?></h3>
            <p><?php esc_html_e('Gestiona todas las ventas de tu negocio.', 'wp-pos'); ?></p>
        </div>
        <div class="wp-pos-control-panel-secondary">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-new-sale-v2')); ?>" class="wp-pos-button">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Nueva venta', 'wp-pos'); ?>
            </a>
            
            <div class="wp-pos-dropdown">
                <button class="wp-pos-button wp-pos-button-secondary wp-pos-dropdown-toggle">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Exportar', 'wp-pos'); ?>
                </button>
                <div class="wp-pos-dropdown-content">
                    <a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg(array('export' => 'csv', '_wpnonce' => wp_create_nonce('wp_pos_export_sales')))); ?>">
                        <?php _e('Exportar a CSV', 'wp-pos'); ?>
                    </a>
                    <a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg(array('export' => 'pdf', '_wpnonce' => wp_create_nonce('wp_pos_export_sales')))); ?>">
                        <?php _e('Exportar a PDF', 'wp-pos'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($action_message)): ?>
    <div class="wp-pos-message wp-pos-message-<?php echo esc_attr($action_type); ?>" style="background-color: <?php echo $action_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $action_type === 'success' ? '#155724' : '#721c24'; ?>; padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid <?php echo $action_type === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">  
        <p style="margin: 0;"><strong><?php echo esc_html($action_message); ?></strong></p>
        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <pre style="font-size: 11px; margin-top: 10px; overflow: auto; max-height: 150px; padding: 8px; background: rgba(0,0,0,0.05);">
Acción: <?php echo esc_html($action); ?>
ID de venta: <?php echo esc_html($sale_id); ?>
Estado: <?php echo esc_html($action_type); ?>
        </pre>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Filtros y búsqueda -->
    <div class="wp-pos-filters">
        <form class="wp-pos-search-form" action="" method="get">
            <input type="hidden" name="page" value="wp-pos-sales">
            <div class="wp-pos-search-input-container">
                <input type="text" name="search" value="<?php echo esc_attr($search); ?>" class="wp-pos-search-input" placeholder="<?php esc_attr_e('Buscar ventas...', 'wp-pos'); ?>">
                <button type="submit" class="wp-pos-search-button"><span class="dashicons dashicons-search"></span></button>
            </div>
            <div class="wp-pos-order-select-container">
                <label for="order_by"><?php _e('Ordenar por:', 'wp-pos'); ?></label>
                <select name="order_by" id="order_by" class="wp-pos-order-select" onchange="this.form.submit()">
                    <?php foreach ($order_options as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($order_by, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    
    <!-- Contenido principal -->
    <div class="wp-pos-content-panel">
        <?php if (empty($sales)): ?>
        <div class="wp-pos-no-results">
            <div class="wp-pos-no-results-icon">
                <span class="dashicons dashicons-clipboard"></span>
            </div>
            <h3><?php _e('No hay ventas que mostrar', 'wp-pos'); ?></h3>
            <p><?php _e('No se encontraron ventas con los criterios de búsqueda actuales.', 'wp-pos'); ?></p>
            <a href="<?php echo wp_pos_safe_esc_url(admin_url('admin.php?page=wp-pos-new-sale-v2')); ?>" class="wp-pos-button wp-pos-button-primary">
                <?php _e('Crear nueva venta', 'wp-pos'); ?>
            </a>
        </div>
        <?php else: ?>
        <?php
        // DEBUG: Verificar ventas antes de preparar para mostrar
        wp_pos_debug(array(
            'sales_before_prepare' => is_array($sales) ? count($sales) : 0,
            'sales_sample_before' => is_array($sales) && !empty($sales) ? array_slice($sales, 0, 2) : 'No hay ventas antes de preparar',
            'sales_type' => gettype($sales)
        ), 'SALES_BEFORE_PREPARE');
        
        // Preprocesar los datos de ventas para agregar el nombre del cliente
        $prepared_sales = wp_pos_prepare_sales_display($sales);
        
        // DEBUG: Verificar ventas después de preparar
        wp_pos_debug(array(
            'sales_after_prepare' => is_array($prepared_sales) ? count($prepared_sales) : 0,
            'sales_sample_after' => is_array($prepared_sales) && !empty($prepared_sales) ? array_slice($prepared_sales, 0, 2) : 'No hay ventas después de preparar',
            'prepared_sales_type' => gettype($prepared_sales)
        ), 'SALES_AFTER_PREPARE');
        
        // Asignar las ventas preparadas de vuelta a la variable original
        $sales = is_array($prepared_sales) ? $prepared_sales : array();
        
        // DEBUG: Verificar las ventas antes de mostrarlas
        wp_pos_debug(array(
            'final_sales_count' => count($sales),
            'final_sales_sample' => !empty($sales) ? array_slice($sales, 0, 2) : 'No hay ventas para mostrar',
            'is_array' => is_array($sales) ? 'Sí' : 'No',
            'is_object' => is_object($sales) ? 'Sí' : 'No',
            'is_countable' => is_countable($sales) ? 'Sí' : 'No',
            'gettype' => gettype($sales)
        ), 'FINAL_SALES_BEFORE_DISPLAY');
        
        // Mensaje de depuración directo
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<div class="notice notice-warning">';
            echo '<h3>Depuración de Ventas</h3>';
            echo '<p>Total de ventas cargadas: ' . count($sales) . '</p>';
            if (!empty($sales)) {
                echo '<h4>Primeras 2 ventas:</h4>';
                echo '<pre>';
                print_r(array_slice($sales, 0, 2));
                echo '</pre>';
            } else {
                echo '<p>No se encontraron ventas para mostrar.</p>';
                
                // Verificar la consulta SQL
                global $wpdb;
                $table_name = $wpdb->prefix . 'pos_sales';
                $total_sales = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                echo '<p>Total de ventas en la base de datos: ' . $total_sales . '</p>';
                
                // Mostrar algunas ventas de ejemplo directamente desde la base de datos
                $sample_sales = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 2");
                if ($sample_sales) {
                    echo '<h4>Ventas de ejemplo desde la base de datos:</h4>';
                    echo '<pre>';
                    print_r($sample_sales);
                    echo '</pre>';
                }
            }
            echo '</div>';
        }
        ?>
        <div class="wp-pos-table-container">
            <form id="wp-pos-bulk-actions-form" method="post">
                <?php wp_nonce_field('wp_pos_bulk_actions_nonce', 'wp_pos_bulk_actions_nonce'); ?>
                
                <div class="wp-pos-bulk-actions">
                    <select name="bulk_action" class="wp-pos-select">
                        <option value=""><?php _e('Acciones en masa', 'wp-pos'); ?></option>
                        <option value="cancel"><?php _e('Cancelar ventas seleccionadas', 'wp-pos'); ?></option>
                        <option value="delete"><?php _e('Eliminar ventas seleccionadas', 'wp-pos'); ?></option>
                    </select>
                    <button type="submit" class="wp-pos-bulk-actions-apply">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Aplicar', 'wp-pos'); ?>
                    </button>
                </div>
                
                <table class="wp-pos-sales-table">
                    <thead>
                        <tr>
                            <th class="wp-pos-checkbox-column">
                                <input type="checkbox" id="wp-pos-select-all" />
                            </th>
                            <th><?php _e('ID', 'wp-pos'); ?></th>
                            <th><?php _e('Fecha', 'wp-pos'); ?></th>
                            <th><?php _e('Cliente', 'wp-pos'); ?></th>
                            <th><?php _e('Items', 'wp-pos'); ?></th>
                            <th><?php _e('Total', 'wp-pos'); ?></th>
                            <th><?php _e('Estado', 'wp-pos'); ?></th>
                            <th class="wp-pos-actions-column"><?php _e('Acciones', 'wp-pos'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // DEBUG: Verificar el estado de $sales antes del bucle
                        wp_pos_debug(array(
                            'sales_count' => is_array($sales) ? count($sales) : 0,
                            'sales_type' => gettype($sales),
                            'sales_sample' => is_array($sales) && !empty($sales) ? array_slice($sales, 0, 2) : 'No hay ventas para mostrar'
                        ), 'SALES_BEFORE_FOREACH');
                        
                        foreach ((array)$sales as $sale): 
                            // DEBUG: Información del objeto de venta
                            wp_pos_debug($sale, 'SALE_OBJECT_DATA_' . ($sale->id ?? 'unknown'));
                            
                            // Asegurarse de que $sale sea un objeto
                            if (!is_object($sale) && is_array($sale)) {
                                $sale = (object)$sale;
                            }
                            
                            // Variables para verificar errores comunes
                            $sale_id = $sale->id ?? 0;
                            $nonce = wp_create_nonce('wp_pos_delete_sale_' . $sale_id);
                            $delete_url = wp_pos_safe_add_query_arg(array('action' => 'delete', 'sale_id' => $sale_id, '_wpnonce' => $nonce), admin_url('admin.php?page=wp-pos-sales'));
                            $view_url = wp_pos_safe_add_query_arg(array('id' => $sale_id), admin_url('admin.php?page=wp-pos-sale-details'));
                            // Usar la página independiente para la impresión de recibos
                            $print_url = plugins_url('/modules/receipts/receipt-standalone.php?id=' . $sale_id, WP_POS_PLUGIN_FILE);
                            $cancel_url = wp_pos_safe_add_query_arg(array('action' => 'cancel', 'sale_id' => $sale_id, '_wpnonce' => wp_create_nonce('wp_pos_cancel_sale_' . $sale_id)), admin_url('admin.php?page=wp-pos-sales'));
                            $status = $sale->status ?? 'completed';
                        ?>
                        <tr class="<?php echo $status === 'cancelled' ? 'wp-pos-sale-row-cancelled' : ''; ?>">
                            <td class="wp-pos-checkbox-column">
                                <input type="checkbox" name="sale_ids[]" value="<?php echo esc_attr($sale_id); ?>" class="wp-pos-sale-checkbox" <?php echo $status === 'cancelled' ? 'disabled' : ''; ?> />
                            </td>
                            <td data-label="<?php _e('ID', 'wp-pos'); ?>">
                                <strong>#<?php echo esc_html($sale->id ?? 'N/A'); ?></strong>
                                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                                <div class="wp-pos-debug-info" style="font-size: 10px; color: #666;">
                                    <code>sale_id: <?php echo esc_html($sale_id); ?></code><br>
                                    <code>nonce: <?php echo esc_html(substr($nonce, 0, 10)) . '...'; ?></code>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php _e('Fecha', 'wp-pos'); ?>">
                                <?php 
                                $date = $sale->date_created ?? $sale->date ?? ''; 
                                echo esc_html(wp_pos_format_date($date)); 
                                ?>
                            </td>
                            <td data-label="<?php _e('Cliente', 'wp-pos'); ?>">
                                <?php 
                                $customer_name = $sale->customer_name ?? '';
                                if (empty($customer_name) && !empty($sale->customer_id)) {
                                    $customer = get_user_by('id', $sale->customer_id);
                                    $customer_name = $customer ? $customer->display_name : '';
                                }
                                echo !empty($customer_name) ? esc_html($customer_name) : '<span class="na">' . __('Cliente anónimo', 'wp-pos') . '</span>'; 
                                ?>
                            </td>
                            <td data-label="<?php _e('Items', 'wp-pos'); ?>">
                                <?php 
                                // Usar items_count si está disponible, de lo contrario, intentar contar los ítems manualmente
                                if (isset($sale->items_count)) {
                                    echo esc_html($sale->items_count);
                                } else {
                                    $items = !empty($sale->items) ? maybe_unserialize($sale->items) : array();
                                    $items_count = is_array($items) ? count($items) : 0;
                                    echo esc_html($items_count);
                                }
                                ?>
                            </td>
                            <td data-label="<?php _e('Total', 'wp-pos'); ?>">
                                <?php 
                                $total = isset($sale->total) ? floatval($sale->total) : 0;
                                echo esc_html(wp_pos_format_currency($total)); 
                                ?>
                            </td>
                            <td data-label="<?php _e('Estado', 'wp-pos'); ?>" class="wp-pos-sale-status-cell">
                                <span class="wp-pos-sale-status wp-pos-sale-status-<?php echo esc_attr($status); ?>">
                                    <?php 
                                    $status_labels = array(
                                        'completed' => 'Completada',
                                        'pending' => 'Pendiente',
                                        'cancelled' => 'Cancelada',
                                        'refunded' => 'Reembolsada'
                                    );
                                    echo esc_html($status_labels[$status] ?? ucfirst($status)); 
                                    ?>
                                </span>
                            </td>
                            <td data-label="<?php _e('Acciones', 'wp-pos'); ?>" class="wp-pos-actions-column">
                                <div class="wp-pos-table-actions">
                                    <?php if ($sale_id) : ?>
                                    <a href="<?php echo esc_url($view_url); ?>" class="wp-pos-button wp-pos-button-small wp-pos-button-icon" title="<?php esc_attr_e('Ver detalles', 'wp-pos'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                    <a href="<?php echo esc_url($print_url); ?>" class="wp-pos-button wp-pos-button-small wp-pos-button-icon" title="<?php esc_attr_e('Imprimir recibo', 'wp-pos'); ?>" target="_blank">
                                        <span class="dashicons dashicons-printer"></span>
                                    </a>
                                    <?php if ($status !== 'cancelled' && $status !== 'refunded') : ?>
                                    <a href="<?php echo esc_url($cancel_url); ?>" class="wp-pos-button wp-pos-button-small wp-pos-button-icon wp-pos-button-warning wp-pos-confirm-action" title="<?php esc_attr_e('Cancelar venta', 'wp-pos'); ?>" data-message="<?php esc_attr_e('¿Estás seguro de que deseas cancelar esta venta?', 'wp-pos'); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url($delete_url); ?>" class="wp-pos-button wp-pos-button-small wp-pos-button-icon wp-pos-button-danger wp-pos-confirm-action" title="<?php esc_attr_e('Eliminar venta', 'wp-pos'); ?>" data-message="<?php esc_attr_e('¿Estás seguro de que deseas eliminar esta venta? Esta acción no se puede deshacer.', 'wp-pos'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            
            <!-- Paginación mejorada -->
            <div class="wp-pos-pagination-container">
                <div class="wp-pos-pagination-info">
                    <?php
                    $start_item = ($current_page - 1) * $per_page + 1;
                    $end_item = min($current_page * $per_page, $total_items);
                    
                    if ($end_item > 0) {
                        printf(
                            __('Mostrando %1$s a %2$s de %3$s resultados', 'wp-pos'),
                            '<strong>' . $start_item . '</strong>',
                            '<strong>' . $end_item . '</strong>',
                            '<strong>' . $total_items . '</strong>'
                        );
                    } else {
                        echo '<strong>' . __('No hay resultados que mostrar', 'wp-pos') . '</strong>';
                    }
                    ?>
                </div>
                
                <?php if ($total_pages > 1) : // Solo mostrar numeración si hay más de una página ?>
                <div class="wp-pos-pagination">
                    <?php 
                    // Crear enlaces de paginación conservando todos los parámetros de filtro y orden
                    $pagination_base_url = admin_url('admin.php?page=wp-pos-sales');
                    
                    // Añadir parámetros de filtro si existen
                    if (!empty($search)) {
                        $pagination_base_url = wp_pos_safe_add_query_arg('search', $search, $pagination_base_url);
                    }
                    if (!empty($status)) {
                        $pagination_base_url = wp_pos_safe_add_query_arg('filter_status', $status, $pagination_base_url);
                    }
                    if (!empty($date_from)) {
                        $pagination_base_url = wp_pos_safe_add_query_arg('date_from', $date_from, $pagination_base_url);
                    }
                    if (!empty($date_to)) {
                        $pagination_base_url = wp_pos_safe_add_query_arg('date_to', $date_to, $pagination_base_url);
                    }
                    if (!empty($order_by)) {
                        $pagination_base_url = wp_pos_safe_add_query_arg('order_by', $order_by, $pagination_base_url);
                    }
                    
                    // Primera página
                    if ($current_page > 3) : 
                        $first_page_url = wp_pos_safe_add_query_arg('paged', 1, $pagination_base_url); 
                    ?>
                    <a href="<?php echo wp_pos_safe_esc_url($first_page_url); ?>" class="page-numbers first-page" title="<?php esc_attr_e('Primera página', 'wp-pos'); ?>">
                        <span class="dashicons dashicons-controls-skipback"></span>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Anterior -->
                    <?php if ($current_page > 1) : 
                        $prev_page_url = wp_pos_safe_add_query_arg('paged', $current_page - 1, $pagination_base_url); 
                    ?>
                    <a href="<?php echo wp_pos_safe_esc_url($prev_page_url); ?>" class="prev page-numbers" title="<?php esc_attr_e('Página anterior', 'wp-pos'); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    // Lógica para mostrar un número limitado de páginas
                    $range = 2; // Número de páginas a mostrar antes y después de la actual
                    
                    // Mostrar puntos suspensivos al inicio si es necesario
                    if ($current_page > $range + 1) : 
                        $page_url = wp_pos_safe_add_query_arg('paged', 1, $pagination_base_url);
                    ?>
                        <a href="<?php echo wp_pos_safe_esc_url($page_url); ?>" class="page-numbers">1</a>
                        <?php if ($current_page > $range + 2) : ?>
                            <span class="page-numbers dots">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Páginas cercanas a la actual -->
                    <?php
                    $start = max(1, $current_page - $range);
                    $end = min($total_pages, $current_page + $range);
                    
                    for ($i = $start; $i <= $end; $i++) :
                        $is_current = $i === $current_page;
                        $page_url = wp_pos_safe_add_query_arg('paged', $i, $pagination_base_url);
                    ?>
                        <a href="<?php echo wp_pos_safe_esc_url($page_url); ?>" class="page-numbers <?php echo $is_current ? 'current' : ''; ?>" <?php echo $is_current ? 'aria-current="page"' : ''; ?>>
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Mostrar puntos suspensivos al final si es necesario -->
                    <?php if ($current_page < $total_pages - $range) : 
                        $page_url = wp_pos_safe_add_query_arg('paged', $total_pages, $pagination_base_url);
                    ?>
                        <?php if ($current_page < $total_pages - $range - 1) : ?>
                            <span class="page-numbers dots">...</span>
                        <?php endif; ?>
                        <a href="<?php echo wp_pos_safe_esc_url($page_url); ?>" class="page-numbers"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <!-- Siguiente -->
                    <?php if ($current_page < $total_pages) : 
                        $next_page_url = wp_pos_safe_add_query_arg('paged', $current_page + 1, $pagination_base_url); 
                    ?>
                    <a href="<?php echo wp_pos_safe_esc_url($next_page_url); ?>" class="next page-numbers" title="<?php esc_attr_e('Página siguiente', 'wp-pos'); ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Última página -->
                    <?php if ($current_page < $total_pages - 2) : 
                        $last_page_url = wp_pos_safe_add_query_arg('paged', $total_pages, $pagination_base_url); 
                    ?>
                    <a href="<?php echo wp_pos_safe_esc_url($last_page_url); ?>" class="page-numbers last-page" title="<?php esc_attr_e('Última página', 'wp-pos'); ?>">
                        <span class="dashicons dashicons-controls-skipforward"></span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php /* Panel de depuración removido */ ?>

        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Los estilos ahora se cargan desde el archivo CSS externo -->

<script>
    jQuery(document).ready(function($) {
        // Seleccionar/deseleccionar todos los checkboxes
        $('#wp-pos-select-all').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.wp-pos-sale-checkbox:not(:disabled)').prop('checked', isChecked);
        });
        
        // Confirmar acción en masa antes de enviar
        $('#wp-pos-bulk-actions-form').on('submit', function(e) {
            var action = $('select[name="bulk_action"]').val();
            var checkedItems = $('.wp-pos-sale-checkbox:checked').length;
            
            if (action === '' || checkedItems === 0) {
                e.preventDefault();
                alert('<?php echo esc_js(__('Por favor, seleccione una acción y al menos una venta.', 'wp-pos')); ?>');
                return false;
            }
            
            var confirmMessage = '';
            if (action === 'cancel') {
                confirmMessage = '<?php echo esc_js(__('¿Está seguro de que desea cancelar las ventas seleccionadas?', 'wp-pos')); ?>';
            } else if (action === 'delete') {
                confirmMessage = '<?php echo esc_js(__('¿Está seguro de que desea eliminar las ventas seleccionadas? Esta acción no se puede deshacer.', 'wp-pos')); ?>';
            }
            
            return confirm(confirmMessage);
        });
    });
</script>
<?php
// Cargar footer
wp_pos_template_footer();
