<?php
class JetForm_Media_Gallery_Admin {
    private $option_name = 'jetform_media_gallery_settings';
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    public function add_settings_page() {
        add_submenu_page(
            'options-general.php',
            'JetForm Media Gallery',
            'Media Gallery',
            'manage_options',
            'jetform-media-gallery',
            [$this, 'render_settings_page']
        );
    }
    
    public function register_settings() {
        register_setting($this->option_name, $this->option_name, [$this, 'sanitize_settings']);
        
        add_settings_section(
            'general_settings',
            'Configuración General',
            null,
            'jetform-media-gallery'
        );
        
        // Tamaño de imágenes
        add_settings_field(
            'image_size',
            'Tamaño de imágenes',
            [$this, 'render_image_size_field'],
            'jetform-media-gallery',
            'general_settings'
        );
        
        // Estilo de botones
        add_settings_field(
            'button_style',
            'Estilo de botones',
            [$this, 'render_button_style_field'],
            'jetform-media-gallery',
            'general_settings'
        );
        
        // Posición del botón eliminar
        add_settings_field(
            'remove_button_position',
            'Posición del botón eliminar',
            [$this, 'render_remove_button_position_field'],
            'jetform-media-gallery',
            'general_settings'
        );
        
        // Tamaño del botón eliminar
        add_settings_field(
            'remove_button_size',
            'Tamaño del botón eliminar',
            [$this, 'render_remove_button_size_field'],
            'jetform-media-gallery',
            'general_settings'
        );
        
        // Overlay de la imagen
        add_settings_field(
            'image_overlay',
            'Overlay al mostrar botón eliminar',
            [$this, 'render_image_overlay_field'],
            'jetform-media-gallery',
            'general_settings'
        );
        
        // Orden del botón de selección
        add_settings_field(
            'select_button_order',
            'Orden del botón de selección',
            [$this, 'render_select_button_order_field'],
            'jetform-media-gallery',
            'general_settings'
        );
        
        // Colores
        add_settings_field(
            'colors',
            'Colores',
            [$this, 'render_colors_field'],
            'jetform-media-gallery',
            'general_settings'
        );
        
        // Estilos del título
        add_settings_field(
            'title_styles',
            'Estilos del título',
            [$this, 'render_title_styles_field'],
            'jetform-media-gallery',
            'general_settings'
        );
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_name);
                do_settings_sections('jetform-media-gallery');
                submit_button('Guardar Cambios');
                ?>
            </form>
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
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo $this->option_name; ?>[use_theme_buttons]" 
                   value="1" 
                   <?php checked($use_theme, true); ?>>
            Usar estilos de botones del tema
        </label>
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
               max="60"> px
        <p class="description">Tamaño del botón eliminar (entre 20px y 60px)</p>
        <?php
    }
    
    public function render_image_overlay_field() {
        $options = get_option($this->option_name);
        $overlay_opacity = isset($options['overlay_opacity']) ? $options['overlay_opacity'] : 0.5;
        $overlay_color = isset($options['overlay_color']) ? $options['overlay_color'] : '#000000';
        ?>
        <p>
            <label>Color del overlay:
                <input type="color" 
                       name="<?php echo $this->option_name; ?>[overlay_color]" 
                       value="<?php echo esc_attr($overlay_color); ?>">
            </label>
        </p>
        <p>
            <label>Intensidad del overlay:
                <input type="range" 
                       name="<?php echo $this->option_name; ?>[overlay_opacity]" 
                       value="<?php echo esc_attr($overlay_opacity); ?>" 
                       min="0" 
                       max="1" 
                       step="0.1">
                <span class="opacity-value"><?php echo $overlay_opacity; ?></span>
            </label>
        </p>
        <p class="description">Color y opacidad del overlay que aparece al mostrar el botón eliminar</p>
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
            <option value="before" <?php selected($order, 'before'); ?>>Antes de las imágenes</option>
            <option value="after" <?php selected($order, 'after'); ?>>Después de las imágenes</option>
        </select>
        <p class="description">Posición del botón de selección de imágenes en relación a las imágenes mostradas</p>
        <?php
    }
    
    public function render_title_styles_field() {
        $options = get_option($this->option_name);
        $tag = isset($options['title_tag']) ? $options['title_tag'] : 'h4';
        $size = isset($options['title_size']) ? $options['title_size'] : '16';
        $classes = isset($options['title_classes']) ? $options['title_classes'] : '';
        ?>
        <p>
            <label>Etiqueta HTML:
                <select name="<?php echo $this->option_name; ?>[title_tag]">
                    <?php
                    $tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span'];
                    foreach ($tags as $t) {
                        echo '<option value="' . esc_attr($t) . '" ' . selected($tag, $t, false) . '>' . esc_html($t) . '</option>';
                    }
                    ?>
                </select>
            </label>
        </p>
        <p>
            <label>Tamaño de fuente:
                <input type="number" 
                       name="<?php echo $this->option_name; ?>[title_size]" 
                       value="<?php echo esc_attr($size); ?>" 
                       min="10" 
                       max="48"> px
            </label>
        </p>
        <p>
            <label>Clases CSS adicionales:
                <input type="text" 
                       name="<?php echo $this->option_name; ?>[title_classes]" 
                       value="<?php echo esc_attr($classes); ?>" 
                       class="regular-text"
                       placeholder="clase1 clase2 clase3">
            </label>
        </p>
        <p class="description">Personaliza el estilo del título del campo</p>
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
    
    public function sanitize_settings($input) {
        $sanitized = [];
        
        // Tamaño de imágenes
        $sanitized['image_width'] = absint($input['image_width']);
        $sanitized['image_height'] = absint($input['image_height']);
        
        // Limitar tamaños
        $sanitized['image_width'] = min(max($sanitized['image_width'], 50), 500);
        $sanitized['image_height'] = min(max($sanitized['image_height'], 50), 500);
        
        // Estilo de botones
        $sanitized['use_theme_buttons'] = isset($input['use_theme_buttons']);
        
        // Posición del botón eliminar
        $valid_positions = ['center', 'top-right', 'top-left'];
        $sanitized['remove_button_position'] = in_array($input['remove_button_position'], $valid_positions) 
            ? $input['remove_button_position'] 
            : 'center';
        
        // Tamaño del botón
        $sanitized['remove_button_size'] = min(max(absint($input['remove_button_size']), 20), 60);

        // Overlay
        $sanitized['overlay_opacity'] = min(max(floatval($input['overlay_opacity']), 0), 1);
        $sanitized['overlay_color'] = sanitize_hex_color($input['overlay_color']);

        // Orden del botón de selección
        $sanitized['select_button_order'] = in_array($input['select_button_order'], ['before', 'after']) 
            ? $input['select_button_order'] 
            : 'before';

        // Estilos del título
        $valid_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span'];
        $sanitized['title_tag'] = in_array($input['title_tag'], $valid_tags) ? $input['title_tag'] : 'h4';
        $sanitized['title_size'] = min(max(absint($input['title_size']), 10), 48);
        $sanitized['title_classes'] = sanitize_text_field($input['title_classes']);
        
        // Colores
        $sanitized['remove_button_bg'] = sanitize_hex_color($input['remove_button_bg']);
        $sanitized['remove_button_color'] = sanitize_hex_color($input['remove_button_color']);
        
        return $sanitized;
    }
} 