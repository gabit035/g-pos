<?php
// Handlers para acciones individuales de ventas en WP-POS
add_action('admin_post_wp_pos_cancel_sale', 'wp_pos_handle_cancel_sale');
add_action('admin_post_wp_pos_delete_sale', 'wp_pos_handle_delete_sale');

function wp_pos_handle_cancel_sale() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('No tienes permisos.');
    }
    $sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
    check_admin_referer('wp_pos_cancel_sale_' . $sale_id);
    global $wpdb;
    $table = $wpdb->prefix . 'pos_sales';
    $wpdb->update($table, array('status' => 'cancelled'), array('id' => $sale_id));
    wp_redirect(admin_url('admin.php?page=wp-pos-sales&msg=cancel_ok'));
    exit;
}

function wp_pos_handle_delete_sale() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('No tienes permisos.');
    }
    $sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
    check_admin_referer('wp_pos_delete_sale_' . $sale_id);
    global $wpdb;
    $table = $wpdb->prefix . 'pos_sales';
    $wpdb->delete($table, array('id' => $sale_id));
    wp_redirect(admin_url('admin.php?page=wp-pos-sales&msg=delete_ok'));
    exit;
}
