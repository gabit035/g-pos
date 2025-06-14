<?php
/**
 * Funciones de traducciu00f3n seguras para WP-POS
 *
 * Estas funciones proporcionan una capa de compatibilidad para evitar errores
 * de carga temprana de traducciones en WordPress 6.7+
 *
 * @package WP-POS
 * @subpackage Core
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;

/**
 * Traduce un texto de forma segura, evitando errores de carga temprana en WP 6.7+
 * 
 * Esta funciu00f3n actu00faa como un wrapper para __() de WordPress, pero evita
 * errores cuando se llama antes del hook 'init'.
 *
 * @param string $text Texto a traducir
 * @param string $domain Dominio de texto (por defecto: 'wp-pos')
 * @return string Texto traducido o el texto original si au00fan no se han cargado las traducciones
 */
function wp_pos_translate($text, $domain = 'wp-pos') {
    // Verificar si estamos antes del hook 'init'
    if (!did_action('init')) {
        // Guardamos en un array global los textos que necesitan ser traducidos
        global $wp_pos_texts_to_translate;
        if (!is_array($wp_pos_texts_to_translate)) {
            $wp_pos_texts_to_translate = array();
        }
        
        // Agregar este texto al array para traducirlo despuu00e9s
        $key = md5($text . '|' . $domain);
        $wp_pos_texts_to_translate[$key] = array(
            'text' => $text,
            'domain' => $domain
        );
        
        // Devolver el texto sin traducir por ahora
        return $text;
    }
    
    // Si ya estamos despuu00e9s de 'init', usar la funciu00f3n normal de WordPress
    return __($text, $domain);
}

/**
 * Muestra un texto traducido de forma segura
 *
 * @param string $text Texto a traducir
 * @param string $domain Dominio de texto (por defecto: 'wp-pos')
 */
function wp_pos_e($text, $domain = 'wp-pos') {
    echo wp_pos_translate($text, $domain);
}

/**
 * Traduce y escapa un texto de forma segura
 *
 * @param string $text Texto a traducir
 * @param string $domain Dominio de texto (por defecto: 'wp-pos')
 * @return string Texto traducido y escapado
 */
function wp_pos_esc_translate($text, $domain = 'wp-pos') {
    return esc_html(wp_pos_translate($text, $domain));
}

/**
 * Inicializar el sistema de traducciu00f3n segura
 * 
 * Esta funciu00f3n debe ejecutarse en el hook 'init' despuu00e9s de que se hayan cargado
 * las traducciones oficialmente.
 */
function wp_pos_init_translations() {
    global $wp_pos_texts_to_translate;
    
    if (is_array($wp_pos_texts_to_translate) && !empty($wp_pos_texts_to_translate)) {
        foreach ($wp_pos_texts_to_translate as $item) {
            // Ahora que ya estamos en 'init', traducir los textos pendientes
            // (esto no hace nada visible, pero prepara las cadenas para el sistema de traducciu00f3n)
            __($item['text'], $item['domain']);
        }
    }
}

// Registrar la inicializaciu00f3n de traducciones con prioridad más alta para evitar cargas tempranas
add_action('init', 'wp_pos_init_translations', 30); // Prioridad 30 para asegurar que se ejecute después de todas las acciones críticas
