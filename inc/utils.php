<?php

// Smileys
$smiley_array = array(':smile:', ':none:', ':)', '=)', ':wink:', ';)', ':tongue:', ':biggrin:', ':sad:', ':(', ':sick:', ':cry:', ':shocked:', ':cool:', ':sleep:', 'zzz', ':angry:', ':mad:', ':embarrassed:', ':shy:', 
    ':rolleyes:', ':nervous:', ':doh:', ':love:', ':please:', ':1please:', ':hrmm:', ':quiet:', ':clap:', ':twitch:', ':blah:', ':bored:', ':crazy:', ':excited:', ':noidea:', ':disappointed:', ':banghead:', 
    ':dance:', ':laughat:', ':ninja:', ':pirate:', ':thumbup:', ':thumbdown:', ':twocents:'
);
$smiley_file_array = array('smile.gif', 'smile.gif', 'smile.gif', 'smile.gif', 'wink.gif', 'wink.gif', 'tongue.gif', 'biggrin.gif', 'sad.gif', 'sad.gif', 'sick.gif', 'cry.gif', 'shocked.gif', 'cool.gif', 
    'sleep.gif', 'sleep.gif', 'angry.gif', 'angry.gif', 'embarrassed.gif', 'embarrassed.gif', 'rolleyes.gif', 'nervous.gif', 'doh.gif', 'love.gif', 'please.gif', 'please.gif', 'hrmm.gif', 'quiet.gif', 
    'clap.gif', 'twitch.gif', 'blah.gif', 'bored.gif', 'crazy.gif', 'excited.gif', 'noidea.gif', 'disappointed.gif', 'banghead.gif', 'dance.gif', 'laughat.gif', 'ninja.gif', 'pirate.gif', 'thumbup.gif', 
    'thumbdown.gif', 'twocents.gif'
);

/**
 * getEmailHeaders 
 * 
 * @param string $name 
 * @param string $email 
 * 
 * @return string
 */
function getEmailHeaders ($name = '', $email = '')
{
    if (empty($name)) {
        $name = getSiteName();
    }
    if (empty($email)) {
        $email = getContactEmail();
    }
    return "From: $name <$email>\r\n" . 
        "Reply-To: $email\r\n" . 
        "Content-Type: text/plain; charset=UTF-8;\r\n" . 
        "MIME-Version: 1.0\r\n" . 
        "X-Mailer: PHP/" . phpversion();
}

/**
 * getTheme 
 * 
 * @param   int     $userid 
 *
 * @return  void
 */
function getTheme ($userid = 0)
{
    if (empty($userid))
    {
        return UI."themes/default/";
    }
    else
    {
        $userid = (int)$userid;

        $sql = "SELECT `theme` 
                FROM `fcms_user_settings` 
                WHERE `user` = '$userid'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            return UI."themes/default/";
        }

        $r = mysql_fetch_array($result);

        // old versions of fcms may still list .css in theme name
        $pos = strpos($r['theme'], '.css');

        if ($pos === false)
        {
            return UI."themes/".basename($r['theme'])."/";
        }
        else
        {
            return UI."themes/".substr($r['theme'], 0, $pos)."/";
        }
    }
}

/**
 * getThemeList 
 * 
 * Returns an array of available themes.
 * 
 * @return array
 */
function getThemeList ()
{
    $dir = THEMES;

    $themes = array();

    if (is_dir($dir))
    {
        if ($dh = opendir($dir))
        {
            while (($file = readdir($dh)) !== false)
            {
                // Skip files
                if (filetype($dir.$file) !== "dir")
                {
                    continue;
                }
                // Skip directories that start with a period
                if ($file[0] === '.')
                {
                    continue;
                }

                $themes[] = $file;
            }

            closedir($dh);
            sort($themes);
        }
    }

    return $themes;
}

/*
 * getUserDisplayName
 *
 * Gets the user's name, displayed how they set in there settings, unless display option is set
 * which will overwrite the user's settings.
 * 
 * @param   int     $userid 
 * @param   int     $display 
 * @param   boolean $isMember 
 * @return  string
 */
function getUserDisplayName ($userid, $display = 0, $isMember = true)
{
    $userid = (int)$userid;

    if ($isMember)
    {
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, s.`displayname` 
                FROM `fcms_users` AS u, `fcms_user_settings` AS s 
                WHERE u.`id` = '$userid' 
                AND u.`id` = s.`user`";
    }
    else
    {
        $sql = "SELECT `fname`, `lname`, `username` 
                FROM `fcms_users` 
                WHERE `id` = '$userid' ";
    }

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '';
    }

    $r = mysql_fetch_assoc($result);

    // Do we want user's settings or overriding it?
    if ($display < 1)
    {
        $displayname = $r['displayname'];
    }
    else
    {
        $displayname = $display;
    }

    $ret = '';

    switch($displayname)
    {
        case '1':
            $ret = cleanOutput($r['fname']);
            break;

        case '2':
            $ret = cleanOutput($r['fname']).' '.cleanOutput($r['lname']);
            break;

        case '3':
            $ret = cleanOutput($r['username']);
            break;

        default:
            $ret = cleanOutput($r['username']);
            break;
    }

    return $ret;
}

/**
 * getPMCount 
 *
 * Returns a string consisting of the user's new pm count in ()'s
 * 
 * @return  string
 */
function getPMCount ()
{
    // Count was calculated during getUserNotifications()
    if (isset($_SESSION['private_messages']))
    {
        $count = $_SESSION['private_messages'];
    }
    else
    {
        $sql = "SELECT * FROM `fcms_privatemsg` 
                WHERE `read` < 1 
                AND `to` = '".(int)$_SESSION['login_id']."'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            return '';
        }

        $count = mysql_num_rows($result);
    }

    if ($count > 0)
    {
        return " ($count)";
    }

    return '';
}

/**
 * getNotificationCount 
 *
 * Returns a string consisting of the user's unread notification count in ()'s
 * 
 * @return  string
 */
function getNotificationCount ()
{
    // Count was calculated during getUserNotifications()
    if (isset($_SESSION['notifications']))
    {
        $count = $_SESSION['notifications'];
    }
    else
    {
        $sql = "SELECT `id` FROM `fcms_notification` 
                WHERE `read` < 1 
                AND `user` = '".(int)$_SESSION['login_id']."'
                AND `created_id` != '".(int)$_SESSION['login_id']."'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            return '';
        }

        $count = mysql_num_rows($result);
    }

    if ($count > 0)
    {
        return " ($count)";
    }

    return '';
}

/**
 * getUserEmail 
 * 
 * @param   string  $userid 
 * @return  string
 */
function getUserEmail ($userid)
{
    $userid = (int)$userid;

    $sql = "SELECT `email`
            FROM `fcms_users`
            WHERE `id` = '$userid'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 'nothing@mail.com';
    }

    $r = mysql_fetch_assoc($result);

    return $r['email'];
}

/**
 * getDefaultNavUrl 
 *
 * Gets the url for the 'Share' default link
 * 
 * @return  string
 */
function getDefaultNavUrl ()
{
    $sql = "SELECT `link` 
            FROM `fcms_navigation` 
            WHERE `col` = 4 
            AND `order` = 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 'gallery/index.php';
    }

    $r = mysql_fetch_assoc($result);

    return getPluginUrl($r['link']);
}

/**
 * getNavLinks 
 *
 * Gets the links and order for all the navigation.
 * Creates a multi-dimensional array of the nav, where
 * the first array is the navigation tabs and the second
 * is the links in those tabs.
 * 
 *      Home
 *      My Stuff
 *        - Profile
 *        - Settings
 *        - Private Messages
 *        - Notifications
 *      Communicate
 *        - Message Board
 *        - Family News
 *        - Prayer Concerns
 *      Share
 *        - Photo Gallery
 *        - Video Gallery
 *        - Address Book 
 *        - Calendar
 *        - Recipes
 *        - Family Tree
 *        - Documents
 *        - Where Is Everyone
 *      Misc.
 *        - Members
 *        - Contact Webmaster
 *        - Help
 *      Administration
 * 
 * @return  array
 */
function getNavLinks ()
{
    $ret = array();

    $sql = "SELECT `link`, `col`
            FROM `fcms_navigation` 
            WHERE `order` != 0 
            AND `col` != 6
            ORDER BY `col`, `order`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return $ret;
    }

    $currentUserId = (int)$_SESSION['login_id'];

    // Add links
    while ($r = mysql_fetch_array($result))
    {
        $ret['my-stuff'] = T_('My Stuff');

        // Notifications
        $notifications = getUserNotifications($currentUserId);
        if ($notifications > 0)
        {
            $ret['my-stuff'] = '<b>'.$notifications.'</b>'.$ret['my-stuff'];
        }

        $ret[$r['col']][] = array(
            'url'   => getPluginUrl($r['link']),
            'text'  => getPluginName($r['link']),
        ); 
    }

    // Add admin
    if (checkAccess($currentUserId) <= 2)
    {
        $ret[6][] = array(
            'url'   => 'index.php',
            'text'  => T_('Administration')
        ); 
    }

    return $ret;
}

/**
 * getAdminNavLinks 
 * 
 * @return void
 */
function getAdminNavLinks ()
{
    $ret = array();

    $sql = "SELECT `link`, `col`
            FROM `fcms_navigation` 
            WHERE `col` = 6
            ORDER BY `order`";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return $ret;
    }

    while ($r = mysql_fetch_array($result))
    {
        $ret[$r['link']] = array(
            'url'   => getPluginUrl($r['link']),
            'text'  => getPluginName($r['link']),
        ); 
    }

    return $ret;
}

/**
 * getPluginName 
 *
 * Given the name of the section from the db, returns the translated text
 * 
 * @param   string  $section 
 * @return  string
 */
function getPluginName ($section)
{
    switch ($section) {
        case 'admin_awards':
            return T_('Awards');
            break;
        case 'admin_configuration':
            return T_('Configuration');
            break;
        case 'admin_facebook':
            return 'Facebook';
            break;
        case 'admin_foursquare':
            return 'Foursquare';
            break;
        case 'admin_instagram':
            return 'Instagram';
            break;
        case 'admin_members':
            return T_('Members');
            break;
        case 'admin_photogallery':
            return T_('Photo Gallery');
            break;
        case 'admin_polls':
            return T_('Polls');
            break;
        case 'admin_scheduler':
            return T_('Scheduler');
            break;
        case 'admin_upgrade':
            return T_('Upgrade');
            break;
        case 'admin_vimeo':
            return 'Vimeo';
            break;
        case 'admin_whereiseveryone':
            return 'Foursquare';
            break;
        case 'admin_youtube':
            return 'YouTube';
            break;
        case 'addressbook':
            return T_('Address Book');
            break;
        case 'calendar':
            return T_('Calendar');
            break;
        case 'chat':
            return T_('Chat');
            break;
        case 'contact':
            return T_('Contact Webmaster');
            break;
        case 'documents':
            return T_('Documents');
            break;
        case 'familynews':
            return T_('Family News');
            break;
        case 'help':
            return T_('Help');
            break;
        case 'messageboard':
            return T_('Message Board');
            break;
        case 'members':
            return T_('Members');
            break;
        case 'notification':
            return T_('Notifications').getNotificationCount();
            break;
        case 'photogallery':
            return T_('Photo Gallery');
            break;
        case 'prayers':
            return T_('Prayers');
            break;
        case 'profile':
            return T_('Profile');
            break;
        case 'pm':
            return T_('Private Messages').getPMCount();
            break;
        case 'recipes':
            return T_('Recipes');
            break;
        case 'settings':
            return T_('Settings');
            break;
        case 'tree':
            return T_('Family Tree');
            break;
        case 'videogallery':
            return T_('Video Gallery');
            break;
        case 'whereiseveryone':
            return T_('Where Is Everyone');
            break;
        default:
            return 'error';
            break;
    }
}

/**
 * getPluginUrl 
 *
 * Given the name of the section from the db, returns the url for that section
 * 
 * @param   string  $section 
 * @return  string
 */
