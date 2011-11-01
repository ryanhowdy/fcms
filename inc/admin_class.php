<?php
/**
 * Admin
 * 
 * PHP versions 4 and 5
 *
 * @category  FCMS
 * @package   Family_Connections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
require_once 'database_class.php';
require_once 'utils.php';

/**
 * Admin
 * 
 * @category  FCMS
 * @package   Family_Connections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
class Admin
{
    var $db;
    var $db2;
    var $db3;
    var $tzOffset;
    var $lastmonth_beg;
    var $lastmonth_end;
    var $currentUserId;

    /**
     * Admin 
     * 
     * @param int $currentUserId The current user's id
     *
     * @return void
     */
    function Admin ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db3 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
        $this->lastmonth_beg = date('Y-m', mktime(0, 0, 0, date('m')-1, 1, date('Y')))."-01 00:00:00";
        $this->lastmonth_end = date('Y-m', mktime(0, 0, 0, date('m')-1, 1, date('Y')))."-31 23:59:59";

        T_bindtextdomain('messages', '.././language');
    }

    /**
     * displayEditPollForm 
     * 
     * @param int $pollid The id of the poll
     * 
     * @return void
     */
    function displayEditPollForm ($pollid = 0)
    {
        $poll_exists = true;

        if ($pollid > 0)
        {
            $sql = "SELECT `question`, o.`id`, `option` 
                    FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
                    WHERE p.`id` = o.`poll_id` 
                    AND p.`id` = '".cleanInput($pollid, 'int')."'";
            $this->db->query($sql) or displaySQLError(
                'Poll Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            if ($this->db->count_rows() <= 0)
            {
                $poll_exists = false;
            }
        }
        else
        {
            // Get last poll info
            $sql = "SELECT MAX(`id`) AS c FROM `fcms_polls`";
            $this->db->query($sql) or displaySQLError(
                'Max Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            $row = $this->db->get_row();
            $latest_poll_id = $row['c'];
            if (is_null($row['c']))
            {
                $poll_exists = false;
                $this->displayAddPollForm();
            }
            else
            {
                $sql = "SELECT `question`, o.`id`, `option` 
                        FROM `fcms_polls` AS p, `fcms_poll_options` AS o 
                        WHERE p.`id` = o.`poll_id` 
                        AND p.`id` = $latest_poll_id";
                $this->db->query($sql) or displaySQLError(
                    'Poll Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                );
            }
        }

        // Display the current poll
        if ($poll_exists)
        {
            echo '
            <form id="editform" name="editform" action="?page=admin_polls" method="post">
                <fieldset>
                    <legend><span>'.T_('Edit Poll').'</span></legend>';
            $i = 1;
            while ($row = $this->db->get_row())
            {
                if ($i < 2)
                {
                    echo '
                    <h3>'.cleanOutput($row['question']).'</h3>';
                }
                echo '
                    <div class="field-row">
                        <div class="field-label"><label for="show'.$i.'"><b>'.sprintf(T_('Option %s'), $i).':</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="show'.$i.'" id="show'.$i.'" ';
                if ($i < 3)
                {
                    echo "class=\"required\"";
                }
                echo ' size="50" value="'.cleanOutput($row['option']).'"/>
                            <input type="hidden" name="option'.$i.'" value="'.$row['id'].'"/>';
                if ($i >= 3)
                {
                    echo '
                            <input type="button" name="deleteoption" class="delbtn" value="'.T_('Delete').'" 
                                title="'.T_('Delete').'" onclick="document.editform.show'.$i.'.value=\'\';"/>';
                }
                echo '
                        </div>
                    </div>';
                $i++;
            }
            while ($i < 11)
            {
                echo '
                    <div class="field-row">
                        <div class="field-label"><label for="show'.$i.'"><b>'.sprintf(T_('Option %s'), $i).':</b></label></div>
                        <div class="field-widget">
                            <input type="text" id="show'.$i.'" name="show'.$i.'" size="50" value=""/>
                            <input type="hidden" name="option'.$i.'" value="new"/>
                        </div>
                    </div>';
                $i++;
            }
            echo '
                    <p>
                        <input class="sub1" type="submit" name="editsubmit" id="editsubmit" value="'.T_('Edit').'"/> &nbsp;
                        <a href="polls.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
        }
    }

    /**
     * displayAddPollForm 
     * 
     * @return void
     */
    function displayAddPollForm ()
    {
        echo '
            <script type="text/javascript" src="../inc/js/livevalidation.js"></script>
            <form id="addform" action="polls.php" method="post">
                <fieldset>
                    <legend><span>'.T_('Add New Poll').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="question"><b>'.T_('Poll Question').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="question" id="question" class="required" size="50"/></div>
                    </div>
                    <script type="text/javascript">
                        var fq = new LiveValidation(\'question\', { onlyOnSubmit: true });
                        fq.add(Validate.Presence, { failureMessage: "'.T_('Required').'" });
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="option1"><b>'.sprintf(T_('Option %s'), '1').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option1" id="option1" class="required" size="40"/></div>
                    </div>
                    <script type="text/javascript">
                        var foption1 = new LiveValidation(\'option1\', { onlyOnSubmit: true });
                        foption1.add(Validate.Presence, {failureMessage: "'.T_('Without at least 2 options, it\'s not much of a poll is it?').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="option2"><b>'.sprintf(T_('Option %s'), '2').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option2" id="option2" class="required" size="40"/></div>
                    </div>
                    <script type="text/javascript">
                        var foption2 = new LiveValidation(\'option2\', { onlyOnSubmit: true });
                        foption2.add(Validate.Presence, {failureMessage: "'.T_('Without at least 2 options, it\'s not much of a poll is it?').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label for="option3"><b>'.sprintf(T_('Option %s'), '3').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option3" id="option3" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option4"><b>'.sprintf(T_('Option %s'), '4').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option4" id="option4" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option5"><b>'.sprintf(T_('Option %s'), '5').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option5" id="option5" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option6"><b>'.sprintf(T_('Option %s'), '6').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option6" id="option6" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option7"><b>'.sprintf(T_('Option %s'), '7').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option7" id="option7" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option8"><b>'.sprintf(T_('Option %s'), '8').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option8" id="option8" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option9"><b>'.sprintf(T_('Option %s'), '9').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option9" id="option9" size="40"/></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="option10"><b>'.sprintf(T_('Option %s'), '10').'</b></label></div> 
                        <div class="field-widget"><input type="text" name="option10" id="option10" size="40"/></div>
                    </div>
                    <p>
                        <input class="sub1" type="submit" name="addsubmit" value="'.T_('Add').'"/> &nbsp;
                        <a href="polls.php">'.T_('Cancel').'</a>
                    </p>
                </fieldset>
            </form>';
    }

    /**
     * displayAdminConfig
     *
     * Displays the forms for changing/configuring the sitename,
     * email, auto activation, user defaults and sections.
     * 
     * @param string $view Which admin config section to view/edit
     * 
     * @return void
     */
    function displayAdminConfig ($view)
    {
        switch($view)
        {
            case 'general':
                $this->displayAdminConfigInfo();
                break;
            case 'defaults':
                $this->displayAdminConfigDefaults();
                break;
            case 'sections':
                $this->displayAdminConfigSections();
                break;
            case 'gallery':
                $this->displayAdminConfigGallery();
                break;
        }
    }

    /**
     * displayAdminConfigInfo 
     * 
     * @return void
     */
    function displayAdminConfigInfo ()
    {
        // General Config
        $sql = "SELECT `name`, `value`
                FROM `fcms_config`";
        $this->db->query($sql) or displaySQLError(
            'Site Info Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );

        $row = array();
        while ($r = $this->db->get_row())
        {
            $row[$r['name']] = $r['value'];
        }
        
        // Activate Options
        $activate_list = array (
            "0" => T_('Admin Activation'),
            "1" => T_('Auto Activation')
        );

        $activate_options = buildHtmlSelectOptions($activate_list, $row['auto_activate']);
        
        // Register Options
        $register_list = array (
            "0" => T_('Off'),
            "1" => T_('On')
        );

        $register_options = buildHtmlSelectOptions($register_list, $row['registration']);

        // Site Off Options
        $site_off_options = '<input type="radio" name="site_off" id="site_off_yes" value="yes"';
        if ($row['site_off'] == 1)
        {
            $site_off_options .= ' checked="checked"';
        }
        $site_off_options .= '><label class="radio_label" for="site_off_yes"> '.T_('Yes').'</label><br><input type="radio" name="site_off" id="site_off_no" value="no"';
        if ($row['site_off'] == 0)
        {
            $site_off_options .= ' checked="checked"';
        }
        $site_off_options .= '><label class="radio_label" for="site_off_no"> '.T_('No').'</label>';

        // Errors
        $error_list = array(
            '1' => T_('Log Errors'),
            '0' => T_('Display Errors')
        );

        $error_options = buildHtmlSelectOptions($error_list, $row['log_errors']);
        
        echo '
        <form action="config.php" method="post">
        <fieldset class="general_cfg">
            <legend><span>'.T_('Website Information').'</span></legend>
            <div id="site_info">
                <div class="field-row clearfix">
                    <div class="field-label"><label for="sitename"><b>'.T_('Site Name').'</b></label></div>
                    <div class="field-widget">
                        <input type="text" name="sitename" size="50" value="'.cleanOutput($row['sitename']).'"/>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="contact"><b>'.T_('Contact Email').'</b></label></div>
                    <div class="field-widget">
                        <input type="text" id="contact" name="contact" size="50" value="'.cleanOutput($row['contact']).'"/>
                    </div>
                </div>
                <script type="text/javascript">
                    var email = new LiveValidation(\'contact\', {onlyOnSubmit: true});
                    email.add(Validate.Email, {failureMessage: "'.T_('That\'s not a valid email address is it?').'"});
                    email.add(Validate.Length, {minimum: 10});
                </script>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="registration"><b>'.T_('Registration').'</b></label></div>
                    <div class="field-widget">
                        <select name="registration">
                            '.$register_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="activation"><b>'.T_('Account Activation').'</b></label></div>
                    <div class="field-widget">
                        <select name="activation">
                            '.$activate_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="site_off"><b>'.T_('Turn Off Site?').'</b></label></div>
                    <div class="field-widget">
                        '.$site_off_options.'
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="log_errors"><b>'.T_('Errors?').'</b></label></div>
                    <div class="field-widget">
                        <select name="log_errors">
                            '.$error_options.'
                        </select>
                    </div>
                </div>
                <p><input type="submit" id="submit-sitename" name="submit-sitename" value="'.T_('Save').'"/></p>
            </div>
        </fieldset>
        </form>';
    }

    /**
     * displayAdminConfigDefaults 
     * 
     * @return void
     */
    function displayAdminConfigDefaults ()
    {
 
        // Defaults Config
        $sql = "DESCRIBE `fcms_user_settings`";
        $this->db3->query($sql) or displaySQLError(
            'Describe Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        while ($drow = $this->db3->get_row())
        {
            if ($drow['Field'] == 'theme')
            {
                $default_theme = $drow['Default'];
            }
            if ($drow['Field'] == 'showavatar')
            {
                $default_showavatar = $drow['Default'];
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
        $dir           = "../themes/";
        $theme_options = '';

        if (is_dir($dir))
        {
            if ($dh = opendir($dir))
            {
                while (($file = readdir($dh)) !== false)
                {
                    // Skip non directories
                    if (filetype($dir . $file) !== "dir") {
                        continue;
                    }
                    // Skip smileys
                    if ($file == 'smileys') {
                        continue;
                    }
                    // Skip directories that start with a period
                    if ($file[0] === '.') {
                        continue;
                    }
                    // skip images dir
                    if ($file === 'images') {
                        continue;
                    }

                    $arr[] = $file;
                }

                closedir($dh);
                sort($arr);

                foreach($arr as $file)
                {
                    $theme_options .= '<option value="'.$file.'"';

                    if ($default_theme == $file)
                    {
                        $theme_options .= ' selected="selected"';
                    }

                    $theme_options .= ">$file</option>";
                }
            }
        }

        // Show Avatars
        $avatar_yes_check = '';
        $avatar_no_check  = '';
        if ($default_showavatar == 1)
        {
            $avatar_yes_check = 'checked="checked"';
        }
        else
        {
            $avatar_no_check = 'checked="checked"';
        }
        $avatars_options  = '<input type="radio" name="showavatar" id="showavatar_yes" value="yes" '.$avatar_yes_check.'>';
        $avatars_options .= '<label class="radio_label" for="showavatar_yes"> '.T_('Yes').'</label><br>';
        $avatars_options .= '<input type="radio" name="showavatar" id="showavatar_no" value="no" '.$avatar_no_check.'>';
        $avatars_options .= '<label class="radio_label" for="showavatar_no"> '.T_('No').'</label>';

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
            "2" => T_('Last 5 (by section)')
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
        $dst_on_check  = '';
        $dst_off_check = '';
        if ($default_dst == 1)
        {
            $dst_on_check = 'checked="checked"';
        }
        else
        {
            $dst_off_check = 'checked="checked"';
        }
        $dst_options  = '<input type="radio" name="dst" id="dst_on" value="on" '.$dst_on_check.'>';
        $dst_options .= '<label class="radio_label" for="dst_on"> '.T_('On').'</label><br>';
        $dst_options .= '<input type="radio" name="dst" id="dst_off" value="off" '.$dst_off_check.'>';
        $dst_options .= '<label class="radio_label" for="dst_off"> '.T_('Off').'</label>';

        // Board Sort
        $boardsort_list = array(
            "ASC" => T_('New Messages at Bottom'),
            "DESC" => T_('New Messages at Top')
        );

        $boardsort_options = buildHtmlSelectOptions($boardsort_list, $default_boardsort);
        
        echo '
        <form enctype="multipart/form-data" action="config.php" method="post">
        <fieldset class="default_cfg">
            <legend><span>'.T_('Defaults').'</span></legend>
            <div id="defaults">
                <div class="field-row clearfix">
                    <div class="field-label"><label for="theme"><b>'.T_('Theme').'</b></label></div>
                    <div class="field-widget">
                        <select name="theme" id="theme">
                            '.$theme_options.'
                        </select>
                    </select>
                </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="showavatar"><b>'.T_('Show Avatars').'</b></label></div>
                    <div class="field-widget">
                        '.$avatars_options.'
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="displayname"><b>'.T_('Display Name').'</b></label></div>
                    <div class="field-widget">
                        <select name="displayname" id="displayname" title="'.T_('How do you want your name to display?').'">
                            '.$displayname_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="frontpage"><b>'.T_('Front Page').'</b></label></div>
                    <div class="field-widget">
                        <select name="frontpage" id="frontpage" title="'.T_('How do you want the latest information to display on the homepage?').'">
                            '.$frontpage_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="timezone"><b>'.T_('Time Zone').'</b></label></div>
                    <div class="field-widget">
                        <select name="timezone" id="timezone" title="'.T_('What time zone do you live in?').'">
                            '.$tz_options.'
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="dst"><b>'.T_('Daylight Savings Time').'</b></label></div>
                    <div class="field-widget">
                        '.$dst_options.'
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="boardsort"><b>'.T_('Sort Messages').'</b></label></div>
                    <div class="field-widget">
                        <select name="boardsort" id="boardsort" title="'.T_('How do you want messages to display on the Message Board?').'">
                            '.$boardsort_options.'
                        </select>
                    </div>
                </div>
                <p>
                    <input type="submit" id="submit-defaults" name="submit-defaults" value="'.T_('Save').'"/> &nbsp;
                    <input type="checkbox" name="changeAll" id="changeAll"/> 
                    <label for="changeAll">'.T_('Update existing users?').'</label>
                </p>
            </div>
        </fieldset>
        </form>';
    }

    /**
     * getOrderSelectBox 
     * 
     * @param int $name     The name of the select box (comm|share)
     * @param int $id       The order number of the spot we are talking about
     * @param int $total    The total number of options for the select box
     * @param int $selected Which order is currently selected
     * @param int $start    What order to start on
     * 
     * @return void
     */
    function getOrderSelectBox ($name, $id, $total, $selected, $start = 1)
    {
        $order_options = '<select id="'.$name.'-order_'.$id.'" name="'.$name.'-order_'.$id.'">';

        for ($i = $start; $i <= $total; $i++)
        {
            $order_options .= '<option value="'.$i.'"';

            if ($i == $selected)
            {
                $order_options .= ' selected="selected"';
            }

            $order_options .= '>'.$i.'</option>';
        }

        $order_options .= '</select>';

        return $order_options;
    }

    /**
     * displayAdminConfigSections 
     * 
     * @return void
     */
    function displayAdminConfigSections ()
    {
        // Get Navigation Data
        $communicateNav = array();
        $shareNav       = array();
        $unused         = array();

        $sql = "SELECT `id`, `link`, `col`, `order`, `req`
                FROM `fcms_navigation` 
                WHERE `col` = 3 
                OR `col` = 4
                ORDER BY `order`";
        if (!$this->db2->query($sql))
        {
            displaySQLError('Navigation Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        while ($r = $this->db2->get_row())
        {
            if ($r['col'] == 3)
            {
                array_push($communicateNav, $r);
            }
            else
            {
                array_push($shareNav, $r);
            }

            if ($r['order'] == 0)
            {
                array_push($unused, $r);
            }
        }

        echo '
        <form action="config.php?view=sections" method="post">
            <fieldset>
                <legend><span>'.T_('Navigation').'</span></legend>';

        if (count($unused) > 0)
        {
            echo '
                <p><b>'.T_('Add Optional Sections').'</b></p>
                <p>';

            foreach ($unused AS $r)
            {
                echo getSectionName($r['link']).' &nbsp;<a class="add" href="?view=sections&amp;add='.$r['id'].'">'.T_('Add').'</a><br/>';
            }

            echo '
                </p>';
        }

        echo '
                <table class="communicate-nav">
                    <thead>
                        <tr colspan="3"><th><h4>'.T_('Communicate').'</h4></th></tr>
                        <tr><th>'.T_('Section').'</th><th>'.T_('Order').'</th><th class="remove">'.T_('Remove').'</th></tr>
                    </thead>
                    <tbody>';

        $communicateTotal = count($communicateNav);
        foreach ($communicateNav AS $r)
        {
            // order = 0 means it's unused
            if ($r['order'] > 0)
            {
                $del = '<i>'.T_('required').'</i>';
                if ($r['req'] < 1 && usingSection($r['link']))
                {
                    $del = '&nbsp;<input class="delbtn" type="submit" name="remove" value="'.$r['id'].'"/>';
                }
                echo '
                        <tr>
                            <td>'.getSectionName($r['link']).'</td>
                            <td>
                                '.$this->getOrderSelectBox('com', $r['id'], $communicateTotal, $r['order']).'
                            </td>
                            <td class="remove">'.$del.'</td>
                        </tr>';
            }
        }

        echo '
                    </tbody>
                </table>
                <table class="share-nav">
                    <thead>
                        <tr colspan="3"><th><h4>'.T_('Share').'</h4></th></tr>
                        <tr><th>'.T_('Section').'</th><th>'.T_('Order').'</th><th class="remove">'.T_('Remove').'</th></tr>
                    </thead>
                    <tbody>';

        $shareTotal = count($shareNav);
        foreach ($shareNav AS $r)
        {
            // order = 0 means it's unused
            if ($r['order'] > 0)
            {
                $del = '<i>'.T_('required').'</i>';
                if ($r['req'] < 1 && usingSection($r['link']))
                {
                    $del = '&nbsp;<input class="delbtn" type="submit" name="remove" value="'.$r['id'].'"/>';
                }
                echo '
                        <tr>
                            <td>'.getSectionName($r['link']).'</td>
                            <td>
                                '.$this->getOrderSelectBox('share', $r['id'], $shareTotal, $r['order']).'
                            </td>
                            <td class="remove">'.$del.'</td>
                        </tr>';
            }
        }

        echo '
                    </tbody>
                </table>
                <p><input type="submit" id="submit-sections" name="submit-sections" value="'.T_('Save').'"/></p>
            </fieldset>
        </form>';
    }

    /**
     * displayAdminConfigGallery 
     * 
     * @return void
     */
    function displayAdminConfigGallery ()
    {
        $sql = "SELECT `name`, `value`
                FROM `fcms_config`";
        $this->db->query($sql) or displaySQLError(
            'Site Info Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );

        $row = array();
        while ($r = $this->db->get_row())
        {
            $row[$r['name']] = $r['value'];
        }
        
        $full_size_list = array(
            "0" => T_('Off (2 photos)'),
            "1" => T_('On (3 photos)')
        );

        $full_size_options = buildHtmlSelectOptions($full_size_list, $row['full_size_photos']);
        
        echo '
        <form action="config.php" method="post">
        <fieldset class="gallery_cfg">
            <legend><span>'.T_('Photo Gallery').'</span></legend>
            <div id="gallery">
                <p class="info-alert">
                    '.T_('By default, Full Sized Photos is turned off to save on storage space and bandwidth.  Turning this feature on can eat up significant space and bandwith.').'
                </p>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="full_size_photos"><b>'.T_('Full Size Photos').'</b></label></div>
                    <div class="field-widget">
                        <select name="full_size_photos">
                            '.$full_size_options.'
                        </select>
                    </div>
                </div>
                <p><input type="submit" id="submit-gallery" name="submit-gallery" value="'.T_('Save').'"/></p>
            </div>
        </fieldset>
        </form>';
    }

    /**
     * displaySectionDropdown 
     * 
     * @param string $which_nav      The name of the navigation item
     * @param string $which_selected The table name for the navigation item that is currently selected for this select box.
     * @param string $num            Which navigation item order number.
     * 
     * @return void
     */
    function displaySectionDropdown ($which_nav, $which_selected, $num)
    { 
        echo '
                <div class="field-row clearfix">
                    <div class="field-label"><label for="'.$which_nav.'"><b>'.T_('Section').' '.$num.'</b></label></div>
                    <div class="field-widget">
                        <select name="'.$which_nav.'">';
        if (tableExists('fcms_news'))
        {
            echo '<option value="familynews"';
            if ($which_selected == 'familynews')
            {
                echo ' selected="selected"';
            }
            echo '>'.T_('Family News').'</option>';
        }
        if (tableExists('fcms_recipes'))
        {
            echo '<option value="recipes"';
            if ($which_selected == 'recipes')
            {
                echo ' selected="selected"';
            }
            echo '>'.T_('Recipes').'</option>';
        }
        if (tableExists('fcms_documents'))
        {
            echo '<option value="documents"';
            if ($which_selected == 'documents')
            {
                echo ' selected="selected"';
            }
            echo '>'.T_('Documents').'</option>';
        }
        if (tableExists('fcms_prayers'))
        {
            echo '<option value="prayers"';
            if ($which_selected == 'prayers')
            {
                echo ' selected="selected"';
            }
            echo '>'.T_('Prayer Concerns').'</option>';
        }
        $i = substr($which_nav, 7);
        echo '<option value="none'.$i.'"';
        $pos = strpos($which_selected, "none");
        if ($pos !== false)
        {
            echo ' selected="selected"';
        }
        echo '>'.T_('none').'</option>
                        </select>
                    </div>
                </div>';
    }

}
