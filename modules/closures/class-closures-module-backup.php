<?php
/**
 * Módulo de Cierres de Caja para G-POS
 *
 * Implementa la funcionalidad para gestionar cierres de caja diarios y mensuales,
 * siguiendo el nuevo sistema modular.
 *
 * @package WP-POS
 * @subpackage Closures
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Cargar la clase base si no se ha cargado
if (!class_exists('WP_POS_Module_Abstract')) {
    require_once WP_POS_INCLUDES_DIR . 'abstract-class-module.php';
}

/**
 * Clase principal del módulo de Cierres de Caja
 */
class WP_POS_Closures_Module extends WP_POS_Module_Abstract {
    /**
     * Instancia del controlador
     *
     * @var WP_POS_Closures_Controller
     */
    private $controller;

    /**
     * Instancia de la API
     *
     * @var WP_POS_Closures_API
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct() {
        // Configurar propiedades básicas del módulo
        $this->id = 'closures';
        $this->name = __('Cierres de Caja', 'wp-pos');
        $this->capability = 'view_pos';
        $this->position = 30;
        $this->icon = 'dashicons-money-alt';
        
        // Activar el módulo y mostrarlo en el menú
        $this->active = true;
        $this->show_in_menu = true;
        
        // Inicializar componentes
        $this->init_components();
    }
    
    /**
     * Inicializar componentes del módulo
     */
    private function init_components() {
        // Cargar controlador
        require_once dirname(__FILE__) . '/includes/class-closures-controller.php';
        $this->controller = WP_POS_Closures_Controller::get_instance();
        
        // Cargar API
        require_once dirname(__FILE__) . '/includes/class-closures-api.php';
        $this->api = new WP_POS_Closures_API();
        
        // Registrar hooks
        add_action('init', array($this, 'register_hooks'));
    }
    
    /**
     * Registrar hooks de WordPress
     */
    public function register_hooks() {
        // Inicializar componentes
        $this->controller->register_hooks();
        $this->api->register_routes();
        
        // Registrar estilos y scripts
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        
        // Inicializar base de datos si es necesario
        add_action('admin_init', array($this, 'ensure_tables_exist'));
    }
    