function getPluginUrl ($section)
{
    switch ($section) {
        case 'admin_awards':
            return 'admin/awards.php';
            break;
        case 'admin_configuration':
            return 'admin/config.php';
            break;
        case 'admin_facebook':
            return 'admin/facebook.php';
            break;
        case 'admin_foursquare':
            return 'admin/foursquare.php';
            break;
        case 'admin_instagram':
            return 'admin/instagram.php';
            break;
        case 'admin_members':
            return 'admin/members.php';
            break;
        case 'admin_photogallery':
            return 'admin/gallery.php';
            break;
        case 'admin_polls':
            return 'admin/polls.php';
            break;
        case 'admin_scheduler':
            return 'admin/scheduler.php';
            break;
        case 'admin_upgrade':
            return 'admin/upgrade.php';
            break;
        case 'admin_vimeo':
            return 'admin/vimeo.php';
            break;
        case 'admin_whereiseveryone':
            return 'admin/foursquare.php';
            break;
        case 'admin_youtube':
            return 'admin/youtube.php';
            break;
        case 'addressbook':
            return 'addressbook.php';
            break;
        case 'calendar':
            return 'calendar.php';
            break;
        case 'chat':
            return 'inc/chat/index.php';
            break;
        case 'contact':
            return 'contact.php';
            break;
        case 'documents':
            return 'documents.php';
            break;
        case 'familynews':
            return 'familynews.php';
            break;
        case 'help':
            return 'help.php';
            break;
        case 'messageboard':
            return 'messageboard.php';
            break;
        case 'members':
            return 'members.php';
            break;
        case 'notification':
            return 'notifications.php';
            break;
        case 'photogallery':
            return 'gallery/index.php';
            break;
        case 'prayers':
            return 'prayers.php';
            break;
        case 'profile':
            return 'profile.php';
            break;
        case 'pm':
            return 'privatemsg.php';
            break;
        case 'recipes':
            return 'recipes.php';
            break;
        case 'settings':
            return 'settings.php';
            break;
        case 'tree':
            return 'familytree.php';
            break;
        case 'videogallery':
            return 'video.php';
            break;
        case 'whereiseveryone':
            return 'whereiseveryone.php';
            break;
        default:
            return 'home.php';
            break;
    }
}

/**
 * getPluginDescription
 *
 * Given the name of the plugin from the db, returns the description.
 * 
 * @param string$plugin 
 * 
 * @return  string
 */
function getPluginDescription ($plugin)
{
    switch ($plugin) {
        case 'admin_awards':
            return T_('Awards');
            break;
        case 'admin_configuration':
            return T_('Configuration');
            break;
        case 'admin_facebook':
            return T_('Facebook');
            break;
        case 'admin_foursquare':
            return T_('Foursquare');
            break;
        case 'admin_members':
            return T_('Members');
            break;
        case 'admin_photogallery':
            return T_('Photo Gallery');
            break;
        case 'admin_polls':
            return T_('Polls');
            break;
        case 'admin_scheduler':
            return T_('Scheduler');
            break;
        case 'admin_upgrade':
            return T_('Upgrade');
            break;
        case 'admin_vimeo':
            return T_('Vimeo');
            break;
        case 'admin_whereiseveryone':
            return T_('Foursquare');
            break;
        case 'admin_youtube':
            return T_('YouTube');
            break;
        case 'addressbook':
            return T_('Allows members to share Address information.');
            break;
        case 'calendar':
            return T_('Allows members to share events and send invitations.');
            break;
        case 'chat':
            return T_('Chat');
            break;
        case 'contact':
            return T_('Contact Webmaster');
            break;
        case 'documents':
            return T_('Allows members to share files.');
            break;
        case 'familynews':
            return T_('Allows members to create a family blog.');
            break;
        case 'help':
            return T_('Help');
            break;
        case 'messageboard':
            return T_('Allows members to communicate with each other.');
            break;
        case 'members':
            return T_('Members');
            break;
        case 'photogallery':
            return T_('Allows members to share photos.');
            break;
        case 'prayers':
            return T_('Allows members to share prayer concerns.');
            break;
        case 'profile':
            return T_('Profile');
            break;
        case 'pm':
            return T_('Private Messages');
            break;
        case 'recipes':
            return T_('Allows members to share recipes.');
            break;
        case 'settings':
            return T_('Settings');
            break;
        case 'tree':
            return T_('Allows members to create a family tree.');
            break;
        case 'videogallery':
            return T_('Allows members to share videos.');
            break;
        case 'whereiseveryone':
            return T_('Allows members to share Foursquare checkins.');
            break;
        default:
            return 'error';
            break;
    }
}
/**
 * getUserNotifications 
 * 
 * @param int $userId 
 * 
 * @return mixed Returns # of notifications or false.
 */
function getUserNotifications ($userId)
{
    $notifications = 0;

    $_SESSION['private_messages'] = $notifications;

    // Private Messages
    $sql = "SELECT `id` FROM `fcms_privatemsg` 
            WHERE `read` < 1 
            AND `to` = '$userId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        logError(__FILE__.' ['.__LINE__.'] Could not get pm notifications.');
        return false;
    }

    if (mysql_num_rows($result) > 0)
    {
        $notifications += mysql_num_rows($result);

        $_SESSION['private_messages'] = $notifications;
    }

    // Tagged notifications
    $sql = "SELECT `id` FROM `fcms_notification` 
            WHERE `read` < 1 
            AND `user` = '$userId'
            AND `created_id` != '$userId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        logError(__FILE__.' ['.__LINE__.'] Could not get tagged notifications.');
        return false;
    }

    if (mysql_num_rows($result) > 0)
    {
        $tagged = mysql_num_rows($result);

        $notifications += $tagged;

        $_SESSION['notifications'] = $tagged;
    }

    return $notifications;
}

/**
 * displayNewPM 
 * 
 * @param   int     $userid 
 *
 * @return  void
 */
function displayNewPM ($userid)
{
    $userid = (int)$userid;

    $sql = "SELECT `id` 
            FROM `fcms_privatemsg` 
            WHERE `to` = '$userid' AND `read` < 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return ' ';
    }

    if (mysql_num_rows($result) > 0)
    {
        echo '<a href="'.URL_PREFIX.'privatemsg.php" class="new_pm">'.T_('New PM').'</a> ';
    }
    else
    {
        echo ' ';
    }
}

/**
 * checkAccess 
 *
 * Returns the access level as a number for the given user.
 * 
 * @param   int     $userid 
 * @return  int
 */
function checkAccess ($userid)
{
    $userid = (int)$userid;

    $sql = "SELECT `access` 
            FROM `fcms_users` 
            WHERE `id` = '$userid'";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '10';
    }

    if (mysql_num_rows($result) <= 0)
    {
        return '10'; // guest
    }

    $r = mysql_fetch_array($result);

    return $r['access'];
}

/**
 * getAccessLevel 
 *
 * Returns the access level name for the given user.
 * 
 * @param   int     $userid 
 * @return  string
 */
function getAccessLevel ($userid)
{
    $access = checkAccess($userid);

    $accessLevel = T_('Member');

    switch ($access) {
        case 1:
            $accessLevel = T_('Admin');
            break;
        case 2:
            $accessLevel = T_('Helper');
            break;
        case 3:
            $accessLevel = T_('Member');
            break;
        case 4:
            $accessLevel = T_('Non-Photographer');
            break;
        case 5:
            $accessLevel = T_('Non-Poster');
            break;
        case 6:
            $accessLevel = T_('Commenter');
            break;
        case 7:
            $accessLevel = T_('Poster');
            break;
        case 8:
            $accessLevel = T_('Photographer');
            break;
        case 9:
            $accessLevel = T_('Blogger');
            break;
        case 10:
            $accessLevel = T_('Guest');
            break;
        case 11:
            $accessLevel = T_('Non-editable Member');
            break;
    }
    return $accessLevel;
}

/**
 * parse 
 * 
 * @param   string  $data 
 *
 * @return  void
 */
function parse ($data)
{
    $data = htmlentities($data, ENT_COMPAT, 'UTF-8');
    $data = parse_smilies($data);
    $data = parse_bbcodes($data);
    $data = nl2br_nospaces($data);
    return $data;
}

/**
 * parse_bbcodes 
 * 
 * @param   string  $data 
 * @return  void
 */
function parse_bbcodes ($data)
{
    $search = getBBCodeList();
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
        '<blockquote>$1</blockquote>',
        'unhtmlentities("\\1")'
    );
    $data = preg_replace ($search, $replace, $data);
    return $data; 
}

/**
 * removeBBCode 
 * 
 * @param   string  $str 
 * @return  string
 */
function removeBBCode ($str)
{
    $search = getBBCodeList();
    $replace = array(
        '$1', // ins 
        '$1', // del
        '$1', // h1
        '$1', // h2
        '$1', // h3
        '$1', // h4
        '$1', // h5
        '$1', // h6
        '$1', // b
        '$1', // i
        '$1', // u
        '$2', // url
        '$1', // url 
        '$2', // align
        '',   // img
        '',   // img
        '$2', // mail
        '$1', // mail
        '$2', // font
        '$2', // size
        '$2', // color
        '$1', // span
        '$2', // span
        '$1', // quote
        '',   // video
    );
    return preg_replace($search, $replace, stripslashes($str));
}

/**
 * getBBCodeList 
 *
 * Returns an array of regex for the current list of BBCodes that FCMS supports.
 *
 * @return  array
 */
function getBBCodeList ()
{
    return array(
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
        '/\[quote\](.*?)\[\/quote\]/is', 
        '/\[video\](.*?)\[\/video\]/ise'
    );
}


/**
 * parse_smilies 
 * 
 * @param   string  $data 
 *
 * @return  void
 */
function parse_smilies ($data)
{
    global $smiley_array, $smiley_file_array;

    $i = 0;
    while($i < count($smiley_array))
    {
        $data = str_replace(
            $smiley_array[$i], 
            '<img src="'.URL_PREFIX.'ui/smileys/'.$smiley_file_array[$i].'" alt="'.$smiley_array[$i].'"/>', 
            $data
        );

        $i++;
    }

    return $data;
}

/**
 * nl2br_nospaces 
 * 
 * @param   string  $string 
 * @return  void
 */
