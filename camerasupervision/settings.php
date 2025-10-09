<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('modsettingsquizcat', new admin_category(
        'quizaccess_camerasupervision',
        get_string('pluginname', 'quizaccess_camerasupervision')
    ));

    $ADMIN->add('quizaccess_camerasupervision', new admin_externalpage(
        'quizaccess_camerasupervision_faces',
        get_string('managefacephotos', 'quizaccess_camerasupervision'),
        new moodle_url('/mod/quiz/accessrule/camerasupervision/manage_faces.php'),
        'moodle/site:config'
    ));
}
