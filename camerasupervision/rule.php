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

    /** 
     * Clave de sesión temporal para el consentimiento actual.
     * Usa un timestamp que expira para permitir nuevos intentos.
     */
    protected function session_key(): string {
        global $USER;
        return 'quizaccess_camerasupervision_' . $this->quizobj->get_quizid() . '_user_' . $USER->id;
    }

    protected function has_preflight_passed(): bool {
        $key = $this->session_key();
        if (empty($_SESSION[$key])) {
            return false;
        }
        
        // Verificar si el consentimiento no ha expirado (válido por 5 minutos)
        $timestamp = $_SESSION[$key];
        $elapsed = time() - $timestamp;
        
        // Si pasaron más de 5 minutos, expiró (nuevo intento)
        if ($elapsed > 300) {
            unset($_SESSION[$key]);
            return false;
        }
        
        return true;
    }

    protected function set_preflight_passed(): void {
        $_SESSION[$this->session_key()] = time();
    }
    
    protected function clear_preflight(): void {
        unset($_SESSION[$this->session_key()]);
    }

    public function __construct(quiz $quizobj, $timenow, $settings) {
        parent::__construct($quizobj, $timenow);
        $this->enabled = (bool)$settings->enabled;
        $this->detectrightclick = (bool)($settings->detectrightclick ?? false);
        $this->detecttabchange = (bool)($settings->detecttabchange ?? false);
        $this->detectappchange = (bool)($settings->detectappchange ?? false);
    }

    /** Crear la regla solo si está habilitada para este quiz. */
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
        // Header para la sección
        $mform->addElement('header', 'camerasupervisionheader',
            get_string('pluginname', 'quizaccess_camerasupervision'));

        // Opción principal: habilitar supervisión por cámara
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

        // Cargar valores guardados si ya existe
        global $DB;
        if (!empty($quizform->get_current()->instance)) {
            $quizid = $quizform->get_current()->instance;
            if ($rec = $DB->get_record('quizaccess_camsup', ['quizid' => $quizid])) {
                $mform->setDefault('camerasupervision_enabled', (int)$rec->enabled);
                $mform->setDefault('camerasupervision_detectrightclick', (int)($rec->detectrightclick ?? 0));
                $mform->setDefault('camerasupervision_detecttabchange', (int)($rec->detecttabchange ?? 0));
                $mform->setDefault('camerasupervision_detectappchange', (int)($rec->detectappchange ?? 0));
            }
        }
    }

    public static function validate_settings_form_fields(array $errors, array $data, $files, mod_quiz_mod_form $quizform) {
        return $errors;
    }

    public static function save_settings($quiz) {
        global $DB;
        $enabled = empty($quiz->camerasupervision_enabled) ? 0 : 1;
        $detectrightclick = empty($quiz->camerasupervision_detectrightclick) ? 0 : 1;
        $detecttabchange = empty($quiz->camerasupervision_detecttabchange) ? 0 : 1;
        $detectappchange = empty($quiz->camerasupervision_detectappchange) ? 0 : 1;

        if ($rec = $DB->get_record('quizaccess_camsup', ['quizid' => $quiz->id])) {
            $rec->enabled = $enabled;
            $rec->detectrightclick = $detectrightclick;
            $rec->detecttabchange = $detecttabchange;
            $rec->detectappchange = $detectappchange;
            $rec->timemodified = time();
            $DB->update_record('quizaccess_camsup', $rec);
        } else {
            $rec = (object)[
                'quizid'            => $quiz->id,
                'enabled'           => $enabled,
                'detectrightclick'  => $detectrightclick,
                'detecttabchange'   => $detecttabchange,
                'detectappchange'   => $detectappchange,
                'timecreated'       => time(),
                'timemodified'      => time(),
            ];
            $DB->insert_record('quizaccess_camsup', $rec);
        }
    }

    /* =================== PREFLIGHT (CONSENTIMIENTO) =================== */

    /** 
     * Requerir consentimiento solo si no se ha dado recientemente
     */
    public function is_preflight_check_required($attemptid) {
        if (!$this->enabled) { 
            return false; 
        }
        
        // Verificar si ya pasó el preflight recientemente
        return !$this->has_preflight_passed();
    }

    public function add_preflight_check_form_fields(mod_quiz_preflight_check_form $quizform, MoodleQuickForm $mform, $attemptid) {
        $mform->addElement('header', 'camsupheader', get_string('supervisiontitle', 'quizaccess_camerasupervision'));
        $mform->addElement('static', 'camsupnotice', '', get_string('legalnotice', 'quizaccess_camerasupervision'));

        // Información sobre qué se va a detectar
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

        if (count($detectioninfo) > 0) {
            $mform->addElement('static', 'detectionlist', 
                get_string('detectiontitle', 'quizaccess_camerasupervision'),
                html_writer::alist($detectioninfo));
        }

        $mform->addElement('advcheckbox', 'camerasupervisionconsent',
            get_string('consentlabel', 'quizaccess_camerasupervision'));
        $mform->setType('camerasupervisionconsent', PARAM_BOOL);
        $mform->setDefault('camerasupervisionconsent', 0);
    }

    /** 
     * Validar y marcar el consentimiento
     */
    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        $accepted = false;
        
        if (isset($data['camerasupervisionconsent'])) {
            $accepted = (bool)$data['camerasupervisionconsent'];
        }
        
        if ($accepted) {
            // Marcar el consentimiento con timestamp actual
            $this->set_preflight_passed();
        } else {
            $errors['camerasupervisionconsent'] = get_string('consentrequired', 'quizaccess_camerasupervision');
        }
        
        return $errors;
    }
    
    /**
     * Se llama cuando el intento termina - limpiar el consentimiento
     */
    public function current_attempt_finished() {
        // Limpiar el consentimiento para que pida nuevamente en el próximo intento
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

        // Verificar el estado del intento
        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], 'state', IGNORE_MISSING);
        if (!$attempt) {
            return;
        }

        // Solo activar la cámara si el intento está EN PROGRESO
        // Estados posibles: 'inprogress', 'finished', 'abandoned', 'overdue'
        if ($attempt->state !== 'inprogress') {
            return; // No cargar JS si el intento ya terminó
        }

        // Verificar que estamos en la página de attempt, no en review o summary
        $pagepath = $page->url->get_path();
        
        // Solo cargar en attempt.php, NO en review.php ni summary.php
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
