<?php
/**
 * Plugin Name: JetFormBuilder Media Gallery Field
 * Description: Agrega un campo de galería de medios para JetFormBuilder que permite seleccionar imagen destacada y galería para el CPT "singlecar"
 * Version: 1.1.0
 * Author: Sn4p.dev
 * Text Domain: jetform-media-gallery
 */

/**
 * DOCUMENTACIÓN DE USO
 * ====================
 * 
 * Este plugin añade un campo personalizado para JetFormBuilder que permite a los usuarios:
 * - Seleccionar una imagen destacada para el post
 * - Seleccionar múltiples imágenes para una galería
 * 
 * CAMPOS DEL FORMULARIO:
 * ---------------------
 * El plugin espera los siguientes nombres de campos en el formulario:
 * - imagen_destacada: Para la imagen destacada del post
 * - galeria: Para las imágenes de la galería
 * 
 * MÉTODO DE IMPLEMENTACIÓN:
 * ------------------------
 * En tu formulario de JetFormBuilder, añade un campo HTML personalizado
 * y dentro agrega el siguiente shortcode:
 * 
 * [media_gallery_field field="nombre_campo" required="1"]
 * 
 * Parámetros disponibles:
 * - field: (requerido) Nombre del campo configurado en el panel de administración
 * - required: (opcional) Si es requerido, usar '1' para requerido, '0' para opcional
 * 
 * ALMACENAMIENTO:
 * --------------
 * - La imagen destacada se establece usando set_post_thumbnail()
 * - Las imágenes de la galería se guardan en el campo meta configurado en el panel de administración
 * 
 * DEPURACIÓN:
 * ----------
 * Los logs se guardan en wp-content/debug-media-gallery.log
 * Incluyen información detallada sobre:
 * - Datos recibidos del formulario
 * - Proceso de guardado de imágenes
 * - Verificación de datos guardados
 * 
 * COMPATIBILIDAD:
 * ----------
 * - Compatible con JetFormBuilder v1.x, v2.x y v3.x
 * - Compatible con JetEngine Forms
 * - Funciona con formularios AJAX y de envío normal
 * - Soporta modo de edición de posts existentes
 * 
 * CHANGELOG:
 * ---------
 * 1.1.0
 * - Rediseñada la interfaz de selección múltiple para iOS con indicadores visuales claros
 * - Añadidas instrucciones paso a paso directamente en la interfaz
 * - Mejorada la visibilidad de las imágenes seleccionadas con etiquetas en cada imagen
 * - Optimizada la experiencia táctil para facilitar la selección de imágenes
 * 
 * 1.0.9
 * - Implementada selección múltiple real para dispositivos iOS con botones dedicados
 * - Añadido botón de confirmación para aplicar la selección múltiple de imágenes
 * - Mejorada la interfaz visual para la selección de imágenes en dispositivos móviles
 * - Añadido botón de cancelación para el modo de selección múltiple
 * 
 * 1.0.8
 * - Mejorada compatibilidad con dispositivos iOS para subida múltiple de imágenes
 * - Implementado soporte táctil para arrastrar y ordenar imágenes en dispositivos móviles
 * - Añadida biblioteca jQuery UI Touch Punch para mejorar interacciones táctiles
 * - Optimizada interfaz de usuario para selección de imágenes en iOS
 * 
 * 1.0.7
 * - Añadida funcionalidad de ordenamiento de imágenes en campos de galería mediante arrastrar y soltar
 * - Mejorada la experiencia en dispositivos móviles para la selección de múltiples imágenes
 * - Añadido indicador visual (drag handle) para facilitar el ordenamiento
 * - Optimizada la interfaz para pantallas pequeñas con estilos responsive
 * - Corregidos problemas de compatibilidad con diferentes navegadores móviles
 * 
 * 1.0.6
 * - Añadido soporte total para JetFormBuilder v3.x
 * - Implementada detección de ID de post mejorada usando reflexión para casos complejos
 * - Actualización para usar los hooks modernos de JetFormBuilder v3
 * - Mejorado manejo de errores para evitar problemas de compatibilidad
 * 
 * 1.0.5
 * - Mejorada la integración con JetFormBuilder utilizando hooks específicos para inserción y actualización de posts
 * - Añadido soporte para diferentes formatos de datos de galería (string, array, JSON)
 * - Mejorada la detección y extracción de IDs de post desde diferentes contextos
 * - Optimizado el proceso de guardado de imágenes para mayor fiabilidad
 * - Añadido registro de diagnóstico más detallado para facilitar la depuración
 * 
 * 1.0.4
 * - Añadido soporte para carga automática de imágenes en modo edición
 * - Mejorada la detección del ID de post en varios contextos
 * - Corregido el problema con los campos que no mostraban las imágenes guardadas
 * - Mejorado el sistema de logging para facilitar la depuración
 * 
 * 1.0.3
 * - Agregada nueva pestaña de administración de logs
 * - Implementada interfaz para activar/desactivar modo debug
 * - Añadido visor de logs con colores por tipo de mensaje
 * - Mejorada la gestión y rotación de archivos de log
 * 
 * 1.0.2
 * - Corregido el procesamiento de campos del formulario
 * - Mejorado el sistema de logging
 * - Añadida compatibilidad con diferentes tipos de post
 * - Optimizado el proceso de guardado de imágenes
 * 
 * 1.0.1
 * - Versión inicial del plugin
 * 
 * 1.0.0
 * - Versión inicial del plugin
 */

// No ejecutar directamente
if (!defined('ABSPATH')) {
    die('No direct script access allowed');
}

// Definir constantes
define('JFB_MEDIA_GALLERY_VERSION', '1.1.0');
define('JFB_MEDIA_GALLERY_PATH', plugin_dir_path(__FILE__));
define('JFB_MEDIA_GALLERY_URL', plugin_dir_url(__FILE__));

// Asegurar que la carpeta js existe
if (!file_exists(JFB_MEDIA_GALLERY_PATH . 'js')) {
    mkdir(JFB_MEDIA_GALLERY_PATH . 'js', 0755);
}

// Cargar traducciones
add_action('plugins_loaded', function() {
    load_plugin_textdomain(
        'jetform-media-gallery',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

// Incluir la clase principal
require_once JFB_MEDIA_GALLERY_PATH . 'includes/class-main.php';

/**
 * Inicializar el plugin
 */
function jetform_media_gallery_init() {
    // Iniciar la instancia principal
    JetForm_Media_Gallery_Main::get_instance();
}
add_action('plugins_loaded', 'jetform_media_gallery_init');

/**
 * Función auxiliar para comprobar si estamos en una página con JetFormBuilder
 */
function jetform_media_gallery_is_form_page() {
    global $post;
    
    if (!$post) {
        return false;
    }
    
    // Comprobar si el post contiene shortcodes de JetFormBuilder
    $has_jetform = has_shortcode($post->post_content, 'jet_fb_form') || 
                  has_shortcode($post->post_content, 'jetengine-booking-form');
    
    // Comprobar si es una página de edición específica
    $is_edit_page = isset($_GET['edit']) || isset($_GET['_post_id']);
    
    return $has_jetform || $is_edit_page;
}

/**
 * Asegurarse de que los scripts de WordPress se cargan en modo frontend
 * cuando haya un formulario en la página para evitar 'wp is not defined'
 */
function jetform_media_gallery_enqueue_wp_scripts() {
    if (!is_admin() && jetform_media_gallery_is_form_page()) {
        wp_enqueue_script('wp-api');
        wp_enqueue_media();
    }
}
add_action('wp_enqueue_scripts', 'jetform_media_gallery_enqueue_wp_scripts', 5);
