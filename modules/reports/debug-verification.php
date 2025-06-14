<?php
/**
 * Verificador de Estado para Corrección de JavaScript - WP-POS Reports
 * 
 * Agrega este código temporalmente a tu clase principal de reportes
 * para verificar que todo esté funcionando correctamente.
 * 
 * @package WP-POS
 * @subpackage Reports
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para verificar el estado de la corrección JavaScript
 */
class WP_POS_Reports_Debug_Verification {
    
    /**
     * Inicializar verificaciones
     */
    public static function init() {
        // Solo para administradores
        if (!current_user_can('administrator')) {
            return;
        }
        
        // Agregar hooks de verificación
        add_action('admin_footer', array(__CLASS__, 'add_debug_scripts'));
        add_action('wp_ajax_wp_pos_verify_js_fix', array(__CLASS__, 'ajax_verify_js_fix'));
        add_action('admin_notices', array(__CLASS__, 'show_verification_notice'));
    }
    
    /**
     * Mostrar notificación de verificación
     */
    public static function show_verification_notice() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wp-pos-reports') === false) {
            return;
        }
        
        ?>
        <div class="notice notice-info is-dismissible" id="wp-pos-js-verification">
            <p>
                <strong>🔧 Verificación de Corrección JavaScript WP-POS</strong><br>
                <span id="verification-status">Verificando corrección JavaScript...</span>
            </p>
            <p>
                <button type="button" class="button" id="run-verification">
                    🔍 Ejecutar Verificación Manual
                </button>
                <button type="button" class="button" id="test-ajax-filters">
                    🧪 Probar Filtros AJAX
                </button>
            </p>
        </div>
        <?php
    }
    
    /**
     * Agregar scripts de verificación
     */
    public static function add_debug_scripts() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wp-pos-reports') === false) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('🔍 === INICIANDO VERIFICACIÓN JAVASCRIPT WP-POS ===');
            
            // Verificación automática al cargar
            setTimeout(function() {
                runJavaScriptVerification();
            }, 2000);
            
            // Botón de verificación manual
            $('#run-verification').on('click', function() {
                runJavaScriptVerification();
            });
            
            // Botón de prueba AJAX
            $('#test-ajax-filters').on('click', function() {
                testAjaxFilters();
            });
            
            function runJavaScriptVerification() {
                console.log('🧪 Ejecutando verificación...');
                
                var results = {
                    wpPosReports: checkWPPosReports(),
                    wpPosFilters: checkWPPosFilters(),
                    globalFunctions: checkGlobalFunctions(),
                    eventHandlers: checkEventHandlers(),
                    ajaxConfig: checkAjaxConfig()
                };
                
                displayResults(results);
                sendResultsToServer(results);
            }
            
            function checkWPPosReports() {
                var checks = {
                    objectExists: typeof window.WPPosReports !== 'undefined',
                    isInitialized: window.WPPosReports?.state?.initialized === true,
                    hasApplyFilters: typeof window.WPPosReports?.applyFilters === 'function',
                    hasGetFilterValues: typeof window.WPPosReports?.getFilterValues === 'function',
                    hasConfig: typeof window.WPPosReports?.config === 'object',
                    hasNonce: !!window.WPPosReports?.config?.nonce
                };
                
                console.log('✅ WPPosReports checks:', checks);
                return checks;
            }
            
            function checkWPPosFilters() {
                var checks = {
                    objectExists: typeof window.WPPosFilters !== 'undefined',
                    isInitialized: window.WPPosFilters?.state?.initialized === true,
                    hasTriggerSubmit: typeof window.WPPosFilters?.triggerSubmit === 'function',
                    hasValidateForm: typeof window.WPPosFilters?.validateForm === 'function'
                };
                
                console.log('✅ WPPosFilters checks:', checks);
                return checks;
            }
            
            function checkGlobalFunctions() {
                var checks = {
                    wpPosApplyFiltersAjax: typeof window.wpPosApplyFiltersAjax === 'function',
                    WPPosApplyFilters: typeof window.WPPosApplyFilters === 'function',
                    WPPosRefreshData: typeof window.WPPosRefreshData === 'function'
                };
                
                console.log('✅ Global functions checks:', checks);
                return checks;
            }
            
            function checkEventHandlers() {
                var checks = {
                    ajaxButton: $('#wp-pos-apply-filters-ajax').length > 0,
                    refreshButton: $('.wp-pos-refresh-button').length > 0,
                    filterForm: $('#wp-pos-filter-form').length > 0,
                    periodSelect: $('#wp-pos-periodo').length > 0
                };
                
                console.log('✅ Event handlers checks:', checks);
                return checks;
            }
            
            function checkAjaxConfig() {
                var checks = {
                    ajaxUrl: !!window.ajaxurl,
                    wpPosConfig: typeof window.wp_pos_reports_config !== 'undefined',
                    hasNonce: !!window.wp_pos_reports_config?.nonce
                };
                
                console.log('✅ AJAX config checks:', checks);
                return checks;
            }
            
            function displayResults(results) {
                var totalChecks = 0;
                var passedChecks = 0;
                var issues = [];
                
                // Contar checks
                Object.keys(results).forEach(function(category) {
                    Object.keys(results[category]).forEach(function(check) {
                        totalChecks++;
                        if (results[category][check]) {
                            passedChecks++;
                        } else {
                            issues.push(category + '.' + check);
                        }
                    });
                });
                
                var percentage = Math.round((passedChecks / totalChecks) * 100);
                var status = '';
                var color = '';
                
                if (percentage >= 90) {
                    status = '✅ Corrección JavaScript EXITOSA (' + percentage + '%)';
                    color = 'green';
                } else if (percentage >= 70) {
                    status = '⚠️ Corrección PARCIAL (' + percentage + '%) - Revisar issues';
                    color = 'orange';
                } else {
                    status = '❌ Corrección FALLIDA (' + percentage + '%) - Verificar implementación';
                    color = 'red';
                }
                
                $('#verification-status').html(status).css('color', color);
                
                if (issues.length > 0) {
                    console.warn('⚠️ Issues encontrados:', issues);
                    $('#verification-status').append('<br><small>Issues: ' + issues.join(', ') + '</small>');
                }
                
                console.log('📊 Verificación completada:', {
                    total: totalChecks,
                    passed: passedChecks,
                    percentage: percentage,
                    issues: issues
                });
            }
            
            function testAjaxFilters() {
                console.log('🧪 Probando filtros AJAX...');
                
                if (typeof window.wpPosApplyFiltersAjax === 'function') {
                    try {
                        window.wpPosApplyFiltersAjax();
                        $('#verification-status').append('<br>✅ Prueba AJAX exitosa');
                    } catch (error) {
                        console.error('❌ Error en prueba AJAX:', error);
                        $('#verification-status').append('<br>❌ Error en prueba AJAX: ' + error.message);
                    }
                } else {
                    $('#verification-status').append('<br>❌ Función wpPosApplyFiltersAjax no disponible');
                }
            }
            
            function sendResultsToServer(results) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_pos_verify_js_fix',
                        results: JSON.stringify(results),
                        nonce: '<?php echo wp_create_nonce('wp_pos_verify_js'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            console.log('📝 Resultados enviados al servidor:', response.data);
                        }
                    }
                });
            }
            
            console.log('🔍 === VERIFICACIÓN JAVASCRIPT CONFIGURADA ===');
        });
        </script>
        
        <style>
        #wp-pos-js-verification {
            border-left: 4px solid #6c5ce7;
        }
        #verification-status {
            font-weight: bold;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX handler para recibir resultados de verificación
     */
    public static function ajax_verify_js_fix() {
        check_ajax_referer('wp_pos_verify_js', 'nonce');
        
        if (!current_user_can('administrator')) {
            wp_send_json_error('Permisos insuficientes');
        }
        
        $results = json_decode(stripslashes($_POST['results']), true);
        
        // Guardar resultados en log
        error_log('=== VERIFICACIÓN JAVASCRIPT WP-POS ===');
        error_log('Timestamp: ' . current_time('mysql'));
        error_log('User: ' . wp_get_current_user()->user_login);
        error_log('Results: ' . print_r($results, true));
        error_log('=========================================');
        
        // Analizar resultados
        $analysis = self::analyze_results($results);
        
        wp_send_json_success(array(
            'message' => 'Resultados de verificación guardados',
            'analysis' => $analysis
        ));
    }
    
    /**
     * Analizar resultados de verificación
     */
    private static function analyze_results($results) {
        $total = 0;
        $passed = 0;
        $critical_issues = array();
        
        foreach ($results as $category => $checks) {
            foreach ($checks as $check => $result) {
                $total++;
                if ($result) {
                    $passed++;
                } else {
                    // Identificar issues críticos
                    if (in_array($check, ['objectExists', 'isInitialized', 'hasApplyFilters'])) {
                        $critical_issues[] = "$category.$check";
                    }
                }
            }
        }
        
        $percentage = $total > 0 ? round(($passed / $total) * 100) : 0;
        
        $status = 'unknown';
        if ($percentage >= 90 && empty($critical_issues)) {
            $status = 'success';
        } elseif ($percentage >= 70 && count($critical_issues) <= 1) {
            $status = 'partial';
        } else {
            $status = 'failed';
        }
        
        return array(
            'total_checks' => $total,
            'passed_checks' => $passed,
            'percentage' => $percentage,
            'status' => $status,
            'critical_issues' => $critical_issues,
            'recommendation' => self::get_recommendation($status, $critical_issues)
        );
    }
    
    /**
     * Obtener recomendación basada en el análisis
     */
    private static function get_recommendation($status, $critical_issues) {
        switch ($status) {
            case 'success':
                return 'La corrección JavaScript se implementó correctamente. Sistema funcionando óptimamente.';
                
            case 'partial':
                return 'La corrección está mayormente implementada. Revisar: ' . implode(', ', $critical_issues);
                
            case 'failed':
                return 'La corrección no se implementó correctamente. Issues críticos: ' . implode(', ', $critical_issues) . '. Verificar que los archivos JavaScript se hayan reemplazado y limpiado la caché.';
                
            default:
                return 'No se pudo determinar el estado. Ejecutar verificación nuevamente.';
        }
    }
    
    /**
     * Obtener información del sistema para debug
     */
    public static function get_system_info() {
        return array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'theme' => get_template(),
            'plugins' => array_keys(get_plugins()),
            'debug_mode' => WP_DEBUG,
            'script_debug' => SCRIPT_DEBUG,
            'memory_limit' => ini_get('memory_limit'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
    }
}

// Inicializar verificación si estamos en desarrollo o debug
if (WP_DEBUG || current_user_can('administrator')) {
    add_action('init', array('WP_POS_Reports_Debug_Verification', 'init'));
}

/**
 * Shortcut function para verificar estado rápidamente
 */
function wp_pos_check_js_status() {
    if (!current_user_can('administrator')) {
        return 'Permisos insuficientes';
    }
    
    $checks = array(
        'Files exist' => array(
            'reports-scripts.js' => file_exists(plugin_dir_path(__FILE__) . 'assets/js/reports-scripts.js'),
            'reports-filters.js' => file_exists(plugin_dir_path(__FILE__) . 'assets/js/reports-filters.js'),
        ),
        'WordPress config' => array(
            'SCRIPT_DEBUG' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
            'WP_DEBUG' => defined('WP_DEBUG') && WP_DEBUG,
            'Can manage options' => current_user_can('manage_options'),
        )
    );
    
    return $checks;
}