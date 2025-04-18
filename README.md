# JetFormBuilder Media Gallery Field

Plugin personalizado para WordPress que agrega un campo de galería de medios para JetFormBuilder, permitiendo a los usuarios seleccionar una imagen destacada y múltiples imágenes para una galería, especialmente diseñado para el CPT "singlecar".

## Características

- Campo de imagen destacada: Permite seleccionar una imagen como destacada para el post
- Campo de galería: Permite seleccionar múltiples imágenes para una galería
- Integración completa con JetFormBuilder
- Soporte para la edición de posts existentes: Carga y muestra automáticamente imágenes guardadas
- Diseño responsive y personalizable
- Panel de administración para configurar los campos
- Sistema de logging para depuración
- Soporte para internacionalización

## Requisitos

- WordPress 5.6 o superior
- JetFormBuilder 2.0 o superior
- PHP 7.2 o superior

## Instalación

1. Sube la carpeta `jetform-media-gallery` al directorio `/wp-content/plugins/`
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Configura los campos en el menú 'JetFormBuilder' > 'Media Gallery'

## Uso

### En el formulario de JetFormBuilder

1. Crea un nuevo formulario con JetFormBuilder o edita uno existente
2. Añade un campo HTML personalizado
3. Dentro del campo HTML, inserta el siguiente shortcode:

```
[media_gallery_field field="nombre_campo" required="1"]
```

Parámetros disponibles:
- `field`: (requerido) Nombre del campo configurado en el panel de administración
- `required`: (opcional) Si es requerido, usar '1' para requerido, '0' para opcional

### Modo de edición

El plugin detecta automáticamente cuando un formulario se está utilizando para editar un post existente y:

1. Carga las imágenes guardadas previamente
2. Muestra la imagen destacada actual del post
3. Muestra las imágenes de la galería actuales
4. Permite añadir o eliminar imágenes existentes

Esta funcionalidad es compatible con:
- Parámetro `_post_id` de JetFormBuilder
- Parámetro `edit` en la URL
- Integración con JetEngine

### Almacenamiento

- La imagen destacada se establece usando `set_post_thumbnail()`
- Las imágenes de la galería se guardan en el campo meta configurado en el panel de administración

## Depuración

Los logs se guardan en `wp-content/debug-media-gallery.log` y pueden ser visualizados y gestionados desde el panel de administración. El sistema de logs incluye:

- Información detallada sobre los datos recibidos del formulario
- Proceso de guardado de imágenes
- Verificación de datos guardados
- Rotación automática de logs

## Estructura del Plugin

El plugin está organizado con un enfoque modular utilizando clases PHP:

```
jetform-media-gallery/
├── admin/
│   └── settings.php                 # Configuración de administración
├── includes/
│   ├── class-main.php               # Clase principal (singleton)
│   ├── class-field.php              # Renderiza el campo y scripts
│   ├── class-process-form.php       # Procesa envíos del formulario
│   ├── class-styles.php             # Genera estilos dinámicos
│   └── class-logger.php             # Sistema de registro
├── languages/                       # Archivos de traducción
├── jetform-media-gallery.php        # Archivo principal del plugin
└── README.md                        # Este archivo
```

## Personalización

El plugin ofrece múltiples opciones de personalización a través del panel de administración:

- Tamaño de las imágenes
- Posición y estilo de los botones
- Colores de los elementos
- Comportamiento y orden de los componentes

## Changelog

### 1.0.4
- Mejorada la integración con JetFormBuilder utilizando hooks específicos para inserción y actualización de posts
- Añadido soporte para diferentes formatos de datos de galería (string, array, JSON)
- Mejorada la detección y extracción de IDs de post desde diferentes contextos
- Optimizado el proceso de guardado de imágenes para mayor fiabilidad
- Añadido registro de diagnóstico más detallado para facilitar la depuración

### 1.0.3
- Añadido soporte para carga automática de imágenes en modo edición
- Mejorada la detección del ID de post en varios contextos
- Corregido el problema con los campos que no mostraban las imágenes guardadas
- Mejorado el sistema de logging para facilitar la depuración

### 1.0.2
- Agregada nueva pestaña de administración de logs
- Implementada interfaz para activar/desactivar modo debug
- Añadido visor de logs con colores por tipo de mensaje
- Mejorada la gestión y rotación de archivos de log

### 1.0.1
- Corregido el procesamiento de campos del formulario
- Mejorado el sistema de logging
- Añadida compatibilidad con diferentes tipos de post
- Optimizado el proceso de guardado de imágenes

### 1.0.0
- Versión inicial del plugin

## Licencia

Este plugin es de propiedad de Sn4p.dev y su uso está destinado exclusivamente para proyectos autorizados.

## Autor

Desarrollado por Sn4p.dev

## Soporte

Para soporte técnico, contactar a través de: soporte@sn4p.dev
