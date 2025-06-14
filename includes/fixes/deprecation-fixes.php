<?php
/**
 * Parches para corregir advertencias de deprecación en PHP 8.1+
 *
 * Este archivo contiene un manejador de errores personalizado para suprimir
 * advertencias de deprecación relacionadas con el paso de valores nulos a
 * funciones nativas de WordPress como strpos() y str_replace().
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manejador de errores personalizado para suprimir advertencias específicas
 *
 * @param int $errno Número de error
 * @param string $errstr Mensaje de error
 * @param string $errfile Archivo donde ocurrió el error
 * @param int $errline Línea donde ocurrió el error
 * @return bool True si el error fue manejado, false para permitir manejo nativo
 */
function wp_pos_custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Verificar si es una advertencia de deprecación
    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        // Suprimir advertencias específicas relacionadas con strpos y str_replace
        if (strpos($errstr, 'strpos(): Passing null to parameter') !== false ||
            strpos($errstr, 'str_replace(): Passing null to parameter') !== false) {
            // No hacemos nada, solo suprimimos la advertencia
            return true;
        }
    }
    
    // Para otros errores, permitir manejo nativo
    return false;
}

// Registrar nuestro manejador en init para asegurarnos de que se aplica en cada request
function wp_pos_register_error_handler() {
    // Solo aplicar en páginas del plugin WP-POS
    if (is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'wp-pos') === 0) {
        // Establecer el manejador de errores
        set_error_handler('wp_pos_custom_error_handler', E_DEPRECATED | E_USER_DEPRECATED);
        
        // Añadir un buffer de salida si estamos en una página de plugin que sabemos que tiene problemas
        if (isset($_GET['page']) && ($_GET['page'] === 'wp-pos-new-sale' || $_GET['page'] === 'wp-pos-new-sale-v2')) {
            ob_start(function($buffer) {
                // Eliminar advertencias de deprecación específicas
                $buffer = preg_replace('/\[.*?\]\s*PHP\s*Deprecated:\s*strpos\(\).*?\n/s', '', $buffer);
                $buffer = preg_replace('/\[.*?\]\s*PHP\s*Deprecated:\s*str_replace\(\).*?\n/s', '', $buffer);
                return $buffer;
            });
        }
    }
}

// Registrar nuestras funciones para ejecutarse temprano
add_action('init', 'wp_pos_register_error_handler', 1);
