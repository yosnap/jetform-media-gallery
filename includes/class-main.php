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
    private $jetengine = null;
    
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
        
        // Inicializar integraciones
        $this->init_integrations();
    }
    
    /**
     * Inicializar integraciones con otros plugins
     */
    private function init_integrations() {
        // Integración con JetEngine
        if (function_exists('jet_engine')) {
            require_once JFB_MEDIA_GALLERY_PATH . 'includes/jetengine-integration.php';
            $this->jetengine = new JetForm_Media_Gallery_JetEngine_Integration($this);
            $this->log_debug("Integración con JetEngine inicializada");
        }
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        // Registrar shortcode
        add_shortcode('media_gallery_field', [$this->field, 'render_shortcode']);
        
        // Scripts y estilos
        add_action('wp_enqueue_scripts', [$this->field, 'enqueue_scripts']);
        
        // Hooks para detectar el post_id de la URL cuando se está editando
        add_action('wp', [$this, 'detect_edit_post_id']);
        
        // Filtros para asegurar el post_id correcto
        add_filter('jet-form-builder/form-handler/form-data', [$this, 'force_correct_post_id'], 5);
        add_filter('jet-engine/forms/handler/form-data', [$this, 'force_correct_post_id'], 5);
        
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
        
        // Hook para el envío del formulario
        add_action('wp_ajax_jet_engine_form_gateway_send', [$this->process, 'intercept_jetengine_form'], 5);
        add_action('wp_ajax_nopriv_jet_engine_form_gateway_send', [$this->process, 'intercept_jetengine_form'], 5);
        
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
     * Obtener la instancia de integración con JetEngine
     */
    public function get_jetengine() {
        return $this->jetengine;
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
    
    /**
     * Detectar ID de post en modo edición
     */
    public function detect_edit_post_id() {
        if (isset($_GET['_post_id']) && !empty($_GET['_post_id'])) {
            $post_id = absint($_GET['_post_id']);
            $post = get_post($post_id);
            
            if ($post) {
                $this->log_debug("Modo edición detectado para post ID: $post_id");
                
                // Guardar en una propiedad para que esté disponible para el procesamiento
                $GLOBALS['jetform_media_gallery_edit_post_id'] = $post_id;
                
                // Sobrescribir el post_id en $_POST y $_REQUEST
                if (isset($_POST['post_id'])) {
                    $_POST['post_id'] = $post_id;
                    $this->log_debug("Sobrescrito post_id en POST con: $post_id");
                }
                
                if (isset($_REQUEST['post_id'])) {
                    $_REQUEST['post_id'] = $post_id;
                    $this->log_debug("Sobrescrito post_id en REQUEST con: $post_id");
                }
                
                // Añadir un hook para modificar el formulario
                add_action('wp_footer', function() use ($post_id) {
                    // Modificar los campos post_id existentes con JavaScript
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Modificar cualquier campo post_id existente
                            var postIdFields = document.querySelectorAll('input[name=\"post_id\"]');
                            if (postIdFields.length > 0) {
                                for (var i = 0; i < postIdFields.length; i++) {
                                    postIdFields[i].value = '{$post_id}';
                                    console.log('Modificado campo post_id existente');
                                }
                            } else {
                                // Si no existe, agregar el campo al formulario
                                var forms = document.querySelectorAll('form');
                                for (var i = 0; i < forms.length; i++) {
                                    var hiddenField = document.createElement('input');
                                    hiddenField.type = 'hidden';
                                    hiddenField.name = 'post_id';
                                    hiddenField.value = '{$post_id}';
                                    forms[i].appendChild(hiddenField);
                                    console.log('Añadido campo post_id al formulario');
                                }
                            }
                            
                            // Hacer lo mismo con _post_id
                            var postIdFields2 = document.querySelectorAll('input[name=\"_post_id\"]');
                            if (postIdFields2.length > 0) {
                                for (var i = 0; i < postIdFields2.length; i++) {
                                    postIdFields2[i].value = '{$post_id}';
                                    console.log('Modificado campo _post_id existente');
                                }
                            } else {
                                // Si no existe, agregar el campo al formulario
                                var forms = document.querySelectorAll('form');
                                for (var i = 0; i < forms.length; i++) {
                                    var hiddenField = document.createElement('input');
                                    hiddenField.type = 'hidden';
                                    hiddenField.name = '_post_id';
                                    hiddenField.value = '{$post_id}';
                                    forms[i].appendChild(hiddenField);
                                    console.log('Añadido campo _post_id al formulario');
                                }
                            }
                        });
                    </script>";
                }, 999);
            }
        }
    }
    
    /**
     * Forzar el ID de post correcto en los datos del formulario
     */
    public function force_correct_post_id($form_data) {
        // Verificar si estamos en modo edición
        if (isset($_GET['_post_id']) && !empty($_GET['_post_id'])) {
            $url_post_id = absint($_GET['_post_id']);
            $post = get_post($url_post_id);
            
            if ($post) {
                $this->log_debug("Forzando post_id correcto desde URL: $url_post_id");
                
                // Reemplazar cualquier post_id existente
                if (isset($form_data['post_id'])) {
                    $old_id = $form_data['post_id'];
                    $form_data['post_id'] = $url_post_id;
                    $this->log_debug("Reemplazado post_id: $old_id -> $url_post_id");
                } else {
                    $form_data['post_id'] = $url_post_id;
                    $this->log_debug("Añadido post_id: $url_post_id");
                }
                
                // También para _post_id
                if (isset($form_data['_post_id'])) {
                    $old_id = $form_data['_post_id'];
                    $form_data['_post_id'] = $url_post_id;
                    $this->log_debug("Reemplazado _post_id: $old_id -> $url_post_id");
                } else {
                    $form_data['_post_id'] = $url_post_id;
                    $this->log_debug("Añadido _post_id: $url_post_id");
                }
                
                // Para JetEngine Insert Post Action
                if (isset($form_data['inserted_post_id'])) {
                    $old_id = $form_data['inserted_post_id'];
                    $form_data['inserted_post_id'] = $url_post_id;
                    $this->log_debug("Reemplazado inserted_post_id: $old_id -> $url_post_id");
                }
                
                // Verificar si hay un array de __fields_map y actualizar el campo ID
                if (isset($form_data['__fields_map']) && is_array($form_data['__fields_map'])) {
                    if (isset($form_data['__fields_map']['ID'])) {
                        $old_id = $form_data['__fields_map']['ID'];
                        $form_data['__fields_map']['ID'] = $url_post_id;
                        $this->log_debug("Reemplazado __fields_map[ID]: $old_id -> $url_post_id");
                    }
                }
            }
        }
        
        return $form_data;
    }
} 