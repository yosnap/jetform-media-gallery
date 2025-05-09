/**
 * JetForm Media Gallery - Manejo de galerías de imágenes
 * 
 * Este script maneja la funcionalidad para selección de imagen destacada
 * y galería de imágenes para formularios de JetFormBuilder.
 */

(function($) {
    'use strict';
    
    // Comprobar si el API de medios está disponible
    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
        console.error('Error: wp.media no está disponible. Asegúrate de cargar los scripts de medios de WordPress.');
        return;
    }
    
    // Detectar si es un dispositivo móvil
    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    // Detectar específicamente si es iOS
    var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    $(document).ready(function() {
        console.log('JetForm Media Gallery inicializado');
        
        // Añadir estilos CSS para mejorar la apariencia y usabilidad
        $('head').append(`
            <style>
                /* Estilos para el manejador de arrastre */
                .gallery-image {
                    position: relative;
                    margin: 5px;
                    border-radius: 4px;
                    overflow: hidden;
                }
                .drag-handle {
                    position: absolute;
                    top: 5px;
                    right: 30px;
                    background: rgba(0,0,0,0.6);
                    color: white;
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    cursor: move;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10;
                }
                .drag-handle:before {
                    content: "\\f545";
                    font-family: dashicons;
                    font-size: 16px;
                }
                .drag-handle:hover {
                    background: rgba(0,0,0,0.8);
                }
                
                /* Estilos para la imagen destacada */
                .featured-image-preview {
                    position: relative;
                    margin-top: 10px;
                    border-radius: 4px;
                    overflow: hidden;
                    max-width: 300px;
                }
                .featured-image-preview img {
                    max-width: 100%;
                    height: auto;
                    display: block;
                }
                .remove-featured-image {
                    position: absolute;
                    top: 5px;
                    right: 5px;
                    background: rgba(0,0,0,0.6);
                    color: white;
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    text-align: center;
                    line-height: 22px;
                    font-size: 18px;
                    cursor: pointer;
                    z-index: 10;
                }
                .remove-featured-image:hover {
                    background: rgba(0,0,0,0.8);
                }
                
                /* Estilos para la galería de imágenes */
                .images-preview {
                    display: flex;
                    flex-wrap: wrap;
                    margin-top: 10px;
                    gap: 10px;
                }
                .gallery-image {
                    width: calc(33.333% - 10px);
                    max-width: 150px;
                    position: relative;
                }
                .gallery-image img {
                    width: 100%;
                    height: auto;
                    display: block;
                    border-radius: 4px;
                }
                .remove-image {
                    position: absolute;
                    top: 5px;
                    right: 5px;
                    background: rgba(0,0,0,0.6);
                    color: white;
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    text-align: center;
                    line-height: 22px;
                    font-size: 18px;
                    cursor: pointer;
                    z-index: 20;
                    text-decoration: none;
                }
                .remove-image:hover {
                    background: rgba(0,0,0,0.8);
                }
                
                /* Estilos para los botones de carga */
                .upload-gallery-images, .upload-featured-image {
                    background: #2271b1;
                    color: white;
                    border: none;
                    padding: 8px 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                    display: inline-block;
                    text-decoration: none;
                }
                .upload-gallery-images:hover, .upload-featured-image:hover {
                    background: #135e96;
                }
                
                /* Estilos para el campo de caption */
                .image-caption {
                    width: 100%;
                    margin-top: 5px;
                    padding: 5px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 12px;
                }
                
                /* Estilos para el modo de arrastre */
                .being-dragged {
                    opacity: 0.7;
                    z-index: 9999;
                }
                .ui-sortable-placeholder {
                    visibility: visible !important;
                    border: 2px dashed #ccc;
                    background: rgba(0,0,0,0.05);
                    border-radius: 4px;
                }
                
                /* Mostrar checks en modo "Add to gallery" para permitir selección/deselección */
                .media-modal .attachment .check {
                    display: block !important;
                    opacity: 1 !important;
                    background: rgba(0, 0, 0, 0.5) !important;
                    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.5) !important;
                    border: none !important;
                    width: 24px !important;
                    height: 24px !important;
                    z-index: 100 !important;
                }
                
                .media-modal .attachment.selected .check {
                    display: block !important;
                    opacity: 1 !important;
                    background: #0073aa !important;
                    box-shadow: 0 0 0 1px #fff !important;
                    z-index: 100 !important;
                }
                
                /* Estilo para el icono de verificación */
                .media-modal .attachment .check:after {
                    content: '✓' !important;
                    color: #fff !important;
                    font-size: 16px !important;
                    position: absolute !important;
                    top: 50% !important;
                    left: 50% !important;
                    transform: translate(-50%, -50%) !important;
                    font-weight: bold !important;
                }
                
                /* Estilos específicos para el checkbox */
                .media-modal .attachment .check div,
                .media-modal .attachment.selected .check div {
                    display: none !important;
                }
                
                /* Asegurar que el botón X para eliminar siga visible */
                .gallery-image .remove-image {
                    z-index: 200 !important;
                }
                
                /* Ajustar posición del checkbox para evitar solapamientos */
                .media-modal .attachment .check {
                    top: 5px !important;
                    right: 5px !important;
                }
                
                /* Mejorar responsive del explorador de galerías */
                .media-frame select.media-attachment-filters,
                .media-frame .media-menu select {
                    min-width: 200px !important;
                    width: auto !important;
                    max-width: 100% !important;
                    font-size: 16px !important;
                    height: 36px !important;
                    padding: 0 24px 0 8px !important;
                    background-color: #fff !important;
                    border: 1px solid #ddd !important;
                    border-radius: 4px !important;
                    box-shadow: 0 1px 2px rgba(0,0,0,0.07) !important;
                    color: #32373c !important;
                    margin: 8px !important;
                }
                
                /* Sobreescribir estilos del botón Menu basados en la imagen proporcionada */
                .wp-core-ui .media-frame:not(.hide-menu) .button.media-frame-menu-toggle {
                    display: inline-flex !important;
                    align-items: center !important;
                    position: absolute !important;
                    left: 50% !important;
                    transform: translateX(-50%) !important;
                    margin: -6px 0 0 !important;
                    padding: 0 2px 0 12px !important;
                    font-size: 0.875rem !important;
                    font-weight: 600 !important;
                    text-decoration: none !important;
                    background: transparent !important;
                    height: 0.1% !important;
                    min-height: 40px !important;
                }
                
                /* Estilos específicos para el select Menu */
                .media-modal .media-menu select,
                .media-modal select.media-menu {
                    min-width: 200px !important;
                    width: 200px !important;
                    max-width: 100% !important;
                    font-size: 16px !important;
                    height: 40px !important;
                    padding: 5px 24px 5px 10px !important;
                    background-color: #fff !important;
                    border: 1px solid #8c8f94 !important;
                    border-radius: 4px !important;
                    box-shadow: 0 0 0 transparent !important;
                    color: #2c3338 !important;
                    margin: 8px !important;
                    -webkit-appearance: menulist !important;
                    appearance: menulist !important;
                }
                
                /* Ajustar tamaño de los botones en el explorador de galerías */
                .media-toolbar-primary .button.media-button-gallery-edit,
                .media-toolbar-primary .button.media-button-reverse,
                .media-toolbar-primary .button.button-primary.media-button-insert {
                    padding: 0 12px !important;
                    height: 30px !important;
                    line-height: 28px !important;
                    font-size: 13px !important;
                    min-height: 30px !important;
                }
                
                /* Mejorar responsive en pantallas pequeñas */
                @media (max-width: 782px) {
                    .media-frame select.media-attachment-filters,
                    .media-frame .media-menu select {
                        width: 100% !important;
                        height: 40px !important;
                        font-size: 16px !important;
                        padding: 0 24px 0 8px !important;
                        margin: 10px 0 !important;
                        background-size: 16px 16px !important;
                        background-position: right 8px center !important;
                    }
                    
                    /* Contenedor del select para asegurar que ocupe el ancho correcto */
                    .media-frame .media-menu,
                    .media-frame .media-toolbar-secondary {
                        width: 100% !important;
                        padding: 10px !important;
                        box-sizing: border-box !important;
                    }
                    
                    .media-toolbar-primary .button,
                    .media-toolbar-secondary .button {
                        padding: 0 10px !important;
                        height: 36px !important;
                        line-height: 34px !important;
                        font-size: 13px !important;
                        margin-left: 5px !important;
                        margin-right: 5px !important;
                    }
                    
                    /* Ajustar el layout de los botones en móvil */
                    .media-frame-toolbar .media-toolbar {
                        display: flex !important;
                        flex-wrap: wrap !important;
                        justify-content: space-between !important;
                    }
                    
                    .media-frame-toolbar .media-toolbar-secondary {
                        margin-bottom: 10px !important;
                        flex: 1 0 100% !important;
                    }
                    
                    .media-frame-toolbar .media-toolbar-primary {
                        flex: 1 0 100% !important;
                        display: flex !important;
                        justify-content: flex-end !important;
                    }
                }
            </style>
        `);
        
        // Evento para abrir el explorador de medios para imagen destacada
        $(document).on('click', '.upload-featured-image', function(e) {
            e.preventDefault();
            
            var fieldName = $(this).data('field');
            var controlsContainer = $(this).closest('.featured-image-controls');
            var previewSelector = '#featured-image-preview-' + fieldName;
            var inputField = $('#featured-image-input-' + fieldName);
            
            console.log('Abriendo selector de imagen destacada para el campo:', fieldName);
            
            // Configurar el frame de medios
            var featuredFrame = wp.media({
                title: 'Seleccionar imagen destacada',
                library: {
                    type: 'image'
                },
                button: {
                    text: 'Establecer imagen destacada'
                },
                multiple: false
            });
            
            // Cuando se selecciona una imagen
            featuredFrame.on('select', function() {
                var attachment = featuredFrame.state().get('selection').first().toJSON();
                console.log('Imagen destacada seleccionada:', attachment);
                
                // Actualizar el campo oculto con el ID de la imagen
                inputField.val(attachment.id);
                
                // Actualizar la vista previa
                if ($(previewSelector).length === 0) {
                    controlsContainer.after('<div id="' + previewSelector.substring(1) + '" class="featured-image-preview"></div>');
                }
                
                var imageUrl = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                $(previewSelector).html('<img src="' + imageUrl + '" alt="' + attachment.alt + '"><a href="#" class="remove-featured-image">×</a>');
                $(previewSelector).show();
            });
            
            featuredFrame.open();
        });
        
        // Evento para eliminar la imagen destacada
        $(document).on('click', '.remove-featured-image', function(e) {
            e.preventDefault();
            
            var previewContainer = $(this).closest('.featured-image-preview');
            var fieldName = previewContainer.attr('id').replace('featured-image-preview-', '');
            var inputField = $('#featured-image-input-' + fieldName);
            
            // Limpiar el campo oculto
            inputField.val('');
            
            // Ocultar la vista previa
            previewContainer.hide();
        });
        
        // Función para inicializar sortable en la galería
        function initSortable(previewSelector, fieldName) {
            var galleryPreview = $(previewSelector);
            
            if (galleryPreview.length > 0 && !galleryPreview.hasClass('ui-sortable')) {
                galleryPreview.sortable({
                    items: '.gallery-image',
                    handle: '.drag-handle',
                    placeholder: 'ui-sortable-placeholder',
                    start: function(event, ui) {
                        ui.item.addClass('being-dragged');
                        ui.placeholder.css('visibility', 'visible');
                    },
                    stop: function(event, ui) {
                        ui.item.removeClass('being-dragged');
                        
                        // Actualizar el orden de las imágenes en el campo oculto
                        var updatedOrder = [];
                        galleryPreview.find('.gallery-image').each(function() {
                            updatedOrder.push($(this).data('id'));
                        });
                        $('#gallery-images-input-' + fieldName).val(updatedOrder.join(','));
                    }
                });
            }
        }
        
        // Aplicar estilos al select Menu cuando aparezca en el DOM
        $(document).on('DOMNodeInserted', function(e) {
            if ($(e.target).is('select.media-menu') || $(e.target).find('select.media-menu').length > 0) {
                console.log('Menu select detectado, aplicando estilos');
                
                // Aplicar estilos directamente
                $('select.media-menu').css({
                    'min-width': '200px',
                    'width': '200px',
                    'max-width': '100%',
                    'font-size': '16px',
                    'height': '40px',
                    'padding': '5px 24px 5px 10px',
                    'background-color': '#fff',
                    'border': '1px solid #8c8f94',
                    'border-radius': '4px',
                    'box-shadow': '0 0 0 transparent',
                    'color': '#2c3338',
                    'margin': '8px',
                    '-webkit-appearance': 'menulist',
                    'appearance': 'menulist'
                });
            }
        });
        
        // Evento para abrir el explorador de medios para galerías
        $(document).on('click', '.upload-gallery-images', function(e) {
            e.preventDefault();
            
            var fieldName = $(this).data('field');
            var controlsContainer = $(this).closest('.gallery-controls');
            var previewSelector = '#gallery-images-preview-' + fieldName;
            var inputField = $('#gallery-images-input-' + fieldName);
            var currentIds = inputField.val() ? inputField.val().split(',').filter(Boolean) : [];
            
            console.log('Abriendo selector de galería para el campo:', fieldName);
            console.log('IDs actuales:', currentIds);
            
            // Función común para procesar la selección de imágenes
            function processSelection(attachments) {
                if (attachments.length > 0) {
                    // Asegurarse de que el contenedor de galería exista y sea visible
                    if ($(previewSelector).length === 0) {
                        controlsContainer.after('<div id="' + previewSelector.substring(1) + '" class="images-preview"></div>');
                    }
                    $(previewSelector).show();
                    
                    // Actualizar el campo oculto con los IDs de las imágenes
                    var newIds = attachments.map(function(attachment) {
                        return attachment.id.toString();
                    });
                    
                    inputField.val(newIds.join(','));
                    
                    // Limpiar y reconstruir la vista previa
                    $(previewSelector).empty();
                    
                    // Añadir cada imagen a la vista previa
                    $.each(attachments, function(index, attachment) {
                        var imageHtml = 
                            '<div class="gallery-image" data-id="' + attachment.id + '">' +
                                '<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" alt="' + attachment.alt + '">' +
                                '<a href="#" class="remove-image">×</a>' +
                                '<div class="drag-handle"></div>' +
                                '<input type="text" class="image-caption" placeholder="Caption..." value="' + (attachment.caption || '') + '">' +
                            '</div>';
                        
                        $(previewSelector).append(imageHtml);
                    });
                    
                    // Inicializar sortable para la nueva galería
                    initSortable(previewSelector, fieldName);
                }
            }
            
            // SOLUCIÓN UNIFICADA: Siempre usar wp.media.gallery.edit
            // Esto garantiza que las imágenes existentes siempre aparezcan seleccionadas
            
            // Crear un shortcode de galería (con o sin IDs)
            var shortcode = '[gallery';
            if (currentIds.length > 0) {
                shortcode += ' ids="' + currentIds.join(',') + '"';
            }
            shortcode += ']';
            
            console.log('Usando shortcode:', shortcode);
            
            // Abrir el editor de galería con el shortcode
            var frame = wp.media.gallery.edit(shortcode);
            
            // Cuando se actualiza la galería
            frame.state('gallery-edit').on('update', function(selection) {
                console.log('Selección actualizada, imágenes:', selection.length);
                
                // Obtener los attachments
                var attachments = selection.map(function(attachment) {
                    return attachment.toJSON();
                });
                
                // Procesar la selección
                processSelection(attachments);
            });
        });
        
        // Eliminar imagen de la galería (delegación de eventos)
        $(document).on('click', '.remove-image', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var imageContainer = $(this).closest('.gallery-image');
            var galleryPreview = imageContainer.closest('.images-preview');
            var fieldName = galleryPreview.attr('id').replace('gallery-images-preview-', '');
            var imageId = imageContainer.data('id');
            var inputField = $('#gallery-images-input-' + fieldName);
            var galleryIds = inputField.val() ? inputField.val().split(',') : [];
            
            galleryIds = galleryIds.filter(function(id) {
                return id != imageId;
            });
            
            // Actualizar el valor del campo oculto
            inputField.val(galleryIds.join(','));
            
            // Eliminar la imagen de la vista previa
            imageContainer.fadeOut(300, function() {
                $(this).remove();
                
                // Si no quedan imágenes, ocultar el contenedor de galería
                if (galleryPreview.children('.gallery-image').length === 0) {
                    galleryPreview.hide();
                }
            });
        });
        
        // Inicializar sortable para galerías existentes
        $('.images-preview').each(function() {
            var fieldName = $(this).attr('id').replace('gallery-images-preview-', '');
            initSortable('#' + $(this).attr('id'), fieldName);
        });
    });
    
})(jQuery);