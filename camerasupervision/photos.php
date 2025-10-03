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
echo $OUTPUT->heading(get_string('photos', 'quizaccess_camerasupervision'));

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'quizaccess_camerasupervision', 'snapshots', $attemptid, 'filename', false);

if (empty($files)) {
    echo html_writer::div(get_string('nofotos', 'quizaccess_camerasupervision'), 'alert alert-info');
} else {
    echo html_writer::start_div('d-flex flex-wrap gap-3');
    foreach ($files as $file) {
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
        echo html_writer::tag('img', '', ['src' => $url, 'style' => 'max-width:220px; height:auto; border:1px solid #ccc; padding:4px;']);
    }
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
