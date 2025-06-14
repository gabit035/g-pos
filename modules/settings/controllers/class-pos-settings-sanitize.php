<?php
/**
 * Sanitizador de campos de configuraciU00F3n
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
 * Clase para sanitizar campos de configuraciU00F3n
 *
 * @since 1.0.0
 */
class WP_POS_Settings_Sanitize {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Registrar sanitizadores personalizados
        add_filter('wp_pos_sanitize_field_code', array($this, 'sanitize_code'), 10, 2);
        add_filter('wp_pos_sanitize_field_html', array($this, 'sanitize_html'), 10, 2);
        add_filter('wp_pos_sanitize_field_order_statuses', array($this, 'sanitize_order_status'), 10, 2);
    }

    /**
     * Sanitizar todos los campos
     *
     * @since 1.0.0
     * @param array $input Datos a sanitizar
     * @param array $fields Configuracid=F3n de todos los campos
     * @return array Datos sanitizados
     */
    public function sanitize($input, $fields) {
        if (!is_array($input) || empty($input)) {
            return array();
        }
        
        $sanitized_input = array();
        
        foreach ($input as $key => $value) {
            // Verificar si el campo existe en nuestra configuracid=F3n
            if (isset($fields[$key])) {
                $field = $fields[$key];
                $sanitized_input[$key] = $this->sanitize_field($value, $field);
            } else {
                // Si el campo no estu00e1 registrado, usar sanitizacid=F3n general
                $sanitized_input[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized_input;
    }

    /**
     * Sanitizar un campo individual
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar
     * @param array $field ConfiguraciU00F3n del campo
     * @return mixed Valor sanitizado
     */
    public function sanitize_field($value, $field) {
        // Sanitizar segU00FAn tipo
        switch ($field['type']) {
            case 'text':
                return sanitize_text_field($value);
                
            case 'textarea':
                return sanitize_textarea_field($value);
                
            case 'email':
                return sanitize_email($value);
                
            case 'url':
                return esc_url_raw($value);
                
            case 'number':
                return $this->sanitize_number($value, $field);
                
            case 'checkbox':
                return $this->sanitize_checkbox($value);
                
            case 'color':
                return sanitize_hex_color($value);
                
            case 'select':
            case 'radio':
                return $this->sanitize_select($value, $field);
                
            case 'editor':
                return wp_kses_post($value);
                
            case 'multiselect':
                return $this->sanitize_multiselect($value, $field);
                
            case 'image':
            case 'file':
                return $this->sanitize_attachment($value);
                
            default:
                // Permitir sanitizaciones personalizadas
                $sanitized = apply_filters('wp_pos_sanitize_field_' . $field['type'], $value, $field);
                if ($sanitized !== null) {
                    return $sanitized;
                }
                
                // Valor por defecto
                return sanitize_text_field($value);
        }
    }

    /**
     * Sanitizar campo numU00E9rico
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar
     * @param array $field Configuraciu00f3n del campo
     * @return float|int Valor numU00E9rico sanitizado
     */
    private function sanitize_number($value, $field) {
        // Asegurar que es numU00E9rico
        if (!is_numeric($value)) {
            return isset($field['default']) ? $field['default'] : 0;
        }
        
        // Convertir a tipo apropiado
        $value = floatval($value);
        
        // Aplicar restricciones min/max si existen
        if (isset($field['options']['min']) && $value < $field['options']['min']) {
            $value = $field['options']['min'];
        }
        
        if (isset($field['options']['max']) && $value > $field['options']['max']) {
            $value = $field['options']['max'];
        }
        
        // Convertir a entero si step es 1
        if (isset($field['options']['step']) && $field['options']['step'] == 1) {
            $value = intval($value);
        }
        
        return $value;
    }

    /**
     * Sanitizar campo checkbox
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar
     * @return string 'yes' o 'no'
     */
    private function sanitize_checkbox($value) {
        return ($value === 'yes') ? 'yes' : 'no';
    }

    /**
     * Sanitizar campo select/radio
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar
     * @param array $field Configuraciu00f3n del campo
     * @return mixed Valor sanitizado
     */
    private function sanitize_select($value, $field) {
        // Validar que es una opciU00F3n vU00E1lida
        $choices = isset($field['options']['choices']) ? $field['options']['choices'] : array();
        
        if (empty($choices)) {
            return isset($field['default']) ? $field['default'] : '';
        }
        
        if (isset($choices[$value])) {
            return $value;
        }
        
        // Si no es vU00E1lido, devolver valor por defecto
        return isset($field['default']) ? $field['default'] : '';
    }

    /**
     * Sanitizar multiselect
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar (array)
     * @param array $field Configuraciu00f3n del campo
     * @return array Valores sanitizados
     */
    private function sanitize_multiselect($value, $field) {
        // Si no es array, convertir a array vacU00EDo
        if (!is_array($value)) {
            return array();
        }
        
        $choices = isset($field['options']['choices']) ? $field['options']['choices'] : array();
        $sanitized = array();
        
        // Filtrar solo opciones vU00E1lidas
        foreach ($value as $option) {
            if (isset($choices[$option])) {
                $sanitized[] = $option;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitizar ID de adjunto
     *
     * @since 1.0.0
     * @param mixed $value ID de adjunto
     * @return int ID sanitizado
     */
    private function sanitize_attachment($value) {
        $attachment_id = absint($value);
        
        // Verificar que el adjunto existe
        if ($attachment_id > 0 && !get_post($attachment_id)) {
            return 0;
        }
        
        return $attachment_id;
    }

    /**
     * Sanitizar campo de cU00F3digo
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar
     * @param array $field Configuraciu00f3n del campo
     * @return string CU00F3digo sanitizado
     */
    public function sanitize_code($value, $field) {
        // No aplicar kses para preservar sintaxis de cU00F3digo
        return $value;
    }

    /**
     * Sanitizar campo HTML
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar
     * @param array $field Configuraciu00f3n del campo
     * @return string HTML sanitizado
     */
    public function sanitize_html($value, $field) {
        // Permitir etiquetas HTML bU00E1sicas
        $allowed_html = array(
            'a' => array(
                'href' => array(),
                'title' => array(),
                'target' => array(),
                'class' => array(),
            ),
            'br' => array(),
            'em' => array(),
            'strong' => array(),
            'p' => array(
                'class' => array(),
            ),
            'div' => array(
                'class' => array(),
                'id' => array(),
            ),
            'span' => array(
                'class' => array(),
                'id' => array(),
            ),
            'ul' => array(
                'class' => array(),
            ),
            'ol' => array(
                'class' => array(),
            ),
            'li' => array(
                'class' => array(),
            ),
            'h1' => array(),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'h6' => array(),
        );
        
        return wp_kses($value, $allowed_html);
    }

    /**
     * Sanitizar estado de pedido
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar
     * @param array $field Configuraciu00f3n del campo
     * @return string Estado sanitizado
     */
    public function sanitize_order_status($value, $field) {
        // Obtener estados vU00E1lidos
        $valid_statuses = array();
        
        if (function_exists('wc_get_order_statuses')) {
            $valid_statuses = array_keys(wc_get_order_statuses());
        } else {
            // Fallback si WooCommerce no estu00e1 activo
            $valid_statuses = array(
                'wc-pending',
                'wc-processing',
                'wc-on-hold',
                'wc-completed',
            );
        }
        
        // Validar estado
        if (in_array($value, $valid_statuses)) {
            return $value;
        }
        
        // Valor por defecto si no es vU00E1lido
        return isset($field['default']) ? $field['default'] : 'wc-completed';
    }
}
