<?php
set_error_handler("fcmsErrorHandler");
include_once('language.php');
$connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
mysql_select_db($cfg_mysql_db);
$email_headers = 'From: ' . getSiteName() . ' <' . getContactEmail() . '>' . "\r\n" . 
    'Reply-To: ' . getContactEmail() . "\r\n" . 
    'Content-Type: text/plain; charset=UTF-8;' . "\r\n" . 
    'MIME-Version: 1.0' . "\r\n" . 
    'X-Mailer: PHP/' . phpversion();
$smileydir = "themes/smileys/";
$smiley_array = array(':smile:', ':none:', ':)', '=)', ':wink:', ';)', ':tongue:', ':biggrin:', ':sad:', ':(', ':sick:', ':cry:', ':shocked:', ':cool:', ':sleep:', 'zzz', ':angry:', ':mad:', ':embarrassed:', ':shy:', 
    ':rolleyes:', ':nervous:', ':doh:', ':love:', ':please:', ':1please:', ':hrmm:', ':quiet:', ':clap:', ':twitch:', ':blah:', ':bored:', ':crazy:', ':excited:', ':noidea:', ':disappointed:', ':banghead:', 
    ':dance:', ':laughat:', ':ninja:', ':pirate:', ':thumbup:', ':thumbdown:', ':twocents:'
);
$smiley_file_array = array('smile.gif', 'smile.gif', 'smile.gif', 'smile.gif', 'wink.gif', 'wink.gif', 'tongue.gif', 'biggrin.gif', 'sad.gif', 'sad.gif', 'sick.gif', 'cry.gif', 'shocked.gif', 'cool.gif', 
    'sleep.gif', 'sleep.gif', 'angry.gif', 'angry.gif', 'embarrassed.gif', 'embarrassed.gif', 'rolleyes.gif', 'nervous.gif', 'doh.gif', 'love.gif', 'please.gif', 'please.gif', 'hrmm.gif', 'quiet.gif', 
    'clap.gif', 'twitch.gif', 'blah.gif', 'bored.gif', 'crazy.gif', 'excited.gif', 'noidea.gif', 'disappointed.gif', 'banghead.gif', 'dance.gif', 'laughat.gif', 'ninja.gif', 'pirate.gif', 'thumbup.gif', 
    'thumbdown.gif', 'twocents.gif'
);

function getTheme ($userid, $d = "")
{
    if (empty($userid)) {
        return $d . "themes/default/";
    } else {
        $result = mysql_query("SELECT `theme` FROM `fcms_user_settings` WHERE `id` = $userid") or die('<h1>Theme Error (util.inc.php 18)</h1>' . mysql_error());
        $r = mysql_fetch_array($result);
        $pos = strpos($r['theme'], '.css');
        if ($pos === false) {
            return $d . "themes/" . $r['theme'] . "/";
        } else {
            return $d . "themes/" . substr($r['theme'], 0, $pos) . "/";
        }
    }
}

/*
 *  getUserDisplayName
 *  
 *  @param  $userid      the id of the desired user
 *  @param  $display     optional - how to display it, overrides user's prefs
 *  @return  a string of the users display name
 */
