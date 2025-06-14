<?php
/**
 * Shared header for all closure-related pages
 *
 * @package WP-POS
 * @subpackage Closures
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Get current view
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'dashboard';
?>

<div class="wp-pos-closures-header">
    <div class="wp-pos-closures-header-primary">
        <h1><?php _e('Cierres de Caja', 'wp-pos'); ?></h1>
        <p><?php _e('Gestiona los cierres de caja para tu punto de venta.', 'wp-pos'); ?></p>
    </div>
    <div class="wp-pos-control-panel-secondary">
        <a href="admin.php?page=wp-pos-closures&view=form" class="wp-pos-add-closure wp-pos-button">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Nuevo cierre', 'wp-pos'); ?></a>
    </div>
</div>

<!-- Page Header -->
<div class="wp-pos-page-header">
    <div class="wp-pos-page-header-content">
        <h1 class="wp-pos-page-title">
            <span class="dashicons dashicons-money-alt"></span>
            <?php _e('Cierres de Caja', 'wp-pos'); ?>
        </h1>
        <div class="wp-pos-page-actions">
            <button id="refresh-page" class="button button-secondary">
                <span class="dashicons dashicons-update"></span> <?php _e('Actualizar', 'wp-pos'); ?>
            </button>
            <?php if ($current_view !== 'form') : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-module-closures&view=form')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus"></span> <?php _e('Nuevo Cierre', 'wp-pos'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <nav class="wp-pos-view-navigation">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-closures&view=dashboard')); ?>" class="button <?php echo $current_view === 'dashboard' ? 'button-primary' : 'button-secondary'; ?>">
            <span class="dashicons dashicons-chart-bar"></span> <?php _e('Dashboard', 'wp-pos'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-closures&view=form')); ?>" class="button <?php echo $current_view === 'form' ? 'button-primary' : 'button-secondary'; ?>">
            <span class="dashicons dashicons-money-alt"></span> <?php _e('Crear cierre', 'wp-pos'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-closures&view=history')); ?>" class="button <?php echo $current_view === 'history' ? 'button-primary' : 'button-secondary'; ?>">
            <span class="dashicons dashicons-list-view"></span> <?php _e('Historial de cierres', 'wp-pos'); ?>
        </a>
    </nav>

    <!-- Current Date -->
    <div class="wp-pos-current-date">
        <span class="dashicons dashicons-calendar"></span> <?php echo date_i18n('l, j F Y', current_time('timestamp')); ?>
    </div>

</div>