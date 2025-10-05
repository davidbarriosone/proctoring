<?php
$string['pluginname'] = 'Supervisión por cámara';
$string['enabled'] = 'Habilitar supervisión por cámara';
$string['enabled_help'] = 'Si está activo, el estudiante deberá consentir la supervisión por cámara y se tomarán fotos cada 30 segundos durante su intento.';

// Opciones de detección
$string['detectrightclick'] = 'Detectar click derecho';
$string['detectrightclick_help'] = 'Si está activo, se registrarán los eventos de click derecho durante el intento del cuestionario.';
$string['detecttabchange'] = 'Detectar cambio de pestaña';
$string['detecttabchange_help'] = 'Si está activo, se registrarán los cambios de pestaña o ventana durante el intento del cuestionario.';
$string['detectappchange'] = 'Detectar cambio de aplicación';
$string['detectappchange_help'] = 'Si está activo, se registrarán los cambios de aplicación o pérdida de foco de la ventana durante el intento del cuestionario.';

// Preflight
$string['consentlabel'] = 'Acepto ser supervisado/a por cámara durante el intento.';
$string['consentrequired'] = 'Debes aceptar la supervisión para iniciar el intento.';
$string['supervisionbtn'] = 'Supervisión por cámara';
$string['supervisiontitle'] = 'Supervisión por cámara';
$string['legalnotice'] = 'Al aceptar, autorizas el uso de tu cámara y el almacenamiento de imágenes con fines de supervisión académica conforme a la normativa vigente.';

// Información de detección en preflight
$string['detectiontitle'] = 'Se monitoreará lo siguiente:';
$string['detection_camera'] = 'Capturas periódicas de cámara (cada 30 segundos)';
$string['detection_rightclick'] = 'Uso del click derecho';
$string['detection_tabchange'] = 'Cambios de pestaña o ventana';
$string['detection_appchange'] = 'Cambios de aplicación';

// Visualización
$string['photos'] = 'Fotos';
$string['events'] = 'Registro de eventos';
$string['attempttime'] = 'Horario del intento';
$string['student'] = 'Estudiante';
$string['nofotos'] = 'Aún no hay fotos para este intento.';
$string['totalfotos'] = 'Total de fotos';
$string['noevents'] = 'No hay eventos registrados para este intento.';
$string['inprogress'] = 'En progreso';

// Tipos de eventos
$string['event_camerastart'] = 'Cámara iniciada';
$string['event_cameraerror'] = 'Error de cámara';
$string['event_rightclick'] = 'Click derecho detectado';
$string['event_tabchange'] = 'Abandonó la página';
$string['event_tabreturn'] = 'Regresó a la página';
$string['event_appchange'] = 'Cambió de aplicación';
$string['event_appreturn'] = 'Regresó a la aplicación';
$string['event_devtools'] = 'Intento de abrir herramientas de desarrollo';
$string['event_viewsource'] = 'Intento de ver código fuente';

// Privacidad
$string['privacy:metadata'] = 'Este plugin almacena imágenes y registros de eventos asociados a intentos de cuestionarios para fines de supervisión.';
$string['privacy:metadata:quizaccess_camsup_events'] = 'Registro de eventos de supervisión del cuestionario';
$string['privacy:metadata:quizaccess_camsup_events:attemptid'] = 'El ID del intento del cuestionario';
$string['privacy:metadata:quizaccess_camsup_events:userid'] = 'El ID del usuario que realiza el cuestionario';
$string['privacy:metadata:quizaccess_camsup_events:eventtype'] = 'El tipo de evento detectado';
$string['privacy:metadata:quizaccess_camsup_events:eventdata'] = 'Información adicional sobre el evento';
$string['privacy:metadata:quizaccess_camsup_events:timecreated'] = 'Cuándo ocurrió el evento';

$string['snapshots_filearea'] = 'Capturas de supervisión';
