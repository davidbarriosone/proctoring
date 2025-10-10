public function add_preflight_check_form_fields(mod_quiz_preflight_check_form $quizform, MoodleQuickForm $mform, $attemptid) {
        global $USER, $PAGE;
        
        $mform->addElement('header', 'camsupheader', get_string('supervisiontitle', 'quizaccess_camerasupervision'));
        $mform->addElement('static', 'camsupnotice', '', get_string('legalnotice', 'quizaccess_camerasupervision'));

        // Información sobre detección
        $detectioninfo = [];
        $detectioninfo[] = get_string('detection_camera', 'quizaccess_camerasupervision');
        if ($this->detectrightclick) {
            $detectioninfo[] = get_string('detection_rightclick', 'quizaccess_camerasupervision');
        }
        if ($this->detecttabchange) {
            $detectioninfo[] = get_string('detection_tabchange', 'quizaccess_camerasupervision');
        }
        if ($this->detectappchange) {
            $detectioninfo[] = get_string('detection_appchange', 'quizaccess_camerasupervision');
        }
        if ($this->facerecognition) {
            $detectioninfo[] = get_string('detection_facerecognition', 'quizaccess_camerasupervision');
        }

        if (count($detectioninfo) > 0) {
            $mform->addElement('static', 'detectionlist', 
                get_string('detectiontitle', 'quizaccess_camerasupervision'),
                html_writer::alist($detectioninfo));
        }

        // Si está habilitado el reconocimiento facial, agregar interfaz
        if ($this->facerecognition) {
            // Cargar face-api.js
            $PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js'), true);
            
            // Cargar módulo AMD
            $PAGE->requires->js_call_amd('quizaccess_camerasupervision/faceverifier', 'init', [
                (int)$USER->id,
                (float)$this->facethreshold
            ]);
            
            $mform->addElement('header', 'faceverificationheader', get_string('faceverificationtitle', 'quizaccess_camerasupervision'));
            $mform->addElement('static', 'faceverificationhelp', '', get_string('faceverificationhelp', 'quizaccess_camerasupervision'));
            
            // Contenedor para la interfaz de verificación facial
            $html = html_writer::start_div('card mt-3 mb-3');
            $html .= html_writer::start_div('card-body');
            
            // Contenedor de la cámara (oculto inicialmente)
            $html .= html_writer::start_div('', ['id' => 'verification-camera-container', 'style' => 'display:none; text-align:center;']);
            $html .= html_writer::tag('video', '', [
                'id' => 'verification-video',
                'autoplay' => true,
                'playsinline' => true,
                'style' => 'width: 100%; max-width: 640px; border: 2px solid #007bff; border-radius: 8px; margin-bottom: 10px;'
            ]);
            $html .= html_writer::end_div();
            
            // Canvas oculto para captura (no se muestra nunca)
            $html .= html_writer::tag('canvas', '', [
                'id' => 'verification-canvas',
                'style' => 'display:none;'
            ]);
            
            // Ya NO incluimos el <img> de la imagen capturada
            
            // Botones
            $html .= html_writer::start_div('mt-2');
            $html .= html_writer::tag('button', get_string('startverificationcamera', 'quizaccess_camerasupervision'), [
                'id' => 'btn-start-verification',
                'type' => 'button',
                'class' => 'btn btn-primary'
            ]);
            $html .= ' ';
            $html .= html_writer::tag('button', get_string('verifyface', 'quizaccess_camerasupervision'), [
                'id' => 'btn-verify-face',
                'type' => 'button',
                'class' => 'btn btn-success',
                'style' => 'display:none;'
            ]);
            $html .= ' ';
            $html .= html_writer::tag('button', get_string('stopverificationcamera', 'quizaccess_camerasupervision'), [
                'id' => 'btn-stop-verification',
                'type' => 'button',
                'class' => 'btn btn-danger',
                'style' => 'display:none;'
            ]);
            $html .= html_writer::end_div();
            
            // Estado de verificación
            $html .= html_writer::start_div('mt-3', ['id' => 'verification-status']);
            $html .= html_writer::end_div();
            
            $html .= html_writer::end_div();
            $html .= html_writer::end_div();
            
            $mform->addElement('static', 'faceverificationui', '', $html);
            
            // Campo oculto para validar si pasó la verificación
            $mform->addElement('hidden', 'face_verification_passed', '0');
            $mform->setType('face_verification_passed', PARAM_INT);
        }

        // Consentimiento
        $mform->addElement('advcheckbox', 'camerasupervisionconsent',
            get_string('consentlabel', 'quizaccess_camerasupervision'));
        $mform->setType('camerasupervisionconsent', PARAM_BOOL);
        $mform->setDefault('camerasupervisionconsent', 0);
    }
