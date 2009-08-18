<?php

include_once('config_inc.php');
include_once('util_inc.php');
include_once('language.php');

// Disable caching
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
header("Cache-Control: no-cache, must-revalidate" ); 
header("Pragma: no-cache" );
header("Content-Type: text/xml; charset=utf-8");

// New user entered chat
if (isset($_POST['enter'])) {
	$sql = "INSERT INTO fcms_chat_users(user_name) VALUES ('" . $_POST['name'] .  "')";
	mysql_query($sql) or die(mysql_error());
}
// New user entered chat
if (isset($_POST['exit'])) {
	$sql = "DELETE FROM fcms_chat_users WHERE user_name = '" . $_POST['name'] .  "'";
	mysql_query($sql) or die(mysql_error());
}
// New Chat text
if (isset($_POST['message']) && $_POST['message'] != '') {
	$sql = "INSERT INTO fcms_chat_messages(chat_id, user_id, user_name, message, post_time) 
		    VALUES (1, " . $_POST['user_id'] . ", '" . $_POST['name'] .  "', '" . $_POST['message'] . "', NOW())";
	mysql_query($sql) or die(mysql_error());
}
// Reset the chat
if (isset($_POST['action']) && $_POST['action'] == 'reset') {
	$sql = "DELETE FROM fcms_chat_messages WHERE chat_id = 1";
	mysql_query($sql);
}

//Create the XML response.
$xml = '<?xml version="1.0" ?><root>';
// Get Users Online
if (isset($_GET['users'])) { 
    $sql = "SELECT user_name FROM fcms_chat_users"; 
    $users_query = mysql_query($sql); 
    while($users_array = mysql_fetch_array($users_query)) {
        $xml .= '<online>'; 
        $xml .= '<user>' . htmlspecialchars($users_array['user_name']) . '</user>'; 
        $xml .= '</online>'; 
    }
}
// Get Chat Text
if (isset($_GET['chat'])) {
	$last = (isset($_GET['last']) && $_GET['last'] != '') ? $_GET['last'] : 0;
	$sql = "SELECT message_id, user_id, user_name, message, date_format(post_time, '%h:%i') as post_time" .  " 
		    FROM fcms_chat_messages WHERE chat_id = 1 
	 	    AND message_id > " . $last;
	$message_query = mysql_query($sql);

	//Now create the XML Message Node
	while($message_array = mysql_fetch_array($message_query)) {
		$xml .= '<message id="' . $message_array['message_id'] . '">';
        if ($message_array['user_id'] < 1) {
		    $xml .= '<user>:: </user>';
        } else {
		    $xml .= '<user>' . htmlspecialchars($message_array['user_name']) . '</user>';
        }
		$xml .= '<text>' . htmlspecialchars($message_array['message']) . '</text>';
		$xml .= '<time>' . $message_array['post_time'] . '</time>';
		$xml .= '</message>';
	}
}
$xml .= '</root>';
echo $xml;
?>
