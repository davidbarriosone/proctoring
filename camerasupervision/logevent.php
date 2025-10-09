<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

require_sesskey();

$attemptid = required_param('attemptid', PARAM_INT);
$eventtype = required_param('eventtype', PARAM_TEXT);
$eventdata = required_param('eventdata', PARAM_TEXT);

global $DB, $USER;

// Verifica intento y cm.
$attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('quiz', $attempt->quiz, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);

if ((int)$attempt->userid !== (int)$USER->id) {
    throw new required_capability_exception(context_module::instance($cm->id), 'mod/quiz:attempt', 'nopermissions', '');
}

// Guarda el evento en la base de datos
$record = new stdClass();
$record->attemptid = $attemptid;
$record->userid = $USER->id;
$record->eventtype = $eventtype;
$record->eventdata = $eventdata;
$record->timecreated = time();

$DB->insert_record('quizaccess_camsup_events', $record);

// Respuesta
echo json_encode(['status' => 'ok', 'eventtype' => $eventtype]);
