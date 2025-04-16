<?php
/**
 * Plugin Name: JetFormBuilder Media Gallery Field
 * Description: Agrega un campo de galería de medios para JetFormBuilder que permite seleccionar imagen destacada y galería para el CPT "singlecar"
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: jetform-media-gallery
 */

/**
 * DOCUMENTACIÓN DE USO
 * ====================
 * 
 * Este plugin añade un campo personalizado para JetFormBuilder que permite a los usuarios:
 * - Seleccionar una imagen destacada para el post
 * - Seleccionar múltiples imágenes para una galería
 * 
 * La imagen destacada se establece automáticamente con la función set_post_thumbnail()
 * Las imágenes de la galería se guardan en el campo meta "ad_gallery" del CPT "singlecar"
 * 
 * MÉTODO DE IMPLEMENTACIÓN:
 * 
 * En tu formulario de JetFormBuilder, añade un campo HTML personalizado
 * y dentro agrega el siguiente shortcode:
 * 
 * [media_gallery_field name="media_gallery" label="Seleccionar imágenes para el anuncio" required="1"]
 * 
 * Parámetros disponibles:
 * - name: (opcional) Nombre base del campo, por defecto 'media_gallery'
 * - label: (opcional) Etiqueta del campo, por defecto 'Seleccionar imágenes'
 * - required: (opcional) Si es requerido, usar '1' para requerido, '0' para opcional
 * 
 * Las entradas de datos generadas serán:
 * - {name}_featured: ID de la imagen destacada
 * - {name}_gallery: IDs de las imágenes de galería separados por comas
 * 
 * DEPURACIÓN:
 * Para activar el modo de depuración, añade ?jfb_debug=1 a la URL del formulario.
 * Los logs se guardarán en wp-content/uploads/jfb-logs/
 */

// No ejecutar directamente
if (!defined('ABSPATH')) {
    die('No direct script access allowed');
}

// Definir constantes
define('JFB_MEDIA_GALLERY_VERSION', '1.0.0');
define('JFB_MEDIA_GALLERY_PATH', plugin_dir_path(__FILE__));
define('JFB_MEDIA_GALLERY_URL', plugin_dir_url(__FILE__));

// Incluir el archivo de administración
require_once plugin_dir_path(__FILE__) . 'admin/settings.php';

/**
 * Clase principal para el campo de galería de medios
 */
class JetForm_Media_Gallery_Field {
    
    /**
     * Almacena el modo de depuración
     */
    private $debug_mode = false;
    
    private $admin;
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Inicializar la página de administración
        $this->admin = new JetForm_Media_Gallery_Admin();
        