function nl2br_nospaces ($string)
{
    $string = str_replace(array("\r\n", "\r", "\n"), "<br/>", $string); 
    return $string; 
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

/**
 * displaySmileys 
 * 
 * @return  void
 */
function displaySmileys ()
{
    global $smiley_array, $smiley_file_array;
    $i=0;
    $previous_smiley_file = '';
    foreach ($smiley_array as $smiley) {
        if ($smiley_file_array[$i] != $previous_smiley_file) {
            echo '<div class="smiley"><img src="../ui/smileys/' . $smiley_file_array[$i] . '" alt="' . $smiley . '" onclick="return addSmiley(\''.str_replace("'", "\'", $smiley).'\')" /></div>';
            $previous_smiley_file = $smiley_file_array[$i];
        }
        $i++;
    }
}

/**
 * escape_string 
 * 
 * @param   string  $string 
 * @return  string
 */
function escape_string ($string)
{
    if (version_compare(phpversion(), "4.3.0") == "-1") {
        return mysql_escape_string($string);
    } else {
        return mysql_real_escape_string($string);
    }
}

/**
 * cleanOutput 
 *
 * Cleans output from the db or from the user so it can be displayed.
 * 
 * @param   mixed   $output 
 * @param   string  $type 
 * @return  mixed
 */
function cleanOutput ($output, $type = 'string')
{
    // Strings that may contain HTML
    if ($type == 'html') {
        return htmlentities($output, ENT_COMPAT, 'UTF-8');
    }

    // Strings without HTML
    $output = strip_tags($output);
    return htmlentities($output, ENT_COMPAT, 'UTF-8');
}

/**
 * cleanFilename 
 *
 * Removes unwanted characters from a filename.
 * 
 * @param string $filename 
 * 
 * @return  void
 */
function cleanFilename ($filename)
{
    // convert spaces to underscores
    $filename = str_replace(" ", "_", $filename);

    // remove everything else but numbers and letters _ -
    $filename = preg_replace('/[^.A-Za-z0-9_-]/', '', $filename);

    return $filename;
}
/**
 * unhtmlentities 
 *
 * html_entity_decode for PHP 4.3.0 and earlier:
 * 
 * @param   string  $string 
 * @return  string
 */
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
    $user_id = (int)$user_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_board_posts`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_array($result);
    $total = $found['c'];

    $sql = "SELECT COUNT(`user`) AS c 
            FROM `fcms_board_posts` 
            WHERE `user` = '$user_id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_array($result);
    $count = $found['c'];

    if ($total < 1 || $count < 1)
    {
        $count = '0';
        $percent = '0%';
    }
    else
    {
        $percent = round((($count/$total)*100), 1) . '%';
    }

    switch($option)
    {
        case 'count':
            return $count;
            break;

        case 'percent':
            return $percent;
            break;

        case 'array':
            return array('count' => $count, 'percent' => $percent);
            break;

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
    $user_id = (int)$user_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_gallery_photos`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $total = $found['c'];

    $sql = "SELECT COUNT(`user`) AS c 
            FROM `fcms_gallery_photos` 
            WHERE `user` = '$user_id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $count = $found['c'];

    if ($total < 1 || $count < 1)
    {
        $count = '0';
        $percent = '0%';
    }
    else
    {
        $percent = round((($count/$total)*100), 1) . '%';
    }

    switch($option)
    {
        case 'count':
            return $count;
            break;

        case 'percent':
            return $percent;
            break;

        case 'array':
            return array('count' => $count, 'percent' => $percent);
            break;

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
    $user_id = (int)$user_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_gallery_photo_comment`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $total = $found['c'];

    $sql = "SELECT COUNT(`user`) AS c 
            FROM `fcms_gallery_photo_comment` 
            WHERE `user` = '$user_id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $count = $found['c'];

    // Check Family News if applicable
    if (usingFamilyNews())
    {
        $sql = "SELECT COUNT(`id`) AS c 
                FROM `fcms_news_comments`";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            return '0';
        }

        $found = mysql_fetch_assoc($result);
        $total = $total + $found['c'];

        $sql = "SELECT COUNT(`user`) AS c 
                FROM `fcms_news_comments` 
                WHERE `user` = '$user_id'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            return '0';
        }

        $found = mysql_fetch_assoc($result);
        $count = $count + $found['c'];
    }

    if ($total < 1 || $count < 1)
    {
        $count = '0';
        $percent = '0%';
    }
    else
    {
        $percent = round((($count/$total)*100), 1) . '%';
    }

    switch($option)
    {
        case 'count':
            return $count;
            break;

        case 'percent':
            return $percent;
            break;

        case 'array':
            return array('count' => $count, 'percent' => $percent);
            break;

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
    $user_id = (int)$user_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_calendar`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $total = $found['c'];

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_calendar` 
            WHERE `created_by` = '$user_id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $count = $found['c'];

    if ($total < 1 || $count < 1)
    {
        $count   = '0';
        $percent = '0%';
    }
    else
    {
        $percent = round((($count/$total)*100), 1) . '%';
    }

    switch ($option)
    {
        case 'count':
            return $count;
            break;

        case 'percent':
            return $percent;
            break;

        case 'array':
            return array('count' => $count, 'percent' => $percent);
            break;

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
    $user_id = (int)$user_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_news`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $total = $found['c'];

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_news` 
            WHERE `user` = '$user_id' 
            GROUP BY `user`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_array($result);
    $count = $found['c'];

    if ($total < 1 || $count < 1)
    {
        $count = '0';
        $percent = '0%';
    }
    else
    {
        $percent = round((($count/$total)*100), 1) . '%';
    }

    switch($option)
    {
        case 'count':
            return $count;
            break;

        case 'percent':
            return $percent;
            break;

        case 'array':
            return array('count' => $count, 'percent' => $percent);
            break;

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
    $user_id = (int)$user_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_recipes`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $total = $found['c'];

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_recipes` 
            WHERE `user` = '$user_id' 
            GROUP BY `user`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $count = $found['c'];

    if ($total < 1 || $count < 1)
    {
        $count = '0';
        $percent = '0%';
    }
    else
    {
        $percent = round((($count/$total)*100), 1) . '%';
    }

    switch($option)
    {
        case 'count':
            return $count;
            break;

        case 'percent':
            return $percent;
            break;

        case 'array':
            return array('count' => $count, 'percent' => $percent);
            break;

        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getDocumentsById
 * 
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
    $user_id = (int)$user_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_documents`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $total = $found['c'];

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_documents` 
            WHERE `user` = '$user_id' 
            GROUP BY `user`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_assoc($result);
    $count = $found['c'];

    if ($total < 1 || $count < 1)
    {
        $count = '0';
        $percent = '0%';
    }
    else
    {
        $percent = round((($count/$total)*100), 1) . '%';
    }

    switch($option)
    {
        case 'count':
            return $count;
            break;

        case 'percent':
            return $percent;
            break;

        case 'array':
            return array('count' => $count, 'percent' => $percent);
            break;

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
    $user_id = (int)$user_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_prayers`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_array($result);
    $total = $found['c'];

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_prayers` 
            WHERE `user` = '$user_id' 
            GROUP BY `user`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return '0';
    }

    $found = mysql_fetch_array($result);
    $count = $found['c'];

    if ($total < 1 || $count < 1)
    {
        $count = '0';
        $percent = '0%';
    }
    else
    {
        $percent = round((($count/$total)*100), 1) . '%';
    }

    switch($option)
    {
        case 'count':
            return $count;
            break;

        case 'percent':
            return $percent;
            break;

        case 'array':
            return array('count' => $count, 'percent' => $percent);
            break;

        case 'both':
        default:
            return "$count ($percent)";
            break;
    }
}

/**
 * getNewsComments 
 * 
 * @param   int     $news_id 
 * @return  void
 */
function getNewsComments ($news_id)
{
    $news_id = (int)$news_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_news_comments` 
            WHERE `news` = '$news_id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 0;
    }

    $found = mysql_fetch_array($result);

    return $found['c'] ? $found['c'] : 0;
}

/**
 * getUserParticipationPoints
 * 
 * Get the participation points for the given member.
 *
 *      Action      Points
 *      -------------------
 *      thread          5
 *      photo           3
 *      news            3
 *      recipe          2
 *      document        2
 *      prayer          2
 *      post            2
 *      comment         2
 *      address         1
 *      phone #         1
 *      date/event      1
 *      vote            1
 *
 * @param   int     $id 
 * @return  int
 */
function getUserParticipationPoints ($id)
{
    $id = (int)$id;

    $points = 0;

    $commentTables = array('fcms_gallery_photo_comment');

    // Thread (5)
    $sql = "SELECT COUNT(`id`) AS thread
            FROM `fcms_board_threads`
            WHERE `started_by` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 0;
    }

    $r = mysql_fetch_array($result);

    $points += $r['thread'] * 5;

    // Photo (3)
    $sql = "SELECT COUNT(`id`) AS photo 
            FROM `fcms_gallery_photos` 
            WHERE `user` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 0;
    }

    $r = mysql_fetch_array($result);

    $points += $r['photo'] * 3;

    // News (3)
    if (usingFamilyNews())
    {
        array_push($commentTables, 'fcms_news_comments');

        $sql = "SELECT COUNT(`id`) AS news 
                FROM `fcms_news` 
                WHERE `user` = '$id'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            return 0;
        }

        $r = mysql_fetch_array($result);

        $points += $r['news'] * 3;
    }

    // Recipe (2)
    if (usingRecipes())
    {
        array_push($commentTables, 'fcms_recipe_comment');

        $sql = "SELECT COUNT(`id`) AS recipe 
                FROM `fcms_recipes` 
                WHERE `user` = '$id'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            return 0;
        }

        $r = mysql_fetch_array($result);

        $points += $r['recipe'] * 2;
    }

    // Document (2)
    if (usingDocuments())
    {
        $sql = "SELECT COUNT(`id`) AS doc 
                FROM `fcms_documents` 
                WHERE `user` = '$id'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            return 0;
        }

        $r = mysql_fetch_array($result);

        $points += $r['doc'] * 2;
    }

    // Prayer (2)
    if (usingPrayers())
    {
        $sql = "SELECT COUNT(`id`) AS prayer 
                FROM `fcms_prayers` 
                WHERE `user` = '$id'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySqlError($sql, mysql_error());
            return 0;
        }

        $r = mysql_fetch_array($result);

        $points += $r['prayer'] * 2;
    }

    // Post (2)
    $sql = "SELECT COUNT(`id`) AS post 
            FROM `fcms_board_posts` 
            WHERE `user` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 0;
    }

    $r = mysql_fetch_array($result);

    $points += $r['post'] * 2;

    // Comment (2)
    $from  = implode('`, `', $commentTables);
    $where = implode("`.`user` = '$id' AND `", $commentTables);

    $sql = "SELECT COUNT(*) AS comment 
            FROM `$from` 
            WHERE `$where`.`user` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 0;
    }

    $r = mysql_fetch_array($result);

    $points += $r['comment'] * 2;

    // Address/Phone (1)
    $sql = "SELECT `address`, `city`, `state`, `home`, `work`, `cell` 
            FROM `fcms_address` 
            WHERE `user` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 0;
    }

    $r = mysql_fetch_array($result);

    if (!empty($r['address']) && !empty($r['city']) && !empty($r['state']))
    {
        $points++;
    }
    if (!empty($r['home']))
    {
        $points++;
    }
    if (!empty($r['work']))
    {
        $points++;
    }
    if (!empty($r['cell']))
    {
        $points++;
    }

    // Date/Event
    $sql = "SELECT COUNT(`id`) AS event 
            FROM `fcms_calendar` 
            WHERE `created_by` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 0;
    }

    $r = mysql_fetch_array($result);

    $points += $r['event'];

    // Vote
    $sql = "SELECT COUNT(`id`) AS vote 
            FROM `fcms_poll_votes` 
            WHERE `user` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 0;
    }

    $r = mysql_fetch_array($result);

    $points += $r['vote'];

    return $points;
}

/**
 * getUserParticipationLevel
 * 
 * Get the participation level for the given points.
 *
 *      Level   Points
 *      ---------------
 *      1           25
 *      2           50
 *      3          100
 *      4          200
 *      5          400
 *      6          800
 *      7        1,600
 *      8        3,200
 *      9        6,400
 *      10      12,800
 *      
 *
 * @param   int     $points
 * @return  string
 */
function getUserParticipationLevel ($points)
{
    $level = '';

    if ($points > 12800) {
        $level = '<div title="'.T_('Level 10').' ('.$points.')" class="level10"></div>';
    } elseif ($points > 6400) {
        $level = '<div title="'.T_('Level 9').' ('.$points.')" class="level9"></div>';
    } elseif ($points > 3200) {
        $level = '<div title="'.T_('Level 8').' ('.$points.')" class="level8"></div>';
    } elseif ($points > 1600) {
        $level = '<div title="'.T_('Level 7').' ('.$points.')" class="level7"></div>';
    } elseif ($points > 800) {
        $level = '<div title="'.T_('Level 6').' ('.$points.')" class="level6"></div>';
    } elseif ($points > 400) {
        $level = '<div title="'.T_('Level 5').' ('.$points.')" class="level5"></div>';
    } elseif ($points > 200) {
        $level = '<div title="'.T_('Level 4').' ('.$points.')" class="level4"></div>';
    } elseif ($points > 100) {
        $level = '<div title="'.T_('Level 3').' ('.$points.')" class="level3"></div>';
    } elseif ($points > 50) {
        $level = '<div title="'.T_('Level 2').' ('.$points.')" class="level2"></div>';
    } elseif ($points > 25) {
        $level = '<div title="'.T_('Level 1').' ('.$points.')" class="level1"></div>';
    } else {
        $level = '<div title="'.T_('Level 0').' ('.$points.')" class="level0"></div>';
    }

    return $level;
}

/**
 * getContactEmail 
 * 
 * @return  string
 */
function getContactEmail ()
{
    $sql = "SELECT `value` 
            FROM `fcms_config`
            WHERE `name` = 'contact'";
    $result = mysql_query($sql);
    if (!$result)
    {
        return 'ERROR-contact';
    }

    $r = mysql_fetch_array($result);

    return $r['value'];
}

/**
 * getSiteName 
 * 
 * @return  string
 */
function getSiteName()
{
    $sql = "SELECT `value`
            FROM `fcms_config`
            WHERE `name` = 'sitename'";

    $result = mysql_query($sql);
    if (!$result)
    {
        return 'ERROR-sitename';
    }

    $r = mysql_fetch_array($result);

    return cleanOutput($r['value']);
}

/**
 * getCurrentVersion 
 * 
 * @return  void
 */
function getCurrentVersion()
{
    $sql = "SELECT `value`
            FROM `fcms_config`
            WHERE `name` = 'current_version'";

    $result = mysql_query($sql);
    if (!$result)
    {
        return 'ERROR-current_version';
    }

    $r = mysql_fetch_array($result);

    return $r['value'];
}

/**
 * displayBBCodeToolbar
 * 
 * @return  void
 */
function displayBBCodeToolbar ()
{
    echo '
            <div id="toolbar" class="toolbar hideme">
                <input type="button" class="bold button" onclick="bb.insertCode(\'B\', \'bold\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Bold').'" />
                <input type="button" class="italic button" onclick="bb.insertCode(\'I\', \'italic\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Italic').'"/>
                <input type="button" class="underline button" onclick="bb.insertCode(\'U\', \'underline\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Underline').'"/>
                <input type="button" class="left_align button" onclick="bb.insertCode(\'ALIGN=LEFT\', \'left right\', \'ALIGN\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Left Align').'"/>
                <input type="button" class="center_align button" onclick="bb.insertCode(\'ALIGN=CENTER\', \'center\', \'ALIGN\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Center').'"/>
                <input type="button" class="right_align button" onclick="bb.insertCode(\'ALIGN=RIGHT\', \'align right\', \'ALIGN\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Right Align').'"/>
                <input type="button" class="h1 button" onclick="bb.insertCode(\'H1\', \'heading 1\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Heading 1').'"/>
                <input type="button" class="h2 button" onclick="bb.insertCode(\'H2\', \'heading 2\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Heading 2').'"/>
                <input type="button" class="h3 button" onclick="bb.insertCode(\'H3\', \'heading 3\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Heading 3').'"/>
                <input type="button" class="board_quote button" onclick="bb.insertCode(\'QUOTE\', \'quote\');" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Quote').'"/>
                <input type="button" class="board_images button" onclick="window.open(\'inc/upimages.php\',\'name\',\'width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no\'); return false;" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Insert Image').'"/>
                <input type="button" class="links button" onclick="bb.insertLink();" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Insert URL').'"/>
                <input type="button" class="smileys button" onclick="window.open(\'inc/smileys.php\',\'name\',\'width=500,height=200,scrollbars=no,resizable=no,location=no,menubar=no,status=no\'); return false;" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('Insert Smiley').'"/>
                <input type="button" class="help button" onclick="window.open(\'inc/bbcode.php\',\'name\',\'width=400,height=300,scrollbars=yes,resizable=no,location=no,menubar=no,status=no\'); return false;" onmouseout="style.border=\'1px solid #f6f6f6\';" onmouseover="style.border=\'1px solid #c1c1c1\';" title="'.T_('BBCode Help').'"/>
            </div>';
}

