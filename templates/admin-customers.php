<?php
/**
 * Plantilla de Gestiu00f3n de Clientes
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluir archivos necesarios
require_once WP_POS_PLUGIN_DIR . '/modules/customers/controllers/class-pos-customers-controller.php';
require_once WP_POS_PLUGIN_DIR . '/includes/helpers/customer-helper.php';

// Inicializar el controlador de clientes
$controller = new WP_POS_Customers_Controller();

// Procesar formularios si se han enviado
$message = '';
$message_type = '';

// Procesar creación/actualización de cliente
if (isset($_POST['wp_pos_save_customer']) && isset($_POST['wp_pos_customer_nonce']) && 
    wp_verify_nonce($_POST['wp_pos_customer_nonce'], 'wp_pos_save_customer')) {
    
    $customer_data = array(
        'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
        'first_name' => isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '',
        'last_name' => isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '',
        'dni' => isset($_POST['dni']) ? sanitize_text_field($_POST['dni']) : '',
        'birth_date' => isset($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : '',
        'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '',
        'address' => isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '',
        'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : ''
    );
    
    if (isset($_POST['customer_id']) && !empty($_POST['customer_id'])) {
        // Actualizar cliente existente
        $customer_id = intval($_POST['customer_id']);
        
        // Guardar metadatos manualmente - campos adicionales como DNI, fecha nacimiento, etc.
        // Guardar directamente cada campo para asegurar que se guarde correctamente
        if (isset($customer_data['dni'])) {
            update_user_meta($customer_id, 'dni', sanitize_text_field($customer_data['dni']));
        }
        
        if (isset($customer_data['birth_date'])) {
            update_user_meta($customer_id, 'birth_date', sanitize_text_field($customer_data['birth_date']));
        }
        
        if (isset($customer_data['phone'])) {
            update_user_meta($customer_id, 'billing_phone', sanitize_text_field($customer_data['phone']));
        }
        
        if (isset($customer_data['address'])) {
            update_user_meta($customer_id, 'billing_address_1', sanitize_textarea_field($customer_data['address']));
        }
        
        if (isset($customer_data['notes'])) {
            update_user_meta($customer_id, '_wp_pos_customer_notes', sanitize_textarea_field($customer_data['notes']));
        }

        // Actualizar nombre y apellido directamente (estos son campos estándar de WordPress)
        $userdata = array(
            'ID' => $customer_id,
            'first_name' => $customer_data['first_name'],
            'last_name' => $customer_data['last_name']
        );
        
        // Actualizar email si cambió
        $user = get_user_by('ID', $customer_id);
        if ($user && $user->user_email !== $customer_data['email']) {
            $userdata['user_email'] = $customer_data['email'];
        }

        // Actualizar datos del usuario
        $update_result = wp_update_user($userdata);
        
        // Verificar resultado y mostrar mensaje apropiado
        if (!is_wp_error($update_result)) {
            $message = __('Cliente actualizado correctamente.', 'wp-pos');
            $message_type = 'success';
            $view = 'list'; // Volver a la vista de lista después de actualizar
        } else {
            $message = $update_result->get_error_message();
            $message_type = 'error';
        }
    } else {
        // Crear nuevo cliente - usamos un enfoque directo
        // Preparar datos básicos del usuario
        $userdata = array(
            'user_login' => sanitize_user($customer_data['email']),
            'user_email' => $customer_data['email'],
            'first_name' => $customer_data['first_name'],
            'last_name' => $customer_data['last_name'],
            'role' => 'pos_customer', // Usar el rol especu00edfico de POS
            'user_pass' => isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : wp_generate_password(12, true, true)
        );
        
        // Insertar el usuario en la base de datos
        $customer_id = wp_insert_user($userdata);
        
        // Verificar si se creó correctamente
        if (!is_wp_error($customer_id)) {
            // Ahora guardar los metadatos adicionales
            if (isset($customer_data['dni'])) {
                update_user_meta($customer_id, 'dni', sanitize_text_field($customer_data['dni']));
            }
            
            if (isset($customer_data['birth_date'])) {
                update_user_meta($customer_id, 'birth_date', sanitize_text_field($customer_data['birth_date']));
            }
            
            // Guardar telu00e9fono en ambos lugares para asegurar compatibilidad
            $phone = isset($customer_data['phone']) ? sanitize_text_field($customer_data['phone']) : '';
            update_user_meta($customer_id, 'billing_phone', $phone);
            update_user_meta($customer_id, 'phone', $phone); // Guardar tambin en 'phone' para doble seguridad
            
            // Guardar dirección en ambas ubicaciones para asegurar compatibilidad
            $address = isset($customer_data['address']) ? sanitize_textarea_field($customer_data['address']) : '';
            update_user_meta($customer_id, 'billing_address_1', $address);
            update_user_meta($customer_id, 'address', $address); // Guardar tambiu00e9n en 'address' para doble seguridad
            
            // Asegurar que las notas siempre se guarden, incluso si están vacías
            update_user_meta($customer_id, '_wp_pos_customer_notes', isset($customer_data['notes']) ? sanitize_textarea_field($customer_data['notes']) : '');
            
            $message = __('Cliente creado correctamente.', 'wp-pos');
            $message_type = 'success';
            $view = 'list'; // Volver a la vista de lista después de crear
        } else {
            $message = $customer_id->get_error_message();
            $message_type = 'error';
        }
    }
}

// Procesar eliminación de cliente
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['customer_id']) && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_customer_' . $_GET['customer_id'])) {
        $customer_id = intval($_GET['customer_id']);
        
        // Eliminar usuario sin trasladar contenido
        $result = wp_delete_user($customer_id);
        
        if ($result) {
            $message = __('Cliente eliminado correctamente.', 'wp-pos');
            $message_type = 'success';
        } else {
            $message = __('Error al eliminar el cliente.', 'wp-pos');
            $message_type = 'error';
        }
    }
}

// Mensajes basados en parámetros GET (redirecciones)
if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $message = __('Cliente actualizado correctamente.', 'wp-pos');
    $message_type = 'success';
} elseif (isset($_GET['created']) && $_GET['created'] === '1') {
    $message = __('Cliente creado correctamente.', 'wp-pos');
    $message_type = 'success';
}

// Determinar la vista actual
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Cargar header
wp_pos_template_header(array(
    'title' => __('Gestiu00f3n de Clientes', 'wp-pos'),
    'active_menu' => 'customers'
));

// Configuración de paginación
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;  // Número de clientes por página

// Cargar clientes
$customers = array();
try {
    // Usar el controlador para buscar clientes
    $args = array(
        'search' => $search_term,
        'paged' => $current_page,
        'per_page' => $per_page,
        'orderby' => 'name',
        'order' => 'ASC'
    );
    
    $results = $controller->search_customers($args);
    $customers = isset($results['customers']) ? $results['customers'] : array();
    $total_customers = isset($results['total_customers']) ? $results['total_customers'] : 0;
    
    if (empty($customers)) {
        // Fallback a consulta directa si el controlador no devuelve resultados
        global $wpdb;
        
        // Parámetros de paginación para WP_User_Query
        $offset = ($current_page - 1) * $per_page;
        
        // Primero contar el total de usuarios (clientes)
        $count_args = array(
            'role__in' => array('pos_customer', 'customer'), // Incluir ambos roles para compatibilidad
            'role__not_in' => array('administrator', 'pos_manager', 'pos_seller', 'editor', 'author', 'contributor'),
            'count_total' => true,
            'fields' => 'ids',
        );
        if (!empty($search_term)) {
            $count_args['search'] = '*' . $search_term . '*';
        }
        $count_query = new WP_User_Query($count_args);
        $total_customers = $count_query->get_total();
        
        // Directamente consultar la base de datos para obtener usuarios paginados
        $user_args = array(
            'role__in' => array('pos_customer', 'customer'), // Incluir ambos roles para compatibilidad
            'role__not_in' => array('administrator', 'pos_manager', 'pos_seller', 'editor', 'author', 'contributor'),
            'number' => $per_page,
            'offset' => $offset,
            'orderby' => 'display_name',
            'order' => 'ASC'
        );
        if (!empty($search_term)) {
            $user_args['search'] = '*' . $search_term . '*';
        }
        $user_query = new WP_User_Query($user_args);
        $users = $user_query->get_results();
        
        // Convertir a nuestro formato de clientes
        foreach ($users as $user) {
            // Obtener metadatos específicos - traemos explícitamente cada campo
            $dni = get_user_meta($user->ID, 'dni', true);
            $birth_date = get_user_meta($user->ID, 'birth_date', true);
            $phone = get_user_meta($user->ID, 'billing_phone', true);
            $address = get_user_meta($user->ID, 'billing_address_1', true);
            
            // Asegurar que incluso si un campo es vacío, la estructura se mantiene
            $customers[] = array(
                'id' => $user->ID,
                'first_name' => get_user_meta($user->ID, 'first_name', true),
                'last_name' => get_user_meta($user->ID, 'last_name', true),
                'full_name' => $user->display_name,
                'email' => $user->user_email,
                'dni' => $dni,  // Campo explícito para DNI
                'birth_date' => $birth_date,  // Campo explícito para fecha de nacimiento
                'billing' => array(
                    'phone' => $phone,  // Campo explícito para teléfono
                    'address_1' => $address  // Campo explícito para dirección
                )
            );
        }
    }
} catch (Exception $e) {
    $message = 'Error al cargar clientes: ' . $e->getMessage();
    $message_type = 'error';
}

// Obtener cliente individual si estamos en vista de ediciu00f3n
$editing_customer = null;
if ($view === 'edit' && isset($_GET['customer_id'])) {
    $customer_id = intval($_GET['customer_id']);
    $user = get_user_by('ID', $customer_id);
    if ($user) {
        $editing_customer = array(
            'id' => $user->ID,
            'full_name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'email' => $user->user_email,
            'dni' => get_user_meta($user->ID, 'dni', true),
            'birth_date' => get_user_meta($user->ID, 'birth_date', true),
            'phone' => get_user_meta($user->ID, 'billing_phone', true),
            'address' => get_user_meta($user->ID, 'billing_address_1', true),
            'notes' => get_user_meta($user->ID, 'customer_notes', true)
        );
    } else {
        $message = 'Cliente no encontrado';
        $message_type = 'error';
        $view = 'list';
    }
}
?>

<!-- Estilos CSS con el mismo estilo visual que la pu00e1gina de Nueva Venta -->
<style type="text/css">
    /* Estilos para la interfaz de clientes */
    .wp-pos-admin-wrapper {
        max-width: 1200px;
        margin: 20px auto 0;
    }
    
    /* Panel de control con degradado */
    .wp-pos-control-panel {
        background: linear-gradient(135deg, #3a6186, #89253e);
        border-radius: 8px;
        margin-bottom: 20px;
        padding: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .wp-pos-control-panel-primary h3 {
        color: #ffffff !important;
        font-size: 22px;
        margin: 0 0 5px 0;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }
    
    .wp-pos-control-panel-primary p {
        color: rgba(255,255,255,0.9) !important;
        margin: 0;
        font-size: 13px;
    }
    
    /* Botones y acciones */
    .wp-pos-button {
        background-color: rgba(255,255,255,0.2);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.3);
        padding: 8px 16px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        text-decoration: none;
        cursor: pointer;
    }
    
    .wp-pos-button:hover {
        background-color: rgba(255,255,255,0.3);
    }
    
    .wp-pos-button .dashicons {
        margin-right: 5px;
    }
    
    /* Header con acciones */
    .wp-pos-header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    /* Buscador elegante */
    .wp-pos-search-container {
        flex: 0 0 350px;
    }
    
    .wp-pos-search-form {
        margin: 0;
    }
    
    .wp-pos-search-input-group {
        display: flex;
        position: relative;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(108, 92, 231, 0.2);
        transition: all 0.3s ease;
    }
    
    .wp-pos-search-input-group:focus-within {
        box-shadow: 0 3px 12px rgba(108, 92, 231, 0.15);
        border-color: rgba(108, 92, 231, 0.4);
    }
    
    .wp-pos-search-input {
        flex: 1;
        border: none !important;
        padding: 10px 15px !important;
        background: #fff !important;
        font-size: 14px;
        color: #333;
        width: 100%;
        outline: none;
    }
    
    .wp-pos-search-input::placeholder {
        color: #999;
        font-style: italic;
    }
    
    .wp-pos-search-button {
        background: #6c5ce7;
        border: none;
        color: white;
        padding: 0;
        width: 40px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease;
    }
    
    .wp-pos-search-button:hover {
        background: #5649c0;
    }
    
    .wp-pos-search-button .dashicons {
        font-size: 18px;
        width: 18px;
        height: 18px;
    }
    
    /* Tabla de clientes */
    .wp-pos-table-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 20px;
        border-left: 3px solid #6c5ce7; /* Color pu00farpura para clientes */
    }
    
    .wp-pos-customers-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .wp-pos-customers-table th {
        text-align: left;
        padding: 12px 15px;
        background-color: #f5f0ff; /* Tono pu00farpura claro */
        color: #6c5ce7;
        font-weight: 600;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .wp-pos-customers-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
    }
    
    .wp-pos-customers-table tr:hover {
        background-color: #f9f9f9;
    }
    
    /* Mensajes e informaciu00f3n */
    .wp-pos-message {
        padding: 12px 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }
    
    .wp-pos-message-error {
        background-color: #ffeaea;
        border-left: 3px solid #e74c3c;
        color: #c0392b;
    }
    
    .wp-pos-message-success {
        background-color: #e7f5e5;
        border-left: 3px solid #2ecc71;
        color: #27ae60;
    }
    
    /* Estado vacu00edo */
    .wp-pos-empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    
    .wp-pos-empty-state .dashicons {
        font-size: 48px;
        width: 48px;
        height: 48px;
        color: #6c5ce7;
        margin-bottom: 15px;
    }
    
    .wp-pos-empty-state p {
        margin: 5px 0;
        color: #666;
    }
    
    .wp-pos-empty-state .wp-pos-button {
        margin: 15px auto 0;
        display: inline-flex;
        background-color: #6c5ce7;
    }
    
    /* Acciones en tabla */
    .wp-pos-actions-cell {
        display: flex;
        gap: 10px;
    }
    
    .wp-pos-action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 4px;
        color: #555;
        text-decoration: none;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
    }
    
    .wp-pos-action-link:hover {
        background-color: #f0f0f0;
    }
    
    .wp-pos-delete-link {
        color: #e74c3c;
    }
    
    .wp-pos-delete-link:hover {
        background-color: #ffeaea;
        border-color: #e74c3c;
    }
    
    /* Formulario de cliente */
    .wp-pos-form-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
        border-left: 3px solid #6c5ce7;
    }
    
    .wp-pos-form-section {
        margin-bottom: 20px;
    }
    
    .wp-pos-form-section h3 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        color: #6c5ce7;
    }
    
    .wp-pos-form-row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .wp-pos-form-col {
        padding: 0 10px;
        margin-bottom: 15px;
        flex: 1 0 300px;
    }
    
    .wp-pos-form-field {
        margin-bottom: 15px;
    }
    
    .wp-pos-form-field label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .wp-pos-form-field input,
    .wp-pos-form-field textarea,
    .wp-pos-form-field select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .wp-pos-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    .wp-pos-form-actions .wp-pos-button {
        background-color: #6c5ce7;
        color: white;
        border: none;
    }
    
    .wp-pos-form-actions .wp-pos-button-secondary {
        background-color: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }
    
    /* Estilos de paginación */
    .wp-pos-pagination {
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .wp-pos-pagination-inner ul.page-numbers {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .wp-pos-pagination-inner .page-numbers li {
        margin: 0 3px;
    }
    
    .wp-pos-pagination-inner .page-numbers a,
    .wp-pos-pagination-inner .page-numbers span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 30px;
        background: #f5f0ff;
        color: #6c5ce7;
        text-decoration: none;
        border-radius: 4px;
        border: 1px solid rgba(108, 92, 231, 0.2);
        font-weight: 500;
        padding: 0 5px;
    }
    
    .wp-pos-pagination-inner .page-numbers span.current {
        background: #6c5ce7;
        color: white;
    }
    
    .wp-pos-pagination-inner .page-numbers a:hover {
        background: #e4dcff;
    }
    
    .wp-pos-pagination-info {
        color: #666;
        font-size: 13px;
    }
</style>

<div class="wp-pos-admin-wrapper wp-pos-customers-wrapper">
    <!-- Panel de control con el estilo visual que le gustu00f3 al usuario -->
    <div class="wp-pos-control-panel">
        <div class="wp-pos-control-panel-primary">
            <h3><span class="dashicons dashicons-admin-users"></span> <?php _e('Gestión de Clientes', 'wp-pos'); ?></h3>
            <p><?php _e('Visualiza, crea y modifica la información de tus clientes.', 'wp-pos'); ?></p>
        </div>
        
        <div class="wp-pos-control-panel-secondary">
            <a href="<?php echo admin_url('admin.php?page=wp-pos-customers&view=new'); ?>" class="wp-pos-button">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Nuevo Cliente', 'wp-pos'); ?>
            </a>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="wp-pos-message wp-pos-message-<?php echo esc_attr($message_type); ?>">
        <span class="dashicons dashicons-<?php echo $message_type == 'error' ? 'warning' : 'yes'; ?>"></span>
        <div class="wp-pos-message-content"><?php echo esc_html($message); ?></div>
    </div>
    <?php endif; ?>

    <?php if ($view === 'list'): /* VISTA DE LISTA - Muestra todos los clientes */ ?>
    
    <!-- Contenido principal - Lista de clientes -->
    <div class="wp-pos-content-area">
        <div class="wp-pos-header-actions">
            <h1><?php _e('Clientes', 'wp-pos'); ?></h1>
            
            <!-- Buscador elegante -->
            <div class="wp-pos-search-container">
                <form method="get" action="<?php echo admin_url('admin.php'); ?>" class="wp-pos-search-form">
                    <input type="hidden" name="page" value="wp-pos-customers">
                    <div class="wp-pos-search-input-group">
                        <input type="text" name="search" value="<?php echo esc_attr($search_term); ?>" placeholder="<?php esc_attr_e('Buscar por nombre, email o DNI...', 'wp-pos'); ?>" class="wp-pos-search-input">
                        <button type="submit" class="wp-pos-search-button">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabla de clientes -->
        <div class="wp-pos-table-container">
            <table class="wp-pos-customers-table">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'wp-pos'); ?></th>
                        <th><?php _e('Nombre', 'wp-pos'); ?></th>
                        <th><?php _e('Email', 'wp-pos'); ?></th>
                        <th><?php _e('Rol', 'wp-pos'); ?></th>
                        <th><?php _e('DNI', 'wp-pos'); ?></th>
                        <th><?php _e('Teléfono', 'wp-pos'); ?></th>
                        <th><?php _e('Fecha de Nacimiento', 'wp-pos'); ?></th>
                        <th><?php _e('Acciones', 'wp-pos'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" class="wp-pos-no-items">
                            <div class="wp-pos-empty-state">
                                <span class="dashicons dashicons-businessman"></span>
                                <p><?php _e('No se encontraron clientes', 'wp-pos'); ?></p>
                                <p><?php _e('Crea tu primer cliente para comenzar a gestionar tus ventas.', 'wp-pos'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=wp-pos-customers&view=new'); ?>" class="wp-pos-button">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <?php _e('Nuevo Cliente', 'wp-pos'); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo esc_html($customer['id']); ?></td>
                            <td><strong><?php echo esc_html($customer['full_name']); ?></strong></td>
                            <td><?php echo esc_html($customer['email']); ?></td>
                            <td>
                                <?php
                                // Obtener el rol del usuario y formatearlo
                                $user = get_user_by('id', $customer['id']);
                                $roles = $user ? $user->roles : array();
                                $role = !empty($roles) ? reset($roles) : '';
                                
                                // Aplicar el filtro para mostrar un nombre amigable
                                $role_display = apply_filters('wp_pos_format_user_role', $role);
                                echo esc_html($role_display);
                                ?>
                            </td>
                            <td>
                                <?php 
                                // Aseguramos que el DNI se muestre correctamente obteniendo directamente del meta
                                $dni = get_user_meta($customer['id'], 'dni', true);
                                echo !empty($dni) ? esc_html($dni) : '—'; 
                                ?>
                            </td>
                            <td><?php echo isset($customer['billing']['phone']) ? esc_html($customer['billing']['phone']) : '—'; ?></td>
                            <td>
                                <?php 
                                // Aseguramos que la fecha de nacimiento se muestre correctamente
                                $birth_date = get_user_meta($customer['id'], 'birth_date', true);
                                echo !empty($birth_date) ? esc_html($birth_date) : '—'; 
                                ?>
                            </td>
                            <td class="wp-pos-actions-cell">
                                <a href="<?php echo admin_url('admin.php?page=wp-pos-customers&view=edit&customer_id=' . esc_attr($customer['id'])); ?>" class="wp-pos-action-link" title="<?php esc_attr_e('Editar cliente', 'wp-pos'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wp-pos-customers&action=delete&customer_id=' . esc_attr($customer['id'])), 'delete_customer_' . $customer['id']); ?>" class="wp-pos-action-link wp-pos-delete-link" title="<?php esc_attr_e('Eliminar cliente', 'wp-pos'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if (!empty($total_customers) && $total_customers > $per_page): ?>
        <div class="wp-pos-pagination">
            <?php
            $total_pages = ceil($total_customers / $per_page);
            $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $current_page,
                'type' => 'list'
            ));
            
            // Mostrar la paginación si hay más de una página
            if ($page_links) {
                echo '<div class="wp-pos-pagination-inner">';
                echo $page_links;
                echo '</div>';
            }
            ?>
            <div class="wp-pos-pagination-info">
                <?php printf(__('Mostrando %1$s-%2$s de %3$s clientes', 'wp-pos'), 
                    ($current_page - 1) * $per_page + 1,
                    min($current_page * $per_page, $total_customers),
                    $total_customers); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php elseif ($view === 'edit' || $view === 'new'): /* VISTA DE EDICIu00d3N/CREACIu00d3N */ ?>
    
    <!-- Contenido principal - Formulario de cliente -->
    <div class="wp-pos-content-area">
        <div class="wp-pos-header-actions">
            <h1><?php echo $view === 'new' ? __('Crear Nuevo Cliente', 'wp-pos') : __('Editar Cliente', 'wp-pos'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=wp-pos-customers'); ?>" class="wp-pos-button wp-pos-button-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span> 
                <?php _e('Volver a la lista', 'wp-pos'); ?>
            </a>
        </div>
        
        <div class="wp-pos-form-container">
            <form method="post" action="<?php echo admin_url('admin.php?page=wp-pos-customers'); ?>" id="wp-pos-customer-form">
                <?php wp_nonce_field('wp_pos_save_customer', 'wp_pos_customer_nonce'); ?>
                
                <?php if ($view === 'edit' && $editing_customer): ?>
                    <input type="hidden" name="customer_id" value="<?php echo esc_attr($editing_customer['id']); ?>" />
                <?php endif; ?>
                
                <div class="wp-pos-form-section">
                    <h3><?php _e('Información Personal', 'wp-pos'); ?></h3>
                    
                    <div class="wp-pos-form-row">
                        <div class="wp-pos-form-col">
                            <div class="wp-pos-form-field">
                                <label for="first_name"><?php _e('Nombre', 'wp-pos'); ?></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo $editing_customer ? esc_attr($editing_customer['first_name']) : ''; ?>" required />
                            </div>
                        </div>
                        
                        <div class="wp-pos-form-col">
                            <div class="wp-pos-form-field">
                                <label for="last_name"><?php _e('Apellidos', 'wp-pos'); ?></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo $editing_customer ? esc_attr($editing_customer['last_name']) : ''; ?>" required />
                            </div>
                        </div>
                    </div>
                    
                    <div class="wp-pos-form-row">
                        <div class="wp-pos-form-col">
                            <div class="wp-pos-form-field">
                                <label for="email"><?php _e('Email', 'wp-pos'); ?></label>
                                <input type="email" id="email" name="email" value="<?php echo $editing_customer ? esc_attr($editing_customer['email']) : ''; ?>" required />
                            </div>
                        </div>
                        
                        <div class="wp-pos-form-col">
                            <div class="wp-pos-form-field">
                                <label for="dni"><?php _e('DNI / NIF', 'wp-pos'); ?></label>
                                <input type="text" id="dni" name="dni" value="<?php echo $editing_customer ? esc_attr($editing_customer['dni']) : ''; ?>" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="wp-pos-form-row">
                        <div class="wp-pos-form-col">
                            <div class="wp-pos-form-field">
                                <label for="phone"><?php _e('Teléfono', 'wp-pos'); ?></label>
                                <input type="tel" id="phone" name="phone" value="<?php 
                                    // Intentar obtener el telu00e9fono de todas las ubicaciones posibles
                                    $phone = '';
                                    if ($editing_customer) {
                                        if (isset($editing_customer['billing']['phone'])) {
                                            $phone = $editing_customer['billing']['phone'];
                                        } elseif (isset($editing_customer['phone'])) {
                                            $phone = $editing_customer['phone'];
                                        } else {
                                            // Intentar obtener directamente de los metadatos
                                            $phone = get_user_meta($editing_customer['id'], 'billing_phone', true);
                                            if (empty($phone)) {
                                                $phone = get_user_meta($editing_customer['id'], 'phone', true);
                                            }
                                        }
                                    }
                                    echo esc_attr($phone);
                                ?>" />
                            </div>
                        </div>
                        
                        <div class="wp-pos-form-col">
                            <div class="wp-pos-form-field">
                                <label for="birth_date"><?php _e('Fecha de Nacimiento', 'wp-pos'); ?></label>
                                <input type="date" id="birth_date" name="birth_date" value="<?php echo $editing_customer ? esc_attr($editing_customer['birth_date']) : ''; ?>" />
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($view === 'new'): ?>
                    <!-- Campo de contraseña solo para nuevos clientes -->
                    <div class="wp-pos-form-row">
                        <div class="wp-pos-form-col">
                            <div class="wp-pos-form-field">
                                <label for="password"><?php _e('Contraseña', 'wp-pos'); ?></label>
                                <input type="password" id="password" name="password" placeholder="<?php esc_attr_e('Dejar en blanco para generar automáticamente', 'wp-pos'); ?>" />
                                <p class="description"><?php _e('Contraseña para el nuevo cliente. Si se deja en blanco, se generará automáticamente una contraseña segura.', 'wp-pos'); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="wp-pos-form-section">
                    <h3><?php _e('Dirección y Notas', 'wp-pos'); ?></h3>
                    
                    <div class="wp-pos-form-row">
                        <div class="wp-pos-form-col">
                            <div class="wp-pos-form-field">
                                <label for="address"><?php _e('Dirección', 'wp-pos'); ?></label>
                                <textarea id="address" name="address" rows="3"><?php 
                                    // Intentar obtener la dirección de todas las ubicaciones posibles
                                    $address = '';
                                    if ($editing_customer) {
                                        if (isset($editing_customer['billing']['address_1'])) {
                                            $address = $editing_customer['billing']['address_1'];
                                        } elseif (isset($editing_customer['address'])) {
                                            $address = $editing_customer['address'];
                                        } else {
                                            // Intentar obtener directamente de los metadatos
                                            $address = get_user_meta($editing_customer['id'], 'billing_address_1', true);
                                            if (empty($address)) {
                                                $address = get_user_meta($editing_customer['id'], 'address', true);
                                            }
                                        }
                                    }
                                    echo esc_textarea($address);
                                ?></textarea>
                            </div>
                        </div>
                        
                        <div class="wp-pos-form-col">
                            <div class="wp-pos-form-field">
                                <label for="notes"><?php _e('Notas Adicionales', 'wp-pos'); ?></label>
                                <textarea id="notes" name="notes" rows="3"><?php 
                                    if ($editing_customer) {
                                        // Intentar obtener notas de diferentes ubicaciones posibles
                                        $notes = '';
                                        if (!empty($editing_customer['notes'])) {
                                            $notes = $editing_customer['notes'];
                                        } else {
                                            // Fallback: obtener directamente de los metadatos
                                            $notes = get_user_meta($editing_customer['id'], '_wp_pos_customer_notes', true);
                                        }
                                        echo esc_textarea($notes);
                                    }
                                ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="wp-pos-form-actions">
                    <a href="<?php echo admin_url('admin.php?page=wp-pos-customers'); ?>" class="wp-pos-button wp-pos-button-secondary">
                        <?php _e('Cancelar', 'wp-pos'); ?>
                    </a>
                    <button type="submit" name="wp_pos_save_customer" class="wp-pos-button">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo $view === 'new' ? __('Crear Cliente', 'wp-pos') : __('Actualizar Cliente', 'wp-pos'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php endif; ?>
    
</div>

<?php
// Cargar footer
wp_pos_template_footer();
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Confirmar eliminaciu00f3n de cliente
    $('.wp-pos-delete-link').on('click', function(e) {
        if (!confirm('<?php echo esc_js(__('estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.', 'wp-pos')); ?>')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Validar formulario antes de enviar
    $('#wp-pos-customer-form').on('submit', function(e) {
        var firstName = $('#first_name').val().trim();
        var lastName = $('#last_name').val().trim();
        var email = $('#email').val().trim();
        
        if (firstName === '') {
            alert('<?php echo esc_js(__('Por favor, introduce el nombre del cliente.', 'wp-pos')); ?>');
            $('#first_name').focus();
            e.preventDefault();
            return false;
        }
        
        if (lastName === '') {
            alert('<?php echo esc_js(__('Por favor, introduce los apellidos del cliente.', 'wp-pos')); ?>');
            $('#last_name').focus();
            e.preventDefault();
            return false;
        }
        
        if (email === '') {
            alert('<?php echo esc_js(__('Por favor, introduce un email vu00e1lido para el cliente.', 'wp-pos')); ?>');
            $('#email').focus();
            e.preventDefault();
            return false;
        }
        
        // Todo correcto, enviar formulario
        return true;
    });
});
</script>
