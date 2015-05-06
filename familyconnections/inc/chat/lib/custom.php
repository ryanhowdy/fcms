<?php
/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license GNU Affero General Public License
 * @link https://blueimp.net/ajax/
 */

// Include custom libraries and initialization code here

$incPath = dirname(dirname(dirname(__FILE__))) . '/';
require_once $incPath.'config_inc.php';

$connection = mysqli_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
mysqli_select_db($connection, $cfg_mysql_db);
