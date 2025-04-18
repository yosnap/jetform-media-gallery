<?php
/**
 * Clase para el procesamiento del formulario
 *
 * @package JetForm_Media_Gallery
 * @since 1.0.0
 */

// No ejecutar directamente
if (!defined('ABSPATH')) {
    die('No direct script access allowed');
}

class JetForm_Media_Gallery_Process {
    
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
     * Procesar la presentación del formulario
     */
    public function process_form_submission($handler, $actions = null, $form_id = null) {
        $this->main->log_debug("=== INICIO PROCESS FORM SUBMISSION ===");
        
        // Verificar si tenemos el objeto handler
        if (!$handler || !is_object($handler)) {
            $this->main->log_debug("El handler no es un objeto válido");
            return;
        }
        
        // Obtener los datos del formulario
        $form_data = isset($handler->form_data) ? $handler->form_data : [];
        
        // Si form_data está vacío, intentar obtener de $_POST
        if (empty($form_data)) {
            $form_data = $_POST;
            $this->main->log_debug("Usando datos de POST: " . print_r($form_data, true));
        }
        
        // Obtener el ID del post
        $post_id = $this->find_post_id($handler, $actions, $form_data);
        
        if (!$post_id) {
            $this->main->log_debug("No se pudo encontrar un ID de post válido");
            return;
        }
        
        $this->main->log_debug("ID de post encontrado: $post_id");
        
        // Procesar imagen destacada
        if (isset($form_data['imagen_destacada']) && !empty($form_data['imagen_destacada'])) {
            $featured_id = intval($form_data['imagen_destacada']);
            $this->main->log_debug("Estableciendo imagen destacada: $featured_id");
            set_post_thumbnail($post_id, $featured_id);
            
            // Verificar que se guardó correctamente
            $saved_thumbnail = get_post_thumbnail_id($post_id);
            $this->main->log_debug("Imagen destacada guardada: $saved_thumbnail");
        }
        
        // Procesar galería
        if (isset($form_data['galeria']) && !empty($form_data['galeria'])) {
            $gallery_value = $form_data['galeria'];
            $gallery_ids = is_array($gallery_value) ? $gallery_value : explode(',', $gallery_value);
            $gallery_ids = array_map('intval', array_filter($gallery_ids));
            
            $this->main->log_debug("Procesando galería con IDs: " . implode(', ', $gallery_ids));
            
            // Eliminar galería existente
            delete_post_meta($post_id, 'ad_gallery');
            
            // Guardar nueva galería
            update_post_meta($post_id, 'ad_gallery', $gallery_ids);
            
            // Verificar que se guardó correctamente
            $saved_gallery = get_post_meta($post_id, 'ad_gallery', true);
            $this->main->log_debug("Galería guardada: " . print_r($saved_gallery, true));
        }
        
        // Forzar limpieza de caché
        clean_post_cache($post_id);
        wp_cache_delete($post_id, 'posts');
        wp_cache_delete($post_id, 'post_meta');
        
        $this->main->log_debug("=== FIN PROCESS FORM SUBMISSION ===");
    }
    
