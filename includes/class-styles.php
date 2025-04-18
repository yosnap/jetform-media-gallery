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
        $position = $settings['remove_button_position'];
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
        $button_z_index = $settings['select_button_order'] === 'before' ? '2' : '1';
        $image_z_index = $settings['select_button_order'] === 'before' ? '1' : '2';
        
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
            /* Estilos personalizados para botones cuando no se usa el tema */
            .media-gallery-field .button {
                background: {$button_bg};
                color: {$button_text};
                border: none;
                padding: {$button_padding};
                border-radius: {$button_border_radius}px;
                cursor: pointer;
                transition: all 0.2s ease;
                font-weight: 500;
                font-size: 14px;
                text-align: center;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                text-decoration: none;
                margin: 5px 0;
                min-width: 150px;
            }
            
            .media-gallery-field .button:hover {
                background: {$button_hover_bg};
                box-shadow: 0 3px 6px rgba(0,0,0,0.15);
                transform: translateY(-1px);
            }
            
            .media-gallery-field .button:active {
                background: " . $this->adjust_brightness($button_hover_bg, -15) . ";
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
                transform: translateY(1px);
            }
            
            .media-gallery-field .upload-featured-image,
            .media-gallery-field .upload-gallery-images {
                position: relative;
                padding-left: 32px;
            }
            
            .media-gallery-field .upload-featured-image:before,
            .media-gallery-field .upload-gallery-images:before {
                content: '';
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                width: 16px;
                height: 16px;
                background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><rect x=\"3\" y=\"3\" width=\"18\" height=\"18\" rx=\"2\" ry=\"2\"/><circle cx=\"8.5\" cy=\"8.5\" r=\"1.5\"/><polyline points=\"21 15 16 10 5 21\"/></svg>');
                background-size: contain;
                background-repeat: no-repeat;
            }
            
            .media-gallery-field .upload-gallery-images:before {
                background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><rect x=\"3\" y=\"3\" width=\"18\" height=\"18\" rx=\"2\" ry=\"2\"/><circle cx=\"8.5\" cy=\"8.5\" r=\"1.5\"/><polyline points=\"21 15 16 10 5 21\"/><polyline points=\"21 11 14 4 7 11\"/></svg>');
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