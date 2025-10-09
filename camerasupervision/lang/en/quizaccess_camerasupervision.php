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

// Reconocimiento facial
$string['facerecognition'] = 'Enable facial recognition';
$string['facerecognition_help'] = 'If enabled, students must verify their identity through facial recognition before starting the attempt. Requires the administrator to have uploaded reference photos of the student.';
$string['facethreshold'] = 'Similarity threshold';
$string['facethreshold_help'] = 'Euclidean distance threshold to consider a facial match (0.0 to 1.0). Lower values are stricter. Recommended: 0.6';

// Preflight
$string['consentlabel'] = 'I agree to be supervised by camera during the attempt.';
$string['consentrequired'] = 'You must accept supervision to start the attempt.';
$string['supervisionbtn'] = 'Camera supervision';
$string['supervisiontitle'] = 'Camera supervision';
$string['legalnotice'] = 'By accepting, you authorize camera use and image storage for academic proctoring in compliance with applicable law.';

// Verificación facial en preflight
$string['faceverificationtitle'] = 'Identity verification';
$string['faceverificationhelp'] = 'Please verify your identity through facial recognition to start the exam.';
$string['startverificationcamera'] = 'Start camera';
$string['stopverificationcamera'] = 'Stop camera';
$string['verifyface'] = 'Verify face';
$string['verificationrequired'] = 'You must complete facial verification before starting the exam.';

// Información de detección en preflight
$string['detectiontitle'] = 'The following will be monitored:';
$string['detection_camera'] = 'Periodic camera snapshots (every 30 seconds)';
$string['detection_rightclick'] = 'Right-click usage';
$string['detection_tabchange'] = 'Tab or window changes';
$string['detection_appchange'] = 'Application switching';
$string['detection_facerecognition'] = 'Facial identity verification';

// Visualización
$string['photos'] = 'Photos';
$string['events'] = 'Events log';
$string['attempttime'] = 'Attempt time';
$string['student'] = 'Student';
$string['nofotos'] = 'No photos yet for this attempt.';
$string['totalfotos'] = 'Total photos';
$string['noevents'] = 'No events recorded for this attempt.';
$string['inprogress'] = 'In progress';

// Gestión de fotos de referencia
$string['managefacephotos'] = 'Manage reference photos';
$string['selectstudent'] = 'Select student';
$string['studentid'] = 'Student ID';
$string['studentinfo'] = 'Student information';
$string['existingphotos'] = 'Existing reference photos';
$string['nophotosyet'] = 'This student does not have reference photos yet.';
$string['uploadnewphoto'] = 'Upload new reference photo';
$string['uploadphotohelp'] = 'You can upload up to 3 photos of the student to improve facial recognition accuracy. Photos should clearly show the student\'s face with good lighting.';
$string['uploadfile'] = 'Upload file';
$string['capturewithcamera'] = 'Capture with camera';
$string['startcamera'] = 'Start camera';
$string['stopcamera'] = 'Stop camera';
$string['takephoto'] = 'Take photo';
$string['photo'] = 'Photo';
$string['maxphotos'] = 'This student already has the maximum of 3 reference photos.';
$string['photouploadsuccess'] = 'Photo uploaded successfully';
$string['photodeletesuccess'] = 'Photo deleted successfully';
$string['confirmdelete'] = 'Are you sure you want to delete this photo?';
$string['usernotfound'] = 'User not found';
$string['invalidimage'] = 'Invalid image or no face detected';
$string['noimage'] = 'No image provided';

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
$string['event_faceverification_success'] = 'Facial verification successful';
$string['event_faceverification_failed'] = 'Facial verification failed';

// Privacidad
$string['privacy:metadata'] = 'This plugin stores images and event logs associated with quiz attempts for proctoring purposes.';
$string['privacy:metadata:quizaccess_camsup_events'] = 'Event logs for quiz supervision';
$string['privacy:metadata:quizaccess_camsup_events:attemptid'] = 'The ID of the quiz attempt';
$string['privacy:metadata:quizaccess_camsup_events:userid'] = 'The ID of the user taking the quiz';
$string['privacy:metadata:quizaccess_camsup_events:eventtype'] = 'The type of event detected';
$string['privacy:metadata:quizaccess_camsup_events:eventdata'] = 'Additional information about the event';
$string['privacy:metadata:quizaccess_camsup_events:timecreated'] = 'When the event occurred';
$string['privacy:metadata:quizaccess_camsup_faces'] = 'Reference photos for facial recognition';
$string['privacy:metadata:quizaccess_camsup_faces:userid'] = 'The ID of the user the photo belongs to';
$string['privacy:metadata:quizaccess_camsup_faces:descriptor'] = 'Mathematical descriptor of the face (not the original image)';
$string['privacy:metadata:quizaccess_camsup_faces:timecreated'] = 'When the reference photo was created';

$string['snapshots_filearea'] = 'Supervision snapshots';
$string['faceref_filearea'] = 'Reference photos for facial recognition';
