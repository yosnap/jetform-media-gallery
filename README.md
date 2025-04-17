# JetFormBuilder Media Gallery Field

Plugin de WordPress que añade campos de galería de medios para JetFormBuilder, permitiendo seleccionar imagen destacada y galería para posts.

## Características

- Selección de imagen destacada
- Selección múltiple para galerías
- Interfaz intuitiva y responsive
- Compatibilidad total con JetFormBuilder
- Soporte para diferentes tipos de post
- Sistema de logging para depuración

## Requisitos

- WordPress 5.0 o superior
- JetFormBuilder instalado y activado
- PHP 7.2 o superior

## Instalación

1. Descarga el plugin
2. Sube la carpeta `jetform-media-gallery` a `/wp-content/plugins/`
3. Activa el plugin desde el panel de administración de WordPress

## Uso

### Configuración de Campos

En el panel de administración, ve a "JetForm Media Gallery" y configura:

1. Ajustes generales (tamaños, estilos, etc.)
2. Campos de imagen (nombre, tipo, meta key)

### En el Formulario

Añade el shortcode en tu formulario de JetFormBuilder:

```php
[media_gallery_field field="nombre_campo" required="1"]
```

### Nombres de Campos

El plugin espera los nombres de campos en el formulario de acuerdo a los ajustes en la administración del plugin


## Depuración

Los logs se guardan en `wp-content/debug-media-gallery.log` e incluyen:
- Datos recibidos del formulario
- Proceso de guardado
- Verificación de datos

## Changelog

### 1.0.2
- Se agrega sistema de administración para el Debug de procesos en el backend

### 1.0.1
- Corregido el procesamiento de campos del formulario
- Mejorado el sistema de logging
- Añadida compatibilidad con diferentes tipos de post
- Optimizado el proceso de guardado de imágenes

### 1.0.0
- Versión inicial del plugin
