<?php
// mod/quiz/accessrule/camerasupervision/rule.php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

class quizaccess_camerasupervision extends quiz_access_rule_base {

    /** @var bool */
    protected $enabled = false;

    /** Clave de sesión para recordar el preflight aceptado. */
    protected function session_key(): string {
        return 'quizaccess_camerasupervision_' . $this->quizobj->get_quizid();
    }

    protected function has_preflight_passed(): bool {
        return !empty($_SESSION[$this->session_key()]);
    }

    protected function set_preflight_passed(): void {
        $_SESSION[$this->session_key()] = 1;
    }

    public function __construct(quiz $quizobj, $timenow, $enabled) {
        parent::__construct($quizobj, $timenow);
        $this->enabled = (bool)$enabled;
    }

    /** Crear la regla solo si está habilitada para este quiz. */
    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        global $DB;
        if ($rec = $DB->get_record('quizaccess_camsup', ['quizid' => $quizobj->get_quizid()])) {
            if ((int)$rec->enabled === 1) {
                return new self($quizobj, $timenow, true);
            }
        }
        return null;
    }

    /* =================== AJUSTES EN EL FORMULARIO DEL QUIZ =================== */

    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $mform->addElement('advcheckbox', 'camerasupervision_enabled',
            get_string('enabled', 'quizaccess_camerasupervision'));
        $mform->addHelpButton('camerasupervision_enabled', 'enabled', 'quizaccess_camerasupervision');

        // Cargar el valor guardado si ya existe.
        global $DB;
        if (!empty($quizform->get_current()->instance)) {
            $quizid = $quizform->get_current()->instance;
            if ($rec = $DB->get_record('quizaccess_camsup', ['quizid' => $quizid])) {
                $mform->setDefault('camerasupervision_enabled', (int)$rec->enabled);
            }
        }
    }

    public static function validate_settings_form_fields(array $errors, array $data, $files, mod_quiz_mod_form $quizform) {
        return $errors;
    }

    public static function save_settings($quiz) {
        global $DB;
        $enabled = empty($quiz->camerasupervision_enabled) ? 0 : 1;

        if ($rec = $DB->get_record('quizaccess_camsup', ['quizid' => $quiz->id])) {
            $rec->enabled = $enabled;
            $rec->timemodified = time();
            $DB->update_record('quizaccess_camsup', $rec);
        } else {
            $rec = (object)[
                'quizid'       => $quiz->id,
                'enabled'      => $enabled,
                'timecreated'  => time(),
                'timemodified' => time(),
            ];
            $DB->insert_record('quizaccess_camsup', $rec);
        }
    }

    /* =================== PREFLIGHT (CONSENTIMIENTO) =================== */

    /** Solo requerir si está activo y aún no está aceptado en sesión. */
    public function is_preflight_check_required($attemptid) {
        if (!$this->enabled) { return false; }
        return !$this->has_preflight_passed();
    }

    public function add_preflight_check_form_fields(mod_quiz_preflight_check_form $quizform, MoodleQuickForm $mform, $attemptid) {
        $mform->addElement('header', 'camsupheader', get_string('supervisiontitle', 'quizaccess_camerasupervision'));
        $mform->addElement('static', 'camsupnotice', '', get_string('legalnotice', 'quizaccess_camerasupervision'));

        $mform->addElement('advcheckbox', 'camerasupervisionconsent',
            get_string('consentlabel', 'quizaccess_camerasupervision'));
        $mform->setType('camerasupervisionconsent', PARAM_BOOL);
        $mform->setDefault('camerasupervisionconsent', 0);

        // Importante: sin reglas del lado del cliente, para no bloquear el submit.
        // (La validación real la hace validate_preflight_check()).
    }

    /** Validación robusta y marca de sesión al aceptar. */
    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        $accepted = 0;
        if (is_object($data)) {
            $accepted = !empty($data->camerasupervisionconsent);
        } else if (is_array($data)) {
            $accepted = !empty($data['camerasupervisionconsent'] ?? null);
        }
        if (!$accepted) {
            // Respaldo: lee del POST por si el formulario llegó “plano”.
            $accepted = optional_param('camerasupervisionconsent', 0, PARAM_BOOL) ? 1 : 0;
        }

        if ($accepted) {
            // Evita el “bucle” del preflight en la misma petición.
            $this->set_preflight_passed();
        } else {
            $errors['camerasupervisionconsent'] = get_string('consentrequired', 'quizaccess_camerasupervision');
        }
        return $errors;
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
        if (!$this->enabled) { return; }
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if (!$attemptid) { return; }

        $saveurl = new moodle_url('/mod/quiz/accessrule/camerasupervision/snapshot.php');
        $page->requires->js_call_amd('quizaccess_camerasupervision/recorder', 'init', [
            (int)$attemptid,
            $saveurl->out(false),
            sesskey()
        ]);
    }
}