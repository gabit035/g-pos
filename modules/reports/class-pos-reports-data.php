<?php
// Prevención de acceso directo
if (!defined('ABSPATH')) exit;

class WP_POS_Reports_Data {
    
    /**
     * Cache de datos para evitar consultas duplicadas
     */
    private static $cache = array();
    
    /**
     * Obtener método de pago de manera consistente
     * Basado en la lógica exitosa de sale-details.php
     */
    public static function get_payment_method_from_sale($sale_id, $sale_data = null) {
        global $wpdb;
        
        // Si se proporciona sale_data, intentar extraer de ahí primero
        if ($sale_data && is_array($sale_data)) {
            // Método directo
            if (!empty($sale_data['payment_method'])) {
                return $sale_data['payment_method'];
            }
        }
        
        // Consultar tabla de pagos (como en sale-details.php)
        $payments_table = $wpdb->prefix . 'pos_payments';
        $payments_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$payments_table'") === $payments_table;
        
        if ($payments_table_exists && $sale_id) {
            $payment = $wpdb->get_row($wpdb->prepare(
                "SELECT payment_method FROM $payments_table WHERE sale_id = %d LIMIT 1",
                $sale_id
            ), ARRAY_A);
            
            if ($payment && !empty($payment['payment_method'])) {
                return $payment['payment_method'];
            }
        }
        
        // Fallback a la tabla principal de ventas
        if ($sale_id) {
            $sales_table = $wpdb->prefix . 'pos_sales';
            $sale = $wpdb->get_row($wpdb->prepare(
                "SELECT payment_method FROM $sales_table WHERE id = %d",
                $sale_id
            ), ARRAY_A);
            
            if ($sale && !empty($sale['payment_method'])) {
                return $sale['payment_method'];
            }
        }
        
        // Si todo falla, devolver 'efectivo' como defecto
        return 'efectivo';
    }
    
    /**
     * Formatear método de pago para mostrar (usando la lógica exitosa)
     */
    public static function format_payment_method($method) {
        if (empty($method)) {
            return '<span class="no-payment-method">' . __('No especificado', 'wp-pos') . '</span>';
        }
        
        // Mapeo de métodos (expandible)
        $methods_map = [
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'credit' => 'Crédito',
            'debit' => 'Débito',
            'check' => 'Cheque',
            'other' => 'Otro'
        ];
        
        $method_lower = strtolower(trim($method));
        
        if (isset($methods_map[$method_lower])) {
            return $methods_map[$method_lower];
        }
        
        // Si no está en el mapeo, capitalizar primera letra (como en sale-details.php)
        return ucfirst($method);
    }
    
