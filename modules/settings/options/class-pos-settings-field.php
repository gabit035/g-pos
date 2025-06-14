<?php
/**
 * Clase para manejar campos de configuracioFn
 *
 * @package WP-POS
 * @subpackage Settings
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase que representa un campo de configuracioFn
 *
 * @since 1.0.0
 */
class WP_POS_Settings_Field {

    /**
     * ID del campo
     *
     * @since 1.0.0
     * @var string
     */
    private $id;
    
    /**
     * ID del grupo al que pertenece
     *
     * @since 1.0.0
     * @var string
     */
    private $group_id;
    
    /**
     * Tu00edtulo del campo
     *
     * @since 1.0.0
     * @var string
     */
    private $title;
    
    /**
     * Descripcid=F3n del campo
     *
     * @since 1.0.0
     * @var string
     */
    private $description;
    
    /**
     * Tipo de campo
     *
     * @since 1.0.0
     * @var string
     */
    private $type;
    
    /**
     * Valor por defecto
     *
     * @since 1.0.0
     * @var mixed
     */
    private $default;
    
    /**
     * Clase CSS
     *
     * @since 1.0.0
     * @var string
     */
    private $css_class;
    
    /**
     * SecciU00F3n a la que pertenece
     *
     * @since 1.0.0
     * @var string
     */
    private $section;
    
    /**
     * Opciones adicionales
     *
     * @since 1.0.0
     * @var array
     */
    private $options;
    
    /**
     * Callback personalizado para renderizar
     *
     * @since 1.0.0
     * @var callable
     */
    private $render_callback;
    
    /**
     * Callback personalizado para sanitizar
     *
     * @since 1.0.0
     * @var callable
     */
    private $sanitize_callback;
    
    /**
     * Condiciones para mostrar el campo
     *
     * @since 1.0.0
     * @var array
     */
    private $conditions;

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $id ID del campo
     * @param array $args Argumentos del campo
     * @param string $group_id ID del grupo al que pertenece
     */
    public function __construct($id, $args = array(), $group_id = '') {
        $this->id = sanitize_key($id);
        $this->group_id = sanitize_key($group_id);
        
        // Nombre completo para la opciU00F3n
        $option_id = $this->group_id . '_' . $this->id;
        
        // Valores por defecto
        $defaults = array(
            'title'             => '',
            'description'       => '',
            'type'              => 'text',
            'default'           => '',
            'css_class'         => '',
            'section'           => 'default',
            'options'           => array(),
            'render_callback'   => null,
            'sanitize_callback' => null,
            'conditions'        => array(),
        );
        
        // Fusionar con valores por defecto
        $args = wp_parse_args($args, $defaults);
        
        // Establecer propiedades
        $this->title = $args['title'];
        $this->description = $args['description'];
        $this->type = $args['type'];
        $this->default = $args['default'];
        $this->css_class = $args['css_class'];
        $this->section = $args['section'];
        $this->options = $args['options'];
        $this->render_callback = $args['render_callback'];
        $this->sanitize_callback = $args['sanitize_callback'];
        $this->conditions = $args['conditions'];
    }

    /**
     * Obtener ID del campo
     *
     * @since 1.0.0
     * @return string ID del campo
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Obtener ID completo del campo (grupo_campo)
     *
     * @since 1.0.0
     * @return string ID completo del campo
     */
    public function get_full_id() {
        return $this->group_id . '_' . $this->id;
    }

    /**
     * Obtener ID del grupo
     *
     * @since 1.0.0
     * @return string ID del grupo
     */
    public function get_group_id() {
        return $this->group_id;
    }

    /**
     * Obtener tu00edtulo del campo
     *
     * @since 1.0.0
     * @return string Tu00edtulo del campo
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Obtener descripciU00F3n del campo
     *
     * @since 1.0.0
     * @return string DescripciU00F3n del campo
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Obtener tipo del campo
     *
     * @since 1.0.0
     * @return string Tipo del campo
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Obtener valor por defecto
     *
     * @since 1.0.0
     * @return mixed Valor por defecto
     */
    public function get_default() {
        return $this->default;
    }

    /**
     * Obtener clase CSS
     *
     * @since 1.0.0
     * @return string Clase CSS
     */
    public function get_css_class() {
        return $this->css_class;
    }

