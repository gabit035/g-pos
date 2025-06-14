<?php
/**
 * Registrar la nueva página de nueva venta V2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Función para registrar el menú de la nueva versión
 */
function wp_pos_register_new_sale_v2_page() {
    global $menu, $submenu;
    
    // Agregar página al menú de WordPress
    add_submenu_page(
        'wp-pos',                       // Página padre - el slug correcto del menú principal
        __('Nueva Venta (V2)', 'wp-pos'), // Título de la página
        __('Nueva Venta (V2)', 'wp-pos'), // Título del menú
        'view_pos',                      // Capacidad requerida (la misma que usa el plugin original)
        'wp-pos-new-sale-v2',            // Slug
        'wp_pos_render_new_sale_v2'      // Función para renderizar
    );
}

// Ya no registramos el menú duplicado aquí, se hace en bootstrap.php
// Mantenemos la función disponible para usar en bootstrap.php

/**
 * Función para renderizar la nueva página
 */
function wp_pos_render_new_sale_v2() {
    if (!current_user_can('view_pos')) {
        wp_die(__('Lo siento, no tenés permisos para acceder a esta página.', 'wp-pos'));
    }
    
    require_once WP_POS_PLUGIN_DIR . 'templates/admin-new-sale-v2.php';
}

// Incluir esta funcionalidad al cargar el plugin
add_action('plugins_loaded', function() {
    // Determinar si estamos en el admin y en la página nueva venta v2
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'wp-pos-new-sale-v2') {
        // Registrar y localizar scripts para esta página específica
        add_action('admin_enqueue_scripts', function() {
            // Solo registrar si estamos en la página correcta
            if (isset($_GET['page']) && $_GET['page'] === 'wp-pos-new-sale-v2') {
                // Asegurar que existan los directorios
                if (!file_exists(WP_POS_PLUGIN_DIR . 'templates/css')) {
                    mkdir(WP_POS_PLUGIN_DIR . 'templates/css', 0755, true);
                }
                if (!file_exists(WP_POS_PLUGIN_DIR . 'templates/js')) {
                    mkdir(WP_POS_PLUGIN_DIR . 'templates/js', 0755, true);
                }
                
                // Registrar estilos y scripts
                wp_enqueue_style(
                    'wp-pos-new-sale-v2', 
                    WP_POS_PLUGIN_URL . 'templates/css/new-sale-v2.css',
                    array(),
                    WP_POS_VERSION
                );
                
                // Cargar módulos JS en orden correcto (dependencias)
                wp_enqueue_script(
                    'wp-pos-utils',
                    WP_POS_PLUGIN_URL . 'templates/js/modules/utils.js',
                    array('jquery'),
                    WP_POS_VERSION,
                    true
                );
                
                wp_enqueue_script(
                    'wp-pos-cart',
                    WP_POS_PLUGIN_URL . 'templates/js/modules/cart.js',
                    array('jquery', 'wp-pos-utils'),
                    WP_POS_VERSION,
                    true
                );
                
                wp_enqueue_script(
                    'wp-pos-customer-search',
                    WP_POS_PLUGIN_URL . 'templates/js/modules/customer-search.js',
                    array('jquery', 'wp-pos-utils'),
                    WP_POS_VERSION,
                    true
                );
                
                wp_enqueue_script(
                    'wp-pos-product-search',
                    WP_POS_PLUGIN_URL . 'templates/js/modules/product-search.js',
                    array('jquery', 'wp-pos-utils', 'wp-pos-cart'),
                    WP_POS_VERSION,
                    true
                );
                
                wp_enqueue_script(
                    'wp-pos-service-search',
                    WP_POS_PLUGIN_URL . 'templates/js/modules/service-search.js',
                    array('jquery', 'wp-pos-utils', 'wp-pos-cart'),
                    WP_POS_VERSION,
                    true
                );
                
                // Script principal que une todos los módulos
                wp_enqueue_script(
                    'wp-pos-new-sale-v2',
                    WP_POS_PLUGIN_URL . 'templates/js/new-sale-v2.js',
                    array('jquery', 'wp-pos-utils', 'wp-pos-cart', 'wp-pos-customer-search', 'wp-pos-product-search', 'wp-pos-service-search'),
                    WP_POS_VERSION,
                    true
                );
                
                // Localizar script para AJAX
                wp_localize_script('wp-pos-new-sale-v2', 'wp_pos_data', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_pos_nonce'),
                    'customer_nonce' => wp_create_nonce('wp_pos_customers_nonce'),
                    'product_nonce' => wp_create_nonce('wp_pos_products_nonce'),
                    'service_nonce' => wp_create_nonce('wp_pos_services_nonce'),
                    'texts' => array(
                        'anonymous_customer' => __('Cliente anónimo', 'wp-pos'),
                        'search_customers' => __('Buscar clientes...', 'wp-pos'),
                        'search_products' => __('Buscar productos...', 'wp-pos'),
                        'search_services' => __('Buscar servicios...', 'wp-pos'),
                        'no_results' => __('No se encontraron resultados', 'wp-pos'),
                        'searching' => __('Buscando...', 'wp-pos'),
                        'error' => __('Error al realizar la búsqueda', 'wp-pos'),
                        'add_product' => __('Añadir producto', 'wp-pos'),
                        'add_service' => __('Añadir servicio', 'wp-pos'),
                        'product_added' => __('Producto añadido', 'wp-pos'),
                        'service_added' => __('Servicio añadido', 'wp-pos'),
                        'remove_item' => __('Eliminar', 'wp-pos'),
                        'empty_cart' => __('No hay productos en la venta', 'wp-pos'),
                        'save_sale' => __('Guardar venta', 'wp-pos'),
                        'processing' => __('Procesando...', 'wp-pos'),
                        'sale_saved' => __('Venta guardada correctamente', 'wp-pos'),
                        'error_saving' => __('Error al guardar la venta', 'wp-pos'),
                    )
                ));
            }
        });
    }
});
