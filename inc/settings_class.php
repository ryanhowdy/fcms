<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

/**
 * Settings 
 * 
 * @package     Family Connections
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Settings
{

    var $db;
    var $currentUserId;
    var $currentUserEmail;
    var $tz_offset;

    /**
     * Settings 
     * 
     * @param   int     $currentUserId 
     * @param   string  $type 
     * @param   string  $host 
     * @param   string  $database 
     * @param   string  $user 
     * @param   string  $pass 
     * @return  void
     */
    function Settings ($currentUserId, $type, $host, $database, $user, $pass)
    {
        $this->currentUserId = cleanInput($currentUserId, 'int');

        $this->db = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT `timezone` 
                FROM `fcms_user_settings` 
                WHERE `user` = '$currentUserId'";
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->tz_offset = $row['timezone'];

        $sql = "SELECT `email` 
                FROM `fcms_users` 
                WHERE `id` = '$currentUserId'";
        $this->db->query($sql) or displaySQLError(
            'Email Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->currentUserEmail = $row['email'];
    }

    /**
     * displayForm 
     * 
     * @param   string  $option 
     * @return  void
     */
    function displayForm ($option = 'all')
    {
        $locale = new Locale();
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, u.`password`, u.`access`, u.`email`, 
                    u.`birthday`, s.`theme`, u.`avatar`, u.`gravatar`, s.`displayname`, s.`frontpage`, 
                    s.`timezone`, s.`dst`, s.`boardsort`, s.`showavatar`, s.`email_updates`, 
                    s.`advanced_upload`, s.`language`, u.`sex` 
                FROM `fcms_users` AS u, `fcms_user_settings` AS s 
                WHERE u.`id` = '" . $this->currentUserId . "' 
                AND u.`id` = s.`user`";
        $this->db->query($sql) or displaySQLError(
            'Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();

        $year  = substr($row['birthday'], 0,4);
        $month = substr($row['birthday'], 5,2);
        $day   = substr($row['birthday'], 8,2);

        // Access Level
        $access = $this->getAccessLevelDescription($row['access']);

        // Theme
        $dir = "themes/";
        $theme_options = '';
        if (is_dir($dir))    {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if (filetype($dir . $file) === "dir" && 
                        $file !== "." && 
                        $file !== ".." && 
                        $file !== "smileys"
                    ) {
                        $arr[] = $file;
                    }
                }
                closedir($dh);
                sort($arr);
                foreach($arr as $file) {
                    $theme_options .= "<option value=\"$file\"";
                    if ($row['theme'] == $file) {
                        $theme_options .= " selected=\"selected\"";
                    }
                    $theme_options .= ">$file</option>";
                }
            }
        }
        // Display Name
        $displayname_list = array(
            "1" => T_('First Name'),
            "2" => T_('First and Last Name'),
            "3" => T_('Username')
        );
        $displayname_options = buildHtmlSelectOptions($displayname_list, $row['displayname']);

        // Front Page
        $frontpage_list = array(
            "1" => T_('All (by date)'),
            "2" => T_('Last 5 (by section)')
        );
        $frontpage_options = buildHtmlSelectOptions($frontpage_list, $row['frontpage']);

        // Email Options
        $email_updates_options = '<input type="radio" name="email_updates" id="email_updates_yes" '
            . 'value="yes"';
        if ($row['email_updates'] == 1) { $email_updates_options .= ' checked="checked"'; }
        $email_updates_options .= '><label class="radio_label" for="email_updates_yes"> '
            . T_('Yes') . '</label><br><input type="radio" name="email_updates" '
            . 'id="email_updates_no" value="no"';
        if ($row['email_updates'] == 0) { $email_updates_options .= ' checked="checked"'; }
        $email_updates_options .= '><label class="radio_label" for="email_updates_no"> '
            . T_('No') . '</label>';

        // Advanced Upload
        $advanced_upload_options = '<input type="radio" name="advanced_upload" id="advanced_upload_yes" '
            . 'value="yes"';
        if ($row['advanced_upload'] == 1) { $advanced_upload_options .= ' checked="checked"'; }
        $advanced_upload_options .= '><label class="radio_label" for="advanced_upload_yes"> '
            . T_('Yes') . '</label><br><input type="radio" name="advanced_upload" '
            . 'id="advanced_upload_no" value="no"';
        if ($row['advanced_upload'] == 0) { $advanced_upload_options .= ' checked="checked"'; }
        $advanced_upload_options .= '><label class="radio_label" for="advanced_upload_no"> '
            . T_('No') . '</label>';

        // Language
        $lang_dir = "language/";
        $lang_options = '';
        if (is_dir($lang_dir))
        {
            if ($dh = opendir($lang_dir))
            {
                $arr = array();
                while (($file = readdir($dh)) !== false)
                {
                    // Skip directories that start with a period
                    if ($file[0] === '.') {
                        continue;
                    }

                    // Skip directories that don't include a messages.mo file
                    if (!file_exists($lang_dir . $file . '/LC_MESSAGES/messages.mo')) {
                        continue;
                    }

                    $arr[$file] = getLangName($file);
                }
                closedir($dh);
                asort($arr);
                foreach($arr as $key => $val)
                {
                    $lang_options .= '<option value="'.$key.'"';
                    if ($row['language'] == $key) {
                        $lang_options .= ' selected="selected"';
                    }
                    $lang_options .= '>'.$val.'</option>';
                }
            }
        }

        // Timezone
        $tz_list = array(
            "-12 hours"             => T_('(GMT -12:00) Eniwetok, Kwajalein'),
            "-11 hours"             => T_('(GMT -11:00) Midway Island, Samoa'),
            "-10 hours"             => T_('(GMT -10:00) Hawaii'),
            "-9 hours"              => T_('(GMT -9:00) Alaska'),
            "-8 hours"              => T_('(GMT -8:00) Pacific Time (US & Canada)'),
            "-7 hours"              => T_('(GMT -7:00) Mountain Time (US & Canada)'),
            "-6 hours"              => T_('(GMT -6:00) Central Time (US & Canada), Mexico City'),
            "-5 hours"              => T_('(GMT -5:00) Eastern Time (US & Canada), Bogota, Lima'),
            "-4 hours"              => T_('(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz'),
            "-3 hours -30 minutes"  => T_('(GMT -3:30) Newfoundland'),
            "-3 hours"              => T_('(GMT -3:00) Brazil, Buenos Aires, Georgetown'),
            "-2 hours"              => T_('(GMT -2:00) Mid-Atlantic'),
            "-1 hours"              => T_('(GMT -1:00) Azores, Cape Verde Islands'),
            "-0 hours"              => T_('(GMT) Western Europe Time, London, Lisbon, Casablanca'),
            "+1 hours"              => T_('(GMT +1:00) Brussels, Copenhagen, Madrid, Paris'),
            "+2 hours"              => T_('(GMT +2:00) Kaliningrad, South Africa'),
            "+3 hours"              => T_('(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburgh'),
            "+3 hours 30 minutes"   => T_('(GMT +3:30) Tehran'),
            "+4 hours"              => T_('(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi'),
            "+4 hours 30 minutes"   => T_('(GMT +4:30) Kabul'),
            "+5 hours"              => T_('(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent'),
            "+5 hours 30 minutes"   => T_('(GMT +5:30) Bombay, Calcutta, Madras, New Delhi'),
            "+6 hours"              => T_('(GMT +6:00) Almaty, Dhaka, Colombo'),
            "+7 hours"              => T_('(GMT +7:00) Bangkok, Hanoi, Jakarta'),
            "+8 hours"              => T_('(GMT +8:00) Beijing, Perth, Singapore, Hong Kong'),
            "+9 hours"              => T_('(GMT +9:00) Tokyo, Seoul, Osaka, Spporo, Yakutsk'),
            "+9 hours 30 minutes"   => T_('(GMT +9:30) Adeliaide, Darwin'),
            "+10 hours"             => T_('(GMT +10:00) Eastern Australia, Guam, Vladivostok'),
            "+11 hours"             => T_('(GMT +11:00) Magadan, Solomon Islands, New Caledonia'),
            "+12 hours"             => T_('(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka')
        );
        $tz_options = buildHtmlSelectOptions($tz_list, $row['timezone']);

        // DST
        $dst_options = '<input type="radio" name="dst" id="dst_on" '
            . 'value="on"';
        if ($row['dst'] == 1) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_on"> ' . T_('On') . '</label><br>'
            . '<input type="radio" name="dst" id="dst_off" value="off"';
        if ($row['dst'] == 0) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_off"> ' . T_('Off') . '</label>';

        // Messageboard Sort
        $boardsort_list = array(
            "ASC" => T_('New Messages at Bottom'),
            "DESC" => T_('New Messages at Top')
        );
        $boardsort_options = buildHtmlSelectOptions($boardsort_list, $row['boardsort']);

        // Avatar
        $current_avatar_type = 'fcms';
        if ($row['avatar'] == 'no_avatar.jpg') {
            $current_avatar_type = 'default';
        } else if ($row['avatar'] == 'gravatar') {
            $current_avatar_type = 'gravatar';
        }
        $avatar_list = array(
            'fcms'      => T_('Upload Avatar'),
            'gravatar'  => T_('Use Gravatar'),
            'default'   => T_('Use Default'),
        );
        $avatar_options = buildHtmlSelectOptions($avatar_list, $current_avatar_type);

        // Show Avatars
        $show_avatars_options = '<input type="radio" name="showavatar" id="showavatar_yes" '
            . 'value="yes"';
        if ($row['showavatar'] == 1) { $show_avatars_options .= ' checked="checked"'; }
        $show_avatars_options .= '><label class="radio_label" for="showavatar_yes"> '
            . T_('Yes') . '</label><br><input type="radio" name="showavatar" '
            . 'id="showavatar_no" value="no"';
        if ($row['showavatar'] == 0) { $show_avatars_options .= ' checked="checked"'; }
        $show_avatars_options .= '><label class="radio_label" for="showavatar_no"> '
            . T_('No') . '</label>';

        // Gender
        $gender_options = buildHtmlSelectOptions(array('M' => T_('Male'), 'F' => T_('Female')), $row['sex']);

        // Birthday
        $day_list = array();
        $i = 1;
        while ($i <= 31) {
            $day_list[$i] = $i;
            $i++;
        }
        $day_options = buildHtmlSelectOptions($day_list, $day);
        $month_list = array();
        $i = 1;
        while ($i <= 12) {
            $month_list[$i] = $locale->getMonthAbbr($i);
            $i++;
        }
        $month_options = buildHtmlSelectOptions($month_list, $month);
        $year_list = array();
        $i = 1900;
        $year_end = $locale->fixDate('Y', $this->tz_offset);
        while ($i <= $year_end) {
            $year_list[$i] = $i;
            $i++;
        }
        $year_options = buildHtmlSelectOptions($year_list, $year);
        
        echo '
                <script type="text/javascript" src="inc/livevalidation.js"></script>
                <form id="frm" enctype="multipart/form-data" action="settings.php" method="post">';

        if ($option == 'all' || $option == 'settings') {
            echo '
                <fieldset class="settings_stgs">
                    <legend><span>'.T_('Settings').'</span></legend>
                    <input type="hidden" name="settings" value="settings"/>
                    <div class="field-row clearfix">
                        <div class="field-label"><b>'.T_('Access Level').'</b></div>
                        <div class="field-widget">'.$access.'</div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="theme"><b>'.T_('Theme').'</b></label></div>
                        <div class="field-widget">
                            <select name="theme" id="theme">
                                '.$theme_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label">
                            <label for="avatar"><b>'.T_('Avatar').'</b></label>
                        </div>
                        <div class="field-widget">
                            <select name="avatar_type" id="avatar_type">
                                '.$avatar_options.'
                            </select><br/>
                            <div id="not-gravatar">
                                <input type="file" name="avatar" id="avatar" size="30" title="'.T_('Upload your personal image (Avatar)').'"/>
                                <input type="hidden" name="avatar_orig" value="'.cleanOutput($row['avatar']).'"/>
                            </div>
                            <div id="gravatar">
                                <b>'.T_('Gravatar Email').'</b><br/>
                                <input type="text" name="gravatar_email" size="30" value="'.cleanOutput($row['gravatar']).'"/>
                            </div>
                            <img id="current-avatar" src="'.getCurrentAvatar($this->currentUserId).'" alt="'.T_('This is your current avatar.').'"/>
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
                        <div class="field-label"><label for="frontpage"><b>'.T_('Frontpage').'</b></label></div>
                        <div class="field-widget">
                            <select name="frontpage" id="frontpage" title="'.T_('How do you want the latest information to display on the frontpage?').'">
                                '.$frontpage_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email_updates"><b>'.T_('Email Updates').'</b></label></div>
                        <div class="field-widget">
                            '.$email_updates_options.'
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="advanced_upload"><b>'.T_('Advanced Uploader').'</b></label></div>
                        <div class="field-widget">
                            '.$advanced_upload_options.'
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="language"><b>'.T_('Language').'</b></label></div>
                        <div class="field-widget">
                            <select name="language" id="language" title="'.T_('What language do you speak?').'">
                                '.$lang_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="timezone"><b>'.T_('Timezone').'</b></label></div>
                        <div class="field-widget">
                            <select name="timezone" id="timezone" title="'.T_('What timezone do you live in?').'">
                                '.$tz_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="dst"><b>'.T_('Daylight Savings Time').'</b></label></div>
                        <div class="field-widget">
                            '.$dst_options.'<br/>
                            <small>('.T_('You will need to manually change this off and on if your City/Town makes use of DST.').')</small>
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>';
        }
        if ($option == 'all' || $option == 'board') {
            echo '
                <fieldset class="messageboard_stgs">
                    <legend><span>'.T_('Message Board').'</span></legend>
                    <input type="hidden" name="board" value="board"/>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="boardsort"><b>'.T_('Sort Messages').'</b></label></div>
                        <div class="field-widget">
                            <select name="boardsort" id="boardsort" title="'.T_('What order do you want new messages to display?').'">
                                '.$boardsort_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="showavatar"><b>'.T_('Show Avatars').'</b></label></div>
                        <div class="field-widget">
                            '.$show_avatars_options.'
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>';
        }
        if ($option == 'all' || $option == 'personal') {
            echo '
                <fieldset class="info_stgs">
                    <legend><span>'.T_('Personal Info').'</span></legend>
                    <input type="hidden" name="personal" value="personal"/>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="fname"><b>'.T_('First Name').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="fname" size="50" id="fname" value="'.cleanOutput($row['fname']).'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but this information is required.').'"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><b><label for="lname">'.T_('Last Name').'</label></b></div>
                        <div class="field-widget">
                            <input type="text" name="lname" size="50" id="lname" value="'.cleanOutput($row['lname']).'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but this information is required.').'"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>'.T_('Email').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="email" size="50" id="email" value="'.cleanOutput($row['email']).'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but this information is required.').'"});
                        femail.add(Validate.Email, {failureMessage: "'.T_('That\'s not a valid email address is it?').'" });
                        femail.add(Validate.Length, {minimum: 10});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><b><label for="sex">'.T_('Gender').'</label></b></div>
                        <div class="field-widget">
                            <select id="sex" name="sex">
                                '.$gender_options.'
                            </select>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var fsex = new LiveValidation(\'sex\', { onlyOnSubmit: true });
                        fsex.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but this information is required.').'"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="sday"><b>'.T_('Birthday').'</b></label></div>
                        <div class="field-widget">
                            <select id="sday" name="sday">
                                '.$day_options.'
                            </select>
                            <select id="smonth" name="smonth">
                                '.$month_options.'
                            </select>
                            <select id="syear" name="syear">
                                '.$year_options.'
                            </select>
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>';
        }
        if ($option == 'all' || $option == 'password') {
            echo '
                <fieldset class="login_stgs">
                    <legend><span>'.T_('Password').'</span></legend>
                    <input type="hidden" name="password" value="password"/>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="uname"><b>'.T_('Username').'</b></label></div>
                        <div class="field-widget">
                            <input disabled="disabled" type="text" name="uname" size="50" id="uname" value="'.cleanOutput($row['username']).'"/>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="pass"><b>'.T_('Password').'</b></label></div>
                        <div class="field-widget">
                            <input type="password" name="pass" size="50" id="pass"/>
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>';
        }
        echo '
            </form>';
    }

    /**
     * getAccessLevelDescription 
     * 
     * @param   int     $access 
     * @return  string
     */
    function getAccessLevelDescription ($access)
    {
        $access = cleanInput($access, 'int');

        switch ($access) {
            case 1:
                $ret = '<b>'.T_('Level 1 (Admin)').'</b>: '.T_('You have administrative rights.').'<br/>'
                    .T_('You have full access to add/change/delete all information.');
                break;
            case 2:
                $ret = '<b>'.T_('Level 2 (Helper)').'</b>: '.T_('You have moderation rights.').'<br/>'
                    .T_('You have some access to add/change/delete information including your own.  This level is mostly used for message board moderation (i.e. cleanup of old/unused posts and other such information).');
                break;
            case 3:
                $ret = '<b>'.T_('Level 3 (Member)').'</b>: '.T_('You have basic rights.').'<br/>'
                    .T_('You can add/change/delete only your own information.');
                break;
            case 4:
                $ret = '<b>'.T_('Level 4 (Non-Photographer)').'</b>: '.T_('You have limited rights.').'<br/>'
                    .T_('You have the same rights as a Member, except you cannot use the Photo Gallery.');
                break;
            case 5:
                $ret = '<b>'.T_('Level 5 (Non-Poster)').'</b>: '.T_('You have limited rights.').'<br/>'
                    .T_('You have the same rights as a Member, except you cannot use the Message Board.');
                break;
            case 6:
                $ret = '<b>'.T_('Level 6 (Commenter)').'</b>: '.T_('You have limited rights.').'<br/>'
                    .T_('You can only add comments to the site.');
                break;
            case 7:
                $ret = '<b>'.T_('Level 7 (Poster)').'</b>: '.T_('You have limited rights.').'<br/>'
                    .T_('You can only post messages to the Message Board.');
                break;
            case 8:
                $ret = '<b>'.T_('Level 8 (Photographer)').'</b>: '.T_('You have limited rights.').'<br/>'
                    .T_('You can only add photos to the Photo Gallery.');
                break;
            case 9:
                $ret = '<b>'.T_('Level 9 (Blogger)').'</b>: '.T_('You have limited rights.').'<br/>'
                    .T_('You can only add news to the Family News section.');
                break;
            case 10:
                $ret = '<b>'.T_('Level 10 (Guest)').'</b>: '.T_('You have view only rights.').'<br/>'
                    .T_('You cannot add/change/delete any information.');
                break;
            default:
                $ret = '<b>'.T_('Level 3 (Member)').'</b>: '.T_('You have basic rights.').'<br/>'
                    .T_('You can add/change/delete only your own information.');
                break;
        }
        return $ret;
    }

} ?>
