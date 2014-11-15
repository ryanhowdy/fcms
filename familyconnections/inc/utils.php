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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    if (empty($userid))
    {
        return UI."themes/default/";
    }
    else
    {
        $userid = (int)$userid;

        $sql = "SELECT `theme` 
                FROM `fcms_user_settings` 
                WHERE `user` = ?";

        $r = $fcmsDatabase->getRow($sql, $userid);
        if ($r === false)
        {
            return UI."themes/default/";
        }

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
 * @param int     $userid 
 * @param int     $display 
 * @param boolean $isMember 
 * 
 * @return string
 */
function getUserDisplayName ($userid, $display = 0, $isMember = true)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $userid = (int)$userid;

    if ($userid <= 0)
    {
        $fcmsError->add(array(
            'type'    => 'operation',
            'message' => 'Invalid user id.',
            'error'   => $userid,
            'file'    => __FILE__,
            'line'    => __LINE__,
        ));
        return 'unknown';
    }

    if ($isMember)
    {
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, s.`displayname` 
                FROM `fcms_users` AS u, `fcms_user_settings` AS s 
                WHERE u.`id` = ?
                AND u.`id` = s.`user`";
    }
    else
    {
        $sql = "SELECT `fname`, `lname`, `username` 
                FROM `fcms_users` 
                WHERE `id` = ?";
    }

    $r = $fcmsDatabase->getRow($sql, $userid);
    if ($r === false)
    {
        return 'unknown';
    }

    if (empty($r))
    {
        $fcmsError->add(array(
            'type'    => 'operation',
            'message' => 'Cannot find user ['.$userid.'].',
            'error'   => $r,
            'file'    => __FILE__,
            'line'    => __LINE__,
        ));
        return 'unknown';
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    // Count was calculated during getUserNotifications()
    if (isset($_SESSION['private_messages']))
    {
        $count = $_SESSION['private_messages'];
    }
    else
    {
        $sql = "SELECT * FROM `fcms_privatemsg` 
                WHERE `read` < 1 
                AND `to` = ?";

        $rows = $fcmsDatabase->getRows($sql, $fcmsUser->id);
        if ($rows === false)
        {
            return '';
        }

        $count = count($rows);
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    // Count was calculated during getUserNotifications()
    if (isset($_SESSION['notifications']))
    {
        $count = $_SESSION['notifications'];
    }
    else
    {
        $sql = "SELECT `id` FROM `fcms_notification` 
                WHERE `read` < 1 
                AND `user` = ?
                AND `created_id` != ?";

        $rows = $fcmsDatabase->getRows($sql, array($fcmsUser->id, $fcmsUser->id));
        if ($rows === false)
        {
            return '';
        }

        $count = count($rows);
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `email`
            FROM `fcms_users`
            WHERE `id` = ?";

    $r = $fcmsDatabase->getRow($sql, $userid);
    if ($r === false)
    {
        return 'nothing@mail.com';
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `link` 
            FROM `fcms_navigation` 
            WHERE `col` = 4 
            AND `order` = 1";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return 'gallery/index.php';
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    $ret = array();

    $sql = "SELECT `link`, `col`
            FROM `fcms_navigation` 
            WHERE `order` != 0 
            AND `col` != 6
            ORDER BY `col`, `order`";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return $ret;
    }

    // Add links
    foreach ($rows as $r)
    {
        $ret['my-stuff'] = T_('My Stuff');

        // Notifications
        $notifications = getUserNotifications($fcmsUser->id);
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
    if ($fcmsUser->access <= 2)
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $ret = array();

    $sql = "SELECT `link`, `col`
            FROM `fcms_navigation` 
            WHERE `col` = 6
            ORDER BY `order`";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return $ret;
    }

    foreach ($rows as $r)
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $notifications = 0;

    $_SESSION['private_messages'] = $notifications;

    // Private Messages
    $sql = "SELECT `id` FROM `fcms_privatemsg` 
            WHERE `read` < 1 
            AND `to` = '$userId'";

    $rows = $fcmsDatabase->getRows($sql, $userId);
    if ($rows === false)
    {
        return false;
    }

    if (count($rows) > 0)
    {
        $notifications += count($rows);

        $_SESSION['private_messages'] = $notifications;
    }

    // Tagged notifications
    $sql = "SELECT `id` FROM `fcms_notification` 
            WHERE `read` < 1 
            AND `user` = '$userId'
            AND `created_id` != '$userId'";

    $rows = $fcmsDatabase->getRows($sql, array($userId, $userId));
    if ($rows === false)
    {
        return false;
    }

    if (count($rows) > 0)
    {
        $tagged = count($rows);

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $userid = (int)$userid;

    $sql = "SELECT `id` 
            FROM `fcms_privatemsg` 
            WHERE `to` = ?
            AND `read` < 1";

    $rows = $fcmsDatabase->getRows($sql, $userid);
    if ($rows === false)
    {
        return ' ';
    }

    if (count($rows) > 0)
    {
        echo '<a href="'.URL_PREFIX.'privatemsg.php" class="new_pm">'.T_('New PM').'</a> ';
    }
    else
    {
        echo ' ';
    }
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = new User($fcmsError, $fcmsDatabase);

    $accessLevel = T_('Member');

    switch ($fcmsUser->access) {
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
        '<iframe class="youtube-player" type="text/html" width="640" height="385" src="http://www.youtube.com/embed/$1" allowfullscreen frameborder="0"></iframe>',
        '<iframe class="youtube-player" type="text/html" width="640" height="385" src="http://www.youtube.com/embed/$1" allowfullscreen frameborder="0"></iframe>'
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
        ''    // video
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
        '/\[video\](?:http(?:s)?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"\'>]+)\[\/video\]/is',
        '/\[video\]([^\?&\"\'>]+)\[\/video\]/is'
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
            '<img src="'.URL_PREFIX.'ui/img/smileys/'.$smiley_file_array[$i].'" alt="'.$smiley_array[$i].'"/>', 
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
            echo '<div class="smiley"><img src="'.URL_PREFIX.'ui/img/smileys/' . $smiley_file_array[$i] . '" alt="' . $smiley . '" onclick="return addSmiley(\''.str_replace("'", "\'", $smiley).'\')" /></div>';
            $previous_smiley_file = $smiley_file_array[$i];
        }
        $i++;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_board_posts`";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $total = isset($row['c']) ? $row['c'] : 0;

    $sql = "SELECT COUNT(`user`) AS c 
            FROM `fcms_board_posts` 
            WHERE `user` = ?";

    $row = $fcmsDatabase->getRow($sql, $user_id);
    if ($row == false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $count = isset($row['c']) ? $row['c'] : 0;

    if ($total < 1 || $count < 1)
    {
        $count   = '0';
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_gallery_photos`";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $total = isset($row['c']) ? $row['c'] : 0;

    $sql = "SELECT COUNT(`user`) AS c 
            FROM `fcms_gallery_photos` 
            WHERE `user` = ?";

    $row = $fcmsDatabase->getRow($sql, $user_id);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $count = isset($row['c']) ? $row['c'] : 0;

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_gallery_photo_comment`";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $total = isset($row['c']) ? $row['c'] : 0;

    $sql = "SELECT COUNT(`user`) AS c 
            FROM `fcms_gallery_photo_comment` 
            WHERE `user` = ?";

    $row = $fcmsDatabase->getRow($sql, $user_id);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $count = isset($row['c']) ? $row['c'] : 0;

    // Check Family News if applicable
    if (usingFamilyNews())
    {
        $sql = "SELECT COUNT(`id`) AS c 
                FROM `fcms_news_comments`";

        $row = $fcmsDatabase->getRow($sql);
        if ($row === false)
        {
            $fcmsError->displayError();
            return '0';
        }

        $total = $total + isset($row['c']) ? $row['c'] : 0;

        $sql = "SELECT COUNT(`user`) AS c 
                FROM `fcms_news_comments` 
                WHERE `user` = ?";

        $row = $fcmsDatabase->getRow($sql, $user_id);
        if ($row === false)
        {
            $fcmsError->displayError();
            return '0';
        }

        $count = $count + isset($row['c']) ? $row['c'] : 0;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_calendar`";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $total = isset($row['c']) ? $row['c'] : 0;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_calendar` 
            WHERE `created_by` = ?";

    $row = $fcmsDatabase->getRow($sql, $user_id);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $count = isset($row['c']) ? $row['c'] : 0;

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_news`";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $total = isset($row['c']) ? $row['c'] : 0;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_news` 
            WHERE `user` = ?
            GROUP BY `user`";

    $row = $fcmsDatabase->getRow($sql, $user_id);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $count = isset($row['c']) ? $row['c'] : 0;

    if ($total < 1 || $count < 1)
    {
        $count   = '0';
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_recipes`";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $total = isset($row['c']) ? $row['c'] : 0;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_recipes` 
            WHERE `user` = ?
            GROUP BY `user`";

    $row = $fcmsDatabase->getRow($sql, $user_id);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $count = isset($row['c']) ? $row['c'] : 0;

    if ($total < 1 || $count < 1)
    {
        $count   = '0';
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_documents`";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $total = isset($row['c']) ? $row['c'] : 0;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_documents` 
            WHERE `user` = ?
            GROUP BY `user`";

    $row = $fcmsDatabase->getRow($sql, $user_id);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $count = isset($row['c']) ? $row['c'] : 0;

    if ($total < 1 || $count < 1)
    {
        $count   = '0';
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_prayers`";

    $row = $fcmsDatabase->getRow($sql, $user_id);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $total = isset($row['c']) ? $row['c'] : 0;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_prayers` 
            WHERE `user` = ?
            GROUP BY `user`";

    $row = $fcmsDatabase->getRow($sql, $user_id);
    if ($row === false)
    {
        $fcmsError->displayError();
        return '0';
    }

    $count = isset($row['c']) ? $row['c'] : 0;

    if ($total < 1 || $count < 1)
    {
        $count   = '0';
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $news_id = (int)$news_id;

    $sql = "SELECT COUNT(`id`) AS c 
            FROM `fcms_news_comments` 
            WHERE `news` = '$news_id'";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return false;
    }

    return $r['c'] ? $r['c'] : 0;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $id     = (int)$id;
    $points = 0;

    $commentTables = array('fcms_gallery_photo_comment');

    // Thread (5)
    $sql = "SELECT COUNT(`id`) AS thread
            FROM `fcms_board_threads`
            WHERE `started_by` = ?";

    $r = $fcmsDatabase->getRow($sql, $id);
    if ($r === false)
    {
        return 0;
    }

    $points += $r['thread'] * 5;

    // Photo (3)
    $sql = "SELECT COUNT(`id`) AS photo 
            FROM `fcms_gallery_photos` 
            WHERE `user` = ?";

    $r = $fcmsDatabase->getRow($sql, $id);
    if ($r === false)
    {
        return 0;
    }

    $points += $r['photo'] * 3;

    // News (3)
    if (usingFamilyNews())
    {
        array_push($commentTables, 'fcms_news_comments');

        $sql = "SELECT COUNT(`id`) AS news 
                FROM `fcms_news` 
                WHERE `user` = ?";

        $r = $fcmsDatabase->getRow($sql, $id);
        if ($r === false)
        {
            return 0;
        }

        $points += $r['news'] * 3;
    }

    // Recipe (2)
    if (usingRecipes())
    {
        array_push($commentTables, 'fcms_recipe_comment');

        $sql = "SELECT COUNT(`id`) AS recipe 
                FROM `fcms_recipes` 
                WHERE `user` = ?";

        $r = $fcmsDatabase->getRow($sql, $id);
        if ($r === false)
        {
            return 0;
        }

        $points += $r['recipe'] * 2;
    }

    // Document (2)
    if (usingDocuments())
    {
        $sql = "SELECT COUNT(`id`) AS doc 
                FROM `fcms_documents` 
                WHERE `user` = ?";

        $r = $fcmsDatabase->getRow($sql, $id);
        if ($r === false)
        {
            return 0;
        }

        $points += $r['doc'] * 2;
    }

    // Prayer (2)
    if (usingPrayers())
    {
        $sql = "SELECT COUNT(`id`) AS prayer 
                FROM `fcms_prayers` 
                WHERE `user` = ?";

        $r = $fcmsDatabase->getRow($sql, $id);
        if ($r === false)
        {
            return 0;
        }

        $points += $r['prayer'] * 2;
    }

    // Post (2)
    $sql = "SELECT COUNT(`id`) AS post 
            FROM `fcms_board_posts` 
            WHERE `user` = ?";

    $r = $fcmsDatabase->getRow($sql, $id);
    if ($r === false)
    {
        return 0;
    }

    $points += $r['post'] * 2;

    // Comment (2)
    $from  = implode('`, `', $commentTables);
    $where = implode("`.`user` = '$id' AND `", $commentTables);

    $sql = "SELECT COUNT(*) AS comment 
            FROM `$from` 
            WHERE `$where`.`user` = '$id'";

    $r = $fcmsDatabase->getRow($sql, $id);
    if ($r === false)
    {
        return 0;
    }

    $points += $r['comment'] * 2;

    // Address/Phone (1)
    $sql = "SELECT `address`, `city`, `state`, `home`, `work`, `cell` 
            FROM `fcms_address` 
            WHERE `user` = ?";

    $r = $fcmsDatabase->getRow($sql, $id);
    if ($r === false)
    {
        return 0;
    }

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
            WHERE `created_by` = ?";

    $r = $fcmsDatabase->getRow($sql, $id);
    if ($r === false)
    {
        return 0;
    }

    $points += $r['event'];

    // Vote
    $sql = "SELECT COUNT(`id`) AS vote 
            FROM `fcms_poll_votes` 
            WHERE `user` = ?";

    $r = $fcmsDatabase->getRow($sql, $id);
    if ($r === false)
    {
        return 0;
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `value` 
            FROM `fcms_config`
            WHERE `name` = 'contact'";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return 'ERROR-contact';
    }

    return $r['value'];
}

/**
 * getSiteName 
 * 
 * @return  string
 */
function getSiteName()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `value`
            FROM `fcms_config`
            WHERE `name` = 'sitename'";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return 'ERROR-sitename';
    }

    return $r['value'];
}

/**
 * getCurrentVersion 
 * 
 * @return  void
 */
function getCurrentVersion()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `value`
            FROM `fcms_config`
            WHERE `name` = 'current_version'";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return 'ERROR-current_version';
    }

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
        <div id="'.$id.'" class="ok-msg-container" style="display:none">
            <div class="ok-msg">
                <a class="close-msg" href="#" onclick="$(\'#'.$id.'\').fadeOut(\'slow\')" title="'.T_('Close Message').'">x</a>
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
            $(document).ready(function() {
                $("#'.$id.'").slideDown("'.$id.'");
                var t=setTimeout("$(\'#'.$id.'\').fadeOut(\'slow\')", '.$timeout.'); 
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = new User($fcmsError, $fcmsDatabase);

    if ($fcmsError->hasError())
    {
        $fcmsError->displayError();
        return;
    }

    include_once('gallery_class.php');

    $currentUserId = (int)$_SESSION['fcms_id'];

    $gallery = new PhotoGallery($fcmsError, $fcmsDatabase, $fcmsUser);

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
 * isValidLoginToken
 * 
 * Checks the user's token against the db.
 *
 * @param $userid
 * @param $token
 *
 * @return boolean
 */
function isValidLoginToken ($userid, $token)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $userid = (int)$userid;

    $sql = "SELECT `token`
            FROM `fcms_users` 
            WHERE `id` = ? 
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql, $userid);
    if ($r === false)
    {
        return false;
    }

    if (empty($r))
    {
        return false;
    }

    if ($r['token'] == $token)
    {
        return true;
    }

    return false;
}

/**
 * loginUser 
 * 
 * Generate token.
 * Save token in db/session/cookie.
 * Update user activity.
 * Reset invalid login attempts
 * 
 * @param $userId   int     Id of user logging in.
 * @param $remember boolean Whether to save token in cookie.
 * 
 * @return boolean
 */
function loginUser ($userId, $remember)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $token = uniqid('');

    $sql = "UPDATE `fcms_users` 
            SET `activity` = NOW(),
                `login_attempts` = '0',
                `token` = ?
            WHERE `id` = ?";

    if (!$fcmsDatabase->update($sql, array($token, $userId)))
    {
        $fcmsError->setMessage(T_('Could not complete login.'));
        return false;
    }

    // Setup Cookie/Session
    if ($remember >= 1)
    {
        setcookie('fcms_cookie_id', $userId, time() + (30*(24*3600)), '/');   // 30 days
        setcookie('fcms_cookie_token', $token, time() + (30*(24*3600)), '/'); // 30 days
    }

    $_SESSION['fcms_id']    = $userId;
    $_SESSION['fcms_token'] = $token;

    // Load up all the good user data
    $fcmsUser = new User($fcmsError, $fcmsDatabase);

    return true;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `id`, `link`, `order`
            FROM `fcms_navigation` 
            WHERE `link` = ?
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql, $section); 
    if ($r === false)
    {
        return false;
    }

    if ($r['order'] > 0)
    {
        return true;
    }

    return false;
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
    $trace = array_reverse(debug_backtrace());

    $stack = '';

    for ($i = 0; $i < count($trace); $i++)
    {
        $stack .= '#'.$i.' '.$trace[$i]['function'].' called at ['.$trace[$i]['file'].':'.$trace[$i]['line'].']<br/>';
    }

    echo '
        <div class="error-alert">
            <p><b>Deprecated: displaySqlError()</b></p>
            <p><b>Stack</b>:<br/><small>'.$stack.'</small></p>
        </div>';
}

/**
 * logError 
 * 
 * @todo  this should be moved to Error class
 *        and shouldn't be called direcly
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `value`
            FROM `fcms_config`
            WHERE `name` = 'debug'
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return false;
    }

    if (count($r) <= 0)
    {
        return false;
    }

    $on = $r['value'] == 1 ? true : false;

    return $on;
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
 * @param int $days 
 * 
 * @return mixed - array on success or false on failure
 */
function getWhatsNewData ($days = 30)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    $whatsNewData = array();

    $sql = "SELECT p.`id`, `date`, `subject` AS title, p.`post` AS details, u.`id` AS userid, `thread` AS id2, 0 AS id3, 'BOARD' AS type
            FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, fcms_users AS u 
            WHERE p.`thread` = t.`id` 
            AND p.`user` = u.`id` 
            AND `date` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 

            UNION SELECT a.`id`, c.`created` AS date, c.`column` AS title, '' AS details, a.`user` AS userid, a.`updated_id` AS id2, u.`sex` AS id3, 'ADDRESSEDIT' AS type
            FROM `fcms_changelog` AS c
            LEFT JOIN `fcms_users` AS u ON c.`user` = u.`id`
            LEFT JOIN `fcms_address` AS a ON u.`id` = a.`user`
            WHERE c.`created` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
            AND c.`column` != 'avatar'

            UNION SELECT a.id, a.updated AS date, 0 AS title, '' AS details, a.user AS userid, a.`created_id` AS id2, u.joindate AS id3, 'ADDRESSADD' AS type
            FROM fcms_address AS a, fcms_users AS u
            WHERE a.user = u.id
            AND u.`phpass` = 'NONMEMBER' 
            AND u.`activated` < 1 
            AND a.updated >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 

            UNION SELECT `id`, `joindate` AS date, 0 AS title, '' AS details, `id` AS userid, 0 AS id2, 0 AS id3, 'JOINED' AS type 
            FROM `fcms_users` 
            WHERE `phpass` != 'NONMEMBER' 
            AND `joindate` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
            AND `activated` > 0 ";
    if (usingFamilyNews())
    {
        $sql .= "UNION SELECT n.`id` AS id, n.`updated` AS date, `title`, n.`news` AS details, u.`id` AS userid, u.`sex` AS id2, 0 AS id3, 'NEWS' AS type 
                 FROM `fcms_users` AS u, `fcms_news` AS n 
                 WHERE u.`id` = n.`user` 
                 AND n.`updated` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
                 AND `username` != 'SITENEWS' 
                 AND `phpass` != 'SITENEWS'

                 UNION SELECT n.`id` AS 'id', nc.`date`, `title`, nc.`comment` AS details, nc.`user` AS userid, 0 AS id2, 0 AS id3, 'NEWSCOM' AS type 
                 FROM `fcms_news_comments` AS nc, `fcms_news` AS n, `fcms_users` AS u 
                 WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
                 AND nc.`user` = u.`id` 
                 AND n.`id` = nc.`news` ";
    }
    if (usingPrayers())
    {
        $sql .= "UNION SELECT 0 AS id, `date`, `for` AS title, `desc` AS details, `user` AS userid, 0 AS id2, 0 AS id3, 'PRAYERS' AS type 
                 FROM `fcms_prayers` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) ";
    }
    if (usingRecipes())
    {
        $sql .= "UNION SELECT `id` AS id, `date`, `name` AS title, '' AS details, `user` AS userid, `category` AS id2, 0 AS id3, 'RECIPES' AS type 
                 FROM `fcms_recipes` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 

                 UNION SELECT r.`id`, rc.`date`, r.`name` AS title, rc.`comment` AS details, rc.`user` AS userid, r.`category` AS id2, 0 AS id3, 'RECIPECOM' AS type
                 FROM `fcms_recipe_comment` AS rc, `fcms_recipes` AS r
                 WHERE rc.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
                 AND rc.`recipe` = r.`id` ";
    }
    if (usingdocuments())
    {
        $sql .= "UNION SELECT d.`id` AS 'id', d.`date`, `name` AS title, d.`description` AS details, d.`user` AS userid, 0 AS id2, 0 AS id3, 'DOCS' AS type 
                 FROM `fcms_documents` AS d, `fcms_users` AS u 
                 WHERE d.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
                 AND d.`user` = u.`id` ";
    }
    $sql .= "UNION SELECT DISTINCT p.`category` AS id, p.`date`, `name` AS title, '' AS details, p.`user` AS userid, COUNT(*) AS id2, DAYOFYEAR(p.`date`) AS id3, 'GALLERY' AS type 
             FROM `fcms_gallery_photos` AS p, `fcms_users` AS u, `fcms_category` AS c 
             WHERE p.`user` = u.`id` 
             AND p.`category` = c.`id` 
             AND p.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
             GROUP BY userid, title, id3

             UNION SELECT p.`id`, gc.`date`, gc.`comment` AS title, gc.`comment` AS details, gc.`user` AS userid, p.`user` AS id2, `filename` AS id3, 'GALCOM' AS type 
             FROM `fcms_gallery_photo_comment` AS gc, `fcms_users` AS u, `fcms_gallery_photos` AS p 
             WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
             AND gc.`user` = u.`id` 
             AND gc.`photo` = p.`id` 

             UNION SELECT g.`id`, g.`created`, c.`name` AS title, g.`comment` AS details, g.`created_id` AS userid, c.`user` AS id2, c.`id` AS id3, 'GALCATCOM' AS type 
             FROM `fcms_gallery_category_comment` AS g
             LEFT JOIN `fcms_users` AS u    ON g.`created_id`  = u.`id`
             LEFT JOIN `fcms_category` AS c ON g.`category_id` = c.`id`
             WHERE g.`created` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 

             UNION SELECT c.`id`, c.`date_added` AS date, `title`, c.`desc` AS details, `created_by` AS userid, `date` AS id2, `category` AS id3, 'CALENDAR' AS type 
             FROM `fcms_calendar` AS c, `fcms_users` AS u 
             WHERE c.`date_added` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
             AND c.`created_by` = u.`id` AND `private` < 1 

             UNION SELECT `id`, `started` AS date, `question` AS title, '' AS details, '0' AS userid, 'na' AS id2, 'na' AS id3, 'POLL' AS type 
             FROM `fcms_polls` 
             WHERE `started` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 

             UNION SELECT p.`id`, c.`created` AS date, p.`question` AS title, c.`comment` AS details, c.`created_id` AS userid, 'na' AS id2, 'na' AS id3, 'POLLCOM' AS type 
             FROM `fcms_poll_comment` AS c
             LEFT JOIN `fcms_polls` AS p ON c.`poll_id` = p.`id`
             WHERE `created` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 

             UNION SELECT `id`, `updated` AS date, `status` AS title, '' AS details, `user` AS userid, `parent` AS id2, `created` AS id3, 'STATUS' AS type 
             FROM `fcms_status` 
             WHERE `updated` >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 

             UNION SELECT 0 as id, c.`created` AS date, 0 AS title, '' AS details, c.`user` AS userid, 0 AS id2, u.`sex` AS id3, 'AVATAR' AS type
             FROM `fcms_changelog` AS c
             LEFT JOIN `fcms_users` AS u ON c.`user` = u.`id`
             WHERE `created` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
             AND `column` = 'avatar'

             UNION SELECT `id`, `created` AS date, `title`, `description` AS details, `created_id` AS userid, `source_id` AS id2, `source` AS id3, 'VIDEO' AS type
             FROM `fcms_video`
             WHERE `created` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
             AND `active` = '1'

             UNION SELECT `video_id` AS 'id', c.`created` AS date, `comment`, '' AS details, c.`created_id` AS userid, `source_id` AS id2, `source` AS id3, 'VIDEOCOM' AS type
             FROM `fcms_video_comment` AS c
             LEFT JOIN `fcms_video` AS v ON c.`video_id` = v.`id`
             WHERE c.`created` >= DATE_SUB(CURDATE(),INTERVAL $days DAY) 
             AND v.`active` = '1'

             ORDER BY date DESC LIMIT 0, 35";

    $whatsNewData = $fcmsDatabase->getRows($sql);
    if ($whatsNewData === false)
    {
        $fcmsError->setMessage(T_('Could not get What\'s New data.'));
        return false;
    }

    // Get additional data
    $whatsNewData = getAdditionalWhatsNewData($whatsNewData);
    if ($whatsNewData === false)
    {
        $fcmsError->setMessage(T_('Could not get additional What\'s New data.'));
        return false;
    }

    // Add external foursquare data
    if (usingWhereIsEveryone())
    {
        $whatsNewData = getFoursquareWhatsNewData($whatsNewData);
        if ($whatsNewData === false)
        {
            $fcmsError->setMessage(T_('Could not get Foursquare What\'s New data.'));
            return false;
        }
    }

    // Now lets group events together
    $groupedData = array();
    foreach ($whatsNewData as $data)
    {
        // Build a lookup for the data for each new item
        $lkup = array();
        switch ($data['type'])
        {
            case 'THREAD':
            case 'BOARD':
                $lkup = array(
                    'type'     => 'thread_',
                    'parent'   => 'THREAD',
                    'child'    => 'BOARD',
                    'parentId' => 'id2',
                    'childId'  => 'id2',
                );
                break;

            case 'GALLERY':
            case 'GALCATCOM':
                $lkup = array(
                    'type'     => 'gallery_',
                    'parent'   => 'GALLERY',
                    'child'    => 'GALCATCOM',
                    'parentId' => 'id',
                    'childId'  => 'id3',
                );
                break;

            case 'NEWS':
            case 'NEWSCOM':
                $lkup = array(
                    'type'     => 'news_',
                    'parent'   => 'NEWS',
                    'child'    => 'NEWSCOM',
                    'parentId' => 'id',
                    'childId'  => 'id',
                );
                break;

            case 'POLL':
            case 'POLLCOM':
                $lkup = array(
                    'type'     => 'poll_',
                    'parent'   => 'POLL',
                    'child'    => 'POLLCOM',
                    'parentId' => 'id',
                    'childId'  => 'id',
                );
                break;

            case 'RECIPES':
            case 'RECIPECOM':
                $lkup = array(
                    'type'     => 'recipe_',
                    'parent'   => 'RECIPES',
                    'child'    => 'RECIPECOM',
                    'parentId' => 'id',
                    'childId'  => 'id',
                );
                break;

            case 'STATUS':
            case 'STATUSCOM':
                $lkup = array(
                    'type'     => 'status_',
                    'parent'   => 'STATUS',
                    'child'    => 'STATUSCOM',
                    'parentId' => 'id',
                    'childId'  => 'id2',
                );
                break;

            case 'VIDEO':
            case 'VIDEOCOM':
                $lkup = array(
                    'type'     => 'video_',
                    'parent'   => 'VIDEO',
                    'child'    => 'VIDEOCOM',
                    'parentId' => 'id',
                    'childId'  => 'id',
                );
                break;
        }

        // Group things together
        if (count($lkup) > 0)
        {
            $id = $data['type'] == $lkup['parent'] ? $data[ $lkup['parentId'] ] 
                                                   : $data[ $lkup['childId'] ];

            if (isset($groupedData[ $lkup['type'] . $id ]))
            {
                if ($data['type'] == $lkup['parent'])
                {
                    // put it at the top of the group
                    array_unshift($groupedData[ $lkup['type'] . $id ], $data);
                }
                else
                {
                    // put it at the end of the group
                    $groupedData[ $lkup['type'] . $id ][] = $data;
                }
            }
            else
            {
                // start a new group
                $groupedData[ $lkup['type'] . $id ] = array( $data );
            }

        }
        // Just add ungrouped items
        else
        {
            $groupedData[]  = array( $data );
        }
    }

    return $groupedData;
} 

