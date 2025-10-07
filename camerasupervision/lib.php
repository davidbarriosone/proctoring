<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Sirve archivos (fotos) del plugin.
 */
function quizaccess_camerasupervision_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;
    
    // Para snapshots
    if ($filearea === 'snapshots') {
        if ($cm) {
            require_login($course, false, $cm);
            
            if ($context->contextlevel != CONTEXT_MODULE) {
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
            $attempt = $DB->get_record('quiz_attempts', ['id' => $itemid], '*', MUST_EXIST);
            $canview = has_capability('quizaccess/camerasupervision:view', $context) || ($attempt->userid == $USER->id);
            if (!$canview) {
                return false;
            }
            
            send_stored_file($file, 0, 0, $forcedownload, $options);
            return true;
        }
    }
    
    // Para fotos de referencia
    if ($filearea === 'faceref') {
        // Verificar que es contexto de sistema
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return false;
        }
        
        require_login();
        
        // Solo administradores o el propio usuario pueden ver sus fotos de referencia
        $itemid = (int)array_shift($args);
        $filename = array_pop($args);
        $filepath = '/' . (empty($args) ? '' : implode('/', $args) . '/');
        
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'quizaccess_camerasupervision', 'faceref', $itemid, $filepath, $filename);
        if (!$file || $file->is_directory()) {
            return false;
        }
        
        // Obtener el userid de la foto
        $facerecord = $DB->get_record('quizaccess_camsup_faces', ['id' => $itemid], 'userid', MUST_EXIST);
        
        // Solo admins o el propio usuario
        $canview = is_siteadmin() || ((int)$facerecord->userid === (int)$USER->id);
        if (!$canview) {
            return false;
        }
        
        send_stored_file($file, 0, 0, $forcedownload, $options);
        return true;
    }
    
    return false;
}
