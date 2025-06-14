<?php
/**
 * Plantilla para la página de notificaciones
 *
 * @package WP-POS
 * @subpackage Notifications
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener todas las notificaciones
$notifications = wp_pos_get_notifications();
$notification_count = count($notifications);

// Obtener notificaciones por contexto
$stock_notifications = wp_pos_get_notifications(WP_POS_NOTIFICATION_CONTEXT_STOCK);
$sales_notifications = wp_pos_get_notifications(WP_POS_NOTIFICATION_CONTEXT_SALES);
$products_notifications = wp_pos_get_notifications(WP_POS_NOTIFICATION_CONTEXT_PRODUCTS);
$global_notifications = wp_pos_get_notifications(WP_POS_NOTIFICATION_CONTEXT_GLOBAL);

// Tipo de visualización (todas o por contexto)
$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'all';

// Filtrar notificaciones según selección
$filtered_notifications = array();
switch ($view_mode) {
    case 'stock':
        $filtered_notifications = $stock_notifications;
        break;
        
    case 'sales':
        $filtered_notifications = $sales_notifications;
        break;
        
    case 'products':
        $filtered_notifications = $products_notifications;
        break;
        
    case 'global':
        $filtered_notifications = $global_notifications;
        break;
        
    case 'all':
    default:
        $filtered_notifications = $notifications;
        break;
}

// Cargar header del plugin
wp_pos_template_header(array(
    'title' => __('Notificaciones', 'wp-pos'),
    'active_menu' => 'notifications'
));

// Encolar los estilos generales y los estilos mejorados para notificaciones
wp_enqueue_style('wp-pos-admin');
wp_enqueue_style('wp-pos-notifications-enhanced', WP_POS_PLUGIN_URL . 'modules/notifications/assets/css/notifications-enhanced.css', array(), WP_POS_VERSION);
?>

<div class="wrap wp-pos-notifications-wrapper">
    <div class="wp-pos-notifications-header">
        <div class="wp-pos-notifications-header-primary">
            <h1><?php _e('Notificaciones', 'wp-pos'); ?></h1>
            <p><?php _e('Gestiona las notificaciones del sistema', 'wp-pos'); ?></p>
        </div>
        <div class="wp-pos-control-panel-secondary">
            <a href="<?php echo admin_url('admin.php?page=wp-pos'); ?>" class="wp-pos-back-button">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Volver al Dashboard', 'wp-pos'); ?>
            </a>
        </div>
    </div>
        
    <!-- Contenedor de filtros y notificaciones -->
    <div class="wp-pos-notifications-content">
        
        <!-- Filtros y conteo -->
        <div class="wp-pos-filter-container">
            <div class="wp-pos-notification-counter">
                <?php if ($notification_count > 0) : ?>
                    <span class="dashicons dashicons-bell"></span>
                    <?php printf(_n('%d Notificación', '%d Notificaciones', $notification_count, 'wp-pos'), $notification_count); ?>
                <?php else : ?>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('No hay notificaciones', 'wp-pos'); ?>
                <?php endif; ?>
            </div>
            
            <div class="wp-pos-tabs-container">
                <a href="<?php echo add_query_arg('view', 'all'); ?>" class="wp-pos-tab <?php echo $view_mode === 'all' ? 'active' : ''; ?>">
                    <?php _e('Todas', 'wp-pos'); ?>
                    <?php if ($notification_count > 0) : ?>
                        <span class="wp-pos-tab-badge"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo add_query_arg('view', 'stock'); ?>" class="wp-pos-tab <?php echo $view_mode === 'stock' ? 'active' : ''; ?>">
                    <?php _e('Stock', 'wp-pos'); ?>
                    <?php if (count($stock_notifications) > 0) : ?>
                        <span class="wp-pos-tab-badge"><?php echo count($stock_notifications); ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo add_query_arg('view', 'sales'); ?>" class="wp-pos-tab <?php echo $view_mode === 'sales' ? 'active' : ''; ?>">
                    <?php _e('Ventas', 'wp-pos'); ?>
                    <?php if (count($sales_notifications) > 0) : ?>
                        <span class="wp-pos-tab-badge"><?php echo count($sales_notifications); ?></span>
                    <?php endif; ?>
                </a>
                    <a href="<?php echo add_query_arg('view', 'products'); ?>" class="wp-pos-tab <?php echo $view_mode === 'products' ? 'active' : ''; ?>">
                    <?php _e('Productos', 'wp-pos'); ?>
                    <?php if (count($products_notifications) > 0) : ?>
                        <span class="wp-pos-tab-badge"><?php echo count($products_notifications); ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo add_query_arg('view', 'global'); ?>" class="wp-pos-tab <?php echo $view_mode === 'global' ? 'active' : ''; ?>">
                    <?php _e('Sistema', 'wp-pos'); ?>
                    <?php if (count($global_notifications) > 0) : ?>
                        <span class="wp-pos-tab-badge"><?php echo count($global_notifications); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
            
        <!-- Listado de notificaciones -->
        <div class="wp-pos-notifications-card">
            <div class="wp-pos-notifications-card-header">
                <h3><span class="dashicons dashicons-list-view"></span> <?php _e('Listado de notificaciones', 'wp-pos'); ?></h3>
            </div>
            <div class="wp-pos-notifications-card-body">
                <?php if (!empty($filtered_notifications)) : ?>
                    <div class="wp-pos-notification-list">
            
                    <?php foreach ($filtered_notifications as $notification) : ?>
                        <div class="wp-pos-list-item wp-pos-notification-<?php echo esc_attr($notification['type']); ?>">
                            <div class="wp-pos-list-item-icon">
                                <i class="dashicons <?php echo esc_attr($notification['icon']); ?>"></i>
                            </div>
                            <div class="wp-pos-list-item-content">
                                <?php if (!empty($notification['title'])) : ?>
                                    <div class="wp-pos-list-item-title"><?php echo esc_html($notification['title']); ?></div>
                                <?php endif; ?>
                                
                                <div class="wp-pos-list-item-description">
                                    <?php echo wp_kses_post($notification['message']); ?>
                                </div>
                                
                                <div class="wp-pos-list-item-meta">
                                    <?php echo esc_html(sprintf(__('Generada el %s', 'wp-pos'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $notification['timestamp']))); ?>
                                    
                                    <?php if (isset($notification['metadata']['product_id'])) : ?>
                                        | <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-products&action=edit&id=' . $notification['metadata']['product_id'])); ?>">
                                            <?php _e('Ver producto', 'wp-pos'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($notification['dismissible']) : ?>
                                <div class="wp-pos-list-item-actions">
                                    <button type="button" class="wp-pos-button wp-pos-button-small wp-pos-button-outline wp-pos-notification-dismiss" data-notification-id="<?php echo esc_attr($notification['id']); ?>">
                                        <i class="dashicons dashicons-no-alt"></i>
                                        <?php _e('Descartar', 'wp-pos'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else : ?>
                <div class="wp-pos-empty-state">
                    <div class="wp-pos-empty-state-icon">
                        <i class="dashicons dashicons-yes-alt"></i>
                    </div>
                    <h3 class="wp-pos-empty-state-title"><?php _e('¡Todo en orden!', 'wp-pos'); ?></h3>
                    <p class="wp-pos-empty-state-description"><?php _e('No hay notificaciones pendientes en esta categoría.', 'wp-pos'); ?></p>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Manejar descartar notificaciones de la lista
    $('.wp-pos-notification-dismiss').on('click', function() {
        var btn = $(this);
        var notificationId = btn.data('notification-id');
        var notificationItem = btn.closest('.wp-pos-list-item');
        
        // Petición AJAX para descartar la notificación
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wp_pos_dismiss_notification',
                notification_id: notificationId,
                nonce: '<?php echo wp_create_nonce("wp_pos_dismiss_notification"); ?>'
            },
            beforeSend: function() {
                btn.prop('disabled', true);
                btn.addClass('wp-pos-button-loading');
            },
            success: function(response) {
                if (response.success) {
                    // Animación de desaparición
                    notificationItem.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Reducir contadores
                        var currentViewMode = '<?php echo $view_mode; ?>';
                        var allCount = parseInt($('.wp-pos-tabs-container a:first-child .wp-pos-tab-badge').text()) - 1;
                        var viewCount = parseInt($('.wp-pos-tabs-container a.active .wp-pos-tab-badge').text()) - 1;
                        
                        // Actualizar contador general y contador específico
                        if (allCount > 0) {
                            $('.wp-pos-tabs-container a:first-child .wp-pos-tab-badge').text(allCount);
                        } else {
                            $('.wp-pos-tabs-container a:first-child .wp-pos-tab-badge').remove();
                        }
                        
                        if (currentViewMode !== 'all') {
                            if (viewCount > 0) {
                                $('.wp-pos-tabs-container a.active .wp-pos-tab-badge').text(viewCount);
                            } else {
                                $('.wp-pos-tabs-container a.active .wp-pos-tab-badge').remove();
                            }
                        }
                        
                        // Si no quedan notificaciones en la vista actual, mostrar el panel vacío
                        if ($('.wp-pos-list-item').length === 0) {
                            $('.wp-pos-notifications-card-body').html(
                                '<div class="wp-pos-empty-state">'+
                                    '<div class="wp-pos-empty-state-icon">'+
                                        '<i class="dashicons dashicons-yes-alt"></i>'+
                                    '</div>'+
                                    '<h3 class="wp-pos-empty-state-title"><?php _e("¡Todo en orden!", "wp-pos"); ?></h3>'+
                                    '<p class="wp-pos-empty-state-description"><?php _e("No hay notificaciones pendientes en esta categoría.", "wp-pos"); ?></p>'+
                                '</div>'
                            );
                            
                            // Actualizar también el contador principal
                            if (allCount === 0) {
                                $('.wp-pos-notification-counter').html(
                                    '<span class="dashicons dashicons-yes-alt"></span> <?php _e("No hay notificaciones", "wp-pos"); ?>'
                                );
                            }
                        }
                    });
                } else {
                    alert('Error: ' + response.data);
                    btn.prop('disabled', false);
                    btn.removeClass('wp-pos-button-loading');
                }
            },
            error: function() {
                alert('<?php _e("Error al procesar la solicitud", "wp-pos"); ?>');
                btn.prop('disabled', false);
                btn.removeClass('wp-pos-button-loading');
            }
        });
    });
});
</script>