/**
 * getAdditionalWhatsNewData 
 * 
 * Adds additional data that couldn't be retreived with one sql
 * statements when getting data.
 * 
 * @param array $whatsNewData 
 * 
 * @return mixed - array on success or false on failure
 */
function getAdditionalWhatsNewData ($whatsNewData)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    // Get some extra data, do little cleanup
    foreach ($whatsNewData as $key => $data)
    {
        // Get last 4 photos in category
        if ($data['type'] == 'GALLERY')
        {
            $limit = 4;
            if ($data['id2'] < $limit)
            {
                $limit = (int)$data['id2'];
            }

            $sql = "SELECT p.`id`, p.`user`, p.`category`, p.`filename`, p.`caption`,
                        p.`external_id`, e.`thumbnail`, e.`medium`, e.`full`
                    FROM `fcms_gallery_photos` AS p
                    LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                    WHERE p.`category` = ?
                    AND DAYOFYEAR(p.`date`) = ?
                    ORDER BY p.`date` 
                    DESC LIMIT $limit";

            $photos = $fcmsDatabase->getRows($sql, array($data['id'], $data['id3']));
            if ($photos === false)
            {
                return false;
            }

            $whatsNewData[$key]['photos'] = $photos;
        }
        // See if this is a new thread or a reply
        elseif ($data['type'] == 'BOARD')
        {
            $sql = "SELECT MIN(`id`) AS 'id' 
                    FROM `fcms_board_posts` 
                    WHERE `thread` = ?";

            $minpost = $fcmsDatabase->getRow($sql, $data['id2']);
            if ($minpost === false)
            {
                return false;
            }

            if ($minpost['id'] == $data['id'])
            {
                $whatsNewData[$key]['type'] = 'THREAD';
            }
        }
        // See if this is a new status update or reply
        elseif ($data['type'] == 'STATUS')
        {
            if ($data['id2'] != 0)
            {
                $whatsNewData[$key]['type'] = 'STATUSCOM';
            }
        }
        elseif ($data['type'] == 'GALCOM')
        {
            $photo = array(
                'id'       => $data['id'],
                'user'     => $data['id2'],
                'filename' => $data['id3'],
            );

            if ($data['id3'] == 'noimage.gif')
            {
                $sql = "SELECT p.`id`, p.`filename`, p.`external_id`, e.`thumbnail`
                        FROM `fcms_gallery_photos` AS p
                        LEFT JOIN `fcms_gallery_external_photo` AS e ON p.`external_id` = e.`id`
                        WHERE p.`id` = ?";

                $p = $this->fcmsDatabase->getRow($sql, $data['id']);
                if ($p === false)
                {
                    $this->fcmsError->displayError();
                    return;
                }

                $photo['external_id'] = $p['external_id'];
                $photo['thumbnail']   = $p['thumbnail'];
            }

            $whatsNewData[$key]['photos'][] = $photo;
        }
    }

    return $whatsNewData;
}

