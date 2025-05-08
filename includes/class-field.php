<?php
/**
 * Clase principal para el campo de galería de medios
 *
 * @package JetForm_Media_Gallery
 * @since 1.0.0
 */

// No ejecutar directamente
if (!defined('ABSPATH')) {
    die('No direct script access allowed');
}

class JetForm_Media_Gallery_Field {
    
    /**
     * Instancia de la clase principal
     */
    private $main;
    
    /**
     * Constructor
     */
    public function __construct($main = null) {
        $this->main = $main;
    }
    
    /**
     * Shortcode para renderizar el campo
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'field' => '',
            'required' => '0',
        ], $atts, 'media_gallery_field');
        
        // Validar que se especificó un campo
        if (empty($atts['field'])) {
            return '<p style="color: red;">Error: Debe especificar un campo usando el atributo "field"</p>';
        }
        
        $settings = $this->main->get_settings();
        
        // Obtener la configuración de campos
        $fields = isset($settings['image_fields']) ? $settings['image_fields'] : [];
        
        // Buscar el campo especificado
        $field_config = null;
        foreach ($fields as $field) {
            if ($field['name'] === $atts['field']) {
                $field_config = $field;
                break;
            }
        }
        
        // Validar que el campo existe
        if (!$field_config) {
            return '<p style="color: red;">Error: El campo "' . esc_html($atts['field']) . '" no está configurado</p>';
        }
        
        // Determinar si el campo es requerido
        $required = filter_var($atts['required'], FILTER_VALIDATE_BOOLEAN) || !empty($field_config['required']);
        $required_attr = $required ? 'required' : '';
        
        // Verificar si estamos en modo edición y cargar valores existentes
        $post_id = $this->get_current_post_id();
        $initial_value = '';
        $initial_images = [];
        
        if ($post_id) {
            if ($field_config['type'] === 'single') {
                // Obtener imagen destacada si es ese tipo de campo
                if ($field_config['meta_key'] === '_thumbnail_id') {
                    $initial_value = get_post_thumbnail_id($post_id);
                } else {
                    $initial_value = get_post_meta($post_id, $field_config['meta_key'], true);
                }
            } else {
                // Obtener galería
                $gallery = get_post_meta($post_id, $field_config['meta_key'], true);
                if (!empty($gallery)) {
                    $initial_value = is_array($gallery) ? implode(',', $gallery) : $gallery;
                    $initial_images = is_array($gallery) ? $gallery : explode(',', $gallery);
                }
            }
        }
        
        ob_start();
        ?>
        <div class="jet-form-builder__field media-gallery-field">
            <label class="jet-form-builder__label">
                <?php echo esc_html($field_config['label']); ?>
                <?php if ($required) : ?>
                    <span class="jet-form-builder__required">*</span>
                <?php endif; ?>
            </label>
            
            <div class="media-gallery-container">
                <?php if ($field_config['type'] === 'single') : ?>
                    <!-- Campo de imagen única -->
                    <div class="featured-image-container">
                        <div class="image-controls">
                            <?php if ($settings['select_button_order'] === 'before') : ?>
                                <button type="button" class="button upload-featured-image" data-field="<?php echo esc_attr($field_config['name']); ?>">
                                    Seleccionar imagen
                                </button>
                            <?php endif; ?>
                            
                            <?php if (!empty($initial_value)) : ?>
                                <div id="featured-image-preview-<?php echo esc_attr($field_config['name']); ?>" class="image-preview has-image"
                                     style="background-image: url('<?php echo wp_get_attachment_url($initial_value); ?>');">
                                    <div class="image-overlay"></div>
                                    <button type="button" class="remove-featured-image">×</button>
                                </div>
                            <?php else: ?>
                                <div id="featured-image-preview-<?php echo esc_attr($field_config['name']); ?>" class="image-preview"
                                     style="display:none;">
                                    <div class="image-overlay"></div>
                                    <button type="button" class="remove-featured-image">×</button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($settings['select_button_order'] === 'after') : ?>
                                <button type="button" class="button upload-featured-image" data-field="<?php echo esc_attr($field_config['name']); ?>">
                                    Seleccionar imagen
                                </button>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" 
                               name="<?php echo esc_attr($field_config['name']); ?>" 
                               id="featured-image-input-<?php echo esc_attr($field_config['name']); ?>" 
                               class="jet-form-builder__field"
                               data-meta-type="<?php echo esc_attr($field_config['meta_type']); ?>"
                               data-meta-key="<?php echo esc_attr($field_config['meta_key']); ?>"
                               value="<?php echo esc_attr($initial_value); ?>"
                               <?php echo $required_attr; ?>>
                    </div>
                <?php else : ?>
                    <!-- Campo de galería -->
                    <div class="gallery-container">
                        <div class="gallery-controls">
                            <?php if ($settings['select_button_order'] === 'before') : ?>
                                <button type="button" class="button upload-gallery-images" data-field="<?php echo esc_attr($field_config['name']); ?>">
                                    Seleccionar imágenes
                                </button>
                            <?php endif; ?>
                            
                            <?php if (!empty($initial_images)) : ?>
                                <div id="gallery-images-preview-<?php echo esc_attr($field_config['name']); ?>" class="images-preview">
                                    <?php foreach ($initial_images as $img_id) : 
                                        if (empty($img_id)) continue;
                                        $img_url = wp_get_attachment_url($img_id); 
                                        if (!$img_url) continue;
                                    ?>
                                        <div class="gallery-image" data-id="<?php echo esc_attr($img_id); ?>" style="background-image: url('<?php echo esc_url($img_url); ?>')">
                                            <div class="image-overlay"></div>
                                            <button type="button" class="remove-image">×</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div id="gallery-images-preview-<?php echo esc_attr($field_config['name']); ?>" class="images-preview" style="display:none;"></div>
                            <?php endif; ?>
                            
                            <?php if ($settings['select_button_order'] === 'after') : ?>
                                <button type="button" class="button upload-gallery-images" data-field="<?php echo esc_attr($field_config['name']); ?>">
                                    Seleccionar imágenes
                                </button>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" 
                               name="<?php echo esc_attr($field_config['name']); ?>" 
                               id="gallery-images-input-<?php echo esc_attr($field_config['name']); ?>" 
                               class="jet-form-builder__field"
                               data-meta-type="<?php echo esc_attr($field_config['meta_type']); ?>"
                               data-meta-key="<?php echo esc_attr($field_config['meta_key']); ?>"
                               value="<?php echo esc_attr($initial_value); ?>"
                               <?php echo $required_attr; ?>>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
            <?php echo $this->main->get_styles()->get_dynamic_styles(); ?>
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Cargar scripts y estilos
     */
    public function enqueue_scripts() {
        if (!is_admin()) {
            // Asegurarse de que los scripts de WordPress necesarios estén disponibles
            wp_enqueue_media();
            
            // Cargar jQuery UI para la funcionalidad de ordenamiento
            wp_enqueue_script('jquery-ui-sortable');
            
            // Registrar dependencias necesarias para el objeto wp
            wp_enqueue_script('wp-api');
            
            // Crear un script propio para el plugin en lugar de inline
            wp_register_script(
                'jetform-media-gallery',
                JFB_MEDIA_GALLERY_URL . 'js/media-gallery.js',
                ['jquery', 'wp-api', 'media-editor'],
                JFB_MEDIA_GALLERY_VERSION,
                true
            );
            
            // Localizar el script con datos que pueda necesitar
            wp_localize_script('jetform-media-gallery', 'JetFormMediaGallery', [
                'i18n' => [
                    'selectFeaturedImage' => __('Seleccionar imagen destacada', 'jetform-media-gallery'),
                    'useThisImage' => __('Usar esta imagen', 'jetform-media-gallery'),
                    'selectGalleryImages' => __('Seleccionar imágenes para galería', 'jetform-media-gallery'),
                    'addToGallery' => __('Añadir a la galería', 'jetform-media-gallery')
                ]
            ]);
            
            // Encolar el script
            wp_enqueue_script('jetform-media-gallery');
        }
    }
    
