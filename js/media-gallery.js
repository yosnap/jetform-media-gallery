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
                
                // Inicializar sortable para permitir reordenar las imágenes
                if (typeof $.fn.sortable !== 'undefined') {
                    var sortableOptions = {
                        items: '.gallery-image',
                        cursor: 'move',
                        opacity: 0.7,
                        placeholder: 'gallery-image-placeholder',
                        tolerance: 'pointer',
                        update: function() {
                            // Actualizar el orden de las imágenes en el campo oculto
                            var newOrder = [];
                            galleryPreview.find('.gallery-image').each(function() {
                                newOrder.push($(this).data('id'));
                            });
                            $('#gallery-images-input-' + fieldName).val(newOrder.join(','));
                        }
                    };
                    
                    // Configuraciones específicas para móviles
                    if (isMobile) {
                        // Mejora para dispositivos táctiles
                        sortableOptions.delay = 150;
                        sortableOptions.distance = 10;
                        sortableOptions.scroll = false;
                        
                        // Configuraciones específicas para iOS
                        if (isIOS) {
                            sortableOptions.handle = '.drag-handle';
                            sortableOptions.helper = 'clone';
                            sortableOptions.appendTo = 'body';
                            sortableOptions.zIndex = 9999;
                            sortableOptions.tolerance = 'intersect';
                        }
                    }
                    
                    galleryPreview.sortable(sortableOptions);
                    
                    // Para iOS, necesitamos inicializar el touch-punch
                    if (isIOS && typeof $.fn.draggable !== 'undefined') {
                        // Inicializar jQuery UI Touch Punch
                        (function($) {
                            if ($.support && $.support.touch !== undefined) return;
                            
                            $.support = $.support || {};
                            $.support.touch = 'ontouchend' in document;
                            
                            if (!$.support.touch) return;
                            
                            var mouseProto = $.ui.mouse.prototype,
                                _mouseInit = mouseProto._mouseInit,
                                _mouseDestroy = mouseProto._mouseDestroy,
                                touchHandled;
                            
                            mouseProto._mouseInit = function() {
                                var self = this;
                                self.element.bind({
                                    'touchstart.jQueryUiTouchPunch': function(event) { return self._touchStart(event); },
                                    'touchmove.jQueryUiTouchPunch': function(event) { return self._touchMove(event); },
                                    'touchend.jQueryUiTouchPunch': function(event) { return self._touchEnd(event); }
                                });
                                _mouseInit.call(self);
                            };
                            
                            mouseProto._mouseDestroy = function() {
                                var self = this;
                                self.element.unbind({
                                    'touchstart.jQueryUiTouchPunch': self._touchStart,
                                    'touchmove.jQueryUiTouchPunch': self._touchMove,
                                    'touchend.jQueryUiTouchPunch': self._touchEnd
                                });
                                _mouseDestroy.call(self);
                            };
                            
                            mouseProto._touchStart = function(event) {
                                var self = this;
                                if (touchHandled || !self._mouseCapture(event.originalEvent.changedTouches[0])) return;
                                
                                touchHandled = true;
                                self._touchMoved = false;
                                
                                event.preventDefault();
                                
                                var touch = event.originalEvent.changedTouches[0];
                                var simulatedEvent = document.createEvent('MouseEvents');
                                simulatedEvent.initMouseEvent('mousedown', true, true, window, 1,
                                    touch.screenX, touch.screenY, touch.clientX, touch.clientY,
                                    false, false, false, false, 0, null);
                                self.element[0].dispatchEvent(simulatedEvent);
                            };
                            
                            mouseProto._touchMove = function(event) {
                                if (!touchHandled) return;
                                
                                this._touchMoved = true;
                                
                                var touch = event.originalEvent.changedTouches[0];
                                var simulatedEvent = document.createEvent('MouseEvents');
                                simulatedEvent.initMouseEvent('mousemove', true, true, window, 1,
                                    touch.screenX, touch.screenY, touch.clientX, touch.clientY,
                                    false, false, false, false, 0, null);
                                this.element[0].dispatchEvent(simulatedEvent);
                                
                                event.preventDefault();
                            };
                            
                            mouseProto._touchEnd = function(event) {
                                if (!touchHandled) return;
                                
                                var touch = event.originalEvent.changedTouches[0];
                                var simulatedEvent = document.createEvent('MouseEvents');
                                simulatedEvent.initMouseEvent(this._touchMoved ? 'mouseup' : 'click', true, true, window, 1,
                                    touch.screenX, touch.screenY, touch.clientX, touch.clientY,
                                    false, false, false, false, 0, null);
                                this.element[0].dispatchEvent(simulatedEvent);
                                
                                touchHandled = false;
                                this._touchMoved = false;
                            };
                        })(jQuery);
                    }
                }
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
            var currentIds = inputField.val() ? inputField.val().split(',').filter(Boolean) : [];
            
            // Configuración optimizada para móviles
            var galleryFrameOptions = {
                title: JetFormMediaGallery.i18n.selectGalleryImages || 'Seleccionar imágenes para galería',
                button: {
                    text: JetFormMediaGallery.i18n.addToGallery || 'Añadir a la galería'
                },
                multiple: true
            };
            
            // Optimizaciones específicas para móviles
            if (isMobile) {
                // En móviles, aseguramos que la biblioteca se abre en modo "Subir archivos" por defecto
                galleryFrameOptions.library = { type: 'image' };
                galleryFrameOptions.frame = 'select';
                galleryFrameOptions.state = 'library';
                
                // Configuraciones específicas para iOS
                if (isIOS) {
                    // Forzar el modo de selección múltiple en iOS
                    galleryFrameOptions.multiple = true;
                    
                    // Añadir un pequeño retraso para iOS
                    setTimeout(function() {
                        $('.media-frame-content .attachments-browser').addClass('ios-enhanced');
                    }, 800);
                }
            }
            
            var galleryFrame = wp.media(galleryFrameOptions);
            
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
                
                // En móviles, mostrar mensaje de ayuda y mejorar la interfaz
                if (isMobile) {
                    setTimeout(function() {
                        // Mensaje de ayuda específico para iOS
                        var helpText = isIOS ? 
                            'INSTRUCCIONES: 1) Toca el botón azul "Seleccionar múltiples imágenes". 2) Toca 2 veces sobre cada imagen que quieras seleccionar (aparecerá un texto verde "Seleccionada ✓"). 3) Cuando termines, toca el botón verde "Confirmar selección".' : 
                            'Para seleccionar múltiples imágenes, mantén presionada cada imagen que desees incluir.';
                        
                        var helpMessage = $('<div class="mobile-upload-help" style="padding: 10px; background: #f7f7f7; margin: 10px; border-radius: 4px; text-align: center;">' + helpText + '</div>');
                        $('.media-frame-content').prepend(helpMessage);
                        
                        // Para iOS, añadir botón de selección múltiple
                        if (isIOS) {
                            var selectButton = $('<button type="button" class="ios-select-button button" style="margin: 10px; display: block; width: calc(100% - 20px); font-size: 18px !important; padding: 15px !important;">1: Seleccionar múltiples imágenes</button>');
                            $('.mobile-upload-help').after(selectButton);
                            
                            // Añadir botón para confirmar selección múltiple
                            var confirmButton = $('<button type="button" class="ios-confirm-button button" style="margin: 10px; display: none; width: calc(100% - 20px); background-color: #4CAF50 !important; font-size: 18px !important; padding: 15px !important;">3: Confirmar selección (0)</button>');
                            selectButton.after(confirmButton);
                            
                            // Variable para almacenar las imágenes seleccionadas
                            var selectedImages = [];
                            
                            selectButton.on('click', function() {
                                // Activar modo de selección múltiple
                                $('.attachments-browser').addClass('ios-multiple-select-mode');
                                $(this).text('2: Toca 2 veces sobre las imágenes').addClass('selecting');
                                confirmButton.show();
                                
                                // Desactivar el botón "Añadir a la galería" durante la selección múltiple
                                $('.media-toolbar-primary .button').prop('disabled', true);
                                
                                // Limpiar selecciones previas
                                selectedImages = [];
                                confirmButton.text('Confirmar selección (0)');
                                $('.attachments .attachment.selected').removeClass('selected');
                                
                                // Añadir indicadores visuales de selección a cada imagen
                                $('.attachments .attachment').each(function() {
                                    var $attachment = $(this);
                                    
                                    // Solo añadir el botón si no existe ya
                                    if ($attachment.find('.ios-select-indicator').length === 0) {
                                        // Crear un botón de selección visible en cada imagen
                                        var $selectButton = $('<div class="ios-select-indicator">Tocar 2 veces para seleccionar</div>');
                                        $attachment.append($selectButton);
                                    }
                                });
                                
                                // Hacer que los attachments sean seleccionables con un toque
                                $('.attachments .attachment').off('click.ios-select').on('click.ios-select', function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    
                                    var $this = $(this);
                                    $this.toggleClass('selected');
                                    
                                    // Obtener el ID del adjunto
                                    var attachmentId = $this.data('id');
                                    
                                    // Actualizar el texto del indicador
                                    var $indicator = $this.find('.ios-select-indicator');
                                    if ($this.hasClass('selected')) {
                                        $indicator.text('Seleccionada ✓').addClass('is-selected');
                                        // Verificar si ya existe en la lista
                                        if (selectedImages.indexOf(attachmentId) === -1) {
                                            selectedImages.push(attachmentId);
                                        }
                                    } else {
                                        $indicator.text('Tocar 2 veces para seleccionar').removeClass('is-selected');
                                        // Si no está seleccionado, quitarlo de la lista
                                        var index = selectedImages.indexOf(attachmentId);
                                        if (index !== -1) {
                                            selectedImages.splice(index, 1);
                                        }
                                    }
                                    
                                    // Actualizar contador en el botón de confirmar
                                    confirmButton.text('PASO 3: Confirmar selección (' + selectedImages.length + ')');
                                });
                            });
                            
                            // Manejar la confirmación de selección múltiple
                            confirmButton.on('click', function() {
                                if (selectedImages.length > 0) {
                                    // Limpiar selección actual
                                    selection.reset();
                                    
                                    // Añadir todas las imágenes seleccionadas
                                    selectedImages.forEach(function(id) {
                                        var attachment = wp.media.attachment(id);
                                        attachment.fetch();
                                        selection.add(attachment);
                                    });
                                    
                                    // Volver al modo normal
                                    $('.attachments-browser').removeClass('ios-multiple-select-mode');
                                    selectButton.text('Seleccionar múltiples imágenes').removeClass('selecting');
                                    confirmButton.hide();
                                    
                                    // Reactivar el botón "Añadir a la galería"
                                    $('.media-toolbar-primary .button').prop('disabled', false);
                                    
                                    // Simular clic en el botón "Añadir a la galería"
                                    setTimeout(function() {
                                        $('.media-toolbar-primary .button').click();
                                    }, 100);
                                } else {
                                    alert('Por favor, selecciona al menos una imagen');
                                }
                            });
                            
                            // Añadir botón para cancelar selección
                            var cancelButton = $('<button type="button" class="ios-cancel-button button" style="margin: 10px; display: none; width: calc(100% - 20px); background-color: #f44336 !important;">Cancelar selección</button>');
                            confirmButton.after(cancelButton);
                            
                            cancelButton.on('click', function() {
                                // Volver al modo normal
                                $('.attachments-browser').removeClass('ios-multiple-select-mode');
                                selectButton.text('Seleccionar múltiples imágenes').removeClass('selecting');
                                confirmButton.hide();
                                cancelButton.hide();
                                
                                // Limpiar selecciones
                                $('.attachments .attachment.selected').removeClass('selected');
                                
                                // Reactivar el botón "Añadir a la galería"
                                $('.media-toolbar-primary .button').prop('disabled', false);
                            });
                            
                            // Mostrar el botón de cancelar cuando se activa el modo de selección
                            selectButton.on('click', function() {
                                cancelButton.show();
                            });
                        }
                        
                        // Mejorar la visualización de la biblioteca de medios en móviles
                        $('.media-frame-content').css({
                            'overflow-y': 'auto',
                            '-webkit-overflow-scrolling': 'touch'
                        });
                        
                        // Hacer que los botones sean más grandes en móviles
                        $('.media-toolbar-primary button, .media-toolbar-secondary button').css({
                            'padding': '12px 15px',
                            'font-size': '16px',
                            'height': 'auto'
                        });
                    }, 800);
                }
            });
            
            galleryFrame.on('select', function() {
                var attachments = galleryFrame.state().get('selection').toJSON();
                
                if (attachments.length > 0) {
                    // Asegurarse de que el contenedor de galería exista y sea visible
                    if ($(previewSelector).length === 0) {
                        controlsContainer.append('<div id="gallery-images-preview-' + fieldName + '" class="images-preview"></div>');
                    } else {
                        // No vaciar el contenedor para mantener las imágenes existentes
                        $(previewSelector).show();
                    }
                    
                    var galleryPreview = $(previewSelector);
                    var existingIds = currentIds;
                    var newIds = [];
                    
                    // Primero, añadir las imágenes existentes que no están en la nueva selección
                    if (!galleryPreview.is(':empty')) {
                        // Conservar las imágenes existentes y su orden
                        existingIds = [];
                        galleryPreview.find('.gallery-image').each(function() {
                            existingIds.push($(this).data('id').toString());
                        });
                    }
                    
                    // Añadir las nuevas imágenes seleccionadas
                    attachments.forEach(function(attachment) {
                        var attachmentId = attachment.id.toString();
                        newIds.push(attachmentId);
                        
                        // Verificar si la imagen ya existe en la galería
                        if (existingIds.indexOf(attachmentId) === -1) {
                            galleryPreview.append(
                                '<div class="gallery-image" data-id="' + attachment.id + '" style="background-image: url(\'' + attachment.url + '\')">' +
                                '<div class="image-overlay"></div>' +
                                '<div class="drag-handle" title="Arrastrar para ordenar"></div>' +
                                '<button type="button" class="remove-image">×</button>' +
                                '</div>'
                            );
                        }
                    });
                    
                    // Actualizar el valor del campo oculto con todas las imágenes
                    inputField.val(newIds.join(','));
                    
                    // Reinicializar sortable después de añadir nuevas imágenes
                    if (typeof $.fn.sortable !== 'undefined') {
                        galleryPreview.sortable('destroy').sortable({
                            items: '.gallery-image',
                            cursor: 'move',
                            opacity: 0.7,
                            placeholder: 'gallery-image-placeholder',
                            tolerance: 'pointer',
                            handle: '.drag-handle',
                            update: function() {
                                // Actualizar el orden de las imágenes en el campo oculto
                                var updatedOrder = [];
                                galleryPreview.find('.gallery-image').each(function() {
                                    updatedOrder.push($(this).data('id'));
                                });
                                inputField.val(updatedOrder.join(','));
                            }
                        });
                    }
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