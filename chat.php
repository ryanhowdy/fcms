<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	$_POST = array_map('stripslashes', $_POST);
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');

if (isset($_SESSION['login_id'])) {
	if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
		displayLoginPage();
		exit();
	}
} elseif (isset($_COOKIE['fcms_login_id'])) {
	if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
		$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
		$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
		$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
	} else {
		displayLoginPage();
		exit();
	}
} else {
	displayLoginPage();
	exit();
}
header("Cache-control: private");

// Setup the Template variables;
$TMPL['pagetitle'] = $LANG['link_chat'];
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = <<<HTML
<script type="text/javascript">
//<![CDATA[
window.onload = startChat;
var sendReq = getXmlHttpRequestObject();
var receiveReq = getXmlHttpRequestObject();
var receiveReq2 = getXmlHttpRequestObject();
var lastMessage = 0;
var mTimer;
var username = '
HTML;
$TMPL['javascript'] .= getUserDisplayName($_SESSION['login_id'],2);
$TMPL['javascript'] .= <<<HTML
'
function startChat() {
    document.getElementById('txt_message').focus();
    sendEnterChatText();
    getChatText();
}		
function getXmlHttpRequestObject() {
    if (window.XMLHttpRequest) {
        return new XMLHttpRequest();
    } else if(window.ActiveXObject) {
        return new ActiveXObject("Microsoft.XMLHTTP");
    } else {
        document.getElementById('p_status').innerHTML = 'Status: Cound not create XmlHttpRequest Object.  Consider upgrading your browser.';
    }
}
function getChatText() {
    if (receiveReq.readyState == 4 || receiveReq.readyState == 0) {
        receiveReq.open("GET", 'inc/getChat.php?chat=text&last=' + lastMessage, true);
        receiveReq.onreadystatechange = handleReceiveChat; 
        receiveReq.send(null);
    }
    getUsers();
}
function getUsers() {
    if (receiveReq2.readyState == 4 || receiveReq2.readyState == 0) {
        receiveReq2.open("GET", 'inc/getChat.php?users=online', true);
        receiveReq2.onreadystatechange = handleReceiveUsers; 
        receiveReq2.send(null);
    }			
}
function sendEnterChatText() {
    if (sendReq.readyState == 4 || sendReq.readyState == 0) {
        sendReq.open("POST", 'inc/getChat.php?enter=chat&last=' + lastMessage, true);
        sendReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        sendReq.onreadystatechange = handleSendChat; 
        var param = 'message=' + username + ' entered the chat.';
        param += '&name=' + username;
        param += '&user_id=0';
        param += '&enter=chat';
        sendReq.send(param);
    }							
}
function sendChatText() {
    if(document.getElementById('txt_message').value == '') {
        alert("You have not entered a message");
        return;
    }
    if (sendReq.readyState == 4 || sendReq.readyState == 0) {
        sendReq.open("POST", 'inc/getChat.php?chat=text&last=' + lastMessage, true);
        sendReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        sendReq.onreadystatechange = handleSendChat; 
        var param = 'message=' + document.getElementById('txt_message').value;
        param += '&name=' + username;
        param += '&user_id=1';
        param += '&chat=text';
        sendReq.send(param);
        document.getElementById('txt_message').value = '';
    }							
}
function endChat() {
    if (sendReq.readyState == 4 || sendReq.readyState == 0) {
        sendReq.open("POST", 'inc/getChat.php?exit=chat', true);
        sendReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        sendReq.onreadystatechange = handleSendChat; 
        var param = 'message=' + username + ' left.';
        param += '&name=' + username;
        param += '&user_id=0';
        param += '&exit=chat';
        sendReq.send(param);
    }							
}
function handleSendChat() {
    clearInterval(mTimer);
    getChatText();
}
function handleReceiveChat() {
    if (receiveReq.readyState == 4) {
        var chat_div = document.getElementById('div_chat');
        var xmldoc = receiveReq.responseXML;
        var message_nodes = xmldoc.getElementsByTagName("message"); 
        var n_messages = message_nodes.length
        for (i = 0; i < n_messages; i++) {
            var user_node = message_nodes[i].getElementsByTagName("user");
            var text_node = message_nodes[i].getElementsByTagName("text");
            var time_node = message_nodes[i].getElementsByTagName("time");
            chat_div.innerHTML += '<span class="chat_info">'+ user_node[0].firstChild.nodeValue + '(</span>';
            chat_div.innerHTML += '<span class="chat_info">' + time_node[0].firstChild.nodeValue + ')&nbsp;&nbsp;</span>';
            chat_div.innerHTML += '<span class="chat_text">' + text_node[0].firstChild.nodeValue + '</span><br/>';
            chat_div.scrollTop = chat_div.scrollHeight;
            lastMessage = (message_nodes[i].getAttribute('id'));
        }
        mTimer = setTimeout('getChatText();',2000); //Refresh our chat in 2 seconds
    }
}
function handleReceiveUsers() {
    if (receiveReq2.readyState == 4) {
        var users_div = document.getElementById('div_users');
        var xmldoc = receiveReq2.responseXML;
        var online_nodes = xmldoc.getElementsByTagName("online"); 
        var n_online = online_nodes.length
        users_div.innerHTML = '';
        for (i = 0; i < n_online; i++) {
            var user_node = online_nodes[i].getElementsByTagName("user");
            users_div.innerHTML += '<b>' + user_node[0].firstChild.nodeValue + '<b/><br/>';
        }
        mTimer = setTimeout('getChatText();',3000);
    }
}
function blockSubmit() {
    sendChatText();
    return false;
}
function resetChat() {
    if (sendReq.readyState == 4 || sendReq.readyState == 0) {
        sendReq.open("POST", 'inc/getChat.php?chat=text&last=' + lastMessage, true);
        sendReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        sendReq.onreadystatechange = handleResetChat; 
        var param = 'action=reset';
        sendReq.send(param);
        document.getElementById('txt_message').value = '';
    }							
}
function handleResetChat() {
    document.getElementById('div_chat').innerHTML = '';
    getChatText();
}	
//]]>
</script>
HTML;
include_once(getTheme($_SESSION['login_id']) . 'header.php');
?>
	<div id="leftcolumn">
        <?php
        include_once(getTheme($_SESSION['login_id']) . 'sidenav.php');
        if (checkAccess($_SESSION['login_id']) < 3) {
            include_once(getTheme($_SESSION['login_id']) . 'adminnav.php');
        }
        ?>
	</div>
	<div id="content">
		<div id="chat" class="centercontent">
		<style type="text/css" media="screen">
			.chat_info { font-style:italic; font-size:9px; font-weight:bold; }
			.chat_text { font-size:9px; }
            #div_chat { float:left; height:300px; width:390px; overflow:auto; background-color:#ccc; border:1px solid #555; }
            #div_users { float:left; margin-left:10px; height:300px; width:100px; overflow:auto; background-color:#ccc; border:1px solid #555; }
		</style>
	</head>
	<body onbeforeunload="javascript:endChat();">
        <noscript>
            <style type="text/css">
            #div_chat, input, #pagetitle {display: none;}
            #noscript {padding:1em;}
            #noscript p {background-color:#ff9; padding:3em; font-size:130%; line-height:200%;}
            </style>
            <div id="noscript">
            <p>
                JavaScript must be enabled in order for you to use the Chat Room. However, it seems JavaScript is either 
                disabled or not supported by your browser. Please enable JavaScript by changing your browser options, 
                then <a href="chat.php">try again</a>.
            </p>
            </div>
        </noscript>

		<div id="div_chat"></div>
        <div id="div_users"></div>
        <div style="clear:both"></div>
		<form id="frmmain" name="frmmain" onsubmit="return blockSubmit();">
			<?php
            if (checkAccess($_SESSION['login_id']) < 3) {
			    echo '<input type="button" name="btn_reset_chat" id="btn_reset_chat" value="Reset Chat" onclick="javascript:resetChat();" /><br />';
        	} ?>
			<input type="text" id="txt_message" name="txt_message" style="width: 447px;" />
			<input type="button" name="btn_send_chat" id="btn_send_chat" value="Send" onclick="javascript:sendChatText();" />
		</form>

		</div><!-- #prayers .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>
