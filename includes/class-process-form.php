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

class JetForm_Media_Gallery_Process_Form {
    
    /**
     * Instancia de la clase principal
     */
    private $main;
    
    /**
     * Constructor
     */
    public function __construct() {
        // El main se establece mediante set_main()
    }
    
    /**
     * Establecer la instancia principal
     */
    public function set_main($main) {
        $this->main = $main;
    }
    
    /**
     * Procesar formulario después de insertar o actualizar un post
     */
    public function process_form($manager, $action, $form_id) {
        $this->main->get_logger()->log_debug("=== INICIO PROCESS FORM ===");
        
        // Verificar si tenemos el objeto manager
        if (!$manager || !is_object($manager)) {
            $this->main->get_logger()->log_debug("El manager no es un objeto válido");
            return;
        }
        
        // Obtener los datos del formulario
        $form_data = $manager->data;
        
        // Obtener el ID del post del resultado de la acción
        $post_id = 0;
        if (isset($action) && is_object($action) && method_exists($action, 'get_response_data')) {
            $response_data = $action->get_response_data();
            if (isset($response_data['inserted_post_id'])) {
                $post_id = absint($response_data['inserted_post_id']);
            } elseif (isset($response_data['post_id'])) {
                $post_id = absint($response_data['post_id']);
            }
        }
        
        // Si no encontramos el ID del post en la respuesta, buscarlo en los datos del formulario
        if (!$post_id && isset($form_data['post_id'])) {
            $post_id = absint($form_data['post_id']);
        }
        
        if (!$post_id) {
            $this->main->get_logger()->log_debug("No se pudo encontrar un ID de post válido");
            return;
        }
        
        $this->main->get_logger()->log_debug("ID de post encontrado: $post_id");
        
        // Procesar imágenes
        $this->process_images($form_data, $post_id);
        
        $this->main->get_logger()->log_debug("=== FIN PROCESS FORM ===");
    }
    
    /**
     * Procesar las imágenes del formulario
     */
    private function process_images($form_data, $post_id) {
        // Procesar imagen destacada
        $this->process_featured_image($form_data, $post_id);
        
        // Procesar campos de galería
        $this->process_gallery($form_data, $post_id);
    }
    
    /**
     * Procesar imagen destacada
     */
    private function process_featured_image($form_data, $post_id) {
        // Buscar el campo de imagen destacada basado en la configuración
        $featured_image_field = $this->get_featured_image_field();
        
        if (!$featured_image_field) {
            return;
        }
        
        if (isset($form_data[$featured_image_field]) && !empty($form_data[$featured_image_field])) {
            $featured_id = intval($form_data[$featured_image_field]);
            $this->main->get_logger()->log_debug("Estableciendo imagen destacada: $featured_id");
            set_post_thumbnail($post_id, $featured_id);
            
            // Verificar que se guardó correctamente
            $saved_thumbnail = get_post_thumbnail_id($post_id);
            $this->main->get_logger()->log_debug("Imagen destacada guardada: $saved_thumbnail");
        }
    }
    
    /**
     * Procesar campos de galería
     */
    private function process_gallery($form_data, $post_id) {
        // Obtener los campos de galería desde la configuración
        $gallery_fields = $this->get_gallery_fields();
        
        if (empty($gallery_fields)) {
            return;
        }
        
        foreach ($gallery_fields as $field_name => $meta_key) {
            if (isset($form_data[$field_name]) && !empty($form_data[$field_name])) {
                $gallery_value = $form_data[$field_name];
                $gallery_ids = is_array($gallery_value) ? $gallery_value : explode(',', $gallery_value);
                $gallery_ids = array_map('intval', array_filter($gallery_ids));
                
                $this->main->get_logger()->log_debug("Procesando galería '$field_name' con IDs: " . implode(', ', $gallery_ids) . " en meta_key: $meta_key");
                
                // Guardar galería
                update_post_meta($post_id, $meta_key, $gallery_ids);
                
                // Verificar que se guardó correctamente
                $saved_gallery = get_post_meta($post_id, $meta_key, true);
                $this->main->get_logger()->log_debug("Galería guardada en $meta_key: " . print_r($saved_gallery, true));
            }
        }
    }
    
    /**
     * Obtener el campo de imagen destacada
     */
    private function get_featured_image_field() {
        // Implementar lógica para obtener el campo de imagen destacada desde la configuración
        return 'imagen_destacada'; // Valor predeterminado
    }
    
    /**
     * Obtener los campos de galería y sus meta_keys
     */
    private function get_gallery_fields() {
        // Implementar lógica para obtener los campos de galería desde la configuración
        return [
            'galeria' => 'galeria', // Valor predeterminado
        ];
    }
    
