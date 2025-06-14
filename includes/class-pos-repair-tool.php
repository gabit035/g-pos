<?php
/**
 * Herramienta de reparación para G-POS
 */
class POS_Repair_Tool {

    /**
     * Inicializar la herramienta
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'handle_repair_actions']);
    }

    /**
     * Añadir menú de administración
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'pos-reports',
            'Herramientas de Reparación',
            'Reparar Informes',
            'manage_options',
            'pos-repair-tool',
            [__CLASS__, 'render_repair_tool']
        );
    }

    /**
     * Manejar acciones de reparación
     */
    public static function handle_repair_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'pos-repair-tool' || !isset($_GET['action'])) {
            return;
        }

        check_admin_referer('pos_repair_action');

        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes para realizar esta acción.');
        }

        $action = sanitize_text_field($_GET['action']);
        $result = [];

        switch ($action) {
            case 'fix_dates':
                $result = self::fix_invalid_dates();
                break;
            case 'create_customers_table':
                $result = self::create_customers_table();
                break;
            case 'fix_payment_methods':
                $result = self::fix_payment_methods();
                break;
            case 'fix_all':
                $result = array_merge(
                    self::create_customers_table(),
                    self::fix_invalid_dates(),
                    self::fix_payment_methods()
                );
                break;
        }

        set_transient('pos_repair_result', $result, 60);
        wp_redirect(admin_url('admin.php?page=pos-repair-tool'));
        exit;
    }

    /**
     * Reparar fechas inválidas
     */
    private static function fix_invalid_dates() {
        global $wpdb;
        $result = [];

        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}pos_sales 
                SET date = %s 
                WHERE date = %s OR date IS NULL",
                current_time('mysql'),
                '0000-00-00 00:00:00'
            )
        );

        if ($updated !== false) {
            $result[] = [
                'type' => 'success',
                'message' => sprintf('Se actualizaron %d registros con fechas inválidas.', $updated)
            ];
        } else {
            $result[] = [
                'type' => 'error',
                'message' => 'Error al actualizar fechas: ' . $wpdb->last_error
            ];
        }

        return $result;
    }

    /**
     * Crear tabla de clientes si no existe
     */
    private static function create_customers_table() {
        global $wpdb;
        $result = [];

        $table_name = $wpdb->prefix . 'pos_customers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            address text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY email (email)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        if (empty($wpdb->last_error)) {
            $result[] = [
                'type' => 'success',
                'message' => 'Tabla de clientes verificada correctamente.'
            ];
        } else {
            $result[] = [
                'type' => 'error',
                'message' => 'Error al crear la tabla de clientes: ' . $wpdb->last_error
            ];
        }

        return $result;
    }

    /**
     * Reparar métodos de pago
     */
    private static function fix_payment_methods() {
        global $wpdb;
        $result = [];

        // Verificar si hay métodos de pago
        $payment_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pos_payments");
        
        if ($payment_count == 0) {
            $inserted = $wpdb->insert(
                "{$wpdb->prefix}pos_payments",
                [
                    'name' => 'Efectivo',
                    'description' => 'Pago en efectivo',
                    'is_active' => 1,
                    'created_at' => current_time('mysql')
                ]
            );

            if ($inserted) {
                $result[] = [
                    'type' => 'success',
                    'message' => 'Se creó el método de pago por defecto (Efectivo).'
                ];
            } else {
                $result[] = [
                    'type' => 'error',
                    'message' => 'Error al crear método de pago: ' . $wpdb->last_error
                ];
                return $result;
            }
        }

        // Actualizar ventas sin método de pago
        $default_method = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}pos_payments LIMIT 1");
        
        if ($default_method) {
            $updated = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}pos_sales 
                    SET payment_method = %s 
                    WHERE payment_method IS NULL OR payment_method = ''",
                    $default_method
                )
            );

            if ($updated !== false) {
                $result[] = [
                    'type' => 'success',
                    'message' => sprintf('Se actualizaron %d ventas sin método de pago.', $updated)
                ];
            } else {
                $result[] = [
                    'type' => 'error',
                    'message' => 'Error al actualizar métodos de pago: ' . $wpdb->last_error
                ];
            }
        }

        return $result;
    }

    /**
     * Mostrar la herramienta de reparación
     */
    public static function render_repair_tool() {
        $results = get_transient('pos_repair_result');
        delete_transient('pos_repair_result');
        
        // Obtener información actual
        global $wpdb;
        $sales_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pos_sales");
        $customers_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pos_customers");
        $payments_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pos_payments");
        $invalid_dates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pos_sales WHERE date = '0000-00-00 00:00:00' OR date IS NULL");
        $missing_payments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pos_sales WHERE payment_method IS NULL OR payment_method = ''");
        ?>
        <div class="wrap">
            <h1>Herramienta de Reparación G-POS</h1>
            
            <?php if ($results) : ?>
                <div class="notice notice-<?php echo $results[0]['type']; ?> is-dismissible">
                    <p><strong>Resultados:</strong></p>
                    <ul>
                        <?php foreach ($results as $result) : ?>
                            <li><?php echo esc_html($result['message']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>Estado Actual</h2>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <th>Total de ventas</th>
                            <td><?php echo number_format($sales_count); ?></td>
                        </tr>
                        <tr>
                            <th>Clientes registrados</th>
                            <td><?php echo number_format($customers_count); ?></td>
                        </tr>
                        <tr>
                            <th>Métodos de pago</th>
                            <td><?php echo number_format($payments_count); ?></td>
                        </tr>
                        <tr>
                            <th>Ventas con fechas inválidas</th>
                            <td class="<?php echo $invalid_dates > 0 ? 'warning' : 'success'; ?>">
                                <?php echo number_format($invalid_dates); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Ventas sin método de pago</th>
                            <td class="<?php echo $missing_payments > 0 ? 'warning' : 'success'; ?>">
                                <?php echo number_format($missing_payments); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card" style="margin-top: 20px;">
                <h2>Acciones de Reparación</h2>
                <div class="repair-actions" style="display: flex; gap: 10px; margin-top: 15px;">
                    <form method="post" action="admin-post.php" style="display: inline;">
                        <input type="hidden" name="action" value="pos_repair_action">
                        <?php wp_nonce_field('pos_repair_action'); ?>
                        <button type="submit" name="repair_action" value="fix_all" class="button button-primary">
                            Reparar Todo Automáticamente
                        </button>
                    </form>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=pos-repair-tool&action=fix_dates'), 'pos_repair_action'); ?>" class="button">
                        Reparar Fechas Inválidas
                    </a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=pos-repair-tool&action=create_customers_table'), 'pos_repair_action'); ?>" class="button">
                        Crear Tabla de Clientes
                    </a>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=pos-repair-tool&action=fix_payment_methods'), 'pos_repair_action'); ?>" class="button">
                        Reparar Métodos de Pago
                    </a>
                </div>
                <p class="description" style="margin-top: 10px;">
                    <strong>Nota:</strong> Se recomienda hacer una copia de seguridad de la base de datos antes de realizar cambios.
                </p>
            </div>

            <style>
                .success { color: #46b450; font-weight: bold; }
                .warning { color: #ffb900; font-weight: bold; }
                .error { color: #dc3232; font-weight: bold; }
                .card {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                    padding: 15px 20px;
                    margin-bottom: 15px;
                }
                .repair-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                }
                .repair-actions .button {
                    margin-right: 5px;
                    margin-bottom: 5px;
                }
            </style>
        </div>
        <?php
    }
}

// Inicializar la herramienta
add_action('plugins_loaded', ['POS_Repair_Tool', 'init']);
