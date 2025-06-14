<?php
/**
 * Código de restauración para el módulo de cierres
 * Contiene la función ajax_get_closures corregida
 */

/**
 * AJAX: Obtener historial de cierres
 */
public function ajax_get_closures() {
    // Verificar nonce
    check_ajax_referer('wp_pos_closures_nonce', 'nonce');
    
    // Verificar permisos
    if (!current_user_can('manage_pos') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'wp-pos')]);
    }
    
    // Parámetros de filtrado y paginación
    $register_id = isset($_REQUEST['register_id']) ? intval($_REQUEST['register_id']) : 0;
    $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
    $date_from = isset($_REQUEST['date_from']) ? sanitize_text_field($_REQUEST['date_from']) : '';
    $date_to = isset($_REQUEST['date_to']) ? sanitize_text_field($_REQUEST['date_to']) : '';
    $status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
    $page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
    $per_page = isset($_REQUEST['per_page']) ? intval($_REQUEST['per_page']) : 10;
    
    // Construir consulta base para contar registros
    global $wpdb;
    $count_query = "SELECT COUNT(*) 
                    FROM {$wpdb->prefix}pos_closures c
                    WHERE 1=1";
    
    // Consulta para obtener datos
    $query = "SELECT c.*, 
                u.display_name as user_name,
                r.name as register_name 
            FROM {$wpdb->prefix}pos_closures c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}pos_registers r ON c.register_id = r.id
            WHERE 1=1";
    
    $query_args = [];
    $count_args = [];
    
    // Aplicar filtros
    if ($register_id > 0) {
        $where_clause = " AND c.register_id = %d";
        $query .= $where_clause;
        $count_query .= $where_clause;
        $query_args[] = $register_id;
        $count_args[] = $register_id;
    }
    
    if ($user_id > 0) {
        $where_clause = " AND c.user_id = %d";
        $query .= $where_clause;
        $count_query .= $where_clause;
        $query_args[] = $user_id;
        $count_args[] = $user_id;
    }
    
    if (!empty($date_from)) {
        $where_clause = " AND DATE(c.created_at) >= %s";
        $query .= $where_clause;
        $count_query .= $where_clause;
        $query_args[] = $date_from;
        $count_args[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_clause = " AND DATE(c.created_at) <= %s";
        $query .= $where_clause;
        $count_query .= $where_clause;
        $query_args[] = $date_to;
        $count_args[] = $date_to;
    }
    
    if (!empty($status)) {
        $where_clause = " AND c.status = %s";
        $query .= $where_clause;
        $count_query .= $where_clause;
        $query_args[] = $status;
        $count_args[] = $status;
    }
    
    // Ordenar por fecha (más reciente primero)
    $query .= " ORDER BY c.created_at DESC";
    
    // Aplicar paginación
    $offset = ($page - 1) * $per_page;
    $query .= " LIMIT %d, %d";
    $query_args[] = $offset;
    $query_args[] = $per_page;
    
    // Preparar consultas
    $prepared_query = !empty($query_args) ? $wpdb->prepare($query, $query_args) : $query;
    $prepared_count_query = !empty($count_args) ? $wpdb->prepare($count_query, $count_args) : $count_query;
    
    // Ejecutar consultas
    $closures = $wpdb->get_results($prepared_query, ARRAY_A);
    $total_items = (int) $wpdb->get_var($prepared_count_query);
    $total_pages = ceil($total_items / $per_page);
    
    // Enviar respuesta - VERSIÓN CORREGIDA SIN DUPLICADOS
    wp_send_json_success([
        'closures' => $closures,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'debug_info' => [
            'filtered_query' => $prepared_query,
            'count_query' => $prepared_count_query
        ]
    ]);
}
