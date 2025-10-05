<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_quizaccess_camerasupervision_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025081805) {
        
        // Agregar nuevos campos a la tabla quizaccess_camsup
        $table = new xmldb_table('quizaccess_camsup');
        
        $field = new xmldb_field('detectrightclick', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enabled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $field = new xmldb_field('detecttabchange', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'detectrightclick');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $field = new xmldb_field('detectappchange', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'detecttabchange');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Crear nueva tabla para eventos
        $table = new xmldb_table('quizaccess_camsup_events');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('eventtype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('eventdata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('attemptid_fk', XMLDB_KEY_FOREIGN, ['attemptid'], 'quiz_attempts', ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        
        $table->add_index('attemptid_idx', XMLDB_INDEX_NOTUNIQUE, ['attemptid']);
        $table->add_index('eventtype_idx', XMLDB_INDEX_NOTUNIQUE, ['eventtype']);
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025081805, 'quizaccess', 'camerasupervision');
    }

    return true;
}
