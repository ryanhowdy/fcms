<?php

include_once('config_inc.php');
include_once('util_inc.php');
include_once('language.php');


//Disable caching
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
header("Cache-Control: no-cache, must-revalidate" ); 
header("Pragma: no-cache" );
header("Content-Type: text/xml; charset=utf-8");

//Refresh of the browser window
if(isset($_POST['message']) && $_POST['message'] != '') {
	// Get User ID from cookie and User name from database
	$sql = "INSERT INTO fcms_chat_messages(chat_id, user_id, user_name, message, post_time) 
		VALUES (" .  $_GET['chat'] . ", 1, '" . $_POST['name'] .  "', '" . $_POST['message'] . "', NOW())";
	mysql_query($sql) or displaySQLError('New Thread Error', 'chat.php [' . __LINE__ . ']', $sql, mysql_error());

}

//Check to see if a reset request was sent.
if(isset($_POST['action']) && $_POST['action'] == 'reset') {
	$sql = "DELETE FROM fcms_chat_messages WHERE chat_id = " . $_GET['chat'];
	mysql_query($sql) or displaySQLError('New Thread Error', 'chat.php [' . __LINE__ . ']', $sql, mysql_error());
}

//Create the XML response.
$xml = '<?xml version="1.0" ?><root>';
// If someone comes here by hand reject them since they are not particiating in a "known" chat room.
if(!isset($_GET['chat'])) {
	$xml .='Your are not currently in a chat session.  <a href="">Enter a chat session here</a>';
	$xml .= '<message id="0">';
	$xml .= '<user>Admin</user>';
	$xml .= '<text>Your are not currently in a chat session.  &lt;a href=""&gt;Enter a chat session here&lt;/a&gt;</text>';
	$xml .= '<time>' . date('h:i') . '</time>';
	$xml .= '</message>';
} else {
	$last = (isset($_GET['last']) && $_GET['last'] != '') ? $_GET['last'] : 0;
	$sql = "SELECT message_id, user_name, message, date_format(post_time, '%h:%i') as post_time" .  " 
		FROM fcms_chat_messages WHERE chat_id = " . $_GET['chat'] . " 
		AND message_id > " . $last;
	$message_query = mysql_query($sql) or displaySQLError('New Thread Error', 'chat.php [' . __LINE__ . ']', $sql, mysql_error());;

	//Now create the XML Message Node
	while($message_array = mysql_fetch_array($message_query)) {
		$xml .= '<message id="' . $message_array['message_id'] . '">';
		$xml .= '<user>' . htmlspecialchars($message_array['user_name']) . '</user>';
		$xml .= '<text>' . htmlspecialchars($message_array['message']) . '</text>';
		$xml .= '<time>' . $message_array['post_time'] . '</time>';
		$xml .= '</message>';
	}
}
$xml .= '</root>';
echo $xml;
?>