/**
 * getFoursquareWhatsNewData 
 * 
 * Adds foursquare data to the whats new data array.
 * 
 * @param array $whatsNewData 
 * 
 * @return mixed - array on success or false on failure
 */
function getFoursquareWhatsNewData ($whatsNewData)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

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
        if ($fcmsUser->access < 2)
        {
            echo '
                    <div class="info-alert">
                        <h2>'.T_('Foursquare is not configured correctly.').'</h2>
                        <p>'.T_('The "Where Is Everyone" feature cannot work without Foursquare.  Please configure Foursquare or turn off "Where Is Everyone".').'</p>
                        <p><a href="admin/foursquare.php">'.T_('Configure Foursquare').'</a></p>
                        <p><a href="admin/config.php?view=plugins">'.T_('Turn Off "Where is Everyone"').'</a></p>
                    </div>';
        }

        return $whatsNewData;
    }

    $foursquareData = array();

    if (count($users[0]) > 0)
    {
        $timeago = gmmktime(0, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y'));

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

    // Order is messed up now, so fix it
    $whatsNewData = subval_sort($whatsNewData, 'date');
    $whatsNewData = array_reverse($whatsNewData);

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
 * getUploaderType
 * 
 * @param int $userid 
 * 
 * @return string
 */
function getUploaderType ($userid)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `uploader` 
            FROM `fcms_user_settings` 
            WHERE `user` = ?";

    $r = $fcmsDatabase->getRow($sql, $userid);
    if ($r === false)
    {
        return 'basic';
    }

    $validUploaderTypes = array(
        'plupload' => 1,
        'java'     => 1,
        'basic'    => 1,
    );

    if (isset($validUploaderTypes[$r['uploader']]))
    {
        return $r['uploader'];
    }

    return 'plupload';
}

/**
 * usingAdvancedTagging
 * 
 * @param   int     $userid 
 * @return  boolean
 */
function usingAdvancedTagging ($userid)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `advanced_tagging`
            FROM `fcms_user_settings` 
            WHERE `user` = ?";

    $r = $fcmsDatabase->getRow($sql, $userid);
    if ($r === false)
    {
        return true;
    }

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
        case 'it_IT':
            return T_('Italian (Italy)');
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
    echo '<pre style="text-align:left; background-color:white; color:#333; padding:20px">';
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `id` 
            FROM `fcms_category` 
            WHERE `type` = 'calendar' 
                AND `name` like 'Birthday'";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return 1;
    }

    if (!empty($r))
    {
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql    = '';
    $params = array();

    if ($caseSensitive)
    {
        $sql = "SELECT `id` 
                FROM `fcms_category` 
                WHERE `type` = 'calendar' 
                    AND `name` LIKE ?";

        $params = array($cat);
    }
    else
    {
        $sql = "SELECT `id` 
                FROM `fcms_category` 
                WHERE `type` = 'calendar' 
                    AND (
                        `name` LIKE ? OR
                        `name` LIKE ? OR
                        `name` LIKE ? OR
                        `name` LIKE ?
                    )";

        $params = array(
            ucfirst($cat),
            strtoupper($cat),
            strtolower($cat),
            $cat
        );
    }

    $r = $fcmsDatabase->getRow($sql, $params);
    if ($r === false)
    {
        $fcmsError->displayError();

        return;
    }

    if (count($r) > 0)
    {
        return $r['id'];
    }

    return 1;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $id = (int)$id;

    $sql = "SELECT `avatar`, `gravatar`
            FROM `fcms_users`
            WHERE `id` = ?";

    $r = $fcmsDatabase->getRow($sql, $id);
    if ($r === false)
    {
        return getAvatarPath('no_avatar.jpg', NULL);
    }

    // No Avatar set
    if (count($r) <= 0)
    {
        return getAvatarPath('no_avatar.jpg', NULL);
    }

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
    if ($avatar === 'gravatar')
    {
        return 'http://www.gravatar.com/avatar.php?gravatar_id='.md5(strtolower($gravatar)).'&amp;s=80';
    }
    else if ($avatar === 'no_avatar.jpg')
    {
        return URL_PREFIX.'uploads/avatar/no_avatar.jpg';
    }

    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = new User($fcmsError, $fcmsDatabase);

    $destinationType = getDestinationType().'ProfileDestination';
    $destination     = new $destinationType($fcmsError, $fcmsUser);

    return $destination->getPhotoSource($avatar);
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `timezone` 
            FROM `fcms_user_settings` 
            WHERE `user` = '$user_id'";

    $r = $fcmsDatabase->getRow($sql, $user_id);
    if ($r === false)
    {
        return '-3 hours';
    }

    if (count($r) <= 0)
    {
        return '-3 hours';
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `value` AS 'registration'
            FROM `fcms_config`
            WHERE `name` = 'registration'
            LIMIT 1";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return false;
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT MAX(`order`) AS 'order'
            FROM `fcms_navigation`
            WHERE `col` = ?";

    $r = $fcmsDatabase->getRow($sql, $col);
    if ($r === false)
    {
        $fcmsError->setMessage('Navigation Order Missing or Corrupt');
        $fcmsError->displayError();
        die();
    }

    if (empty($r))
    {
        $fcmsError->setMessage('Navigation Order Missing or Corrupt');
        $fcmsError->displayError();
        die();
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT count(*) AS c 
            FROM `fcms_board_posts` 
            WHERE `thread` = ?";

    $row = $fcmsDatabase->getRow($sql, $thread_id);
    if ($row === false)
    {
        return 0;
    }

    return isset($row['c']) ? $row['c'] : 0;
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `value`
            FROM `fcms_config`
            WHERE `name` = 'running_job'
            AND `value` = '1'";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        // Lets be safe and say a job is being run
        return true;
    }

    if (empty($row))
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `name`
            FROM `fcms_config`
            WHERE `name` = 'running_job'";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        // error logged by db obj
        return false;
    }

    if (!empty($row))
    {
        $sql = "UPDATE `fcms_config`
                SET `value` = NOW()
                WHERE `name` = 'running_job'";

        if (!$fcmsDatabase->update($sql))
        {
            return false;
        }
    }
    // running_job config was missing, add it
    else
    {
        $date = date('Y-m-d H:I:S');

        $sql = "INSERT INTO `fcms_config` (`name`, `value`)
                VALUES ('running_job', ?)";

        if (!$fcmsDatabase->insert($sql, $date))
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "UPDATE `fcms_config`
            SET `value` = NULL
            WHERE `name` = 'running_job'";

    if (!$fcmsDatabase->update($sql))
    {
        return false;
    }

    if ($fcmsDatabase->getRowCount() <= 0)
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $ids = array();

    $sql = "SELECT `source_id`
            FROM `fcms_video`
            WHERE `source` = 'youtube'";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return $ids;
    }

    foreach ($rows as $row)
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $ids = array();

    $sql = "SELECT `source_id`
            FROM `fcms_gallery_external_photo`";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return $ids;
    }

    foreach ($rows as $row)
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `value` 
            FROM `fcms_config`
            WHERE `name` = 'country'";

    $r = $fcmsDatabase->getRow($sql);
    if ($r === false)
    {
        return 'US';
    }

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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $members = array();

    $sql = "SELECT `id`, `fname`, `lname`
            FROM `fcms_users`
            WHERE `activated` != 1
            AND `phpass` != 'NONMEMBER'
            ORDER BY `joindate` DESC";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return false;
    }

    foreach ($rows as $r)
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `value` 
            FROM `fcms_config`
            WHERE `name` = 'start_week'";

    $row = $fcmsDatabase->getRow($sql);
    if ($row === false)
    {
        return '0';
    }

    if (count($row) <= 0)
    {
        return '0';
    }

    return (int)$row['value'];
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
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $userId = (int)$userId;

    $sql = "SELECT `id`
            FROM `fcms_category`
            WHERE `name` = 'Instagram'
            AND `user` = ?";

    $r = $fcmsDatabase->getRow($sql, $userId);
    if ($r === false)
    {
        // TODO
        return;
    }

    if (empty($r))
    {
        // Create a new one
        $sql = "INSERT INTO `fcms_category`
                    (`name`, `type`, `user`, `date`)
                VALUES
                    ('Instagram', 'gallery', ?, NOW())";

        $id = $fcmsDatabase->insert($sql, $userId);
        if ($id === false)
        {
            // TODO
            return;
        }

        return $id;
    }

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

