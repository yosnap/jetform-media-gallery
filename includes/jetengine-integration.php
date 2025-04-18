<?php // Integración con JetEngine

/**
 * Clase de integración con JetEngine
 *
 * @package JetForm_Media_Gallery
 * @since 1.0.4
 */

// No ejecutar directamente
if (!defined('ABSPATH')) {
    die('No direct script access allowed');
}

class JetForm_Media_Gallery_JetEngine_Integration {
    
    /**
     * Instancia de la clase principal
     */
    private $main;
    
    /**
     * Constructor
     */
    public function __construct($main) {
        $this->main = $main;
        $this->setup_hooks();
    }
    
    /**
     * Configurar hooks
     */
    private function setup_hooks() {
        // Hooks para JetEngine
        add_action('jet-engine/forms/booking/notification/success', [$this, 'process_jetengine_form'], 10, 3);
        add_action('jet-engine/forms/booking/notification/insert-post/success', [$this, 'process_jetengine_post_insert'], 10, 4);
        
        // Filtros para modificar datos de JetEngine
        add_filter('jet-engine/forms/booking/notification/fields-after-save', [$this, 'modify_saved_fields'], 10, 3);
    }
    
    /**
     * Procesar formulario de JetEngine
     */
    public function process_jetengine_form($manager, $notifications, $data) {
        $this->main->log_debug("=== PROCESANDO FORMULARIO JETENGINE ===");
        $this->main->log_debug("Datos de formulario: " . print_r($data, true));
        
        // Intentar encontrar el ID del post
        $post_id = null;
        
        // Buscar en los datos de notificación
        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                if (!empty($notification['post_id'])) {
                    $post_id = absint($notification['post_id']);
                    $this->main->log_debug("Post ID encontrado en notificación: $post_id");
                    break;
                }
            }
        }
        
        // Verificar si tenemos un post_id en los datos
        if (!$post_id && !empty($data['post_id'])) {
            $post_id = absint($data['post_id']);
            $this->main->log_debug("Post ID encontrado en datos: $post_id");
        }
        
        // Si tenemos un post_id, procesar las imágenes
        if ($post_id) {
            $this->main->get_process()->save_images_to_post($post_id, $data);
        } else {
            $this->main->log_debug("No se encontró un post_id en el formulario JetEngine");
        }
    }
    
    /**
     * Procesar inserción de post de JetEngine
     */
    public function process_jetengine_post_insert($inserted_id, $manager, $notifications, $settings) {
        $this->main->log_debug("=== PROCESANDO INSERCIÓN DE POST JETENGINE ===");
        $this->main->log_debug("Post ID insertado: $inserted_id");
        
        if ($inserted_id) {
            // Obtener datos del formulario
            $data = $manager->data;
            $this->main->log_debug("Datos de formulario: " . print_r($data, true));
            
            // Procesar imágenes para el post
            $this->main->get_process()->save_images_to_post($inserted_id, $data);
        }
    }
    
    /**
     * Modificar campos guardados
     */
    public function modify_saved_fields($fields, $post_id, $settings) {
        $this->main->log_debug("=== MODIFICANDO CAMPOS GUARDADOS JETENGINE ===");
        $this->main->log_debug("Post ID: $post_id");
        $this->main->log_debug("Campos antes de modificar: " . print_r($fields, true));
        
        // Campos de media gallery que debemos manejar especialmente
        $media_gallery_fields = ['imagen_destacada', 'galeria'];
        
        foreach ($media_gallery_fields as $field_name) {
            if (isset($fields[$field_name])) {
                // Asegurarnos de que JetEngine no procese estos campos
                // ya que los manejamos nosotros
                unset($fields[$field_name]);
            }
        }
        
        $this->main->log_debug("Campos después de modificar: " . print_r($fields, true));
        
        return $fields;
    }
}