    /**
     * Preparar datos del campo para renderizado
     * 
     * Este método se ejecuta antes de renderizar un campo para proporcionar datos correctos
     * para la edición de entradas existentes.
     */
    public function prepare_field_data_for_render($field_data, $form_id) {
        // Solo procesamos si hay un post_id en la URL
        $post_id = $this->get_editing_post_id();
        if (!$post_id) {
            return $field_data;
        }
        
        // Si es un campo de tipo galería
        if (isset($field_data['type']) && $field_data['type'] === 'media-field' && isset($field_data['name'])) {
            $field_name = $field_data['name'];
            
            // Si es imagen destacada
            if ($this->is_featured_image_field($field_name)) {
                $thumbnail_id = get_post_thumbnail_id($post_id);
                if ($thumbnail_id) {
                    $field_data['default'] = $thumbnail_id;
                }
            } 
            // Si es galería
            elseif ($this->is_gallery_field($field_name)) {
                $meta_key = $this->get_meta_key_for_field($field_name);
                $gallery_ids = get_post_meta($post_id, $meta_key, true);
                
                if (!empty($gallery_ids)) {
                    $field_data['default'] = is_array($gallery_ids) ? $gallery_ids : explode(',', $gallery_ids);
                }
            }
        }
        
        return $field_data;
    }
    
    /**
     * Verificar si un campo es para imagen destacada
     */
    private function is_featured_image_field($field_name) {
        // Implementar lógica para verificar si un campo es para imagen destacada
        return $field_name === 'imagen_destacada';
    }
    
    /**
     * Verificar si un campo es para galería
     */
    private function is_gallery_field($field_name) {
        // Implementar lógica para verificar si un campo es para galería
        return $field_name === 'galeria';
    }
    
    /**
     * Obtener la clave meta para un campo específico
     */
    private function get_meta_key_for_field($field_name) {
        // Implementar lógica para obtener la clave meta para un campo específico
        return $field_name;
    }
    
    /**
     * Obtener el ID del post que se está editando
     */
    private function get_editing_post_id() {
        // Verificar si hay un post_id en la URL
        if (isset($_GET['post_id'])) {
            return absint($_GET['post_id']);
        }
        
        // Verificar si hay un post en la URL
        if (isset($_GET['post'])) {
            return absint($_GET['post']);
        }
        
        return 0;
    }

    /**
     * Guardar imágenes después de insertar un post
     * 
     * @param int $post_id ID del post
     * @param array $form_data Datos del formulario
     */
    public function save_post_images($post_id, $form_data = []) {
        $this->main->get_logger()->log_debug("Hook save_post_images activado con post_id: $post_id");
        
        // Convertir objetos a arrays si es necesario
        if (is_object($form_data) && method_exists($form_data, 'get_form_data')) {
            $form_data = $form_data->get_form_data();
        } elseif (is_object($form_data) && method_exists($form_data, 'to_array')) {
            $form_data = $form_data->to_array();
        } elseif (empty($form_data) || !is_array($form_data)) {
            // Si no hay datos o no son un array, intentar obtenerlos de $_POST
            $form_data = $_POST;
        }
        
        // Procesar imágenes
        $this->process_images($form_data, $post_id);
    }
    
    /**
     * Guardar imágenes después de enviar un formulario
     * 
     * @param array $form_data Datos del formulario
     * @param int $form_id ID del formulario
     */
    public function save_form_images($form_data, $form_id = null) {
        $this->main->get_logger()->log_debug("Hook save_form_images activado");
        
        // Intentar encontrar el ID del post a partir de los datos del formulario
        $post_id = null;
        
        // Buscar en los datos de respuesta estándar
        if (isset($form_data['inserted_post_id'])) {
            $post_id = absint($form_data['inserted_post_id']);
        } elseif (isset($form_data['post_id'])) {
            $post_id = absint($form_data['post_id']);
        }
        
        if (!$post_id) {
            $this->main->get_logger()->log_debug("No se pudo encontrar un ID de post válido en save_form_images");
            return;
        }
        
        $this->main->get_logger()->log_debug("ID de post encontrado en save_form_images: $post_id");
        
        // Procesar imágenes
        $this->process_images($form_data, $post_id);
    }
    
    /**
     * Guardar imágenes después de todas las acciones
     * 
     * @param array $request Datos de la solicitud
     * @param int $form_id ID del formulario
     */
    public function save_after_actions($request, $form_id) {
        $this->main->get_logger()->log_debug("Hook save_after_actions activado");
        
        // Verificar si hay un post ID en la solicitud
        $post_id = null;
        
        if (isset($request['inserted_post_id'])) {
            $post_id = absint($request['inserted_post_id']);
        } elseif (isset($request['post_id'])) {
            $post_id = absint($request['post_id']);
        }
        
        if (!$post_id) {
            $this->main->get_logger()->log_debug("No se pudo encontrar un ID de post válido en save_after_actions");
            return;
        }
        
        $this->main->get_logger()->log_debug("ID de post encontrado en save_after_actions: $post_id");
        
        // Procesar imágenes
        $this->process_images($request, $post_id);
    }
    