    /**
     * Encontrar el ID del post
     */
    public function find_post_id($handler, $actions = null, $form_data = []) {
        $post_id = null;
        
        // 1. Buscar en los datos del formulario
        if (isset($form_data['post_id'])) {
            $post_id = absint($form_data['post_id']);
            $this->main->log_debug("Post ID encontrado en form_data[post_id]: $post_id");
        }
        
        // 2. Buscar en las acciones de JetFormBuilder
        if (!$post_id && is_array($actions)) {
            foreach ($actions as $action) {
                if (isset($action['type']) && $action['type'] === 'insert_post' && isset($action['post_id'])) {
                    $post_id = absint($action['post_id']);
                    $this->main->log_debug("Post ID encontrado en acciones: $post_id");
                    break;
                }
            }
        }
        
        // 3. Buscar en los datos de respuesta del handler
        if (!$post_id && isset($handler->action_handler) && isset($handler->action_handler->response_data)) {
            $response_data = $handler->action_handler->response_data;
            
            if (isset($response_data['inserted_post_id'])) {
                $post_id = absint($response_data['inserted_post_id']);
                $this->main->log_debug("Post ID encontrado en response_data[inserted_post_id]: $post_id");
            } elseif (isset($response_data['post_id'])) {
                $post_id = absint($response_data['post_id']);
                $this->main->log_debug("Post ID encontrado en response_data[post_id]: $post_id");
            }
        }
        
        // 4. Buscar el último post del tipo correcto
        if (!$post_id) {
            $post_id = $this->find_last_post();
            if ($post_id) {
                $this->main->log_debug("Post ID encontrado buscando el último post: $post_id");
            }
        }
        
        // Verificar que el post existe
        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                return $post_id;
            }
        }
        
        return null;
    }
    
    /**
     * Guardar imágenes después de insertar post
     */
    public function save_post_images($post_id, $form_data = []) {
        $this->main->log_debug("Hook save_post_images activado con post_id: $post_id");
        
        // Convertir objetos a arrays si es necesario
        if (is_object($form_data) && method_exists($form_data, 'get_form_data')) {
            $form_data = $form_data->get_form_data();
        } elseif (is_object($form_data) && method_exists($form_data, 'to_array')) {
            $form_data = $form_data->to_array();
        } elseif (empty($form_data) || !is_array($form_data)) {
            // Si no hay datos o no son un array, intentar obtenerlos de $_POST
            $form_data = $_POST;
        }
        
        $this->main->log_debug("Datos del formulario en save_post_images: " . print_r($form_data, true));
        $this->save_images_to_post($post_id, $form_data);
    }
    
    /**
     * Guardar imágenes después del envío del formulario
     */
    public function save_form_images($form_data, $form_id = null) {
        $this->main->log_debug("Hook save_form_images activado");
        
        // Intentar encontrar el ID del post a partir de los datos del formulario
        $post_id = null;
        
        // Buscar en los datos de respuesta estándar
        if (isset($form_data['inserted_post_id'])) {
            $post_id = $form_data['inserted_post_id'];
        } elseif (isset($form_data['post_id'])) {
            $post_id = $form_data['post_id'];
        }
        
        // Si no encontramos el ID, buscar el último post creado
        if (!$post_id) {
            $post_id = $this->find_last_post();
        }
        
        if (!$post_id) {
            $this->main->log_debug("No se pudo encontrar un ID de post válido en save_form_images");
            return;
        }
        
        $this->main->log_debug("ID de post encontrado en save_form_images: $post_id");
        
        // Convertir al formato adecuado si es necesario
        $data = is_array($form_data) ? $form_data : $_POST;
        
        $this->save_images_to_post($post_id, $data);
    }
    
    /**
     * Guardar al guardar directamente un post
     */
    public function save_on_direct_post_save($post_id, $post, $update) {
        // Solo procesar si estamos actualizando un post
        if (!$update || empty($_POST)) {
            return;
        }
        
        $this->main->log_debug("Hook save_on_direct_post_save activado para post_id: $post_id");
        $this->save_images_to_post($post_id, $_POST);
    }
    
    /**
     * Guardar después de todas las acciones de JetFormBuilder
     */
    public function save_after_actions($actions_handler, $request) {
        $this->main->log_debug("Hook save_after_actions activado");
        
        // Obtener el ID del post de las acciones
        $post_id = null;
        
        if (isset($actions_handler) && method_exists($actions_handler, 'get_response_data')) {
            $response_data = $actions_handler->get_response_data();
            
            if (isset($response_data['inserted_post_id'])) {
                $post_id = $response_data['inserted_post_id'];
            } elseif (isset($response_data['post_id'])) {
                $post_id = $response_data['post_id'];
            }
        }
        
        if (!$post_id) {
            $post_id = $this->find_last_post();
        }
        
        if (!$post_id) {
            $this->main->log_debug("No se pudo encontrar un ID de post válido en save_after_actions");
            return;
        }
        
        $this->main->log_debug("ID de post encontrado en save_after_actions: $post_id");
        
        $data = is_array($request) ? $request : $_POST;
        $this->save_images_to_post($post_id, $data);
    }
    
    /**
     * Método de emergencia para guardar imágenes al final del procesamiento
     */
    public function emergency_save_images() {
        // Solo procesar si estamos en un envío de formulario de JetFormBuilder
        if (!isset($_POST['jet_form_builder_submit']) || !isset($_POST['has_media_gallery'])) {
            return;
        }
        
        $this->main->log_debug("Método de emergencia activado para guardar imágenes");
        
        // Intentar encontrar el ID del post más reciente del tipo correcto
        $post_id = $this->find_last_post();
        
        if (!$post_id) {
            $this->main->log_debug("No se pudo encontrar un ID de post válido en emergency_save_images");
            return;
        }
        
        $this->main->log_debug("ID de post encontrado en emergency_save_images: $post_id");
        $this->save_images_to_post($post_id, $_POST);
    }
    
    /**
     * Guardar imágenes en un post específico
     */
    public function save_images_to_post($post_id, $form_data) {
        $this->main->log_debug("=== INICIO DE GUARDADO DE IMÁGENES ===");
        $this->main->log_debug("Post ID: $post_id");
        $this->main->log_debug("Datos del formulario completos: " . print_r($form_data, true));
        
        if (!$post_id) {
            $this->main->log_debug("Error: Post ID no válido");
            return false;
        }
        
        // Asegurarnos de que tenemos los datos de imágenes
        // Verificar campos de imagen destacada que comienzan con 'featured_' o 'imagen_'
        foreach ($form_data as $key => $value) {
            if (preg_match('/^(featured_|imagen_)/', $key) && !empty($value)) {
                $form_data['media_gallery_featured'] = $value;
                $this->main->log_debug("Campo de imagen destacada detectado: $key con valor: $value");
                break;
            }
        }
        
        // Verificar campos de galería
        foreach ($form_data as $key => $value) {
            if (preg_match('/^(gallery|galeria)/', $key) && !empty($value)) {
                $form_data['media_gallery_gallery'] = $value;
                $this->main->log_debug("Campo de galería detectado: $key con valor: " . (is_array($value) ? implode(',', $value) : $value));
                break;
            }
        }
        
        // Verificar si hay datos de media_gallery
        if (isset($_POST['media_gallery_featured'])) {
            $form_data['media_gallery_featured'] = $_POST['media_gallery_featured'];
        }
        if (isset($_POST['media_gallery_gallery'])) {
            $form_data['media_gallery_gallery'] = $_POST['media_gallery_gallery'];
        }
        
        // Verificar campos configurados
        $settings = $this->main->get_settings();
        $fields = isset($settings['image_fields']) ? $settings['image_fields'] : [];
        
        foreach ($fields as $field) {
            $field_name = isset($field['name']) ? $field['name'] : '';
            $meta_key = isset($field['meta_key']) ? $field['meta_key'] : '';
            
            if (!empty($field_name) && !empty($meta_key) && isset($form_data[$field_name])) {
                $value = $form_data[$field_name];
                
                if ($field['type'] === 'single') {
                    // Imagen destacada
                    if ($meta_key === '_thumbnail_id') {
                        $form_data['media_gallery_featured'] = $value;
                    } else {
                        update_post_meta($post_id, $meta_key, $value);
                        $this->main->log_debug("Guardado campo single personalizado: $meta_key con valor: $value");
                    }
                } else {
                    // Galería
                    $form_data['media_gallery_gallery'] = $value;
                    $this->main->log_debug("Campo de galería configurado: $field_name -> $meta_key con valor: " . (is_array($value) ? implode(',', $value) : $value));
                }
            }
        }
        
        // Verificar permisos
        if (!current_user_can('upload_files')) {
            $this->main->log_debug("Error: El usuario no tiene permisos para subir archivos");
            return false;
        }
        
        // Verificar que el post existe
        $post = get_post($post_id);
        if (!$post) {
            $this->main->log_debug("Error: Post no encontrado");
            return false;
        }
        
        $success = true;
        
        // Procesar imagen destacada
        if (!empty($form_data['media_gallery_featured'])) {
            $featured_id = intval($form_data['media_gallery_featured']);
            $this->main->log_debug("Intentando guardar imagen destacada con ID: $featured_id");
            
            // Verificar que la imagen existe
            $attachment = get_post($featured_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                $result = set_post_thumbnail($post_id, $featured_id);
                $this->main->log_debug("Resultado set_post_thumbnail: " . ($result ? "éxito" : "fallo"));
                
                if (!$result) {
                    // Intentar forzar el guardado
                    update_post_meta($post_id, '_thumbnail_id', $featured_id);
                    $this->main->log_debug("Forzando actualización de _thumbnail_id directamente");
                }
            } else {
                $this->main->log_debug("Error: La imagen con ID $featured_id no existe o no es un adjunto válido");
            }
        }
        
        // Procesar galería
        if (!empty($form_data['media_gallery_gallery'])) {
            $gallery_value = $form_data['media_gallery_gallery'];
            $gallery_ids = is_array($gallery_value) ? $gallery_value : explode(',', $gallery_value);
            $gallery_ids = array_map('intval', array_filter($gallery_ids));
            
            if (!empty($gallery_ids)) {
                $this->main->log_debug("Intentando guardar galería con IDs: " . implode(', ', $gallery_ids));
                
                // Limpiar meta existente
                delete_post_meta($post_id, 'ad_gallery');
                
                // Guardar nueva galería
                $result = add_post_meta($post_id, 'ad_gallery', $gallery_ids, true);
                
                if (!$result) {
                    $result = update_post_meta($post_id, 'ad_gallery', $gallery_ids);
                    $this->main->log_debug("Actualizada galería existente en ad_gallery");
                } else {
                    $this->main->log_debug("Añadida nueva galería en ad_gallery");
                }
                
                $this->main->log_debug("Resultado guardado galería: " . ($result ? "éxito" : "fallo"));
            } else {
                $this->main->log_debug("Advertencia: Array de galería vacío después de filtrar");
            }
        } else {
            $this->main->log_debug("No se encontraron datos para la galería");
        }
        
        // Verificación final
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $gallery = get_post_meta($post_id, 'ad_gallery', true);
        
        $this->main->log_debug("=== VERIFICACIÓN FINAL ===");
        $this->main->log_debug("Thumbnail ID guardado: " . ($thumbnail_id ? $thumbnail_id : "no encontrado"));
        $this->main->log_debug("Galería guardada: " . (is_array($gallery) ? implode(', ', $gallery) : "no encontrada"));
        
        // Forzar limpieza de caché
        clean_post_cache($post_id);
        wp_cache_delete($post_id, 'posts');
        wp_cache_delete($post_id, 'post_meta');
        
        $this->main->log_debug("=== FIN DE GUARDADO DE IMÁGENES ===");
        
        return $success;
    }
    
    /**
     * Encontrar el último post insertado del tipo correcto
     */
    public function find_last_post() {
        global $wpdb;
        $post_type = 'singlecar'; // El tipo de post esperado
        
        $latest_post = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_status != 'trash' ORDER BY ID DESC LIMIT 1",
                $post_type
            )
        );
        
        return $latest_post ? $latest_post->ID : null;
    }
    
    /**
     * Verificar que los metadatos se guardaron correctamente
     */
    public function verify_post_meta($meta_id, $post_id, $meta_key, $meta_value) {
        if ($meta_key === 'ad_gallery') {
            $this->main->log_debug("Verificando metadatos después de guardar");
            $this->main->log_debug("Meta ID: $meta_id");
            $this->main->log_debug("Post ID: $post_id");
            $this->main->log_debug("Meta Key: $meta_key");
            $this->main->log_debug("Meta Value: " . print_r($meta_value, true));
            
            // Verificar que los datos se guardaron correctamente
            $saved_value = get_post_meta($post_id, $meta_key, true);
            if ($saved_value !== $meta_value) {
                $this->main->log_debug("Error: Los metadatos no se guardaron correctamente");
                $this->main->log_debug("Valor guardado: " . print_r($saved_value, true));
                
                // Intentar guardar nuevamente
                update_post_meta($post_id, $meta_key, $meta_value);
            }
        }
    }
    
    /**
     * Debug de datos del formulario
     */
    public function debug_form_data($form_data, $handler = null) {
        $this->main->log_debug("=== DEBUG FORM DATA ===");
        $this->main->log_debug("Form Data: " . print_r($form_data, true));
        $this->main->log_debug("POST Data: " . print_r($_POST, true));
        $this->main->log_debug("Files: " . print_r($_FILES, true));
        if ($handler) {
            $this->main->log_debug("Handler: " . print_r($handler, true));
        }
        return $form_data;
    }
} 