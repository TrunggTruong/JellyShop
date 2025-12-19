<?php
// config.php generated automatically
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'raucaushop';

function db_connect(){
    global $DB_HOST,$DB_USER,$DB_PASS,$DB_NAME;
    $db = new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
    if($db->connect_errno){
        error_log('DB connect error: '.$db->connect_error);
        return null;
    }
    $db->set_charset('utf8mb4');
    return $db;
}
?>