<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

require_login();

$userid = required_param('userid', PARAM_INT);

global $DB, $USER;

// Verificar que el usuario solicita sus propios descriptores o es admin
if ((int)$userid !== (int)$USER->id && !is_siteadmin()) {
    throw new moodle_exception('nopermissions');
}

// Obtener descriptores de la BD
$records = $DB->get_records('quizaccess_camsup_faces', ['userid' => $userid], 'photoorder ASC', 'descriptor');

$descriptors = [];
foreach ($records as $record) {
    $descriptor = json_decode($record->descriptor);
    if ($descriptor && is_array($descriptor)) {
        $descriptors[] = $descriptor;
    }
}

if (empty($descriptors)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No reference photos found',
        'descriptors' => []
    ]);
} else {
    echo json_encode([
        'status' => 'ok',
        'descriptors' => $descriptors
    ]);
}
