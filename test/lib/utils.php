<?php

define('TEST', dirname(dirname(__FILE__)) . '/');
define('INC', dirname(dirname(dirname(__FILE__))) . '/inc/');

function connectDatabase ()
{
    global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

    $connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
    mysql_select_db($cfg_mysql_db);

    if (!mysql_query("SET NAMES 'utf8'"))
    {
        displaySqlError($sql, mysql_error());
    }
}