    /**
     * Obtener secciU00F3n
     *
     * @since 1.0.0
     * @return string SecciU00F3n
     */
    public function get_section() {
        return $this->section;
    }

    /**
     * Obtener opciones adicionales
     *
     * @since 1.0.0
     * @return array Opciones adicionales
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Obtener opcid=Fn especiFfica
     *
     * @since 1.0.0
     * @param string $key Clave de la opciU00F3n
     * @param mixed $default Valor por defecto si no existe
     * @return mixed Valor de la opciU00F3n
     */
    public function get_option($key, $default = null) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Obtener condiciones
     *
     * @since 1.0.0
     * @return array Condiciones
     */
    public function get_conditions() {
        return $this->conditions;
    }

    /**
     * Obtener todos los argumentos del campo como array
     *
     * @since 1.0.0
     * @return array Todos los argumentos del campo
     */
    public function get_args() {
        return array(
            'id' => $this->id,
            'group_id' => $this->group_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'default' => $this->default,
            'css_class' => $this->css_class,
            'section' => $this->section,
            'options' => $this->options,
            'conditions' => $this->conditions
        );
    }

    /**
     * Obtener valor actual del campo
     *
     * @since 1.0.0
     * @return mixed Valor actual
     */
    public function get_value() {
        return wp_pos_get_option($this->get_full_id(), $this->default);
    }

    /**
     * Renderizar el campo
     *
     * @since 1.0.0
     * @return string HTML del campo
     */
    public function render() {
        // Si hay callback personalizado, usarlo
        if (is_callable($this->render_callback)) {
            return call_user_func($this->render_callback, $this);
        }
        
        // Obtener valor actual
        $value = $this->get_value();
        
        // Iniciar output buffer
        ob_start();
        
        // Clases para el contenedor
        $wrapper_class = 'wp-pos-field-wrapper wp-pos-field-' . $this->type;
        if (!empty($this->css_class)) {
            $wrapper_class .= ' ' . $this->css_class;
        }
        
        // Au00f1adir atributos de condiciU00F3n si existen
        $condition_attrs = '';
        if (!empty($this->conditions)) {
            $wrapper_class .= ' wp-pos-field-conditional';
            $condition_attrs = ' data-conditions="' . esc_attr(json_encode($this->conditions)) . '"';
        }
        
        // Abrir contenedor
        echo '<div class="' . esc_attr($wrapper_class) . '"' . $condition_attrs . '>';
        
        // Nombre del campo para el formulario
        $field_name = 'wp_pos_options[' . $this->get_full_id() . ']';
        
        // Renderizar segoFn tipo
        switch ($this->type) {
            case 'text':
            case 'email':
            case 'url':
            case 'password':
            case 'number':
                $this->render_text_field($field_name, $value);
                break;
                
            case 'textarea':
                $this->render_textarea($field_name, $value);
                break;
                
            case 'checkbox':
                $this->render_checkbox($field_name, $value);
                break;
                
            case 'radio':
                $this->render_radio($field_name, $value);
                break;
                
            case 'select':
                $this->render_select($field_name, $value);
                break;
                
            case 'color':
                $this->render_color_picker($field_name, $value);
                break;
                
            case 'file':
                $this->render_file_upload($field_name, $value);
                break;
                
            case 'editor':
                $this->render_editor($field_name, $value);
                break;
                
            case 'image':
                $this->render_image_upload($field_name, $value);
                break;
                
            default:
                // Permitir tipos personalizados
                do_action('wp_pos_render_field_' . $this->type, $this, $field_name, $value);
                break;
        }
        
        // Mostrar descripcioFn si existe
        if (!empty($this->description)) {
            echo '<p class="description">' . wp_kses_post($this->description) . '</p>';
        }
        
        // Cerrar contenedor
        echo '</div>';
        
        // Devolver contenido
        return ob_get_clean();
    }

