<?php
/**
 * Funciones de compatibilidad de precio para WP-POS
 *
 * Proporciona funciones de respaldo cuando WooCommerce no está disponible
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compatibilidad con wc_price() para temas/plugins que dependen de WooCommerce
 */
if (!function_exists('wc_price')) {
    /**
     * Función de compatibilidad para wc_price
     *
     * @param float $price Precio a formatear
     * @param array $args Argumentos adicionales
     * @return string Precio formateado
     */
    function wc_price($price, $args = array()) {
        return wp_pos_format_price($price, $args);
    }
}

