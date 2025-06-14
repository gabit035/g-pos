<?php
/**
 * Plantilla para la pu00e1gina de administraciu00f3n de permisos
 *
 * @package WP-POS
 * @since 1.1.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('Gestiu00f3n de Permisos POS', 'wp-pos'); ?></h1>
    
    <?php settings_errors('wp_pos_permissions'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('wp_pos_save_permissions', 'wp_pos_permissions_nonce'); ?>
        
        <p><?php echo esc_html__('Configura quu00e9 permisos tiene cada rol en el sistema POS.', 'wp-pos'); ?></p>
        
        <div class="wp-pos-tabs">
            <div class="wp-pos-tabs-navigation">
                <ul>
                    <?php $first = true; ?>
                    <?php foreach ($roles as $role_id => $role_data) : ?>
                        <?php if ($role_id === 'administrator') continue; // No mostrar administradores pues tienen todo ?>
                        <li>
                            <a href="#tab-<?php echo esc_attr($role_id); ?>" class="<?php echo $first ? 'active' : ''; ?>">
                                <?php echo esc_html($role_data['name']); ?>
                            </a>
                        </li>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="wp-pos-tabs-content">
                <?php $first = true; ?>
                <?php foreach ($roles as $role_id => $role_data) : ?>
                    <?php if ($role_id === 'administrator') continue; // Saltar administradores ?>
                    <div id="tab-<?php echo esc_attr($role_id); ?>" class="wp-pos-tab-panel <?php echo $first ? 'active' : ''; ?>">
                        <h2><?php echo sprintf(esc_html__('Permisos para: %s', 'wp-pos'), $role_data['name']); ?></h2>
                        
                        <table class="form-table wp-pos-permissions-table">
                            <thead>
                                <tr>
                                    <th class="permission-area"><?php echo esc_html__('u00c1rea', 'wp-pos'); ?></th>
                                    <th class="permission-description"><?php echo esc_html__('Descripciu00f3n', 'wp-pos'); ?></th>
                                    <th class="permission-capabilities"><?php echo esc_html__('Capacidades', 'wp-pos'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (WP_POS_Permissions::$capability_groups as $group_id => $group) : ?>
                                    <tr>
                                        <th scope="row"><?php echo esc_html($group['title']); ?></th>
                                        <td><?php echo esc_html($group['description']); ?></td>
                                        <td>
                                            <label>
                                                <input type="checkbox"
                                                       name="wp_pos_role_permissions[<?php echo esc_attr($role_id); ?>][<?php echo esc_attr($group['default']); ?>]"
                                                       <?php checked(isset($wp_roles->roles[$role_id]['capabilities'][$group['default']]) && $wp_roles->roles[$role_id]['capabilities'][$group['default']]); ?>
                                                >
                                                <?php echo esc_html__('Acceso', 'wp-pos'); ?>
                                            </label>
                                            
                                            <?php if (isset($group['capabilities'])) : ?>
                                                <div class="additional-capabilities">
                                                    <strong><?php echo esc_html__('Capacidades adicionales:', 'wp-pos'); ?></strong>
                                                    <ul>
                                                        <?php foreach ($group['capabilities'] as $cap => $label) : ?>
                                                            <li>
                                                                <label>
                                                                    <input type="checkbox"
                                                                           name="wp_pos_role_permissions[<?php echo esc_attr($role_id); ?>][<?php echo esc_attr($cap); ?>]"
                                                                           <?php checked(isset($wp_roles->roles[$role_id]['capabilities'][$cap]) && $wp_roles->roles[$role_id]['capabilities'][$cap]); ?>
                                                                    >
                                                                    <?php echo esc_html($label); ?>
                                                                </label>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php $first = false; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php submit_button(__('Guardar Permisos', 'wp-pos')); ?>
    </form>
</div>

<style>
/* Estilos para la interfaz de permisos */
.wp-pos-tabs {
    margin-top: 20px;
}

.wp-pos-tabs-navigation ul {
    display: flex;
    border-bottom: 1px solid #ccc;
    margin: 0;
    padding: 0;
    list-style: none;
}

.wp-pos-tabs-navigation li {
    margin-bottom: -1px;
}

.wp-pos-tabs-navigation a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    border: 1px solid transparent;
    border-bottom: none;
    margin-right: 5px;
    color: #555;
    font-weight: 600;
}

.wp-pos-tabs-navigation a.active {
    border-color: #ccc;
    background: #fff;
    border-bottom: 1px solid #fff;
}

.wp-pos-tab-panel {
    display: none;
    padding: 20px;
    border: 1px solid #ccc;
    border-top: none;
}

.wp-pos-tab-panel.active {
    display: block;
}

.wp-pos-permissions-table {
    width: 100%;
}

.wp-pos-permissions-table .permission-area {
    width: 20%;
}

.wp-pos-permissions-table .permission-description {
    width: 30%;
}

.wp-pos-permissions-table .permission-capabilities {
    width: 50%;
}

.additional-capabilities {
    margin-top: 10px;
    padding-left: 10px;
    border-left: 3px solid #f0f0f0;
}

.additional-capabilities ul {
    margin: 10px 0 0 0;
}

.additional-capabilities li {
    margin-bottom: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Manejo de tabs
    $('.wp-pos-tabs-navigation a').on('click', function(e) {
        e.preventDefault();
        
        // Desactivar todas las tabs
        $('.wp-pos-tabs-navigation a').removeClass('active');
        $('.wp-pos-tab-panel').removeClass('active');
        
        // Activar la tab seleccionada
        $(this).addClass('active');
        $($(this).attr('href')).addClass('active');
    });
});
</script>
