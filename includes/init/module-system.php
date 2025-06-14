<?php
/**
 * Sistema modular para G-POS
 *
 * Este archivo inicializa el sistema modular que permite gestionar
 * mu00f3dulos de forma simple, intuitiva y escalable.
 *
 * @package WP-POS
 * @subpackage Core
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;

// Cargar las clases base del sistema modular
require_once WP_POS_INCLUDES_DIR . 'interfaces/interface-module.php';
require_once WP_POS_INCLUDES_DIR . 'abstract-class-module.php';
require_once WP_POS_INCLUDES_DIR . 'class-module-registry.php';

/**
 * Inicializar el sistema modular
 */
function wp_pos_init_module_system() {
    // Obtener la instancia del registro de mu00f3dulos
    $registry = WP_POS_Module_Registry::get_instance();
    
    // Lanzar acciu00f3n para que los mu00f3dulos se registren
    do_action('wp_pos_modules_loaded', $registry);
}

/**
 * Registrar un mu00f3dulo en el sistema
 *
 * @param WP_POS_Module_Interface $module Instancia del mu00f3dulo
 * @return bool True si se registru00f3 correctamente, false en caso contrario
 */
function wp_pos_register_module($module) {
    $registry = WP_POS_Module_Registry::get_instance();
    return $registry->register_module($module);
}

/**
 * Obtener un mu00f3dulo por su ID
 *
 * @param string $module_id ID del mu00f3dulo
 * @return WP_POS_Module_Interface|null Instancia del mu00f3dulo o null si no existe
 */
function wp_pos_get_module($module_id) {
    $registry = WP_POS_Module_Registry::get_instance();
    return $registry->get_module($module_id);
}

/**
 * Obtener todos los mu00f3dulos registrados
 *
 * @param bool $active_only True para obtener solo los mu00f3dulos activos
 * @return array Array de mu00f3dulos
 */
function wp_pos_get_modules($active_only = true) {
    $registry = WP_POS_Module_Registry::get_instance();
    return $registry->get_modules($active_only);
}

// Inicializar el sistema modular
add_action('init', 'wp_pos_init_module_system', 5);
