# JetFormBuilder Media Gallery Field

Plugin para WordPress que agrega un campo de galería de medios para JetFormBuilder, permitiendo seleccionar imagen destacada y galería para el CPT "singlecar".

## Versión 1.1.1

## Características

- **Selección de imagen destacada**: Permite a los usuarios seleccionar una imagen destacada para el post.
- **Selección de galería de imágenes**: Permite a los usuarios seleccionar múltiples imágenes para una galería.
- **Ordenamiento de imágenes**: Permite reordenar las imágenes de la galería mediante arrastrar y soltar.
- **Compatibilidad móvil**: Optimizado para dispositivos móviles, permitiendo seleccionar múltiples imágenes.
- **Integración con JetFormBuilder**: Compatible con JetFormBuilder v1.x, v2.x y v3.x.
- **Integración con JetEngine Forms**: Compatible con formularios de JetEngine.

## Instalación

1. Sube el plugin a tu directorio `/wp-content/plugins/`
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Configura los campos de imagen en la configuración del plugin

## Uso

### En tu formulario de JetFormBuilder

Añade un campo HTML personalizado y dentro agrega el siguiente shortcode:

```
[media_gallery_field field="nombre_campo" required="1"]
```

### Parámetros disponibles

- `field`: (requerido) Nombre del campo configurado en el panel de administración
- `required`: (opcional) Si es requerido, usar '1' para requerido, '0' para opcional

## Novedades en la versión 1.1.1

- **Selección acumulativa de imágenes**: Las imágenes seleccionadas ahora se acumulan en lugar de reemplazarse.
- **Ordenamiento de imágenes existentes**: Las imágenes existentes en la galería tienen la funcionalidad de ordenamiento desde el inicio.
- **Interfaz mejorada para iOS**: Rediseño completo de la interfaz de selección múltiple con indicadores visuales claros.
- **Instrucciones paso a paso**: Guía visual con pasos numerados para facilitar la selección de imágenes.
- **Indicadores visuales en cada imagen**: Etiquetas que muestran claramente qué imágenes están seleccionadas.
- **Compatibilidad táctil mejorada**: Soporte optimizado para arrastrar y ordenar imágenes en dispositivos táctiles.

## Historial de cambios

### 1.1.1
- Corregido el problema con el ordenamiento de imágenes existentes en la galería
- Mejorada la selección de imágenes para que mantenga las selecciones previas
- Optimizado el proceso de añadir imágenes para que se acumulen en lugar de reemplazarse
- Reinicialización automática del sortable después de añadir nuevas imágenes

### 1.1.0
- Rediseñada la interfaz de selección múltiple para iOS con indicadores visuales claros
- Añadidas instrucciones paso a paso directamente en la interfaz
- Mejorada la visibilidad de las imágenes seleccionadas con etiquetas en cada imagen
- Optimizada la experiencia táctil para facilitar la selección de imágenes

### 1.0.9
- Implementada selección múltiple real para dispositivos iOS con botones dedicados
- Añadido botón de confirmación para aplicar la selección múltiple de imágenes
- Mejorada la interfaz visual para la selección de imágenes en dispositivos móviles
- Añadido botón de cancelación para el modo de selección múltiple

### 1.0.8
- Mejorada compatibilidad con dispositivos iOS para subida múltiple de imágenes
- Implementado soporte táctil para arrastrar y ordenar imágenes en dispositivos móviles
- Añadida biblioteca jQuery UI Touch Punch para mejorar interacciones táctiles
- Optimizada interfaz de usuario para selección de imágenes en iOS

### 1.0.7
- Añadida funcionalidad de ordenamiento de imágenes en campos de galería mediante arrastrar y soltar
- Mejorada la experiencia en dispositivos móviles para la selección de múltiples imágenes
- Añadido indicador visual (drag handle) para facilitar el ordenamiento
- Optimizada la interfaz para pantallas pequeñas con estilos responsive
- Corregidos problemas de compatibilidad con diferentes navegadores móviles

### 1.0.6
- Añadido soporte total para JetFormBuilder v3.x
- Implementada detección de ID de post mejorada usando reflexión para casos complejos
- Actualización para usar los hooks modernos de JetFormBuilder v3
- Mejorado manejo de errores para evitar problemas de compatibilidad

### 1.0.5
- Mejorada la integración con JetFormBuilder utilizando hooks específicos para inserción y actualización de posts
- Añadido soporte para diferentes formatos de datos de galería (string, array, JSON)
- Mejorada la detección y extracción de IDs de post desde diferentes contextos
- Optimizado el proceso de guardado de imágenes para mayor fiabilidad
- Añadido registro de diagnóstico más detallado para facilitar la depuración

### 1.0.4
- Añadido soporte para carga automática de imágenes en modo edición
- Mejorada la detección del ID de post en varios contextos
- Corregido el problema con los campos que no mostraban las imágenes guardadas
- Mejorado el sistema de logging para facilitar la depuración

### 1.0.3
- Agregada nueva pestaña de administración de logs
- Implementada interfaz para activar/desactivar modo debug
- Añadido visor de logs con colores por tipo de mensaje
- Mejorada la gestión y rotación de archivos de log

### 1.0.2
- Corregido el procesamiento de campos del formulario
- Mejorado el sistema de logging
- Añadida compatibilidad con diferentes tipos de post
- Optimizado el proceso de guardado de imágenes

### 1.0.1 y 1.0.0
- Versiones iniciales del plugin