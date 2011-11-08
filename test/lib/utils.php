<?php

function connectDatabase ()
{
    global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

    $connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
    mysql_select_db($cfg_mysql_db);

    if (!mysql_query("SET NAMES 'utf8'"))
    {
        displaySQLError('UTF8 Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
    }
}
