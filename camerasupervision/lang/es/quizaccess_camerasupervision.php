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

// Reconocimiento facial
$string['facerecognition'] = 'Habilitar reconocimiento facial';
$string['facerecognition_help'] = 'Si está activo, el estudiante deberá verificar su identidad mediante reconocimiento facial antes de iniciar el intento. Requiere que el administrador haya cargado fotos de referencia del estudiante.';
$string['facethreshold'] = 'Umbral de similitud';
$string['facethreshold_help'] = 'Umbral de distancia euclidiana para considerar una coincidencia facial (0.0 a 1.0). Valores más bajos son más estrictos. Recomendado: 0.6';

// Preflight
$string['consentlabel'] = 'Acepto ser supervisado/a por cámara durante el intento.';
$string['consentrequired'] = 'Debes aceptar la supervisión para iniciar el intento.';
$string['supervisionbtn'] = 'Supervisión por cámara';
$string['supervisiontitle'] = 'Supervisión por cámara';
$string['legalnotice'] = 'Al aceptar, autorizas el uso de tu cámara y el almacenamiento de imágenes con fines de supervisión académica conforme a la normativa vigente.';

// Verificación facial en preflight
$string['faceverificationtitle'] = 'Verificación de identidad';
$string['faceverificationhelp'] = 'Por favor, verifica tu identidad mediante reconocimiento facial para poder iniciar el examen.';
$string['startverificationcamera'] = 'Iniciar cámara';
$string['stopverificationcamera'] = 'Detener cámara';
$string['verifyface'] = 'Verificar rostro';
$string['verificationrequired'] = 'Debes completar la verificación facial antes de iniciar el examen.';

// Información de detección en preflight
$string['detectiontitle'] = 'Se monitoreará lo siguiente:';
$string['detection_camera'] = 'Capturas periódicas de cámara (cada 30 segundos)';
$string['detection_rightclick'] = 'Uso del click derecho';
$string['detection_tabchange'] = 'Cambios de pestaña o ventana';
$string['detection_appchange'] = 'Cambios de aplicación';
$string['detection_facerecognition'] = 'Verificación de identidad facial';

// Visualización
$string['photos'] = 'Fotos';
$string['events'] = 'Registro de eventos';
$string['attempttime'] = 'Horario del intento';
$string['student'] = 'Estudiante';
$string['nofotos'] = 'Aún no hay fotos para este intento.';
$string['totalfotos'] = 'Total de fotos';
$string['noevents'] = 'No hay eventos registrados para este intento.';
$string['inprogress'] = 'En progreso';

// Gestión de fotos de referencia
$string['managefacephotos'] = 'Gestionar fotos de referencia';
$string['selectstudent'] = 'Seleccionar estudiante';
$string['studentid'] = 'ID del estudiante';
$string['studentinfo'] = 'Información del estudiante';
$string['existingphotos'] = 'Fotos de referencia existentes';
$string['nophotosyet'] = 'Este estudiante no tiene fotos de referencia aún.';
$string['uploadnewphoto'] = 'Subir nueva foto de referencia';
$string['uploadphotohelp'] = 'Puedes subir hasta 3 fotos del estudiante para mejorar la precisión del reconocimiento facial. Las fotos deben mostrar claramente el rostro del estudiante con buena iluminación.';
$string['uploadfile'] = 'Subir archivo';
$string['capturewithcamera'] = 'Capturar con cámara';
$string['startcamera'] = 'Iniciar cámara';
$string['stopcamera'] = 'Detener cámara';
$string['takephoto'] = 'Tomar foto';
$string['photo'] = 'Foto';
$string['maxphotos'] = 'Este estudiante ya tiene el máximo de 3 fotos de referencia.';
$string['photouploadsuccess'] = 'Foto subida correctamente';
$string['photodeletesuccess'] = 'Foto eliminada correctamente';
$string['confirmdelete'] = '¿Estás seguro de que deseas eliminar esta foto?';
$string['usernotfound'] = 'Usuario no encontrado';
$string['invalidimage'] = 'Imagen inválida o no se detectó rostro';
$string['noimage'] = 'No se proporcionó ninguna imagen';

// Tipos de eventos
$string['event_camerastart'] = 'Cámara iniciada';
$string['event_c
