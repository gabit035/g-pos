<?php
/**
 * Vista principal del mu00f3dulo de Cierres
 *
 * @package WP-POS
 * @subpackage Closures
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;
?>

<div class="wp-pos-closures-container">
    <div class="wp-pos-closures-header">
        <h2><?php _e('Cierre de Caja', 'wp-pos'); ?></h2>
    </div>

    <div class="wp-pos-closures-content">
        <!-- Formulario de Cierre de Caja -->
        <div class="wp-pos-closure-form-container">
            <?php include_once dirname(__FILE__) . '/closure-form.php'; ?>
        </div>
    </div>
</div>
