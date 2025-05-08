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
        
        // Valores personalizados para el icono de ordenamiento
        $drag_handle_color = isset($settings['drag_handle_color']) ? sanitize_hex_color($settings['drag_handle_color']) : '#323232';
        $drag_handle_size = isset($settings['drag_handle_size']) ? absint($settings['drag_handle_size']) : 24;
        $drag_handle_opacity = isset($settings['drag_handle_opacity']) ? floatval($settings['drag_handle_opacity']) : 0.85;
        $drag_handle_position = isset($settings['drag_handle_position']) ? sanitize_text_field($settings['drag_handle_position']) : 'top-left';
        $drag_handle_lines_color = isset($settings['drag_handle_lines_color']) ? sanitize_hex_color($settings['drag_handle_lines_color']) : '#ffffff';
        
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
            border: 2px dashed #0073aa;
            background-color: rgba(0, 115, 170, 0.1);
            height: ' . $height . 'px;
            width: ' . $width . 'px;
            margin: 5px;
            border-radius: 4px;
            visibility: visible !important;
        }
        
        .being-dragged {
            z-index: 9999;
            opacity: 0.9;
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .gallery-image {
            cursor: default;
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
            width: ' . $drag_handle_size . 'px;
            height: ' . $drag_handle_size . 'px;
            background-color: ' . $drag_handle_color . ';
            border-radius: 4px;
            z-index: 3;
            cursor: move;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            opacity: ' . $drag_handle_opacity . ';
            ' . $this->get_drag_handle_position_styles($drag_handle_position) . '
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .drag-handle:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        
        /* Líneas para indicar que es un elemento arrastrable */
        .drag-handle:before {
            content: "";
            display: block;
            width: 60%;
            height: 2px;
            background-color: ' . $drag_handle_lines_color . ';
            border-radius: 1px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 -4px 0 ' . $drag_handle_lines_color . ', 0 4px 0 ' . $drag_handle_lines_color . ';
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
                padding: 12px !important;
                margin: 15px !important;
                border-radius: 8px !important;
                background-color: #f0f7ff !important;
                border: 1px solid #d0e3ff !important;
            }
            
            /* Estilos específicos para iOS */
            .ios-select-button {
                display: block;
                width: calc(100% - 30px) !important;
                margin: 15px !important;
                padding: 12px !important;
                font-size: 16px !important;
                text-align: center;
                border-radius: 8px !important;
                background-color: #0073aa !important;
                color: white !important;
                border: none !important;
                font-weight: bold !important;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
            }
            
            .ios-select-button.selecting {
                background-color: #e63946 !important;
            }
            
            .ios-confirm-button {
                display: block;
                width: calc(100% - 30px) !important;
                margin: 15px !important;
                padding: 12px !important;
                font-size: 16px !important;
                text-align: center;
                border-radius: 8px !important;
                background-color: #4CAF50 !important;
                color: white !important;
                border: none !important;
                font-weight: bold !important;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
            }
            
            .ios-cancel-button {
                display: block;
                width: calc(100% - 30px) !important;
                margin: 15px !important;
                padding: 12px !important;
                font-size: 16px !important;
                text-align: center;
                border-radius: 8px !important;
                background-color: #f44336 !important;
                color: white !important;
                border: none !important;
                font-weight: bold !important;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
            }
            
            .ios-enhanced .attachment {
                width: 33.33% !important;
                padding: 10px !important;
                box-sizing: border-box !important;
            }
            
            .ios-multiple-select-mode .attachment {
                position: relative;
                border: 2px solid transparent !important;
                transition: all 0.2s ease !important;
                margin: 5px !important;
                cursor: pointer !important;
            }
            
            .ios-multiple-select-mode .attachment.selected {
                border: 3px solid #4CAF50 !important;
                box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.3) !important;
                transform: scale(0.95) !important;
            }
            
            /* Indicador de selección para iOS */
            .ios-select-indicator {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background-color: rgba(0, 0, 0, 0.7);
                color: white;
                text-align: center;
                padding: 8px 5px;
                font-size: 12px;
                z-index: 100;
                transition: all 0.3s ease;
                font-weight: bold;
            }
            
            .ios-select-indicator.is-selected {
                background-color: #4CAF50;
                color: white;
            }
            
            /* Hacer que las imágenes sean más grandes y fáciles de tocar */
            .ios-multiple-select-mode .attachment {
                min-width: 120px !important;
                min-height: 120px !important;
                margin: 10px !important;
            }
            
            /* Mejorar la visualización de las imágenes seleccionadas */
            .ios-multiple-select-mode .attachment.selected {
                border: 3px solid #4CAF50 !important;
                box-shadow: 0 0 10px rgba(76, 175, 80, 0.8) !important;
            }
            
            .ios-multiple-select-mode .attachment {
                cursor: pointer !important;
            }
            
            /* Mejorar el manejo táctil para arrastrar y soltar */
            .drag-handle {
                width: ' . ($drag_handle_size * 1.5) . 'px !important;
                height: ' . ($drag_handle_size * 1.5) . 'px !important;
                background-color: ' . $drag_handle_color . ' !important;
                border: none !important;
                box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3) !important;
                border-radius: 6px !important;
                padding: 6px !important;
                opacity: ' . min(1, $drag_handle_opacity + 0.1) . ' !important;
                ' . $this->get_drag_handle_position_styles($drag_handle_position) . '
                transform: scale(1) !important;
                transition: transform 0.2s ease, box-shadow 0.2s ease !important;
            }
            
            /* Efecto de pulsación en móviles */
            .drag-handle:active {
                transform: scale(0.95) !important;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2) !important;
            }
            
            /* Añadir un indicador visual para móviles */
            .gallery-image .drag-handle::after {
                content: "Tocar y arrastrar" !important;
                position: absolute !important;
                bottom: -25px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                background-color: rgba(0, 0, 0, 0.7) !important;
                color: white !important;
                padding: 3px 8px !important;
                border-radius: 3px !important;
                font-size: 10px !important;
                white-space: nowrap !important;
                opacity: 0 !important;
                transition: opacity 0.3s ease !important;
                pointer-events: none !important;
            }
            
            .gallery-image .drag-handle:active::after {
                opacity: 1 !important;
            }
            
            /* Líneas para el drag handle en móviles */
            .drag-handle:before {
                content: "" !important;
                display: block !important;
                width: 60% !important;
                height: 3px !important;
                background-color: ' . $drag_handle_lines_color . ' !important;
                border-radius: 1.5px !important;
                position: absolute !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                box-shadow: 0 -6px 0 ' . $drag_handle_lines_color . ', 0 6px 0 ' . $drag_handle_lines_color . ' !important;
            }
            
            .ui-sortable-helper {
                transform: scale(1.05) !important;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2) !important;
                z-index: 9999 !important;
            }
            
            /* Mejoras específicas para arrastrar en iOS */
            .gallery-image {
                -webkit-touch-callout: none !important;
                -webkit-user-select: none !important;
                -khtml-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
                touch-action: none !important;
            }
            
            .gallery-image-placeholder {
                border: 2px dashed #0073aa !important;
                background-color: rgba(0, 115, 170, 0.1) !important;
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
    
    /**
     * Obtiene los estilos CSS para la posición del icono de ordenamiento
     * @param string $position Posición del icono (top-left, top-right, bottom-left, bottom-right, center)
     * @return string Estilos CSS para la posición
     */
    public function get_drag_handle_position_styles($position) {
        switch ($position) {
            case 'top-left':
                return 'top: 10px !important; left: 10px !important; right: auto !important; bottom: auto !important; transform: none !important;';
            case 'top-right':
                return 'top: 10px !important; right: 10px !important; left: auto !important; bottom: auto !important; transform: none !important;';
            case 'bottom-left':
                return 'bottom: 10px !important; left: 10px !important; top: auto !important; right: auto !important; transform: none !important;';
            case 'bottom-right':
                return 'bottom: 10px !important; right: 10px !important; top: auto !important; left: auto !important; transform: none !important;';
            case 'center':
                return 'top: 50% !important; left: 50% !important; right: auto !important; bottom: auto !important; transform: translate(-50%, -50%) !important;';
            default:
                return 'top: 10px !important; left: 10px !important; right: auto !important; bottom: auto !important; transform: none !important;';
        }
    }
}