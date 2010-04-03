<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('locale.php');

class Settings
{

    var $db;
    var $cur_user_id;
    var $cur_user_email;

    function Settings ($current_user_id, $type, $host, $database, $user, $pass)
    {
        $this->cur_user_id = $current_user_id;
        $this->db = new database($type, $host, $database, $user, $pass);
        $sql = "SELECT email FROM `fcms_users` WHERE id = " . $this->cur_user_id;
        $this->db->query($sql) or displaySQLError(
            'Timezone Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $this->cur_user_email = $row['email'];
    }

    function displayForm ($option = 'all')
    {
        $locale = new Locale();
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, u.`password`, u.`access`, u.`email`, 
                    u.`birthday`, s.`theme`, u.`avatar`, s.`displayname`, s.`frontpage`, 
                    s.`timezone`, s.`dst`, s.`boardsort`, s.`showavatar`, s.`email_updates`, 
                    s.`advanced_upload`, s.`language` 
                FROM `fcms_users` AS u, `fcms_user_settings` AS s 
                WHERE u.`id` = " . $this->cur_user_id . " 
                AND u.`id` = s.`user`";
        $this->db->query($sql) or displaySQLError(
            'Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $year = substr($row['birthday'], 0,4);
        $month = substr($row['birthday'], 5,2);
        $day = substr($row['birthday'], 8,2);

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
            "1" => _('First Name'),
            "2" => _('First and Last Name'),
            "3" => _('Username')
        );
        $displayname_options = buildHtmlSelectOptions($displayname_list, $row['displayname']);
        // Front Page
        $frontpage_list = array(
            "1" => _('All (by date)'),
            "2" => _('Last 5 (by section)')
        );
        $frontpage_options = buildHtmlSelectOptions($frontpage_list, $row['frontpage']);
        // Email Options
        $email_updates_options = '<input type="radio" name="email_updates" id="email_updates_yes" '
            . 'value="yes"';
        if ($row['email_updates'] == 1) { $email_updates_options .= ' checked="checked"'; }
        $email_updates_options .= '><label class="radio_label" for="email_updates_yes"> '
            . _('Yes') . '</label><br><input type="radio" name="email_updates" '
            . 'id="email_updates_no" value="no"';
        if ($row['email_updates'] == 0) { $email_updates_options .= ' checked="checked"'; }
        $email_updates_options .= '><label class="radio_label" for="email_updates_no"> '
            . _('No') . '</label>';
        // Advanced Upload
        $advanced_upload_options = '<input type="radio" name="advanced_upload" id="advanced_upload_yes" '
            . 'value="yes"';
        if ($row['advanced_upload'] == 1) { $advanced_upload_options .= ' checked="checked"'; }
        $advanced_upload_options .= '><label class="radio_label" for="advanced_upload_yes"> '
            . _('Yes') . '</label><br><input type="radio" name="advanced_upload" '
            . 'id="advanced_upload_no" value="no"';
        if ($row['advanced_upload'] == 0) { $advanced_upload_options .= ' checked="checked"'; }
        $advanced_upload_options .= '><label class="radio_label" for="advanced_upload_no"> '
            . _('No') . '</label>';
        // Language
        $dir = "language/";
        $lang_options = '';
        if (is_dir($dir))    {
            if ($dh = opendir($dir)) {
                $arr = array();
                while (($file = readdir($dh)) !== false) {
                    if (filetype($dir . $file) === "dir" && 
                        $file !== "." && 
                        $file !== ".." 
                    ) {
                        $arr[$file] = getLangName($file);
                    }
                }
                closedir($dh);
                asort($arr);
                foreach($arr as $key => $val) {
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
            "-12 hours" => _('(GMT -12:00) Eniwetok, Kwajalein'),
            "-11 hours" => _('(GMT -11:00) Midway Island, Samoa'),
            "-10 hours" => _('(GMT -10:00) Hawaii'),
            "-9 hours" => _('(GMT -9:00) Alaska'),
            "-8 hours" => _('(GMT -8:00) Pacific Time (US & Canada)'),
            "-7 hours" => _('(GMT -7:00) Mountain Time (US & Canada)'),
            "-6 hours" => _('(GMT -6:00) Central Time (US & Canada), Mexico City'),
            "-5 hours" => _('(GMT -5:00) Eastern Time (US & Canada), Bogota, Lima'),
            "-4 hours" => _('(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz'),
            "-3 hours -30 minutes" => _('(GMT -3:30) Newfoundland'),
            "-3 hours" => _('(GMT -3:00) Brazil, Buenos Aires, Georgetown'),
            "-2 hours" => _('(GMT -2:00) Mid-Atlantic'),
            "-1 hours" => _('(GMT -1:00) Azores, Cape Verde Islands'),
            "-0 hours" => _('(GMT) Western Europe Time, London, Lisbon, Casablanca'),
            "+1 hours" => _('(GMT +1:00) Brussels, Copenhagen, Madrid, Paris'),
            "+2 hours" => _('(GMT +2:00) Kaliningrad, South Africa'),
            "+3 hours" => _('(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburgh'),
            "+3 hours 30 minutes" => _('(GMT +3:30) Tehran'),
            "+4 hours" => _('(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi'),
            "+4 hours 30 minutes" => _('(GMT +4:30) Kabul'),
            "+5 hours" => _('(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent'),
            "+5 hours 30 minutes" => _('(GMT +5:30) Bombay, Calcutta, Madras, New Delhi'),
            "+6 hours" => _('(GMT +6:00) Almaty, Dhaka, Colombo'),
            "+7 hours" => _('(GMT +7:00) Bangkok, Hanoi, Jakarta'),
            "+8 hours" => _('(GMT +8:00) Beijing, Perth, Singapore, Hong Kong'),
            "+9 hours" => _('(GMT +9:00) Tokyo, Seoul, Osaka, Spporo, Yakutsk'),
            "+9 hours 30 minutes" => _('(GMT +9:30) Adeliaide, Darwin'),
            "+10 hours" => _('(GMT +10:00) Eastern Australia, Guam, Vladivostok'),
            "+11 hours" => _('(GMT +11:00) Magadan, Solomon Islands, New Caledonia'),
            "+12 hours" => _('(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka')
        );
        $tz_options = buildHtmlSelectOptions($tz_list, $row['timezone']);
        // DST
        $dst_options = '<input type="radio" name="dst" id="dst_on" '
            . 'value="on"';
        if ($row['dst'] == 1) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_on"> ' . _('On') . '</label><br>'
            . '<input type="radio" name="dst" id="dst_off" value="off"';
        if ($row['dst'] == 0) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_off"> ' . _('Off') . '</label>';
        // Messageboard Sort
        $boardsort_list = array(
            "ASC" => _('New Messages at Bottom'),
            "DESC" => _('New Messages at Top')
        );
        $boardsort_options = buildHtmlSelectOptions($boardsort_list, $row['boardsort']);
        // Show Avatars
        $avatars_options = '<input type="radio" name="showavatar" id="showavatar_yes" '
            . 'value="yes"';
        if ($row['showavatar'] == 1) { $avatars_options .= ' checked="checked"'; }
        $avatars_options .= '><label class="radio_label" for="showavatar_yes"> '
            . _('Yes') . '</label><br><input type="radio" name="showavatar" '
            . 'id="showavatar_no" value="no"';
        if ($row['showavatar'] == 0) { $avatars_options .= ' checked="checked"'; }
        $avatars_options .= '><label class="radio_label" for="showavatar_no"> '
            . _('No') . '</label>';
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
        while ($i <= date('Y')) {
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
                    <legend><span>'._('Settings').'</span></legend>
                    <input type="hidden" name="settings" value="settings"/>
                    <div class="field-row clearfix">
                        <div class="field-label"><b>'._('Access Level').'</b></div>
                        <div class="field-widget">'.$access.'</div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="theme"><b>'._('Theme').'</b></label></div>
                        <div class="field-widget">
                            <select name="theme" id="theme">
                                '.$theme_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label">
                            <label for="avatar"><b>'._('Avatar').'</b></label><br/>
                            <img src="gallery/avatar/'.$row['avatar'].'" alt="avatar"/>
                        </div>
                        <div class="field-widget">
                            <input type="file" name="avatar" id="avatar" size="30" title="'._('Upload your personal image (Avatar)').'"/>
                            <input type="hidden" name="avatar_orig" value="'.$row['avatar'].'"/>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="displayname"><b>'._('Display Name').'</b></label></div>
                        <div class="field-widget">
                            <select name="displayname" id="displayname" title="'._('How do you want your name to display?').'">
                                '.$displayname_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="frontpage"><b>'._('Frontpage').'</b></label></div>
                        <div class="field-widget">
                            <select name="frontpage" id="frontpage" title="'._('How do you want the latest information to display on the frontpage?').'">
                                '.$frontpage_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email_updates"><b>'._('Email Updates').'</b></label></div>
                        <div class="field-widget">
                            '.$email_updates_options.'
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="advanced_upload"><b>'._('Advanced Uploader').'</b></label></div>
                        <div class="field-widget">
                            '.$advanced_upload_options.'
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="language"><b>'._('Language').'</b></label></div>
                        <div class="field-widget">
                            <select name="language" id="language" title="'._('What language do you speak?').'">
                                '.$lang_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="timezone"><b>'._('Timezone').'</b></label></div>
                        <div class="field-widget">
                            <select name="timezone" id="timezone" title="'._('What timezone do you live in?').'">
                                '.$tz_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="dst"><b>'._('Daylight Savings Time').'</b></label></div>
                        <div class="field-widget">
                            '.$dst_options.'<br/>
                            <small>('._('You will need to manually change this off and on if your City/Town makes use of DST.').')</small>
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'._('Submit').'"/></p>
                </fieldset>';
        }
        if ($option == 'all' || $option == 'board') {
            echo '
                <fieldset class="messageboard_stgs">
                    <legend><span>'._('Message Board').'</span></legend>
                    <input type="hidden" name="board" value="board"/>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="boardsort"><b>'._('Sort Messages').'</b></label></div>
                        <div class="field-widget">
                            <select name="boardsort" id="boardsort" title="'._('What order do you want new messages to display?').'">
                                '.$boardsort_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="showavatar"><b>'._('Show Avatars').'</b></label></div>
                        <div class="field-widget">
                            '.$avatars_options.'
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'._('Submit').'"/></p>
                </fieldset>';
        }
        if ($option == 'all' || $option == 'personal') {
            echo '
                <fieldset class="info_stgs">
                    <legend><span>'._('Personal Info').'</span></legend>
                    <input type="hidden" name="personal" value="personal"/>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="fname"><b>'._('First Name').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="fname" size="50" id="fname" value="'.$row['fname'].'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: "'._('Sorry, but this information is required.').'"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><b><label for="lname">'._('Last Name').'</label></b></div>
                        <div class="field-widget">
                            <input type="text" name="lname" size="50" id="lname" value="'.$row['lname'].'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: "'._('Sorry, but this information is required.').'"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="email"><b>'._('Email').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="email" size="50" id="email" value="'.$row['email'].'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                        femail.add(Validate.Presence, {failureMessage: "'._('Sorry, but this information is required.').'"});
                        femail.add(Validate.Email, {failureMessage: "'._('That\'s not a valid email address is it?').'" });
                        femail.add(Validate.Length, {minimum: 10});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="sday"><b>'._('Birthday').'</b></label></div>
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
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'._('Submit').'"/></p>
                </fieldset>';
        }
        if ($option == 'all' || $option == 'password') {
            echo '
                <fieldset class="login_stgs">
                    <legend><span>'._('Password').'</span></legend>
                    <input type="hidden" name="password" value="password"/>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="uname"><b>'._('Username').'</b></label></div>
                        <div class="field-widget">
                            <input disabled="disabled" type="text" name="uname" size="50" id="uname" value="'.$row['username'].'"/>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="pass"><b>'._('Password').'</b></label></div>
                        <div class="field-widget">
                            <input type="password" name="pass" size="50" id="pass"/>
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'._('Submit').'"/></p>
                </fieldset>';
        }
        echo '
            </form>';
    }

    function getAccessLevelDescription ($access)
    {
        switch ($access) {
            case 1:
                $ret = '<b>'._('Level 1 (Admin)').'</b>: '._('You have administrative rights.').'<br/>'
                    ._('You have full access to add/change/delete all information.');
                break;
            case 2:
                $ret = '<b>'._('Level 2 (Helper)').'</b>: '._('You have moderation rights.').'<br/>'
                    ._('You have some access to add/change/delete information including your own.  This level is mostly used for message board moderation (i.e. cleanup of old/unused posts and other such information).');
                break;
            case 3:
                $ret = '<b>'._('Level 3 (Member)').'</b>: '._('You have basic rights.').'<br/>'
                    ._('You can add/change/delete only your own information.');
                break;
            case 4:
                $ret = '<b>'._('Level 4 (Non-Photographer)').'</b>: '._('You have limited rights.').'<br/>'
                    ._('You have the same rights as a Member, except you cannot use the Photo Gallery.');
                break;
            case 5:
                $ret = '<b>'._('Level 5 (Non-Poster)').'</b>: '._('You have limited rights.').'<br/>'
                    ._('You have the same rights as a Member, except you cannot use the Message Board.');
                break;
            case 6:
                $ret = '<b>'._('Level 6 (Commenter)').'</b>: '._('You have limited rights.').'<br/>'
                    ._('You can only add comments to the site.');
                break;
            case 7:
                $ret = '<b>'._('Level 7 (Poster)').'</b>: '._('You have limited rights.').'<br/>'
                    ._('You can only post messages to the Message Board.');
                break;
            case 8:
                $ret = '<b>'._('Level 8 (Photographer)').'</b>: '._('You have limited rights.').'<br/>'
                    ._('You can only add photos to the Photo Gallery.');
                break;
            case 9:
                $ret = '<b>'._('Level 9 (Blogger)').'</b>: '._('You have limited rights.').'<br/>'
                    ._('You can only add news to the Family News section.');
                break;
            case 10:
                $ret = '<b>'._('Level 10 (Guest)').'</b>: '._('You have view only rights.').'<br/>'
                    ._('You cannot add/change/delete any information.');
                break;
            default:
                $ret = '<b>'._('Level 3 (Member)').'</b>: '._('You have basic rights.').'<br/>'
                    ._('You can add/change/delete only your own information.');
                break;
        }
        return $ret;
    }

} ?>