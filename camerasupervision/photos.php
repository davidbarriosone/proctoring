<?php
require_once(__DIR__ . '/../../../../config.php');

$attemptid = required_param('attemptid', PARAM_INT);

global $DB;

$attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('quiz', $attempt->quiz, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url(new moodle_url('/mod/quiz/accessrule/camerasupervision/photos.php', ['attemptid' => $attemptid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('photos', 'quizaccess_camerasupervision'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

// Información del estudiante
$user = $DB->get_record('user', ['id' => $attempt->userid], '*', MUST_EXIST);
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('student', 'quizaccess_camerasupervision') . ': ' . fullname($user), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('attempttime', 'quizaccess_camerasupervision') . ': ' . 
    userdate($attempt->timestart) . ' — ' . ($attempt->timefinish ? userdate($attempt->timefinish) : get_string('inprogress', 'quizaccess_camerasupervision')),
    ['class' => 'card-text']);
echo html_writer::end_div();
echo html_writer::end_div();

// Tabs para separar fotos y eventos
echo html_writer::start_tag('ul', ['class' => 'nav nav-tabs', 'role' => 'tablist']);

echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link('#photos', get_string('photos', 'quizaccess_camerasupervision'), [
    'class' => 'nav-link active',
    'data-toggle' => 'tab',
    'role' => 'tab'
]);
echo html_writer::end_tag('li');

echo html_writer::start_tag('li', ['class' => 'nav-item']);
echo html_writer::link('#events', get_string('events', 'quizaccess_camerasupervision'), [
    'class' => 'nav-link',
    'data-toggle' => 'tab',
    'role' => 'tab'
]);
echo html_writer::end_tag('li');

echo html_writer::end_tag('ul');

// Contenido de los tabs
echo html_writer::start_div('tab-content mt-3');

// =================== TAB DE FOTOS ===================
echo html_writer::start_div('tab-pane fade show active', ['id' => 'photos', 'role' => 'tabpanel']);
echo html_writer::tag('h4', get_string('photos', 'quizaccess_camerasupervision'));

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'quizaccess_camerasupervision', 'snapshots', $attemptid, 'filename', false);

if (empty($files)) {
    echo html_writer::div(get_string('nofotos', 'quizaccess_camerasupervision'), 'alert alert-info');
} else {
    echo html_writer::div(get_string('totalfotos', 'quizaccess_camerasupervision') . ': ' . count($files), 'alert alert-success mb-3');
    echo html_writer::start_div('row');
    
    foreach ($files as $file) {
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
        
        // Extraer timestamp del nombre del archivo
        $filename = $file->get_filename();
        $timestamp = '';
        if (preg_match('/^(\d{8})_(\d{6})/', $filename, $matches)) {
            $datestr = $matches[1] . $matches[2];
            $timestamp = userdate(strtotime($datestr), '%d/%m/%Y %H:%M:%S');
        }
        
        echo html_writer::start_div('col-md-3 mb-3');
        echo html_writer::start_div('card');
        echo html_writer::tag('img', '', [
            'src' => $url,
            'class' => 'card-img-top',
            'style' => 'cursor: pointer;',
            'onclick' => 'window.open(this.src, "_blank");',
            'alt' => 'Snapshot'
        ]);
        if ($timestamp) {
            echo html_writer::div($timestamp, 'card-body text-center small');
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    
    echo html_writer::end_div();
}

echo html_writer::end_div(); // Fin tab fotos

// =================== TAB DE EVENTOS ===================
echo html_writer::start_div('tab-pane fade', ['id' => 'events', 'role' => 'tabpanel']);
echo html_writer::tag('h4', get_string('events', 'quizaccess_camerasupervision'));

$events = $DB->get_records('quizaccess_camsup_events', ['attemptid' => $attemptid], 'timecreated DESC');

if (empty($events)) {
    echo html_writer::div(get_string('noevents', 'quizaccess_camerasupervision'), 'alert alert-info');
} else {
    
    // ========== ESTADÍSTICAS DE EVENTOS ==========
    $eventstats = [];
    foreach ($events as $event) {
        if (!isset($eventstats[$event->eventtype])) {
            $eventstats[$event->eventtype] = 0;
        }
        $eventstats[$event->eventtype]++;
    }
    
    echo html_writer::start_div('row mb-4');
    
    foreach ($eventstats as $type => $count) {
        $badgeclass = 'badge-secondary';
        
        // Clasificar por severidad
        if (in_array($type, ['tabchange', 'appchange', 'rightclick'])) {
            $badgeclass = 'badge-warning';
        } elseif (in_array($type, ['cameraerror', 'devtools', 'viewsource'])) {
            $badgeclass = 'badge-danger';
        } elseif ($type === 'camerastart') {
            $badgeclass = 'badge-success';
        }
        
        echo html_writer::start_div('col-md-3 mb-2');
        echo html_writer::start_div('card');
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('h5', $count, ['class' => 'card-title']);
        echo html_writer::tag('span', get_string('event_' . $type, 'quizaccess_camerasupervision'), 
            ['class' => 'badge ' . $badgeclass]);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    
    echo html_writer::end_div();
    
    // ========== TIMELINE DE EVENTOS ==========
    echo html_writer::start_div('timeline');
    
    foreach ($events as $event) {
        $iconclass = 'fa-info-circle';
        $alertclass = 'alert-secondary';
        
        // Iconos y colores según tipo de evento
        switch ($event->eventtype) {
            case 'camerastart':
                $iconclass = 'fa-video';
                $alertclass = 'alert-success';
                break;
            case 'cameraerror':
                $iconclass = 'fa-exclamation-triangle';
                $alertclass = 'alert-danger';
                break;
            case 'tabchange':
            case 'tabreturn':
                $iconclass = 'fa-window-restore';
                $alertclass = 'alert-warning';
                break;
            case 'appchange':
            case 'appreturn':
                $iconclass = 'fa-desktop';
                $alertclass = 'alert-warning';
                break;
            case 'rightclick':
                $iconclass = 'fa-mouse-pointer';
                $alertclass = 'alert-warning';
                break;
            case 'devtools':
            case 'viewsource':
                $iconclass = 'fa-code';
                $alertclass = 'alert-danger';
                break;
        }
        
        echo html_writer::start_div('alert ' . $alertclass . ' timeline-item');
        echo html_writer::start_div('d-flex justify-content-between align-items-start');
        
        // Contenido del evento
        echo html_writer::start_div('');
        echo html_writer::tag('i', '', ['class' => 'fa ' . $iconclass . ' mr-2']);
        echo html_writer::tag('strong', get_string('event_' . $event->eventtype, 'quizaccess_camerasupervision'));
        echo html_writer::tag('p', $event->eventdata, ['class' => 'mb-0 mt-1']);
        echo html_writer::end_div();
        
        // Timestamp
        echo html_writer::tag('small', userdate($event->timecreated, '%d/%m/%Y %H:%M:%S'), ['class' => 'text-muted']);
        
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    
    echo html_writer::end_div(); // Fin timeline
}

echo html_writer::end_div(); // Fin tab eventos

echo html_writer::end_div(); // Fin tab-content

// Script para mejorar la navegación entre tabs
echo html_writer::script("
require(['jquery'], function($) {
    $('.nav-link[data-toggle=\"tab\"]').on('click', function(e) {
        e.preventDefault();
        $(this).tab('show');
    });
});
");

echo $OUTPUT->footer();
