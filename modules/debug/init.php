<?php
/**
 * Inicialización del módulo de debug
 *
 * @package WP-POS
 * @subpackage Debug
 * @since 2.3.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluir la clase principal del módulo
require_once dirname(__FILE__) . '/class-pos-debug-module.php';

// Inicializar el módulo solo si estamos en modo debug
if (defined('WP_DEBUG') && WP_DEBUG) {
    global $wp_pos_debug_module;
    $wp_pos_debug_module = new WP_POS_Debug_Module();
}