function getUserDisplayName ($userid, $display = 0, $isMember = true)
{
    if ($isMember) {
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, s.`displayname` "
             . "FROM `fcms_users` AS u, `fcms_user_settings` AS s "
             . "WHERE u.`id` = $userid "
             . "AND u.`id` = s.`user`";
    } else {
        $sql = "SELECT `fname`, `lname`, `username` "
             . "FROM `fcms_users` "
             . "WHERE `id` = $userid ";
    }
    $result = mysql_query($sql) or displaySQLError('Displayname Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
    $r = mysql_fetch_array($result);
    // Do we want user's settings or overriding it?
    if ($display < 1) {
        $displayname = $r['displayname'];
    } else {
        $displayname = $display;
    }
    switch($displayname) {
        case '1': return $r['fname']; break;
        case '2': return $r['fname'].' '.$r['lname']; break;
        case '3': return $r['username']; break;
        default: return $r['username']; break;
    }
}

/*
 *  displayOptSection
 *  
 *  @param  $start      starting section
 *  @param  $length     how many sections to display
 *  @param  $type       URL or LINK
 *  @return  nothing, echo's sections
 */
function displayOptSection ($start, $length, $type = '')
{
    global $LANG;
    $sql = "SELECT * FROM `fcms_config`";
    $result = mysql_query($sql) or displaySQLError('Get Config Error', 'util_inc.php [' . __LINE__ . ']', $sql, mysql_error());
    $r = mysql_fetch_array($result);
    if (!empty($type)) {
        while ($length > 0) {
            switch ($start) {
                case '1': $pos = strpos($r['section1'], "none"); if ($pos === false) { echo $r['section1'] . '.php'; } break;
                case '2': $pos = strpos($r['section2'], "none"); if ($pos === false) { echo $r['section2'] . '.php'; } break;
                case '3': $pos = strpos($r['section3'], "none"); if ($pos === false) { echo $r['section3'] . '.php'; } break;
                case '4': $pos = strpos($r['section4'], "none"); if ($pos === false) { echo $r['section4'] . '.php'; } break;
                default: return false; break;
            }
            $length--;
            $start++;
        }
    } else {
        while ($length > 0) {
            switch ($start) {
                case '1': $pos = strpos($r['section1'], "none"); if ($pos === false) { echo $LANG['link_' . $r['section1']]; } break;
                case '2': $pos = strpos($r['section2'], "none"); if ($pos === false) { echo $LANG['link_' . $r['section2']]; } break;
                case '3': $pos = strpos($r['section3'], "none"); if ($pos === false) { echo $LANG['link_' . $r['section3']]; } break;
                case '4': $pos = strpos($r['section4'], "none"); if ($pos === false) { echo $LANG['link_' . $r['section4']]; } break;
                default: return false; break;
            }
            $length--;
            $start++;
        }
    }
    return true;
}

function countOptSections ()
{
    global $LANG;
    $sql = "SELECT `section1`, `section2`, `section3`, `section4` FROM `fcms_config`";
    $result = mysql_query($sql) or displaySQLError('Count Sections Error', 'util_inc.php [' . __LINE__ . ']', $sql, mysql_error());
    if (mysql_num_rows($result) > 0) {
        $count = 0;
        $r = mysql_fetch_array($result);
        for ($i=0; $i<4; $i++) {
            $pos = strpos($r[$i], "none");
            if (ctype_alpha($r[$i]) && $pos === false) {
                $count++;
            }
        }
        return $count;
    } else {
        return -1;
    }
    
}

function displayFooter ($d = "")
{ 
    global $LANG; 
    if (!empty($d)) { $d = "../"; }
    $ver = getCurrentVersion();
    $date = date('Y');
    echo <<<HTML
        <div id="footer">
            <p>
                <a href="{$d}index.php" class="ft">{$LANG['link_home']}</a> | 
                <a href="http://www.familycms.com/forum/index.php" class="ft">{$LANG['link_support']}</a> | 
                <a href="{$d}help.php" class="ft">{$LANG['link_help']}</a><br />
                <a href="http://www.familycms.com">{$ver}</a> - Copyright &copy; 2006-{$date} Ryan Haudenschilt.
            </p>
        </div>

HTML;
}

function displayNewPM ($userid, $d = "")
{
    global $LANG; 
    $sql = "SELECT `id` FROM `fcms_privatemsg` WHERE `to` = $userid AND `read` < 1";
    $result = mysql_query($sql) or displaySQLError('Get New PM', 'util_inc.php [' . __LINE__ . ']', $sql, mysql_error());
    if (mysql_num_rows($result) > 0) {
        echo "<a href=\"" . $d . "privatemsg.php\" class=\"new_pm\">" . $LANG['new_pm'] . "</a> ";
    } else {
        echo " ";
    }
}

function checkAccess ($userid)
{
    $result = mysql_query("SELECT access FROM fcms_users WHERE id = $userid") or die('<h1>Access Error (util.inc.php 47)</h1>' . mysql_error());
    $r = mysql_fetch_array($result);
    return $r['access'];
}

function parse ($data, $allowVideo = 0)
{
    $data = htmlentities($data, ENT_COMPAT, 'UTF-8');
    $data = parse_smilies($data);
    $data = parse_bbcodes($data, $allowVideo);
    $data = bbcode_quote($data);
    $data = nl2br_nospaces($data);
    echo $data;
}

function parse_bbcodes ($data, $allowVideo)
{
    $search = array('/\[ins\](.*?)\[\/ins\]/is', '/\[del\](.*?)\[\/del\]/is', '/\[h1\](.*?)\[\/h1\]/is', '/\[h2\](.*?)\[\/h2\]/is', '/\[h3\](.*?)\[\/h3\]/is', '/\[h4\](.*?)\[\/h4\]/is', '/\[h5\](.*?)\[\/h5\]/is', '/\[h6\](.*?)\[\/h6\]/is', 
        '/\[b\](.*?)\[\/b\]/is', '/\[i\](.*?)\[\/i\]/is', '/\[u\](.*?)\[\/u\]/is', '/\[url\=(.*?)\](.*?)\[\/url\]/is', '/\[url\](.*?)\[\/url\]/is', '/\[align\=(left|center|right)\](.*?)\[\/align\]/is','/\[img\=(.*?)\]/is', '/\[img\](.*?)\[\/img\]/is', 
        '/\[mail\=(.*?)\](.*?)\[\/mail\]/is', '/\[mail\](.*?)\[\/mail\]/is', '/\[font\=(.*?)\](.*?)\[\/font\]/is', '/\[size\=(.*?)\](.*?)\[\/size\]/is', '/\[color\=(.*?)\](.*?)\[\/color\]/is', '/\[span\](.*?)\[\/span\]/is', '/\[span\=(.*?)\](.*?)\[\/span\]/is');
    $replace = array('<ins>$1</ins>', '<del>$1</del>', '<h1>$1</h1>', '<h2>$1</h2>', '<h3>$1</h3>', '<h4>$1</h4>', '<h5>$1</h5>', '<h6>$1</h6>', 
        '<b>$1</b>', '<i>$1</i>', '<u>$1</u>', '<a href="$1">$2</a>', '<a href="$1">$1</a>', '<div style="text-align: $1;">$2</div>', '<img src="$1" />','<img src="$1" />', 
        '<a href="mailto:$1">$2</a>', '<a href="mailto:$1">$1</a>', '<span style="font-family: $1;">$2</span>', '<span style="font-size: $1;">$2</span>','<span style="color: $1;">$2</span>', '<span>$1</span>', '<span class="$1">$2</span>');
    $data = preg_replace ($search, $replace, $data);
    if ($allowVideo > 0) {
        $found = 0;
        // find all video tags
        while (is_integer($found)) {
            $start = stripos($data, '[video]');
            $found = $start;
            $end = stripos($data, '[/video]');
            if ($start !== false && $end !== false) {
                // add/subtract here because we don't want to include '[video]' or '[/video]'
                $video_code = substr($data, $start+7, $end-7);
                $regx = '/&lt;object.*?&gt;.*?(&lt;param.*?&gt;&lt;\/param&gt;)*.*?&lt;embed.*?&gt;.*?&lt;\/.mbed&gt;.*?&lt;\/object&gt;/is';
                if (preg_match($regx, $video_code)) {
                    $video_code = unhtmlentities($video_code);
                    if ($start - 7 < 0) {
                        $start = 0;
                    } else {
                        $start = $start - 7;
                    }
                    $data_beg = substr($data, 0, $start);
                    $data_end = substr($data, $end+8);
                    $data = $data_beg . $video_code . $data_end;
                }
            }
        }
    }
    
    return $data; 
}

function parse_smilies ($data)
{
    global $smiley_array, $smiley_file_array, $smileydir;
    $i = 0;
    while($i < count($smiley_array)) {
        $data = str_replace($smiley_array[$i], '<img src="' . $smileydir . $smiley_file_array[$i] . '" alt="'. $smiley_array[$i] . '" />', $data);
        $i ++;
    }
    return $data;
}

function bbcode_quote ($data)
{
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

function nl2br_nospaces ($string)
{
    $string = str_replace(array("\r\n", "\r", "\n"), "<br/>", $string); 
    return $string; 
} 

function displaySmileys ()
{
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

function escape_string ($string)
{
    if (version_compare(phpversion(), "4.3.0") == "-1") {
        return mysql_escape_string($string);
    } else {
        return mysql_real_escape_string($string);
    }
}

// html_entity_decode for PHP 4.3.0 and earlier:
function unhtmlentities($string)
{
    // replace numeric entities
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
    // replace literal entities
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}

function getPostsById($user_id)
{
    $result = mysql_query("SELECT * FROM fcms_board_posts") or die('<h1>Posts Error (util.inc.php 116)</h1>' . mysql_error());
    $total = mysql_num_rows($result);
    mysql_free_result($result);
    $result = mysql_query("SELECT count(user) AS c FROM fcms_board_posts WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 119)</h1>' . mysql_error());
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if($total < 1) { return "0 (0%)"; } else { return $count . " (" . round((($count/$total)*100), 1) . "%)"; }
}

function getPhotosById ($user_id)
{
    $result = mysql_query("SELECT * FROM fcms_gallery_photos") or die('<h1>Photos Error (util.inc.php 125)</h1>' . mysql_error());
    $total = mysql_num_rows($result);
    mysql_free_result($result);
    $results = mysql_query("SELECT count(user) AS c FROM fcms_gallery_photos WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 128)</h1>' . mysql_error());
    $found = mysql_fetch_array($results);
    $count = $found['c'];
    if ($total < 1) { return "0 (0%)"; } else { return $count . " (" . round((($count/$total)*100), 1) . "%)"; }
}

function getFamilyNewsById ($user_id)
{
    $result = mysql_query("SELECT * FROM `fcms_news`");
    $total = mysql_num_rows($result);
    mysql_free_result($result);
    $result = mysql_query("SELECT COUNT(`id`) AS count FROM `fcms_news` WHERE `user` = $user_id GROUP BY `user`") or die(mysql_error());
    $r = mysql_fetch_array($result);
    $count = $r['count'];
    if ($count < 1) { return "0 (0%)"; } else { return $count . " (" . round((($count/$total)*100), 1) . "%)"; }
}

function getCommentsById ($user_id)
{
    $result = mysql_query("SELECT * FROM `fcms_gallery_comments`");
    $total = mysql_num_rows($result);
    mysql_free_result($result);
    $count = 0;
    if (usingFamilyNews()) {
        $result = mysql_query("SELECT * FROM `fcms_news_comments`");
        $total = $total + mysql_num_rows($result);
        mysql_free_result($result);
        $result = mysql_query("SELECT COUNT(*) AS count FROM `fcms_news_comments` WHERE `user` = $user_id") or die(mysql_error());
        $r = mysql_fetch_array($result);
        $count = $r['count'];
        mysql_free_result($result);
    }
    $result = mysql_query("SELECT COUNT(*) AS count FROM `fcms_gallery_comments` WHERE `user` = $user_id") or die(mysql_error());
    $r = mysql_fetch_array($result);
    $count = $count + $r['count'];
    if ($count < 1) { return "0 (0%)"; } else { return $count . " (" . round((($count/$total)*100), 1) . "%)"; }
}

function getNewsComments ($news_id)
{
    $result = mysql_query("SELECT count(id) AS c FROM fcms_news_comments WHERE news = $news_id") or die('<h1>Count Error (util.inc.php 134)</h1>' . mysql_error());
    $found = mysql_fetch_array($result);
    return  $found['c'] ? $found['c'] : 0;
}

function getUserRankById ($user_id)
{
    $points = 0; $news_count = 0; 
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
    if (usingFamilyNews()) {
        $result = mysql_query("SELECT count(user) AS c FROM fcms_news WHERE user = $user_id") or die('<h1>Count Error (util.inc.php 159)</h1>' . mysql_error());
        $found = mysql_fetch_array($result);
        $news_count = $found['c'];
    }
    $points = ($post_count / 75) + ($photo_count / 25) + ($comments_count / 20) + ($calendar_count / 5) + ($news_count / 10) + ($vote_count / 4);
    return $points;
}

function getContactEmail ()
{
    $result = mysql_query("SELECT `contact` FROM `fcms_config`");
    $r = mysql_fetch_array($result);
    return $r['contact'];
}

function getSiteName()
{
    $result = mysql_query("SELECT `sitename` FROM `fcms_config`");
    $r = mysql_fetch_array($result);
    return $r['sitename'];
}

function getCurrentVersion()
{
    $result = mysql_query("SELECT `current_version` FROM `fcms_config`");
    $r = mysql_fetch_array($result);
    return $r['current_version'];
}

function displayMBToolbar ()
{
    global $LANG;
    echo "\t\t\t\t<div class=\"toolbar\">\n";
    echo "\t\t\t\t\t<input type=\"button\" class=\"bold button\" onclick=\"bb.insertCode('B', 'bold');\" onmouseout=\"style.border='1px solid #f6f6f6';\" onmouseover=\"style.border='1px solid #c1c1c1';\" title=\"".$LANG['b_txt']."\" />\n";
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

function uploadImages ($filetype, $filename, $filetmpname, $destination, $max_h, $max_w, $unique = 'no')
{
    global $LANG;
    $known_photo_types = array('image/pjpeg' => 'jpg', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/bmp' => 'bmp', 'image/x-png' => 'png', 'image/png' => 'png');
    $gd_function_suffix = array('image/pjpeg' => 'JPEG', 'image/jpeg' => 'JPEG', 'image/gif' => 'GIF', 'image/bmp' => 'WBMP', 'image/x-png' => 'PNG', 'image/png' => 'PNG');
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

// include page in url (index.php?uid=0)
function displayPages ($url, $cur_page, $total_pages)
{
    global $LANG;
    if ($total_pages > 1) {
        echo "\t\t\t<div class=\"pages clearfix\">\n\t\t\t\t<ul>\n";
        if ($cur_page > 1) {
            $prev = ($cur_page - 1);
            echo "\t\t\t\t\t<li><a title=\"".$LANG['title_first_page']."\" class=\"first\" href=\"$url&amp;page=1\"></a></li>\n";
            echo "\t\t\t\t\t<li><a title=\"".$LANG['title_prev_page']."\" class=\"previous\" href=\"$url&amp;page=$prev\"></a></li>\n";
        } 
        if ($total_pages > 8) {
            if ($cur_page > 2) {
                for ($i = ($cur_page-2); $i <= ($cur_page+5); $i++) {
                    if ($i <= $total_pages) { echo "\t\t\t\t\t<li><a href=\"$url&amp;page=$i\"";  if($cur_page == $i) { echo " class=\"current\""; } echo ">$i</a></li>\n"; }
                } 
            } else {
                for ($i = 1; $i <= 8; $i++) { echo "\t\t\t\t\t<li><a href=\"$url&amp;page=$i\"";  if($cur_page == $i) { echo " class=\"current\""; } echo ">$i</a></li>\n"; } 
            }
        } else {
            for ($i = 1; $i <= $total_pages; $i++) {
                echo "\t\t\t\t\t<li><a href=\"$url&amp;page=$i\"";  if($cur_page == $i) { echo " class=\"current\""; } echo ">$i</a></li>\n";
            } 
        }
        if ($cur_page < $total_pages) { 
            $next = ($cur_page + 1); 
            echo "\t\t\t\t\t<li><a title=\"" . $LANG['title_next_page'] . "\" class=\"next\" href=\"$url&amp;page=$next\"></a></li>\n";
            echo "\t\t\t\t\t<li><a title=\"" . $LANG['title_last_page'] . "\" class=\"last\" href=\"$url&amp;page=$total_pages\"></a></li>\n";
        } 
        echo "\t\t\t\t</ul>\n\t\t\t</div>\n";
    }    
}

function formatSize($file_size)
{
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

function displayMembersOnline ()
{
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

function isLoggedIn ($userid, $username, $password)
{
    $result = mysql_query("SELECT * FROM `fcms_users` WHERE `id` = $userid LIMIT 1") or die('<h1>Login Error (util.inc.php 275)</h1>' . mysql_error());
    if (mysql_num_rows($result) > 0) {
        $r = mysql_fetch_array($result);
        if ($r['username'] !== $username) { return false; } elseif ($r['password'] !== $password) { return false; } else { return true; }
    } else {
        return false;
    }
}

function displayLoginPage ($d = "")
{
    global $LANG;
    if (!empty($d)) {
        $d = "../";
    }
    $sitename = getSiteName();
    echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$LANG['lang']}" lang="{$LANG['lang']}">
<head>
<link rel="stylesheet" type="text/css" href="{$d}themes/default/style.css"/>
</head>
<body>
    <div id="header">
        <div id="logo">
            <a href="{$d}index.php"><img src="{$d}themes/default/images/logo.jpg"/></a>
        </div>
    </div>
    <div id="content">
        <div class="centercontent" style="padding: 0 0 300px 0;">
            <div class="error-alert">
                <h1>{$LANG['access_denied1']}</h1>
                <p>{$LANG['access_denied2']}</p>
                <p><a href="{$d}index.php">{$LANG['access_denied3']}</a></p>
            </div>
        </div>
    </div>
HTML;
    displayFooter($d);
    echo "</body>\n</html>";
}

/**
 * buildHtmlSelectOptions
 * 
 * Builds a list of select options, given an array of values and selected values.
 * 
 * @param   $options    array of available options, key is the value of the option
 * @param   $selected   array or string of selected options, key is the value of the option
 * returns  a string of options
 * 
 */
function buildHtmlSelectOptions ($options, $selected_options)
{
    $return = '';
    foreach ($options as $key => $value) {
        $selected = '';
        if (is_array($selected)) {
            if (array_key_exists($key, $selected_options)) {
                $selected = ' selected="selected"';
            }
        } else {
            if ($key == $selected_options) {
                $selected = ' selected="selected"';
            }
        }
        $return .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
    }
    return $return;
}

/**
 * getLangMonthName
 * 
 * Displays the month name from the language.php file.
 * This function is needed because the Full and Short value for May is the same.
 * 
 * @param   $month  short month name date('M')
 * returns  the month name from the language file
 * 
 */
function getLangMonthName ($month)
{
    global $LANG;
    if ($month == 'May') {
        return $LANG['May_short'];
    } else {
        return $LANG[$month];
    }
}

function fixDST ($date, $userid, $format = 'F j, Y, g:i a')
{
    $sql = "SELECT `dst` FROM `fcms_user_settings` WHERE `user` = $userid";
    $result = mysql_query($sql) or displaySQLError(
        'DST Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    if ($r['dst'] > 0) {
        return date($format, strtotime($date . " +1 hours"));
    } else {
        return date($format, strtotime($date));
    }
}

/*
 *  To find out which optional sections are being used:
 *  1 = familynews, 2 = prayer concerns, 3 = calendar, 4 = recipes, 5 = documents
 */
function usingFamilyNews()
{
    return usingSection('familynews');
}
function usingPrayers()
{
    return usingSection('prayers');
}
function usingRecipes()
{
    return usingSection('recipes');
}
function usingDocuments()
{
    return usingSection('documents');
}
function usingSection ($i)
{
    $result = mysql_query("SELECT `section1`, `section2`, `section3`, `section4` FROM `fcms_config`");
    $r = mysql_fetch_array($result);
    if ($r['section1'] == $i) {
        return true;
    } else if ($r['section2'] == $i) {
        return true;
    } else if ($r['section3'] == $i) {
        return true;
    } else if ($r['section4'] == $i) {
        return true;
    } else {
        return false;
    }
}

function tableExists ($tbl)
{
    global $cfg_mysql_db;
    $table = mysql_query("SHOW TABLES FROM `$cfg_mysql_db` LIKE '".$tbl."'");
    if (mysql_fetch_row($table) === false) {
        return false;
    } else {
        return true ;
    }
}

function getDomainAndDir ()
{
    $pageURL = 'http';
    if (isset($_SERVER["HTTPS"])) { if ($_SERVER["HTTPS"] == "on") { $pageURL .= "s"; } }
    $pageURL .= "://";
    if (isset($_SERVER["SERVER_PORT"])) {
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
    }
    // Return the domain and any directories, but exlude the filename from the end
    return substr($pageURL, 0, strripos($pageURL, '/')+1);
}

function displaySQLError ($heading, $file, $sql, $error)
{
    echo "<div class=\"error-alert\"><big><b>$heading</b></big><br/><small><b>File:</b> $file</small><br/><small><b>Statement:</b> $sql</small><br/>";
    echo "<small><b>Error:</b> $error</small><br/><small><b>MySQL Version:</b> " . mysql_get_server_info() . "</small><br/>";
    echo "<small><b>PHP Version:</b> " . phpversion() . "</small></div>";
}

function fcmsErrorHandler($errno, $errstr, $errfile, $errline)
{
    $pos = strpos($errstr, "It is not safe to rely on the system's timezone settings");
    if ($pos === false) {
        switch ($errno) {
            case E_USER_ERROR:
                echo "<div class=\"error-alert\"><big><b>Fatal Error</b></big><br/><small><b>$errstr</b></small><br/>";
                echo "<small><b>Where:</b> on line $errline in $errfile</small><br/><small><b>Environment:</b> PHP " . PHP_VERSION . " (" . PHP_OS . ")</small></div>";
                exit(1);
                break;
            case E_USER_WARNING:
                echo "<div class=\"error-alert\"><big><b>Warning</b></big><br/><small><b>$errstr</b></small><br/>";
                echo "<small><b>Where:</b> on line $errline in $errfile</small><br/><small><b>Environment:</b> PHP " . PHP_VERSION . " (" . PHP_OS . ")</small></div>";
                break;
            case E_USER_NOTICE:
                echo "<div class=\"error-alert\"><big><b>Notice</b></big><br/><small><b>$errstr</b></small><br/>";
                echo "<small><b>Where:</b> on line $errline in $errfile</small><br/><small><b>Environment:</b> PHP " . PHP_VERSION . " (" . PHP_OS . ")</small></div>";
                break;
            default:
                echo "<div class=\"error-alert\"><big><b>Error</b></big><br/><small><b>$errstr</b></small><br/>";
                echo "<small><b>Where:</b> on line $errline in $errfile</small><br/><small><b>Environment:</b> PHP " . PHP_VERSION . " (" . PHP_OS . ")</small></div>";
                break;
        }
    }
    // Don't execute PHP internal error handler
    return true;
}

function displayWhatsNewAll ($userid)
{
    global $cfg_mysql_host, $cfg_use_news, $cfg_use_prayers, $LANG;
    $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $userid";
    $t_result = mysql_query($sql) or displaySQLError(
        'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $t = mysql_fetch_array($t_result);
    $tz_offset = $t['timezone'];
    $today = date('Y-m-d');
    $yesterday  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));
    $sql = "SELECT p.`id`, `date`, `subject` AS title, u.`id` AS userid, `thread` AS id2, 0 AS id3, 'BOARD' AS type FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, fcms_users AS u WHERE p.`thread` = t.`id` AND p.`user` = u.`id` AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) "
        . "UNION SELECT a.`id`, `updated` AS 'date', 0 AS title, `user` AS userid, `entered_by` AS id2, 0 AS id3, 'ADDRESS' AS type FROM `fcms_users` AS u, `fcms_address` AS a WHERE u.`id` = a.`user` AND 'date' >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) ";
    if (usingFamilyNews()) { $sql .= "UNION SELECT n.`id` AS id, n.`date`, `title`, u.`id` AS userid, 0 AS id2, 0 AS id3, 'NEWS' AS type FROM `fcms_users` AS u, `fcms_news` AS n WHERE u.`id` = n.`user` AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' "; }
    if (usingPrayers()) { $sql .= "UNION SELECT 0 AS id, `date`, `for` AS title, `user` AS userid, 0 AS id2, 0 AS id3, 'PRAYERS' AS type FROM `fcms_prayers` WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) "; }
    if (usingRecipes()) { $sql .= "UNION SELECT `id` AS id, `date`, `name` AS title, `user` AS userid, `category` AS id2, 0 AS id3, 'RECIPES' AS type FROM `fcms_recipes` WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) "; }
    if (usingdocuments()) { $sql .= "UNION SELECT d.`id` AS 'id', d.`date`, `name` AS title, d.`user` AS userid, 0 AS id2, 0 AS id3, 'DOCS' AS type FROM `fcms_documents` AS d, `fcms_users` AS u WHERE d.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)  AND d.`user` = u.`id` "; }
    $sql .= "UNION SELECT DISTINCT p.`category` AS id, `date`, `name` AS title, p.`user` AS userid, COUNT(*) AS id2, DAYOFYEAR(`date`) AS id3, 'GALLERY' AS type FROM `fcms_gallery_photos` AS p, `fcms_users` AS u, `fcms_gallery_category` AS c WHERE p.`user` = u.`id` AND p.`category` = c.`id` AND 'date' >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY userid, title, id3 ";
    if (usingFamilyNews()) { $sql .= "UNION SELECT n.`id` AS 'id', nc.`date`, `title`, nc.`user` AS userid, 0 AS id2, 0 AS id3, 'NEWSCOM' AS type FROM `fcms_news_comments` AS nc, `fcms_news` AS n, `fcms_users` AS u WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)  AND nc.`user` = u.`id` AND n.`id` = nc.`news` "; }
    $sql .= "UNION SELECT p.`id`, gc.`date`, `comment` AS title, gc.`user` AS userid, p.`user` AS id2, `filename` AS id3, 'GALCOM' AS type FROM `fcms_gallery_comments` AS gc, `fcms_users` AS u, `fcms_gallery_photos` AS p WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND gc.`user` = u.`id` AND gc.`photo` = p.`id` ";
    $sql .= "UNION SELECT c.`id`, c.`date_added` AS date, `title`, `created_by` AS userid, `date` AS id2, `type` AS id3, 'CALENDAR' AS type FROM `fcms_calendar` AS c, `fcms_users` AS u WHERE c.`date_added` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND c.`created_by` = u.`id` AND `private` < 1 ";
    $sql .= "UNION SELECT `id`, `started` AS date, `question`, '0' AS userid, 'na' AS id2, 'na' AS id3, 'POLL' AS type FROM `fcms_polls` WHERE `started` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
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
            echo "<a href=\"messageboard.php?thread=" . $r['id2'] . "\" title=\"" . htmlentities($subject, ENT_COMPAT, 'UTF-8') . "\">$subject</a>. <small><i>$rdate</i></small></p>\n";
        } elseif ($r['type'] == 'ADDRESS') {
            $new_result = mysql_query("SELECT `joindate`, `password` FROM `fcms_users` WHERE `id` = " . $r['userid']);
            $n = mysql_fetch_array($new_result);
            if (date('Y-m-d H', strtotime($n['joindate'])) == date('Y-m-d H', strtotime($r['date'])) AND $n['password'] != 'NONMEMBER') {
                // A new user joined the site
                echo "\t\t\t<p class=\"newmember\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
                echo getUserDisplayName($r['userid']);
                echo "</a> ".$LANG['joined_site']." <small><i>$rdate</i></small></p>\n";
            } else {
                // A user has added an address for a non-member
                if ($r['userid'] != $r['id2']) {
                    echo "\t\t\t<p class=\"newaddress\"><a class=\"u\" href=\"profile.php?member=" . $r['id2'] . "\">";
                    echo getUserDisplayName($r['id2']);
                    echo "</a> ".$LANG['added_address']." <a href=\"addressbook.php?address=" . $r['id'] . "\">";
                    echo getUserDisplayName($r['userid'], 2, false);
                    echo "</a>. <small><i>$rdate</i></small></p>\n";
                // User updated his/her address
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
        } elseif ($r['type'] == 'RECIPES') {
            switch ($r['id2']) {
                case $LANG['appetizer']: $url = "recipes.php?category=1&amp;id=".$r['id']; break;
                case $LANG['breakfast']: $url = "recipes.php?category=2&amp;id=".$r['id']; break;
                case $LANG['dessert']: $url = "recipes.php?category=3&amp;id=".$r['id']; break;
                case $LANG['entree_meat']: $url = "recipes.php?category=4&amp;id=".$r['id']; break;
                case $LANG['entree_seafood']: $url = "recipes.php?category=5&amp;id=".$r['id']; break;
                case $LANG['entree_veg']: $url = "recipes.php?category=6&amp;id=".$r['id']; break;
                case $LANG['salad']: $url = "recipes.php?category=7&amp;id=".$r['id']; break;
                case $LANG['side_dish']: $url = "recipes.php?category=8&amp;id=".$r['id']; break;
                case $LANG['soup']: $url = "recipes.php?category=9&amp;id=".$r['id']; break;
                default: $url = "recipes.php"; break;
            }
            echo "\t\t\t<p class=\"newrecipe\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
            echo getUserDisplayName($r['userid']);
            echo "</a> ".$LANG['added_recipe1']." <a href=\"$url\">".$r['title']."</a> ".$LANG['added_recipe2']." <small><i>$rdate</i></small></p>\n";
        } elseif ($r['type'] == 'DOCS') {
            echo "\t\t\t<p class=\"newdocument\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
            echo getUserDisplayName($r['userid']);
            echo "</a> " . $LANG['added_docs'] . " (<a href=\"documents.php\">".$r['title']."</a>). <small><i>$rdate</i></small></p>\n";
        } elseif ($r['type'] == 'GALLERY') {
            echo "\t\t\t<p class=\"newphoto\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
            echo getUserDisplayName($r['userid']);
            echo "</a> ".$LANG['added_photos1']." " . $r['id2'] . " ".$LANG['added_photos2']." <a href=\"gallery/index.php?uid=" . $r['userid'] . "&amp;cid=" . $r['id'] . "\">".$r['title']."</a> ".$LANG['added_photos3']." <small><i>$rdate</i></small><br/>";
            $limit = 4;
            if ($r['id2'] < $limit) { $limit = $r['id2']; }
            $photos = mysql_query("SELECT * FROM `fcms_gallery_photos` WHERE `category` = " . $r['id'] . " AND DAYOFYEAR(`date`) = " . $r['id3'] . " ORDER BY `date` DESC LIMIT $limit") or die("<h1>Photo Info Error (util.inc.php 405)</h1>" . mysql_error());
            while ($p=mysql_fetch_array($photos)) {
                echo "<a href=\"gallery/index.php?uid=" . $r['userid'] . "&amp;cid=" . $r['id'] . "\"><img src=\"gallery/photos/member" . $r['userid'] . "/tb_" . $p['filename'] . "\" alt=\"".htmlentities($p['caption'], ENT_COMPAT, 'UTF-8')."\"/></a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            echo "</p>\n";
        } elseif ($r['type'] == 'NEWSCOM') {
            echo "\t\t\t<p class=\"newcom\"><a class=\"u\" href=\"profile.php?member=".$r['userid']."\">";
            echo getUserDisplayName($r['userid']);
            echo "</a> ".$LANG['com_news']." <a href=\"familynews.php?getnews=".$r['userid']."&amp;newsid=".$r['id']."\">".$r['title']."</a>. <small><i>$rdate</i></small></p>\n";
        } elseif ($r['type'] == 'GALCOM') {
            echo "\t\t\t<p class=\"newcom\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
            echo getUserDisplayName($r['userid']);
            echo "</a> ".$LANG['com_gallery']." <small><i>$rdate</i></small><br/><a href=\"gallery/index.php?uid=0&amp;cid=comments&amp;pid=" . $r['id'] . "\"><img src=\"gallery/photos/member" . $r['id2'] . "/tb_" . $r['id3'] . "\"/></a></p>\n";
        } elseif ($r['type'] == 'CALENDAR') {
            $date_date = fixDST(gmdate('F j, Y', strtotime($r['id2'] . $tz_offset)), $_SESSION['login_id'], 'm-d-y');
            $date_date2 = fixDST(gmdate('F j, Y', strtotime($r['id2'] . $tz_offset)), $_SESSION['login_id'], 'F j, Y');
            echo "\t\t\t<p class=\"newcal\"><a class=\"u\" href=\"profile.php?member=" . $r['userid'] . "\">";
            echo getUserDisplayName($r['userid']);
            echo "</a> " . $LANG['added_cal1'] . " $date_date " . $LANG['added_cal2'] . " <a href=\"calendar.php?year=" . date('Y', strtotime($date_date2)) . "&amp;month=" . date('m', strtotime($date_date2)) . "&amp;day=" . date('d', strtotime($date_date2)) . "\">" . $r['title'] . "</a>. <small><i>$rdate</i></small></p>\n";
        } elseif ($r['type'] == 'POLL') {
            echo "\t\t\t<p class=\"newpoll\">" . $LANG['added_poll1'] . " (<a href=\"home.php?poll_id=" . $r['id'] . "\">" . $r['title'] . "</a>) " . $LANG['added_poll2'] . ". <small><i>$rdate</i></small></p>\n";
        }
        $lastday = $day;
    }
}

/*********************************************/
/* Function: ImageCreateFromBMP              */
/* Author:   DHKold                          */
/* Contact:  admin@dhkold.com                */
/* Date:     The 15th of June 2005           */
/* Version:  2.0B                            */
/*********************************************/
function ImageCreateFromBMP ($filename)
{
    if (! $f1 = fopen($filename,"rb")) return FALSE;
    
    $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
    if ($FILE['file_type'] != 19778) return FALSE;
    
    $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel/Vcompression/Vsize_bitmap/Vhoriz_resolution/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
    $BMP['colors'] = pow(2,$BMP['bits_per_pixel']);
    if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
    $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
    $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
    $BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
    $BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
    $BMP['decal'] = 4-(4*$BMP['decal']);
    if ($BMP['decal'] == 4) $BMP['decal'] = 0;
    
    $PALETTE = array();
    if ($BMP['colors'] < 16777216) {
        $PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
    }
    
    $IMG = fread($f1,$BMP['size_bitmap']);
    $VIDE = chr(0);
    
    $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
    $P = 0;
    $Y = $BMP['height']-1;
    while ($Y >= 0) {

    $X=0;
        while ($X < $BMP['width']) {
            if ($BMP['bits_per_pixel'] == 24) {
                $COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
            } elseif ($BMP['bits_per_pixel'] == 16) {  
                $COLOR = unpack("n",substr($IMG,$P,2));
                $COLOR[1] = $PALETTE[$COLOR[1]+1];
            } elseif ($BMP['bits_per_pixel'] == 8) {  
                $COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
                $COLOR[1] = $PALETTE[$COLOR[1]+1];
            } elseif ($BMP['bits_per_pixel'] == 4) {
                $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
                if (($P*2)%2 == 0) $COLOR[1] = ($COLOR[1] >> 4) ; else $COLOR[1] = ($COLOR[1] & 0x0F);
                $COLOR[1] = $PALETTE[$COLOR[1]+1];
            } elseif ($BMP['bits_per_pixel'] == 1) {
                $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
                if     (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
                elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
                elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
                elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
                elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
                elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
                elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
                elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
                $COLOR[1] = $PALETTE[$COLOR[1]+1];
            } else {
                return FALSE;
            }
            imagesetpixel($res,$X,$Y,$COLOR[1]);
            $X++;
            $P += $BMP['bytes_per_pixel'];
        }
        $Y--;
        $P+=$BMP['decal'];
    }
    
    //Fermeture du fichier
    fclose($f1);
    
    return $res;
}
?>