/**
 * formatYMD 
 * 
 * @param string $year 
 * @param string $month 
 * @param string $day 
 * 
 * @return array
 */
function formatYMD ($year, $month, $day)
{
    $retYear  = '';
    $retMonth = '';
    $retDay   = '';

    if (!empty($year))
    {
        $retYear = (int)$year;
    }
    if (!empty($month))
    {
        $retMonth = (int)$month;
        $retMonth = str_pad($retMonth, 2, "0", STR_PAD_LEFT);
    }
    if (!empty($day))
    {
        $retDay = (int)$day;
        $retDay = str_pad($retDay, 2, "0", STR_PAD_LEFT);
    }

    return array($retYear, $retMonth, $retDay);
}

/**
 * highlight 
 * 
 * Will wrap <b> tags around the needle.
 * Does not alter capitilization of needle.
 * 
 * @param string $needle 
 * @param string $haystack 
 * 
 * @return string
 */
function highlight ($needle, $haystack)
{
    $highlighted = $haystack;

    $ind = stripos($haystack, $needle); 
    $len = strlen($needle); 

    if ($ind !== false)
    { 
        $highlighted  = substr($haystack, 0, $ind);
        $highlighted .= '<b>' . substr($haystack, $ind, $len) . '</b>';
        $highlighted .= highlight($needle, substr($haystack, $ind + $len));

    }

    return $highlighted;
} 