    /**
     * Función temporal para depuración - verificar datos en la tabla de ventas
     */
    private static function debug_sales_data() {
        global $wpdb;
        
        $results = [];
        
        // Verificar si la tabla existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}pos_sales'");
        $results['table_exists'] = !empty($table_exists);
        
        if ($results['table_exists']) {
            // Contar registros totales
            $results['total_records'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pos_sales");
            
            // Obtener algunos registros de ejemplo
            $results['sample_records'] = $wpdb->get_results("SELECT id, date_created, total, status FROM {$wpdb->prefix}pos_sales ORDER BY id DESC LIMIT 5", ARRAY_A);
            
            // Verificar estructura de la tabla
            $results['table_structure'] = $wpdb->get_results("DESCRIBE {$wpdb->prefix}pos_sales", ARRAY_A);
        }
        
        error_log('=== DATOS DE DEPURACIÓN DE VENTAS ===');
        error_log(print_r($results, true));
        error_log('======================================');
        
        return $results;
    }
    
    /**
     * Construye la cláusula WHERE para el método de pago
     * @param string $payment_method Valor del filtro
     * @return array [where_condition, params] o ['clause' => '', 'params' => []]
     */
    private static function build_payment_method_where($payment_method) {
        // Usar el procesador de filtros unificado
        $processor = new WP_POS_Reports_Filter_Processor();
        return $processor->build_payment_method_where_unified($payment_method, 's');
    }
    
    public static function get_totals($args = []) {
        global $wpdb;
        
        error_log('=== INICIO get_totals CORREGIDO ===');
        error_log('Argumentos recibidos: ' . print_r($args, true));
        
        // Filtros por defecto mejorados
        $defaults = [
            'date_from' => date('Y-m-d 00:00:00'),
            'date_to'   => date('Y-m-d 23:59:59'),
            'payment_method' => 'all',
            'seller_id' => 'all',
            'status'    => 'completed',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Cache key para evitar consultas duplicadas
        $cache_key = 'wp_pos_totals_' . md5(serialize($args));
        
        // Verificar caché de WordPress primero
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            error_log('Devolviendo datos desde caché de WordPress');
            return $cached;
        }
        
        // Verificar caché en memoria
        if (isset(self::$cache[$cache_key])) {
            error_log('Devolviendo datos desde caché en memoria');
            return self::$cache[$cache_key];
        }
        
        $sales_table = $wpdb->prefix . 'pos_sales';
        
        // Verificar si las tablas existen con una consulta más segura
        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}pos_%'");
        $sales_table_exists = in_array($sales_table, $tables);
        
        error_log('Tablas encontradas: ' . print_r($tables, true));
        error_log("Tabla $sales_table existe: " . ($sales_table_exists ? 'Sí' : 'No'));
        
        if (!$sales_table_exists) {
            $error_msg = 'G-POS Reports: La tabla de ventas no existe: ' . $sales_table;
            error_log($error_msg);
            
            return [
                'success' => false,
                'message' => 'Las tablas de ventas no existen: ' . $sales_table,
                'sales_count' => 0,
                'total_revenue' => 0,
                'total_profit' => 0,
                'profit_margin' => 0,
                'average_sale' => 0,
                'debug' => [
                    'sales_table' => $sales_table,
                    'tables_exist' => [
                        'sales' => $sales_table_exists,
                    ],
                    'all_tables' => $tables,
                    'wpdb_prefix' => $wpdb->prefix
                ]
            ];
        }
        
        // CORREGIDO: Construir la consulta para obtener los totales con filtros aplicados
        $where_conditions = [];
        $params = [];
        
        // Filtro por fechas mejorado
        if (!empty($args['date_from']) && !empty($args['date_to'])) {
            $date_from = self::normalize_date($args['date_from'], '00:00:00');
            $date_to = self::normalize_date($args['date_to'], '23:59:59');
            
            $where_conditions[] = "(DATE(s.date_created) BETWEEN %s AND %s)";
            $params[] = date('Y-m-d', strtotime($date_from));
            $params[] = date('Y-m-d', strtotime($date_to));
        }
        
        // Filtro por estado mejorado
        if ($args['status'] !== 'all') {
            if ($args['status'] === 'completed') {
                $where_conditions[] = "(s.status = 'completed' OR s.status = '' OR s.status IS NULL)";
            } else {
                $where_conditions[] = "s.status = %s";
                $params[] = $args['status'];
            }
        }
        
        // CORREGIDO: Filtro por método de pago (mejorado para manejar diferentes formatos)
        if (isset($args['payment_method']) && $args['payment_method'] !== 'all') {
            error_log('=== PROCESANDO FILTRO DE MÉTODO DE PAGO ===');
            error_log('Valor del filtro: ' . $args['payment_method']);
            
            $payment_where = self::build_payment_method_where($args['payment_method']);
            
            if (!empty($payment_where['clause'])) {
                $where_conditions[] = $payment_where['clause'];
                if (!empty($payment_where['params'])) {
                    $params = array_merge($params, $payment_where['params']);
                }
                error_log('Cláusula SQL generada: ' . $payment_where['clause']);
                error_log('Parámetros: ' . print_r($payment_where['params'], true));
            } else {
                error_log('No se generó ninguna cláusula para el método de pago');
            }
        } else {
            error_log('No se aplicó filtro de método de pago (valor: ' . ($args['payment_method'] ?? 'no definido') . ')');
        }
        
        // CORREGIDO: Filtro por vendedor  
        if (isset($args['seller_id']) && $args['seller_id'] !== 'all') {
            if (is_numeric($args['seller_id'])) {
                $where_conditions[] = "s.user_id = %d";
                $params[] = intval($args['seller_id']);
                error_log("FILTRO APLICADO - Vendedor ID: " . intval($args['seller_id']));
            } else {
                // Buscar por login de usuario
                $user = get_user_by('login', $args['seller_id']);
                if ($user) {
                    $where_conditions[] = "s.user_id = %d";
                    $params[] = $user->ID;
                    error_log("FILTRO APLICADO - Vendedor '{$args['seller_id']}' convertido a ID: {$user->ID}");
                }
            }
        }
        
        // Construir la cláusula WHERE
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Verificar si necesitamos unir con la tabla de pagos
        $join_payments = '';
        if (isset($args['payment_method']) && $args['payment_method'] !== 'all') {
            $payments_table = $wpdb->prefix . 'pos_payments';
            $join_payments = " LEFT JOIN {$payments_table} p ON s.id = p.sale_id ";
        }
        
        // Consulta optimizada para obtener los totales - Incluye manejo de método de pago
        $query = "SELECT 
                    COUNT(DISTINCT s.id) as sales_count,
                    COALESCE(SUM(CASE WHEN s.status != 'cancelled' THEN s.total ELSE 0 END), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN s.status != 'cancelled' THEN (s.total * 0.70) ELSE 0 END), 0) as total_profit,
                    s.payment_method
                FROM {$sales_table} s
                {$join_payments}
                {$where_clause}
                GROUP BY s.payment_method";
                