        // Cargar configuraciones
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
            'title_classes' => ''
        ]);
        
        // Forzar modo de depuración
        $this->debug_mode = true;
        
        // Registrar el shortcode
        add_shortcode('media_gallery_field', [$this, 'render_shortcode']);
        
        // Cargar scripts y estilos
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Hooks directos para garantizar el guardado
        $this->register_save_hooks();
        
        // Añadir un método de emergencia como último recurso
        add_action('shutdown', [$this, 'emergency_save_images'], 999);
        
        // Añadir filtros para debug y procesamiento de formulario
        add_filter('jet-form-builder/form-handler/form-data', [$this, 'debug_form_data'], 10);
        add_filter('jet-form-builder/form-handler/after-send', [$this, 'process_form_submission'], 10, 3);
    }
    
    /**
     * Registrar hooks de guardado
     */
    private function register_save_hooks() {
        // Hook principal para el guardado del post
        add_action('save_post_singlecar', [$this, 'save_on_direct_post_save'], 999, 3);
        
        // Hooks de JetFormBuilder
        add_action('jet-form-builder/form-handler/after-send', [$this, 'process_form_submission'], 999, 3);
        add_action('jet-form-builder/post-insert/after', [$this, 'save_post_images'], 999, 2);
        add_action('jet-form-builder/action/after/insert_post', [$this, 'save_post_images'], 999, 2);
        add_action('jet-form-builder/submit-form/after', [$this, 'save_form_images'], 999, 2);
        add_action('jet-form-builder/actions/after-actions', [$this, 'save_after_actions'], 999, 2);
        
        // Hook para JetEngine
        add_action('jet-engine/forms/booking/notifications/after-base-fields', [$this, 'save_post_images'], 999, 2);
        
        // Hook de emergencia
        add_action('shutdown', [$this, 'emergency_save_images'], 999);
        
        // Hook adicional para asegurar que los metadatos se guarden después de la actualización del post
        add_action('updated_post_meta', [$this, 'verify_post_meta'], 10, 4);
        add_action('added_post_meta', [$this, 'verify_post_meta'], 10, 4);
    }
    
    /**
     * Shortcode para renderizar el campo
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'name' => 'media_gallery',
            'label' => 'Seleccionar imágenes',
            'required' => '0',
        ], $atts, 'media_gallery_field');
        
        $name = sanitize_key($atts['name']);
        $label = sanitize_text_field($atts['label']);
        $required = filter_var($atts['required'], FILTER_VALIDATE_BOOLEAN) ? 'required' : '';
        $field_id = 'field_' . $name;
        
        // Obtener configuración del título
        $title_tag = $this->settings['title_tag'];
        $title_classes = $this->settings['title_classes'];
        
        ob_start();
        ?>
        <div class="jet-form-builder__field media-gallery-field">
            <label for="<?php echo esc_attr($field_id); ?>" class="jet-form-builder__label">
                <?php echo esc_html($label); ?>
                <?php if ($required) : ?>
                    <span class="jet-form-builder__required">*</span>
                <?php endif; ?>
            </label>
            
            <div class="media-gallery-container">
                <!-- Contenedor para la imagen destacada -->
                <div class="featured-image-container">
                    <<?php echo esc_attr($title_tag); ?> class="section-title <?php echo esc_attr($title_classes); ?>">
                        Imagen destacada
                    </<?php echo esc_attr($title_tag); ?>>
                    <div class="image-controls">
                        <?php if ($this->settings['select_button_order'] === 'before') : ?>
                            <button type="button" class="button upload-featured-image" data-field="<?php echo esc_attr($name); ?>">Seleccionar imagen destacada</button>
                        <?php endif; ?>
                        <div id="featured-image-preview-<?php echo esc_attr($name); ?>" class="image-preview">
                            <div class="image-overlay"></div>
                            <button type="button" class="remove-featured-image" style="display: none;">×</button>
                        </div>
                        <?php if ($this->settings['select_button_order'] === 'after') : ?>
                            <button type="button" class="button upload-featured-image" data-field="<?php echo esc_attr($name); ?>">Seleccionar imagen destacada</button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="<?php echo esc_attr($name . '_featured'); ?>" id="featured-image-input-<?php echo esc_attr($name); ?>" class="jet-form-builder__field" <?php echo $required; ?>>
                </div>
                
                <!-- Contenedor para la galería -->
                <div class="gallery-container">
                    <<?php echo esc_attr($title_tag); ?> class="section-title <?php echo esc_attr($title_classes); ?>">
                        Galería de imágenes
                    </<?php echo esc_attr($title_tag); ?>>
                    <div class="gallery-controls">
                        <?php if ($this->settings['select_button_order'] === 'before') : ?>
                            <button type="button" class="button upload-gallery-images" data-field="<?php echo esc_attr($name); ?>">Seleccionar imágenes para galería</button>
                        <?php endif; ?>
                        <div id="gallery-images-preview-<?php echo esc_attr($name); ?>" class="images-preview"></div>
                        <?php if ($this->settings['select_button_order'] === 'after') : ?>
                            <button type="button" class="button upload-gallery-images" data-field="<?php echo esc_attr($name); ?>">Seleccionar imágenes para galería</button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="<?php echo esc_attr($name . '_gallery'); ?>" id="gallery-images-input-<?php echo esc_attr($name); ?>" class="jet-form-builder__field">
                </div>
            </div>
            
            <!-- Campo oculto para trackear el envío -->
            <input type="hidden" name="jfb_media_gallery_active" value="1">
            <input type="hidden" name="has_media_gallery" value="1">
        </div>
        
        <style>
            <?php echo $this->get_dynamic_styles(); ?>
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Procesar la presentación del formulario
     */
    public function process_form_submission($handler, $actions = null, $form_id = null) {
        $this->log_debug("=== INICIO PROCESS FORM SUBMISSION ===");
        
        // Verificar si tenemos el objeto handler
        if (!$handler || !is_object($handler)) {
            $this->log_debug("El handler no es un objeto válido");
            return;
        }
        
        // Obtener los datos del formulario
        $form_data = isset($handler->form_data) ? $handler->form_data : [];
        
        // Asegurarnos de que los campos de imágenes estén incluidos
        if (isset($_POST['media_gallery_featured'])) {
            $form_data['media_gallery_featured'] = $_POST['media_gallery_featured'];
        }
        if (isset($_POST['media_gallery_gallery'])) {
            $form_data['media_gallery_gallery'] = $_POST['media_gallery_gallery'];
        }
        
        $this->log_debug("Datos del formulario procesados: " . print_r($form_data, true));
        
        // Obtener el ID del post
        $post_id = $this->find_post_id($handler, $actions, $form_data);
        
        if (!$post_id) {
            $this->log_debug("No se pudo encontrar un ID de post válido");
            return;
        }
        
        $this->log_debug("ID de post encontrado: $post_id");
        
        // Procesar la imagen destacada
        if (!empty($form_data['media_gallery_featured'])) {
            $this->log_debug("Guardando imagen destacada: " . $form_data['media_gallery_featured']);
            set_post_thumbnail($post_id, intval($form_data['media_gallery_featured']));
        }
        
        // Procesar la galería
        if (!empty($form_data['media_gallery_gallery'])) {
            $gallery_ids = explode(',', $form_data['media_gallery_gallery']);
            $gallery_ids = array_map('intval', array_filter($gallery_ids));
            
            if (!empty($gallery_ids)) {
                $this->log_debug("Guardando galería: " . implode(', ', $gallery_ids));
                update_post_meta($post_id, 'ad_gallery', $gallery_ids);
            }
        }
        
        // Verificación final
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $gallery = get_post_meta($post_id, 'ad_gallery', true);
        
        $this->log_debug("=== VERIFICACIÓN FINAL ===");
        $this->log_debug("Thumbnail ID guardado: " . ($thumbnail_id ? $thumbnail_id : "no encontrado"));
        $this->log_debug("Galería guardada: " . (is_array($gallery) ? implode(', ', $gallery) : "no encontrada"));
        $this->log_debug("=== FIN PROCESS FORM SUBMISSION ===");
    }
    
    /**
     * Guardar imágenes después de insertar post
     */
    public function save_post_images($post_id, $form_data = []) {
        $this->log_debug("Hook save_post_images activado con post_id: $post_id");
        
        // Convertir objetos a arrays si es necesario
        if (is_object($form_data) && method_exists($form_data, 'get_form_data')) {
            $form_data = $form_data->get_form_data();
        } elseif (is_object($form_data) && method_exists($form_data, 'to_array')) {
            $form_data = $form_data->to_array();
        } elseif (empty($form_data) || !is_array($form_data)) {
            // Si no hay datos o no son un array, intentar obtenerlos de $_POST
            $form_data = $_POST;
        }
        
        $this->log_debug("Datos del formulario en save_post_images: " . print_r($form_data, true));
        $this->save_images_to_post($post_id, $form_data);
    }
    
    /**
     * Guardar imágenes después del envío del formulario
     */
    public function save_form_images($form_data, $form_id = null) {
        $this->log_debug("Hook save_form_images activado");
        
        // Intentar encontrar el ID del post a partir de los datos del formulario
        $post_id = null;
        
        // Buscar en los datos de respuesta estándar
        if (isset($form_data['inserted_post_id'])) {
            $post_id = $form_data['inserted_post_id'];
        } elseif (isset($form_data['post_id'])) {
            $post_id = $form_data['post_id'];
        }
        
        // Si no encontramos el ID, buscar el último post creado
        if (!$post_id) {
            $post_id = $this->find_last_post();
        }
        
        if (!$post_id) {
            $this->log_debug("No se pudo encontrar un ID de post válido en save_form_images");
            return;
        }
        
        $this->log_debug("ID de post encontrado en save_form_images: $post_id");
        
        // Convertir al formato adecuado si es necesario
        $data = is_array($form_data) ? $form_data : $_POST;
        
        $this->save_images_to_post($post_id, $data);
    }
    
    /**
     * Guardar al guardar directamente un post
     */
    public function save_on_direct_post_save($post_id, $post, $update) {
        // Solo procesar si estamos actualizando un post
        if (!$update || empty($_POST)) {
            return;
        }
        
        $this->log_debug("Hook save_on_direct_post_save activado para post_id: $post_id");
        $this->save_images_to_post($post_id, $_POST);
    }
    
    /**
     * Guardar después de todas las acciones de JetFormBuilder
     */
    public function save_after_actions($actions_handler, $request) {
        $this->log_debug("Hook save_after_actions activado");
        
        // Obtener el ID del post de las acciones
        $post_id = null;
        
        if (isset($actions_handler) && method_exists($actions_handler, 'get_response_data')) {
            $response_data = $actions_handler->get_response_data();
            
            if (isset($response_data['inserted_post_id'])) {
                $post_id = $response_data['inserted_post_id'];
            } elseif (isset($response_data['post_id'])) {
                $post_id = $response_data['post_id'];
            }
        }
        
        if (!$post_id) {
            $post_id = $this->find_last_post();
        }
        
        if (!$post_id) {
            $this->log_debug("No se pudo encontrar un ID de post válido en save_after_actions");
            return;
        }
        
        $this->log_debug("ID de post encontrado en save_after_actions: $post_id");
        
        $data = is_array($request) ? $request : $_POST;
        $this->save_images_to_post($post_id, $data);
    }
    
    /**
     * Método de emergencia para guardar imágenes al final del procesamiento
     */
    public function emergency_save_images() {
        // Solo procesar si estamos en un envío de formulario de JetFormBuilder
        if (!isset($_POST['jet_form_builder_submit']) || !isset($_POST['has_media_gallery'])) {
            return;
        }
        
        $this->log_debug("Método de emergencia activado para guardar imágenes");
        
        // Intentar encontrar el ID del post más reciente del tipo correcto
        $post_id = $this->find_last_post();
        
        if (!$post_id) {
            $this->log_debug("No se pudo encontrar un ID de post válido en emergency_save_images");
            return;
        }
        
        $this->log_debug("ID de post encontrado en emergency_save_images: $post_id");
        $this->save_images_to_post($post_id, $_POST);
    }
    
    /**
     * Guardar imágenes en un post específico
     */
    private function save_images_to_post($post_id, $form_data) {
        $this->log_debug("=== INICIO DE GUARDADO DE IMÁGENES ===");
        $this->log_debug("Post ID: $post_id");
        $this->log_debug("Datos del formulario completos: " . print_r($form_data, true));
        
        if (!$post_id) {
            $this->log_debug("Error: Post ID no válido");
            return false;
        }
        
        // Asegurarnos de que tenemos los datos de imágenes
        if (isset($_POST['media_gallery_featured'])) {
            $form_data['media_gallery_featured'] = $_POST['media_gallery_featured'];
        }
        if (isset($_POST['media_gallery_gallery'])) {
            $form_data['media_gallery_gallery'] = $_POST['media_gallery_gallery'];
        }
        
        // Verificar permisos
        if (!current_user_can('upload_files')) {
            $this->log_debug("Error: El usuario no tiene permisos para subir archivos");
            return false;
        }
        
        // Verificar que el post existe
        $post = get_post($post_id);
        if (!$post) {
            $this->log_debug("Error: Post no encontrado");
            return false;
        }
        
        $success = true;
        
        // Procesar imagen destacada
        if (!empty($form_data['media_gallery_featured'])) {
            $featured_id = intval($form_data['media_gallery_featured']);
            $this->log_debug("Intentando guardar imagen destacada con ID: $featured_id");
            
            // Verificar que la imagen existe
            $attachment = get_post($featured_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                $result = set_post_thumbnail($post_id, $featured_id);
                $this->log_debug("Resultado set_post_thumbnail: " . ($result ? "éxito" : "fallo"));
                
                if (!$result) {
                    // Intentar forzar el guardado
                    update_post_meta($post_id, '_thumbnail_id', $featured_id);
                }
            }
        }
        
        // Procesar galería
        if (!empty($form_data['media_gallery_gallery'])) {
            $gallery_ids = is_array($form_data['media_gallery_gallery']) 
                ? $form_data['media_gallery_gallery'] 
                : explode(',', $form_data['media_gallery_gallery']);
            
            $gallery_ids = array_map('intval', array_filter($gallery_ids));
            
            if (!empty($gallery_ids)) {
                $this->log_debug("Intentando guardar galería con IDs: " . implode(', ', $gallery_ids));
                
                // Limpiar meta existente
                delete_post_meta($post_id, 'ad_gallery');
                
                // Guardar nueva galería
                $result = add_post_meta($post_id, 'ad_gallery', $gallery_ids, true);
                
                if (!$result) {
                    $result = update_post_meta($post_id, 'ad_gallery', $gallery_ids);
                }
                
                $this->log_debug("Resultado guardado galería: " . ($result ? "éxito" : "fallo"));
            }
        }
        
        // Verificación final
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $gallery = get_post_meta($post_id, 'ad_gallery', true);
        
        $this->log_debug("=== VERIFICACIÓN FINAL ===");
        $this->log_debug("Thumbnail ID: " . ($thumbnail_id ? $thumbnail_id : "no encontrado"));
        $this->log_debug("Galería: " . (is_array($gallery) ? implode(', ', $gallery) : "no encontrada"));
        
        // Forzar limpieza de caché
        clean_post_cache($post_id);
        wp_cache_delete($post_id, 'posts');
        wp_cache_delete($post_id, 'post_meta');
        
        $this->log_debug("=== FIN DE GUARDADO DE IMÁGENES ===");
        
        return $success;
    }
    
    /**
     * Encontrar el ID del post a partir de varias fuentes
     */
    private function find_post_id($handler = null, $actions = null, $form_data = []) {
        $post_id = null;
        
        // Método 1: Buscar en acciones
        if (is_array($actions)) {
            foreach ($actions as $action) {
                if (isset($action['type']) && $action['type'] === 'insert_post' && isset($action['post_id'])) {
                    $post_id = $action['post_id'];
                    $this->log_debug("Post ID encontrado en acciones: $post_id");
                    break;
                }
            }
        }
        
        // Método 2: Buscar en datos de respuesta del handler
        if (!$post_id && isset($handler) && isset($handler->action_handler) && isset($handler->action_handler->response_data)) {
            $response_data = $handler->action_handler->response_data;
            
            if (isset($response_data['inserted_post_id'])) {
                $post_id = $response_data['inserted_post_id'];
                $this->log_debug("Post ID encontrado en response_data (inserted_post_id): $post_id");
            } elseif (isset($response_data['post_id'])) {
                $post_id = $response_data['post_id'];
                $this->log_debug("Post ID encontrado en response_data (post_id): $post_id");
            }
        }
        
        // Método 3: Buscar en form_data
        if (!$post_id && is_array($form_data)) {
            if (isset($form_data['inserted_post_id'])) {
                $post_id = $form_data['inserted_post_id'];
                $this->log_debug("Post ID encontrado en form_data (inserted_post_id): $post_id");
            } elseif (isset($form_data['post_id'])) {
                $post_id = $form_data['post_id'];
                $this->log_debug("Post ID encontrado en form_data (post_id): $post_id");
            }
        }
        
        // Método 4: Buscar el último post insertado
        if (!$post_id) {
            $post_id = $this->find_last_post();
            if ($post_id) {
                $this->log_debug("Post ID encontrado buscando el último post: $post_id");
            }
        }
        
        return $post_id;
    }
    
    /**
     * Encontrar el último post insertado del tipo correcto
     */
    private function find_last_post() {
        global $wpdb;
        $post_type = 'singlecar'; // El tipo de post esperado
        
        $latest_post = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_status != 'trash' ORDER BY ID DESC LIMIT 1",
                $post_type
            )
        );
        
        return $latest_post ? $latest_post->ID : null;
    }
    
    /**
     * Cargar scripts y estilos
     */
    public function enqueue_scripts() {
        if (!is_admin()) {
            wp_enqueue_media();
            wp_add_inline_script('jquery', $this->get_inline_script());
        }
    }
    
    /**
     * Obtener el script inline para el campo
     */
    private function get_inline_script() {
        return "
        jQuery(document).ready(function($) {
            if (typeof wp.media === 'undefined') {
                console.error('Error: wp.media no está disponible. Asegúrate de cargar los scripts de medios de WordPress.');
                return;
            }
            
            // Media Uploader para la imagen destacada
            $(document).on('click', '.upload-featured-image', function(e) {
                e.preventDefault();
                
                var fieldName = $(this).data('field');
                var previewContainer = $('#featured-image-preview-' + fieldName);
                var removeButton = previewContainer.find('.remove-featured-image');
                
                var featuredImageFrame = wp.media({
                    title: 'Seleccionar imagen destacada',
                    button: {
                        text: 'Usar esta imagen'
                    },
                    multiple: false
                });
                
                featuredImageFrame.on('select', function() {
                    var attachment = featuredImageFrame.state().get('selection').first().toJSON();
                    previewContainer.css('background-image', 'url(' + attachment.url + ')');
                    previewContainer.addClass('has-image');
                    $('#featured-image-input-' + fieldName).val(attachment.id);
                    removeButton.show();
                });
                
                featuredImageFrame.open();
            });
            
            // Eliminar imagen destacada
            $(document).on('click', '.remove-featured-image', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var fieldName = $(this).closest('.featured-image-container').find('.upload-featured-image').data('field');
                var previewContainer = $('#featured-image-preview-' + fieldName);
                
                previewContainer.css('background-image', '');
                previewContainer.removeClass('has-image');
                $('#featured-image-input-' + fieldName).val('');
                $(this).hide();
            });
            
            // Media Uploader para la galería
            $(document).on('click', '.upload-gallery-images', function(e) {
                e.preventDefault();
                
                var fieldName = $(this).data('field');
                
                var galleryFrame = wp.media({
                    title: 'Seleccionar imágenes para galería',
                    button: {
                        text: 'Agregar a galería'
                    },
                    multiple: true
                });
                
                galleryFrame.on('select', function() {
                    var attachments = galleryFrame.state().get('selection').toJSON();
                    var galleryIds = $('#gallery-images-input-' + fieldName).val() ? $('#gallery-images-input-' + fieldName).val().split(',') : [];
                    
                    $.each(attachments, function(index, attachment) {
                        if (!galleryIds.includes(attachment.id.toString())) {
                            galleryIds.push(attachment.id);
                            
                            $('#gallery-images-preview-' + fieldName).append(
                                '<div class=\"gallery-image\" data-id=\"' + attachment.id + '\" style=\"background-image: url(' + attachment.url + ')\">' +
                                '<div class=\"image-overlay\"></div>' +
                                '<button type=\"button\" class=\"remove-image\">×</button>' +
                                '</div>'
                            );
                        }
                    });
                    
                    $('#gallery-images-input-' + fieldName).val(galleryIds.join(','));
                });
                
                galleryFrame.open();
            });
            
            // Eliminar imagen de la galería
            $(document).on('click', '.remove-image', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var fieldName = $(this).closest('.gallery-container').find('.upload-gallery-images').data('field');
                var imageContainer = $(this).parent();
                var imageId = imageContainer.data('id');
                var galleryIds = $('#gallery-images-input-' + fieldName).val().split(',');
                
                galleryIds = galleryIds.filter(function(id) {
                    return id != imageId;
                });
                
                $('#gallery-images-input-' + fieldName).val(galleryIds.join(','));
                imageContainer.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        });
        ";
    }
    
    /**
     * Registrar mensaje de depuración
     */
    private function log_debug($message) {
        if (!$this->debug_mode) {
            return;
        }
        
        // Usar wp-content directamente para los logs
        $log_file = WP_CONTENT_DIR . '/debug-media-gallery.log';
        
        // Formatear el mensaje
        $formatted_message = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            wp_get_current_user()->user_login,
            is_array($message) || is_object($message) ? print_r($message, true) : $message
        );
        
        // Escribir mensaje
        error_log($formatted_message, 3, $log_file);
        
        // También escribir en el log de WordPress para depuración
        error_log('JetForm Media Gallery: ' . $formatted_message);
    }

    /**
     * Verificar que los metadatos se guardaron correctamente
     */
    public function verify_post_meta($meta_id, $post_id, $meta_key, $meta_value) {
        if ($meta_key === 'ad_gallery') {
            $this->log_debug("Verificando metadatos después de guardar");
            $this->log_debug("Meta ID: $meta_id");
            $this->log_debug("Post ID: $post_id");
            $this->log_debug("Meta Key: $meta_key");
            $this->log_debug("Meta Value: " . print_r($meta_value, true));
            
            // Verificar que los datos se guardaron correctamente
            $saved_value = get_post_meta($post_id, $meta_key, true);
            if ($saved_value !== $meta_value) {
                $this->log_debug("Error: Los metadatos no se guardaron correctamente");
                $this->log_debug("Valor guardado: " . print_r($saved_value, true));
                
                // Intentar guardar nuevamente
                update_post_meta($post_id, $meta_key, $meta_value);
            }
        }
    }

    /**
     * Debug de datos del formulario
     */
    public function debug_form_data($form_data, $handler = null) {
        $this->log_debug("=== DEBUG FORM DATA ===");
        $this->log_debug("Form Data: " . print_r($form_data, true));
        $this->log_debug("POST Data: " . print_r($_POST, true));
        $this->log_debug("Files: " . print_r($_FILES, true));
        if ($handler) {
            $this->log_debug("Handler: " . print_r($handler, true));
        }
        return $form_data;
    }

    private function get_dynamic_styles() {
        $width = absint($this->settings['image_width']);
        $height = absint($this->settings['image_height']);
        $use_theme = !empty($this->settings['use_theme_buttons']);
        $position = $this->settings['remove_button_position'];
        $remove_bg = sanitize_hex_color($this->settings['remove_button_bg']);
        $remove_color = sanitize_hex_color($this->settings['remove_button_color']);
        $button_size = absint($this->settings['remove_button_size']);
        $overlay_opacity = floatval($this->settings['overlay_opacity']);
        $overlay_color = sanitize_hex_color($this->settings['overlay_color']);
        $title_size = absint($this->settings['title_size']);
        
        // Posicionamiento del botón eliminar
        $position_styles = '';
        switch ($position) {
            case 'top-right':
                $position_styles = 'top: 10px; right: 10px; transform: none;';
                break;
            case 'top-left':
                $position_styles = 'top: 10px; left: 10px; transform: none;';
                break;
            default: // center
                $position_styles = 'top: 50%; left: 50%; transform: translate(-50%, -50%);';
                break;
        }

        // Orden del botón (z-index)
        $button_z_index = $this->settings['select_button_order'] === 'before' ? '2' : '1';
        $image_z_index = $this->settings['select_button_order'] === 'before' ? '1' : '2';
        
        return "
            .media-gallery-container {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 20px;
                width: 100%;
            }
            
            .featured-image-container, .gallery-container {
                flex: 1;
                min-width: 300px;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
                background: #fff;
            }

            .section-title {
                font-size: {$title_size}px;
                margin-bottom: 15px;
            }
            
            .image-controls, .gallery-controls {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            
            .image-preview {
                display: none;
                width: {$width}px;
                height: {$height}px;
                position: relative;
                margin-top: 10px;
            }
            
            .image-preview.has-image {
                display: block;
                background-size: cover;
                background-position: center;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .image-overlay {
                display: none;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: {$overlay_color};
                opacity: 0;
                transition: opacity 0.2s ease;
                border-radius: 4px;
            }
            
            .gallery-image:hover .image-overlay,
            .image-preview:hover .image-overlay {
                display: block;
                opacity: {$overlay_opacity};
            }
            
            .images-preview {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-top: 10px;
            }
            
            .gallery-image {
                width: {$width}px;
                height: {$height}px;
                background-size: cover;
                background-position: center;
                position: relative;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .remove-image, .remove-featured-image {
                position: absolute;
                {$position_styles}
                background: {$remove_bg};
                color: {$remove_color};
                border: none;
                border-radius: 50%;
                width: {$button_size}px;
                height: {$button_size}px;
                font-size: " . ($button_size * 0.66) . "px;
                line-height: 1;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                opacity: 0;
                z-index: {$button_z_index};
            }
            
            .gallery-image:hover .remove-image,
            .image-preview:hover .remove-featured-image {
                opacity: 1;
            }
            
            .remove-image:hover, .remove-featured-image:hover {
                background: {$remove_bg};
                transform: " . ($position === 'center' ? 'translate(-50%, -50%) scale(1.1)' : 'scale(1.1)') . ";
            }
            " . (!$use_theme ? "
            .button {
                background: #0073aa;
                color: #fff;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .button:hover {
                background: #005177;
            }
            " : "") . "
            
            @media (max-width: 768px) {
                .media-gallery-container {
                    flex-direction: column;
                }
                
                .featured-image-container, .gallery-container {
                    width: 100%;
                    min-width: auto;
                }
                
                .image-preview, .gallery-image {
                    width: {$width}px;
                    height: {$height}px;
                    margin: 0 auto;
                }
            }
        ";
    }
}

// Inicializar la clase
$jetform_media_gallery = new JetForm_Media_Gallery_Field();