/**
 * getTimezoneList 
 * 
 * @return void
 */
function getTimezoneList ()
{
    return array(
        '-12 hours'             => T_('(GMT -12:00) Eniwetok, Kwajalein'),
        '-11 hours'             => T_('(GMT -11:00) Midway Island, Samoa'),
        '-10 hours'             => T_('(GMT -10:00) Hawaii'),
        '-9 hours'              => T_('(GMT -9:00) Alaska'),
        '-8 hours'              => T_('(GMT -8:00) Pacific Time (US & Canada)'),
        '-7 hours'              => T_('(GMT -7:00) Mountain Time (US & Canada)'),
        '-6 hours'              => T_('(GMT -6:00) Central Time (US & Canada), Mexico City'),
        '-5 hours'              => T_('(GMT -5:00) Eastern Time (US & Canada), Bogota, Lima'),
        '-4 hours'              => T_('(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz'),
        '-3 hours -30 minutes'  => T_('(GMT -3:30) Newfoundland'),
        '-3 hours'              => T_('(GMT -3:00) Brazil, Buenos Aires, Georgetown'),
        '-2 hours'              => T_('(GMT -2:00) Mid-Atlantic'),
        '-1 hours'              => T_('(GMT -1:00) Azores, Cape Verde Islands'),
        '-0 hours'              => T_('(GMT) Western Europe Time, London, Lisbon, Casablanca'),
        '+1 hours'              => T_('(GMT +1:00) Brussels, Copenhagen, Madrid, Paris'),
        '+2 hours'              => T_('(GMT +2:00) Kaliningrad, South Africa'),
        '+3 hours'              => T_('(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburgh'),
        '+3 hours +30 minutes'   => T_('(GMT +3:30) Tehran'),
        '+4 hours'              => T_('(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi'),
        '+4 hours +30 minutes'   => T_('(GMT +4:30) Kabul'),
        '+5 hours'              => T_('(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent'),
        '+5 hours +30 minutes'   => T_('(GMT +5:30) Bombay, Calcutta, Madras, New Delhi'),
        '+6 hours'              => T_('(GMT +6:00) Almaty, Dhaka, Colombo'),
        '+7 hours'              => T_('(GMT +7:00) Bangkok, Hanoi, Jakarta'),
        '+8 hours'              => T_('(GMT +8:00) Beijing, Perth, Singapore, Hong Kong'),
        '+9 hours'              => T_('(GMT +9:00) Tokyo, Seoul, Osaka, Spporo, Yakutsk'),
        '+9 hours +30 minutes'   => T_('(GMT +9:30) Adeliaide, Darwin'),
        '+10 hours'             => T_('(GMT +10:00) Eastern Australia, Guam, Vladivostok'),
        '+11 hours'             => T_('(GMT +11:00) Magadan, Solomon Islands, New Caledonia'),
        '+12 hours'             => T_('(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka')
    );
}

