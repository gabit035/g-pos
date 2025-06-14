<?php
// Prevención de acceso directo
if (!defined('ABSPATH')) {
    die('Acceso directo no permitido.');
}

// Información de depuración
echo '<div style="background: #f1f1f1; padding: 20px; margin: 20px; border: 1px solid #ccc; font-family: monospace;">';
echo '<h2>Diagnóstico de Estilos del Formulario de Productos</h2>';

// Verificar la ruta del archivo CSS
$css_file_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'modules/products/assets/css/product-form.css';
echo '<p>Ruta del archivo CSS: ' . $css_file_path . '</p>';
echo '<p>¿El archivo existe? ' . (file_exists($css_file_path) ? 'SÍ' : 'NO') . '</p>';

if (file_exists($css_file_path)) {
    echo '<p>Tamaño del archivo: ' . filesize($css_file_path) . ' bytes</p>';
    echo '<p>Últimos 200 caracteres del CSS:</p>';
    $css_content = file_get_contents($css_file_path);
    echo '<pre>' . esc_html(substr($css_content, -200)) . '</pre>';
}

// Verificar qué estilos CSS están registrados
echo '<h3>Estilos CSS registrados:</h3>';
echo '<pre>';
print_r(wp_styles());
echo '</pre>';

echo '</div>';
