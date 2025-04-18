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
                            
                            <div id="featured-image-preview-<?php echo esc_attr($field_config['name']); ?>" class="image-preview">
                                <div class="image-overlay"></div>
                                <button type="button" class="remove-featured-image" style="display: none;">×</button>
                            </div>
                            
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
                            
                            <div id="gallery-images-preview-<?php echo esc_attr($field_config['name']); ?>" class="images-preview"></div>
                            
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
     * Establecer la instancia principal
     */
    public function set_main($main) {
        $this->main = $main;
    }
} 