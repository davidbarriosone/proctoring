<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

require_sesskey();
require_login();

$eventtype = required_param('eventtype', PARAM_TEXT);
$eventdata = required_param('eventdata', PARAM_TEXT);

global $DB, $USER;

// Guardar el evento en una tabla temporal o en la sesión
// Para eventos de preflight, los guardamos con attemptid = 0
$record = new stdClass();
$record->attemptid = 0;
$record->userid = $USER->id;
$record->eventtype = $eventtype;
$record->eventdata = $eventdata;
$record->timecreated = time();

$DB->insert_record('quizaccess_camsup_events', $record);

echo json_encode(['status' => 'ok']);