/**
 * displayWysiwygJs 
 * 
 * @param mixed $id 
 * 
 * @return void
 */
function displayWysiwygJs ($id)
{
    $elements = $id;

    if (is_array($id))
    {
        $elements = implode(",", $id);
    }

    echo '
<script type="text/javascript">
tinyMCE.init({
    // General options
    mode : "exact",
    elements : "'.$elements.'",
    theme : "advanced",

    // Theme options
    theme_advanced_buttons1 : "myimage,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,|,bullist,numlist,|,link,unlink,|,blockquote,|,forecolor,removeformat",
    theme_advanced_buttons2 : "",
    theme_advanced_buttons3 : "",
    theme_advanced_buttons4 : "",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,
    setup : function(ed) {
        ed.addButton("myimage", {
            title : "'.T_('Images').'",
            image : "img/example.gif",
            onclick : function() {
                window.open("inc/upimages.php","name","width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no"); 
                return false;
            }
        });
    }
});
</script>';
}


/**
 * displayOkMessage 
 * 
 * Displays a success/ok message that closes automatically through js.
 * 
 * @param string $msg     defaults to 'Changes Updated Successfully.'
 * @param int    $timeout defaults to 4000 ms
 * 
 * @return void
 */
function displayOkMessage ($msg = '', $timeout = 0)
{
    $id = 'msg-'.time();

    if (empty($msg))
    {
        $msg = T_('Changes Updated Successfully.');
    }

    if ($timeout <= 0)
    {
        $timeout = '4000';
    }

    echo '
        <script type="text/javascript" src="'.URL_PREFIX.'ui/js/scriptaculous.js"></script>
        <div id="'.$id.'" class="ok-msg-container" style="display:none">
            <div class="ok-msg">
                <a class="close-msg" href="#" onclick="Effect.Fade(\''.$id.'\')" title="'.T_('Close Message').'">x</a>
                '.$msg.'
            </div>
        </div>
        <noscript>
        <style type="text/css">
        .ok-msg-container { display: block !important; margin: 0 0 30px 0 !important; position: relative; }
        .ok-msg { width: auto !important; padding: 10px 45px !important; }
        .close-msg { display: none; }
        </style>
        </noscript>
        <script type="text/javascript">
            Event.observe(window, \'load\', function() {
                Effect.BlindDown(\''.$id.'\');
                var t=setTimeout("Effect.Fade(\''.$id.'\')",'.$timeout.'); 
            });
        </script>';
}

/**
 * uploadImages 
 * 
 * @param string  $filetype 
 * @param string  $filename 
 * @param string  $filetmpname 
 * @param string  $destination 
 * @param int     $max_h 
 * @param int     $max_w 
 * @param boolean $unique 
 * @param boolean $show
 * @param boolean $square
 * 
 * @return  string
 */
function uploadImages ($filetype, $filename, $filetmpname, $destination, $max_h, $max_w, $unique = false, $show = true, $square = false)
{
    global $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass;

    include_once('gallery_class.php');
    include_once('database_class.php');

    $currentUserId = (int)$_SESSION['login_id'];

    $database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
    $gallery  = new PhotoGallery($currentUserId, $database);

    $known_photo_types = array(
        'image/pjpeg' => 'jpeg', 
        'image/jpeg'  => 'jpg', 
        'image/gif'   => 'gif', 
        'image/bmp'   => 'bmp', 
        'image/x-png' => 'png', 
        'image/png'   => 'png'
    );
    $gd_function_suffix = array(
        'image/pjpeg' => 'JPEG', 
        'image/jpeg'  => 'JPEG', 
        'image/gif'   => 'GIF', 
        'image/bmp'   => 'WBMP', 
        'image/x-png' => 'PNG', 
        'image/png'   => 'PNG'
    );

    // Get extension of photo
    $ext = explode('.', $filename);
    $ext = end($ext);
    $ext = strtolower($ext);

    // Check mime type
    if (!array_key_exists($filetype, $known_photo_types)) {
        echo '
            <p class="error-alert">
                '.sprintf(T_('Error: File %s is not a photo.  Photos must be of type (.JPG, .JPEG, .GIF, .BMP or .PNG).'), $filetype).'
            </p>';
    // Check file extension
    } elseif (!in_array($ext, $known_photo_types)) {
        echo '
            <p class="error-alert">
                '.sprintf(T_('Error: File %s is not a photo.  Photos must be of type (.JPG, .JPEG, .GIF, .BMP or .PNG).'), $filetype).'
            </p>';
    } else {

        // Make filename unique
        if ($unique) {
            $new_id = uniqid("");
            $extention = $known_photo_types[$filetype];
            $filename = $new_id . "." . $extention;
        }

        copy($filetmpname, $destination . $filename);
        $size = GetImageSize($destination . $filename);

        if ($square) {
            $thumbnail = $gallery->getResizeSizeSquare(
                $size[0], 
                $size[1], 
                $max_w
            );
            $temp_width  = $thumbnail[0];
            $temp_height = $thumbnail[1];
            $width       = $thumbnail[2];
            $height      = $thumbnail[3];
        } else {
            $thumbnail = $gallery->getResizeSize(
                $size[0], 
                $size[1], 
                $max_w, 
                $max_h
            );
            $temp_width  = $thumbnail[0];
            $temp_height = $thumbnail[1];
            $width       = $thumbnail[0];
            $height      = $thumbnail[1];
        }


        if ($size[0] > $max_w && $size[1] > $max_h) {
            $function_suffix = $gd_function_suffix[$filetype];
            $function_to_read = "ImageCreateFrom".$function_suffix;
            $function_to_write = "Image".$function_suffix;
            $source_handle = $function_to_read($destination . $filename); 
            if ($source_handle) {
                $destination_handle = ImageCreateTrueColor($width, $height);
                ImageCopyResampled($destination_handle, $source_handle, 0, 0, 0, 0, $temp_width, $temp_height, $size[0], $size[1]);
            }
            $function_to_write($destination_handle, $destination . $filename);
            ImageDestroy($destination_handle );
        }
    }

    // Show thumbnail?
    if ($show) {
        echo "<img src=\"" . $destination . $filename . "\" alt=\"\"/>";
    }

    return $filename;
}


/**
 * displayPages
 * 
 * Function renamed in 2.0, needs to stay until old calls are updated.
 *
 * @deprecated deprecated since version 2.0 
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

    if ($total_pages > 1)
    {
        echo '
            <div class="pagination pages">
                <ul>';

        // First / Previous
        if ($cur_page > 1)
        {
            $prev = ($cur_page - 1);
            echo '
                    <li><a title="'.T_('First Page').'" class="first" href="'.$url.$divider.'page=1">'.T_('First').'</a></li>
                    <li><a title="'.T_('Previous Page').'" class="previous" href="'.$url.$divider.'page='.$prev.'">'.T_('Previous').'</a></li>';
        }
        else
        {
            echo '
                    <li><a title="'.T_('First Page').'" class="first" href="'.$url.$divider.'page=1">'.T_('First').'</a></li>
                    <li><a title="'.T_('Previous Page').'" class="previous" href="'.$url.$divider.'page=1">'.T_('Previous').'</a></li>';
        }

        // Numbers
        if ($total_pages > 8)
        {
            if ($cur_page > 2)
            {
                for ($i = ($cur_page-2); $i <= ($cur_page+5); $i++)
                {
                    if ($i <= $total_pages)
                    {
                        $aClass = $cur_page == $i ? ' class="current"' : '';
                        $lClass = $cur_page == $i ? ' class="active"'  : '';

                        echo '
                    <li'.$lClass.'><a href="'.$url.$divider.'page='.$i.'"'.$aClass.'>'.$i.'</a></li>';
                    }
                } 
            }
            else
            {
                for ($i = 1; $i <= 8; $i++)
                {
                    $aClass = $cur_page == $i ? ' class="current"' : '';
                    $lClass = $cur_page == $i ? ' class="active"'  : '';

                    echo '
                    <li'.$lClass.'><a href="'.$url.$divider.'page='.$i.'"'.$aClass.'>'.$i.'</a></li>';
                } 
            }
        }
        else
        {
            for ($i = 1; $i <= $total_pages; $i++)
            {
                $aClass = $cur_page == $i ? ' class="current"' : '';
                $lClass = $cur_page == $i ? ' class="active"'  : '';

                echo '
                    <li'.$lClass.'><a href="'.$url.$divider.'page='.$i.'"'.$aClass.'>'.$i.'</a></li>';
            } 
        }

        // Next / Last
        if ($cur_page < $total_pages)
        {
            $next = ($cur_page + 1);
            echo '
                    <li><a title="'.T_('Next Page').'" class="next" href="'.$url.$divider.'page='.$next.'">'.T_('Next').'</a></li>
                    <li><a title="'.T_('Last page').'" class="last" href="'.$url.$divider.'page='.$total_pages.'">'.T_('Last').'</a></li>';
        }
        else
        {
            echo '
                    <li><a title="'.T_('Next Page').'" class="next" href="'.$url.$divider.'page='.$total_pages.'">'.T_('Next').'</a></li>
                    <li><a title="'.T_('Last page').'" class="last" href="'.$url.$divider.'page='.$total_pages.'">'.T_('Last').'</a></li>';
        } 

        echo '
                </ul>
            </div>';
    }    
}

/**
 * formatSize 
 * 
 * @param   int     $file_size 
 * @return  string
 */
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

/**
 * displayMembersOnline 
 * 
 * @return  void
 */
function displayMembersOnline ()
{
    $last24hours = time() - (60 * 60 * 24);

    $sql = "SELECT * 
            FROM fcms_users 
            WHERE UNIX_TIMESTAMP(`activity`) >= $last24hours 
            ORDER BY `activity` DESC";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError('Online Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    echo '
            <h3>'.T_('Last Seen').':</h3>
            <ul class="avatar-member-list">';

    while ($r = mysql_fetch_assoc($result))
    {
        $displayname = getUserDisplayName($r['id']);
        $tz_offset   = getTimezone($r['id']);
        $activity    = fixDate('F d, h:i a', $tz_offset, $r['activity']);
        $since       = getHumanTimeSince(strtotime($r['activity']));

        echo '
                <li>
                    <a href="profile.php?member='.$r['id'].'" class="tooltip" title="['.$displayname.'] - '.$activity.'" onmouseover="showTooltip(this)" onmouseout="hideTooltip(this)">
                        <img alt="avatar" src="'.getCurrentAvatar($r['id']).'" alt="'.$displayname.'"/> 
                    </a>
                    <div class="tooltip" style="display:none;">
                        <h5>'.$displayname.'</h5>
                        <span>'.$since.'</span>
                    </div>
                </li>';
    }

    echo '
            </ul><br/><br/>';
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
    $userid = (int)$userid;

    $sql = "SELECT `username`, `password` 
            FROM `fcms_users` 
            WHERE `id` = '$userid' 
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return false;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return false;
    }

    $r = mysql_fetch_array($result);

    if ($r['username'] !== $username)
    {
        return false;
    }
    elseif ($r['password'] !== $password)
    {
        return false;
    }
    else
    {
        return true;
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

    foreach ($options as $key => $value)
    {
        $selected = '';
        if (is_array($selected))
        {
            if (array_key_exists($key, $selected_options))
            {
                $selected = ' selected="selected"';
            }
        }
        else
        {
            if ($key == $selected_options)
            {
                $selected = ' selected="selected"';
            }
        }

        $return .= '<option value="'.cleanOutput($key).'"'.$selected.'>'.cleanOutput($value).'</option>';
    }

    return $return;
}

/**
 * usingFamilyNews 
 * 
 * Wrapper function for usingPlugin.
 * 
 * @return  boolean
 */
function usingFamilyNews()
{
    return usingPlugin('familynews');
}
/**
 * usingPrayers 
 * 
 * Wrapper function for usingPlugin.
 * 
 * @return  boolean
 */
function usingPrayers()
{
    return usingPlugin('prayers');
}
/**
 * usingRecipes 
 * 
 * Wrapper function for usingPlugin.
 * 
 * @return  boolean
 */
