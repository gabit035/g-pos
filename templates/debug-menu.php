<?php
/**
 * Archivo temporal para depuración de menús
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Esta función registrará la página directamente en el gancho admin_menu con una prioridad muy alta
 */
function wp_pos_register_test_page() {
    // Registrar página de prueba
    add_submenu_page(
        'wp-pos',
        __('Página de Prueba', 'wp-pos'),
        __('Página de Prueba', 'wp-pos'),
        'view_pos',
        'wp-pos-test-page',
        function() {
            echo '<div class="wrap"><h1>Página de prueba</h1><p>Si puedes ver esta página, el sistema de menús está funcionando correctamente.</p></div>';
        }
    );
}
// Usar prioridad muy alta para asegurarnos que se ejecute al final
add_action('admin_menu', 'wp_pos_register_test_page', 100);

// Añadir mensaje de depuración en el pie de página del admin para verificar que el archivo se carga
add_action('admin_footer', function() {
    echo '<!-- El archivo debug-menu.php ha sido cargado correctamente -->';
});
?>
