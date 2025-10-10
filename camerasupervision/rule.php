<?php
// mod/quiz/accessrule/camerasupervision/rule.php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

class quizaccess_camerasupervision extends quiz_access_rule_base {

    /** @var bool */
    protected $enabled = false;
    
    /** @var bool */
    protected $detectrightclick = false;
    
    /** @var bool */
    protected $detecttabchange = false;
    
    /** @var bool */
    protected $detectappchange = false;
    
    /** @var bool */
    protected $facerecognition = false;
    
    /** @var float */
    protected $facethreshold = 0.6;

    protected function session_key(): string {
        global $USER;
        return 'quizaccess_camerasupervision_' . $this->quizobj->get_quizid() . '_user_' . $USER->id;
    }

    protected function has_preflight_passed(): bool {
        global $SESSION, $USER, $DB;
        
        // Asegurar que $SESSION existe
        if (!isset($SESSION)) {
            return false;
        }
        
        $key = $this->session_key();
        
        // Verificar si existe en la sesión de Moodle
        if (!isset($SESSION->{$key})) {
            // ALTERNATIVA: Verificar en la base de datos si ya aceptó el consentimiento
            $attemptid = optional_param('attempt', 0, PARAM_INT);
            if ($attemptid > 0) {
                // Verificar si existe un evento de consentimiento para este intento
                $consent = $DB->get_record('quizaccess_camsup_events', [
                    'attemptid' => $attemptid,
                    'userid' => $USER->id,
                    'eventtype' => 'consent_accepted'
                ]);
                
                if ($consent) {
                    // Restaurar la sesión
                    $SESSION->{$key} = $consent->timecreated;
                    return true;
                }
            }
            
            return false;
        }
        
        $timestamp = $SESSION->{$key};
        $elapsed = time() - $timestamp;
        
        // Aumentar el tiempo de validez a 24 horas
        if ($elapsed > 86400) {
            unset($SESSION->{$key});
            return false;
        }
        
        return true;
    }

    protected function set_preflight_passed(): void {
        global $SESSION, $USER, $DB;
        
        // Asegurar que $SESSION existe
        if (!isset($SESSION)) {
            $SESSION = new stdClass();
        }
        
        $key = $this->session_key();
        $SESSION->{$key} = time();
        
        // GUARDAR EN BD como respaldo
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if ($attemptid > 0) {
            // Verificar si ya existe
            $existing = $DB->get_record('quizaccess_camsup_events', [
                'attemptid' => $attemptid,
                'userid' => $USER->id,
                'eventtype' => 'consent_accepted'
            ]);
            
            if (!$existing) {
                $record = new stdClass();
                $record->attemptid = $attemptid;
                $record->userid = $USER->id;
                $record->eventtype = 'consent_accepted';
                $record->eventdata = 'User accepted camera supervision consent';
                $record->timecreated = time();
                $DB->insert_record('quizaccess_camsup_events', $record);
            }
        }
    }
    
    protected function clear_preflight(): void {
        global $SESSION;
        
        if (isset($SESSION)) {
            $key = $this->session_key();
            unset($SESSION->{$key});
        }
    }

