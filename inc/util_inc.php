<?php
include_once('language.php');
$connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
mysql_select_db($cfg_mysql_db);
$stgs_release = "Family Connections 1.4";
$smileydir = "themes/images/smileys/";
$smiley_array = array(':smile:', ':none:', ':)', '=)', ':wink:', ';)', ':tongue:', ':biggrin:', ':sad:', ':(', ':sick:', ':cry:', ':shocked:', ':cool:', ':sleep:', 'zzz', ':angry:', ':mad:', ':embarrassed:', ':shy:', 
	':rolleyes:', ':nervous:', ':doh:', ':love:', ':please:', ':1please:', ':hrmm:', ':quiet:', ':clap:', ':twitch:', ':blah:', ':bored:', ':crazy:', ':excited:', ':noidea:', ':disappointed:', ':banghead:', 
	':dance:', ':laughat:', ':ninja:', ':pirate:', ':thumbup:', ':thumbdown:', ':twocents:'
);
$smiley_file_array = array('smile.gif', 'smile.gif', 'smile.gif', 'smile.gif', 'wink.gif', 'wink.gif', 'tongue.gif', 'biggrin.gif', 'sad.gif', 'sad.gif', 'sick.gif', 'cry.gif', 'shocked.gif', 'cool.gif', 
	'sleep.gif', 'sleep.gif', 'angry.gif', 'angry.gif', 'embarrassed.gif', 'embarrassed.gif', 'rolleyes.gif', 'nervous.gif', 'doh.gif', 'love.gif', 'please.gif', 'please.gif', 'hrmm.gif', 'quiet.gif', 
	'clap.gif', 'twitch.gif', 'blah.gif', 'bored.gif', 'crazy.gif', 'excited.gif', 'noidea.gif', 'disappointed.gif', 'banghead.gif', 'dance.gif', 'laughat.gif', 'ninja.gif', 'pirate.gif', 'thumbup.gif', 
	'thumbdown.gif', 'twocents.gif'
);
function getTheme ($userid) {
	if (empty($userid)) { echo "themes/default.css"; } 
	else {
		$result = mysql_query("SELECT theme FROM fcms_users WHERE id = $userid") or die('<h1>Theme Error (util.inc.php 18)</h1>' . mysql_error());
		$r = mysql_fetch_array($result);
		echo "themes/" . $r['theme'];
	}
}
function getUserDisplayName ($userid, $display = 0) {
	$result = mysql_query("SELECT * FROM fcms_users WHERE id = $userid") or die('<h1>Displayname Error (util.inc.php 24)</h1>' . mysql_error());
	$r = mysql_fetch_array($result);
	if ($display < 1) { $displayname = $r['displayname']; } else { $displayname = $display; }
	switch($displayname) {
		case '1': return $r['fname']; break;
		case '2': return $r['fname'].' '.$r['lname']; break;
		case '3': return $r['username']; break;
		default: return $r['username']; break;
	}
}
function displayTopNav ($d = "") {
	global $cfg_use_news, $cfg_use_prayers, $LANG;
	if (!empty($d)) { $d = "../"; }
	echo '<div id="topmenu"><ul id="navlist"><li><span><a class="firstlastnavmenu" href="' . $d . 'home.php">'.$LANG['link_home'].'</a></span></li><li><span><a class="navmenu" href="' . $d . 'gallery/index.php">'.$LANG['link_gallery'].'</a></span></li><li><span><a class="navmenu" href="' . $d . 'messageboard.php">'.$LANG['link_board'].'</a></span></li><li><span><a class="navmenu" href="' . $d . 'addressbook.php">'.$LANG['link_address'].'</a></span></li>';
	if ($cfg_use_news == 'YES' && $cfg_use_prayers == 'YES') {
		echo '<li><span><a class="navmenu" href="' . $d . 'familynews.php">'.$LANG['link_news'].'</a></span></li><li><span><a class="firstlastnavmenu" href="' . $d . 'prayers.php">'.$LANG['link_prayer'].'</a></span></li>';
	} else {
		if ($cfg_use_news == 'YES') { echo '<li><span><a class="navmenu" href="' . $d . 'familynews.php">'.$LANG['link_news'].'</a></span></li><li><span><a class="firstlastnavmenu" href="#">&nbsp;</a></span></li>'; }
		elseif ($cfg_use_prayers == 'YES') { echo '<li><span><a class="navmenu" href="' . $d . 'prayers.php">'.$LANG['link_prayer'].'</a></span></li><li><span><a class="firstlastnavmenu" href="#">&nbsp;</a></span></li>'; }
		else { echo '<li><span><a class="navmenu" href="#">&nbsp;</a></span></li><li><span><a class="firstlastnavmenu" href="#">&nbsp;</a></span></li>'; }
	}
	echo "</ul></div>";
}
function displaySideNav ($d = "") {
	global $LANG;
	if (!empty($d)) { $d = "../"; }
	echo "<div class=\"firstmenu menu\">\n\t\t\t<ul>\n\t\t\t\t";
	echo "<li><a href=\"".$d."calendar.php\" title=\"".$LANG['link_title_calendar']."\">".$LANG['link_calendar']."</a></li>\n\t\t\t\t";
	echo "<li><a href=\"".$d."profile.php\" title=\"".$LANG['link_title_profiles']."\">".$LANG['link_profiles']."</a></li>\n\t\t\t\t";
	echo "<li><a href=\"".$d."contact.php\" title=\"".$LANG['link_title_contact']."\">".$LANG['link_contact']."</a></li>\n\t\t\t\t";
	echo "<li><a href=\"".$d."help.php\" title=\"".$LANG['link_get_help']."\">".$LANG['link_get_help']."</a></li>\n\t\t\t\t";
	echo "<li><a href=\"".$d."logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></li>\n\t\t\t</ul>\n\t\t</div>\n\t";
}
function displayAdminNav ($d = "") {
	global $LANG;
	if ($d == 'fix') { $d = "admin/"; } elseif ($d == 'fixgal') { $d = "../admin/"; }
	echo "\t<div class=\"menu\">\n\t\t\t<ul>\n";
	if (checkAccess($_SESSION['login_id']) < 2) { echo "\t\t\t\t<li><a href=\"".$d."members.php\">".$LANG['link_admin_members']."</a></li>\n"; }
	if (checkAccess($_SESSION['login_id']) < 2) { echo "\t\t\t\t<li><a href=\"".$d."board.php\">".$LANG['link_admin_board']."</a></li>\n"; }
	echo "\t\t\t\t<li><a href=\"".$d."polls.php\">".$LANG['link_admin_polls']."</a></li>\n";
	echo "\t\t\t\t<li><a href=\"".$d."awards.php\">".$LANG['link_admin_awards']."</a></li>\n";
	if (checkAccess($_SESSION['login_id']) < 2) { echo "\t\t\t\t<li><a href=\"".$d."upgrade.php\">".$LANG['link_admin_upgrade']."</a></li>\n"; }
	echo "\t\t\t</ul>\n\t\t</div>\n\t";
}

