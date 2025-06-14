<?php
/**
 * Funciones utilitarias para el mu00f3dulo de reportes
 *
 * @package WP-POS
 * @subpackage Reports
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Formatea un nu00famero como moneda
 *
 * @param float $amount Cantidad a formatear
 * @return string Cantidad formateada
 */
function wp_pos_format_currency($amount) {
    return '$' . number_format($amount, 2, '.', ',');
}

/**
 * Convierte una fecha a formato legible
 *
 * @param string $date Fecha en formato Y-m-d H:i:s
 * @return string Fecha formateada
 */
function wp_pos_format_date($date) {
    $timestamp = strtotime($date);
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Obtiene los datos de demo para reportes
 *
 * @return array Datos de demostraciu00f3n para reportes
 */
function wp_pos_get_demo_sales() {
    return [
        [
            'id' => 1001,
            'display_name' => 'Juan Pu00e9rez',
            'items_count' => 3,
            'total' => 456.78,
            'payment_method' => 'Efectivo',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'id' => 1002,
            'display_name' => 'Maru00eda Garcu00eda',
            'items_count' => 2,
            'total' => 289.99,
            'payment_method' => 'Tarjeta',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
        ],
        [
            'id' => 1003,
            'display_name' => 'Carlos Rodru00edguez',
            'items_count' => 5,
            'total' => 768.50,
            'payment_method' => 'Transferencia',
            'created_at' => date('Y-m-d H:i:s', strtotime('-8 hours'))
        ],
        [
            'id' => 1004,
            'display_name' => 'Ana Martu00ednez',
            'items_count' => 1,
            'total' => 124.99,
            'payment_method' => 'Tarjeta',
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 hours'))
        ]
    ];
}
