<?php
/**
 * Settings.
 *
 * @copyright   Copyright (c) 2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Settings
{
    public $fcmsError;
    public $fcmsDatabase;
    public $fcmsUser;

    /**
     * __construct.
     *
     * @param FCMS_Error $fcmsError
     * @param Database   $fcmsDatabase
     * @param User       $fcmsUser
     *
     * @return void
     */
    public function __construct(FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;
    }

    /**
     * displayAccountInformation.
     *
     * @return void
     */
    public function displayAccountInformation()
    {
        $sql = 'SELECT `username`, `email`, `phpass`
                FROM `fcms_users`
                WHERE `id` = ?';

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        echo '
                <script type="text/javascript" src="ui/js/livevalidation.js"></script>
                <form id="frm" action="settings.php?view=account" method="post">
                <fieldset>
                    <legend><span>'.T_('Account Information').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="uname"><b>'.T_('Username').'</b></label></div>
                        <div class="field-widget">
                            <input disabled="disabled" type="text" name="uname" size="50" id="uname" value="'.cleanOutput($row['username']).'"/>
                        </div>
                    </div>
                    <div class="field-row">
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
                    <div class="field-row">
                        <div class="field-label"><label for="pass"><b>'.T_('Change Password').'</b></label></div>
                        <div class="field-widget">
                            <input type="password" name="pass" size="50" id="pass"/><br/>
                            '.T_('Leave blank if you don\'t wish to change it.').'
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>
                </form>';
    }

    /**
     * displayTheme.
     *
     * @return void
     */
    public function displayTheme()
    {
        $sql = 'SELECT `theme`
                FROM `fcms_user_settings`
                WHERE `user` = ?';

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        // Theme
        $themes = [];
        $themeList = getThemeList();

        foreach($themeList as $file)
        {
            $themeData = $this->getThemeData($file);
            $themes[$file] = $themeData;
        }

        $currentTheme = $themes[$row['theme']];

        // current theme screenshot
        if (file_exists('ui/themes/'.$currentTheme['file'].'/screenshot.png'))
        {
            $img = '<img src="ui/themes/'.$currentTheme['file'].'/screenshot.png"/>';
        }
        else
        {
            $img = '<span>'.T_('No Preview').'</span>';
        }

        echo '
                <h3>'.T_('Current Theme').'</h3>

                <div class="current-theme">
                    '.$img.'
                    <div>
                        <p><b>'.$currentTheme['name'].'</b></p>
                        <p>'.$currentTheme['desc'].'</p>
                        <p>'.$currentTheme['author'].'<br/>'.$currentTheme['updated'].'</p>
                    </div>
                </div>

                <h3>'.T_('Themes').'</h3>';

        $canDelete = false;
        if ($this->fcmsUser->access == 1)
        {
            $canDelete = true;
        }

        foreach ($themes as $theme)
        {
            // skip current theme
            if ($theme['file'] == $row['theme'])
            {
                continue;
            }

            // screenshot
            if (file_exists('ui/themes/'.$theme['file'].'/screenshot.png'))
            {
                $img = '<img src="ui/themes/'.$theme['file'].'/screenshot.png"/>';
            }
            else
            {
                $img = '<span>'.T_('No Preview').'</span>';
            }

            // only admin can delete themes
            $del = '';
            if ($canDelete)
            {
                $del = ' | <a class="del_theme"href="?view=theme&amp;delete='.$theme['file'].'">'.T_('Delete').'</a>';
            }

            echo '
                <div class="theme-block">
                    <a href="?view=theme&amp;use='.$theme['file'].'" 
                        title="'.T_('Click to use this theme.').'">'.$img.'</a><br/>
                    <b>'.$theme['name'].'</b>
                    <p><a href="?view=theme&amp;use='.$theme['file'].'">'.T_('Use Theme').'</a>'.$del.'</p>
                    <p>'.$theme['desc'].'</p>
                    <p>'.$theme['author'].'<br/>'.$theme['updated'].'</p>
                </div>';
        }
    }

    /**
     * displaySettings.
     *
     * @return void
     */
    public function displaySettings()
    {
        $sql = "SELECT `displayname`, `language`,
                    `dst`, `timezone`, `boardsort`, `frontpage`
                FROM `fcms_user_settings`
                WHERE `user` = '".$this->fcmsUser->id."'";

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        // Display Name
        $displayname_list = [
            '1' => T_('First Name'),
            '2' => T_('First and Last Name'),
            '3' => T_('Username'),
        ];
        $displayname_options = buildHtmlSelectOptions($displayname_list, $row['displayname']);

        // Language
        $lang_dir = 'language/';
        $lang_options = '';

        if (is_dir($lang_dir))
        {
            if ($dh = opendir($lang_dir))
            {
                $arr = [];
                while (($file = readdir($dh)) !== false)
                {
                    // Skip directories that start with a period
                    if ($file[0] === '.') {
                        continue;
                    }

                    // Skip files (messages.pot)
                    if (!is_dir("$lang_dir$file"))
                    {
                        continue;
                    }

                    // Skip directories that don't include a messages.mo file
                    if (!file_exists($lang_dir.$file.'/LC_MESSAGES/messages.mo')) {
                        continue;
                    }

                    $arr[$file] = getLangName($file);
                }
                closedir($dh);
                asort($arr);
                foreach($arr as $key => $val)
                {
                    $sel = $row['language'] == $key ? 'selected="selected"' : '';
                    $lang_options .= '<option value="'.$key.'" '.$sel.'>'.$val.'</option>';
                }
            }
        }

        // Timezone
        $tz_list = getTimezoneList();
        $tz_options = buildHtmlSelectOptions($tz_list, $row['timezone']);

        // DST
        $yc = $row['dst'] == 1 ? 'checked="checked"' : '';
        $nc = $row['dst'] == 0 ? 'checked="checked"' : '';
        $dst_options = '<input type="radio" name="dst" id="dst_on" value="on" '.$yc.'>';
        $dst_options .= '<label class="radio_label" for="dst_on">'.T_('On').'</label>&nbsp;&nbsp; ';
        $dst_options .= '<input type="radio" name="dst" id="dst_off" value="off" '.$nc.'>';
        $dst_options .= '<label class="radio_label" for="dst_off">'.T_('Off').'</label>';

        // Front Page
        $frontpage_list = [
            '1' => T_('All (by date)'),
            '2' => T_('Last 5 (by plugin)'),
        ];
        $frontpage_options = buildHtmlSelectOptions($frontpage_list, $row['frontpage']);

        echo '
                <script type="text/javascript" src="ui/js/livevalidation.js"></script>
                <form id="frm" action="settings.php?view=settings" method="post">
                <fieldset>
                    <legend><span>'.T_('Language and Time').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="language"><b>'.T_('Language').'</b></label></div>
                        <div class="field-widget">
                            <select name="language" id="language" title="'.T_('What language do you speak?').'">
                                '.$lang_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="timezone"><b>'.T_('Timezone').'</b></label></div>
                        <div class="field-widget">
                            <select name="timezone" id="timezone" title="'.T_('What timezone do you live in?').'">
                                '.$tz_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="dst"><b>'.T_('Daylight Savings Time').'</b></label></div>
                        <div class="field-widget">
                            '.$dst_options.'<br/>
                            <small>'.T_('You will need to manually change this off and on if your City/Town makes use of DST.').'</small>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend><span>'.T_('Preferences').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="displayname"><b>'.T_('Display Name').'</b></label></div>
                        <div class="field-widget">
                            <select name="displayname" id="displayname" title="'.T_('How do you want your name to display?').'">
                                '.$displayname_options.'
                            </select>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="frontpage"><b>'.T_('Frontpage').'</b></label></div>
                        <div class="field-widget">
                            <select name="frontpage" id="frontpage" title="'.T_('How do you want the latest information to display on the frontpage?').'">
                                '.$frontpage_options.'
                            </select>
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>
                </form>';
    }

    /**
     * displayNotifications.
     *
     * @return void
     */
    public function displayNotifications()
    {
        $sql = "SELECT `email_updates` 
                FROM `fcms_user_settings`
                WHERE `user` = '".$this->fcmsUser->id."'";

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        // Email Options
        $email_updates_options = '<input type="radio" name="email_updates" id="email_updates_yes" '
            .'value="yes"';
        if ($row['email_updates'] == 1) { $email_updates_options .= ' checked="checked"'; }
        $email_updates_options .= '><label class="radio_label" for="email_updates_yes">'
            .T_('Yes').'</label>&nbsp;&nbsp; <input type="radio" name="email_updates" '
            .'id="email_updates_no" value="no"';
        if ($row['email_updates'] == 0) { $email_updates_options .= ' checked="checked"'; }
        $email_updates_options .= '><label class="radio_label" for="email_updates_no">'
            .T_('No').'</label>';

        echo '
                <script type="text/javascript" src="ui/js/livevalidation.js"></script>
                <form id="frm" action="settings.php?view=notifications" method="post">
                <fieldset>
                    <legend><span>'.T_('Notifications').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="email_updates"><b>'.T_('Email Updates').'</b></label></div>
                        <div class="field-widget">
                            '.$email_updates_options.'
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>
                </form>';
    }

    /**
     * displayPhotoGallerySettings.
     *
     * @return void
     */
    public function displayPhotoGallerySettings()
    {
        $sql = 'SELECT `uploader`, `advanced_tagging`
                FROM `fcms_user_settings`
                WHERE `user` = ?';

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        // Advanced Upload
        $plupload = '';
        $java = '';
        $basic = '';
        if ($row['uploader'] == 'plupload')
        {
            $plupload = 'checked="checked"';
        }
        elseif ($row['uploader'] == 'java')
        {
            $java = 'checked="checked"';
        }
        else
        {
            $basic = 'checked="checked"';
        }

        // Advanced Tagging
        $yc = $row['advanced_tagging'] == 1 ? 'checked="checked"' : '';
        $nc = $row['advanced_tagging'] == 0 ? 'checked="checked"' : '';

        $advanced_tagging_options = '<input type="radio" name="advanced_tagging" id="advanced_tagging_yes" value="yes" '.$yc.'>';
        $advanced_tagging_options .= '<label class="radio_label" for="advanced_tagging_yes">'.T_('Yes').'</label>&nbsp;&nbsp; ';
        $advanced_tagging_options .= '<input type="radio" name="advanced_tagging" id="advanced_tagging_no" value="no" '.$nc.'>';
        $advanced_tagging_options .= '<label class="radio_label" for="advanced_tagging_no">'.T_('No').'</label>';

        echo '
                <script type="text/javascript" src="ui/js/livevalidation.js"></script>
                <form id="frm" action="settings.php?view=photogallery" method="post">
                <fieldset>
                    <legend><span>'.T_('Photo Gallery Settings').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="uploader"><b>'.T_('Uploader').'</b></label></div>
                        <div class="field-widget">
                            <input type="radio" name="uploader" id="advanced_uploader" value="plupload" '.$plupload.'>
                            <label class="radio_label" for="advanced_uploader">'.T_('Advanced').'</label><br/>
                            <small>
                                <b>'.T_('Recommended.').'</b>
                                '.T_('Should work in most modern browsers. Allows you to upload multiple photos at once and very large photos.').'
                            </small><br/><br/>
                            <input type="radio" name="uploader" id="java_uploader" value="java" '.$java.'>
                            <label class="radio_label" for="java_uploader">'.T_('Java').'</label><br/>
                            <small>
                                '.T_('Allows you to upload multiple photos at once and very large photos.').'
                            </small><br/><br/>
                            <input type="radio" name="uploader" id="basic_uploader" value="basic" '.$basic.'>
                            <label class="radio_label" for="basic_uploader">'.T_('Basic').'</label><br/>
                            <small>
                                '.T_('For older browsers. Uploads one photo at a time.  May not work for on very large photos.').'
                            </small><br/><br/>
                        </div>
                    </div>
                    <div id="advanced_tagging_div" class="field-row" style="display:none">
                        <div class="field-label"><label for="advanced_tagging"><b>'.T_('Advanced Tagging').'</b></label></div>
                        <div class="field-widget">
                            '.$advanced_tagging_options.'<br/>
                            <small>
                                <b>'.T_('Requires JavaScript.').'</b>
                                '.T_('Allows you to more quickly tag members in photos.').'
                            </small>
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>
                </form>';
    }

    /**
     * displayFamilyNews.
     *
     * @return void
     */
    public function displayFamilyNews()
    {
        $sql = "SELECT `blogger`, `tumblr`, `wordpress`, `posterous`
                FROM `fcms_user_settings`
                WHERE `user` = '".$this->fcmsUser->id."'";

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $bloggerManualImport = empty($row['blogger'])
                                ? ''
                                : '<p><a class="blogger" href="?view=familynews&amp;import=blogger">'.T_('Manually Import Posts').'</a></p>';
        $tumblrManualImport = empty($row['tumblr'])
                                ? ''
                                : '<p><a class="tumblr" href="?view=familynews&amp;import=tumblr">'.T_('Manually Import Posts').'</a></p>';
        $wordpressManualImport = empty($row['wordpress'])
                                ? ''
                                : '<p><a class="wordpress" href="?view=familynews&amp;import=wordpress">'.T_('Manually Import Posts').'</a></p>';
        $posterousManualImport = empty($row['posterous'])
                                ? ''
                                : '<p><a class="posterous" href="?view=familynews&amp;import=posterous">'.T_('Manually Import Posts').'</a></p>';

        echo '
                <script type="text/javascript" src="ui/js/livevalidation.js"></script>
                <form id="frm" action="settings.php?view=familynews" method="post">
                <fieldset>
                    <legend><span>'.T_('Import Blog Posts').'</span></legend>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="blogger"><b>'.T_('Blogger').'</b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" name="blogger" name="blogger" size="50" value="'.cleanOutput($row['blogger']).'"/><br/>
                            '.T_('Enter your blogger id.').'
                            <a href="http://www.google.com/support/blogger/bin/answer.py?answer=42191">'.T_('More Info').'</a>
                            '.$bloggerManualImport.'
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="tumblr"><b>'.T_('Tumblr').'</b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" name="tumblr" name="tumblr" size="50" value="'.cleanOutput($row['tumblr']).'"/><br/>
                            '.T_('Enter the url to your blog.').'
                            '.$tumblrManualImport.'
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="wordpress"><b>'.T_('WordPress').'</b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" name="wordpress" name="wordpress" size="50" value="'.cleanOutput($row['wordpress']).'"/><br/>
                            '.T_('Enter the url to the wordpress rss feed.').'
                            '.$wordpressManualImport.'
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="posterous"><b>'.T_('Posterous').'</b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" name="posterous" name="posterous" size="50" value="'.cleanOutput($row['posterous']).'"/><br/>
                            '.T_('Enter the account name for your blog.').'<br/>
                            Ex: bob123.posterous.com use bob123'.'
                            '.$posterousManualImport.'
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>
                </form>';
    }

    /**
     * displayMessageBoard.
     *
     * @return void
     */
    public function displayMessageBoard()
    {
        $sql = "SELECT `boardsort`
                FROM `fcms_user_settings`
                WHERE `user` = '".$this->fcmsUser->id."'";

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        // Messageboard Sort
        $boardsort_list = [
            'ASC'  => T_('New Messages at Bottom'),
            'DESC' => T_('New Messages at Top'),
        ];
        $boardsort_options = buildHtmlSelectOptions($boardsort_list, $row['boardsort']);

        echo '
                <script type="text/javascript" src="ui/js/livevalidation.js"></script>
                <form id="frm" action="settings.php?view=messageboard" method="post">
                <fieldset>
                    <legend><span>'.T_('Message Board').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="boardsort"><b>'.T_('Sort Messages').'</b></label></div>
                        <div class="field-widget">
                            <select name="boardsort" id="boardsort" title="'.T_('What order do you want new messages to display?').'">
                                '.$boardsort_options.'
                            </select>
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>
                </form>';
    }

    /**
     * displayWhereIsEveryone.
     *
     * Displays the form for linking your foursquare account.
     *
     * @return void
     */
    public function displayWhereIsEveryone()
    {
        $sql = "SELECT `fs_user_id`, `fs_access_token`
                FROM `fcms_user_settings`
                WHERE `user` = '".$this->fcmsUser->id."'";
        if (!$this->db->query($sql))
        {
            $this->fcmsError->displayError();

            return;
        }

        $row = $this->db->get_row();

        $link = '';
        if (!empty($row['fs_user_id']))
        {
            $sql = 'SELECT `fs_client_id`, `fs_client_secret`, `fs_callback_url`
                    FROM `fcms_config`
                    LIMIT 1';

            $r = $this->fcmsDatabase->getRow($sql);
            if ($r === false)
            {
                $this->fcmsError->displayError();

                return;
            }

            if (empty($r))
            {
                echo '
                    <p class="error-alert">'.T_('No configuration data found.').'</p>';

                return;
            }

            $id = cleanOutput($r['fs_client_id']);
            $secret = cleanOutput($r['fs_client_secret']);
            $url = cleanOutput($r['fs_callback_url']);

            $url = 'https://foursquare.com/oauth2/authenticate?client_id='.$id.'&response_type=code&redirect_uri='.$url;
            $link = '<p><a class="foursquare" href="'.$url.'">'.T_('Click here to link your account to foursquare.').'</a></p>';
        }

        echo '
                <form id="frm" action="settings.php?view=whereiseveryone" method="post">
                <fieldset>
                    <legend><span>'.T_('Foursquare').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="id"><b>'.T_('Foursquare ID').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="id" name="id" size="20" value="'.cleanOutput($row['fs_user_id']).'"/>
                            '.$link.'
                        </div>
                    </div>
                    <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </fieldset>
                </form>';
    }

    /**
     * getAccessLevelDescription.
     *
     * @param int $access
     *
     * @return string
     */
    public function getAccessLevelDescription($access)
    {
        $access = (int) $access;

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
                    .T_('You can only add news to Family News.');
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

    /**
     * getThemeData.
     *
     * @param string $file
     *
     * @return void
     */
    public function getThemeData($file)
    {
        $file = basename($file);

        $data = [
            'file'      => $file,
            'name'      => '',
            'desc'      => '',
            'size'      => '',
            'updated'   => '',
            'author'    => '',
        ];

        if (!file_exists(THEMES."$file/README"))
        {
            $data['name'] = $file;

            return $data;
        }

        $f = @fopen(THEMES."$file/README", 'r');
        if (!$f)
        {
            $data['name'] = $file;

            return $data;
        }

        // name
        $name = fgets($f, 1000);
        $name = explode(':', $name);
        $name = end($name);
        $name = trim($name);

        $data['name'] = $name;

        // description
        $desc = fgets($f, 1000);
        $desc = explode(':', $desc);
        $desc = end($desc);
        $desc = trim($desc);

        $data['desc'] = $desc;

        // size
        $size = fgets($f, 1000);
        $size = explode(':', $size);
        $size = end($size);
        $size = trim($size);

        $data['size'] = $size;

        // compatible
        $version = fgets($f, 1000);

        // updated
        $updated = fgets($f, 1000);
        $updated = explode(':', $updated);
        $updated = end($updated);
        $updated = trim($updated);

        $data['updated'] = $updated;

        // author
        $author = fgets($f, 1000);
        $author = explode(':', $author);
        $author = end($author);
        $author = trim($author);

        $data['author'] = $author;

        return $data;
    }
}
