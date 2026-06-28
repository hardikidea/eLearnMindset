<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype = getenv('MOODLE_DB_TYPE') ?: 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost = getenv('MOODLE_DB_HOST') ?: 'db';
$CFG->dbname = getenv('POSTGRES_DB') ?: 'moodle';
$CFG->dbuser = getenv('POSTGRES_USER') ?: 'moodle';
$CFG->dbpass = getenv('POSTGRES_PASSWORD') ?: '';
$CFG->prefix = getenv('MOODLE_DB_PREFIX') ?: 'mdl_';
$CFG->dboptions = [
    'dbpersist' => 0,
    'dbport' => getenv('MOODLE_DB_PORT') ?: '5432',
    'dbsocket' => '',
];

$CFG->wwwroot = getenv('MOODLE_WWWROOT') ?: 'http://localhost:8080';
$CFG->dataroot = getenv('MOODLE_DATAROOT') ?: '/var/www/moodledata';
$CFG->directorypermissions = 02777;
$CFG->admin = 'admin';
$CFG->routerconfigured = true;

if ((getenv('MOODLE_SSLPROXY') ?: 'false') === 'true') {
    $CFG->sslproxy = true;
}

if ((getenv('MOODLE_REVERSEPROXY') ?: 'false') === 'true') {
    $CFG->reverseproxy = true;
}

require_once(__DIR__ . '/lib/setup.php');
