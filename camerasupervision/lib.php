<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Sirve archivos (fotos) del plugin.
 */
function quizaccess_camerasupervision_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_login($course, false, $cm);

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea !== 'snapshots') {
        return false;
    }

    // $args: first is itemid (attemptid), rest is path/filename.
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = '/' . (empty($args) ? '' : implode('/', $args) . '/');

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'quizaccess_camerasupervision', 'snapshots', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    // Verificación de permisos: profesores o el dueño del intento.
    global $DB, $USER;
    $attempt = $DB->get_record('quiz_attempts', ['id' => $itemid], '*', MUST_EXIST);
    $canview = has_capability('quizaccess/camerasupervision:view', $context) || ($attempt->userid == $USER->id);
    if (!$canview) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
