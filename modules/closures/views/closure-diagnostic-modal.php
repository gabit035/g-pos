<?php
/**
 * Modal para diagnóstico de ventas en Cierres de Caja
 *
 * @package WP-POS
 * @subpackage Closures
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) exit;
?>

<div id="diagnostics-modal" class="wp-pos-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
    <div class="wp-pos-modal-content" style="background-color:#fefefe; margin:5% auto; padding:0; border:1px solid #888; width:80%; max-width:800px; border-radius:5px; box-shadow:0 4px 8px rgba(0,0,0,0.2);">
        <div class="wp-pos-modal-header" style="background-color:#2271b1; color:white; padding:10px 20px; display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0;"><span class="dashicons dashicons-chart-bar"></span> Diagnóstico de Ventas</h2>
            <span class="wp-pos-modal-close" style="color:white; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
        </div>
        <div class="wp-pos-modal-body" style="padding:20px; max-height:70vh; overflow-y:auto;">
            <div id="diagnostics-content">
                <div class="wp-pos-loading" style="text-align:center; padding:20px;">
                    <span class="spinner is-active" style="float:none; width:20px; height:20px; margin:0 auto;"></span>
                    <p>Analizando datos de ventas...</p>
                </div>
            </div>
        </div>
        <div class="wp-pos-modal-footer" style="background-color:#f5f5f5; padding:10px 20px; text-align:right; border-top:1px solid #ddd; border-radius:0 0 5px 5px;">
            <button id="close-diagnostics" class="button button-secondary">Cerrar</button>
        </div>
    </div>
</div>