    /**
     * Renderizar campo de texto
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    private function render_text_field($field_name, $value) {
        $placeholder = $this->get_option('placeholder', '');
        $min = $this->get_option('min', '');
        $max = $this->get_option('max', '');
        $step = $this->get_option('step', '');
        
        // Atributos especu00edficos para el tipo number
        $extra_attrs = '';
        if ($this->type === 'number') {
            if ($min !== '') {
                $extra_attrs .= ' min="' . esc_attr($min) . '"';
            }
            if ($max !== '') {
                $extra_attrs .= ' max="' . esc_attr($max) . '"';
            }
            if ($step !== '') {
                $extra_attrs .= ' step="' . esc_attr($step) . '"';
            }
        }
        
        echo '<input type="' . esc_attr($this->type) . '" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($this->get_full_id()) . '" '
             . 'value="' . esc_attr($value) . '" '
             . ($placeholder ? 'placeholder="' . esc_attr($placeholder) . '" ' : '')
             . $extra_attrs
             . 'class="regular-text" />';
    }

    /**
     * Renderizar campo textarea
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    private function render_textarea($field_name, $value) {
        $placeholder = $this->get_option('placeholder', '');
        $rows = $this->get_option('rows', 5);
        $cols = $this->get_option('cols', 50);
        
        echo '<textarea '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($this->get_full_id()) . '" '
             . 'rows="' . esc_attr($rows) . '" '
             . 'cols="' . esc_attr($cols) . '" '
             . ($placeholder ? 'placeholder="' . esc_attr($placeholder) . '" ' : '')
             . 'class="large-text code">' 
             . esc_textarea($value) 
             . '</textarea>';
    }

    /**
     * Renderizar campo checkbox
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    private function render_checkbox($field_name, $value) {
        $checkbox_label = $this->get_option('checkbox_label', '');
        
        echo '<label for="' . esc_attr($this->get_full_id()) . '">';
        echo '<input type="checkbox" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($this->get_full_id()) . '" '
             . 'value="yes" '
             . checked($value, 'yes', false) . ' />';
             
        if ($checkbox_label) {
            echo ' ' . esc_html($checkbox_label);
        }
        
        echo '</label>';
    }

    /**
     * Renderizar campo radio
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    private function render_radio($field_name, $value) {
        $choices = $this->get_option('choices', array());
        
        if (empty($choices)) {
            return;
        }
        
        echo '<fieldset>';
        foreach ($choices as $choice_value => $choice_label) {
            $id = $this->get_full_id() . '_' . sanitize_key($choice_value);
            
            echo '<label for="' . esc_attr($id) . '">';
            echo '<input type="radio" '
                 . 'name="' . esc_attr($field_name) . '" '
                 . 'id="' . esc_attr($id) . '" '
                 . 'value="' . esc_attr($choice_value) . '" '
                 . checked($value, $choice_value, false) . ' />';
            echo ' ' . esc_html($choice_label) . '</label><br />';
        }
        echo '</fieldset>';
    }

    /**
     * Renderizar campo select
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    private function render_select($field_name, $value) {
        $choices = $this->get_option('choices', array());
        $multiple = $this->get_option('multiple', false);
        $placeholder = $this->get_option('placeholder', '');
        
        // Modificar nombre para multiple
        if ($multiple) {
            $field_name .= '[]';
        }
        
        echo '<select '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($this->get_full_id()) . '" '
             . 'class="regular-text" '
             . ($multiple ? 'multiple="multiple" ' : '')
             . '>';
             
        // Placeholder como primera opciU00F3n
        if ($placeholder && !$multiple) {
            echo '<option value="">' . esc_html($placeholder) . '</option>';
        }
        
        // Opciones
        foreach ($choices as $choice_value => $choice_label) {
            // Comprobar si estu00e1 seleccionada
            $selected = '';
            if ($multiple && is_array($value)) {
                $selected = in_array($choice_value, $value) ? 'selected="selected"' : '';
            } else {
                $selected = selected($value, $choice_value, false);
            }
            
            echo '<option value="' . esc_attr($choice_value) . '" ' . $selected . '>';
            echo esc_html($choice_label);
            echo '</option>';
        }
        
        echo '</select>';
    }

    /**
     * Renderizar selector de color
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    private function render_color_picker($field_name, $value) {
        echo '<input type="text" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($this->get_full_id()) . '" '
             . 'value="' . esc_attr($value) . '" '
             . 'class="wp-pos-color-picker" />';
             
        // Script para inicializar el color picker
        echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                $("#' . esc_js($this->get_full_id()) . '").wpColorPicker();
            });
        </script>';
    }

    /**
     * Renderizar subida de archivos
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    private function render_file_upload($field_name, $value) {
        $button_text = $this->get_option('button_text', __('Seleccionar archivo', 'wp-pos'));
        $file_types = $this->get_option('file_types', '');
        
        echo '<div class="wp-pos-file-upload">';
        echo '<input type="text" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($this->get_full_id()) . '" '
             . 'value="' . esc_attr($value) . '" '
             . 'class="regular-text" />';
             
        echo '<button type="button" class="button wp-pos-upload-button" '
             . 'data-target="' . esc_attr($this->get_full_id()) . '" '
             . 'data-file-types="' . esc_attr($file_types) . '">';
        echo esc_html($button_text);
        echo '</button>';
        echo '</div>';
    }

    /**
     * Renderizar editor de texto enriquecido
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    private function render_editor($field_name, $value) {
        $editor_settings = $this->get_option('editor_settings', array());
        
        // Configurar ajustes por defecto
        $default_settings = array(
            'textarea_name' => $field_name,
            'textarea_rows' => 10,
            'media_buttons' => true,
        );
        
        // Fusionar con ajustes personalizados
        $editor_settings = wp_parse_args($editor_settings, $default_settings);
        
        // Renderizar editor
        wp_editor($value, $this->get_full_id(), $editor_settings);
    }

    /**
     * Renderizar subida de imu00e1genes
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual (ID de imagen)
     */
    private function render_image_upload($field_name, $value) {
        $button_text = $this->get_option('button_text', __('Seleccionar imagen', 'wp-pos'));
        $preview_size = $this->get_option('preview_size', 'thumbnail');
        
        // Contenedor
        echo '<div class="wp-pos-image-upload">';
        
        // Campo oculto para el ID
        echo '<input type="hidden" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($this->get_full_id()) . '" '
             . 'value="' . esc_attr($value) . '" />';
        
        // Vista previa de la imagen
        echo '<div class="wp-pos-image-preview">';
        if (!empty($value)) {
            echo wp_get_attachment_image($value, $preview_size);
        }
        echo '</div>';
        
        // BotoFn de subida
        echo '<button type="button" class="button wp-pos-upload-image" '
             . 'data-target="' . esc_attr($this->get_full_id()) . '" '
             . 'data-preview-size="' . esc_attr($preview_size) . '">';
        echo esc_html($button_text);
        echo '</button>';
        
        // BotoFn de eliminaciU00F3n
        if (!empty($value)) {
            echo ' <button type="button" class="button wp-pos-remove-image" '
                 . 'data-target="' . esc_attr($this->get_full_id()) . '">';
            echo esc_html__('Eliminar imagen', 'wp-pos');
            echo '</button>';
        }
        
        echo '</div>';
    }

