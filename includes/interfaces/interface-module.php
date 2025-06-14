<?php
/**
 * Interfaz para todos los mu00f3dulos del sistema G-POS
 *
 * Define el contrato que todos los mu00f3dulos deben implementar
 * para poder ser registrados y gestionados por el sistema.
 *
 * @package WP-POS
 * @subpackage Core
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;

interface WP_POS_Module_Interface {
    /**
     * Obtener el ID u00fanico del mu00f3dulo
     *
     * @return string Identificador u00fanico del mu00f3dulo
     */
    public function get_id();
    
    /**
     * Obtener el nombre para mostrar del mu00f3dulo
     *
     * @return string Nombre del mu00f3dulo para mostrar en el menu00fa
     */
    public function get_name();
    
    /**
     * Obtener la capacidad (permiso) requerida para acceder al mu00f3dulo
     *
     * @return string Capacidad de WordPress requerida
     */
    public function get_capability();
    
    /**
     * Obtener la posiciu00f3n del mu00f3dulo en el menu00fa
     *
     * Los valores mu00e1s bajos aparecen primero. Null para usar el valor por defecto.
     *
     * @return int|null Posiciu00f3n en el menu00fa o null
     */
    public function get_position();
    
    /**
     * Obtener el icono del mu00f3dulo
     *
     * Puede ser una URL, una clase dashicon, o null para usar el valor por defecto.
     *
     * @return string|null Icono del mu00f3dulo o null
     */
    public function get_icon();
    
    /**
     * Verificar si el mu00f3dulo estu00e1 activo
     *
     * @return bool True si el mu00f3dulo estu00e1 activo, false en caso contrario
     */
    public function is_active();
    
    /**
     * Verificar si el mu00f3dulo debe mostrarse en el menu00fa
     *
     * @return bool True si el mu00f3dulo debe mostrarse, false en caso contrario
     */
    public function show_in_menu();
    
    /**
     * Inicializar el mu00f3dulo
     *
     * Este mu00e9todo se llama cuando el mu00f3dulo se carga por primera vez.
     *
     * @return void
     */
    public function initialize();
    
    /**
     * Registrar assets (CSS, JS) del mu00f3dulo
     *
     * @return void
     */
    public function register_assets();
    
    /**
     * Renderizar el contenido del mu00f3dulo
     *
     * Esta funciu00f3n se llama cuando se accede a la pu00e1gina del mu00f3dulo.
     *
     * @return void
     */
    public function render_content();
}
