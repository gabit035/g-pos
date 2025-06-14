<?php
/**
 * Inicialización del módulo de recibos
 *
 * @package WP-POS
 * @subpackage Receipts
 * @since 2.3.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluir la clase principal del módulo
require_once dirname(__FILE__) . '/class-pos-receipts-module.php';

// Inicializar el módulo
global $wp_pos_receipts_module;
$wp_pos_receipts_module = new WP_POS_Receipts_Module();
