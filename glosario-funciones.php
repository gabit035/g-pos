<?php
/**
 * Glosario de Funciones del Plugin G-POS
 *
 * Este archivo contiene una descripción detallada de las funciones clave utilizadas en el plugin G-POS,
 * organizadas por módulos funcionales. Cada función sigue los estándares de documentación de WordPress.
 *
 * @package G-POS
 * @since 1.0.0
 */

// =============================================
// FUNCIONES PRINCIPALES
// =============================================

/**
 * wp_pos_load_modules
 *
 * Carga e inicializa los módulos del plugin G-POS.
 * 
 * @since 1.0.0
 * @return void
 */
function wp_pos_load_modules() {}

/**
 * wp_pos_get_option
 *
 * Recupera las opciones configuradas para el plugin G-POS.
 *
 * @since 1.0.0
 * @param string $option Nombre de la opción a recuperar
 * @param mixed $default Valor por defecto si la opción no existe
 * @return mixed Valor de la opción solicitada o $default si no existe
 */
function wp_pos_get_option($option, $default = false) {}

// =============================================
// MÓDULO DE DEPURACIÓN
// =============================================

/**
 * wp_pos_direct_debug_panel
 *
 * Agrega un panel de depuración directamente al dashboard del plugin.
 * Muestra información sobre el stock bajo y permite realizar acciones rápidas.
 *
 * @since 1.2.0
 * @return void
 */
function wp_pos_direct_debug_panel() {}

/**
 * wp_pos_show_debug_overlay
 *
 * Muestra un overlay de depuración que cubre toda la pantalla, mostrando información
 * detallada sobre el estado del sistema y permitiendo cerrar el overlay con un botón.
 *
 * @since 1.2.0
 * @return void
 */
function wp_pos_show_debug_overlay() {}

/**
 * wp_pos_get_low_stock_threshold
 *
 * Obtiene el umbral configurado para considerar un producto como de stock bajo.
 *
 * @since 1.0.0
 * @return int Umbral de stock bajo
 */
function wp_pos_get_low_stock_threshold() {}

/**
 * wp_pos_get_products_info
 *
 * Recupera información sobre los productos con stock bajo desde la base de datos.
 *
 * @since 1.0.0
 * @param int $threshold Umbral de stock bajo
 * @return array Información de productos con stock bajo
 */
function wp_pos_get_products_info($threshold) {}

/**
 * wp_pos_render_debug_panel
 *
 * Renderiza el panel de depuración con información sobre el estado del stock.
 *
 * @since 1.2.0
 * @param int $threshold Umbral de stock bajo
 * @param array $pos_products_info Información de productos POS
 * @param array $wc_products_info Información de productos WooCommerce
 * @return void
 */
function wp_pos_render_debug_panel($threshold, $pos_products_info, $wc_products_info) {}

// =============================================
// MÓDULO DE VENTAS
// =============================================

/**
 * wp_pos_create_sale
 *
 * Crea una nueva venta en el sistema.
 *
 * @since 1.0.0
 * @param array $sale_data Datos de la venta
 * @return int|WP_Error ID de la venta creada o error
 */
function wp_pos_create_sale($sale_data) {}

/**
 * wp_pos_get_sale
 *
 * Obtiene los datos de una venta específica.
 *
 * @since 1.0.0
 * @param int $sale_id ID de la venta
 * @return array|false Datos de la venta o false si no existe
 */
function wp_pos_get_sale($sale_id) {}

/**
 * wp_pos_update_sale_status
 *
 * Actualiza el estado de una venta.
 *
 * @since 1.0.0
 * @param int $sale_id ID de la venta
 * @param string $new_status Nuevo estado
 * @return bool|WP_Error True si se actualizó correctamente, false o error en caso contrario
 */
function wp_pos_update_sale_status($sale_id, $new_status) {}

// =============================================
// MÓDULO DE PRODUCTOS
// =============================================

/**
 * wp_pos_get_product
 *
 * Obtiene la información detallada de un producto.
 *
 * @since 1.0.0
 * @param int $product_id ID del producto
 * @return array|false Datos del producto o false si no existe
 */
function wp_pos_get_product($product_id) {}

