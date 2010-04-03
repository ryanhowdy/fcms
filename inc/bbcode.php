<?php
session_start();
include_once('config_inc.php');
include_once('util_inc.php');
include_once('locale.php');

// Check that the user is logged in
isLoggedIn();

echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._('lang').'" lang="'._('lang').'">
<head>
<title>'.getSiteName().' - '._('powered by').' '.getCurrentVersion().'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="'.getTheme($_SESSION['login_id'], "../").'style.css"/>
<link rel="shortcut icon" href="../themes/favicon.ico"/>';
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
<h1>'._('BBCode Help').'</h1>
<p>'._('BBCode is a easy way to format text. Check out the examples below, the first line shows the bbcode and the second line show the output.').'</p>
<h2>'._('Available BBCodes').'</h2>
<hr/>
<h3>'._('Text Formatting').'</h2>
<p>[b]'._('Bold Text').'[/b]<br/><b>'._('Bold Text').'</b></p>
<p>[i]'._('Italic Text').'[/i]<br/><i>'._('Italic Text').'</i></p>
<p>[u]'._('Underline Text').'[/u]<br/><u>'._('Underline Text').'</u></p>
<p>I [del]like[/del] [ins]love[/ins] cheese.<br/>I <del>like</del> <ins>love</ins> cheese.</p>
<hr/>
<h3>'._('Text Alignment').'</h2>
<p>[align=left]'._('Left Aligned Text').'[/align]<br/>'._('Left Aligned Text').'</p>
[align=center]'._('Centered Text').'[/align]<br/><div class="center">'._('Centered Text').'</div><br/>
[align=right]'._('Right Aligned Text').'[/align]<br/><div class="alignright">'._('Underline Text').'</div>
<hr/>
<h3>'._('Headers').'</h2>
<p>[h1]'._('Really Big Header').'[/h1]<br/><h1>'._('Really Big Header').'</h1></p>
<p>[h2]'._('Big Header').'[/h2]<br/><h2>'._('Big Header').'</h2></p>
<p>'._('You can use any header number 1-6.').'</p>
<hr/>
<h3>'._('Quote').'</h2>
<p>[quote]'._('You can Quote me on it.').'[/quote]<br/><blockquote>'._('You can Quote me on it.').'</blockquote></p>
<h3>'._('Add Images From a URL').'</h2>
<p>[img=http://www.familycms.com/logo.gif]<br/><img src="../themes/default/images/logo.jpg"/></p>
<h3>'._('Change Text Color').'</h2>
<p>[color=#f00]'._('Red Text').'[/color]<br/><span style="color:#f00">'._('Red Text').'</span></p>
<p>[color=blue]'._('Blue Text').'[/color]<br/><span style="color:blue">'._('Blue Text').'</span></p>
<h3>'._('Links').'</h2>
<p>[url=http://www.google.com/]'._('Click Here For Google').'[/url]<br/><a href="http://www.google.com/">'._('Click Here For Google').'</a></p>
<p>[url]http://www.yahoo.com/[/url]<br/><a href="http://www.yahoo.com/">http://www.yahoo.com/</a></p>
<p>[mail=someguy@mail.com]'._('Mail Some Guy').'[/mail]<br/><a href="mailto:someguy@mail.com">'._('Mail Some Guy').'</a></p>
<p>[mail]anotherguy@mail.com[/mail]<br/><a href="mailto:anotherguy@mail.com">anotherguy@mail.com</a></p>
<h3>'._('Youtube Video').'</h2>
<p>
    [video]&lt;object width="320" height="265"&gt;&lt;param name="movie" value="http://www.youtube.com/v/dMH0bHeiRNg&hl=en_US&fs=1&"&gt;&lt;/param&gt;&lt;param name="allowFullScreen" value="true"&gt;&lt;/param&gt;&lt;param name="allowscriptaccess" value="always"&gt;&lt;/param&gt;&lt;embed src="http://www.youtube.com/v/dMH0bHeiRNg&hl=en_US&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="320" height="265"&gt;&lt;/embed&gt;&lt;/object&gt;>[/video]<br/>
    <object width="320" height="265"><param name="movie" value="http://www.youtube.com/v/dMH0bHeiRNg&hl=en_US&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/dMH0bHeiRNg&hl=en_US&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="320" height="265"></embed></object>
</p>
</body>
</html>';
?>
