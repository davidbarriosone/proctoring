<?php
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('quizaccess_camerasupervision_faces');

$userid = optional_param('userid', 0, PARAM_INT);
$username = optional_param('username', '', PARAM_USERNAME);
$action = optional_param('action', '', PARAM_ALPHA);
$deleteid = optional_param('deleteid', 0, PARAM_INT);

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/mod/quiz/accessrule/camerasupervision/manage_faces.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('managefacephotos', 'quizaccess_camerasupervision'));
$PAGE->set_heading(get_string('managefacephotos', 'quizaccess_camerasupervision'));

// Cargar face-api.js
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js'), true);
$PAGE->requires->js_call_amd('quizaccess_camerasupervision/facemanager', 'init');

// Procesar eliminación
if ($action === 'delete' && $deleteid && confirm_sesskey()) {
    $DB->delete_records('quizaccess_camsup_faces', ['id' => $deleteid]);
    redirect($PAGE->url, get_string('photodeletesuccess', 'quizaccess_camerasupervision'), null, 
        \core\output\notification::NOTIFY_SUCCESS);
}

// Buscar usuario por username si se proporcionó
if (!empty($username) && $userid == 0) {
    $user = $DB->get_record('user', ['username' => $username], 'id', IGNORE_MISSING);
    if ($user) {
        $userid = $user->id;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managefacephotos', 'quizaccess_camerasupervision'));

// Formulario de búsqueda de usuario
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('selectstudent', 'quizaccess_camerasupervision'), ['class' => 'card-title']);

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out(false), 'class' => 'form-inline']);

// Campo para ID
echo html_writer::start_div('form-group mr-3 mb-2');
echo html_writer::label(get_string('studentid', 'quizaccess_camerasupervision'), 'userid', false, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'name' => 'userid',
    'id' => 'userid',
    'class' => 'form-control',
    'value' => $userid > 0 ? $userid : '',
    'placeholder' => 'ID',
    'style' => 'width: 100px;'
]);
echo html_writer::end_div();

// Campo para Username
echo html_writer::start_div('form-group mr-3 mb-2');
echo html_writer::label('Username', 'username', false, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'username',
    'id' => 'username',
    'class' => 'form-control',
    'value' => $username,
    'placeholder' => 'Username',
    'style' => 'width: 200px;'
]);
echo html_writer::end_div();

echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('search'), 'class' => 'btn btn-primary mb-2']);
echo html_writer::end_tag('form');

// Ayuda
echo html_writer::div(
    html_writer::tag('small', 'Puedes buscar por ID de usuario o por nombre de usuario (username)', ['class' => 'text-muted']),
    'mt-2'
);

echo html_writer::end_div();
echo html_writer::end_div();

