<?php
/**
 * Clase para manejar los estilos dinámicos
 *
 * @package JetForm_Media_Gallery
 * @since 1.0.0
 */

// No ejecutar directamente
if (!defined('ABSPATH')) {
    die('No direct script access allowed');
}

class JetForm_Media_Gallery_Styles {
    
    /**
     * Instancia de la clase principal
     */
    private $main;
    
    /**
     * Constructor
     */
    public function __construct($main) {
        $this->main = $main;
    }
    
    /**
     * Generar estilos dinámicos para el campo
     */
    public function get_dynamic_styles() {
        $settings = $this->main->get_settings();
        
        $width = absint($settings['image_width']);
        $height = absint($settings['image_height']);
        $use_theme = !empty($settings['use_theme_buttons']);
        $position = isset($settings['remove_button_position']) ? $settings['remove_button_position'] : 'center';
        $remove_bg = sanitize_hex_color($settings['remove_button_bg']);
        $remove_color = sanitize_hex_color($settings['remove_button_color']);
        $button_size = absint($settings['remove_button_size']);
        $overlay_opacity = floatval($settings['overlay_opacity']);
        $overlay_color = sanitize_hex_color($settings['overlay_color']);
        $title_size = absint($settings['title_size']);
        
        // Valores personalizados para los botones
        $button_bg = isset($settings['button_bg']) ? sanitize_hex_color($settings['button_bg']) : '#0073aa';
        $button_text = isset($settings['button_text']) ? sanitize_hex_color($settings['button_text']) : '#ffffff';
        $button_hover_bg = isset($settings['button_hover_bg']) ? sanitize_hex_color($settings['button_hover_bg']) : '#005c8a';
        $button_border_radius = isset($settings['button_border_radius']) ? absint($settings['button_border_radius']) : 4;
        $button_padding = isset($settings['button_padding']) ? sanitize_text_field($settings['button_padding']) : '10px 16px';
        
        // Posicionamiento del botón eliminar
        $position_styles = 'top: 50%; left: 50%; transform: translate(-50%, -50%);';
        if ($position === 'top-right') {
            $position_styles = 'top: 10px; right: 10px; transform: none;';
        } elseif ($position === 'top-left') {
            $position_styles = 'top: 10px; left: 10px; transform: none;';
        }

        // Orden del botón (z-index)
        $button_z_index = isset($settings['select_button_order']) && $settings['select_button_order'] === 'before' ? '2' : '1';
        
        // Construir los estilos CSS
        $css = '.media-gallery-container {
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
            display: flex;
            flex-direction: column;
            height: auto;
        }

        .section-title {
            font-size: ' . $title_size . 'px;
            margin-bottom: 15px;
        }
        
        .image-controls, .gallery-controls {
            display: flex;
            flex-direction: column;
            gap: 15px;
            flex: 1;
            justify-content: flex-start;
        }
        
        .image-preview {
            width: ' . $width . 'px;
            height: ' . $height . 'px;
            position: relative;
            margin-top: 10px;
            background-size: cover;
            background-position: center;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .image-preview.has-image {
            background-size: cover;
            background-position: center;
            border: none;
            background-color: transparent;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .image-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: ' . $overlay_color . ';
            opacity: 0;
            transition: opacity 0.2s ease;
            border-radius: 4px;
        }
        
        .gallery-image:hover .image-overlay,
        .image-preview:hover .image-overlay {
            display: block;
            opacity: ' . $overlay_opacity . ';
        }
        
        /* Estilos para el ordenamiento de imágenes */
        .gallery-image-placeholder {
            border: 2px dashed #ccc;
            background-color: #f9f9f9;
            height: ' . $height . 'px;
            width: ' . $width . 'px;
            margin: 5px;
            border-radius: 4px;
        }
        
        .gallery-image {
            cursor: move;
            position: relative;
            width: ' . $width . 'px;
            height: ' . $height . 'px;
            background-size: cover;
            background-position: center;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .drag-handle {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 20px;
            height: 20px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 3px;
            z-index: 3;
            cursor: move;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .images-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .images-preview:not(:empty) {
            border: none;
            background-color: transparent;
        }
        
        .remove-featured-image,
        .remove-image {
            position: absolute;
            ' . $position_styles . '
            background-color: ' . $remove_bg . ';
            color: ' . $remove_color . ';
            width: ' . $button_size . 'px;
            height: ' . $button_size . 'px;
            border-radius: 50%;
            border: none;
            font-size: ' . ($button_size * 0.7) . 'px;
            line-height: 0;
            padding: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .gallery-image:hover .remove-image,
        .image-preview:hover .remove-featured-image {
            opacity: 1;
        }';
        
        // Estilos para los botones si no se usa el tema
        if (!$use_theme) {
            $css .= '
            .button.upload-featured-image,
            .button.upload-gallery-images {
                background-color: ' . $button_bg . ';
                color: ' . $button_text . ';
                border: none;
                padding: ' . $button_padding . ';
                border-radius: ' . $button_border_radius . 'px;
                cursor: pointer;
                transition: background-color 0.2s ease;
                text-align: center;
                display: inline-block;
                font-size: 14px;
                line-height: 1.5;
                font-weight: 500;
                text-decoration: none;
                z-index: ' . $button_z_index . ';
                position: relative;
            }
            
            .button.upload-featured-image:hover,
            .button.upload-gallery-images:hover {
                background-color: ' . $button_hover_bg . ';
            }';
        }
        
        // Estilos para móviles
        $css .= '
        @media (max-width: 768px) {
            .media-gallery-container {
                flex-direction: column;
            }
            
            .featured-image-container, .gallery-container {
                width: 100%;
                min-width: auto;
            }
            
            .image-preview, .gallery-image {
                width: ' . $width . 'px;
                height: ' . $height . 'px;
                margin: 0 auto;
            }
            
            .images-preview {
                justify-content: center;
            }
            
            .gallery-image {
                margin: 5px;
                flex: 0 0 auto;
            }
            
            .mobile-upload-help {
                font-size: 14px;
                line-height: 1.4;
                color: #333;
            }
        }';
        
        return $css;
    }
    
    /**
     * Ajusta el brillo de un color hexadecimal
     * @param string $hex Color hexadecimal
     * @param int $steps Pasos para ajustar (-255 a 255)
     * @return string Color hexadecimal ajustado
     */
    public function adjust_brightness($hex, $steps) {
        // Eliminar el # si existe
        $hex = ltrim($hex, '#');
        
        // Convertir a RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Ajustar cada componente
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        // Convertir de nuevo a hex
        return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
    }
}