function usingRecipes()
{
    return usingPlugin('recipes');
}
/**
 * usingDocuments 
 * 
 * Wrapper function for usingPlugin.
 * 
 * @return  boolean
 */
function usingDocuments()
{
    return usingPlugin('documents');
}
/**
 * usingWhereIsEveryone
 * 
 * Wrapper function for usingPlugin.
 * 
 * @return  boolean
 */
function usingWhereIsEveryone()
{
    return usingPlugin('whereiseveryone');
}
/**
 * usingFacebook
 * 
 * Wrapper function for usingPlugin.
 * 
 * @return  boolean
 */
function usingFacebook()
{
    return usingPlugin('admin_facebook');
}
/**
 * usingPlugin 
 * 
 * Checks whether the given section is currently being used.
 * 
 * @param   string  $section
 * @return  boolean
 */
function usingPlugin ($section)
{
    $sql = "SELECT `id`, `link`, `order`
            FROM `fcms_navigation` 
            WHERE `link` = '".escape_string($section)."'
            LIMIT 1";
    
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return false;
    }

    if (mysql_num_rows($result) > 0)
    {
        $r = mysql_fetch_assoc($result);
        if ($r['order'] > 0)
        {
            return true;
        }
    }
    return false;
}

/**
 * tableExists 
 * 
 * @param   string  $tbl 
 * @return  boolean
 */
function tableExists ($tbl)
{
    global $cfg_mysql_db;

    $tbl = escape_string($tbl);

    $table = mysql_query("SHOW TABLES FROM `$cfg_mysql_db` LIKE '$tbl'");
    if (mysql_fetch_row($table) === false) {
        return false;
    } else {
        return true ;
    }
}

/**
 * getDomainAndDir 
 * 
 * @return  string
 */
function getDomainAndDir ()
{
    $pageURL = 'http';

    if (isset($_SERVER["HTTPS"]))
    {
        if ($_SERVER["HTTPS"] == "on")
        {
            $pageURL .= 's';
        }
    }

    $pageURL .= '://';

    if (isset($_SERVER["SERVER_PORT"]))
    {
        if ($_SERVER["SERVER_PORT"] != "80")
        {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        }
        else
        {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
    }

    // Return the domain and any directories, but exlude the filename from the end
    return substr($pageURL, 0, strripos($pageURL, '/')+1);
}

/**
 * displaySqlError 
 * 
 * Logs sql errors and prints a generic error message.
 * If debugging is turned on, will also print debug info.
 * 
 * @param string $sql 
 * @param string $error 
 * 
 * @return void
 */
function displaySqlError ($sql, $error)
{
    $backtrace = debug_backtrace(false);
    $last      = $backtrace[1];
    $file      = $last['file'];
    $line      = $last['line'];
    $debugInfo = '';

    if (debugOn())
    {
        $debugInfo = '
        <p><b>File:</b> '.$file.'</p>
        <p><b>Statement:</b> '.$sql.'</p>
        <p><b>Error:</b> '.$error.'</p>
        <p><b>MySQL Version:</b> '.mysql_get_server_info().'</p>
        <p><b>PHP Version:</b> '.phpversion().'</p>';
    }

    echo '
    <div class="error-alert">
        <p><b>'.T_('There was a problem communicating with the database.').'</b></p>
        '.$debugInfo.'
    </div>';

    // Remove newlines, tabs, spaces from sql
    $sql = str_replace(array("\n", "\r", "  ", "\t"), '', $sql);

    logError($file.' ['.$line.'] - '.$error.' - '.$sql);
}

/**
 * displayError 
 * 
 * @param string  $error 
 * 
 * @return void
 */
function displayError ($error)
{
    // Get file and line
    $backtrace = debug_backtrace(false);
    $last      = $backtrace[1];
    $file      = $last['file'];
    $line      = $last['line'];
    $debugInfo = '';

    if (debugOn())
    {
        $debugInfo = '
        <p><b>File:</b> '.$file.'</p>
        <p><b>Line:</b> '.$line.'</p>
        <p><b>MySQL Version:</b> '.mysql_get_server_info().'</p>
        <p><b>PHP Version:</b> '.phpversion().'</p>';
    }

    echo '
    <div class="error-alert">
        '.$error.'
        '.$debugInfo.'
    </div>';

    logError($file.' ['.$line.'] - '.$error);
}

/**
 * logError 
 * 
 * @param string $string The full error string
 * 
 * @return void
 */
function logError ($string)
{
    require_once THIRDPARTY.'KLogger.php';

    $log = new KLogger(ROOT.'logs/', KLogger::ERR );

    $log->logError($string);
}

/**
 * debugOn 
 * 
 * @return void
 */
function debugOn ()
{
    $sql = "SELECT `value`
            FROM `fcms_config`
            WHERE `name` = 'debug'
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return false;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return false;
    }

    $r = mysql_fetch_assoc($result);

    $on = $r['value'] == 1 ? true : false;

    return $on;
}

/**
 * displayWhatsNewAll 
 * 
 * Displays the following types of new data from the site:
 *
 *  ADDRESSADD      Add address of non-member
 *  ADDRESSEDIT     Edit own address
 *  AVATAR          Change avatar
 *  BOARD           Message board post
 *  CALENDAR        Add date to calendar
 *  DOCS            Added document
 *  GALCATCOM       Commented on category of photos
 *  GALCOM          Commented on photo
 *  GALLERY         Added photo
 *  JOINED          Joined the site (became active)
 *  NEWS            Added family news
 *  NEWSCOM         Commented on family news
 *  POLL            Added poll
 *  POLLCOM         Commented on poll
 *  PRAYERS         Added prayer concern
 *  RECIPES         Added recipe
 *  RECIPECOM       Commented on recipe
 *  STATUS          Added status update
 *  VIDEO           Added video
 *  VIDEOCOM        Commented on video
 *  WHEREISEVERYONE Checked in on foursquare
 *
 * @param int $userid 
 *
 * @return void
 */
