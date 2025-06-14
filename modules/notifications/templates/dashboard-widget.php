<?php
/**
 * Plantilla para el widget de notificaciones en el dashboard
 *
 * @package WP-POS
 * @subpackage Notifications
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener notificaciones importantes
$stock_notifications = wp_pos_get_notifications(WP_POS_NOTIFICATION_CONTEXT_STOCK);
$warning_notifications = array_filter($stock_notifications, function($notification) {
    return $notification['type'] === WP_POS_NOTIFICATION_WARNING || $notification['type'] === WP_POS_NOTIFICATION_ERROR;
});

$total_notifications = count($warning_notifications);
?>

<div class="wp-pos-dashboard-widget">
    <div class="wp-pos-dashboard-widget-header" style="display: flex; align-items: center; margin-bottom: 15px;">
        <span class="dashicons dashicons-bell" style="color: #6c5ce7; font-size: 24px; width: 24px; height: 24px; margin-right: 10px;"></span>
        <h3 style="margin: 0; color: #2c3e50;"><?php _e('Estado de Inventario', 'wp-pos'); ?></h3>
    </div>
    
    <?php if ($total_notifications > 0) : ?>
        <div class="wp-pos-dashboard-widget-content">
            <?php foreach ($warning_notifications as $notification) : ?>
                <div class="wp-pos-dashboard-notification wp-pos-notification-<?php echo esc_attr($notification['type']); ?>" style="background-color: <?php echo $notification['type'] === WP_POS_NOTIFICATION_ERROR ? '#feebe9' : '#fef7e9'; ?>; border-left: 4px solid <?php echo $notification['type'] === WP_POS_NOTIFICATION_ERROR ? '#e74c3c' : '#f39c12'; ?>; padding: 12px; margin-bottom: 10px; border-radius: 4px;">
                    <div style="display: flex; align-items: flex-start;">
                        <div style="margin-right: 10px;">
                            <span class="dashicons <?php echo esc_attr($notification['icon']); ?>" style="color: <?php echo $notification['type'] === WP_POS_NOTIFICATION_ERROR ? '#e74c3c' : '#f39c12'; ?>;"></span>
                        </div>
                        <div>
                            <?php if (!empty($notification['title'])) : ?>
                                <strong><?php echo esc_html($notification['title']); ?></strong><br>
                            <?php endif; ?>
                            <?php echo wp_kses_post($notification['message']); ?>
                            
                            <?php if (isset($notification['metadata']['product_id'])) : ?>
                                <div style="margin-top: 8px;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-products&action=edit&id=' . $notification['metadata']['product_id'])); ?>" class="button button-small" style="background: #6c5ce7; color: white; border-color: #5549c6;">
                                        <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span> 
                                        <?php _e('Editar Producto', 'wp-pos'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div style="margin-top: 15px; text-align: right;">
                <a href="<?php echo admin_url('admin.php?page=wp-pos-notifications&view=stock'); ?>" class="button" style="background: #6c5ce7; color: white; border-color: #5549c6;">
                    <?php _e('Ver Todas las Notificaciones', 'wp-pos'); ?>
                </a>
            </div>
        </div>
    <?php else : ?>
        <div class="wp-pos-dashboard-widget-content" style="text-align: center; padding: 20px 10px;">
            <span class="dashicons dashicons-yes-alt" style="color: #2ecc71; font-size: 48px; width: 48px; height: 48px; margin-bottom: 10px;"></span>
            <p><?php _e('Todo en orden. No hay problemas de inventario.', 'wp-pos'); ?></p>
        </div>
    <?php endif; ?>
</div>
