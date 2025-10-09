<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

require_sesskey();

$attemptid = required_param('attemptid', PARAM_INT);
$image     = required_param('image', PARAM_RAW); // dataURL base64 "data:image/png;base64,..."

global $DB, $USER;

// Verifica intento y cm.
$attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('quiz', $attempt->quiz, 0, false, MUST_EXIST);
require_login($cm->course, false, $cm);

if ((int)$attempt->userid !== (int)$USER->id) {
    throw new required_capability_exception(context_module::instance($cm->id), 'mod/quiz:attempt', 'nopermissions', '');
}

// Decodifica base64.
if (preg_match('/^data:image\/png;base64,/', $image)) {
    $image = substr($image, strpos($image, ',') + 1);
}
$data = base64_decode($image);
if ($data === false) {
    throw new moodle_exception('Invalid image data');
}

// Guarda en File API.
$context = context_module::instance($cm->id);
$fs = get_file_storage();
$filename = userdate(time(), '%Y%m%d_%H%M%S') . '_' . $USER->id . '.png';

$filerecord = [
    'contextid' => $context->id,
    'component' => 'quizaccess_camerasupervision',
    'filearea'  => 'snapshots',
    'itemid'    => $attemptid,
    'filepath'  => '/',
    'filename'  => $filename
];

$fs->create_file_from_string($filerecord, $data);

// Respuesta.
echo json_encode(['status' => 'ok', 'filename' => $filename]);