    /**
     * Verificar metadatos después de guardarlos
     * 
     * @param int $meta_id ID del metadato
     * @param int $post_id ID del post
     * @param string $meta_key Clave del metadato
     * @param mixed $meta_value Valor del metadato
     */
    public function verify_post_meta($meta_id, $post_id, $meta_key, $meta_value) {
        // Verificar si el meta_key es uno de nuestros campos
        $gallery_fields = $this->get_gallery_fields();
        $featured_field = $this->get_featured_image_field();
        
        $is_our_field = false;
        if ($meta_key === '_thumbnail_id' || $meta_key === $featured_field) {
            $is_our_field = true;
            $this->main->get_logger()->log_debug("Verificando meta de imagen destacada: $meta_key = $meta_value");
        } elseif (in_array($meta_key, $gallery_fields)) {
            $is_our_field = true;
            $this->main->get_logger()->log_debug("Verificando meta de galería: $meta_key = " . print_r($meta_value, true));
        }
        
        if ($is_our_field) {
            // Verificar que los IDs de medios existan y pertenezcan al usuario actual
            if (is_array($meta_value)) {
                foreach ($meta_value as $attachment_id) {
                    $this->verify_attachment_id($attachment_id, $post_id);
                }
            } else {
                $this->verify_attachment_id($meta_value, $post_id);
            }
        }
    }
    
    /**
     * Verificar que un ID de adjunto sea válido
     * 
     * @param int $attachment_id ID del adjunto
     * @param int $post_id ID del post
     */
    private function verify_attachment_id($attachment_id, $post_id) {
        $attachment_id = absint($attachment_id);
        if ($attachment_id <= 0) {
            return;
        }
        
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            $this->main->get_logger()->log_debug("Advertencia: ID de adjunto inválido $attachment_id para el post $post_id");
            return;
        }
        
        // Verificar propietario (opcional)
        if (current_user_can('manage_options')) {
            return; // Los administradores pueden usar cualquier adjunto
        }
        
        $current_user_id = get_current_user_id();
        if ($attachment->post_author != $current_user_id) {
            $this->main->get_logger()->log_debug("Advertencia: El adjunto $attachment_id no pertenece al usuario actual $current_user_id");
        }
    }
    
    /**
     * Guardar imágenes de emergencia al final de la ejecución
     */
    public function emergency_save_images() {
        // Este método se ejecuta en el hook 'shutdown' como último recurso
        // para asegurarse de que las imágenes se guarden
        
        // No implementamos lógica adicional por ahora, ya que los otros hooks deberían manejar los casos normales
    }
    
    /**
     * Depurar datos del formulario
     * 
     * @param array $form_data Datos del formulario
     * @return array Datos del formulario sin modificar
     */
    public function debug_form_data($form_data) {
        if (defined('JETFORM_MEDIA_GALLERY_DEBUG') && JETFORM_MEDIA_GALLERY_DEBUG) {
            $this->main->get_logger()->log_debug("Datos del formulario: " . print_r($form_data, true));
        }
        return $form_data;
    }
    
    /**
     * Guardar imágenes al guardar directamente un post
     * 
     * @param int $post_id ID del post
     * @param WP_Post $post Objeto post
     * @param bool $update Si es una actualización
     */
    public function save_on_direct_post_save($post_id, $post, $update) {
        $this->main->get_logger()->log_debug("Hook save_on_direct_post_save activado para post $post_id");
        
        // Evitar guardado recursivo
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verificar nonce para formularios estándar de WordPress
        if (isset($_POST['_wpnonce']) && !wp_verify_nonce($_POST['_wpnonce'], 'update-post_' . $post_id)) {
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Procesar imágenes usando los datos de $_POST
        $this->process_images($_POST, $post_id);
    }
    
    /**
     * Procesar formulario después del envío
     * 
     * @param array $data Datos del formulario
     * @param object $handler Manejador de formularios
     * @param int $form_id ID del formulario
     */
    public function process_form_submission($data, $handler, $form_id) {
        $this->main->get_logger()->log_debug("Hook process_form_submission activado");
        
        // Buscar el ID del post en los datos procesados
        $post_id = null;
        
        if (isset($data['inserted_post_id'])) {
            $post_id = absint($data['inserted_post_id']);
        } elseif (isset($data['post_id'])) {
            $post_id = absint($data['post_id']);
        }
        
        if (!$post_id) {
            $this->main->get_logger()->log_debug("No se pudo encontrar un ID de post válido en process_form_submission");
            return;
        }
        
        $this->main->get_logger()->log_debug("ID de post encontrado en process_form_submission: $post_id");
        
        // Procesar imágenes
        $this->process_images($data, $post_id);
    }
} 