<?php
/**
 * Plantilla de administración de servicios
 *
 * @package WP-POS
 * @subpackage Services
 */
if (!defined('ABSPATH')) exit;

// Incluir hoja de estilos mejorados
wp_enqueue_style('wp-pos-services-enhanced', WP_POS_PLUGIN_URL . 'modules/services/assets/css/services-enhanced.css', array(), WP_POS_VERSION);

global $wpdb;
$table = $wpdb->prefix . 'pos_services';

$deleted_message = '';
$bulk_message = '';

// Eliminar individual
if (isset($_GET['action'], $_GET['service_id'], $_GET['_wpnonce']) && $_GET['action'] === 'delete') {
    $id = absint($_GET['service_id']);
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_service_' . $id)) {
        $wpdb->delete($table, ['id' => $id]);
        $deleted_message = __('Servicio eliminado correctamente.', 'wp-pos');
    } else {
        $deleted_message = __('Error: Token no válido.', 'wp-pos');
    }
}

// Bulk actions
if (isset($_POST['bulk_action'], $_POST['services'], $_POST['_wpnonce'])) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'bulk_services_action')) {
        if ($_POST['bulk_action'] === 'delete') {
            $ids = array_map('absint', $_POST['services']);
            foreach ($ids as $id) {
                $wpdb->delete($table, ['id' => $id]);
            }
            $bulk_message = __('Servicios eliminados.', 'wp-pos');
        }
    }
}

// Filtros y búsqueda
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$where = '';
if ($search) {
    $where = $wpdb->prepare(" WHERE name LIKE %s", '%' . $wpdb->esc_like($search) . '%');
}

// Paginación
$per_page = 20;
$paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($paged - 1) * $per_page;

$total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table" . $where);
$services = $wpdb->get_results("SELECT * FROM $table" . $where . " ORDER BY name ASC LIMIT $per_page OFFSET $offset", ARRAY_A);

$base_url = admin_url('admin.php?page=wp-pos-services');

