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
    
    $(document).ready(function() {
        // Inicializar campos con valores existentes si estamos en modo edición
        $('.media-gallery-field').each(function() {
            // Comprobar si hay un valor inicial en los campos hidden
            var inputField = $(this).find('input[type="hidden"]');
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
            var controlsContainer = $(this).closest('.image-controls');
            var previewSelector = '#featured-image-preview-' + fieldName;
            
            var featuredImageFrame = wp.media({
                title: JetFormMediaGallery.i18n.selectFeaturedImage || 'Seleccionar imagen destacada',
                button: {
                    text: JetFormMediaGallery.i18n.useThisImage || 'Usar esta imagen'
                },
                multiple: false
            });
            
            featuredImageFrame.on('select', function() {
                var attachment = featuredImageFrame.state().get('selection').first().toJSON();
                
                // Si no existe el contenedor de vista previa, crearlo
                if ($(previewSelector).length === 0) {
                    controlsContainer.append(
                        '<div id="featured-image-preview-' + fieldName + '" class="image-preview has-image" style="background-image: url(\'' + attachment.url + '\')">' +
                        '<div class="image-overlay"></div>' +
                        '<button type="button" class="remove-featured-image">×</button>' +
                        '</div>'
                    );
                } else {
                    // Si ya existe, actualizar su contenido
                    var previewContainer = $(previewSelector);
                    previewContainer.css('background-image', 'url(' + attachment.url + ')');
                    previewContainer.addClass('has-image');
                    previewContainer.show();
                }
                
                $('#featured-image-input-' + fieldName).val(attachment.id);
            });
            
            featuredImageFrame.open();
        });
        
        // Eliminar imagen destacada
        $(document).on('click', '.remove-featured-image', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var previewContainer = $(this).closest('.image-preview');
            var fieldName = previewContainer.attr('id').replace('featured-image-preview-', '');
            
            $('#featured-image-input-' + fieldName).val('');
            
            // Eliminar completamente el contenedor de vista previa
            previewContainer.remove();
        });
        
        // Media Uploader para la galería
        $(document).on('click', '.upload-gallery-images', function(e) {
            e.preventDefault();
            
            var fieldName = $(this).data('field');
            var controlsContainer = $(this).closest('.gallery-controls');
            var previewSelector = '#gallery-images-preview-' + fieldName;
            var inputField = $('#gallery-images-input-' + fieldName);
            var currentIds = inputField.val() ? inputField.val().split(',') : [];
            
            var galleryFrame = wp.media({
                title: JetFormMediaGallery.i18n.selectGalleryImages || 'Seleccionar imágenes para galería',
                button: {
                    text: JetFormMediaGallery.i18n.addToGallery || 'Añadir a la galería'
                },
                multiple: true
            });
            
            // Preseleccionar imágenes existentes
            galleryFrame.on('open', function() {
                var selection = galleryFrame.state().get('selection');
                
                // Añadir las imágenes existentes a la selección
                currentIds.forEach(function(id) {
                    if (id) {
                        var attachment = wp.media.attachment(id);
                        attachment.fetch();
                        selection.add(attachment ? [attachment] : []);
                    }
                });
            });
            
            galleryFrame.on('select', function() {
                var attachments = galleryFrame.state().get('selection').toJSON();
                
                if (attachments.length > 0) {
                    // Asegurarse de que el contenedor de galería exista y sea visible
                    if ($(previewSelector).length === 0) {
                        controlsContainer.append('<div id="gallery-images-preview-' + fieldName + '" class="images-preview"></div>');
                    } else {
                        $(previewSelector).empty().show();
                    }
                    
                    var galleryPreview = $(previewSelector);
                    var newIds = [];
                    
                    attachments.forEach(function(attachment) {
                        newIds.push(attachment.id);
                        
                        galleryPreview.append(
                            '<div class="gallery-image" data-id="' + attachment.id + '" style="background-image: url(\'' + attachment.url + '\')">' +
                            '<div class="image-overlay"></div>' +
                            '<button type="button" class="remove-image">×</button>' +
                            '</div>'
                        );
                    });
                    
                    // Actualizar el valor del campo oculto
                    inputField.val(newIds.join(','));
                }
            });
            
            galleryFrame.open();
        });
        
        // Función para manejar la eliminación de imágenes de galería
        function handleGalleryImageRemoval(e, fieldName) {
            e.preventDefault();
            e.stopPropagation();
            
            var clickedButton = $(e.target);
            var imageContainer = clickedButton.parent();
            var galleryPreview = imageContainer.closest('.images-preview');
            var imageId = imageContainer.data('id');
            var inputField = $('#gallery-images-input-' + fieldName);
            var galleryIds = inputField.val().split(',');
            
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
        }
        
        // Eliminar imagen de la galería (delegación de eventos)
        $(document).on('click', '.remove-image', function(e) {
            var fieldName = $(this).closest('.gallery-container').find('.upload-gallery-images').data('field');
            handleGalleryImageRemoval(e, fieldName);
        });
    });
    
})(jQuery); 