/**
 * wp_pos_update_product_stock
 *
 * Actualiza el stock de un producto.
 *
 * @since 1.0.0
 * @param int $product_id ID del producto
 * @param int $quantity Cantidad a sumar/restar (puede ser negativo)
 * @param string $operation Tipo de operación ('add', 'subtract', 'set')
 * @return bool|WP_Error True si se actualizó correctamente, false o error en caso contrario
 */
function wp_pos_update_product_stock($product_id, $quantity, $operation = 'add') {}

/**
 * wp_pos_get_low_stock_products
 *
 * Obtiene la lista de productos con stock bajo.
 *
 * @since 1.0.0
 * @param int $threshold Umbral de stock bajo (opcional)
 * @return array Lista de productos con stock bajo
 */
function wp_pos_get_low_stock_products($threshold = null) {}

// =============================================
// MÓDULO DE CLIENTES
// =============================================

/**
 * wp_pos_create_customer
 *
 * Crea un nuevo cliente en el sistema.
 *
 * @since 1.0.0
 * @param array $customer_data Datos del cliente
 * @return int|WP_Error ID del cliente creado o error
 */
function wp_pos_create_customer($customer_data) {}

/**
 * wp_pos_get_customer
 *
 * Obtiene la información de un cliente.
 *
 * @since 1.0.0
 * @param int $customer_id ID del cliente
 * @return array|false Datos del cliente o false si no existe
 */
function wp_pos_get_customer($customer_id) {}

/**
 * wp_pos_get_customer_purchases
 *
 * Obtiene el historial de compras de un cliente.
 *
 * @since 1.0.0
 * @param int $customer_id ID del cliente
 * @param array $args Argumentos adicionales (paginación, fechas, etc.)
 * @return array Lista de compras del cliente
 */
function wp_pos_get_customer_purchases($customer_id, $args = array()) {}

// =============================================
// MÓDULO DE CIERRES DE CAJA
// =============================================

/**
 * wp_pos_open_register
 *
 * Abre una nueva caja registradora.
 *
 * @since 1.3.0
 * @param int $user_id ID del usuario que abre la caja
 * @param float $opening_balance Saldo inicial
 * @param string $notes Notas adicionales
 * @return int|WP_Error ID del registro de caja o error
 */
function wp_pos_open_register($user_id, $opening_balance, $notes = '') {}

/**
 * wp_pos_close_register
 *
 * Cierra una caja registradora.
 *
 * @since 1.3.0
 * @param int $register_id ID del registro de caja
 * @param float $closing_balance Saldo final
 * @param array $counts Conteo de billetes/monedas
 * @param string $notes Notas adicionales
 * @return bool|WP_Error True si se cerró correctamente, error en caso contrario
 */
function wp_pos_close_register($register_id, $closing_balance, $counts = array(), $notes = '') {}

/**
 * wp_pos_get_register_status
 *
 * Obtiene el estado actual de una caja registradora.
 *
 * @since 1.3.0
 * @param int $register_id ID del registro de caja
 * @return array|false Estado de la caja o false si no existe
 */
function wp_pos_get_register_status($register_id) {}

// =============================================
// FUNCIONES DE AYUDA
// =============================================

/**
 * wp_pos_format_price
 *
 * Formatea un precio según la configuración de moneda.
 *
 * @since 1.0.0
 * @param float $price Precio a formatear
 * @param string $currency Código de moneda (opcional)
 * @return string Precio formateado
 */
function wp_pos_format_price($price, $currency = '') {}

/**
 * wp_pos_log
 * 
 * Registra un mensaje en el log del sistema.
 *
 * @since 1.0.0
 * @param string $message Mensaje a registrar
 * @param string $level Nivel de log (error, warning, info, debug)
 * @param array $context Contexto adicional
 * @return void
 */
function wp_pos_log($message, $level = 'info', $context = array()) {}

/**
 * wp_pos_has_capability
 *
 * Verifica si el usuario actual tiene un permiso específico.
 *
 * @since 1.0.0
 * @param string $capability Nombre del permiso
 * @param int $user_id ID del usuario (opcional, por defecto usuario actual)
 * @return bool True si el usuario tiene el permiso, false en caso contrario
 */
function wp_pos_has_capability($capability, $user_id = null) {}