?>
<div class="wrap wp-pos-services-wrapper">
    <div class="wp-pos-wrapper wp-pos-services-container">
        <div class="wp-pos-services-header">
            <div class="wp-pos-services-header-primary">
                <h1><?php esc_html_e('Servicios', 'wp-pos'); ?></h1>
                <p><?php esc_html_e('Gestiona los servicios para tu punto de venta.', 'wp-pos'); ?></p>
            </div>
            <div class="wp-pos-control-panel-secondary">
                <a href="<?php echo esc_url(add_query_arg('action', 'add', $base_url)); ?>" class="wp-pos-add-service wp-pos-button">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Agregar servicio', 'wp-pos'); ?>
                </a>
            </div>
        </div>

        <?php if ($deleted_message) : ?>
        <div class="wp-pos-message wp-pos-message-success">
            <span class="dashicons dashicons-yes-alt"></span>
            <div><?php echo esc_html($deleted_message); ?></div>
        </div>
        <?php endif; ?>
        <?php if ($bulk_message) : ?>
        <div class="wp-pos-message wp-pos-message-success">
            <span class="dashicons dashicons-yes-alt"></span>
            <div><?php echo esc_html($bulk_message); ?></div>
        </div>
        <?php endif; ?>

        <div class="wp-pos-search-order-container">
            <form method="get" action="<?php echo esc_url($base_url); ?>">
                <input type="hidden" name="page" value="wp-pos-services">
                <div class="wp-pos-search-order-area">
                    <div class="wp-pos-search-box">
                        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Buscar servicios...', 'wp-pos'); ?>">
                        <button type="submit" class="search-button"><span class="dashicons dashicons-search"></span></button>
                    </div>
                    <div class="wp-pos-order-box">
                        <label for="services-order">Ordenar por:</label>
                        <select id="services-order" name="order">
                            <option value="newest" selected>Fecha (más reciente primero)</option>
                            <option value="oldest">Fecha (más antiguo primero)</option>
                            <option value="name_asc">Nombre (A-Z)</option>
                            <option value="name_desc">Nombre (Z-A)</option>
                            <option value="price_asc">Precio (menor a mayor)</option>
                            <option value="price_desc">Precio (mayor a menor)</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="wp-pos-services-card">
            <div class="wp-pos-services-card-header">
                <h3><span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Lista de servicios', 'wp-pos'); ?></h3>
            </div>
            <div class="wp-pos-services-card-body">
                <form method="post" action="" id="wp-pos-bulk-actions-form">
                    <?php wp_nonce_field('bulk_services_action'); ?>
                    <div class="wp-pos-bulk-actions">
                        <select name="bulk_action" class="wp-pos-bulk-select">
                            <option value=""><?php esc_html_e('Acciones Masivas', 'wp-pos'); ?></option>
                            <option value="delete"><?php esc_html_e('Eliminar', 'wp-pos'); ?></option>
                        </select>
                        <button type="submit" class="wp-pos-bulk-actions-apply" name="do_bulk_action">
                            <span class="dashicons dashicons-yes"></span> <?php esc_html_e('Aplicar', 'wp-pos'); ?>
                        </button>
                    </div>
                    <table class="wp-pos-services-table wp-list-table widefat fixed striped posts" id="wp-pos-services-table">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input id="wp-pos-select-all" type="checkbox" class="wp-pos-checkbox">
                                </td>
                                <th class="manage-column column-name">
                                    <span class="dashicons dashicons-portfolio"></span> <?php esc_html_e('Nombre', 'wp-pos'); ?>
                                </th>
                                <th class="manage-column column-price">
                                    <span class="dashicons dashicons-money-alt"></span> <?php esc_html_e('Precio de venta', 'wp-pos'); ?>
                                </th>
                                <th class="manage-column column-actions">
                                    <span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('Acciones', 'wp-pos'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($services) : foreach ($services as $service) : ?>
                            <tr>
                                <td class="wp-pos-checkbox-column">
                                    <input type="checkbox" name="services[]" value="<?php echo esc_attr($service['id']); ?>" class="wp-pos-service-checkbox">
                                </td>
                                <td class="wp-pos-service-name">
                                    <strong><?php echo esc_html($service['name']); ?></strong>
                                </td>
                                <td class="wp-pos-service-price">
                                    <span class="wp-pos-price-tag"><?php echo wp_pos_format_price($service['sale_price']); ?></span>
                                </td>
                                <td class="wp-pos-service-actions">
                                    <div class="wp-pos-action-buttons">
                                        <a href="<?php echo esc_url(add_query_arg(['action'=>'edit','service_id'=>$service['id']], $base_url)); ?>" class="wp-pos-edit-button">
                                            <span class="dashicons dashicons-edit"></span> <?php esc_html_e('Editar', 'wp-pos'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action'=>'delete','service_id'=>$service['id']], $base_url), 'delete_service_' . $service['id'])); ?>" class="wp-pos-delete-button wp-pos-service-delete">
                                            <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Eliminar', 'wp-pos'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr class="wp-pos-no-items">
                                <td colspan="4">
                                    <div class="wp-pos-no-items-message">
                                        <span class="dashicons dashicons-info"></span>
                                        <p><?php esc_html_e('No hay servicios disponibles.', 'wp-pos'); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($total > $per_page) : 
                        $total_pages = ceil($total / $per_page);
                        $pagination_base_url = remove_query_arg('paged', $base_url);
                        if (!empty($search)) {
                            $pagination_base_url = add_query_arg('s', $search, $pagination_base_url);
                        }
                        // Rango para mostrar en páginas
                        $range = 2;
                    ?>
                    <div class="wp-pos-pagination">
                        <div class="tablenav-pages">
                            <!-- Primera página -->
                            <?php if ($paged > 3) : 
                                $first_page_url = add_query_arg('paged', 1, $pagination_base_url); 
                            ?>
                            <a href="<?php echo esc_url($first_page_url); ?>" class="page-numbers first-page" title="<?php esc_attr_e('Primera página', 'wp-pos'); ?>">
                                <span class="dashicons dashicons-controls-skipback"></span>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Anterior -->
                            <?php if ($paged > 1) : 
                                $prev_page_url = add_query_arg('paged', $paged - 1, $pagination_base_url); 
                            ?>
                            <a href="<?php echo esc_url($prev_page_url); ?>" class="prev page-numbers" title="<?php esc_attr_e('Página anterior', 'wp-pos'); ?>">
                                <span class="dashicons dashicons-arrow-left-alt2"></span>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Mostrar puntos suspensivos al principio si es necesario -->
                            <?php if ($paged > $range + 1) : 
                                $first_url = add_query_arg('paged', 1, $pagination_base_url);
                            ?>
                            <a href="<?php echo esc_url($first_url); ?>" class="page-numbers">1</a>
                            <?php if ($paged > $range + 2) : ?>
                                <span class="page-numbers dots">...</span>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- Páginas numeradas -->
                            <?php
                            $start = max(1, $paged - $range);
                            $end = min($total_pages, $paged + $range);
                            
                            for ($i = $start; $i <= $end; $i++) :
                                $is_current = $i === $paged;
                                $page_url = add_query_arg('paged', $i, $pagination_base_url);
                            ?>
                                <a href="<?php echo esc_url($page_url); ?>" class="page-numbers <?php echo $is_current ? 'current' : ''; ?>" <?php echo $is_current ? 'aria-current="page"' : ''; ?>>
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <!-- Mostrar puntos suspensivos al final si es necesario -->
                            <?php if ($paged < $total_pages - $range) : 
                                $last_url = add_query_arg('paged', $total_pages, $pagination_base_url);
                            ?>
                                <?php if ($paged < $total_pages - $range - 1) : ?>
                                    <span class="page-numbers dots">...</span>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($last_url); ?>" class="page-numbers"><?php echo $total_pages; ?></a>
                            <?php endif; ?>
                            
                            <!-- Siguiente -->
                            <?php if ($paged < $total_pages) : 
                                $next_page_url = add_query_arg('paged', $paged + 1, $pagination_base_url); 
                            ?>
                            <a href="<?php echo esc_url($next_page_url); ?>" class="next page-numbers" title="<?php esc_attr_e('Página siguiente', 'wp-pos'); ?>">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Última página -->
                            <?php if ($paged < $total_pages - 2) : 
                                $last_page_url = add_query_arg('paged', $total_pages, $pagination_base_url); 
                            ?>
                            <a href="<?php echo esc_url($last_page_url); ?>" class="page-numbers last-page" title="<?php esc_attr_e('Última página', 'wp-pos'); ?>">
                                <span class="dashicons dashicons-controls-skipforward"></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div> <!-- .wp-pos-services-container -->
</div>

<script>
jQuery(document).ready(function($) {
    // Seleccionar/deseleccionar todos los checkboxes
    $('#wp-pos-select-all').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.wp-pos-service-checkbox').prop('checked', isChecked);
    });
    
    // Confirmar eliminación de servicio individual
    $('.wp-pos-service-delete').on('click', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr('href');
        
        if (confirm('<?php echo esc_js(__('¿Estás seguro de que deseas eliminar este servicio?', 'wp-pos')); ?>')) {
            window.location.href = deleteUrl;
        }
    });
    
    // Confirmar acción en masa antes de enviar
    $('#wp-pos-bulk-actions-form').on('submit', function(e) {
        var action = $('select[name="bulk_action"]').val();
        var checkedItems = $('.wp-pos-service-checkbox:checked').length;
        
        if (action === '' || checkedItems === 0) {
            e.preventDefault();
            alert('<?php echo esc_js(__('Por favor, seleccione una acción y al menos un servicio.', 'wp-pos')); ?>');
            return false;
        }
        
        if (action === 'delete') {
            if (!confirm('<?php echo esc_js(__('¿Está seguro de que desea eliminar los servicios seleccionados? Esta acción no se puede deshacer.', 'wp-pos')); ?>')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
