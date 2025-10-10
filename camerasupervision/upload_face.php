<?php
require_once(__DIR__ . '/../../../../config.php');

require_sesskey();

$userid = required_param('userid', PARAM_INT);
$image = optional_param('image', '', PARAM_RAW);
$descriptor = required_param('descriptor', PARAM_RAW);

global $DB, $USER;

$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Verificar que el usuario existe
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// Contar fotos existentes
$photocount = $DB->count_records('quizaccess_camsup_faces', ['userid' => $userid]);
if ($photocount >= 3) {
    $error = get_string('maxphotos', 'quizaccess_camerasupervision');
    
    // Detectar si es AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => $error]);
        exit;
    } else {
        redirect(new moodle_url('/mod/quiz/accessrule/camerasupervision/manage_faces.php', ['userid' => $userid]),
            $error,
            null,
            \core\output\notification::NOTIFY_ERROR);
    }
}

$photoorder = $photocount + 1;
$data = null;

// Opción 1: Archivo subido
if (isset($_FILES['photofile']) && $_FILES['photofile']['error'] === UPLOAD_ERR_OK) {
    $data = file_get_contents($_FILES['photofile']['tmp_name']);
    $filename = clean_filename($_FILES['photofile']['name']);
    
    // Verificar que es una imagen
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimetype = $finfo->buffer($data);
    if (!in_array($mimetype, ['image/jpeg', 'image/png', 'image/jpg'])) {
        $error = get_string('invalidimage', 'quizaccess_camerasupervision');
        
        // Detectar si es AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        } else {
            redirect(new moodle_url('/mod/quiz/accessrule/camerasupervision/manage_faces.php', ['userid' => $userid]),
                $error,
                null,
                \core\output\notification::NOTIFY_ERROR);
        }
    }
}
// Opción 2: Captura de cámara (base64)
else if (!empty($image)) {
    if (preg_match('/^data:image\/png;base64,/', $image)) {
        $image = substr($image, strpos($image, ',') + 1);
    }
    $data = base64_decode($image);
    if ($data === false) {
        $error = get_string('invalidimage', 'quizaccess_camerasupervision');
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        } else {
            redirect(new moodle_url('/mod/quiz/accessrule/camerasupervision/manage_faces.php', ['userid' => $userid]),
                $error,
                null,
                \core\output\notification::NOTIFY_ERROR);
        }
    }
    $filename = 'capture_' . time() . '.png';
}
else {
    $error = get_string('noimage', 'quizaccess_camerasupervision');
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => $error]);
        exit;
    } else {
        redirect(new moodle_url('/mod/quiz/accessrule/camerasupervision/manage_faces.php', ['userid' => $userid]),
            $error,
            null,
            \core\output\notification::NOTIFY_ERROR);
    }
}

// Guardar registro en BD
$record = new stdClass();
$record->userid = $userid;
$record->photoorder = $photoorder;
$record->descriptor = $descriptor;
$record->timecreated = time();
$record->createdby = $USER->id;

$faceid = $DB->insert_record('quizaccess_camsup_faces', $record);

// Guardar imagen en File API
$fs = get_file_storage();
$filerecord = [
    'contextid' => $context->id,
    'component' => 'quizaccess_camerasupervision',
    'filearea'  => 'faceref',
    'itemid'    => $faceid,
    'filepath'  => '/',
    'filename'  => $filename
];

$fs->create_file_from_string($filerecord, $data);

$successmsg = get_string('photouploadsuccess', 'quizaccess_camerasupervision');

// Detectar si es AJAX (captura de cámara)
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode([
        'status' => 'ok',
        'message' => $successmsg,
        'faceid' => $faceid
    ]);
} else {
    // Si es form normal (subida de archivo), redirigir con mensaje amigable
    redirect(new moodle_url('/mod/quiz/accessrule/camerasupervision/manage_faces.php', ['userid' => $userid]),
        $successmsg . ' (Foto #' . $photoorder . ' de ' . fullname($user) . ')',
        null,
        \core\output\notification::NOTIFY_SUCCESS);
}
