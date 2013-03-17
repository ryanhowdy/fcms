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
<link rel="stylesheet" type="text/css" href="../ui/themes/default/style.css"/>
<link rel="shortcut icon" href="../ui/favicon.ico"/>';
// TODO
// Move css to fcms-core
echo '
<style type="text/css">
html { background: #fff; }
body { width: 350px; margin: 0; padding: 15px; text-align: left; font: 14px/20px Verdana, Tahoma, Arial, sans-serif; border: none; background: #fff; }
h1 { font: bold 20px/30px Verdana, Tahoma, Arial, sans-serif; }
h2 { font: bold 18px/30px Verdana, Tahoma, Arial, sans-serif; }
h3 { font: bold 16px/30px Verdana, Tahoma, Arial, sans-serif; }
</style>
</head>
<body>
<h1>'.T_('BBCode Help').'</h1>
<p>'.T_('BBCode is a easy way to format text. Check out the examples below, the first line shows the bbcode and the second line show the output.').'</p>
<h2>'.T_('Available BBCodes').'</h2>
<hr/>
<h3>'.T_('Text Formatting').'</h2>
<p>[b]'.T_('Bold Text').'[/b]<br/><b>'.T_('Bold Text').'</b></p>
<p>[i]'.T_('Italic Text').'[/i]<br/><i>'.T_('Italic Text').'</i></p>
<p>[u]'.T_('Underline Text').'[/u]<br/><u>'.T_('Underline Text').'</u></p>
<p>I [del]like[/del] [ins]love[/ins] cheese.<br/>I <del>like</del> <ins>love</ins> cheese.</p>
<hr/>
<h3>'.T_('Text Alignment').'</h2>
<p>[align=left]'.T_('Left Aligned Text').'[/align]<br/>'.T_('Left Aligned Text').'</p>
[align=center]'.T_('Centered Text').'[/align]<br/><div class="center">'.T_('Centered Text').'</div><br/>
[align=right]'.T_('Right Aligned Text').'[/align]<br/><div class="alignright">'.T_('Underline Text').'</div>
<hr/>
<h3>'.T_('Headers').'</h2>
<p>[h1]'.T_('Really Big Header').'[/h1]<br/><h1>'.T_('Really Big Header').'</h1></p>
<p>[h2]'.T_('Big Header').'[/h2]<br/><h2>'.T_('Big Header').'</h2></p>
<p>'.T_('You can use any header number 1-6.').'</p>
<hr/>
<h3>'.T_('Quote').'</h2>
<p>[quote]'.T_('You can Quote me on it.').'[/quote]<br/><blockquote>'.T_('You can Quote me on it.').'</blockquote></p>
<h3>'.T_('Add Images From a URL').'</h2>
<p>[img=http://www.familycms.com/logo.gif]<br/><img src="../ui/images/logo.gif"/></p>
<h3>'.T_('Change Text Color').'</h2>
<p>[color=#f00]'.T_('Red Text').'[/color]<br/><span style="color:#f00">'.T_('Red Text').'</span></p>
<p>[color=blue]'.T_('Blue Text').'[/color]<br/><span style="color:blue">'.T_('Blue Text').'</span></p>
<h3>'.T_('Links').'</h2>
<p>[url=http://www.google.com/]'.T_('Click Here For Google').'[/url]<br/><a href="http://www.google.com/">'.T_('Click Here For Google').'</a></p>
<p>[url]http://www.yahoo.com/[/url]<br/><a href="http://www.yahoo.com/">http://www.yahoo.com/</a></p>
<p>[mail=someguy@mail.com]'.T_('Mail Some Guy').'[/mail]<br/><a href="mailto:someguy@mail.com">'.T_('Mail Some Guy').'</a></p>
<p>[mail]anotherguy@mail.com[/mail]<br/><a href="mailto:anotherguy@mail.com">anotherguy@mail.com</a></p>
<h3>'.T_('Youtube Video').'</h2>
<p>
    [video]&lt;object width="320" height="265"&gt;&lt;param name="movie" value="http://www.youtube.com/v/dMH0bHeiRNg&hl=en_US&fs=1&"&gt;&lt;/param&gt;&lt;param name="allowFullScreen" value="true"&gt;&lt;/param&gt;&lt;param name="allowscriptaccess" value="always"&gt;&lt;/param&gt;&lt;embed src="http://www.youtube.com/v/dMH0bHeiRNg&hl=en_US&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="320" height="265"&gt;&lt;/embed&gt;&lt;/object&gt;>[/video]<br/>
    <object width="320" height="265"><param name="movie" value="http://www.youtube.com/v/dMH0bHeiRNg&hl=en_US&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/dMH0bHeiRNg&hl=en_US&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="320" height="265"></embed></object>
</p>
</body>
</html>';
?>