    /**
     * Registrar estilos y scripts
     */
    public function register_assets() {
        // Solo cargar en páginas del módulo
        if (!isset($_GET['page']) || strpos($_GET['page'], 'wp-pos-closures') === false) {
            return;
        }
        
        // Estilos
        wp_enqueue_style(
            'wp-pos-closures-css',
            $this->get_assets_url() . 'css/closures.css',
            array(),
            WP_POS_VERSION
        );
        
        // Scripts de utilidades
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-tabs');
        
        // Scripts del módulo
        wp_enqueue_script(
            'wp-pos-closures-js',
            $this->get_assets_url() . 'js/closures.js',
            array('jquery', 'jquery-ui-dialog', 'jquery-ui-tabs'),
            WP_POS_VERSION,
            true
        );
        
        // Localizar script con datos necesarios
        wp_localize_script('wp-pos-closures-js', 'wpPosClosures', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_pos_closures_nonce'),
            'i18n' => array(
                'confirmDelete' => __('¿Estás seguro de que deseas eliminar este cierre? Esta acción no se puede deshacer.', 'wp-pos'),
                'errorLoading' => __('Error al cargar los datos. Por favor, inténtalo de nuevo.', 'wp-pos'),
            )
        ));
    }
    
    /**
     * Obtiene la URL base para los assets del módulo
     *
     * @return string URL base para los assets
     */
    public function get_assets_url() {
        return plugin_dir_url(__FILE__) . 'assets/';
    }
    
    /**
     * Renderizar el contenido principal del módulo
     */
    public function render_content() {
        // Verificar permisos
        if (!current_user_can($this->capability)) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'wp-pos'));
        }
        
        // Incluir la vista principal
        include dirname(__FILE__) . '/templates/admin-closures.php';
    }
    
    /**
     * Asegura que las tablas necesarias existan en la base de datos
     */
    public function ensure_tables_exist() {
        global $wpdb;
        
        // Cargar la función dbDelta para crear o actualizar tablas
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // 1. Tabla de cajas registradoras
        $table_registers = $wpdb->prefix . 'pos_registers';
        $sql_registers = "CREATE TABLE $table_registers (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            location varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'closed',
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status)
        ) " . $wpdb->get_charset_collate() . ";";
        
        dbDelta($sql_registers);
        
        // 2. Tabla de cierres
        $table_closures = $wpdb->prefix . 'pos_closures';
        
        // Verificar si la tabla existe para decidir si la recreamos completamente
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_closures'") === $table_closures;
        
        if ($table_exists) {
            // Si existe, verificamos si tiene la estructura correcta
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_closures");
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            
            // Verificar si tiene las columnas que necesitamos
            $required_columns = ['initial_amount', 'expected_amount', 'actual_amount', 'difference'];
            $missing_columns = array_diff($required_columns, $column_names);
            
            if (!empty($missing_columns)) {
                // Si faltan columnas, eliminamos la tabla para recrearla
                $wpdb->query("DROP TABLE IF EXISTS $table_closures");
                $table_exists = false;
            }
        }
        
        // Crear la tabla de cierres con la estructura correcta si no existe o si la eliminamos por incompleta
        $sql_closures = "CREATE TABLE $table_closures (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            register_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            initial_amount decimal(10,2) DEFAULT 0.00,
            expected_amount decimal(10,2) DEFAULT 0.00,
            actual_amount decimal(10,2) DEFAULT 0.00,
            difference decimal(10,2) DEFAULT 0.00,
            justification text DEFAULT NULL,
            approved_by bigint(20) unsigned DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY register_id (register_id),
            KEY user_id (user_id),
            KEY status (status)
        ) " . $wpdb->get_charset_collate() . ";";
        
        dbDelta($sql_closures);
        
        // 3. Asegurar que existe la tabla de historial
        $this->ensure_status_history_table_exists();
        
        // Verificar si hay alguna caja registradora, si no, crear una por defecto
        $registers_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_registers");
        if ($registers_count == 0) {
            $wpdb->insert(
                $table_registers,
                [
                    'name' => 'Caja Principal',
                    'location' => 'Tienda',
                    'status' => 'active',
                    'created_by' => get_current_user_id(),
                    'created_at' => current_time('mysql'),
    }
    
    // Estilos
    wp_enqueue_style(
        'wp-pos-closures-css',
        $this->get_assets_url() . 'css/closures.css',
        array(),
        WP_POS_VERSION
    );
    
    // Scripts de utilidades
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_script('jquery-ui-tabs');
    
    // Scripts del módulo
    wp_enqueue_script(
        'wp-pos-closures-js',
        $this->get_assets_url() . 'js/closures.js',
        array('jquery', 'jquery-ui-dialog', 'jquery-ui-tabs'),
        WP_POS_VERSION,
        true
    );
    
    // Localizar script con datos necesarios
    wp_localize_script('wp-pos-closures-js', 'wpPosClosures', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_pos_closures_nonce'),
        'i18n' => array(
            'confirmDelete' => __('¿Estás seguro de que deseas eliminar este cierre? Esta acción no se puede deshacer.', 'wp-pos'),
            'errorLoading' => __('Error al cargar los datos. Por favor, inténtalo de nuevo.', 'wp-pos'),
        )
    ));
}

/**
 * Obtiene la URL base para los assets del módulo
 *
 * @return string URL base para los assets
 */
public function get_assets_url() {
    return plugin_dir_url(__FILE__) . 'assets/';
}

/**
 * Renderizar el contenido principal del módulo
 */
public function render_content() {
    // Verificar permisos
    if (!current_user_can($this->capability)) {
        wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'wp-pos'));
    }
    
    // Incluir la vista principal
    include dirname(__FILE__) . '/templates/admin-closures.php';
}