    public function __construct(quiz $quizobj, $timenow, $settings) {
        parent::__construct($quizobj, $timenow);
        $this->enabled = (bool)$settings->enabled;
        $this->detectrightclick = (bool)($settings->detectrightclick ?? false);
        $this->detecttabchange = (bool)($settings->detecttabchange ?? false);
        $this->detectappchange = (bool)($settings->detectappchange ?? false);
        $this->facerecognition = (bool)($settings->facerecognition ?? false);
        $this->facethreshold = (float)($settings->facethreshold ?? 0.6);
    }

    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        global $DB;
        if ($rec = $DB->get_record('quizaccess_camsup', ['quizid' => $quizobj->get_quizid()])) {
            if ((int)$rec->enabled === 1) {
                return new self($quizobj, $timenow, $rec);
            }
        }
        return null;
    }

    /* =================== AJUSTES EN EL FORMULARIO DEL QUIZ =================== */

    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $mform->addElement('header', 'camerasupervisionheader',
            get_string('pluginname', 'quizaccess_camerasupervision'));

        $mform->addElement('advcheckbox', 'camerasupervision_enabled',
            get_string('enabled', 'quizaccess_camerasupervision'));
        $mform->addHelpButton('camerasupervision_enabled', 'enabled', 'quizaccess_camerasupervision');

        // Opciones de detección
        $mform->addElement('advcheckbox', 'camerasupervision_detectrightclick',
            get_string('detectrightclick', 'quizaccess_camerasupervision'));
        $mform->addHelpButton('camerasupervision_detectrightclick', 'detectrightclick', 'quizaccess_camerasupervision');
        $mform->disabledIf('camerasupervision_detectrightclick', 'camerasupervision_enabled');

        $mform->addElement('advcheckbox', 'camerasupervision_detecttabchange',
            get_string('detecttabchange', 'quizaccess_camerasupervision'));
        $mform->addHelpButton('camerasupervision_detecttabchange', 'detecttabchange', 'quizaccess_camerasupervision');
        $mform->disabledIf('camerasupervision_detecttabchange', 'camerasupervision_enabled');

        $mform->addElement('advcheckbox', 'camerasupervision_detectappchange',
            get_string('detectappchange', 'quizaccess_camerasupervision'));
        $mform->addHelpButton('camerasupervision_detectappchange', 'detectappchange', 'quizaccess_camerasupervision');
        $mform->disabledIf('camerasupervision_detectappchange', 'camerasupervision_enabled');

        // Reconocimiento facial
        $mform->addElement('advcheckbox', 'camerasupervision_facerecognition',
            get_string('facerecognition', 'quizaccess_camerasupervision'));
        $mform->addHelpButton('camerasupervision_facerecognition', 'facerecognition', 'quizaccess_camerasupervision');
        $mform->disabledIf('camerasupervision_facerecognition', 'camerasupervision_enabled');

        $mform->addElement('text', 'camerasupervision_facethreshold',
            get_string('facethreshold', 'quizaccess_camerasupervision'), ['size' => 10]);
        $mform->setType('camerasupervision_facethreshold', PARAM_FLOAT);
        $mform->setDefault('camerasupervision_facethreshold', 0.6);
        $mform->addHelpButton('camerasupervision_facethreshold', 'facethreshold', 'quizaccess_camerasupervision');
        $mform->disabledIf('camerasupervision_facethreshold', 'camerasupervision_facerecognition');

        // Cargar valores guardados
        global $DB;
        if (!empty($quizform->get_current()->instance)) {
            $quizid = $quizform->get_current()->instance;
            if ($rec = $DB->get_record('quizaccess_camsup', ['quizid' => $quizid])) {
                $mform->setDefault('camerasupervision_enabled', (int)$rec->enabled);
                $mform->setDefault('camerasupervision_detectrightclick', (int)($rec->detectrightclick ?? 0));
                $mform->setDefault('camerasupervision_detecttabchange', (int)($rec->detecttabchange ?? 0));
                $mform->setDefault('camerasupervision_detectappchange', (int)($rec->detectappchange ?? 0));
                $mform->setDefault('camerasupervision_facerecognition', (int)($rec->facerecognition ?? 0));
                $mform->setDefault('camerasupervision_facethreshold', (float)($rec->facethreshold ?? 0.6));
            }
        }
    }

    public static function validate_settings_form_fields(array $errors, array $data, $files, mod_quiz_mod_form $quizform) {
        // Validar threshold
        if (!empty($data['camerasupervision_facerecognition'])) {
            $threshold = $data['camerasupervision_facethreshold'] ?? 0.6;
            if ($threshold < 0 || $threshold > 1) {
                $errors['camerasupervision_facethreshold'] = get_string('error');
            }
        }
        return $errors;
    }

    public static function save_settings($quiz) {
        global $DB;
        $enabled = empty($quiz->camerasupervision_enabled) ? 0 : 1;
        $detectrightclick = empty($quiz->camerasupervision_detectrightclick) ? 0 : 1;
        $detecttabchange = empty($quiz->camerasupervision_detecttabchange) ? 0 : 1;
        $detectappchange = empty($quiz->camerasupervision_detectappchange) ? 0 : 1;
        $facerecognition = empty($quiz->camerasupervision_facerecognition) ? 0 : 1;
        $facethreshold = isset($quiz->camerasupervision_facethreshold) ? (float)$quiz->camerasupervision_facethreshold : 0.6;

        if ($rec = $DB->get_record('quizaccess_camsup', ['quizid' => $quiz->id])) {
            $rec->enabled = $enabled;
            $rec->detectrightclick = $detectrightclick;
            $rec->detecttabchange = $detecttabchange;
            $rec->detectappchange = $detectappchange;
            $rec->facerecognition = $facerecognition;
            $rec->facethreshold = $facethreshold;
            $rec->timemodified = time();
            $DB->update_record('quizaccess_camsup', $rec);
        } else {
            $rec = (object)[
                'quizid'            => $quiz->id,
                'enabled'           => $enabled,
                'detectrightclick'  => $detectrightclick,
                'detecttabchange'   => $detecttabchange,
                'detectappchange'   => $detectappchange,
                'facerecognition'   => $facerecognition,
                'facethreshold'     => $facethreshold,
                'timecreated'       => time(),
                'timemodified'      => time(),
            ];
            $DB->insert_record('quizaccess_camsup', $rec);
        }
    }

    /* =================== PREFLIGHT (CONSENTIMIENTO + VERIFICACIÓN FACIAL) =================== */

    public function is_preflight_check_required($attemptid) {
        if (!$this->enabled) {
            return false; 
        }
        
        $passed = $this->has_preflight_passed();
        
        return !$passed;
    }

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

    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        global $DB, $USER;
        
        $accepted = false;
        
        if (isset($data['camerasupervisionconsent'])) {
            $accepted = (bool)$data['camerasupervisionconsent'];
        }
        
        if (!$accepted) {
            $errors['camerasupervisionconsent'] = get_string('consentrequired', 'quizaccess_camerasupervision');
        }
        
        // Si está habilitado el reconocimiento facial, verificar que pasó la verificación
        if ($this->facerecognition && $accepted) {
            $verificationPassed = isset($data['face_verification_passed']) ? (int)$data['face_verification_passed'] : 0;
            
            if ($verificationPassed !== 1) {
                $errors['face_verification_passed'] = get_string('verificationrequired', 'quizaccess_camerasupervision');
            } else {
                // Verificar que tiene fotos de referencia
                $hasPhotos = $DB->count_records('quizaccess_camsup_faces', ['userid' => $USER->id]);
                
                if ($hasPhotos < 1) {
                    $errors['face_verification_passed'] = 'No reference photos found for verification';
                }
            }
        }
        
        // Solo marcar como pasado si NO hay errores
        if (empty($errors)) {
            $this->set_preflight_passed();
        }
        
        return $errors;
    }
    
    public function current_attempt_finished() {
        $this->clear_preflight();
    }

    /* =================== UI: BOTÓN DE SUPERVISIÓN =================== */

    public function description() {
        if (!$this->enabled) {
            return '';
        }
        $context = context_module::instance($this->quizobj->get_cmid());
        if (has_capability('quizaccess/camerasupervision:view', $context)) {
            $url = new moodle_url('/mod/quiz/accessrule/camerasupervision/review.php',
                ['cmid' => $this->quizobj->get_cmid()]);
            $btn = html_writer::link($url, get_string('supervisionbtn', 'quizaccess_camerasupervision'),
                ['class' => 'btn btn-secondary']);
            return html_writer::div($btn);
        }
        return '';
    }

    /* =================== CARGA DEL JS EN EL INTENTO =================== */

    public function setup_attempt_page($page) {
        global $DB;
        
        if (!$this->enabled) { 
            return; 
        }
        
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if (!$attemptid) { 
            return; 
        }

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], 'state', IGNORE_MISSING);
        if (!$attempt) {
            return;
        }

        if ($attempt->state !== 'inprogress') {
            return;
        }

        $pagepath = $page->url->get_path();
        
        if (strpos($pagepath, '/mod/quiz/attempt.php') === false) {
            return;
        }

        $saveurl = new moodle_url('/mod/quiz/accessrule/camerasupervision/snapshot.php');
        $logurl = new moodle_url('/mod/quiz/accessrule/camerasupervision/logevent.php');
        
        $settings = [
            'detectRightClick' => $this->detectrightclick,
            'detectTabChange' => $this->detecttabchange,
            'detectAppChange' => $this->detectappchange,
        ];
        
        $page->requires->js_call_amd('quizaccess_camerasupervision/recorder', 'init', [
            (int)$attemptid,
            $saveurl->out(false),
            $logurl->out(false),
            sesskey(),
            $settings
        ]);
    }
}
