# Camera Supervision - Quiz Access Rule Plugin for Moodle

Plugin de supervisión por cámara para cuestionarios de Moodle 3.11+

## Características

### Supervisión por Cámara
- Captura automática de fotos cada 30 segundos durante el intento del cuestionario
- Almacenamiento seguro de imágenes en el sistema de archivos de Moodle
- Visualización de todas las fotos capturadas por intento

### Detección de Eventos
- **Click derecho**: Registra cuando el estudiante hace click derecho
- **Cambio de pestaña**: Detecta cuando el estudiante cambia de pestaña o minimiza el navegador
- **Cambio de aplicación**: Registra cuando el estudiante cambia a otra aplicación o ventana
- **Intento de abrir DevTools**: Bloquea y registra intentos de abrir herramientas de desarrollador
- **Vista de código fuente**: Bloquea y registra intentos de ver el código fuente

### Registro de Eventos
- Timeline detallado de todos los eventos durante el intento
- Estadísticas resumidas por tipo de evento
- Marcas de tiempo precisas para cada evento
- Clasificación visual de eventos (éxito, advertencia, error)

## Requisitos

- Moodle 3.11 o superior
- HTTPS habilitado (requerido para acceso a la cámara)
- Navegadores modernos con soporte para:
  - getUserMedia API
  - Page Visibility API
  - FormData y Fetch API

## Instalación

### Vía interfaz de administración (Recomendado)

1. Descarga el archivo ZIP del plugin
2. Accede como administrador a tu sitio Moodle
3. Ve a **Administración del sitio → Plugins → Instalar plugins**
4. Arrastra el archivo ZIP o usa el selector de archivos
5. Haz clic en **"Instalar plugin desde archivo ZIP"**
6. Sigue el proceso de actualización de la base de datos
7. Confirma la instalación

### Vía FTP/SSH

1. Extrae el contenido del ZIP
2. Sube la carpeta `camerasupervision` a: `/mod/quiz/accessrule/`
3. Visita `/admin/index.php` como administrador para completar la instalación

## Configuración

### A nivel de cuestionario

1. Edita un cuestionario o crea uno nuevo
2. En la sección **"Supervisión por cámara"**:
   - ✅ **Habilitar supervisión por cámara**: Activa la captura de fotos
   - ✅ **Detectar click derecho**: Registra eventos de click derecho
   - ✅ **Detectar cambio de pestaña**: Registra cambios de pestaña/ventana
   - ✅ **Detectar cambio de aplicación**: Registra cambios de aplicación

3. Guarda los cambios

### Consentimiento del estudiante

Antes de iniciar el intento, el estudiante debe:
- Leer el aviso legal
- Ver qué se monitoreará durante el examen
- Aceptar el consentimiento marcando la casilla

Sin aceptar, no podrá iniciar el intento.

## Uso

### Para estudiantes

1. Al iniciar el cuestionario, leer y aceptar el consentimiento
2. Permitir el acceso a la cámara cuando el navegador lo solicite
3. Realizar el cuestionario normalmente
4. La cámara capturará fotos automáticamente cada 30 segundos

**Notas importantes**:
- La cámara debe estar conectada y funcional
- El sitio debe usar HTTPS
- No cambiar de pestaña ni aplicación durante el examen (se registrará)
- No usar click derecho (se registrará)

### Para profesores

1. En la página principal del cuestionario, hacer clic en **"Supervisión por cámara"**
2. Ver lista de todos los intentos del cuestionario
3. Hacer clic en **"Ver fotos"** del intento deseado
4. Revisar:
   - **Pestaña Fotos**: Galería de todas las capturas
   - **Pestaña Eventos**: Timeline detallado de eventos sospechosos

#### Interpretación de eventos

- 🟢 **Verde** (Cámara iniciada): Proceso normal
- 🟡 **Amarillo** (Cambios de pestaña/app, click derecho): Advertencia, posible conducta irregular
- 🔴 **Rojo** (Error de cámara, DevTools): Error grave o intento de manipulación

## Permisos

El plugin define el permiso:
- `quizaccess/camerasupervision:view`: Permite ver fotos y eventos de los intentos

