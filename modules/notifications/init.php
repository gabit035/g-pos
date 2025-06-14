<?php
/**
 * Inicialización del módulo de notificaciones
 *
 * @package WP-POS
 * @subpackage Notifications
 * @since 2.3.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluir la clase principal del módulo
require_once dirname(__FILE__) . '/class-pos-notifications-module.php';

// Inicializar el módulo
global $wp_pos_notifications_module;
$wp_pos_notifications_module = WP_POS_Notifications_Module::get_instance();