    /**
     * Obtener el script inline para el campo
     */
    private function get_inline_script() {
        return "
        jQuery(document).ready(function($) {
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                console.error('Error: wp.media no está disponible. Asegúrate de cargar los scripts de medios de WordPress.');
                return;
            }
            
            // Inicializar campos con valores existentes si estamos en modo edición
            $('.media-gallery-field').each(function() {
                // Comprobar si hay un valor inicial en los campos hidden
                var inputField = $(this).find('input[type=\"hidden\"]');
                var fieldName = '';
                
                // Procesar campos de imagen destacada
                if ($(this).find('.featured-image-container').length > 0) {
                    fieldName = $(this).find('.upload-featured-image').data('field');
                    var previewContainer = $('#featured-image-preview-' + fieldName);
                    var removeButton = previewContainer.find('.remove-featured-image');
                    
                    // Si el campo tiene valor, mostrar la imagen y el botón de eliminar
                    if (inputField.val()) {
                        previewContainer.addClass('has-image');
                        removeButton.show();
                    }
                }
                
                // Procesar campos de galería
                if ($(this).find('.gallery-container').length > 0) {
                    fieldName = $(this).find('.upload-gallery-images').data('field');
                    var galleryPreview = $('#gallery-images-preview-' + fieldName);
                    
                    // Asegurarse de que los eventos click estén asignados a los botones de eliminar
                    galleryPreview.find('.remove-image').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
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
                }
            });
            
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
            
            // Agregar un campo oculto para indicar que el formulario contiene campos de media gallery
            if ($('.media-gallery-field').length && $('form').length) {
                $('form').append('<input type=\"hidden\" name=\"has_media_gallery\" value=\"1\">');
            }
        });
        ";
    }
    
    /**
     * Obtener el ID del post actual
     * 
     * Verifica si estamos en modo edición y obtiene el ID del post
     */
    private function get_current_post_id() {
        global $wp;
        
        // Verificar si estamos editando un post existente a través de JetFormBuilder
        if (isset($_REQUEST['_post_id']) && !empty($_REQUEST['_post_id'])) {
            $this->main->log_debug("Post ID encontrado en _post_id: " . intval($_REQUEST['_post_id']));
            return intval($_REQUEST['_post_id']);
        }
        
        // Verificar si estamos en modo edición en una página de JetEngine
        if (isset($_GET['post_id']) && !empty($_GET['post_id'])) {
            $this->main->log_debug("Post ID encontrado en GET post_id: " . intval($_GET['post_id']));
            return intval($_GET['post_id']);
        }
        
        // Verificar si estamos en el panel de edición de WordPress
        if (isset($_GET['post']) && !empty($_GET['post'])) {
            $this->main->log_debug("Post ID encontrado en GET post: " . intval($_GET['post']));
            return intval($_GET['post']);
        }
        
        // Verificar específicamente para JetFormBuilder
        $current_url = home_url(add_query_arg(array(), $wp->request));
        if (strpos($current_url, 'edit=') !== false) {
            preg_match('/edit=(\d+)/', $current_url, $matches);
            if (!empty($matches[1])) {
                $this->main->log_debug("Post ID encontrado en URL edit: " . intval($matches[1]));
                return intval($matches[1]);
            }
        }
        
        // Verificar parámetro en la URL
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $this->main->log_debug("Post ID encontrado en GET edit: " . intval($_GET['edit']));
            return intval($_GET['edit']);
        }
        
        // Verificar si hay un post_id en POST
        if (isset($_POST['post_id']) && !empty($_POST['post_id'])) {
            $this->main->log_debug("Post ID encontrado en POST post_id: " . intval($_POST['post_id']));
            return intval($_POST['post_id']);
        }
        
        // En caso de funciones de JetEngine/Elementor
        if (function_exists('jet_engine') && isset(jet_engine()->listings) && method_exists(jet_engine()->listings, 'get_current_object')) {
            $current_object = jet_engine()->listings->get_current_object();
            if ($current_object && is_object($current_object) && isset($current_object->ID)) {
                $this->main->log_debug("Post ID encontrado en JetEngine current_object: " . intval($current_object->ID));
                return intval($current_object->ID);
            }
        }
        
        // Verificar si estamos en una página single
        if (is_singular()) {
            $post_id = get_the_ID();
            if ($post_id) {
                $this->main->log_debug("Post ID encontrado en is_singular: " . intval($post_id));
                return intval($post_id);
            }
        }
        
        $this->main->log_debug("No se pudo determinar un Post ID");
        return null;
    }
    
    /**
     * Establecer la instancia principal
     */
    public function set_main($main) {
        $this->main = $main;
    }
} 