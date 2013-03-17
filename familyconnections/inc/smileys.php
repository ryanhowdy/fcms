<?php
session_start();

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

setLanguage();
isLoggedIn('inc/');

$currentUserId = (int)$_SESSION['login_id'];

echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_('lang').'" lang="'.T_('lang').'">
<head>
<title>'.getSiteName().' - '.T_('powered by').' '.getCurrentVersion().'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="'.getTheme($currentUserId, "../").'style.css"/>
<link rel="shortcut icon" href="../themes/favicon.ico"/>';
// TODO
// Move css to fcms-core
echo '
<style type="text/css">
html { background: #fff; }
body { width: 100%; margin: 0; padding: 15px 0 0 0; text-align: left; font-family: Verdana, Tahoma, Arial, sans-serif; font-size: 11px; border: none; background: #fff; }
.smiley { float: left; text-align: center; vertical-align: middle; width: 50px; height: 50px; }
img { cursor: pointer; cursor: hand; }
</style>
<script type="text/javascript">
//<![CDATA[
function addSmiley(str) {
    var textarea = window.opener.document.getElementById(\'post\');
    if (textarea) {
        if (textarea.value == "message") { textarea.value = str + " "; } else { textarea.value += str + " "; }
        textarea.focus();
    }
    javascript:window.close();
    return true;
}
//]]>
</script>
</head>
<body>';
displaySmileys();
echo '
</body>
</html>';
