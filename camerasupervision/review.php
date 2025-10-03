<?php
require_once(__DIR__ . '/../../../../config.php');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('quizaccess/camerasupervision:view', $context);

$PAGE->set_url(new moodle_url('/mod/quiz/accessrule/camerasupervision/review.php', ['cmid' => $cmid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('supervisiontitle', 'quizaccess_camerasupervision'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('supervisiontitle', 'quizaccess_camerasupervision'));

$attempts = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id], 'timestart DESC');

$table = new html_table();
$table->head = [
    get_string('student', 'quizaccess_camerasupervision'),
    get_string('attempttime', 'quizaccess_camerasupervision'),
    get_string('photos', 'quizaccess_camerasupervision')
];

foreach ($attempts as $a) {
    $user = $DB->get_record('user', ['id' => $a->userid], 'id, firstname, lastname, email', MUST_EXIST);
    $name = fullname($user);
    $time = userdate($a->timestart) . ' — ' . ($a->timefinish ? userdate($a->timefinish) : '-');
    $url = new moodle_url('/mod/quiz/accessrule/camerasupervision/photos.php', ['attemptid' => $a->id]);
    $btn = html_writer::link($url, get_string('photos', 'quizaccess_camerasupervision'), ['class' => 'btn btn-secondary btn-sm']);
    $table->data[] = [format_string($name), $time, $btn];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
