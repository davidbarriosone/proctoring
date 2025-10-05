<?php
$string['pluginname'] = 'Camera supervision';
$string['enabled'] = 'Enable camera supervision';
$string['enabled_help'] = 'If enabled, the student must consent to camera supervision; snapshots will be taken every 30 seconds during the attempt.';

// Opciones de detección
$string['detectrightclick'] = 'Detect right-click';
$string['detectrightclick_help'] = 'If enabled, right-click events will be logged during the quiz attempt.';
$string['detecttabchange'] = 'Detect tab changes';
$string['detecttabchange_help'] = 'If enabled, tab or window changes will be logged during the quiz attempt.';
$string['detectappchange'] = 'Detect application changes';
$string['detectappchange_help'] = 'If enabled, application or window focus changes will be logged during the quiz attempt.';

// Preflight
$string['consentlabel'] = 'I agree to be supervised by camera during the attempt.';
$string['consentrequired'] = 'You must accept supervision to start the attempt.';
$string['supervisionbtn'] = 'Camera supervision';
$string['supervisiontitle'] = 'Camera supervision';
$string['legalnotice'] = 'By accepting, you authorize camera use and image storage for academic proctoring in compliance with applicable law.';

// Información de detección en preflight
$string['detectiontitle'] = 'The following will be monitored:';
$string['detection_camera'] = 'Periodic camera snapshots (every 30 seconds)';
$string['detection_rightclick'] = 'Right-click usage';
$string['detection_tabchange'] = 'Tab or window changes';
$string['detection_appchange'] = 'Application switching';

// Visualización
$string['photos'] = 'Photos';
$string['events'] = 'Events log';
$string['attempttime'] = 'Attempt time';
$string['student'] = 'Student';
$string['nofotos'] = 'No photos yet for this attempt.';
$string['totalfotos'] = 'Total photos';
$string['noevents'] = 'No events recorded for this attempt.';
$string['inprogress'] = 'In progress';

// Tipos de eventos
$string['event_camerastart'] = 'Camera started';
$string['event_cameraerror'] = 'Camera error';
$string['event_rightclick'] = 'Right-click detected';
$string['event_tabchange'] = 'Left the page';
$string['event_tabreturn'] = 'Returned to page';
$string['event_appchange'] = 'Switched application';
$string['event_appreturn'] = 'Returned to application';
$string['event_devtools'] = 'Developer tools attempt';
$string['event_viewsource'] = 'View source attempt';

// Privacidad
$string['privacy:metadata'] = 'This plugin stores images and event logs associated with quiz attempts for proctoring purposes.';
$string['privacy:metadata:quizaccess_camsup_events'] = 'Event logs for quiz supervision';
$string['privacy:metadata:quizaccess_camsup_events:attemptid'] = 'The ID of the quiz attempt';
$string['privacy:metadata:quizaccess_camsup_events:userid'] = 'The ID of the user taking the quiz';
$string['privacy:metadata:quizaccess_camsup_events:eventtype'] = 'The type of event detected';
$string['privacy:metadata:quizaccess_camsup_events:eventdata'] = 'Additional information about the event';
$string['privacy:metadata:quizaccess_camsup_events:timecreated'] = 'When the event occurred';

$string['snapshots_filearea'] = 'Supervision snapshots';
