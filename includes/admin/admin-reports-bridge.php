<?php
/**
 * Puente entre el menu00fa de administraciu00f3n y el mu00f3dulo de reportes
 *
 * Este archivo sirve como conector entre la estructura de menu00fa de WordPress y el mu00f3dulo
 * de reportes renovado. Implementa funcionalidad para trabajar con datos reales del sistema.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Callback para la pu00e1gina de reportes
 * 
 * Implementaciu00f3n directa que utiliza datos reales del sistema
 *
 * @since 1.0.0
 */
function wp_pos_admin_reports_page() {
    // Ruta del mu00f3dulo y la vista
    $module_path = WP_POS_PLUGIN_DIR . 'modules/reports/';
    $view_file = $module_path . 'views/reports-dashboard.php';
    
    // Cargar e inicializar el módulo de reportes
    $module_file = $module_path . 'class-pos-reports-module.php';
    if (file_exists($module_file)) {
        require_once $module_file;
        // Inicializar módulo
        $reports_module = WP_POS_Reports_Module::get_instance();
    }
    
    // Cargar la clase de datos para reportes
    $data_class_file = $module_path . 'includes/class-wp-pos-reports-data.php';
    if (file_exists($data_class_file)) {
        require_once $data_class_file;
    }
    
    // Cargar estilos y scripts necesarios
    wp_enqueue_style('dashicons');
    wp_enqueue_script('jquery');
    
    // Cargar libreru00eda Chart.js
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array('jquery'), '3.9.1', true);
    
    // Cargar CSS del mu00f3dulo de reportes - Garantizando que los estilos visuales se apliquen correctamente
    wp_enqueue_style(
        'wp-pos-reports-styles',
        plugins_url('/modules/reports/assets/css/reports-styles.css', WP_POS_PLUGIN_FILE),
        array(),
        WP_POS_VERSION
    );
    
    // Agregar estilos inline para asegurar que se aplique el estilo visual preferido
    wp_add_inline_style('wp-pos-reports-styles', '
        .wp-pos-reports-container {
            margin: 20px auto;
            width: 1200px;
        }
        .wp-pos-reports-header {
            background: linear-gradient(135deg, #3a6186, #89253e);
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
        }
        .wp-pos-button {
            background: linear-gradient(135deg, #3a6186, #89253e);
            color: #fff;
            border-radius: 4px;
            padding: 8px 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }
        .wp-pos-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
    ');
    
    // Cargar JavaScript del mu00f3dulo de reportes
    if (file_exists($module_path . 'assets/js/reports-scripts.js')) {
        wp_enqueue_script(
            'wp-pos-reports-scripts',
            plugins_url('/modules/reports/assets/js/reports-scripts.js', WP_POS_PLUGIN_FILE),
            array('jquery', 'chartjs'),
            WP_POS_VERSION,
            true
        );
        
        // Localizar script con los datos necesarios
        wp_localize_script('wp-pos-reports', 'wp_pos_reports', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_pos_reports_nonce'),
            'currency' => get_option('wp_pos_currency', '$'),
            'start_date' => isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days')),
            'end_date' => isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d'),
            'i18n' => array(
                'loading' => esc_html__('Cargando...', 'wp-pos'),
                'error' => esc_html__('Error al cargar los datos', 'wp-pos'),
                'no_data' => esc_html__('No hay datos para mostrar', 'wp-pos'),
                'confirm_delete' => esc_html__('¿Estás seguro de que quieres eliminar este elemento?', 'wp-pos')
            )
        ));
    }
    
    // Cargar JavaScript para gru00e1ficos
    if (file_exists($module_path . 'assets/js/reports-charts.js')) {
        wp_enqueue_script(
            'wp-pos-reports-charts',
            plugins_url('/modules/reports/assets/js/reports-charts.js', WP_POS_PLUGIN_FILE),
            array('jquery', 'chartjs'),
            WP_POS_VERSION,
            true
        );
    }
    
    // Cargar la vista directamente
    if (file_exists($view_file)) {
        include $view_file;
        return;
    }
    
    // Alternativa si el mu00f3dulo no estu00e1 disponible o hay algu00fan problema
    // Mostramos una versiu00f3n simplificada con el estilo visual que sabemos que le gusta al usuario
    ?>
    <div class="wrap">
        <h1><?php _e('Informes y Estadu00edsticas', 'wp-pos'); ?></h1>
        
        <!-- Panel con degradado -->
        <div style="background: linear-gradient(135deg, #3a6186, #89253e); color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); margin-bottom: 20px;">
            <h3 style="margin-top: 0;"><?php _e('Mu00f3dulo de Reportes', 'wp-pos'); ?></h3>
            <p><?php _e('El mu00f3dulo de reportes estu00e1 siendo renovado para ofrecerte una mejor experiencia.', 'wp-pos'); ?></p>
        </div>
        
        <!-- Mensaje de estado -->
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">
            <p><?php _e('Por favor, verifica que el mu00f3dulo de reportes estu00e1 correctamente instalado.', 'wp-pos'); ?></p>
            <p><strong><?php _e('Ruta esperada:', 'wp-pos'); ?></strong> <?php echo esc_html(WP_POS_MODULES_DIR . 'reports/class-pos-reports-module.php'); ?></p>
            
            <a href="<?php echo admin_url('admin.php?page=wp-pos'); ?>" style="display: inline-flex; align-items: center; background: linear-gradient(135deg, #3a6186, #89253e); color: #fff; text-decoration: none; padding: 10px 15px; border-radius: 4px; margin-top: 10px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <span class="dashicons dashicons-arrow-left-alt" style="margin-right: 5px;"></span>
                <?php _e('Volver al Dashboard', 'wp-pos'); ?>
            </a>
        </div>
    </div>
    <?php
}
