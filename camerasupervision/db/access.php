<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'quizaccess/camerasupervision:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'teacher' => CAP_ALLOW
        ]
    ],
];