Por defecto, tienen este permiso:
- Editores de curso (editingteacher)
- Profesores (teacher)
- Administradores (manager)

## Estructura de archivos

```
camerasupervision/
├── version.php                    # Información del plugin
├── rule.php                       # Regla de acceso principal
├── lib.php                        # Funciones de biblioteca (pluginfile)
├── snapshot.php                   # Endpoint AJAX para guardar fotos
├── logevent.php                   # Endpoint AJAX para guardar eventos
├── photos.php                     # Visualización de fotos y eventos
├── review.php                     # Lista de intentos
├── styles.css                     # Estilos CSS
├── README.md                      # Este archivo
├── lang/
│   ├── en/
│   │   └── quizaccess_camerasupervision.php
│   └── es/
│       └── quizaccess_camerasupervision.php
├── db/
│   ├── access.php                 # Definición de permisos
│   ├── install.xml                # Esquema de base de datos
│   └── upgrade.php                # Script de actualización
└── amd/
    ├── src/
    │   └── recorder.js            # Código JavaScript (fuente)
    └── build/
        └── recorder.min.js        # Código JavaScript (compilado)
```

## Base de datos

### Tabla: quizaccess_camsup
Almacena la configuración por cuestionario:
- `quizid`: ID del cuestionario
- `enabled`: Supervisión por cámara habilitada (0/1)
- `detectrightclick`: Detectar click derecho (0/1)
- `detecttabchange`: Detectar cambio de pestaña (0/1)
- `detectappchange`: Detectar cambio de aplicación (0/1)

### Tabla: quizaccess_camsup_events
Almacena el registro de eventos:
- `attemptid`: ID del intento
- `userid`: ID del usuario
- `eventtype`: Tipo de evento
- `eventdata`: Descripción del evento
- `timecreated`: Timestamp del evento

### Tipos de eventos
- `camerastart`: Cámara iniciada correctamente
- `cameraerror`: Error al iniciar la cámara
- `rightclick`: Click derecho detectado
- `tabchange`: Usuario abandonó la pestaña
- `tabreturn`: Usuario regresó a la pestaña
- `appchange`: Usuario cambió de aplicación
- `appreturn`: Usuario regresó a la aplicación
- `devtools`: Intento de abrir herramientas de desarrollo
- `viewsource`: Intento de ver código fuente

## Actualización desde versión anterior

Si ya tienes una versión anterior instalada:

1. Haz backup de tu base de datos
2. Sube la nueva versión del plugin
3. Visita `/admin/index.php` como administrador
4. El script de upgrade agregará automáticamente:
   - Nuevos campos a la tabla `quizaccess_camsup`
   - Nueva tabla `quizaccess_camsup_events`

## Solución de problemas

### La cámara no se activa

**Posibles causas**:
- Sitio sin HTTPS → Configurar SSL/TLS
- Navegador no soportado → Usar Chrome, Firefox, Edge o Safari modernos
- Permisos de cámara denegados → Revisar configuración del navegador
- Cámara en uso por otra aplicación → Cerrar otras aplicaciones

**Solución**:
1. Verificar que la URL comience con `https://`
2. En el navegador, permitir acceso a la cámara cuando se solicite
3. Revisar la consola JavaScript (F12) para ver mensajes de error

### Los eventos no se registran

**Verificar**:
1. Que las opciones de detección estén habilitadas en la configuración del cuestionario
2. Que el JavaScript se esté cargando correctamente
3. Revisar logs de Moodle en: Administración del sitio → Informes → Registros

### Las fotos no se muestran

**Verificar**:
1. Permisos de archivos en el servidor
2. Que la función `quizaccess_camerasupervision_pluginfile()` esté en `lib.php`
3. Revisar permisos del usuario (debe tener `quizaccess/camerasupervision:view`)

### Error "Invalid plugin package" al instalar

**Solución**:
- Asegurarse de que la carpeta se llame exactamente `camerasupervision`
- Verificar que el ZIP contenga la carpeta con todos los archivos dentro
- NO debe ser un ZIP con archivos sueltos en la raíz

## Privacidad y GDPR

