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

    function startSnapshots(attemptid, saveurl, sesskey) {
        var intervalMs = 15000; // 15s

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
        video.style.bottom = '-9999px'; // oculto pero activo
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
                // Aquí sí mostramos el error al usuario.
                var reason = err && err.name ? err.name : ('' + err);
                alertUser('No se pudo activar la cámara: ' + reason +
                          '. Revisa permisos del navegador o usa HTTPS.');
                log.debug('camerasupervision: cannot start camera ' + reason);
            });
    }

    return {
        /**
         * @param {Number} attemptid
         * @param {String} saveurl
         * @param {String} sesskey
         */
        init: function(attemptid, saveurl, sesskey) {
            try {
                if (!document.body.classList.contains('path-mod-quiz')) { return; }
                setTimeout(function() {
                    startSnapshots(attemptid, saveurl, sesskey);
                }, 1200);
            } catch (e) {
                log.debug('camerasupervision init error: ' + e);
            }
        }
    };
});
