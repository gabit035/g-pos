<?php
/**
 * Mu00f3dulo de Cierres de Caja para G-POS
 *
 * Implementa la funcionalidad para gestionar cierres de caja diarios y mensuales,
 * siguiendo el nuevo sistema modular.
 *
 * @package WP-POS
 * @subpackage Closures
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;

// Cargar la clase base si no se ha cargado
if (!class_exists('WP_POS_Module_Abstract')) {
    require_once WP_POS_INCLUDES_DIR . 'abstract-class-module.php';
}

class WP_POS_Closures_Module extends WP_POS_Module_Abstract {
    /**
     * Función de depuración personalizada para registrar mensajes en debug.log
     */
    private function debug_log($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (is_array($data) || is_object($data)) {
                error_log('[G-POS Closures Debug] ' . $message . ': ' . print_r($data, true));
            } else if ($data !== null) {
                error_log('[G-POS Closures Debug] ' . $message . ': ' . $data);
            } else {
                error_log('[G-POS Closures Debug] ' . $message);
            }
        }
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Configurar propiedades bu00e1sicas del mu00f3dulo
        $this->id = 'closures';
        $this->name = __('Cierres de Caja', 'wp-pos');
        $this->capability = 'view_pos';
        $this->position = 30; // Posiciu00f3n en el menu00fa (ajustar segu00fan sea necesario)
        $this->icon = 'dashicons-money-alt';
        
        // Activar el mu00f3dulo y mostrarlo en el menu00fa
        $this->active = true;
        $this->show_in_menu = true;
        
        // Registrar el inicio del módulo en debug.log
        $this->debug_log('Módulo de Cierres de Caja inicializado');
        
        // Registrar hooks AJAX
        add_action('wp_ajax_wp_pos_closures_get_registers', [$this, 'ajax_get_registers']);
        add_action('wp_ajax_wp_pos_closures_add_register', [$this, 'ajax_add_register']);
        add_action('wp_ajax_wp_pos_closures_update_register', [$this, 'ajax_update_register']);
        add_action('wp_ajax_wp_pos_closures_delete_register', [$this, 'ajax_delete_register']);
        add_action('wp_ajax_wp_pos_closures_get_closures', [$this, 'ajax_get_closures']);
        add_action('wp_ajax_wp_pos_closures_save_closure', [$this, 'ajax_save_closure']);
        add_action('wp_ajax_wp_pos_closures_calculate_amounts', [$this, 'ajax_calculate_amounts']);
        add_action('wp_ajax_wp_pos_closures_get_closure_details', [$this, 'ajax_get_closure_details']);
        add_action('wp_ajax_wp_pos_closures_update_status', [$this, 'ajax_update_status']);
        add_action('wp_ajax_wp_pos_closures_get_status_history', [$this, 'ajax_get_status_history']);
        add_action('wp_ajax_wp_pos_closures_delete_closure', [$this, 'ajax_delete_closure']);
        add_action('wp_ajax_wp_pos_closures_dashboard_data', [$this, 'ajax_dashboard_data']);
        add_action('wp_ajax_wp_pos_closures_diagnostic', [$this, 'ajax_diagnostic']);
        add_action('wp_ajax_wp_pos_get_available_payment_methods', [$this, 'ajax_get_available_payment_methods']);
        add_action('wp_ajax_wp_pos_forensic_investigation', [$this, 'ajax_forensic_investigation']);
        add_action('wp_ajax_wp_pos_closures_get_breakdown', [$this, 'ajax_get_breakdown']);
        
        // Inicializar base de datos si es necesario
        add_action('admin_init', [$this, 'ensure_tables_exist']);
    }
    
    /**
     * Registrar estilos y scripts
     */
    public function register_assets() {
        // Registrar scripts de utilidades
        wp_register_script('wp-pos-notifications-js', $this->get_assets_url() . 'js/notifications.js', array('jquery'), WP_POS_VERSION, true);
        wp_register_script('wp-pos-notifications-fallback-js', $this->get_assets_url() . 'js/notification-fallback.js', array('jquery', 'wp-pos-notifications-js'), WP_POS_VERSION, true);
        wp_register_script('wp-pos-loading-indicator-js', $this->get_assets_url() . 'js/loading-indicator.js', array('jquery'), WP_POS_VERSION, true);
        wp_register_script('wp-pos-closures-approval-js', $this->get_assets_url() . 'js/closures-approval.js', array('jquery'), WP_POS_VERSION, true);
        wp_register_script('wp-pos-loaders-implementation-js', $this->get_assets_url() . 'js/loaders-implementation.js', array('jquery', 'wp-pos-loading-indicator-js'), WP_POS_VERSION, true);
        wp_register_script('wp-pos-loader-cleanup-js', $this->get_assets_url() . 'js/loader-cleanup.js', array('jquery', 'wp-pos-loaders-implementation-js'), WP_POS_VERSION, true);
        
        // Registrar Chart.js desde CDN
        wp_register_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
        
        // Registrar scripts del módulo
        wp_register_script(
            'wp-pos-closures-js',
            $this->get_assets_url() . 'js/closures.js',
            array(
                'jquery',
                'jquery-ui-core',
                'jquery-ui-dialog',      
                'jquery-ui-datepicker',  
                'jquery-ui-button',
                'wp-pos-notifications-js',
                'wp-pos-notifications-fallback-js',
                'wp-pos-loading-indicator-js',
                'wp-pos-loaders-implementation-js',
                'wp-pos-loader-cleanup-js'
            ),
            WP_POS_VERSION,
            true
        );
        
        // Registrar scripts específicos
        wp_register_script('wp-pos-closures-config-js', $this->get_assets_url() . 'js/closures-config.js', array('jquery'), WP_POS_VERSION, true);
        wp_register_script('wp-pos-closures-forms-js', $this->get_assets_url() . 'js/closures-forms.js', array('jquery', 'wp-pos-closures-js'), WP_POS_VERSION, true);
        wp_register_script('wp-pos-closures-reports-js', $this->get_assets_url() . 'js/closures-reports.js', array('jquery', 'wp-pos-closures-js'), WP_POS_VERSION, true);
        wp_register_script('wp-pos-closures-dashboard-js', $this->get_assets_url() . 'js/closures-dashboard.js', array('jquery', 'chart-js', 'wp-pos-notifications-js', 'wp-pos-loading-indicator-js'), WP_POS_VERSION, true);
        wp_register_script('wp-pos-closures-history-fix-js', $this->get_assets_url() . 'js/closures-history-fix.js', array('jquery'), WP_POS_VERSION, true);
        
        // Registrar estilos para el módulo
        wp_register_style('wp-pos-closures-css', $this->get_assets_url() . 'css/closures.css', array(), WP_POS_VERSION);
        wp_register_style('wp-pos-closures-status-css', $this->get_assets_url() . 'css/closures-status.css', array('wp-pos-closures-css'), WP_POS_VERSION);
    }
    
    /**
     * {@inheritDoc}
     */
    public function enqueue_assets() {
        // Obtener la vista actual
        $current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'dashboard';
        
        // Cargar estilos base
        wp_enqueue_style('wp-pos-closures-css');
        
        // Cargar scripts base
        wp_enqueue_script('wp-pos-closures-js');
        
        // Cargar configuración para control de auto-refresh (SIEMPRE)
        wp_enqueue_script('wp-pos-closures-config-js');
        
        // Cargar scripts específicos según la vista
        if ($current_view === 'dashboard') {
            // Cargar Chart.js
            wp_enqueue_script('chart-js');
            
            // Cargar script del dashboard
            wp_enqueue_script('wp-pos-closures-dashboard-js');
            
            // Localizar script del dashboard
            wp_localize_script('wp-pos-closures-dashboard-js', 'wp_pos_closures_dashboard', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_pos_closures_dashboard_nonce'),
                'default_period' => 'month',
                'messages' => array(
                    'loading' => __('Cargando datos...', 'wp-pos'),
                    'error' => __('Error al cargar los datos. Por favor, inténtalo de nuevo.', 'wp-pos')
                )
            ));
        } elseif ($current_view === 'history') {
            // Cargar estilos específicos del historial
            wp_enqueue_style('wp-pos-closures-status-css');
        }
        
        // Localizar script principal
        wp_localize_script('wp-pos-closures-js', 'wp_pos_closures', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_pos_closures_nonce'),
            'messages' => array(
                'confirm_delete' => __('¿Estás seguro de que deseas eliminar esta caja registradora?', 'wp-pos'),
                'error' => __('Ha ocurrido un error. Por favor intenta nuevamente.', 'wp-pos')
            )
        ));
    }
    
    /**
     * {@inheritDoc}
     */
    public function render_content() {
        // Obtener la vista solicitada, por defecto es el formulario de cierre
        $current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'dashboard';
        
        // Cargar la vista correspondiente
        switch ($current_view) {
            case 'dashboard':
                $view_file = 'closures-dashboard.php';
                break;
            
            case 'history':
                $view_file = 'closures-history.php';
                break;
            
            case 'form':
            default:
                $view_file = 'closure-form.php';
                break;
        }
        
        // Verificar que exista la vista solicitada
        $view_path = $this->get_module_dir() . 'views/' . $view_file;
        if (!file_exists($view_path)) {
            echo '<div class="notice notice-error"><p>' . 
                sprintf(__('Error: No se pudo encontrar la vista (%s)', 'wp-pos'), $view_path) . 
                '</p></div>';
            return;
        }


       /*
        // Enlaces de navegación entre vistas
        echo '<div class="wp-pos-view-navigation">
            <a href="' . admin_url('admin.php?page=wp-pos-closures&view=dashboard') . '" class="button ' . ($current_view === 'dashboard' ? 'button-primary' : '') . '">
                <span class="dashicons dashicons-chart-bar"></span> ' . __('Dashboard', 'wp-pos') . '
            </a>
            <a href="' . admin_url('admin.php?page=wp-pos-closures&view=form') . '" class="button ' . ($current_view === 'form' ? 'button-primary' : '') . '">
                <span class="dashicons dashicons-money-alt"></span> ' . __('Crear cierre', 'wp-pos') . '
            </a>
            <a href="' . admin_url('admin.php?page=wp-pos-closures&view=history') . '" class="button ' . ($current_view === 'history' ? 'button-primary' : '') . '">
                <span class="dashicons dashicons-list-view"></span> ' . __('Historial de cierres', 'wp-pos') . '
            </a>
        </div>';
        */

        
        // Cargar la vista
        include $view_path;
    }
    
    /**
     * Asegura que la tabla de historial de estados exista
     */
    private function ensure_status_history_table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pos_closure_status_history';
        
        // Verificar si la tabla ya existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                closure_id bigint(20) NOT NULL,
                old_status varchar(50) NOT NULL,
                new_status varchar(50) NOT NULL,
                justification text,
                changed_by bigint(20) NOT NULL,
                changed_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY closure_id (closure_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            return true;
        }
        
        return true;
    }
    
    /**
     * Registra un cambio de estado en el historial de auditoriu00e1
     * 
     * @param int $closure_id ID del cierre
     * @param string $old_status Estado anterior
     * @param string $new_status Nuevo estado
     * @param string $justification Justificaciu00f3n del cambio (para rechazos)
     * @return bool True si se registró correctamente, False en caso de error
     */
    private function register_status_change($closure_id, $old_status, $new_status, $justification = '') {
        global $wpdb;
        
        if (empty($closure_id) || !is_numeric($closure_id)) {
            return false;
        }
        
        // Asegurar que existe la tabla de historial
        $this->ensure_status_history_table_exists();
        
        // Sanitizar datos de entrada
        $old_status = sanitize_text_field($old_status);
        $new_status = sanitize_text_field($new_status);
        $justification = sanitize_textarea_field($justification);
        $current_user_id = get_current_user_id();
        
        // Insertar registro en la tabla de historial
        $result = $wpdb->insert(
            $wpdb->prefix . 'pos_closure_status_history',
            [
                'closure_id' => $closure_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'changed_by' => $current_user_id,
                'justification' => $justification,
                'changed_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Notifica al usuario original sobre el cambio de estado de su cierre
     * 
     * @param object $closure Datos del cierre
     * @param string $new_status Nuevo estado
     * @param string $justification Justificación (para rechazos)
     * @return bool True si la notificación se envió correctamente, False en caso contrario
     */
    private function notify_user_about_status_change($closure, $new_status, $justification = '') {
        // Si no hay datos del cierre o del usuario, no hacer nada
        if (empty($closure) || empty($closure->user_id)) {
            return false;
        }
        
        // Obtener datos del usuario
        $user_data = get_userdata($closure->user_id);
        if (!$user_data || empty($user_data->user_email)) {
            return false;
        }
        
        // Obtener datos del administrador
        $admin_id = get_current_user_id();
        $admin_name = __('Un administrador', 'wp-pos');
        if ($admin_id) {
            $admin_data = get_userdata($admin_id);
            if ($admin_data && !empty($admin_data->display_name)) {
                $admin_name = $admin_data->display_name;
            }
        }
        
        // Formatear fecha
        $closure_date = __('fecha desconocida', 'wp-pos');
        if (!empty($closure->created_at)) {
            $closure_date = date_i18n(get_option('date_format'), strtotime($closure->created_at));
        }
        
        // Mensaje según el estado
        if ($new_status === 'approved') {
            $subject = __('Cierre de Caja Aprobado', 'wp-pos');
            $message = sprintf(
                __('Hola %s, Tu cierre de caja del %s ha sido APROBADO por %s. Gracias por tu trabajo.', 'wp-pos'),
                $user_data->display_name,
                $closure_date,
                $admin_name
            );
        } else {
            $subject = __('Cierre de Caja Rechazado', 'wp-pos');
            $message = sprintf(
                __('Hola %s, Tu cierre de caja del %s ha sido RECHAZADO por %s. Motivo del rechazo: %s Por favor, contacta con tu supervisor para más detalles.', 'wp-pos'),
                $user_data->display_name,
                $closure_date,
                $admin_name,
                $justification ?: __('No se especificó una justificación', 'wp-pos')
            );
        }
        
        // Intentar enviar el correo
        return wp_mail($user_data->user_email, $subject, $message);
    }
    
    /**
     * Asegura que existe la tabla de cierres
     */
    public function ensure_tables_exist() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $this->debug_log('Verificando y creando tablas necesarias');
        
        // Charset y collation para las tablas
        $charset_collate = $wpdb->get_charset_collate();
        
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
        ) $charset_collate";
        
        dbDelta($sql_registers);
        $this->debug_log('Tabla de cajas registradoras verificada/creada');
        
        // 2. Tabla de transacciones
        $table_transactions = $wpdb->prefix . 'pos_transactions';
        $sql_transactions = "CREATE TABLE $table_transactions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            register_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            type varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_method varchar(50) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY register_id (register_id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql_transactions);
        $this->debug_log('Tabla de transacciones verificada/creada');
        
        // 3. Tabla de cierres - Esta es la tabla que nos interesa asegurar que existe
        $table_closures = $wpdb->prefix . 'pos_closures';
        
        // Verificar si la tabla existe para decidir si la recreamos completamente
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_closures'") === $table_closures;
        
        if ($table_exists) {
            // Si existe, verificamos si tiene la estructura correcta
            $this->debug_log('La tabla de cierres existe, verificando su estructura');
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_closures");
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            
            // Verificar si tiene las columnas que necesitamos
            $required_columns = ['initial_amount', 'expected_amount', 'actual_amount', 'difference'];
            $missing_columns = array_diff($required_columns, $column_names);
            
            if (!empty($missing_columns)) {
                // Si faltan columnas, eliminamos la tabla para recrearla
                $this->debug_log('Faltan columnas en la tabla de cierres', $missing_columns);
                $wpdb->query("DROP TABLE IF EXISTS $table_closures");
                $table_exists = false;
            } else {
                $this->debug_log('La tabla de cierres tiene la estructura correcta');
            }
        } else {
            $this->debug_log('La tabla de cierres no existe, será creada');
        }
        
        // Crear la tabla de cierres con la estructura correcta
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
            payment_breakdown LONGTEXT DEFAULT NULL,
            PRIMARY KEY (id),
            KEY register_id (register_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate";
        
        dbDelta($sql_closures);
        $this->debug_log('Tabla de cierres verificada/creada');
        
        // Verificar si se creó correctamente
        $verification = $wpdb->get_var("SHOW TABLES LIKE '$table_closures'") === $table_closures;
        $this->debug_log('Verificación final de tabla de cierres', $verification ? 'Existe' : 'No existe');
        
        // Crear una caja registradora por defecto si no existe ninguna
        $registers_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_registers");
        if ($registers_count == 0) {
            $this->debug_log('No hay cajas registradoras, creando una por defecto');
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
            $this->debug_log('Caja registradora por defecto creada');
        }
    }
    
    /**
     * AJAX: Obtener listado de cajas registradoras
     */
    public function ajax_get_registers() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('view_pos') && !current_user_can('administrator')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acciu00f3n.', 'wp-pos')]);
        }
        
        // Obtener registros (implementaciu00f3n buu00e1sica)
        global $wpdb;
        $registers = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}pos_registers ORDER BY name ASC"
        );
        
        wp_send_json_success(['registers' => $registers]);
    }
    
    /**
     * AJAX: Agregar caja registradora
     */
    public function ajax_add_register() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_pos') && !current_user_can('administrator')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acciu00f3n.', 'wp-pos')]);
        }
        
        // Validar campos obligatorios
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            wp_send_json_error(['message' => __('El nombre de la caja registradora es obligatorio.', 'wp-pos')]);
        }
        
        // Sanear datos
        $name = sanitize_text_field($_POST['name']);
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
        
        // Insertar en la base de datos
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'pos_registers',
            [
                'name' => $name,
                'location' => $location,
                'status' => 'closed',
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Caja registradora creada exitosamente.', 'wp-pos'),
                'register_id' => $wpdb->insert_id
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al crear la caja registradora.', 'wp-pos')]);
        }
    }
    
    /**
     * AJAX: Actualizar caja registradora
     */
    public function ajax_update_register() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_pos') && !current_user_can('administrator')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acciu00f3n.', 'wp-pos')]);
        }
        
        // Validar campos obligatorios
        if (!isset($_POST['register_id']) || empty($_POST['register_id']) || !isset($_POST['name']) || empty($_POST['name'])) {
            wp_send_json_error(['message' => __('El ID y nombre de la caja son obligatorios.', 'wp-pos')]);
        }
        
        // Sanear datos
        $register_id = intval($_POST['register_id']);
        $name = sanitize_text_field($_POST['name']);
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
        
        // Actualizar en la base de datos
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'pos_registers',
            [
                'name' => $name,
                'location' => $location,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $register_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => __('Caja registradora actualizada exitosamente.', 'wp-pos')]);
        } else {
            wp_send_json_error(['message' => __('Error al actualizar la caja registradora.', 'wp-pos')]);
        }
    }
    
    /**
     * AJAX: Eliminar caja registradora
     */
    public function ajax_delete_register() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Solo los administradores pueden eliminar cajas
        if (!current_user_can('administrator')) {
            wp_send_json_error(['message' => __('Solo los administradores pueden eliminar cajas registradoras.', 'wp-pos')]);
        }
        
        // Validar datos requeridos
        if (!isset($_POST['register_id']) || empty($_POST['register_id'])) {
            wp_send_json_error(['message' => __('El ID de la caja es obligatorio.', 'wp-pos')]);
        }
        
        // Sanear datos
        $register_id = intval($_POST['register_id']);
        
        // Eliminar de la base de datos
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'pos_registers',
            ['id' => $register_id],
            ['%d']
        );
        
        if ($result) {
            wp_send_json_success(['message' => __('Caja registradora eliminada exitosamente.', 'wp-pos')]);
        } else {
            wp_send_json_error(['message' => __('Error al eliminar la caja registradora.', 'wp-pos')]);
        }
    }
    
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
        // Decodificar desglose de métodos de pago
        foreach ($closures as &$closure) {
            if (isset($closure['payment_breakdown']) && !empty($closure['payment_breakdown'])) {
                $decoded = json_decode($closure['payment_breakdown'], true);
                $closure['payment_breakdown_decoded'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
            } else {
                $closure['payment_breakdown_decoded'] = null;
            }
        }
        unset($closure);
        // Enviar respuesta
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
    
    /**
     * AJAX: Guardar cierre de caja
     */
    public function ajax_save_closure() {
        $this->debug_log('Iniciando guardar cierre de caja');
        
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        $this->debug_log('Nonce verificado');
        
        // Verificar permisos
        if (!current_user_can('manage_pos') && !current_user_can('administrator')) {
            $this->debug_log('Error de permisos');
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acciu00f3n.', 'wp-pos')]);
        }
        $this->debug_log('Permisos verificados');
        
        // Validar campos obligatorios
        $required_fields = ['closure_date', 'initial_amount', 'expected_amount', 'counted_amount'];
        $this->debug_log('Datos POST recibidos', $_POST);
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $this->debug_log('Campo obligatorio faltante', $field);
                wp_send_json_error([
                    'message' => sprintf(__('El campo %s es obligatorio.', 'wp-pos'), $field)
                ]);
            }
        }
        $this->debug_log('Todos los campos obligatorios presentes');
        
        // Sanear datos
        $closure_date = sanitize_text_field($_POST['closure_date']);
        $user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
        
        // Asegurar que el monto inicial sea un número válido, de lo contrario usar 0
        $initial_amount = isset($_POST['initial_amount']) && is_numeric($_POST['initial_amount']) 
            ? (float) $_POST['initial_amount'] 
            : 0.00;
            
        $expected_amount = (float) $_POST['expected_amount'];
        $counted_amount = (float) $_POST['counted_amount'];
        $difference = $counted_amount - $expected_amount;
        $register_id = isset($_POST['register_id']) ? intval($_POST['register_id']) : 0;
        
        // Si no se especifica un registro, usar el primer registro activo disponible
        if ($register_id === 0) {
            global $wpdb;
            $register = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}pos_registers LIMIT 1");
            if ($register) {
                $register_id = $register->id;
            } else {
                // Si no hay cajas registradas, retornar error
                wp_send_json_error([
                    'message' => __('No hay cajas registradoras disponibles. Por favor, crea una caja primero.', 'wp-pos')
                ]);
            }
        }
        
        // VERIFICACIÓN DEFINITIVA: Comprobar si ya existe un cierre para esta fecha y registro
        // Primero convertimos la fecha a formato YYYY-MM-DD para asegurar consistencia
        global $wpdb;
        
        // Convertir fecha a formato seguro
        $date_obj = new DateTime($closure_date);
        $formatted_date = $date_obj->format('Y-m-d');
        
        // Rango completo del du00ea
        $closure_date_start = $formatted_date . ' 00:00:00';
        $closure_date_end = $formatted_date . ' 23:59:59';
        
        $this->debug_log('Verificando cierres existentes', [
            'fecha_original' => $closure_date,
            'fecha_formateada' => $formatted_date,
            'fecha_inicio' => $closure_date_start,
            'fecha_fin' => $closure_date_end,
            'registro' => $register_id,
            'usuario' => $user_id
        ]);
        
        // Verificar de manera definitiva con consulta SQL directa 
        $query = $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pos_closures 
            WHERE DATE(date_created) = %s 
            AND register_id = %d",
            $formatted_date, $register_id
        );
        
        $this->debug_log('Consulta SQL para verificar duplicados', $query);
        $existing_closure = $wpdb->get_row($query);
        
        if ($existing_closure) {
            $this->debug_log('Intento de guardar cierre duplicado', [
                'fecha' => $closure_date,
                'registro' => $register_id,
                'usuario' => $user_id,
                'id_existente' => $existing_closure->id
            ]);
            wp_send_json_error([
                'message' => __('Ya existe un cierre para esta fecha, caja y usuario. No se permiten cierres duplicados.', 'wp-pos'),
                'existing_id' => $existing_closure->id
            ]);
            return;
        }
        
        // Insertar el cierre en la base de datos
        // Adaptando los campos al esquema real de la tabla
        global $wpdb;
        $current_time = current_time('mysql');
        
        // Hacer una consulta directa para ver los cierres existentes
        try {
            $existing_closures = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pos_closures LIMIT 10");
            $this->debug_log('Cierres existentes antes de guardar', $existing_closures);
        } catch (Exception $e) {
            $this->debug_log('Error al consultar cierres existentes', $e->getMessage());
        }
        
        // Ejecutar consulta SQL directa para crear la tabla si no existe con la estructura correcta
        $table_name = $wpdb->prefix . 'pos_closures';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
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
            PRIMARY KEY (id),
            KEY register_id (register_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate";
        
        $this->debug_log('SQL para crear tabla', $sql);
        
        // Usar dbDelta para crear o actualizar la tabla de forma segura
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Datos para insertar
        $data = [
            'register_id' => $register_id,
            'user_id' => $user_id,
            'initial_amount' => $initial_amount,
            'expected_amount' => $expected_amount,
            'actual_amount' => $counted_amount, // actual_amount en lugar de counted_amount
            'difference' => $difference,
            'justification' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '',
            'status' => 'pending',  // El cierre está pendiente hasta que sea aprobado
            'created_at' => $current_time
        ];
        // Guardar desglose de métodos de pago si viene en el request
        if (isset($_POST['payment_breakdown'])) {
            $payment_breakdown = wp_unslash($_POST['payment_breakdown']);
            if (is_string($payment_breakdown)) {
                // Validar que sea JSON válido
                json_decode($payment_breakdown);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['payment_breakdown'] = $payment_breakdown;
                } else {
                    $this->debug_log('payment_breakdown inválido, ignorado', $payment_breakdown);
                }
            }
        }
        
        // Formatos para insertar
        $formats = ['%d', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s'];
        if (isset($data['payment_breakdown'])) {
            $formats[] = '%s';
        }
        
        // Registrar los datos que se insertarán
        $this->debug_log('Datos a insertar', $data);
        $this->debug_log('Formatos para inserción', $formats);
        
        // Ahora insertar el registro con la estructura correcta
        try {
            $result = $wpdb->insert(
                $wpdb->prefix . 'pos_closures',
                $data,
                $formats
            );
            
            // Registrar el resultado de la inserción
            $this->debug_log('Resultado de inserción', ($result ? 'Exitoso' : 'Fallido'));
            if (!$result) {
                $this->debug_log('Último error de SQL', $wpdb->last_error);
            } else {
                $insert_id = $wpdb->insert_id;
                $this->debug_log('ID del nuevo cierre', $insert_id);
                
                // Verificar si realmente se insertó consultando de nuevo
                $nuevo_cierre = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pos_closures WHERE id = %d", $insert_id), ARRAY_A);
                $this->debug_log('Datos del nuevo cierre guardado', $nuevo_cierre);
            }
        } catch (Exception $e) {
            $this->debug_log('Excepción al insertar', $e->getMessage());
        }
        
        if ($result) {
            $closure_id = $wpdb->insert_id;
            wp_send_json_success([
                'message' => __('Cierre de caja guardado exitosamente.', 'wp-pos'),
                'closure_id' => $closure_id
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Error al guardar el cierre de caja: ' . $wpdb->last_error, 'wp-pos')
            ]);
        }
    }
    
    /**
     * AJAX: Calcular montos para el cierre de caja
     */
    public function ajax_calculate_amounts() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_pos') && !current_user_can('administrator')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acciu00f3n.', 'wp-pos')]);
        }
        
        // Obtener los parámetros
        $register_id = isset($_REQUEST['register_id']) ? intval($_REQUEST['register_id']) : 0;
        $user_id = isset($_REQUEST['user_id']) && $_REQUEST['user_id'] !== '' ? intval($_REQUEST['user_id']) : 0;
        $date = isset($_REQUEST['date']) ? sanitize_text_field($_REQUEST['date']) : date('Y-m-d');
        
        // Depuración de los parámetros recibidos - DETALLADA
        $this->debug_log("PARAMETERS RECEIVED FOR CALCULATE AMOUNTS: ", array(
            'register_id' => $register_id,
            'user_id' => $user_id,
            'user_id_raw' => isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 'not set',
            'user_id_empty_check' => isset($_REQUEST['user_id']) ? (empty($_REQUEST['user_id']) ? 'EMPTY' : 'NOT EMPTY') : 'NOT SET',
            'date' => $date,
            'raw_request' => $_REQUEST
        ));
        
        if ($register_id <= 0) {
            wp_send_json_error(['message' => __('Selecciona una caja registradora válida.', 'wp-pos')]);
        }
        
        // NUEVA VALIDACIÓN: Asegurar que user_id sea válido
        if ($user_id > 0) {
            $this->debug_log("*** MODO FILTRADO POR USUARIO ACTIVADO ***", [
                'user_id' => $user_id,
                'filtering_enabled' => true
            ]);
        } else {
            $this->debug_log("*** MODO SIN FILTRO DE USUARIO ***", [
                'user_id' => $user_id,
                'filtering_enabled' => false
            ]);
        }
        
        // Consulta para calcular montos basados en transacciones
        global $wpdb;
        
        // El monto inicial ahora se ingresa manualmente por el usuario
        // Pero obtenemos el último cierre solo para calcular el monto esperado
        // sin afectar el valor del monto inicial que será 0 para el usuario
        $initial_amount = 0; // Valor por defecto para ingreso manual
        
        // Obtener el último cierre SOLO para el cálculo del monto esperado
        $last_closure = $wpdb->get_row($wpdb->prepare(
            "SELECT actual_amount FROM {$wpdb->prefix}pos_closures 
            WHERE register_id = %d 
            ORDER BY created_at DESC LIMIT 1",
            $register_id
        ));
        
        // Utilizaremos este valor solo para cálculo interno del monto esperado
        $last_closure_amount = $last_closure ? (float)$last_closure->actual_amount : 0;
        
        // Buscar en tabla de ventas del plugin G-POS
        // Primero verificamos si la tabla de ventas existe
        $pos_sales_table = $wpdb->prefix . 'pos_sales';
        $pos_sale_items_table = $wpdb->prefix . 'pos_sale_items';
        $pos_transactions_table = $wpdb->prefix . 'pos_transactions';
        
        // Inicializar totales
        $sales_total = 0;
        $transactions_total = 0;
        $debug_info = [];
        
        // 1. Verificar ventas en la tabla pos_sales si existe
        $sales_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pos_sales_table}'") === $pos_sales_table;
        if ($sales_table_exists) {
            // Primero obtenemos la estructura de la tabla para encontrar las columnas correctas
            $columns = $wpdb->get_results("DESCRIBE {$pos_sales_table}");
            
            // Encontrar columna de fecha (similar al método de diagnóstico)
            $date_column = 'created_at'; // Valor predeterminado
            $date_columns_priority = ['date_created', 'created_at', 'date', 'timestamp'];
            foreach ($columns as $column) {
                foreach ($date_columns_priority as $priority_name) {
                    if (strtolower($column->Field) === strtolower($priority_name)) {
                        $date_column = $column->Field;
                        break 2;
                    }
                }
            }
            
            // Determinar si existe columna 'total'
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            $total_column = in_array('total', $column_names) ? 'total' : (in_array('amount', $column_names) ? 'amount' : 'total');
            
            // Determinar si existe columna para método de pago
            $payment_method_column = '';
            foreach ($column_names as $col) {
                if (strpos(strtolower($col), 'payment_method') !== false || 
                    strpos(strtolower($col), 'payment') !== false ||
                    strpos(strtolower($col), 'method') !== false) {
                    $payment_method_column = $col;
                    break;
                }
            }
            
            // Consulta para obtener el total de ventas usando las columnas detectadas
            // Construir consulta para ventas en efectivo
            $sales_query = "SELECT SUM({$total_column}) as total_sales FROM {$pos_sales_table} WHERE DATE({$date_column}) = %s";
            $sales_args = [$date];
            
            // Filtrar por método de pago en efectivo si la columna existe
            if ($payment_method_column) {
                // Posibles valores para efectivo en diferentes implementaciones
                $cash_values = ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'];
                $placeholders = array_fill(0, count($cash_values), '%s');
                $placeholder_string = implode(',', $placeholders);
                
                $sales_query .= " AND {$payment_method_column} IN ({$placeholder_string})";
                $sales_args = array_merge($sales_args, $cash_values);
            }
            
            // Si se especifica un usuario, verificar todas las posibles columnas de usuario
            if ($user_id > 0) {
                $this->debug_log("Aplicando filtro por usuario {$user_id} en tabla pos_sales");
                
                // Buscar todas las posibles columnas de usuario
                $possible_user_columns = ['user_id', 'created_by', 'employee_id', 'cashier_id', 'seller_id'];
                $found_user_column = false;
                
                foreach ($possible_user_columns as $possible_column) {
                    if (in_array($possible_column, $column_names)) {
                        $sales_query .= " AND {$possible_column} = %d";
                        $sales_args[] = $user_id;
                        $found_user_column = true;
                        $this->debug_log("Columna de usuario encontrada en pos_sales: {$possible_column}");
                        break;
                    }
                }
                
                if (!$found_user_column) {
                    $this->debug_log("No se encontró columna de usuario en pos_sales. Columnas disponibles: " . implode(", ", $column_names));
                }
            }
            
            $prepared_sales_query = $wpdb->prepare($sales_query, $sales_args);
            $sales_result = $wpdb->get_var($prepared_sales_query);
            $sales_total = (float)($sales_result ?: 0);
            
            $debug_info['sales_query'] = $prepared_sales_query;
            $debug_info['sales_total'] = $sales_total;
            $debug_info['date_column_used'] = $date_column;
            $debug_info['total_column_used'] = $total_column;
            $debug_info['payment_method_column'] = $payment_method_column ?: 'No encontrada';
        }
        
        // 2. Verificar transacciones en la tabla pos_transactions si existe
        $transactions_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pos_transactions_table}'") === $pos_transactions_table;
        if ($transactions_table_exists) {
            $this->debug_log("🔍 ANÁLISIS pos_transactions - Búsqueda columna método de pago", [
                'table' => 'pos_transactions',
                'payment_method_requested' => 'cash',
                'search_values' => ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'],
                'all_columns' => array_map(function($col) { return $col->Field; }, $wpdb->get_results("DESCRIBE {$pos_transactions_table}")),
                'payment_method_column_found' => 'payment_method'
            ]);
            
            $trans_columns = $wpdb->get_results("DESCRIBE {$pos_transactions_table}");
            $trans_column_names = array_map(function($col) { return $col->Field; }, $trans_columns);
            
            // Buscar columna de método de pago
            $trans_payment_method = '';
            foreach ($trans_column_names as $col) {
                if (strpos(strtolower($col), 'payment_method') !== false || 
                    strpos(strtolower($col), 'payment') !== false ||
                    strpos(strtolower($col), 'method') !== false) {
                    $trans_payment_method = $col;
                    break;
                }
            }
            
            if ($trans_payment_method) {
                $this->debug_log("🔍 DETALLE DE COLUMNAS pos_transactions", [
                    'columnas' => $trans_column_names,
                    'columna_pago_encontrada' => $trans_payment_method
                ]);
                
                // Consulta para calcular ventas y otros movimientos
                $trans_query = "SELECT SUM(amount) as total_amount FROM {$pos_transactions_table} 
                               WHERE register_id = %d 
                               AND DATE(created_at) = %s";
                $trans_args = [$register_id, $date];
                
                // Filtrar por método de pago en efectivo si la columna existe
                if ($trans_payment_method) {
                    // Posibles valores para efectivo
                    $cash_values = ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'];
                    $placeholders = array_fill(0, count($cash_values), '%s');
                    $placeholder_string = implode(',', $placeholders);
                    
                    $trans_query .= " AND {$trans_payment_method} IN ({$placeholder_string})";
                    $trans_args = array_merge($trans_args, $cash_values);
                }
                
                // Si se especifica un usuario, verificar todas las posibles columnas de usuario
                if ($user_id > 0) {
                    $this->debug_log("Aplicando filtro por usuario {$user_id} en tabla pos_transactions");
                    
                    // Buscar todas las posibles columnas de usuario
                    $possible_user_columns = ['user_id', 'created_by', 'employee_id', 'cashier_id', 'seller_id'];
                    $found_user_column = false;
                    
                    foreach ($possible_user_columns as $possible_column) {
                        if (in_array($possible_column, $trans_column_names)) {
                            $trans_query .= " AND {$possible_column} = %d";
                            $trans_args[] = $user_id;
                            $found_user_column = true;
                            $this->debug_log("Columna de usuario encontrada en pos_transactions: {$possible_column}");
                            break;
                        }
                    }
                    
                    if (!$found_user_column) {
                        $this->debug_log("No se encontró columna de usuario en pos_transactions. Columnas disponibles: " . implode(", ", $trans_column_names));
                    }
                }
                
                // Filtrar por fecha
                $trans_query .= " AND DATE(created_at) = %s";
                $trans_args[] = $date;
                
                $prepared_trans_query = $wpdb->prepare($trans_query, $trans_args);
                $trans_result = $wpdb->get_var($prepared_trans_query);
                $transactions_total = (float)($trans_result ?: 0);
                
                $debug_info['transactions_query'] = $prepared_trans_query;
                $debug_info['transactions_total'] = $transactions_total;
                $debug_info['transactions_payment_method_column'] = $trans_payment_method ?: 'No encontrada';
            }
        }
        
        // 3. Consulta directa a la tabla de pedidos de WooCommerce si G-POS usa WooCommerce como backend
        $wc_orders_table = $wpdb->prefix . 'wc_order_stats';
        $wc_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wc_orders_table}'") === $wc_orders_table;
        
        $wc_total = 0;
        if ($wc_table_exists) {
            // Consulta para obtener ventas de WooCommerce del día
            $wc_query = "SELECT SUM(total_sales) as wc_total FROM {$wc_orders_table} 
                      WHERE status = 'wc-completed' AND DATE(date_created) = %s";
            
            $prepared_wc_query = $wpdb->prepare($wc_query, [$date]);
            $wc_result = $wpdb->get_var($prepared_wc_query);
            $wc_total = (float)($wc_result ?: 0);
            
            $debug_info['wc_query'] = $prepared_wc_query;
            $debug_info['wc_total'] = $wc_total;
        }
        
        // Get all payment methods with proper filtering
        $payment_methods = $this->calculate_all_payment_methods($register_id, $user_id, $date);
        
        // Get cash total from payment methods
        $cash_total = isset($payment_methods['cash']) ? (float)$payment_methods['cash'] : 0;
        
        // Calculate total transactions as sum of all payment methods
        $total_transactions = array_sum($payment_methods);
        
        // Debug log para verificar los montos
        $this->debug_log("=== RESUMEN DE MONTOS CALCULADOS ===", [
            'initial_amount' => $initial_amount,
            'cash_total' => $cash_total,
            'total_transactions' => $total_transactions,
            'user_id_filter' => $user_id,
            'register_id' => $register_id,
            'date' => $date
        ]);
        
        // Calculate expected amount as initial amount + cash total
        $expected_amount = $initial_amount + $cash_total;
        
        // Debug information
        $debug_info['payment_methods'] = $payment_methods;
        $debug_info['last_closure_amount'] = $last_closure_amount;
        
        // Return the results
        wp_send_json_success([
            'initial_amount' => $initial_amount,
            'total_transactions' => $total_transactions,
            'expected_amount' => $expected_amount,
            'cash_total' => $cash_total,
            'payment_methods' => $payment_methods,
            'debug' => $debug_info
        ]);
        
        return; // Ensure we don't execute any code after sending the response
    }

    /**
     * Calcular todos los montos por método de pago en una sola operación para evitar duplicidades
     * 
     * @param int $register_id ID de la caja registradora
     * @param int $user_id ID del usuario (opcional)
     * @param string $date Fecha en formato Y-m-d
     * @return array Array con los montos para cada método de pago
     */
    private function calculate_all_payment_methods($register_id, $user_id, $date) {
        global $wpdb;
        
        // Obtener métodos de pago del sistema o usar los predeterminados
        $default_methods = [
            'cash' => ['label' => __('Efectivo', 'wp-pos'), 'icon' => 'money-alt'],
            'card' => ['label' => __('Tarjeta', 'wp-pos'), 'icon' => 'credit-card'],
            'transfer' => ['label' => __('Transferencia', 'wp-pos'), 'icon' => 'bank'],
            'check' => ['label' => __('Cheque', 'wp-pos'), 'icon' => 'money'],
            'other' => ['label' => __('Otro', 'wp-pos'), 'icon' => 'admin-generic']
        ];
        
        // Obtener métodos de pago del sistema si la función existe
        $system_methods = function_exists('wp_pos_get_payment_methods') ? wp_pos_get_payment_methods() : $default_methods;
        
        // Inicializar array de resultados con todos los métodos disponibles
        $payment_methods = [];
        foreach ($system_methods as $method_id => $method) {
            $payment_methods[$method_id] = 0;
        }
        
        // Asegurarse de que los métodos predeterminados siempre estén presentes
        foreach ($default_methods as $method_id => $method) {
            if (!isset($payment_methods[$method_id])) {
                $payment_methods[$method_id] = 0;
            }
        }
        
        // Obtener nombre de usuario para mejor logging
        $user_name = '';
        if ($user_id > 0) {
            $user = get_user_by('ID', $user_id);
            $user_name = $user ? $user->display_name : 'Desconocido';
        }
        
        $this->debug_log("=== CALCULANDO TODOS LOS MÉTODOS DE PAGO EN UNA OPERACIÓN ===", [
            'register_id' => $register_id,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'date' => $date,
            'user_filtering' => $user_id > 0 ? 'ACTIVADO' : 'DESACTIVADO'
        ]);
        
        // Verificación especial para usuarios que pueden necesitar filtrado avanzado
        $special_user_trace = ($user_name == 'Ileana');
        
        // 1. Procesar tabla pos_sales
        $pos_sales_table = $wpdb->prefix . 'pos_sales';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_sales_table}'") === $pos_sales_table) {
            $this->debug_log("Procesando tabla pos_sales para métodos de pago");
            
            // Obtener columnas de la tabla
            $column_names = $wpdb->get_col("DESC {$pos_sales_table}", 0);
            
            // Identificar columnas relevantes
            $payment_method_column = null;
            $total_column = null;
            $date_column = null;
            
            // Buscar columnas de método de pago, total y fecha
            foreach ($column_names as $column) {
                $column_lower = strtolower($column);
                
                if (strpos($column_lower, 'payment_method') !== false || 
                    strpos($column_lower, 'payment') !== false || 
                    strpos($column_lower, 'method') !== false) {
                    $payment_method_column = $column;
                }
                
                if (strpos($column_lower, 'total') !== false || 
                    strpos($column_lower, 'amount') !== false) {
                    $total_column = $column;
                }
                
                if (strpos($column_lower, 'date') !== false || 
                    strpos($column_lower, 'created_at') !== false || 
                    strpos($column_lower, 'timestamp') !== false) {
                    $date_column = $column;
                }
            }
            
            // Si encontramos las columnas necesarias
            if ($payment_method_column && $total_column && $date_column) {
                // Preparar condición de usuario
                $user_condition = "";
                $args = [$date];
                
                // Aplicar filtro por usuario si es necesario
                if ($user_id > 0) {
                    // Obtener columnas de usuario disponibles
                    $user_columns = $special_user_trace ? 
                        ['seller_id', 'cashier_id', 'created_by', 'user_id', 'employee_id'] :
                        ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                    
                    foreach ($user_columns as $user_col) {
                        if (in_array($user_col, $column_names)) {
                            $user_condition = " AND {$user_col} = %d";
                            $args[] = $user_id;
                            $this->debug_log("Aplicando filtro de usuario en pos_sales: {$user_col} = {$user_id}");
                            break;
                        }
                    }
                }
                
                // Query para obtener transacciones agrupadas por método de pago
                $query = "SELECT {$payment_method_column} as payment_method, SUM({$total_column}) as total 
                         FROM {$pos_sales_table} 
                         WHERE DATE({$date_column}) = %s {$user_condition} 
                         GROUP BY {$payment_method_column}";
                
                $results = $wpdb->get_results($wpdb->prepare($query, $args));
                
                if ($results) {
                    $this->debug_log("Resultados de pos_sales agrupados: ", $results);
                    
                    // Obtener todos los métodos de pago del sistema para el mapeo
                    $all_methods = array_keys($system_methods);
                    $payment_mappings = [];
                    
                    // Crear mapeo de sinónimos para cada método
                    foreach ($system_methods as $method_id => $method) {
                        $labels = [strtolower($method_id)];
                        if (is_array($method) && !empty($method['label'])) {
                            $labels[] = strtolower($method['label']);
                            $labels[] = strtolower(sanitize_title($method['label']));
                        }
                        $payment_mappings[$method_id] = array_unique($labels);
                    }
                    
                    // Añadir mapeos comunes
                    $common_mappings = [
                        'cash' => ['efectivo', '1', 'efectivo', 'cash', 'money', 'dinero'],
                        'card' => ['tarjeta', '2', 'credit_card', 'debit_card', 'creditcard', 'debitcard', 'visa', 'mastercard'],
                        'transfer' => ['transferencia', '3', 'bank_transfer', 'banktransfer', 'wire_transfer'],
                        'check' => ['cheque', '4', 'check', 'cheque_bancario'],
                        'other' => ['otro', '5', 'other', 'otro_metodo', 'otro_medio']
                    ];
                    
                    // Combinar con los mapeos comunes
                    foreach ($common_mappings as $method_id => $synonyms) {
                        if (isset($payment_mappings[$method_id])) {
                            $payment_mappings[$method_id] = array_merge($payment_mappings[$method_id], $synonyms);
                        } else {
                            $payment_mappings[$method_id] = $synonyms;
                        }
                    }
                    
                    // Asignar montos a las categorías correspondientes
                    foreach ($results as $result) {
                        $found_category = false;
                        $payment_method = is_object($result->payment_method) ? '' : strtolower(trim($result->payment_method));
                        
                        // Buscar coincidencia exacta primero
                        if (isset($payment_methods[$payment_method])) {
                            $payment_methods[$payment_method] += (float)$result->total;
                            $found_category = true;
                            $this->debug_log("Asignando {$result->total} a categoría {$payment_method} (coincidencia exacta)");
                        } 
                        // Buscar en los sinónimos
                        else {
                            foreach ($payment_mappings as $category => $synonyms) {
                                if (in_array($payment_method, $synonyms, true) || 
                                    in_array(strtolower($payment_method), array_map('strtolower', $synonyms))) {
                                    
                                    $payment_methods[$category] += (float)$result->total;
                                    $found_category = true;
                                    $this->debug_log("Asignando {$result->total} a categoría {$category} (sinónimo: {$payment_method})");
                                    break;
                                }
                            }
                        }
                        
                        // Si no encontramos categoría, asignar a 'other'
                        if (!$found_category && !empty($payment_method)) {
                            $payment_methods['other'] += (float)$result->total;
                            $this->debug_log("Método no reconocido '{$payment_method}' asignado a 'other': {$result->total}");
                        }
                    }
                }
            }
        }
        
        // 2. Procesar tabla pos_transactions (similar a pos_sales)
        $pos_transactions_table = $wpdb->prefix . 'pos_transactions';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_transactions_table}'") === $pos_transactions_table) {
            $this->debug_log("Procesando tabla pos_transactions para métodos de pago");
            
            // Obtener columnas de la tabla
            $trans_column_names = $wpdb->get_col("DESC {$pos_transactions_table}", 0);
            
            // Buscar columnas relevantes
            $trans_payment_method = null;
            $trans_amount = null;
            $trans_date = null;
            
            // Identificar columnas basadas en nombres comunes
            foreach ($trans_column_names as $column) {
                $column_lower = strtolower($column);
                
                if (strpos($column_lower, 'payment_method') !== false || 
                    strpos($column_lower, 'payment') !== false || 
                    strpos($column_lower, 'method') !== false) {
                    $trans_payment_method = $column;
                }
                
                if (strpos($column_lower, 'amount') !== false || 
                    strpos($column_lower, 'total') !== false) {
                    $trans_amount = $column;
                }
                
                if (strpos($column_lower, 'date') !== false || 
                    strpos($column_lower, 'created_at') !== false || 
                    strpos($column_lower, 'timestamp') !== false) {
                    $trans_date = $column;
                }
            }
            
            // Si encontramos las columnas necesarias
            if ($trans_payment_method && $trans_amount && $trans_date) {
                // Preparar condición y parámetros base
                $trans_query = "SELECT {$trans_payment_method} as payment_method, SUM({$trans_amount}) as total 
                               FROM {$pos_transactions_table} 
                               WHERE DATE({$trans_date}) = %s";
                $trans_args = [$date];
                
                // Filtrar por usuario si se especifica
                if ($user_id > 0) {
                    // Determinar qué columna de usuario usar
                    $user_columns = $special_user_trace ? 
                        ['seller_id', 'cashier_id', 'created_by', 'user_id', 'employee_id'] :
                        ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                    
                    $user_column_found = false;
                    
                    foreach ($user_columns as $user_col) {
                        if (in_array($user_col, $trans_column_names)) {
                            $trans_query .= " AND {$user_col} = %d";
                            $trans_args[] = $user_id;
                            $user_column_found = true;
                            $this->debug_log("Aplicando filtro de usuario en pos_transactions: {$user_col} = {$user_id}");
                            break;
                        }
                    }
                    
                    // Debug especial para Ileana
                    if ($special_user_trace && !$user_column_found) {
                        $this->debug_log("ADVERTENCIA: No se encontró columna de usuario para ILEANA en pos_transactions");
                    }
                }
                
                // Completar y ejecutar la consulta agrupada por método de pago
                $trans_query .= " GROUP BY {$trans_payment_method}";
                $prepared_trans_query = $wpdb->prepare($trans_query, $trans_args);
                $trans_results = $wpdb->get_results($prepared_trans_query);
                
                $this->debug_log("CONSULTA pos_transactions", [
                    'query' => $prepared_trans_query,
                    'results_count' => $trans_results ? count($trans_results) : 0
                ]);
                
                // Procesar resultados y mapearlos a las categorías estándar
                if ($trans_results) {
                    $payment_mappings = [
                        'cash' => ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'],
                        'card' => ['card', 'tarjeta', 'credit_card', 'tarjeta_credito', '2', 'Card', 'Tarjeta', 'CARD'],
                        'transfer' => ['transfer', 'transferencia', 'bank_transfer', '4', 'Transfer', 'Transferencia', 'TRANSFER'],
                        'check' => ['check', 'cheque', '5', 'Check', 'Cheque', 'CHECK'],
                        'other' => ['other', 'otro', '6', 'Other', 'Otro', 'OTHER']
                    ];
                    
                    foreach ($trans_results as $result) {
                        $found_category = false;
                        
                        foreach ($payment_mappings as $category => $values) {
                            if (in_array($result->payment_method, $values) || 
                                (is_string($result->payment_method) && 
                                (in_array(strtolower($result->payment_method), array_map('strtolower', $values)) ||
                                in_array(strtoupper($result->payment_method), array_map('strtoupper', $values))))) {
                                
                                $payment_methods[$category] += (float)$result->total;
                                $found_category = true;
                                $this->debug_log("[TRANS] Asignando {$result->total} a categoría {$category} (método: {$result->payment_method})");
                                break;
                            }
                        }
                        
                        // Si no encontramos categoría, asignar a 'other'
                        if (!$found_category && !empty($result->payment_method)) {
                            $payment_methods['other'] += (float)$result->total;
                            $this->debug_log("[TRANS] Método no reconocido '{$result->payment_method}' asignado a 'other': {$result->total}");
                        }
                    }
                }
            } else {
                $this->debug_log("ADVERTENCIA: No se encontraron las columnas necesarias en pos_transactions", [
                    'payment_method_col' => $trans_payment_method,
                    'amount_col' => $trans_amount,
                    'date_col' => $trans_date,
                    'columnas_disponibles' => $trans_column_names
                ]);
            }
        }
        
        // 3. Procesar tabla pos_payments 
        $pos_payments_table = $wpdb->prefix . 'pos_payments';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_payments_table}'") === $pos_payments_table) {
            $this->debug_log("Procesando tabla pos_payments para métodos de pago");
            
            // Obtener columnas de la tabla
            $payments_column_names = $wpdb->get_col("DESC {$pos_payments_table}", 0);
            
            // Buscar columnas relevantes
            $payment_method_col = null;
            $amount_col = null;
            $date_col = null;
            
            // Identificar columnas basadas en nombres comunes
            foreach ($payments_column_names as $column) {
                $column_lower = strtolower($column);
                
                if (strpos($column_lower, 'payment_method') !== false || 
                    strpos($column_lower, 'payment') !== false || 
                    strpos($column_lower, 'method') !== false) {
                    $payment_method_col = $column;
                }
                
                if (strpos($column_lower, 'amount') !== false || 
                    strpos($column_lower, 'total') !== false) {
                    $amount_col = $column;
                }
                
                if (strpos($column_lower, 'date') !== false || 
                    strpos($column_lower, 'created_at') !== false || 
                    strpos($column_lower, 'timestamp') !== false) {
                    $date_col = $column;
                }
            }
            
            // Si encontramos las columnas necesarias
            if ($payment_method_col && $amount_col && $date_col) {
                // Preparar condición y parámetros base
                $payments_query = "SELECT {$payment_method_col} as payment_method, SUM({$amount_col}) as total 
                               FROM {$pos_payments_table} 
                               WHERE DATE({$date_col}) = %s";
                $payments_args = [$date];
                
                // Filtrar por usuario si se especifica
                if ($user_id > 0) {
                    // Determinar qué columna de usuario usar
                    $user_columns = $special_user_trace ? 
                        ['seller_id', 'cashier_id', 'created_by', 'user_id', 'employee_id'] :
                        ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                    
                    $user_column_found = false;
                    
                    foreach ($user_columns as $user_col) {
                        if (in_array($user_col, $payments_column_names)) {
                            $payments_query .= " AND {$user_col} = %d";
                            $payments_args[] = $user_id;
                            $user_column_found = true;
                            $this->debug_log("Aplicando filtro de usuario en pos_payments: {$user_col} = {$user_id}");
                            break;
                        }
                    }
                    
                    // Filtrado especial para Ileana en caso de no encontrar columna de usuario
                    if ($special_user_trace && !$user_column_found) {
                        $this->debug_log("ADVERTENCIA: No se encontró columna de usuario para ILEANA en pos_payments");
                        
                        // Intentar filtrar por nombre en campos descriptivos si existen
                        $description_columns = array_intersect(['description', 'notes', 'comment', 'memo', 'reference'], $payments_column_names);
                        if (!empty($description_columns)) {
                            $desc_col = reset($description_columns); // Tomar el primer elemento
                            $payments_query .= " AND {$desc_col} LIKE %s";
                            $payments_args[] = "%Ileana%";
                            $this->debug_log("FILTRO ESPECIAL para Ileana: Buscando 'Ileana' en columna {$desc_col}");
                        }
                    }
                }
                
                // Completar y ejecutar la consulta agrupada por método de pago
                $payments_query .= " GROUP BY {$payment_method_col}";
                $prepared_payments_query = $wpdb->prepare($payments_query, $payments_args);
                $payments_results = $wpdb->get_results($prepared_payments_query);
                
                $this->debug_log("CONSULTA pos_payments", [
                    'query' => $prepared_payments_query,
                    'results_count' => $payments_results ? count($payments_results) : 0
                ]);
                
                // Procesar resultados y mapearlos a las categorías estándar
                if ($payments_results) {
                    $payment_mappings = [
                        'cash' => ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'],
                        'card' => ['card', 'tarjeta', 'credit_card', 'tarjeta_credito', '2', 'Card', 'Tarjeta', 'CARD'],
                        'transfer' => ['transfer', 'transferencia', 'bank_transfer', '4', 'Transfer', 'Transferencia', 'TRANSFER'],
                        'check' => ['check', 'cheque', '5', 'Check', 'Cheque', 'CHECK'],
                        'other' => ['other', 'otro', '6', 'Other', 'Otro', 'OTHER']
                    ];
                    
                    foreach ($payments_results as $result) {
                        $found_category = false;
                        
                        foreach ($payment_mappings as $category => $values) {
                            if (in_array($result->payment_method, $values) || 
                                (is_string($result->payment_method) && 
                                (in_array(strtolower($result->payment_method), array_map('strtolower', $values)) ||
                                in_array(strtoupper($result->payment_method), array_map('strtoupper', $values))))) {
                                
                                $payment_methods[$category] += (float)$result->total;
                                $found_category = true;
                                $this->debug_log("[PAYMENTS] Asignando {$result->total} a categoría {$category} (método: {$result->payment_method})");
                                break;
                            }
                        }
                        
                        // Si no encontramos categoría, asignar a 'other'
                        if (!$found_category && !empty($result->payment_method)) {
                            $payment_methods['other'] += (float)$result->total;
                            $this->debug_log("[PAYMENTS] Método no reconocido '{$result->payment_method}' asignado a 'other': {$result->total}");
                        }
                    }
                }
            } else {
                $this->debug_log("ADVERTENCIA: No se encontraron las columnas necesarias en pos_payments", [
                    'payment_method_col' => $payment_method_col,
                    'amount_col' => $amount_col,
                    'date_col' => $date_col,
                    'columnas_disponibles' => $payments_column_names
                ]);
            }
        }
        
        // Registro final de los montos calculados
        $this->debug_log("RESULTADOS FINALES DE MÉTODOS DE PAGO", $payment_methods);
        
        return $payment_methods;
    }
    
    /**
     * Calcular el monto para un método de pago específico
     */
    public function calculate_method_amount($payment_method, $register_id, $user_id, $date) {
        global $wpdb;
        
        // Buscar columna de método de pago
        $payment_method_column = '';
        foreach ($column_names as $col) {
            if (strpos(strtolower($col), 'payment_method') !== false || 
                strpos(strtolower($col), 'payment') !== false ||
                strpos(strtolower($col), 'method') !== false) {
                $payment_method_column = $col;
                break;
            }
        }
        
        // Log detallado del inicio del cálculo
        $this->debug_log("=== CALCULANDO MÉTODO DE PAGO: {$payment_method} ===", [
            'register_id' => $register_id,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'date' => $date,
            'user_filtering_enabled' => ($user_id > 0)
        ]);
        
        // Verificación especial para usuarios que pueden necesitar filtrado avanzado
        $special_user_trace = ($user_name == 'Ileana');
        if ($special_user_trace) {
            $this->debug_log("ATENCIÓN: Usuario especial detectado: {$user_name}. Activando traza detallada.");
        }
        
        // Mapear métodos de pago a sus posibles valores en la base de datos
        $payment_mappings = [
            'cash' => ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'],
            'card' => ['card', 'tarjeta', 'credit_card', 'tarjeta_credito', '2', 'Card', 'Tarjeta', 'CARD'],
            'transfer' => ['transfer', 'transferencia', 'bank_transfer', '4', 'Transfer', 'Transferencia', 'TRANSFER'],
            'check' => ['check', 'cheque', '5', 'Check', 'Cheque', 'CHECK'],
            'other' => ['other', 'otro', '6', 'Other', 'Otro', 'OTHER']
        ];
        
        $search_values = $payment_mappings[$payment_method] ?? [$payment_method];
        
        // 1. Buscar en tabla pos_sales si existe
        $pos_sales_table = $wpdb->prefix . 'pos_sales';
        $sales_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pos_sales_table}'") === $pos_sales_table;
        
        if ($sales_table_exists) {
            // Obtener estructura de la tabla
            $columns = $wpdb->get_results("DESCRIBE {$pos_sales_table}");
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            
            // Buscar columna de método de pago
            $payment_method_column = '';
            foreach ($column_names as $col) {
                if (strpos(strtolower($col), 'payment_method') !== false || 
                    strpos(strtolower($col), 'payment') !== false ||
                    strpos(strtolower($col), 'method') !== false) {
                    $payment_method_column = $col;
                    break;
                }
            }
            
            // DEBUG: Log detallado de columnas y búsqueda
            $this->debug_log("🔍 ANÁLISIS pos_sales - Búsqueda columna método de pago", [
                'table' => 'pos_sales',
                'payment_method_requested' => $payment_method,
                'search_values' => $search_values,
                'all_columns' => $column_names,
                'payment_method_column_found' => $payment_method_column ?: 'NINGUNA'
            ]);
            
            if ($payment_method_column) {
                // Buscar columna de fecha
                $date_column = 'created_at'; // Valor predeterminado
                $date_columns_priority = ['date_created', 'created_at', 'date', 'timestamp'];
                foreach ($columns as $column) {
                    foreach ($date_columns_priority as $priority_name) {
                        if (strtolower($column->Field) === strtolower($priority_name)) {
                            $date_column = $column->Field;
                            break 2;
                        }
                    }
                }
                
                // Determinar columna de total
                $total_column = in_array('total', $column_names) ? 'total' : (in_array('amount', $column_names) ? 'amount' : 'total');
                
                // Construir query
                $placeholders = array_fill(0, count($search_values), '%s');
                $placeholder_string = implode(',', $placeholders);
                
                $query = "SELECT SUM({$total_column}) as total FROM {$pos_sales_table} 
                         WHERE {$payment_method_column} IN ({$placeholder_string}) 
                         AND DATE({$date_column}) = %s";
                $args = array_merge($search_values, [$date]);
                
                // Filtrar por caja registradora si existe la columna
                if (in_array('register_id', $column_names)) {
                    $query .= " AND register_id = %d";
                    $args[] = $register_id;
                }
                
                // CRÍTICO: Filtrar por usuario si se especifica - MEJORADO
                if ($user_id > 0) {
                    // Obtener el usuario para verificaciones adicionales
                    $user = get_user_by('ID', $user_id);
                    $user_name = $user ? $user->display_name : 'Desconocido';
                    $is_ileana = ($user_name == 'Ileana');
                    
                    // Columnas donde buscar el usuario, con orden optimizado para Ileana
                    $user_columns = $is_ileana ? 
                        // Orden prioritario para Ileana (personalizado)
                        ['seller_id', 'cashier_id', 'created_by', 'user_id', 'employee_id'] :
                        // Orden normal para otros usuarios
                        ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                    
                    $user_column_found = false;
                    
                    foreach ($user_columns as $user_col) {
                        if (in_array($user_col, $column_names)) {
                            $query .= " AND {$user_col} = %d";
                            $args[] = $user_id;
                            $user_column_found = true;
                            
                            $this->debug_log("FILTRO POR USUARIO APLICADO en pos_sales", [
                                'payment_method' => $payment_method,
                                'user_column' => $user_col,
                                'user_id' => $user_id,
                                'user_name' => $user_name,
                                'es_ileana' => $is_ileana,
                                'table' => 'pos_sales'
                            ]);
                            break;
                        }
                    }
                    
                    if (!$user_column_found) {
                        $this->debug_log("ADVERTENCIA: No se encontró columna de usuario en pos_sales. Columnas disponibles: " . implode(", ", $column_names));
                    }
                    
                    // Debug adicional para caso de Ileana
                    if ($is_ileana && $special_user_trace) {
                        $this->debug_log("DATOS DETALLADOS PARA ILEANA en pos_sales", [
                            'schema' => $column_names,
                            'condición_aplicada' => $user_column_found ? "{$user_col} = {$user_id}" : 'Ninguna',
                            'payment_method' => $payment_method
                        ]);
                    }
                }
                
                $prepared_query = $wpdb->prepare($query, $args);
                $result = $wpdb->get_var($prepared_query);
                $amount_from_sales = (float)($result ?: 0);
                $total_amount += $amount_from_sales;
                
                $this->debug_log("RESULTADO de pos_sales para {$payment_method}", [
                    'query' => $prepared_query,
                    'amount' => $amount_from_sales,
                    'user_filtered' => ($user_id > 0)
                ]);
            } else {
                // Si no se encuentra columna de método de pago, el resultado debe ser 0 para métodos específicos como 'cash'
                if (in_array($payment_method, ['cash', 'card', 'transfer', 'check'])) {
                    $this->debug_log("NO SE ENCONTRÓ COLUMNA DE MÉTODO DE PAGO EN pos_sales. ESTABLECIENDO TOTAL A 0", [
                        'payment_method' => $payment_method,
                        'table' => 'pos_sales'
                    ]);
                    $total_amount = 0;
                }
            }
        }
        
        // 2. Buscar en tabla pos_transactions si existe
        $pos_transactions_table = $wpdb->prefix . 'pos_transactions';
        $transactions_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pos_transactions_table}'") === $pos_transactions_table;
        
        if ($transactions_table_exists) {
            $trans_columns = $wpdb->get_results("DESCRIBE {$pos_transactions_table}");
            $trans_column_names = array_map(function($col) { return $col->Field; }, $trans_columns);
            
            // Buscar columna de método de pago
            $trans_payment_method = '';
            foreach ($trans_column_names as $col) {
                if (strpos(strtolower($col), 'payment_method') !== false || 
                    strpos(strtolower($col), 'payment') !== false ||
                    strpos(strtolower($col), 'method') !== false) {
                    $trans_payment_method = $col;
                    break;
                }
            }
            
            if ($trans_payment_method) {
                $this->debug_log("🔍 DETALLE DE COLUMNAS pos_transactions", [
                    'columnas' => $trans_column_names,
                    'columna_pago_encontrada' => $trans_payment_method
                ]);
                
                $placeholders = array_fill(0, count($search_values), '%s');
                $placeholder_string = implode(',', $placeholders);
                
                $trans_query = "SELECT SUM(amount) as total FROM {$pos_transactions_table} 
                               WHERE {$trans_payment_method} IN ({$placeholder_string}) 
                               AND DATE(created_at) = %s";
                $trans_args = array_merge($search_values, [$date]);
                
                // Filtrar por caja registradora
                if (in_array('register_id', $trans_column_names)) {
                    $trans_query .= " AND register_id = %d";
                    $trans_args[] = $register_id;
                }
                
                // CRÍTICO: Filtrar por usuario si se especifica - MEJORADO
                if ($user_id > 0) {
                    // Obtener el usuario para verificaciones adicionales
                    $user = get_user_by('ID', $user_id);
                    $user_name = $user ? $user->display_name : 'Desconocido';
                    $is_ileana = ($user_name == 'Ileana');
                    
                    // Columnas donde buscar el usuario, con orden optimizado para Ileana
                    $user_columns = $is_ileana ? 
                        // Orden prioritario para Ileana (personalizado)
                        ['seller_id', 'cashier_id', 'created_by', 'user_id', 'employee_id'] :
                        // Orden normal para otros usuarios
                        ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                    
                    $user_column_found = false;
                    
                    foreach ($user_columns as $user_col) {
                        if (in_array($user_col, $trans_column_names)) {
                            $trans_query .= " AND {$user_col} = %d";
                            $trans_args[] = $user_id;
                            $user_column_found = true;
                            $this->debug_log("FILTRO POR USUARIO APLICADO en pos_transactions", [
                                'payment_method' => $payment_method,
                                'user_column' => $user_col,
                                'user_id' => $user_id,
                                'user_name' => $user_name,
                                'es_ileana' => $is_ileana,
                                'table' => 'pos_transactions'
                            ]);
                            break;
                        }
                    }
                    
                    if (!$user_column_found) {
                        $this->debug_log("ADVERTENCIA: No se encontró columna de usuario en pos_transactions. Columnas disponibles: " . implode(", ", $trans_column_names));
                    }
                    
                    // Debug adicional y verificación especial para Ileana
                    if ($is_ileana && $special_user_trace) {
                        $this->debug_log("DATOS DETALLADOS PARA ILEANA en pos_transactions", [
                            'schema' => $trans_column_names,
                            'condición_aplicada' => $user_column_found ? "{$user_col} = {$user_id}" : 'Ninguna',
                            'payment_method' => $payment_method
                        ]);
                        
                        // Ejecutar una consulta de diagnóstico para Ileana
                        $diagnostic_query = "SELECT * FROM {$pos_transactions_table} 
                                            WHERE {$trans_payment_method} IN ('cash','efectivo','1','Cash','Efectivo','CASH','EFECTIVO')
                                            AND DATE(created_at) = %s 
                                            LIMIT 5";
                        $diagnostic_result = $wpdb->get_results($wpdb->prepare($diagnostic_query, $date));
                        
                        if ($diagnostic_result) {
                            $this->debug_log("MUESTRA DE DATOS para Ileana - EFECTIVO", $diagnostic_result);
                        } else {
                            $this->debug_log("NO HAY DATOS de muestra para Ileana - EFECTIVO");
                        }
                    }
                }
                
                // Filtrar por fecha
                $trans_query .= " AND DATE(created_at) = %s";
                $trans_args[] = $date;
                
                $prepared_trans_query = $wpdb->prepare($trans_query, $trans_args);
                $trans_result = $wpdb->get_var($prepared_trans_query);
                $amount_from_transactions = (float)($trans_result ?: 0);
                $total_amount += $amount_from_transactions;
                
                $this->debug_log("RESULTADO de pos_transactions para {$payment_method}", [
                    'query' => $prepared_trans_query,
                    'amount' => $amount_from_transactions,
                    'user_filtered' => ($user_id > 0)
                ]);
            } else {
                // Si no se encuentra columna de método de pago, el resultado debe ser 0 para métodos específicos como 'cash'
                if (in_array($payment_method, ['cash', 'card', 'transfer', 'check'])) {
                    $this->debug_log("NO SE ENCONTRÓ COLUMNA DE MÉTODO DE PAGO EN pos_transactions. ESTABLECIENDO TOTAL A 0", [
                        'payment_method' => $payment_method,
                        'table' => 'pos_transactions'
                    ]);
                    $total_amount = 0;
                }
            }
        }
        
        // 3. Buscar en tabla pos_payments si existe
        $pos_payments_table = $wpdb->prefix . 'pos_payments';
        $payments_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pos_payments_table}'") === $pos_payments_table;
        
        if ($payments_table_exists) {
            $payments_columns = $wpdb->get_results("DESCRIBE {$pos_payments_table}");
            $payments_column_names = array_map(function($col) { return $col->Field; }, $payments_columns);
            
            // Buscar columna de fecha
            $payments_date_column = 'created_at'; // Valor predeterminado
            $date_columns_priority = ['date_created', 'created_at', 'date', 'timestamp'];
            foreach ($payments_columns as $column) {
                foreach ($date_columns_priority as $priority_name) {
                    if (strtolower($column->Field) === strtolower($priority_name)) {
                        $payments_date_column = $column->Field;
                        break 2;
                    }
                }
            }
            
            // Si no encontramos columna por nombre exacto, buscamos por patrones
            if (!$payments_date_column) {
                foreach ($payments_columns as $column) {
                    if (strpos(strtolower($column->Field), 'date') !== false || 
                        strpos(strtolower($column->Field), 'created') !== false || 
                        strpos(strtolower($column->Field), 'time') !== false) {
                        $payments_date_column = $column->Field;
                        break;
                    }
                }
            }
            
            // Agrupar correctamente las condiciones OR con paréntesis
            $payments_query = "SELECT SUM(amount) as total FROM {$pos_payments_table} 
                              WHERE (payment_method LIKE '%cash%' OR payment_method LIKE '%efectivo%')";
            
            if ($payments_date_column) {
                $payments_query .= " AND DATE({$payments_date_column}) = %s";
                $payments_args = [$date];
                
                // CRÍTICO: Filtrar por usuario si se especifica - MEJORADO
                if ($user_id > 0) {
                    $user_columns = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                    $user_column_found = false;
                    
                    foreach ($user_columns as $user_col) {
                        if (in_array($user_col, $payments_column_names)) {
                            $payments_query .= " AND {$user_col} = %d";
                            $payments_args[] = $user_id;
                            $user_column_found = true;
                            $this->debug_log("FILTRO POR USUARIO APLICADO en pos_payments", [
                                'payment_method' => $payment_method,
                                'user_column' => $user_col,
                                'user_id' => $user_id,
                                'table' => 'pos_payments'
                            ]);
                            break;
                        }
                    }
                    
                    if (!$user_column_found) {
                        $this->debug_log("ADVERTENCIA: No se encontró columna de usuario en pos_payments. Columnas disponibles: " . implode(", ", $payments_column_names));
                    }
                }
                
                $prepared_payments_query = $wpdb->prepare($payments_query, $payments_args);
                $this->debug_log("Query pos_payments: {$prepared_payments_query}");
                $payments_result = $wpdb->get_var($prepared_payments_query);
                
                if ($payments_result && (float)$payments_result > 0) {
                    $payments_total = (float)$payments_result;
                    
                    // Si hay filtro de usuario y ya tenemos otros totales, sumamos en vez de reemplazar
                    if ($user_id > 0 && $total_amount > 0) {
                        // Sumamos el total de pos_payments al total existente
                        $this->debug_log("Sumando pos_payments:{$payments_total} al total existente:{$total_amount}");
                        $total_amount += $payments_total;
                    } else {
                        // Si no hay filtro o no hay otros totales, simplemente asignamos
                        $this->debug_log("Asignando total de pos_payments:{$payments_total} como total final");
                        $total_amount = $payments_total;
                    }
                    
                    $this->debug_log("RESULTADO de pos_payments para {$payment_method}", [
                        'query' => $prepared_payments_query,
                        'amount' => $payments_total,
                        'user_filtered' => ($user_id > 0)
                    ]);
                }
            }
        }
        
        // Log final con resumen completo
        $this->debug_log("=== RESUMEN FINAL MÉTODO {$payment_method} ===", [
            'total_amount' => $total_amount,
            'user_id' => $user_id,
            'user_filtering_applied' => ($user_id > 0),
            'tables_checked' => [
                'pos_sales' => $sales_table_exists,
                'pos_transactions' => $transactions_table_exists,
                'pos_payments' => $payments_table_exists
            ]
        ]);
        
        // NUEVA VALIDACIÓN: Si estamos buscando 'cash' y el total es muy alto, podría estar sumando todas las ventas
        if ($payment_method === 'cash' && $total_amount > 0) {
            $this->debug_log("⚠️ VERIFICACIÓN EFECTIVO: Total parece alto", [
                'payment_method' => $payment_method,
                'total_amount' => $total_amount,
                'warning' => 'Verificar si está sumando todas las ventas en lugar de solo efectivo'
            ]);
        }
        
        return $total_amount;
    }
    
    /**
     * AJAX: Obtener métodos de pago disponibles según los filtros
     */
    public function ajax_get_available_payment_methods() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_pos_closure_nonce')) {
            wp_send_json_error(['message' => __('Solicitud no autorizada.', 'wp-pos')]);
        }
        
        // Obtener parámetros
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('Y-m-d');
        $register_id = isset($_POST['register_id']) ? intval($_POST['register_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$register_id) {
            wp_send_json_error(['message' => __('Se requiere el ID de la caja registradora.', 'wp-pos')]);
        }
        
        global $wpdb;
        $methods = [];
        
        // Obtener métodos de pago del sistema
        $system_methods = function_exists('wp_pos_get_payment_methods') ? 
            wp_pos_get_payment_methods() : [
                'cash' => ['label' => __('Efectivo', 'wp-pos'), 'icon' => 'money-alt'],
                'card' => ['label' => __('Tarjeta', 'wp-pos'), 'icon' => 'credit-card'],
                'transfer' => ['label' => __('Transferencia', 'wp-pos'), 'icon' => 'bank'],
                'check' => ['label' => __('Cheque', 'wp-pos'), 'icon' => 'money'],
                'other' => ['label' => __('Otro', 'wp-pos'), 'icon' => 'admin-generic']
            ];
        
        // Verificar qué métodos tienen transacciones para los filtros dados
        $tables_to_check = [
            $wpdb->prefix . 'pos_sales',
            $wpdb->prefix . 'pos_transactions',
            $wpdb->prefix . 'pos_payments'
        ];
        
        $found_methods = [];
        
        foreach ($tables_to_check as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                continue;
            }
            
            // Obtener columnas de la tabla
            $columns = $wpdb->get_col("DESCRIBE $table");
            
            // Construir consulta dinámica
            $query = "SELECT DISTINCT payment_method FROM $table WHERE 1=1";
            $args = [];
            
            // Filtrar por fecha si existe columna de fecha
            $date_columns = ['date_created', 'created_at', 'date', 'timestamp'];
            $date_column = null;
            
            foreach ($date_columns as $col) {
                if (in_array($col, $columns)) {
                    $date_column = $col;
                    break;
                }
            }
            
            if ($date_column) {
                $query .= " AND DATE($date_column) = %s";
                $args[] = $date;
            }
            
            // Filtrar por caja registradora
            if (in_array('register_id', $columns) && $register_id) {
                $query .= " AND register_id = %d";
                $args[] = $register_id;
            }
            
            // Filtrar por usuario si se especifica
            if ($user_id) {
                $user_columns = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                $user_column_found = false;
                
                foreach ($user_columns as $user_col) {
                    if (in_array($user_col, $columns)) {
                        $query .= " AND $user_col = %d";
                        $args[] = $user_id;
                        $user_column_found = true;
                        break;
                    }
                }
            }
            
            // Ejecutar consulta
            if (!empty($args)) {
                $query = $wpdb->prepare($query, $args);
            }
            
            $results = $wpdb->get_results($query);
            
            // Procesar resultados
            foreach ($results as $row) {
                if (!empty($row->payment_method)) {
                    $found_methods[strtolower($row->payment_method)] = true;
                }
            }
        }
        
        // Si no se encontraron métodos, usar todos los métodos del sistema
        if (empty($found_methods)) {
            foreach ($system_methods as $id => $method) {
                $methods[] = [
                    'id' => $id,
                    'label' => is_array($method) ? $method['label'] : $method,
                    'icon' => is_array($method) && !empty($method['icon']) ? $method['icon'] : 'admin-generic'
                ];
            }
        } else {
            // Mapear métodos encontrados a los métodos del sistema
            $method_mapping = [
                'cash' => ['efectivo', 'cash', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'],
                'card' => ['card', 'tarjeta', 'credit_card', 'tarjeta_credito', '2', 'Card', 'Tarjeta', 'CARD'],
                'transfer' => ['transfer', 'transferencia', 'bank_transfer', '4', 'Transfer', 'Transferencia', 'TRANSFER'],
                'check' => ['check', 'cheque', '5', 'Check', 'Cheque', 'CHECK'],
                'other' => ['other', 'otro', '6', 'Other', 'Otro', 'OTHER']
            ];
            
            $added_methods = [];
            
            // Primero, agregar métodos encontrados
            foreach ($found_methods as $method => $_) {
                $method_lower = strtolower($method);
                $found = false;
                
                // Buscar en los mapeos
                foreach ($method_mapping as $sys_id => $variations) {
                    if (in_array($method_lower, array_map('strtolower', $variations))) {
                        if (!in_array($sys_id, $added_methods) && isset($system_methods[$sys_id])) {
                            $methods[] = [
                                'id' => $sys_id,
                                'label' => is_array($system_methods[$sys_id]) ? $system_methods[$sys_id]['label'] : $system_methods[$sys_id],
                                'icon' => is_array($system_methods[$sys_id]) && !empty($system_methods[$sys_id]['icon']) ? 
                                    $system_methods[$sys_id]['icon'] : 'admin-generic'
                            ];
                            $added_methods[] = $sys_id;
                        }
                        $found = true;
                        break;
                    }
                }
                
                // Si no se encontró en los mapeos, agregar tal cual
                if (!$found && !in_array($method_lower, $added_methods)) {
                    $methods[] = [
                        'id' => sanitize_key($method_lower),
                        'label' => ucfirst($method_lower),
                        'icon' => 'admin-generic'
                    ];
                    $added_methods[] = $method_lower;
                }
            }
            
            // Asegurarse de que al menos los métodos del sistema estén incluidos
            foreach ($system_methods as $id => $method) {
                if (!in_array($id, $added_methods)) {
                    $methods[] = [
                        'id' => $id,
                        'label' => is_array($method) ? $method['label'] : $method,
                        'icon' => is_array($method) && !empty($method['icon']) ? $method['icon'] : 'admin-generic'
                    ];
                }
            }
        }
        
        wp_send_json_success(['methods' => $methods]);
    }
    
    /**
     * AJAX: Diagnóstico para problemas de cálculo
     */
    public function ajax_diagnostic() {
        // Verificar permisos
        if (!current_user_can('administrator')) {
            wp_send_json_error(['message' => __('Solo los administradores pueden ejecutar diagnósticos.', 'wp-pos')]);
        }
        
        // Obtener fecha
        $date = isset($_REQUEST['date']) ? sanitize_text_field($_REQUEST['date']) : date('Y-m-d');
        
        global $wpdb;
        $output = [];
        
        // Información general
        $output['date'] = $date;
        $output['database_prefix'] = $wpdb->prefix;
        
        // Listar todas las tablas con prefijo pos_
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}pos_%'");
        $pos_tables = [];
        
        if ($tables) {
            foreach ($tables as $table) {
                foreach ($table as $table_name) {
                    $pos_tables[] = $table_name;
                }
            }
        }
        
        $output['pos_tables'] = $pos_tables;
        
        // Buscar en custom post types (si G-POS usa CPT)
        $posts_query = "SELECT ID, post_title, post_date FROM {$wpdb->posts} 
                       WHERE post_type LIKE '%order%' OR post_type LIKE '%sale%' OR post_type LIKE '%pos%'
                       AND DATE(post_date) = %s LIMIT 10";
        
        $sales_posts = $wpdb->get_results($wpdb->prepare($posts_query, [$date]));
        $output['sales_in_cpt'] = $sales_posts ? $sales_posts : [];
        
        // Verificar tablas específicas de ventas
        $sales_data = [];
        $total_sales = 0;
        
        // 1. Verificar tabla pos_sales si existe
        $pos_sales_table = $wpdb->prefix . 'pos_sales';
        $sales_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pos_sales_table}'") === $pos_sales_table;
        
        if ($sales_table_exists) {
            // Obtener estructura de la tabla
            $columns = $wpdb->get_results("DESCRIBE {$pos_sales_table}");
            $sales_data['pos_sales_structure'] = $columns;
            
            // Encontrar columna de fecha
            $date_column = 'created_at'; // Valor predeterminado
            $date_columns_priority = ['date_created', 'created_at', 'date', 'timestamp'];
            foreach ($columns as $column) {
                foreach ($date_columns_priority as $priority_name) {
                    if (strtolower($column->Field) === strtolower($priority_name)) {
                        $date_column = $column->Field;
                        break 2;
                    }
                }
            }
            
            // Consultar ventas para esa fecha
            $sales_query = "SELECT * FROM {$pos_sales_table} WHERE DATE({$date_column}) = %s LIMIT 10";
            $sales_rows = $wpdb->get_results($wpdb->prepare($sales_query, [$date]));
            $sales_data['pos_sales_data'] = $sales_rows;
            
            // Calcular total de ventas
            if (in_array('total', array_map(function($col) { return $col->Field; }, $columns))) {
                $total_query = "SELECT SUM(total) as total_sum FROM {$pos_sales_table} WHERE DATE({$date_column}) = %s";
                $total_result = $wpdb->get_row($wpdb->prepare($total_query, [$date]));
                if ($total_result && isset($total_result->total_sum)) {
                    $total_sales = (float)$total_result->total_sum;
                    $sales_data['pos_sales_total'] = $total_sales;
                }
            }
        }
        
        $output['sales_data'] = $sales_data;
        
        // 2. Verificar WooCommerce (si está instalado)
        $wc_data = [];
        $wc_table = $wpdb->prefix . 'wc_order_stats';
        $wc_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wc_table}'") === $wc_table;
        
        if ($wc_table_exists) {
            $wc_query = "SELECT * FROM {$wc_table} WHERE DATE(date_created) = %s AND status = 'wc-completed' LIMIT 10";
            $wc_orders = $wpdb->get_results($wpdb->prepare($wc_query, [$date]));
            $wc_data['wc_orders'] = $wc_orders;
            
            // Calcular total de WooCommerce
            $wc_total_query = "SELECT SUM(total_sales) as total_sum FROM {$wc_table} WHERE DATE(date_created) = %s AND status = 'wc-completed'";
            $wc_total = $wpdb->get_row($wpdb->prepare($wc_total_query, [$date]));
            if ($wc_total && isset($wc_total->total_sum)) {
                $wc_data['wc_total'] = (float)$wc_total->total_sum;
            }
        }
        
        $output['woocommerce_data'] = $wc_data;
        
        // 3. Verificar si hay un registro de ventas personalizado
        // Esto depende del sistema específico de G-POS, podría necesitar adaptación
        
        // Buscar la tabla real donde se almacenan las ventas
        $possible_sale_tables = [];
        foreach ($pos_tables as $table) {
            // Si la tabla contiene 'sale', 'order' o 'venta' en su nombre
            if (strpos($table, 'sale') !== false || strpos($table, 'order') !== false || 
                strpos($table, 'venta') !== false || strpos($table, 'orden') !== false) {
                $possible_sale_tables[] = $table;
            }
        }
        
        // Investigar cada tabla potencial
        $other_sales_data = [];
        foreach ($possible_sale_tables as $table) {
            if ($table === $pos_sales_table) continue; // Ya lo hemos verificado
            
            // Verificar estructura
            $columns = $wpdb->get_results("DESCRIBE {$table}");
            
            // Encontrar columna de fecha
            $date_column = null;
            foreach ($columns as $column) {
                if (strpos(strtolower($column->Field), 'date') !== false || 
                    strpos(strtolower($column->Field), 'created') !== false || 
                    strpos(strtolower($column->Field), 'time') !== false) {
                    $date_column = $column->Field;
                    break;
                }
            }
            
            if ($date_column) {
                // Buscar ventas para la fecha seleccionada
                $query = "SELECT * FROM {$table} WHERE DATE({$date_column}) = %s LIMIT 10";
                $results = $wpdb->get_results($wpdb->prepare($query, [$date]));
                
                if (!empty($results)) {
                    $other_sales_data[$table] = [
                        'structure' => $columns,
                        'data' => $results
                    ];
                    
                    // Buscar columnas que podrían contener importes
                    $amount_columns = [];
                    foreach ($columns as $column) {
                        if (in_array(strtolower($column->Field), ['total', 'amount', 'price', 'valor', 'importe', 'monto'])) {
                            $amount_columns[] = $column->Field;
                        }
                    }
                    
                    // Si encontramos columnas de importe, calcular totales
                    if (!empty($amount_columns)) {
                        foreach ($amount_columns as $amount_column) {
                            $total_query = "SELECT SUM({$amount_column}) as column_sum FROM {$table} WHERE DATE({$date_column}) = %s";
                            $total_result = $wpdb->get_row($wpdb->prepare($total_query, [$date]));
                            if ($total_result && isset($total_result->column_sum) && (float)$total_result->column_sum > 0) {
                                $other_sales_data[$table]['totals'][$amount_column] = (float)$total_result->column_sum;
                            }
                        }
                    }
                }
            }
        }
        
        $output['other_sales_data'] = $other_sales_data;
        
        // Recomendaciones basadas en los datos encontrados
        $recommendations = [];
        
        if ($total_sales > 0) {
            $recommendations[] = sprintf(
                'Encontramos un total de $%s en ventas para la fecha %s en la tabla %s.',
                number_format($total_sales, 2),
                $date,
                $pos_sales_table
            );
        } elseif (isset($wc_data['wc_total']) && $wc_data['wc_total'] > 0) {
            $recommendations[] = sprintf(
                'Encontramos un total de $%s en ventas de WooCommerce para la fecha %s.',
                number_format($wc_data['wc_total'], 2),
                $date
            );
        } else {
            $found_other_sales = false;
            foreach ($other_sales_data as $table => $data) {
                if (isset($data['totals'])) {
                    foreach ($data['totals'] as $column => $amount) {
                        if ($amount > 0) {
                            $recommendations[] = sprintf(
                                'Encontramos un total de $%s en la columna %s de la tabla %s para la fecha %s.',
                                number_format($amount, 2),
                                $column,
                                $table,
                                $date
                            );
                            $found_other_sales = true;
                        }
                    }
                }
            }
            
            if (!$found_other_sales) {
                $recommendations[] = 'No encontramos ventas registradas para la fecha ' . $date . ' en ninguna tabla.';
                $recommendations[] = 'Verifica que las ventas se estén guardando correctamente o prueba con otra fecha.';
            }
        }
        
        $output['recommendations'] = $recommendations;
        
        // Devolver todos los datos encontrados
        wp_send_json_success($output);
    }
    
    /**
     * AJAX: Obtener detalles de un cierre específico
     */
    public function ajax_get_closure_details() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_pos') && !current_user_can('administrator')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'wp-pos')]);
        }
        
        // Obtener el ID del cierre
        $closure_id = isset($_POST['closure_id']) ? intval($_POST['closure_id']) : 0;
        
        if ($closure_id <= 0) {
            wp_send_json_error(['message' => __('ID de cierre inválido.', 'wp-pos')]);
        }
        
        global $wpdb;
        
        // Consulta para obtener los detalles del cierre
        $closure = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, 
                     u.display_name as user_name,
                     r.name as register_name 
              FROM {$wpdb->prefix}pos_closures c
              LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
              LEFT JOIN {$wpdb->prefix}pos_registers r ON c.register_id = r.id
              WHERE c.id = %d",
            $closure_id
        ), ARRAY_A);
        
        if (!$closure) {
            wp_send_json_error(['message' => __('Cierre no encontrado.', 'wp-pos')]);
        }
        
        // Decodificar desglose de métodos de pago si existe
        if (isset($closure['payment_breakdown']) && !empty($closure['payment_breakdown'])) {
            $decoded = json_decode($closure['payment_breakdown'], true);
            $closure['payment_breakdown_decoded'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
        } else {
            $closure['payment_breakdown_decoded'] = null;
        }
        wp_send_json_success(['closure' => $closure]);
    }
    
    // La función ajax_update_status ha sido movida y mejorada con soporte para historial y notificaciones
    
    /**
     * AJAX: Actualizar estado de un cierre (aprobar/rechazar)
     */
    public function ajax_update_status() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Verificar permisos (solo administradores pueden cambiar estados)
        if (!current_user_can('administrator')) {
            wp_send_json_error(['message' => __('Solo los administradores pueden aprobar o rechazar cierres.', 'wp-pos')]);
            return;
        }
        
        // Obtener parámetros
        $closure_id = isset($_POST['closure_id']) ? intval($_POST['closure_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $justification = isset($_POST['justification']) ? sanitize_textarea_field($_POST['justification']) : '';
        
        // Validar parámetros
        if ($closure_id <= 0) {
            wp_send_json_error(['message' => __('ID de cierre inválido.', 'wp-pos')]);
            return;
        }
        
        if (!in_array($status, ['approved', 'rejected'])) {
            wp_send_json_error(['message' => __('Estado inválido.', 'wp-pos')]);
            return;
        }
        
        // Si es rechazo, verificar que haya justificación
        if ($status === 'rejected' && empty($justification)) {
            wp_send_json_error(['message' => __('Debe proporcionar una justificación para rechazar el cierre.', 'wp-pos')]);
            return;
        }
        
        global $wpdb;
        
        // Obtener datos actuales del cierre
        $closure = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pos_closures WHERE id = %d",
            $closure_id
        ));
        
        if (!$closure) {
            wp_send_json_error(['message' => __('No se encontró el cierre especificado.', 'wp-pos')]);
            return;
        }
        
        // Guardar el estado anterior
        $old_status = $closure->status;
        
        // Si ya tenía el mismo estado, no hacer nada
        if ($old_status === $status) {
            wp_send_json_success([
                'message' => __('El cierre ya tenía este estado.', 'wp-pos'),
                'status' => $status
            ]);
            return;
        }
        
        // Actualizar estado
        $updated = $wpdb->update(
            $wpdb->prefix . 'pos_closures',
            ['status' => $status],
            ['id' => $closure_id],
            ['%s'],
            ['%d']
        );
        
        if ($updated === false) {
            wp_send_json_error(['message' => __('Error al actualizar el estado del cierre.', 'wp-pos')]);
            return;
        }
        
        // Registrar el cambio en el historial
        $this->register_status_change($closure_id, $old_status, $status, $justification);
        
        // Notificar al usuario sobre el cambio de estado
        $this->notify_user_about_status_change($closure, $status, $justification);
        
        // Respuesta exitosa
        wp_send_json_success([
            'message' => $status === 'approved' ? 
                __('Cierre aprobado correctamente.', 'wp-pos') : 
                __('Cierre rechazado correctamente.', 'wp-pos'),
            'status' => $status
        ]);
    }
    
    /**
     * AJAX: Obtener historial de cambios de estado de un cierre
     */
    public function ajax_get_status_history() {
        // Verificar nonce
        check_ajax_referer('wp_pos_closures_nonce', 'nonce');
        
        // Verificar permisos
        if (!current_user_can('manage_pos') && !current_user_can('administrator')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'wp-pos')]);
            return;
        }
        
        // Obtener parámetros
        $closure_id = isset($_POST['closure_id']) ? intval($_POST['closure_id']) : 0;
        
        if ($closure_id <= 0) {
            wp_send_json_error(['message' => __('ID de cierre inválido.', 'wp-pos')]);
            return;
        }
        
        // Verificar si existe la tabla de historial
        $this->ensure_status_history_table_exists();
        
        global $wpdb;
        
        // Obtener información del cierre
        $closure = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, u.display_name as user_name
            FROM {$wpdb->prefix}pos_closures c
            LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
            WHERE c.id = %d",
            $closure_id
        ));
        
        if (!$closure) {
            wp_send_json_error(['message' => __('No se encontró el cierre especificado.', 'wp-pos')]);
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
            'status_distribution' => [],
            'current_register' => [
                'balance' => 0,
                'today_change' => 0
            ],
            'today_sales' => [
                'amount' => 0,
                'change_percent' => 0
            ]
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
        
        // 0. Obtener datos de la caja actual
        $current_register = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}pos_registers 
            WHERE status = 'open' 
            ORDER BY id DESC 
            LIMIT 1"
        );
        
        if ($current_register) {
            $dashboard_data['current_register']['balance'] = floatval($current_register->current_balance);
            
            // Calcular cambio del día (diferencia entre balance actual y apertura)
            $today_start = current_time('Y-m-d 00:00:00');
            $dashboard_data['current_register']['today_change'] = floatval($current_register->current_balance) - floatval($current_register->opening_balance);
        }
        
        // 0.1 Obtener ventas del día actual
        $today_sales = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COALESCE(SUM(total), 0) as amount,
                (SELECT COALESCE(SUM(total), 0) 
                 FROM {$wpdb->prefix}pos_sales 
                 WHERE DATE(date_created) = DATE(DATE_SUB(%s, INTERVAL 1 DAY))
                ) as prev_day_amount
            FROM {$wpdb->prefix}pos_sales 
            WHERE DATE(date_created) = %s",
            $current_date,
            $current_date
        ));
        
        if ($today_sales) {
            $dashboard_data['today_sales']['amount'] = floatval($today_sales->amount);
            
            // Calcular porcentaje de cambio respecto al día anterior
            if ($today_sales->prev_day_amount > 0) {
                $change = (($today_sales->amount - $today_sales->prev_day_amount) / $today_sales->prev_day_amount) * 100;
                $dashboard_data['today_sales']['change_percent'] = round($change, 1);
            } elseif ($today_sales->amount > 0) {
                // Si no hay ventas el día anterior pero sí hoy, mostramos un aumento del 100%
                $dashboard_data['today_sales']['change_percent'] = 100.0;
            }
        }
        
        // 1. Datos del mes actual
        $current_month_start = date('Y-m-01', strtotime($current_date));
        $current_month_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(actual_amount) as total_amount,
                SUM(actual_amount - expected_amount) as total_difference
            FROM {$wpdb->prefix}pos_closures
            WHERE DATE(date_created) BETWEEN %s AND %s",
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
            WHERE DATE(date_created) BETWEEN %s AND %s",
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
            WHERE DATE(date_created) BETWEEN %s AND %s
            GROUP BY DATE(date_created)
            ORDER BY DATE(date_created) ASC",
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

    /**
     * Calcula el monto de un método de pago específico
     * 
     * @param string $payment_method Método de pago (cash, card, transfer, check, other)
     * @param int $register_id ID de la caja registradora
     * @param int $user_id ID del usuario (opcional)
     * @param string $date Fecha en formato Y-m-d
     * @return float Monto total para el método de pago
     */
    private function calculate_payment_method_amount($payment_method, $register_id, $user_id, $date) {
        global $wpdb;
        
        // Log de inicio con parámetros recibidos
        $this->debug_log("=== CALCULANDO MÉTODO DE PAGO: {$payment_method} ===", [
            'register_id' => $register_id,
            'user_id' => $user_id,
            'date' => $date,
            'user_filtering_enabled' => ($user_id > 0)
        ]);
        
        // Mapeo de valores posibles para cada método de pago en la base de datos
        $payment_mappings = [
            'cash' => ['cash', 'efectivo', '1', 'Cash', 'Efectivo', 'CASH', 'EFECTIVO'],
            'card' => ['card', 'tarjeta', 'credit_card', 'tarjeta_credito', '2', 'Card', 'Tarjeta', 'CARD'],
            'transfer' => ['transfer', 'transferencia', 'bank_transfer', '4', 'Transfer', 'Transferencia', 'TRANSFER'],
            'check' => ['check', 'cheque', '5', 'Check', 'Cheque', 'CHECK'],
            'other' => ['other', 'otro', '6', 'Other', 'Otro', 'OTHER']
        ];
        
        // Verificar que el método solicitado existe en nuestro mapeo
        if (!isset($payment_mappings[$payment_method])) {
            $this->debug_log("❌ ERROR: Método de pago no reconocido: {$payment_method}");
            return 0;
        }
        
        // Valores a buscar en la base de datos para este método
        $method_values = $payment_mappings[$payment_method];
        $total_amount = 0;
        
        // 1. Consulta a pos_sales (ventas)
        $pos_sales_table = $wpdb->prefix . 'pos_sales';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_sales_table}'") === $pos_sales_table) {
            // Obtener columnas de la tabla
            $column_names = $wpdb->get_col("DESC {$pos_sales_table}", 0);
            
            // Buscar columnas relevantes: método de pago, total, fecha
            $payment_method_column = null;
            $total_column = 'total'; // Default
            $date_column = 'created_at'; // Default
            
            // Buscar columna de método de pago
            foreach ($column_names as $column) {
                $column_lower = strtolower($column);
                if (strpos($column_lower, 'payment_method') !== false || 
                    strpos($column_lower, 'payment') !== false || 
                    strpos($column_lower, 'method') !== false) {
                    $payment_method_column = $column;
                    break;
                }
            }
            
            // Buscar columna de total/monto
            if (!in_array('total', $column_names)) {
                foreach ($column_names as $column) {
                    $column_lower = strtolower($column);
                    if (strpos($column_lower, 'total') !== false || 
                        strpos($column_lower, 'amount') !== false || 
                        strpos($column_lower, 'monto') !== false) {
                        $total_column = $column;
                        break;
                    }
                }
            }
            
            // Buscar columna de fecha
            if (!in_array('created_at', $column_names)) {
                foreach ($column_names as $column) {
                    $column_lower = strtolower($column);
                    if (strpos($column_lower, 'date') !== false || 
                        strpos($column_lower, 'created') !== false || 
                        strpos($column_lower, 'fecha') !== false) {
                        $date_column = $column;
                        break;
                    }
                }
            }
            
            // Si encontramos columna de método de pago, construir consulta
            if ($payment_method_column) {
                $query = "SELECT SUM({$total_column}) as total FROM {$pos_sales_table} WHERE ";
                $conditions = [];
                $args = [];
                
                // Condición para método de pago (usando OR para múltiples posibles valores)
                $method_placeholders = array_fill(0, count($method_values), '%s');
                $conditions[] = "{$payment_method_column} IN (" . implode(", ", $method_placeholders) . ")";
                $args = array_merge($args, $method_values);
                
                // Condición para fecha
                $conditions[] = "DATE({$date_column}) = %s";
                $args[] = $date;
                
                // Condición para caja registradora (si existe la columna)
                if (in_array('register_id', $column_names)) {
                    $conditions[] = "register_id = %d";
                    $args[] = $register_id;
                }
                
                // Filtrar por usuario si se especificó
                if ($user_id > 0) {
                    $user_columns = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                    $user_column_found = false;
                    
                    foreach ($user_columns as $user_col) {
                        if (in_array($user_col, $column_names)) {
                            $conditions[] = "{$user_col} = %d";
                            $args[] = $user_id;
                            $user_column_found = true;
                            $this->debug_log("✅ Filtro por usuario aplicado en pos_sales", [
                                'columna' => $user_col,
                                'user_id' => $user_id
                            ]);
                            break;
                        }
                    }
                    
                    if (!$user_column_found && $user_id > 0) {
                        $this->debug_log("⚠️ ADVERTENCIA: No se encontró columna de usuario en pos_sales para filtrar");
                    }
                }
                
                // Construir consulta final
                $query .= implode(" AND ", $conditions);
                $prepared_query = $wpdb->prepare($query, $args);
                
                // Ejecutar consulta y obtener resultado
                $this->debug_log("Consulta a pos_sales para {$payment_method}", ['query' => $prepared_query]);
                $result = $wpdb->get_var($prepared_query);
                $sales_total = (float)($result ?: 0);
                $total_amount += $sales_total;
                
                $this->debug_log("Resultado de pos_sales para {$payment_method}", ['total' => $sales_total]);
            }
        }
        
        // 2. Consulta a pos_transactions (transacciones)
        $pos_transactions_table = $wpdb->prefix . 'pos_transactions';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_transactions_table}'") === $pos_transactions_table) {
            // Obtener columnas de la tabla
            $column_names = $wpdb->get_col("DESC {$pos_transactions_table}", 0);
            
            // Buscar columnas relevantes: método de pago, monto, fecha
            $payment_method_column = null;
            $amount_column = 'amount'; // Default
            $date_column = 'created_at'; // Default
            
            // Buscar columna de método de pago
            foreach ($column_names as $column) {
                $column_lower = strtolower($column);
                if (strpos($column_lower, 'payment_method') !== false || 
                    strpos($column_lower, 'payment') !== false || 
                    strpos($column_lower, 'method') !== false) {
                    $payment_method_column = $column;
                    break;
                }
            }
            
            // Buscar columna de monto
            if (!in_array('amount', $column_names)) {
                foreach ($column_names as $column) {
                    $column_lower = strtolower($column);
                    if (strpos($column_lower, 'amount') !== false || 
                        strpos($column_lower, 'total') !== false || 
                        strpos($column_lower, 'monto') !== false) {
                        $amount_column = $column;
                        break;
                    }
                }
            }
            
            // Buscar columna de fecha
            if (!in_array('created_at', $column_names)) {
                foreach ($column_names as $column) {
                    $column_lower = strtolower($column);
                    if (strpos($column_lower, 'date') !== false || 
                        strpos($column_lower, 'created') !== false || 
                        strpos($column_lower, 'fecha') !== false) {
                        $date_column = $column;
                        break;
                    }
                }
            }
            
            // Si encontramos columna de método de pago, construir consulta
            if ($payment_method_column) {
                $query = "SELECT SUM({$amount_column}) as total FROM {$pos_transactions_table} WHERE ";
                $conditions = [];
                $args = [];
                
                // Condición para método de pago (usando OR para múltiples posibles valores)
                $method_placeholders = array_fill(0, count($method_values), '%s');
                $conditions[] = "{$payment_method_column} IN (" . implode(", ", $method_placeholders) . ")";
                $args = array_merge($args, $method_values);
                
                // Condición para fecha
                $conditions[] = "DATE({$date_column}) = %s";
                $args[] = $date;
                
                // Condición para caja registradora (si existe la columna)
                if (in_array('register_id', $column_names)) {
                    $conditions[] = "register_id = %d";
                    $args[] = $register_id;
                }
                
                // Filtrar por usuario si se especificó
                if ($user_id > 0) {
                    $user_columns = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                    $user_column_found = false;
                    
                    foreach ($user_columns as $user_col) {
                        if (in_array($user_col, $column_names)) {
                            $conditions[] = "{$user_col} = %d";
                            $args[] = $user_id;
                            $user_column_found = true;
                            $this->debug_log("✅ Filtro por usuario aplicado en pos_transactions", [
                                'columna' => $user_col,
                                'user_id' => $user_id
                            ]);
                            break;
                        }
                    }
                    
                    if (!$user_column_found && $user_id > 0) {
                        $this->debug_log("⚠️ ADVERTENCIA: No se encontró columna de usuario en pos_transactions para filtrar");
                    }
                }
                
                // Construir consulta final
                $query .= implode(" AND ", $conditions);
                $prepared_query = $wpdb->prepare($query, $args);
                
                // Ejecutar consulta y obtener resultado
                $this->debug_log("Consulta a pos_transactions para {$payment_method}", ['query' => $prepared_query]);
                $result = $wpdb->get_var($prepared_query);
                $transactions_total = (float)($result ?: 0);
                $total_amount += $transactions_total;
                
                $this->debug_log("Resultado de pos_transactions para {$payment_method}", ['total' => $transactions_total]);
            }
        }
        
        // 3. Consulta a pos_payments (pagos)
        $pos_payments_table = $wpdb->prefix . 'pos_payments';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_payments_table}'") === $pos_payments_table) {
            // Obtener columnas de la tabla
            $column_names = $wpdb->get_col("DESC {$pos_payments_table}", 0);
            
            // Buscar columnas relevantes: método de pago, monto, fecha
            $payment_method_column = null;
            $amount_column = 'amount'; // Default
            $date_column = 'created_at'; // Default
            
            // Buscar columna de método de pago
            foreach ($column_names as $column) {
                $column_lower = strtolower($column);
                if (strpos($column_lower, 'payment_method') !== false || 
                    strpos($column_lower, 'payment') !== false || 
                    strpos($column_lower, 'method') !== false) {
                    $payment_method_column = $column;
                    break;
                }
            }
            
            // Buscar columna de monto
            if (!in_array('amount', $column_names)) {
                foreach ($column_names as $column) {
                    $column_lower = strtolower($column);
                    if (strpos($column_lower, 'amount') !== false || 
                        strpos($column_lower, 'total') !== false || 
                        strpos($column_lower, 'monto') !== false) {
                        $amount_column = $column;
                        break;
                    }
                }
            }
            
            // Buscar columna de fecha
            if (!in_array('created_at', $column_names)) {
                foreach ($column_names as $column) {
                    $column_lower = strtolower($column);
                    if (strpos($column_lower, 'date') !== false || 
                        strpos($column_lower, 'created') !== false || 
                        strpos($column_lower, 'fecha') !== false) {
                        $date_column = $column;
                        break;
                    }
                }
            }
            
            // Si encontramos columna de método de pago, construir consulta
            if ($payment_method_column) {
                $query = "SELECT SUM({$amount_column}) as total FROM {$pos_payments_table} WHERE ";
                $conditions = [];
                $args = [];
                
                // Condición para método de pago (usando OR para múltiples posibles valores)
                $method_placeholders = array_fill(0, count($method_values), '%s');
                $conditions[] = "{$payment_method_column} IN (" . implode(", ", $method_placeholders) . ")";
                $args = array_merge($args, $method_values);
                
                // Condición para fecha
                $conditions[] = "DATE({$date_column}) = %s";
                $args[] = $date;
                
                // Condición para caja registradora (si existe la columna)
                if (in_array('register_id', $column_names)) {
                    $conditions[] = "register_id = %d";
                    $args[] = $register_id;
                }
                
                // Filtrar por usuario si se especificó
                if ($user_id > 0) {
                    $user_columns = ['user_id', 'created_by', 'cashier_id', 'seller_id', 'employee_id'];
                    $user_column_found = false;
                    
                    foreach ($user_columns as $user_col) {
                        if (in_array($user_col, $column_names)) {
                            $conditions[] = "{$user_col} = %d";
                            $args[] = $user_id;
                            $user_column_found = true;
                            $this->debug_log("✅ Filtro por usuario aplicado en pos_payments", [
                                'columna' => $user_col,
                                'user_id' => $user_id
                            ]);
                            break;
                        }
                    }
                    
                    if (!$user_column_found && $user_id > 0) {
                        $this->debug_log("⚠️ ADVERTENCIA: No se encontró columna de usuario en pos_payments para filtrar");
                    }
                }
                
                // Construir consulta final
                $query .= implode(" AND ", $conditions);
                $prepared_query = $wpdb->prepare($query, $args);
                
                // Ejecutar consulta y obtener resultado
                $this->debug_log("Consulta a pos_payments para {$payment_method}", ['query' => $prepared_query]);
                $result = $wpdb->get_var($prepared_query);
                $payments_total = (float)($result ?: 0);
                $total_amount += $payments_total;
                
                $this->debug_log("Resultado de pos_payments para {$payment_method}", ['total' => $payments_total]);
            }
        }
        
        $this->debug_log("✅ Total calculado para método {$payment_method}", ['total_final' => $total_amount]);
        return $total_amount;
    }
    
    /**
     * AJAX: Obtener el desglose de métodos de pago para fecha y caja específica
     * 
     * Endpoint para obtener automáticamente el desglose de métodos de pago cuando cambia la fecha o usuario
     * en el formulario de cierre. Diseñado para trabajar con la funcionalidad de auto-update.
     */
    public function ajax_get_breakdown() {
        // Log completo de la petición
        $this->debug_log('⭐️ INICIO PETICIÓN ajax_get_breakdown ⭐️', [
            'GET' => $_GET,
            'POST' => $_POST,
            'REQUEST' => $_REQUEST,
            'nonce' => isset($_REQUEST['nonce']) ? substr($_REQUEST['nonce'], 0, 5) . '...' : 'no proporcionado'
        ]);
        
        try {
            // Verificar nonce de seguridad
            if (!isset($_REQUEST['nonce'])) {
                $this->debug_log('❌ ERROR: Nonce no enviado en ajax_get_breakdown');
                wp_send_json_error(['message' => 'Nonce de seguridad no proporcionado']);
                return;
            }
            
            if (!wp_verify_nonce($_REQUEST['nonce'], 'wp_pos_closures_nonce')) {
                $this->debug_log('❌ ERROR: Verificación de nonce falló', [
                    'nonce_proporcionado' => substr($_REQUEST['nonce'], 0, 5) . '...'
                ]);
                wp_send_json_error(['message' => 'Nonce de seguridad inválido']);
                return;
            } else {
                $this->debug_log('✅ Verificación de nonce correcta');
            }
            
            // Obtener parámetros
            $date = isset($_REQUEST['date']) ? sanitize_text_field($_REQUEST['date']) : null;
            $register_id = isset($_REQUEST['register_id']) ? intval($_REQUEST['register_id']) : 0;
            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            
            $this->debug_log('📝 Parámetros procesados', [
                'date' => $date,
                'register_id' => $register_id,
                'user_id' => $user_id
            ]);
            
            if (!$date || !$register_id) {
                $this->debug_log('❌ ERROR: Parámetros incompletos en ajax_get_breakdown');
                wp_send_json_error(['message' => 'Fecha o caja registradora no especificadas']);
                return;
            }
            
            // Verificar si es una fecha válida
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $this->debug_log('❌ ERROR: Formato de fecha inválido', ['date' => $date]);
                wp_send_json_error(['message' => 'Formato de fecha inválido']);
                return;
            }
            
            $this->debug_log('🔍 Calculando desglose de métodos de pago', [
                'date' => $date,
                'register_id' => $register_id,
                'user_id' => $user_id,
                'user_filtering_enabled' => ($user_id > 0)
            ]);
            
            // Calcular cada método de pago con captura de errores individual
            $payment_methods = [];
            $methods_to_calculate = ['cash', 'card', 'transfer', 'check', 'other'];
            
            foreach ($methods_to_calculate as $method) {
                try {
                    $payment_methods[$method] = $this->calculate_payment_method_amount($method, $register_id, $user_id, $date);
                    $this->debug_log("✅ Método {$method} calculado: " . $payment_methods[$method]);
                } catch (Exception $e) {
                    $this->debug_log("❌ Error al calcular método {$method}: " . $e->getMessage());
                    $payment_methods[$method] = 0; // Valor por defecto en caso de error
                }
            }
            
            // Preparar respuesta
            $response = [
                'breakdown' => $payment_methods,
                'date' => $date,
                'register_id' => $register_id,
                'user_id' => $user_id
            ];
            
            $this->debug_log('✅ Desglose de métodos de pago calculado con éxito', $response);
            
            // Enviar respuesta
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            $this->debug_log('❌ ERROR GENERAL en ajax_get_breakdown: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            wp_send_json_error(['message' => 'Error al calcular el desglose: ' . $e->getMessage()]);
        }
    }
}
