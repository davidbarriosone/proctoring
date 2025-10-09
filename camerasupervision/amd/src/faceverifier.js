// AMD module para verificación facial en preflight
define(['jquery', 'core/log', 'core/notification'], function($, log, notification) {
    
    var stream = null;
    var faceapi = null;
    var referenceDescriptors = [];
    var threshold = 0.6;
    var verificationPassed = false;
    
    /**
     * Cargar modelos de face-api
     */
    async function loadModels() {
        if (!window.faceapi) {
            return false;
        }
        
        faceapi = window.faceapi;
        
        try {
            var modelPath = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/model';
            
            await faceapi.nets.tinyFaceDetector.loadFromUri(modelPath);
            await faceapi.nets.faceLandmark68Net.loadFromUri(modelPath);
            await faceapi.nets.faceRecognitionNet.loadFromUri(modelPath);
            
            return true;
        } catch (err) {
            return false;
        }
    }
    
    /**
     * Cargar descriptores de referencia del usuario
     */
    async function loadReferenceDescriptors(userid) {
        try {
            var response = await fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/camerasupervision/get_descriptors.php?userid=' + userid, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            var result = await response.json();
            
            if (result.status === 'ok' && result.descriptors && result.descriptors.length > 0) {
                referenceDescriptors = result.descriptors.map(function(d) {
                    return new Float32Array(d);
                });
                
                log.debug('camerasupervision: loaded ' + referenceDescriptors.length + ' reference descriptors');
                return true;
            } else {
                log.debug('camerasupervision: no reference descriptors found');
                return false;
            }
        } catch (err) {
            log.error('camerasupervision: error loading descriptors: ' + err);
            return false;
        }
    }
    
    /**
     * Comparar descriptor actual con referencias
     */
    function compareDescriptor(currentDescriptor) {
        if (referenceDescriptors.length === 0) {
            return { match: false, distance: 1.0 };
        }
        
        var minDistance = 1.0;
        
        for (var i = 0; i < referenceDescriptors.length; i++) {
            var distance = faceapi.euclideanDistance(currentDescriptor, referenceDescriptors[i]);
            if (distance < minDistance) {
                minDistance = distance;
            }
        }
        
        var match = minDistance < threshold;
        
        return {
            match: match,
            distance: minDistance,
            similarity: (1 - minDistance) * 100
        };
    }
    
    /**
     * Verificar rostro capturado
     */
    async function verifyFace(imageElement) {
        try {
            $('#verification-status').html('<div class="alert alert-info">Analizando rostro...</div>');
            
            var detection = await faceapi
                .detectSingleFace(imageElement, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            if (!detection) {
                $('#verification-status').html('<div class="alert alert-warning">No se detectó ningún rostro. Por favor, asegúrate de que tu rostro esté bien iluminado y centrado en la cámara.</div>');
                return false;
            }
            
            var result = compareDescriptor(detection.descriptor);
            
            log.debug('camerasupervision: verification result - match: ' + result.match + ', distance: ' + result.distance);
            
            if (result.match) {
                $('#verification-status').html(
                    '<div class="alert alert-success">' +
                    '<strong>✓ Verificación exitosa</strong><br>' +
                    'Similitud: ' + result.similarity.toFixed(1) + '%<br>' +
                    'Puedes continuar con el examen.' +
                    '</div>'
                );
                
                // CRÍTICO: Actualizar el campo oculto
                $('#face_verification_passed').val('1');
                $('input[name="face_verification_passed"]').val('1');
                
                log.debug('camerasupervision: Updated face_verification_passed field to 1');
                
                verificationPassed = true;
                
                // Guardar evento de verificación exitosa
                await logVerificationEvent('success', result.similarity);
                
                return true;
            } else {
                $('#verification-status').html(
                    '<div class="alert alert-danger">' +
                    '<strong>✗ Verificación fallida</strong><br>' +
                    'No se pudo confirmar tu identidad (similitud: ' + result.similarity.toFixed(1) + '%).<br>' +
                    'Por favor, intenta de nuevo o contacta a tu profesor.' +
                    '</div>'
                );
                
                $('#face_verification_passed').val('0');
                $('input[name="face_verification_passed"]').val('0');
                verificationPassed = false;
                
                // Guardar evento de verificación fallida
                await logVerificationEvent('failed', result.similarity);
                
                return false;
            }
        } catch (err) {
            log.error('camerasupervision: error verifying face: ' + err);
            $('#verification-status').html('<div class="alert alert-danger">Error al verificar el rostro</div>');
            return false;
        }
    }
    
    /**
     * Registrar evento de verificación
     */
    async function logVerificationEvent(status, similarity) {
        try {
            var formData = new FormData();
            formData.append('eventtype', 'faceverification_' + status);
            formData.append('eventdata', 'Similitud: ' + similarity.toFixed(2) + '%');
            formData.append('sesskey', M.cfg.sesskey);
            
            await fetch(M.cfg.wwwroot + '/mod/quiz/accessrule/camerasupervision/log_preflight_event.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
        } catch (err) {
            log.error('camerasupervision: error logging verification event: ' + err);
        }
    }
    
    /**
     * Iniciar cámara para verificación
     */
    async function startVerificationCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }, 
                audio: false 
            });
            
            var video = document.getElementById('verification-video');
            video.srcObject = stream;
            
            $('#verification-camera-container').show();
            $('#btn-start-verification').hide();
            $('#btn-verify-face').show();
            $('#btn-stop-verification').show();
            
            return true;
        } catch (err) {
            log.error('camerasupervision: error starting verification camera: ' + err);
            notification.alert('Error', 'No se pudo acceder a la cámara: ' + err.name, 'OK');
            return false;
        }
    }
    
    /**
     * Detener cámara
     */
    function stopVerificationCamera() {
        if (stream) {
            stream.getTracks().forEach(function(track) {
                track.stop();
            });
            stream = null;
        }
        
        var video = document.getElementById('verification-video');
        if (video) {
            video.srcObject = null;
        }
        
        $('#verification-camera-container').hide();
        $('#btn-start-verification').show();
        $('#btn-verify-face').hide();
        $('#btn-stop-verification').hide();
    }
    
    /**
     * Capturar y verificar
     */
    async function captureAndVerify() {
        var video = document.getElementById('verification-video');
        var canvas = document.getElementById('verification-canvas');
        
        if (!video || !canvas) {
            notification.alert('Error', 'Elementos no encontrados', 'OK');
            return;
        }
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Mostrar imagen capturada
        $('#captured-image').attr('src', canvas.toDataURL('image/png')).show();
        
        // Verificar
        await verifyFace(canvas);
        
        // Detener cámara después de capturar
        stopVerificationCamera();
    }
    
    /**
     * Bloquear envío del formulario si no pasó verificación
     */
    function blockFormSubmission() {
        $('form[action*="startattempt.php"]').on('submit', function(e) {
            // Verificar el valor del campo justo antes de enviar
            var fieldValue = $('input[name="face_verification_passed"]').val();
            log.debug('camerasupervision: form submit - face_verification_passed = ' + fieldValue);
            
            if (fieldValue !== '1' && !verificationPassed) {
                e.preventDefault();
                notification.alert(
                    'Verificación requerida',
                    'Debes completar la verificación facial antes de iniciar el examen.',
                    'OK'
                );
                return false;
            }
        });
    }
    
    /**
     * Inicializar
     */
    return {
        init: async function(userid, thresholdValue) {
            log.debug('camerasupervision: faceverifier init for user ' + userid);
            
            threshold = thresholdValue || 0.6;
            
            // Cargar modelos
            var modelsLoaded = await loadModels();
            if (!modelsLoaded) {
                $('#verification-status').html('<div class="alert alert-danger">Error al cargar los modelos de reconocimiento facial</div>');
                return;
            }
            
            // Cargar descriptores de referencia
            var descriptorsLoaded = await loadReferenceDescriptors(userid);
            if (!descriptorsLoaded) {
                $('#verification-status').html(
                    '<div class="alert alert-warning">' +
                    'No se encontraron fotos de referencia para tu perfil.<br>' +
                    'Por favor, contacta a tu profesor para registrar tus fotos.' +
                    '</div>'
                );
                
                // Deshabilitar el formulario
                $('input[type="submit"]').prop('disabled', true);
                return;
            }
            
            $('#verification-status').html('<div class="alert alert-info">Sistema de verificación listo. Haz clic en "Iniciar cámara" para comenzar.</div>');
            
            // Event listeners
            $('#btn-start-verification').on('click', async function() {
                await startVerificationCamera();
            });
            
            $('#btn-stop-verification').on('click', function() {
                stopVerificationCamera();
            });
            
            $('#btn-verify-face').on('click', async function() {
                await captureAndVerify();
            });
            
            // Bloquear envío si no pasó verificación
            blockFormSubmission();
        }
    };
});
