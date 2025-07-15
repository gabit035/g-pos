<?php
/**
 * Plantilla para añadir/editar Servicio
 *
 * @package WP-POS
 * @subpackage Services
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'pos_services';

$service_id = isset($_GET['service_id']) ? absint($_GET['service_id']) : 0;
$edit_mode = $service_id > 0;
$title = $edit_mode ? __('Editar Servicio', 'wp-pos') : __('Añadir Nuevo Servicio', 'wp-pos');

$service = array(
    'id' => 0,
    'name' => '',
    'description' => '',
    'purchase_price' => '',
    'sale_price' => '',
);

if ($edit_mode) {
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $service_id), ARRAY_A);
    if ($row) {
        $service = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wp_pos_save_service')) {
        echo '<div class="notice notice-error"><p>' . __('Error de seguridad.', 'wp-pos') . '</p></div>';
    } else {
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'purchase_price' => floatval($_POST['purchase_price']),
            'sale_price' => floatval($_POST['sale_price']),
        );
        if ($edit_mode) {
            $result = $wpdb->update($table, $data, array('id' => $service_id));
            $success = ($result !== false);
        } else {
            $result = $wpdb->insert($table, $data);
            $success = ($result !== false);
            if ($success) $service_id = $wpdb->insert_id;
        }
        if ($success) {
            $redirect = admin_url('admin.php?page=wp-pos-services&service_saved=1');
            echo "<script>window.location.href='" . esc_url_raw($redirect) . "';</script>";
            exit;
        } else {
            echo '<div class="notice notice-error"><p>' . __('Error al guardar servicio.', 'wp-pos') . '</p></div>';
        }
    }
}
?>
<div class="wrap wp-pos-products-wrapper">
    <div class="wp-pos-products-header">
        <div class="wp-pos-products-header-primary">
            <h1><?php echo esc_html($title); ?></h1>
            <?php if ($edit_mode) : ?>
                <p><?php esc_html_e('Edita un servicio existente.', 'wp-pos'); ?></p>
            <?php else : ?>
                <p><?php esc_html_e('Completa los campos para añadir un nuevo servicio.', 'wp-pos'); ?></p>
            <?php endif; ?>
        </div>
        <div class="wp-pos-return-link">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-services')); ?>" class="wp-pos-view-all-button">
                <span class="wp-pos-view-all-icon"><span class="dashicons dashicons-arrow-left-alt"></span></span>
                <span class="wp-pos-view-all-text"><?php esc_html_e('Volver a Servicios', 'wp-pos'); ?></span>
            </a>
        </div>
    </div>
    <div class="wp-pos-container">
        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=wp-pos-services&action=' . ($edit_mode ? 'edit&service_id=' . $service_id : 'add'))); ?>">
            <?php wp_nonce_field('wp_pos_save_service'); ?>
            <input type="hidden" name="service_id" value="<?php echo esc_attr($service_id); ?>">
            <div id="poststuff" class="wp-pos-metabox-holder">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="postbox wp-pos-dashboard-card">
                            <div class="wp-pos-dashboard-card-header">
                                <h2 class="wp-pos-dashboard-card-title"><span class="dashicons dashicons-info"></span> <?php esc_html_e('Información Básica', 'wp-pos'); ?></h2>
                            </div>
                            <div class="inside wp-pos-dashboard-card-body">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="name"><?php esc_html_e('Nombre', 'wp-pos'); ?><span class="required">*</span></label></th>
                                        <td><input type="text" name="name" id="name" value="<?php echo esc_attr($service['name']); ?>" class="regular-text" required></td>
                                    </tr>
                                    <tr>
                                        <th><label for="description"><?php esc_html_e('Descripción', 'wp-pos'); ?></label></th>
                                        <td><textarea name="description" id="description" rows="5" class="large-text"><?php echo esc_textarea($service['description']); ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <th><label for="purchase_price"><?php esc_html_e('Precio de compra', 'wp-pos'); ?><span class="required">*</span></label></th>
                                        <td><div class="wp-pos-price-field"><span class="currency-symbol">$</span><input type="number" step="0.01" name="purchase_price" id="purchase_price" value="<?php echo esc_attr($service['purchase_price']); ?>" required></div></td>
                                    </tr>
                                    <tr>
                                        <th><label for="sale_price"><?php esc_html_e('Precio de venta', 'wp-pos'); ?><span class="required">*</span></label></th>
                                        <td><div class="wp-pos-price-field"><span class="currency-symbol">$</span><input type="number" step="0.01" name="sale_price" id="sale_price" value="<?php echo esc_attr($service['sale_price']); ?>" required></div></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox wp-pos-dashboard-card products">
                            <div class="wp-pos-dashboard-card-header">
                                <h2 class="wp-pos-dashboard-card-title"><span class="dashicons dashicons-saved"></span> <?php esc_html_e('Publicar', 'wp-pos'); ?></h2>
                            </div>
                            <div class="inside wp-pos-dashboard-card-body">
                                <div id="submitpost" class="submitbox">
                                    <div class="misc-publishing-actions">
                                        <div class="misc-pub-section">
                                            <span class="dashicons dashicons-yes"></span> <?php echo $edit_mode ? esc_html__('Actualizar Servicio','wp-pos') : esc_html__('Publicar Servicio','wp-pos'); ?>
                                        </div>
                                    </div>
                                    <div id="major-publishing-actions">
                                        <div id="publishing-action">
                                            <div class="submit-box">
                                                <button type="submit" name="submit_service" id="submit-service" class="wp-pos-action-button primary" title="<?php echo esc_attr($edit_mode ? esc_html__('Actualizar Servicio','wp-pos') : esc_html__('Publicar Servicio','wp-pos')); ?>">
                                                    <span class="dashicons dashicons-saved"></span>
                                                    <?php echo $edit_mode ? esc_html__('Actualizar Servicio','wp-pos') : esc_html__('Publicar Servicio','wp-pos'); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Submit handled in sidebar Publish box -->
        </form>
    </div>
</div>
