<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

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

    function displayForm ()
    {
        global $LANG;
        $sql = "SELECT u.`fname`, u.`lname`, u.`username`, u.`password`, u.`access`, u.`email`, "
                . "u.`birthday`, s.`theme`, u.`avatar`, s.`displayname`, s.`frontpage`, "
                . "s.`timezone`, s.`dst`, s.`boardsort`, s.`showavatar`, s.`email_updates` "
             . "FROM `fcms_users` AS u, `fcms_user_settings` AS s "
             . "WHERE u.`id` = " . $this->cur_user_id . " "
             . "AND u.`id` = s.`user`";
        $this->db->query($sql) or displaySQLError(
            'Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();
        $year = substr($row['birthday'], 0,4);
        $month = substr($row['birthday'], 5,2);
        $day = substr($row['birthday'], 8,2);
        // Access Level
        $access = "<b>" . $LANG['level' .$row['access'] . '_1'] . "</b>: " 
            . $LANG['level' . $row['access'] . '_2'] . "<br/>" 
            . $LANG['level' . $row['access'] . '_3'];
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
            "1" => $LANG['first_name'],
            "2" => $LANG['first_last_name'],
            "3" => $LANG['username']
        );
        $displayname_options = buildHtmlSelectOptions($displayname_list, $row['displayname']);
        // Front Page
        $frontpage_list = array(
            "1" => $LANG['all_by_date'],
            "2" => $LANG['last_5_sections']
        );
        $frontpage_options = buildHtmlSelectOptions($frontpage_list, $row['frontpage']);
        // Email Options
        $email_updates_options = '<input type="radio" name="email_updates" id="email_updates_yes" '
            . 'value="yes"';
        if ($row['email_updates'] == 1) { $email_updates_options .= ' checked="checked"'; }
        $email_updates_options .= '><label class="radio_label" for="email_updates_yes"> '
            . $LANG['yes'] . '</label><br><input type="radio" name="email_updates" '
            . 'id="email_updates_no" value="no"';
        if ($row['email_updates'] == 0) { $email_updates_options .= ' checked="checked"'; }
        $email_updates_options .= '><label class="radio_label" for="email_updates_no"> '
            . $LANG['no'] . '</label>';
        // Timezone
        $tz_list = array(
            "-12 hours" => $LANG['tz_12'],
            "-11 hours" => $LANG['tz_11'],
            "-10 hours" => $LANG['tz_10'],
            "-9 hours" => $LANG['tz_9'],
            "-8 hours" => $LANG['tz_8'],
            "-7 hours" => $LANG['tz_7'],
            "-6 hours" => $LANG['tz_6'],
            "-5 hours" => $LANG['tz_5'],
            "-4 hours" => $LANG['tz_4'],
            "-3 hours -30 minutes" => $LANG['tz_33'],
            "-3 hours" => $LANG['tz_3'],
            "-2 hours" => $LANG['tz_2'],
            "-1 hours" => $LANG['tz_1'],
            "+0 hours" => $LANG['tz0'],
            "+1 hours" => $LANG['tz1'],
            "+2 hours" => $LANG['tz2'],
            "+3 hours" => $LANG['tz3'],
            "+3 hours 30 minutes" => $LANG['tz33'],
            "+4 hours" => $LANG['tz4'],
            "+4 hours 30 minutes" => $LANG['tz43'],
            "+5 hours" => $LANG['tz5'],
            "+5 hours 30 minutes" => $LANG['tz53'],
            "+6 hours" => $LANG['tz6'],
            "+7 hours" => $LANG['tz7'],
            "+8 hours" => $LANG['tz8'],
            "+9 hours" => $LANG['tz9'],
            "+9 hours 30 minutes" => $LANG['tz93'],
            "+10 hours" => $LANG['tz10'],
            "+11 hours" => $LANG['tz11'],
            "+12 hours" => $LANG['tz12']
        );
        $tz_options = buildHtmlSelectOptions($tz_list, $row['timezone']);
        // DST
        $dst_options = '<input type="radio" name="dst" id="dst_on" '
            . 'value="on"';
        if ($row['dst'] == 1) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_on"> ' . $LANG['on'] . '</label><br>'
            . '<input type="radio" name="dst" id="dst_off" value="off"';
        if ($row['dst'] == 0) { $dst_options .= ' checked="checked"'; }
        $dst_options .= '><label class="radio_label" for="dst_off"> ' . $LANG['off'] . '</label>';
        // Messageboard Sort
        $boardsort_list = array(
            "ASC" => $LANG['msgs_bottom'],
            "DESC" => $LANG['msgs_top']
        );
        $boardsort_options = buildHtmlSelectOptions($boardsort_list, $row['boardsort']);
        // Show Avatars
        $avatars_options = '<input type="radio" name="showavatar" id="showavatar_yes" '
            . 'value="yes"';
        if ($row['showavatar'] == 1) { $avatars_options .= ' checked="checked"'; }
        $avatars_options .= '><label class="radio_label" for="showavatar_yes"> '
            . $LANG['yes'] . '</label><br><input type="radio" name="showavatar" '
            . 'id="showavatar_no" value="no"';
        if ($row['showavatar'] == 0) { $avatars_options .= ' checked="checked"'; }
        $avatars_options .= '><label class="radio_label" for="showavatar_no"> '
            . $LANG['no'] . '</label>';
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
            $monthName = date('M', mktime(0, 0, 0, $i, 1, 2006));
            $month_list[$i] = getLangMonthName($monthName);
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
        
        echo <<<HTML
        <script type="text/javascript" src="inc/livevalidation.js"></script>
        <form id="frm" enctype="multipart/form-data" action="settings.php" method="post">
            <fieldset class="settings_stgs">
                <legend>{$LANG['settings']}</legend>
                <div class="field-row clearfix">
                    <div class="field-label"><b>{$LANG['access_level']}</b></div>
                    <div class="field-widget">{$access}</div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="theme"><b>{$LANG['theme']}</b></label></div>
                    <div class="field-widget">
                        <select name="theme" id="theme">
                            {$theme_options}
                        </select><br/>
                        <small>({$LANG['theme_desc']})</small>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label">
                        <label for="avatar"><b>{$LANG['avatar']}</b></label><br/>
                        <img src="gallery/avatar/{$row['avatar']}" alt="avatar"/>
                    </div>
                    <div class="field-widget">
                        <input type="file" name="avatar" id="avatar" size="30" title="{$LANG['title_avatar']}"/>
                        <input type="hidden" name="avatar_orig" value="{$row['avatar']}"/>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="displayname"><b>{$LANG['display_name']}</b></label></div>
                    <div class="field-widget">
                        <select name="displayname" id="displayname" title="{$LANG['title_display']}">
                            {$displayname_options}
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="frontpage"><b>{$LANG['frontpage']}</b></label></div>
                    <div class="field-widget">
                        <select name="frontpage" id="frontpage" title="{$LANG['title_frontpage']}">
                            {$frontpage_options}
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="email_updates"><b>{$LANG['email_updates']}</b></label></div>
                    <div class="field-widget">
                        {$email_updates_options}
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="timezone"><b>{$LANG['timezone']}</b></label></div>
                    <div class="field-widget">
                        <select name="timezone" id="timezone" title="{$LANG['title_timezone']}">
                            {$tz_options}
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="dst"><b>{$LANG['daylight']}</b></label></div>
                    <div class="field-widget">
                        {$dst_options}<br/>
                        <small>({$LANG['daylight_desc']})</small>
                    </div>
                </div>
            </fieldset>
            <fieldset class="messageboard_stgs">
                <legend>{$LANG['link_board']}</legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="boardsort"><b>{$LANG['sort_msgs']}</b></label></div>
                    <div class="field-widget">
                        <select name="boardsort" id="boardsort" title="{$LANG['title_sort_msgs']}">
                            {$boardsort_options}
                        </select>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="showavatar"><b>{$LANG['show_avatars']}</b></label></div>
                    <div class="field-widget">
                        {$avatars_options}
                    </div>
                </div>
            </fieldset>
            <fieldset class="info_stgs">
                <legend>{$LANG['personal_info']}</legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="fname"><b>{$LANG['first_name']}</b></label></div>
                    <div class="field-widget">
                        <input type="text" name="fname" size="50" id="fname" value="{$row['fname']}" title="{$LANG['title_fname']}"/>
                    </div>
                </div>
                <script type="text/javascript">
                    var ffname = new LiveValidation('fname', { validMessage: "{$LANG['lv_thanks']}", wait: 500});
                    ffname.add(Validate.Presence, {failureMessage: "{$LANG['lv_sorry_req']}"});
                </script>
                <div class="field-row clearfix">
                    <div class="field-label"><b><label for="lname">{$LANG['last_name']}</label></b></div>
                    <div class="field-widget">
                        <input type="text" name="lname" size="50" id="lname" value="{$row['lname']}" title="{$LANG['title_lname']}"/>
                    </div>
                </div>
                <script type="text/javascript">
                    var flname = new LiveValidation('lname', { validMessage: "{$LANG['lv_thanks']}", wait: 500});
                    flname.add(Validate.Presence, {failureMessage: "{$LANG['lv_sorry_req']}"});
                </script>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="email"><b>{$LANG['email']}</b></label></div>
                    <div class="field-widget">
                        <input type="text" name="email" size="50" id="email" value="{$row['email']}" title="{$LANG['title_email']}"/>
                    </div>
                </div>
                <script type="text/javascript">
                    var femail = new LiveValidation('email', { validMessage: "{$LANG['lv_thanks']}", wait: 500});
                    femail.add(Validate.Presence, {failureMessage: "{$LANG['lv_sorry_req']}"});
                    femail.add(Validate.Email, {failureMessage: "{$LANG['lv_bad_email']}" });
                    femail.add(Validate.Length, {minimum: 10});
                </script>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="sday"><b>{$LANG['birthday']}</b></label></div>
                    <div class="field-widget">
                        <select id="sday" name="sday">
                            {$day_options}
                        </select>
                        <select id="smonth" name="smonth">
                            {$month_options}
                        </select>
                        <select id="syear" name="syear">
                            {$year_options}
                        </select>
                    </div>
                </div>
            </fieldset>
            <fieldset class="login_stgs">
                <legend>{$LANG['login_info']}</legend>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="uname"><b>{$LANG['username']}</b></label></div>
                    <div class="field-widget">
                        <input disabled="disabled" type="text" name="uname" size="50" id="uname" value="{$row['username']}"/>
                    </div>
                </div>
                <div class="field-row clearfix">
                    <div class="field-label"><label for="pass"><b>{$LANG['password']}</b></label></div>
                    <div class="field-widget">
                        <input type="password" name="pass" size="50" id="pass"/><br/>
                        <small>({$LANG['password_desc']})</small>
                    </div>
                </div>
            </fieldset>
            <p><input type="submit" name="submit" id="submit" value="{$LANG['submit']}"/></p>
        </form>

HTML;
    }

} ?>