<?php
/**
 * Clase para manejar grupos de configuracioFn
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
 * Clase que representa un grupo de configuracioFn
 *
 * @since 1.0.0
 */
class WP_POS_Settings_Group {

    /**
     * ID del grupo
     *
     * @since 1.0.0
     * @var string
     */
    private $id;
    
    /**
     * Tu00edtulo del grupo
     *
     * @since 1.0.0
     * @var string
     */
    private $title;
    
    /**
     * Descripcid=F3n del grupo
     *
     * @since 1.0.0
     * @var string
     */
    private $description;
    
    /**
     * Icono del grupo
     *
     * @since 1.0.0
     * @var string
     */
    private $icon;
    
    /**
     * Prioridad para ordenacioFn
     *
     * @since 1.0.0
     * @var int
     */
    private $priority;
    
    /**
     * Campos de configuracioFn del grupo
     *
     * @since 1.0.0
     * @var array
     */
    private $fields = array();
    
    /**
     * Secciones del grupo
     *
     * @since 1.0.0
     * @var array
     */
    private $sections = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $id ID del grupo
     * @param array $args Argumentos del grupo
     */
    public function __construct($id, $args = array()) {
        $this->id = sanitize_key($id);
        
        // Valores por defecto
        $defaults = array(
            'title'       => '',
            'description' => '',
            'icon'        => 'dashicons-admin-generic',
            'priority'    => 10,
            'sections'    => array(),
        );
        
        // Fusionar con valores por defecto
        $args = wp_parse_args($args, $defaults);
        
        // Establecer propiedades
        $this->title = $args['title'];
        $this->description = $args['description'];
        $this->icon = $args['icon'];
        $this->priority = intval($args['priority']);
        
        // Registrar secciones si existen
        if (!empty($args['sections']) && is_array($args['sections'])) {
            foreach ($args['sections'] as $section_id => $section) {
                $this->add_section($section_id, $section);
            }
        } else {
            // Si no hay secciones, crear una por defecto
            $this->add_section('default', array(
                'title' => '',
                'description' => '',
            ));
        }
    }

    /**
     * Obtener ID del grupo
     *
     * @since 1.0.0
     * @return string ID del grupo
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Obtener tu00edtulo del grupo
     *
     * @since 1.0.0
     * @return string Tu00edtulo del grupo
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Obtener descripcioFn del grupo
     *
     * @since 1.0.0
     * @return string DescripciU00F3n del grupo
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Obtener icono del grupo
     *
     * @since 1.0.0
     * @return string Icono del grupo
     */
    public function get_icon() {
        return $this->icon;
    }

    /**
     * Obtener prioridad del grupo
     *
     * @since 1.0.0
     * @return int Prioridad del grupo
     */
    public function get_priority() {
        return $this->priority;
    }

    /**
     * Au00f1adir una secciU00F3n al grupo
     *
     * @since 1.0.0
     * @param string $section_id ID de la secciU00F3n
     * @param array $args Argumentos de la secciU00F3n
     * @return WP_POS_Settings_Group Instancia del grupo (fluent)
     */
    public function add_section($section_id, $args = array()) {
        $section_id = sanitize_key($section_id);
        
        // Valores por defecto
        $defaults = array(
            'title'       => '',
            'description' => '',
            'priority'    => 10,
        );
        
        // Fusionar con valores por defecto
        $args = wp_parse_args($args, $defaults);
        
        // Registrar secciU00F3n
        $this->sections[$section_id] = $args;
        
        return $this;
    }

    /**
     * Obtener secciones del grupo
     *
     * @since 1.0.0
     * @return array Secciones del grupo
     */
    public function get_sections() {
        return $this->sections;
    }

    /**
     * Obtener una secciU00F3n especu00edfica
     *
     * @since 1.0.0
     * @param string $section_id ID de la secciU00F3n
     * @return array|false Datos de la secciU00F3n o false si no existe
     */
    public function get_section($section_id) {
        return isset($this->sections[$section_id]) ? $this->sections[$section_id] : false;
    }

    /**
     * Au00f1adir un campo al grupo
     *
     * @since 1.0.0
     * @param string $field_id ID del campo
     * @param array|WP_POS_Settings_Field $args Argumentos del campo o instancia de WP_POS_Settings_Field
     * @return WP_POS_Settings_Group Instancia del grupo (fluent)
     */
    public function add_field($field_id, $args = array()) {
        // Si $args ya es un objeto WP_POS_Settings_Field, usarlo directamente
        if ($args instanceof WP_POS_Settings_Field) {
            $field = $args;
        } else {
            // Valor por defecto para la secciu00f3n
            if (empty($args['section'])) {
                $args['section'] = 'default';
            }
            
            // Crear instancia del campo
            $field = new WP_POS_Settings_Field($field_id, $args, $this->id);
        }
        
        // Almacenar campo
        $this->fields[$field_id] = $field;
        
        return $this;
    }

    /**
     * Obtener todos los campos del grupo
     *
     * @since 1.0.0
     * @return array Campos del grupo
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Obtener campo especu00edfico
     *
     * @since 1.0.0
     * @param string $field_id ID del campo
     * @return WP_POS_Settings_Field|false Campo o false si no existe
     */
    public function get_field($field_id) {
        return isset($this->fields[$field_id]) ? $this->fields[$field_id] : false;
    }

    /**
     * Obtener campos por secciU00F3n
     *
     * @since 1.0.0
     * @param string $section_id ID de la secciU00F3n
     * @return array Campos en la secciU00F3n
     */
    public function get_fields_by_section($section_id) {
        $section_fields = array();
        
        foreach ($this->fields as $field_id => $field) {
            if ($field->get_section() === $section_id) {
                $section_fields[$field_id] = $field;
            }
        }
        
        return $section_fields;
    }

    /**
     * Obtener URL de la pantalla de configuraciU00F3n para este grupo
     *
     * @since 1.0.0
     * @param array $args Argumentos adicionales para la URL
     * @return string URL de configuraciU00F3n
     */
    public function get_url($args = array()) {
        return wp_pos_get_settings_url($this->id, $args);
    }
}