        // Debug: Registrar la consulta y parámetros
        error_log('G-POS Reports Query: ' . $query);
        error_log('Parámetros de la consulta: ' . print_r($params, true));
        
        // Preparar y ejecutar consulta con manejo de errores mejorado
        try {
            if (!empty($params)) {
                // Usar wpdb::prepare para consultas seguras
                $prepared_query = $wpdb->prepare($query, $params);
                error_log('Consulta preparada: ' . $prepared_query);
                $result = $wpdb->get_row($prepared_query, ARRAY_A);
            } else {
                $result = $wpdb->get_row($query, ARRAY_A);
            }
            
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
            
            if (empty($result)) {
                error_log('La consulta no devolvió resultados');
                $result = [
                    'sales_count' => 0,
                    'total_revenue' => 0,
                    'total_profit' => 0
                ];
            }
            
        } catch (Exception $e) {
            error_log('Error en la consulta SQL: ' . $e->getMessage());
            
            // Si hay un error, devolver datos vacíos pero con un mensaje de error
            return [
                'success' => false,
                'message' => 'Error al consultar la base de datos: ' . $e->getMessage(),
                'sales_count' => 0,
                'total_revenue' => 0,
                'total_profit' => 0,
                'profit_margin' => 0,
                'average_sale' => 0,
                'debug' => [
                    'query' => $query,
                    'params' => $params,
                    'error' => $e->getMessage(),
                    'last_error' => $wpdb->last_error,
                    'last_query' => $wpdb->last_query
                ]
            ];
        }
        
        // Calcular métricas adicionales con manejo de errores
        $total_revenue = isset($result['total_revenue']) ? floatval($result['total_revenue']) : 0;
        $total_profit = isset($result['total_profit']) ? floatval($result['total_profit']) : 0;
        $sales_count = isset($result['sales_count']) ? intval($result['sales_count']) : 0;
        
        $profit_margin = $total_revenue > 0 ? ($total_profit / $total_revenue) * 100 : 0;
        $average_sale = $sales_count > 0 ? $total_revenue / $sales_count : 0;
        
        // Preparar los datos de respuesta con información de depuración
        $data = [
            'success' => true,
            'message' => $sales_count > 0 ? '' : 'No se encontraron ventas para los filtros seleccionados',
            'sales_count' => $sales_count,
            'total_revenue' => $total_revenue,
            'total_profit' => $total_profit,
            'profit_margin' => round($profit_margin, 2),
            'average_sale' => round($average_sale, 2),
            'debug_info' => [
                'query' => isset($prepared_query) ? $prepared_query : $query,
                'params' => $params,
                'filters' => $args,
                'timestamp' => current_time('mysql'),
                'tables' => [
                    'sales' => $sales_table,
                ]
            ]
        ];

