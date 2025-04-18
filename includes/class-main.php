<?php
/**
 * Clase principal que coordina todas las funcionalidades
 *
 * @package JetForm_Media_Gallery
 * @since 1.0.0
 */

// No ejecutar directamente
if (!defined('ABSPATH')) {
    die('No direct script access allowed');
}

class JetForm_Media_Gallery_Main {
    
    /**
     * Instancia singleton
     */
    private static $instance = null;
    
    /**
     * Version del plugin
     */
    private $version = '1.0.4';
    
    /**
     * Configuraciones del plugin
     */
    private $settings;
    
    /**
     * Instancias de las clases
     */
    private $field = null;
    private $process = null;
    private $styles = null;
    private $logger = null;
    private $admin = null;
    
    /**
     * Constructor
     */
    private function __construct() {
        // Cargar configuraciones
        $this->load_settings();
        
        // Inicializar componentes
        $this->init_components();
        
        // Configurar hooks
        $this->setup_hooks();
    }
    
    /**
     * Obtener la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Cargar configuraciones del plugin
     */
    private function load_settings() {
        $this->settings = get_option('jetform_media_gallery_settings', [
            'image_width' => 150,
            'image_height' => 150,
            'use_theme_buttons' => false,
            'remove_button_position' => 'center',
            'remove_button_bg' => '#ff0000',
            'remove_button_color' => '#ffffff',
            'remove_button_size' => 30,
            'overlay_opacity' => 0.5,
            'overlay_color' => '#000000',
            'select_button_order' => 'before',
            'title_tag' => 'h4',
            'title_size' => 16,
            'title_classes' => '',
            'debug_mode' => false,
            'max_log_size' => 5,
            'max_log_files' => 5,
            'button_bg' => '#0073aa',
            'button_text' => '#ffffff',
            'button_hover_bg' => '#005c8a',
            'button_border_radius' => 4,
            'button_padding' => '10px 16px',
        ]);
        
        // Asegurarnos de que tenemos un array de image_fields
        if (!isset($this->settings['image_fields']) || !is_array($this->settings['image_fields'])) {
            $this->settings['image_fields'] = [];
            update_option('jetform_media_gallery_settings', $this->settings);
        }
    }
    
    /**
     * Inicializar componentes
     */
    private function init_components() {
        // Inicializar el logger primero para que esté disponible para los demás componentes
        require_once JFB_MEDIA_GALLERY_PATH . 'includes/class-logger.php';
        $this->logger = new JetForm_Media_Gallery_Logger($this);
        
        // Inicializar el sistema de admin
        require_once JFB_MEDIA_GALLERY_PATH . 'admin/settings.php';
        $this->admin = new JetForm_Media_Gallery_Admin();
        
        // Inicializar otros componentes
        require_once JFB_MEDIA_GALLERY_PATH . 'includes/class-field.php';
        require_once JFB_MEDIA_GALLERY_PATH . 'includes/class-process-form.php';
        require_once JFB_MEDIA_GALLERY_PATH . 'includes/class-styles.php';
        
        $this->field = new JetForm_Media_Gallery_Field($this);
        $this->process = new JetForm_Media_Gallery_Process($this);
        $this->styles = new JetForm_Media_Gallery_Styles($this);
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        // Registrar shortcode
        add_shortcode('media_gallery_field', [$this->field, 'render_shortcode']);
        
        // Scripts y estilos
        add_action('wp_enqueue_scripts', [$this->field, 'enqueue_scripts']);
        
        // Hooks para guardar campos
        add_action('save_post_singlecar', [$this->process, 'save_on_direct_post_save'], 999, 3);
        add_action('jet-form-builder/form-handler/after-send', [$this->process, 'process_form_submission'], 999, 3);
        add_action('jet-form-builder/post-insert/after', [$this->process, 'save_post_images'], 999, 2);
        add_action('jet-form-builder/action/after/insert_post', [$this->process, 'save_post_images'], 999, 2);
        add_action('jet-form-builder/submit-form/after', [$this->process, 'save_form_images'], 999, 2);
        add_action('jet-form-builder/actions/after-actions', [$this->process, 'save_after_actions'], 999, 2);
        
        // Hooks específicos para capturar eventos de edición e inserción
        add_action('jet-form-builder/action/after-post-insert', [$this->process, 'save_post_images'], 999, 2);
        add_action('jet-form-builder/action/after-post-update', [$this->process, 'save_post_images'], 999, 2);
        
        // Hook para JetEngine
        add_action('jet-engine/forms/booking/notifications/after-base-fields', [$this->process, 'save_post_images'], 999, 2);
        
        // Hook de emergencia
        add_action('shutdown', [$this->process, 'emergency_save_images'], 999);
        
        // Hooks para verificación de meta
        add_action('updated_post_meta', [$this->process, 'verify_post_meta'], 10, 4);
        add_action('added_post_meta', [$this->process, 'verify_post_meta'], 10, 4);
        
        // Debug
        add_filter('jet-form-builder/form-handler/form-data', [$this->process, 'debug_form_data'], 10);
        
        // Asegurarnos de que tenemos el campo galería en la configuración
        $this->register_default_fields();
    }
    
