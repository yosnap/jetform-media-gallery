# JetFormBuilder Media Gallery Field

Plugin para WordPress que agrega un campo de galería de medios para JetFormBuilder, permitiendo seleccionar imagen destacada y galería para el CPT "singlecar".

## Versión 1.1.9

## Características

- **Selección de imagen destacada**: Permite a los usuarios seleccionar una imagen destacada para el post.
- **Selección de galería de imágenes**: Permite a los usuarios seleccionar múltiples imágenes para una galería.
- **Ordenamiento de imágenes**: Permite reordenar las imágenes de la galería mediante arrastrar y soltar.
- **Compatibilidad móvil**: Optimizado para dispositivos móviles, permitiendo seleccionar múltiples imágenes.
- **Filtrado de biblioteca por usuario**: Muestra solo las imágenes subidas por el usuario actual en el explorador de medios.
- **Interfaz optimizada**: Explorador de medios mejorado con más espacio para las imágenes y mejor usabilidad.
- **Integración con JetFormBuilder**: Compatible con JetFormBuilder v1.x, v2.x y v3.x.
- **Integración con JetEngine Forms**: Compatible con formularios de JetEngine.

## Novedades en la versión 1.1.9

### Correcciones importantes

- Corregido error PHP relacionado con el uso de $\_POST dentro de cadenas de texto de mensajes de log
- Aplicado correctamente el tamaño configurado a las imágenes de vista previa en el frontend
- Mejorada la presentación visual de las imágenes al respetar los tamaños configurados
- Optimizado el código JavaScript para usar las configuraciones globales de forma consistente

## Novedades en la versión 1.1.8

### Filtrado de biblioteca por usuario

- Ahora el explorador de medios muestra solo las imágenes subidas por el usuario actual
- Los administradores pueden seguir viendo todas las imágenes
- Mejora la seguridad y facilita la selección de imágenes propias

### Interfaz mejorada del explorador de medios

- Eliminada la barra lateral para dar más espacio a las imágenes
- Corregido el color del texto en los botones para mejorar la legibilidad
- Eliminados elementos innecesarios que causaban solapamientos
- Eliminados los checkboxes que aparecían en la esquina superior derecha de las imágenes

### Corrección de errores

- Solucionado el problema con el botón de eliminar imagen destacada
- Mejorada la experiencia de usuario en dispositivos móviles

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

## Novedades en la versión 1.1.6

- **Solución a problemas de selección de imágenes**: Corregido el problema donde las imágenes seleccionadas no aparecían en el formulario.
- **Mejora en la gestión de galerías vacías**: Implementado un enfoque simplificado para añadir imágenes cuando la galería está vacía.
- **Visualización de iconos de ordenamiento**: Mejorada la visibilidad de los iconos de ordenamiento (drag handles) al cargar inicialmente el formulario.
- **Manejo robusto de errores**: Implementado sistema de gestión de errores para evitar problemas de comunicación asíncrona.
- **Gestión mejorada de frames activos**: Sistema para rastrear y cerrar correctamente los frames del explorador de medios, evitando conflictos.

## Novedades en la versión 1.1.5

- **Mejora de los checkboxes de selección**: Optimizada la visibilidad de los checkboxes en el modo "Add to gallery" para identificar claramente las imágenes seleccionadas.
- **Selección consistente de imágenes**: Implementado un enfoque unificado usando wp.media.gallery.edit para garantizar que las imágenes existentes aparezcan siempre seleccionadas.
- **Mejoras responsive**: Optimizados los estilos para botones y controles en dispositivos móviles.
- **Mejor visibilidad de controles**: Añadidos estilos específicos para mejorar la visibilidad del selector "Menu" en la interfaz.
- **Optimización de la experiencia de usuario**: Simplificado el flujo de trabajo para añadir imágenes a galerías existentes.

## Novedades en la versión 1.1.4

- **Mejora del explorador de medios**: Implementado comportamiento inteligente que abre el explorador en modo "Edit Gallery" cuando hay imágenes existentes y en modo "Create Gallery" cuando el campo está vacío.
- **Optimización de la selección de imágenes**: Mejorada la experiencia de selección múltiple para mantener todas las imágenes seleccionadas.
- **Eliminación de imágenes mejorada**: Corregido el problema donde las imágenes eliminadas no se quitaban correctamente del listado al actualizar la galería.
- **Mejora visual de controles**: Eliminados los checks que interferían con el botón X para eliminar imágenes de la galería.
- **Optimización para dispositivos móviles**: Mejorada la interfaz para facilitar la selección y ordenamiento en pantallas táctiles.

## Novedades en la versión 1.1.3

- **Filtrado de tipos de archivos por campo**: Ahora cada campo puede configurarse para permitir tipos específicos de archivos (imágenes, vídeos, documentos, audio).
- **Opciones predefinidas**: Selección rápida entre categorías comunes de archivos (solo imágenes, solo vídeos, solo documentos, solo audio).
- **Personalización avanzada**: Opción para seleccionar tipos MIME específicos para cada campo (JPEG, PNG, GIF, WebP, AVIF, PDF, MP4, MOV, MP3, WAV).
- **Experiencia de usuario mejorada**: La biblioteca de medios de WordPress se filtra automáticamente según los tipos de archivos permitidos para cada campo.

## Novedades en la versión 1.1.2

- **Icono de ordenamiento personalizable**: Panel de administración para personalizar el color, tamaño, posición y opacidad del icono de ordenamiento.
- **Mejora en la experiencia de arrastre**: El cursor de arrastrar solo aparece al pasar sobre el icono de ordenamiento, evitando confusiones.
- **Optimización para dispositivos táctiles**: En móviles, el arrastre solo se activa al tocar el icono de ordenamiento.
- **Feedback visual mejorado**: Efectos visuales durante el arrastre y ordenamiento para una mejor experiencia de usuario.
- **Centrado vertical de líneas**: Mejora visual del icono de ordenamiento con líneas perfectamente centradas.
- **Vista previa en tiempo real**: Panel de administración con vista previa en tiempo real de los cambios en el icono de ordenamiento.

## Novedades en la versión 1.1.1

- **Selección acumulativa de imágenes**: Las imágenes seleccionadas ahora se acumulan en lugar de reemplazarse.
- **Ordenamiento de imágenes existentes**: Las imágenes existentes en la galería tienen la funcionalidad de ordenamiento desde el inicio.
- **Interfaz mejorada para iOS**: Rediseño completo de la interfaz de selección múltiple con indicadores visuales claros.
- **Instrucciones paso a paso**: Guía visual con pasos numerados para facilitar la selección de imágenes.
- **Indicadores visuales en cada imagen**: Etiquetas que muestran claramente el estado de selección de cada imagen.ente qué imágenes están seleccionadas.
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

### 1.1.2

- Implementado panel de administración para personalizar el icono de ordenamiento (color, tamaño, posición, opacidad)
- Mejorada la experiencia de arrastre para que el cursor solo cambie al pasar sobre el icono
- Optimizada la interacción táctil en dispositivos móviles
- Añadido feedback visual durante el arrastre y ordenamiento
- Centradas verticalmente las líneas del icono de ordenamiento
- Implementada vista previa en tiempo real en el panel de administración
- Corregidos errores de sintaxis en el código PHP

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
