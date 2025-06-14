<?php
/**
 * Asegurar que todos los archivos necesarios para la interfaz V2 existan
 * durante la activación o primera inicialización del plugin
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Función para asegurar que todos los archivos necesarios para la versión V2 existan
 */
function wp_pos_ensure_v2_files() {
    // Verificar y crear directorios necesarios
    $directories = array(
        WP_POS_PLUGIN_DIR . 'templates/js',
        WP_POS_PLUGIN_DIR . 'templates/js/modules',
        WP_POS_PLUGIN_DIR . 'templates/css'
    );
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
    
    // Archivos básicos que necesitan existir
    $required_files = array(
        'js/new-sale-v2.js',
        'js/modules/utils.js',
        'js/modules/cart.js',
        'js/modules/customer-search.js',
        'js/modules/product-search.js',
        'js/modules/service-search.js',
        'css/new-sale-v2.css',
        'admin-new-sale-v2.php'
    );
    
    $files_created = array();
    
    // Verificar cada archivo y crear una versión básica si no existe
    foreach ($required_files as $file) {
        $file_path = WP_POS_PLUGIN_DIR . 'templates/' . $file;
        
        if (!file_exists($file_path)) {
            // Crear contenido mínimo para cada tipo de archivo
            $content = wp_pos_get_default_file_content($file);
            
            // Guardar el archivo
            file_put_contents($file_path, $content);
            $files_created[] = $file;
        }
    }
    
    // Registrar en el log si se crearon archivos
    if (!empty($files_created)) {
        error_log('WP-POS: Se crearon archivos iniciales para la versión V2: ' . implode(', ', $files_created));
    }
    
    return !empty($files_created);
}

/**
 * Obtener el contenido predeterminado para cada tipo de archivo
 */
