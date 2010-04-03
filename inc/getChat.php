<?php
if (!isset($_SESSION)) {
    session_start();
}

include_once('config_inc.php');
include_once('util_inc.php');

// Disable caching
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
header("Cache-Control: no-cache, must-revalidate" ); 
header("Pragma: no-cache" );
header("Content-Type: text/xml; charset=utf-8");

$locale = new Locale();

// New user entered chat
if (isset($_POST['enter'])) {
    $sql = "DELETE FROM fcms_chat_users WHERE user_name = '" . escape_string($_POST['name']) .  "'";
    mysql_query($sql) or die($sql.'<br/>'.mysql_error());
    $sql = "INSERT INTO fcms_chat_users(user_name,time) VALUES ('" . escape_string($_POST['name']) .  "', NOW())";
    mysql_query($sql) or die($sql.'<br/>'.mysql_error());
}
// User left the chat
if (isset($_POST['exit'])) {
    $sql = "DELETE FROM fcms_chat_users WHERE user_name = '" . escape_string($_POST['name']) .  "'";
    mysql_query($sql) or die(mysql_error());
}
// New Chat text
if (isset($_POST['message']) && $_POST['message'] != '') {
    $sql = "INSERT INTO fcms_chat_messages(chat_id, user_id, user_name, message, post_time) 
            VALUES (1, " . escape_string($_POST['user_id']) . ", '" . escape_string($_POST['name']) .  "', '" . escape_string($_POST['message']) . "', NOW())";
    mysql_query($sql) or die(mysql_error());
    $sql = "DELETE FROM fcms_chat_users WHERE user_name = '" . escape_string($_POST['name']) .  "'";
    mysql_query($sql) or die($sql.'<br/>'.mysql_error());
    $sql = "INSERT INTO fcms_chat_users(user_name,time) VALUES ('" . escape_string($_POST['name']) .  "', NOW())";
    mysql_query($sql) or die($sql.'<br/>'.mysql_error());
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
    // Timezone stuff
    $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = ".escape_string($_GET['user_id']);
    $t_result = mysql_query($sql) or die($sql.'<br/>'.mysql_error());
    $t = mysql_fetch_array($t_result);
    $tz_offset = $t['timezone'];
    $now = $locale->fixDate('YmdHis', $tz_offset);
    $sql = "SELECT * FROM fcms_chat_users"; 
    $users_query = mysql_query($sql); 
    while($users_array = mysql_fetch_array($users_query)) {
        $date = $locale->fixDate('YmdHis', $tz_offset, $users_array['time']);
        if ($now - 12 <= $date) {
            $xml .= '<online>'; 
            $xml .= '<user>' . htmlspecialchars($users_array['user_name']) . '</user>'; 
            $xml .= '<time>' . $date . '</time>'; 
            $xml .= '</online>'; 
        } else {
            $sql = "INSERT INTO fcms_chat_messages(chat_id, user_id, user_name, message, post_time) 
                    VALUES (1, 0, '" . $users_array['user_name'] .  "', '".$users_array['user_name']." left.', NOW())";
            mysql_query($sql) or die(mysql_error());
            $sql = "DELETE FROM fcms_chat_users WHERE user_name = '" . $users_array['user_name'] .  "'";
            mysql_query($sql) or die($sql.'<br/>'.mysql_error());
        }
    }
}
// Get Chat Text
if (isset($_GET['chat'])) {
    // Update the status of the current user
    $name = isset($_GET['name']) ? escape_string($_GET['name']) : escape_string($_POST['name']);
    $sql = "UPDATE fcms_chat_users SET time = NOW() WHERE user_name = '$name'";
    mysql_query($sql) or die(mysql_error());

    $last = (isset($_GET['last']) && $_GET['last'] != '') ? $_GET['last'] : 0;
    $first = (isset($_GET['first'])) ? true : false;
    if ($first) {
        $sql = "SELECT * FROM (
                    SELECT message_id, user_id, user_name, message, date_format(post_time, '%h:%i') as post_time 
                    FROM fcms_chat_messages WHERE chat_id = 1 
                    AND message_id > " . escape_string($last) . "
                    ORDER BY message_id DESC
                    LIMIT 2
                ) AS sub 
                ORDER BY message_id";
    } else {
        $sql = "SELECT message_id, user_id, user_name, message, date_format(post_time, '%h:%i') as post_time 
                FROM fcms_chat_messages WHERE chat_id = 1 
                AND message_id > " . escape_string($last);
    }
    $message_query = mysql_query($sql);

    //Now create the XML Message Node
    while($message_array = mysql_fetch_array($message_query)) {
        $xml .= '<message id="' . $message_array['message_id'] . '">';
        if ($message_array['user_id'] < 1) {
            $xml .= '<user>system</user>';
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