        // Guardar en caché solo si no hay errores
        if ($data['success']) {
            try {
                // Guardar en caché de WordPress (5 minutos)
                set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);
                // Guardar en caché en memoria
                self::$cache[$cache_key] = $data;
                error_log('Datos guardados en caché con éxito');
            } catch (Exception $e) {
                error_log('Error al guardar en caché: ' . $e->getMessage());
                // Continuar aunque falle el caché
            }
        }
        
        error_log('=== FIN get_totals CORREGIDO ===');
        error_log('Resultado: ' . print_r([
            'sales_count' => $data['sales_count'],
            'total_revenue' => $data['total_revenue'],
            'total_profit' => $data['total_profit'],
            'filters_applied' => $args
        ], true));
        
        return $data;
    }
        
    public static function get_recent_sales($args = []) {
        global $wpdb;

        error_log('=== INICIO get_recent_sales CORREGIDO ===');
        error_log('Argumentos recibidos: ' . print_r($args, true));

        // Valores por defecto con validación
        $defaults = [
            'date_from' => '',
            'date_to'   => '',
            'status'    => 'completed',
            'seller_id' => 'all',
            'payment_method' => 'all',
            'limit'     => 10
        ];

        $args = wp_parse_args($args, $defaults);
        $args['limit'] = absint($args['limit']);
        if ($args['limit'] > 50) $args['limit'] = 50;

        // Generar clave de caché única
        $cache_key = 'wp_pos_recent_sales_' . md5(serialize($args));
        
        // Verificar caché en memoria primero
        if (isset(self::$cache[$cache_key])) {
            error_log('Devolviendo desde caché en memoria');
            return self::$cache[$cache_key];
        }
        
        // Verificar caché de WordPress
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            self::$cache[$cache_key] = $cached;
            error_log('Devolviendo desde caché de WordPress');
            return $cached;
        }

        try {
            // Nombres de tablas
            $sales_table = $wpdb->prefix . 'pos_sales';
            $sale_items_table = $wpdb->prefix . 'pos_sale_items';
            $payments_table = $wpdb->prefix . 'pos_payments';
            $users_table = $wpdb->users;

        // Verificar si las tablas existen
        $tables_exist = $wpdb->get_var("SHOW TABLES LIKE '$sales_table'") === $sales_table;

        if (!$tables_exist) {
            error_log('La tabla de ventas no existe');
            return [
                'success' => false,
                'message' => 'Las tablas de ventas no están disponibles',
                'recent_sales' => [],
                'sales' => [],
                'count' => 0
            ];
        }

        // Inicializar la consulta SQL
        $query = "SELECT s.*, u.display_name as seller_name 
                FROM $sales_table s 
                LEFT JOIN $users_table u ON s.user_id = u.ID 
                WHERE 1=1";
        
        $query_params = [];
        
        // Aplicar filtros
        if (!empty($args['status'])) {
            if ($args['status'] === 'completed') {
                    $query .= " AND (s.status = 'completed' OR s.status = '' OR s.status IS NULL)";
                } else {
                    $query .= " AND s.status = %s";
                    $query_params[] = $args['status'];
                }
            }


            if (!empty($args['date_from'])) {
                $query .= " AND DATE(s.date_created) >= %s";
                $query_params[] = date('Y-m-d', strtotime($args['date_from']));
            }

            if (!empty($args['date_to'])) {
                $query .= " AND DATE(s.date_created) <= %s";
                $query_params[] = date('Y-m-d', strtotime($args['date_to']));
            }

            // Filtro por vendedor
            if (isset($args['seller_id']) && $args['seller_id'] !== 'all') {
                if (is_numeric($args['seller_id'])) {
                    $query .= " AND s.user_id = %d";
                    $query_params[] = intval($args['seller_id']);
                    error_log("FILTRO APLICADO - Vendedor ID: " . intval($args['seller_id']));
                } else {
                    // Buscar por login de usuario
                    $user = get_user_by('login', $args['seller_id']);
                    if ($user) {
                        $query .= " AND s.user_id = %d";
                        $query_params[] = $user->ID;
                        error_log("FILTRO APLICADO - Vendedor '{$args['seller_id']}' convertido a ID: {$user->ID}");
                    }
                }
            }

            // Aplicar filtro de método de pago
            if (isset($args['payment_method']) && $args['payment_method'] !== 'all') {
                $payment_where = self::build_payment_method_where($args['payment_method']);
                if (!empty($payment_where['clause'])) {
                    $query .= ' AND ' . $payment_where['clause'];
                    $query_params = array_merge($query_params, $payment_where['params']);
                    error_log("FILTRO APLICADO - Método de pago: '" . $args['payment_method'] . "' (mapeado a: " . implode(', ', $payment_where['params']) . ")");
                }
            }

            // Ordenar y limitar
            $query .= " ORDER BY s.date_created DESC LIMIT %d";
            $query_params[] = $args['limit'];

            error_log('Consulta SQL: ' . $query);
            error_log('Parámetros: ' . print_r($query_params, true));

            // Preparar y ejecutar
            if (!empty($query_params)) {
                $prepared_query = $wpdb->prepare($query, $query_params);
                $sales = $wpdb->get_results($prepared_query, ARRAY_A);
            } else {
                $sales = $wpdb->get_results($query, ARRAY_A);
            }

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            error_log('Ventas encontradas: ' . count($sales));

            // Obtener recuentos de ítems si hay ventas
            $items_counts = [];
            if (!empty($sales)) {
                $sale_ids = array_column($sales, 'id');
                
                // Verificar si la tabla de items existe
                if ($wpdb->get_var("SHOW TABLES LIKE '$sale_items_table'") === $sale_items_table) {
                    $placeholders = implode(',', array_fill(0, count($sale_ids), '%d'));
                    
                    $items_query = $wpdb->prepare(
                        "SELECT sale_id, COUNT(*) as item_count FROM $sale_items_table WHERE sale_id IN ($placeholders) GROUP BY sale_id",
                        $sale_ids
                    );

                    $items_results = $wpdb->get_results($items_query, ARRAY_A);
                    foreach ($items_results as $item_result) {
                        $items_counts[$item_result['sale_id']] = intval($item_result['item_count']);
                    }
                }
            }

            // Formatear los resultados - CORREGIDO PARA MÉTODOS DE PAGO
            $formatted_sales = [];
            foreach ($sales as $sale) {
                // Obtener nombre del cliente
                $customer_name = 'Cliente no registrado';
                if (!empty($sale['customer_id'])) {
                    $customer = get_user_by('ID', $sale['customer_id']);
                    if ($customer) {
                        $customer_name = $customer->display_name ?: $customer->user_login;
                    }
                }

                // Obtener conteo de items
                $items_count = isset($items_counts[$sale['id']]) ? $items_counts[$sale['id']] : 0;

                // CORREGIR MÉTODO DE PAGO - usar la nueva función helper
                $payment_method_raw = self::get_payment_method_from_sale($sale['id'], $sale);
                $payment_method_display = self::format_payment_method($payment_method_raw);

                // Formatear datos de la venta y asegurar que la fecha sea válida
                
                // Validar y formatear la fecha correctamente
                $date_value = !empty($sale['date_created']) ? $sale['date_created'] : $sale['date'];
                
                // Verificar que la fecha sea válida
                $valid_date = !empty($date_value) && $date_value !== '0000-00-00 00:00:00';
                $formatted_date = $valid_date ? date('Y-m-d H:i:s', strtotime($date_value)) : current_time('mysql');
                
                // Log para debugging
                error_log("Fecha original: {$date_value}, Fecha validada: {$formatted_date}");
                
                $formatted_sale = [
                    'id' => (int)$sale['id'],
                    'date' => $formatted_date,
                    'created_at' => $formatted_date,
                    'total' => (float)$sale['total'],
                    'status' => $sale['status'],
                    'payment_method' => $payment_method_raw,
                    'payment_method_display' => $payment_method_display,
                    'payment_type' => $payment_method_raw,
                    'customer_id' => (int)$sale['customer_id'],
                    'display_name' => $customer_name,
                    'customer_name' => $customer_name,
                    'seller_id' => (int)$sale['seller_id'],
                    'seller' => $sale['seller_name'],
                    'seller_name' => $sale['seller_name'],
                    'discount' => (float)($sale['discount'] ?? 0),
                    'tax' => (float)($sale['tax'] ?? 0),
                    'note' => $sale['note'] ?? '',
                    'items_count' => $items_count,
                    'items_total' => (float)$sale['total']
                ];

                $formatted_sales[] = $formatted_sale;
            }

            // Preparar respuesta exitosa
            $response = [
                'success' => true,
                'message' => 'Ventas obtenidas correctamente',
                'recent_sales' => $formatted_sales,
                'sales' => $formatted_sales,
                'count' => count($formatted_sales),
                'filters_applied' => $args
            ];

            // Almacenar en caché por 2 minutos
            set_transient($cache_key, $response, 2 * MINUTE_IN_SECONDS);
            self::$cache[$cache_key] = $response;

            error_log('=== FIN get_recent_sales CORREGIDO (ÉXITO) ===');
            error_log('Respuesta: ' . print_r(['count' => $response['count'], 'success' => true, 'filters' => $args], true));

            return $response;

        } catch (Exception $e) {
            error_log('Error en get_recent_sales: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al obtener las ventas recientes: ' . $e->getMessage(),
                'sales' => [],
                'recent_sales' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }




    /**
     * Construir cláusula WHERE para vendedor
     */
    private static function build_seller_where($seller_id) {
        if (empty($seller_id) || $seller_id === 'all') {
            return ['clause' => '', 'params' => []];
        }

        // Si es numérico, buscar por ID
        if (is_numeric($seller_id)) {
            return [
                'clause' => 's.user_id = %d',
                'params' => [intval($seller_id)]
            ];
        }

        // Si es string, buscar por login
        $user = get_user_by('login', $seller_id);
        if ($user) {
            return [
                'clause' => 's.user_id = %d',
                'params' => [$user->ID]
            ];
        }

        return ['clause' => '', 'params' => []];
    }
    
    /**
     * Obtiene los métodos de pago disponibles (mejorado)
     */
    public static function get_payment_methods() {
        // Cache para evitar consultas repetidas
        static $methods_cache = null;
        
        if ($methods_cache !== null) {
            return $methods_cache;
        }
        
        $methods = [
            'cash' => __('Efectivo', 'wp-pos'),
            'card' => __('Tarjeta', 'wp-pos'),
            'transfer' => __('Transferencia', 'wp-pos'),
            'check' => __('Cheque', 'wp-pos'),
            'other' => __('Otro', 'wp-pos'),
        ];
        
        // Obtener métodos de pago únicos de la base de datos
        global $wpdb;
        $sales_table = $wpdb->prefix . 'pos_sales';
        
        // Verificar si la tabla existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sales_table'");
        
        if ($table_exists) {
            // Obtener métodos de pago únicos de la columna payment_method
            $db_methods = $wpdb->get_col("SELECT DISTINCT payment_method FROM $sales_table WHERE payment_method IS NOT NULL AND payment_method != '' LIMIT 20");
            
            // Agregar métodos únicos al array de métodos
            if (!empty($db_methods)) {
                $db_methods = array_unique(array_filter($db_methods));
                foreach ($db_methods as $method) {
                    // Solo agregar si no existe ya en los métodos por defecto
                    $method_lower = strtolower($method);
                    if (!isset($methods[$method_lower]) && !in_array($method, $methods)) {
                        $methods[$method_lower] = ucfirst($method);
                    }
                }
            }
        }
        
        $methods_cache = apply_filters('wp_pos_payment_methods', $methods);
        return $methods_cache;
    }
    
    /**
     * Normaliza una fecha al formato Y-m-d H:i:s
     */
    private static function normalize_date($date, $time = '00:00:00') {
        if (empty($date)) {
            return current_time('mysql');
        }
        
        // Si la fecha ya tiene hora, usarla directamente
        if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $date)) {
            return $date;
        }
        
        // Si solo tiene fecha, agregar la hora
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $date)) {
            return $date . ' ' . $time;
        }
        
        // Si no es un formato reconocido, devolver la fecha actual
        return current_time('mysql');
    }
    
    /**
     * Limpiar cache de datos
     */
    public static function clear_cache() {
        self::$cache = array();
        
        // Limpiar transients relacionados
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wp_pos_%'");
    }



    







    /**
 * Diagnóstico completo de métodos de pago en la base de datos
 */