/**
 * Asegura que las tablas necesarias existan en la base de datos
 */
public function ensure_tables_exist() {
    global $wpdb;
    
    // Cargar la función dbDelta para crear o actualizar tablas
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // 1. Tabla de cajas registradoras
    $table_registers = $wpdb->prefix . 'pos_registers';
    $sql_registers = "CREATE TABLE $table_registers (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        location varchar(255) DEFAULT '',
        status varchar(20) DEFAULT 'closed',
        created_by bigint(20) unsigned DEFAULT NULL,
        created_at datetime DEFAULT NULL,
        updated_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY status (status)
    ) " . $wpdb->get_charset_collate() . ";";
    
    dbDelta($sql_registers);
    
    // 2. Tabla de cierres
    $table_closures = $wpdb->prefix . 'pos_closures';
    $sql_closures = "CREATE TABLE $table_closures (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        register_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned DEFAULT NULL,
        initial_amount decimal(10,2) DEFAULT 0.00,
        expected_amount decimal(10,2) DEFAULT 0.00,
        actual_amount decimal(10,2) DEFAULT 0.00,
        difference decimal(10,2) DEFAULT 0.00,
        justification text DEFAULT NULL,
        approved_by bigint(20) unsigned DEFAULT NULL,
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT NULL,
        updated_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY register_id (register_id),
        KEY user_id (user_id),
        KEY status (status)
    ) " . $wpdb->get_charset_collate() . ";";
    
    dbDelta($sql_closures);
    
    // 3. Asegurar que existe la tabla de historial
    $this->ensure_status_history_table_exists();
    
    // Verificar si hay alguna caja registradora, si no, crear una por defecto
    $registers_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_registers");
    if ($registers_count == 0) {
        $wpdb->insert(
            $table_registers,
            [
                'name' => 'Caja Principal',
                'location' => 'Tienda',
                'status' => 'active',
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
    }
}

/**
 * Asegura que la tabla de historial de estados exista en la base de datos
 */
public function ensure_status_history_table_exists() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pos_closure_status_history';
    
    // Verificar si la tabla ya existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            closure_id bigint(20) NOT NULL,
            old_status varchar(50) NOT NULL,
            new_status varchar(50) NOT NULL,
            justification text,
            changed_by bigint(20) NOT NULL,
            changed_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY closure_id (closure_id)
        ) " . $wpdb->get_charset_collate() . ";";
        
                    'name' => 'Caja Principal',
                    'location' => 'Tienda',
                    'status' => 'active',
                    'created_by' => get_current_user_id(),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%d', '%s', '%s']
            );
            $this->debug_log('Caja registradora por defecto creada');
        }
    }
        );
    }
    
    // Intentar enviar el correo
    /**
     * Asegura que las tablas necesarias existan en la base de datos
     */
    public function ensure_tables_exist() {
        global $wpdb;
        
        // Cargar la función dbDelta para crear o actualizar tablas
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // 1. Tabla de cajas registradoras
        $table_registers = $wpdb->prefix . 'pos_registers';
        $sql_registers = "CREATE TABLE $table_registers (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            location varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'closed',
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY status (status)
        ) " . $wpdb->get_charset_collate() . ";";
        
        dbDelta($sql_registers);
        
        // 2. Tabla de cierres
        $table_closures = $wpdb->prefix . 'pos_closures';
        $sql_closures = "CREATE TABLE $table_closures (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            register_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            initial_amount decimal(10,2) DEFAULT 0.00,
            expected_amount decimal(10,2) DEFAULT 0.00,
            actual_amount decimal(10,2) DEFAULT 0.00,
            difference decimal(10,2) DEFAULT 0.00,
            justification text DEFAULT NULL,
            approved_by bigint(20) unsigned DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY register_id (register_id),
            KEY user_id (user_id),
            KEY status (status)
        ) " . $wpdb->get_charset_collate() . ";";
        
        dbDelta($sql_closures);
        
        // 3. Asegurar que existe la tabla de historial
        $this->ensure_status_history_table_exists();
        
        // Verificar si hay alguna caja registradora, si no, crear una por defecto
        $registers_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_registers");
        if ($registers_count == 0) {
            $wpdb->insert(
                $table_registers,
                [
                    'name' => 'Caja Principal',
                    'location' => 'Tienda',
                    'status' => 'active',
                    'created_by' => get_current_user_id(),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%d', '%s', '%s']
            );
        }
        
        // Verificar si existe la tabla de historial
        $this->ensure_status_history_table_exists();
        
        global $wpdb;
        
        // Obtener informaciu00f3n del cierre
        $closure = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, u.display_name as user_name
            FROM {$wpdb->prefix}pos_closures c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            WHERE c.id = %d",
            $closure_id
        ));
        
        if (!$closure) {
            wp_send_json_error(['message' => __('No se encontru00f3 el cierre especificado.', 'wp-pos')]);
            return;
        }
        
        // Agregar el estado original como primer registro del historial
        $status_history = [
            [
                'status' => 'pending', // El estado original siempre es pendiente
                'user_name' => $closure->user_name ?: __('Usuario desconocido', 'wp-pos'),
                'date' => $closure->created_at,
                'justification' => ''
            ]
        ];
        
        // Obtener historial de cambios de estado
        $history_records = $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, u.display_name as user_name 
            FROM {$wpdb->prefix}pos_closure_status_history h
            LEFT JOIN {$wpdb->users} u ON h.changed_by = u.ID
            WHERE h.closure_id = %d
            ORDER BY h.changed_at ASC",
            $closure_id
        ));
        
        // Agregar registros del historial
        if ($history_records) {
            foreach ($history_records as $record) {
                $status_history[] = [
                    'status' => $record->new_status,
                    'user_name' => $record->user_name ?: __('Usuario desconocido', 'wp-pos'),
                    'date' => $record->changed_at,
                    'justification' => $record->justification
                ];
            }
        }
        
        // Enviar respuesta
        wp_send_json_success([
            'closure_id' => $closure_id,
            'status_history' => $status_history
        ]);
    }
    
    /**
     * AJAX: Eliminar un cierre de caja
     */
    public function ajax_delete_closure() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Verificar permisos (solo administradores)
        if (!current_user_can('administrator')) {
            wp_send_json_error(['message' => __('Solo los administradores pueden eliminar cierres.', 'wp-pos')]);
        }
        
        // Obtener el ID del cierre
        $closure_id = isset($_POST['closure_id']) ? intval($_POST['closure_id']) : 0;
        
        if ($closure_id <= 0) {
            wp_send_json_error(['message' => __('ID de cierre inválido.', 'wp-pos')]);
        }
        
        global $wpdb;
        
        // Eliminar el cierre
        $result = $wpdb->delete(
            $wpdb->prefix . 'pos_closures',
            ['id' => $closure_id],
            ['%d']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Error al eliminar el cierre.', 'wp-pos')]);
        }
        
        wp_send_json_success(['message' => __('Cierre eliminado correctamente.', 'wp-pos')]);
    }
    
    /**
     * AJAX: Obtener datos para el dashboard
     * 
     * Proporciona datos estadísticos y gráficos para el dashboard de cierres
     */
    public function ajax_dashboard_data() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_pos') && !current_user_can('administrator')) {
            wp_send_json_error(['message' => __('No tienes permisos para ver estos datos.', 'wp-pos')]);
        }
        
        // Obtener el período solicitado
        $period = isset($_REQUEST['period']) ? sanitize_text_field($_REQUEST['period']) : 'month';
        
        // Definir fechas según el período
        $current_date = current_time('Y-m-d');
        
        switch ($period) {
            case 'week':
                $start_date = date('Y-m-d', strtotime('-7 days', strtotime($current_date)));
                $end_date = $current_date;
                break;
                
            case 'month':
                $start_date = date('Y-m-01', strtotime($current_date));
                $end_date = $current_date;
                break;
                
            case 'prev_month':
                $start_date = date('Y-m-01', strtotime('-1 month', strtotime($current_date)));
                $end_date = date('Y-m-t', strtotime('-1 month', strtotime($current_date)));
                break;
                
            case 'quarter':
                $start_date = date('Y-m-d', strtotime('-3 months', strtotime($current_date)));
                $end_date = $current_date;
                break;
                
            default:
                $start_date = date('Y-m-01', strtotime($current_date));
                $end_date = $current_date;
        }
        
        // Inicializar datos del dashboard
        $dashboard_data = [
            'current_month' => [
                'total' => 0,
                'difference' => 0
            ],
            'prev_month' => [
                'total' => 0
            ],
            'pending_count' => 0,
            'recent_closures' => [],
            'daily_amounts' => [],
            'status_distribution' => []
        ];
        
        global $wpdb;
        
        // Verificar si la tabla existe
        $this->ensure_tables_exist();
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}pos_closures'" ) === $wpdb->prefix . 'pos_closures';
        
        if (!$table_exists) {
            $this->debug_log("La tabla de cierres no existe al intentar cargar datos del dashboard");
            wp_send_json_success($dashboard_data);
            return;
        }
        
        // 1. Datos del mes actual
        $current_month_start = date('Y-m-01', strtotime($current_date));
        $current_month_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(actual_amount) as total_amount,
                SUM(actual_amount - expected_amount) as total_difference
            FROM {$wpdb->prefix}pos_closures
            WHERE DATE(created_at) BETWEEN %s AND %s",
            $current_month_start,
            $current_date
        ));
        
        if ($current_month_data) {
            $dashboard_data['current_month']['total'] = floatval($current_month_data->total_amount);
            $dashboard_data['current_month']['difference'] = floatval($current_month_data->total_difference);
        }
        
        // 2. Datos del mes anterior
        $prev_month_start = date('Y-m-01', strtotime('-1 month', strtotime($current_date)));
        $prev_month_end = date('Y-m-t', strtotime('-1 month', strtotime($current_date)));
        
        $prev_month_data = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(actual_amount) as total_amount
            FROM {$wpdb->prefix}pos_closures
            WHERE DATE(created_at) BETWEEN %s AND %s",
            $prev_month_start,
            $prev_month_end
        ));
        
        if ($prev_month_data) {
            $dashboard_data['prev_month']['total'] = floatval($prev_month_data->total_amount);
        }
        
        // 3. Conteo de cierres pendientes
        $pending_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}pos_closures WHERE status = 'pending'"
        );
        
        $dashboard_data['pending_count'] = intval($pending_count);
        
        // 4. Cierres recientes (últimos 5)
        $recent_closures = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name as user_name
            FROM {$wpdb->prefix}pos_closures c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            ORDER BY c.created_at DESC
            LIMIT 5"
        ));
        
        $dashboard_data['recent_closures'] = $recent_closures ? $recent_closures : [];
        
        // 5. Datos diarios para el período seleccionado
        $daily_amounts = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                SUM(actual_amount) as amount,
                SUM(actual_amount - expected_amount) as difference
            FROM {$wpdb->prefix}pos_closures
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC",
            $start_date,
            $end_date
        ));
        
        $dashboard_data['daily_amounts'] = $daily_amounts ? $daily_amounts : [];
        
        // 6. Distribución de estados
        $status_distribution = $wpdb->get_results(
            "SELECT status, COUNT(*) as count
            FROM {$wpdb->prefix}pos_closures
            GROUP BY status"
        );
        
        $status_counts = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        ];
        
        if ($status_distribution) {
            foreach ($status_distribution as $item) {
                $status_counts[$item->status] = intval($item->count);
            }
        }
        
        $dashboard_data['status_distribution'] = $status_counts;
        
        // Enviar respuesta
        $this->debug_log("Datos del dashboard generados correctamente", $dashboard_data);
        wp_send_json_success($dashboard_data);
    }
}
