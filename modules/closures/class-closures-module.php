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
    }
    
    /**
     * {@inheritDoc}
     */
    public function initialize() {
        // Registrar acciones AJAX
        add_action('wp_ajax_wp_pos_closures_get_registers', array($this, 'ajax_get_registers'));
        add_action('wp_ajax_wp_pos_closures_add_register', array($this, 'ajax_add_register'));
        add_action('wp_ajax_wp_pos_closures_update_register', array($this, 'ajax_update_register'));
        add_action('wp_ajax_wp_pos_closures_delete_register', array($this, 'ajax_delete_register'));
        add_action('wp_ajax_wp_pos_closures_get_closures', array($this, 'ajax_get_closures'));
        add_action('wp_ajax_wp_pos_closures_save_closure', array($this, 'ajax_save_closure'));
        add_action('wp_ajax_wp_pos_closures_calculate_amounts', array($this, 'ajax_calculate_amounts'));
        add_action('wp_ajax_wp_pos_closures_diagnostic', array($this, 'ajax_diagnostic'));
        add_action('wp_ajax_wp_pos_closures_get_closure_details', array($this, 'ajax_get_closure_details'));
        add_action('wp_ajax_wp_pos_closures_update_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_wp_pos_closures_delete_closure', array($this, 'ajax_delete_closure'));
        add_action('wp_ajax_wp_pos_closures_dashboard_data', array($this, 'ajax_dashboard_data'));
        add_action('wp_ajax_wp_pos_closures_get_status_history', array($this, 'ajax_get_status_history'));
        
        // Inicializar base de datos si es necesario
        add_action('admin_init', array($this, 'ensure_tables_exist'));
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
        ) $charset_collate";
        
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
        $initial_amount = (float) $_POST['initial_amount'];
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
        
        // Rango completo del du00eda
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
            WHERE DATE(created_at) = %s 
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
            'status' => 'pending',  // El cierre estu00e1 pendiente hasta que sea aprobado
            'created_at' => $current_time
        ];
        
        // Formatos para insertar
        $formats = ['%d', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s'];
        
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
        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        $date = isset($_REQUEST['date']) ? sanitize_text_field($_REQUEST['date']) : date('Y-m-d');
        
        if ($register_id <= 0) {
            wp_send_json_error(['message' => __('Selecciona una caja registradora válida.', 'wp-pos')]);
        }
        
        // Consulta para calcular montos basados en transacciones
        global $wpdb;
        
        // Obtener el último cierre para esta caja para determinar el monto inicial
        $last_closure = $wpdb->get_row($wpdb->prepare(
            "SELECT actual_amount FROM {$wpdb->prefix}pos_closures 
            WHERE register_id = %d 
            ORDER BY created_at DESC LIMIT 1",
            $register_id
        ));
        
        // Monto inicial basado en el último cierre o cero si no hay cierres previos
        $initial_amount = $last_closure ? (float)$last_closure->actual_amount : 0;
        
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
            foreach ($columns as $column) {
                if (strpos(strtolower($column->Field), 'date') !== false || 
                    strpos(strtolower($column->Field), 'created') !== false || 
                    strpos(strtolower($column->Field), 'time') !== false) {
                    $date_column = $column->Field;
                    break;
                }
            }
            
            // Determinar si existe columna 'total'
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            $total_column = in_array('total', $column_names) ? 'total' : (in_array('amount', $column_names) ? 'amount' : 'total');
            
            // Consulta para obtener el total de ventas usando las columnas detectadas
            $sales_query = "SELECT SUM({$total_column}) as total_sales FROM {$pos_sales_table} WHERE DATE({$date_column}) = %s";
            $sales_args = [$date];
            
            // Si se especifica un usuario y existe columna 'created_by' o 'user_id'
            if ($user_id > 0) {
                $user_column = in_array('created_by', $column_names) ? 'created_by' : (in_array('user_id', $column_names) ? 'user_id' : '');
                if ($user_column) {
                    $sales_query .= " AND {$user_column} = %d";
                    $sales_args[] = $user_id;
                }
            }
            
            $prepared_sales_query = $wpdb->prepare($sales_query, $sales_args);
            $sales_result = $wpdb->get_var($prepared_sales_query);
            $sales_total = (float)($sales_result ?: 0);
            
            $debug_info['sales_query'] = $prepared_sales_query;
            $debug_info['sales_total'] = $sales_total;
            $debug_info['date_column_used'] = $date_column;
            $debug_info['total_column_used'] = $total_column;
        }
        
        // 2. Verificar transacciones en la tabla pos_transactions si existe
        $transactions_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$pos_transactions_table}'") === $pos_transactions_table;
        if ($transactions_table_exists) {
            // Consulta para calcular ventas y otros movimientos
            $trans_query = "SELECT SUM(amount) as total_amount FROM {$pos_transactions_table} WHERE register_id = %d";
            $trans_args = [$register_id];
            
            // Si se especifica un usuario, filtrar por ese usuario
            if ($user_id > 0) {
                $trans_query .= " AND user_id = %d";
                $trans_args[] = $user_id;
            }
            
            // Filtrar por fecha
            $trans_query .= " AND DATE(created_at) = %s";
            $trans_args[] = $date;
            
            $prepared_trans_query = $wpdb->prepare($trans_query, $trans_args);
            $trans_result = $wpdb->get_var($prepared_trans_query);
            $transactions_total = (float)($trans_result ?: 0);
            
            $debug_info['transactions_query'] = $prepared_trans_query;
            $debug_info['transactions_total'] = $transactions_total;
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
        
        // Determinar el total final (usando el mayor valor entre los métodos)
        $total_amount = max($sales_total, $transactions_total, $wc_total);
        
        // Si no hay ventas pero sí hay transacciones, usar las transacciones
        if ($total_amount == 0 && ($transactions_total > 0 || $sales_total > 0 || $wc_total > 0)) {
            $total_amount = $transactions_total + $sales_total + $wc_total;
        }
        
        // Calcular el total esperado (inicial + transacciones)
        $expected_amount = $initial_amount + $total_amount;
        
        // Agregar información de depuración para ayudar a resolver el problema
        $debug_info['date_requested'] = $date;
        $debug_info['register_id'] = $register_id;
        $debug_info['user_id'] = $user_id;
        $debug_info['tables_checked'] = [
            'pos_sales' => $sales_table_exists,
            'pos_transactions' => $transactions_table_exists,
            'wc_orders' => $wc_table_exists
        ];
        $debug_info['final_amount_used'] = $total_amount;
        
        // Asegurar que los valores sean cero si no hay datos
        $initial_amount = $initial_amount ?: 0;
        $total_amount = $total_amount ?: 0;
        $expected_amount = $expected_amount ?: 0;
        
        $this->debug_log("Calculando montos para el cierre: ", [
            'fecha' => $date,
            'registro' => $register_id,
            'usuario' => $user_id,
            'monto_inicial' => $initial_amount,
            'transacciones' => $total_amount,
            'esperado' => $expected_amount
        ]);
        
        // Retornar los datos calculados
        wp_send_json_success([
            'initial_amount' => number_format($initial_amount, 2, '.', ''),
            'total_transactions' => number_format($total_amount, 2, '.', ''),
            'expected_amount' => number_format($expected_amount, 2, '.', ''),
            // Au00f1adimos informaciu00f3n bu00e1sica de debug para diagnosticar el problema
            'debug_info' => [
                'sales_total' => $sales_total,
                'transactions_total' => $transactions_total,
                'wc_total' => $wc_total,
                'final_total' => $total_amount,
                'date' => $date
            ]
        ]);
    }

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
            foreach ($columns as $column) {
                if (strpos(strtolower($column->Field), 'date') !== false || 
                    strpos(strtolower($column->Field), 'created') !== false || 
                    strpos(strtolower($column->Field), 'time') !== false) {
                    $date_column = $column->Field;
                    break;
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
        
        // Obtener paru00e1metros
        $closure_id = isset($_POST['closure_id']) ? intval($_POST['closure_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $justification = isset($_POST['justification']) ? sanitize_textarea_field($_POST['justification']) : '';
        
        // Validar paru00e1metros
        if ($closure_id <= 0) {
            wp_send_json_error(['message' => __('ID de cierre invu00e1lido.', 'wp-pos')]);
            return;
        }
        
        if (!in_array($status, ['approved', 'rejected'])) {
            wp_send_json_error(['message' => __('Estado invu00e1lido.', 'wp-pos')]);
            return;
        }
        
        // Si es rechazo, verificar que haya justificaciu00f3n
        if ($status === 'rejected' && empty($justification)) {
            wp_send_json_error(['message' => __('Debe proporcionar una justificaciu00f3n para rechazar el cierre.', 'wp-pos')]);
            return;
        }
        
        global $wpdb;
        
        // Obtener datos actuales del cierre
        $closure = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pos_closures WHERE id = %d",
            $closure_id
        ));
        
        if (!$closure) {
            wp_send_json_error(['message' => __('No se encontru00f3 el cierre especificado.', 'wp-pos')]);
            return;
        }
        
        // Guardar el estado anterior
        $old_status = $closure->status;
        
        // Si ya tenu00eda el mismo estado, no hacer nada
        if ($old_status === $status) {
            wp_send_json_success([
                'message' => __('El cierre ya tenu00eda este estado.', 'wp-pos'),
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
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta accioi4n.', 'wp-pos')]);
            return;
        }
        
        // Obtener paroi4metros
        $closure_id = isset($_POST['closure_id']) ? intval($_POST['closure_id']) : 0;
        
        if ($closure_id <= 0) {
            wp_send_json_error(['message' => __('ID de cierre invoi4lido.', 'wp-pos')]);
            return;
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
                 WHERE DATE(created_at) = DATE(DATE_SUB(%s, INTERVAL 1 DAY))
                ) as prev_day_amount
            FROM {$wpdb->prefix}pos_sales 
            WHERE DATE(created_at) = %s",
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
