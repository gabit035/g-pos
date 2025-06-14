<?php
/**
 * Renderizador de campos de configuracioFn
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
 * Clase para renderizar campos de configuraciB3n
 *
 * @since 1.0.0
 */
class WP_POS_Settings_Fields_Renderer {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Registrar tipos de campos personalizados
        add_action('wp_pos_render_field_order_statuses', array($this, 'render_order_statuses_field'), 10, 3);
        add_action('wp_pos_render_field_code', array($this, 'render_code_field'), 10, 3);
        add_action('wp_pos_render_field_html', array($this, 'render_html_field'), 10, 3);
    }

    /**
     * Renderizar campo de configuraciB3n
     *
     * @since 1.0.0
     * @param array $args Argumentos del campo
     */
    public function render_field($args) {
        // Extraer argumentos
        $id = isset($args['id']) ? $args['id'] : '';
        $group = isset($args['group']) ? $args['group'] : '';
        $field = isset($args['field']) ? $args['field'] : array();
        
        if (empty($id) || empty($group) || empty($field)) {
            return;
        }
        
        // ID completo del campo
        $full_id = $group . '_' . $id;
        
        // Obtener valor actual
        $value = wp_pos_get_option($full_id, isset($field['default']) ? $field['default'] : '');
        
        // Clases para el contenedor
        $wrapper_class = 'wp-pos-field-wrapper wp-pos-field-' . $field['type'];
        if (!empty($field['css_class'])) {
            $wrapper_class .= ' ' . $field['css_class'];
        }
        
        // Au00f1adir atributos de condiciU00F3n si existen
        $condition_attrs = '';
        if (!empty($field['conditions'])) {
            $wrapper_class .= ' wp-pos-field-conditional';
            $condition_attrs = ' data-conditions="' . esc_attr(json_encode($field['conditions'])) . '"';
        }
        
        // Abrir contenedor
        echo '<div class="' . esc_attr($wrapper_class) . '"' . $condition_attrs . '>';
        
        // Nombre del campo para el formulario
        $field_name = 'wp_pos_options[' . $full_id . ']';
        
        // Renderizar segoFn tipo
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'url':
            case 'password':
            case 'number':
                $this->render_text_field($field_name, $value, $field, $full_id);
                break;
                
            case 'textarea':
                $this->render_textarea($field_name, $value, $field, $full_id);
                break;
                
            case 'checkbox':
                $this->render_checkbox($field_name, $value, $field, $full_id);
                break;
                
            case 'radio':
                $this->render_radio($field_name, $value, $field, $full_id);
                break;
                
            case 'select':
                $this->render_select($field_name, $value, $field, $full_id);
                break;
                
            case 'color':
                $this->render_color_picker($field_name, $value, $field, $full_id);
                break;
                
            case 'file':
                $this->render_file_upload($field_name, $value, $field, $full_id);
                break;
                
            case 'editor':
                $this->render_editor($field_name, $value, $field, $full_id);
                break;
                
            case 'image':
                $this->render_image_upload($field_name, $value, $field, $full_id);
                break;
                
            default:
                // Permitir tipos personalizados
                do_action('wp_pos_render_field_' . $field['type'], $field, $field_name, $value);
                break;
        }
        
        // Mostrar descripcioFn si existe
        if (!empty($field['description'])) {
            echo '<p class="description">' . wp_kses_post($field['description']) . '</p>';
        }
        
        // Cerrar contenedor
        echo '</div>';
    }

    /**
     * Renderizar campo de texto
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     * @param array $field Configuración del campo
     * @param string $id ID del campo
     */
    private function render_text_field($field_name, $value, $field, $id) {
        $placeholder = isset($field['options']['placeholder']) ? $field['options']['placeholder'] : '';
        $min = isset($field['options']['min']) ? $field['options']['min'] : '';
        $max = isset($field['options']['max']) ? $field['options']['max'] : '';
        $step = isset($field['options']['step']) ? $field['options']['step'] : '';
        
        // Atributos especu00edficos para el tipo number
        $extra_attrs = '';
        if ($field['type'] === 'number') {
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
        
        echo '<input type="' . esc_attr($field['type']) . '" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($id) . '" '
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
     * @param array $field Configuración del campo
     * @param string $id ID del campo
     */
    private function render_textarea($field_name, $value, $field, $id) {
        $placeholder = isset($field['options']['placeholder']) ? $field['options']['placeholder'] : '';
        $rows = isset($field['options']['rows']) ? $field['options']['rows'] : 5;
        $cols = isset($field['options']['cols']) ? $field['options']['cols'] : 50;
        
        echo '<textarea '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($id) . '" '
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
     * @param array $field Configuración del campo
     * @param string $id ID del campo
     */
    private function render_checkbox($field_name, $value, $field, $id) {
        $checkbox_label = isset($field['options']['checkbox_label']) ? $field['options']['checkbox_label'] : '';
        
        echo '<label for="' . esc_attr($id) . '">';
        echo '<input type="checkbox" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($id) . '" '
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
     * @param array $field Configuración del campo
     * @param string $id ID del campo
     */
    private function render_radio($field_name, $value, $field, $id) {
        $choices = isset($field['options']['choices']) ? $field['options']['choices'] : array();
        
        if (empty($choices)) {
            return;
        }
        
        echo '<fieldset>';
        foreach ($choices as $choice_value => $choice_label) {
            $choice_id = $id . '_' . sanitize_key($choice_value);
            
            echo '<label for="' . esc_attr($choice_id) . '">';
            echo '<input type="radio" '
                 . 'name="' . esc_attr($field_name) . '" '
                 . 'id="' . esc_attr($choice_id) . '" '
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
     * @param array $field Configuración del campo
     * @param string $id ID del campo
     */
    private function render_select($field_name, $value, $field, $id) {
        $choices = isset($field['options']['choices']) ? $field['options']['choices'] : array();
        $multiple = isset($field['options']['multiple']) ? $field['options']['multiple'] : false;
        $placeholder = isset($field['options']['placeholder']) ? $field['options']['placeholder'] : '';
        
        // Modificar nombre para multiple
        if ($multiple) {
            $field_name .= '[]';
        }
        
        echo '<select '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($id) . '" '
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
     * @param array $field Configuración del campo
     * @param string $id ID del campo
     */
    private function render_color_picker($field_name, $value, $field, $id) {
        echo '<input type="text" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($id) . '" '
             . 'value="' . esc_attr($value) . '" '
             . 'class="wp-pos-color-picker" />';
             
        // Script para inicializar el color picker
        echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                $("#' . esc_js($id) . '").wpColorPicker();
            });
        </script>';
    }

    /**
     * Renderizar subida de archivos
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     * @param array $field Configuración del campo
     * @param string $id ID del campo
     */
    private function render_file_upload($field_name, $value, $field, $id) {
        $button_text = isset($field['options']['button_text']) ? $field['options']['button_text'] : __('Seleccionar archivo', 'wp-pos');
        $file_types = isset($field['options']['file_types']) ? $field['options']['file_types'] : '';
        
        echo '<div class="wp-pos-file-upload">';
        echo '<input type="text" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($id) . '" '
             . 'value="' . esc_attr($value) . '" '
             . 'class="regular-text" />';
             
        echo '<button type="button" class="button wp-pos-upload-button" '
             . 'data-target="' . esc_attr($id) . '" '
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
     * @param array $field Configuración del campo
     * @param string $id ID del campo
     */
    private function render_editor($field_name, $value, $field, $id) {
        $editor_settings = isset($field['options']['editor_settings']) ? $field['options']['editor_settings'] : array();
        
        // Configurar ajustes por defecto
        $default_settings = array(
            'textarea_name' => $field_name,
            'textarea_rows' => 10,
            'media_buttons' => true,
        );
        
        // Fusionar con ajustes personalizados
        $editor_settings = wp_parse_args($editor_settings, $default_settings);
        
        // Renderizar editor
        wp_editor($value, $id, $editor_settings);
    }

    /**
     * Renderizar subida de imu00e1genes
     *
     * @since 1.0.0
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual (ID de imagen)
     * @param array $field Configuración del campo
     * @param string $id ID del campo
     */
    private function render_image_upload($field_name, $value, $field, $id) {
        $button_text = isset($field['options']['button_text']) ? $field['options']['button_text'] : __('Seleccionar imagen', 'wp-pos');
        $preview_size = isset($field['options']['preview_size']) ? $field['options']['preview_size'] : 'thumbnail';
        
        // Contenedor
        echo '<div class="wp-pos-image-upload">';
        
        // Campo oculto para el ID
        echo '<input type="hidden" '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($id) . '" '
             . 'value="' . esc_attr($value) . '" />';
        
        // Vista previa de la imagen
        echo '<div class="wp-pos-image-preview">';
        if (!empty($value)) {
            echo wp_get_attachment_image($value, $preview_size);
        }
        echo '</div>';
        
        // BotoFn de subida
        echo '<button type="button" class="button wp-pos-upload-image" '
             . 'data-target="' . esc_attr($id) . '" '
             . 'data-preview-size="' . esc_attr($preview_size) . '">';
        echo esc_html($button_text);
        echo '</button>';
        
        // BotoFn de eliminaciU00F3n
        if (!empty($value)) {
            echo ' <button type="button" class="button wp-pos-remove-image" '
                 . 'data-target="' . esc_attr($id) . '">';
            echo esc_html__('Eliminar imagen', 'wp-pos');
            echo '</button>';
        }
        
        echo '</div>';
    }

    /**
     * Renderizar campo de estados de pedido
     *
     * @since 1.0.0
     * @param array $field Configuración del campo
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    public function render_order_statuses_field($field, $field_name, $value) {
        // Obtener estados de pedido de WooCommerce
        $order_statuses = array();
        
        if (function_exists('wc_get_order_statuses')) {
            $order_statuses = wc_get_order_statuses();
        } else {
            // Fallback si WooCommerce no estu00e1 activo
            $order_statuses = array(
                'wc-pending'    => __('Pendiente de pago', 'wp-pos'),
                'wc-processing' => __('Procesando', 'wp-pos'),
                'wc-on-hold'    => __('En espera', 'wp-pos'),
                'wc-completed'  => __('Completado', 'wp-pos'),
            );
        }
        
        echo '<select name="' . esc_attr($field_name) . '" id="' . esc_attr($field['id']) . '" class="regular-text">';
        
        foreach ($order_statuses as $status => $label) {
            echo '<option value="' . esc_attr($status) . '" ' . selected($value, $status, false) . '>';
            echo esc_html($label);
            echo '</option>';
        }
        
        echo '</select>';
    }

    /**
     * Renderizar campo de cU00F3digo
     *
     * @since 1.0.0
     * @param array $field Configuración del campo
     * @param string $field_name Nombre del campo
     * @param mixed $value Valor actual
     */
    public function render_code_field($field, $field_name, $value) {
        $language = isset($field['options']['language']) ? $field['options']['language'] : 'php';
        $rows = isset($field['options']['rows']) ? $field['options']['rows'] : 10;
        
        echo '<div class="wp-pos-code-editor-wrapper">';
        echo '<textarea '
             . 'name="' . esc_attr($field_name) . '" '
             . 'id="' . esc_attr($field['id']) . '" '
             . 'rows="' . esc_attr($rows) . '" '
             . 'class="wp-pos-code-editor" '
             . 'data-language="' . esc_attr($language) . '">' 
             . esc_textarea($value) 
             . '</textarea>';
        echo '</div>';
    }

    /**
     * Renderizar campo HTML personalizado
     *
     * @since 1.0.0
     * @param array $field Configuración del campo
     * @param string $field_name Nombre del campo (no usado)
     * @param mixed $value Valor actual (no usado)
     */
    public function render_html_field($field, $field_name, $value) {
        $html = isset($field['options']['html']) ? $field['options']['html'] : '';
        
        if (!empty($html)) {
            echo wp_kses_post($html);
        }
    }
}
