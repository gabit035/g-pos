<?php
/**
 * Helper para gestionar permisos en el plugin WP-POS
 *
 * @package WP-POS
 * @subpackage Helpers
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verifica si el usuario actual tiene permiso para editar productos
 *
 * @since 1.0.0
 * @return bool True si tiene permiso, false en caso contrario
 */
function wp_pos_can_edit_products() {
    // Solo administradores y gerentes pueden editar productos
    return current_user_can('manage_options') || current_user_can('manage_woocommerce');
}

/**
 * Verifica si el usuario actual tiene permiso para editar ventas
 *
 * @since 1.0.0
 * @return bool True si tiene permiso, false en caso contrario
 */
function wp_pos_can_edit_sales() {
    // Solo administradores y gerentes pueden editar ventas
    return current_user_can('manage_options') || current_user_can('manage_woocommerce');
}

/**
 * Verifica si el usuario actual tiene permiso para eliminar productos
 *
 * @since 1.0.0
 * @return bool True si tiene permiso, false en caso contrario
 */
function wp_pos_can_delete_products() {
    // Solo administradores y gerentes pueden eliminar productos
    return current_user_can('manage_options') || current_user_can('manage_woocommerce');
}

/**
 * Verifica si el usuario actual tiene permiso para eliminar ventas
 *
 * @since 1.0.0
 * @return bool True si tiene permiso, false en caso contrario
 */
function wp_pos_can_delete_sales() {
    // Solo administradores y gerentes pueden eliminar ventas
    return current_user_can('manage_options') || current_user_can('manage_woocommerce');
}

/**
 * Verifica si el usuario actual tiene permiso para ver informes
 *
 * @since 1.0.0
 * @return bool True si tiene permiso, false en caso contrario
 */
function wp_pos_can_view_reports() {
    // Administradores, gerentes y vendedores pueden ver reportes
    return current_user_can('manage_options') || current_user_can('manage_woocommerce') || current_user_can('edit_shop_orders');
}

/**
 * Bloquea el acceso si el usuario actual no tiene los permisos requeridos
 *
 * @since 1.0.0
 * @param callable $permission_check Función de verificación de permisos
 * @return void
 */
function wp_pos_require_permission($permission_check) {
    if (!$permission_check()) {
        wp_die(
            __('No tienes permiso para realizar esta acción.', 'wp-pos'),
            __('Acceso denegado', 'wp-pos'),
            array('response' => 403)
        );
    }
}
