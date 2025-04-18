<?php
/**
 * Plugin Name: JetFormBuilder Media Gallery Field
 * Description: Agrega un campo de galería de medios para JetFormBuilder que permite seleccionar imagen destacada y galería para el CPT "singlecar"
 * Version: 1.0.4
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
 * CHANGELOG:
 * ---------
 * 1.0.4
 * - Mejorada la integración con JetFormBuilder utilizando hooks específicos para inserción y actualización de posts
 * - Añadido soporte para diferentes formatos de datos de galería (string, array, JSON)
 * - Mejorada la detección y extracción de IDs de post desde diferentes contextos
 * - Optimizado el proceso de guardado de imágenes para mayor fiabilidad
 * - Añadido registro de diagnóstico más detallado para facilitar la depuración
 * 
 * 1.0.3
 * - Añadido soporte para carga automática de imágenes en modo edición
 * - Mejorada la detección del ID de post en varios contextos
 * - Corregido el problema con los campos que no mostraban las imágenes guardadas
 * - Mejorado el sistema de logging para facilitar la depuración
 * 
 * 1.0.2
 * - Agregada nueva pestaña de administración de logs
 * - Implementada interfaz para activar/desactivar modo debug
 * - Añadido visor de logs con colores por tipo de mensaje
 * - Mejorada la gestión y rotación de archivos de log
 * 
 * 1.0.1
 * - Corregido el procesamiento de campos del formulario
 * - Mejorado el sistema de logging
 * - Añadida compatibilidad con diferentes tipos de post
 * - Optimizado el proceso de guardado de imágenes
 * 
 * 1.0.0
 * - Versión inicial del plugin
 */

// No ejecutar directamente
if (!defined('ABSPATH')) {
    die('No direct script access allowed');
}

// Definir constantes
define('JFB_MEDIA_GALLERY_VERSION', '1.0.4');
define('JFB_MEDIA_GALLERY_PATH', plugin_dir_path(__FILE__));
define('JFB_MEDIA_GALLERY_URL', plugin_dir_url(__FILE__));

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