function checkAccess ($userid) {
	$result = mysql_query("SELECT access FROM fcms_users WHERE id = $userid") or die('<h1>Access Error (util.inc.php 47)</h1>' . mysql_error());
	$r = mysql_fetch_array($result);
	return $r['access'];
}
function parse ($data) {
	$data = htmlentities($data, ENT_COMPAT, 'UTF-8');
	$data = parse_smilies($data);
	$data = parse_bbcodes($data);
	$data = bbcode_quote($data);
	$data = nl2br($data);
	echo $data;
}
function parse_bbcodes ($data) {
	$search = array('/\[ins\](.*?)\[\/ins\]/is', '/\[del\](.*?)\[\/del\]/is', '/\[h1\](.*?)\[\/h1\]/is', '/\[h2\](.*?)\[\/h2\]/is', '/\[h3\](.*?)\[\/h3\]/is', '/\[h4\](.*?)\[\/h4\]/is', '/\[h5\](.*?)\[\/h5\]/is', '/\[h6\](.*?)\[\/h6\]/is', 
		'/\[b\](.*?)\[\/b\]/is', '/\[i\](.*?)\[\/i\]/is', '/\[u\](.*?)\[\/u\]/is', '/\[url\=(.*?)\](.*?)\[\/url\]/is', '/\[url\](.*?)\[\/url\]/is', '/\[align\=(left|center|right)\](.*?)\[\/align\]/is','/\[img\=(.*?)\]/is', '/\[img\](.*?)\[\/img\]/is', 
		'/\[mail\=(.*?)\](.*?)\[\/mail\]/is', '/\[mail\](.*?)\[\/mail\]/is', '/\[font\=(.*?)\](.*?)\[\/font\]/is', '/\[size\=(.*?)\](.*?)\[\/size\]/is', '/\[color\=(.*?)\](.*?)\[\/color\]/is', '/\[span\](.*?)\[\/span\]/is', '/\[span\=(.*?)\](.*?)\[\/span\]/is');
	$replace = array('<ins>$1</ins>', '<del>$1</del>', '<h1>$1</h1>', '<h2>$1</h2>', '<h3>$1</h3>', '<h4>$1</h4>', '<h5>$1</h5>', '<h6>$1</h6>', 
		'<b>$1</b>', '<i>$1</i>', '<u>$1</u>', '<a href="$1">$2</a>', '<a href="$1">$1</a>', '<div style="text-align: $1;">$2</div>', '<img src="$1" />','<img src="$1" />', 
		'<a href="mailto:$1">$2</a>', '<a href="mailto:$1">$1</a>', '<span style="font-family: $1;">$2</span>', '<span style="font-size: $1;">$2</span>','<span style="color: $1;">$2</span>', '<span>$1</span>', '<span class="$1">$2</span>');
	$data = preg_replace ($search, $replace, $data); 
	return $data; 
}
function parse_smilies ($data) {
	global $smiley_array, $smiley_file_array, $smileydir;
	$i = 0;
	while($i < count($smiley_array)) {
		$data = str_replace($smiley_array[$i], '<img src="' . $smileydir . $smiley_file_array[$i] . '" alt="'. $smiley_array[$i] . '" />', $data);
		$i ++;
	}
	return $data;
}
function bbcode_quote ($data) { 
    $open = '<blockquote>'; 
    $close = '</blockquote>'; 
    preg_match_all ('/\[quote\]/i', $data, $matches); 
    $opentags = count($matches['0']); 
    preg_match_all ('/\[\/quote\]/i', $data, $matches); 
    $closetags = count($matches['0']); 
    $unclosed = $opentags - $closetags; 
    for ($i = 0; $i < $unclosed; $i++) { $data.= '</blockquote>'; } 
    $data = str_replace ('[' . 'QUOTE]', $open, $data); 
	$data = str_replace ('[' . 'quote]', $open, $data); 
    $data = str_replace ('[/' . 'QUOTE]', $close, $data); 
    $data = str_replace ('[/' . 'quote]', $close, $data); 
    return $data; 
}
function displaySmileys () {
	global $smiley_array, $smiley_file_array, $smileydir, $LANG;
	$i=0;
	$previous_smiley_file = '';
	foreach ($smiley_array as $smiley) {
		if ($i == 30) {
			echo "<a href=\"#\" onclick=\"$('more').toggle(); return false\">(".$LANG['more'].")</a></p><p id=\"more\" style=\"display:none;\">";
		}
		if ($smiley_file_array[$i] != $previous_smiley_file) {
			echo '<img src="' . $smileydir . $smiley_file_array[$i] . '" alt="' . $smiley . '" onclick="return addSmiley(\''.str_replace("'", "\'", $smiley).'\')" /> ';
			$previous_smiley_file = $smiley_file_array[$i];
		}
		$i++;
	}
}
function escape_string ($string) {
	if (version_compare(phpversion(), "4.3.0") == "-1") {
		return mysql_escape_string($string);
	} else {
		return mysql_real_escape_string($string);
	}
}
function getPostsById($user_id) {
	$result = mysql_query("SELECT * FROM fcms_board_posts") or die('<h1>Posts Error (util.inc.php 116)</h1>' . mysql_error());
	$total = mysql_num_rows($result);
	mysql_free_result($result);
	$result = mysql_query("SELECT count(user) AS c FROM fcms_board_posts WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 119)</h1>' . mysql_error());
	$found = mysql_fetch_array($result);
	$count = $found['c'];
	if($total < 1) { return "0 (0%)"; } else { return $count . " (" . round((($count/$total)*100), 1) . "%)"; }
}
function getPhotosById ($user_id) {
	$result = mysql_query("SELECT * FROM fcms_gallery_photos") or die('<h1>Photos Error (util.inc.php 125)</h1>' . mysql_error());
	$total = mysql_num_rows($result);
	mysql_free_result($result);
	$results = mysql_query("SELECT count(user) AS c FROM fcms_gallery_photos WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 128)</h1>' . mysql_error());
	$found = mysql_fetch_array($results);
	$count = $found['c'];
	if ($total < 1) { return "0 (0%)"; } else { return $count . " (" . round((($count/$total)*100), 1) . "%)"; }
}
function getFamilyNewsById ($user_id) {
	$result = mysql_query("SELECT * FROM `fcms_news`");
	$total = mysql_num_rows($result);
	mysql_free_result($result);
	$result = mysql_query("SELECT COUNT(`id`) AS count FROM `fcms_news` WHERE `user` = $user_id GROUP BY `user`") or die(mysql_error());
	$r = mysql_fetch_array($result);
	$count = $r['count'];
	if ($count < 1) { return "0 (0%)"; } else { return $count . " (" . round((($count/$total)*100), 1) . "%)"; }
}
function getCommentsById ($user_id) {
	$result = mysql_query("SELECT * FROM `fcms_gallery_comments`");
	$total = mysql_num_rows($result);
	mysql_free_result($result);
	$result = mysql_query("SELECT * FROM `fcms_news_comments`");
	$total = $total + mysql_num_rows($result);
	mysql_free_result($result);
	$result = mysql_query("SELECT COUNT(*) AS count FROM `fcms_news_comments` WHERE `user` = $user_id") or die(mysql_error());
	$r = mysql_fetch_array($result);
	$count = $r['count'];
	mysql_free_result($result);
	$result = mysql_query("SELECT COUNT(*) AS count FROM `fcms_gallery_comments` WHERE `user` = $user_id") or die(mysql_error());
	$r = mysql_fetch_array($result);
	$count = $count + $r['count'];
	if ($count < 1) { return "0 (0%)"; } else { return $count . " (" . round((($count/$total)*100), 1) . "%)"; }
}
function getNewsComments ($news_id) {
	$result = mysql_query("SELECT count(id) AS c FROM fcms_news_comments WHERE news = $news_id") or die('<h1>Count Error (util.inc.php 134)</h1>' . mysql_error());
	$found = mysql_fetch_array($result);
	return  $found['c'] ? $found['c'] : 0;
}
function getUserRankById ($user_id) {
	$result = mysql_query("SELECT count(user) AS c FROM fcms_board_posts WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 139)</h1>' . mysql_error());
	$found = mysql_fetch_array($result);
	$post_count = $found['c'];
	mysql_free_result($result);
	$result = mysql_query("SELECT count(user) AS c FROM fcms_gallery_photos WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 143)</h1>' . mysql_error());
	$found = mysql_fetch_array($result);
	$photo_count = $found['c'];
	mysql_free_result($result);
	$result = mysql_query("SELECT count(user) AS c FROM fcms_gallery_comments WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 147)</h1>' . mysql_error());
	$found = mysql_fetch_array($result);
	$comments_count = $found['c'];
	mysql_free_result($result);
	$result = mysql_query("SELECT count(user) AS c FROM fcms_poll_users WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 151)</h1>' . mysql_error());
	$found = mysql_fetch_array($result);
	$vote_count = $found['c'];
	mysql_free_result($result);
	$result = mysql_query("SELECT count(created_by) AS c FROM fcms_calendar WHERE created_by = $user_id") or die('<h1>Count Error (util.inc.php 155)</h1>' . mysql_error());
	$found = mysql_fetch_array($result);
	$calendar_count = $found['c'];
	mysql_free_result($result);
	$result = mysql_query("SELECT count(user) AS c FROM fcms_news WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 159)</h1>' . mysql_error());
	$found = mysql_fetch_array($result);
	$news_count = $found['c'];
	$points = ($post_count / 75) + ($photo_count / 25) + ($comments_count / 20) + ($calendar_count / 5) + ($news_count / 10) + ($vote_count / 4);
	return $points;
}
function displayMBToolbar () {
	global $LANG;
	echo "\t\t\t\t<div class=\"toolbar\">\n";
	echo "\t\t\t\t\t.<input type=\"button\" class=\"bold button\" onclick=\"bb.insertCode('B', 'bold');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['b_txt']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"italic button\" onclick=\"bb.insertCode('I', 'italic');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['i_txt']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"underline button\" onclick=\"bb.insertCode('U', 'underline');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['u_txt']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"left_align button\" onclick=\"bb.insertCode('ALIGN=LEFT', 'left right', 'ALIGN');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['left_txt']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"center_align button\" onclick=\"bb.insertCode('ALIGN=CENTER', 'center', 'ALIGN');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['center_txt']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"right_align button\" onclick=\"bb.insertCode('ALIGN=RIGHT', 'align right', 'ALIGN');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['right_txt']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"h1 button\" onclick=\"bb.insertCode('H1', 'heading 1');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['h1_txt']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"h2 button\" onclick=\"bb.insertCode('H2', 'heading 2');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['h2_txt']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"h3 button\" onclick=\"bb.insertCode('H3', 'heading 3');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['h3_txt']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"board_quote button\" onclick=\"bb.insertCode('QUOTE', 'quote');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['blockquote']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"board_images button\" onclick=\"bb.insertImage();\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['ins_image']."\" />\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"links button\" onclick=\"bb.insertLink();\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['ins_link']."\" />&nbsp;&nbsp;|&nbsp;&nbsp;\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"black color\" onclick=\"bb.insertCode('COLOR=BLACK', 'black colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['black_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"white color\" onclick=\"bb.insertCode('COLOR=WHITE', 'white colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['white_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"gray color\" onclick=\"bb.insertCode('COLOR=GRAY', 'gray colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['gray_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"silver color\" onclick=\"bb.insertCode('COLOR=SILVER', 'silver colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['silver_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"maroon color\" onclick=\"bb.insertCode('COLOR=MAROON', 'maroon colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['maroon_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"red color\" onclick=\"bb.insertCode('COLOR=RED', 'red colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['red_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"olive color\" onclick=\"bb.insertCode('COLOR=OLIVE', 'olive colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['olive_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"yellow color\" onclick=\"bb.insertCode('COLOR=YELLOW', 'yellow colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['yellow_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"green color\" onclick=\"bb.insertCode('COLOR=GREEN', 'green colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['green_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"lime color\" onclick=\"bb.insertCode('COLOR=LIME', 'lime colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['lime_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"teal color\" onclick=\"bb.insertCode('COLOR=TEAL', 'teal colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['teal_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"aqua color\" onclick=\"bb.insertCode('COLOR=AQUA', 'aqua colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['aqua_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"navy color\" onclick=\"bb.insertCode('COLOR=NAVY', 'navy colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['navy_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"blue color\" onclick=\"bb.insertCode('COLOR=BLUE', 'blue colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['blue_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"purple color\" onclick=\"bb.insertCode('COLOR=PURPLE', 'purple colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['purple_txt']."\"/>\n";
	echo "\t\t\t\t\t<input type=\"button\" class=\"fuchsia color\" onclick=\"bb.insertCode('COLOR=FUCHSIA', 'pink colored text', 'COLOR');\" onmouseout=\"style.border='1px solid #000000';\" onmouseover=\"style.border='1px solid #ffffff';\" title=\"".$LANG['pink_txt']."\"/>\n";
	echo "\t\t\t\t</div>\n";
}
function uploadImages ($filetype, $filename, $filetmpname, $destination, $max_h, $max_w, $unique = 'no') {
	global $LANG;
	$known_photo_types = array('image/pjpeg' => 'jpg', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/bmp' => 'bmp', 'image/x-png' => 'png');
	$gd_function_suffix = array('image/pjpeg' => 'JPEG', 'image/jpeg' => 'JPEG', 'image/gif' => 'GIF', 'image/bmp' => 'WBMP', 'image/x-png' => 'PNG');
	if (!array_key_exists($filetype, $known_photo_types)) {
		echo "<p class=\"error-alert\">".$LANG['err_not_file1']." $filetype ".$LANG['err_not_file2']."</p>";
	} else {
		if ($unique !== 'no') {
			$new_id = uniqid("");
			$extention = $known_photo_types[$filetype];
			$filename = $new_id . "." . $extention;
		}
		copy($filetmpname, $destination . $filename);
		$size = GetImageSize($destination . $filename);
		if ($size[0] > $size[1]) {
			if ($size[0] > $max_w) { $thumbnail_width = $max_w; $thumbnail_height = (int)($max_w * $size[1] / $size[0]); } else { $thumbnail_width = $size[0]; $thumbnail_height = $size[1]; }
		} else {
			if ($size[1] > $max_h) { $thumbnail_width = (int)($max_h * $size[0] / $size[1]); $thumbnail_height = $max_h; } else { $thumbnail_width = $size[0]; $thumbnail_height = $size[1]; }
		}
		if ($size[0] > $max_w && $size[1] > $max_h) {
			$function_suffix = $gd_function_suffix[$filetype];
			$function_to_read = "ImageCreateFrom".$function_suffix;
			$function_to_write = "Image".$function_suffix;
			$source_handle = $function_to_read($destination . $filename); 
			if ($source_handle) {
				$destination_handle = ImageCreateTrueColor($thumbnail_width, $thumbnail_height);
				ImageCopyResampled($destination_handle, $source_handle, 0, 0, 0, 0, $thumbnail_width, $thumbnail_height, $size[0], $size[1]);
			}
			$function_to_write($destination_handle, $destination . $filename);
			ImageDestroy($destination_handle );
		}
	}
	echo "<img src=\"" . $destination . $filename . "\" alt=\"\"/>";
	return $filename;
}
function formatSize($file_size){
	if ($file_size >= 1073741824) {
		$file_size = round($file_size / 1073741824 * 100) / 100 . "Gb";
	} elseif ($file_size >= 1048576) { 
		$file_size = round($file_size / 1048576 * 100) / 100 . "Mb";
	} elseif ($file_size >= 1024) {
		$file_size = round($file_size / 1024 * 100) / 100 . "Kb";
	} else {
		$file_size = $file_size . "b";
	}
	return $file_size;
}
function displayMembersOnline () {
		global $LANG;
		$last15min = time() - (60 * 15);
		$lastday = time() - (60 * 60 * 24);
		$sql_last15min = mysql_query("SELECT * FROM fcms_users WHERE UNIX_TIMESTAMP(activity) >= $last15min ORDER BY `activity` DESC") or die('<h1>Online Error (util.inc.php 246)</h1>' . mysql_error());
		$sql_lastday = mysql_query("SELECT * FROM fcms_users WHERE UNIX_TIMESTAMP(activity) >= $lastday ORDER BY `activity` DESC") or die('<h1>Online Error (util.inc.php 247)</h1>' . mysql_error());
		echo "<h3>".$LANG['now'].":</h3><p>";
		$i = 1;
		$onlinenow_array = array();
		while ($e = mysql_fetch_array($sql_last15min)) {
			$displayname = getUserDisplayName($e['id']);
			$onlinenow_array[$i] = $e['id']; $i++; echo "<a class=\"member\" href=\"profile.php?member=".$e['id']."\">$displayname</a><br/>";
		}
		echo "</p><h3>".$LANG['last_24hrs'].":</h3><p>";
		while ($d = mysql_fetch_array($sql_lastday)) {
			$displayname = getUserDisplayName($d['id']);
			if(!array_search((string)$d['id'], $onlinenow_array)) { echo "<a class=\"member\" href=\"profile.php?member=".$d['id']."\">$displayname</a><br/>"; }
		}
		echo "</p><br/><br/>\n";
}
function isLoggedIn ($userid, $username, $password) {
	$result = mysql_query("SELECT * FROM `fcms_users` WHERE `id` = $userid LIMIT 1") or die('<h1>Login Error (util.inc.php 275)</h1>' . mysql_error());
	if (mysql_num_rows($result) > 0) {
		$r = mysql_fetch_array($result);
		if ($r['username'] !== $username) { return false; } elseif ($r['password'] !== $password) { return false; } else { return true; }
	} else {
		return false;
	}
}
function displayLoginPage () {
	global $LANG;
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">"
		. "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".$LANG['lang']."\" lang=\"".$LANG['lang']."\"><head><link rel=\"stylesheet\" type=\"text/css\" href=\"themes/default.css\" /></head>"
		. "<body><div id=\"header\"><h1 id=\"logo\">$cfg_sitename</h1></div><div id=\"content\"><div class=\"centercontent\" style=\"padding: 0 0 300px 0;\">"
		. "<div class=\"error-alert\"><h1>".$LANG['access_denied1']."</h1><p>".$LANG['access_denied2']."</p><p><a href=\"index.php\">".$LANG['access_denied3']."</a></p></div></div></div>"
		. "<div id=\"footer\"><p><a href=\"http://www.haudenschilt.com/fcms/\" class=\"ft\">".$LANG['link_home']."</a> | <a href=\"http://www.haudenschilt.com/forum/index.php\" class=\"ft\">".$LANG['link_support']."</a> | <a href=\"help.php\" class=\"ft\">".$LANG['link_help']."</a><br />"
		. "<a href=\"http://www.haudenschilt.com/fcms/\">$stgs_release</a> - Copyright &copy; 2006/07 Ryan Haudenschilt.</p></div></body></html>";
}
function fixDST ($date, $userid, $format = 'F j, Y, g:i a') {
	$result = mysql_query("SELECT `dst` FROM `fcms_users` WHERE `id` = $userid");
	$r = mysql_fetch_array($result);
	if ($r['dst'] > 0) {
		return date($format, strtotime($date . " +1 hours"));
	} else {
		return date($format, strtotime($date));
	}
}
function displayWhatsNewAll($userid) {
	global $cfg_mysql_host, $cfg_use_news, $cfg_use_prayers, $LANG;
	$t_result = mysql_query("SELECT `timezone` FROM `fcms_users` WHERE `id` = $userid");
	$t = mysql_fetch_array($t_result);
	$tz_offset = $t['timezone'];
	$today = date('Y-m-d');
	$yesterday  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));
	$sql = "SELECT p.`id`, `date`, `subject` AS title, u.`id` AS userid, `thread` AS id2, 0 AS id3, 'BOARD' AS type FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, fcms_users AS u WHERE p.`thread` = t.`id` AND p.`user` = u.`id` AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) "
		. "UNION SELECT a.`id`, `updated` AS 'date', 0 AS title, `user` AS userid, `entered_by` AS id2, 0 AS id3, 'ADDRESS' AS type FROM `fcms_users` AS u, `fcms_address` AS a WHERE u.`id` = a.`user` AND 'date' >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) ";
	if ($cfg_use_news == 'YES') { $sql .= "UNION SELECT n.`id` AS id, n.`date`, `title`, u.`id` AS userid, 0 AS id2, 0 AS id3, 'NEWS' AS type FROM `fcms_users` AS u, `fcms_news` AS n WHERE u.`id` = n.`user` AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' "; }
	if($cfg_use_prayers == 'YES') { $sql .= "UNION SELECT 0 AS id, `date`, `for` AS title, `user` AS userid, 0 AS id2, 0 AS id3, 'PRAYERS' AS type FROM `fcms_prayers` WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) "; }
	$sql .= "UNION SELECT DISTINCT p.`category` AS id, `date`, `name` AS title, p.`user` AS userid, COUNT(*) AS id2, DAYOFYEAR(`date`) AS id3, 'GALLERY' AS type FROM `fcms_gallery_photos` AS p, `fcms_users` AS u, `fcms_gallery_category` AS c WHERE p.`user` = u.`id` AND p.`category` = c.`id` AND 'date' >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY userid, title, id3 "
		. "UNION SELECT n.`id` AS 'id', nc.`date`, `title`, nc.`user` AS userid, 0 AS id2, 0 AS id3, 'NEWSCOM' AS type FROM `fcms_news_comments` AS nc, `fcms_news` AS n, `fcms_users` AS u WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)  AND nc.`user` = u.`id` AND n.`id` = nc.`news` "
		. "UNION SELECT p.`id`, gc.`date`, `comment` AS title, gc.`user` AS userid, p.`user` AS id2, `filename` AS id3, 'GALCOM' AS type FROM `fcms_gallery_comments` AS gc, `fcms_users` AS u, `fcms_gallery_photos` AS p WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND gc.`user` = u.`id` AND gc.`photo` = p.`id` ";
	$sql .= "ORDER BY date DESC LIMIT 0, 35";
	$result = mysql_query($sql) or die("<h1>Latest Info Error (util.inc.php 345)</h1>" . mysql_error() . "<h6>$sql</h6>");
	$lastday = '0-0';
	while ($r=mysql_fetch_array($result)) {
		$day = date('Y-m-d', strtotime($r['date']));
		if ($day != $lastday) {
			if ($day == $today) {
				echo "\t\t\t<p><b>".$LANG['today']."</b></p>\n";
			} elseif ($day == $yesterday) {
				echo "\t\t\t<p><b>".$LANG['yesterday']."</b></p>\n";
			} else {
				$monthName = date('F', strtotime($r['date']));
				$date_suffix = date('S', strtotime($r['date']));
				echo "\t\t\t<p><b>".$LANG[$monthName].date(' j', strtotime($r['date'])).$LANG[$date_suffix].date(', Y', strtotime($r['date']))."</b></p>\n";
			}
		}
		$rdate = fixDST(gmdate('g:i a', strtotime($r['date'] . $tz_offset)), $_SESSION['login_id'], 'g:i a');
		if ($r['type'] == 'BOARD') {
			$check = mysql_query("SELECT min(`id`) AS id FROM `fcms_board_posts` WHERE `thread` = " . $r['id2']) or die("<h1>Thread or Post Error (util.inc.php 360)</h1>" . mysql_error());
			$minpost = mysql_fetch_array($check);
			if ($r['id'] == $minpost['id']) {
				echo "\t\t\t<p class=\"newthread\">";
			} else {
				echo "\t\t\t<p class=\"newpost\">";
			}
			echo "<a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
			echo getUserDisplayName($r['userid']);
			echo "</a> ";
			if ($r['id'] == $minpost['id']) {
				echo $LANG['started_thread']." ";
			} else {
				echo $LANG['replied_to']." ";
			}
			$subject = $r['title'];
			$pos = strpos($subject, '#ANOUNCE#');
			if ($pos !== false) { $subject = substr($subject, 9, strlen($subject)-9); }
			echo "<a href=\"messageboard.php?thread=" . $r['id2'] . "\" title=\"" . htmlentities($subject) . "\">$subject</a>. <small><i>$rdate</i></small></p>\n";
		} elseif ($r['type'] == 'ADDRESS') {
			$new_result = mysql_query("SELECT `joindate`, `password` FROM `fcms_users` WHERE `id` = " . $r['userid']);
			$n = mysql_fetch_array($new_result);
			if (date('Y-m-d H', strtotime($n['joindate'])) == date('Y-m-d H', strtotime($r['date'])) AND $n['password'] != 'NONMEMBER') {
				echo "\t\t\t<p class=\"newmember\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
				echo getUserDisplayName($r['userid']);
				echo "</a> ".$LANG['joined_site']." <small><i>$rdate</i></small></p>\n";
			} else {
				if ($r['userid'] != $r['id2']) {
					echo "\t\t\t<p class=\"newaddress\"><a class=\"u\" href=\"profile.php?member=" . $r['id2'] . "\">";
					echo getUserDisplayName($r['id2']);
					echo "</a> ".$LANG['added_address']." <a href=\"addressbook.php?address=" . $r['id'] . "\">";
					echo getUserDisplayName($r['userid'], 2);
					echo "</a>. <small><i>$rdate</i></small></p>\n";
				} else {
					echo "\t\t\t<p class=\"newaddress\"><a class=\"u\" href=\"profile.php?member=" . $r['id2'] . "\">";
					echo getUserDisplayName($r['id2']);
					echo "</a> ".$LANG['upd_address1']." <a href=\"addressbook.php?address=" . $r['id'] . "\">".$LANG['upd_address2']."</a>. <small><i>$rdate</i></small></p>\n";
				}
			}
		} elseif ($r['type'] == 'NEWS') {
			echo "\t\t\t<p class=\"newnews\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
			echo getUserDisplayName($r['userid']);
			echo "</a> ".$LANG['new_news1']." <a href=\"familynews.php?getnews=".$r['userid']."&amp;newsid=".$r['id']."\">".$r['title']."</a> ".$LANG['new_news2']." <small><i>$rdate</i></small></p>\n";
		} elseif ($r['type'] == 'PRAYERS') {
			echo "\t\t\t<p class=\"newprayer\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
			echo getUserDisplayName($r['userid']);
			echo "</a> ".$LANG['added_concern']." <a href=\"prayers.php\">".$r['title']."</a>. <small><i>$rdate</i></small></p>\n";
		} elseif ($r['type'] == 'GALLERY') {
			echo "\t\t\t<p class=\"newphoto\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
			echo getUserDisplayName($r['userid']);
			echo "</a> ".$LANG['added_photos1']." " . $r['id2'] . " ".$LANG['added_photos2']." <a href=\"gallery/index.php?uid=" . $r['userid'] . "&amp;cid=" . $r['id'] . "\">".$r['title']."</a> ".$LANG['added_photos3']." <small><i>$rdate</i></small><br/>";
			$limit = 4;
			if ($r['id2'] < $limit) { $limit = $r['id2']; }
			$photos = mysql_query("SELECT * FROM `fcms_gallery_photos` WHERE `category` = " . $r['id'] . " AND DAYOFYEAR(`date`) = " . $r['id3'] . " ORDER BY `date` DESC LIMIT $limit") or die("<h1>Photo Info Error (util.inc.php 405)</h1>" . mysql_error());
			while ($p=mysql_fetch_array($photos)) {
				echo "<a href=\"gallery/index.php?uid=" . $r['userid'] . "&amp;cid=" . $r['id'] . "\"><img src=\"gallery/photos/member" . $r['userid'] . "/tb_" . $p['filename'] . "\" alt=\"".htmlentities($p['caption'])."\"/></a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			}
			echo "</p>\n";
		} elseif ($r['type'] == 'NEWSCOM') {
			echo "\t\t\t<p class=\"newcom\"><a class=\"u\" href=\"profile.php?member=".$r['userid']."\">";
			echo getUserDisplayName($r['userid']);
			echo "</a> ".$LANG['com_news']." <a href=\"familynews.php?getnews=".$r['userid']."&amp;newsid=".$r['id']."\">".$r['title']."</a>. <small><i>$rdate</i></small></p>\n";
		} elseif ($r['type'] == 'GALCOM') {
			echo "\t\t\t<p class=\"newcom\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
			echo getUserDisplayName($r['userid']);
			echo "</a> ".$LANG['com_gallery']." <small><i>$rdate</i></small><br/><a href=\"gallery/index.php?commentpid=" . $r['id3'] . "\"><img src=\"gallery/photos/member" . $r['id2'] . "/tb_" . $r['id3'] . "\"/></a></p>\n";
		}
		$lastday = $day;
	}
}
?>