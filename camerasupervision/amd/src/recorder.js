// AMD module for Moodle 3.11
define(['core/log', 'core/notification'], function(log, notification) {

    function alertUser(msg) {
        try { notification.alert('Supervisión por cámara', msg, 'OK'); }
        catch (e) { window.alert(msg); }
    }

    function dataURLFromVideo(video, width, height) {
        var canvas = document.createElement('canvas');
        canvas.width = width || video.videoWidth;
        canvas.height = height || video.videoHeight;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        return canvas.toDataURL('image/png');
    }

    function logEvent(attemptid, eventType, eventData, logurl, sesskey) {
        var form = new FormData();
        form.append('attemptid', attemptid);
        form.append('eventtype', eventType);
        form.append('eventdata', eventData);
        form.append('sesskey', sesskey);

        fetch(logurl, {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        }).catch(function(err) {
            log.debug('camerasupervision: log event error ' + err);
        });
    }

    function setupEventDetection(attemptid, logurl, sesskey, settings) {
        var lastFocusTime = Date.now();
        var isPageVisible = true;

        // Detección de click derecho
        if (settings.detectRightClick) {
            document.addEventListener('contextmenu', function(e) {
                logEvent(attemptid, 'rightclick', 
                    'Click derecho detectado en: ' + (e.target.tagName || 'desconocido'),
                    logurl, sesskey);
                log.debug('camerasupervision: right click detected');
            });
        }

        // Detección de cambio de pestaña (Page Visibility API)
        if (settings.detectTabChange) {
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    isPageVisible = false;
                    logEvent(attemptid, 'tabchange', 
                        'Estudiante cambió de pestaña o minimizó el navegador',
                        logurl, sesskey);
                    log.debug('camerasupervision: tab changed - page hidden');
                } else {
                    var timeAway = Math.round((Date.now() - lastFocusTime) / 1000);
                    isPageVisible = true;
                    logEvent(attemptid, 'tabreturn', 
                        'Estudiante regresó a la pestaña después de ' + timeAway + ' segundos',
                        logurl, sesskey);
                    log.debug('camerasupervision: returned to tab');
                }
            });
        }

        // Detección de cambio de aplicación (window blur/focus)
        if (settings.detectAppChange) {
            window.addEventListener('blur', function() {
                lastFocusTime = Date.now();
                logEvent(attemptid, 'appchange', 
                    'Estudiante cambió a otra aplicación o ventana',
                    logurl, sesskey);
                log.debug('camerasupervision: window lost focus');
            });

            window.addEventListener('focus', function() {
                if (!isPageVisible) return; // Ya lo manejó visibilitychange
                var timeAway = Math.round((Date.now() - lastFocusTime) / 1000);
                if (timeAway > 2) { // Filtrar cambios muy cortos
                    logEvent(attemptid, 'appreturn', 
                        'Estudiante regresó después de ' + timeAway + ' segundos',
                        logurl, sesskey);
                    log.debug('camerasupervision: window regained focus');
                }
            });
        }

        // Detectar intento de abrir DevTools (F12, Ctrl+Shift+I, etc.)
        if (settings.detectRightClick) { // Reutilizamos esta configuración
            document.addEventListener('keydown', function(e) {
                // F12
                if (e.keyCode === 123) {
                    logEvent(attemptid, 'devtools', 
                        'Intento de abrir herramientas de desarrollador (F12)',
                        logurl, sesskey);
                    e.preventDefault();
                }
                // Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C, Ctrl+U
                if (e.ctrlKey && e.shiftKey && 
                    (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) {
                    logEvent(attemptid, 'devtools', 
                        'Intento de abrir herramientas de desarrollador (atajo de teclado)',
                        logurl, sesskey);
                    e.preventDefault();
                }
                if (e.ctrlKey && e.keyCode === 85) {
                    logEvent(attemptid, 'viewsource', 
                        'Intento de ver código fuente (Ctrl+U)',
                        logurl, sesskey);
                    e.preventDefault();
                }
            });
        }
    }

    function startSnapshots(attemptid, saveurl, logurl, sesskey, settings) {
        var intervalMs = 30000; // 30 segundos

        // 1) Entorno no seguro o sin API => avisar y salir.
        if (!window.isSecureContext) {
            alertUser('No se pudo activar la cámara porque el sitio no usa HTTPS. ' +
                      'Por seguridad del navegador, la cámara solo puede usarse en HTTPS o localhost.');
            log.debug('camerasupervision: insecure context (no HTTPS)');
            return;
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alertUser('Este navegador no soporta acceso a cámara (getUserMedia no disponible).');
            log.debug('camerasupervision: mediaDevices/getUserMedia not available');
            return;
        }

        var video = document.createElement('video');
        video.setAttribute('autoplay', true);
        video.setAttribute('playsinline', true);
        video.style.position = 'fixed';
        video.style.bottom = '-9999px';
        video.style.left = '-9999px';
        document.body.appendChild(video);

        var streamRef = null;
        var timerRef = null;

        function stopAll() {
            try {
                if (timerRef) { clearInterval(timerRef); }
                if (streamRef) { streamRef.getTracks().forEach(function(t){ t.stop(); }); }
                if (video && video.parentNode) { video.parentNode.removeChild(video); }
            } catch (e) { /* ignore */ }
        }
        window.addEventListener('beforeunload', stopAll);

        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(function(stream) {
                streamRef = stream;
                video.srcObject = stream;

                // Log inicial
                logEvent(attemptid, 'camerastart', 
                    'Cámara iniciada correctamente',
                    logurl, sesskey);

                timerRef = setInterval(function() {
                    try {
                        if (!video.videoWidth || !video.videoHeight) { return; }
                        var img = dataURLFromVideo(video);
                        var form = new FormData();
                        form.append('attemptid', attemptid);
                        form.append('image', img);
                        form.append('sesskey', sesskey);

                        fetch(saveurl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: form
                        }).catch(function(err) {
                            log.debug('camerasupervision: upload error ' + err);
                        });
                    } catch (e) {
                        log.debug('camerasupervision: snapshot error ' + e);
                    }
                }, intervalMs);
            })
            .catch(function(err) {
                var reason = err && err.name ? err.name : ('' + err);
                alertUser('No se pudo activar la cámara: ' + reason +
                          '. Revisa permisos del navegador o usa HTTPS.');
                log.debug('camerasupervision: cannot start camera ' + reason);
                
                // Log del error
                logEvent(attemptid, 'cameraerror', 
                    'Error al iniciar cámara: ' + reason,
                    logurl, sesskey);
            });
    }

    return {
        /**
         * @param {Number} attemptid
         * @param {String} saveurl URL para guardar snapshots
         * @param {String} logurl URL para guardar eventos
         * @param {String} sesskey
         * @param {Object} settings Configuración de detección
         */
        init: function(attemptid, saveurl, logurl, sesskey, settings) {
            try {
                if (!document.body.classList.contains('path-mod-quiz')) { return; }
                
                // Configurar detección de eventos
                setupEventDetection(attemptid, logurl, sesskey, settings);
                
                // Iniciar capturas de cámara
                setTimeout(function() {
                    startSnapshots(attemptid, saveurl, logurl, sesskey, settings);
                }, 1200);
            } catch (e) {
                log.debug('camerasupervision init error: ' + e);
            }
        }
    };
});