public static function debug_payment_methods_in_db($detailed = true) {
    global $wpdb;
    
    $results = [
        'success' => false,
        'message' => '',
        'debug_info' => [],
        'payment_methods' => [],
        'statistics' => [],
        'recommendations' => []
    ];
    
    try {
        error_log('=== INICIO DIAGNÓSTICO MÉTODOS DE PAGO ===');
        
        // Verificar tablas
        $sales_table = $wpdb->prefix . 'pos_sales';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sales_table'") === $sales_table;
        
        $results['debug_info']['sales_table'] = $sales_table;
        $results['debug_info']['table_exists'] = $table_exists;
        
        if (!$table_exists) {
            $results['message'] = 'La tabla de ventas no existe: ' . $sales_table;
            error_log('ERROR: ' . $results['message']);
            return $results;
        }
        
        // Contar registros totales
        $total_sales = $wpdb->get_var("SELECT COUNT(*) FROM $sales_table");
        $results['debug_info']['total_sales'] = intval($total_sales);
        
        if ($total_sales == 0) {
            $results['message'] = 'No hay ventas en la base de datos';
            $results['success'] = true;
            return $results;
        }
        
        // Obtener métodos de pago únicos con estadísticas
        $methods_query = "
            SELECT 
                COALESCE(payment_method, 'NULL_VALUE') as method_raw,
                CASE 
                    WHEN payment_method IS NULL THEN 'NULL'
                    WHEN payment_method = '' THEN 'EMPTY'
                    ELSE payment_method 
                END as method_display,
                COUNT(*) as count,
                SUM(total) as total_amount,
                AVG(total) as avg_amount,
                MIN(total) as min_amount,
                MAX(total) as max_amount,
                MIN(date_created) as first_sale,
                MAX(date_created) as last_sale
            FROM $sales_table 
            GROUP BY payment_method 
            ORDER BY count DESC
        ";
        
        $methods = $wpdb->get_results($methods_query, ARRAY_A);
        
        if ($wpdb->last_error) {
            throw new Exception('Error en consulta SQL: ' . $wpdb->last_error);
        }
        
        $results['payment_methods'] = $methods;
        
        // Generar estadísticas
        $stats = [
            'total_methods' => count($methods),
            'null_or_empty' => 0,
            'valid_methods' => 0,
            'problematic_methods' => []
        ];
        
        $valid_method_patterns = ['cash', 'efectivo', 'card', 'tarjeta', 'transfer', 'transferencia', 'check', 'cheque'];
        
        foreach ($methods as $method) {
            if (in_array($method['method_display'], ['NULL', 'EMPTY'])) {
                $stats['null_or_empty'] += $method['count'];
            } else {
                $is_valid = false;
                $method_lower = strtolower($method['method_raw']);
                
                foreach ($valid_method_patterns as $pattern) {
                    if (strpos($method_lower, $pattern) !== false) {
                        $is_valid = true;
                        break;
                    }
                }
                
                if ($is_valid) {
                    $stats['valid_methods'] += $method['count'];
                } else {
                    $stats['problematic_methods'][] = [
                        'method' => $method['method_raw'],
                        'count' => $method['count'],
                        'total' => $method['total_amount']
                    ];
                }
            }
        }
        
        $results['statistics'] = $stats;
        
        // Generar recomendaciones
        $recommendations = [];
        
        if ($stats['null_or_empty'] > 0) {
            $recommendations[] = "Hay {$stats['null_or_empty']} ventas con método de pago NULL o vacío - considerar corregir";
        }
        
        if (!empty($stats['problematic_methods'])) {
            $recommendations[] = "Hay " . count($stats['problematic_methods']) . " métodos no estándar que podrían causar problemas en filtros";
        }
        
        if ($stats['total_methods'] > 10) {
            $recommendations[] = "Demasiados métodos diferentes ({$stats['total_methods']}) - considerar normalizar";
        }
        
        $results['recommendations'] = $recommendations;
        $results['success'] = true;
        $results['message'] = "Diagnóstico completado exitosamente. {$total_sales} ventas analizadas.";
        
        // Log detallado
        error_log('DIAGNÓSTICO COMPLETADO:');
        error_log('- Total ventas: ' . $total_sales);
        error_log('- Métodos únicos: ' . $stats['total_methods']);
        error_log('- NULL/vacíos: ' . $stats['null_or_empty']);
        error_log('- Válidos: ' . $stats['valid_methods']);
        error_log('- Problemáticos: ' . count($stats['problematic_methods']));
        
        foreach ($methods as $method) {
            error_log(sprintf(
                "  - '%s' (%s): %d ventas, $%.2f total",
                $method['method_raw'],
                $method['method_display'],
                $method['count'],
                $method['total_amount']
            ));
        }
        
        error_log('=== FIN DIAGNÓSTICO ===');
        
    } catch (Exception $e) {
        $results['message'] = 'Error en diagnóstico: ' . $e->getMessage();
        error_log('ERROR DIAGNÓSTICO: ' . $e->getMessage());
    }
    
    return $results;
}





}



    // ==== 5. HOOK PARA EJECUTAR DIAGNÓSTICO ====

    /**
     * AGREGAR esto al final de class-pos-reports-module.php para ejecutar diagnóstico
     */
    add_action('wp_ajax_wp_pos_debug_payment_methods', function() {
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos');
        }
        
        $methods = WP_POS_Reports_Data::debug_payment_methods_in_db();
        
        wp_send_json_success([
            'message' => 'Diagnóstico completado, revisa el log de errores',
            'methods' => $methods
        ]);
    });