<?php
/**
 * Controlador de la página de configuraciones
 *
 * @package WP-POS
 * @subpackage Settings
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para la página de configuraciones en el admin
 *
 * @since 1.0.0
 */
class WP_POS_Settings_Page {

    /**
     * Grupos de configuraciones
     *
     * @var array
     */
    private $groups = array();

    /**
     * Pestaña activa
     *
     * @var string
     */
    private $active_tab = '';

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param array $groups Grupos de configuraciones
     */
    public function __construct($groups = array()) {
        $this->groups = $groups;
        
        // Inicializar funcionalidad
        add_action('admin_init', array($this, 'init'));
    }

    /**
     * Inicializar configuraciones
     *
     * @since 1.0.0
     */
    public function init() {
        // Obtener tab activa
        $this->active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';
        
        // Usar primera pestaña si la activa no es válida
        if (empty($this->active_tab) || !isset($this->groups[$this->active_tab])) {
            $tabs = array_keys($this->groups);
            $this->active_tab = !empty($tabs) ? $tabs[0] : '';
        }
        
        // Registrar configuraciones
        $this->register_settings();
    }

    /**
     * Registrar configuraciones en WordPress
     *
     * @since 1.0.0
     */
    private function register_settings() {
        // Registrar opción para todas las configuraciones
        register_setting(
            'wp_pos_options',
            'wp_pos_options',
            array($this, 'sanitize_options')
        );
        
        // Registrar secciones y campos para cada grupo
        foreach ($this->groups as $group_id => $group) {
            // Obtener campos
            $fields = $group->get_fields();
            
            if (empty($fields)) {
                continue;
            }
            
            // Registrar sección
            add_settings_section(
                'wp_pos_' . $group_id,
                $group->get_title(),
                array($this, 'render_section'),
                'wp_pos_' . $group_id
            );
            
            // Registrar campos
            foreach ($fields as $field_id => $field) {
                add_settings_field(
                    $group_id . '_' . $field_id,
                    $field->get_title(),
                    array($this, 'render_field'),
                    'wp_pos_' . $group_id,
                    'wp_pos_' . $group_id,
                    array(
                        'id' => $field_id,
                        'group' => $group_id,
                        'field' => $field->get_args(),
                    )
                );
            }
        }
    }

    /**
     * Sanitizar opciones
     *
     * @since 1.0.0
     * @param array $input Datos a sanitizar
     * @return array Datos sanitizados
     */
    public function sanitize_options($input) {
        // Obtener todos los campos configurados
        $all_fields = array();
        
        foreach ($this->groups as $group_id => $group) {
            $fields = $group->get_fields();
            
            foreach ($fields as $field_id => $field) {
                $key = $group_id . '_' . $field_id;
                $all_fields[$key] = $field->get_args();
            }
        }
        
        // Crear instancia del sanitizador
        $sanitizer = new WP_POS_Settings_Sanitize();
        
        // Sanitizar entrada
        $sanitized = $sanitizer->sanitize($input, $all_fields);
        
        // Recuperar valores existentes para fusionar
        $current_options = wp_pos_get_all_options();
        
        // Fusionar con opciones existentes
        return array_merge($current_options, $sanitized);
    }

    /**
     * Renderizar sección
     *
     * @since 1.0.0
     * @param array $args Argumentos de la sección
     */
    public function render_section($args) {
        $section_id = $args['id'];
        $group_id = str_replace('wp_pos_', '', $section_id);
        
        if (isset($this->groups[$group_id])) {
            $description = $this->groups[$group_id]->get_description();
            
            if (!empty($description)) {
                echo '<p class="wp-pos-section-description">' . wp_kses_post($description) . '</p>';
            }
        }
    }

    /**
     * Renderizar campo
     *
     * @since 1.0.0
     * @param array $args Argumentos del campo
     */
    public function render_field($args) {
        // Instanciar renderizador de campos
        $renderer = new WP_POS_Settings_Fields_Renderer();
        
        // Renderizar campo
        $renderer->render_field($args);
    }

    /**
     * Renderizar página de configuraciones
     *
     * @since 1.0.0
     */
    public function render_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Obtener pestañas
        $tabs = $this->get_tabs();
        
        // Iniciar contenido
        echo '<div class="wrap wp-pos-settings-page">';
        echo '<h1>' . esc_html__('Configuraciones de WP POS', 'wp-pos') . '</h1>';
        
        // Mostrar mensaje si no hay pestañas
        if (empty($tabs)) {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('No hay configuraciones disponibles.', 'wp-pos');
            echo '</p></div>';
            echo '</div>';
            return;
        }
        
        // Navegación de pestañas
        echo '<h2 class="nav-tab-wrapper wp-clearfix">';
        foreach ($tabs as $tab_id => $tab) {
            $active_class = ($this->active_tab === $tab_id) ? 'nav-tab-active' : '';
            echo '<a href="?page=wp-pos-settings&tab=' . esc_attr($tab_id) . '" class="nav-tab ' . esc_attr($active_class) . '">';
            
            // Mostrar icono si existe
            if (!empty($tab['icon'])) {
                echo '<span class="dashicons ' . esc_attr($tab['icon']) . '"></span> ';
            }
            
            echo esc_html($tab['title']);
            echo '</a>';
        }
        echo '</h2>';
        
        // Formulario de configuraciones
        echo '<form method="post" action="options.php" id="wp-pos-settings-form">';
        
        // Campos ocultos y nonce
        settings_fields('wp_pos_options');
        
        // Contenido de la pestaña activa
        do_settings_sections('wp_pos_' . $this->active_tab);
        
        // Botón de guardar
        submit_button();
        
        echo '</form>';
        echo '</div>';
        
        // Scripts para campos condicionales
        $this->render_conditional_script();
    }

    /**
     * Obtener pestañas de configuración
     *
     * @since 1.0.0
     * @return array Pestañas con títulos e iconos
     */
    private function get_tabs() {
        $tabs = array();
        
        foreach ($this->groups as $group_id => $group) {
            $tabs[$group_id] = array(
                'title' => $group->get_title(),
                'icon' => $group->get_icon(),
            );
        }
        
        return apply_filters('wp_pos_settings_tabs', $tabs);
    }

    /**
     * Renderizar script para campos condicionales
     *
     * @since 1.0.0
     */
    private function render_conditional_script() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Función para actualizar campos condicionales
            function updateConditionalFields() {
                $('.wp-pos-field-conditional').each(function() {
                    var $field = $(this);
                    var conditions = $field.data('conditions');
                    
                    if (!conditions) {
                        return;
                    }
                    
                    var show = true;
                    
                    // Verificar todas las condiciones
                    $.each(conditions, function(field_id, condition) {
                        var $control = $('#' + field_id);
                        var value = '';
                        
                        // Obtener valor según tipo
                        if ($control.is(':checkbox')) {
                            value = $control.is(':checked') ? 'yes' : 'no';
                        } else {
                            value = $control.val();
                        }
                        
                        // Verificar condición
                        if (condition.operator === '==' && value != condition.value) {
                            show = false;
                        } else if (condition.operator === '!=' && value == condition.value) {
                            show = false;
                        }
                    });
                    
                    // Mostrar u ocultar
                    if (show) {
                        $field.show();
                    } else {
                        $field.hide();
                    }
                });
            }
            
            // Ejecutar al cargar
            updateConditionalFields();
            
            // Escuchar cambios en los campos
            $('#wp-pos-settings-form input, #wp-pos-settings-form select').on('change', function() {
                updateConditionalFields();
            });
        });
        </script>
        <?php
    }
}
