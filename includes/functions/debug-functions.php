<?php
/**
 * Funciones de depuraciu00f3n y registro para WP-POS
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar mensaje en el log del sistema
 *
 * @since 1.0.0
 * @param string $message Mensaje a registrar
 * @param string $level Nivel del log (info, warning, error)
 * @param array $context Contexto adicional opcional
 */
function wp_pos_log($message, $level = 'info', $context = array()) {
    // Solo registrar si la depuraciu00f3n estu00e1 activa o es un error
    $debug_mode = defined('WP_DEBUG') && WP_DEBUG;
    if (!$debug_mode && $level !== 'error') {
        return;
    }
    
    // Formatear mensaje con fecha y hora
    $log_message = sprintf('[%s] [%s] %s', 
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message
    );
    
    // Agregar contexto si existe
    if (!empty($context)) {
        $context_str = json_encode($context, JSON_PRETTY_PRINT);
        $log_message .= " - Contexto: {$context_str}";
    }
    
    // Registrar en error_log de PHP
    error_log($log_message);
}

/**
 * Mostrar mensaje de depuraciu00f3n en pantalla
 * Solo visible si WP_DEBUG y WP_DEBUG_DISPLAY estu00e1n activos
 *
 * @since 1.0.0
 * @param mixed $data Datos a mostrar
 * @param bool $die Detener la ejecuciu00f3n despuu00e9s de mostrar
 */
function wp_pos_debug($data, $die = false) {
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
        echo '<pre>';
        if (is_array($data) || is_object($data)) {
            print_r($data);
        } else {
            echo $data;
        }
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }
}