    /**
     * Sanitizar el valor del campo
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar
     * @return mixed Valor sanitizado
     */
    public function sanitize($value) {
        // Si hay callback personalizado, usarlo
        if (is_callable($this->sanitize_callback)) {
            return call_user_func($this->sanitize_callback, $value, $this);
        }
        
        // Sanitizar segoFn tipo
        switch ($this->type) {
            case 'text':
                return sanitize_text_field($value);
                
            case 'textarea':
                return sanitize_textarea_field($value);
                
            case 'email':
                return sanitize_email($value);
                
            case 'url':
                return esc_url_raw($value);
                
            case 'number':
                return is_numeric($value) ? floatval($value) : $this->default;
                
            case 'checkbox':
                return ($value === 'yes') ? 'yes' : 'no';
                
            case 'color':
                return sanitize_hex_color($value);
                
            // Para select y radio, validar que es una opciU00F3n vu00e1lida
            case 'select':
            case 'radio':
                $choices = $this->get_option('choices', array());
                return isset($choices[$value]) ? $value : $this->default;
                
            case 'editor':
                return wp_kses_post($value);
                
            default:
                // Permitir sanitizaciU00F3n personalizada
                $sanitized = apply_filters('wp_pos_sanitize_field_' . $this->type, $value, $this);
                if ($sanitized !== null) {
                    return $sanitized;
                }
                
                // Valor por defecto
                return sanitize_text_field($value);
        }
    }
}
