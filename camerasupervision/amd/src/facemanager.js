// AMD module para gestión de fotos de referencia
define(['jquery', 'core/log', 'core/notification'], function($, log, notification) {
    
    var stream = null;
    var faceapi = null;
    
    /**
     * Cargar modelos de face-api
     */
    async function loadModels() {
        if (!window.faceapi) {
            notification.alert('Error', 'face-api.js no está cargado', 'OK');
            return false;
        }
        
        faceapi = window.faceapi;
        
        try {
            var modelPath = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/model';
            
            await faceapi.nets.tinyFaceDetector.loadFromUri(modelPath);
            await faceapi.nets.faceLandmark68Net.loadFromUri(modelPath);
            await faceapi.nets.faceRecognitionNet.loadFromUri(modelPath);
            
            log.debug('camerasupervision: face-api models loaded');
            return true;
        } catch (err) {
            log.error('camerasupervision: error loading face-api models: ' + err);
            notification.alert('Error', 'No se pudieron cargar los modelos de reconocimiento facial', 'OK');
            return false;
        }
    }
    
    /**
     * Detectar rostro y extraer descriptor
     */
    async function detectFaceDescriptor(imageElement) {
        try {
            var detection = await faceapi
                .detectSingleFace(imageElement, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            if (!detection) {
                return null;
            }
            
            return Array.from(detection.descriptor);
        } catch (err) {
            log.error('camerasupervision: error detecting face: ' + err);
            return null;
        }
    }
    
    /**
     * Iniciar cámara
     */
    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }, 
                audio: false 
            });
            
            var video = document.getElementById('camera-video');
            video.srcObject = stream;
            
            $('#camera-container').show();
            $('#btn-start-camera').hide();
            $('#btn-capture').show();
            $('#btn-stop-camera').show();
            
            return true;
        } catch (err) {
            log.error('camerasupervision: error starting camera: ' + err);
            notification.alert('Error', 'No se pudo acceder a la cámara: ' + err.name, 'OK');
            return false;
        }
    }
    
    /**
     * Detener cámara
     */
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function(track) {
                track.stop();
            });
            stream = null;
        }
        
        var video = document.getElementById('camera-video');
        if (video) {
            video.srcObject = null;
        }
        
        $('#camera-container').hide();
        $('#btn-start-camera').show();
        $('#btn-capture').hide();
        $('#btn-stop-camera').hide();
    }
    
    /**
     * Capturar foto y procesarla
     */
    async function capturePhoto() {
        var video = document.getElementById('camera-video');
        var canvas = document.getElementById('camera-canvas');
        
        if (!video || !canvas) {
            notification.alert('Error', 'Elementos no encontrados', 'OK');
            return;
        }
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        $('#capture-status').html('<div class="alert alert-info">Procesando imagen...</div>');
        
        // Detectar rostro
        var descriptor = await detectFaceDescriptor(canvas);
        
        if (!descriptor) {
            $('#capture-status').html('<div class="alert alert-danger">No se detectó ningún rostro en la imagen. Por favor, intenta de nuevo asegurándote de que tu rostro esté bien iluminado y centrado.</div>');
            return;
        }
        
        // Convertir a base64
        var imageData = canvas.toDataURL('image/png');
        
        // Enviar al servidor
        var userid = $('#capture-userid').val();
        var sesskey = $('#capture-sesskey').val();
        
        $('#capture-status').html('<div class="alert alert-info">Guardando foto...</div>');
        
        // Crear un formulario oculto y enviarlo (para que el servidor redirija)
        var form = $('<form>', {
            'method': 'post',
            'action': M.cfg.wwwroot + '/mod/quiz/accessrule/camerasupervision/upload_face.php'
        });
        
        form.append($('<input>', {'type': 'hidden', 'name': 'sesskey', 'value': sesskey}));
        form.append($('<input>', {'type': 'hidden', 'name': 'userid', 'value': userid}));
        form.append($('<input>', {'type': 'hidden', 'name': 'image', 'value': imageData}));
        form.append($('<input>', {'type': 'hidden', 'name': 'descriptor', 'value': JSON.stringify(descriptor)}));
        
        form.appendTo('body').submit();
    }
    
    /**
     * Procesar archivo subido
     */
    async function processUploadedFile(file) {
        return new Promise(function(resolve, reject) {
            var reader = new FileReader();
            
            reader.onload = async function(e) {
                var img = new Image();
                
                img.onload = async function() {
                    var descriptor = await detectFaceDescriptor(img);
                    
                    if (!descriptor) {
                        reject(new Error('No se detectó rostro en la imagen'));
                        return;
                    }
                    
                    resolve(descriptor);
                };
                
                img.onerror = function() {
                    reject(new Error('Error al cargar la imagen'));
                };
                
                img.src = e.target.result;
            };
            
            reader.onerror = function() {
                reject(new Error('Error al leer el archivo'));
            };
            
            reader.readAsDataURL(file);
        });
    }
    
    /**
     * Inicializar
     */
    return {
        init: async function() {
            log.debug('camerasupervision: facemanager init');
            
            // Cargar modelos
            var modelsLoaded = await loadModels();
            if (!modelsLoaded) {
                return;
            }
            
            // Event listeners
            $('#btn-start-camera').on('click', async function() {
                await startCamera();
            });
            
            $('#btn-stop-camera').on('click', function() {
                stopCamera();
            });
            
            $('#btn-capture').on('click', async function() {
                await capturePhoto();
            });
            
            // Interceptar envío de formulario de archivo
            $('form').on('submit', async function(e) {
                var fileInput = $('#photofile')[0];
                
                if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                    return true; // Dejar que el form se envíe normalmente
                }
                
                e.preventDefault();
                
                $('#capture-status').html('<div class="alert alert-info">Procesando imagen...</div>');
                
                try {
                    var descriptor = await processUploadedFile(fileInput.files[0]);
                    
                    // Agregar descriptor al formulario
                    var form = $(this);
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'descriptor',
                        value: JSON.stringify(descriptor)
                    }).appendTo(form);
                    
                    // Enviar formulario (el servidor redirigirá)
                    form.off('submit').submit();
                    
                } catch (err) {
                    $('#capture-status').html('<div class="alert alert-danger">' + err.message + '</div>');
                }
                
                return false;
            });
        }
    };
});
