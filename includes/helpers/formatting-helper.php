<?php
/**
 * Funciones de ayuda para formateo
 *
 * @package WP-POS
 * @since 1.1.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Formatear un nu00famero como precio
 *
 * @since 1.1.0
 * @param float $amount Monto a formatear
 * @param bool $with_symbol Si se debe incluir el su00edmbolo de moneda
 * @return string Precio formateado
 */
function wp_pos_format_price($amount, $with_symbol = true) {
    $symbol = get_woocommerce_currency_symbol();
    $price = number_format((float)$amount, 2, ',', '.');
    
    return $with_symbol ? $symbol . ' ' . $price : $price;
}

/**
 * Formatear una fecha para mostrar en el sistema
 *
 * @since 1.1.0
 * @param string $date Fecha en formato ISO
 * @param string $format Formato personalizado (opcional). Si no se especifica, usa el formato de WordPress
 * @return string Fecha formateada
 */
function wp_pos_format_date($date, $format = '') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date; // Devolver la fecha original si no se puede convertir
    }
    
    // Si no se especifica un formato, usar el formato de WordPress
    if (empty($format)) {
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        $formatted_date = date_i18n($date_format . ' ' . $time_format, $timestamp);
        
        // Forzar codificación UTF-8
        return mb_convert_encoding($formatted_date, 'UTF-8', 'auto');
    }
    
    // Usar el formato personalizado si se especificó
    $formatted_date = date_i18n($format, $timestamp);
    return mb_convert_encoding($formatted_date, 'UTF-8', 'auto');
}
