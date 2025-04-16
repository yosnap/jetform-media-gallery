# Guía de Instalación y Uso de JetFormBuilder Media Gallery Field

## Instalación

1. Descarga el archivo `jetform-media-gallery.php` del plugin
2. Sube el archivo a la carpeta `/wp-content/plugins/jetform-media-gallery/` de tu WordPress
   - Alternativamente, puedes crear esta carpeta y subir el archivo dentro
3. Activa el plugin desde el panel de administración de WordPress (Plugins > Plugins Instalados)

## Uso Básico

El plugin proporciona tres formas diferentes de usar el campo de selección de medios:

### 1. Como Shortcode

Añade el siguiente shortcode en tu formulario de JetFormBuilder:

```
[media_gallery_field name="media_gallery" label="Seleccionar imágenes para el anuncio" required="1"]
```

Parámetros disponibles:
- `name` (opcional): Nombre base del campo, por defecto 'media_gallery'
- `label` (opcional): Etiqueta del campo, por defecto 'Seleccionar imágenes'
- `required` (opcional): Si es requerido, usar '1' para requerido, '0' para opcional

### 2. Como Macro de JetEngine

Si estás usando JetEngine, puedes utilizar la macro:

```
%%media_gallery_field name="media_gallery" label="Seleccionar imágenes" required="1"%%
```

Los mismos parámetros que en el shortcode están disponibles.

### 3. Como Bloque de Gutenberg

Si estás usando el editor de bloques de Gutenberg con JetFormBuilder:

1. Añade un nuevo bloque
2. Busca "Media Gallery Field" en la categoría de JetFormBuilder
3. Configura las opciones en el panel lateral

## Ejemplo de Implementación

### 1. Crear un nuevo formulario en JetFormBuilder

1. Ve a JetFormBuilder > Añadir nuevo
2. Configura el título del formulario
3. Añade los campos necesarios para tu formulario de vehículos
4. En el lugar donde quieres que aparezca el selector de medios, añade un campo "HTML Personalizado"
5. En este campo HTML, añade el shortcode:
   ```
   [media_gallery_field name="media_gallery" label="Seleccionar imágenes para el anuncio" required="1"]
   ```

### 2. Configurar la acción Post Submit

1. Ve a la pestaña "Acciones Post Submit" en JetFormBuilder
2. Añade la acción "Insertar/Actualizar Post"
3. Configura el tipo de post como "singlecar"
4. Configura los campos del formulario para mapear a los campos correspondientes del post
5. Guarda el formulario

El plugin se encargará automáticamente de:
- Guardar la imagen seleccionada como imagen destacada del post
- Guardar las imágenes de la galería en el campo meta "ad_gallery"

## Estructura de Datos Generada

Cuando el usuario selecciona las imágenes, el plugin genera los siguientes campos en el formulario:

1. `{nombre}_featured`: Contiene el ID de la imagen destacada seleccionada
2. `{nombre}_gallery`: Contiene los IDs de las imágenes de la galería separados por comas

Ejemplo: Si usas `name="media_gallery"`, los campos serán:
- `media_gallery_featured`
- `media_gallery_gallery`

## Solución de Problemas

### Las imágenes no se guardan correctamente

1. Verifica que los nombres de campo estén correctos
2. Asegúrate de que el formulario esté configurado para insertar posts del tipo "singlecar"
3. Comprueba que los scripts de media se estén cargando correctamente
4. Verifica que el usuario tenga permisos para subir medios

### El selector de medios no aparece

1. Asegúrate de que el plugin está activado
2. Verifica que el shortcode esté correctamente escrito
3. Comprueba la consola del navegador para ver si hay errores de JavaScript

### Personalización del Estilo

Si necesitas personalizar el aspecto del campo, puedes añadir CSS adicional a tu tema. Los principales selectores a tener en cuenta son:

```css
.media-gallery-container { /* Contenedor principal */ }
.featured-image-container { /* Contenedor de imagen destacada */ }
.gallery-container { /* Contenedor de la galería */ }
.image-preview { /* Previsualización de la imagen destacada */ }
.images-preview { /* Previsualización de las imágenes de la galería */ }
.gallery-image { /* Cada imagen individual en la galería */ }
.remove-image { /* Botón para eliminar una imagen */ }
```