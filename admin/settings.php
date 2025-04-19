<?php
class JetForm_Media_Gallery_Admin {
    private $option_name = 'jetform_media_gallery_settings';
    private $active_tab;
    private $log_file;
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Configurar archivo de log
        $this->log_file = WP_CONTENT_DIR . '/debug-media-gallery.log';
        
        // Set active tab
        $this->active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        
        // Manejar acciones de logs
        add_action('admin_init', [$this, 'handle_log_actions']);
    }
    
    public function add_settings_page() {
        add_submenu_page(
            'options-general.php',
            'JFBuilder Media Fields',
            'JFBuilder Media Fields',
            'manage_options',
            'jetform-media-gallery',
            [$this, 'render_settings_page']
        );
    }
    
    public function register_settings() {
        // Registrar una única opción para todas las configuraciones
        register_setting($this->option_name, $this->option_name, [$this, 'sanitize_settings']);
        
        // Sección de configuración general
        add_settings_section(
            'general_settings',
            __('Configuración General', 'jetform-media-gallery'),
            null,
            'jetform-media-gallery-general'
        );
        
        // Campos de configuración general
        add_settings_field(
            'image_size',
            __('Tamaño de imágenes', 'jetform-media-gallery'),
            [$this, 'render_image_size_field'],
            'jetform-media-gallery-general',
            'general_settings'
        );
        
        add_settings_field(
            'button_style',
            __('Estilo de botones', 'jetform-media-gallery'),
            [$this, 'render_button_style_field'],
            'jetform-media-gallery-general',
            'general_settings'
        );
        
        add_settings_field(
            'remove_button_position',
            __('Posición del botón eliminar', 'jetform-media-gallery'),
            [$this, 'render_remove_button_position_field'],
            'jetform-media-gallery-general',
            'general_settings'
        );
        
        add_settings_field(
            'remove_button_size',
            __('Tamaño del botón eliminar', 'jetform-media-gallery'),
            [$this, 'render_remove_button_size_field'],
            'jetform-media-gallery-general',
            'general_settings'
        );

        add_settings_field(
            'overlay_settings',
            __('Configuración del Overlay', 'jetform-media-gallery'),
            [$this, 'render_overlay_settings_field'],
            'jetform-media-gallery-general',
            'general_settings'
        );

        add_settings_field(
            'select_button_order',
            __('Orden del botón seleccionar', 'jetform-media-gallery'),
            [$this, 'render_select_button_order_field'],
            'jetform-media-gallery-general',
            'general_settings'
        );

        add_settings_field(
            'title_settings',
            __('Configuración del título', 'jetform-media-gallery'),
            [$this, 'render_title_settings_field'],
            'jetform-media-gallery-general',
            'general_settings'
        );
        
        add_settings_field(
            'colors',
            __('Colores', 'jetform-media-gallery'),
            [$this, 'render_colors_field'],
            'jetform-media-gallery-general',
            'general_settings'
        );

        // Sección de campos
        add_settings_section(
            'fields_settings',
            __('Configuración de Campos', 'jetform-media-gallery'),
            null,
            'jetform-media-gallery-fields'
        );

        add_settings_field(
            'image_fields',
            __('Campos de Imágenes', 'jetform-media-gallery'),
            [$this, 'render_image_fields'],
            'jetform-media-gallery-fields',
            'fields_settings'
        );

        // Añadir sección de depuración
        add_settings_section(
            'debug_settings',
            __('Configuración de Depuración', 'jetform-media-gallery'),
            [$this, 'render_debug_section'],
            'jetform-media-gallery'
        );

        // Campo para activar/desactivar debug
        add_settings_field(
            'debug_mode',
            __('Modo de depuración', 'jetform-media-gallery'),
            [$this, 'render_debug_mode_field'],
            'jetform-media-gallery',
            'debug_settings'
        );

        // Campo para tamaño máximo del log
        add_settings_field(
            'max_log_size',
            __('Tamaño máximo del log (MB)', 'jetform-media-gallery'),
            [$this, 'render_max_log_size_field'],
            'jetform-media-gallery',
            'debug_settings'
        );

        // Campo para número máximo de archivos
        add_settings_field(
            'max_log_files',
            __('Número máximo de archivos de log', 'jetform-media-gallery'),
            [$this, 'render_max_log_files_field'],
            'jetform-media-gallery',
            'debug_settings'
        );
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Obtener las opciones guardadas
        $options = get_option($this->option_name);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="admin-container" style="display: grid; grid-template-columns: 1fr 300px; gap: 20px; margin-top: 20px;">
                <div class="main-content">
                    <!-- Tabs -->
                    <nav class="nav-tab-wrapper">
                        <a href="?page=jetform-media-gallery&tab=general" class="nav-tab <?php echo $this->active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
                            <?php _e('Configuración General', 'jetform-media-gallery'); ?>
                        </a>
                        <a href="?page=jetform-media-gallery&tab=fields" class="nav-tab <?php echo $this->active_tab == 'fields' ? 'nav-tab-active' : ''; ?>">
                            <?php _e('Configuración de Campos', 'jetform-media-gallery'); ?>
                        </a>
                        <a href="?page=jetform-media-gallery&tab=logs" class="nav-tab <?php echo $this->active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">
                            <?php _e('Logs', 'jetform-media-gallery'); ?>
                        </a>
                    </nav>

                    <?php if ($this->active_tab == 'logs') : ?>
                        <?php $this->render_logs_page(); ?>
                    <?php else: ?>
                        <form action="options.php" method="post">
                            <?php
                            settings_fields($this->option_name);
                            
                            if ($this->active_tab == 'general') {
                                do_settings_sections('jetform-media-gallery-general');
                            } else {
                                do_settings_sections('jetform-media-gallery-fields');
                            }
                            
                            submit_button();
                            ?>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Plugin Info Box -->
                    <div class="card">
                        <h2><?php _e('About JFBuilder Media Fields', 'jetform-media-gallery'); ?></h2>
                        <p><?php _e('This plugin adds media field capabilities to JetFormBuilder, allowing you to handle featured images and galleries in your forms.', 'jetform-media-gallery'); ?></p>
                        <p><a href="https://github.com/yosnap/jetform-media-gallery" target="_blank" class="button button-secondary">
                            <span class="dashicons dashicons-book" style="vertical-align: text-top;"></span>
                            <?php _e('View Documentation', 'jetform-media-gallery'); ?>
                        </a></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_image_size_field() {
        $options = get_option($this->option_name);
        $width = isset($options['image_width']) ? $options['image_width'] : 150;
        $height = isset($options['image_height']) ? $options['image_height'] : 150;
        ?>
        <input type="number" 
               name="<?php echo $this->option_name; ?>[image_width]" 
               value="<?php echo esc_attr($width); ?>" 
               min="50" 
               max="500"> px (ancho) ×
        <input type="number" 
               name="<?php echo $this->option_name; ?>[image_height]" 
               value="<?php echo esc_attr($height); ?>" 
               min="50" 
               max="500"> px (alto)
        <p class="description">Tamaño de las imágenes en la galería y vista previa</p>
        <?php
    }
    
    public function render_button_style_field() {
        $options = get_option($this->option_name);
        $use_theme = isset($options['use_theme_buttons']) ? $options['use_theme_buttons'] : false;
        $button_bg = isset($options['button_bg']) ? $options['button_bg'] : '#0073aa';
        $button_text = isset($options['button_text']) ? $options['button_text'] : '#ffffff';
        $button_hover_bg = isset($options['button_hover_bg']) ? $options['button_hover_bg'] : '#005c8a';
        $button_border_radius = isset($options['button_border_radius']) ? $options['button_border_radius'] : 4;
        $button_padding = isset($options['button_padding']) ? $options['button_padding'] : '10px 16px';
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo $this->option_name; ?>[use_theme_buttons]" 
                   value="1" 
                   id="use-theme-buttons"
                   <?php checked($use_theme, true); ?>>
            Usar estilos de botones del tema
        </label>

        <div id="custom-button-options" style="margin-top: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; <?php echo $use_theme ? 'display: none;' : ''; ?>">
            <h4 style="margin-top: 0;">Personalizar botones</h4>
            
            <p>
                <label>
                    Color de fondo:
                    <input type="color" 
                           name="<?php echo $this->option_name; ?>[button_bg]" 
                           value="<?php echo esc_attr($button_bg); ?>">
                </label>
            </p>
            
            <p>
                <label>
                    Color del texto:
                    <input type="color" 
                           name="<?php echo $this->option_name; ?>[button_text]" 
                           value="<?php echo esc_attr($button_text); ?>">
                </label>
            </p>
            
            <p>
                <label>
                    Color de fondo (hover):
                    <input type="color" 
                           name="<?php echo $this->option_name; ?>[button_hover_bg]" 
                           value="<?php echo esc_attr($button_hover_bg); ?>">
                </label>
            </p>
            
            <p>
                <label>
                    Radio de borde (px):
                    <input type="number" 
                           name="<?php echo $this->option_name; ?>[button_border_radius]" 
                           value="<?php echo esc_attr($button_border_radius); ?>"
                           min="0"
                           max="50">
                </label>
            </p>
            
            <p>
                <label>
                    Padding (formato CSS):
                    <input type="text" 
                           name="<?php echo $this->option_name; ?>[button_padding]" 
                           value="<?php echo esc_attr($button_padding); ?>"
                           placeholder="10px 16px">
                </label>
                <span class="description">Ejemplo: '10px 16px' (vertical horizontal)</span>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#use-theme-buttons').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#custom-button-options').slideUp();
                } else {
                    $('#custom-button-options').slideDown();
                }
            });
        });
        </script>
        <?php
    }
    
    public function render_remove_button_position_field() {
        $options = get_option($this->option_name);
        $position = isset($options['remove_button_position']) ? $options['remove_button_position'] : 'center';
        ?>
        <select name="<?php echo $this->option_name; ?>[remove_button_position]">
            <option value="center" <?php selected($position, 'center'); ?>>Centro</option>
            <option value="top-right" <?php selected($position, 'top-right'); ?>>Superior derecha</option>
            <option value="top-left" <?php selected($position, 'top-left'); ?>>Superior izquierda</option>
        </select>
        <?php
    }
    
    public function render_remove_button_size_field() {
        $options = get_option($this->option_name);
        $size = isset($options['remove_button_size']) ? $options['remove_button_size'] : 30;
        ?>
        <input type="number" 
               name="<?php echo $this->option_name; ?>[remove_button_size]" 
               value="<?php echo esc_attr($size); ?>" 
               min="20" 
               max="50"> px
        <p class="description">Tamaño del botón para eliminar imágenes</p>
        <?php
    }

    public function render_overlay_settings_field() {
        $options = get_option($this->option_name);
        $opacity = isset($options['overlay_opacity']) ? $options['overlay_opacity'] : 0.5;
        $color = isset($options['overlay_color']) ? $options['overlay_color'] : '#000000';
        ?>
        <p>
            <label>Opacidad del overlay:</label><br>
            <input type="range" 
                   name="<?php echo $this->option_name; ?>[overlay_opacity]" 
                   value="<?php echo esc_attr($opacity); ?>"
                   min="0" 
                   max="1" 
                   step="0.1"
                   style="width: 200px; vertical-align: middle;">
            <span class="opacity-value" style="margin-left: 10px;"><?php echo $opacity; ?></span>
        </p>
        <p>
            <label>Color del overlay:
                <input type="color" 
                       name="<?php echo $this->option_name; ?>[overlay_color]" 
                       value="<?php echo esc_attr($color); ?>">
            </label>
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('input[type="range"]').on('input', function() {
                $(this).next('.opacity-value').text($(this).val());
            });
        });
        </script>
        <?php
    }

    public function render_select_button_order_field() {
        $options = get_option($this->option_name);
        $order = isset($options['select_button_order']) ? $options['select_button_order'] : 'before';
        ?>
        <select name="<?php echo $this->option_name; ?>[select_button_order]">
            <option value="before" <?php selected($order, 'before'); ?>>Antes de la imagen</option>
            <option value="after" <?php selected($order, 'after'); ?>>Después de la imagen</option>
        </select>
        <p class="description">Posición del botón para seleccionar imágenes</p>
        <?php
    }

    public function render_title_settings_field() {
        $options = get_option($this->option_name);
        $tag = isset($options['title_tag']) ? $options['title_tag'] : 'h4';
        $size = isset($options['title_size']) ? $options['title_size'] : 16;
        $classes = isset($options['title_classes']) ? $options['title_classes'] : '';
        ?>
        <p>
            <label>Etiqueta HTML:
                <select name="<?php echo $this->option_name; ?>[title_tag]">
                    <option value="h1" <?php selected($tag, 'h1'); ?>>H1</option>
                    <option value="h2" <?php selected($tag, 'h2'); ?>>H2</option>
                    <option value="h3" <?php selected($tag, 'h3'); ?>>H3</option>
                    <option value="h4" <?php selected($tag, 'h4'); ?>>H4</option>
                    <option value="h5" <?php selected($tag, 'h5'); ?>>H5</option>
                    <option value="h6" <?php selected($tag, 'h6'); ?>>H6</option>
                </select>
            </label>
        </p>
        <p>
            <label>Tamaño del título:
                <input type="number" 
                       name="<?php echo $this->option_name; ?>[title_size]" 
                       value="<?php echo esc_attr($size); ?>"
                       min="12" 
                       max="36"> px
            </label>
        </p>
        <p>
            <label>Clases CSS adicionales:
                <input type="text" 
                       name="<?php echo $this->option_name; ?>[title_classes]" 
                       value="<?php echo esc_attr($classes); ?>"
                       class="regular-text"
                       placeholder="ej: my-title custom-heading text-center">
            </label>
            <p class="description">Separa múltiples clases con espacios. Ejemplos: title-large, text-primary, custom-font</p>
        </p>
        <?php
    }
    
    public function render_colors_field() {
        $options = get_option($this->option_name);
        $remove_bg = isset($options['remove_button_bg']) ? $options['remove_button_bg'] : '#ff0000';
        $remove_color = isset($options['remove_button_color']) ? $options['remove_button_color'] : '#ffffff';
        ?>
        <p>
            <label>Color de fondo del botón eliminar:
                <input type="color" 
                       name="<?php echo $this->option_name; ?>[remove_button_bg]" 
                       value="<?php echo esc_attr($remove_bg); ?>">
            </label>
        </p>
        <p>
            <label>Color del texto del botón eliminar:
                <input type="color" 
                       name="<?php echo $this->option_name; ?>[remove_button_color]" 
                       value="<?php echo esc_attr($remove_color); ?>">
            </label>
        </p>
        <?php
    }
    
    public function render_image_fields() {
        $options = get_option($this->option_name);
        $fields = isset($options['image_fields']) ? $options['image_fields'] : [];
        
        // Asegurar que tenemos al menos un campo vacío
        if (empty($fields)) {
            $fields[] = [
                'name' => '',
                'label' => '',
                'type' => 'single',
                'meta_type' => 'native',
                'meta_key' => '',
                'post_type' => '',
                'required' => false
            ];
        }
        ?>
        <div class="shortcode-instructions" style="margin-bottom: 20px; padding: 15px; background: #f0f9ff; border-left: 4px solid #2271b1;">
            <h3 style="margin-top: 0;"><?php _e('Uso de los campos', 'jetform-media-gallery'); ?></h3>
            <ol>
                <li><?php _e('Configura los campos que necesitas para tu formulario.', 'jetform-media-gallery'); ?></li>
                <li><?php _e('Guarda la configuración.', 'jetform-media-gallery'); ?></li>
                <li><?php _e('Copia el shortcode generado para cada campo.', 'jetform-media-gallery'); ?></li>
                <li><?php _e('En tu formulario JetFormBuilder, añade un campo HTML personalizado.', 'jetform-media-gallery'); ?></li>
                <li><?php _e('Pega el shortcode en el campo HTML.', 'jetform-media-gallery'); ?></li>
            </ol>
            <p><strong><?php _e('Importante:', 'jetform-media-gallery'); ?></strong> <?php _e('No modifiques manualmente los shortcodes. Usa siempre los generados por esta página para asegurar la correcta configuración.', 'jetform-media-gallery'); ?></p>
        </div>
        
        <div id="image-fields-container">
            <?php foreach ($fields as $index => $field) : ?>
            <div class="image-field-row" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin-top: 0;">Campo #<?php echo ($index + 1); ?></h4>
                
                <p>
                    <label>
                        Nombre del campo:
                        <input type="text" 
                               name="<?php echo $this->option_name; ?>[image_fields][<?php echo $index; ?>][name]" 
                               value="<?php echo esc_attr($field['name']); ?>"
                               class="field-name regular-text"
                               placeholder="ej: featured_image">
                    </label>
                    <span class="description">Identificador único para el campo</span>
                </p>

                <p>
                    <label>
                        Etiqueta:
                        <input type="text" 
                               name="<?php echo $this->option_name; ?>[image_fields][<?php echo $index; ?>][label]" 
                               value="<?php echo esc_attr($field['label']); ?>"
                               class="regular-text"
                               placeholder="ej: Imagen Destacada">
                    </label>
                    <span class="description">Texto que verá el usuario</span>
                </p>

                <p>
                    <label>
                        Tipo de campo:
                        <select name="<?php echo $this->option_name; ?>[image_fields][<?php echo $index; ?>][type]">
                            <option value="single" <?php selected($field['type'], 'single'); ?>>Imagen única</option>
                            <option value="gallery" <?php selected($field['type'], 'gallery'); ?>>Galería de imágenes</option>
                        </select>
                    </label>
                </p>

                <p>
                    <label>
                        Tipo de meta:
                        <select name="<?php echo $this->option_name; ?>[image_fields][<?php echo $index; ?>][meta_type]" 
                                class="meta-type-select">
                            <option value="native" <?php selected($field['meta_type'], 'native'); ?>>WordPress nativo</option>
                            <option value="acf" <?php selected($field['meta_type'], 'acf'); ?>>Advanced Custom Fields</option>
                            <option value="jetengine" <?php selected($field['meta_type'], 'jetengine'); ?>>JetEngine</option>
                            <option value="metabox" <?php selected($field['meta_type'], 'metabox'); ?>>MetaBox</option>
                        </select>
                    </label>
                </p>

                <p>
                    <label>
                        Clave meta:
                        <input type="text" 
                               name="<?php echo $this->option_name; ?>[image_fields][<?php echo $index; ?>][meta_key]" 
                               value="<?php echo esc_attr($field['meta_key']); ?>"
                               class="regular-text"
                               placeholder="ej: _thumbnail_id o ad_gallery">
                    </label>
                    <span class="description">Clave meta donde se guardará el valor</span>
                </p>

                <p>
                    <label>
                        Tipo de post:
                        <select name="<?php echo $this->option_name; ?>[image_fields][<?php echo $index; ?>][post_type]">
                            <option value="">Seleccionar tipo de post</option>
                            <?php 
                            $post_types = get_post_types(['public' => true], 'objects');
                            foreach ($post_types as $post_type) :
                                $selected = selected($field['post_type'], $post_type->name, false);
                                echo "<option value='{$post_type->name}' {$selected}>{$post_type->label}</option>";
                            endforeach;
                            ?>
                        </select>
                    </label>
                </p>

                <p>
                    <label>
                        <input type="checkbox" 
                               name="<?php echo $this->option_name; ?>[image_fields][<?php echo $index; ?>][required]" 
                               value="1" 
                               class="field-required"
                               <?php checked(!empty($field['required'])); ?>>
                        Campo requerido
                    </label>
                </p>

                <?php if (!empty($field['name'])) : ?>
                <div class="shortcode-preview" style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin: 10px 0;">
                    <p><strong><?php _e('Shortcode:', 'jetform-media-gallery'); ?></strong></p>
                    <code class="field-shortcode">[media_gallery_field field="<?php echo esc_attr($field['name']); ?>"<?php echo !empty($field['required']) ? ' required="1"' : ''; ?>]</code>
                    <button type="button" class="button button-secondary copy-shortcode" style="margin-left: 10px;">
                        <span class="dashicons dashicons-clipboard" style="vertical-align: text-top;"></span>
                        <?php _e('Copiar', 'jetform-media-gallery'); ?>
                    </button>
                    <span class="copy-feedback" style="display: none; color: #008a20; margin-left: 10px;">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('¡Copiado!', 'jetform-media-gallery'); ?>
                    </span>
                </div>
                <?php endif; ?>

                <button type="button" class="button remove-field" <?php echo (count($fields) === 1) ? 'style="display:none;"' : ''; ?>>
                    Eliminar campo
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="button button-secondary" id="add-image-field">
            Agregar nuevo campo
        </button>

        <script>
        jQuery(document).ready(function($) {
            var container = $('#image-fields-container');
            var fieldCount = <?php echo count($fields); ?>;

            // Función para actualizar el shortcode de un campo específico
            function updateFieldShortcode(fieldRow) {
                var name = fieldRow.find('.field-name').val();
                var required = fieldRow.find('.field-required').is(':checked');
                var shortcodePreview = fieldRow.find('.shortcode-preview');
                
                if (name) {
                    var shortcode = '[media_gallery_field field="' + name + '"';
                    if (required) {
                        shortcode += ' required="1"';
                    }
                    shortcode += ']';
                    
                    if (shortcodePreview.length === 0) {
                        // Crear nuevo preview si no existe
                        shortcodePreview = $('<div class="shortcode-preview" style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin: 10px 0;">' +
                            '<p><strong><?php _e('Shortcode:', 'jetform-media-gallery'); ?></strong></p>' +
                            '<code class="field-shortcode"></code>' +
                            '<button type="button" class="button button-secondary copy-shortcode" style="margin-left: 10px;">' +
                            '<span class="dashicons dashicons-clipboard" style="vertical-align: text-top;"></span>' +
                            '<?php _e('Copiar', 'jetform-media-gallery'); ?>' +
                            '</button>' +
                            '<span class="copy-feedback" style="display: none; color: #008a20; margin-left: 10px;">' +
                            '<span class="dashicons dashicons-yes"></span>' +
                            '<?php _e('¡Copiado!', 'jetform-media-gallery'); ?>' +
                            '</span>' +
                            '</div>');
                        fieldRow.find('.remove-field').before(shortcodePreview);
                    }
                    fieldRow.find('.field-shortcode').text(shortcode);
                    shortcodePreview.show();
                } else {
                    shortcodePreview.hide();
                }
            }

            $('#add-image-field').on('click', function() {
                var template = container.children().first().clone();
                fieldCount++;

                // Actualizar título
                template.find('h4').text('Campo #' + fieldCount);

                // Limpiar valores
                template.find('input[type="text"]').val('');
                template.find('input[type="checkbox"]').prop('checked', false);
                template.find('select').prop('selected', false);

                // Actualizar nombres de campos
                template.find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + (fieldCount - 1) + ']'));
                    }
                });

                // Ocultar shortcode preview inicialmente
                template.find('.shortcode-preview').remove();

                // Mostrar botón eliminar
                template.find('.remove-field').show();
                container.children().first().find('.remove-field').show();

                container.append(template);
            });

            container.on('click', '.remove-field', function() {
                if (container.children().length > 1) {
                    $(this).closest('.image-field-row').remove();
                    
                    // Actualizar números de campos
                    container.children().each(function(index) {
                        $(this).find('h4').text('Campo #' + (index + 1));
                        $(this).find('input, select').each(function() {
                            var name = $(this).attr('name');
                            if (name) {
                                $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                            }
                        });
                    });
                }
            });

            // Actualizar shortcode cuando se modifica el campo
            container.on('input change', '.field-name, .field-required', function() {
                updateFieldShortcode($(this).closest('.image-field-row'));
            });

            // Copiar shortcode
            container.on('click', '.copy-shortcode', function() {
                var shortcode = $(this).siblings('.field-shortcode').text();
                navigator.clipboard.writeText(shortcode).then(function() {
                    var feedback = $(this).siblings('.copy-feedback');
                    feedback.fadeIn().delay(2000).fadeOut();
                }.bind(this));
            });
        });
        </script>
        <?php
    }
    
    public function render_debug_section() {
        echo '<p>' . __('Configura las opciones de depuración y logging del plugin.', 'jetform-media-gallery') . '</p>';
        
        // Mostrar información sobre los logs actuales
        $log_file = WP_CONTENT_DIR . '/debug-media-gallery.log';
        if (file_exists($log_file)) {
            $size = size_format(filesize($log_file), 2);
            echo '<p>' . sprintf(__('Archivo de log actual: %s', 'jetform-media-gallery'), $size) . '</p>';
        }
    }

    public function render_debug_mode_field() {
        $options = get_option('jetform_media_gallery_settings', []);
        // Asegurar que debug_mode es un valor booleano explícito
        $debug_mode = isset($options['debug_mode']) && $options['debug_mode'] === true;
        ?>
        <label>
            <input type="checkbox" 
                   name="jetform_media_gallery_settings[debug_mode]" 
                   value="1" 
                   <?php checked($debug_mode, true); ?>>
            <?php _e('Activar modo de depuración', 'jetform-media-gallery'); ?>
        </label>
        <p class="description">
            <?php _e('Guarda información detallada sobre el procesamiento de imágenes en el archivo de log.', 'jetform-media-gallery'); ?>
        </p>
        <?php
    }

    public function render_max_log_size_field() {
        $options = get_option('jetform_media_gallery_settings');
        $max_size = isset($options['max_log_size']) ? absint($options['max_log_size']) : 5;
        ?>
        <input type="number" 
               name="jetform_media_gallery_settings[max_log_size]" 
               value="<?php echo esc_attr($max_size); ?>"
               min="1"
               max="100"
               step="1">
        <p class="description">
            <?php _e('Tamaño máximo en megabytes (MB) antes de rotar el archivo de log.', 'jetform-media-gallery'); ?>
        </p>
        <?php
    }

    public function render_max_log_files_field() {
        $options = get_option('jetform_media_gallery_settings');
        $max_files = isset($options['max_log_files']) ? absint($options['max_log_files']) : 5;
        ?>
        <input type="number" 
               name="jetform_media_gallery_settings[max_log_files]" 
               value="<?php echo esc_attr($max_files); ?>"
               min="1"
               max="20"
               step="1">
        <p class="description">
            <?php _e('Número máximo de archivos de log a mantener durante la rotación.', 'jetform-media-gallery'); ?>
        </p>
        <?php
    }
    
    public function sanitize_settings($input) {
        $sanitized = [];
        
        // Mantener los valores existentes
        $existing = get_option($this->option_name, []);
        
        // Fusionar con los nuevos valores
        $sanitized = array_merge($existing, $input);
        
        // Sanitizar campos generales
        if (isset($input['image_width'])) {
            $sanitized['image_width'] = absint($input['image_width']);
            $sanitized['image_width'] = min(max($sanitized['image_width'], 50), 500);
        }
        
        if (isset($input['image_height'])) {
            $sanitized['image_height'] = absint($input['image_height']);
            $sanitized['image_height'] = min(max($sanitized['image_height'], 50), 500);
        }
        
        if (isset($input['use_theme_buttons'])) {
            $sanitized['use_theme_buttons'] = (bool)$input['use_theme_buttons'];
        } else {
            $sanitized['use_theme_buttons'] = false;
        }
        
        if (isset($input['remove_button_position'])) {
            $valid_positions = ['center', 'top-right', 'top-left'];
            $sanitized['remove_button_position'] = in_array($input['remove_button_position'], $valid_positions) 
                ? $input['remove_button_position'] 
                : 'center';
        }

        if (isset($input['remove_button_size'])) {
            $sanitized['remove_button_size'] = absint($input['remove_button_size']);
            $sanitized['remove_button_size'] = min(max($sanitized['remove_button_size'], 20), 50);
        }

        if (isset($input['overlay_opacity'])) {
            $sanitized['overlay_opacity'] = floatval($input['overlay_opacity']);
            $sanitized['overlay_opacity'] = min(max($sanitized['overlay_opacity'], 0), 1);
        }

        if (isset($input['overlay_color'])) {
            $sanitized['overlay_color'] = sanitize_hex_color($input['overlay_color']);
        }

        if (isset($input['select_button_order'])) {
            $sanitized['select_button_order'] = in_array($input['select_button_order'], ['before', 'after']) 
                ? $input['select_button_order'] 
                : 'before';
        }

        if (isset($input['title_tag'])) {
            $valid_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            $sanitized['title_tag'] = in_array($input['title_tag'], $valid_tags) 
                ? $input['title_tag'] 
                : 'h4';
        }

        if (isset($input['title_size'])) {
            $sanitized['title_size'] = absint($input['title_size']);
            $sanitized['title_size'] = min(max($sanitized['title_size'], 12), 36);
        }

        if (isset($input['title_classes'])) {
            $sanitized['title_classes'] = sanitize_text_field($input['title_classes']);
        }
        
        if (isset($input['remove_button_bg'])) {
            $sanitized['remove_button_bg'] = sanitize_hex_color($input['remove_button_bg']);
        }
        
        if (isset($input['remove_button_color'])) {
            $sanitized['remove_button_color'] = sanitize_hex_color($input['remove_button_color']);
        }
        
        // Sanitizar campos de imágenes
        if (isset($input['image_fields']) && is_array($input['image_fields'])) {
            foreach ($input['image_fields'] as $index => $field) {
                $sanitized['image_fields'][$index] = [
                    'name' => sanitize_key($field['name']),
                    'label' => sanitize_text_field($field['label']),
                    'type' => in_array($field['type'], ['single', 'gallery']) ? $field['type'] : 'single',
                    'meta_type' => in_array($field['meta_type'], ['native', 'acf', 'jetengine', 'metabox']) 
                        ? $field['meta_type'] 
                        : 'native',
                    'meta_key' => sanitize_key($field['meta_key']),
                    'post_type' => sanitize_key($field['post_type']),
                    'required' => !empty($field['required'])
                ];
            }
        }
        
        // Sanitizar opciones de depuración
        if (isset($input['debug_mode'])) {
            $sanitized['debug_mode'] = (bool)$input['debug_mode'];
        } else {
            // Si el checkbox no está marcado, el valor no se envía
            // Por lo tanto, debemos establecerlo explícitamente a false
            $sanitized['debug_mode'] = false;
        }
        
        $sanitized['max_log_size'] = isset($input['max_log_size']) ? absint($input['max_log_size']) : 5;
        if ($sanitized['max_log_size'] < 1) $sanitized['max_log_size'] = 1;
        if ($sanitized['max_log_size'] > 100) $sanitized['max_log_size'] = 100;

        $sanitized['max_log_files'] = isset($input['max_log_files']) ? absint($input['max_log_files']) : 5;
        if ($sanitized['max_log_files'] < 1) $sanitized['max_log_files'] = 1;
        if ($sanitized['max_log_files'] > 20) $sanitized['max_log_files'] = 20;

        // Sanitizar los estilos personalizados de botones
        if (isset($input['button_bg'])) {
            $sanitized['button_bg'] = sanitize_hex_color($input['button_bg']);
        }
        
        if (isset($input['button_text'])) {
            $sanitized['button_text'] = sanitize_hex_color($input['button_text']);
        }
        
        if (isset($input['button_hover_bg'])) {
            $sanitized['button_hover_bg'] = sanitize_hex_color($input['button_hover_bg']);
        }
        
        if (isset($input['button_border_radius'])) {
            $sanitized['button_border_radius'] = absint($input['button_border_radius']);
            $sanitized['button_border_radius'] = min(max($sanitized['button_border_radius'], 0), 50);
        }
        
        if (isset($input['button_padding'])) {
            $sanitized['button_padding'] = sanitize_text_field($input['button_padding']);
        }

        return $sanitized;
    }

    /**
     * Manejar acciones de logs
     */
    public function handle_log_actions() {
        if (!current_user_can('manage_options') || !isset($_GET['page']) || $_GET['page'] !== 'jetform-media-gallery') {
            return;
        }

        if (isset($_POST['action'])) {
            check_admin_referer('jetform_media_gallery_logs');

            switch ($_POST['action']) {
                case 'clear_logs':
                    $this->clear_logs();
                    break;
                case 'toggle_debug':
                    $this->toggle_debug_mode();
                    break;
            }

            wp_redirect(add_query_arg(['page' => 'jetform-media-gallery', 'tab' => 'logs'], admin_url('options-general.php')));
            exit;
        }
    }

    /**
     * Limpiar logs
     */
    private function clear_logs() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
        
        // También eliminar archivos rotados
        for ($i = 1; $i <= 20; $i++) {
            $rotated_file = $this->log_file . '.' . $i;
            if (file_exists($rotated_file)) {
                unlink($rotated_file);
            }
        }
        
        add_settings_error(
            'jetform_media_gallery_logs',
            'logs_cleared',
            __('Los logs han sido eliminados correctamente.', 'jetform-media-gallery'),
            'success'
        );
    }

    /**
     * Alternar modo debug
     */
    private function toggle_debug_mode() {
        $options = get_option($this->option_name, []);
        
        // Asegurar que el valor booleano es correcto
        if (isset($options['debug_mode'])) {
            $options['debug_mode'] = !$options['debug_mode'];
        } else {
            $options['debug_mode'] = true;
        }
        
        update_option($this->option_name, $options);
        
        $status = $options['debug_mode'] ? 'activado' : 'desactivado';
        add_settings_error(
            'jetform_media_gallery_logs',
            'debug_toggled',
            sprintf(__('El modo debug ha sido %s.', 'jetform-media-gallery'), $status),
            'success'
        );
    }

    /**
     * Renderizar página de logs
     */
    private function render_logs_page() {
        // Obtener configuraciones frescas
        $options = get_option($this->option_name, []);
        // Asegurar que debug_mode es un booleano explícito
        $debug_enabled = isset($options['debug_mode']) && $options['debug_mode'] === true;
        ?>
        <div class="logs-container">
            <div class="log-controls" style="margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <form method="post" style="display: flex; gap: 10px; align-items: center;">
                    <?php wp_nonce_field('jetform_media_gallery_logs'); ?>
                    
                    <button type="submit" name="action" value="toggle_debug" class="button <?php echo $debug_enabled ? 'button-primary' : 'button-secondary'; ?>">
                        <?php echo $debug_enabled ? __('Desactivar Debug', 'jetform-media-gallery') : __('Activar Debug', 'jetform-media-gallery'); ?>
                    </button>
                    
                    <button type="submit" name="action" value="clear_logs" class="button button-secondary" 
                            onclick="return confirm('<?php _e('¿Estás seguro de que deseas eliminar todos los logs?', 'jetform-media-gallery'); ?>');">
                        <?php _e('Limpiar Logs', 'jetform-media-gallery'); ?>
                    </button>
                    
                    <?php if (file_exists($this->log_file)): ?>
                        <span class="log-info">
                            <?php 
                            $size = size_format(filesize($this->log_file), 2);
                            printf(__('Tamaño actual del log: %s', 'jetform-media-gallery'), $size); 
                            ?>
                        </span>
                    <?php endif; ?>
                </form>
            </div>

            <div class="log-viewer" style="background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div class="log-content" style="padding: 15px; max-height: 500px; overflow-y: auto; font-family: monospace;">
                    <?php
                    if (file_exists($this->log_file)) {
                        $logs = file_get_contents($this->log_file);
                        if (!empty($logs)) {
                            // Dividir en líneas y mostrar las últimas 1000 líneas
                            $lines = array_filter(explode("\n", $logs));
                            $lines = array_slice($lines, -1000);
                            
                            foreach ($lines as $line) {
                                // Detectar y colorear diferentes tipos de mensajes
                                $class = '';
                                if (stripos($line, 'error') !== false) {
                                    $class = 'color: #dc3232;'; // Rojo para errores
                                } elseif (stripos($line, 'success') !== false || stripos($line, 'éxito') !== false) {
                                    $class = 'color: #46b450;'; // Verde para éxitos
                                } elseif (stripos($line, 'warning') !== false || stripos($line, 'advertencia') !== false) {
                                    $class = 'color: #ffb900;'; // Amarillo para advertencias
                                }
                                
                                printf('<div style="%s">%s</div>', $class, esc_html($line));
                            }
                        } else {
                            echo '<p>' . __('El archivo de log está vacío.', 'jetform-media-gallery') . '</p>';
                        }
                    } else {
                        echo '<p>' . __('No hay archivos de log disponibles.', 'jetform-media-gallery') . '</p>';
                    }
                    ?>
                </div>
            </div>

            <?php if (!$debug_enabled): ?>
            <div class="debug-notice" style="margin-top: 15px; padding: 10px; background: #fff8e5; border-left: 4px solid #ffb900;">
                <p><?php _e('El modo debug está desactivado. Actívalo para registrar información detallada sobre el procesamiento de imágenes.', 'jetform-media-gallery'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <style>
            .log-content div {
                padding: 2px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            .log-info {
                color: #666;
                font-style: italic;
            }
        </style>
        <?php
    }
} 