/**
 * getActiveMemberIdNameLookup 
 * 
 * Returns an array of id to name lookup for all
 * active members.
 * 
 * @return array
 */
function getActiveMemberIdNameLookup ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `id`, `fname`, `lname`
            FROM `fcms_users` 
            WHERE `activated` > 0";

    $rows = $fcmsDatabase->getRows($sql);
    if ($rows === false)
    {
        return false;
    }

    $members = array();

    foreach ($rows as $r)
    {
        $members[$r['id']] = cleanOutput($r['fname']).' '.cleanOutput($r['lname']);
    }

    asort($members);

    return $members;
}

/**
 * displayErrors 
 * 
 * @param array $errors 
 * 
 * @return void
 */
function displayErrors ($errors)
{
    echo '
            <div class="error-alert">
                <h2>'.T_('Oops, there was an error:').'</h2>';

    foreach ($errors as $error)
    {
        echo '<p>'.$error.'</p>';
    }

    echo '
            </div>';
}

/**
 * usingFullSizePhotos 
 * 
 * @return boolean
 */
function usingFullSizePhotos ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $sql = "SELECT `value` AS 'full_size_photos'
            FROM `fcms_config`
            WHERE `name` = 'full_size_photos'";

    $r = $fcmsDatabase->getRow($sql);
    if (empty($r))
    {
        return false;
    }

    if ($r['full_size_photos'] == 1)
    {
        return true;
    }

    return false;
}

