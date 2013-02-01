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
define('GALLERY_PREFIX', '../gallery/');

require URL_PREFIX.'fcms.php';

init('admin/');

$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;

        $this->fcmsTemplate = array(
            'sitename'      => getSiteName(),
            'nav-link'      => getAdminNavLinks(),
            'pagetitle'     => T_('Administration: Configuration'),
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $this->control();
    }

    /**
     * control 
     * 
     * The controlling structure for this script.
     * 
     * @return void
     */
    function control ()
    {
        if ($this->fcmsUser->access > 2)
        {
            $this->displayInvalidAccessLevel();
            return;
        }
        elseif (isset($_GET['view']))
        {
            $view = $_GET['view'];

            if ($view == 'general')
            {
                if (isset($_POST['submit-sitename']))
                {
                    $this->displayGeneralFormSubmit();
                }
                else
                {
                    $this->displayGeneralForm();
                }
            }
            elseif ($view == 'defaults')
            {
                if (isset($_POST['submit-defaults']))
                {
                    $this->displayDefaultsFormSubmit();
                }
                else
                {
                    $this->displayDefaultsForm();
                }
            }
            elseif ($view == 'plugins')
            {
                if (isset($_POST['submit-plugins']))
                {
                    $this->displayPluginsFormSubmit();
                }
                else
                {
                    $this->displayPluginsForm();
                }
            }
            elseif ($view == 'navigation')
            {
                if (isset($_POST['submit-navigation']))
                {
                    $this->displayNavigationFormSubmit();
                }
                else
                {
                    $this->displayNavigationForm();
                }
            }
            // TODO move out of here
            elseif ($view == 'gallery')
            {
                if (isset($_POST['submit-gallery']))
                {
                    $this->displayPhotoGalleryFormSubmit();
                }
                else
                {
                    $this->displayPhotoGalleryForm();
                }
            }
            else
            {
                $this->displayGeneralForm();
            }
        }
        elseif (isset($_POST['submit-ajax-navigation']))
        {
            $this->displayNavigationFormSubmit(true);
        }
        else
        {
            $this->displayGeneralForm();
        }
    }

    /**
     * displayHeader 
     * 
     * @return void
     */
    function displayHeader ()
    {
        $TMPL = $this->fcmsTemplate;

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
        $TMPL = $this->fcmsTemplate;

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
        $this->displayHeader();

        echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 1 (Admin).').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

        $this->displayFooter();
    }

    /**
     * displayGeneralForm 
     * 
     * @return void
     */
    function displayGeneralForm ()
    {
        $this->displayHeader();

        $sql = "SELECT `name`, `value`
                FROM `fcms_config`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $row = array();

        foreach ($rows as $r)
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
            <legend>'.T_('General Configuration').'</legend>
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

        $this->displayFooter();
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

            $sql = "UPDATE `fcms_config` 
                    SET `value` = ?
                    WHERE `name` = 'sitename'";

            if (!$this->fcmsDatabase->update($sql, $sitename))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        if (isset($_POST['contact']))
        {
            $contact = strip_tags($_POST['contact']);

            $sql = "UPDATE `fcms_config` 
                    SET `value` = ?
                    WHERE `name` = 'contact'";

            if (!$this->fcmsDatabase->update($sql, $contact))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        if (isset($_POST['activation']))
        {
            $sql = "UPDATE `fcms_config` 
                    SET `value` = ?
                    WHERE `name` = 'auto_activate'";

            if (!$this->fcmsDatabase->update($sql, $_POST['activation']))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        if (isset($_POST['registration']))
        {
            $sql = "UPDATE `fcms_config` 
                    SET `value` = ?
                    WHERE `name` = 'registration'";

            if (!$this->fcmsDatabase->update($sql, $_POST['registration']))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        if (isset($_POST['start_week']))
        {
            $sql = "UPDATE `fcms_config` 
                    SET `value` = ?
                    WHERE `name` = 'start_week'";

            if (!$this->fcmsDatabase->update($sql, $_POST['start_week']))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        if (isset($_POST['site_off']))
        {
            $val = $_POST['site_off'] == 'yes' ? '1' : '0';

            $sql = "UPDATE `fcms_config` 
                    SET `value` = ?
                    WHERE `name` = 'site_off'";

            if (!$this->fcmsDatabase->update($sql, $val))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
                return;
            }
        }

        if (isset($_POST['debug']))
        {
            $sql = "UPDATE `fcms_config` 
                    SET `value` = ?
                    WHERE `name` = 'debug'";

            if (!$this->fcmsDatabase->update($sql, $_POST['debug']))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
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
        $this->displayHeader();

        // Defaults Config
        $sql = "DESCRIBE `fcms_user_settings`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        foreach ($rows as $drow)
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

        foreach ($themes as $file)
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
        $tz_list    = getTimezoneList();
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

        $this->displayFooter();
    }

    /**
     * displayDefaultsFormSubmit 
     * 
     * @return void
     */
    function displayDefaultsFormSubmit ()
    {
        $theme = basename($_POST['theme']);

        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `theme` SET DEFAULT ?";

        if (!$this->fcmsDatabase->alter($sql, $theme))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `displayname` 
                SET DEFAULT ?";

        if (!$this->fcmsDatabase->alter($sql, $_POST['displayname']))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `frontpage` 
                SET DEFAULT ?";

        if (!$this->fcmsDatabase->alter($sql, $_POST['frontpage']))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `timezone` 
                SET DEFAULT ?";

        if (!$this->fcmsDatabase->alter($sql, $_POST['timezone']))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "ALTER TABLE `fcms_user_settings` ALTER `dst`
                SET DEFAULT ?";

        if (!$this->fcmsDatabase->alter($sql, $_POST['dst']))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `boardsort` 
                SET DEFAULT ?";

        if (!$this->fcmsDatabase->alter($sql, $_POST['boardsort']))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        // Update existing users
        if (isset($_POST['changeAll']))
        {
            $avatar = isset($upfile) ? $upfile : 'no_avatar.jpg';
            $theme  = basename($_POST['theme']);

            $sql = "UPDATE `fcms_user_settings` 
                    SET `theme`       = ?,
                        `displayname` = ?,
                        `frontpage`   = ?,
                        `timezone`    = ?,
                        `dst`         = ?,
                        `boardsort`   = ?";

            $params = array(
                $theme,
                $_POST['displayname'],
                $_POST['frontpage'], 
                $_POST['timezone'],
                $_POST['dst'],
                $_POST['boardsort']
            );

            if (!$this->fcmsDatabase->update($sql, $params))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
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
        $this->displayHeader();

        // Get Plugin Data
        $plugins = array();

        $sql = "SELECT `id`, `link`, `col`, `order`, `req`
                FROM `fcms_navigation` 
                WHERE `col` = 3 
                OR `col` = 4
                ORDER BY `order`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        foreach ($rows as $r)
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

        $this->displayFooter();
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

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        foreach ($rows as $r)
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

            if (!$this->fcmsDatabase->update($sql))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
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
                    SET `order` = ?
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->update($sql, array($order, $id)))
            {
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
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
        $this->displayHeader();

        // Get Plugin Data
        $communicateNav = array();
        $shareNav       = array();

        $sql = "SELECT `id`, `link`, `col`, `order`, `req`
                FROM `fcms_navigation` 
                WHERE `col` = 3 
                OR `col` = 4
                AND `order` > 0
                ORDER BY `order`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        foreach ($rows as $r)
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
                                <span class="order">'.$this->getOrderSelectBox('com', $r['id'], $communicateTotal, $r['order'], $i).'</span>
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
                                <span class="order">'.$this->getOrderSelectBox('share', $r['id'], $shareTotal, $r['order'], $i).'</span>
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

        $this->displayFooter();
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
                    $this->displayHeader();

                    echo '
                    <div class="alert-message block-message error">
                        <p><b>'.T_('Can\'t have two items with the same order.').'</b></p>
                        <div class="alert-actions">
                            <a class="btn" href="config.php?view=navigation">'.T_('Please try again').'</a>
                        </div>
                    </div>';

                    $this->displayFooter();
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
                    $this->displayHeader();
                    echo '
                    <div class="alert-message block-message error">
                        <p><b>'.T_('Can\'t have two items with the same order.').'</b></p>
                        <div class="alert-actions">
                            <a class="btn" href="config.php?view=navigation">'.T_('Please try again').'</a>
                        </div>
                    </div>';
                    $this->displayFooter();
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
                    SET `order` = ?
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->update($sql, array($order, $id)))
            {
                if ($ajax)
                {
                    echo 'error';
                    exit();
                }
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
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
                    SET `order` = ?
                    WHERE `id` = ?";

            if (!$this->fcmsDatabase->update($sql, array($order, $id)))
            {
                if ($ajax)
                {
                    echo 'error';
                    exit();
                }
                $this->displayHeader();
                $this->fcmsError->displayError();
                $this->displayFooter();
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
        $this->displayHeader();

        $sql = "SELECT `name`, `value`
                FROM `fcms_config`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        $row = array();

        foreach ($rows as $r)
        {
            $row[$r['name']] = $r['value'];
        }
        
        $full_size_list = array(
            '0' => T_('Off (2 photos)'),
            '1' => T_('On (3 photos)')
        );

        $full_size_options = buildHtmlSelectOptions($full_size_list, $row['full_size_photos']);

        if (defined('UPLOADS'))
        {
            $protected = '<span class="label success">'.T_('Protected').'</span>';
        }
        else
        {
            $protected  = '<span class="label warning">'.T_('Un-protected').'</span><br/><br/>';
            $protected .= '<p><b>'.T_('Your photos can be viewed from non-authorized users.').'</b></p>';
            $protected .= '<p>'.T_('In order to protect your photos so only logged in users can view them, please refer to the help document below.').'</p>';
            $protected .= '<p><a href="'.URL_PREFIX.'help.php?topic=admin#adm-protect-photos">'.T_('Help Me Protect My Photos').'</a></p>';
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
        <form action="config.php?view=gallery" method="post">
        <fieldset>
            <legend>'.T_('Photo Gallery').'</legend>
            '.$message.'
            <div class="clearfix">
                <label for="full_size_photos">'.T_('Full Size Photos').'</label>
                <div class="input">
                    <select name="full_size_photos">
                        '.$full_size_options.'
                    </select><br/><br/>
                    <span class="help-block">
                        '.T_('By default, Full Sized Photos is turned off to save on storage space and bandwidth.  Turning this feature on can eat up significant space and bandwith.').'
                    </span>
                </div>
            </div>
            <div class="clearfix">
                <label for="protected">'.T_('Protected Photos').'</label>
                <div class="input">
                    '.$protected.'
                </div>
            </div>
            <div class="actions"><input type="submit" class="btn primary" id="submit-gallery" name="submit-gallery" value="'.T_('Save').'"/></div>
            </div>
        </fieldset>
        </form>';

        $this->displayFooter();
    }

    /**
     * displayPhotoGalleryFormSubmit 
     * 
     * @return void
     */
    function displayPhotoGalleryFormSubmit ()
    {
        $sql = "UPDATE `fcms_config` 
                SET `value` = ?
                WHERE `name` = 'full_size_photos'";

        if (!$this->fcmsDatabase->update($sql, $_POST['full_size_photos']))
        {
            $this->displayHeader();
            $this->fcmsError->displayError();
            $this->displayFooter();
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
}
