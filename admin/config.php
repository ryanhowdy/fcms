<?php
/**
 * Configuration
 * 
 * PHP versions 4 and 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2010 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

init('admin/');

$currentUserId = (int)$_SESSION['login_id'];

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getAdminNavLinks(),
    'pagetitle'     => T_('Administration: Configuration'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

control();
exit();

/**
 * control 
 * 
 * The controlling structure for this script.
 * 
 * @return void
 */
function control ()
{
    global $currentUserId;

    if (checkAccess($currentUserId) > 2)
    {
        displayInvalidAccessLevel();
        return;
    }
    elseif (isset($_GET['view']))
    {
        $view = $_GET['view'];

        if ($view == 'general')
        {
            if (isset($_POST['submit-sitename']))
            {
                displayGeneralFormSubmit();
            }
            else
            {
                displayGeneralForm();
            }
        }
        elseif ($view == 'defaults')
        {
            if (isset($_POST['submit-defaults']))
            {
                displayDefaultsFormSubmit();
            }
            else
            {
                displayDefaultsForm();
            }
        }
        elseif ($view == 'plugins')
        {
            if (isset($_POST['submit-plugins']))
            {
                displayPluginsFormSubmit();
            }
            else
            {
                displayPluginsForm();
            }
        }
        elseif ($view == 'navigation')
        {
            if (isset($_POST['submit-navigation']))
            {
                displayNavigationFormSubmit();
            }
            else
            {
                displayNavigationForm();
            }
        }
        // TODO move out of here
        elseif ($view == 'gallery')
        {
            if (isset($_POST['submit-gallery']))
            {
                displayPhotoGalleryFormSubmit();
            }
            else
            {
                displayPhotoGalleryForm();
            }
        }
        else
        {
            displayGeneralForm();
        }
    }
    elseif (isset($_POST['submit-ajax-navigation']))
    {
        displayNavigationFormSubmit(true);
    }
    else
    {
        displayGeneralForm();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = '
<script src="'.URL_PREFIX.'ui/js/prototype.js" type="text/javascript"></script>
<script src="'.URL_PREFIX.'ui/js/scriptaculous.js" type="text/javascript"></script>
<script src="'.URL_PREFIX.'ui/js/admin.js" type="text/javascript"></script>
<script src="'.URL_PREFIX.'ui/js/livevalidation.js" type="text/javascript"></script>';

    include_once URL_PREFIX.'ui/admin/header.php';

    $general    = '';
    $defaults   = '';
    $plugins    = '';
    $navigation = '';
    $gallery    = '';

    if (!isset($_GET['view']))
    {
        $general = 'active';
    }
    else
    {
        switch ($_GET['view'])
        {
            case 'general':
            default:
                $general = 'active';
                break;
            case 'defaults':
                $defaults = 'active';
                break;
            case 'plugins':
                $plugins = 'active';
                break;
            case 'navigation':
                $navigation = 'active';
                break;
            case 'gallery':
                $gallery = 'active';
                break;
        }
    }

    echo '
        <div id="config" style="position:relative;">

            <ul class="tabs">
                <li class="'.$general.'"><a href="?view=general">'.T_('General').'</a></li>
                <li class="'.$defaults.'"><a href="?view=defaults">'.T_('Defaults').'</a></li>
                <li class="'.$plugins.'"><a href="?view=plugins">'.T_('Plugins').'</a></li>
                <li class="'.$navigation.'"><a href="?view=navigation">'.T_('Navigation').'</a></li>
                <li class="'.$gallery.'"><a href="?view=gallery">'.T_('Photo Gallery').'</a></li>
            </ul>';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $currentUserId, $TMPL;

    echo '
        </div><!--/config-->';

    include_once URL_PREFIX.'ui/admin/footer.php';
}

/**
 * displayInvalidAccessLevel 
 * 
 * @return void
 */
function displayInvalidAccessLevel ()
{
    displayHeader();

    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 1 (Admin).').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

    displayFooter();
}

/**
 * displayGeneralForm 
 * 
 * @return void
 */
function displayGeneralForm ()
{
    displayHeader();

    $sql = "SELECT `name`, `value`
            FROM `fcms_config`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $row = array();

    while ($r = mysql_fetch_assoc($result))
    {
        $row[$r['name']] = $r['value'];
    }
    
    // Activate Options
    $activateList = array (
        '0' => T_('Admin Activation'),
        '1' => T_('Auto Activation')
    );

    $activateOptions = buildHtmlSelectOptions($activateList, $row['auto_activate']);
    
    // Register Options
    $registerList = array (
        '0' => T_('Off'),
        '1' => T_('On')
    );

    $registerOptions = buildHtmlSelectOptions($registerList, $row['registration']);

    // Start of week
    $startSun = ($row['start_week'] == 0) ? 'checked' : '';;
    $startMon = ($row['start_week'] == 1) ? 'checked' : '';
    $startTue = ($row['start_week'] == 2) ? 'checked' : '';
    $startWed = ($row['start_week'] == 3) ? 'checked' : '';
    $startThr = ($row['start_week'] == 4) ? 'checked' : '';
    $startFri = ($row['start_week'] == 5) ? 'checked' : '';
    $startSat = ($row['start_week'] == 6) ? 'checked' : '';

    // Site Off Options
    // TODO - config table or file?
    $siteOffYes = '';
    $siteOffNo  = '';
    if ($row['site_off'] == 1)
    {
        $siteOffYes = 'checked';
    }
    else
    {
        $siteOffNo = 'checked';
    }

    // Debug
    $debugList = array(
        '0' => T_('Off'),
        '1' => T_('On')
    );

    $debugOptions = buildHtmlSelectOptions($debugList, $row['debug']);

    $message = '';

    if (isset($_SESSION['success']))
    {
        $message  = '<div class="alert-message success">';
        $message .= '<a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>';
        $message .= T_('Changes Updated Successfully').'</div>';

        unset($_SESSION['success']);
    }

    echo '
        <form action="config.php?view=general" method="post">
        <fieldset>
            <legend>'.T_('Website Information').'</legend>
            '.$message.'
            <div class="clearfix">
                <label for="sitename">'.T_('Site Name').'</label>
                <div class="input">
                    <input class="xlarge span8" id="sitename" name="sitename" type="text" value="'.cleanOutput($row['sitename']).'">
                </div>
            </div>
            <div class="clearfix">
                <label for="contact">'.T_('Contact Email').'</label>
                <div class="input">
                    <input class="xlarge span8" id="contact" name="contact" type="text" value="'.cleanOutput($row['contact']).'">
                </div>
                <script type="text/javascript">
                    var email = new LiveValidation(\'contact\', {onlyOnSubmit: true});
                    email.add(Validate.Email, {failureMessage: "'.T_('That\'s not a valid email address is it?').'"});
                    email.add(Validate.Length, {minimum: 10});
                </script>
            </div>
            <div class="clearfix">
                <label for="registration">'.T_('Registration').'</label>
                <div class="input">
                    <select id="registration" name="registration">
                        '.$registerOptions.'
                    </select>
                </div>
            </div>
            <div class="clearfix">
                <label for="activation">'.T_('Account Activation').'</label>
                <div class="input">
                    <select id="activation" name="activation">
                        '.$activateOptions.'
                    </select>
                </div>
            </div>
            <div class="clearfix">
                <label>'.T_('Start of the Week').'</label>
                <div class="input">
                    <ul class="inputs-list">
                        <li>
                            <label>
                                <input type="radio" '.$startSun.' id="start_sun" name="start_week" value="0">
                                <span>'.T_('Sunday').'</span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" '.$startMon.' id="start_mon" name="start_week" value="1">
                                <span>'.T_('Monday').'</span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" '.$startTue.' id="start_tue" name="start_week" value="2">
                                <span>'.T_('Tuesday').'</span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" '.$startWed.' id="start_wed" name="start_week" value="3">
                                <span>'.T_('Wednesday').'</span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" '.$startThr.' id="start_thr" name="start_week" value="4">
                                <span>'.T_('Thursday').'</span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" '.$startFri.' id="start_fri" name="start_week" value="5">
                                <span>'.T_('Friday').'</span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" '.$startSat.' id="start_sat" name="start_week" value="6">
                                <span>'.T_('Saturday').'</span>
                            </label>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="clearfix">
                <label>'.T_('Turn Off Site?').'</label>
                <div class="input">
                    <ul class="inputs-list">
                        <li>
                            <label>
                                <input type="radio" '.$siteOffYes.' id="site_off_yes" name="site_off" value="yes">
                                <span>'.T_('Yes, turn the site off, so no one can access it.').'</span>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" '.$siteOffNo.' id="site_off_no" name="site_off" value="no">
                                <span>'.T_('No, keep the site on and running.').'</span>
                            </label>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="clearfix">
                <label for="debug">'.T_('Debug').'</label>
                <div class="input">
                    <select id="debug" name="debug">
                        '.$debugOptions.'
                    </select>
                </div>
            </div>
            <div class="actions">
                <input type="submit" class="btn primary" id="submit-sitename" name="submit-sitename" value="'.T_('Save').'">
            </div>
        </fieldset>
        </form>';

    displayFooter();
}

/**
 * displayGeneralFormSubmit 
 * 
 * @return void
 */
function displayGeneralFormSubmit ()
{
    if (isset($_POST['sitename']))
    {
        $sitename = strip_tags($_POST['sitename']);
        $sitename = escape_string($sitename);

        $sql = "UPDATE `fcms_config` 
                SET `value` = '$sitename'
                WHERE `name` = 'sitename'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    if (isset($_POST['contact']))
    {
        $contact = strip_tags($_POST['contact']);
        $contact = escape_string($contact);

        $sql = "UPDATE `fcms_config` 
                SET `value` = '$contact'
                WHERE `name` = 'contact'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    if (isset($_POST['activation']))
    {
        $sql = "UPDATE `fcms_config` 
                SET `value` = '".escape_string($_POST['activation'])."'
                WHERE `name` = 'auto_activate'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    if (isset($_POST['registration']))
    {
        $sql = "UPDATE `fcms_config` 
                SET `value` = '".escape_string($_POST['registration'])."'
                WHERE `name` = 'registration'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    if (isset($_POST['start_week']))
    {
        $sql = "UPDATE `fcms_config` 
                SET `value` = '".(int)$_POST['start_week']."'
                WHERE `name` = 'start_week'";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    if (isset($_POST['site_off']))
    {
        $val = $_POST['site_off'] == 'yes' ? '1' : '0';

        $sql = "UPDATE `fcms_config` 
                SET `value` = '".($_POST['site_off'] == 'yes' ? '1' : '0')."'
                WHERE `name` = 'site_off'";

        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    if (isset($_POST['debug']))
    {
        $sql = "UPDATE `fcms_config` 
                SET `value` = '".escape_string($_POST['debug'])."'
                WHERE `name` = 'debug'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    $_SESSION['success'] = 1;

    header("Location: config.php?view=general");
}

/**
 * displayDefaultsForm
 * 
 * @return void
 */
function displayDefaultsForm ()
{
    displayHeader();

    // Defaults Config
    $sql = "DESCRIBE `fcms_user_settings`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    while ($drow = mysql_fetch_assoc($result))
    {
        if ($drow['Field'] == 'theme')
        {
            $default_theme = $drow['Default'];
        }
        if ($drow['Field'] == 'displayname')
        {
            $default_displayname = $drow['Default'];
        }
        if ($drow['Field'] == 'frontpage')
        {
            $default_frontpage = $drow['Default'];
        }
        if ($drow['Field'] == 'timezone')
        {
            $default_tz = $drow['Default'];
        }
        if ($drow['Field'] == 'dst')
        {
            $default_dst = $drow['Default'];
        }
        if ($drow['Field'] == 'boardsort')
        {
            $default_boardsort = $drow['Default'];
        }
    }

    // Themes
    $themes        = getThemeList();
    $theme_options = '';

    foreach($themes as $file)
    {
        $theme_options .= '<option value="'.$file.'"';

        if ($default_theme == $file)
        {
            $theme_options .= ' selected="selected"';
        }

        $theme_options .= ">$file</option>";
    }

    // Display Name
    $displayname_list = array(
        "1" => T_('First Name'),
        "2" => T_('First & Last Name'),
        "3" => T_('Username')
    );

    $displayname_options = buildHtmlSelectOptions($displayname_list, $default_displayname);

    // Frontpage
    $frontpage_list = array(
        "1" => T_('All (by date)'),
        "2" => T_('Last 5 (by plugin)')
    );

    $frontpage_options = buildHtmlSelectOptions($frontpage_list, $default_frontpage);

    // Timezone
    $tz_list = array(
        "-12 hours"            => T_('(GMT -12:00) Eniwetok, Kwajalein'),
        "-11 hours"            => T_('(GMT -11:00) Midway Island, Samoa'),
        "-10 hours"            => T_('(GMT -10:00) Hawaii'),
        "-9 hours"             => T_('(GMT -9:00) Alaska'),
        "-8 hours"             => T_('(GMT -8:00) Pacific Time (US & Canada)'),
        "-7 hours"             => T_('(GMT -7:00) Mountain Time (US & Canada)'),
        "-6 hours"             => T_('(GMT -6:00) Central Time (US & Canada), Mexico City'),
        "-5 hours"             => T_('(GMT -5:00) Eastern Time (US & Canada), Bogota, Lima'),
        "-4 hours"             => T_('(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz'),
        "-3 hours -30 minutes" => T_('(GMT -3:30) Newfoundland'),
        "-3 hours"             => T_('(GMT -3:00) Brazil, Buenos Aires, Georgetown'),
        "-2 hours"             => T_('(GMT -2:00) Mid-Atlantic'),
        "-1 hours"             => T_('(GMT -1:00) Azores, Cape Verde Islands'),
        "-0 hours"             => T_('(GMT) Western Europe Time, London, Lisbon, Casablanca'),
        "+1 hours"             => T_('(GMT +1:00) Brussels, Copenhagen, Madrid, Paris'),
        "+2 hours"             => T_('(GMT +2:00) Kaliningrad, South Africa'),
        "+3 hours"             => T_('(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburgh'),
        "+3 hours 30 minutes"  => T_('(GMT +3:30) Tehran'),
        "+4 hours"             => T_('(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi'),
        "+4 hours 30 minutes"  => T_('(GMT +4:30) Kabul'),
        "+5 hours"             => T_('(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent'),
        "+5 hours 30 minutes"  => T_('(GMT +5:30) Bombay, Calcutta, Madras, New Delhi'),
        "+6 hours"             => T_('(GMT +6:00) Almaty, Dhaka, Colombo'),
        "+7 hours"             => T_('(GMT +7:00) Bangkok, Hanoi, Jakarta'),
        "+8 hours"             => T_('(GMT +8:00) Beijing, Perth, Singapore, Hong Kong'),
        "+9 hours"             => T_('(GMT +9:00) Tokyo, Seoul, Osaka, Spporo, Yakutsk'),
        "+9 hours 30 minutes"  => T_('(GMT +9:30) Adeliaide, Darwin'),
        "+10 hours"            => T_('(GMT +10:00) Eastern Australia, Guam, Vladivostok'),
        "+11 hours"            => T_('(GMT +11:00) Magadan, Solomon Islands, New Caledonia'),
        "+12 hours"            => T_('(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka')
    );

    $tz_options = buildHtmlSelectOptions($tz_list, $default_tz);

    // DST
    $dst_list = array(
        1 => T_('On'),
        0 => T_('Off')
    );
    $dst_options = buildHtmlSelectOptions($dst_list, $default_dst);

    // Board Sort
    $boardsort_list = array(
        "ASC" => T_('New Messages at Bottom'),
        "DESC" => T_('New Messages at Top')
    );

    $boardsort_options = buildHtmlSelectOptions($boardsort_list, $default_boardsort);
    
    $message = '';

    if (isset($_SESSION['success']))
    {
        $message  = '<div class="alert-message success">';
        $message .= '<a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>';
        $message .= T_('Changes Updated Successfully').'</div>';

        unset($_SESSION['success']);
    }

    echo '
        <form action="config.php?view=defaults" method="post">
        '.$message.'
        <fieldset>
            <legend>'.T_('New Member Default Settings').'</legend>
            <div class="clearfix">
                <label for="theme">'.T_('Theme').'</label>
                <div class="input">
                    <select name="theme" id="theme">
                        '.$theme_options.'
                    </select>
                </div>
            </div>
            <div class="clearfix">
                <label for="displayname">'.T_('Display Name').'</label>
                <div class="input">
                    <select name="displayname" id="displayname" title="'.T_('How do you want your name to display?').'">
                        '.$displayname_options.'
                    </select>
                </div>
            </div>
            <div class="clearfix">
                <label for="frontpage">'.T_('Front Page').'</label>
                <div class="input">
                    <select name="frontpage" id="frontpage" title="'.T_('How do you want the latest information to display on the homepage?').'">
                        '.$frontpage_options.'
                    </select>
                </div>
            </div>
            <div class="clearfix">
                <label for="timezone">'.T_('Time Zone').'</label>
                <div class="input">
                    <select class="span8" name="timezone" id="timezone" title="'.T_('What time zone do you live in?').'">
                        '.$tz_options.'
                    </select>
                </div>
            </div>
            <div class="clearfix">
                <label for="dst">'.T_('Daylight Savings Time').'</label>
                <div class="input">
                    <select name="dst" id="dst">
                        '.$dst_options.'
                    </select>
                </div>
            </div>
            <div class="clearfix">
                <label for="boardsort">'.T_('Sort Messages').'</label>
                <div class="input">
                    <select name="boardsort" id="boardsort" title="'.T_('How do you want messages to display on the Message Board?').'">
                        '.$boardsort_options.'
                    </select>
                </div>
            </div>
            <div class="clearfix">
                <label>&nbsp;</label>
                <div class="input">
                    <ul class="inputs-list">
                        <li>
                            <label>
                                <input type="checkbox" name="changeAll" id="changeAll"/> 
                                <span>'.T_('Update existing users?').'</span>
                            </label>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="actions">
                <input type="submit" id="submit-defaults" name="submit-defaults" class="btn primary" value="'.T_('Save').'"/> &nbsp;
            </div>
        </fieldset>
        </form>';

    displayFooter();
}

/**
 * displayDefaultsFormSubmit 
 * 
 * @return void
 */
function displayDefaultsFormSubmit ()
{
    $theme = basename($_POST['theme']);
    $theme = escape_string($theme);

    $sql = "ALTER TABLE `fcms_user_settings` 
            ALTER `theme` SET DEFAULT '$theme'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "ALTER TABLE `fcms_user_settings` 
            ALTER `displayname` 
            SET DEFAULT '".escape_string($_POST['displayname'])."'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "ALTER TABLE `fcms_user_settings` 
            ALTER `frontpage` 
            SET DEFAULT '".escape_string($_POST['frontpage'])."'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "ALTER TABLE `fcms_user_settings` 
            ALTER `timezone` 
            SET DEFAULT '".escape_string($_POST['timezone'])."'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "ALTER TABLE `fcms_user_settings` ALTER `dst`
            SET DEFAULT '".escape_string($_POST['dst'])."'";

    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $sql = "ALTER TABLE `fcms_user_settings` 
            ALTER `boardsort` 
            SET DEFAULT '".escape_string($_POST['boardsort'])."'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    // Update existing users
    if (isset($_POST['changeAll']))
    {
        $avatar = isset($upfile) ? $upfile : 'no_avatar.jpg';
        $theme  = basename($_POST['theme']);
        $theme  = escape_string($theme);

        $sql = "UPDATE `fcms_user_settings` 
                SET `theme` = '$theme',
                    `displayname`  = '".escape_string($_POST['displayname'])."', 
                    `frontpage`    = '".escape_string($_POST['frontpage'])."', 
                    `timezone`     = '".escape_string($_POST['timezone'])."',
                    `dst`          = '".escape_string($_POST['dst'])."',
                    `boardsort`    = '".escape_string($_POST['boardsort'])."'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    $_SESSION['success'] = 1;

    header("Location: config.php?view=defaults");
}

/**
 * displayPluginsForm
 * 
 * @return void
 */
function displayPluginsForm ()
{
    displayHeader();

    // Get Plugin Data
    $plugins = array();

    $sql = "SELECT `id`, `link`, `col`, `order`, `req`
            FROM `fcms_navigation` 
            WHERE `col` = 3 
            OR `col` = 4
            ORDER BY `order`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    while ($r = mysql_fetch_assoc($result))
    {
        $plugins[getPluginName($r['link'])] = $r;
    }

    ksort($plugins);

    $message = '';

    if (isset($_SESSION['success']))
    {
        $message  = '<div class="alert-message success">';
        $message .= '<a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>';
        $message .= T_('Changes Updated Successfully').'</div>';

        unset($_SESSION['success']);
    }

    echo '
            <h2>'.T_('Plugins').'</h2>
            '.$message.'
            <form action="config.php?view=plugins" method="post">
                <table class="bordered-table zebra-striped">
                    <thead>
                        <tr><th class="check">'.T_('Enabled').'</th><th>'.T_('Name').'</th><th>'.T_('Description').'</th></tr>
                    </thead>
                    <tbody>';

    foreach ($plugins AS $name => $plugin)
    {
        $checked  = $plugin['order'] == 0 ? '' : ' checked="checked"';
        $disabled = $plugin['req']   == 0 ? '' : ' disabled="disabled"';

        echo '
                        <tr>
                            <td class="check"><input type="checkbox" id="'.$plugin['link'].'" name="'.$plugin['link'].'" '.$checked.$disabled.'/></td>
                            <td><b>'.$name.'</b></td>
                            <td>'.getPluginDescription($plugin['link']).'</td>
                        </tr>';
    }

    echo '
                    </tbody>
                </table>
                <p><input type="submit" class="btn primary" id="submit-plugins" name="submit-plugins" value="'.T_('Save').'"/></p>
            </form>';

    displayFooter();
}

/**
 * displayPluginsFormSubmit
 * 
 * @return void
 */
function displayPluginsFormSubmit ()
{
    $on  = array();
    $off = array();

    // Get Plugin Data
    $sql = "SELECT `id`, `link`, `col`, `order`, `req`
            FROM `fcms_navigation` 
            WHERE (
                `col` = 3 
                OR `col` = 4
            )
            AND `req` = 0
            ORDER BY `order`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    while ($r = mysql_fetch_assoc($result))
    {
        // Turn on
        if (isset($_POST[$r['link']]))
        {
            if ($r['order'] == 0)
            {
                $on[] = $r;
            }
        }
        // Turn off
        else
        {
            $off[] = $r['id'];
        }
    }

    // Turn off all that need turned off
    if (count($off) > 0)
    {
        $offIds = implode(',', $off);

        $sql = "UPDATE `fcms_navigation` 
                SET `order` = 0 
                WHERE `id` IN ($offIds)";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    // Turn on all that need turned on
    $communicateOrder = getNextNavigationOrder(3);
    $shareOrder       = getNextNavigationOrder(4);

    foreach ($on as $plugin)
    {
        if ($plugin['col'] == 3)
        {
            $order = $communicateOrder;
            $communicateOrder++;
        }
        elseif ($plugin['col'] == 4)
        {
            $order = $shareOrder;
            $shareOrder++;
        }

        $id = (int)$plugin['id'];

        $sql = "UPDATE `fcms_navigation` 
                SET `order` = '$order' 
                WHERE `id` = '$id'";
        if (!mysql_query($sql))
        {
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    $_SESSION['success'] = 1;

    header("Location: config.php?view=plugins");
}

/**
 * displayNavigationForm 
 * 
 * @return void
 */
function displayNavigationForm ()
{
    displayHeader();

    // Get Plugin Data
    $communicateNavj  = array();
    $shareNav = array();

    $sql = "SELECT `id`, `link`, `col`, `order`, `req`
            FROM `fcms_navigation` 
            WHERE `col` = 3 
            OR `col` = 4
            AND `order` > 0
            ORDER BY `order`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    while ($r = mysql_fetch_assoc($result))
    {
        if ($r['col'] == 3)
        {
            $communicateNav[] = $r;
        }
        else
        {
            $shareNav[] = $r;
        }
    }

    $message = '';

    if (isset($_SESSION['success']))
    {
        $message  = '<div class="alert-message success">';
        $message .= '<a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>';
        $message .= T_('Changes Updated Successfully').'</div>';

        unset($_SESSION['success']);
    }

    echo '
            <h2 id="navigation-heading">'.T_('Navigation').'</h2>
            '.$message.'
            <form action="config.php?view=navigation" method="post">

                <div class="row">

                    <div class="span8">
                        <h3>'.T_('Communicate').'</h3>
                        <ol id="com_order" class="unstyled">';

    $communicateTotal = count($communicateNav);

    $i = 1;
    foreach ($communicateNav as $r)
    {
        echo '
                            <li id="com_'.$r['id'].'">
                                <span class="order">'.getOrderSelectBox('com', $r['id'], $communicateTotal, $r['order'], $i).'</span>
                                <b>'.getPluginName($r['link']).'</b>
                            </li>';
        $i++;
    }

    echo '
                        </ol>
                    </div><!-- /span8 -->

                    <div class="span8">
                        <h3>'.T_('Share').'</h3>
                        <ol id="share_order" class="unstyled">';

    $shareTotal = count($shareNav);

    $i = 1;
    foreach ($shareNav as $r)
    {
        echo '
                            <li id="share_'.$r['id'].'">
                                <span class="order">'.getOrderSelectBox('share', $r['id'], $shareTotal, $r['order'], $i).'</span>
                                <b>'.getPluginName($r['link']).'</b>
                            </li>';
        $i++;
    }

    echo '
                        </ol>
                    </div><!-- /span8 -->

                </div><!-- /row -->

                <p><input type="submit" class="btn primary" id="submit-navigation" name="submit-navigation" value="'.T_('Save').'"/></p>
            </form>

            <script type="text/javascript">
            $("submit-navigation").hide();
            $$(".order").each(function(item) {
                item.hide();
                item.up("li").addClassName("sortable");
            });
            Sortable.create("com_order", {
                onUpdate: function() {
                    new Ajax.Request("config.php", {
                        method: "post",
                        onSuccess: function(response) {
                            if (response.responseText !== "success") {
                                insertAlertMessage("error", "navigation-heading", "'.T_('An error occurred. Changes could not be saved.').'");
                            } else {
                                insertAlertMessage("success inline-alert", "navigation-heading", "'.T_('Changes Saved').'");
                            }
                        },
                        onFailure: function(response) {
                            insertAlertMessage("error", "navigation-heading", "'.T_('An error occurred. Changes could not be saved.').'");
                        },
                        parameters: { 
                            "submit-ajax-navigation": "1",
                            data: Sortable.serialize("com_order")
                        }
                    });
                }
            });
            Sortable.create("share_order", {
                onUpdate: function() {
                    new Ajax.Request("config.php", {
                        method: "post",
                        onSuccess: function(response) {
                            if (response.responseText !== "success") {
                                insertAlertMessage("error", "navigation-heading", "'.T_('An error occurred. Changes could not be saved.').'");
                            } else {
                                insertAlertMessage("success inline-alert", "navigation-heading", "'.T_('Changes Saved').'");
                            }
                        },
                        onFailure: function(response) {
                            insertAlertMessage("error", "navigation-heading", "'.T_('An error occurred. Changes could not be saved.').'");
                        },
                        parameters: { 
                            "submit-ajax-navigation": "1",
                            data: Sortable.serialize("share_order")
                        }
                    });
                }
            });
            </script>';

    displayFooter();
}

/**
 * displayNavigationFormSubmit 
 * 
 * Handles the submit form for both ajax and regular form.
 * 
 * @param boolean $ajax 
 * 
 * @return void
 */
function displayNavigationFormSubmit ($ajax = false)
{
    $communicateOrder = array();
    $shareOrder       = array();

    // Fix the data (Ajax)
    if (isset($_POST['data']))
    {
        parse_str($_POST['data']);

        if (isset($share_order))
        {
            $shareOrder = $share_order;
        }

        if (isset($com_order))
        {
            $communicateOrder = $com_order;
        }
    }
    // Fix the data (Form)
    else
    {
        // Communciate
        $i = 1;
        while (isset($_POST['com-order_'.$i]))
        {
            $arr   = explode(':', $_POST['com-order_'.$i]);
            $id    = $arr[0];
            $order = $arr[1];

            if (isset($communicateOrder[$order]))
            {
                displayHeader();

                echo '
                    <div class="alert-message block-message error">
                        <p><b>'.T_('Can\'t have two items with the same order.').'</b></p>
                        <div class="alert-actions">
                            <a class="btn" href="config.php?view=navigation">'.T_('Please try again').'</a>
                        </div>
                    </div>';

                displayFooter();
                return;
            }
            $communicateOrder[$order] = $id;

            $i++;
        }

        // Share
        $i = 1;
        while (isset($_POST['share-order_'.$i]))
        {
            $arr   = explode(':', $_POST['share-order_'.$i]);
            $id    = $arr[0];
            $order = $arr[1];

            if (isset($shareOrder[$order]))
            {
                displayHeader();
                echo '
                    <div class="alert-message block-message error">
                        <p><b>'.T_('Can\'t have two items with the same order.').'</b></p>
                        <div class="alert-actions">
                            <a class="btn" href="config.php?view=navigation">'.T_('Please try again').'</a>
                        </div>
                    </div>';
                displayFooter();
                return;
            }
            $shareOrder[$order] = $id;

            $i++;
        }
    }

    // Update the order of Share column
    foreach ($shareOrder as $order => $id)
    {
        $id    = (int)$id;
        $order = (int)$order;

        if ($ajax)
        {
            $order++;
        }

        $sql = "UPDATE `fcms_navigation` 
                SET `order` = '$order' 
                WHERE `id` = '$id'";
        if (!mysql_query($sql))
        {
            if ($ajax)
            {
                echo 'error';
                exit();
            }
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    // Update the order of Communication column
    foreach ($communicateOrder as $order => $id)
    {
        $id    = (int)$id;
        $order = (int)$order;

        if ($ajax)
        {
            $order++;
        }

        $sql = "UPDATE `fcms_navigation` 
                SET `order` = '$order' 
                WHERE `id` = '$id'";
        if (!mysql_query($sql))
        {
            if ($ajax)
            {
                echo 'error';
                exit();
            }
            displayHeader();
            displaySqlError($sql, mysql_error());
            displayFooter();
            return;
        }
    }

    if ($ajax)
    {
        echo 'success';
        exit();
    }

    $_SESSION['success'] = 1;

    header("Location: config.php?view=navigation");
}

/**
 * displayPhotoGalleryForm 
 * 
 * @return void
 */
function displayPhotoGalleryForm ()
{
    displayHeader();

    $sql = "SELECT `name`, `value`
            FROM `fcms_config`";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }


    $row = array();
    while ($r = mysql_fetch_assoc($result))
    {
        $row[$r['name']] = $r['value'];
    }
    
    $full_size_list = array(
        "0" => T_('Off (2 photos)'),
        "1" => T_('On (3 photos)')
    );

    $full_size_options = buildHtmlSelectOptions($full_size_list, $row['full_size_photos']);
    
    $message = '';

    if (isset($_SESSION['success']))
    {
        $message  = '<div class="alert-message success">';
        $message .= '<a class="close" href="#" onclick="$(this).up(\'div\').hide(); return false;">&times;</a>';
        $message .= T_('Changes Updated Successfully').'</div>';

        unset($_SESSION['success']);
    }

    echo '
        <form action="config.php?view=gallery" method="post">
        <fieldset>
            <legend>'.T_('Photo Gallery').'</legend>
            '.$message.'
            <div class="alert-message block-message info">
                '.T_('By default, Full Sized Photos is turned off to save on storage space and bandwidth.  Turning this feature on can eat up significant space and bandwith.').'
            </div>
            <div class="clearfix">
                <label for="full_size_photos">'.T_('Full Size Photos').'</label>
                <div class="input">
                    <select name="full_size_photos">
                        '.$full_size_options.'
                    </select>
                </div>
            </div>
            <div class="actions"><input type="submit" class="btn primary" id="submit-gallery" name="submit-gallery" value="'.T_('Save').'"/></div>
            </div>
        </fieldset>
        </form>';

    displayFooter();
}

/**
 * displayPhotoGalleryFormSubmit 
 * 
 * @return void
 */
function displayPhotoGalleryFormSubmit ()
{
    $sql = "UPDATE `fcms_config` 
            SET `value` = '".escape_string($_POST['full_size_photos'])."'
            WHERE `name` = 'full_size_photos'";
    if (!mysql_query($sql))
    {
        displayHeader();
        displaySqlError($sql, mysql_error());
        displayFooter();
        return;
    }

    $_SESSION['success'] = 1;

    header("Location: config.php?view=gallery");
}

/**
 * getOrderSelectBox 
 * 
 * @param int $name     The name of the select box (comm|share)
 * @param int $id       The order number of the spot we are talking about
 * @param int $total    The total number of options for the select box
 * @param int $selected Which order is currently selected
 * @param int $number   The number of select box on screen.
 * 
 * @return void
 */
function getOrderSelectBox ($name, $id, $total, $selected, $number)
{
    $order_options = '<select class="span1" name="'.$name.'-order_'.$number.'">';

    for ($i = 1; $i <= $total; $i++)
    {
        $order_options .= '<option value="'.$id.':'.$i.'"';

        if ($i == $selected)
        {
            $order_options .= ' selected="selected"';
        }

        $order_options .= '>'.$i.'</option>';
    }

    $order_options .= '</select>';

    return $order_options;
}