/**
 * displayPageHeader 
 * 
 * @param array $params  params are passed to loadTemplate()
 * @param array $options can be one of the following:
 *                        js       - js functions, global vars
 *                        jsOnload - js that must be run onload
 *                        modules  - an array of modules to load
 * 
 * @return void
 */
function displayPageHeader ($params, $options = null)
{
    $js       = isset($options['js'])       ? $options['js']       : '';
    $jsOnload = isset($options['jsOnload']) ? $options['jsOnload'] : '';

    $params['javascript'] = '';

    // Load any js modules
    if (isset($options['modules']))
    {
        $list = getModuleList();
        foreach ($options['modules'] as $module)
        {
            $params['javascript'] .= $list[$module]."\n";
        }
    }

    // Set page specific javascript
    $params['javascript'] .= $js;

    // Set onload javascript
    $params['javascript'] .= '
    <script type="text/javascript">
    $(document).ready(function() {
        initChatBar("'.T_('Chat').'", "'.$params['path'].'");
        '.$jsOnload.'
    });
    </script>';

    // Display the theme header
    loadTemplate('global', 'header', $params);
}

/**
 * getModuleList 
 * 
 * @return array
 */
function getModuleList ()
{
    return array(
        'livevalidation'    => '<script type="text/javascript" src="'.URL_PREFIX.'ui/js/livevalidation.js"></script>',
        'datechooser'       => '<link rel="stylesheet" type="text/css" href="ui/css/datechooser.css"/>'
                              .'<script type="text/javascript" src="'.URL_PREFIX.'ui/js/datechooser.js"></script>',
        'scriptaculous'     => '<script type="text/javascript" src="'.URL_PREFIX.'ui/js/scriptaculous.js"></script>',
        'autocomplete'      => '<script type="text/javascript" src="'.URL_PREFIX.'ui/js/autocomplete/jquery.autocomplete.min.js"></script>',
        'tablesorter'       => '<script type="text/javascript" src="'.URL_PREFIX.'ui/js/tablesorter/js/jquery.tablesorter.min.js"></script>',
    );
}

