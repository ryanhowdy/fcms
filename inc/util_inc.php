<?php
set_error_handler("fcmsErrorHandler");
include_once('gettext.inc');
include_once('locale.php');

// Setup MySQL
$connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
mysql_select_db($cfg_mysql_db);

// Setup php-gettext
if (isset($_SESSION['language'])) {
    T_setlocale(LC_MESSAGES, $_SESSION['language']);
} else {
    $lang = getLanguage();
    T_setlocale(LC_MESSAGES, $lang);
}
bindtextdomain('messages', './language');
if (function_exists('bind_textdomain_codeset')) {
  bind_textdomain_codeset('messages', 'UTF-8');
}
textdomain('messages');

// Email Headers and Smileys
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
        $result = mysql_query("SELECT `theme` FROM `fcms_user_settings` WHERE `user` = $userid") or die('<h1>Theme Error (util.inc.php 18)</h1>' . mysql_error());
        $r = mysql_fetch_array($result);
        $pos = strpos($r['theme'], '.css');
        if ($pos === false) {
            return $d . "themes/" . $r['theme'] . "/";
        } else {
            return $d . "themes/" . substr($r['theme'], 0, $pos) . "/";
        }
    }
}

function getLanguage ()
{
    if (isset($_SESSION['login_id'])) {
        $sql = "SELECT `language` FROM `fcms_user_settings` WHERE `id` = " . escape_string($_SESSION['login_id']);
        $result = mysql_query($sql) or displaySQLError(
            'Language Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = mysql_fetch_array($result);
        if (mysql_num_rows($result) > 0) {
            return $row['language'];
        }
    }
    return 'en_US';
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
 *  getPMCount
 *  
 *  @return  a string consisting of the user's new pm count in ()'s
 */
function getPMCount ()
{
    $sql = "SELECT * FROM `fcms_privatemsg` 
            WHERE `read` < 1 
            AND `to` = '".escape_string($_SESSION['login_id'])."'";
    $result = mysql_query($sql) or displaySQLError(
        'PM Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        return ' ('.mysql_num_rows($result).')';
    }
    return '';
}

/*
 *  getUserEmail
 *  
 *  @param  $userid - the id of the desired user
 *  @return  a string of the users email
 */
function getUserEmail ($userid)
{
    $sql = "SELECT `email` "
         . "FROM `fcms_users` "
         . "WHERE `id` = $userid";
    $result = mysql_query($sql) or displaySQLError(
        'Email Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    return $r['email'];
}

/*
 *  getDefaultNavUrl
 *  
 *  Gets the url for the 'Share' default link
 *
 *  @return  string of the url
 */
function getDefaultNavUrl ()
{
    $sql = "SELECT `link` FROM `fcms_navigation` WHERE `col` = 4 AND `order` = 1";
    $result = mysql_query($sql) or displaySQLError(
        'Nav Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    return getSectionUrl($r['link']);
}

/*
 *  getNavLinks
 *  
 *  Gets the links and order for the 'Share' sub menu
 *
 *  @return  an array of the info
 */
function getNavLinks ()
{
    $sql = "SELECT * FROM `fcms_navigation` WHERE `col` = 4 AND `order` != 0 ORDER BY `order`";
    $result = mysql_query($sql) or displaySQLError(
        'Nav Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $ret = array();
    while ($r = mysql_fetch_array($result)) {
        $ret[] = array(
            'url' => getSectionUrl($r['link']),
            'text' => getSectionName($r['link']),
        ); 
    }
    return $ret;
}

/*
 *  getSectionName
 *  
 *  Given the name of the section from the db, returns the translated text
 *
 *  @param      the name of the section from the navigation tbl
 *  @return     a string with the translated name
 */
function getSectionName ($section)
{
    switch ($section) {
        case 'photogallery':
            return _('Photo Gallery');
            break;
        case 'addressbook':
            return _('Address Book');
            break;
        case 'calendar':
            return _('Calendar');
            break;
        case 'familynews':
            return _('Family News');
            break;
        case 'recipes':
            return _('Recipes');
            break;
        case 'documents':
            return _('Documents');
            break;
        case 'prayers':
            return _('Prayers');
            break;
        default:
            return 'error';
            break;
    }
}

/*
 *  getSectionUrl
 *  
 *  Given the name of the section from the db, returns the url for that section
 *
 *  @param      the name of the section from the navigation tbl
 *  @return     a string with the url
 */
function getSectionUrl ($section)
{
    switch ($section) {
        case 'photogallery':
            return 'gallery/index.php';
            break;
        case 'addressbook':
            return 'addressbook.php';
            break;
        case 'calendar':
            return 'calendar.php';
            break;
        case 'familynews':
            return 'familynews.php';
            break;
        case 'recipes':
            return 'recipes.php';
            break;
        case 'documents':
            return 'documents.php';
            break;
        case 'prayers':
            return 'prayers.php';
            break;
        default:
            return 'home.php';
            break;
    }
}

function displayNewPM ($userid, $d = "")
{
    $sql = "SELECT `id` FROM `fcms_privatemsg` WHERE `to` = $userid AND `read` < 1";
    $result = mysql_query($sql) or displaySQLError('Get New PM', 'util_inc.php [' . __LINE__ . ']', $sql, mysql_error());
    if (mysql_num_rows($result) > 0) {
        echo "<a href=\"" . $d . "privatemsg.php\" class=\"new_pm\">" . _('New PM') . "</a> ";
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

function getAccessLevel ($userid)
{
    $result = mysql_query("SELECT access FROM fcms_users WHERE id = $userid") or die('<h1>Access Error (util.inc.php 47)</h1>' . mysql_error());
    $r = mysql_fetch_array($result);
    $access = _('Member');
    switch ($r['access']) {
        case 1:
            $access = _('Admin');
            break;
        case 2:
            $access = _('Helper');
            break;
        case 3:
            $access = _('Member');
            break;
        case 4:
            $access = _('Non-Poster');
            break;
        case 5:
            $access = _('Non-Photographer');
            break;
        case 6:
            $access = _('Commenter');
            break;
        case 7:
            $access = _('Poster');
            break;
        case 8:
            $access = _('Photographer');
            break;
        case 9:
            $access = _('Blogger');
            break;
        case 10:
            $access = _('Guest');
            break;
    }
    return $access;
}

function parse ($data, $d = '')
{
    $data = htmlentities($data, ENT_COMPAT, 'UTF-8');
    $data = parse_smilies($data, $d);
    $data = parse_bbcodes($data);
    $data = bbcode_quote($data);
    $data = nl2br_nospaces($data);
    return $data;
}

function parse_bbcodes ($data)
{
    $search = array(
        '/\[ins\](.*?)\[\/ins\]/is', 
        '/\[del\](.*?)\[\/del\]/is', 
        '/\[h1\](.*?)\[\/h1\]/is', 
        '/\[h2\](.*?)\[\/h2\]/is', 
        '/\[h3\](.*?)\[\/h3\]/is', 
        '/\[h4\](.*?)\[\/h4\]/is', 
        '/\[h5\](.*?)\[\/h5\]/is', 
        '/\[h6\](.*?)\[\/h6\]/is', 
        '/\[b\](.*?)\[\/b\]/is', 
        '/\[i\](.*?)\[\/i\]/is', 
        '/\[u\](.*?)\[\/u\]/is', 
        '/\[url\=(.*?)\](.*?)\[\/url\]/is', 
        '/\[url\](.*?)\[\/url\]/is', 
        '/\[align\=(left|center|right)\](.*?)\[\/align\]/is', 
        '/\[img\=(.*?)\]/is', 
        '/\[img\](.*?)\[\/img\]/is', 
        '/\[mail\=(.*?)\](.*?)\[\/mail\]/is', 
        '/\[mail\](.*?)\[\/mail\]/is', 
        '/\[font\=(.*?)\](.*?)\[\/font\]/is', 
        '/\[size\=(.*?)\](.*?)\[\/size\]/is', 
        '/\[color\=(.*?)\](.*?)\[\/color\]/is', 
        '/\[span\](.*?)\[\/span\]/is', 
        '/\[span\=(.*?)\](.*?)\[\/span\]/is', 
        '/\[video\](.*?)\[\/video\]/ise'
    );
    $replace = array(
        '<ins>$1</ins>', 
        '<del>$1</del>', 
        '<h1>$1</h1>', 
        '<h2>$1</h2>', 
        '<h3>$1</h3>', 
        '<h4>$1</h4>', 
        '<h5>$1</h5>', 
        '<h6>$1</h6>', 
        '<b>$1</b>', 
        '<i>$1</i>', 
        '<u>$1</u>', 
        '<a href="$1">$2</a>', 
        '<a href="$1">$1</a>', 
        '<div style="text-align: $1;">$2</div>', 
        '<img src="$1"/>', 
        '<img src="$1"/>', 
        '<a href="mailto:$1">$2</a>', 
        '<a href="mailto:$1">$1</a>', 
        '<span style="font-family: $1;">$2</span>', 
        '<span style="font-size: $1;">$2</span>', 
        '<span style="color: $1;">$2</span>', 
        '<span>$1</span>', 
        '<span class="$1">$2</span>',
        'unhtmlentities("\\1")'
    );
    $data = preg_replace ($search, $replace, $data);
    return $data; 
}

// Used for PHP 4 and less
if (!function_exists('stripos')) {
    function stripos($haystack, $needle, $offset = 0) {
        return strpos(strtolower($haystack), strtolower($needle), $offset);
    }
}

// If php is compiled without mbstring support
if (!function_exists('mb_detect_encoding')) {
    function mb_detect_encoding($text) {
        return 'UTF-8';
    }
    function mb_convert_encoding($text,$target_encoding,$source_encoding) {
        return $text;
    }
}


function parse_smilies ($data, $d = '')
{
    global $smiley_array, $smiley_file_array, $smileydir;
    $i = 0;
    while($i < count($smiley_array)) {
        $data = str_replace($smiley_array[$i], '<img src="' . $d . $smileydir . $smiley_file_array[$i] . '" alt="'. $smiley_array[$i] . '" />', $data);
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
    global $smiley_array, $smiley_file_array;
    $i=0;
    $previous_smiley_file = '';
    foreach ($smiley_array as $smiley) {
        if ($smiley_file_array[$i] != $previous_smiley_file) {
            echo '<div class="smiley"><img src="../themes/smileys/' . $smiley_file_array[$i] . '" alt="' . $smiley . '" onclick="return addSmiley(\''.str_replace("'", "\'", $smiley).'\')" /></div>';
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

/**
 * getPostsById
 * 
 * Gets the post count and percentage of total posts for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getPostsById ($user_id, $option = 'both')
{
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_board_posts`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_board_posts` WHERE `user` = $user_id";
    $result = mysql_query($sql) or displaySQLError(
        'Count Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getPhotosById
 * 
 * Gets the photo count and percentage of total posts for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getPhotosById ($user_id, $option = 'both')
{
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_gallery_photos`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_gallery_photos` WHERE `user` = $user_id";
    $result = mysql_query($sql) or displaySQLError(
        'Count Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getCommentsById
 * 
 * Gets the news/gallery comment count and percentage of total news/gallery for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getCommentsById ($user_id, $option = 'both')
{
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_gallery_comments`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Gallery Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_gallery_comments` WHERE `user` = $user_id";
    $result = mysql_query($sql) or displaySQLError(
        'Count Gallery Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];

    // Check Family News if applicable
    if (usingFamilyNews()) {
        $sql = "SELECT COUNT(`id`) AS c FROM `fcms_news_comments`";
        $result = mysql_query($sql) or displaySQLError(
            'Total News Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $found = mysql_fetch_array($result);
        $total = $total + $found['c'];
        $sql = "SELECT COUNT(`user`) AS c FROM `fcms_news_comments` WHERE `user` = $user_id";
        $result = mysql_query($sql) or displaySQLError(
            'Count News Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $found = mysql_fetch_array($result);
        $count = $count + $found['c'];
    }
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getCalendarEntriesById
 * 
 * Gets the calendar entries count and percentage of total for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getCalendarEntriesById ($user_id, $option = 'both')
{
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_calendar`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_calendar` WHERE `created_by` = $user_id";
    $result = mysql_query($sql) or displaySQLError(
        'Count Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getFamilyNewsById
 * 
 * Gets the news count and percentage of total news for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getFamilyNewsById ($user_id, $option = 'both')
{
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_news`";
    $result = mysql_query($sql) or displaySQLError(
        'Total News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_news` WHERE `user` = $user_id GROUP BY `user`";
    $result = mysql_query($sql) or displaySQLError(
        'Count News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getRecipesById
 * 
 * Gets the recipes count and percentage of total for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getRecipesById ($user_id, $option = 'both')
{
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_recipes`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Recipes Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_recipes` WHERE `user` = $user_id GROUP BY `user`";
    $result = mysql_query($sql) or displaySQLError(
        'Count Recipes Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getDocumentsById
. * 
 * Gets the documents count and percentage of total for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getDocumentsById ($user_id, $option = 'both')
{
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_documents`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_documents` WHERE `user` = $user_id GROUP BY `user`";
    $result = mysql_query($sql) or displaySQLError(
        'Count Documents Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getPrayersById
 * 
 * Gets the prayers count and percentage of total for the givin user
 * @param   user_id     the id of the desired user
 * @param   option      how you want the data returned
 *                          count - returns just the count
 *                          percent - returns just the percent
 *                          array - returns both, but in an array
 *                          both - returns both in "X (X%)" format
 * @return  a string or array of strings
 */
function getPrayersById ($user_id, $option = 'both')
{
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_prayers`";
    $result = mysql_query($sql) or displaySQLError(
        'Total Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $total = $found['c'];
    $sql = "SELECT COUNT(`id`) AS c FROM `fcms_prayers` WHERE `user` = $user_id GROUP BY `user`";
    $result = mysql_query($sql) or displaySQLError(
        'Count Prayers Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $count = $found['c'];
    if ($total < 1 || $count < 1) {
        $count = '0';
        $percent = '0%';
    } else {
        $percent = round((($count/$total)*100), 1) . '%';
    }
    switch($option) {
        case 'count':
            return $count;
            break;
        case 'percent':
            return $percent;
            break;
        case 'array':
            return array('count' => $count, 'percent' => $percent);
        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

function getNewsComments ($news_id)
{
    $result = mysql_query("SELECT count(id) AS c FROM fcms_news_comments WHERE news = $news_id") or die('<h1>Count Error (util.inc.php 134)</h1>' . mysql_error());
    $found = mysql_fetch_array($result);
    return  $found['c'] ? $found['c'] : 0;
}

function getUserRankById ($user_id)
{
    $points = 0;
    $news_count = 0;
    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_board_posts` WHERE `user` = $user_id";
    $result = mysql_query($sql)  or displaySQLError(
        'Count Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $post_count = $found['c'];
    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_gallery_photos` WHERE `user` = $user_id";
    $result = mysql_query($sql)  or displaySQLError(
        'Count Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $photo_count = $found['c'];
    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_gallery_comments` WHERE `user` = $user_id";
    $result = mysql_query($sql)  or displaySQLError(
        'Count Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $comments_count = $found['c'];
    $sql = "SELECT COUNT(`user`) AS c FROM `fcms_poll_votes` WHERE `user` = $user_id";
    $result = mysql_query($sql)  or displaySQLError(
        'Count Polls Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $vote_count = $found['c'];
    $sql = "SELECT COUNT(`created_by`) AS c FROM `fcms_calendar` WHERE `created_by` = $user_id";
    $result = mysql_query($sql)  or displaySQLError(
        'Count Calendar Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $found = mysql_fetch_array($result);
    $calendar_count = $found['c'];
    if (usingFamilyNews()) {
        $sql = "SELECT COUNT(`user`) AS c FROM `fcms_news` WHERE `user` = $user_id";
        $result = mysql_query($sql)  or displaySQLError(
            'Count News Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $found = mysql_fetch_array($result);
        $news_count = $found['c'];
    }
    $points = ($post_count / 75) + 
              ($photo_count / 25) + 
              ($comments_count / 20) + 
              ($calendar_count / 5) + 
              ($news_count / 10) + 
              ($vote_count / 10);
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
    echo '
            <div id="toolbar" class="toolbar hideme">
                <input type="button" class="bold button" onclick="bb.insertCode(\'B\', \'bold\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Bold').'" />
                <input type="button" class="italic button" onclick="bb.insertCode(\'I\', \'italic\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Italic').'"/>
                <input type="button" class="underline button" onclick="bb.insertCode(\'U\', \'underline\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Underline').'"/>
                <input type="button" class="left_align button" onclick="bb.insertCode(\'ALIGN=LEFT\', \'left right\', \'ALIGN\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Left Align').'"/>
                <input type="button" class="center_align button" onclick="bb.insertCode(\'ALIGN=CENTER\', \'center\', \'ALIGN\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Center').'"/>
                <input type="button" class="right_align button" onclick="bb.insertCode(\'ALIGN=RIGHT\', \'align right\', \'ALIGN\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Right Align').'"/>
                <input type="button" class="h1 button" onclick="bb.insertCode(\'H1\', \'heading 1\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Heading 1').'"/>
                <input type="button" class="h2 button" onclick="bb.insertCode(\'H2\', \'heading 2\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Heading 2').'"/>
                <input type="button" class="h3 button" onclick="bb.insertCode(\'H3\', \'heading 3\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Heading 3').'"/>
                <input type="button" class="board_quote button" onclick="bb.insertCode(\'QUOTE\', \'quote\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Quote').'"/>
                <input type="button" class="board_images button" onclick="window.open(\'inc/upimages.php\',\'name\',\'width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no\'); return false;" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Insert Image').'"/>
                <input type="button" class="links button" onclick="bb.insertLink();" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Insert URL').'"/>
                <input type="button" class="smileys button" onclick="window.open(\'inc/smileys.php\',\'name\',\'width=500,height=200,scrollbars=no,resizable=no,location=no,menubar=no,status=no\'); return false;" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('Insert Smiley').'"/>
                <input type="button" class="help button" onclick="window.open(\'inc/bbcode.php\',\'name\',\'width=400,height=300,scrollbars=yes,resizable=no,location=no,menubar=no,status=no\'); return false;" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'._('BBCode Help').'"/>
            </div>';
}

function uploadImages ($filetype, $filename, $filetmpname, $destination, $max_h, $max_w, $unique = 'no')
{
    $known_photo_types = array(
        'image/pjpeg' => 'jpeg', 
        'image/jpeg' => 'jpg', 
        'image/gif' => 'gif', 
        'image/bmp' => 'bmp', 
        'image/x-png' => 'png', 
        'image/png' => 'png'
    );
    $gd_function_suffix = array(
        'image/pjpeg' => 'JPEG', 
        'image/jpeg' => 'JPEG', 
        'image/gif' => 'GIF', 
        'image/bmp' => 'WBMP', 
        'image/x-png' => 'PNG', 
        'image/png' => 'PNG'
    );
    $ext = explode('.', $filename);
    $ext = end($ext);
    // Check mime type
    if (!array_key_exists($filetype, $known_photo_types)) {
        echo '
            <p class="error-alert">
                '.sprintf(_('Error: File %s is not a photo.  Photos must be of type (.JPG, .JPEG, .GIF, .BMP or .PNG).'), $filetype).'
            </p>';
    // Check file extension
    } elseif (!in_array($ext, $known_photo_types)) {
        echo '
            <p class="error-alert">
                '.sprintf(_('Error: File %s is not a photo.  Photos must be of type (.JPG, .JPEG, .GIF, .BMP or .PNG).'), $filetype).'
            </p>';
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


/**
 * displayPages
 * 
 * Function renamed in 2.0, needs to stay until old calls are updated.
 */
function displayPages ($url, $cur_page, $total_pages)
{
    displayPagination($url, $cur_page, $total_pages);
}

/**
 * displayPagination
 * 
 * Displays the pagination links.
 *
 * @param   url             the url of the page (index.php?uid=0)
 * @param   cur_page        the current page #
 * @param   total_pages     The total # of pages needed
 * @return  nothing
 */
function displayPagination ($url, $cur_page, $total_pages)
{
    // Check if we have a index.php url or a index.php?uid=0 url
    $end = substr($url, strlen($url) - 4);
    if ($end == '.php') {
        $divider = '?';
    } else {
        $divider = '&amp;';
    }

    if ($total_pages > 1) {
        echo '
            <div class="pages clearfix">
                <ul>';

        // First / Previous
        if ($cur_page > 1) {
            $prev = ($cur_page - 1);
            echo '
                    <li><a title="'._('First Page').'" class="first" href="'.$url.$divider.'page=1">'._('First').'</a></li>
                    <li><a title="'._('Previous Page').'" class="previous" href="'.$url.$divider.'page='.$prev.'">'._('Previous').'</a></li>';
        } else {
            echo '
                    <li><a title="'._('First Page').'" class="first" href="'.$url.$divider.'page=1">'._('First').'</a></li>
                    <li><a title="'._('Previous Page').'" class="previous" href="'.$url.$divider.'page=1">'._('Previous').'</a></li>';
        }

        // Numbers
        if ($total_pages > 8) {
            if ($cur_page > 2) {
                for ($i = ($cur_page-2); $i <= ($cur_page+5); $i++) {
                    if ($i <= $total_pages) {
                        $class = $cur_page == $i ? ' class="current"' : '';
                        echo '
                    <li><a href="'.$url.$divider.'page='.$i.'"'.$class.'>'.$i.'</a></li>';
                    }
                } 
            } else {
                for ($i = 1; $i <= 8; $i++) {
                    $class = $cur_page == $i ? ' class="current"' : '';
                    echo '
                    <li><a href="'.$url.$divider.'page='.$i.'"'.$class.'>'.$i.'</a></li>';
                } 
            }
        } else {
            for ($i = 1; $i <= $total_pages; $i++) {
                $class = $cur_page == $i ? ' class="current"' : '';
                echo '
                    <li><a href="'.$url.$divider.'page='.$i.'"'.$class.'>'.$i.'</a></li>';
            } 
        }

        // Next / Last
        if ($cur_page < $total_pages) { 
            $next = ($cur_page + 1);
            echo '
                    <li><a title="'._('Next Page').'" class="next" href="'.$url.$divider.'page='.$next.'">'._('Next').'</a></li>
                    <li><a title="'._('Last page').'" class="last" href="'.$url.$divider.'page='.$total_pages.'">'._('Last').'</a></li>';
        } else {
            echo '
                    <li><a title="'._('Next Page').'" class="next" href="'.$url.$divider.'page='.$total_pages.'">'._('Next').'</a></li>
                    <li><a title="'._('Last page').'" class="last" href="'.$url.$divider.'page='.$total_pages.'">'._('Last').'</a></li>';
        } 
        echo '
                </ul>
            </div>';
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
    $last15min = time() - (60 * 15);
    $lastday = time() - (60 * 60 * 24);
    $sql_last15min = mysql_query("SELECT * FROM fcms_users WHERE UNIX_TIMESTAMP(activity) >= $last15min ORDER BY `activity` DESC") or die('<h1>Online Error (util.inc.php 246)</h1>' . mysql_error());
    $sql_lastday = mysql_query("SELECT * FROM fcms_users WHERE UNIX_TIMESTAMP(activity) >= $lastday ORDER BY `activity` DESC") or die('<h1>Online Error (util.inc.php 247)</h1>' . mysql_error());
    echo '
            <h3>'._('Now').':</h3>
            <p>';
    $i = 1;
    $onlinenow_array = array();
    while ($e = mysql_fetch_array($sql_last15min)) {
        $displayname = getUserDisplayName($e['id']);
        $onlinenow_array[$i] = $e['id'];
        $i++;
        echo '
                <a class="member" href="profile.php?member='.$e['id'].'">'.$displayname.'</a><br/>';
    }
    echo '
            </p>
            <h3>'._('Last 24 Hours').':</h3>
            <p>';
    while ($d = mysql_fetch_array($sql_lastday)) {
        $displayname = getUserDisplayName($d['id']);
        if (!array_search((string)$d['id'], $onlinenow_array)) {
            echo '
                <a class="member" href="profile.php?member='.$d['id'].'">'.$displayname.'</a><br/>';
        }
    }
    echo '
            </p><br/><br/>';
}

/**
 * isLoggedIn
 * 
 * Checks whether user is logged in or not.  If user is logged in 
 * it just returns, if not, it redirects to login screen.
 * returns  boolean
 */
function isLoggedIn ($d = '')
{
    if ($d != '') {
        $up = '../';
    } else {
        $up = '';
    }

    // User has a session
    if (isset($_SESSION['login_id'])) {
        $id = $_SESSION['login_id'];
        $user = $_SESSION['login_uname'];
        $pass = $_SESSION['login_pw'];
    // User has a cookie
    } elseif (isset($_COOKIE['fcms_login_id'])) {
        $_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
        $_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
        $_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
        $id = $_SESSION['login_id'];
        $user = $_SESSION['login_uname'];
        $pass = $_SESSION['login_pw'];
    // User has nothing
    } else {
        $url = basename($_SERVER["REQUEST_URI"]);
        header("Location: {$up}index.php?err=login&url=$d$url");
        exit();
    }

    // Make sure id is a digit
    if (!ctype_digit($id)) {
        $url = basename($_SERVER["REQUEST_URI"]);
        header("Location: {$up}index.php?err=login&url=$d$url");
        exit();
    }

    // User's session/cookie credentials are good
    if (checkLoginInfo($id, $user, $pass)) {
        $sql = "SELECT `access`, `site_off` 
                FROM `fcms_users` AS u, `fcms_config` 
                WHERE u.`id` = ".escape_string($id)." LIMIT 1";
        $result = mysql_query($sql) or displaySQLError(
            'Site Status Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $r = mysql_fetch_array($result);
        // Site is off and your not an admin
        if ($r['site_off'] == 1 && $r['access'] > 1) {
            header("Location: {$up}index.php?err=off");
            exit();
        // Good login, you may proceed
        } else {
            return;
        }
    // The user's session/cookie credentials are bad
    } else {
        header("Location: {$up}index.php?err=login");
        exit();
    }
}

/**
 * checkLoginInfo
 * 
 * Checks the user's username/pw combo
 *
 * @param   $userid     the id of the user you want to check
 * @param   $username   the username of the user
 * @param   $password   the password of the user
 * returns  boolean
 */
function checkLoginInfo ($userid, $username, $password)
{
    $sql = "SELECT `username`, `password` FROM `fcms_users` WHERE `id` = $userid LIMIT 1";
    $result = mysql_query($sql) or displaySQLError(
        'Login Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        $r = mysql_fetch_array($result);
        if ($r['username'] !== $username) {
            return false;
        } elseif ($r['password'] !== $password) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
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

/*
 *  To find out which optional sections are being used:
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
    $sql = "SELECT * FROM `fcms_navigation` WHERE `link` = '$i' LIMIT 1";
    $result = mysql_query($sql) or displaySQLError(
        'Section Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    if (mysql_num_rows($result) > 0) {
        $r = mysql_fetch_array($result);
        if ($r['order'] > 0) {
            return true;
        }
    }
    return false;
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
    global $cfg_mysql_host, $cfg_use_news, $cfg_use_prayers;
    $locale = new Locale();
    $sql = "SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $userid";
    $t_result = mysql_query($sql) or displaySQLError(
        'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $t = mysql_fetch_array($t_result);
    $tz_offset = $t['timezone'];
    $today = date('Y-m-d');
    $yesterday  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));
    $sql = "SELECT p.`id`, `date`, `subject` AS title, u.`id` AS userid, `thread` AS id2, 0 AS id3, 'BOARD' AS type 
            FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, fcms_users AS u 
            WHERE p.`thread` = t.`id` 
            AND p.`user` = u.`id` 
            AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 

            UNION SELECT a.id, a.updated AS date, 0 AS title, a.user AS userid, a.entered_by AS id2, u.joindate AS id3, 'ADDRESSEDIT' AS type
            FROM fcms_address AS a, fcms_users AS u
            WHERE a.user = u.id
            AND DATE_FORMAT(a.updated, '%Y-%m-%d %h') != DATE_FORMAT(u.joindate, '%Y-%m-%d %h') 
            AND a.updated >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 

            UNION SELECT a.id, a.updated AS date, 0 AS title, a.user AS userid, a.entered_by AS id2, u.joindate AS id3, 'ADDRESSADD' AS type
            FROM fcms_address AS a, fcms_users AS u
            WHERE a.user = u.id
            AND u.`password` = 'NONMEMBER' 
            AND a.updated >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 

            UNION SELECT `id`, `joindate` AS date, 0 AS title, `id` AS userid, 0 AS id2, 0 AS id3, 'JOINED' AS type 
            FROM `fcms_users` 
            WHERE `password` != 'NONMEMBER' 
            AND `activated` > 0 
            AND `joindate` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    if (usingFamilyNews()) {
        $sql .= "UNION SELECT n.`id` AS id, n.`date`, `title`, u.`id` AS userid, 0 AS id2, 0 AS id3, 'NEWS' AS type 
                 FROM `fcms_users` AS u, `fcms_news` AS n 
                 WHERE u.`id` = n.`user` 
                 AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 
                 AND `username` != 'SITENEWS' 
                 AND `password` != 'SITENEWS' ";
    }
    if (usingPrayers()) {
        $sql .= "UNION SELECT 0 AS id, `date`, `for` AS title, `user` AS userid, 0 AS id2, 0 AS id3, 'PRAYERS' AS type 
                 FROM `fcms_prayers` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    }
    if (usingRecipes()) {
        $sql .= "UNION SELECT `id` AS id, `date`, `name` AS title, `user` AS userid, `category` AS id2, 0 AS id3, 'RECIPES' AS type 
                 FROM `fcms_recipes` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    }
    if (usingdocuments()) {
        $sql .= "UNION SELECT d.`id` AS 'id', d.`date`, `name` AS title, d.`user` AS userid, 0 AS id2, 0 AS id3, 'DOCS' AS type 
                 FROM `fcms_documents` AS d, `fcms_users` AS u 
                 WHERE d.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                 AND d.`user` = u.`id` ";
    }
    $sql .= "UNION SELECT DISTINCT p.`category` AS id, `date`, `name` AS title, p.`user` AS userid, COUNT(*) AS id2, DAYOFYEAR(`date`) AS id3, 'GALLERY' AS type 
             FROM `fcms_gallery_photos` AS p, `fcms_users` AS u, `fcms_gallery_category` AS c 
             WHERE p.`user` = u.`id` 
             AND p.`category` = c.`id` 
             AND 'date' >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
             GROUP BY userid, title, id3 ";
    if (usingFamilyNews()) {
        $sql .= "UNION SELECT n.`id` AS 'id', nc.`date`, `title`, nc.`user` AS userid, 0 AS id2, 0 AS id3, 'NEWSCOM' AS type 
                 FROM `fcms_news_comments` AS nc, `fcms_news` AS n, `fcms_users` AS u 
                 WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                 AND nc.`user` = u.`id` 
                 AND n.`id` = nc.`news` ";
    }
    $sql .= "UNION SELECT p.`id`, gc.`date`, `comment` AS title, gc.`user` AS userid, p.`user` AS id2, `filename` AS id3, 'GALCOM' AS type 
             FROM `fcms_gallery_comments` AS gc, `fcms_users` AS u, `fcms_gallery_photos` AS p 
             WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
             AND gc.`user` = u.`id` 
             AND gc.`photo` = p.`id` 

             UNION SELECT c.`id`, c.`date_added` AS date, `title`, `created_by` AS userid, `date` AS id2, `type` AS id3, 'CALENDAR' AS type 
             FROM `fcms_calendar` AS c, `fcms_users` AS u 
             WHERE c.`date_added` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
             AND c.`created_by` = u.`id` AND `private` < 1 

             UNION SELECT `id`, `started` AS date, `question`, '0' AS userid, 'na' AS id2, 'na' AS id3, 'POLL' AS type 
             FROM `fcms_polls` 
             WHERE `started` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 

             ORDER BY date DESC LIMIT 0, 35";
    $result = mysql_query($sql) or displaySQLError(
        'Latest Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $lastday = '0-0';
    while ($r=mysql_fetch_array($result)) {
        $day = date('Y-m-d', strtotime($r['date']));
        if ($day != $lastday) {
            if ($day == $today) {
                echo '
                <p><b>'._('Today').'</b></p>';
            } elseif ($day == $yesterday) {
                echo '
                <p><b>'._('Yesterday').'</b></p>';
            } else {
                $date = $locale->fixDate('F j, Y', $tz_offset, $r['date']);
                echo '
                <p><b>'.$date.'</b></p>';
            }
        }
        $rdate = $locale->fixDate('g:i a', $tz_offset, $r['date']);
        if ($r['type'] == 'BOARD') {
            $check = mysql_query("SELECT min(`id`) AS id FROM `fcms_board_posts` WHERE `thread` = " . $r['id2']) or die("<h1>Thread or Post Error (util.inc.php 360)</h1>" . mysql_error());
            $minpost = mysql_fetch_array($check);
            $userName = getUserDisplayName($r['userid']);
            $userName = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$userName.'</a>';
            $subject = $r['title'];
            $pos = strpos($subject, '#ANOUNCE#');
            if ($pos !== false) {
                $subject = substr($subject, 9, strlen($subject)-9);
            }
            $title = htmlentities($subject, ENT_COMPAT, 'UTF-8');
            $subject = '<a href="messageboard.php?thread='.$r['id2'].'" title="'.$title.'">'.$subject.'</a>';
            if ($r['id'] == $minpost['id']) {
                $class = 'newthread';
                $text = sprintf(_('%s started the new thread %s.'), $userName, $subject);
            } else {
                $class = 'newpost';
                $text = sprintf(_('%s replied to %s.'), $userName, $subject);
            }
            echo '
                <p class="'.$class.'">
                    '.$text.'. <small><i>'.$rdate.'</i></small>
                </p>';
        } elseif ($r['type'] == 'JOINED') {
            // A new user joined the site
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            echo '
                <p class="newmember">'.sprintf(_('%s has joined the website.'), $displayname).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'ADDRESSEDIT') {
            // User updated his/her address
            $displayname = getUserDisplayName($r['id2']);
            $displayname = '<a class="u" href="profile.php?member='.$r['id2'].'">'.$displayname.'</a>';
            $address = '<a href="addressbook.php?address='.$r['id'].'">'._('address').'</a>';
            echo '
                <p class="newaddress">'.sprintf(_('%s has updated his/her %s.'), $displayname, $address).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'ADDRESSADD') {
            // A user has added an address for a non-member
            $displayname = getUserDisplayName($r['id2']);
            $displayname = '<a class="u" href="profile.php?member='.$r['id2'].'">'.$displayname.'</a>';
            $for = '<a href="addressbook.php?address='.$r['id'].'">'.getUserDisplayName($r['userid'], 2, false).'</a>';
            echo '
                <p class="newaddress">'.sprintf(_('%s has added address information for %s.'), $displayname, $for).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'NEWS') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $news = '<a href="familynews.php?getnews='.$r['userid'].'&amp;newsid='.$r['id'].'">'.$r['title'].'</a>'; 
            echo '
                <p class="newnews">'.sprintf(_('%s has added %s to his/her Family News.'), $displayname, $news).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'PRAYERS') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $for = '<a href="prayers.php">'.$r['title'].'</a>';
            echo '
                <p class="newprayer">'.sprintf(_('%s has added a Prayer Concern for %s.'), $displayname, $for).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'RECIPES') {
            switch ($r['id2']) {
                case _('Appetizer'): $url = "recipes.php?category=1&amp;id=".$r['id']; break;
                case _('Breakfast'): $url = "recipes.php?category=2&amp;id=".$r['id']; break;
                case _('Dessert'): $url = "recipes.php?category=3&amp;id=".$r['id']; break;
                case _('Entree (Meat)'): $url = "recipes.php?category=4&amp;id=".$r['id']; break;
                case _('Entree (Seafood)'): $url = "recipes.php?category=5&amp;id=".$r['id']; break;
                case _('Entree (Vegetarian)'): $url = "recipes.php?category=6&amp;id=".$r['id']; break;
                case _('Salad'): $url = "recipes.php?category=7&amp;id=".$r['id']; break;
                case _('Side Dish'): $url = "recipes.php?category=8&amp;id=".$r['id']; break;
                case _('Soup'): $url = "recipes.php?category=9&amp;id=".$r['id']; break;
                default: $url = "recipes.php"; break;
            }
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $rec = '<a href="'.$url.'">'.$r['title'].'</a>';
            echo '
                <p class="newrecipe">'.sprintf(_('%s has added the %s recipe.'), $displayname, $rec).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'DOCS') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $doc = '<a href="documents.php">'.$r['title'].'</a>';
            echo '
                <p class="newdocument">'.sprintf(_('%s has added a new Document (%s).'), $displayname, $doc).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'GALLERY') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $cat = '<a href="gallery/index.php?uid='.$r['userid'].'&amp;cid='.$r['id'].'">'.$r['title'].'</a>';
            echo '
                    <p class="newphoto">
                        '.sprintf(_('%s has added %d new photos to the %s category.'), $displayname, $r['id2'], $cat).' <small><i>'.$rdate.'</i></small><br/>';
            $limit = 4;
            if ($r['id2'] < $limit) {
                $limit = $r['id2'];
            }
            $sql = "SELECT * 
                    FROM `fcms_gallery_photos` 
                    WHERE `category` = ".$r['id']." 
                    AND DAYOFYEAR(`date`) = ".$r['id3']." 
                    ORDER BY `date` 
                    DESC LIMIT $limit";
            $photos = mysql_query($sql) or displaySQLError(
                'Photos Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
            while ($p=mysql_fetch_array($photos)) {
                echo '
                        <a href="gallery/index.php?uid='.$r['userid'].'&amp;cid='.$r['id'].'&amp;pid='.$p['id'].'">
                            <img src="gallery/photos/member'.$r['userid'].'/tb_'.$p['filename'].'" alt="'.htmlentities($p['caption'], ENT_COMPAT, 'UTF-8').'"/>
                        </a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            echo '
                    </p>';
        } elseif ($r['type'] == 'NEWSCOM') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $news = '<a href="familynews.php?getnews='.$r['userid'].'&amp;newsid='.$r['id'].'">'.$r['title'].'</a>';
            echo '
                    <p class="newcom">'.sprintf(_('%s commented on Family News %s.'), $displayname, $news).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'GALCOM') {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            echo '
                    <p class="newcom">
                        '.sprintf(_('%s commented on the following photo:'), $displayname).' <small><i>'.$rdate.'</i></small><br/>
                        <a href="gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$r['id'].'">
                            <img src="gallery/photos/member'.$r['id2'].'/tb_'.$r['id3'].'"/>
                        </a>
                    </p>';
        } elseif ($r['type'] == 'CALENDAR') {
            $date_date = $locale->fixDate('m-d-y', $tz_offset, $r['id2']);
            $date_date2 = $locale->fixDate('F j, Y', $tz_offset, $r['id2']);
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $for = '<a href="calendar.php?year='.date('Y', strtotime($date_date2))
                .'&amp;month='.date('m', strtotime($date_date2))
                .'&amp;day='.date('d', strtotime($date_date2)).'">'.$r['title'].'</a>';
            echo '
                    <p class="newcal">'.sprintf(_('%s has added a new Calendar entry on %s for %s.'), $displayname, $date_date, $for).' <small><i>'.$rdate.'</i></small></p>';
        } elseif ($r['type'] == 'POLL') {
            $poll = '<a href="home.php?poll_id='.$r['id'].'">'.$r['title'].'</a>';
            echo '
                <p class="newpoll">'.sprintf(_('A new Poll (%s) has been added.'), $poll).' <small><i>'.$rdate.'</i></small></p>';
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

/*
 *  usingAdvancedUploader
 *  
 *  @param  $userid      the id of the desired user
 *  @return  bolean
 */
function usingAdvancedUploader ($userid)
{
    $sql = "SELECT `advanced_upload` FROM `fcms_user_settings` WHERE `user` = $userid ";
    $result = mysql_query($sql) or displaySQLError(
        'Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    if ($r['advanced_upload'] == 1) {
        return true;
    } else {
        return false;
    }
}

/**
 * multi_array_key_exists
 *
 * @param   $needle     The key you want to check for
 * @param   $haystack   The array you want to search
 * @return  boolean
 */
function multi_array_key_exists ($needle, $haystack)
{
    foreach ($haystack as $key => $value) {
        if ($needle == $key) {
            return true;
        }
        if (is_array($value)) {
            if (multi_array_key_exists($needle, $value) == true) {
                return true;
            } else {
                continue;
            }
        }
    }
    return false;
}

/**
 * getLangName
 *
 * Given a gettext language code, it returns the translated
 * language full name
 *
 * @param   $code       the code for the language name
 * @return  string      the translated language name
 */
function getLangName ($code)
{
    switch($code) {
        case 'cs_CZ':
            return _('Czech (Czech Republic)');
            break;
        case 'da_DK':
            return _('Danish (Denmark)');
            break;
        case 'de_DE':
            return _('German (Germany)');
            break;
        case 'en_US':
            return _('English (United States)');
            break;
        case 'es_ES':
            return _('Spanish (Spain)');
            break;
        case 'et':
            return _('Estonian');
            break;
        case 'fr_FR':
            return _('French (France)');
            break;
        case 'lv':
            return _('Latvian');
            break;
        case 'nl':
            return _('Dutch');
            break;
        case 'pt_BR':
            return _('Portuguese (Brazil)');
            break;
        case 'sk_SK':
            return _('Slovak');
            break;
        case 'zh_CN':
            return _('Chinese (China)');
            break;
        case 'x-wrap':
            return _('X Wrapped');
            break;
        default:
            return $code;
            break;
    }
}
?>