function wp_pos_get_default_file_content($file) {
    $file_extension = pathinfo($file, PATHINFO_EXTENSION);
    $file_name = basename($file);
    
    switch ($file_extension) {
        case 'js':
            if ($file_name === 'new-sale-v2.js') {
                return "/**\n * JavaScript para la interfaz de nueva venta (V2)\n */\n\n(function($) {\n    'use strict';\n\n    // Datos globales compartidos\n    window.POS = window.wp_pos_data || {\n        ajaxurl: '',\n        nonce: '',\n        texts: {}\n    };\n\n    $(document).ready(function() {\n        console.log(\"Inicializando POS V2\");\n        \n        // Inicializar módulos si existen\n        if (typeof PosCart !== 'undefined') PosCart.init();\n        if (typeof PosCustomerSearch !== 'undefined') PosCustomerSearch.init();\n        if (typeof PosProductSearch !== 'undefined') PosProductSearch.init();\n        if (typeof PosServiceSearch !== 'undefined') PosServiceSearch.init();\n    });\n\n})(jQuery);";
            } else if (strpos($file, 'modules/utils.js') !== false) {
                return "/**\n * Utilidades generales para el POS\n */\n\nconst PosUtils = (function($) {\n    'use strict';\n\n    function formatPrice(price) {\n        return Number(price).toFixed(2);\n    }\n\n    function showMessage(message, type, duration) {\n        console.log(message);\n    }\n\n    return {\n        formatPrice: formatPrice,\n        showMessage: showMessage\n    };\n\n})(jQuery);";
            } else if (strpos($file, 'modules/cart.js') !== false) {
                return "/**\n * Módulo de carrito para nueva venta\n */\n\nconst PosCart = (function($) {\n    'use strict';\n    \n    // Estado del carrito\n    let cart = {\n        items: [],\n        subtotal: 0,\n        total: 0\n    };\n    \n    function init() {\n        console.log('Inicializando carrito');\n    }\n    \n    function addItem(item) {\n        // Añadir item al carrito\n        cart.items.push(item);\n        return true;\n    }\n    \n    return {\n        init: init,\n        addItem: addItem\n    };\n    \n})(jQuery);";
            } else if (strpos($file, 'modules/customer-search.js') !== false) {
                return "/**\n * Módulo de búsqueda de clientes\n */\n\nconst PosCustomerSearch = (function($) {\n    'use strict';\n    \n    function init() {\n        console.log('Inicializando búsqueda de clientes');\n    }\n    \n    return {\n        init: init\n    };\n    \n})(jQuery);";
            } else if (strpos($file, 'modules/product-search.js') !== false) {
                return "/**\n * Módulo de búsqueda de productos\n */\n\nconst PosProductSearch = (function($) {\n    'use strict';\n    \n    function init() {\n        console.log('Inicializando búsqueda de productos');\n    }\n    \n    return {\n        init: init\n    };\n    \n})(jQuery);";
            } else if (strpos($file, 'modules/service-search.js') !== false) {
                return "/**\n * Módulo de búsqueda de servicios\n */\n\nconst PosServiceSearch = (function($) {\n    'use strict';\n    \n    function init() {\n        console.log('Inicializando búsqueda de servicios');\n    }\n    \n    return {\n        init: init\n    };\n    \n})(jQuery);";
            }
            break;
            
        case 'css':
            return "/**\n * Estilos para la interfaz de Nueva Venta V2\n */\n\n/* Estilos generales */\n.wp-pos-layout {\n    display: flex;\n    flex-wrap: wrap;\n    gap: 20px;\n    margin-top: 20px;\n}\n\n.wp-pos-panel {\n    background: #fff;\n    border-radius: 4px;\n    box-shadow: 0 1px 3px rgba(0,0,0,0.1);\n}\n\n.wp-pos-card {\n    margin-bottom: 20px;\n}\n\n.wp-pos-card-header {\n    padding: 12px 15px;\n    border-bottom: 1px solid #e5e5e5;\n}\n\n.wp-pos-card-header h3 {\n    margin: 0;\n    font-size: 16px;\n    font-weight: 600;\n}\n\n.wp-pos-card-body {\n    padding: 15px;\n}\n\n/* Secciones */\n.wp-pos-panel-left {\n    flex: 2;\n    min-width: 60%;\n}\n\n.wp-pos-panel-right {\n    flex: 1;\n    min-width: 30%;\n}";
            
        case 'php':
            // Para el archivo admin-new-sale-v2.php (plantilla básica)
            return "<?php\n/**\n * Plantilla para la página de Nueva Venta (V2)\n */\n\n// Prevención de acceso directo\nif (!defined('ABSPATH')) {\n    exit;\n}\n?>\n\n<div class=\"wrap wp-pos-container\">\n    <h1><?php _e('Nueva Venta', 'wp-pos'); ?></h1>\n    \n    <form id=\"wp-pos-new-sale-form\" method=\"post\">\n        <!-- Campo oculto para identificar el formulario -->\n        <input type=\"hidden\" name=\"wp_pos_process_sale_direct\" value=\"1\">\n        <input type=\"hidden\" name=\"wp_pos_sale_data\" id=\"wp_pos_sale_data\" value=\"\">\n        <?php wp_nonce_field('wp_pos_process_sale_direct', 'wp_pos_sale_nonce'); ?>\n        \n        <div class=\"wp-pos-layout\">\n            <!-- Panel izquierdo: Datos de venta y carrito -->\n            <div class=\"wp-pos-panel wp-pos-panel-left\">\n                <!-- Sección de Información de venta -->\n                <div class=\"wp-pos-card\">\n                    <div class=\"wp-pos-card-header\">\n                        <h3><?php _e('Información de la venta', 'wp-pos'); ?></h3>\n                    </div>\n                    <div class=\"wp-pos-card-body\">\n                        <!-- Datos de venta -->\n                    </div>\n                </div>\n                \n                <!-- Lista de productos -->\n                <div class=\"wp-pos-card\">\n                    <div class=\"wp-pos-card-header\">\n                        <h3><?php _e('Items de la venta', 'wp-pos'); ?></h3>\n                    </div>\n                    <div class=\"wp-pos-card-body\">\n                        <!-- Listado de items -->\n                    </div>\n                </div>\n            </div>\n            \n            <!-- Panel derecho: Búsqueda de productos y servicios -->\n            <div class=\"wp-pos-panel wp-pos-panel-right\">\n                <!-- Pestañas para productos/servicios -->\n                <div class=\"wp-pos-tabs\">\n                    <div class=\"wp-pos-tab wp-pos-tab-active\" data-tab=\"products\">\n                        <?php _e('Productos', 'wp-pos'); ?>\n                    </div>\n                    <div class=\"wp-pos-tab\" data-tab=\"services\">\n                        <?php _e('Servicios', 'wp-pos'); ?>\n                    </div>\n                </div>\n                \n                <!-- Contenido de las pestañas -->\n                <div class=\"wp-pos-tab-contents\">\n                    <!-- Contenido: Productos -->\n                    <div id=\"wp-pos-tab-products\" class=\"wp-pos-tab-content wp-pos-tab-content-active\">\n                        <!-- Búsqueda de productos -->\n                    </div>\n                    \n                    <!-- Contenido: Servicios -->\n                    <div id=\"wp-pos-tab-services\" class=\"wp-pos-tab-content\">\n                        <!-- Búsqueda de servicios -->\n                    </div>\n                </div>\n            </div>\n        </div>\n        \n        <!-- Resumen y totales -->\n        <div class=\"wp-pos-footer\">\n            <button type=\"submit\" id=\"wp-pos-save-sale\" class=\"button button-primary\">\n                <?php _e('Guardar venta', 'wp-pos'); ?>\n            </button>\n        </div>\n    </form>\n</div>\n\n<?php\n// Localizar script para AJAX\nwp_localize_script('wp-pos-new-sale-v2', 'wp_pos_data', array(\n    'ajaxurl' => admin_url('admin-ajax.php'),\n    'nonce' => wp_create_nonce('wp_pos_nonce'),\n    'texts' => array(\n        'anonymous_customer' => __('Cliente anónimo', 'wp-pos'),\n        'empty_cart' => __('No hay productos en la venta', 'wp-pos'),\n        'save_sale' => __('Guardar venta', 'wp-pos'),\n        'processing' => __('Procesando...', 'wp-pos'),\n    )\n));\n?>";
    }
    
    return ''; // Contenido vacío por defecto
}
