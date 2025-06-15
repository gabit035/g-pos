/**
 * Configuración del Módulo de Cierres de Cajas
 * 
 * Este archivo permite configurar el comportamiento de las actualizaciones automáticas
 * del módulo de cierres de cajas para evitar actualizaciones no deseadas.
 * 
 * @package WP-POS
 * @subpackage Closures
 * @version 1.0.0
 */

// Configuración global para el módulo de cierres
window.WP_POS_CONFIG = window.WP_POS_CONFIG || {};

/**
 * CONFIGURACIÓN DE ACTUALIZACIÓN AUTOMÁTICA
 * 
 * Modifica estos valores para controlar el comportamiento del módulo:
 */

// ========================================
// CÁLCULO AUTOMÁTICO INICIAL
// ========================================
window.WP_POS_CONFIG.autoCalculate = true;        // true = habilitar, false = deshabilitar
window.WP_POS_CONFIG.autoCalculateDelay = 1000;   // Delay en milisegundos (1000 = 1 segundo)

// ========================================
// LIMPIEZA AUTOMÁTICA DE INDICADORES
// ========================================
window.WP_POS_CONFIG.autoCleanup = true;          // true = habilitar, false = deshabilitar
window.WP_POS_CONFIG.cleanupInterval = 30000;     // Intervalo en milisegundos (30000 = 30 segundos)

// ========================================
// MODO DE DEPURACIÓN
// ========================================
window.WP_POS_CONFIG.debugMode = false;           // true = mostrar logs detallados

/**
 * PRESETS DISPONIBLES
 * 
 * Descomenta UNA de las siguientes líneas para aplicar un preset:
 */

// PRESET 1: Modo Silencioso (Sin actualizaciones automáticas)
// window.WP_POS_CONFIG.autoCalculate = false;
// window.WP_POS_CONFIG.autoCleanup = false;

// PRESET 2: Modo Conservativo (Actualizaciones menos frecuentes)
// window.WP_POS_CONFIG.autoCalculateDelay = 3000;  // 3 segundos
// window.WP_POS_CONFIG.cleanupInterval = 60000;    // 60 segundos

// PRESET 3: Modo Agresivo (Actualizaciones más frecuentes)
// window.WP_POS_CONFIG.autoCalculateDelay = 500;   // 0.5 segundos
// window.WP_POS_CONFIG.cleanupInterval = 10000;    // 10 segundos

/**
 * INFORMACIÓN DE DEBUGGING
 * 
 * Para diagnosticar problemas de actualización automática:
 * 1. Establece debugMode = true
 * 2. Abre la consola del navegador (F12)
 * 3. Observa los mensajes de log que aparecen
 */

// Log de configuración actual (si debug está habilitado)
if (window.WP_POS_CONFIG.debugMode) {
    jQuery(document).ready(function($) {
        console.log('🔧 CONFIGURACIÓN WP-POS CLOSURES:');
        console.log('   - Cálculo automático:', window.WP_POS_CONFIG.autoCalculate ? 'HABILITADO' : 'DESHABILITADO');
        console.log('   - Delay de cálculo:', window.WP_POS_CONFIG.autoCalculateDelay + 'ms');
        console.log('   - Auto-limpieza:', window.WP_POS_CONFIG.autoCleanup ? 'HABILITADA' : 'DESHABILITADA');
        console.log('   - Intervalo limpieza:', window.WP_POS_CONFIG.cleanupInterval + 'ms');
        console.log('   - Modo debug:', window.WP_POS_CONFIG.debugMode ? 'ACTIVO' : 'INACTIVO');
    });
}