// Si se seleccionó un usuario
if ($userid > 0) {
    $user = $DB->get_record('user', ['id' => $userid], '*', IGNORE_MISSING);
    
    if (!$user) {
        echo $OUTPUT->notification(get_string('usernotfound', 'quizaccess_camerasupervision'), 
            \core\output\notification::NOTIFY_ERROR);
    } else {
        echo html_writer::start_div('card mb-4');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', get_string('studentinfo', 'quizaccess_camerasupervision'), ['class' => 'card-title']);
        echo html_writer::tag('p', '<strong>' . get_string('fullname') . ':</strong> ' . fullname($user), ['class' => 'card-text']);
        echo html_writer::tag('p', '<strong>Username:</strong> ' . $user->username, ['class' => 'card-text']);
        echo html_writer::tag('p', '<strong>' . get_string('email') . ':</strong> ' . $user->email, ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Mostrar fotos existentes
        $photos = $DB->get_records('quizaccess_camsup_faces', ['userid' => $userid], 'photoorder ASC');
        
        echo html_writer::start_div('card mb-4');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', get_string('existingphotos', 'quizaccess_camerasupervision'), ['class' => 'card-title']);
        
        if (empty($photos)) {
            echo html_writer::div(get_string('nophotosyet', 'quizaccess_camerasupervision'), 'alert alert-info');
        } else {
            echo html_writer::start_div('row');
            foreach ($photos as $photo) {
                $fs = get_file_storage();
                $files = $fs->get_area_files($context->id, 'quizaccess_camerasupervision', 'faceref', 
                    $photo->id, 'filename', false);
                
                if (!empty($files)) {
                    $file = reset($files);
                    $url = moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                    );
                    
                    $deleteurl = new moodle_url($PAGE->url, [
                        'action' => 'delete',
                        'deleteid' => $photo->id,
                        'userid' => $userid,
                        'sesskey' => sesskey()
                    ]);
                    
                    echo html_writer::start_div('col-md-4 mb-3');
                    echo html_writer::start_div('card');
                    echo html_writer::tag('img', '', ['src' => $url, 'class' => 'card-img-top', 'alt' => 'Photo ' . $photo->photoorder]);
                    echo html_writer::start_div('card-body');
                    echo html_writer::tag('p', get_string('photo', 'quizaccess_camerasupervision') . ' ' . $photo->photoorder, 
                        ['class' => 'card-text text-center']);
                    echo html_writer::link($deleteurl, get_string('delete'), 
                        ['class' => 'btn btn-danger btn-sm btn-block', 
                         'onclick' => 'return confirm("' . get_string('confirmdelete', 'quizaccess_camerasupervision') . '");']);
                    echo html_writer::end_div();
                    echo html_writer::end_div();
                    echo html_writer::end_div();
                }
            }
            echo html_writer::end_div();
        }
        
        echo html_writer::end_div();
        echo html_writer::end_div();

        // Formulario para subir nuevas fotos (máximo 3)
        if (count($photos) < 3) {
            echo html_writer::start_div('card');
            echo html_writer::start_div('card-body');
            echo html_writer::tag('h5', get_string('uploadnewphoto', 'quizaccess_camerasupervision'), ['class' => 'card-title']);
            echo html_writer::tag('p', get_string('uploadphotohelp', 'quizaccess_camerasupervision'), ['class' => 'text-muted']);
            
            echo html_writer::start_div('row');
            
            // Opción 1: Subir archivo
            echo html_writer::start_div('col-md-6');
            echo html_writer::tag('h6', get_string('uploadfile', 'quizaccess_camerasupervision'));
            echo html_writer::start_tag('form', [
                'method' => 'post',
                'enctype' => 'multipart/form-data',
                'action' => new moodle_url('/mod/quiz/accessrule/camerasupervision/upload_face.php'),
                'id' => 'upload-form'
            ]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);
            echo html_writer::start_div('form-group');
            echo html_writer::empty_tag('input', [
                'type' => 'file',
                'name' => 'photofile',
                'id' => 'photofile',
                'class' => 'form-control-file',
                'accept' => 'image/*',
                'required' => true
            ]);
            echo html_writer::end_div();
            echo html_writer::empty_tag('input', [
                'type' => 'submit',
                'value' => get_string('upload'),
                'class' => 'btn btn-primary'
            ]);
            echo html_writer::end_tag('form');
            echo html_writer::end_div();
            
            // Opción 2: Capturar con cámara
            echo html_writer::start_div('col-md-6');
            echo html_writer::tag('h6', get_string('capturewithcamera', 'quizaccess_camerasupervision'));
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'id' => 'capture-userid', 'value' => $userid]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'id' => 'capture-sesskey', 'value' => sesskey()]);
            
            echo html_writer::start_div('', ['id' => 'camera-container', 'style' => 'display:none;']);
            echo html_writer::tag('video', '', [
                'id' => 'camera-video',
                'autoplay' => true,
                'playsinline' => true,
                'style' => 'width: 100%; max-width: 400px; border: 2px solid #ccc; border-radius: 8px;'
            ]);
            echo html_writer::end_div();
            
            echo html_writer::tag('canvas', '', [
                'id' => 'camera-canvas',
                'style' => 'display:none;'
            ]);
            
            echo html_writer::start_div('mt-2');
            echo html_writer::tag('button', get_string('startcamera', 'quizaccess_camerasupervision'), [
                'id' => 'btn-start-camera',
                'class' => 'btn btn-info'
            ]);
            echo html_writer::tag('button', get_string('takephoto', 'quizaccess_camerasupervision'), [
                'id' => 'btn-capture',
                'class' => 'btn btn-success ml-2',
                'style' => 'display:none;'
            ]);
            echo html_writer::tag('button', get_string('stopcamera', 'quizaccess_camerasupervision'), [
                'id' => 'btn-stop-camera',
                'class' => 'btn btn-danger ml-2',
                'style' => 'display:none;'
            ]);
            echo html_writer::end_div();
            
            echo html_writer::start_div('mt-3', ['id' => 'capture-status']);
            echo html_writer::end_div();
            
            echo html_writer::end_div();
            
            echo html_writer::end_div();
            
            echo html_writer::end_div();
            echo html_writer::end_div();
        } else {
            echo html_writer::div(get_string('maxphotos', 'quizaccess_camerasupervision'), 'alert alert-warning');
        }
    }
}

echo $OUTPUT->footer();