function displayWhatsNewAll ($userid)
{
    global $cfg_mysql_host, $cfg_use_news, $cfg_use_prayers;

    load('gallery');

    $userid    = (int)$userid;
    $tz_offset = getTimezone($userid);

    $galleryObj = new PhotoGallery($userid);

    $lastday = '0-0';

    $today_start = fixDate('Ymd', $tz_offset, date('Y-m-d H:i:s')) . '000000';
    $today_end   = fixDate('Ymd', $tz_offset, date('Y-m-d H:i:s')) . '235959';

    $time            = mktime(0, 0, 0, date('m')  , date('d')-1, date('Y'));
    $yesterday_start = fixDate('Ymd', $tz_offset, date('Y-m-d H:i:s', $time)) . '000000';
    $yesterday_end   = fixDate('Ymd', $tz_offset, date('Y-m-d H:i:s', $time)) . '235959';

    // Get data
    $whatsNewData = getWhatsNewData($userid, 30);
    if ($whatsNewData === false)
    {
        return;
    }

    $cachedUserData = array();

    $position = 1;

    // Loop through data
    foreach ($whatsNewData as $r)
    {
        $updated     = fixDate('Ymd',    $tz_offset, $r['date']);
        $updatedFull = fixDate('YmdHis', $tz_offset, $r['date']);

        // Print date header
        if ($updated != $lastday)
        {
            // Today
            if ($updatedFull >= $today_start && $updatedFull <= $today_end)
            {
                echo '
                <p><b>'.T_('Today').'</b></p>';
            }
            // Yesterday
            if ($updatedFull >= $yesterday_start && $updatedFull <= $yesterday_end)
            {
                echo '
                <p><b>'.T_('Yesterday').'</b></p>';
            }
        }

        $rtime = strtotime($r['date']);

        // Use cached data
        if (isset($cachedUserData[$r['userid']]))
        {
            $displayname = $cachedUserData[$r['userid']]['displayname'];
            $avatar      = $cachedUserData[$r['userid']]['avatar'];
        }
        // Get new data
        else
        {
            $displayname = getUserDisplayName($r['userid']);
            $displayname = '<a class="u" href="profile.php?member='.$r['userid'].'">'.$displayname.'</a>';
            $avatar      = '<img src="'.getCurrentAvatar($r['userid']).'" alt="'.cleanOutput($displayname).'"/>';

            // Save this for later
            $cachedUserData[$r['userid']]['avatar']      = $avatar;
            $cachedUserData[$r['userid']]['displayname'] = $displayname;
        }

        if ($r['type'] == 'ADDRESSADD')
        {
            $displayname = getUserDisplayName($r['id2']);
            $displayname = '<a class="u" href="profile.php?member='.$r['id2'].'">'.$displayname.'</a>';
            $avatar      = '<img src="'.getCurrentAvatar($r['id2']).'" alt="'.cleanOutput($displayname).'"/>';
            $for         = '<a href="addressbook.php?address='.$r['id'].'">'.getUserDisplayName($r['userid'], 2, false).'</a>';

            echo '
                <div id="'.$position.'" class="new newaddress">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Added address information for %s.'), $for).'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'ADDRESSEDIT')
        {
            if ($r['title'] == 'address')
            {
                $titleType = T_('address');
            }
            elseif ($r['title'] == 'email')
            {
                $titleType = T_('email address');
            }
            elseif ($r['title'] == 'home')
            {
                $titleType = T_('home phone number');
            }
            elseif ($r['title'] == 'work')
            {
                $titleType = T_('work phone number');
            }
            elseif ($r['title'] == 'cell')
            {
                $titleType = T_('cell phone number');
            }
            // this shouldn't happen
            else
            {
                $titleType = T_('address');
            }

            $editor  = getUserDisplayName($r['id2']);
            $editor  = '<a class="u" href="profile.php?member='.$r['id2'].'">'.$editor.'</a>';
            $avatar  = '<img src="'.getCurrentAvatar($r['id2']).'" alt="'.cleanOutput($editor).'"/>';
            $address = '<a href="addressbook.php?address='.$r['id'].'">'.$titleType.'</a>';

            if ($r['id2'] != $r['userid'])
            {
                $user = getUserDisplayName($r['userid']);
                $text = sprintf(T_pgettext('Example: "Updated the <address/phone/email> for <name>."', 'Updated the %s for %s.'), $address, $user);
            }
            else
            {
                if ($r['id3'] == 'F')
                {
                    $text = sprintf(T_pgettext('Example: "Updated her <address/phone/email>."', 'Updated her %s.'), $address);
                }
                else
                {
                    $text = sprintf(T_pgettext('Example: "Updated his <address/phone/email>."', 'Updated his %s.'), $address);
                }
            }

            echo '
                <div id="'.$position.'" class="new newaddress">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$editor.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.$text.'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'AVATAR')
        {
            $text = T_('Changed his profile picture.');

            if ($r['id3'] == 'F')
            {
                $text = T_('Changed her profile picture.');
            }

            echo '
                <div id="'.$position.'" class="new newavatar">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>'.$text.'</p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'BOARD')
        {
            $sql = "SELECT MIN(`id`) AS 'id' 
                    FROM `fcms_board_posts` 
                    WHERE `thread` = '".$r['id2']."'";

            $result = mysql_query($sql);
            if (!$result)
            {
                displaySqlError($sql, mysql_error());
                return;
            }
            $minpost = mysql_fetch_array($result);

            $subject  = $r['title'];

            $pos = strpos($subject, '#ANOUNCE#');
            if ($pos !== false)
            {
                $subject = substr($subject, 9, strlen($subject)-9);
            }

            $title   = cleanOutput($subject);
            $subject = cleanOutput($subject);
            $subject = '<a href="messageboard.php?thread='.$r['id2'].'" title="'.$title.'">'.$subject.'</a>';

            if ($r['id'] == $minpost['id'])
            {
                $class = 'newthread';
                $text = sprintf(T_('Started the new thread %s.'), $subject);
            }
            else
            {
                $class = 'newpost';
                $text = sprintf(T_('Replied to %s.'), $subject);
            }

            echo '
                <div id="'.$position.'" class="new '.$class.'">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.$text.'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'CALENDAR')
        {
            $date_date = date('F j, Y', strtotime($r['id2']));
            $for       = '<a href="calendar.php?event='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

            echo '
                <div id="'.$position.'" class="new newcal">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.$for.' - '.$date_date.'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'DOCS')
        {
            $doc = '<a href="documents.php">'.cleanOutput($r['title']).'</a>';

            echo '
                <div id="'.$position.'" class="new newdocument">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Added a new Document (%s).'), $doc).'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'GALCATCOM')
        {
            $category = '<a href="gallery/index.php?uid='.$r['id2'].'&amp;cid='.$r['id3'].'">'.cleanOutput($r['title']).'</a>';

            echo '
                <div id="'.$position.'" class="new newcom">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Commented on %s.'), $category).'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'GALCOM')
        {
            $data = array(
                'id'          => $r['id'],
                'user'        => $r['id2'],
                'filename'    => $r['id3'],
                'external_id' => null,
                'thumbnail'   => null
            );

            if ($r['id3'] == 'noimage.gif')
            {
                $sql = "SELECT p.`id`, p.`filename`, p.`external_id`, e.`thumbnail`
                        FROM `fcms_gallery_photos` AS p
                        LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                        WHERE p.`id` = '".(int)$r['id']."'";

                $result = mysql_query($sql);
                if (!$result)
                {
                    displaySqlError($sql, mysql_error());
                    return;
                }

                $p = mysql_fetch_assoc($result);

                $data['external_id'] = $p['external_id'];
                $data['thumbnail']   = $p['thumbnail'];
            }

            $photoSrc = $galleryObj->getPhotoSource($data);

            echo '
                <div id="'.$position.'" class="new newcom">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.T_('Commented on the following photo:').'<br/>
                            <a href="gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$r['id'].'">
                                <img src="'.$photoSrc.'"/>
                            </a>
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'GALLERY')
        {
            $cat    = '<a href="gallery/index.php?uid='.$r['userid'].'&amp;cid='.$r['id'].'">'.cleanOutput($r['title']).'</a>';
            $photos = '';

            $limit = 4;
            if ($r['id2'] < $limit)
            {
                $limit = $r['id2'];
            }
            $sql = "SELECT p.`id`, p.`user`, p.`category`, p.`filename`, p.`caption`,
                        p.`external_id`, e.`thumbnail`, e.`medium`, e.`full`
                    FROM `fcms_gallery_photos` AS p
                    LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                    WHERE p.`category` = '".(int)$r['id']."' 
                    AND DAYOFYEAR(p.`date`) = '".(int)$r['id3']."' 
                    ORDER BY p.`date` 
                    DESC LIMIT $limit";

            $result = mysql_query($sql);
            if (!$result)
            {
                displaySqlError($sql, mysql_error());
                return;
            }

            while ($p = mysql_fetch_assoc($result))
            {
                $photoSrc = $galleryObj->getPhotoSource($p);

                $photos .= '
                        <a href="gallery/index.php?uid='.$r['userid'].'&amp;cid='.$r['id'].'&amp;pid='.$p['id'].'">
                            <img src="'.$photoSrc.'" alt="'.cleanOutput($p['caption']).'"/>
                        </a> &nbsp;';
            }

            echo '
                <div id="'.$position.'" class="new newphoto">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Added %d new photos to the %s category.'), $r['id2'], $cat).'<br/>
                            '.$photos.'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'JOINED')
        {
            echo '
                <div id="'.$position.'" class="new newmember">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>'.T_('Joined the website.').'</p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'NEWS')
        {
            $title = !empty($r['title']) ? cleanOutput($r['title']) : T_('untitled');
            $news  = '<a href="familynews.php?getnews='.$r['userid'].'&amp;newsid='.$r['id'].'">'.$title.'</a>'; 

            echo '
                <div id="'.$position.'" class="new newnews">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Added %s to his/her Family News.'), $news).'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'NEWSCOM')
        {
            $news = '<a href="familynews.php?getnews='.$r['userid'].'&amp;newsid='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

            echo '
                <div id="'.$position.'" class="new newcom">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Commented on Family News %s.'), $news).'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'POLL')
        {
            $poll = '<a href="polls.php?id='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

            echo '
                <p class="new newpoll">'.sprintf(T_('A new Poll (%s) has been added.'), $poll).' <small><i>'.getHumanTimeSince($rtime).'</i></small></p>';
        }
        elseif ($r['type'] == 'POLLCOM')
        {
            $poll = '<a href="polls.php?id='.$r['id'].'"#comments>'.cleanOutput($r['title']).'</a>';

            echo '
                <div id="'.$position.'" class="new pollcom">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Commented on Poll %s.'), $poll).'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'PRAYERS')
        {
            $for = '<a href="prayers.php">'.cleanOutput($r['title']).'</a>';

            echo '
                <div id="'.$position.'" class="new newprayer">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Added a Prayer Concern for %s.'), $for).'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'RECIPES')
        {
            $rec = '<a href="recipes.php?category='.$r['id2'].'&amp;id='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

            echo '
                <div id="'.$position.'" class="new newrecipe">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Added the %s recipe.'), $rec).'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'RECIPECOM')
        {
            $rec = '<a href="recipes.php?category='.$r['id2'].'&amp;id='.$r['id'].'">'.cleanOutput($r['title']).'</a>';

            echo '
                <div id="'.$position.'" class="new newcom">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Commented on Recipe %s.'), $rec).'
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'STATUS')
        {
            $title = cleanOutput($r['title']);
            $title = nl2br_nospaces($title);

            // Get any replies to this status update
            $sql = "SELECT `id`, `user`, `status`, `parent`, `updated`, `created` 
                    FROM `fcms_status` 
                    WHERE `parent` = '".(int)$r['id']."' 
                    ORDER BY `id`";
            $result = mysql_query($sql);
            if (!$result)
            {
                displaySqlError($sql, mysql_error());
                return;
            }

            $statuses = '';

            if (mysql_num_rows($result) > 0)
            {
                while ($s = mysql_fetch_assoc($result))
                {
                    $name = getUserDisplayName($s['user']);
                    $name = '<a class="u" href="profile.php?member='.$s['user'].'">'.$name.'</a>';

                    $avatar2 = '<img src="'.getCurrentAvatar($s['user']).'" alt="'.cleanOutput($name).'"/>';

                    $status = cleanOutput($s['status']);
                    $status = nl2br_nospaces($status);

                    $statuses .= '
                    <div class="newstatus">
                        <div class="avatar">'.$avatar2.'</div>
                        <div class="info">
                            '.$name.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince(strtotime($s['created'])).'</i></small>
                            <p>
                                '.$status.'
                            </p>
                        </div>
                    </div>';
                }
            }

            echo '
                <div id="'.$position.'" class="new newstatus">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince(strtotime($r['id3'])).'</i></small>
                        <p>
                            '.$title.'
                        </p>
                        '.$statuses;

            displayStatusUpdateForm($r['id']);
            echo '
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'VIDEO')
        {
            echo '
                <div id="'.$position.'" class="new newvideo">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.T_('Added a new Video.').'<br/>
                            <a href="video.php?u='.$r['userid'].'&amp;id='.$r['id'].'"><img src="http://i.ytimg.com/vi/'.$r['id2'].'/default.jpg"/></a>
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'VIDEOCOM')
        {
            echo '
                <div id="'.$position.'" class="new newcom">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.T_('Commented on the following Video:').'<br/>
                            <a href="video.php?u='.$r['userid'].'&amp;id='.$r['id'].'#comments"><img src="http://i.ytimg.com/vi/'.$r['id2'].'/default.jpg"/></a>
                        </p>
                    </div>
                </div>';
        }
        elseif ($r['type'] == 'WHEREISEVERYONE')
        {
            echo '
                <div id="'.$position.'" class="new newwhere">
                    <div class="avatar">'.$avatar.'</div>
                    <div class="info">
                        '.$displayname.' &nbsp;- &nbsp;<small><i>'.getHumanTimeSince($rtime).'</i></small>
                        <p>
                            '.sprintf(T_('Visited %s.'), $r['title']).'
                        </p>
                        '.(!empty($r['id2']) ? '<blockquote>'.cleanOutput($r['id2']).'</blockquote>' : '').'
                    </div>
                </div>';
        }

        $position++;

        $lastday = $updated;
    }
}

/**
 * getWhatsNewData 
 * 
 * Get the latest information in the site, including any external data.
 * Defaults to the last 30 days.
 * 
 * Types of data:
 * 
 *  ADDRESSADD      Add address of non-member
 *  ADDRESSEDIT     Edit own address
 *  AVATAR          Change avatar
 *  BOARD           Message board post
 *  CALENDAR        Add date to calendar
 *  DOCS            Added document
 *  GALCATCOM       Commented on category of photos
 *  GALCOM          Commented on photo
 *  GALLERY         Added photo
 *  JOINED          Joined the site (became active)
 *  NEWS            Added family news
 *  NEWSCOM         Commented on family news
 *  POLL            Added poll
 *  POLLCOM         Commented on poll
 *  PRAYERS         Added prayer concern
 *  RECIPES         Added recipe
 *  RECIPECOM       Commented on recipe
 *  STATUS          Added status update
 *  VIDEO           Added video
 *  VIDEOCOM        Commented on video
 *  WHEREISEVERYONE Checked in on foursquare
 * 
 * @param int    $userid
 * @param int    $days 
 * @param string $groupByType 
 * 
 * @return void
 */
function getWhatsNewData ($userid, $days = 30, $groupByType = false)
{
    $currentUserId = (int)$userid;

    $sql = "SELECT p.`id`, `date`, `subject` AS title, u.`id` AS userid, `thread` AS id2, 0 AS id3, 'BOARD' AS type 
            FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, fcms_users AS u 
            WHERE p.`thread` = t.`id` 
            AND p.`user` = u.`id` 
            AND `date` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 

            UNION SELECT a.`id`, c.`created` AS date, c.`column` AS title, a.`user` AS userid, a.`updated_id` AS id2, u.`sex` AS id3, 'ADDRESSEDIT' AS type
            FROM `fcms_changelog` AS c
            LEFT JOIN `fcms_users` AS u ON c.`user` = u.`id`
            LEFT JOIN `fcms_address` AS a ON u.`id` = a.`user`
            WHERE c.`created` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
            AND c.`column` != 'avatar'

            UNION SELECT a.id, a.updated AS date, 0 AS title, a.user AS userid, a.`created_id` AS id2, u.joindate AS id3, 'ADDRESSADD' AS type
            FROM fcms_address AS a, fcms_users AS u
            WHERE a.user = u.id
            AND u.`password` = 'NONMEMBER' 
            AND u.`activated` < 1 
            AND a.updated >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 

            UNION SELECT `id`, `joindate` AS date, 0 AS title, `id` AS userid, 0 AS id2, 0 AS id3, 'JOINED' AS type 
            FROM `fcms_users` 
            WHERE `password` != 'NONMEMBER' 
            AND `activated` > 0 
            AND `joindate` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) ";
    if (usingFamilyNews())
    {
        $sql .= "UNION SELECT n.`id` AS id, n.`updated` AS date, `title`, u.`id` AS userid, 0 AS id2, 0 AS id3, 'NEWS' AS type 
                 FROM `fcms_users` AS u, `fcms_news` AS n 
                 WHERE u.`id` = n.`user` 
                 AND n.`updated` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
                 AND `username` != 'SITENEWS' 
                 AND `password` != 'SITENEWS'

                 UNION SELECT n.`id` AS 'id', nc.`date`, `title`, nc.`user` AS userid, 0 AS id2, 0 AS id3, 'NEWSCOM' AS type 
                 FROM `fcms_news_comments` AS nc, `fcms_news` AS n, `fcms_users` AS u 
                 WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
                 AND nc.`user` = u.`id` 
                 AND n.`id` = nc.`news` ";
    }
    if (usingPrayers())
    {
        $sql .= "UNION SELECT 0 AS id, `date`, `for` AS title, `user` AS userid, 0 AS id2, 0 AS id3, 'PRAYERS' AS type 
                 FROM `fcms_prayers` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) ";
    }
    if (usingRecipes())
    {
        $sql .= "UNION SELECT `id` AS id, `date`, `name` AS title, `user` AS userid, `category` AS id2, 0 AS id3, 'RECIPES' AS type 
                 FROM `fcms_recipes` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 

                 UNION SELECT r.`id`, rc.`date`, r.`name` AS title, rc.`user` AS userid, r.`category` AS id2, 0 AS id3, 'RECIPECOM' AS type
                 FROM `fcms_recipe_comment` AS rc, `fcms_recipes` AS r
                 WHERE rc.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
                 AND rc.`recipe` = r.`id` ";
    }
    if (usingdocuments())
    {
        $sql .= "UNION SELECT d.`id` AS 'id', d.`date`, `name` AS title, d.`user` AS userid, 0 AS id2, 0 AS id3, 'DOCS' AS type 
                 FROM `fcms_documents` AS d, `fcms_users` AS u 
                 WHERE d.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
                 AND d.`user` = u.`id` ";
    }
    $sql .= "UNION SELECT DISTINCT p.`category` AS id, p.`date`, `name` AS title, p.`user` AS userid, COUNT(*) AS id2, DAYOFYEAR(p.`date`) AS id3, 'GALLERY' AS type 
             FROM `fcms_gallery_photos` AS p, `fcms_users` AS u, `fcms_category` AS c 
             WHERE p.`user` = u.`id` 
             AND p.`category` = c.`id` 
             AND p.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
             GROUP BY userid, title, id3

             UNION SELECT p.`id`, gc.`date`, `comment` AS title, gc.`user` AS userid, p.`user` AS id2, `filename` AS id3, 'GALCOM' AS type 
             FROM `fcms_gallery_photo_comment` AS gc, `fcms_users` AS u, `fcms_gallery_photos` AS p 
             WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
             AND gc.`user` = u.`id` 
             AND gc.`photo` = p.`id` 

             UNION SELECT g.`id`, g.`created`, c.`name` AS title, g.`created_id` AS userid, c.`user` AS id2, c.`id` AS id3, 'GALCATCOM' AS type 
             FROM `fcms_gallery_category_comment` AS g
             LEFT JOIN `fcms_users` AS u    ON g.`created_id`  = u.`id`
             LEFT JOIN `fcms_category` AS c ON g.`category_id` = c.`id`
             WHERE g.`created` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 

             UNION SELECT c.`id`, c.`date_added` AS date, `title`, `created_by` AS userid, `date` AS id2, `category` AS id3, 'CALENDAR' AS type 
             FROM `fcms_calendar` AS c, `fcms_users` AS u 
             WHERE c.`date_added` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
             AND c.`created_by` = u.`id` AND `private` < 1 

             UNION SELECT `id`, `started` AS date, `question`, '0' AS userid, 'na' AS id2, 'na' AS id3, 'POLL' AS type 
             FROM `fcms_polls` 
             WHERE `started` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 

             UNION SELECT p.`id`, c.`created` AS date, p.`question` AS title, c.`created_id` AS userid, 'na' AS id2, 'na' AS id3, 'POLLCOM' AS type 
             FROM `fcms_poll_comment` AS c
             LEFT JOIN `fcms_polls` AS p ON c.`poll_id` = p.`id`
             WHERE `created` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 

             UNION SELECT `id`, `updated` AS date, `status` AS title, `user` AS userid, `parent` AS id2, `created` AS id3, 'STATUS' AS type 
             FROM `fcms_status` 
             WHERE `updated` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
             AND `parent` = 0

             UNION SELECT 0 as id, c.`created` AS date, 0 AS title, c.`user` AS userid, 0 AS id2, u.`sex` AS id3, 'AVATAR' AS type
             FROM `fcms_changelog` AS c
             LEFT JOIN `fcms_users` AS u ON c.`user` = u.`id`
             WHERE `created` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
             AND `column` = 'avatar'

             UNION SELECT `id`, `created` AS date, `title`, `created_id` AS userid, `source_id` AS id2, `source` AS id3, 'VIDEO' AS type
             FROM `fcms_video`
             WHERE `created` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
             AND `active` = '1'

             UNION SELECT `video_id` AS 'id', c.`created` AS date, `comment`, c.`created_id` AS userid, `source_id` AS id2, `source` AS id3, 'VIDEOCOM' AS type
             FROM `fcms_video_comment` AS c
             LEFT JOIN `fcms_video` AS v ON c.`video_id` = v.`id`
             WHERE c.`created` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
             AND v.`active` = '1'

             ORDER BY date DESC LIMIT 0, 35";

    $result = mysql_query($sql);

    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return false;
    }

    // Save data
    while ($r = mysql_fetch_assoc($result))
    {
        if ($groupByType)
        {
            $whatsNewData[$r['type']][] = $r;
        }
        else
        {
            $whatsNewData[] = $r;
        }
    }

    // Add external foursquare data
    if (usingWhereIsEveryone())
    {
        include_once('socialmedia.php');
        include_once('thirdparty/foursquare/EpiFoursquare.php');
        include_once('thirdparty/foursquare/EpiCurl.php');

        $users  = getFoursquareUsersData();
        $config = getFoursquareConfigData();

        // TODO
        // Move this check inside the getFoursquareConfigData and have it return false on failure.

        // Foursquare hasn't been setup or is invalid
        if (empty($config['fs_client_id']) or empty($config['fs_client_secret']))
        {
            // If admin is viewing, alert them that the config is missing/messed up
            if (checkAccess($currentUserId) < 2)
            {
                echo '
                    <div class="info-alert">
                        <h2>'.T_('Foursquare is not configured correctly.').'</h2>
                        <p>'.T_('The "Where Is Everyone" feature cannot work without Foursquare.  Please configure Foursquare or turn off "Where Is Everyone".').'</p>
                    </div>';
            }

            return $whatsNewData;
        }

        $foursquareData = array();

        if (count($users[0]) > 0)
        {
            $timeago = gmmktime(0, 0, 0, gmdate('m'), gmdate('d')-$days, gmdate('Y'));

            $i = 0;
            foreach ($users as $k => $data)
            {
                // Skip users who don't have foursquare setup
                if (empty($data['access_token']))
                {
                    continue;
                }

                $fsObj = new EpiFoursquare($config['fs_client_id'], $config['fs_client_secret'], $data['access_token']);

                try
                {
                    $params = array(
                        'afterTimestamp' => $timeago
                    );
                    $creds = $fsObj->get('/users/'.$data['user_id'].'/checkins', $params);
                }
                catch(EpiFoursquareException $e)
                {
                    echo 'We caught an EpiOAuthException';
                    echo $e->getMessage();
                    return false;
                }
                catch(Exception $e)
                {
                    echo 'We caught an unexpected Exception';
                    echo $e->getMessage();
                    return false;
                }

                foreach ($creds->response->checkins->items as $checkin)
                {
                    // Skip shouts, etc
                    if ($checkin->type != 'checkin')
                    {
                        continue;
                    }

                    $date  = date('Y-m-d H:i:s', $checkin->createdAt);
                    $sort  = $checkin->createdAt;
                    $shout = isset($checkin->shout) ? $checkin->shout : '';

                    // Save data
                    if ($groupByType)
                    {
                        $whatsNewData['WHEREISEVERYONE'][] = array(
                            'id'        => '',
                            'date'      => $date,
                            'title'     => $checkin->venue->name,
                            'userid'    => $data['fcms_user_id'],
                            'id2'       => $shout,
                            'id3'       => '',
                            'type'      => 'WHEREISEVERYONE'
                        );
                    }
                    else
                    {
                        $whatsNewData[] = array(
                            'id'        => '',
                            'date'      => $date,
                            'title'     => $checkin->venue->name,
                            'userid'    => $data['fcms_user_id'],
                            'id2'       => $shout,
                            'id3'       => '',
                            'type'      => 'WHEREISEVERYONE'
                        );
                    }
                }
            }
        }

        // Order is messed up now, so fix it
        if ($groupByType)
        {
            $sorted = array();
            foreach ($whatsNewData as $type => $data)
            {
                $tmp = subval_sort($whatsNewData[$type], 'date');
                $tmp = array_reverse($tmp);

                $sorted[$type] = $tmp;
            }
            $whatsNewData = $sorted;
        }
        else
        {
            $whatsNewData = subval_sort($whatsNewData, 'date');
            $whatsNewData = array_reverse($whatsNewData);
        }
    }

    return $whatsNewData;
}