    /**
     * Registrar campos por defecto
     */
    private function register_default_fields() {
        // Verificar si ya tenemos configuración para el campo "galeria"
        $found_galeria = false;
        foreach ($this->settings['image_fields'] as $field) {
            if ($field['name'] === 'galeria') {
                $found_galeria = true;
                break;
            }
        }
        
        // Si no existe, registrar el campo "galeria"
        if (!$found_galeria) {
            $this->register_image_field(
                'galeria',
                'Galería de imágenes',
                'gallery',
                'galeria',
                'JetEngine',
                false
            );
            $this->log_debug("Campo 'galeria' registrado automáticamente");
        }
    }
    
    /**
     * Obtener configuraciones
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Recargar configuraciones
     */
    public function reload_settings() {
        $this->settings = get_option('jetform_media_gallery_settings', $this->settings);
        return $this->settings;
    }
    
    /**
     * Registrar mensaje en el log
     */
    public function log_debug($message) {
        $this->logger->log_debug($message);
    }
    
    /**
     * Obtener la instancia del campo
     */
    public function get_field() {
        return $this->field;
    }
    
    /**
     * Obtener la instancia del procesador
     */
    public function get_process() {
        return $this->process;
    }
    
    /**
     * Obtener la instancia de estilos
     */
    public function get_styles() {
        return $this->styles;
    }
    
    /**
     * Obtener la instancia del logger
     */
    public function get_logger() {
        return $this->logger;
    }
    
    /**
     * Obtener la instancia de admin
     */
    public function get_admin() {
        return $this->admin;
    }
    
    /**
     * Obtener la versión del plugin
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Registrar un campo de imagen
     * 
     * @param string $field_name Nombre del campo
     * @param string $label Etiqueta del campo
     * @param string $type Tipo de campo: 'single' o 'gallery'
     * @param string $meta_key Clave meta donde se guardará el valor
     * @param string $meta_type Tipo de meta: 'image' o 'gallery'
     * @param bool $required Si el campo es requerido
     * @return bool Éxito o fallo
     */
    public function register_image_field($field_name, $label, $type = 'gallery', $meta_key = '', $meta_type = 'JetEngine', $required = false) {
        if (empty($field_name) || empty($label)) {
            return false;
        }
        
        // Usar el nombre del campo como clave meta si no se especifica
        if (empty($meta_key)) {
            $meta_key = $field_name;
        }
        
        // Verificar si el campo ya existe
        $exists = false;
        foreach ($this->settings['image_fields'] as $key => $field) {
            if ($field['name'] === $field_name) {
                // Actualizar campo existente
                $this->settings['image_fields'][$key] = [
                    'name' => $field_name,
                    'label' => $label,
                    'type' => $type,
                    'meta_key' => $meta_key,
                    'meta_type' => $meta_type,
                    'required' => $required,
                ];
                $exists = true;
                break;
            }
        }
        
        // Si no existe, añadir nuevo campo
        if (!$exists) {
            $this->settings['image_fields'][] = [
                'name' => $field_name,
                'label' => $label,
                'type' => $type,
                'meta_key' => $meta_key,
                'meta_type' => $meta_type,
                'required' => $required,
            ];
        }
        
        // Guardar configuración
        update_option('jetform_media_gallery_settings', $this->settings);
        return true;
    }
} 