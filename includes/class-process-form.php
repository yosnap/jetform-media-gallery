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
        
        // Verificar que el post existe
        $post = get_post($post_id);
        if (!$post) {
            $this->main->log_debug("Error: El post con ID $post_id no existe en process_form_submission");
            return;
        }
        
        $this->main->log_debug("Post encontrado: " . $post->post_title);
        
        // Para procesamiento simple, delegamos al método save_images_to_post
        $this->save_images_to_post($post_id, $form_data);
        
        $this->main->log_debug("=== FIN PROCESS FORM SUBMISSION ===");
    }
    
    /**
     * Obtener la clave meta para un campo específico
     */
    private function get_meta_key_for_field($field_name) {
        $settings = $this->main->get_settings();
        $fields = isset($settings['image_fields']) ? $settings['image_fields'] : [];
        
        foreach ($fields as $field) {
            if ($field['name'] === $field_name) {
                return $field['meta_key'];
            }
        }
        
        // Si no se encuentra, usar el nombre del campo como clave meta
        return $field_name;
    }
    
    /**
     * Encontrar el ID del post
     */
    public function find_post_id($handler, $actions = null, $form_data = []) {
        $post_id = null;
        
        $this->main->log_debug("=== INICIO DE BÚSQUEDA DE POST ID ===");
        
        // 0. PRIORIDAD MÁXIMA: Verificar en la URL (_post_id)
        if (isset($_GET['_post_id']) && !empty($_GET['_post_id'])) {
            $url_post_id = absint($_GET['_post_id']);
            $post = get_post($url_post_id);
            
            if ($post) {
                $this->main->log_debug("Post ID encontrado en URL (_post_id): $url_post_id");
                $this->main->log_debug("Post encontrado: " . $post->post_title);
                return $url_post_id;
            } else {
                $this->main->log_debug("Post ID en URL no es válido: $url_post_id");
            }
        }
        
        // 0.1. Verificar la variable global
        if (isset($GLOBALS['jetform_media_gallery_edit_post_id'])) {
            $global_post_id = $GLOBALS['jetform_media_gallery_edit_post_id'];
            $post = get_post($global_post_id);
            
            if ($post) {
                $this->main->log_debug("Post ID encontrado en variable global: $global_post_id");
                $this->main->log_debug("Post encontrado: " . $post->post_title);
                return $global_post_id;
            }
        }
        
        // 0.2. Verificar _post_id en POST (campo oculto que añadimos)
        if (isset($_POST['_post_id']) && !empty($_POST['_post_id'])) {
            $form_post_id = absint($_POST['_post_id']);
            $post = get_post($form_post_id);
            
            if ($post) {
                $this->main->log_debug("Post ID encontrado en POST (_post_id): $form_post_id");
                $this->main->log_debug("Post encontrado: " . $post->post_title);
                return $form_post_id;
            }
        }
        
        // 1. Intentar extraer post_id si $handler es un objeto
        if (is_object($handler)) {
            $post_id = $this->extract_post_id_from_objects($handler);
            
            if ($post_id) {
                $this->main->log_debug("Post ID extraído de objeto handler: $post_id");
                
                // Verificar que el post existe
                $post = get_post($post_id);
                if (!$post) {
                    $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                    $post_id = null;
                } else {
                    $this->main->log_debug("Post encontrado por ID de objeto: " . $post->post_title);
                    return $post_id;
                }
            }
        }
        
        // 0.1. Verificar si JetFormBuilder está activo y obtener el ID del handler
        if ($this->is_jetformbuilder_active() && !$post_id) {
            $post_id = $this->get_current_post_id_from_handler();
            
            if ($post_id) {
                $this->main->log_debug("Post ID obtenido del handler actual: $post_id");
                
                // Verificar que el post existe
                $post = get_post($post_id);
                if (!$post) {
                    $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                    $post_id = null;
                } else {
                    $this->main->log_debug("Post encontrado por ID del handler: " . $post->post_title);
                    return $post_id;
                }
            }
        }
        
        // 1. Buscar en los datos del formulario (formato estándar)
        if (isset($form_data['post_id'])) {
            $post_id = absint($form_data['post_id']);
            $this->main->log_debug("Post ID encontrado en form_data[post_id]: $post_id");
            
            // Verificar que el post existe
            $post = get_post($post_id);
            if (!$post) {
                $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                $post_id = null;
            } else {
                $this->main->log_debug("Post encontrado por form_data[post_id]: " . $post->post_title);
                return $post_id;
            }
        }
        
        // 1.1 Buscar en _post_id (formato común en JetFormBuilder para edición)
        if (!$post_id && isset($form_data['_post_id'])) {
            $post_id = absint($form_data['_post_id']);
            $this->main->log_debug("Post ID encontrado en form_data[_post_id]: $post_id");
            
            // Verificar que el post existe
            $post = get_post($post_id);
            if (!$post) {
                $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                $post_id = null;
            } else {
                return $post_id;
            }
        }
        
        // 2. Buscar en las acciones de JetFormBuilder
        if (!$post_id && is_array($actions)) {
            foreach ($actions as $action) {
                if (isset($action['type']) && $action['type'] === 'insert_post' && isset($action['post_id'])) {
                    $post_id = absint($action['post_id']);
                    $this->main->log_debug("Post ID encontrado en acciones: $post_id");
                    
                    // Verificar que el post existe
                    $post = get_post($post_id);
                    if (!$post) {
                        $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                        $post_id = null;
                        continue;
                    }
                    
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
                
                // Verificar que el post existe
                $post = get_post($post_id);
                if (!$post) {
                    $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                    $post_id = null;
                }
            } elseif (isset($response_data['post_id'])) {
                $post_id = absint($response_data['post_id']);
                $this->main->log_debug("Post ID encontrado en response_data[post_id]: $post_id");
                
                // Verificar que el post existe
                $post = get_post($post_id);
                if (!$post) {
                    $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                    $post_id = null;
                }
            }
        }
        
        // 4. Buscar en las acciones del handler (casos de edición)
        if (!$post_id && isset($handler->action_handler) && isset($handler->action_handler->actions)) {
            $this->main->log_debug("Buscando post ID en las acciones del handler (modo edición)");
            $actions = $handler->action_handler->actions;
            
            foreach ($actions as $action) {
                // Verificar si el action es un objeto y tiene la propiedad settings
                if (is_object($action) && isset($action->settings)) {
                    if (isset($action->settings['post_id']) && !empty($action->settings['post_id'])) {
                        $post_id = absint($action->settings['post_id']);
                        $this->main->log_debug("Post ID encontrado en action->settings[post_id]: $post_id");
                        
                        // Verificar que el post existe
                        $post = get_post($post_id);
                        if (!$post) {
                            $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                            $post_id = null;
                            continue;
                        }
                        
                        break;
                    }
                    
                    // También verificar en fields_map, que es donde podría estar cuando editamos
                    if (isset($action->settings['fields_map']) && is_array($action->settings['fields_map'])) {
                        foreach ($action->settings['fields_map'] as $field => $value) {
                            if ($field === 'ID' && !empty($value)) {
                                $post_id = absint($value);
                                $this->main->log_debug("Post ID encontrado en action->settings[fields_map][ID]: $post_id");
                                
                                // Verificar que el post existe
                                $post = get_post($post_id);
                                if (!$post) {
                                    $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                                    $post_id = null;
                                    continue;
                                }
                                
                                break 2;
                            }
                        }
                    }
                }
                
                // Verificar en el modificador (para JetFormBuilder v2+)
                if (is_object($action) && method_exists($action, 'get_modifier') && $action->get_id() === 'insert_post') {
                    $modifier = $action->get_modifier();
                    if ($modifier && method_exists($modifier, 'get') && method_exists($modifier->get('ID'), 'get_value')) {
                        $post_id = absint($modifier->get('ID')->get_value());
                        $this->main->log_debug("Post ID encontrado en modificador: $post_id");
                        
                        // Verificar que el post existe
                        $post = get_post($post_id);
                        if (!$post) {
                            $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                            $post_id = null;
                            continue;
                        }
                        
                        break;
                    }
                }
            }
        }
        
        // 5. Verificar en _POST directamente por si estamos en un formulario estándar
        if (!$post_id && isset($_POST['ID']) && !empty($_POST['ID'])) {
            $post_id = absint($_POST['ID']);
            $this->main->log_debug("Post ID encontrado en _POST[ID]: $post_id");
            
            // Verificar que el post existe
            $post = get_post($post_id);
            if (!$post) {
                $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                $post_id = null;
            }
        }
        
        // También buscar en _POST['_post_id']
        if (!$post_id && isset($_POST['_post_id']) && !empty($_POST['_post_id'])) {
            $post_id = absint($_POST['_post_id']);
            $this->main->log_debug("Post ID encontrado en _POST[_post_id]: $post_id");
            
            // Verificar que el post existe
            $post = get_post($post_id);
            if (!$post) {
                $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                $post_id = null;
            }
        }
        
        // 6. Buscar el último post del tipo correcto si todo lo demás falla
        if (!$post_id) {
            $post_id = $this->find_last_post();
            if ($post_id) {
                $this->main->log_debug("Post ID encontrado buscando el último post: $post_id");
                
                // Verificar que el post existe
                $post = get_post($post_id);
                if (!$post) {
                    $this->main->log_debug("El post ID $post_id no existe en la base de datos, continuando búsqueda...");
                    $post_id = null;
                }
            }
        }
        
        // Verificar que el post existe
        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                $this->main->log_debug("Verificado que el post $post_id existe: " . $post->post_title);
                return $post_id;
            } else {
                $this->main->log_debug("El post ID $post_id no existe en la base de datos");
            }
        }
        
        return null;
    }
    
    /**
     * Guardar imágenes después de insertar post
     */
    public function save_post_images($post_id, $form_data = []) {
        $this->main->log_debug("Hook save_post_images activado con post_id: $post_id");
        
        // Si el post_id es un objeto (puede ocurrir con los hooks nuevos), intentar extraer el ID
        if (is_object($post_id) && method_exists($post_id, 'get_id')) {
            $this->main->log_debug("Post ID es un objeto con método get_id()");
            if (method_exists($post_id, 'get_post_id')) {
                $post_id = $post_id->get_post_id();
                $this->main->log_debug("ID extraído con get_post_id(): $post_id");
            } elseif (property_exists($post_id, 'post_id')) {
                $post_id = $post_id->post_id;
                $this->main->log_debug("ID extraído de propiedad post_id: $post_id");
            } else {
                // Intentar extraer de JetFormBuilder 
                if (property_exists($post_id, 'action') && property_exists($post_id->action, 'inserted_id')) {
                    $post_id = $post_id->action->inserted_id;
                    $this->main->log_debug("ID extraído de action->inserted_id: $post_id");
                } else {
                    $this->main->log_debug("No se pudo extraer el ID del objeto");
                    // Intentemos obtener la acción actual
                    if (function_exists('jet_fb_action_handler') && method_exists(jet_fb_action_handler(), 'get_current_action')) {
                        $current_action = jet_fb_action_handler()->get_current_action();
                        if ($current_action && property_exists($current_action, 'inserted_id')) {
                            $post_id = $current_action->inserted_id;
                            $this->main->log_debug("ID extraído de current_action->inserted_id: $post_id");
                        }
                    }
                }
            }
        }
        
        // Si form_data es un objeto (JetFormBuilder action handler), extrae los datos
        if (is_object($form_data)) {
            $this->main->log_debug("form_data es un objeto");
            if (method_exists($form_data, 'get_form_data')) {
                $form_data = $form_data->get_form_data();
                $this->main->log_debug("Datos extraídos con get_form_data()");
            } elseif (method_exists($form_data, 'to_array')) {
                $form_data = $form_data->to_array();
                $this->main->log_debug("Datos extraídos con to_array()");
            } elseif (function_exists('jet_fb_action_handler') && method_exists(jet_fb_action_handler(), 'request_data')) {
                // Obtener los datos del request actual
                $form_data = jet_fb_action_handler()->request_data;
                $this->main->log_debug("Datos extraídos del request_data del action_handler");
            } else {
                // Si no podemos extraer los datos, usamos $_POST
                $form_data = $_POST;
                $this->main->log_debug("No se pudieron extraer datos, usando $_POST");
            }
        } elseif (empty($form_data) || !is_array($form_data)) {
            // Si no hay datos o no son un array, intentar obtenerlos de $_POST
            $form_data = $_POST;
            $this->main->log_debug("Usando $_POST como fuente de datos");
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
        // Solo procesar si estamos en un envío de formulario y hay datos de media gallery
        if (empty($_POST) || (!isset($_POST['has_media_gallery']) && 
                              !isset($_POST['imagen_destacada']) && 
                              !isset($_POST['galeria'])
                             )) {
            return;
        }
        
        $this->main->log_debug("=== MÉTODO DE EMERGENCIA ACTIVADO ===");
        $this->main->log_debug("Datos POST: " . print_r($_POST, true));
        
        // Intentar encontrar el ID del post
        $post_id = null;
        
        // 1. ALTA PRIORIDAD: Verificar ID en la URL
        if (isset($_GET['_post_id']) && !empty($_GET['_post_id'])) {
            $url_post_id = absint($_GET['_post_id']);
            $post = get_post($url_post_id);
            
            if ($post) {
                $post_id = $url_post_id;
                $this->main->log_debug("Post ID encontrado en URL (_post_id): $post_id");
                $this->main->log_debug("Post encontrado: " . $post->post_title);
            } else {
                $this->main->log_debug("Post ID en URL no es válido: $url_post_id");
            }
        }
        
        // 2. Verificar si tenemos el post_id en la variable global
        if (!$post_id && isset($GLOBALS['jetform_media_gallery_edit_post_id'])) {
            $global_post_id = $GLOBALS['jetform_media_gallery_edit_post_id'];
            $post = get_post($global_post_id);
            
            if ($post) {
                $post_id = $global_post_id;
                $this->main->log_debug("Post ID encontrado en globals: $post_id");
                $this->main->log_debug("Post encontrado: " . $post->post_title);
            }
        }
        
        // 3. Verificar _post_id en POST
        if (!$post_id && isset($_POST['_post_id']) && !empty($_POST['_post_id'])) {
            $form_post_id = absint($_POST['_post_id']);
            $post = get_post($form_post_id);
            
            if ($post) {
                $post_id = $form_post_id;
                $this->main->log_debug("Post ID encontrado en _post_id: $post_id");
                $this->main->log_debug("Post encontrado: " . $post->post_title);
            }
        }
        
        // 4. Verificar post_id en POST
        if (!$post_id && isset($_POST['post_id']) && !empty($_POST['post_id'])) {
            $form_post_id = absint($_POST['post_id']);
            $post = get_post($form_post_id);
            
            if ($post) {
                $post_id = $form_post_id;
                $this->main->log_debug("Post ID encontrado en post_id: $post_id");
                $this->main->log_debug("Post encontrado: " . $post->post_title);
            } else {
                $this->main->log_debug("Post ID en post_id no es válido: $form_post_id");
            }
        }
        
        // 5. Último recurso: buscar el último post
        if (!$post_id) {
            $post_id = $this->find_last_post();
            if ($post_id) {
                $post = get_post($post_id);
                if ($post) {
                    $this->main->log_debug("Post ID encontrado buscando el último post: $post_id");
                    $this->main->log_debug("Post encontrado: " . $post->post_title);
                }
            }
        }
        
        if (!$post_id) {
            $this->main->log_debug("No se pudo encontrar un ID de post válido en emergency_save_images");
            return;
        }
        
        // En este punto ya hemos verificado que el post existe
        $this->save_images_to_post($post_id, $_POST);
    }
    
    /**
     * Guardar imágenes en un post específico
     */
    public function save_images_to_post($post_id, $form_data) {
        $this->main->log_debug("=== INICIO DE GUARDADO DE IMÁGENES ===");
        $this->main->log_debug("Post ID: $post_id");
        
        if (!$post_id) {
            $this->main->log_debug("Error: Post ID no válido");
            return false;
        }
        
        // Verificar que el post existe
        $post = get_post($post_id);
        if (!$post) {
            $this->main->log_debug("Error: El post con ID $post_id no existe");
            return false;
        }
        
        $this->main->log_debug("Post encontrado: " . $post->post_title);
        $this->main->log_debug("Datos del formulario completos: " . print_r($form_data, true));
        
        // Variables para almacenar las configuraciones de campo encontradas
        $gallery_field_config = null;
        $featured_field_config = null;
        
        // Obtener configuraciones de campos
        $settings = $this->main->get_settings();
        $fields = isset($settings['image_fields']) ? $settings['image_fields'] : [];
        
        // Primero, busquemos las configuraciones para entender qué campos estamos procesando
        foreach ($fields as $field) {
            $field_name = isset($field['name']) ? $field['name'] : '';
            $meta_key = isset($field['meta_key']) ? $field['meta_key'] : '';
            
            if (!empty($field_name) && !empty($meta_key)) {
                if ($field['type'] === 'single') {
                    $featured_field_config = $field;
                } else {
                    $gallery_field_config = $field;
                }
            }
        }
        
        $this->main->log_debug("Configuración de campo de galería: " . print_r($gallery_field_config, true));
        $this->main->log_debug("Configuración de campo de imagen destacada: " . print_r($featured_field_config, true));
        
        // Asegurarnos de que tenemos los datos de imágenes
        $featured_image_found = false;
        $gallery_found = false;
        $gallery_field_name = '';
        $featured_field_name = '';
        
        // Verificar campos de imagen destacada que comienzan con 'featured_' o 'imagen_'
        foreach ($form_data as $key => $value) {
            if ((preg_match('/^(featured_|imagen_)/', $key) || $key === 'imagen_destacada') && !empty($value)) {
                $form_data['media_gallery_featured'] = $value;
                $featured_field_name = $key;
                $featured_image_found = true;
                $this->main->log_debug("Campo de imagen destacada detectado: $key con valor: $value");
                break;
            }
            
            // Si tenemos una configuración de campo de imagen destacada, verificar ese nombre específico
            if ($featured_field_config && $key === $featured_field_config['name'] && !empty($value)) {
                $form_data['media_gallery_featured'] = $value;
                $featured_field_name = $key;
                $featured_image_found = true;
                $this->main->log_debug("Campo de imagen destacada configurado encontrado: $key con valor: $value");
                break;
            }
        }
        
        // Verificar campos de galería
        foreach ($form_data as $key => $value) {
            if (preg_match('/^(gallery|galeria)/', $key)) {
                $this->main->log_debug("Campo de galería detectado: $key con valor: " . (is_array($value) ? implode(',', $value) : $value));
                $form_data['media_gallery_gallery'] = $value;
                $gallery_found = true;
                $gallery_field_name = $key;
                break;
            }
            
            // Si tenemos una configuración de campo de galería, verificar ese nombre específico
            if ($gallery_field_config && $key === $gallery_field_config['name']) {
                $this->main->log_debug("Campo de galería configurado encontrado: $key con valor: " . (is_array($value) ? implode(',', $value) : $value));
                $form_data['media_gallery_gallery'] = $value;
                $gallery_found = true;
                $gallery_field_name = $key;
                break;
            }
        }
        
        // Verificar si hay datos de media_gallery
        if (isset($_POST['media_gallery_featured'])) {
            $form_data['media_gallery_featured'] = $_POST['media_gallery_featured'];
            $featured_image_found = true;
        }
        if (isset($_POST['media_gallery_gallery'])) {
            $form_data['media_gallery_gallery'] = $_POST['media_gallery_gallery'];
            $gallery_found = true;
        }
        
        // Verificar campos configurados
        $gallery_meta_key = $this->get_meta_key_for_field($gallery_field_name);
        
        foreach ($fields as $field) {
            $field_name = isset($field['name']) ? $field['name'] : '';
            $meta_key = isset($field['meta_key']) ? $field['meta_key'] : '';
            
            if (!empty($field_name) && !empty($meta_key) && isset($form_data[$field_name])) {
                $value = $form_data[$field_name];
                
                if ($field['type'] === 'single') {
                    // Imagen destacada
                    if ($meta_key === '_thumbnail_id') {
                        $form_data['media_gallery_featured'] = $value;
                        $featured_image_found = true;
                    } else {
                        update_post_meta($post_id, $meta_key, $value);
                        $this->main->log_debug("Guardado campo single personalizado: $meta_key con valor: $value");
                    }
                } else {
                    // Galería
                    $form_data['media_gallery_gallery'] = $value;
                    $gallery_meta_key = $meta_key; // Guardar la clave meta configurada
                    $gallery_found = true;
                    $this->main->log_debug("Campo de galería configurado: $field_name -> $meta_key con valor: " . (is_array($value) ? implode(',', $value) : $value));
                }
            } else if (!empty($field_name) && !empty($meta_key) && $field['type'] !== 'single' && $gallery_found && empty($form_data['media_gallery_gallery'])) {
                // Si el campo existe en la configuración pero no en los datos del formulario y estamos en modo de edición
                // significa que el usuario limpió la galería
                $this->main->log_debug("Campo de galería configurado pero vacío: $field_name -> $meta_key, se eliminará");
                delete_post_meta($post_id, $meta_key);
                if ($gallery_field_name === $field_name) {
                    $gallery_meta_key = $meta_key;
                }
            }
            
            // Si encontramos un campo que coincide con el nombre de campo de galería
            if ($field['type'] !== 'single' && $gallery_field_name === $field_name) {
                $gallery_meta_key = $meta_key;
                $this->main->log_debug("Clave meta para galería ($gallery_field_name): $gallery_meta_key");
            }
        }
        
        // Si estamos editando y el campo de galería está vacío pero existe en el formulario,
        // significa que el usuario eliminó todas las imágenes
        if ($gallery_found && empty($form_data['media_gallery_gallery']) && !empty($gallery_meta_key)) {
            $this->main->log_debug("Campo de galería vacío detectado, se eliminarán todas las imágenes de: $gallery_meta_key");
            delete_post_meta($post_id, $gallery_meta_key);
        }
        
        // Verificar permisos
        if (!current_user_can('upload_files')) {
            $this->main->log_debug("Error: El usuario no tiene permisos para subir archivos");
            return false;
        }
        
        $success = true;
        
        // Procesar imagen destacada
        if ($featured_image_found && !empty($form_data['media_gallery_featured'])) {
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
        if ($gallery_found && !empty($form_data['media_gallery_gallery']) && !empty($gallery_meta_key)) {
            $gallery_value = $form_data['media_gallery_gallery'];
            
            // Asegurarnos de que gallery_value sea un array de IDs
            if (is_string($gallery_value)) {
                // Si es una cadena separada por comas
                if (strpos($gallery_value, ',') !== false) {
                    $gallery_ids = explode(',', $gallery_value);
                } 
                // Si es un string en formato JSON
                else if (strpos($gallery_value, '[') === 0) {
                    $decoded = json_decode($gallery_value, true);
                    $gallery_ids = is_array($decoded) ? $decoded : [$gallery_value];
                } 
                // Si es un solo número
                else if (is_numeric($gallery_value)) {
                    $gallery_ids = [$gallery_value];
                } 
                // Cualquier otro caso
                else {
                    $gallery_ids = [$gallery_value];
                }
            } else if (is_array($gallery_value)) {
                $gallery_ids = $gallery_value;
            } else {
                $this->main->log_debug("Valor de galería tiene un formato no reconocido: " . gettype($gallery_value));
                $gallery_ids = [];
            }
            
            // Filtrar y convertir a enteros
            $gallery_ids = array_map('intval', array_filter($gallery_ids));
            
            if (!empty($gallery_ids)) {
                $this->main->log_debug("Intentando guardar galería con IDs: " . implode(', ', $gallery_ids) . " en meta_key: $gallery_meta_key");
                
                // Limpiar meta existente
                delete_post_meta($post_id, $gallery_meta_key);
                
                // Guardar nueva galería
                $result = add_post_meta($post_id, $gallery_meta_key, $gallery_ids, true);
                
                if (!$result) {
                    $result = update_post_meta($post_id, $gallery_meta_key, $gallery_ids);
                    $this->main->log_debug("Actualizada galería existente en meta_key: $gallery_meta_key");
                } else {
                    $this->main->log_debug("Añadida nueva galería en meta_key: $gallery_meta_key");
                }
                
                $this->main->log_debug("Resultado guardado galería: " . ($result ? "éxito" : "fallo"));
            } else {
                $this->main->log_debug("Advertencia: Array de galería vacío después de filtrar");
            }
        } else if ($gallery_found && !empty($gallery_meta_key)) {
            // Si el campo existe en el formulario pero está vacío, debemos eliminar la galería
            $this->main->log_debug("Galería vacía, se eliminará el meta '$gallery_meta_key'");
            delete_post_meta($post_id, $gallery_meta_key);
        }
        
        // Verificación final
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $gallery = !empty($gallery_meta_key) ? get_post_meta($post_id, $gallery_meta_key, true) : [];
        
        $this->main->log_debug("=== VERIFICACIÓN FINAL ===");
        $this->main->log_debug("Thumbnail ID guardado: " . ($thumbnail_id ? $thumbnail_id : "no encontrado"));
        $this->main->log_debug("Meta key de galería: $gallery_meta_key");
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
        // Obtener configuraciones de campos
        $settings = $this->main->get_settings();
        $fields = isset($settings['image_fields']) ? $settings['image_fields'] : [];
        
        $is_gallery_meta = false;
        
        // Verificar si es una clave meta de galería
        foreach ($fields as $field) {
            if ($field['type'] !== 'single' && $field['meta_key'] === $meta_key) {
                $is_gallery_meta = true;
                break;
            }
        }
        
        if ($is_gallery_meta) {
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
    
    /**
     * Extraer post_id de objetos de JetFormBuilder
     */
    private function extract_post_id_from_objects($object) {
        $post_id = null;
        
        if (!is_object($object)) {
            return null;
        }
        
        $this->main->log_debug("Intentando extraer post_id de un objeto: " . get_class($object));
        
        // Extraer de un objeto action de JetFormBuilder
        if (method_exists($object, 'get_id') && method_exists($object, 'get_post_id')) {
            $post_id = $object->get_post_id();
            $this->main->log_debug("Post ID extraído con get_post_id(): $post_id");
            return $post_id;
        }
        
        // Extraer de propiedades comunes
        $properties_to_check = ['post_id', 'inserted_id', 'ID'];
        foreach ($properties_to_check as $prop) {
            if (property_exists($object, $prop) && !empty($object->$prop)) {
                $post_id = $object->$prop;
                $this->main->log_debug("Post ID extraído de propiedad $prop: $post_id");
                return $post_id;
            }
        }
        
        // Extraer de objetos anidados
        $nested_properties = ['action', 'current_action', 'modifier'];
        foreach ($nested_properties as $prop) {
            if (property_exists($object, $prop) && is_object($object->$prop)) {
                $nested_id = $this->extract_post_id_from_objects($object->$prop);
                if ($nested_id) {
                    return $nested_id;
                }
            }
        }
        
        // Si sigue siendo null, verificar métodos específicos de JetFormBuilder
        if (method_exists($object, 'get_inserted_post_id')) {
            $post_id = $object->get_inserted_post_id();
            $this->main->log_debug("Post ID extraído con get_inserted_post_id(): $post_id");
            return $post_id;
        }
        
        return null;
    }
    
    /**
     * Verificar si estamos en JetFormBuilder
     */
    private function is_jetformbuilder_active() {
        return function_exists('jet_fb_action_handler') || function_exists('jet_form_builder');
    }
    
    /**
     * Obtener el ID de post actual del handler de JetFormBuilder
     */
    private function get_current_post_id_from_handler() {
        if (!function_exists('jet_fb_action_handler')) {
            return null;
        }
        
        $handler = jet_fb_action_handler();
        
        if (!$handler) {
            return null;
        }
        
        // Verificar response_data
        if (method_exists($handler, 'get_response_data')) {
            $response_data = $handler->get_response_data();
            
            if (isset($response_data['inserted_post_id'])) {
                return absint($response_data['inserted_post_id']);
            }
            
            if (isset($response_data['post_id'])) {
                return absint($response_data['post_id']);
            }
        }
        
        // Verificar acciones
        if (method_exists($handler, 'get_current_action')) {
            $current_action = $handler->get_current_action();
            
            if ($current_action) {
                $action_id = $this->extract_post_id_from_objects($current_action);
                
                if ($action_id) {
                    return $action_id;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Interceptar envío de formulario JetEngine
     */
    public function intercept_jetengine_form() {
        $this->main->log_debug("=== INTERCEPTANDO FORMULARIO JETENGINE ===");
        
        // Verificar si tenemos datos de formulario
        if (empty($_POST)) {
            $this->main->log_debug("No hay datos POST disponibles");
            return;
        }
        
        $this->main->log_debug("Datos del formulario: " . print_r($_POST, true));
        
        // Intentar encontrar el ID del post
        $post_id = null;
        
        // Buscar en los datos del formulario
        if (isset($_POST['_post_id']) && !empty($_POST['_post_id'])) {
            $post_id = absint($_POST['_post_id']);
            $this->main->log_debug("Post ID encontrado en _post_id: $post_id");
        } elseif (isset($_POST['post_id']) && !empty($_POST['post_id'])) {
            $post_id = absint($_POST['post_id']);
            $this->main->log_debug("Post ID encontrado en post_id: $post_id");
        } elseif (isset($GLOBALS['jetform_media_gallery_edit_post_id'])) {
            $post_id = $GLOBALS['jetform_media_gallery_edit_post_id'];
            $this->main->log_debug("Post ID encontrado en globals: $post_id");
        }
        
        // Verificar si el post existe
        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                $this->main->log_debug("Post encontrado: " . $post->post_title);
                $this->save_images_to_post($post_id, $_POST);
            } else {
                $this->main->log_debug("Post no encontrado con ID: $post_id");
            }
        } else {
            $this->main->log_debug("No se encontró un ID de post en los datos del formulario");
        }
    }
} 