/**
 * ImageCreateFromBMP 
 * 
 * @author  DHKold
 * @contact admin@dhkold.com
 * @date    The 15th of June 2005
 * @version 2.0B
 *
 * @param   string  $filename 
 * @return  void
 */
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

/**
 * usingAdvancedUploader 
 * 
 * @param   int     $userid 
 * @return  boolean
 */
function usingAdvancedUploader ($userid)
{
    $userid = (int)$userid;

    $sql = "SELECT `advanced_upload` 
            FROM `fcms_user_settings` 
            WHERE `user` = '$userid'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return true;
    }

    $r = mysql_fetch_array($result);

    if ($r['advanced_upload'] != 1)
    {
        return false;
    }

    return true;
}

/**
 * usingAdvancedTagging
 * 
 * @param   int     $userid 
 * @return  boolean
 */
function usingAdvancedTagging ($userid)
{
    $userid = (int)$userid;

    $sql = "SELECT `advanced_tagging`
            FROM `fcms_user_settings` 
            WHERE `user` = '$userid'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return true;
    }

    $r = mysql_fetch_array($result);

    if ($r['advanced_tagging'] == 1)
    {
        return true;
    }
    else
    {
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
 * @param   string  $code 
 * @return  string
 */
function getLangName ($code)
{
    switch($code) {
        case 'cs_CZ':
            return T_('Czech (Czech Republic)');
            break;
        case 'da_DK':
            return T_('Danish (Denmark)');
            break;
        case 'de_DE':
            return T_('German (Germany)');
            break;
        case 'en_US':
            return T_('English (United States)');
            break;
        case 'es_ES':
            return T_('Spanish (Spain)');
            break;
        case 'et':
            return T_('Estonian');
            break;
        case 'fr_FR':
            return T_('French (France)');
            break;
        case 'lv':
            return T_('Latvian');
            break;
        case 'nl':
            return T_('Dutch');
            break;
        case 'pt_BR':
            return T_('Portuguese (Brazil)');
            break;
        case 'sk_SK':
            return T_('Slovak');
            break;
        case 'zh_CN':
            return T_('Chinese (China)');
            break;
        case 'x-wrap':
            return T_('X Wrapped');
            break;
        default:
            return $code;
            break;
    }
}

/**
 * recursive_array_search 
 * 
 * @param   string  $needle 
 * @param   string  $haystack 
 * @return  void
 */
function recursive_array_search ($needle, $haystack)
{
    foreach($haystack as $key=>$value) {
        $current_key = $key;
        if (
                $needle === $value OR 
                (is_array($value) && recursive_array_search($needle,$value) !== false)
        ) {
            return $current_key;
        }
    }
    return false;
}

/**
 * printr 
 *
 * Development only, wraps pre tags around print_r output.
 * 
 * @param   string  $var 
 * @return  void
 */
function printr ($var)
{
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

/**
 * getBirthdayCategory
 *
 * returns the id of the category for bithday, if available
 *
 * @return int
 */
function getBirthdayCategory ()
{
    $sql = "SELECT `id` 
            FROM `fcms_category` 
            WHERE `type` = 'calendar' 
                AND `name` like 'Birthday'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return 1;
    }

    if (mysql_num_rows($result) > 0)
    {
        $r = mysql_fetch_array($result);

        return $r['id'];
    }
    else
    {
        return 1;
    }
}

/**
 * getCalendarCategory 
 * 
 * Searches the db for a category that matches the given string
 *
 * @param   string  $cat 
 * @param   boolean $caseSensitive 
 * @return  int
 */
function getCalendarCategory ($cat, $caseSensitive = false)
{
    if ($caseSensitive)
    {
        $sql = "SELECT `id` 
                FROM `fcms_category` 
                WHERE `type` = 'calendar' 
                    AND `name` like '$cat'";
    }
    else
    {
        $sql = "SELECT `id` 
                FROM `fcms_category` 
                WHERE `type` = 'calendar' 
                    AND (
                        `name` like '".ucfirst($cat)."' OR
                        `name` like '".strtoupper($cat)."' OR
                        `name` like '".strtolower($cat)."' OR
                        `name` like '$cat'
                    )";
    }

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    if (mysql_num_rows($result) > 0)
    {
        $r = mysql_fetch_assoc($result);

        return $r['id'];
    }
    else
    {
        return 1;
    }
}

/**
 * getCurrentAvatar 
 * 
 * @param int $id User id
 * 
 * @return string
 */
function getCurrentAvatar ($id)
{
    $id = (int)$id;

    $sql = "SELECT `avatar`, `gravatar`
            FROM `fcms_users`
            WHERE `id` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return getAvatarPath('no_avatar.jpg', NULL);
    }

    // No Avatar set
    if (mysql_num_rows($result) <= 0)
    {
        return getAvatarPath('no_avatar.jpg', NULL);
    }

    $r = mysql_fetch_array($result);

    return getAvatarPath($r['avatar'], $r['gravatar']);
}

/**
 * getAvatarPath 
 * 
 * @param string $avatar   no_avatar.jpg | gravatar | <filename>
 * @param string $gravatar email address for gravatar or ''
 * 
 * @return string
 */
function getAvatarPath ($avatar, $gravatar)
{
    // Protected uploads
    if (defined('UPLOADS'))
    {
        switch ($avatar)
        {
            case 'no_avatar.jpg':
                return URL_PREFIX.'file.php?a=no_avatar.jpg';
                break;

            case 'gravatar':
                return 'http://www.gravatar.com/avatar.php?gravatar_id='.md5(strtolower($gravatar)).'&amp;s=80'; 
                break;

            default:
                return URL_PREFIX.'file.php?a='.basename($avatar);
                break;
        }
    }

    // Unprotected uploads
    switch ($avatar)
    {
        case 'no_avatar.jpg':
            return URL_PREFIX.'uploads/avatar/no_avatar.jpg';
            break;

        case 'gravatar':
            return 'http://www.gravatar.com/avatar.php?gravatar_id='.md5(strtolower($gravatar)).'&amp;s=80'; 
            break;

        default:
            return URL_PREFIX.'uploads/avatar/'.basename($avatar);
            break;
    }
}

/**
 * getTimezone 
 * 
 * @param int $user_id 
 * 
 * @return string
 */