Este plugin almacena:
- **Imágenes**: Fotos capturadas de la cámara del estudiante
- **Eventos**: Registro de acciones durante el intento (cambios de pestaña, clicks, etc.)
- **Metadatos**: Fecha/hora, usuario, intento asociado

### Cumplimiento GDPR
- Los datos se almacenan únicamente con fines académicos de supervisión
- Los estudiantes deben dar consentimiento explícito antes del intento
- Los profesores solo pueden ver datos de sus propios cursos
- Los datos se vinculan al intento del cuestionario

### Eliminación de datos
Los datos se eliminan automáticamente cuando:
- Se elimina el intento del cuestionario
- Se elimina el cuestionario completo
- Se elimina al usuario

## Seguridad

### Medidas implementadas
- ✅ Verificación de `sesskey` en todas las peticiones AJAX
- ✅ Validación de permisos por contexto de módulo
- ✅ Verificación de propiedad del intento (usuario solo puede enviar sus propios datos)
- ✅ Sanitización de datos de entrada
- ✅ Uso de File API de Moodle para almacenamiento seguro
- ✅ Acceso a archivos solo para usuarios autorizados

### Recomendaciones
- Usar HTTPS en producción (obligatorio para cámara)
- Configurar límites de tamaño de archivo en PHP
- Monitorear espacio en disco (las fotos pueden ocupar espacio)
- Revisar logs periódicamente

## Rendimiento

### Optimizaciones
- Capturas cada 30 segundos (balance entre supervisión y rendimiento)
- Compresión PNG para reducir tamaño de archivos
- Carga asíncrona de JavaScript (AMD)
- Índices en base de datos para consultas rápidas

### Consideraciones
- Cada foto ocupa aproximadamente 50-200 KB
- Un intento de 1 hora = ~120 fotos = ~12-24 MB
- Planificar espacio en disco según cantidad de estudiantes y cuestionarios

## Desarrolladores

### Compilar JavaScript

Si modificas `amd/src/recorder.js`, debes compilarlo:

```bash
# En el directorio raíz de Moodle
php admin/cli/grunt.php amd --force
```

O manualmente copiar el contenido a `amd/build/recorder.min.js` (minificado)

### Personalización

**Cambiar intervalo de captura**:
Editar en `amd/src/recorder.js`, línea ~115:
```javascript
var intervalMs = 30000; // Cambiar a los milisegundos deseados
```

**Agregar nuevos tipos de eventos**:
1. Agregar detección en `amd/src/recorder.js`
2. Agregar string en archivos de idioma
3. Actualizar visualización en `photos.php`

**Cambiar calidad de imagen**:
Editar en `amd/src/recorder.js`, función `dataURLFromVideo()`:
```javascript
return canvas.toDataURL('image/jpeg', 0.8); // JPEG con 80% calidad
```

## Soporte

### Reportar errores
- Revisar primero la consola JavaScript (F12)
- Verificar logs de Moodle
- Incluir: versión de Moodle, navegador, y pasos para reproducir

### Contribuir
Las contribuciones son bienvenidas:
- Corrección de errores
- Nuevas funcionalidades
- Traducciones a otros idiomas
- Mejoras de rendimiento

## Licencia

Este plugin se distribuye bajo licencia GPL v3.

## Changelog

### v0.2 (2025-08-18)
- ✨ Cambio de intervalo de captura de 15s a 30s
- ✨ Detección de click derecho
- ✨ Detección de cambio de pestaña
- ✨ Detección de cambio de aplicación
- ✨ Sistema de registro de eventos (logs)
- ✨ Visualización mejorada con tabs (fotos/eventos)
- ✨ Estadísticas de eventos por tipo
- ✨ Timeline visual de eventos
- ✨ Detección de intento de abrir DevTools
- 🐛 Mejoras en validación de consentimiento
- 📝 Documentación completa

### v0.1 (2025-08-18)
- 🎉 Versión inicial
- Captura de fotos cada 15 segundos
- Sistema de consentimiento
- Almacenamiento en File API
- Visualización básica de fotos

## Créditos

Desarrollado para Moodle 3.11+

---

**¿Preguntas o sugerencias?** Revisa la documentación completa o consulta con tu administrador de Moodle.