/**
 * loadTemplate 
 * 
 * Will load a single php template file with some variables.
 * 
 * @param string $subDirectory 
 * @param string $template 
 * @param array  $variables 
 * 
 * @return void
 */
function loadTemplate ($subDirectory, $template, $variables = array())
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    if (isset($fcmsUser->id))
    {
        $themePath = getTheme($fcmsUser->id);
    }
    else
    {
        $themePath = getTheme();
    }

    $TMPL = $variables;

    $subDirectory = basename($subDirectory);
    $template     = basename($template);
    $templateFile = $themePath.'templates/'.$subDirectory.'/'.$template.'.php';

    require_once($templateFile);
}

/**
 * startsWith 
 * 
 * @param string $haystack 
 * @param string $needle 
 * 
 * @return boolean
 */
function startsWith($haystack, $needle)
{
    return !strncmp($haystack, $needle, strlen($needle));
}

/**
 * getDestinationType
 * 
 * Returns the name of the destination type.
 * 
 * @return string
 */
function getDestinationType ()
{
    // Save outside the root (Protected)
    if (defined('UPLOADS'))
    {
        $destination = 'Protected';
    }
    // Save to Amazon S3
    elseif (defined('S3') && date('Ymd', S3) < date('Ymd'))
    {
        $destination = 'S3';
    }
    // Save in uploads/photos/*
    else
    {
        $destination = '';
    }

    return $destination;
}

/**
 * getPhotoGallery
 * 
 * Returns the name of the appropriate photo gallery object.
 * 
 * @return string
 */
function getPhotoGallery ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    if (isset($_GET['advanced']))
    {
        $type = 'plupload';
    }
    // Get selected type (user clicked on type from menu)
    elseif (isset($_GET['type']))
    {
        $type = $_GET['type'];
    }
    // Use last upload type (user clicked on 'Upload Photos' button
    elseif (isset($_SESSION['fcms_uploader_type']))
    {
        $type = $_SESSION['fcms_uploader_type'];
    }
    else
    {
        $type = getUploaderType($fcmsUser->id);
    }

    if ($type == 'plupload')
    {
        $photoGallery = 'PluploadUploadPhotoGallery';
    }
    else if ($type == 'java')
    {
        $photoGallery = 'JavaUploadPhotoGallery';
    }
    else if ($type == 'instagram')
    {
        $photoGallery = 'InstagramUploadPhotoGallery';
    }
    else if ($type == 'picasa')
    {
        $photoGallery = 'PicasaUploadPhotoGallery';
    }
    else
    {
        $photoGallery = 'UploadPhotoGallery';
    }

    return $photoGallery;
}

/**
 * getProfileClassName
 * 
 * Returns the name of the appropriate profile class.
 * 
 * @return string
 */
function getProfileClassName ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    $type = getUploaderType($fcmsUser->id);

    if ($type == 'plupload')
    {
        $className = 'PluploadUploadProfile';
    }
    else if ($type == 'java')
    {
        $className = 'JavaUploadProfile';
    }
    else
    {
        $className = 'UploadProfile';
    }

    return $className;
}

/**
 * getFamilyTreeClassName 
 * 
 * Returns the name of the appropriate family tree
 * avatar upload class name.
 * 
 * @return string
 */
function getFamilyTreeClassName ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);
    $fcmsUser     = User::getInstance($fcmsError, $fcmsDatabase);

    $type = getUploaderType($fcmsUser->id);

    if ($type == 'plupload')
    {
        $className = 'PluploadUploadFamilyTree';
    }
    else if ($type == 'java')
    {
        $className = 'JavaUploadFamilyTree';
    }
    else
    {
        $className = 'UploadFamilyTree';
    }

    return $className;
}

/**
 * getMemoryLimitBytes 
 * 
 * Will get the current memory limit in bytes.
 * 
 * @return integer
 */
function getMemoryLimitBytes ()
{
    $memory = ini_get('memory_limit');
    $size   = substr($memory, -1);
    $memory = substr($memory, 0, -1);

    // KB
    if ($size == 'K')
    {
        $memory = ($memory * 1024);
    }
    // MB
    else
    {
        $memory = ($memory * 1024) * 1024;
    }

    return $memory;
}