function getTimezone ($user_id)
{
    $sql = "SELECT `timezone` 
            FROM `fcms_user_settings` 
            WHERE `user` = '$user_id'";

    $result = mysql_query($sql);

    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        die();
    }

    if (mysql_num_rows($result) <= 0)
    {
        displaySqlError($sql, mysql_error());
        die();
    }

    $r = mysql_fetch_array($result);

    return $r['timezone'];
}

/**
 * subval_sort 
 * 
 * Sorts a multidimensional array by a key in the sub array.
 * 
 * @param array  $a 
 * @param string $subkey 
 * 
 * @return void
 */
function subval_sort ($a, $subkey)
{
    foreach($a as $k => $v)
    {
        $b[$k] = strtolower($v[$subkey]);
    }

    asort($b);

    foreach($b as $key => $val)
    {
        $c[] = $a[$key];
    }

    return $c;
}

/**
 * isRegistrationOn 
 * 
 * Checks the admin configuration to see if the site is allowing registration of new users.
 * 
 * @return void
 */
function isRegistrationOn ()
{
    $sql = "SELECT `value` AS 'registration'
            FROM `fcms_config`
            WHERE `name` = 'registration'
            LIMIT 1";
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return false;
    }
    $r = mysql_fetch_array($result);

    $on = $r['registration'] == 1 ? true : false;

    return $on;
}

/**
 * getNextShareNavigationOrder 
 * 
 * Wrapper for getNextNavigationOrder.
 * Returns the next order number for the Share navigation.
 * 
 * @return void
 */
function getNextShareNavigationOrder ()
{
    return getNextNavigationOrder(4);
}

/**
 * getNextAdminNavigationOrder 
 * 
 * Wrapper for getNextNavigationOrder.
 * Returns the next order number for the Admininstration navigation.
 * 
 * @return void
 */
function getNextAdminNavigationOrder ()
{
    return getNextNavigationOrder(6);
}

/**
 * getNextNavigationOrder 
 * 
 * Returns the next order number for the given navigation column.
 * 
 * @return void
 */
function getNextNavigationOrder ($col)
{
    $sql = "SELECT MAX(`order`) AS 'order'
            FROM `fcms_navigation`
            WHERE `col` = $col";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return false;
    }

    if (mysql_num_rows($result) <= 0)
    {
        trigger_error('Navigation Order Missing or Corrupt');
        die();
    }

    $r = mysql_fetch_assoc($result);

    $next = $r['order'] + 1;

    return $next;
}

/**
 * getNumberOfPosts 
 * 
 * @param   int $thread_id 
 * @return  int
 */
function getNumberOfPosts ($thread_id)
{
    $sql = "SELECT count(*) AS c 
            FROM `fcms_board_posts` 
            WHERE `thread` = '".(int)$thread_id."'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return;
    }

    $row = mysql_fetch_assoc($result);

    return (int)$row['c'];
}

/**
 * postAsync
 * 
 * Sends an asynchronous post without waiting for a response.
 * Used to start "cron-like" jobs in the background.
 * 
 * @param string $url 
 * @param array  $params 
 * 
 * @return void
 */
function postAsync ($url, $params)
{
    foreach ($params as $key => &$val)
    {
        if (is_array($val))
        {
            $val = implode(',', $val);
        }
        $post_params[] = $key.'='.urlencode($val);
    }

    $post_string = implode('&', $post_params);

    $parts = parse_url($url);

    $fp = fsockopen($parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 30);

    $out  = "POST ".$parts['path']." HTTP/1.1\r\n";
    $out .= "Host: ".$parts['host']."\r\n";
    $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $out .= "Content-Length: ".strlen($post_string)."\r\n";
    $out .= "Connection: Close\r\n\r\n";

    if (isset($post_string))
    {
        $out .= $post_string;
    }

    fwrite($fp, $out);
    fclose($fp);
}

/**
 * runningJob 
 * 
 * Is a cron job currently being run?  If any errors occur, we say cron is running
 * just to be safe.
 * 
 * @return boolean
 */
function runningJob ()
{
    $sql = "SELECT `value`
            FROM `fcms_config`
            WHERE `name` = 'running_job'
            AND `value` = '1'";
    $result = mysql_query($sql);
    if (!$result)
    {
        // Lets be safe and say a job is being run
        return true;
    }

    if (mysql_num_rows($result) <= 0)
    {
        return false;
    }

    return true;
}

/**
 * runJob 
 * 
 * Sets the db flag that a job has started.  Returns true if worked, else false.
 * 
 * @return boolean
 */
function runJob ()
{
    $sql = "UPDATE `fcms_config`
            SET `value` = '1'
            WHERE `name` = 'running_job'";
    if (!mysql_query($sql))
    {
        return false;
    }

    // running_job config was missing, add it
    if (mysql_affected_rows() <= 0)
    {
        $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                VALUES ('running_job', '1')";
        if (!mysql_query($sql))
        {
            return false;
        }
    }

    return true;
}

/**
 * stopJob 
 * 
 * Turns off the job flag.  Returns true if worked, else false.
 * 
 * @return boolean
 */
function stopJob ()
{
    $sql = "UPDATE `fcms_config`
            SET `value` = '0'
            WHERE `name` = 'running_job'";
    if (!mysql_query($sql))
    {
        return false;
    }

    if (mysql_affected_rows() <= 0)
    {
        return false;
    }

    return true;
}

/**
 * getExistingYouTubeIds 
 * 
 * @return array
 */
function getExistingYouTubeIds ()
{
    $ids = array();

    $sql = "SELECT `source_id`
            FROM `fcms_video`
            WHERE `source` = 'youtube'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());

        return $ids;
    }

    while ($row = mysql_fetch_assoc($result))
    {
        $ids[$row['source_id']] = 1;
    }

    return $ids;
}

/**
 * getExistingInstagramIds 
 * 
 * @return array
 */
function getExistingInstagramIds ()
{
    $ids = array();

    $sql = "SELECT `source_id`
            FROM `fcms_gallery_external_photo`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());

        return $ids;
    }

    while ($row = mysql_fetch_assoc($result))
    {
        $ids[$row['source_id']] = 1;
    }

    return $ids;
}

/**
 * userConnectedSocialMedia 
 * 
 * @param int $userId 
 * 
 * @return boolean
 */
function userConnectedSocialMedia ($userId)
{
    // Get Social Media data
    $facebook   = getUserFacebookAccessToken($userId);
    $foursquare = getFoursquareUserData($userId);
    $youtube    = getYouTubeUserData($userId);

    // Facebook
    if (!empty($facebook))
    {
        return true;
    }

    // Foursquare
    if (!empty($foursquare['fs_user_id']) && !empty($foursquare['fs_access_token']))
    {
        return true;
    }

    // YouTube
    if (!empty($youtube['youtube_session_token']))
    {
        return true;
    }

    return false;
}

/**
 * delete_dir
 * 
 * Deletes a directory and everything in it.
 * 
 * @param string  $directory 
 * 
 * @return void
 */
function delete_dir ($directory)
{
    // removes trailing slash
    if (substr($directory, -1) == '/')
    {
        $directory = substr($directory, 0, -1);
    }

    if (!file_exists($directory) || !is_dir($directory))
    {
        return false;

    }
    elseif (!is_readable($directory))
    {
        return false;
    }

    $handle = opendir($directory);

    while (false !== ($item = readdir($handle)))
    {
        // skip . and ..
        if ($item != '.' && $item != '..')
        {
            $path = $directory.'/'.$item;

            // dirctory
            if (is_dir($path)) 
            {
                delete_dir($path);

            }
            else
            {
                unlink($path);
            }
        }
    }

    closedir($handle);

    // this dir is empty, delete it
    if (!rmdir($directory))
    {
        return false;
    }

    return true;
}

/**
 * buildCountryList 
 * 
 * Builds an array of country ISO 3166 names keyed by alpha-2 codes from the country.txt file.
 * This text file is from http://www.iso.org/iso/country_codes/iso_3166_code_lists.htm.
 * 
 * @return array
 */
function buildCountryList ()
{
    $countries = array();

    $lines = @file('inc/country.txt');
    if ($lines === false)
    {
        echo '<div class="error-alert">'.T_('Could not read inc/country.txt file.').'</div>';
        return $countries;
    }

    foreach($lines as $line)
    {
        $country = explode(";", $line);

        $country[0] = trim($country[0]);
        $country[1] = trim($country[1]);

        $countries[$country[1]] = $country[0];
    }

    return $countries;
}

/**
 * getDefaultCountry 
 * 
 * @return void
 */
function getDefaultCountry ()
{
    $sql = "SELECT `value` 
            FROM `fcms_config`
            WHERE `name` = 'country'";
    $result = mysql_query($sql);
    if (!$result)
    {
        return 'US';
    }

    $r = mysql_fetch_array($result);

    return $r['value'];
}

/**
 * getAddRemoveTaggedMembers 
 * 
 * @param array $tagged 
 * @param array $prev 
 * 
 * @return array
 */
function getAddRemoveTaggedMembers ($tagged = null, $prev = null)
{
    // Nothing to add or remove
    if (is_null($tagged) && is_null($prev))
    {
        return true;
    }

    if (!is_array($tagged) && !is_array($prev))
    {
        return false;
    }

    $add    = array();
    $remove = array();

    // Tagging new users on photo with already tagged users
    if (is_array($tagged) && is_array($prev))
    {
        // Find all additions
        foreach ($tagged as $id)
        {
            if (!in_array($id, $prev))
            {
                $add[] = $id;
            }
        }

        // Find all removals
        foreach ($prev as $id)
        {
            if (!in_array($id, $tagged))
            {
                $remove[] = $id;
            }
        }
    }
    // No tagged members now, but did have some previously
    elseif (!is_array($tagged) && is_array($prev))
    {
        $remove = $prev;
    }
    // Tagging new users, didn't have any previously
    elseif (is_array($tagged) && !is_array($prev))
    {
        $add = $tagged;
    }

    return array('add' => $add, 'remove' => $remove);
}

/**
 * getMembersNeedingActivation 
 * 
 * @return void
 */
function getMembersNeedingActivation ()
{
    $members = array();

    $sql = "SELECT `id`, `fname`, `lname`
            FROM `fcms_users`
            WHERE `activated` != 1
            AND `password` != 'NONMEMBER'
            ORDER BY `joindate` DESC";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        return false;
    }

    while ($r = mysql_fetch_assoc($result))
    {
        $members[$r['id']] = $r['fname'].' '.$r['lname'];
    }

    return $members;
}

/**
 * getCalendarWeekStart 
 * 
 * Returns the day, the calendar is set to start on.
 * 
 * 0 - 6, with 0 being Sunday and 6 being Saturday.
 * 
 * @return int
 */
function getCalendarWeekStart ()
{
    $sql = "SELECT `value` 
            FROM `fcms_config`
            WHERE `name` = 'start_week'";
    $result = mysql_query($sql);
    if (!$result)
    {
        return '0';
    }

    if (mysql_num_rows($result) <= 0)
    {
        return '0';
    }

    $r = mysql_fetch_array($result);

    return (int)$r['value'];
}

/**
 * getPage 
 * 
 * Returns the page number that was requested, defaults to 1.
 * 
 * @return int
 */
function getPage ()
{
    $page = 1;

    if (isset($_GET['page']))
    {
        $page = (int)$_GET['page'];
    }

    return $page;
}

/**
 * shortenString 
 * 
 * @param string $string 
 * @param int    $maxLength
 * @param string $end 
 * 
 * @return string
 */
function shortenString ($string, $maxLength = 100, $end = '')
{
    $endLength = 0;

    if ($end != '')
    {
        $endLength = strlen($end);
    }

    if (strlen($string) > $maxLength)
    {
        $string = substr($string, 0, $maxLength - $endLength) . $end;
    }

    return $string;
}

/**
 * getUserInstagramCategory 
 * 
 * Will return the category id for the instagram category.
 * If one doesn't exist yet, will create it and return the id.
 * 
 * @param int $userId 
 * 
 * @return void
 */
function getUserInstagramCategory ($userId)
{
    $userId = (int)$userId;

    $sql = "SELECT `id`
            FROM `fcms_category`
            WHERE `name` = 'Instagram'
            AND `user` = '$userId'";

    $result = mysql_query($sql);
    if (!$result)
    {
        // TODO
        return;
    }

    if (mysql_num_rows($result) <= 0)
    {
        // Create a new one
        $sql = "INSERT INTO `fcms_category`
                    (`name`, `type`, `user`, `date`)
                VALUES
                    ('Instagram', 'gallery', '$userId', NOW())";

        $result = mysql_query($sql);
        if (!$result)
        {
            // TODO
            return;
        }

        return mysql_insert_id();
    }

    $r = mysql_fetch_array($result);

    return $r['id'];
}

/**
 * getUploadsAbsolutePath 
 * 
 * Returns the absolute path to the uploads directory.
 * 
 * @return string
 */
function getUploadsAbsolutePath ()
{
    if (defined('UPLOADS'))
    {
        return UPLOADS;
    }

    return ROOT.'uploads/';
}
